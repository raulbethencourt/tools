<?php

namespace App\Tests\Integration\Repository;

use App\Enum\LockDownStatus;
use App\Factory\LockDownFactory;
use App\Repository\LockDownRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @package App\Tests\Integration\Repository
 */
class LockDownRepositoryTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    public function testIsInLockDownWithNoLockDownRows(): void
    {
        self::bootKernel();

        $this->assertFalse($this->getlockDownRepository()->isInLockDown());
    }

    public function testIsInLockDownReturnsTrueIfMostRecentLockDownIsActive(): void
    {
        self::bootKernel();

        LockDownFactory::createOne([
            'createdAt' => new \DateTimeImmutable('-1 day'),
            'status' => LockDownStatus::ACTIVE
        ]);
        LockDownFactory::createMany(5, [
            'createdAt' => new \DateTimeImmutable('-2 day'),
            'status' => LockDownStatus::ENDED
        ]);

        $this->assertTrue($this->getlockDownRepository()->isInLockDown());
    }

    public function testIsInLockDownReturnsFalseIfMostRecentIsNotActive(): void
    {
        self::bootKernel();

        LockDownFactory::createOne([
            'createdAt' => new \DateTimeImmutable('-1 day'),
            'status' => LockDownStatus::ENDED
        ]);
        LockDownFactory::createMany(5, [
            'createdAt' => new \DateTimeImmutable('-2 day'),
            'status' => LockDownStatus::ACTIVE
        ]);

        $this->assertFalse($this->getlockDownRepository()->isInLockDown());
    }

    public function getLockDownRepository(): LockDownRepository
    {
        return self::getContainer()->get(LockDownRepository::class);
    }
}
