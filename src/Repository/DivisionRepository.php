<?php

declare(strict_types=1);

namespace EveSrp\Repository;

use EveSrp\Model\Division;
use Doctrine\ORM\EntityRepository;

/**
 * @method Division|null find($id, $lockMode = null, $lockVersion = null)
 * @method Division|null findOneBy(array $criteria, array $orderBy = null)
 * @method Division[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DivisionRepository extends EntityRepository
{
}
