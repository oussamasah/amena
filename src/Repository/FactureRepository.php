<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 *
 * @method Facture|null find($id, $lockMode = null, $lockVersion = null)
 * @method Facture|null findOneBy(array $criteria, array $orderBy = null)
 * @method Facture[]    findAll()
 * @method Facture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

//    /**
//     * @return Facture[] Returns an array of Facture objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Facture
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function findFactureCountByExpeditorAndDateRange(int $expeditorId,  $startDate,  $endDate):array
{
   
    $qb = $this->createQueryBuilder('f')
        
        ->where('f.expeditor = :expeditorId')
         ->andWhere('f.createAt >= :startDate')
        ->andWhere('f.createAt <= :endDate') 
        ->andWhere('f.state = :state') 
       // ->groupBy('u.id')
        ->setParameter('expeditorId', $expeditorId)
       ->setParameter('startDate', $startDate." 00:00:00")
        ->setParameter('endDate', $endDate." 23:59:59")
        ->setParameter('state', "done")
;
/* if($expeditorId == 4){
    $query=$qb->getQuery();
    // SHOW SQL: 
    
    dd($query->getSQL(),$query->getParameters());
}
 */
    return $qb->getQuery()->getResult();
}

public function findFactureByExpeditor(int $expeditorId):array
{
   
    $qb = $this->createQueryBuilder('f')
        
        ->where('f.expeditor = :expeditorId')

       // ->groupBy('u.id')
        ->setParameter('expeditorId', $expeditorId)
       
;
    return $qb->getQuery()->getResult();
}
public function findFactureByAdmin(int $id):array
{
   
    $qb = $this->createQueryBuilder('f')
        ->join('f.expeditor', 'u')
        ->andWhere('u.account = :a')
        ->setParameter('a', $id)
       
;
    return $qb->getQuery()->getResult();
}


}
