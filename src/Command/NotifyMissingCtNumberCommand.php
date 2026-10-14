<?php

namespace App\Command;

use App\Service\MissingCtNumberService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:notify:missing-ct-number',
    description: 'Envoie un email pour les comptes en attente de CT_Num.'
)]
class NotifyMissingCtNumberCommand extends Command
{
    public function __construct(
        private readonly MissingCtNumberService $missingCtNumberService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $sent = $this->missingCtNumberService->sendNotification();

            if ($sent === 0) {
                $output->writeln('Aucun compte à notifier.');

                return Command::SUCCESS;
            }

            $output->writeln(sprintf(
                'Email envoyé pour %d compte(s) en attente de CT_Num.',
                $sent
            ));

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
