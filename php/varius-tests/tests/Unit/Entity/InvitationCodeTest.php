<?php

namespace App\Tests\Unit\Entity;

use App\Entity\InvitationCode;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * ENTITY TESTING MASTERCLASS: InvitationCode Entity Tests
 * ======================================================
 * 
 * OBJECTIVE:
 * This test class demonstrates how to properly validate a Doctrine entity against its
 * validation constraints in a Symfony application. Use this as a template for creating
 * test classes for your own entities.
 * 
 * KEY CONCEPTS DEMONSTRATED:
 * - Entity validation using Symfony's validator service
 * - Testing different validation constraints (length, not blank, uniqueness)
 * - Working with database fixtures for uniqueness tests
 * - Organizing test methods with clear, purpose-driven naming
 * - Creating reusable helper methods for test efficiency
 * 
 * VALIDATION CONSTRAINTS BEING TESTED:
 * - Length constraints (min/max characters)
 * - NotBlank constraints (required fields)
 * - Unique constraints (database-level validation)
 * 
 * TEST ORGANIZATION PATTERN:
 * 1. Setup methods (setUp, tearDown) - Prepare and clean test environment
 * 2. Helper methods (getEntity, assertHasErrors, getErrorsAsMessage) - Reusable test logic
 * 3. Test methods - Individual validation scenarios (one constraint per test method)
 * 
 * WHEN TO USE THIS PATTERN:
 * - When you need to validate entity constraints independently of your controllers
 * - For validating complex business rules at the entity level
 * - To ensure your database constraints match your entity validation rules
 * 
 * @package App\Tests\Unit\Entity
 */
class InvitationCodeTest extends KernelTestCase
{
    /** 
     * @var AbstractDatabaseTool
     * Database tool for loading fixtures and interacting with the test database
     */
    protected $databaseTool;

    /**
     * SETUP PHASE: Prepare the test environment
     * =========================================
     * 
     * WHY IS THIS IMPORTANT?
     * The setUp method runs before each test method, creating a fresh and
     * isolated environment for each test to run in. This prevents tests
     * from affecting each other (test isolation principle).
     * 
     * WHAT HAPPENS HERE:
     * 1. The parent::setUp() initializes the PHPUnit testing framework
     * 2. self::bootKernel() boots up a Symfony kernel for testing
     * 3. We get the database tool service for loading fixtures
     * 
     * TEACHING POINT:
     * Always boot the kernel in setUp() rather than in individual test methods
     * to maintain consistency and avoid code duplication.
     */
    public function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    /**
     * HELPER METHOD: Format validation errors for readable error messages
     * ==================================================================
     * 
     * PURPOSE:
     * Converts technical ValidationViolation objects into human-readable strings
     * that help quickly identify what went wrong in failed tests.
     * 
     * HOW IT WORKS:
     * 1. Creates an empty array to collect error messages
     * 2. Loops through each validation error in the provided collection
     * 3. Formats each error as "propertyPath => errorMessage"
     * 4. Joins all messages with commas for readability
     * 
     * WHY THIS IS USEFUL:
     * When a test fails, this provides clear feedback about which
     * constraint was violated and why, making debugging much faster.
     * 
     * EXAMPLE OUTPUT:
     * "code => This value should have exactly 5 characters., description => This value should not be blank."
     *
     * @param ConstraintViolationListInterface $errors Collection of validation errors
     * @return string Formatted string of validation errors
     */
    public function getErrorsAsMessage(ConstraintViolationListInterface $errors): string
    {
        $messages = [];

        /** @var \Symfony\Component\Validator\ConstraintViolation $error */
        foreach ($errors as $error)
            $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage();

        return implode(', ', $messages);
    }

    /**
     * CORE TEST HELPER: Validate an entity and verify error count
     * ===========================================================
     * 
     * PURPOSE:
     * This is the central validation method used by all test methods.
     * It standardizes how we validate entities and check for errors.
     * 
     * HOW TO USE THIS PATTERN:
     * 1. Create an entity with the state you want to test
     * 2. Call this method with that entity and the number of expected errors
     * 3. If the validation produces a different number of errors, the test fails
     * 
     * BEHIND THE SCENES:
     * 1. Gets the Symfony validator service from the container
     * 2. Validates the entity against all its constraints
     * 3. Counts the resulting validation errors
     * 4. Compares the count to the expected number
     * 5. If they don't match, shows formatted error messages
     * 
     * TEACHING POINT:
     * By centralizing validation in one method, we avoid code duplication
     * and make our tests more maintainable.
     *
     * @param InvitationCode $code   The invitation code entity to validate
     * @param int            $number Expected number of validation errors (default: 0)
     */
    public function assertHasErrors(InvitationCode $code, int $number = 0): void
    {
        $errors = self::getContainer()->get('validator')->validate($code);
        $this->assertCount($number, $errors, $this->getErrorsAsMessage($errors));
    }

    /**
     * ENTITY FACTORY: Create a valid entity instance for testing
     * =========================================================
     * 
     * PURPOSE:
     * Provides a standard, valid entity that tests can modify to create
     * invalid states. This follows the "start valid, break one thing" testing pattern.
     * 
     * DESIGN PRINCIPLE:
     * Creating a valid entity first and then modifying it to make it invalid
     * ensures that tests isolate a single validation constraint at a time.
     * 
     * WHAT MAKES THE ENTITY VALID:
     * 1. Code: Exactly 5 characters (meeting the Length constraint)
     * 2. Description: Non-empty string (meeting the NotBlank constraint)
     * 3. ExpireAt: Valid DateTime object
     * 
     * HOW TO USE IN TESTS:
     * 1. For positive tests: Use this entity directly
     * 2. For negative tests: Modify one property to make it invalid
     * 
     * TEACHING POINT:
     * Entity factories make tests more maintainable. If entity requirements
     * change, you only need to update this method, not every test.
     *
     * @return InvitationCode A valid invitation code entity for testing
     */
    public function getEntity(): InvitationCode
    {
        $code = (new InvitationCode())
            ->setCode('12345')
            ->setDescription('Test description')
            ->setExpireAt(new \DateTime());
        return $code;
    }

