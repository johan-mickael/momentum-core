<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures;

use App\Domain\Event\EventStatus;
use App\Infrastructure\Persistence\Doctrine\Entity\EventEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\TicketCategoryEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\VenueEntity;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

final class EventFixture extends Fixture implements DependentFixtureInterface
{
    private const int   TOTAL     = 1000;
    private const int   BATCH     = 50;
    private static array $GENRES = [
        'Jazz & Blues', 'Électro', 'Rock', 'Classique', 'Hip-Hop', 'Variété Française',
        'Pop', 'Metal', 'R&B', 'Reggae', 'Folk', 'Opéra', 'Techno', 'House', 'World Music',
        'Chanson Française', 'Indie', 'Soul', 'Punk', 'Afrobeat',
    ];

    private static array $ADJECTIVES = [
        'Festival', 'Nuit de', 'Soirée', 'Grand Concert', 'Gala', 'Spectacle',
        'Showcase', 'Tour', 'Live', 'Session', 'Cabaret', 'Rencontres',
    ];

    private static array $ARTISTS = [
        'Phoenix', 'Stromae', 'Angèle', 'Indochine', 'Orelsan', 'Clara Luciani',
        'Bertrand Cantat', 'Grand Corps Malade', 'Zaz', 'Benjamin Biolay', 'Yannick Noah',
        'Julien Doré', 'Vianney', 'Soprano', 'Kendji Girac', 'Louane', 'Patrick Bruel',
        'Charles Aznavour Tribute', 'Edith Piaf Tribute', 'Vanessa Paradis',
        'Christophe Maé', 'Calogero', 'Gims', 'Oxmo Puccino', 'IAM', 'NTM Tribute',
        'Booba', 'Nekfeu', 'SCH', 'Lomepal', 'Hamza', 'Laylow', 'Jorja Smith',
        'Massive Attack', 'Portishead', 'Air', 'Daft Punk Legacy', 'Laurent Garnier',
        'Bob Sinclar', 'David Guetta', 'Martin Solveig', 'DJ Snake', 'Gesaffelstein',
        'The Blaze', 'Polo & Pan', 'FKJ', 'Woodkid', 'Rone', 'Fakear',
        'Orchestre National', 'Quatuor Debussy', 'Les Arts Florissants',
    ];

    private static array $CATEGORIES_TEMPLATES = [
        [['Fosse',    2000, 500], ['Tribune',  3500, 300], ['Carré VIP', 8000,  50]],
        [['Standard', 2500, 800], ['Premium',  5000, 200]],
        [['Pelouse',  1500, 2000], ['Tribune', 3000, 1000], ['Loges', 15000, 20]],
        [['Early Bird', 1500, 500], ['Regular', 2500, 3000], ['Late', 3500, 1500]],
        [['Parterre', 3500, 400], ['Balcon 1', 4500, 200], ['Balcon 2', 5500, 100]],
        [['Général', 2000, 1500], ['VIP', 7500, 100]],
        [['Arène', 1800, 5000], ['Tribune Nord', 2500, 2000], ['Tribune Sud', 2500, 2000], ['Loges Prestige', 20000, 30]],
        [['Debout', 2000, 1000], ['Assis', 3000, 500]],
    ];

    public function __construct(private readonly EntityManagerInterface $em) {}

    public function getDependencies(): array
    {
        return [VenueFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $venueIds = $this->loadVenueIds();
        $batch    = 0;

        for ($i = 0; $i < self::TOTAL; $i++) {
            $venueId = $venueIds[$i % count($venueIds)];
            $venue   = $manager->find(VenueEntity::class, $venueId);
            $status  = $this->status($i);
            $start   = $this->startDate($i, $status);

            $event = new EventEntity(
                id:          uuid_create(UUID_TYPE_RANDOM),
                venue:       $venue,
                name:        $this->eventName($i),
                slug:        $this->slug($i),
                startAt:     $start,
                endAt:       $start->modify('+3 hours'),
                status:      $status,
                description: $this->description($i),
            );

            foreach ($this->categories($i, $event) as $category) {
                $event->addTicketCategory($category);
                $manager->persist($category);
            }

            $manager->persist($event);

            if (++$batch % self::BATCH === 0) {
                $manager->flush();
                $manager->clear();
                // Re-load venue IDs (entities detached after clear)
                $venueIds = $this->loadVenueIds();
            }
        }

        $manager->flush();
        $manager->clear();
    }

    /** @return string[] */
    private function loadVenueIds(): array
    {
        return $this->em->createQuery(
            'SELECT v.id FROM App\Infrastructure\Persistence\Doctrine\Entity\VenueEntity v'
        )->getSingleColumnResult();
    }

    private function status(int $i): EventStatus
    {
        return match (true) {
            $i < 600  => EventStatus::PUBLISHED,
            $i < 800  => EventStatus::DRAFT,
            $i < 950  => EventStatus::ENDED,
            default   => EventStatus::CANCELLED,
        };
    }

    private function startDate(int $i, EventStatus $status): DateTimeImmutable
    {
        $base = new DateTimeImmutable('2026-01-01');

        return match ($status) {
            EventStatus::PUBLISHED  => $base->modify(sprintf('+%d days +%d hours', $i % 365, ($i * 3) % 12 + 18)),
            EventStatus::DRAFT      => $base->modify(sprintf('+%d days', $i % 200 + 300)),
            EventStatus::ENDED      => $base->modify(sprintf('-%d days', $i % 365 + 30)),
            EventStatus::CANCELLED  => $base->modify(sprintf('+%d days', $i % 100 + 10)),
        };
    }

    private function eventName(int $i): string
    {
        $adj    = self::$ADJECTIVES[$i % count(self::$ADJECTIVES)];
        $artist = self::$ARTISTS[$i % count(self::$ARTISTS)];
        $year   = 2026 + ($i % 2);

        return "{$adj} {$artist} {$year}";
    }

    private function slug(int $i): string
    {
        return 'event-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
    }

    private function description(int $i): ?string
    {
        if ($i % 5 === 0) {
            return null;
        }

        $genre = self::$GENRES[$i % count(self::$GENRES)];

        return "Un événement exceptionnel de {$genre}. Retrouvez-nous pour une soirée inoubliable avec les meilleurs artistes du moment.";
    }

    /** @return TicketCategoryEntity[] */
    private function categories(int $i, EventEntity $event): array
    {
        $template = self::$CATEGORIES_TEMPLATES[$i % count(self::$CATEGORIES_TEMPLATES)];

        return array_map(
            fn(array $cat) => new TicketCategoryEntity(
                id:               uuid_create(UUID_TYPE_RANDOM),
                event:            $event,
                name:             $cat[0],
                priceAmount:      $cat[1],
                priceCurrency:    'EUR',
                totalQuantity:    $cat[2],
                reservedQuantity: 0,
            ),
            $template,
        );
    }
}
