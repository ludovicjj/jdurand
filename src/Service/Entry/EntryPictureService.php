<?php

namespace App\Service\Entry;

use App\Entity\Entry;
use App\Entity\EntryPicture;
use App\Repository\EntryPictureRepository;
use App\Service\ImageOptimizerService;
use App\Service\S3Service;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class EntryPictureService
{
    public const int MAX_PICTURES_PER_ENTRY = 5;

    private const string S3_PREFIX = 'entry_pictures';
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png'];
    private const int MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    public function __construct(
        private EntryPictureRepository $repository,
        private EntityManagerInterface $entityManager,
        private ImageOptimizerService $imageOptimizer,
        private S3Service $s3Service,
        private Security $security,
    ) {
    }

    /**
     * Synchronous upload: validate file, generate the two resized variants
     * (1200px + 400px), upload both to S3, persist the EntryPicture row.
     * Rolls back the lightbox object if the thumbnail upload fails.
     */
    public function upload(Entry $entry, UploadedFile $file): EntryPicture
    {
        if ($entry->getId() === null) {
            throw new RuntimeException('Entry must be persisted before uploading pictures.');
        }

        if ($this->repository->countByEntry($entry) >= self::MAX_PICTURES_PER_ENTRY) {
            throw new RuntimeException(sprintf('Limite atteinte : %d images max.', self::MAX_PICTURES_PER_ENTRY));
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Type de fichier non autorisé. Formats acceptés : JPG, PNG.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException(sprintf('Fichier trop volumineux. Taille max : %d Mo.', self::MAX_FILE_SIZE / 1024 / 1024));
        }

        $sourceContent = file_get_contents($file->getPathname());
        if ($sourceContent === false) {
            throw new RuntimeException('Impossible de lire le fichier uploadé.');
        }

        $variants = $this->imageOptimizer->optimizePicture($sourceContent);

        $base = sprintf('%s/%d/%s', self::S3_PREFIX, $entry->getId(), uniqid());
        $lightboxKey = $base . '.jpg';
        $thumbnailKey = $base . '-thumb.jpg';

        if (!$this->s3Service->uploadPublicFile($lightboxKey, $variants['lightbox'], 'image/jpeg')) {
            throw new RuntimeException('Échec de l\'upload S3 (lightbox).');
        }

        if (!$this->s3Service->uploadPublicFile($thumbnailKey, $variants['thumbnail'], 'image/jpeg')) {
            $this->s3Service->deleteFile($lightboxKey);
            throw new RuntimeException('Échec de l\'upload S3 (thumbnail).');
        }

        $picture = new EntryPicture()
            ->setEntry($entry)
            ->setLightboxPath($lightboxKey)
            ->setThumbnailPath($thumbnailKey)
            ->setPosition($this->repository->getNextPositionByEntry($entry))
            ->setCreatedBy($this->security->getUser());

        if ($entry->isDraft()) {
            $entry->setIsDraft(false);
        }

        $this->entityManager->persist($picture);
        $this->entityManager->flush();

        return $picture;
    }

    /**
     * Delete an EntryPicture entity and both associated S3 objects.
     */
    public function delete(EntryPicture $picture): void
    {
        $this->s3Service->deleteFile($picture->getLightboxPath());
        $this->s3Service->deleteFile($picture->getThumbnailPath());

        $this->entityManager->remove($picture);
        $this->entityManager->flush();
    }

    /**
     * Delete every S3 file (lightbox + thumbnail) attached to an entry's pictures.
     * Does NOT remove the EntryPicture rows — caller relies on the FK CASCADE to
     * clean those up when the Entry row is removed.
     */
    public function cleanupFilesForEntry(Entry $entry): void
    {
        foreach ($this->repository->findByEntryOrdered($entry) as $picture) {
            $this->s3Service->deleteFile($picture->getLightboxPath());
            $this->s3Service->deleteFile($picture->getThumbnailPath());
        }
    }

    /**
     * Reorder EntryPicture rows of an entry to match the given id sequence.
     * Ids that don't belong to the entry are silently ignored.
     *
     * @param int[] $ids
     */
    public function reorder(Entry $entry, array $ids): void
    {
        $pictures = $this->repository->findBy(['id' => $ids, 'entry' => $entry]);

        $indexed = [];
        foreach ($pictures as $picture) {
            $indexed[$picture->getId()] = $picture;
        }

        foreach ($ids as $position => $id) {
            if (isset($indexed[$id])) {
                $indexed[$id]->setPosition($position);
            }
        }

        $this->entityManager->flush();
    }
}
