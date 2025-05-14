<?php

namespace App\Factory;

use App\Entity\LockDown;
use App\Enum\LockDownStatus;
use App\Repository\LockDownRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<LockDown>
 *
 * @method        LockDown|Proxy                              create(array|callable $attributes = [])
 * @method static LockDown|Proxy                              createOne(array $attributes = [])
 * @method static LockDown|Proxy                              find(object|array|mixed $criteria)
 * @method static LockDown|Proxy                              findOrCreate(array $attributes)
 * @method static LockDown|Proxy                              first(string $sortedField = 'id')
 * @method static LockDown|Proxy                              last(string $sortedField = 'id')
 * @method static LockDown|Proxy                              random(array $attributes = [])
 * @method static LockDown|Proxy                              randomOrCreate(array $attributes = [])
 * @method static LockDownRepository|ProxyRepositoryDecorator repository()
 * @method static LockDown[]|Proxy[]                          all()
 * @method static LockDown[]|Proxy[]                          createMany(int $number, array|callable $attributes = [])
 * @method static LockDown[]|Proxy[]                          createSequence(iterable|callable $sequence)
 * @method static LockDown[]|Proxy[]                          findBy(array $attributes)
 * @method static LockDown[]|Proxy[]                          randomRange(int $min, int $max, array $attributes = [])
 * @method static LockDown[]|Proxy[]                          randomSet(int $number, array $attributes = [])
 */
final class LockDownFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return LockDown::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'reason' => self::faker()->text(),
            'status' => self::faker()->randomElement(LockDownStatus::cases()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(LockDown $lockDown): void {})
        ;
    }
}
