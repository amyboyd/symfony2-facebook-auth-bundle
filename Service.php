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
        $idInSession = $this->session->get('aw_facebook_auth_id');
        if (!$idInSession) {
            return null;
        }

        return $this->entityManager
            ->getRepository('AWFacebookAuthBundle:User')
            ->find($idInSession);
    }

    /**
     * @param \AW\Bundle\FacebookAuthBundle\Entity\User $user
     */
    public function setUserInSession(User $user)
    {
        $this->session->set('aw_facebook_auth_id', $user->getId());
    }

    /**
     * @param string $path Everything after 'https://graph.facebook.com/' - e.g. 'me/picture?access_token=...'
     * @throws \AW\Bundle\FacebookAuthBundle\Exception if there was an error making the request.
     */
    public function makeGraphApiRequest($path)
    {
        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, 'https://graph.facebook.com/' . $path);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($request);
        if (!curl_errno($request)) {
        }
        else {
            throw new Exception('Curl error: ' . curl_error($request));
        }

        curl_close($request);
        return $response;
    }
}
