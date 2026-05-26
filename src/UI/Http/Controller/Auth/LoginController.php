<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Auth;

use App\Application\Auth\Command\LoginCommand;
use App\Application\Auth\CommandHandler\LoginUseCase;
use App\UI\Http\Request\Auth\LoginRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly int $jwtTokenTtl,
    ) {}

    public function __invoke(
        #[MapRequestPayload] LoginRequest $dto,
        Request $request,
    ): Response {
        $token = ($this->loginUseCase)(new LoginCommand(
            email: $dto->email,
            password: $dto->password,
        ));

        $response = new Response(null, Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(
            Cookie::create('jwt_token')
                ->withValue($token)
                ->withExpires(time() + $this->jwtTokenTtl)
                ->withPath('/')
                ->withHttpOnly()
                ->withSecure($request->isSecure())
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        return $response;
    }
}
