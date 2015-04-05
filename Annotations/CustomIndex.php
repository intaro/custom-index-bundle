<?php

namespace Intaro\CustomIndexBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 *
 * CREATE [UNIQUE] INDEX <$name> ON <table> [USING <$method>] ( <$columns> ) [WHERE <$where>]
 */
class CustomIndex extends Annotation
{
    /**
     * Index name
     *
     * @var string
     */
    public $name;

    /**
     * Index is unique
     *
     * @var bool
     */
    public $unique = false;

    /**
     * Using index structure (btree, hash, gist, or gin)
     *
     * @var string
     */
    public $using;

    /**
     * For partial index
     *
     * @var string
     */
    public $where;

    /**
     * Index columns
     *
     * @var array
     */
    public $columns;

}