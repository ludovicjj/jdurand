<?php

namespace App\Controller\Front;

use App\Entity\Entry;
use App\Enum\EntryType;
use App\Repository\EntryPictureRepository;
use App\Repository\EntryRepository;
use App\Service\Entry\EntryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

#[Route('/{type}', name: 'app_front_entry_', requirements: ['type' => new EnumRequirement(EntryType::class)])]
class EntryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        EntryType $type,
        EntryRepository $entryRepository,
        EntryPictureRepository $entryPictureRepository,
    ): Response {
        $entries = $entryRepository->findPublicActive($type);
        $ids = array_map(fn (Entry $entry) => $entry->getId(), $entries);
        $picturesByEntryId = $entryPictureRepository->findGroupedByEntryIds($ids);

        return $this->render('front/entry/index.html.twig', [
            'type' => $type,
            'entries' => $entries,
            'picturesByEntryId' => $picturesByEntryId,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(
        EntryType $type,
        Entry $entry,
        Request $request,
        EntryService $entryService,
        EntryPictureRepository $entryPictureRepository,
    ): Response {
        if (!$entryService->canAccessEntry($entry, $request->query->get('token'))) {
            return $this->redirectToRoute('app_front_entry_index', ['type' => $type->value]);
        }

        return $this->render('front/entry/show.html.twig', [
            'type' => $type,
            'entry' => $entry,
            'token' => $request->query->get('token'),
            'pictures' => $entryPictureRepository->findByEntryOrdered($entry),
        ]);
    }
}
