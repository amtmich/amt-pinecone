<?php

declare(strict_types=1);

return
    [
        Amt\AmtPinecone\Domain\Model\Pinecone::class => [
            'tableName' => 'tx_amt_pinecone_pineconeindex',
        ],
        Amt\AmtPinecone\Domain\Model\PineconeConfigIndex::class => [
            'tableName' => 'tx_amt_pinecone_configindex',
        ],
    ];
