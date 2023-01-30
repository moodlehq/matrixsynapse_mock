<?php

namespace App\Repository;

use App\Entity\Tokens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tokens>
 *
 * @method Tokens|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tokens|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tokens[]    findAll()
 * @method Tokens[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tokens::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Tokens $entity, bool $flush = true): void
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
    public function remove(Tokens $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
