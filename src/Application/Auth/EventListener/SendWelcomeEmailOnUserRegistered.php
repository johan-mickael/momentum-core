<?php

declare(strict_types=1);

namespace App\Application\Auth\EventListener;

use App\Domain\User\Event\UserRegistered;
use App\Domain\User\UserRepositoryInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final readonly class SendWelcomeEmailOnUserRegistered
{
    public function __construct(
        private UserRepositoryInterface $users,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(UserRegistered $event): void
    {
        $user = $this->users->findById($event->userId);

        if (null === $user) {
            return;
        }

        $email = new TemplatedEmail()
            ->to($event->email)
            ->subject('Welcome to whenn!')
            ->htmlTemplate('email/welcome.html.twig')
            ->context([
                'firstName' => $user->firstName(),
            ]);

        $this->mailer->send($email);
    }
}
