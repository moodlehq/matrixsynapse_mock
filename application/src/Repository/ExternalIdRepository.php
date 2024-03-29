<?php

namespace App\Repository;

use App\Entity\ExternalId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalId>
 *
 * @method ExternalId|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExternalId|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalId[]    findAll()
 * @method ExternalId[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalIdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalId::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ExternalId $entity, bool $flush = true): void
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
    public function remove(ExternalId $entity, bool $flush = true): void
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
    public function getUserExternalIds(string $serverID, string $userID)
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
}
