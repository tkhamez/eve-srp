<?php

declare(strict_types=1);

namespace EveSrp\Repository;

use EveSrp\Model\Action;
use Doctrine\ORM\EntityRepository;

/**
 * @method Action|null find($id, $lockMode = null, $lockVersion = null)
 * @method Action|null findOneBy(array $criteria, array $orderBy = null)
 * @method Action[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionRepository extends EntityRepository
{
}
