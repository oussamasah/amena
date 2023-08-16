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


        $tunisianRegions = [
            [
                'name' => 'Tunis',
                'label' => 'Tunis',
                'cities' => [
                    ['name' => 'Tunis', 'label' => 'Tunis'],
                    ['name' => 'Ariana', 'label' => 'Ariana'],
                    ['name' => 'La Marsa', 'label' => 'La Marsa'],
                    ['name' => 'Carthage', 'label' => 'Carthage'],
                ],
            ],
            [
                'name' => 'Nabeul',
                'label' => 'Nabeul',
                'cities' => [
                    ['name' => 'Nabeul', 'label' => 'Nabeul'],
                    ['name' => 'Hammamet', 'label' => 'Hammamet'],
                    ['name' => 'Kelibia', 'label' => 'Kelibia'],
                    ['name' => 'Dar Chaabane', 'label' => 'Dar Chaabane'],
                ],
            ],
            [
                'name' => 'Sousse',
                'label' => 'Sousse',
                'cities' => [
                    ['name' => 'Sousse', 'label' => 'Sousse'],
                    ['name' => 'Monastir', 'label' => 'Monastir'],
                    ['name' => 'Mahdia', 'label' => 'Mahdia'],
                ],
            ],
            [
                'name' => 'Sfax',
                'label' => 'Sfax',
                'cities' => [
                    ['name' => 'Sfax', 'label' => 'Sfax'],
                    ['name' => 'Gabès', 'label' => 'Gabès'],
                    ['name' => 'El Jem', 'label' => 'El Jem'],
                    ['name' => 'Kerkennah', 'label' => 'Kerkennah'],
                ],
            ],
            // Add more regions with their respective cities
        ];

        foreach ($tunisianRegions as $regionData) {
            $region = new Region();
            $region->setName($regionData['name']);
            $region->setLabel($regionData['label']);
            $manager->persist($region);

            foreach ($regionData['cities'] as $cityData) {
                $city = new City();
                $city->setName($cityData['name']);
                $city->setLabel($cityData['label']);
                $city->setRegion($region);
                $manager->persist($city);
            }
        }

        $user = new User();
        $user->setAccount($account);
        $user->setEmail("admin@gmail.com");
        $user->setPassword('$2y$13$gjX1WQlrAb5vTt0cPGqLfu/atwHnZ8TmT53LXu0GTLIJqk6voKLUS');
        $user->setName("Admin");
        $user->setRoles(["ADMIN_ROLE"]);
        $user->setState(['active']);
        $user->setIdentity("06101994");
        $user->setRegion($manager->getRepository(Region::class)->find(1));
        $user->setCity($manager->getRepository(City::class)->find(1));
        $user->setAdress("06 rue abue kassem chebbi");
    
        $manager->persist($user);


        $manager->flush();
    }
}