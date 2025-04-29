<?php

namespace App\Form;


use App\Entity\Entrega;
use App\Entity\Remito;
use App\Entity\TipoBandeja;
use App\Entity\TipoDescuento;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemitoType extends AbstractType {

    private $entityManager;

    /**
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('entrega', RemitoEntregaType::class, array(
                'required' => false,
                'mapped' => false,
                'data_class' => 'App\Entity\Entrega',
            ))
            ->add('entregas', CollectionType::class, array(
                    'required' => false,
                    'mapped' => true,
                    'entry_type' => RemitoEntregaType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'label' => '',
                    'prototype_name' => '__entregas__',
                    'label_attr' => array('class' => 'hidden'),
                    'attr' => array('class' => 'hidden'))
            )
            ->add('cliente', EntityType::class, array(
                'class' => Usuario::class,
                'required' => true,
                'label' => 'CLIENTE',
                'placeholder' => '-- Elija --',
                'attr' => array(
                    'class' => 'form-control choice',
                    'data-placeholder' => '-- Elija --',
                    'tabindex' => '5'
                ),
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('x')
                        ->where('x.tipoUsuario = 1')
                        ->andWhere('x.habilitado = 1')
                        ->orderBy('x.apellido', 'ASC');
                },
            ))
            ->add(
                'tipoDescuento',
                EntityType::class,
                array(
                    'class' => TipoDescuento::class,
                    'required' => false,
                    'placeholder' => 'SIN DESCUENTO',
                    'attr' => array(
                        'class' => 'form-control choice',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                )
            )
            ->add('cantidadDescuento', TextType::class, array(
                    'required' => false,
                    'attr' => array(
                        'placeholder' => 'Cantidad',
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Remito::class,
        ]);
    }

}
