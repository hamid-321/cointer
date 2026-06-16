<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
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
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The password fields must match.',
                'first_options'  => [
                    'label' => 'Password',
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(
                            message: 'Please enter a password',
                        ),
                    ],
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(
                            message: 'Please confirm your password',
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
