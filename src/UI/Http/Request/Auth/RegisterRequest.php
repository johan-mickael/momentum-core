<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Auth;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['email', 'password', 'password_confirmation', 'first_name', 'last_name'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'password', type: 'string', minLength: 8, description: 'Min 8 chars, 1 uppercase, 1 digit', example: 'Password123!'),
        new OA\Property(property: 'password_confirmation', type: 'string', example: 'Password123!'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Regular'),
        new OA\Property(property: 'last_name', type: 'string', example: 'User'),
    ],
)]
final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'email is required.')]
        #[Assert\Email(message: 'Invalid email format.')]
        public readonly ?string $email,
        #[Assert\NotBlank(message: 'password is required.')]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters.')]
        #[Assert\Regex(pattern: '/[A-Z]/', message: 'Password must contain at least one uppercase letter.')]
        #[Assert\Regex(pattern: '/[0-9]/', message: 'Password must contain at least one digit.')]
        public readonly ?string $password,
        #[Assert\NotBlank(message: 'password_confirmation is required.')]
        #[Assert\EqualTo(propertyPath: 'password', message: 'Passwords do not match.')]
        public readonly ?string $password_confirmation,
        #[Assert\NotBlank(message: 'first_name is required.')]
        public readonly ?string $first_name,
        #[Assert\NotBlank(message: 'last_name is required.')]
        public readonly ?string $last_name,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            password_confirmation: $data['password_confirmation'] ?? null,
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
        );
    }
}
