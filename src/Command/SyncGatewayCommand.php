<?php

namespace App\Command;

use App\Repository\OrderRepository;
use App\Service\MapperService;
use App\Service\SourceConnection;
use App\Service\SourceCustomerReader;
use App\Service\SourceOrderReader;
use App\Service\SyncAccountService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync:gateway',
    description: 'Synchronise Sage puis importe les comptes et commandes PrestaShop.'
)]
class SyncGatewayCommand extends Command
{
    public function __construct(
        private readonly SyncAccountService $syncAccountService,
        private readonly SourceConnection $sourceConnection,
        private readonly SourceCustomerReader $customerReader,
        private readonly SourceOrderReader $orderReader,
        private readonly MapperService $mapperService,
        private readonly OrderRepository $orderRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('source', InputArgument::OPTIONAL, 'Source unique à synchroniser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $sage = $this->syncAccountService->synchronize();
            $output->writeln(sprintf('Sage : %d compte(s) synchronisé(s).', $sage['total']));

            $requestedSource = $input->getArgument('source');
            $sources = $requestedSource
                ? [(string) $requestedSource]
                : $this->sourceConnection->getEnabledSources();

            foreach ($sources as $source) {
                $customers = $this->customerReader->readCustomers($source);
                $accountResult = $this->mapperService->run($customers, $source, 'account');

                $lastUpdate = $this->orderRepository->findLastUpdateBySource($source);
                $orders = $this->orderReader->readOrdersWithItems($source, $lastUpdate);
                $orderResult = $this->mapperService->run($orders, $source, 'order');

                $output->writeln(sprintf(
                    '%s : comptes %d/%d, commandes %d/%d, ignorés %d.',
                    $source,
                    $accountResult['created'],
                    $accountResult['updated'],
                    $orderResult['created'],
                    $orderResult['updated'],
                    $accountResult['skipped'] + $orderResult['skipped']
                ));
            }

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
