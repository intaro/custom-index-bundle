# CustomIndexBundle

The CustomIndexBundle allows create index for doctrine entities using annotation with entity definition and console command.

## Installation

CustomIndexBundle requires Symfony 2.1 or higher. Now work only with PostgreSQL.

Require the bundle in your `composer.json` file:

 ````json
{
    "require": {
        "intaro/custom-index-bundle": "~0.1.1",
    }
}
```

Install the bundle:

```
$ composer update intaro/custom-index-bundle
```

Register the bundle in `AppKernel`:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        //...

        new Intaro\CustomIndexBundle\IntaroCustomIndexBundle(),
    );

    //...
}
```

## Usage

1) Add annotation in your entity

```php
<?php

namespace Acme\MyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Intaro\CustomIndexBundle\Annotations as CustomIndexAnnotation

/**
 * @ORM\Table(name="my_entity")
 * @ORM\Entity()
 * @CustomIndexAnnotation\CustomIndexes(indexes={
 *     @CustomIndexAnnotation\CustomIndex(columns="my_property1"),
 *     @CustomIndexAnnotation\CustomIndex(columns={"lower(my_property1)", "lower(my_property2)"})
 * })
 */
class MyEntity
{
    /**
     * @ORM\Column(type="string", length=256)
     */
    protected $myProperty1;

    /**
     * @ORM\Column(type="string", length=256)
     */
    protected $myProperty2;
}
```

Available CustomIndex attributes:

* `columns` - string or array with columns.
* `name` - index name (default='i_cindex_<md5 hash from all CustomIndex attributes>').
* `unique` - unique index (default=false).
* `using` - corresponds to `USING` directive in PostgreSQL `CREATE INDEX` command.

Required only `columns` attribute.

2) Use `intaro:doctrine:index:update` command for update db.

```
php app/console intaro:doctrine:index:update
```

You may use `dump-sql` parameter for dump sql with `DROP/CRATE INDEX` commands

```
php app/console intaro:doctrine:index:update --dump-sql
```

## Some annotation examples

Create index using `pg_trgm` extension:
```
@CustomIndexAnnotation\CustomIndex(columns="lower(my_column) gist_trgm_ops", using="gist"})
```

Create index using PostgreSql functions:
```
@CustomIndexAnnotation\CustomIndex(columns={"lower(my_column1)", "nullif(true, not my_column2 isnull)"} gist_trgm_ops", using="gist"}, unique=true)
```