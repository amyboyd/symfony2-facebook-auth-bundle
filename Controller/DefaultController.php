<?php

namespace AW\Bundle\FacebookAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AW\Bundle\FacebookAuthBundle\Entity\User;

class DefaultController extends Controller
{
    /**
     * HTTP param: continue: where to redirect to after authenticating.
     * Optional HTTP param: code
     * Optional HTTP param: state
     * Optional HTTP param: error
     */
    public function authAction(Request $request)
    {
        $this->requireContinueParam($request);

        $code = $request->get('code');
        if (!empty($code)) {
            // If the request has a 'code' parameter, the user has authorized our app.
            $result = $this->exchangeCodeForAnOauthToken($request);
            if ($result instanceof Response) {
                return $result;
            }
            else {
                // $result is an oauth token and it's expiry time.
                return $this->createOrUpdateUser($request, $result);
            }
        }

        $error = $request->get('error');
        if (!empty($error)) {
            return $this->error($request);
        }

        return $this->sendToLoginPage($request);
    }

    private function sendToLoginPage(Request $request)
    {
        // CSRF protection - create a random state that we can verify later.
        $state = md5(uniqid(rand(), true));
        $request->getSession()->set('aw_facebook_auth_state', $state);

        // Redirect to the Facebook login page.
        $loginUrl = sprintf('https://www.facebook.com/dialog/oauth?client_id=%s&redirect_uri=%s&state=%s&scope=%s',
            $this->container->getParameter('aw_facebook_auth.app_id'),
            urlencode($this->generateUrl('aw_facebook_auth', array('continue' => $request->get('continue')), true)),
            $state,
            $this->container->getParameter('aw_facebook_auth.scope'));
        return new Response("<script> top.location.href='" . $loginUrl . "'</script>");
    }

    private function error(Request $request)
    {
        return $this->render('AWFacebookAuthBundle:Default:fail.html.twig');
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Response|array[access_token,expires]
     */
    private function exchangeCodeForAnOauthToken(Request $request)
    {
        if ($request->getSession()->get('aw_facebook_auth_state') !== $request->get('state')) {
            // Failed CRSF check.
            return $this->render('AWFacebookAuthBundle:Default:fail.html.twig');
        }

        // Exchange the code for an access token.
        $url = sprintf('oauth/access_token?client_id=%s&client_secret=%s&redirect_uri=%s&code=%s',
            $this->container->getParameter('aw_facebook_auth.app_id'),
            $this->container->getParameter('aw_facebook_auth.app_secret'),
            urlencode($this->generateUrl('aw_facebook_auth', array('continue' => $request->get('continue')), true)),
            $request->get('code'));

        $response = \AW\Bundle\FacebookAuthBundle\Service::makeGraphApiRequest($url);
        // $response now looks like:
        // access_token=USER_ACCESS_TOKEN&expires=NUMBER_OF_SECONDS_UNTIL_TOKEN_EXPIRES
        $responseParsed = array();
        parse_str($response, $responseParsed);

        return $responseParsed;
    }

    private function createOrUpdateUser(Request $request, array $tokenAndExpires)
    {
        $token = $tokenAndExpires['access_token'];
        $expires = $tokenAndExpires['expires'];

        $response = \AW\Bundle\FacebookAuthBundle\Service::makeGraphApiRequest('me?access_token=' . $token);
        $response = json_decode($response, false);

        // Create or update the user entity.
        $user = $this->getDoctrine()->getRepository('AWFacebookAuthBundle:User')->find($response->id);
        if ($user) {
            $user->updateWithNewData($response);
        }
        else {
            $user = new User($response);
        }
        $user->setToken($token);
        $user->setTokenExpires($expires);

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($user);
        $em->flush();

        // Persist the user in the session, and redirect back to the continue URL.
        $this->get('aw_facebook_auth')->setUserInSession($user);
        return $this->redirect($request->get('continue'));
    }

    private function requireContinueParam(Request $request)
    {
        if (!$request->get('continue')) {
            throw new \AW\Bundle\FacebookAuthBundle\Exception('No continue parameter in the request query string');
        }
    }

    /**
     * User has de-authorized the app from their app settings page.
     *
     * From Facebook's documentation: "Upon app removal we will send an HTTP POST
     * request containing a single parameter, signed_request, which, once decoded,
     * will yield a JSON object containing the user_id of the user who just
     * deauthorized your app. You will not receive a user access token in this
     * request and all existing user access tokens that were previously issued
     * on behalf of that user will become invalid."
     */
    public function deauthAction(Request $request)
    {
        $data = $this->decodeSignedRequestFromFacebook($request);
        /*
          Data looks like:
          Array(
          [algorithm] => HMAC-SHA256
          [issued_at] => 1347017664
          [user] => Array(
            [country] => gb
            [locale] => en_GB
          )
          [user_id] => 100001661128961
          )
         */

        $this->get('logger')->info('FB user deauthorized: ' . $data['user_id']);
        $user = $this->getDoctrine()->getRepository('AWFacebookAuthBundle:User')->find($data['user_id']);
        if ($user) {
            $user->setIsAuthorized(false);
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($user);
            $em->flush();
        }

        return new Response();
    }

    /**
     * @return array
     */
    private function decodeSignedRequestFromFacebook()
    {
        list($encodedSig, $payload) = explode('.', $request->get('signed_request'), 2);
        unset($encodedSig); // not needed.
        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    }
}
