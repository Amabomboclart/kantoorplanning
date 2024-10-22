<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class defaultValueType extends AbstractType
{

    function getWeekday($date) {
        return date('l', strtotime($date));
    }
    public function buildForm(FormBuilderInterface $builder, array $options,)
    {
        $weekDays = [
            'Monday' => NULL,
            'Tuesday' => NULL,
            'Wednesday' => NULL,
            'Thursday' => NULL,
            'Friday' => NULL,
        ];

        $locations = [
            "Monday" => NULL,
            "Tuesday" => NULL,
            "Wednesday" => NULL,
            "Thursday" => NULL,
            "Friday" => NULL,
        ];

        foreach($options['locationAll'] as $key => $location) {
            $locations[$this->getWeekday($key)] = $location;
        }

        (!empty($locations)) ? $locations = array_merge($weekDays, $locations) : $locations = [];

        foreach ($locations as $day => $location) {
            $builder->add($day, ChoiceType::class, [
                'label' => '',
                'choices' => [
                    'Kantoor' => 0,
                    'Thuis' => 1,
                    'Afwezig' => 2,
                ],

                'data' => $location,
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Submit',
            'attr' => [
                'id' => 'submit',
                'class' => 'btn button-primary'
            ],
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Submit',
            'attr' => [
                'id' => 'submit',
                'class' => 'btn button-primary'
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'locationAll' => [],
        ]);
    }
}
