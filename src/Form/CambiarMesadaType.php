<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CambiarMesadaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mesadaUno', MesadaType::class, [
                'label' => 'Mesada N° 1',
            ])
            ->add('mesadaDos', MesadaType::class, [
                'required' => false,
                'label' => 'Mesada N° 2',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PedidoProducto::class,
        ]);
    }
}
