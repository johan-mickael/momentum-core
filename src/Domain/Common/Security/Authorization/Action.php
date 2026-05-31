<?php

declare(strict_types=1);

namespace Domain\Common\Security\Authorization;

enum Action: string
{
    case LIST = 'LIST';
    case CREATE = 'CREATE';
    case DELETE = 'DELETE';
    case VIEW = 'VIEW';
    case UPDATE = 'UPDATE';
    case CHECKOUT = 'CHECKOUT';
    case CANCEL = 'CANCEL';
}
