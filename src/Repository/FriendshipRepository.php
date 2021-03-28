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
        $qb = $this->createQueryBuilder('g');

        return $qb->where($qb->expr()->orX(
                $qb->expr()->eq('g.user1',':val'),
                $qb->expr()->eq('g.user2',':val')
            ))
            ->andWhere('g.accepted = 1')
            ->setParameter('val', $userId)
            ->orderBy('g.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    public function getSendedRequests($userId)
    {
        $qb = $this->createQueryBuilder('g');

        return $qb->where(
            $qb->expr()->eq('g.user1',':val')
         )
            ->andWhere('g.accepted = 0')
            ->setParameter('val', $userId)
            ->orderBy('g.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    public function getReceivedRequests($userId)
    {
        $qb = $this->createQueryBuilder('g');

        return $qb->where(
            $qb->expr()->eq('g.user2',':val')
        )
            ->andWhere('g.accepted = 0')
            ->setParameter('val', $userId)
            ->orderBy('g.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    public function isRequestWaiting($user1Id, $user2Id)
    {
        $qb = $this->createQueryBuilder('g');

        $result = $qb->where($qb->expr()->eq('g.user1',':user2'))
            ->andWhere($qb->expr()->orX($qb->expr()->eq('g.user2',':user1')))
            ->andWhere('g.accepted = 0')
            ->setParameter('user1', $user1Id)
            ->setParameter('user2', $user2Id)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    public function isRequestSended($user1Id, $user2Id)
    {
        $qb = $this->createQueryBuilder('g');

        $result = $qb->where($qb->expr()->eq('g.user1',':user1'))
            ->andWhere($qb->expr()->orX($qb->expr()->eq('g.user2',':user2')))
            ->andWhere('g.accepted = 0')
            ->setParameter('user1', $user1Id)
            ->setParameter('user2', $user2Id)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }


    public function isAlreadyFriend($user1Id, $user2Id){
        $qb = $this->createQueryBuilder('g');

        $result = $qb->where($qb->expr()->orX(
            $qb->expr()->eq('g.user1',':user1'),
            $qb->expr()->eq('g.user2',':user1')
            ))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('g.user1',':user2'),
                $qb->expr()->eq('g.user2',':user2')
            ))
            ->setParameter('user1', $user1Id)
            ->setParameter('user2', $user2Id)
            ->getQuery()
            ->getResult()
            ;

        return ($result)?1:0;
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