    /**
     * TEST: Validate that a correctly formed entity passes validation
     * ==============================================================
     * 
     * PURPOSE:
     * This is a positive test that verifies our "valid" entity factory
     * actually creates an entity that passes all validation constraints.
     * 
     * WHY THIS IS IMPORTANT:
     * Before testing invalid states, we need to confirm our baseline is correct.
     * This prevents false positives in our negative tests.
     * 
     * TEST STEPS:
     * 1. Get a valid entity using our factory method
     * 2. Assert that it has zero validation errors
     * 
     * EXPECTED OUTCOME:
     * The entity should have 0 validation errors, confirming it's valid.
     */
    public function testValidInvitationCodeEntity(): void
    {
        $code = $this->getEntity();
        $this->assertHasErrors($code, 0);
    }

    /**
     * TEST: Validate the length constraint on the code property
     * ========================================================
     * 
     * PURPOSE:
     * Tests that the code property enforces its length constraint
     * (must be exactly 5 characters).
     * 
     * TEST STRATEGY:
     * We test both boundaries of the constraint:
     * 1. Too long: code with more than 5 characters
     * 2. Too short: code with fewer than 5 characters
     * 
     * TEST STEPS:
     * 1. Start with a valid entity
     * 2. Modify its code to be too long and test
     * 3. Modify its code to be too short and test
     * 
     * EXPECTED OUTCOME:
     * Each invalid code should produce exactly 1 validation error.
     * 
     * VALIDATION CONSTRAINT TESTED:
     * @Assert\Length(exactly=5)
     */
    public function testInvalidCodeEntity(): void
    {
        $code = $this->getEntity()->setCode('1a3fdfd4579');
        $this->assertHasErrors($code, 1);

        $code = $this->getEntity()->setCode('179');
        $this->assertHasErrors($code, 1);
    }

    /**
     * TEST: Validate the NotBlank constraint on the code property
     * ==========================================================
     * 
     * PURPOSE:
     * Tests that the code property cannot be blank (empty string).
     * 
     * TEST STRATEGY:
     * Set the code to an empty string and verify it fails validation.
     * 
     * TEST STEPS:
     * 1. Start with a valid entity
     * 2. Set its code to an empty string
     * 3. Validate and expect an error
     * 
     * EXPECTED OUTCOME:
     * The entity should have exactly 1 validation error.
     * 
     * VALIDATION CONSTRAINT TESTED:
     * @Assert\NotBlank()
     */
    public function testInvalidBlankCodeEntity(): void
    {
        $code = $this->getEntity()->setCode('');
        $this->assertHasErrors($code, 1);
    }

    /**
     * TEST: Validate the NotBlank constraint on the description property
     * =================================================================
     * 
     * PURPOSE:
     * Tests that the description property cannot be blank (empty string).
     * 
     * TEST STRATEGY:
     * Set the description to an empty string and verify it fails validation.
     * 
     * TEST STEPS:
     * 1. Start with a valid entity
     * 2. Set its description to an empty string
     * 3. Validate and expect an error
     * 
     * EXPECTED OUTCOME:
     * The entity should have exactly 1 validation error.
     * 
     * VALIDATION CONSTRAINT TESTED:
     * @Assert\NotBlank()
     */
    public function testInvalidBlankDescriptionEntity(): void
    {
        $code = $this->getEntity()->setDescription('');
        $this->assertHasErrors($code, 1);
    }

    /**
     * TEST: Validate the Unique constraint on the code property
     * ========================================================
     * 
     * PURPOSE:
     * Tests that the code property must be unique in the database.
     * This is more complex than other constraints because it requires
     * database interaction.
     * 
     * TEST STRATEGY:
     * 1. Load fixtures containing pre-existing invitation codes
     * 2. Create a new entity with a code that already exists in the fixtures
     * 3. Validate and expect a uniqueness error
     * 
     * TEST STEPS:
     * 1. Load test fixtures with known invitation codes
     * 2. Create a valid entity but set its code to match one in the fixtures
     * 3. Validate and expect an error
     * 
     * EXPECTED OUTCOME:
     * The entity should have exactly 1 validation error.
     * 
     * VALIDATION CONSTRAINT TESTED:
     * @ORM\UniqueConstraint or @UniqueEntity annotation
     * 
     * TEACHING POINT:
     * This demonstrates how to test database-level constraints using fixtures,
     * which is more complex than testing simple property constraints.
     */
    public function testInvalidDuplicatedCodeEntity(): void
    {
        $this->databaseTool->loadAliceFixture([
            dirname(__DIR__) . '/fixtures/invitation_code.yaml',
        ]);

        $code = $this->getEntity()->setCode('23790');
        $this->assertHasErrors($code, 1);
    }

    /**
     * CLEANUP PHASE: Release resources after each test
     * ===============================================
     * 
     * PURPOSE:
     * Properly cleans up resources after each test to prevent memory leaks
     * and ensure test isolation.
     * 
     * WHY THIS IS IMPORTANT:
     * Proper cleanup prevents one test from affecting another and helps
     * avoid memory issues when running many tests.
     * 
     * WHAT HAPPENS HERE:
     * 1. Call the parent tearDown() to handle standard PHPUnit cleanup
     * 2. Unset our database tool to release its resources
     * 
     * TEACHING POINT:
     * Always include a tearDown() method that releases any resources
     * acquired in setUp() or during the test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }
}
