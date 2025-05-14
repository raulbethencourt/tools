<?php

namespace App\MessageHandler;

use App\Message\LockDownStartedNotification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class LockDownStartedNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function __invoke(LockDownStartedNotification $message)
    {
        $this->sendEmailAlert();
    }

    private function sendEmailAlert(): void
    {
        $email = (new Email())
            ->from('bob@dinotopia.com')
            ->to('staff@dinotopia.com')
            ->subject('PARK LOCKDOWN')
            ->text('RUNNNNNNN!!!!');

        $this->mailer->send($email);
    }
}
