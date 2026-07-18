<?php

namespace App\Form;

use App\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Page|null $page */
        $page = $options['data'] ?? null;

        // Menu labels only make sense for root pages (the only ones shown in the nav).
        if ($page?->getParent() === null) {
            $builder
                ->add('labelFr', TextType::class, [
                    'label' => 'Libellé du menu (FR)',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Texte du lien dans le menu du site',
                        'maxlength' => 255,
                    ],
                ])
                ->add('labelEn', TextType::class, [
                    'label' => 'Libellé du menu (EN)',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Link text in the site menu',
                        'maxlength' => 255,
                    ],
                ]);
        }

        $builder
            ->add('titleFr', TextType::class, [
                'label' => 'Titre de la page (FR)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Titre affiché sur la page',
                    'maxlength' => 255,
                ],
            ])
            ->add('titleEn', TextType::class, [
                'label' => 'Titre de la page (EN)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Title displayed on the page',
                    'maxlength' => 255,
                ],
            ])
            ->add('subtitleFr', TextareaType::class, [
                'label' => 'Sous-titre (FR)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Sous-titre affiché sous le titre (une ligne par retour à la ligne)',
                    'rows' => 3,
                ],
            ])
            ->add('subtitleEn', TextareaType::class, [
                'label' => 'Sous-titre (EN)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Subtitle displayed below the title (one line per line break)',
                    'rows' => 3,
                ],
            ])
            ->add('metaTitleFr', TextType::class, [
                'label' => 'Meta title (FR)',
                'attr' => [
                    'placeholder' => 'Titre affiché dans l\'onglet et les SERP',
                    'maxlength' => 70,
                ],
            ])
            ->add('metaTitleEn', TextType::class, [
                'label' => 'Meta title (EN)',
                'attr' => [
                    'placeholder' => 'Title shown in the browser tab and SERP',
                    'maxlength' => 70,
                ],
            ])
            ->add('metaDescriptionFr', TextareaType::class, [
                'label' => 'Meta description (FR)',
                'attr' => [
                    'placeholder' => 'Description courte affichée dans les résultats Google',
                    'maxlength' => 200,
                    'rows' => 3,
                ],
            ])
            ->add('metaDescriptionEn', TextareaType::class, [
                'label' => 'Meta description (EN)',
                'attr' => [
                    'placeholder' => 'Short description shown in Google search results',
                    'maxlength' => 200,
                    'rows' => 3,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
