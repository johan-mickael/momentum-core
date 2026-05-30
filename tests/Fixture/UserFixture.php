<?php

declare(strict_types=1);

namespace App\Tests\Fixture;

use App\Domain\User\Role;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Tests\Fixture\Security\SymfonyUserProxy;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture implements DependentFixtureInterface
{
    public const BUYER_USER = 'user-buyer';
    public const ADMIN_USER = 'user-admin';
    public const STAFF_USER = 'user-staff';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->users() as [$ref, $email, $role]) {
            $user = new UserEntity();

            $user->id = uuid_create(UUID_TYPE_RANDOM);
            $user->email = $email;
            $user->role = $role;
            $user->createdAt = new \DateTimeImmutable();

            $user->passwordHash = $this->hasher->hashPassword(
                new SymfonyUserProxy($email),
                'secret123'
            );

            $manager->persist($user);
            $this->addReference($ref, $user);
        }

        $manager->flush();
    }

    private function users(): array
    {
        return [
            [self::BUYER_USER, 'buyer@acme.com', Role::BUYER],
            [self::ADMIN_USER, 'admin@acme.com', Role::ADMIN],
            [self::STAFF_USER, 'staff@acme.com', Role::STAFF],
        ];
    }

    public function getDependencies(): array
    {
        return [];
    }
}
