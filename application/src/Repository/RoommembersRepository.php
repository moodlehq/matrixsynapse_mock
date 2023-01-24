<?php

namespace App\Repository;

use App\Entity\Roommembers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Roommembers>
 *
 * @method Roommembers|null find($id, $lockMode = null, $lockVersion = null)
 * @method Roommembers|null findOneBy(array $criteria, array $orderBy = null)
 * @method Roommembers[]    findAll()
 * @method Roommembers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoommembersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Roommembers::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Roommembers $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Roommembers $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
