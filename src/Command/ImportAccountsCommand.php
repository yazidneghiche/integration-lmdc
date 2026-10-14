<?php

namespace App\Command;

use App\Service\MapperService;
use App\Service\SourceConnection;
use App\Service\SourceCustomerReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:gateway:accounts:import',
    description: 'Importe les clients depuis une source PrestaShop.'
)]
class ImportAccountsCommand extends Command
{
    public function __construct(
        private readonly SourceCustomerReader $customerReader,
        private readonly MapperService $mapperService,
        private readonly SourceConnection $sourceConnection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('source', InputArgument::REQUIRED, 'Nom de la source à importer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = (string) $input->getArgument('source');

        try {
            $this->sourceConnection->getConfig($source);
            $customers = $this->customerReader->readCustomers($source);
            $result = $this->mapperService->run($customers, $source, 'account');

            $output->writeln(sprintf(
                'Comptes : %d créé(s), %d mis à jour, %d ignoré(s).',
                $result['created'],
                $result['updated'],
                $result['skipped']
            ));

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
