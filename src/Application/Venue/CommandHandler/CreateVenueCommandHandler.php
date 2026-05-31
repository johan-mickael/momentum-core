<?php

declare(strict_types=1);

namespace Application\Venue\CommandHandler;

use Application\Venue\Command\CreateVenueCommand;
use Application\Venue\Result\CreateVenueResult;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Venue\Exception\CreateVenueForbidden;
use Domain\Venue\Service\VenueAddressUniquenessChecker;
use Domain\Venue\Service\VenueNameUniquenessChecker;
use Domain\Venue\ValueObject\Address;
use Domain\Venue\ValueObject\Capacity;
use Domain\Venue\ValueObject\GeoLocation;
use Domain\Venue\ValueObject\VenueId;
use Domain\Venue\Venue;
use Domain\Venue\VenueRepositoryInterface;

final readonly class CreateVenueCommandHandler implements CreateVenueUseCase
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private VenueNameUniquenessChecker $venueNameMustBeUnique,
        private VenueAddressUniquenessChecker $venueAddressMustBeUnique,
        private TransactionManagerInterface $transaction,
        private AuthorizationServiceInterface $authorization,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(
        CreateVenueCommand $command,
        UserContext $actor,
    ): CreateVenueResult {
        if (!$this->authorization->authorize(
            $actor,
            Action::CREATE,
            Venue::class,
        )) {
            throw new CreateVenueForbidden();
        }

        $venue = Venue::create(
            id: VenueId::fromString(
                $this->idGenerator->generate(),
            ),
            name: $command->name,
            address: Address::create(
                street: $command->street,
                city: $command->city,
                zipCode: $command->zipCode,
                country: $command->country,
            ),
            location: GeoLocation::create(
                latitude: $command->latitude,
                longitude: $command->longitude,
            ),
            capacity: Capacity::fromInteger($command->capacity),
        );

        $this->venueNameMustBeUnique->check($venue);
        $this->venueAddressMustBeUnique->check($venue);

        $this->venues->save($venue);
        $this->transaction->flush();

        return new CreateVenueResult(
            (string) $venue->id(),
            $venue->name(),
            $venue->address()->street(),
            $venue->address()->city(),
            $venue->address()->country(),
            $venue->address()->zipCode(),
            $venue->capacity()->value,
            $venue->location()->latitude(),
            $venue->location()->longitude(),
        );
    }
}
