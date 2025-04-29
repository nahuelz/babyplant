<?php

namespace App\Form;


use App\Entity\PedidoProducto;
use App\Entity\Reserva;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservaType extends AbstractType {

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
            ->add('clienteOrigen', EntityType::class, array(
                'class' => Usuario::class,
                'required' => true,
                'mapped' => false,
                'label' => 'CLIENTE ORIGEN',
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
            ->add('cliente', EntityType::class, array(
                'class' => Usuario::class,
                'required' => true,
                'label' => 'CLIENTE RESERVA',
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
            ->add('pedidoProducto', EntityType::class, array(
                    'label' => 'Producto',
                    'class' => pedidoProducto::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija el pedido producto --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el pedido producto --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->orderBy('x.id', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija el pedido producto --',
                    'auto_initialize' => false)
            )
            ->add('cantidadBandejas', IntegerType::class, array(
                    'required' => true,
                    'label' => 'Cantidad de Bandejas',
                    'attr' => array(
                        'placeholder' => 'Escriba la cantidad de bandejas',
                        'min' => 1,
                        'class' => 'form-control')
                )
            )
            ->add('fechaEntregaEstimada', DateType::class, array(
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy',
                    'model_timezone' => 'America/Argentina/Buenos_Aires',
                    'required' => true,
                    'label' => 'Fecha de Entrega Estimada',
                    'attr' => array(
                        'placeholder' => 'Fecha de Entrega Estimada',
                        'class' => 'form-control datepicker',
                        'data-date-start-date' => "+0d",
                        'tabindex' => '5'
                    ))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Reserva::class,
        ]);
    }

}
