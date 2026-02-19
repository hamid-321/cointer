<?php

namespace App\Form;

use App\Entity\Coin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CoinAutoCompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Coin::class,
            'choice_label' => 'name',
            'searchable_fields' => ['name', 'symbol'],
            'tom_select_options' => [
                // Only open dropdown when user types, not on focus (avoids showing selected value again in list)
                'openOnFocus' => false,
            ],
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
