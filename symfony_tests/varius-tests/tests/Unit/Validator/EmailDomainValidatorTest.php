<?php

namespace App\Tests\Unit\Validator;

use App\Repository\ConfigRepository;
use App\Validator\EmailDomain;
use App\Validator\EmailDomainValidator;
use PHPUnit\Framework\TestCase;
/* use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase; */
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Unit tests for the EmailDomainValidator class.
 *
 * This test suite validates the behavior of the EmailDomainValidator class, which
 * implements the validation logic for the EmailDomain constraint.
 *
 * TEST CONCEPTS DEMONSTRATED:
 * --------------------------
 * 1. Mock testing - creating and configuring mock objects
 * 2. Behavior testing - verifying method calls and interactions
 * 3. Validation logic testing - testing both positive and negative validation cases
 *
 * WHAT THIS TEST CLASS COVERS:
 * ---------------------------
 * - Validator behavior when email contains a blocked domain
 * - Validator behavior when email contains an acceptable domain
 * - Proper interaction with the Symfony validation context
 *
 * ADVANCED TESTING TECHNIQUES:
 * --------------------------
 * - Mock expectation chaining
 * - Test helper methods
 * - Testing validation violations
 *
 * @package App\Tests\Unit\Validator
 */
class EmailDomainValidatorTest extends TestCase
{
    private $validator;

    /* public function setUp(): void */
    /* { */
    /*     parent::setUp(); */
    /*     self::bootKernel(); */
    /**/
    /*     $this->validator = static::getContainer()->get(EmailDomainValidator::class); */
    /* } */

    /**
     * Creates a mock validation context for testing the validator
     * 
     * WHY IT MATTERS:
     * - Mocking the validation context allows testing validation logic without integration testing
     * - This separates the validation rules from the Symfony validation infrastructure
     * - Configuring expectations verifies that the validator interacts correctly with the context
     * - The method handles both positive and negative test cases through the $expectedViolation parameter
     * 
     * @param bool $expectedViolation Whether we expect a validation error to be raised
     * @return ExecutionContextInterface The configured mock context
     */
    private function getContext(bool $expectedViolation): ExecutionContextInterface
    {
        // Create mock validation context
        $context = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();

        if ($expectedViolation) {
            // If a violation is expected, set up the violation builder
            // This simulates Symfony's validation violation building process
            $violation = $this->getMockBuilder(
                ConstraintViolationBuilderInterface::class
            )->getMock();

            // Configure chained method calls for the violation builder
            // This mimics how Symfony builds violations with method chaining
            $violation->expects($this->any())->method('setParameter')->willReturn($violation);
            
            // Verify that addViolation() is called exactly once - this confirms the validator adds a violation
            $violation->expects($this->once())->method('addViolation');

            // The context should call buildViolation exactly once when validation fails
            // This verifies the validator properly signals validation failures
            $context
                ->expects($this->once())
                ->method('buildViolation')
                ->willReturn($violation);
        } else {
            // If no violation is expected, buildViolation should never be called
            // This verifies the validator correctly passes valid values
            $context
                ->expects($this->never())
                ->method('buildViolation');
        }

        /** @disregard P1006 Mock problem */
        return $context;
    }

