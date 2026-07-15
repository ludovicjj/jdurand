<?php

namespace App\Controller\Front;

use App\Entity\Gallery;
use App\Repository\CategoryRepository;
use App\Repository\GalleryRepository;
use App\Repository\PageRepository;
use App\Service\Gallery\GalleryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_front_sitemap', methods: ['GET'])]
    public function index(
        GalleryRepository $galleryRepository,
        CategoryRepository $categoryRepository,
        GalleryService $galleryService,
        PageRepository $pageRepository,
    ): Response {
        $urls = [];

        $urls[] = [
            'loc' => $this->generateUrl('app_front_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        $bioPage = $pageRepository->findOneBySlug('bio');
        if ($bioPage && $bioPage->isEffectivelyEnabled()) {
            $urls[] = [
                'loc' => $this->generateUrl('app_front_bio', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        $photoPage = $pageRepository->findOneBySlug('photo_index');
        if ($photoPage && $photoPage->isEffectivelyEnabled()) {
            $urls[] = [
                'loc' => $this->generateUrl('app_front_gallery_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];

            foreach ($categoryRepository->findVisibleOrdered() as $category) {
                $urls[] = [
                    'loc' => $this->generateUrl(
                        'app_front_gallery_index',
                        ['category' => $category->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }

            $galleries = $galleryRepository->findBy(['visibility' => true, 'type' => Gallery::TYPE_PHOTO]);
            foreach ($galleries as $gallery) {
                $urls[] = [
                    'loc' => $galleryService->generatePublicUrl($gallery),
                    'lastmod' => $gallery->getUpdatedAt()?->format('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => '0.8',
                ];
            }
        }

        $pressPage = $pageRepository->findOneBySlug('press_index');
        if ($pressPage && $pressPage->isEffectivelyEnabled()) {
            $urls[] = [
                'loc' => $this->generateUrl('app_front_press_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];

            $presses = $galleryRepository->findBy(['visibility' => true, 'type' => Gallery::TYPE_PRESS]);
            foreach ($presses as $press) {
                $urls[] = [
                    'loc' => $galleryService->generatePublicUrl($press),
                    'lastmod' => $press->getUpdatedAt()?->format('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ];
            }
        }

        $clipPage = $pageRepository->findOneBySlug('clip_index');
        if ($clipPage && $clipPage->isEffectivelyEnabled()) {
            $urls[] = [
                'loc' => $this->generateUrl('app_front_video_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $contactPage = $pageRepository->findOneBySlug('contact');
        if ($contactPage && $contactPage->isEffectivelyEnabled()) {
            $urls[] = [
                'loc' => $this->generateUrl('app_front_contact', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        $response = $this->render('front/sitemap.xml.twig', ['urls' => $urls]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
