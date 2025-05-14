<?php

namespace App\Tests\Unit\Controller;

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test Suite for SecurityController
 * 
 * This class demonstrates best practices for testing authentication functionality in Symfony applications:
 * - How to test page rendering (display login page)
 * - How to test form submission with invalid credentials
 * - How to test successful authentication with valid credentials using fixtures
 * 
 * The tests follow a consistent pattern:
 * 1. Set up the test environment
 * 2. Execute the action being tested
 * 3. Assert the expected outcomes
 * 
 * WHY THIS MATTERS:
 * - Authentication tests are critical for security concerns
 * - Testing different scenarios (success, failure) ensures robust authentication logic
 * - This pattern ensures comprehensive coverage of login functionality
 * - These tests can catch regressions in security-related code
 * 
 * @package App\Tests\Unit\Controller
 */
class SecurityControllerTest extends WebTestCase
{
    /**
     * Test that the login page displays correctly
     * 
     * This test verifies:
     * 1. The login page is accessible
     * 2. It returns a 200 OK status code
     * 3. It contains the expected heading
     * 4. No error messages are initially displayed
     */
    public function testDisplayLogin(): void
    {
        // Create a client that simulates a browser - this initializes the kernel in test environment
        // and allows us to make requests without an actual browser
        $client = static::createClient();
        
        // Make a GET request to the login page - simulates a user visiting the page
        $client->request('GET', '/login');
        
        // Verify the response has a 200 OK status - ensures the page is accessible
        // Using constants from Response class improves readability and maintainability
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        // Check that the page contains the expected heading - verifies correct template is rendered
        $this->assertSelectorTextContains('h1', 'Please sign in');
        
        // Verify no error messages are shown initially - ensures clean state for first-time visitors
        $this->assertSelectorNotExists('.alert.alert-danger');
    }

    /**
     * Test login attempt with invalid credentials
     * 
     * This test verifies:
     * 1. When using invalid credentials, the authentication fails
     * 2. The user is redirected back to the login page
     * 3. An error message is displayed to the user
     * 
     * The test demonstrates how to:
     * - Find and interact with a form in the page
     * - Submit form data with specific values
     * - Follow redirects to examine the resulting page
     * - Check for the presence of error messages
     */
    public function testLoginWithBadCredentials(): void
    {
        $client = static::createClient();
        
        // Request the login page and get a Crawler object to interact with the DOM
        // The Crawler helps us locate elements like forms and buttons
        $crawler = $client->request('GET', '/login');
        
        // Find the form by its submit button and fill it with invalid credentials
        // This approach is robust because it finds the right form even if there are multiple forms
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'chachote@domain.fr',
            'password' => 'te la mandaste'
        ]);
        
        // Submit the form - this simulates the user clicking the submit button
        $client->submit($form);
        
        // Verify we're redirected back to login page (failed authentication pattern)
        $this->assertResponseRedirects('/login');
        
        // Follow the redirect to see the resulting page - essential to verify error messages
        $client->followRedirect();
        
        // Verify an error message is displayed - confirms proper error handling
        $this->assertSelectorExists('.alert.alert-danger');
    }

    /**
     * Test successful login with valid credentials
     * 
     * This test verifies:
     * 1. A user with valid credentials can log in successfully
     * 2. After login, they are redirected to the protected page
     * 
     * The test demonstrates:
     * - How to use fixtures to prepare test data (create a test user)
     * - How to submit login credentials through a form
     * - How to verify successful authentication by checking the redirect
     * 
     * Note: The commented code shows an alternative approach for direct form submission
     * which might be useful in certain scenarios where you need more control over the
     * submission process, including handling CSRF tokens manually.
     */
    public function testSuccessfullLogin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Load test users from fixture file
        // This creates predictable, isolated test data for each test run
        // The database is reset between tests, preventing cross-test contamination
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadAliceFixture([
            dirname(__DIR__) . '/fixtures/user_login.yaml',
        ]);

        // Submit login form with valid credentials
        // Using the form submission approach tests the entire authentication flow
        // including form rendering, validation, and processing
        $form = $crawler->selectButton('Sign in')->form([
        'email' => 'user@domain.fr',
        'password' => '000000'
        ]);
        $client->submit($form);

        // Direct test example
        /* $csrfToken = $client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate'); */
        /* $client->request('POST', '/login', [ */
        /*     '_csrf_token' => $csrfToken, */
        /*     'email' => 'user@domain.fr', */
        /*     'password' => '000000' */
        /* ]); */

        // Verify successful login by checking redirect to protected area
        // A redirect to a protected area indicates successful authentication
        $this->assertResponseRedirects('/auth');
    }
}
