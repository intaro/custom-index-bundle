<?php

namespace Intaro\CustomIndexBundle\Metadata;

use Intaro\CustomIndexBundle\DTO\CustomIndex;

interface ReaderInterface
{
    /** @return array<string, CustomIndex> */
    public function getIndexes(string $currentSchema, bool $searchInAllSchemas): array;
}
