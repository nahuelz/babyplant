<?php

namespace App\Form;

use App\Entity\TipoProducto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TipoProductoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre')
            ->add('descripcion')
            ->add('fechaCreacion')
            ->add('fechaUltimaModificacion')
            ->add('fechaBaja')
            ->add('codigoInterno')
            ->add('habilitado')
            ->add('fechaDeshabilitado')
            ->add('usuarioCreacion')
            ->add('usuarioUltimaModificacion')
            ->add('usuarioDeshabilito')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TipoProducto::class,
        ]);
    }
}