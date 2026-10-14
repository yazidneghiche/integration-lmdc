<?php

namespace App\Service;

use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PushNewAccountsService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly OrderRepository $orderRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountsApiUrl,
        private readonly string $apiKey,
    ) {
    }

    /**
     * Envoie les comptes indiqués, ou tous les nouveaux comptes si aucun tableau n'est fourni.
     *
     * @param Account[]|null $accounts
     * @return array{total: int, sent: int, failed: int}
     */
    public function push(?array $accounts = null): array
    {
        $accounts ??= $this->accountRepository->findAccountsWithoutCtNumber();
        $payload = [];

        foreach ($accounts as $account) {
            if (!$account instanceof Account) {
                continue;
            }

            $order = $this->orderRepository->findOneBy(
                ['account' => $account],
                ['id' => 'ASC']
            );

            if ($order === null) {
                continue;
            }

            $payload[] = [
                'accountId' => $account->getId(),
                'customerCode' => $this->temporaryCustomerCode(
                    $order->getSource(),
                    (int) $account->getId()
                ),
                'source' => $order->getSource(),
                'platform' => $order->getPlatform(),
                'name' => $account->getCompany() ?: trim(sprintf(
                    '%s %s',
                    $account->getFirstname() ?? '',
                    $account->getLastname() ?? ''
                )),
                'company' => $account->getCompany(),
                'firstname' => $account->getFirstname(),
                'lastname' => $account->getLastname(),
                'email' => $account->getEmail(),
                'phone' => $account->getPhone(),
                'address' => $account->getAddress(),
                'addressComplement' => $account->getAddressComplement(),
                'zipCode' => $account->getZipcode(),
                'city' => $account->getCity(),
                'country' => $account->getCountry(),
                'vat' => $account->getVat(),
                'siret' => $account->getSiret(),
            ];
        }

        if ($payload === []) {
            return [
                'total' => count($accounts),
                'sent' => 0,
                'failed' => count($accounts),
            ];
        }

        $response = $this->httpClient->request(
            'POST',
            rtrim($this->accountsApiUrl, '/') . '/accounts/post/new/collection',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-API-KEY' => $this->apiKey,
                ],
                'json' => ['accounts' => $payload],
            ]
        );

        $statusCode = $response->getStatusCode();
        $responseBody = $response->getContent(false);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(sprintf(
                'Erreur API durant l’envoi des comptes (code %d) : %s',
                $statusCode,
                $responseBody
            ));
        }

        $responseData = $response->toArray(false);
        $results = $responseData['accounts'] ?? $responseData['data'] ?? [];
        $sent = 0;

        foreach ($results as $result) {
            $accountId = $result['accountId'] ?? $result['id'] ?? null;

            if ($accountId === null || ($result['success'] ?? false) !== true) {
                continue;
            }

            $account = $this->accountRepository->find($accountId);

            if ($account !== null) {
                $account->setSync(true);
                ++$sent;
            }
        }

        $this->entityManager->flush();

        return [
            'total' => count($accounts),
            'sent' => $sent,
            'failed' => count($accounts) - $sent,
        ];
    }

    private function temporaryCustomerCode(?string $source, int $accountId): string
    {
        $prefix = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $source ?? 'B2C'));
        $prefix = substr($prefix, 0, 8);

        return substr($prefix . str_pad((string) $accountId, 9, '0', STR_PAD_LEFT), 0, 17);
    }
}
