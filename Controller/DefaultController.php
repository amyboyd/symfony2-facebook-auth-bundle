<?php

namespace AW\Bundle\FacebookAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AW\Bundle\FacebookAuthBundle\Entity\User;

class DefaultController extends Controller
{
    /**
     * HTTP param: continue: where to redirect to after authenticating.
     */
    public function authAction(Request $request)
    {
        // @todo
    }

    public function callbackAction(Request $request)
    {
        // @todo
    }
}
