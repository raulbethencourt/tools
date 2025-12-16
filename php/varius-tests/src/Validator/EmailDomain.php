<?php

namespace App\Validator;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Constraint;

/**
 * Custom validator constraint to validate email domains.
 *
 * This constraint checks if an email domain is not in a list of blocked domains.
 * It can be applied to properties or methods that return email addresses.
 *
 * Usage example:
 * ```php
 * class User
 * {
 *     #[EmailDomain(blocked: ['example.com', 'blocked-domain.com'])]
 *     private string $email;
 * }
 * ```
 *
 * @package App\Validator
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class EmailDomain extends Constraint
{
    /**
     * The string messege that is send it in violation.
     *
     * @var string
     */
    public string $message = 'The array "{{ array }}" contains an illegal domain: it can only contain proper domain names.';

    /**
     * Array of domains that should be blocked.
     *
     * @var array
     */
    public $blocked = [];

    /**
     * Constructor for the EmailDomain constraint.
     *
     * @param array|null $groups Validation groups
     * @param mixed|null $payload Payload for the validator
     * @param array $options Additional options including 'blocked' domains array
     * @throws ConstraintDefinitionException When the blocked option is not an array
     */
    public function __construct(
        ?array $groups = null,
        mixed $payload = null,
        array $options = []
    ) {
        parent::__construct($options, $groups, $payload);

        if (!is_array($options['blocked']))
            throw new ConstraintDefinitionException(
                'The "blocked" option must be an array of blocked domains.'
            );
    }

    /**
     * Returns the list of required options for this constraint.
     *
     * @return array List of required option names
     */
    public function getRequiredOptions(): array
    {
        return ['blocked'];
    }
}
