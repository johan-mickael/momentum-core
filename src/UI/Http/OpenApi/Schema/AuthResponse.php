<?php

declare(strict_types=1);

namespace App\UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterUserResponse',
    required: ['id', 'email', 'role', 'firstName', 'lastName'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'role', type: 'string', enum: ['BUYER', 'STAFF', 'ADMIN'], example: 'BUYER'),
        new OA\Property(property: 'firstName', type: 'string', example: 'Regular'),
        new OA\Property(property: 'lastName', type: 'string', example: 'User'),
    ],
)]
#[OA\Schema(
    schema: 'MeResponse',
    required: ['id', 'email', 'role'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'role', type: 'string', enum: ['BUYER', 'STAFF', 'ADMIN'], example: 'BUYER'),
        new OA\Property(property: 'firstName', type: 'string', nullable: true, example: 'Regular'),
        new OA\Property(property: 'lastName', type: 'string', nullable: true, example: 'User'),
    ],
)]
final class AuthResponse
{
}
