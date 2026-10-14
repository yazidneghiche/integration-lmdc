<?php

namespace App\Command;

use App\Repository\OrderRepository;
use App\Service\ApiOrder;
use App\Service\CronLog;
use App\Service\UpdateOrderCtNumberService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync:orders:push',
    description: 'Envoie les commandes non synchronisées vers l’API.'
)]
class PushOrdersCommand extends Command
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UpdateOrderCtNumberService $updateOrderCtNumberService,
        private readonly ApiOrder $apiOrder,
        private readonly EntityManagerInterface $em,
        private readonly CronLog $cronLog
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $start = microtime(true);

        try {
            $updatedOrders = $this->updateOrderCtNumberService->update();
            $orders = $this->orderRepository->findOrdersReadyToSync();

            if ($orders === []) {
                $output->writeln('✅ Aucune commande à synchroniser.');
                $this->cronLog->create(
                    'sync_order_push',
                    'Sync des nouvelles commandes',
                    'Aucune commande à synchroniser.',
                    [
                        'ct_numbers_updated' => $updatedOrders,
                        'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                    ]
                );

                return Command::SUCCESS;
            }

            $output->writeln(sprintf('📤 Envoi de %d commande(s)...', count($orders)));
            $result = $this->apiOrder->postOrders($orders);

            if (
                !is_array($result)
                || empty($result['orders'])
                || !is_array($result['orders'])
            ) {
                throw new \RuntimeException(
                    'La réponse de l’API ne contient aucune commande synchronisée.'
                );
            }

            $synchronized = 0;

            foreach ($result['orders'] as $orderData) {
                /*
                 * L’API peut retourner directement un identifiant :
                 *
                 * "orders": [1, 2, 3]
                 *
                 * ou un tableau contenant l’identifiant :
                 *
                 * "orders": [
                 *     {"id": 1},
                 *     {"id": 2}
                 * ]
                 */
                $orderId = is_array($orderData)
                    ? ($orderData['id'] ?? null)
                    : $orderData;

                if ($orderId === null) {
                    continue;
                }

                $order = $this->orderRepository->find($orderId);

                if ($order === null) {
                    continue;
                }

                $order->setSync(true);
                $synchronized++;
            }

            $this->em->flush();

            $output->writeln(
                sprintf(
                    '✅ Synchronisation réussie : %d commande(s).',
                    $synchronized
                )
            );

            $this->cronLog->create(
                'sync_order_push',
                'Sync des nouvelles commandes',
                'OK',
                [
                    'orders_sent' => count($orders),
                    'orders_synchronized' => $synchronized,
                    'ct_numbers_updated' => $updatedOrders,
                    'duration_ms' => (int) (
                        (microtime(true) - $start) * 1000
                    ),
                ]
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln(
                '<error>❌ Erreur : '
                . $e->getMessage()
                . '</error>'
            );

            $this->cronLog->create(
                'sync_order_push',
                'Sync des nouvelles commandes',
                'ERROR: ' . $e->getMessage(),
                [
                    'exception' => $e::class,
                    'duration_ms' => (int) (
                        (microtime(true) - $start) * 1000
                    ),
                ]
            );

            return Command::FAILURE;
        }
    }
}
