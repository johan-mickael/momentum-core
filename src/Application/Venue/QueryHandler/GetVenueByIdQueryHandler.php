<?php

declare(strict_types=1);

namespace App\Application\Venue\QueryHandler;

use App\Application\Venue\Query\GetVenueByIdQuery;
use App\Application\Venue\Result\GetVenueResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Venue\Exception\GetVenueForbidden;
use App\Domain\Venue\Security\Authorization\VenueContext;
use App\Domain\Venue\ValueObject\VenueId;
use App\Domain\Venue\VenueRepositoryInterface;

final readonly class GetVenueByIdQueryHandler implements GetVenueByIdUseCase
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(GetVenueByIdQuery $query, UserContext $actor): GetVenueResult
    {
        $venue = $this->venues->getById(VenueId::fromString($query->id));

        if (!$this->authorizationService->authorize($actor, Action::VIEW, new VenueContext($venue))) {
            throw GetVenueForbidden::forId($venue->id());
        }

        return new GetVenueResult(
            (string)$venue->id(),
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
