<?php

namespace Intaro\CustomIndexBundle\Metadata\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Intaro\CustomIndexBundle\Annotations\CustomIndexes;
use Intaro\CustomIndexBundle\DTO\CustomIndex;
use Intaro\CustomIndexBundle\Metadata\ReaderInterface;

final class Reader implements ReaderInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getIndexes(string $currentSchema, bool $searchInAllSchemas): array
    {
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $result = [];
        $abstractClassesInfo = $this->getAbstractClassesInfo($metadata);
        // add all custom indexes into $result array
        $indexesToResult = function (ClassMetadata $meta, $tableName, $tablePostfix = false) use (&$result, $currentSchema, $searchInAllSchemas) {
            if ($indexes = $this->getCustomIndexes($meta)) {
                foreach ($indexes as $aIndex) {
                    $schema = $meta->getSchemaName() ?: $currentSchema;

                    // skip index from side schema in single schema mode
                    if (!$searchInAllSchemas && $schema !== $currentSchema) {
                        continue;
                    }

                    $index = new CustomIndex(
                        $tableName,
                        $schema,
                        $aIndex->columns,
                        $aIndex->name . ($aIndex->name && $tablePostfix ? '_' . $tableName : ''),
                        $aIndex->unique,
                        $aIndex->using,
                        $aIndex->where,
                    );

                    $key = $schema . '.' . $index->getName();
                    $result[$key] = $index;
                }
            }
        };

        foreach ($metadata as $meta) {
            if (!$this->isAbstract($meta)) {
                $indexesToResult($meta, $meta->getTableName());

                // create index using abstract parent
                $parentsMeta = $this->searchParentsWithIndex($meta, $abstractClassesInfo);
                foreach ($parentsMeta as $parentMeta) {
                    if ($meta->inheritanceType === ClassMetadata::INHERITANCE_TYPE_JOINED) {
                        $tableName = $parentMeta->getTableName();
                    } else {
                        $tableName = $meta->getTableName();
                    }

                    $indexesToResult($parentMeta, $tableName, true);
                }
            }
        }

        return $result;
    }


    /** @return array<\Intaro\CustomIndexBundle\Annotations\CustomIndex> */
    private function getCustomIndexes(ClassMetadata $meta): array
    {
        $reader = new AnnotationReader();
        $refl = $meta->getReflectionClass();
        $annotation = $reader->getClassAnnotation($refl, CustomIndexes::class);
        if ($annotation) {
            return $annotation->indexes;
        }

        return [];
    }

    private function isAbstract(ClassMetadata $meta): bool
    {
        return $meta->getReflectionClass()->isAbstract();
    }


    /**
     * @param array $metadata
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
     * @param array<string, mixed> $abstractClasses
     * @return array<string, mixed>
     */
    private function searchParentsWithIndex(ClassMetadata $meta, array $abstractClasses): array
    {
        $refl = $meta->getReflectionClass();
        $parentMeta = [];
        foreach ($abstractClasses as $entityName => $entityMeta) {
            if ($refl->isSubclassOf($entityName)) {
                $parentMeta[$entityName] = $entityMeta;
            }
        }

        return $parentMeta;
    }
}
