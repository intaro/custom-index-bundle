<?php

namespace Intaro\CustomIndexBundle\Metadata;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Intaro\CustomIndexBundle\DTO\CustomIndex;

/**
 * @template T of object
 */
final class Reader implements ReaderInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getIndexes(string $currentSchema, bool $searchInAllSchemas): array
    {
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $indexNamesToCustomIndexes = [];
        $abstractClassesInfo = $this->getAbstractClassesInfo($metadata);
        foreach ($metadata as $meta) {
            if ($this->isAbstract($meta)) {
                continue;
            }

            $this->collect($indexNamesToCustomIndexes, $meta, $meta->getTableName(), $currentSchema, $searchInAllSchemas);
            $parentsMeta = $this->searchParentsWithIndex($meta, $abstractClassesInfo);
            foreach ($parentsMeta as $parentMeta) {
                $tableName = $this->getTableNameFromMetadata($meta, $parentMeta);
                $this->collect($indexNamesToCustomIndexes, $parentMeta, $tableName, $currentSchema, $searchInAllSchemas, true);
            }
        }

        return $indexNamesToCustomIndexes;
    }

    /**
     * @param array<string, CustomIndex> $indexNamesToCustomIndexes
     * @param ClassMetadata<T>           $metadata
     */
    private function collect(
        array &$indexNamesToCustomIndexes,
        ClassMetadata $metadata,
        string $tableName,
        string $currentSchema,
        bool $searchInAllSchemas,
        bool $tablePostfix = false,
    ): void {
        $reflectionAttributes = $this->getCustomIndexesAttributes($metadata);
        if (empty($reflectionAttributes)) {
            return;
        }

        foreach ($reflectionAttributes as $attribute) {
            $schema = $metadata->getSchemaName() ?: $currentSchema;
            // skip index from side schema in single schema mode
            if (!$searchInAllSchemas && $schema !== $currentSchema) {
                continue;
            }

            $attributeArguments = $attribute->getArguments();
            $name = $attributeArguments['name'] ?? '';
            $index = new CustomIndex(
                $tableName,
                $schema,
                $currentSchema,
                $attributeArguments['columns'] ?? [],
                $name . ($name && $tablePostfix ? '_' . $tableName : ''),
                $attributeArguments['unique'] ?? false,
                $attributeArguments['using'] ?? null,
                $attributeArguments['where'] ?? null,
            );

            $key = $schema . '.' . $index->getName();
            $indexNamesToCustomIndexes[$key] = $index;
        }
    }

    /**
     * @param ClassMetadata<T> $metadata
     * @param ClassMetadata<T> $parentMetadata
     */
    private function getTableNameFromMetadata(ClassMetadata $metadata, ClassMetadata $parentMetadata): string
    {
        if (ClassMetadataInfo::INHERITANCE_TYPE_JOINED === $metadata->inheritanceType) {
            return $parentMetadata->getTableName();
        }

        return $metadata->getTableName();
    }

    /**
     * @param ClassMetadata<T> $meta
     *
     * @return array<\ReflectionAttribute<Attribute\CustomIndex>>
     */
    private function getCustomIndexesAttributes(ClassMetadata $meta): array
    {
        return $meta->getReflectionClass()->getAttributes(Attribute\CustomIndex::class);
    }

    /**
     * @param ClassMetadata<T> $meta
     */
    private function isAbstract(ClassMetadata $meta): bool
    {
        return $meta->getReflectionClass()->isAbstract();
    }

    /**
     * @param array<ClassMetadata<T>> $metadata
     *
     * @return array<string, mixed>
     */
    private function getAbstractClassesInfo(array $metadata): array
    {
        $abstractClasses = [];
        foreach ($metadata as $meta) {
            if ($this->isAbstract($meta)) {
                $abstractClasses[$meta->getName()] = $meta;
            }
        }

        return $abstractClasses;
    }

    /**
     * @param ClassMetadata<T>     $meta
     * @param array<string, mixed> $abstractClasses
     *
     * @return array<string, mixed>
     */
    private function searchParentsWithIndex(ClassMetadata $meta, array $abstractClasses): array
    {
        $reflectionClass = $meta->getReflectionClass();
        $parentMeta = [];
        foreach ($abstractClasses as $entityName => $entityMeta) {
            if ($reflectionClass->isSubclassOf($entityName)) {
                $parentMeta[$entityName] = $entityMeta;
            }
        }

        return $parentMeta;
    }
}
