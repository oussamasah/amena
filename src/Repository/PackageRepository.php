<?php

namespace App\Repository;

use App\Entity\Package;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Package>
 *
 * @method Package|null find($id, $lockMode = null, $lockVersion = null)
 * @method Package|null findOneBy(array $criteria, array $orderBy = null)
 * @method Package[]    findAll()
 * @method Package[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    public function save(Package $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Package $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Package[] Returns an array of Package objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Package
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

public function getWithState($state,$id,$debut,$fin): ?array
   {
       return $this->createQueryBuilder('p')
           ->andWhere('p.state = :val')
           ->setParameter('val', $state)
           ->andWhere('p.expeditor = :id')
           ->setParameter('id', $id)
           ->andWhere('p.create_date >= :debut')
           ->setParameter('debut', $debut)
           ->andWhere('p.create_date >= :fin')
           ->setParameter('fin', $fin)
           ->getQuery()
            ->getResult()
       ;
   }

public function findAllForAdmin(): ?array
   {
       return $this->createQueryBuilder('p')
           ->andWhere('p.state != :val')
           ->setParameter('val', "waiting")
           ->getQuery()
            ->getResult();
       
   }

public function findAllForDelivery($id): ?array
   {
       return $this->createQueryBuilder('p')
           ->andWhere('p.delivery = :id')
           ->setParameter('id', $id)
           ->getQuery()
            ->getResult();
   }
}