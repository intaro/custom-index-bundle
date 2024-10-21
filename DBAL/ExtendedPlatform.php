<?php

namespace Intaro\CustomIndexBundle\DBAL;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Intaro\CustomIndexBundle\DTO\CustomIndex;

final class ExtendedPlatform extends PostgreSQLPlatform
{
    public function createIndexSQL(CustomIndex $index): string
    {
        $sql = 'CREATE';
        if ($index->getUnique()) {
            $sql .= ' UNIQUE';
        }

        $sql .= ' INDEX ' . $index->getName();
        $sql .= ' ON ' . $index->getTableName();

        if ($index->getUsing()) {
            $sql .= ' USING ' . $index->getUsing();
        }

        $sql .= ' (' . implode(', ', $index->getColumns()) . ')';

        if ($index->getWhere()) {
            $sql .= ' WHERE ' . $index->getWhere();
        }

        return $sql;
    }

    public function dropIndexSQL(string $indexName): string
    {
        return "DROP index $indexName";
    }

    public function currentSchemaSelectSQL(): string
    {
        return 'SELECT current_schema()';
    }

    public function indexesNamesSelectSQL(bool $searchInAllSchemas): string
    {
        $sql = "
            SELECT schemaname || '.' || indexname as relname 
            FROM pg_indexes 
            WHERE indexname LIKE :indexName
            AND indexname NOT LIKE '%_ccnew'
        ";
        if (!$searchInAllSchemas) {
            $sql .= ' AND schemaname = current_schema()';
        }

        return $sql;
    }
}
