<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

class BioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contentFr', TextareaType::class, [
                'label' => 'Biographie (français)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre biographie en français...',
                    'rows' => 12,
                    'data-ckeditor' => 'true',
                ],
                'constraints' => [
                    new Length(max: 20000, maxMessage: 'La biographie ne doit pas dépasser {{ limit }} caractères.'),
                ],
            ])
            ->add('contentEn', TextareaType::class, [
                'label' => 'Biographie (anglais)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Your biography in English...',
                    'rows' => 12,
                    'data-ckeditor' => 'true',
                ],
                'constraints' => [
                    new Length(max: 20000, maxMessage: 'La biographie ne doit pas dépasser {{ limit }} caractères.'),
                ],
            ]);
    }
}
