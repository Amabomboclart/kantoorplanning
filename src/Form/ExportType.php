<?php

namespace App\Form;

use App\Entity\Locatie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType; // Change this line
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('export', SubmitType::class, [
                'label' => 'Export',
                'attr' => [
                    'id' => 'export',
                    'class' => 'btn button-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Locatie::class,
        ]);
    }
}
