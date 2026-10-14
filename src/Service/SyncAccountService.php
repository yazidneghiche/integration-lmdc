<?php

namespace App\Service;

use App\Entity\Account;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SyncAccountService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly AccountRepository $accountRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountsApiUrl,
        private readonly string $apiKey,
    ) {
    }

    /**
     * Synchronise les comptes provenant de l'API vers l'intégration.
     *
     * @return array{created: int, updated: int, total: int}
     */
    public function synchronize(): array
    {
        $response = $this->httpClient->request(
            'GET',
            rtrim($this->accountsApiUrl, '/') . '/accounts/get/all',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-API-KEY' => $this->apiKey,
                ],
            ]
        );

        $responseData = $response->toArray();

        if (($responseData['success'] ?? false) !== true) {
            throw new \RuntimeException(
                'Impossible de récupérer les comptes depuis l’API.'
            );
        }

        $apiAccounts = $responseData['data'] ?? [];

        $created = 0;
        $updated = 0;

        foreach ($apiAccounts as $apiAccount) {

            $account = null;

            // Recherche prioritaire avec le CT_Num Sage.
          /*  if ($ctNumber) {
                $account = $this->accountRepository->findOneBy([
                    'ct_number' => $ctNumber,
                ]);
            }*/

            $ctNumber = isset($apiAccount['ctnumber'])
                ? trim((string) $apiAccount['ctnumber'])
                : null;

            $email = isset($apiAccount['email'])
                ? strtolower(trim((string) $apiAccount['email']))
                : null;

            // Un CT_Num terminé par "-tmp" n'est pas un véritable code Sage.
            if (
                $ctNumber !== null
                && preg_match('/-tmp$/i', $ctNumber)
            ) {
                $ctNumber = null;
            }

            // Recherche prioritaire avec un véritable CT_Num Sage.
            if ($ctNumber !== null && $ctNumber !== '') {
                $account = $this->accountRepository->findOneBy([
                    'ct_number' => $ctNumber,
                ]);
            }

            // Recherche de secours par email.
            if (!$account && $email !== '') {
                $account = $this->accountRepository->findOneBy([
                    'email' => $email,
                ]);
            }

            if (!$account) {
                $account = new Account();
                $this->entityManager->persist($account);
                ++$created;
            } else {
                ++$updated;
            }

            $account
                ->setCompany($apiAccount['company'] ?? '')
                ->setFirstname($apiAccount['firstname'] ?? null)
                ->setLastname($apiAccount['lastname'] ?? null)
                ->setPhone($apiAccount['phone'] ?? null)
                ->setAddress($apiAccount['address'] ?? null)
                ->setAddressComplement($apiAccount['address_complement'] ?? null)
                ->setVat($apiAccount['vat'] ?? null)
                ->setSiret($apiAccount['siret'] ?? null)
                ->setZipcode($apiAccount['zipcode'] ?? '')
                ->setCity($apiAccount['city'] ?? '')
                ->setCountry($apiAccount['country'] ?? '')
                ->setEmail($email)
                ->setCtNumber($ctNumber)
                ->setUpdatedAt(
                    !empty($apiAccount['updatedAt'])
                        ? new \DateTime($apiAccount['updatedAt'])
                        : new \DateTime()
                )
                ->setIsNew(false)
                ->setValidate(true)
                ->setSync(true)
                ->setNotificationSentAt(null);
        }

        $this->entityManager->flush();

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($apiAccounts),
        ];
    }

}
