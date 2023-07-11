<?php

namespace App\Form;

use App\Entity\Package;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\UserRepository;
use App\Entity\Region;
use App\Entity\City;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\DateType;
class PackageType extends AbstractType
{
    
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label')
            ->add('price')
            ->add('region',EntityType::class,[
                "class"=>Region::class,
                'choice_label' => 'label',
                "expanded"=>false,
                "multiple"=>false
                ])
            ->add('city',EntityType::class,[
                "class"=>City::class,
                'choice_label' => 'label',
                "expanded"=>false,
                "multiple"=>false
                ])
            ->add('adress')
            ->add('dest_name')
            ->add('dest_phone')
            ->add('nb_product')
            ->add('state',ChoiceType::class, [
                'choices'  => [
                    'Waiting' => "waiting",
                    'Approved' => "approved",
                    'Picked' => "picked",
                    'Delivered' => "delivered",
                    'Returned' => "returned",
                ],
            ])
            ->add('delivery',EntityType::class, [
                "class"=>User::class,
                'query_builder' =>  function(UserRepository $userRep)
                {
                    return $userRep->createQueryBuilder("u")->andWhere('u.roles LIKE \'%DELIVERY_ROLE%\' ');
                },
                'choice_label' => 'name',
                "expanded"=>false,
                "multiple"=>false
                
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Package::class,
        ]);
    }
}
