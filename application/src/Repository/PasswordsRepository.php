<?php

namespace App\Repository;

use App\Entity\Passwords;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Passwords>
 *
 * @method Passwords|null find($id, $lockMode = null, $lockVersion = null)
 * @method Passwords|null findOneBy(array $criteria, array $orderBy = null)
 * @method Passwords[]    findAll()
 * @method Passwords[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasswordsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Passwords::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Passwords $entity, bool $flush = true): void
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
    public function remove(Passwords $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
