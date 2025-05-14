<?php

namespace App\Tests\Integration\Service;

use App\Enum\LockDownStatus;
use App\Factory\LockDownFactory;
use App\Service\GithubService;
use App\Service\LockDownHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class LockDownHelperTest extends KernelTestCase
{
    use ResetDatabase, Factories;
    use InteractsWithMailer;
    use InteractsWithMessenger;

    public function testEndCurrentLockdown(): void
    {
        self::bootKernel();

        $lockDown = LockDownFactory::createOne([
            'status' => LockDownStatus::ACTIVE
        ]);

        $gitHubService = $this->createMock(GithubService::class);
        $gitHubService
            ->expects($this->once())
            ->method('clearLockDownAlerts');
        self::getContainer()->set(GithubService::class, $gitHubService);

        $this->getLockDownHelper()->endCurrentLockDown();
        $this->assertSame(LockDownStatus::ENDED, $lockDown->getStatus());
    }

    public function testDinoEscapedPersistsLockDown(): void
    {
        self::bootKernel();

        $this->transport()->queue()->assertEmpty();

        $this->getLockDownHelper()->dinoEscaped();
        LockDownFactory::repository()->assert()->count(1);

        $this->transport()->processOrFail();

        $this->mailer()->assertSentEmailCount(1);
        $this->mailer()->assertEmailSentTo('staff@dinotopia.com', 'PARK LOCKDOWN');
    }

    private function getLockDownHelper(): LockDownHelper
    {
        return self::getContainer()->get(LockDownHelper::class);
    }
}
