<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiOrder
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $em,
        private readonly string $baseUrl,
        private readonly string $apiKey
    ) {
    }

    /**
     * Envoie une commande vers l'API Sage.
     */
    public function postOrder(Order $order): ?array
    {
        $items = [];

        foreach ($order->getOrderProducts() as $item) {
            $items[] = [
                'reference' => $item->getReference(),
                'quantity' => $item->getQuantity(),
                'pu' => $item->getUnitPrice(),
                'price' => $item->getPrice(),
            ];
        }

        $payload = [
            'id' => $order->getId(),
            'company' => $order->getCompany(),
            'ct_number' => $order->getCtNumber(),
            'items' => $items,
            'source' => $order->getSource(),
            'platform' => $order->getPlatform(),
            'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),
        ];

        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl . '/order/post',
            [
                'json' => $payload,
                'headers' => $this->getHeaders(),
            ]
        );

        return $this->handleResponse($response);
    }

    /**
     * Envoie plusieurs commandes vers l'API Sage.
     *
     * @param Order[] $orders
     */
    public function postOrders(array $orders): ?array
    {
        $payload = [];

        foreach ($orders as $order) {
            if (!$order instanceof Order) {
                continue;
            }

            $items = [];

            foreach ($order->getOrderProducts() as $item) {
                $items[] = [
                    'reference' => $item->getReference(),
                    'quantity' => $item->getQuantity(),
                    'pu' => $item->getUnitPrice(),
                    'price' => $item->getPrice(),
                ];
            }

            if ($items === []) {
                continue;
            }

            $payload[] = [
                'id' => $order->getId(),
                'company' => $order->getCompany(),
                'status' => $order->getStatus(),
                'ct_number' => $order->getCtNumber(),
                'items' => $items,
                'b2c_site_id' =>  $order->getId(),
                'source' => $order->getSource(),
                'platform' => $order->getPlatform(),
                'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),
            ];
        }

        if ($payload === []) {
            return [];
        }

        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl . '/orders/post',
            [
                'json' => $payload,
                'headers' => $this->getHeaders(),
            ]
        );

        return $this->handleResponse($response);
    }

    /**
     * Récupère les commandes mises à jour depuis l'API Sage.
     */
    public function getOrders(?string $lastUpdate = null): ?array
    {
        $url = $this->baseUrl . '/order/get/all';

        if ($lastUpdate !== null && $lastUpdate !== '') {
            $url = $this->baseUrl
                . '/order/get/updated/'
                . rawurlencode($lastUpdate);
        }

        $response = $this->httpClient->request(
            'GET',
            $url,
            [
                'headers' => $this->getHeaders(),
            ]
        );

        $status = $response->getStatusCode();

        if ($status === 404) {
            return null;
        }

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(
                sprintf(
                    'Erreur API lors de la récupération des commandes (code %d)',
                    $status
                )
            );
        }

        $datas = $response->toArray(false);

        if (empty($datas['data']) || !is_array($datas['data'])) {
            return $datas;
        }

        $updated = 0;

        foreach ($datas['data'] as $row) {
            $remoteId = $row['b2b_site_id'] ?? null;

            if ($remoteId === null) {
                continue;
            }

            $order = $this->orderRepository->find($remoteId);

            if (!$order) {
                continue;
            }

            $order->setStatus(
                isset($row['status']) ? (int) $row['status'] : null
            );

            $order->setSync(true);

            if (!empty($row['updatedAt'])) {
                $order->setLastUpdate(
                    new \DateTimeImmutable($row['updatedAt'])
                );
            }

            $order->setSageType(
                isset($row['sage_type'])
                    ? (int) $row['sage_type']
                    : null
            );

            $order->setSageStatus(
                isset($row['sage_statut'])
                    ? (int) $row['sage_statut']
                    : null
            );

            $order->setSagePiece(
                $row['sage_piece'] ?? null
            );

            $order->setSageTotalHt(
                isset($row['sage_total_ht'])
                    ? (float) $row['sage_total_ht']
                    : null
            );

            $order->setSageTotalTtc(
                isset($row['sage_total_ttc'])
                    ? (float) $row['sage_total_ttc']
                    : null
            );

            $order->setSageNetAPayer(
                isset($row['sage_net_a_payer'])
                    ? (float) $row['sage_net_a_payer']
                    : null
            );

            $updated++;

            // Pas obligatoire si l'entité vient du repository,
            // mais ce n'est pas gênant.
            $this->em->persist($order);
        }

        $this->em->flush();

        return [
            'updated' => $updated,
            'data' => $datas['data'],
        ];
    }

    private function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'X-API-KEY' => $this->apiKey,
        ];
    }

    private function handleResponse(
        \Symfony\Contracts\HttpClient\ResponseInterface $response
    ): ?array {
        $status = $response->getStatusCode();

        if ($status === 404) {
            return null;
        }

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(
                sprintf(
                    'Problème durant l’envoi vers l’API (code %d)',
                    $status
                )
            );
        }

        $content = $response->getContent(false);

        if ($content === '') {
            return [];
        }

        return $response->toArray(false);
    }
}
