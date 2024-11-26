<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Domain\Repository;

use Amt\AmtPinecone\Domain\Model\Pinecone;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Pinecone>
 */
class PineconeRepository extends Repository
{
    public const TABLENAME = 'tx_amt_pinecone_pineconeindex';
    public const TABLENAME_CONFIG = 'tx_amt_pinecone_configindex';
    private ConnectionPool $connectionPool;
    private QueryBuilder $pineconeRepositoryQueryBuilder;

    public function __construct(ConnectionPool $connectionPool, QueryBuilder $pineconeRepositoryQueryBuilder)
    {
        parent::__construct();
        $this->connectionPool = $connectionPool;
        $this->pineconeRepositoryQueryBuilder = $pineconeRepositoryQueryBuilder;
    }

    /**
     * @return array<int,array<string,string|int>>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDetachedRecords(): array
    {
        $detachedRecords = [];
        $connection = $this->connectionPool->getConnectionForTable(self::TABLENAME);
        $arrayRelatedTables = [];

        $relatedTables = $this->findAll();

        if ($relatedTables instanceof QueryResultInterface) {
            $arrayRelatedTables = $relatedTables->toArray();
        }
        $uniqueTableNames = [];

        /** @var Pinecone $record */
        foreach ($arrayRelatedTables as $record) {
            $tableName = $record->getTableName();
            if (!in_array($tableName, $uniqueTableNames, true)) {
                $uniqueTableNames[] = $tableName;
            }
        }

