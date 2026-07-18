<?php

namespace App\Controller\Admin;

use App\Entity\Picture;
use App\Enum\EntryType;
use App\Repository\EntryRepository;
use App\Repository\GalleryRepository;
use App\Repository\PictureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route(path: '/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        GalleryRepository $galleryRepository,
        PictureRepository $pictureRepository,
        EntryRepository $entryRepository,
    ): Response {
        $galleryCount = $galleryRepository->countAll();
        $pictureCount = $pictureRepository->countByStatus(Picture::STATUS_READY);
        $filmoCount = $entryRepository->countAll(EntryType::FILMO);

        return $this->render('admin/dashboard/index.html.twig', [
            'galleryCount' => $galleryCount,
            'pictureCount' => $pictureCount,
            'filmoCount' => $filmoCount,
        ]);
    }
}
