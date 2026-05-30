<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\UserMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(string $id): ?User
    {
        $entity = $this->em->find(UserEntity::class, $id);

        return $entity ? UserMapper::toDomain($entity) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $entity = $this->em->getRepository(UserEntity::class)->findOneBy(['email' => $email]);

        return $entity ? UserMapper::toDomain($entity) : null;
    }

    public function save(User $user): void
    {
        $entity = UserMapper::toEntity($user);

        $this->em->persist($entity);
    }

    public function remove(User $user): void
    {
        $entity = UserMapper::toEntity($user);

        $this->em->remove($entity);
    }
}
