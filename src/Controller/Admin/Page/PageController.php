<?php

namespace App\Controller\Admin\Page;

use App\Entity\Page;
use App\Form\PageType;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/admin/page', name: 'app_admin_page_')]
class PageController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(PageRepository $pageRepository): Response
    {
        return $this->render('admin/page/index.html.twig', [
            'pages' => $pageRepository->findRootPagesOrdered(),
        ]);
    }

    #[Route('/{id}/update', name: 'update', methods: ['GET', 'POST'])]
    public function update(
        Request $request,
        Page $page,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Page modifiée avec succès.');

            return $page->getParent() !== null
                ? $this->redirectToRoute('app_admin_page_update', ['id' => $page->getParent()->getId()])
                : $this->redirectToRoute('app_admin_page_index');
        }

        return $this->render('admin/page/update.html.twig', [
            'page' => $page,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(
        Request $request,
        Page $page,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle_page_' . $page->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide. Rechargez la page et réessayez.');

            return $this->redirectToRoute('app_admin_page_index');
        }

        if ($page->getParent() !== null || $page->getSlug() === 'home') {
            throw $this->createNotFoundException();
        }

        $page->setEnabled(!$page->isEnabled());
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'La page « %s » est maintenant %s.',
            $page->getLabel(),
            $page->isEnabled() ? 'visible' : 'masquée'
        ));

        return $this->redirectToRoute('app_admin_page_index');
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        PageRepository $pageRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        try {
            $ids = $request->toArray()['ids'] ?? [];

            if (!is_array($ids)) {
                throw new InvalidArgumentException('Invalid input data, expected array.');
            }

            $pages = $pageRepository->findBy(['id' => $ids]);
            $indexed = [];
            foreach ($pages as $page) {
                $indexed[$page->getId()] = $page;
            }

            foreach ($ids as $position => $id) {
                if (isset($indexed[$id])) {
                    $indexed[$id]->setPosition($position);
                }
            }

            $entityManager->flush();

            return $this->json(['success' => true]);
        } catch (Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
