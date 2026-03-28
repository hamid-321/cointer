<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'empty_data' => '',
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter an email address',
                    ),
                    new Email(
                        message: 'Please enter a valid email address',
                    ),
                    new Length(
                        max: 180,
                        maxMessage: 'Email cannot be longer than {{ limit }} characters',
                    ),
                ],
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Display Name',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'name'],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter a display name',
                    ),
                    new Length(
                        min: 2,
                        minMessage: 'Display name must be at least {{ limit }} characters',
                        max: 30,
                        maxMessage: 'Display name cannot be longer than {{ limit }} characters',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}