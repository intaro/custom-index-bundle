<?php

namespace Intaro\CustomIndexBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class AllowedIndexType extends Constraint
{
    public $message = 'Index type {{ type }} is not allowed. List of allowed types: {{ allowed_types }}.';

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}
