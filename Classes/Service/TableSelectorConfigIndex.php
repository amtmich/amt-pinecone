<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Service;

use Amt\AmtPinecone\Domain\Repository\PineconeRepository;

class TableSelectorConfigIndex
{
    /**
     * @param array<mixed> $params
     */
    public function getValidTables(&$params): void
    {
        $params['items'] = self::filterTables();
    }

    /**
     * @return array<mixed>
     */
    private static function filterTables(): array
    {
        $tablesNames = array_keys($GLOBALS['TCA']);
        $tablesToExclude = [PineconeRepository::TABLENAME_CONFIG, PineconeRepository::TABLENAME];
        $items = [];
        foreach ($tablesNames as $tableName) {
            if (in_array($tableName, $tablesToExclude, true)
                || preg_match('/^(sys_|be_|fe_|backend_)/', (string) $tableName)) {
                continue;
            }
            $items[] = [$tableName, $tableName];
        }
        asort($items);

        return $items;
    }
}
