<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository\User;

use Doctrine\ORM\EntityManagerInterface;
use Domain\User\User;
use Domain\User\UserRepositoryInterface;
use Infrastructure\Doctrine\Entity\UserEntity;
use Infrastructure\Doctrine\Mapper\UserMapper;

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
