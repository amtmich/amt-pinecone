<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Domain\Repository;

use Amt\AmtPinecone\Domain\Model\PineconeConfigIndex;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<PineconeConfigIndex>
 */
class PineconeConfigIndexRepository extends Repository
{
    public const TABLENAME_CONFIG = 'tx_amt_pinecone_configindex';
    private ConnectionPool $connectionPool;
    private QueryBuilder $pineconeConfigIndexRepositoryQueryBuilder;

    public function __construct(ConnectionPool $connectionPool, QueryBuilder $pineconeConfigIndexRepositoryQueryBuilder)
    {
        parent::__construct();
        $this->connectionPool = $connectionPool;
        $this->pineconeConfigIndexRepositoryQueryBuilder = $pineconeConfigIndexRepositoryQueryBuilder;
    }

    /**
     * @return array<int,array<string,string>>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRecord(string $tableName, string $recordPid): array
    {
        list($queryBuilder, $expr) = $this->prepareQueryBuilder();

        $conditions =
            [
                $expr->or(
                    $expr->like('record_pid', $queryBuilder->createNamedParameter($recordPid.',%')),
                    $expr->like('record_pid', $queryBuilder->createNamedParameter('%,'.$recordPid.',%')),
                    $expr->like('record_pid', $queryBuilder->createNamedParameter('%,'.$recordPid)),
                    $expr->eq('record_pid', $queryBuilder->createNamedParameter($recordPid))
                ),
            ];

        $queryBuilder
            ->select('*')
            ->from(self::TABLENAME_CONFIG)
            ->where(
                ...$conditions
            )
            ->andWhere($expr->eq('tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)),
            );

        return $queryBuilder->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array<int,array<string,string>>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRecordIfRecordPidIsEmpty(string $tableName): array
    {
        $conditions = [];
        list($queryBuilder, $expr) = $this->prepareQueryBuilder();

        $conditions[] = $expr->eq('tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR));

        $queryBuilder
            ->select('*')
            ->from(self::TABLENAME_CONFIG)
            ->where(
                ...$conditions
            );

        return $queryBuilder->executeQuery()
            ->fetchAllAssociative();
    }

    public function findUidByTableName(string $tableName): int
    {
        return $this->findOneBy(['tablename' => $tableName])->getUid();
    }

    /**
     * @return array<mixed>
     */
    public function prepareQueryBuilder(): array
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLENAME_CONFIG);
        $queryBuilder = $connection->createQueryBuilder();
        $expr = $this->pineconeConfigIndexRepositoryQueryBuilder->expr();

        return [$queryBuilder, $expr];
    }

    public function findOneByTableName(string $tableName): ?PineconeConfigIndex
    {
        return $this->findOneBy(['tablename' => $tableName]);
    }
}
