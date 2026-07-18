<?php

namespace App\Controller\Admin\Api;

use App\Entity\Entry;
use App\Entity\EntryPicture;
use App\Service\Entry\EntryPictureService;
use App\Service\S3Service;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/admin/api', name: 'app_admin_api_entry_picture_')]
#[IsGranted('ROLE_ADMIN')]
class EntryPictureController extends AbstractController
{
    #[Route('/entry/{id}/pictures', name: 'upload', methods: ['POST'])]
    public function upload(
        Entry $entry,
        Request $request,
        EntryPictureService $service,
        S3Service $s3Service,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        $file = $request->files->get('file');

        if ($file === null) {
            return $this->json(['error' => 'Aucun fichier fourni.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $picture = $service->upload($entry, $file);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'id' => $picture->getId(),
            'position' => $picture->getPosition(),
            'thumbnailUrl' => $s3Service->getPublicUrl($picture->getThumbnailPath()),
            'lightboxUrl' => $s3Service->getPublicUrl($picture->getLightboxPath()),
            'csrfToken' => $csrfTokenManager->getToken('delete_entry_picture' . $picture->getId())->getValue(),
        ]);
    }

    #[Route('/entry-picture/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        EntryPicture $picture,
        Request $request,
        EntryPictureService $service,
    ): Response {
        $token = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_entry_picture' . $picture->getId(), $token)) {
            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $service->delete($picture);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/entry/{id}/pictures/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(
        Entry $entry,
        Request $request,
        EntryPictureService $service,
    ): JsonResponse {
        try {
            $ids = $request->toArray()['ids'] ?? [];

            if (!is_array($ids)) {
                throw new InvalidArgumentException('Invalid input data, expected array.');
            }

            $service->reorder($entry, $ids);

            return $this->json(['success' => true]);
        } catch (Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
