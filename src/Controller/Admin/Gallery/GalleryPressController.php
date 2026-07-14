<?php

namespace App\Controller\Admin\Gallery;

use App\Entity\Gallery;
use App\Entity\Picture;
use App\Form\GalleryType;
use App\Repository\GalleryRepository;
use App\Repository\PictureRepository;
use App\Service\Gallery\GalleryService;
use App\Service\Gallery\PictureService;
use App\Service\Gallery\ThumbnailService;
use App\Service\QrCodeService;
use App\Service\S3Service;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/admin/press', name: 'app_admin_press_')]
class GalleryPressController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(GalleryRepository $galleryRepository): Response
    {
        $type = Gallery::TYPE_PRESS;
        $galleries = $galleryRepository->findAllWithThumbnails(null, false, $type);
        $galleryCount = $galleryRepository->countAll(null, false, $type);

        return $this->render('admin/gallery/press/index.html.twig', [
            'galleries' => $galleries,
            'galleryCount' => $galleryCount,
            'filterParams' => [],
        ]);
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        GalleryRepository $galleryRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
            $ids = $payload['ids'] ?? [];

            if (!is_array($ids)) {
                throw new InvalidArgumentException('Invalid input data.');
            }

            $galleries = $galleryRepository->findBy(['id' => $ids, 'type' => Gallery::TYPE_PRESS]);
            $indexed = [];
            foreach ($galleries as $gallery) {
                $indexed[$gallery->getId()] = $gallery;
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

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ThumbnailService $thumbnailService,
        GalleryService $galleryService,
        GalleryRepository $galleryRepository,
    ): Response {
        $gallery = new Gallery()
            ->setType(Gallery::TYPE_PRESS)
            ->setPosition($galleryRepository->countAll(type: Gallery::TYPE_PRESS));

        $form = $this->createForm(GalleryType::class, $gallery, ['with_categories' => false]);
        $form->handleRequest($request);

        // Extract filter param for redirect
        $filterParams = $galleryService->extractAdminFilterParams($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persist immediately gallery. S3 path required gallery ID
            $entityManager->persist($gallery);
            $entityManager->flush();

            // Handle the cover upload
            $thumbnailService->handle($form);
            $entityManager->flush();

            $this->addFlash('success', 'Votre galerie press est prête !');

            return $this->redirectToRoute(
                'app_admin_press_update',
                ['id' => $gallery->getId()] + $filterParams
            );
        }

        return $this->render('admin/gallery/press/create.html.twig', [
            'gallery' => $gallery,
            'form' => $form,
            'filterParams' => $filterParams,
        ]);
    }


    #[Route('/{id}/update', name: 'update', methods: ['GET', 'POST'])]
    public function update(
        Request $request,
        Gallery $gallery,
        EntityManagerInterface $entityManager,
        PictureRepository $pictureRepository,
        ThumbnailService $thumbnailService,
        PictureService $pictureService,
        GalleryService $galleryService,
        QrCodeService $qrCodeService,
    ): Response {
        $pictures = $pictureRepository->findByGalleryAndOrderPosition($gallery);
        $pictureIds = $pictureRepository->findIdsByGallery($gallery);

        // Build absolute URL
        $frontGalleryUrl = $galleryService->generatePublicUrl($gallery);

        // Extract filter param for redirect
        $filterParams = $galleryService->extractAdminFilterParams($request);

        $form = $this->createForm(GalleryType::class, $gallery, ['with_categories' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Thumbnail from form data
            $thumbnailService->handle($form);

            // Sort Gallery's pictures
            $pictureService->sortPicture();

            $entityManager->flush();
            $this->addFlash('success', 'Galerie press modifiée avec succès.');

            return $this->redirectToRoute('app_admin_press_index', $filterParams);
        }

        return $this->render('admin/gallery/press/update.html.twig', [
            'gallery' => $gallery,
            'form' => $form,
            'pictures' => $pictures,
            'pictureIds' => $pictureIds,
            'front_gallery_url' => $frontGalleryUrl,
            'front_gallery_qr_code' => $qrCodeService->generateDataUri($frontGalleryUrl),
            'filterParams' => $filterParams,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Gallery $gallery,
        EntityManagerInterface $entityManager,
        S3Service $s3Service,
        GalleryService $galleryService,
    ): Response {
        $filterParams = $galleryService->extractAdminFilterParams($request);

        if ($this->isCsrfTokenValid('delete' . $gallery->getId(), $request->request->get('_token'))) {
            // Clear All Temp files (S3 : temp/{id}.jpg)
            $tempKeys = [];
            foreach ($gallery->getPictures() as $picture) {
                if (in_array($picture->getStatus(), [Picture::STATUS_PROCESSING, Picture::STATUS_FAILED], true)) {
                    $tempKeys[] = PictureService::buildTempKey($picture);
                }
            }
            if (!empty($tempKeys)) {
                $s3Service->deleteFiles($tempKeys);
            }

            // Batch-delete everything, cover + pictures  (S3 : galleries/{id}/)
            $key = sprintf('galleries/%d/', $gallery->getId());
            $s3Service->deleteFilesByPrefix($key);

            // Remove Gallery => cascade deletes Thumbnail and Picture rows in DB.
            $entityManager->remove($gallery);
            $entityManager->flush();

            $this->addFlash('success', 'Galerie press supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_press_index', $filterParams);
    }
}