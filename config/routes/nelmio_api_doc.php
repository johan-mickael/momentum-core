<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    if ('dev' !== $routes->env()) {
        return;
    }

    $routes->import(
        '@NelmioApiDocBundle/config/routing/swaggerui.php',
        'php',
    )->prefix('/api/doc');
};
