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
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
class EditExpeditorType extends AbstractType
{
    
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name')
            ->add('email',EmailType::class)
        
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
            ->add('identity')
            ->add('frais_de_livraison')
            ->add('frais_de_retour')
            ->add('phone')
            ->add('state', ChoiceType::class, [
                'choices'  => [
                    'active' => "active",
                    'inactive' => "inactive"
                 
                ],
            ]);           


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
