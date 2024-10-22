<?php

namespace App\Repository;

use App\Entity\Locatie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 * @method Locatie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Locatie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Locatie[]    findAll()
 * @method Locatie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocatieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Locatie::class);
    }

    public function findOneByusername_($username_)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.name = :name')
            ->setParameter('name', $username_)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Locatie[] Returns an array of Locatie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Locatie
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
