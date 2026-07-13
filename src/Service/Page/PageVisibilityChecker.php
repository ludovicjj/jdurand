<?php

namespace App\Service\Page;

use App\Repository\PageRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class PageVisibilityChecker
{
    public function __construct(
        private PageRepository $pageRepository,
    ) {
    }

    public function denyUnlessEnabled(string $slug): void
    {
        $page = $this->pageRepository->findOneBySlug($slug);

        if ($page !== null && !$page->isEffectivelyEnabled()) {
            throw new NotFoundHttpException(sprintf('Page "%s" is disabled.', $slug));
        }
    }
}