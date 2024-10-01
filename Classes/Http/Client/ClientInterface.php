<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Http\Client;

interface ClientInterface
{
    public function getTestApiCall(): \stdClass;

    public function validateResponse($response): \stdClass;
}
