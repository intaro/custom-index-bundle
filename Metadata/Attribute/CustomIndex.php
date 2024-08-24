<?php

namespace Intaro\CustomIndexBundle\Metadata\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class CustomIndex
{
    /** @param array<string> $columns */
    public function __construct(
        public ?string $name = null,
        public array $columns = [],
        public ?string $where = null,
        public ?string $using = null,
        public bool $unique = false,
    ) {
    }
}
