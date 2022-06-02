<?php

namespace App\Form;

use App\Entity\Radio;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RadioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'name'])
            ->add('streamUrl', null, ['label' => 'streamUrl'])
            ->add('homepageUrl', null, ['label' => 'homePage'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Radio::class,
        ]);
    }
}
