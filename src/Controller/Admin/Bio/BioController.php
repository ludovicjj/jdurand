<?php

namespace App\Controller\Admin\Bio;

use App\Enum\BrandSetting;
use App\Form\BioType;
use App\Service\Setting\BrandSettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/admin/bio', name: 'app_admin_bio')]
class BioController extends AbstractController
{
    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function index(Request $request, BrandSettingService $brandSettingService): Response
    {
        $form = $this->createForm(BioType::class, [
            'contentFr' => $brandSettingService->get(BrandSetting::BIO_FR),
            'contentEn' => $brandSettingService->get(BrandSetting::BIO_EN),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                $brandSettingService->save(BrandSetting::BIO_FR, $data['contentFr'] ?? null);
                $brandSettingService->save(BrandSetting::BIO_EN, $data['contentEn'] ?? null);
                $this->addFlash('success', 'Biographie mise à jour.');
            } catch (Throwable $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_admin_bio');
        }

        return $this->render('admin/bio/index.html.twig', [
            'form' => $form,
        ]);
    }
}
