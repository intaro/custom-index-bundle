<?php

namespace Intaro\CustomIndexBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class CustomIndex extends Annotation
{
    /**
     * CREATE [UNIQUE] INDEX <$name> ON <table> [USING <$method> (] <$columns> [)]
     *
     */

    // имя индекса будет добавлен общий префикс
    public $name = '';
    
    // уникальный индекс
    public $unique = false;
    
    // используемый метод (btree, hash, gist, or gin)
    public $using = '';
    
    // колонки индекса (в формате строки)
    public $columns;
        
}