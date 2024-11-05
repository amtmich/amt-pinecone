<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Pinecone extends AbstractEntity
{
    protected bool $deleted = false;
    protected string $uidPinecone = '';
    protected int $recordUid = 0;
    protected string $tablename = '';
    protected int $isIndexed = 0;
    protected ?\DateTime $indexedTimestamp = null;

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getUidPinecone(): string
    {
        return $this->uidPinecone;
    }

    public function setUidPinecone(string $uidPinecone): void
    {
        $this->uidPinecone = $uidPinecone;
    }

    public function getRecordUid(): int
    {
        return $this->recordUid;
    }

    public function setRecordUid(int $recordUid): void
    {
        $this->recordUid = $recordUid;
    }

    public function getTablename(): string
    {
        return $this->tablename;
    }

    public function setTablename(string $tablename): void
    {
        $this->tablename = $tablename;
    }

    public function getIsIndexed(): int
    {
        return $this->isIndexed;
    }

    public function setIsIndexed(int $isIndexed): void
    {
        $this->isIndexed = $isIndexed;
    }

    public function getIndexedTimestamp(): ?\DateTime
    {
        return $this->indexedTimestamp;
    }

    public function setIndexedTimestamp(?\DateTime $indexedTimestamp): void
    {
        $this->indexedTimestamp = $indexedTimestamp;
    }
}
