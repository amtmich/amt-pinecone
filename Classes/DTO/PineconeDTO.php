<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\DTO;

class PineconeDTO
{
    private ?string $apiKey;
    private ?string $optionalHost;
    private ?string $indexName;
    private array $allIndexes;
    private bool $validateApiKey;
    private bool $validateIndexName;
    private array $indexingProgress;
    private array $nonExistsTables;

    public function __construct(
        ?string $apiKey,
        ?string $optionalHost,
        ?string $indexName,
        array   $allIndexes,
        bool    $validateApiKey,
        bool    $validateIndexName,
        array   $indexingProgress,
        array   $nonExistsTables
    )
    {
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

    public function getAllIndexes(): array
    {
        return $this->allIndexes;
    }

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

    public function getIndexingProgress(): array
    {
        return $this->indexingProgress;
    }

    public function setIndexingProgress(array $indexingProgress): void
    {
        $this->indexingProgress = $indexingProgress;
    }

    public function getNonExistsTables(): array
    {
        return $this->nonExistsTables;
    }

    public function setNonExistsTables(array $nonExistsTables): void
    {
        $this->nonExistsTables = $nonExistsTables;
    }
}
