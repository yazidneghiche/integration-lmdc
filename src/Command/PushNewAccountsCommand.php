<?php

namespace App\Command;

use App\Service\PushNewAccountsService;
use App\Service\MissingCtNumberService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync:accounts:push',
    description: 'Envoie les nouveaux comptes vers l’API.'
)]
class PushNewAccountsCommand extends Command
{
    public function __construct(
        private readonly PushNewAccountsService $pushNewAccountsService,
        private readonly MissingCtNumberService $missingCtNumberService,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        try {
            $result = $this->pushNewAccountsService->push();

            $notified = $result['failed'] > 0
                ? $this->missingCtNumberService->sendNotification()
                : 0;

            if ($result['total'] === 0) {
                $output->writeln(
                    'Aucun nouveau compte à envoyer.'
                );

                return Command::SUCCESS;
            }

            $output->writeln(sprintf(
                '%d compte(s) envoyé(s), %d échec(s), %d notification(s).',
                $result['sent'],
                $result['failed'],
                $notified
            ));

            return $result['failed'] > 0
                ? Command::FAILURE
                : Command::SUCCESS;
        } catch (\Throwable $e) {
            $notified = $this->missingCtNumberService->sendNotification();
            $output->writeln(
                '<error>❌ Erreur : '
                . $e->getMessage()
                . '</error>'
            );
            $output->writeln(sprintf('%d compte(s) signalé(s) par email.', $notified));

            return Command::FAILURE;
        }
    }
}
