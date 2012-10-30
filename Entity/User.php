<?php

namespace AW\Bundle\FacebookAuthBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AW\Bundle\FacebookAuthBundle\Entity\User
 *
 * @ORM\Table(name="awfacebookauth_user")
 * @ORM\Entity(repositoryClass="AW\Bundle\FacebookAuthBundle\Entity\UserRepository")
 */
class User
{
    /**
     * @var integer $id Facebook ID.
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string $name
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string $allDataSerialized
     * @ORM\Column(name="all_data_serialized", type="text")
     */
    private $allDataSerialized;

    public function __construct(\stdClass $data)
    {
        $this->id = $data->id;
        $this->updateWithNewData($data);
    }

    public function updateWithNewData(\stdClass $data)
    {
        if ($this->id != $data->id) {
            throw new \AW\Bundle\FacebookAuthBundle\Exception('IDs don\'t match');
        }

        $this->name = $data->name;
        $this->allDataSerialized = serialize($data);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAllDataSerialized()
    {
        return $this->allDataSerialized;
    }
}
