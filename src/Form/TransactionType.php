<?php

namespace App\Form;

use App\Entity\Coin;
use App\Entity\Transaction;
use App\Twig\DataFormatter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Form\CoinAutoCompleteField;
use Symfony\Component\Validator\Constraints\NotBlank;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $holdingsByCoin = $options['holdings_by_coin'];
        $quantityFormatter = $options['quantity_formatter'];

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Buy' => 'buy',
                    'Sell' => 'sell',
                ],
            ])
            ->add('coin', CoinAutoCompleteField::class, [
                'label' => 'Select Coin',
                'attr' => [
                    'placeholder' => 'Type to search',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Coin is required.',
                    ),
                ],
            ])
            ->add('quantity', NumberType::class, [
                'scale' => 8,
                'attr' => ['step' => 'any'],
                'constraints' => [
                    new Range(
                        min: 0.00000001,
                        max: 99999999.99999999,
                        notInRangeMessage: 'Quantity must be greater than 0 and less than {{ max }}.',
                    ),
                    new Callback(function ($quantity, ExecutionContextInterface $context) use ($holdingsByCoin, $quantityFormatter): void {
                        $transaction = $context->getRoot()->getData();
                        if (!$transaction instanceof Transaction || $transaction->getType() !== 'sell') 
                        {
                            return;
                        }

                        $coin = $transaction->getCoin();
                        
                        if (!$coin) 
                        {
                            return;
                        }

                        $available = $holdingsByCoin[$coin->getId()] ?? 0.0;

                        if ((float) $quantity > $available) 
                        {
                            $formatted = $quantityFormatter instanceof DataFormatter
                                ? $quantityFormatter->formatQuantityInput($available)
                                : number_format($available, 8);
                            $context->buildViolation('You can only sell up to {{ balance }} tokens.')
                                ->setParameter('{{ balance }}', $formatted)
                                ->addViolation();
                        }
                    }),
                ],
            ])
            //pricePerCoin is not actually in the database
            //It is used for the JS price calc functionality
            //Allows user to input price per coin instead of total cost, auto calcs total cost vice versa
            ->add('pricePerCoin', NumberType::class, [
                'mapped' => false,
                'required' => false,
                'scale' => 10,
                'attr' => ['step' => 'any'],
                'data' => $options['price_per_coin'],
                'constraints' => [
                    new GreaterThanOrEqual(
                        value: 0,
                        message: 'Price per coin cannot be negative.',
                    ),
                    new Range(
                        min: 0,
                        max: 999999999.999999999,
                        notInRangeMessage: 'Price per coin must be between {{ min }} and {{ max }}.',
                    ),
                ],
            ])
            ->add('price', NumberType::class, [
                'scale' => 10,
                'attr' => ['step' => 'any'],
                'constraints' => [
                    new GreaterThanOrEqual(
                        value: 0,
                        message: 'Price cannot be negative.',
                    ),
                    new Range(
                        min: 0,
                        max: 999999999.99999999,
                        notInRangeMessage: 'Total value must be between {{ min }} and {{ max }}.',
                    ),
                ],
            ])
            ->add('createdAt', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'required' => true,
                'attr' => [
                    'class' => 'block w-full rounded-md border border-gray-600 bg-primary px-3 py-2 text-white shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent scheme-dark',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'price_per_coin' => null,
            'holdings_by_coin' => [],
            'quantity_formatter' => null,
        ]);
        $resolver->setAllowedTypes('holdings_by_coin', 'array');
        $resolver->setAllowedTypes('quantity_formatter', [DataFormatter::class, 'null']);
    }
}
