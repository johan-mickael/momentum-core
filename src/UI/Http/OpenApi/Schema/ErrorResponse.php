<?php

declare(strict_types=1);

namespace UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ErrorResponse', type: 'string', example: 'Resource not found.')]
final class ErrorResponse
{
}
