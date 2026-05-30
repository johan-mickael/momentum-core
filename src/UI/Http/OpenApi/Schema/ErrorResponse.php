<?php

declare(strict_types=1);

namespace App\UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ErrorResponse', type: 'string', example: 'Resource not found.')]
final class ErrorResponse
{
}
