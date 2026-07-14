<?php

namespace App\Controller\Front;

use App\Entity\Gallery;
use App\Repository\GalleryRepository;
use App\Repository\PictureRepository;
use App\Service\Gallery\GalleryService;
use App\Service\Page\PageVisibilityChecker;
use App\Service\S3Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/press', name: 'app_front_press_')]
class GalleryPressController extends AbstractController
{
    private const int PICTURES_PER_PAGE = 15;
    private const int GALLERIES_PER_PAGE = 6;

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        GalleryRepository $galleryRepository,
        PageVisibilityChecker $pageVisibilityChecker,
    ): Response {
        // Check page is enable
        $pageVisibilityChecker->denyUnlessEnabled('press_index');

        $galleries = $galleryRepository->findVisibleWithThumbnailsPaginated(
            null,
            0,
            self::GALLERIES_PER_PAGE,
            Gallery::TYPE_PRESS
        );
        $total = $galleryRepository->countVisible(null, Gallery::TYPE_PRESS);

        return $this->render('front/gallery/press/index.html.twig', [
            'galleries' => $galleries,
            'activeCategory' => null,
            'hasMore' => count($galleries) < $total,
            'nextOffset' => count($galleries),
        ]);
    }

    #[Route('/{id<\d+>}/{slug}', name: 'show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function show(
        Gallery $gallery,
        string $slug,
        Request $request,
        PictureRepository $pictureRepository,
        GalleryService $galleryService,
        PageVisibilityChecker $pageVisibilityChecker,
    ): Response {
        // Check page is enable
        $pageVisibilityChecker->denyUnlessEnabled('press_show');

        if ($gallery->getType() !== Gallery::TYPE_PRESS) {
            throw $this->createNotFoundException();
        }

        // Check can access to private gallery
        if (!$galleryService->canAccessGallery($gallery, $request->query->get('token'))) {
            return $this->redirectToRoute('app_front_home');
        }

        $expectedSlug = $galleryService->resolveSlug($gallery);

        if ($slug !== $expectedSlug) {
            return $this->redirectToRoute(
                'app_front_press_show',
                ['id' => $gallery->getId(), 'slug' => $expectedSlug] + $request->query->all(),
                Response::HTTP_MOVED_PERMANENTLY,
            );
        }

        $pictures = $pictureRepository->findByGalleryPaginated($gallery, 0, self::PICTURES_PER_PAGE);
        $totalPictures = $pictureRepository->countByGallery($gallery);

        $pictureLightboxPaths = $gallery->isVisibility()
            ? $pictureRepository->findReadyLightboxPathsByGallery($gallery)
            : [];

        return $this->render('front/gallery/press/show.html.twig', [
            'gallery' => $gallery,
            'pictures' => $pictures,
            'hasMore' => $totalPictures > self::PICTURES_PER_PAGE,
            'token' => $request->query->get('token'),
            'backParams' => [],
            'pictureLightboxPaths' => $pictureLightboxPaths,
        ]);
    }

    #[Route('/api/list', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        GalleryRepository $galleryRepository,
        S3Service $s3Service,
        GalleryService $galleryService,
        PageVisibilityChecker $pageVisibilityChecker,
    ): JsonResponse {
        // Check page is enable
        $pageVisibilityChecker->denyUnlessEnabled('press_index');

        $offset = max(0, $request->query->getInt('offset'));

        $galleries = $galleryRepository->findVisibleWithThumbnailsPaginated(
            category: null,
            offset: $offset,
            limit: self::GALLERIES_PER_PAGE,
            type: Gallery::TYPE_PRESS
        );

        $total = $galleryRepository->countVisible(null, Gallery::TYPE_PRESS);

        $payload = array_map(fn(Gallery $gallery) => [
            'id' => $gallery->getId(),
            'title' => $gallery->getTitle(),
            'url' => $this->generateUrl('app_front_press_show', ['id' => $gallery->getId(), 'slug' => $galleryService->resolveSlug($gallery)]),
            'thumbnailUrl' => $gallery->getThumbnail() ? $s3Service->getPublicUrl($gallery->getThumbnail()->getFilename()) : null,
        ], $galleries);

        return $this->json([
            'galleries' => $payload,
            'hasMore' => ($offset + self::GALLERIES_PER_PAGE) < $total,
            'nextOffset' => $offset + self::GALLERIES_PER_PAGE,
        ]);
    }
}