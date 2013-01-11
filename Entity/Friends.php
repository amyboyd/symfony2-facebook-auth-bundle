<?php

namespace AW\Bundle\FacebookAuthBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AW\Bundle\FacebookAuthBundle\Service;
use AW\Bundle\FacebookAuthBundle\Exception;

/**
 * @ORM\Table(name="awfacebookauth_friends")
 * @ORM\Entity()
 */
class Friends
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="\AW\Bundle\FacebookAuthBundle\Entity\User",
     *     cascade={"persist"}, fetch="LAZY")
     * @ORM\JoinColumn(name="user", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * JSON-encoded array.
     * @ORM\Column(name="names_and_ids", type="text")
     */
    private $namesAndIDs;

    /**
     * @ORM\Column(name="count", type="integer")
     */
    private $count;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated", type="datetime")
     */
    private $lastUpdatedAt;

    private $refreshFrequencyInMinutes;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param int $minutes Refresh if data is older than $minutes.
     */
    public function setRefreshFrequency($minutes)
    {
       $this->refreshFrequencyInMinutes = $minutes;
    }

    /**
     * @return array[id, name]
     */
    public function getAll()
    {
        if ($this->isRefreshNeeded()) {
            $this->refresh();
        }
        return json_decode($this->namesAndIDs, true);
    }

    public function getCount()
    {
        if ($this->isRefreshNeeded()) {
            $this->refresh();
        }
        return $this->count;
    }

    private function isRefreshNeeded()
    {
        if ($this->lastUpdatedAt == null) {
            return true;
        }
        elseif ($this->refreshFrequencyInMinutes !== null
            && $this->refreshFrequencyInMinutes !== false
            && (time() > $this->lastUpdatedAt->getTimestamp() + ($this->refreshFrequencyInMinutes * 60))) {
            return true;
        }
        return false;
    }

    private function refresh()
    {
        $response = Service::makeGraphApiRequest(sprintf(
            'me/friends?access_token=%s',
            $this->user->getToken()));
        $response = json_decode($response, true);
        $friends = $response['data'];

        while (isset($response['paging']) && isset($response['paging']['next'])) {
            // Get the next page too.
            $response = Service::makeGraphApiRequest($response['paging']['next']);
            $response = json_decode($response, true);
            $friends = array_merge($friends, $response['data']);
        }

        $this->namesAndIDs = json_encode($friends);
        $this->count = count($friends);
        $this->lastUpdatedAt = new \DateTime();

        // Save.
        global $kernel;
        $doctrine = $kernel->getContainer()->get('doctrine');
        $em = $doctrine->getEntityManager();
        $em->persist($this);
        $em->flush();
    }
}
