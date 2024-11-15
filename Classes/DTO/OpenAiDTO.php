<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\DTO;

class OpenAiDTO
{
    private ?string $apiKey;
    private ?string $modelForEmbeddings;
    private int $usedTokens;
    private int $tokenLimit;
    private float $percentageTokensUsed;
    private int $availableTokens;
    private bool $validateApiKey;
    private bool $validateModel;

    public function __construct(
        ?string $apiKey,
        ?string $modelForEmbeddings,
        int $usedTokens,
        int $tokenLimit,
        float $percentageTokensUsed,
        int $availableTokens,
        bool $validateApiKey,
        bool $validateModel,
    ) {
        $this->apiKey = $apiKey;
        $this->modelForEmbeddings = $modelForEmbeddings;
        $this->usedTokens = $usedTokens;
        $this->tokenLimit = $tokenLimit;
        $this->percentageTokensUsed = $percentageTokensUsed;
        $this->availableTokens = $availableTokens;
        $this->validateApiKey = $validateApiKey;
        $this->validateModel = $validateModel;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getModelForEmbeddings(): ?string
    {
        return $this->modelForEmbeddings;
    }

    public function setModelForEmbeddings(string $modelForEmbeddings): void
    {
        $this->modelForEmbeddings = $modelForEmbeddings;
    }

    public function getUsedTokens(): int
    {
        return $this->usedTokens;
    }

    public function setUsedTokens(int $usedTokens): void
    {
        $this->usedTokens = $usedTokens;
    }

    public function getTokenLimit(): int
    {
        return $this->tokenLimit;
    }

    public function setTokenLimit(int $tokenLimit): void
    {
        $this->tokenLimit = $tokenLimit;
    }

    public function getPercentageTokensUsed(): float
    {
        return $this->percentageTokensUsed;
    }

    public function setPercentageTokensUsed(float $percentageTokensUsed): void
    {
        $this->percentageTokensUsed = $percentageTokensUsed;
    }

    public function getAvailableTokens(): int
    {
        return $this->availableTokens;
    }

    public function setAvailableTokens(int $availableTokens): void
    {
        $this->availableTokens = $availableTokens;
    }

    public function isValidateApiKey(): bool
    {
        return $this->validateApiKey;
    }

    public function setValidateApiKey(bool $validateApiKey): void
    {
        $this->validateApiKey = $validateApiKey;
    }

    public function isValidateModel(): bool
    {
        return $this->validateModel;
    }

    public function setValidateModel(bool $validateModel): void
    {
        $this->validateModel = $validateModel;
    }
}
