<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\DTO;

class PineconeDTO
{
    private ?string $apiKey;
    private ?string $optionalHost;
    private ?string $indexName;

    /**
     * @var array<int,array<string,string>>
     */
    private array $allIndexes;
    private bool $validateApiKey;
    private bool $validateIndexName;

    /**
     * @var array<int,array<string,int|string|float>>
     */
    private array $indexingProgress;

    /**
     * @var array<int,array<string,int|string>>
     */
    private array $nonExistsTables;

    /**
     * @param array<array<string,string>>               $allIndexes
     * @param array<int,array<string,int|string|float>> $indexingProgress
     * @param array<int,array<string,int|string>>       $nonExistsTables
     */
    public function __construct(
        ?string $apiKey,
        ?string $optionalHost,
        ?string $indexName,
        array $allIndexes,
        bool $validateApiKey,
        bool $validateIndexName,
        array $indexingProgress,
        array $nonExistsTables,
    ) {
        $this->apiKey = $apiKey;
        $this->optionalHost = $optionalHost;
        $this->indexName = $indexName;
        $this->allIndexes = $allIndexes;
        $this->validateApiKey = $validateApiKey;
        $this->validateIndexName = $validateIndexName;
        $this->indexingProgress = $indexingProgress;
        $this->nonExistsTables = $nonExistsTables;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getOptionalHost(): ?string
    {
        return $this->optionalHost;
    }

    public function setOptionalHost(string $optionalHost): void
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

    /**
     * @return array<int,array<string,string>>
     */
    public function getAllIndexes(): array
    {
        return $this->allIndexes;
    }

    /**
     * @param array<int,array<string,string>> $allIndexes
     */
    public function setAllIndexes(array $allIndexes): void
    {
        $this->allIndexes = $allIndexes;
    }

    public function isValidateApiKey(): bool
    {
        return $this->validateApiKey;
    }

    public function setValidateApiKey(bool $validateApiKey): void
    {
        $this->validateApiKey = $validateApiKey;
    }

    public function isValidateIndexName(): bool
    {
        return $this->validateIndexName;
    }

    public function setValidateIndexName(bool $validateIndexName): void
    {
        $this->validateIndexName = $validateIndexName;
    }

    /**
     * @return array<int,array<string,int|string|float>>
     */
    public function getIndexingProgress(): array
    {
        return $this->indexingProgress;
    }

    /**
     * @param array<int,array<string,int|string|float>> $indexingProgress
     */
    public function setIndexingProgress(array $indexingProgress): void
    {
        $this->indexingProgress = $indexingProgress;
    }

    /**
     * @return array<int,array<string,int|string>>
     */
    public function getNonExistsTables(): array
    {
        return $this->nonExistsTables;
    }

    /**
     * @param array<int,array<string,int|string>> $nonExistsTables
     */
    public function setNonExistsTables(array $nonExistsTables): void
    {
        $this->nonExistsTables = $nonExistsTables;
    }
}
