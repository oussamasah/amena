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
           ->andWhere('p.create_date <= :fin')
           ->setParameter('fin', $fin)
           ->orderBy('p.validated_at', 'DESC')

           ->getQuery()
            ->getResult()
       ;
   }

public function getWithStateforAdmin($state,$id,$debut,$fin): ?array
   {
       return $this->createQueryBuilder('p')
       ->join('p.expeditor', 'u')
           ->andWhere('p.state = :val')
           ->setParameter('val', $state)
           ->andWhere('u.account = :a')
           ->setParameter('a', $id)
           ->andWhere('p.create_date >= :debut')
           ->setParameter('debut', $debut)
           ->andWhere('p.create_date <= :fin')
           ->setParameter('fin', $fin)
           ->orderBy('p.validated_at', 'DESC')

           ->getQuery()
            ->getResult()
       ;
   }

public function findAllForAdmin($id): ?array
   {
    return $this->createQueryBuilder("p")
       ->join('p.expeditor', 'u')
           ->andWhere('p.state != :val')
           ->setParameter('val', "waiting")
           ->andWhere('u.account = :a')
           ->setParameter('a', $id)
           ->orderBy('p.validated_at', 'DESC')
           ->getQuery()
            ->getResult(); 

       
   }

public function findAllForDelivery($id): ?array
   {
       return $this->createQueryBuilder('p')
           ->andWhere('p.delivery = :id')
           ->setParameter('id', $id)
           ->orderBy('p.validated_at', 'DESC')

           ->getQuery()
            ->getResult();
   }
   
public function findAllForExpeditor($id): ?array
{
    return $this->createQueryBuilder('p')
        ->andWhere('p.expeditor = :id')
        ->setParameter('id', $id)
        ->orderBy('p.validated_at', 'DESC')

        ->getQuery()
         ->getResult();
}   
public function findAllForExpeditorInvoice($id): ?array
{
    return $this->createQueryBuilder('p')
        ->andWhere('p.expeditor = :id')
        ->setParameter('id', $id)
         ->andWhere('p.facture is null')
   
        ->andWhere($this->createQueryBuilder('p')->expr()->orX(
            $this->createQueryBuilder('p')->expr()->eq('p.state', ':delivered'),
            $this->createQueryBuilder('p')->expr()->eq('p.state', ':returned')
        ))
        ->setParameter('delivered', 'delivered')
        ->setParameter('returned', 'returned')
        ->orderBy('p.validated_at', 'DESC')

        ->getQuery()
         ->getResult();
}

public function findPackagesCountByExpeditorAndDateRange(int $expeditorId,  $startDate,  $endDate):array
{
   
    $qb = $this->createQueryBuilder('p')
        
        ->where('p.expeditor = :expeditorId')
         ->andWhere('p.validated_at >= :startDate')
        ->andWhere('p.validated_at <= :endDate') 
       // ->groupBy('u.id')
        ->setParameter('expeditorId', $expeditorId)
       ->setParameter('startDate', $startDate." 00:00:00")
        ->setParameter('endDate', $endDate." 23:59:59")
;
    return $qb->getQuery()->getResult();
}

public function findPackagesCountByDeliveryAndDateRange(int $deliveryId, \DateTimeInterface $startDate, \DateTimeInterface $endDate):array
{
   
    $qb = $this->createQueryBuilder('p')
        
        ->where('p.delivery = :deliveryId')
         ->andWhere('p.validated_at >= :startDate')
        ->andWhere('p.validated_at <= :endDate') 
       // ->groupBy('u.id')
        ->setParameter('deliveryId', $deliveryId)
       ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
;
    return $qb->getQuery()->getResult();
}


}