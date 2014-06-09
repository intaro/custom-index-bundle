<?php

namespace Intaro\CustomIndexBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class CustomIndexes extends Annotation
{
    /**
     * @CustomIndexes(indexes=[
     *     @CustomIndex(...),
     *     @CustomIndex(...),
     *     ...
     * ])
    **/
    public $indexes = [];
     
}