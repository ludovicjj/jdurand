<?php

namespace App\Controller\Admin\Entry;

use App\Entity\Entry;
use App\Enum\EntryType;
use App\Form\EntryFormType;
use App\Repository\EntryPictureRepository;
use App\Repository\EntryRepository;
use App\Service\Entry\EntryPictureService;
use App\Service\Entry\EntryService;
use App\Service\S3Service;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/admin/entry/{type}', name: 'app_admin_entry_', requirements: ['type' => new EnumRequirement(EntryType::class)])]
#[IsGranted('ROLE_ADMIN')]
class EntryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntryType $type, EntryRepository $entryRepository): Response
    {
        return $this->render('admin/entry/index.html.twig', [
            'type' => $type,
            'entries' => $entryRepository->findAllOrdered($type),
            'entryCount' => $entryRepository->countAll($type),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET'])]
    public function create(
        EntryType $type,
        EntryRepository $entryRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $draft = $entryRepository->findOneBy(['isDraft' => true, 'type' => $type]);

        if ($draft === null) {
            $draft = new Entry()
                ->setType($type)
                ->setIsDraft(true)
                ->setVisibility(false)
                ->setPosition($entryRepository->getNextPosition($type));

            $entityManager->persist($draft);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_entry_update', [
            'type' => $type->value,
            'id' => $draft->getId(),
        ]);
    }

    #[Route('/{id}/update', name: 'update', methods: ['GET', 'POST'])]
    public function update(
        Request $request,
        EntryType $type,
        Entry $entry,
        EntityManagerInterface $entityManager,
        EntryService $entryService,
        EntryPictureRepository $entryPictureRepository,
        S3Service $s3Service,
    ): Response {
        $form = $this->createForm(EntryFormType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $posterError = null;
            $posterFile = $form->get('posterFile')->getData();
            if ($posterFile !== null) {
                try {
                    $entryService->uploadPoster($entry, $posterFile);
                } catch (Throwable $e) {
                    $posterError = $e->getMessage();
                }
            }

            $wasDraft = $entry->isDraft();
            if ($wasDraft) {
                $entry->setIsDraft(false);
            }

            $entityManager->flush();

            if ($posterError !== null) {
                $this->addFlash('error', 'Modifications enregistrées, mais l\'affiche n\'a pas pu être uploadée : ' . $posterError);

                return $this->redirectToRoute('app_admin_entry_update', [
                    'type' => $type->value,
                    'id' => $entry->getId(),
                ]);
            }

            $this->addFlash('success', $wasDraft ? 'Élément ajouté avec succès.' : 'Élément modifié avec succès.');

            return $this->redirectToRoute('app_admin_entry_index', ['type' => $type->value]);
        }

        $entryPictures = array_map(
            fn ($picture) => [
                'id' => $picture->getId(),
                'thumbnailUrl' => $s3Service->getPublicUrl($picture->getThumbnailPath()),
            ],
            $entryPictureRepository->findByEntryOrdered($entry),
        );

        return $this->render('admin/entry/update.html.twig', [
            'type' => $type,
            'entry' => $entry,
            'form' => $form,
            'front_entry_url' => $entryService->generatePublicUrl($entry),
            'entryPictures' => $entryPictures,
            'maxPictures' => EntryPictureService::MAX_PICTURES_PER_ENTRY,
        ]);
    }

    #[Route('/{id}/token', name: 'token', methods: ['POST'])]
    public function resetToken(
        Entry $entry,
        EntityManagerInterface $entityManager,
        EntryService $entryService,
    ): Response {
        $entry->resetToken();
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'url' => $entryService->generatePublicUrl($entry),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntryType $type,
        Entry $entry,
        EntityManagerInterface $entityManager,
        EntryPictureService $entryPictureService,
        EntryService $entryService,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $entry->getId(), $request->request->get('_token'))) {
            $entryPictureService->cleanupFilesForEntry($entry);
            $entryService->deletePosterFile($entry);
            $entityManager->remove($entry);
            $entityManager->flush();

            $this->addFlash('success', 'Élément supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_entry_index', ['type' => $type->value]);
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(
        Request $request,
        EntryType $type,
        Entry $entry,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->isCsrfTokenValid('toggle' . $entry->getId(), $request->request->get('_token'))) {
            $entry->setActive(!$entry->isActive());
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_entry_index', ['type' => $type->value]);
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        EntryType $type,
        EntryRepository $entryRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        try {
            $ids = $request->toArray()['ids'] ?? [];

            if (!is_array($ids)) {
                throw new InvalidArgumentException('Invalid input data, expected array.');
            }

            $entries = $entryRepository->findBy(['id' => $ids, 'type' => $type]);
            $indexed = [];
            foreach ($entries as $entry) {
                $indexed[$entry->getId()] = $entry;
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
