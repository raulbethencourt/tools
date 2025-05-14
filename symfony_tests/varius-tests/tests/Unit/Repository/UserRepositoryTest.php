<?php

namespace App\Tests\Repository;

use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * ======== REPOSITORY TESTING MASTERCLASS ========
 * 
 * This class serves as an educational example for properly testing Symfony repositories.
 * 
 * === OBJECTIVE ===
 * To demonstrate how to efficiently test repository methods using fixtures and the Symfony test container,
 * focusing on database interaction testing in isolation from other components.
 * 
 * === KEY CONCEPTS DEMONSTRATED ===
 * 1. Using KernelTestCase for repository testing
 * 2. Setting up a test database with LiipTestFixturesBundle
 * 3. Loading Alice fixtures for predictable test data
 * 4. Accessing the repository through the service container
 * 5. Testing repository methods with assertions
 * 
 * === WHEN TO USE THIS PATTERN ===
 * - When you need to test repository methods that interact with the database
 * - When you want to verify custom query methods work correctly
 * - When testing complex database operations like joins, filters, etc.
 * - When you need a predictable dataset for testing repository behavior
 * 
 * === TEST REQUIREMENTS ===
 * - Configured LiipTestFixturesBundle
 * - Alice fixtures for test data generation
 * - Properly registered repository services
 * - Test database configuration
 *
 * @package App\Tests\Repository
 */
class UserRepositoryTest extends KernelTestCase
{
    /**
     * @var AbstractDatabaseTool
     * 
     * SETUP COMPONENT: DATABASE TOOL
     * This tool allows us to load fixtures and manage the test database state.
     * It's a core part of the LiipTestFixturesBundle that enables database isolation for tests.
     */
    protected $databaseTool;

    /**
     * TEST INITIALIZATION METHOD
     * ===========================
     * 
     * PURPOSE:
     * Sets up the testing environment before each test method is executed.
     * 
     * HOW IT WORKS:
     * 1. Boots the Symfony kernel to access the service container
     * 2. Calls the parent setUp method to ensure proper test initialization
     * 3. Gets the database tool from the container to manipulate test data
     * 
     * TEACHING POINTS:
     * - Always boot the kernel before accessing the container in KernelTestCase
     * - The DatabaseToolCollection service provides access to database manipulation tools
     * - This pattern ensures each test runs with a fresh database state
     */
    public function setUp(): void
    {
        self::bootKernel();

        parent::setUp();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    /**
     * REPOSITORY COUNT METHOD TEST
     * ============================
     * 
     * PURPOSE:
     * Demonstrates how to test a repository's count method using fixtures
     * to verify that the repository correctly counts database records.
     * 
     * TEST STEPS:
     * 1. Load fixtures to create a predictable dataset (20 user records)
     * 2. Get the repository from the service container
     * 3. Call the count method being tested
     * 4. Assert that the result matches the expected count (20)
     * 
     * TEACHING POINTS:
     * - Fixtures provide a consistent, predictable dataset for testing
     * - The Alice YAML fixture format allows for creation of complex test data
     * - Accessing repositories through the container ensures you test the actual service
     * - Specific assertion messages help identify test failures quickly
     * 
     * EXPECTED OUTCOME:
     * The count method returns exactly 20, matching our fixture dataset size
     */
    public function testCount(): void
    {
        $this->databaseTool->loadAliceFixture([
            dirname(__DIR__) . '/fixtures/user_repository.yaml',
        ]);

        $userRepository = static::getContainer()->get(UserRepository::class);

        $userCount = $userRepository->count([]);

        $this->assertEquals(20, $userCount, 'The number of users should be 20');
    }

    /**
     * TEST CLEANUP METHOD
     * ===================
     * 
     * PURPOSE:
     * Cleans up resources after each test to prevent memory leaks and test interference.
     * 
     * HOW IT WORKS:
     * 1. Calls the parent tearDown method to perform standard cleanup
     * 2. Explicitly unsets the database tool to free resources
     * 
     * TEACHING POINTS:
     * - Proper cleanup prevents memory leaks in test suites
     * - Releasing database connections helps maintain test isolation
     * - Always call parent::tearDown() to ensure proper cleanup chain
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }
}
