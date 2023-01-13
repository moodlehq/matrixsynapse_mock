<?php

namespace App\Repository;

use App\Entity\Threepids;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Threepids|null find($id, $lockMode = null, $lockVersion = null)
 * @method Threepids|null findOneBy(array $criteria, array $orderBy = null)
 * @method Threepids[]    findAll()
 * @method Threepids[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThreepidsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Threepids::class);
    }
}
