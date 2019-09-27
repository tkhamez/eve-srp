<?php

declare(strict_types=1);

namespace Brave\EveSrp\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="permissions")
 */
class Permission
{
    /**
     * May submit a request
     */
    public const SUBMIT = 'submit';

    /**
     * May approve a request
     */
    public const REVIEW = 'review';

    /**
     * May payout the ISK.
     */
    public const PAY = 'pay';

    /**
     * Can change permission etc.
     */
    public const ADMIN = 'admin';
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var integer
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Division")
     * @var Division
     */
    private $division;

    /**
     * @ORM\ManyToOne(targetEntity="ExternalGroup")
     * @ORM\JoinColumn(name="external_group_id")
     * @var ExternalGroup
     */
    private $externalGroup;

    /**
     * @var string
     * @ORM\Column(type="string", length=8)
     */
    private $permission = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getDivision(): Division
    {
        return $this->division;
    }

    public function getExternalGroup(): ExternalGroup
    {
        return $this->externalGroup;
    }

    public function getPermission(): string
    {
        return $this->permission;
    }
}
