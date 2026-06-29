<?php

namespace App\Form;

use App\Entity\Devolucion;
use App\Entity\PedidoProducto;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DevolucionType extends AbstractType {

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('cliente', EntityType::class, array(
                'class' => Usuario::class,
                'required' => true,
                'mapped' => false,
                'label' => 'Cliente',
                'placeholder' => '-- Elija el cliente --',
                'attr' => array(
                    'class' => 'form-control choice',
                    'data-placeholder' => '-- Elija el cliente --',
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
            ))
            ->add('precioUnitario', NumberType::class, array(
                'required' => true,
                'label' => 'Precio Unitario',
                'scale' => 2,
                'html5' => true,
                'attr' => array(
                    'placeholder' => 'Escriba el precio por bandeja (ej: 150,00)',
                    'min' => 0,
                    'step' => 0.01,
                    'class' => 'form-control',
                    'inputmode' => 'decimal',
                    'pattern' => '[0-9]+([,][0-9]+)?'
                )
            ))
            ->add('fechaDevolucion', DateType::class, array(
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'model_timezone' => 'America/Argentina/Buenos_Aires',
                'required' => true,
                'label' => 'Fecha de Devolución',
                'data' => new \DateTime(),
                'attr' => array(
                    'placeholder' => 'Fecha de Devolución',
                    'class' => 'form-control datepicker',
                    'tabindex' => '5'
                )
            ))
            ->add('observacion', TextareaType::class, array(
                'required' => false,
                'label' => 'Observación / Motivo',
                'attr' => array(
                    'placeholder' => 'Escriba el motivo de la devolución',
                    'class' => 'form-control',
                    'rows' => 3,
                    'tabindex' => '5'
                )
            ))
        ;

        // El select de productos se llena dinámicamente por AJAX según el cliente.
        // No cargamos todos los PedidoProducto (evita el N+1 del __toString).
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $devolucion = $event->getData();
            $pedidoProducto = $devolucion instanceof Devolucion ? $devolucion->getPedidoProducto() : null;
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
            'class' => PedidoProducto::class,
            'required' => true,
            'label' => 'Producto a devolver',
            'placeholder' => '-- Elija el producto --',
            'attr' => array(
                'class' => 'form-control choice',
                'data-placeholder' => '-- Elija el producto --',
                'tabindex' => '5'
            ),
            'choices' => $pedidoProducto ? [$pedidoProducto] : [],
            'label_attr' => array('class' => 'control-label'),
            'auto_initialize' => false
        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Devolucion::class,
        ]);
    }
}
