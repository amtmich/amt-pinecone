<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class PineconeConfigIndex extends AbstractEntity
{
    protected ?\DateTime $createdAt = null;
    protected ?\DateTime $updatedAt = null;
    protected bool $deleted = false;
    protected string $tablename = '';
    protected string $columnsIndex = '';
    protected string $recordPid = '';

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getTablename(): string
    {
        return $this->tablename;
    }

    public function setTablename(string $tablename): void
    {
        $this->tablename = $tablename;
    }

    public function getColumnsIndex(): string
    {
        return $this->columnsIndex;
    }

    public function setColumnsIndex(string $columnsIndex): void
    {
        $this->columnsIndex = $columnsIndex;
    }

    public function getRecordPid(): string
    {
        return $this->recordPid;
    }

    public function setRecordPid(string $recordPid): void
    {
        $this->recordPid = $recordPid;
    }
}
