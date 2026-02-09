<?php

namespace App\Form;

use App\Entity\Portfolio;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PortfolioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Portfolio Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a portfolio name',
                        'normalizer' => 'trim',
                    ]),
                    new Length([
                        'min' => 1,
                        'max' => 255,
                        'maxMessage' => 'Portfolio name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Portfolio::class,
        ]);
    }
}
