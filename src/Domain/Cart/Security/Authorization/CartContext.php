<?php

declare(strict_types=1);

namespace Domain\Cart\Security\Authorization;

use Domain\Cart\Cart;
use Domain\Common\Security\Authorization\Subject;

final readonly class CartContext implements Subject
{
    public function __construct(
        public Cart $cart,
    ) {
    }
}
