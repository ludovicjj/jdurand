<?php

namespace App\Controller\Front;

use App\Enum\BrandSetting;
use App\Service\Page\PageVisibilityChecker;
use App\Service\Setting\BrandSettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BioController extends AbstractController
{
    #[Route('/bio', name: 'app_front_bio', methods: ['GET'])]
    public function index(
        Request $request,
        BrandSettingService $brandSettingService,
        PageVisibilityChecker $pageVisibilityChecker,
    ): Response {
        // Check page is enable
        $pageVisibilityChecker->denyUnlessEnabled('bio');

        $bio = $request->getLocale() === 'en'
            ? ($brandSettingService->get(BrandSetting::BIO_EN) ?? $brandSettingService->get(BrandSetting::BIO_FR))
            : $brandSettingService->get(BrandSetting::BIO_FR);

        return $this->render('front/bio/index.html.twig', [
            'bio' => $bio,
        ]);
    }
}
