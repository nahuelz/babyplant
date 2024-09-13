<?php

namespace App\DataFixtures;

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
        $user = new Usuario();
        $user->setHabilitado(1);
        $user->setNombre('admin');
        $user->setApellido('admin');
        $user->setEmail('admin@admin.com');
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                '123456'
            )
        );

        $manager->flush();
    }
}
