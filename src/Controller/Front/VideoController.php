<?php

namespace App\Controller\Front;

use App\Entity\Video;
use App\Repository\VideoRepository;
use App\Service\Page\PageVisibilityChecker;
use App\Service\Video\VideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/extrait', name: 'app_front_video_')]
class VideoController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        VideoRepository $videoRepository,
        PageVisibilityChecker $pageVisibilityChecker,
    ): Response {
        // Check page is enable
        $pageVisibilityChecker->denyUnlessEnabled('clip_index');

        return $this->render('front/video/index.html.twig', [
            'videos' => $videoRepository->findPublicActive(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(
        Video $video,
        Request $request,
        VideoService $videoService,
        PageVisibilityChecker $pageVisibilityChecker,
    ): Response {
        // Check page is enable
        $pageVisibilityChecker->denyUnlessEnabled('clip_show');

        // Check can access to private clip
        if (!$videoService->canAccessVideo($video, $request->query->get('token'))) {
            return $this->redirectToRoute('app_front_video_index');
        }

        return $this->render('front/video/show.html.twig', [
            'video' => $video,
            'token' => $request->query->get('token'),
        ]);
    }
}
