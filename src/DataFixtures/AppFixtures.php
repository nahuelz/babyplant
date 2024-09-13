<?php

namespace App\DataFixtures;

use App\Entity\EstadoPedido;
use App\Entity\EstadoPedidoProducto;
use App\Entity\Grupo;
use App\Entity\TipoUsuario;
use App\Entity\Usuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

     public function __construct(UserPasswordEncoderInterface $passwordEncoder)
     {
         $this->passwordEncoder = $passwordEncoder;
     }

    public function load(ObjectManager $manager): void
    {
        $estadoPedidoProducto = new EstadoPedidoProducto();
        $estadoPedidoProducto->setCodigoInterno(1);
        $estadoPedidoProducto->setNombre('PENDIENTE');
        $manager->persist($estadoPedidoProducto);

        $estadoPedido = new EstadoPedido();
        $estadoPedido->setCodigoInterno(1);
        $estadoPedido->setNombre('NUEVO');
        $manager->persist($estadoPedido);

        $grupo = new Grupo();
        $grupo->setNombre("Administrador");
        $grupo->addRole("ROLE_USER");
        $grupo->addRole("ROLE_ALL");
        $grupo->setDescripcion("Grupo para administrador");
        $manager->persist($grupo);

        $tipoUsuario = new TipoUsuario();
        $tipoUsuario->setHabilitado(1);
        $tipoUsuario->setCodigoInterno(2);
        $tipoUsuario->setNombre('Cliente');
        $manager->persist($tipoUsuario);

        $tipoUsuario2 = new TipoUsuario();
        $tipoUsuario2->setHabilitado(1);
        $tipoUsuario2->setCodigoInterno(2);
        $tipoUsuario2->setNombre('Tecnico');
        $manager->persist($tipoUsuario2);

        $user = new Usuario();
        $user->setHabilitado(1);
        $user->setNombre('admin');
        $user->setApellido('admin');
        $user->setUsername('admin');
        $user->setEmail('admin@admin.com');
        $user->setTieneRazonSocial(0);
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                '123456'
            )
        );
        $manager->persist($user);
        $manager->flush();
    }
}
