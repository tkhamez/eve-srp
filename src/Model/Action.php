<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Brave\EveSrp\Model;

use Brave\EveSrp\Type;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="actions")
 */
class Action
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $created;

    /**
     * Action category/type: one of the Brave\EveSrp\Type constants.
     * 
     * @ORM\Column(type="string", length=16)
     * @var string
     * @see Type
     */
    private $category;
    
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Request", inversedBy="actions")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $request;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     * @var string
     */
    private $note;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRequest(): User
    {
        return $this->request;
    }

    public function getNote(): string
    {
        return $this->note;
    }
}
