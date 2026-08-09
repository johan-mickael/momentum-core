<?php

declare(strict_types=1);

namespace UI\Console\Command;

use Application\Event\Search\EventSearchIndexer;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Common\ValueObject\SortDirection;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventVisibility;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:search:events:reindex',
    description: 'Rebuild the Elasticsearch index from all events in Postgres (all statuses)',
)]
final class ReindexEventsCommand extends Command
{
    private const int BATCH_SIZE = 100;

    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly EventSearchIndexer $indexer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $page = 1;
        $indexed = 0;

        do {
            $list = $this->events->list(
                EventVisibility::ALL,
                new PaginationCriteria($page, self::BATCH_SIZE),
                new SortCriteria('createdAt', SortDirection::ASC),
            );

            foreach ($list->items as $event) {
                $this->indexer->reindex($event->id());
                ++$indexed;
            }

            $io->text("Indexed page {$page} ({$indexed}/{$list->total}).");
            ++$page;
        } while ($indexed < $list->total && [] !== $list->items);

        $io->success("Reindexed {$indexed} events.");

        return Command::SUCCESS;
    }
}
