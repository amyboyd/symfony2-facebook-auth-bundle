<?php

namespace AW\Bundle\FacebookAuthBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AW\Bundle\FacebookAuthBundle\Service;
use AW\Bundle\FacebookAuthBundle\Exception;

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
     * @ORM\Column(name="friend_count", type="integer")
     */
    private $friendCount;

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
        $this->friendCount = 0;
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

    /**
     * 5-character locale, like "en_GB".
     */
    public function getLocale()
    {
        return $this->locale;
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

    public function getFriendCount()
    {
        return $this->friendCount;
    }

    /**
     * Update the object's friend count (doesn't automatically persist).
     * @param \AW\Bundle\FacebookAuthBundle\Service $service
     * @param $autoPersistAndFlush Whether or not to auto-persist the object.
     * @return int Number of friends.
     */
    public function updateFriendCount(Service $service, $autoPersistAndFlush = false)
    {
        if (!$this->isAuthorized) {
            throw new Exception('User is not authorized on Facebook');
        }

        // The number of friends a user has isn't available in Graph's Person object.
        // Instead, the number can be retrieved through an FQL query.
        $fql = 'SELECT friend_count FROM user WHERE uid = me()';
        $response = $service->makeGraphApiRequest(sprintf(
            'fql?q=%s&access_token=%s',
            urlencode($fql),
            $this->token));
        $response = json_decode($response, true);

        $this->friendCount = (int) @$response['data'][0]['friend_count'];

        if ($autoPersistAndFlush) {
            $em = $service->getEntityManager();
            $em->flush($this);
        }

        return $this->friendCount;
    }
}
