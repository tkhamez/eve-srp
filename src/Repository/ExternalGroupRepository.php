<?php

declare(strict_types=1);

namespace Brave\EveSrp\Repository;

use Brave\EveSrp\Model\ExternalGroup;
use Doctrine\ORM\EntityRepository;

/**
 * @method ExternalGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExternalGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalGroup[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalGroupRepository extends EntityRepository
{
}
