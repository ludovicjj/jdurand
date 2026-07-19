<?php

namespace App\Form;

use App\Entity\Video;
use App\Service\Video\VideoThumbnailResolver;
use App\Service\Video\VideoUrlParser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class VideoType extends AbstractType
{
    public function __construct(
        private readonly VideoUrlParser $videoUrlParser,
        private readonly VideoThumbnailResolver $videoThumbnailResolver,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.rich_text_with_links')]
        private readonly HtmlSanitizerInterface $descriptionSanitizer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Titre de la vidéo (facultatif)',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description (facultatif)',
                    'rows' => 6,
                    'data-ckeditor' => 'true',
                ],
            ])
            ->add('url', UrlType::class, [
                'label' => 'Lien de la vidéo',
                'attr' => [
                    'placeholder' => 'https://www.youtube.com/watch?v=...',
                ],
                'constraints' => [
                    new Callback([$this, 'validateProviderUrl']),
                ],
            ])
            ->add('visibility', CheckboxType::class, [
                'label' => 'Visibilité',
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Video|null $video */
            $video = $event->getData();

            if ($video === null) {
                return;
            }

            if ($video->getUrl() !== null) {
                // Fetch parser by URL
                $parsed = $this->videoUrlParser->parse($video->getUrl());

                if ($parsed !== null) {
                    $video->setProvider($parsed['provider']);
                    $video->setExternalId($parsed['externalId']);
                    $video->setThumbnailUrl(
                        $this->videoThumbnailResolver->resolve($parsed['provider'], $parsed['externalId'])
                    );
                }
            }

            if ($video->getDescription() !== null) {
                $sanitized = $this->descriptionSanitizer->sanitize($video->getDescription());

                // Strip empty paragraphs (<p>&nbsp;</p>)
                $sanitized = preg_replace('~<p>[\s\x{00A0}]*</p>~u', '', $sanitized);
                $sanitized = trim($sanitized);
                $video->setDescription($sanitized === '' ? null : $sanitized);
            }
        });
    }

    public function validateProviderUrl(mixed $url, ExecutionContextInterface $context): void
    {
        if (!is_string($url) || $url === '') {
            return;
        }

        if ($this->videoUrlParser->parse($url) === null) {
            $context->buildViolation('URL non reconnue. Utilisez un lien YouTube, Vimeo ou Dailymotion.')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
        ]);
    }
}
