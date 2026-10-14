<?php

namespace App\Controller\Admin;

use App\Service\TestSyncScenarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/test-sync')]
class TestSyncController extends AbstractController
{
    #[Route('', name: 'app_admin_test_sync', methods: ['GET', 'POST'])]
    public function index(Request $request, TestSyncScenarioService $scenario): Response
    {
        $result = null;
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('test-sync', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            try {
                $action = (string) $request->request->get('action');
                $result = $action === 'resume'
                    ? $scenario->resume((int) $request->request->get('order_id'), false)
                    : $scenario->start((string) $request->request->get('source'));
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render('admin/test_sync/index.html.twig', [
            'result' => $result,
            'error' => $error,
        ]);
    }
}
