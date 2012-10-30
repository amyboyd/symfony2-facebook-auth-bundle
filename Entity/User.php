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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;

    /**
     * @ORM\Column(name="token", type="string")
     */
    private $token;

    /**
     * Unix timestamp.
     * @ORM\Column(name="token_expires", type="integer")
     */
    private $tokenExpires;

    /**
     * This will only be changed to false if the user revokes our app's
     * permissions.
     *
     * @ORM\Column(name="is_authorized", type="boolean")
     */
    private $isAuthorized;

    /**
     * @var string $allDataJson
     * @ORM\Column(name="all_data_json", type="text")
     */
    private $allDataJson;

    public function __construct(\stdClass $data)
    {
        $this->id = $data->id;
        $this->isAuthorized = true;
        $this->updateWithNewData($data);
    }

    public function updateWithNewData(\stdClass $data)
    {
        $this->name = $data->name;
        $this->locale = $data->locale;
        $this->allDataJson = json_encode($data);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAllDataJson()
    {
        return $this->allDataJson;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function setTokenExpires($tokenExpires)
    {
        $this->tokenExpires = $tokenExpires;
    }

    public function setIsAuthorized($isAuthorized)
    {
        $this->isAuthorized = $isAuthorized;
    }
}
