<?php

namespace App\Command;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:seed-pages',
    description: 'Truncate the Page table and re-seed it with the static front pages.',
)]
class SeedPagesCommand extends Command
{

    private const array PAGES = [
        [
            'slug' => 'home',
            'parentSlug' => null,
            'label' => 'Accueil',
            'position' => 1,
            'titleFr' => 'Julie Durand',
            'titleEn' => 'Julie Durand',
            'subtitleFr' => "Comédienne",
            'subtitleEn' => "Actress",
            'metaTitleFr' => 'Julie Durand - Comédienne',
            'metaTitleEn' => 'Julie Durand - Actress',
            'metaDescriptionFr' => 'Julie Durand, Comédienne. Découvrez ses galeries photo et ses réalisations vidéo.',
            'metaDescriptionEn' => 'Julie Durand, actress. Browse her photo galleries and video clips.',
        ],
        [
            'slug' => 'photo_index',
            'parentSlug' => null,
            'label' => 'Photos',
            'position' => 2,
            'titleFr' => 'Photos',
            'titleEn' => 'Photos',
            'subtitleFr' => 'Explorez nos galeries photo',
            'subtitleEn' => 'Explore our photo galleries',
            'metaTitleFr' => 'Photos | Julie Durand',
            'metaTitleEn' => 'Photos | Julie Durand',
            'metaDescriptionFr' => 'Les galeries photo de Julie Durand, comédienne : portraits, shootings et scènes.',
            'metaDescriptionEn' => 'The photo galleries of Julie Durand, actress: portraits, shoots and stage work.',
        ],
        [
            'slug' => 'photo_show',
            'parentSlug' => 'photo_index',
            'label' => 'Détail photo',
            'position' => 3,
            'titleFr' => null,
            'titleEn' => null,
            'subtitleFr' => null,
            'subtitleEn' => null,
            'metaTitleFr' => 'Galerie photo | Julie Durand',
            'metaTitleEn' => 'Photo gallery | Julie Durand',
            'metaDescriptionFr' => 'Galerie photo de Julie Durand, comédienne.',
            'metaDescriptionEn' => 'Photo gallery of Julie Durand, actress.',
        ],
        [
            'slug' => 'press_index',
            'parentSlug' => null,
            'label' => 'Press',
            'position' => 4,
            'titleFr' => 'Press',
            'titleEn' => 'Press',
            'subtitleFr' => 'Dossiers de presse et photos officielles',
            'subtitleEn' => 'Press kits and official photos',
            'metaTitleFr' => 'Press | Julie Durand',
            'metaTitleEn' => 'Press | Julie Durand',
            'metaDescriptionFr' => 'Les dossiers de presse de Julie Durand, comédienne : photos officielles et visuels presse.',
            'metaDescriptionEn' => 'The press kits of Julie Durand, actress: official photos and press visuals.',
        ],
        [
            'slug' => 'press_show',
            'parentSlug' => 'press_index',
            'label' => 'Détail press',
            'position' => 5,
            'titleFr' => null,
            'titleEn' => null,
            'subtitleFr' => null,
            'subtitleEn' => null,
            'metaTitleFr' => 'Press | Julie Durand',
            'metaTitleEn' => 'Press | Julie Durand',
            'metaDescriptionFr' => 'Dossier de presse de Julie Durand, comédienne.',
            'metaDescriptionEn' => 'Press kit of Julie Durand, actress.',
        ],
        [
            'slug' => 'clip_index',
            'parentSlug' => null,
            'label' => 'Extraits',
            'position' => 6,
            'titleFr' => 'Extraits',
            'titleEn' => 'Clips',
            'subtitleFr' => "Extraits vidéo",
            'subtitleEn' => "video clips",
            'metaTitleFr' => 'Extraits | Julie Durand',
            'metaTitleEn' => 'Clips | Julie Durand',
            'metaDescriptionFr' => 'Les extraits vidéo de Julie Durand, comédienne — scènes, bande démo et captations.',
            'metaDescriptionEn' => "Julie Durand's video clips — scenes, showreel and recordings.",
        ],
        [
            'slug' => 'clip_show',
            'parentSlug' => 'clip_index',
            'label' => 'Détail extrait',
            'position' => 7,
            'titleFr' => null,
            'titleEn' => null,
            'subtitleFr' => null,
            'subtitleEn' => null,
            'metaTitleFr' => 'Extrait | Julie Durand',
            'metaTitleEn' => 'Clip | Julie Durand',
            'metaDescriptionFr' => 'Extrait vidéo de Julie Durand, comédienne.',
            'metaDescriptionEn' => 'Video clip of Julie Durand, actress.',
        ],
        [
            'slug' => 'contact',
            'parentSlug' => null,
            'label' => 'Contact',
            'position' => 8,
            'titleFr' => 'Contact',
            'titleEn' => 'Contact',
            'subtitleFr' => 'Une question, un projet ? Écrivez-nous.',
            'subtitleEn' => 'A question, a project? Write to us.',
            'metaTitleFr' => 'Contact | Julie Durand',
            'metaTitleEn' => 'Contact | Julie Durand',
            'metaDescriptionFr' => 'Contactez Julie Durand, comédienne — castings, projets et collaborations.',
            'metaDescriptionEn' => 'Get in touch with Julie Durand, actress — castings, projects and collaborations.',
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seed pages');

        try {
            $connection = $this->entityManager->getConnection();
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            $connection->executeStatement('TRUNCATE TABLE page');
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            $io->text('table "page" truncated');

            /** @var array<string, Page> $bySlug */
            $bySlug = [];

            foreach (self::PAGES as $data) {
                $page = new Page()
                    ->setSlug($data['slug'])
                    ->setLabel($data['label'])
                    ->setPosition($data['position'])
                    ->setTitleFr($data['titleFr'])
                    ->setTitleEn($data['titleEn'])
                    ->setSubtitleFr($data['subtitleFr'])
                    ->setSubtitleEn($data['subtitleEn'])
                    ->setMetaTitleFr($data['metaTitleFr'])
                    ->setMetaTitleEn($data['metaTitleEn'])
                    ->setMetaDescriptionFr($data['metaDescriptionFr'])
                    ->setMetaDescriptionEn($data['metaDescriptionEn']);

                $this->entityManager->persist($page);
                $bySlug[$data['slug']] = $page;
                $io->text(sprintf(' - %s  →  created', $data['slug']));
            }

            foreach (self::PAGES as $data) {
                if ($data['parentSlug'] === null) {
                    continue;
                }

                if (!isset($bySlug[$data['parentSlug']])) {
                    throw new RuntimeException(sprintf('Page "%s" references unknown parent "%s".', $data['slug'], $data['parentSlug']));
                }

                $bySlug[$data['slug']]->setParent($bySlug[$data['parentSlug']]);
            }

            $this->entityManager->flush();

            $io->success(sprintf('%d page(s) seeded.', count(self::PAGES)));

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
