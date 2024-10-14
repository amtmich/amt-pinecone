<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Service;

use \Amt\AmtPinecone\Http\Client\OpenAiClient;
use \Amt\AmtPinecone\Http\Client\PineconeClient;
use Amt\AmtPinecone\Utility\ClientUtility;
use TYPO3\CMS\Core\Registry;
use \TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClientService
{
    protected OpenAiClient $openAiClient;
    protected PineconeClient $pineconeClient;
    protected Registry $registry;

    public function __construct(OpenAiClient $openAiClient, PineconeClient $pineconeClient, Registry $registry)
    {
        $this->openAiClient = $openAiClient;
        $this->pineconeClient = $pineconeClient;
        $this->registry = $registry;
    }

    public function indexRecordsToPinecone(string $tableName, int $batchSize): void
    {
        $offset = 0;

        do {
            $records = $this->fetchRecords($tableName, $batchSize, $offset);
            $offset += $batchSize;

            foreach ($records as $record) {
                $embedding = $this->getEmbeddingFromRecord($record, $tableName);
                if ($embedding) {
                    $indexData = [
                        'id' => StringUtility::getUniqueId('uid' . $record['uid']),
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
                        $this->storeIndexedRecord($record['uid'], $tableName);
                    }
                }
            }

        } while (count($records) > 0);
    }

    private function storeIndexedRecord(int $uid, string $tableName): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_amt_pinecone_pineconeindex');

        $queryBuilder
            ->insert('tx_amt_pinecone_pineconeindex')
            ->values([
                'record_uid' => $uid,
                'tablename' => $tableName,
                'is_indexed' => 1,
                'indexed_timestamp' => time(),
            ])
            ->executeStatement();
    }

    public function generateEmbedding(string $text): ?array
    {
        $configuration = ClientUtility::createExtensionConfigurationObject()->get('amt_pinecone');

        $data = [
            'input' => $text,
            'model' => $configuration['openAiModelForEmbeddings']
        ];
        $jsonData = $this->openAiClient->serializeData($data);
        $responseData = $this->openAiClient->validateResponse($this->openAiClient->sendRequest($this->openAiClient->getRequestHeader(), 'embeddings', 'POST', $jsonData));
        $this->sumUpUsedTokensOpenAi($responseData->usage->prompt_tokens);

        return $responseData->data[0]->embedding;
    }

    public function getResultQuery(array $embeddings): array
    {
        $results = $this->pineconeClient->queryResult($embeddings);

        return $results->matches;
    }

    private function fetchRecords(string $tableName, int $limit, int $offset): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
        $schemaManager = $connectionPool->getConnectionForTable($tableName)->createSchemaManager();
        $columns = $schemaManager->listTableColumns($tableName);
        $conditions = [];
        $secondConditions = [
            $queryBuilder->expr()->eq('t.uid', 'i.record_uid'),
            $queryBuilder->expr()->eq('i.tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('i.is_indexed', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
        ];

        if (isset($columns['deleted'])) {
            $conditions[] = $queryBuilder->expr()->eq('t.deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT));
        }

        $conditions[] = $queryBuilder->expr()->isNull('i.record_uid');
        $compositeExpression = $queryBuilder->expr()->and(
            ...$secondConditions
        );
        $joinCondition = (string)$compositeExpression;

        $query = $queryBuilder
            ->select('t.*')
            ->from($tableName, 't')
            ->leftJoin(
                't',
                'tx_amt_pinecone_pineconeindex',
                'i',
                $joinCondition
            )
            ->where(
                ...$conditions,
            )
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->executeQuery();

        return $query->fetchAllAssociative();
    }

    private function getSearchFields(string $tableName): array
    {
        $tca = $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'] ?? '';
        return GeneralUtility::trimExplode(',', $tca, true);
    }

    private function getEmbeddingFromRecord(array $record, string $tableName): ?array
    {
        $fields = $this->getSearchFields($tableName);
        $concatenatedFields = '';

        foreach ($fields as $field) {
            if (isset($record[$field])) {
                $concatenatedFields .= ' ' . strip_tags($record[$field]);
            }
        }

        return $this->generateEmbedding($concatenatedFields);
    }

    private function sumUpUsedTokensOpenAi(?int $usedTokens): void
    {
        if ($usedTokens) {
            $currentTotalTokens = $this->getTotalTokens();
            $updatedTotalTokens = $currentTotalTokens + $usedTokens;
            $this->registry->set('AmtPinecone', 'embeddings_prompt_tokens', $updatedTotalTokens);
        }
    }

    public function getTotalTokens()
    {
       return $this->registry->get('AmtPinecone', 'embeddings_prompt_tokens');
    }
}
