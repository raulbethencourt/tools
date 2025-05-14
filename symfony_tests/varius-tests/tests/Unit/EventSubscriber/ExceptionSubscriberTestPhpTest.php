<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * ExceptionSubscriberTest - Unit Test Example
 * 
 * This test class demonstrates how to properly test an event subscriber
 * that responds to exceptions by sending emails. It shows several key testing concepts:
 * 
 * 1. Testing event subscriptions using getSubscribedEvents()
 * 2. Mocking external dependencies (MailerInterface)
 * 3. Testing method calls with expectations (->method('send')->expects($this->once()))
 * 4. Using callbacks to validate complex objects like Email
 * 5. Testing different aspects of the same functionality in separate test methods
 * 
 * You can use this test class as a template for testing other event subscribers,
 * especially those that interact with external services like email.
 * 
 * @package App\Tests\Unit\EventSubscriber
 */
class ExceptionSubscriberTest extends TestCase
{
    /**
     * Test that our subscriber properly subscribes to the ExceptionEvent
     * 
     * This verifies that the subscriber is correctly configured to listen
     * for exception events in the Symfony event system.
     */
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(
            ExceptionEvent::class,
            ExceptionSubscriber::getSubscribedEvents()
        );
    }

    /**
     * Test basic functionality that an email is sent when an exception occurs
     * 
     * This is the simplest test case - it only verifies that:
     * 1. The mailer service is called
     * 2. The send method is called exactly once
     * 
     * This test doesn't inspect email content, just confirms the action happens.
     */
    public function testOnExceptionSendEmail(): void
    {
        $mailer = $this
            ->getMockBuilder(MailerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailer
            ->expects($this->once())
            ->method('send');

        /** @disregard P1006 Mock problem */
        $this->dispatch($mailer);
    }

    /**
     * Test that emails are sent with the correct sender and recipient
     * 
     * This test goes deeper than the previous one by validating:
     * 1. The 'from' address matches our configuration
     * 2. The 'to' address is set to the admin email
     * 
     * It demonstrates how to use callback assertions to inspect
     * the properties of complex objects passed to mocked methods.
     */
    public function testOnExceptionSendEmailToTheAdmin(): void
    {
        $mailer = $this
            ->getMockBuilder(MailerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(
                function (Email $email) {
                    $from = $email->getFrom();
                    $to = $email->getTo();

                    return count($from) === 1 &&
                        $from[0]->getAddress() === 'from@domain.fr' &&
                        count($to) === 1 &&
                        $to[0]->getAddress() === 'to@domain.fr';
                }
            ));

        /** @disregard P1006 Mock problem */
        $this->dispatch($mailer);
    }

    /**
     * Test that the email contains the exception trace information
     * 
     * This test validates the email content by checking:
     * 1. That a text body exists in the email
     * 2. That it contains details about the exception source
     * 3. That it contains the exception message
     * 
     * This demonstrates how to test the content of objects generated
     * during the execution of the code under test.
     */
    public function testOnExceptionSendEmailWithTheTrace(): void
    {
        $mailer = $this
            ->getMockBuilder(MailerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(
                function (Email $email) {
                    $textBody = $email->getTextBody();
                    return $textBody !== null &&
                        strpos($textBody, 'ExceptionSubscriberTest') &&
                        strpos($textBody, 'Hello World');
                }
            ));

        /** @disregard P1006 Mock problem */
        $this->dispatch($mailer);
    }

    /**
     * Helper method to dispatch an exception event
     * 
     * This private method encapsulates the common setup code for all tests:
     * 1. Creates the ExceptionSubscriber with the provided mailer and email addresses
     * 2. Creates a mock kernel and exception event
     * 3. Sets up an event dispatcher and triggers the event
     * 
     * Using this helper method keeps the test methods focused on their specific assertions
     * rather than repeating the same setup code.
     * 
     * @param MailerInterface $mailer The mock mailer service to use
     */
    private function dispatch($mailer): void
    {
        $subscriber = new ExceptionSubscriber($mailer, 'from@domain.fr', 'to@domain.fr');
        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();

        /** @disregard P1006 Mock problem */
        $event = new ExceptionEvent(
            $kernel,
            new Request(),
            1,
            new \Exception('Hello World')
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch($event);
    }
}
