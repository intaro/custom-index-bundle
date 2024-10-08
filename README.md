# CustomIndexBundle

The CustomIndexBundle allows create index for doctrine entities using attribute with entity definition and console command.

## Installation

CustomIndexBundle requires Symfony 5 or higher. Works only with PostgreSQL.

Run into your project directory:
```
$ composer require intaro/custom-index-bundle
```

Register the bundle in `config/bundles.php`:

```php

<?php

return [
   ...
    Intaro\CustomIndexBundle\IntaroCustomIndexBundle::class => ['all' => true],
];
```

If your project have many schemas in single database and command must generate custom indexes only for one schema then add in your `config.yml`:

```yaml
intaro_custom_index:
    search_in_all_schemas: false
    allowed_index_types: ['gin', 'gist', 'btree', 'hash']

```

Default value of `search_in_all_schemas` is `true`.
If you have different entities in different schemas and you need to update custom indexes in all schemas at once then you must set `search_in_all_schemas` to `true` or omit this config.
If you have database with only public schema then `search_in_all_schemas` value doesn't matter.

Parameter `allowed_index_types` helps to exclude some types of indexes. If someone will try to use excluded type, command `intaro:doctrine:index:update` will return an error.  
Default value is `['gin', 'gist', 'btree', 'hash']`.

## Usage

1) Add attributes in your entity

```php
<?php

namespace Acme\MyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Intaro\CustomIndexBundle\Metadata\Attribute\CustomIndex;

#[ORM\Table(name:'my_entity')]
#[ORM\Entity]
#[CustomIndex(columns: ['my_property1'])]
#[CustomIndex(columns: ['lower(my_property1)', 'lower(my_property2)'])]
class MyEntity
{
    #[ORM\Column(type:'string', length: 256)]
    private $myProperty1;
    #[ORM\Column(type:'string', length: 256)]
    private $myProperty2;
}
```

Available CustomIndex properties:

* `columns` - array of the table columns
* `name` - index name (default = `'i_cindex_<md5 hash from all CustomIndex attributes>'`).
* `unique` - index is unique (default = false).
* `using` - corresponds to `USING` directive in PostgreSQL `CREATE INDEX` command.
* `where` - corresponds to `WHERE` directive in PostgreSQL `CREATE INDEX` command.

Required only `columns` property.

2) Use `intaro:doctrine:index:update` command for update db.

```
php app/console intaro:doctrine:index:update
```

You may use `dump-sql` parameter for dump sql with `DROP/CREATE INDEX` commands

```
php app/console intaro:doctrine:index:update --dump-sql
```

## Examples

Create index using `pg_trgm` extension:
```php
<?php

#[CustomIndex(columns: ['lower(my_column) gist_trgm_ops'], using: 'gist')]
```

Create unique index using PostgreSQL functions:
```php
<?php

#[CustomIndex(columns: ['lower(my_column1)', 'nullif(true, not my_column2 isnull)'], unique: true)]
```

Create partial index:
```php
<?php

#[CustomIndex(columns: ['site_id'], where: 'product_id IS NULL')]
```
