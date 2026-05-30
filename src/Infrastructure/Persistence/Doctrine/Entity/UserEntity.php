<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Domain\User\Role;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`user`')]
class UserEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public string $id;

    #[ORM\Column]
    public string $email;

    #[ORM\Column(nullable: true)]
    public ?string $passwordHash = null;

    #[ORM\Column(nullable: true)]
    public ?string $firstName = null;

    #[ORM\Column(nullable: true)]
    public ?string $lastName = null;

    #[ORM\Column(enumType: Role::class)]
    public Role $role;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    public \DateTimeImmutable $updatedAt;
}
