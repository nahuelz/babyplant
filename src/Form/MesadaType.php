<?php

namespace App\Form;

use App\Entity\Mesada;
use App\Entity\TipoMesada;
use App\Entity\TipoProducto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MesadaType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('tipoProducto', EntityType::class, array(
                    'label' => 'Tipo Mesada',
                    'class' => TipoProducto::class,
                    'required' => false,
                    'mapped' => false,
                    'attr' => array(
                        'class' => 'form-control choice',
                        'tabindex' => '5'
                    ),
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Mostrar todas las mesadas --',
                    'auto_initialize' => false,
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    }
            ))
            ->add('tipoMesada', EntityType::class, array(
                    'label' => 'Mesada',
                    'mapped' => true,
                    'class' => TipoMesada::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija la mesada --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija la mesada --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.numero', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija la mesada --',
                    'auto_initialize' => true)
            )
            ->add('cantidadBandejas', IntegerType::class, array(
                    'required' => true,
                    'mapped' => true,
                    'label' => 'Cantidad Bandejas',
                    'attr' => array(
                        'class' => 'form-control cantBandejas',
                        'min' => 1,
                        'tabindex' => '5'))
            )

        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Mesada::class,
        ]);
    }

}
