<?php

namespace App\Controller\Api;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

final class AccountController extends AbstractController
{

    #[Route('/api/accounts/test', name: 'app_api_accounts_test', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Ignore]
    public function testForm(): Response
    {
        return $this->render('api/account-form.html.twig');
    }


    #[Route('/api/accounts', name: 'app_api_accounts_create', methods: ['POST'])]
    #[OA\Tag(name: 'Comptes')]

    #[OA\Post(
        summary: 'Créer un nouveau compte',
        description: 'Enregistre un compte dans l’intégration avec sa source et sa plateforme. '
        . 'Le serveur définit isNew à true et génère un numéro client temporaire terminé par tmp. '
        . 'Cette opération ne confirme pas la création du compte dans Sage.'
    )]

    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: [
                'company',
                'email',
                'zipcode',
                'city',
                'country',
                'source',
                'platform',
            ],
            properties: [
                new OA\Property(
                    property: 'company',
                    type: 'string',
                    maxLength: 255,
                    description: 'Nom de la société.',
                    example: 'Commerce Exemple'
                ),
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    maxLength: 255,
                    description: 'Adresse e-mail du compte. Elle ne doit pas déjà exister dans l’intégration.',
                    example: 'camille.martin@mysite.com'
                ),
                new OA\Property(
                    property: 'firstname',
                    type: 'string',
                    maxLength: 255,
                    description: 'Prénom du contact.',
                    example: 'Camille'
                ),
                new OA\Property(
                    property: 'lastname',
                    type: 'string',
                    maxLength: 255,
                    description: 'Nom du contact.',
                    example: 'Martin'
                ),
                new OA\Property(
                    property: 'phone',
                    type: 'string',
                    maxLength: 255,
                    description: 'Numéro de téléphone.',
                    example: '0612345678'
                ),
                new OA\Property(
                    property: 'address',
                    type: 'string',
                    maxLength: 255,
                    description: 'Adresse postale.',
                    example: '12 rue du Commerce'
                ),
                new OA\Property(
                    property: 'address_complement',
                    type: 'string',
                    maxLength: 255,
                    description: 'Complément d’adresse.',
                    example: 'Bâtiment A'
                ),
                new OA\Property(
                    property: 'zipcode',
                    type: 'string',
                    maxLength: 255,
                    description: 'Code postal.',
                    example: '54000'
                ),
                new OA\Property(
                    property: 'city',
                    type: 'string',
                    maxLength: 255,
                    description: 'Ville.',
                    example: 'Nancy'
                ),
                new OA\Property(
                    property: 'country',
                    type: 'string',
                    maxLength: 255,
                    description: 'Pays. Utiliser de préférence un code à deux lettres.',
                    example: 'FR'
                ),
                new OA\Property(
                    property: 'vat',
                    type: 'string',
                    maxLength: 255,
                    description: 'Numéro de TVA intracommunautaire.'
                ),
                new OA\Property(
                    property: 'siret',
                    type: 'string',
                    maxLength: 32,
                    description: 'Numéro SIRET.'
                ),
                new OA\Property(
                    property: 'source',
                    type: 'string',
                    maxLength: 64,
                    description: 'Origine du compte, selon la valeur convenue avec l’intégration.',
                    example: 'publicite'
                ),
                new OA\Property(
                    property: 'platform',
                    type: 'string',
                    maxLength: 64,
                    description: 'Identifiant de la plateforme qui transmet le compte.',
                    example: 'site-test'
                ),
            ]
        )
    )]

    #[OA\Response(
        response: 201,
        description: 'Compte enregistré dans l’intégration.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Compte enregistré.'),
                new OA\Property(property: 'id', type: 'integer', example: 123),
                new OA\Property(
                    property: 'ct_number',
                    type: 'string',
                    description: 'Numéro temporaire, à remplacer par le numéro définitif retourné par Sage.',
                    example: 'GW123tmp'
                ),
                new OA\Property(property: 'is_new', type: 'boolean', example: true),
                new OA\Property(property: 'source', type: 'string', example: 'publicite'),
                new OA\Property(property: 'platform', type: 'string', example: 'site-etienne'),
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 400,
        description: 'Le contenu JSON est invalide.'
    )]

    #[OA\Response(
        response: 409,
        description: 'Un compte existe déjà avec cette adresse e-mail.'
    )]

    #[OA\Response(
        response: 422,
        description: 'Un champ obligatoire est absent, une valeur est invalide ou trop longue.'
    )]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // Accepter du JSON ou un formulaire HTML classique.
        try {
            $data = $request->getContentTypeFormat() === 'json'
                ? $request->toArray()
                : $request->request->all();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException) {
            return $this->json([
                'success' => false,
                'message' => 'JSON invalide.',
            ], 400);
        }

        foreach ([
                     'company', 'email', 'zipcode', 'city',
                     'country', 'source', 'platform',
                 ] as $field) {
            if (!isset($data[$field])
                || !is_string($data[$field])
                || trim($data[$field]) === ''
            ) {
                return $this->json([
                    'success' => false,
                    'message' => 'Champ obligatoire : ' . $field,
                ], 422);
            }
        }

        $limits = [
            'company' => 255,
            'email' => 255,
            'zipcode' => 255,
            'city' => 255,
            'country' => 255,
            'source' => 64,
            'platform' => 64,
            'firstname' => 255,
            'lastname' => 255,
            'phone' => 255,
            'address' => 255,
            'address_complement' => 255,
            'vat' => 255,
            'siret' => 32,
        ];

        foreach ($limits as $field => $max) {
            if (!isset($data[$field])) {
                continue;
            }

            if (!is_string($data[$field])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Champ invalide : ' . $field,
                ], 422);
            }

            $data[$field] = trim($data[$field]);

            if (preg_match_all('/./us', $data[$field]) > $max) {
                return $this->json([
                    'success' => false,
                    'message' => 'Champ trop long : ' . $field,
                ], 422);
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'Adresse e-mail invalide.',
            ], 422);
        }

        $existingAccount = $em->getRepository(Account::class)->findOneBy([
            'email' => $data['email'],
        ]);

        if ($existingAccount !== null) {
            return $this->json([
                'success' => false,
                'message' => 'Un compte existe déjà avec cette adresse e-mail.',
            ], 409);
        }

        $account = new Account();
        $account->setCompany($data['company']);
        $account->setEmail($data['email']);
        $account->setFirstname($data['firstname'] ?? null);
        $account->setLastname($data['lastname'] ?? null);
        $account->setPhone($data['phone'] ?? null);
        $account->setAddress($data['address'] ?? null);
        $account->setAddressComplement($data['address_complement'] ?? null);
        $account->setZipcode($data['zipcode']);
        $account->setCity($data['city']);
        $account->setCountry($data['country']);
        $account->setVat($data['vat'] ?? null);
        $account->setSiret($data['siret'] ?? null);

        $account->setSource($data['source']);
        $account->setPlatform($data['platform']);

        // État imposé à la création.
        $account->setIsNew(true);
        $account->setValidate(false);
        $account->setSync(false);
        $account->setUpdatedAt(new \DateTime());

        // Enregistrer le compte et son numéro temporaire ensemble.
        $em->wrapInTransaction(function () use ($em, $account): void {
            $em->persist($account);
            $em->flush();

            $account->setCtNumber('GW' . $account->getId() . 'tmp');
        });

        return $this->json([
            'success' => true,
            'message' => 'Compte enregistré.',
            'id' => $account->getId(),
            'ct_number' => $account->getCtNumber(),
            'is_new' => $account->isNew(),
            'source' => $account->getSource(),
            'platform' => $account->getPlatform(),
        ], 201);
    }
}
