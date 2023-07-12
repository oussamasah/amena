<?php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\Region;
use App\Entity\City;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $account = new Account();
        $account->setId(1);
        $account->setName("TunisiaLivreurs");
        $account->setCreatedAt(new \DateTime('now'));
        $manager->persist($account);


        $r1 = new Region();
        $r1->setLabel("Nabeul");
        $r1->setName("name");
        $manager->persist($r1);

        $r2 = new Region();
        $r2->setLabel("Tunis");
        $r2->setName("tunis");
        $manager->persist($r2);


        $c1 = new City();
        $c1->setLabel("Grombalia");
        $c1->setName("grombalia");
        $c1->setRegion($r1);
        $manager->persist($c1);

        $c2 = new City();
        $c2->setLabel("Alain savary");
        $c2->setName("alain_savary");
        $c1->setRegion($r2);
        $manager->persist($c2);

        $user = new User();
        $user->setAccount($account);
        $user->setEmail("admin@gmail.com");
        $user->setPassword('$2y$13$gjX1WQlrAb5vTt0cPGqLfu/atwHnZ8TmT53LXu0GTLIJqk6voKLUS');
        $user->setName("Admin");
        $user->setRoles(["ADMIN_ROLE"]);
        $user->setIdentity("06101994");
        $user->setRegion($r1);
        $user->setCity($c1);
        $user->setAdress("06 rue abue kassem chebbi");
        $user->setCode("06101994");
        $manager->persist($user);


        $manager->flush();
    }
}