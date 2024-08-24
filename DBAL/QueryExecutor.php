<?php

namespace Intaro\CustomIndexBundle\DBAL;

use Doctrine\DBAL\Connection;
use Intaro\CustomIndexBundle\DTO\CustomIndex;

final class QueryExecutor
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function createIndex(ExtendedPlatform $platform, CustomIndex $customIndex): void
    {
        $sql = $platform->createIndexSQL($customIndex);
        $statement = $this->connection->prepare($sql);
        $statement->executeStatement();
    }

    public function dropIndex(ExtendedPlatform $platform, string $indexName): void
    {
        $sql = $platform->dropIndexSQL($indexName);
        $statement = $this->connection->prepare($sql);
        $statement->executeStatement();
    }

    /** @return array<string> */
    public function getIndexesNames(ExtendedPlatform $platform, bool $searchInAllSchemas): array
    {
        $sql = $platform->indexesNamesSelectSQL($searchInAllSchemas);
        $statement = $this->connection->prepare($sql);
        $statement->bindValue('indexName', CustomIndex::PREFIX . '%');
        $result = $statement->executeQuery();

        $indexesNames = [];
        $data = $result->fetchAllAssociative();
        foreach ($data as $row) {
            $indexesNames[] = $row['relname'];
        }

        return $indexesNames;
    }

    public function getCurrentSchema(ExtendedPlatform $platform): string
    {
        $sql = $platform->currentSchemaSelectSQL();
        $statement = $this->connection->prepare($sql);
        $result = $statement->executeQuery();
        $data = $result->fetchAssociative();
        $currentSchema = $data['current_schema'] ?? null;
        if (null === $currentSchema) {
            throw new \LogicException('Current schema not found');
        }

        return $currentSchema;
    }
}
