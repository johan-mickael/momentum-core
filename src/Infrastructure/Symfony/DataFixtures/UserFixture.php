<?php

declare(strict_types=1);

namespace Infrastructure\Symfony\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Domain\User\Role;
use Infrastructure\Doctrine\Entity\UserEntity;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserFixture extends Fixture
{
    private const int BATCH = 200;

    // Named references for use in other fixtures
    public const string BUYER = 'dev-user-buyer';
    public const string STAFF = 'dev-user-staff';
    public const string ADMIN = 'dev-user-admin';

    /** @var array<string> */
    private static array $FIRST_NAMES = [
        'Alice', 'Bob', 'Carol', 'David', 'Eva', 'François', 'Gabrielle', 'Hugo',
        'Isabelle', 'Jules', 'Karine', 'Luc', 'Marie', 'Nathan', 'Olivia', 'Paul',
        'Quentin', 'Rachel', 'Samuel', 'Théa', 'Ugo', 'Valérie', 'William', 'Xavier',
        'Yasmine', 'Zoé', 'Antoine', 'Béatrice', 'Clément', 'Diane', 'Emile', 'Flore',
        'Gilles', 'Hélène', 'Igor', 'Juliette', 'Kevin', 'Laura', 'Marc', 'Nathalie',
        'Omar', 'Patricia', 'Rémi', 'Sophie', 'Thomas', 'Ursula', 'Victor', 'Wendy',
    ];

    /** @var array<string> */
    private static array $LAST_NAMES = [
        'Dupont', 'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand',
        'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David',
        'Bertrand', 'Roux', 'Vincent', 'Fournier', 'Morel', 'Girard', 'André', 'Lefèvre',
        'Mercier', 'Dupuis', 'Lambert', 'Bonnet', 'François', 'Martinez', 'Legrand', 'Garnier',
        'Faure', 'Rousseau', 'Blanc', 'Guerin', 'Muller', 'Henry', 'Roussel', 'Nicolas',
        'Perrin', 'Morin', 'Mathieu', 'Clement', 'Gauthier', 'Dumont', 'Lopez', 'Fontaine',
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $hash = $this->hasher->hashPassword($this->proxy('fixture@whenn.dev'), 'secret123');

        $i = 0;
        foreach ($this->userData($hash) as [$ref, $entity]) {
            $manager->persist($entity);
            if (null !== $ref) {
                $this->addReference($ref, $entity);
            }

            if (0 === ++$i % self::BATCH) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
        $manager->clear();
    }

    /** @return iterable<int, array{string|null, UserEntity}> */
    private function userData(string $hash): iterable
    {
        $firstNames = self::$FIRST_NAMES;
        $lastNames = self::$LAST_NAMES;
        $fCount = count($firstNames);
        $lCount = count($lastNames);

        // Named fixtures first (guaranteed to land before first flush)
        yield from [
            [self::BUYER, $this->makeEntity('buyer@whenn.dev', 'Alice', 'Dupont', Role::BUYER, $hash)],
            [self::STAFF, $this->makeEntity('staff@whenn.dev', 'Bob', 'Martin', Role::STAFF, $hash)],
            [self::ADMIN, $this->makeEntity('admin@whenn.dev', 'Carol', 'Leblanc', Role::ADMIN, $hash)],
        ];

        // ~900 buyers
        for ($n = 0; $n < 900; ++$n) {
            $first = $firstNames[$n % $fCount];
            $last = $lastNames[$n % $lCount];
            yield [null, $this->makeEntity("buyer{$n}@whenn.dev", $first, $last, Role::BUYER, $hash)];
        }

        // ~70 staff
        for ($n = 0; $n < 70; ++$n) {
            $first = $firstNames[($n + 10) % $fCount];
            $last = $lastNames[($n + 10) % $lCount];
            yield [null, $this->makeEntity("staff{$n}@whenn.dev", $first, $last, Role::STAFF, $hash)];
        }

        // ~27 admins
        for ($n = 0; $n < 27; ++$n) {
            $first = $firstNames[($n + 20) % $fCount];
            $last = $lastNames[($n + 20) % $lCount];
            yield [null, $this->makeEntity("admin{$n}@whenn.dev", $first, $last, Role::ADMIN, $hash)];
        }
    }

    private function makeEntity(string $email, string $first, string $last, Role $role, string $hash): UserEntity
    {
        $e = new UserEntity();
        $e->id = uuid_create(UUID_TYPE_RANDOM);
        $e->email = $email;
        $e->firstName = $first;
        $e->lastName = $last;
        $e->role = $role;
        $e->createdAt = new \DateTimeImmutable();
        $e->passwordHash = $hash;

        return $e;
    }

    private function proxy(string $email): UserInterface&PasswordAuthenticatedUserInterface
    {
        return new class($email) implements UserInterface, PasswordAuthenticatedUserInterface {
            public function __construct(private readonly string $email)
            {
            }

            public function getUserIdentifier(): string
            {
                return $this->email;
            }

            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getPassword(): ?string
            {
                return null;
            }
        };
    }
}
