<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Media $entity, bool $flush = true): void
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
    public function remove(Media $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Get the Medias for a user.
     *
     * @param string $userID
     * @param string $serverID
     * @return float|int|mixed|string
     */
    public function getUserMedias(string $serverID, string $userID)
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
