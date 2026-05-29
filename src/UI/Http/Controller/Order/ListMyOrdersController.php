<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Query\ListMyOrdersQuery;
use App\Application\Order\QueryHandler\ListMyOrdersUseCase;
use App\UI\Http\Controller\HttpController;
use App\Domain\Common\ValueObject\SortDirection;
use App\UI\Http\Request\ListQueryParser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/me', methods: ['GET'])]
#[OA\Get(
    path: '/api/orders/me',
    operationId: 'listMyOrders',
    summary: 'List authenticated user\'s orders',
    tags: ['Orders'],
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1), description: 'Page number'),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100), description: 'Items per page'),
        new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['placedAt', 'totalAmount'], default: 'placedAt'), description: 'Sort field'),
        new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'DESC'), description: 'Sort direction'),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['PENDING_PAYMENT', 'PAID', 'CANCELLED', 'EXPIRED', 'REFUNDED']), description: 'Filter by order status'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated list of my orders', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedOrdersResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListMyOrdersController extends HttpController
{
    private const array SORT_FIELDS      = ['placedAt', 'totalAmount'];
    private const string DEFAULT_SORT    = 'placedAt';

    public function __construct(
        private readonly ListMyOrdersUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = ($this->useCase)(
            new ListMyOrdersQuery(
                pagination:   ListQueryParser::pagination($request),
                sort:         ListQueryParser::sort($request, self::SORT_FIELDS, self::DEFAULT_SORT, SortDirection::DESC),
                statusFilter: $request->query->getString('status') ?: null,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
