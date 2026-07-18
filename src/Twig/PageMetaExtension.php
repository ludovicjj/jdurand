<?php

namespace App\Twig;

use App\Entity\Page;
use App\Repository\PageRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageMetaExtension extends AbstractExtension
{
    /** @var array<string, Page> */
    private array $cache = [];

    private const array NAV = [
        'home'        => ['route' => 'app_front_home',          'params' => []],
        'bio'         => ['route' => 'app_front_bio',           'params' => []],
        'filmo_index' => ['route' => 'app_front_entry_index',   'params' => ['type' => 'filmo']],
        'photo_index' => ['route' => 'app_front_gallery_index', 'params' => []],
        'press_index' => ['route' => 'app_front_press_index',   'params' => []],
        'clip_index'  => ['route' => 'app_front_video_index',   'params' => []],
        'contact'     => ['route' => 'app_front_contact',       'params' => []],
    ];

    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('page_title', fn (string $slug): string => $this->pageTitle($slug)),
            new TwigFunction('page_subtitle', fn (string $slug): string => $this->pageSubtitle($slug)),
            new TwigFunction('page_meta_title', fn (string $slug): string => $this->metaTitle($slug)),
            new TwigFunction('page_meta_description', fn (string $slug): string => $this->metaDescription($slug)),
            new TwigFunction('nav_items', [$this, 'navItems'])
        ];
    }

    private function pageTitle(string $slug): string
    {
        $page = $this->resolve($slug);

        return $this->isEnglish() ? (string) $page->getTitleEn() : (string) $page->getTitleFr();
    }

    private function pageSubtitle(string $slug): string
    {
        $page = $this->resolve($slug);

        return $this->isEnglish() ? (string) $page->getSubtitleEn() : (string) $page->getSubtitleFr();
    }

    private function metaTitle(string $slug): string
    {
        $page = $this->resolve($slug);

        return $this->isEnglish() ? (string) $page->getMetaTitleEn() : (string) $page->getMetaTitleFr();
    }

    private function metaDescription(string $slug): string
    {
        $page = $this->resolve($slug);

        return $this->isEnglish() ? (string) $page->getMetaDescriptionEn() : (string) $page->getMetaDescriptionFr();
    }

    private function resolve(string $slug): Page
    {
        if (!array_key_exists($slug, $this->cache)) {
            $page = $this->pageRepository->findOneBySlug($slug);

            if ($page === null) {
                throw new RuntimeException(sprintf('Page "%s" not found. Run "php bin/console app:seed-pages".', $slug));
            }

            $this->cache[$slug] = $page;
        }

        return $this->cache[$slug];
    }

    private function isEnglish(): bool
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() === 'en';
    }

    public function navItems(): array
    {
        $items = [];

        foreach ($this->pageRepository->findRootPagesOrdered() as $page) {
            $config = self::NAV[$page->getSlug()] ?? null;

            if ($config === null || !$page->isEffectivelyEnabled()) {
                continue;
            }

            $label = $this->isEnglish() ? $page->getLabelEn() : $page->getLabelFr();

            $items[] = $config + ['label' => $label ?? $page->getLabel()];
        }

        return $items;
    }
}
