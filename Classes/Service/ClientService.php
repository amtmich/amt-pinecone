<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Service;

use Amt\AmtPinecone\Domain\Repository\PineconeConfigIndexRepository;
use Amt\AmtPinecone\Domain\Repository\PineconeRepository;
use Amt\AmtPinecone\Http\Client\OpenAiClient;
use Amt\AmtPinecone\Http\Client\PineconeClient;
use Amt\AmtPinecone\Utility\StringUtility;
use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClientService
{
    protected OpenAiClient $openAiClient;
    protected PineconeClient $pineconeClient;
    protected PineconeRepository $pineconeRepository;
    protected PineconeConfigIndexRepository $pineconeConfigIndexRepository;

    public function __construct(OpenAiClient $openAiClient, PineconeClient $pineconeClient, PineconeRepository $pineconeRepository, PineconeConfigIndexRepository $pineconeConfigIndexRepository)
    {
        $this->openAiClient = $openAiClient;
        $this->pineconeClient = $pineconeClient;
        $this->pineconeRepository = $pineconeRepository;
        $this->pineconeConfigIndexRepository = $pineconeConfigIndexRepository;
    }

    public function getResultQueryByParams(string $text, int $count, string $table): \stdClass
    {
        return $this->pineconeClient->queryResult($this->openAiClient->generateEmbedding($text), $count, $table);
    }

    /**
     * @return array<int,array<string,string|float|int>>
     */
    public function getIndexingProgress(): array
    {
        $tablesToIndex = $this->getTablesToIndex();
        $indexingProgress = [];

        foreach ($tablesToIndex as $record) {
            $tableName = (string) $record['tablename'];
            if (!$this->doesTableExist($tableName)) {
                $this->sendFlashMessage('Please check records configuration and correct table name.');
                continue;
            }
            $totalRecords = $this->getTotalRecords($tableName);
            $indexedRecords = $this->pineconeRepository->getIndexedRecordsCount($tableName);
            $progress = ($totalRecords > 0) ? ($indexedRecords / $totalRecords) * 100 : 0;

            $indexingProgress[] = [
                'uidTable' => $this->pineconeConfigIndexRepository->findUidByTableName($tableName),
                'tableName' => $tableName,
                'totalRecords' => $totalRecords,
                'indexedRecords' => $indexedRecords,
                'progress' => round($progress),
            ];
        }

        return $indexingProgress;
    }

    public function doesTableExist(string $tableName): bool
    {
        $schemaManager = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tableName)
            ->createSchemaManager();

        return $schemaManager->tablesExist([$tableName]);
    }

    public function sendFlashMessage(string $message): void
    {
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            '',
            ContextualFeedbackSeverity::ERROR,
            true
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($message);
    }

    /**
     * @return array<array<string,int|string>>
     */
    public function getNonExistsTables(): array
    {
        $tablesToIndex = $this->getTablesToIndex();
        $nonExistsTables = [];
        foreach ($tablesToIndex as $record) {
            $tableName = (string) $record['tablename'];
            if (!$this->doesTableExist($tableName)) {
                $this->sendFlashMessage('Please check records configuration and correct table name.');
                $nonExistsTables[] =
                    [
                        'uid' => $record['uid'],
                        'tablename' => $tableName,
                    ];
            }
        }

        return $nonExistsTables;
    }

    /**
     * @return array<int,string>
     */
    public function compareLocalToPinecone(): array
    {
        return array_diff(array_column($this->pineconeClient->getVectorsList(), 'id'), $this->pineconeRepository->getPineconeRecordsUids());
    }

    /**
     * @return array<int,string>
     */
    public function findDetachedRecordsInPinecone(): array
    {
        return array_diff($this->pineconeRepository->getPineconeRecordsUids(), array_column($this->pineconeClient->getVectorsList(), 'id'));
    }

    public function indexRecordsToPinecone(string $tableName, int $batchSize): void
    {
        $offset = 0;

        do {
            $records = $this->pineconeRepository->fetchRecords($tableName, $batchSize, $offset);
            $offset += $batchSize;

            foreach ($records as $record) {
                $embedding = $this->getEmbeddingFromRecord($record, $tableName);
                if ([] !== $embedding) {
                    $uidPinecone = StringUtility::concatString($tableName, (string) $record['uid']);
                    $indexData = [
                        'id' => $uidPinecone,
                        'values' => $embedding,
                        'metadata' => [
                            'tablename' => $tableName,
                            'uid' => $record['uid'],
                        ],
                    ];
                    $jsonData = $this->pineconeClient->serializeData(
                        [
                            'vectors' => $indexData,
                        ],
                    );
                    $result = $this->pineconeClient->validateResponse($this->pineconeClient->sendRequest($this->pineconeClient->getRequestHeader(), '/vectors/upsert', 'POST', $jsonData, $this->pineconeClient->getOptionalHost()));
                    if ($result->upsertedCount > 0) {
                        $this->pineconeRepository->saveIndexedRecord($record['uid'], $tableName, $uidPinecone);
                    }
                }
            }
        } while (count($records) > 0);
    }

    /**
     * @param array<string,string|int> $record
     *
     * @return array<int,float|int>
     *
     * @throws \Exception
     */
    public function getEmbeddingFromRecord(array $record, string $tableName): array
    {
        $indexFieldsDefault = $this->getSearchFields($tableName);
        $concatenatedFields = '';
        $indexConfigRecord = $this->pineconeConfigIndexRepository->getRecord($tableName, (string) $record['pid']) ?: $this->pineconeConfigIndexRepository->getRecordIfRecordPidIsEmpty($tableName);
        $indexFieldsFromConfiguration = $this->getFilterFieldsConfig($indexConfigRecord[0]['columns_index'], $indexFieldsDefault);

        foreach ($indexFieldsDefault as $field) {
            if (!isset($record[$field]) || !in_array($field, $indexFieldsFromConfiguration)) {
                continue;
            }
            $concatenatedFields .= ' '.strip_tags((string) $record[$field]);
        }
        $indexConfigRecordValues = $this->explodeString($indexConfigRecord['0']['record_pid']);

        return $this->generateEmbeddingForRecord($indexConfigRecordValues, $record['pid'], $indexConfigRecord['0']['record_pid'], $concatenatedFields);
    }

    /**
     * @return array<array<string,int|string>>
     */
    public function getTablesToIndex(): array
    {
        return $this->pineconeRepository->fetchTablesToIndex();
    }

    public function getIndexedRecordsCount(): int
    {
        $totalIndexedRecords = 0;
        $indexedRecords = $this->getIndexingProgress();
        foreach ($indexedRecords as $indexedRecord) {
            $totalIndexedRecords += $indexedRecord['indexedRecords'];
        }

        return $totalIndexedRecords;
    }

    public function checkDataIntegrityStatus(int $pineconeIndexedRecords): bool
    {
        return $this->getIndexedRecordsCount() === $pineconeIndexedRecords;
    }

    public function getPercentageTokensUsed(int $usedTokens, int $openAiTokensLimit): float
    {
        return min(100, ($usedTokens / $openAiTokensLimit) * 100);
    }

    public function deleteRecordsByMissingConfig(): void
    {
        $tableNames = [];
        $configRecords = $this->pineconeConfigIndexRepository->findAll();

        foreach ($configRecords as $configRecord) {
            $tableNames[] =
                $configRecord->getTablename();
        }

        $recordsToDelete = $this->pineconeRepository->getRecordsWithInvalidConfiguration($tableNames);
        $this->pineconeRepository->deleteByTableNames($tableNames);
        $this->pineconeClient->vectorsDelete($recordsToDelete);
    }

    public function deleteRecordsWithModifiedPid(string $tableName): void
    {
        $recordsToDelete = $this->getRecordsWithPidNotInConfigRepository($tableName);
        $uidsToDelete = [];

        foreach ($recordsToDelete as $record) {
            $uidsToDelete[] = StringUtility::concatString($tableName, (string) $record['uid']);
        }

        $this->pineconeRepository->deleteByUids($uidsToDelete);
        $this->pineconeClient->vectorsDelete($uidsToDelete);
    }

    private function getTotalRecords(string $tableName): int
    {
        $configRepository = $this->pineconeConfigIndexRepository->findOneByTableName($tableName);
        $configRepositoryRecordPid = $configRepository->getRecordPid();
        if ('empty' === $configRepositoryRecordPid) {
            return $this->getTotalRecordsIfPidsEmpty($tableName);
        }

        $queryBuilder = $this->getConnectionForTable($tableName);
        $configRepositoryPids = $this->explodeString($configRepositoryRecordPid);
        $count = 0;

        foreach ($configRepositoryPids as $configRepositoryPid) {
            $count += (int) $queryBuilder->count('uid')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter((int) $configRepositoryPid, \PDO::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();
        }

        return $count;
    }

    /**
     * @return array<mixed>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function getRecordsWithPidNotInConfigRepository(string $tableName): array
    {
        $configRepository = $this->pineconeConfigIndexRepository->findOneByTableName($tableName);

        if ('empty' === $configRepository->getRecordPid()) {
            return [];
        }

        $configRepositoryRecordPid = $configRepository->getRecordPid();
        $queryBuilder = $this->getConnectionForTable($tableName);
        $configRepositoryPids = array_map('intval', $this->explodeString($configRepositoryRecordPid));

        $notInCondition = $queryBuilder->expr()->notIn(
            'pid',
            $queryBuilder->createNamedParameter($configRepositoryPids, ArrayParameterType::INTEGER)
        );

        return $queryBuilder->select('*')
            ->from($tableName)
            ->where($notInCondition)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function getTotalRecordsIfPidsEmpty(string $tableName): int
    {
        $queryBuilder = $this->getConnectionForTable($tableName);

        return (int) $queryBuilder->count('uid')
            ->from($tableName)
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return array<int,string>
     */
    private function getSearchFields(string $tableName): array
    {
        $tca = $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'] ?? '';

        return GeneralUtility::trimExplode(',', $tca, true);
    }

    /**
     * @return array<int,string>
     */
    private function explodeString(string $text, string $separator = ','): array
    {
        return explode($separator, $text);
    }

    /**
     * @param array<int,string> $indexConfigRecordValues
     *
     * @return array|float[]|int[]|null
     *
     * @throws \Exception
     */
    private function generateEmbeddingForRecord(array $indexConfigRecordValues, int|string $recordPid, string $configRecordPid, string $concatenatedFields): ?array
    {
        foreach ($indexConfigRecordValues as $indexConfigRecordValue) {
            $indexConfigRecordValue = (int) $indexConfigRecordValue;
            if ($indexConfigRecordValue === (int) $recordPid || 'empty' === $configRecordPid) {
                return $this->openAiClient->generateEmbedding($concatenatedFields);
            }
        }

        return [];
    }

    /**
     * @param array<int,string> $indexFieldsDefault
     *
     * @return array|string[]
     */
    private function getFilterFieldsConfig(?string $indexFieldsConfiguration, array $indexFieldsDefault): array
    {
        if (null === $indexFieldsConfiguration) {
            $indexFieldsConfiguration = '';
        }
        $indexFieldsFromConfiguration = array_filter($this->explodeString($indexFieldsConfiguration));

        return [] === $indexFieldsFromConfiguration ? $indexFieldsDefault : $indexFieldsFromConfiguration;
    }

    private function getConnectionForTable(string $tableName): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        return $queryBuilder;
    }
}
