<?php

namespace Intaro\CustomIndexBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class AllowedIndexType extends Constraint
{
    public string $message = 'Index type {{ type }} is not allowed. List of allowed types: {{ allowed_types }}.';

    public function validatedBy(): string
    {
        return AllowedIndexTypeValidator::class;
    }
}
