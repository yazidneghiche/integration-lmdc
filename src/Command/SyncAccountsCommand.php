<?php


namespace App\Command;

use App\Service\SyncAccountService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-accounts',
    description: 'Synchronise les comptes Sage depuis l’API.'
)]
class SyncAccountsCommand extends Command
{
    public function __construct(
        private readonly SyncAccountService $syncAccountService
    )
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Synchronisation des comptes Sage');

        try {
            $result = $this->syncAccountService->synchronize();

            $io->success(sprintf(
                'Synchronisation terminée : %d compte(s) créé(s), %d mis à jour, %d au total.',
                $result['created'],
                $result['updated'],
                $result['total']
            ));

           /* foreach ($result['errors'] as $error) {
                $io->warning($error);
            }*/

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }
}
