<?php

namespace App\Form;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class TareaAsignarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('empleado', EntityType::class, [
            'class' => Usuario::class,
            'choice_label' => function (Usuario $u) {
                return $u->getNombreCompleto() ?: $u->getUsername();
            },
            'label' => 'Empleado',
            'placeholder' => 'Sin asignar',
            'required' => false,
            'query_builder' => function (UsuarioRepository $er) {
                return $er->createQueryBuilder('u')
                    ->where('IDENTITY(u.tipoUsuario) = :tipoEmpleadoId')
                    ->andWhere('u.habilitado = true')
                    ->setParameter('tipoEmpleadoId', 2);
            },
        ])
        ->add('submit', SubmitType::class, [
            'label' => 'Asignar',
            'attr' => ['class' => 'btn btn-light-primary font-weight-bold submit-button'],
        ]);
    }
}
