<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Auth;

use App\Application\Auth\Command\RegisterUserCommand;
use App\Application\Auth\CommandHandler\RegisterUserUseCase;
use App\UI\Http\Request\Auth\RegisterRequest;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/register', methods: ['POST'])]
#[OA\Post(
    path: '/api/auth/register',
    operationId: 'registerUser',
    summary: 'Register a new user account',
    tags: ['Auth'],
    security: [],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')),
    responses: [
        new OA\Response(response: 201, description: 'User registered', content: new OA\JsonContent(ref: '#/components/schemas/RegisterUserResponse')),
        new OA\Response(response: 409, description: 'Email already in use', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] RegisterRequest $dto,
    ): JsonResponse
    {
        $registerUserResult = ($this->registerUserUseCase)(new RegisterUserCommand(
            email: $dto->email,
            password: $dto->password,
            firstName: $dto->first_name,
            lastName: $dto->last_name,
        ));

        return $this->json($registerUserResult, Response::HTTP_CREATED);
    }
}
