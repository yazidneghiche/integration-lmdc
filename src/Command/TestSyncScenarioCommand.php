<?php

namespace App\Command;

use App\Service\TestSyncScenarioService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test:sync-scenario', description: 'Teste le parcours PrestaShop vers l’intégration et l’API.')]
class TestSyncScenarioCommand extends Command
{
    public function __construct(private readonly TestSyncScenarioService $scenario)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::OPTIONAL, 'Source PrestaShop de test')
            ->addOption('resume', null, InputOption::VALUE_REQUIRED, 'Identifiant de la commande dans l’intégration')
            ->addOption('push', null, InputOption::VALUE_NONE, 'Envoie réellement la commande à l’API');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $resumeId = $input->getOption('resume');
            $result = $resumeId !== null
                ? $this->scenario->resume((int) $resumeId, (bool) $input->getOption('push'))
                : $this->scenario->start((string) $input->getArgument('source'));

            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