        foreach ($uniqueTableNames as $tableName) {
            $secondConditions = [];
            $conditions = null;
            $queryBuilder = $this->prepareQueryBuilder($connection);
            $expr = $this->pineconeRepositoryQueryBuilder->expr();

            $connection = $this->connectionPool->getConnectionForTable($tableName);
            $schemaManager = $connection->createSchemaManager();
            $columns = $schemaManager->listTableColumns($tableName);
            $uidField = isset($columns['uid']) ? 'uid' : null;
            $deletedField = isset($columns['deleted']) ? 'deleted' : null;

            $secondConditions[] = $expr->eq(self::TABLENAME.'.record_uid', $tableName.'.'.$uidField);
            $compositeExpression = $queryBuilder->expr()->and(
                ...$secondConditions
            );
            $joinCondition = (string) $compositeExpression;
            $conditions[] = $expr->isNull($tableName.'.'.$uidField);

            if (null === $deletedField) {
                $conditions[] = $expr->eq(self::TABLENAME.'.tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR));
                $queryBuilder->where($expr->and(...$conditions));
            } else {
                $conditions[] = $expr->eq($tableName.'.'.$deletedField, $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT));
                $queryBuilder->where($expr->or(...$conditions));
                $queryBuilder->andWhere($expr->eq(self::TABLENAME.'.tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)));
            }

            $queryBuilder->leftJoin(
                self::TABLENAME,
                $tableName,
                $tableName,
                $joinCondition
            );

            $detachedRecords = array_merge($detachedRecords, $queryBuilder->executeQuery()->fetchAllAssociative());
        }

        return $detachedRecords;
    }

    /**
     * @param array<mixed> $recordsToDelete
     */
    public function hardDelete(array $recordsToDelete): void
    {
        if (empty($recordsToDelete)) {
            return;
        }

        $uidsToDelete = array_column($recordsToDelete, 'uid');

        $this->pineconeRepositoryQueryBuilder->delete(self::TABLENAME)
            ->where($this->pineconeRepositoryQueryBuilder->expr()->in('uid', $uidsToDelete));
        $this->pineconeRepositoryQueryBuilder->executeStatement();
    }

    /**
     * @param array<mixed> $recordsToDelete
     */
    public function softDelete(array $recordsToDelete): void
    {
        if (empty($recordsToDelete)) {
            return;
        }

        /*
         * @var array<int,array<string,int|string>> $recordsToDelete
         */
        foreach ($recordsToDelete as $recordToDelete) {
            if (!isset($recordToDelete['uid'])) {
                continue;
            }

            /**
             * @var Pinecone $record
             */
            $record = $this->findByUid((int) $recordToDelete['uid']);
            if (null !== $record) {
                $this->remove($record);
            }
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * @return array<int,string>
     */
    public function getPineconeRecordsUids(): array
    {
        $uidsPinecone = [];
        $arrayPineconeIndexRecords = [];
        $pineconeIndexRecords = $this->findAll();

        if ($pineconeIndexRecords instanceof QueryResultInterface) {
            $arrayPineconeIndexRecords = $pineconeIndexRecords->toArray();
        }

        /**
         * @var Pinecone $record
         */
        foreach ($arrayPineconeIndexRecords as $record) {
            $uidsPinecone[] = $record->getUidPinecone();
        }

        return $uidsPinecone;
    }

    /**
     * @param array<int,string> $uids
     */
    public function deleteByUids(array $uids): void
    {
        if (empty($uids)) {
            return;
        }
        $placeholders = array_map(fn ($uid) => $this->pineconeRepositoryQueryBuilder->createNamedParameter($uid, \PDO::PARAM_STR), $uids);

        $this->pineconeRepositoryQueryBuilder->delete(self::TABLENAME)
            ->where($this->pineconeRepositoryQueryBuilder->expr()->in('uid_pinecone', $placeholders));
        $this->pineconeRepositoryQueryBuilder->executeStatement();
    }

    /**
     * @return array<array<string|mixed>>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function fetchRecords(string $tableName, int $limit, int $offset): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $schemaManager = $this->connectionPool->getConnectionForTable($tableName)->createSchemaManager();
        $columns = $schemaManager->listTableColumns($tableName);
        $conditions = [];
        $secondConditions = [
            $queryBuilder->expr()->eq('t.uid', 'i.record_uid'),
            $queryBuilder->expr()->eq('i.tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('i.is_indexed', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
        ];

        if (isset($columns['deleted'])) {
            $conditions[] = $queryBuilder->expr()->eq('t.deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT));
        }

        $conditions[] = $queryBuilder->expr()->isNull('i.record_uid');
        $compositeExpression = $queryBuilder->expr()->and(
            ...$secondConditions
        );
        $joinCondition = (string) $compositeExpression;

        $query = $queryBuilder
            ->select('t.*')
            ->from($tableName, 't')
            ->leftJoin(
                't',
                self::TABLENAME,
                'i',
                $joinCondition
            )
            ->where(
                ...$conditions,
            )
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->executeQuery();

        return $query->fetchAllAssociative();
    }

    /**
     * @return array<array<string,int|string>>
     */
    public function fetchTablesToIndex(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLENAME_CONFIG);

        return $queryBuilder->select('*')
            ->from(self::TABLENAME_CONFIG)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function saveIndexedRecord(int $uid, string $tableName, string $uidPinecone): void
    {
        $object = new Pinecone();
        $object->setDeleted(false);
        $object->setUidPinecone($uidPinecone);
        $object->setRecordUid($uid);
        $object->setTablename($tableName);
        $object->setIsIndexed(1);
        $object->setIndexedTimestamp(new \DateTime());
        $this->add($object);
        $this->persistenceManager->persistAll();
    }

    public function getIndexedRecordsCount(string $tableName): int
    {
        return count($this->findBy(['tablename' => $tableName]));
    }

    /**
     * @param array<mixed> $tablesNames
     */
    public function deleteByTableNames(array $tablesNames): void
    {
        if (empty($tablesNames)) {
            return;
        }

        $placeholders = array_map(fn ($tableName) => $this->pineconeRepositoryQueryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR), $tablesNames);

        $this->pineconeRepositoryQueryBuilder->delete(self::TABLENAME)
            ->where($this->pineconeRepositoryQueryBuilder->expr()->notIn('tablename', $placeholders));
        $this->pineconeRepositoryQueryBuilder->executeStatement();
    }

    /**
     * @param array<mixed> $tablesNames
     *
     * @return array<mixed>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRecordsWithInvalidConfiguration(array $tablesNames): array
    {
        $placeholders = array_map(fn ($tableName) => $this->pineconeRepositoryQueryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR), $tablesNames);

        $this->pineconeRepositoryQueryBuilder->select('*')
            ->from(self::TABLENAME)
            ->where($this->pineconeRepositoryQueryBuilder->expr()->notIn('tablename', $placeholders));

        return $this->pineconeRepositoryQueryBuilder->executeQuery()->fetchAllAssociative();
    }

    private function prepareQueryBuilder(Connection $connection): QueryBuilder
    {
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $queryBuilder->select(self::TABLENAME.'.uid', self::TABLENAME.'.tablename', self::TABLENAME.'.record_uid')
            ->from(self::TABLENAME);

        return $queryBuilder;
    }
}