    /**
     * Creates and configures a validator instance for testing.
     *
     * EXPLANATION:
     * -----------
     * This helper method creates a properly configured EmailDomainValidator with mocked
     * dependencies for testing. It:
     *
     * 1. Creates a mock ConfigRepository (dependency of EmailDomainValidator)
     * 2. Creates a mock ExecutionContext (Symfony's validation context)
     * 3. Sets up expectations for how the context should be used based on test parameters
     * 4. Initializes the validator with the mocked context
     *
     * The method accepts parameters to configure whether a violation is expected,
     * allowing it to be reused for both positive and negative test cases.
     *
     * MOCKING DETAILS:
     * --------------
     * - ConfigRepository: Currently not actively used in the validator but is injected
     * - ExecutionContextInterface: Mocked to verify validation behavior
     * - ConstraintViolationBuilderInterface: Mocked to verify violation creation
     *
     * @param bool $expectedViolation Whether a validation violation is expected
     * @param array $dbBlockedDomain Optional blocked domains from database (for future use)
     *
     * @return EmailDomainValidator Configured validator instance ready for testing
     */
    public function getValidator(
        bool $expectedViolation = false,
        array $dbBlockedDomain = []
    ): EmailDomainValidator {
        // Create mock repository (dependency of the validator)
        $repository = $this
            ->getMockBuilder(ConfigRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Configure the repository mock to return blocked domains
        $repository
            ->expects($this->any())
            ->method('getAsArray')
            ->with('blocked_domains')
            ->willReturn($dbBlockedDomain);

        /** @disregard P1006 Mock problem */
        // Create the actual validator with our mocked repository
        $validator = new EmailDomainValidator($repository);

        $context = $this->getContext($expectedViolation);

        /** @disregard P1006 Mock problem */
        // Initialize the validator with our mocked context
        $validator->initialize($context);

        return $validator;
    }

    /**
     * Tests that the validator correctly identifies and rejects blocked domains.
     *
     * EXPLANATION:
     * -----------
     * This test verifies that when an email with a blocked domain is validated,
     * the validator correctly detects it and creates a violation.
     *
     * Steps:
     * 1. Create an EmailDomain constraint with a list of blocked domains
     * 2. Get a validator configured to expect a violation
     * 3. Validate an email address that uses one of the blocked domains
     * 4. The test passes if the validator creates a violation as expected
     *
     * TEST DATA:
     * ---------
     * - Blocked domains: 'baddomain.fr', 'aze.com'
     * - Test email: 'demo@baddomain.fr'
     * - Expected result: Validation violation
     *
     * @return void
     */
    public function testCatchBadDomains(): void
    {
        // Create constraint with blocked domains
        $constraint = new EmailDomain(options: [
            'blocked' => ['baddomain.fr', 'aze.com'],
        ]);

        // Get validator that expects a violation
        $validator = $this->getValidator(true);

        // Validate an email with a blocked domain - should trigger a violation
        $validator->validate('demo@baddomain.fr', $constraint);
    }

    /**
     * Tests that the validator correctly accepts non-blocked domains.
     *
     * EXPLANATION:
     * -----------
     * This test verifies that when an email with a non-blocked domain is validated,
     * the validator correctly accepts it without creating any violations.
     *
     * Steps:
     * 1. Create an EmailDomain constraint with a list of blocked domains
     * 2. Get a validator configured to expect NO violations
     * 3. Validate an email address that uses a domain NOT in the blocked list
     * 4. The test passes if the validator doesn't create any violations
     *
     * TEST DATA:
     * ---------
     * - Blocked domains: 'baddomain.fr', 'aze.com'
     * - Test email: 'demo@gooddomain.fr'
     * - Expected result: No validation violation
     *
     * @return void
     */
    public function testAcceptGoodDomains(): void
    {
        // Create constraint with blocked domains
        $constraint = new EmailDomain(options: [
            'blocked' => ['baddomain.fr', 'aze.com'],
        ]);

        // Get validator that expects NO violations
        $validator = $this->getValidator();

        // Validate an email with a non-eblocked domain - should NOT trigger a violation
        $validator->validate('demo@gooddomain.fr', $constraint);
    }

    /**
     * Tests that the validator correctly identifies database-sourced blocked domains.
     *
     * EXPLANATION:
     * -----------
     * This test verifies that when an email with a domain blocked in the database 
     * (but not in the constraint configuration) is validated, the validator correctly
     * detects it and creates a violation.
     *
     * Steps:
     * 1. Create an EmailDomain constraint with a list of blocked domains
     * 2. Get a validator configured to expect a violation, with a mocked database source
     * 3. Validate an email address that uses one of the database-blocked domains
     * 4. The test passes if the validator creates a violation as expected
     *
     * TEST DATA:
     * ---------
     * - Config-blocked domains: 'baddomain.fr', 'aze.com'
     * - Database-blocked domains: 'baddbdomain.fr'
     * - Test email: 'demo@baddbdomain.fr'
     * - Expected result: Validation violation
     *
     * @return void
     */
    public function testBlockedDomainFromDatabase(): void
    {
        // Create constraint with blocked domains
        $constraint = new EmailDomain(options: [
            'blocked' => ['baddomain.fr', 'aze.com'],
        ]);

        // Get validator that expects a violation and configure it with
        // a mocked database source of additional blocked domains
        $validator = $this->getValidator(true, ['baddbdomain.fr']);

        // Validate an email with a domain blocked in the database
        // This should trigger a violation even though it's not in the constraint's list
        $validator->validate('demo@baddbdomain.fr', $constraint);
    }
}
