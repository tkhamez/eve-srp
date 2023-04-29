<?php

declare(strict_types=1);

namespace EveSrp\Repository;

use EveSrp\Model\User;
use Doctrine\ORM\EntityRepository;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends EntityRepository
{
    /**
     * @return User[]
     */
    public function findByName(string $search): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->where($qb->expr()->like('u.name', ':search'))
            ->setParameter('search', $search);

        return $qb->getQuery()->getResult();
    }
}
