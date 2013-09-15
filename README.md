## Sample Use In A Controller Action ##

    $facebookUser = $this->get('aw_facebook_auth')->getUserFromSession();
    // $facebookUser is of type AW\Bundle\FacebookAuthBundle\Entity\User

    if (!$facebookUser) {
        // Not logged in as a Facebook user - get the user to login now.
        return $this->redirect($this->generateUrl(
            'aw_facebook_auth',
            array('continue' => $this->generateUrl('page_to_continue_to'))
        ));
    }
    return $this->redirect($this->generateUrl('page_to_continue_to'));

## Install ##

* If you use Git, run `git submodule add git@github.com:amyboyd/symfony2-facebook-auth-bundle.git path/to/bundles/AW/Bundle/FacebookAuthBundle`

* If you don't use Git, download the source and put it into your bundles
  directory.

* Visit https://developers.facebook.com/apps to generate your
  app ID, app secret, and to register your redirect URI.

* Enable AWFacebookAuthBundle in your `app/AppKernel.php`

* Add to your app/config/routing.yml:

    `aw_facebook_auth: {
        resource: "@AWFacebookAuthBundle/Resources/config/routing.yml",
        prefix:   / }`

* Copy the contents of `Resources/config/parameters.yml.sample` to your own `app/config/parameters.yml`

* Review `app/console doctrine:schema:update --dump-sql`

* Run `app/console doctrine:schema:update --force` if the above was OK.
