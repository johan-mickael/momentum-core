<?php

declare(strict_types=1);

namespace App\UI\Http\OpenApi;

use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

final class OpenApiSchemaDescriber implements DescriberInterface
{
    public function describe(OA\OpenApi $api): void
    {
        $openapi = new Generator()->generate([__DIR__], validate: false);

        if (null === $openapi) {
            return;
        }

        Util::merge($api, $openapi, false);
    }
}
