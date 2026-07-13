<?php

namespace App\Form;


use App\Entity\PedidoProducto;
use App\Entity\Reserva;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
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
            ->add('origen_cliente', EntityType::class, array(
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
            ->add('cantidadBandejas', NumberType::class, array(
                    'required' => true,
                    'label' => 'Cantidad de Bandejas',
                    'scale' => 1,
                    'html5' => true,
                    'attr' => array(
                        'placeholder' => 'Escriba la cantidad de bandejas (ej: 0,5)',
                        'min' => 0.5,
                        'step' => 0.1,
                        'class' => 'form-control',
                        'inputmode' => 'decimal',
                        'pattern' => '[0-9]+([,][0-9]+)?'
                    )
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
            ->add('porFalla', CheckboxType::class, array(
                'required' => false,
                'label' => 'Reserva por falla',
                'attr' => array(
                    'class' => 'form-check-input',
                    'tabindex' => '5'
                )
            ))
            ->add('observacion', TextareaType::class, array(
                'required' => false,
                'label' => 'Observación',
                'attr' => array(
                    'placeholder' => 'Escriba una observación',
                    'class' => 'form-control',
                    'rows' => 3,
                    'tabindex' => '5'
                )
            ))
        ;

        // El select de productos se llena dinámicamente por AJAX según el cliente.
        // No cargamos todos los PedidoProducto (evita el N+1 del __toString).
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $reserva = $event->getData();
            $pedidoProducto = $reserva instanceof Reserva ? $reserva->getPedidoProducto() : null;
            $pedidoConFalla = $reserva instanceof Reserva ? $reserva->getPedidoConFalla() : null;
            $this->addPedidoProductoField($event->getForm(), $pedidoProducto);
            $this->addPedidoConFallaField($event->getForm(), $pedidoConFalla);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $id = $data['pedidoProducto'] ?? null;
            $pedidoProducto = $id
                ? $this->entityManager->getRepository(PedidoProducto::class)->find($id)
                : null;
            $idFalla = $data['pedidoConFalla'] ?? null;
            $pedidoConFalla = $idFalla
                ? $this->entityManager->getRepository(PedidoProducto::class)->find($idFalla)
                : null;
            $this->addPedidoProductoField($event->getForm(), $pedidoProducto);
            $this->addPedidoConFallaField($event->getForm(), $pedidoConFalla);
        });
    }

    /**
     * Agrega el campo pedidoProducto limitando las choices a la opción seleccionada
     * (o ninguna en el alta). El resto de opciones las provee el JS por AJAX.
     */
    private function addPedidoProductoField(FormInterface $form, ?PedidoProducto $pedidoProducto): void
    {
        $form->add('pedidoProducto', EntityType::class, array(
            'label' => 'Producto',
            'class' => PedidoProducto::class,
            'required' => true,
            'attr' => array(
                'placeholder' => '-- Elija el pedido producto --',
                'class' => 'form-control choice',
                'data-placeholder' => '-- Elija el pedido producto --',
                'tabindex' => '5'
            ),
            'choices' => $pedidoProducto ? [$pedidoProducto] : [],
            'label_attr' => array('class' => 'control-label'),
            'placeholder' => '-- Elija el pedido producto --',
            'auto_initialize' => false
        ));
    }

    /**
     * Agrega el campo pedidoConFalla limitando las choices a la opción seleccionada
     * (o ninguna en el alta). El resto de opciones las provee el JS por AJAX.
     */
    private function addPedidoConFallaField(FormInterface $form, ?PedidoProducto $pedidoConFalla): void
    {
        $form->add('pedidoConFalla', EntityType::class, array(
            'label' => 'Producto con falla',
            'class' => PedidoProducto::class,
            'required' => false,
            'attr' => array(
                'placeholder' => '-- Elija el pedido con falla --',
                'class' => 'form-control choice',
                'data-placeholder' => '-- Elija el pedido con falla --',
                'tabindex' => '5'
            ),
            'choices' => $pedidoConFalla ? [$pedidoConFalla] : [],
            'label_attr' => array('class' => 'control-label'),
            'placeholder' => '-- Elija el pedido con falla --',
            'auto_initialize' => false
        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Reserva::class,
        ]);
    }

}
