<?php

namespace Intaro\CustomIndexBundle\Validator\Constraints;

use Intaro\CustomIndexBundle\Validator\Constraints\AllowedIndexType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AllowedIndexTypeValidator extends ConstraintValidator
{
    private static $constraintClass = 'Intaro\CustomIndexBundle\Validator\Constraints\AllowedIndexType';

    /**
     * @var string[]
     */
    protected $allowedIndexTypes;

    /**
     * @param string[] $allowedIndexTypes
     */
    public function __construct(array $allowedIndexTypes)
    {
        $this->allowedIndexTypes = $allowedIndexTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AllowedIndexType) {
            throw new UnexpectedTypeException($constraint, self::$constraintClass);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!in_array($value, $this->allowedIndexTypes)) {
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
