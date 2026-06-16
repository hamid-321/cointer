<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('current_password', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter your current password',
                    ),
                ],
            ])
            ->add('new_password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The password fields must match.',
                'first_options'  => [
                    'label' => 'New password',
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(
                            message: 'Please enter a new password',
                        ),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm new password',
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(
                            message: 'Please confirm your new password',
                        ),
                        new Length(
                            min: 8,
                            minMessage: 'Your password must be at least {{ limit }} characters.',
                            max: 128,
                            maxMessage: 'Your password must be at most {{ limit }} characters.',
                        ),
                        new Regex(
                            pattern: '/[A-Z]/',
                            message: 'Your password must contain at least one uppercase letter.',
                        ),
                        new Regex(
                            pattern: '/[0-9]/',
                            message: 'Your password must contain at least one number.',
                        ),
                        new Regex(
                            pattern: '/[^a-zA-Z0-9]/',
                            message: 'Your password must contain at least one special character.',
                        ),
                    ],
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
