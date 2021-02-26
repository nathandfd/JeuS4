<?php

namespace App\Repository;

use App\Entity\Gal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Gal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Gal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Gal[]    findAll()
 * @method Gal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gal::class);
    }

    // /**
    //  * @return Gal[] Returns an array of Gal objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Gal
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
