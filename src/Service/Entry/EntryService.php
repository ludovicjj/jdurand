<?php

namespace App\Service\Entry;

use App\Entity\Entry;
use App\Service\ImageOptimizerService;
use App\Service\S3Service;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class EntryService
{
    private const string S3_POSTER_PREFIX = 'entry_posters';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ImageOptimizerService $imageOptimizer,
        private S3Service $s3Service,
    ) {
    }

    /**
     * Generate the public URL for an entry.
     * If the entry is private (visibility = false), the token is included as a query parameter.
     */
    public function generatePublicUrl(Entry $entry): string
    {
        $params = [
            'type' => $entry->getType()->value,
            'id' => $entry->getId(),
        ];

        if (!$entry->isVisibility()) {
            $params['token'] = $entry->getToken();
        }

        return $this->urlGenerator->generate(
            'app_front_entry_show',
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Upload the poster image to S3 (single optimized JPEG) and store its path
     * on the entry. The previous poster file, if any, is deleted from S3.
     * The caller is responsible for flushing.
     */
    public function uploadPoster(Entry $entry, UploadedFile $file): void
    {
        if ($entry->getId() === null) {
            throw new RuntimeException('Entry must be persisted before uploading a poster.');
        }

        $sourceContent = file_get_contents($file->getPathname());
        if ($sourceContent === false) {
            throw new RuntimeException('Impossible de lire le fichier uploadé.');
        }

        $optimized = $this->imageOptimizer->optimizeThumbnail($sourceContent, ImageOptimizerService::BIG_WIDTH);

        $key = sprintf('%s/%d/%s.jpg', self::S3_POSTER_PREFIX, $entry->getId(), uniqid());

        if (!$this->s3Service->uploadPublicFile($key, $optimized, 'image/jpeg')) {
            throw new RuntimeException('Échec de l\'upload S3 (affiche).');
        }

        $this->deletePosterFile($entry);
        $entry->setPosterPath($key);
    }

    /**
     * Delete the poster S3 object, if any. Does not flush.
     */
    public function deletePosterFile(Entry $entry): void
    {
        if ($entry->getPosterPath() !== null) {
            $this->s3Service->deleteFile($entry->getPosterPath());
        }
    }

    public function canAccessEntry(Entry $entry, ?string $token): bool
    {
        if ($entry->isVisibility()) {
            return true;
        }

        $entryToken = $entry->getToken();
        if ($entryToken === null || $token === null) {
            return false;
        }

        return hash_equals($entryToken, $token);
    }
}
