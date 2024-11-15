<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PineconeConfigIndexRepository
{
    public const TABLENAME_CONFIG = 'tx_amt_pinecone_configindex';

    /**
     * @return array<int,array<string,string>>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRecordColumnsIndex(string $tableName): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLENAME_CONFIG);

        return $queryBuilder
            ->select('columns_index')
            ->from(self::TABLENAME_CONFIG)
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function getUidByTablename(string $tableName): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLENAME_CONFIG);

        return $queryBuilder->select('uid')
            ->from(self::TABLENAME_CONFIG)
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR))
            )
            ->executeQuery()
            ->fetchOne();
    }
}
