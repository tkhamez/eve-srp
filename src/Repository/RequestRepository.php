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
    public function findByCriteria(
        array $criteria,
        ?int $limit = null,
        int $offset = 0,
        string $sort = 'created',
        string $order = 'ASC',
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->setFirstResult($offset)
            ->orderBy("r.$sort", $order);
        if ($limit) {
            $qb->setMaxResults($limit); // don't use with JOIN
        }
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

    public function sumPayout(array $criteria): ?int
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('SUM(r.payout)');
        $this->addCriteria($qb, $criteria);

        $r = $qb->getQuery()->getResult();

        return isset($r[0]) ? (int)$r[0][1] : null;
    }

    public function getCurrentMonthPayoutsByDivisionForReviewer(int $requestOwnerId, int $divisionId): array
    {
        //This can definately be done properly, but i have no clue how to do it using doctrine and not raw sql
        $conn = $this->getEntityManager()->getConnection();

        $qb = $conn->createQueryBuilder();
        $qb->select('d.name AS name', 'SUM(r.payout) AS payout')
            ->from('requests', 'r')
            ->join('r', 'divisions', 'd', 'd.id = r.division_id')
            ->where('r.user_id = :requestOwnerId')
            ->andWhere('r.division_id = :divisionId')
            ->andWhere($qb->expr()->in('r.status', [':statusPaid', ':statusApproved']))
            ->andWhere('EXTRACT(MONTH FROM r.kill_time) = EXTRACT(MONTH FROM CURRENT_DATE)')
            ->andWhere('EXTRACT(YEAR FROM r.kill_time) = EXTRACT(YEAR FROM CURRENT_DATE)')
            ->groupBy('d.name')
            ->setParameter('requestOwnerId', $requestOwnerId)
            ->setParameter('divisionId', $divisionId)
            ->setParameter('statusPaid', 'paid')
            ->setParameter('statusApproved', 'approved');

        return $qb->executeQuery()->fetchAllAssociative();
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
