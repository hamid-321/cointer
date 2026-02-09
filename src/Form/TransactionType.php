<?php

namespace App\Form;

use App\Entity\Coin;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Buy' => 'buy',
                    'Sell' => 'sell',
                ],
            ])
            ->add('coin', EntityType::class, [
                'class' => Coin::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a coin',
            ])
            ->add('quantity', NumberType::class, [
                'scale' => 8,
                'attr' => ['step' => 'any'],
            ])
            //pricePerCoin is not actually in the database
            //It is used for the JS price calc functionality
            //Allows user to input price per coin instead of total cost, auto calcs total cost vice versa
            ->add('pricePerCoin', NumberType::class, [
                'mapped' => false,
                'required' => false,
                'scale' => 2,
                'attr' => ['step' => 'any'],
                'data' => $options['price_per_coin'],
            ])
            ->add('price', NumberType::class, [
                'scale' => 10,
                'attr' => ['step' => 'any'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'price_per_coin' => null,
        ]);
    }
}
