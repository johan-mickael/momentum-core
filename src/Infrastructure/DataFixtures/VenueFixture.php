<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures;

use App\Infrastructure\Persistence\Doctrine\Entity\VenueEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class VenueFixture extends Fixture
{
    // Stable references for EventFixture
    public const string OLYMPIA        = 'dev-venue-0';
    public const string GRAND_PALAIS   = 'dev-venue-1';
    public const string ZENITH         = 'dev-venue-2';

    // All references follow the pattern "dev-venue-{n}"
    public const string REF_PREFIX = 'dev-venue-';

    private static array $VENUES = [
        // Paris
        ["L'Olympia",             '28 Bd des Capucines',               'Paris',      '75009', 'France',  48.8713,  2.3313,  2000],
        ['Grand Palais',          '3 Av. du Général Eisenhower',        'Paris',      '75008', 'France',  48.8660,  2.3134, 10000],
        ['Zénith de Paris',       'Av. Jean Jaurès',                    'Paris',      '75019', 'France',  48.8927,  2.3932,  6300],
        ['Accor Arena',           '8 Bd de Bercy',                      'Paris',      '75012', 'France',  48.8384,  2.3780, 20300],
        ['La Cigale',             '120 Bd de Rochechouart',             'Paris',      '75018', 'France',  48.8839,  2.3322,  1200],
        ['Le Bataclan',           '50 Bd Voltaire',                     'Paris',      '75011', 'France',  48.8588,  2.3712,  1500],
        ['Philharmonie de Paris', '221 Av. Jean Jaurès',                'Paris',      '75019', 'France',  48.8896,  2.3943,  2400],
        ['Stade de France',       'Rue Francis de Pressensé',           'Saint-Denis','93216', 'France',  48.9244,  2.3601, 81000],
        ['La Gaîté Lyrique',      '3 bis Rue Papin',                    'Paris',      '75003', 'France',  48.8645,  2.3526,   900],
        ['Le Trianon',            '80 Bd de Rochechouart',              'Paris',      '75018', 'France',  48.8836,  2.3340,  1600],
        // Lyon
        ['Halle Tony Garnier',    '20 Pl. des Docteurs Mérieux',        'Lyon',       '69007', 'France',  45.7334,  4.8251, 17000],
        ['Transbordeur',          '3 Bd Stalingrad',                    'Villeurbanne','69100','France',  45.7665,  4.8667,  1500],
        ['Amphithéâtre Romain',   '6 Rue de l\'Antiquaille',            'Lyon',       '69005', 'France',  45.7621,  4.8183,  3000],
        ['Fourvière',             'Montée de Fourvière',                'Lyon',       '69005', 'France',  45.7622,  4.8168,  3100],
        // Marseille
        ['Le Dôme',               '48 Av. de Saint-Just',               'Marseille',  '13004', 'France',  43.3064,  5.4050,  9500],
        ['Palais Longchamp',      '1 Pl. Henri Dunant',                 'Marseille',  '13004', 'France',  43.2988,  5.3973,  1200],
        ['Espace Julien',         '39 Cours Julien',                    'Marseille',  '13006', 'France',  43.2929,  5.3811,   600],
        // Bordeaux
        ['Arkéa Arena',           '73 Rue Michel Labrousse',            'Floirac',    '33270', 'France',  44.8348, -0.5275, 12000],
        ['Le Rocher de Palmer',   '1 Rue Aristide Briand',              'Cenon',      '33150', 'France',  44.8477, -0.5277,  1900],
        ['Grand Théâtre',         'Pl. de la Comédie',                  'Bordeaux',   '33000', 'France',  44.8412, -0.5724,  1114],
        // Toulouse
        ['Zénith Toulouse',       '11 Av. Raymond Badiou',              'Toulouse',   '31300', 'France',  43.5879,  1.4289,  9000],
        ['Brique Rouge',          'Rue Saint-Pierre',                   'Toulouse',   '31000', 'France',  43.5975,  1.4310,   800],
        // Nantes
        ['Zénith Nantes',         'Bd du Zénith',                       'Saint-Herblain','44800','France',47.2208, -1.6142,  9000],
        ['Le Ferrailleur',        '3 Quai Fernand Crouan',              'Nantes',     '44100', 'France',  47.2066, -1.5736,   800],
        // Strasbourg
        ['Zénith Strasbourg',     '1 Allée du Zénith',                  'Strasbourg', '67200', 'France',  48.5615,  7.7335,  9900],
        // Lille
        ['Zénith Lille',          'Arena Blvd',                         'Lille',      '59800', 'France',  50.6163,  3.1453,  7000],
        ['Grand Palais de Lille', '1 Bd des Cités Unies',               'Lille',      '59777', 'France',  50.6264,  3.0817, 25000],
        // Nice
        ['Acropolis',             '1 Esplanade J.F. Kennedy',           'Nice',       '06302', 'France',  43.6942,  7.2657,  2500],
        // Rennes
        ['L\'Étage',              '26 Rue Penhoët',                     'Rennes',     '35000', 'France',  48.1142, -1.6766,   600],
        ['Zénith Rennes',         'Route de la Boulaie',                'Saint-Jacques-de-la-Lande','35136','France',48.0628,-1.7143,9000],
        // Montpellier
        ['Zénith Montpellier',    '50 Rue Agropolis',                   'Montpellier','34090', 'France',  43.6381,  3.8632,  9000],
        // Grenoble
        ['Summum',                '4 Allée du Sénateur Drouot',         'Grenoble',   '38100', 'France',  45.2042,  5.7263,  3600],
        // Tours
        ['Le Vinci',              'Pl. François Sicard',                'Tours',      '37000', 'France',  47.3942,  0.6871,  2000],
        // Nîmes
        ['Arènes de Nîmes',       'Bd des Arènes',                      'Nîmes',      '30000', 'France',  43.8359,  4.3597, 16000],
        // Avignon
        ['Palais des Papes',      'Pl. du Palais',                      'Avignon',    '84000', 'France',  43.9512,  4.8060,  2000],
        // Reims
        ['Reims Arena',           '1 Allée du Boulingrin',              'Reims',      '51100', 'France',  49.2517,  4.0312,  7200],
        // Rouen
        ['Zénith de Rouen',       '2 Voie Verte',                       'Le Grand-Quevilly','76120','France',49.4077,1.0417,6500],
        // Caen
        ['Zénith Caen',           '17 Rue Claude Bloch',                'Caen',       '14000', 'France',  49.1802, -0.3659,  6000],
        // Clermont-Ferrand
        ['La Coopérative de Mai', '6 Rue de Rabanesse',                 'Clermont-Ferrand','63000','France',45.7676,3.0801,1200],
        // Brest
        ['La Carène',             '1 Bd de Plymouth',                   'Brest',      '29200', 'France',  48.3848, -4.5043,  1500],
        // Dijon
        ['Zenith de Dijon',       'Quai Nicolas Rolin',                 'Dijon',      '21000', 'France',  47.3263,  5.0320,  9500],
        // Amiens
        ['La Lune des Pirates',   '63 Quai Bélu',                       'Amiens',     '80000', 'France',  49.8959,  2.3011,   400],
        // Toulon
        ['Zénith Omega',          'Av. de la Résistance',               'Toulon',     '83000', 'France',  43.1315,  5.9281,  8000],
        // Nancy
        ['Zénith Nancy',          '1 Av. du Général Leclerc',           'Vandœuvre-lès-Nancy','54500','France',48.6491,6.1689,8500],
        // Le Mans
        ['Antares',               'Av. des Platanes',                   'Le Mans',    '72100', 'France',  48.0117,  0.1914,  7500],
        // Angers
        ['L\'Chabada',            '56 Bd du Doyenné',                   'Angers',     '49100', 'France',  47.4697, -0.5372,   900],
        // Besançon
        ['Micropolis',            '4 Av. de l\'Impératrice Eugénie',    'Besançon',   '25000', 'France',  47.2528,  6.0128,  3500],
        // Limoges
        ['Zénith de Limoges',     'Bd de Beaublanc',                    'Limoges',    '87000', 'France',  45.8623,  1.2711,  6000],
        // Perpignan
        ['Arkéa Arena Perpignan', 'Rue du Moulin à Vent',               'Perpignan',  '66000', 'France',  42.7088,  2.8804,  4000],
        // Metz
        ['L\'Arsenal',            '3 Av. Ney',                          'Metz',       '57000', 'France',  49.1198,  6.1804,  1500],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::$VENUES as $i => [$name, $street, $city, $zip, $country, $lat, $lng, $capacity]) {
            $entity            = new VenueEntity();
            $entity->id        = uuid_create(UUID_TYPE_RANDOM);
            $entity->name      = $name;
            $entity->street    = $street;
            $entity->city      = $city;
            $entity->zipCode   = $zip;
            $entity->country   = $country;
            $entity->latitude  = $lat;
            $entity->longitude = $lng;
            $entity->capacity  = $capacity;

            $manager->persist($entity);
            $this->addReference(self::REF_PREFIX . $i, $entity);
        }

        $manager->flush();
    }
}
