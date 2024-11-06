<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PineconeConfigIndexRepository
{
    public function getRecordColumnsIndex($tableName): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_amt_pinecone_configindex');
        return $queryBuilder
            ->select('columns_index')
            ->from('tx_amt_pinecone_configindex')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
