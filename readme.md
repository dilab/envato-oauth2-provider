# Envato Provider for OAuth 2.0 Client


## Installation

To install, use composer:

``` composer require dilab/envato-oauth2-provider ```


## Authorization Code Flow
```
$provider = new \Dilab\OAuth2\Client\Provider\Envato([
    'clientId'          => '{envato-client-id}',
    'clientSecret'      => '{envato-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->state;
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $userDetails = $provider->getUserDetails($token);

        // Use these details to create a new profile
        printf('Hello %s!', $userDetails->firstName);

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->accessToken;
}
```


## Some Auth User Data

### $provider->getUserDetails($token) 

User Object:

```
$user->name

$user->firstname

$user->lastname

$user->location

$user->imageurl
```

### $provider->getUserEmail($token)
String: the currently logged in user's email address 


### $provider->getScreenName($token)
String: the currently logged in user's Envato Account username



## Testing
```
$ ./vendor/bin/phpunit
```

