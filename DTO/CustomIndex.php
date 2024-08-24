<?php

namespace Intaro\CustomIndexBundle\DTO;

use Intaro\CustomIndexBundle\Validator\Constraints\AllowedIndexType;
use Symfony\Component\Validator\Constraints as Assert;

final class CustomIndex
{
    public const PREFIX = 'i_cindex_';
    private const UNIQUE = 'unique';
    private const DEFAULT_SCHEMA = 'public';

    #[Assert\Length(min: 1, max: 63, minMessage: 'Name must be set', maxMessage: 'Name is too long')]
    private ?string $name;
    #[Assert\Count(min: 1, minMessage: 'You must specify at least one column')]
    #[Assert\All([
        new Assert\Type([
            'type' =>'string',
            'message' => 'Column should be type of string',
        ]),
    ])]
    private array $columns = [];
    private bool $unique;
    #[AllowedIndexType]
    private ?string $using;
    private ?string $where;
    #[Assert\Length(min: 1, max: 63, minMessage: 'TableName must be set', maxMessage: 'TableName is too long')]
    private string $tableName;
    private string $schema;

    public function __construct(
        string $tableName,
        string $schema,
        array|string $columns,
        ?string $name = null,
        bool $unique = false,
        ?string $using = null,
        ?string $where = null,
    ) {
        $this->tableName = $tableName;
        $this->setColumns($columns);
        $this->unique = $unique;
        $this->using = $using;
        $this->where = (string) $where;
        $this->schema = $schema;

        if (!empty($name)) {
            $this->setName($name);

            return;
        }

        $this->generateName();
    }

    public function getTableName(): string
    {
        if ($this->getSchema() !== self::DEFAULT_SCHEMA) {
            return $this->getSchema() . '.' . $this->tableName;
        }

        return $this->tableName;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getUnique(): bool
    {
        return $this->unique;
    }

    public function getUsing(): ?string
    {
        return $this->using;
    }

    public function getWhere(): ?string
    {
        return $this->where;
    }

    private function generateName(): void
    {
        $columns = $this->getColumns();
        $strToMd5 = $this->getTableName();
        foreach ($columns as $column) {
            $strToMd5 .= $column;
        }

        $strToMd5 .= $this->getUsing() . ($this->getWhere() ? '_' . $this->getWhere() : '');
        $name = self::PREFIX . ( $this->getUnique() ? self::UNIQUE . '_' : '' ) . md5($strToMd5);
        $this->setName($name);

    }

    private function setColumns($columns): void
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        $this->columns = [];
        foreach ($columns as $column) {
            if (!empty($column)) {
                $this->columns[] = $column;
            }
        }
    }

    private function setName($name): void
    {
        if (!str_starts_with($name, self::PREFIX)) {
            $name = self::PREFIX . $name;
        }

        $this->name = $name;
    }
}
