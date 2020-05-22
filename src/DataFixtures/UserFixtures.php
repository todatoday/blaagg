<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    //récupération du service d'encodage de mot de passe de symfony
    private $encoder;
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        for ($i=1; $i <= 15 ; $i++) { 
            $user = new User();
            $user->setUsername('test'.$i);
            $user->setAvatar('https://via.placeholder.com/150');
            $user->setSignUpDate(new \DateTime());
              //on appelle l'encodeur pour chiffrer notre mot de passe
            //pour l'utilisateur présent
            $password = $this->encoder->encodePassword($user, 'mdp' .$i);
            $user->setPassword($password);
             //ajoute une reference utilisable par d'autres fixtures
            $this->addReference('user' .$i, $user);
             $manager->persist($user);
        }
      $manager->flush();
    }

}

