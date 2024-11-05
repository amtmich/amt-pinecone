<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Service;

use Amt\AmtPinecone\Domain\Repository\PineconeRepository;
use \Amt\AmtPinecone\Http\Client\OpenAiClient;
use \Amt\AmtPinecone\Http\Client\PineconeClient;
use Amt\AmtPinecone\Utility\StringUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;

class ClientService
{
    protected OpenAiClient $openAiClient;
    protected PineconeClient $pineconeClient;
    protected PineconeRepository $pineconeRepository;

    public function __construct(OpenAiClient $openAiClient, PineconeClient $pineconeClient, PineconeRepository $pineconeRepository)
    {
        $this->openAiClient = $openAiClient;
        $this->pineconeClient = $pineconeClient;
        $this->pineconeRepository = $pineconeRepository;
    }

    public function getResultQueryByParams(string $text, int $count, string $table): \stdClass
    {
        return $this->pineconeClient->queryResult($this->openAiClient->generateEmbedding($text), $count, $table);
    }

    public function getIndexingProgress(): array
    {
        $tablesToIndex = $this->getTablesToIndex();
        $indexingProgress = [];

        foreach ($tablesToIndex as $record) {
            $tableName = $record['tablename'];
            if (!$this->doesTableExist($tableName)) {
                $this->sendFlashMessage('Please check records configuration and correct table name.');
                continue;
            }
            $totalRecords = $this->getTotalRecords($tableName);
            $indexedRecords = $this->pineconeRepository->getIndexedRecordsCount($tableName);
            $progress = ($totalRecords > 0) ? ($indexedRecords / $totalRecords) * 100 : 0;

            $indexingProgress[] = [
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
     * @return array<int,string>
     */
    public function getNonExistsTables(): array
    {
        $tablesToIndex = $this->getTablesToIndex();
        $nonExistsTables = [];
        foreach ($tablesToIndex as $record) {
            $tableName = $record['tablename'];
            if (!$this->doesTableExist($tableName)) {
                $this->sendFlashMessage('Please check records configuration and correct table name.');
                $nonExistsTables[] =
                    [
                        'uid' => $record['uid'],
                        'tablename' => $tableName
                    ];
            }
        }

        return $nonExistsTables;
    }

    public function compareLocalToPinecone(): array
    {
        return array_diff(array_column($this->pineconeClient->getVectorsList(), 'id'), $this->pineconeRepository->getPineconeRecordsUids());
    }

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
                if ($embedding) {
                    $uidPinecone = StringUtility::concatString($tableName, (string)$record['uid']);
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
                            'vectors' => $indexData
                        ],
                    );
                    $result = $this->pineconeClient->validateResponse($this->pineconeClient->sendRequest($this->pineconeClient->getRequestHeader(), "/vectors/upsert", 'POST', $jsonData, $this->pineconeClient->getOptionalHost()));
                    if ($result) {
                        $this->pineconeRepository->saveIndexedRecord($record['uid'], $tableName, $uidPinecone);
                    }
                }
            }

        } while (count($records) > 0);
    }

    public function getEmbeddingFromRecord(array $record, string $tableName): ?array
    {
        $fields = $this->getSearchFields($tableName);
        $concatenatedFields = '';

        foreach ($fields as $field) {
            if (isset($record[$field])) {
                $concatenatedFields .= ' ' . strip_tags($record[$field]);
            }
        }

        return $this->openAiClient->generateEmbedding($concatenatedFields);
    }

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

    private function getTotalRecords(string $tableName): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        return (int)$queryBuilder->count('uid')
            ->from($tableName)
            ->executeQuery()
            ->fetchOne();
    }

    private function getSearchFields(string $tableName): array
    {
        $tca = $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'] ?? '';
        return GeneralUtility::trimExplode(',', $tca, true);
    }
}
