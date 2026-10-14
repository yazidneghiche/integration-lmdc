<?php

namespace App\Controller\Admin;

use App\Repository\AccountRepository;
use App\Repository\OrderRepository;
use App\Service\MonitoringService;
use App\Service\SourceConfigService;
use App\Service\SourceMappingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'app_admin_')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        AccountRepository $accountRepository,
        OrderRepository $orderRepository,
        SourceConfigService $sourceConfigService,
        MonitoringService $monitoringService,
    ): Response {
        return $this->render('admin/index.html.twig', [
            'sources' => $sourceConfigService->all(),
            'monitoring' => $monitoringService->getStatus(),
            'stats' => [
                'accounts' => $accountRepository->count([]),
                'accountsWithoutCtNumber' => $accountRepository->countWithoutCtNumber(),
                'ordersPending' => $orderRepository->countPending(),
                'ordersSynchronized' => $orderRepository->countSynchronized(),
            ],
        ]);
    }

    #[Route('/sources/new', name: 'source_new', methods: ['GET', 'POST'])]
    public function newSource(Request $request, SourceConfigService $service): Response
    {
        return $this->sourceForm($request, $service);
    }

    #[Route('/sources/{source}/edit', name: 'source_edit', methods: ['GET', 'POST'])]
    public function editSource(string $source, Request $request, SourceConfigService $service): Response
    {
        $config = $service->get($source);

        if ($config === null) {
            throw $this->createNotFoundException('Source inconnue.');
        }

        return $this->sourceForm($request, $service, $source, $config);
    }

    #[Route('/sources/{source}/delete', name: 'source_delete', methods: ['POST'])]
    public function deleteSource(string $source, Request $request, SourceConfigService $service): Response
    {
        if ($this->isCsrfTokenValid('delete-source-' . $source, (string) $request->request->get('_token'))) {
            $service->remove($source);
            $this->addFlash('success', 'La source a été supprimée.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/sources/{source}/test', name: 'source_test', methods: ['POST'])]
    public function testSource(string $source, Request $request, SourceConfigService $service): Response
    {
        if (!$this->isCsrfTokenValid('test-source-' . $source, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $service->test($source);
            $this->addFlash('success', sprintf('Connexion à %s réussie.', $source));
        } catch (\Throwable $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route(
        '/sources/{source}/mapping',
        name: 'source_mapping',
        methods: ['GET', 'POST']
    )]
    public function mappingSource(
        string $source,
        Request $request,
        SourceConfigService $sourceConfigService,
        SourceMappingService $sourceMappingService,
    ): Response {
        $config = $sourceConfigService->get($source);

        if ($config === null) {
            throw $this->createNotFoundException('Source inconnue.');
        }

        $mappingData = $sourceMappingService->mapping($source);
        $mapping = $mappingData['mapping'];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'source-mapping-' . $source,
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException();
            }

            try {
                $mapping = $request->request->all('mapping');

                $sourceMappingService->save($source, $mapping);

                $this->addFlash(
                    'success',
                    'Le mapping personnalisé a été enregistré.'
                );

                return $this->redirectToRoute(
                    'app_admin_source_details',
                    ['source' => $source]
                );
            } catch (\Throwable $exception) {
                $this->addFlash('error', $exception->getMessage());
                $mapping = $mappingData['mapping'];
            }
        }

        $connectionError = null;
        $schema = [];

        try {
            $schema = $sourceMappingService->schema($source);
        } catch (\Throwable $exception) {
            $connectionError = $exception->getMessage();
        }

        return $this->render(
            'admin/source_details_form.html.twig',
            [
                'sourceName' => $source,
                'source' => $config,
                'schema' => $schema,
                'mapping' => $mapping,
                'isCustomMapping' => $mappingData['custom'],
                'connectionError' => $connectionError,
            ]
        );
    }

    #[Route('/sources/{source}/details', name: 'source_details', methods: ['GET'])]
    public function detailsSource(
        string $source,
        SourceConfigService $sourceConfigService,
        SourceMappingService $sourceMappingService,
    ): Response {
        $config = $sourceConfigService->get($source);

        if ($config === null) {
            throw $this->createNotFoundException('Source inconnue.');
        }

        $connectionError = null;
        $schema = [];

        try {
            $schema = $sourceMappingService->schema($source);
        } catch (\Throwable $exception) {
            $connectionError = $exception->getMessage();
        }

        $mappingData = $sourceMappingService->mapping($source);

        return $this->render('admin/source_details.html.twig', [
            'sourceName' => $source,
            'source' => $config,
            'schema' => $schema,
            'mapping' => $mappingData['mapping'],
            'isCustomMapping' => $mappingData['custom'],
            'connectionError' => $connectionError,
        ]);
    }

    private function sourceForm(
        Request $request,
        SourceConfigService $service,
        ?string $originalName = null,
        array $source = [],
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('source-form', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            try {
                $service->save(
                    (string) $request->request->get('name'),
                    $request->request->all(),
                    $originalName
                );
                $this->addFlash('success', 'La source a été enregistrée.');

                return $this->redirectToRoute('app_admin_dashboard');
            } catch (\Throwable $exception) {
                $this->addFlash('error', $exception->getMessage());
                $source = $request->request->all();
            }
        }

        return $this->render('admin/source_form.html.twig', [
            'sourceName' => $originalName,
            'source' => $source,
        ]);
    }


}
