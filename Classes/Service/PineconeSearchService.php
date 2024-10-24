<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Service;

class PineconeSearchService
{
    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function search(string $query, int $count, string $table): \stdClass
    {
        return $this->clientService->getResultQueryWithParams($query, $count, $table);
    }
}
