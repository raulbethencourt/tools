<?php

namespace App\Validator;

use App\Repository\ConfigRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator implementation for the EmailDomain constraint.
 *
 * This class implements the validation logic for the EmailDomain constraint,
 * checking if an email address contains a domain that matches one in a blocklist.
 *
 * HOW IT WORKS:
 * ------------
 * 1. The validator extracts the domain part of an email address (the part after @)
 * 2. It checks if this domain exists in the constraint's blocked domains list
 * 3. If there's a match, it adds a validation violation to the context
 *
 * VALIDATION PROCESS:
 * -----------------
 * - Empty or null values are considered valid (early return)
 * - The domain is extracted from the email using string functions
 * - The domain is checked against the blocklist using in_array()
 * - If found in the blocklist, a violation is added to the validation context
 *
 * SYMFONY VALIDATOR INTEGRATION:
 * ---------------------------
 * - This class extends ConstraintValidator, Symfony's base validator class
 * - It uses Symfony's violation builder API to create validation errors
 * - The validator is automatically registered and used by Symfony's validation system
 *
 * @package App\Validator
 */
final class EmailDomainValidator extends ConstraintValidator
{
    private array $globalBlocked = [];

    /**
     * Constructor for the EmailDomainValidator.
     *
     * This validator currently doesn't utilize the ConfigRepository, but the dependency
     * is injected to demonstrate how external services can be used in custom validators.
     *
     * In a real-world scenario, you might use the ConfigRepository to dynamically
     * load blocked domains from a database or configuration instead of hard-coding them.
     *
     * @param ConfigRepository $configRepository Repository for accessing application configuration
     */
    public function __construct(
        private ConfigRepository $configRepository,
        private string $globalBlockedDomains = ''
    ) {
        $this->globalBlocked = explode(',', $globalBlockedDomains);
    }

    /**
     * Validates whether an email's domain is in the blocked domains list.
     *
     * VALIDATION LOGIC:
     * ---------------
     * 1. If the value is null or empty, it's considered valid (returns early)
     * 2. The domain portion of the email is extracted (everything after the @ symbol)
     * 3. If the domain is found in the constraint's blocked list, a violation is created
     *
     * ERROR HANDLING:
     * -------------
     * - This implementation assumes the value is a valid email string containing '@'
     * - In a production environment, you might want to add more robust email format validation
     *
     * EXAMPLE:
     * -------
     * - If value = "user@example.com" and blocked = ["example.com", "spam.com"]
     * - Then a violation will be created because "example.com" is in the blocked list
     *
     * @param mixed $value The value to validate (expected to be an email address string)
     * @param Constraint $constraint The constraint to validate against (must be EmailDomain)
     *
     * @return void
     *
     * @see \Symfony\Component\Validator\ConstraintValidator::validate()
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var EmailDomain $constraint */

        // Skip validation for null or empty values
        if (null === $value || '' === $value) {
            return;
        }

        // Extract the domain part (everything after @)
        $domain = substr($value, strpos($value, '@') + 1);

        // Merge passed constraint with config
        $blockedDomain = array_merge(
            $constraint->blocked,
            $this->configRepository->getAsArray('blocked_domains'),
            $this->globalBlocked
        );

        // Check if the domain is in the blocked list
        if (in_array($domain, $blockedDomain)) {
            // Create a violation using Symfony's violation builder
            $this
                ->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
