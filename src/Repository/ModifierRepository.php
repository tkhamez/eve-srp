<?php

declare(strict_types=1);

namespace EveSrp\Repository;

use Doctrine\ORM\EntityRepository;
use EveSrp\Model\Modifier;

/**
 * @method Modifier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Modifier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Modifier[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModifierRepository extends EntityRepository
{
}
