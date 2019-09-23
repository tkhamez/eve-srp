<?php

namespace Brave\EveSrp\Repository;

use Brave\EveSrp\Model\Character;
use Doctrine\ORM\EntityRepository;

/**
 * @method Character|null find($id, $lockMode = null, $lockVersion = null)
 * @method Character|null findOneBy(array $criteria, array $orderBy = null)
 * @method Character[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CharacterRepository extends EntityRepository
{
}
