<?php

namespace App\Repository;

use App\Entity\Externalids;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Externalids>
 *
 * @method Externalids|null find($id, $lockMode = null, $lockVersion = null)
 * @method Externalids|null findOneBy(array $criteria, array $orderBy = null)
 * @method Externalids[]    findAll()
 * @method Externalids[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalidsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Externalids::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Externalids $entity, bool $flush = true): void
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
    public function remove(Externalids $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Get the External ids for a user.
     *
     * @param string $userID
     * @param string $serverID
     * @return float|int|mixed|string
     */
    public function getUserExternalids(string $serverID, string $userID)
    {
        return $this->createQueryBuilder('t')
                ->andWhere('t.userid = :userid')
                ->setParameter('userid', $userID)
                ->andWhere('t.serverid = :serverid')
                ->setParameter('serverid', $serverID)
                ->select('t.auth_provider', 't.external_id')
                ->getQuery()
                ->getResult();
    }

    // /**
    //  * @return Externalids[] Returns an array of Externalids objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Externalids
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
