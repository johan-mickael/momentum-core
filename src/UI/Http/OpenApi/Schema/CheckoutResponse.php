<?php

declare(strict_types=1);

namespace UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CheckoutResponse',
    required: ['checkoutUrl'],
    properties: [
        new OA\Property(property: 'checkoutUrl', type: 'string', format: 'uri', example: 'https://checkout.stripe.com/c/pay/...'),
    ],
)]
final class CheckoutResponse
{
}
