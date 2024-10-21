<?php

namespace Intaro\CustomIndexBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AllowedIndexTypeValidator extends ConstraintValidator
{
    /**
     * @param array<string> $allowedIndexTypes
     */
    public function __construct(private readonly array $allowedIndexTypes)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AllowedIndexType) {
            throw new UnexpectedTypeException($constraint, AllowedIndexType::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!in_array($value, $this->allowedIndexTypes, true)) {
            $this->context->addViolation(
                $constraint->message,
                [
                    '{{ type }}' => $value,
                    '{{ allowed_types }}' => implode(', ', $this->allowedIndexTypes),
                ]
            );
        }
    }
}
