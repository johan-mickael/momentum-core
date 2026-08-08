<?php

declare(strict_types=1);

namespace UI\Console\Command;

use Elastic\Elasticsearch\Client;
use Infrastructure\Elasticsearch\ElasticsearchEventSearchIndex;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:search:events:init-index',
    description: 'Create the Elasticsearch index for event search',
)]
final class InitSearchIndexCommand extends Command
{
    private const string MAPPING_FILE = __DIR__.'/../../../Infrastructure/Elasticsearch/mapping/event-index.json';

    public function __construct(
        private readonly Client $client,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'recreate',
            mode: InputOption::VALUE_NONE,
            description: 'Delete the index first if it already exists',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $index = ElasticsearchEventSearchIndex::INDEX;

        $exists = $this->client->indices()->exists(['index' => $index])->asBool();

        if ($exists && !$input->getOption('recreate')) {
            $io->warning("Index \"{$index}\" already exists. Pass --recreate to drop and recreate it.");

            return Command::FAILURE;
        }

        if ($exists) {
            $this->client->indices()->delete(['index' => $index]);
            $io->text("Deleted existing index \"{$index}\".");
        }

        $body = json_decode(file_get_contents(self::MAPPING_FILE), true, 512, JSON_THROW_ON_ERROR);

        $this->client->indices()->create([
            'index' => $index,
            'body' => $body,
        ]);

        $io->success("Created index \"{$index}\".");

        return Command::SUCCESS;
    }
}
