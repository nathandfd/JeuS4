<?php

namespace App\Repository;

use App\Entity\Friendship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Friendship|null find($id, $lockMode = null, $lockVersion = null)
 * @method Friendship|null findOneBy(array $criteria, array $orderBy = null)
 * @method Friendship[]    findAll()
 * @method Friendship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

     /**
      * @return Friendship[] Returns an array of Friendship objects
      */
    public function findFriendshipOfUser($userId)
    {
        return $this->createQueryBuilder('g')
            ->where($this->createQueryBuilder()->expr()->orX(
                $this->createQueryBuilder()->expr()->eq('g.user1',':val'),
                $this->createQueryBuilder()->expr()->eq('g.user2',':val')
            ))
            ->andWhere('g.accepted = 1')
            ->setParameter('val', $userId)
            ->orderBy('g.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    /*
    public function findOneBySomeField($value): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
