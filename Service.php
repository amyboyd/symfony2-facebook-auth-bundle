<?php

namespace AW\Bundle\FacebookAuthBundle;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;

class Service
{
    private $entityManager;
    private $session;

    public function __construct(EntityManager $entityManager, Session $session)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
    }

    /**
     * @return AW\Bundle\FacebookAuthBundle\Entity\User or null
     */
    public function getUserFromSession()
    {
        $idInSession = $this->session->get('aw_facebook_id');
        if (!$idInSession) {
            return null;
        }

        return $this->entityManager
            ->getRepository('AWFacebookAuthBundle:User')
            ->find($idInSession);
    }
}
