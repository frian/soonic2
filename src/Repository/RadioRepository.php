<?php

namespace App\Repository;

use App\Entity\Radio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Radio|null find($id, $lockMode = null, $lockVersion = null)
 * @method Radio|null findOneBy(array $criteria, array $orderBy = null)
 * @method Radio[]    findAll()
 * @method Radio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RadioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Radio::class);
    }

    // /**
    //  * @return Radio[] Returns an array of Radio objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Radio
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
