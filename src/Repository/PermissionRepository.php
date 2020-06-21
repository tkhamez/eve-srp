<?php

declare(strict_types=1);

namespace EveSrp\Repository;

use EveSrp\Model\Permission;
use Doctrine\ORM\EntityRepository;

/**
 * @method Permission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Permission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Permission[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermissionRepository extends EntityRepository
{
}
