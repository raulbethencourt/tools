<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * @package App\EventSubscriber
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private string $from = '',
        private string $to = ''
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event)
    {
        if (empty($this->from) || empty($this->to))
            return;

        $message = (new Email())
            ->from($this->from)
            ->to($this->to)
            ->text("{$event->getRequest()->getRequestUri()}
                
                
{$event->getThrowable()->getMessage()}

                
{$event->getThrowable()->getTraceAsString()}");

        $this->mailer->send($message);
    }
}
