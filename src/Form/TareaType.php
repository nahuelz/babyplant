<?php

namespace App\Form;

use App\Entity\Tarea;
use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TareaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fechaProgramada', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Fecha programada',
                'attr' => [
                    'class' => 'form-control datepicker',
                    'placeholder' => 'Seleccione una fecha',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('titulo', TextType::class, [
                'required' => true,
                'label' => 'Título',
                'constraints' => [
                    new NotBlank(['message' => 'El título no puede estar vacío.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese el título de la tarea',
                ],
            ])
            ->add('descripcion', TextareaType::class, [
                'required' => false,
                'label' => 'Descripción',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Ingrese la descripción de la tarea',
                ],
            ])
            ->add('empleado', EntityType::class, [
                'class' => Usuario::class,
                'choice_label' => function (Usuario $u) {
                    return $u->getNombreCompleto() ?: $u->getUsername();
                },
                'label' => 'Asignar a empleado',
                'placeholder' => 'Sin asignar',
                'required' => false,
                'query_builder' => function (UsuarioRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('IDENTITY(u.tipoUsuario) = :tipoEmpleadoId')
                        ->andWhere('u.habilitado = true')
                        ->setParameter('tipoEmpleadoId', 2);
                },
            ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Guardar',
            'attr' => ['class' => 'btn btn-light-primary font-weight-bold submit-button'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tarea::class,
        ]);
    }
}
