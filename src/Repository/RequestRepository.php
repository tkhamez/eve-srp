<?php

declare(strict_types=1);

namespace EveSrp\Repository;

use EveSrp\Model\Request;
use Doctrine\ORM\EntityRepository;

/**
 * @method Request|null find($id, $lockMode = null, $lockVersion = null)
 * @method Request|null findOneBy(array $criteria, array $orderBy = null)
 * @method Request[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequestRepository extends EntityRepository
{
}
