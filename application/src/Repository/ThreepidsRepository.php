<?php

namespace App\Repository;

use App\Entity\Threepids;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Threepids>
 *
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

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Threepids $entity, bool $flush = true): void
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
    public function remove(Threepids $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Get the threepids for a user.
     *
     * @param string $userID
     * @param string $serverID
     * @return float|int|mixed|string
     */
    public function getUserThreepids(string $serverID, string $userID)
    {
        return $this->createQueryBuilder('t')
                ->andWhere('t.userid = :userid')
                ->setParameter('userid', $userID)
                ->andWhere('t.serverid = :serverid')
                ->setParameter('serverid', $serverID)
                ->select('t.medium', 't.address')
                ->getQuery()
                ->getResult();
    }
}
