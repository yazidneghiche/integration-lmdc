<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\MapperService;
use App\Service\PushNewAccountsService;
use App\Service\SourceCustomerReader;
use App\Service\SourceOrderReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gateway', name: 'app_gateway_')]
final class GatewayController extends AbstractController
{
    #[Route('/orders/{source}', name: 'orders', methods: ['POST'])]
    public function orders(
        string $source,
        SourceOrderReader $orderReader,
        MapperService $mapperService,
        OrderRepository $orderRepository,
    ): JsonResponse {
        $lastUpdate = $orderRepository->findLastUpdateBySource($source);
        $orders = $orderReader->readOrdersWithItems($source, $lastUpdate);
        $result = $mapperService->run($orders, $source, 'order');

        return $this->json(['success' => true, 'result' => $result]);
    }

    #[Route('/accounts/{source}', name: 'accounts', methods: ['POST'])]
    public function accounts(
        string $source,
        SourceCustomerReader $customerReader,
        MapperService $mapperService,
    ): JsonResponse {
        $customers = $customerReader->readCustomers($source);
        $result = $mapperService->run($customers, $source, 'account');

        return $this->json(['success' => true, 'result' => $result]);
    }

    #[Route('/new-accounts/push', name: 'accounts_push', methods: ['POST'])]
    public function pushAccounts(PushNewAccountsService $service): JsonResponse
    {
        return $this->json(['success' => true, 'result' => $service->push()]);
    }
}
