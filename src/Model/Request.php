<?php

declare(strict_types=1);

namespace Brave\EveSrp\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="requests")
 */
class Request
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var integer
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Character", inversedBy="requests")
     * @ORM\JoinColumn(nullable=false)
     * @var Character
     */
    private $character;
}
