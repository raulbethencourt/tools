<?php

namespace App\Tests\Unit\Validator;

use App\Validator\EmailDomain;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Unit tests for the EmailDomain constraint class.
 * 
 * This test suite validates the behavior of the EmailDomain constraint class,
 * focusing on its configuration options and validation rules.
 * 
 * TEST CONCEPTS DEMONSTRATED:
 * --------------------------
 * 1. Exception testing - verifying that appropriate exceptions are thrown
 * 2. Property testing - ensuring class properties are correctly set
 * 3. Constructor validation - testing that constructor validates inputs properly
 * 
 * WHAT THIS TEST CLASS COVERS:
 * ---------------------------
 * - Required parameter validation
 * - Parameter type validation
 * - Parameter assignment to properties
 *
 * RELATED FILES:
 * ------------
 * - src/Validator/EmailDomain.php: The constraint being tested
 * - src/Validator/EmailDomainValidator.php: The validator that uses this constraint
 * - tests/Unit/Validator/EmailDomainValidatorTest.php: Tests for the validator logic
 */
class EmailDomainTest extends TestCase
{
    /**
     * Tests that the EmailDomain constraint throws an exception when required parameters are missing.
     * 
     * EXPLANATION:
     * -----------
     * The EmailDomain constraint requires a 'blocked' option to be provided. 
     * When instantiated without any options, it should throw a MissingOptionsException.
     * This test verifies this behavior by:
     * 1. Setting up an expectation for a MissingOptionsException
     * 2. Attempting to instantiate EmailDomain without any options
     * 3. The test passes if the expected exception is thrown
     * 
     * This demonstrates how to test exception cases in PHPUnit and validates that
     * the constraint properly enforces its required options.
     * 
     * @return void
     */
    public function testRequiredParameters(): void
    {
        // Expect that a MissingOptionsException will be thrown
        $this->expectException(MissingOptionsException::class);
        
        // This should trigger the exception because 'blocked' is required
        new EmailDomain();
    }

    /**
     * Tests that the EmailDomain constraint validates the type of the 'blocked' parameter.
     * 
     * EXPLANATION:
     * -----------
     * The 'blocked' option must be an array of domain names. This test verifies that
     * when a non-array value is passed (a string in this case), the constraint throws
     * a ConstraintDefinitionException.
     * 
     * This test demonstrates:
     * 1. How to test for specific exceptions with PHPUnit
     * 2. How constraints validate their input parameters
     * 3. The importance of type checking in constructor parameters
     * 
     * This validation happens in the EmailDomain constructor, where it checks:
     * if (!is_array($options['blocked'])) throw new ConstraintDefinitionException(...)
     * 
     * @return void
     */
    public function testBadShapedBlockedParameter(): void
    {
        // Expect a ConstraintDefinitionException to be thrown
        $this->expectException(ConstraintDefinitionException::class);
        
        // Attempt to create the constraint with a string instead of an array
        new EmailDomain(options: ['blocked' => 'azeazee']); // 'azeazee' is a string, not an array
    }

    /**
     * Tests that the 'blocked' option is correctly assigned to the constraint's property.
     * 
     * EXPLANATION:
     * -----------
     * This test verifies that when a valid 'blocked' option is provided to the EmailDomain
     * constructor, the value is correctly assigned to the $blocked property of the object.
     * 
     * This test demonstrates:
     * 1. How to verify property assignment in unit tests
     * 2. How constructor options are mapped to object properties
     * 3. Basic assertion techniques in PHPUnit
     * 
     * Steps:
     * 1. Create an array of sample domain values
     * 2. Instantiate EmailDomain with this array as the 'blocked' option
     * 3. Verify that the object's $blocked property equals the provided array
     * 
     * @return void
     */
    public function testOptionIsSetAsProperty(): void
    {
        // Define a sample array of blocked domains
        $array = ['a', 'b'];
        
        // Create an EmailDomain constraint with the array
        $domain = new EmailDomain(options: ['blocked' => $array]);
        
        // Assert that the blocked property equals the array we provided
        $this->assertEquals($array, $domain->blocked);
    }
}
