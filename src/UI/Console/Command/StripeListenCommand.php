<?php

declare(strict_types=1);

namespace App\UI\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'stripe:listen',
    description: 'Forward Stripe webhook events to the local app via the Stripe CLI',
)]
final class StripeListenCommand extends Command
{
    private const string DEFAULT_URL = 'https://localhost/stripe/webhook';

    protected function configure(): void
    {
        $this->addOption(
            name: 'url',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The local webhook URL to forward events to',
            default: self::DEFAULT_URL,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = $input->getOption('url');

        $io->title('Stripe Webhook Listener');
        $io->text("Forwarding Stripe events → <info>{$url}</info>");
        $io->text('Press <comment>Ctrl+C</comment> to stop.');
        $io->newLine();

        $process = new Process(['stripe', 'listen', '--forward-to', $url, '--skip-verify']);
        $process->setTimeout(null);
        $process->setTty(false);

        $process->start(function (string $_type, string $chunk) use ($io): void {
            if (str_contains($chunk, 'webhook signing secret')) {
                preg_match('/whsec_\S+/', $chunk, $matches);

                if (isset($matches[0])) {
                    $io->success('Webhook signing secret detected!');
                    $io->block(
                        messages: $matches[0],
                        type: 'STRIPE_WEBHOOK_SECRET',
                        style: 'fg=black;bg=green',
                        padding: true,
                    );
                    $io->text('Add this to your <comment>.env.local</comment>:');
                    $io->text("  <info>STRIPE_WEBHOOK_SECRET={$matches[0]}</info>");
                    $io->newLine();

                    return;
                }
            }

            $output = trim($chunk);
            if ('' !== $output) {
                $io->text($output);
            }
        });

        $process->wait();

        if (!$process->isSuccessful()) {
            $io->error([
                'Stripe CLI exited with an error.',
                'Make sure the Stripe CLI is installed: https://stripe.com/docs/stripe-cli',
                'And that you are logged in: stripe login',
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
