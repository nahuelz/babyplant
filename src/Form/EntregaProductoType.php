<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use App\Entity\EntregaProducto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntregaProductoType extends AbstractType {

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
            ->add('precioUnitario', null, array(
                    'required' => false,
                    'label' => 'PRECIO UNITARIO',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('cantidadBandejas', null, array(
                    'required' => true,
                    'label' => 'CANTIDAD DE BANDEJAS A ENTREGAR',
                    'attr' => array(
                        'placeholder' => 'Escriba la cantidad de bandejas',
                        'min' => 1,
                        'class' => 'form-control')
                )
            )
        ;

        // El select de productos se llena dinámicamente por AJAX según el cliente.
        // No cargamos todos los PedidoProducto (evita el N+1 del __toString).
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $entregaProducto = $event->getData();
            $pedidoProducto = $entregaProducto instanceof EntregaProducto ? $entregaProducto->getPedidoProducto() : null;
            $this->addPedidoProductoField($event->getForm(), $pedidoProducto);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $id = $data['pedidoProducto'] ?? null;
            $pedidoProducto = $id
                ? $this->entityManager->getRepository(PedidoProducto::class)->find($id)
                : null;
            $this->addPedidoProductoField($event->getForm(), $pedidoProducto);
        });
    }

    /**
     * Agrega el campo pedidoProducto limitando las choices a la opción seleccionada
     * (o ninguna en el alta). El resto de opciones las provee el JS por AJAX.
     */
    private function addPedidoProductoField(FormInterface $form, ?PedidoProducto $pedidoProducto): void
    {
        $form->add('pedidoProducto', EntityType::class, array(
            'label' => 'PRODUCTOS DEL CLIENTE EN EL INVERNACULO',
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

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => EntregaProducto::class,
        ]);
    }

}
