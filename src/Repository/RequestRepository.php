<?php

declare(strict_types=1);

namespace EveSrp\Repository;

use Doctrine\ORM\QueryBuilder;
use EveSrp\Model\Request;
use Doctrine\ORM\EntityRepository;

/**
 * @method Request|null find($id, $lockMode = null, $lockVersion = null)
 * @method Request|null findOneBy(array $criteria, array $orderBy = null)
 * @method Request[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequestRepository extends EntityRepository
{
    /**
     * @return Request[]
     */
    public function findByCriteria(array $criteria, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.created', 'DESC')
            ->setMaxResults($limit) // don't use with JOIN
            ->setFirstResult($offset);
        $this->addCriteria($qb, $criteria);

        return $qb->getQuery()->getResult();
    }

    public function countByCriteria(array $criteria): ?int
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('COUNT(r.id)');
        $this->addCriteria($qb, $criteria);

        $r = $qb->getQuery()->getResult();

        return isset($r[0][1]) ? (int)$r[0][1] : null;
    }

    private function addCriteria(QueryBuilder $qb, array $criteria): void
    {
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $expr = $qb->expr()->in("r.$field", ":$field");
            } elseif (is_null($value)) {
                $expr = $qb->expr()->isNull("r.$field");
            } elseif (str_ends_with((string)$value, '%')) {
                $expr = $qb->expr()->like("r.$field", ":$field");
            } else {
                $expr = $qb->expr()->eq("r.$field", ":$field");
            }
            $qb->andWhere($expr);
            if (!is_null($value)) {
                $qb->setParameter($field, $value);
            }
        }
    }
}
