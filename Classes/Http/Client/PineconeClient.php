<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Http\Client;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class PineconeClient extends BaseClient
{
    protected const CLIENT_API_KEY = 'pineconeApiKey';
    protected const OPTIONAL_HOST_KEY = 'pineconeOptionalHost';
    protected const INDEX_NAME = 'pineconeIndexName';
    protected string $clientApiKeyValue = '';
    protected string $baseUrl = 'https://api.pinecone.io/';
    protected ?string $optionalHost = '';
    protected ?string $indexName = null;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        parent::__construct();
        $this->clientApiKeyValue = $extensionConfiguration->get('amt_pinecone')[self::CLIENT_API_KEY] ?? '';
        $this->indexName = $extensionConfiguration->get('amt_pinecone')[self::INDEX_NAME] ?? '';
        $this->optionalHost = $this->getOptionalHostFromApi();
    }

    public function getOptionalHost(): ?string
    {
        return $this->optionalHost;
    }

    public function setOptionalHost(?string $optionalHost): void
    {
        $this->optionalHost = $optionalHost;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(string $indexName): void
    {
        $this->indexName = $indexName;
    }

    public function validateResponse($response): \stdClass
    {
        if (!is_string($response)) {
            throw new \Exception('Error, please try again later.');
        }
        $response = $this->decodeData($response);

        if ($response->error ?? null) {
            throw new \Exception($response->error->message);
        }

        if (is_null($response)) {
            throw new \Exception('Error, please provide a valid Pinecone API key and index name.');
        }

        return $response;
    }

    public function getTestApiCall(): \stdClass
    {
        $response = $this->validateResponse($this->sendRequest($this->getRequestHeader(), 'indexes', 'GET'));

        return $response;
    }

    public function createIndex(string $indexName, int $indexDimensions = 1536, string $metric = 'cosine'): \stdClass
    {
        $indexName = $this->getNewIndexNameIfEmpty($indexName);
        $data = [
            'name' => $indexName,
            'dimension' => $indexDimensions,
            'metric' => $metric,
            'spec' => [
                'serverless' => [
                    'cloud' => 'aws',
                    'region' => 'us-east-1'
                ]
            ]
        ];
        $response = $this->validateResponse($this->sendRequest($this->getRequestHeader(), 'indexes', 'POST', $this->serializeData($data)));

        return $response;
    }

    public function getAllIndexes(): array
    {
        $response = $this->sendRequest($this->getRequestHeader(), 'indexes', 'GET');
        $dataResponse = json_decode($response, true);
        $allIndexes = [];

        foreach ($dataResponse as $indexes) {
            foreach ($indexes as $index) {
                $allIndexes[] = [
                    'name' => $index['name'],
                    'host' => $index['host'],
                ];
            }
        }

        return $allIndexes;
    }

    /**
     * @return array<string>
     */
    public function getRequestHeader(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Api-Key' => $this->clientApiKeyValue,
            'X-Pinecone-API-Version: 2024-07',
        ];
    }

    public function queryResult(?array $embeddings, int $count, string $table): \stdClass
    {
        if (empty($embeddings)) {
            return new \stdClass();
        }
        if (!empty($table)) {
            $filters = [
                'tablename' =>
                    [
                        '$eq' => $table
                    ],
            ];
        }
        $data = [
            'topK' => $count,
            'includeMetadata' => true,
            'vector' => $embeddings,
            'filter' => $filters
        ];

        $response = $this->validateResponse($this->sendRequest($this->getRequestHeader(), '/query', 'POST', $this->serializeData($data), $this->optionalHost));

        return $response;
    }

    public function validateIndexProvidedByUser(): bool
    {
        $allIndexes = $this->getAllIndexes();
        foreach ($allIndexes as $index) {
            if ($index['name'] === $this->indexName) {
                return true;
            }
        }
        return false;
    }

    public function getOptionalHostFromApi(): string
    {
        if ($this->validateIndexProvidedByUser()) {
            $response = $this->validateResponse($this->sendRequest($this->getRequestHeader(), 'indexes/' . $this->indexName, 'GET'));
            $this->setOptionalHost("https://".$response->host);
        }

        return $this->getOptionalHost();
    }

    private function getNewIndexNameIfEmpty(string $indexName): string
    {
        if (empty($indexName)) {
            $indexName = 'index' . rand(0, 100);
            $this->setIndexName($indexName);
        }

        return $this->getIndexName();
    }
}
