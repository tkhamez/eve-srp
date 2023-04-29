<?php

declare(strict_types=1);

namespace EveSrp\Repository;

use EveSrp\Model\Character;
use Doctrine\ORM\EntityRepository;

/**
 * @method Character|null find($id, $lockMode = null, $lockVersion = null)
 * @method Character|null findOneBy(array $criteria, array $orderBy = null)
 * @method Character[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CharacterRepository extends EntityRepository
{
    /**
     * @return Character[]
     */
    public function findByName(string $search): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where($qb->expr()->like('c.name', ':search'))
            ->setParameter('search', $search);

        return $qb->getQuery()->getResult();
    }
}
