<?php

namespace App\Repository;

use App\Entity\RoomMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoomMember>
 *
 * @method RoomMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoomMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoomMember[]    findAll()
 * @method RoomMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomMember::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(RoomMember $entity, bool $flush = true): void
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
    public function remove(RoomMember $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
