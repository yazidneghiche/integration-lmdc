<?php

namespace App\Command;

use App\Repository\OrderRepository;
use App\Service\MapperService;
use App\Service\SourceOrderReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:gateway:orders:import',
    description: 'Importe les commandes depuis une source externe'
)]
class ImportOrdersCommand extends Command
{
    public function __construct(
        private readonly SourceOrderReader $orderReader,
        private readonly MapperService $mapperService,
        private readonly OrderRepository $orderRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'Nom de la source à importer'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $source = $input->getArgument('source');

        $output->writeln(
            sprintf('Import de la source "%s"...', $source)
        );

        $lastUpdate = $this->orderRepository
            ->findLastUpdateBySource($source);

        if ($lastUpdate) {
            $output->writeln(
                'Dernière synchronisation : ' .
                $lastUpdate->format('Y-m-d H:i:s')
            );
        } else {
            $output->writeln(
                'Aucune synchronisation précédente.'
            );
        }

        $orders = $this->orderReader->readOrdersWithItems(
            $source,
            $lastUpdate
        );

        if (empty($orders)) {
            $output->writeln('Aucune nouvelle commande.');

            return Command::SUCCESS;
        }

        $result = $this->mapperService->run(
            $orders,
            $source,
            'order'
        );

        $output->writeln(
            sprintf(
                '%d créée(s), %d mise(s) à jour, %d ignorée(s).',
                $result['created'],
                $result['updated'],
                $result['skipped']
            )
        );

        return Command::SUCCESS;
    }
}
