<?php

namespace Intaro\CustomIndexBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @deprecated left only for automatic conversion of annotations to attributes
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
