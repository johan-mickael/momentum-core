<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'venue')]
class VenueEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public string $id;

    #[ORM\Column]
    public string $name;

    #[ORM\Column]
    public string $street;

    #[ORM\Column]
    public string $city;

    #[ORM\Column]
    public string $zipCode;

    #[ORM\Column]
    public string $country;

    #[ORM\Column]
    public float $latitude;

    #[ORM\Column]
    public float $longitude;

    #[ORM\Column(type: 'integer')]
    public int $capacity;

    #[ORM\OneToMany(targetEntity: EventEntity::class, mappedBy: 'venue')]
    private Collection $events;
}
