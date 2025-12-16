<?php

namespace App\Tests\Unit\Controller;

use App\Tests\Unit\NeedLogin;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;

/**
 * Test Suite for PageController
 * 
 * This class demonstrates a variety of Symfony testing techniques:
 * - Basic page rendering tests
 * - Authentication and authorization tests
 * - Role-based access control tests
 * - Testing email functionality with the profiler
 * 
 * The class uses the NeedLogin trait to simplify testing with authenticated users.
 * It also demonstrates how to use fixtures to create test users with different roles.
 *
 * WHY THIS MATTERS:
 * - Having a comprehensive test suite ensures your application behaves as expected
 * - Testing different user roles validates your security implementation
 * - The variety of test types provides full coverage of controller functionality
 * - These patterns can be reused across your application's test suite
 * 
 * @package App\Tests\Unit\Controller
 */
class PageControllerTest extends WebTestCase
{
    /**
     * Import the NeedLogin trait which provides helper methods for authenticating
     * users in tests, making it easier to test protected routes
     * 
     * WHY IT MATTERS:
     * - Encapsulates the complex logic of authenticating a user in tests
     * - Makes tests more readable by hiding authentication implementation details
     * - Allows for consistent authentication across multiple test classes
     * - Separates authentication concerns from the actual test logic
     */
    use NeedLogin;

    /**
     * Test that a public page renders correctly
     * 
     * This basic test verifies that a non-secured page:
     * 1. Is accessible (returns 200 OK status)
     * 2. Contains the expected content
     */
    public function testHelloPage(): void
    {
        // Create a simulated browser client to interact with the application
        $client = static::createClient();
        
        // Make a request to the hello page - tests public page accessibility
        $client->request('GET', '/hello');
        
        // Verify the status code is 200 OK - page loads successfully
        // Using constants instead of hardcoded values improves maintainability
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        // Check that the expected content is present - validates template rendering
        $this->assertSelectorTextContains('h1', 'Welcome to my site');
    }

    /**
     * Test that a protected page returns redirect response when accessed anonymously
     * 
     * This test checks that:
     * 1. The /auth route requires authentication
     * 2. Unauthenticated users are redirected to login (302 Found)
     */
    public function testAuthPageIsRestricted(): void
    {
        // Create a client with no authenticated user
        $client = static::createClient();
        
        // Attempt to access a protected route - simulates anonymous user
        $client->request('GET', '/auth');
        
        // Verify redirect to login page - validates security is working
        $this->assertResponseRedirects('/login');
        
        // Check the correct HTTP status (302 Found) for redirect
        // This ensures the security system is handling unauthorized access properly
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Verify the protected page redirects specifically to the login page
     * 
     * This test focuses exclusively on the redirection destination.
     */
    public function testAuthPageRedirectToLogin(): void
    {
        // Create a test client without authentication
        $client = static::createClient();
        
        // Attempt to access a route that requires authentication
        // This tests the security system's handling of anonymous requests
        $client->request('GET', '/auth');
        
        // Verify that the security system redirects to the login page
        // This is a key part of the authentication flow - unauthorized users should be directed to login
        $this->assertResponseRedirects('/login');
    }

    /**
     * Test that authenticated users can access protected pages
     * 
     * This test demonstrates:
     * 1. How to load test fixtures to create test users
     * 2. How to log in as a specific user using the NeedLogin trait
     * 3. How to verify access to a protected resource
     */
    public function testLetAuthenticatedUserAccessAuth(): void
    {
        $client = static::createClient();
        
        // Load fixtures to create test users with predefined roles
        // This creates isolated test data that won't interfere with other tests
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $users = $databaseTool->loadAliceFixture([
            dirname(__DIR__) . '/fixtures/user_login.yaml',
        ]);
        
        // Use the NeedLogin trait to authenticate as a regular user
        // This abstracts away the complexity of the authentication process
        $this->login($client, $users['user_user']);
        
        // Try to access a protected route that requires authentication
        $client->request('GET', '/auth');
        
        // Verify access is granted (HTTP 200) - confirms authentication works
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test that users without admin role cannot access admin pages
     * 
     * This test verifies:
     * 1. A user with only ROLE_USER permissions is denied access
     * 2. The application returns HTTP 403 Forbidden (not 401 Unauthorized)
     * 
     * Note: This tests security based on roles, not just authentication
     */
    public function testAdminRequireAdminRole(): void
    {
        // Create a client for testing
        $client = static::createClient();
        
        // Load fixtures to create test users with different roles
        // This provides consistent test data with predefined permissions
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $users = $databaseTool->loadAliceFixture([
            dirname(__DIR__) . '/fixtures/user_login.yaml',
        ]);
        
        // Authenticate as a regular user WITHOUT admin privileges
        // This tests the negative case - a user who shouldn't have access
        $this->login($client, $users['user_user']);
        
        // Attempt to access an admin-only route
        // This tests that role-based access control (RBAC) is properly implemented
        $client->request('GET', '/admin');
        
        // Verify access is denied with 403 Forbidden (not 401 Unauthorized)
        // This is the correct status code when a user is authenticated but lacks permissions
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test that users with admin role can access admin pages
     * 
     * This test complements the previous one by verifying:
     * 1. A user with ROLE_ADMIN permissions can access admin pages
     * 2. The application correctly differentiates between user roles
     */
    public function testAdminRequireAdminRoleWithSufficientRole(): void
    {
        // Create test client to simulate a browser
        $client = static::createClient();
        
        // Load test fixtures to create users with predefined roles
        // This is the positive test case counterpart to testAdminRequireAdminRole
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $users = $databaseTool->loadAliceFixture([
            dirname(__DIR__) . '/fixtures/user_login.yaml',
        ]);
        
        // Authenticate as a user WITH admin privileges
        // This tests the positive case - a user who should have access
        $this->login($client, $users['user_admin']);
        
        // Attempt to access the admin route with proper permissions
        // This verifies that the role hierarchy is correctly implemented
        $client->request('GET', '/admin');
        
        // Verify access is granted with HTTP 200 OK
        // This confirms that the authorization system properly recognizes admin privileges
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test that emails are properly sent by the application
     * 
     * This advanced test demonstrates:
     * 1. How to enable and use the Symfony profiler in tests
     * 2. How to access collected data from the profiler
     * 3. How to inspect email messages sent during the request
     * 4. How to verify email recipients and content
     */
    public function testMailSendEmail(): void
    {
        // Create a client and enable the profiler to collect data about the request
        // The profiler is essential for testing "invisible" features like emails
        $client = static::createClient();
        $client->enableProfiler();
        
        // Request the page that should trigger an email
        $client->request('GET', '/mail');
        
        // Access the mailer collector from the profiler to inspect sent emails
        // This lets us test that emails are actually being sent without sending real emails
        $mailCollector = $client->getProfile()->getCollector('mailer');
        assert($mailCollector instanceof MessageDataCollector);
        
        // Get all emails that were sent during this request
        $messages = $mailCollector->getEvents()->getMessages();
        
        // Verify that exactly one email was sent
        $this->assertEquals(
            1,
            count($messages)
        );
        
        // Verify the recipient address is correct - validates email addressing
        $emailToAddress = $messages[0]->getTo()[0]->getAddress();
        $this->assertEquals('contact@domain.fr', $emailToAddress);
    }
}
