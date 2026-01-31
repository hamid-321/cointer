<?php

namespace App\Form;

use App\Entity\Coin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coin_gecko_id')
            ->add('symbol')
            ->add('name')
            ->add('price')
            ->add('market_cap')
            ->add('change_24h')
            ->add('updated_at', null, [
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Coin::class,
        ]);
    }
}
