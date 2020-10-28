<?php

namespace App\Form;

use App\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('language', null , array(
                'label' => 'language',
                'placeholder' => false,
                // 'choice_attr' => function ($allChoices, $currentChoiceKey) {
                //     return array('data-text' => 'text-muted');
                // },
            ))
            ->add('theme', null , array(
                'label' => 'theme',
                'placeholder' => false,
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
            'lang' => null,
        ]);

        // $resolver->setAllowedTypes('lang', ['array', 'null']);
    }
}
