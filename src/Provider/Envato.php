<?php namespace Dilab\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Entity\User;
use League\OAuth2\Client\Token\AccessToken;

class Envato extends AbstractProvider
{
    public $responseType = 'json';
    public $authorizationHeader = 'Bearer';
    public $version = 'v1';

    /**
     * Get the URL that this provider uses to begin authorization.
     *
     * @return string
     */
    public function urlAuthorize()
    {
        return 'https://api.envato.com/authorization';
    }

    /**
     * Get the URL that this provider users to request an access token.
     *
     * @return string
     */
    public function urlAccessToken()
    {
        return 'https://api.envato.com/token';
    }

    /**
     * Get the URL that this provider uses to request user details.
     *
     * Since this URL is typically an authorized route, most providers will require you to pass the access_token as
     * a parameter to the request. For example, the google url is:
     *
     * 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token='.$token
     *
     * @param AccessToken $token
     * @return string
     */
    public function urlUserDetails(AccessToken $token)
    {
        return 'https://api.envato.com/v1/market/private/user/account.json';
    }

    public function urlEmail(AccessToken $token)
    {
        return 'https://api.envato.com/v1/market/private/user/email.json';
    }

    public function urlScreenName(AccessToken $token)
    {
        return 'https://api.envato.com/v1/market/private/user/username.json';
    }

    /**
     * Given an object response from the server, process the user details into a format expected by the user
     * of the client.
     *
     * @param object $response
     * @param AccessToken $token
     * @return mixed
     */
    public function userDetails($response, AccessToken $token)
    {
        $user = new User();
        $account = $response->account;

        $user->exchangeArray([
            'name' => $account->firstname . ' ' . $account->surname,
            'firstname' => $account->firstname,
            'lastname' => $account->surname,
            'location' => $account->country,
            'imageurl' => $account->image,
        ]);

        return $user;
    }

    public function getUserEmail(AccessToken $token)
    {
        $response = $this->fetchUserEmail($token);

        return $this->userEmail(json_decode($response), $token);
    }

    public function getScreenName(AccessToken $token)
    {
        $response = $this->fetchScreenName($token);

        return $this->userScreenName(json_decode($response), $token);
    }

    public function userEmail($response, AccessToken $token)
    {
       return $response->email;
    }

    public function userScreenName($response, AccessToken $token)
    {
        return $response->username;
    }

    protected function fetchUserEmail(AccessToken $token)
    {
        $url = $this->urlEmail($token);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function fetchScreenName(AccessToken $token)
    {
        $url = $this->urlScreenName($token);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }
}