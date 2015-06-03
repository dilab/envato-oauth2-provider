<?php namespace Dilab\OAuth2\Client\Test\Provider;

use Mockery as m;

class EnvatoTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected function setUp()
    {
        $this->provider = new \Dilab\OAuth2\Client\Provider\Envato([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->state);
    }

    public function testUrlAccessToken()
    {
        $url = $this->provider->urlAccessToken();
        $uri = parse_url($url);
        $this->assertEquals('/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Guzzle\Http\Message\Response');
        $response->shouldReceive('getBody')->times(1)->andReturn('{"access_token": "mock_access_token", "expires_in": 3600, "refresh_token": "mock_refresh_token", "token_type": "bearer"}');

        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(1);
        $client->shouldReceive('post->send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->accessToken);
        $this->assertLessThanOrEqual(time() + 3600, $token->expires);
        $this->assertGreaterThanOrEqual(time(), $token->expires);
        $this->assertEquals('mock_refresh_token', $token->refreshToken);
    }

    public function testUrlUserDetails()
    {
        $url = $this->provider->urlUserDetails(m::mock('League\OAuth2\Client\Token\AccessToken'));
        $uri = parse_url($url);
        $this->assertEquals('/v1/market/private/user/account.json', $uri['path']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Required option not passed: access_token
     */
    public function testGetAccessTokenWithInvalidJson()
    {
        $response = m::mock('Guzzle\Http\Message\Response');
        $response->shouldReceive('getBody')->times(1)->andReturn('invalid');
        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(1);
        $client->shouldReceive('post->send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        $this->provider->responseType = 'json';
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testGetAccessTokenSetResultUid()
    {
        $this->provider->uidKey = 'otherKey';
        $response = m::mock('Guzzle\Http\Message\Response');
        $response->shouldReceive('getBody')->times(1)->andReturn('{"access_token": "mock_access_token", "expires_in": 3600, "refresh_token": "mock_refresh_token", "token_type": "bearer", "otherKey":"{1234}"}');
        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(1);
        $client->shouldReceive('post->send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->accessToken);
        $this->assertLessThanOrEqual(time() + 3600, $token->expires);
        $this->assertGreaterThanOrEqual(time(), $token->expires);
        $this->assertEquals('mock_refresh_token', $token->refreshToken);
        $this->assertEquals('{1234}', $token->uid);
    }

    public function testScopes()
    {
        $this->provider->setScopes(['user', 'repo']);
        $this->assertEquals(['user', 'repo'], $this->provider->getScopes());
    }

    public function testUserDetails()
    {
        $postResponse = m::mock('Guzzle\Http\Message\Response');
        $postResponse->shouldReceive('getBody')->times(1)->andReturn('{"access_token": "mock_access_token", "expires_in": 3600, "refresh_token": "mock_refresh_token", "token_type": "bearer"}');

        $getResponse = m::mock('Guzzle\Http\Message\Response');
        $getResponse->shouldReceive('getBody')->times(1)->andReturn('{"account": {"image": "mock_avator.jpg", "firstname": "mock_firstname", "surname": "mock_surname", "available_earnings": "mock_available_earnings", "total_deposits": "mock_total_deposits", "balance": "mock_balance", "country": "mock_country"}}');

        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(2);
        $client->shouldReceive('setDefaultOption')->times(1);
        $client->shouldReceive('post->send')->times(1)->andReturn($postResponse);
        $client->shouldReceive('get->send')->times(1)->andReturn($getResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getUserDetails($token); var_dump($user);
        $this->assertEquals('mock_firstname mock_surname', $user->name);
        $this->assertEquals('mock_firstname', $user->firstName);
        $this->assertEquals('mock_surname', $user->lastName);
        $this->assertEquals('mock_avator.jpg', $user->imageUrl);
        $this->assertEquals('mock_country', $user->location);
    }

    public function testUrlEmail()
    {
        $url = $this->provider->urlEmail(m::mock('League\OAuth2\Client\Token\AccessToken'));
        $uri = parse_url($url);
        $this->assertEquals('/v1/market/private/user/email.json', $uri['path']);

    }

    public function testGetUserEmail()
    {
        $postResponse = m::mock('Guzzle\Http\Message\Response');
        $postResponse->shouldReceive('getBody')->times(1)->andReturn('{"access_token": "mock_access_token", "expires_in": 3600, "refresh_token": "mock_refresh_token", "token_type": "bearer"}');

        $getResponse = m::mock('Guzzle\Http\Message\Response');
        $getResponse->shouldReceive('getBody')->times(1)->andReturn('{"email": "mock_email@gmail.com"}');

        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(2);
        $client->shouldReceive('setDefaultOption')->times(1);
        $client->shouldReceive('post->send')->times(1)->andReturn($postResponse);
        $client->shouldReceive('get->send')->times(1)->andReturn($getResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_email@gmail.com', $this->provider->getUserEmail($token));
    }

    public function testUrlScreenName()
    {
        $url = $this->provider->urlScreenName(m::mock('League\OAuth2\Client\Token\AccessToken'));
        $uri = parse_url($url);
        $this->assertEquals('/v1/market/private/user/username.json', $uri['path']);

    }

    public function testGetScreenName()
    {
        $postResponse = m::mock('Guzzle\Http\Message\Response');
        $postResponse->shouldReceive('getBody')->times(1)->andReturn('{"access_token": "mock_access_token", "expires_in": 3600, "refresh_token": "mock_refresh_token", "token_type": "bearer"}');

        $getResponse = m::mock('Guzzle\Http\Message\Response');
        $getResponse->shouldReceive('getBody')->times(1)->andReturn('{"username": "mock_username"}');

        $client = m::mock('Guzzle\Service\Client');
        $client->shouldReceive('setBaseUrl')->times(2);
        $client->shouldReceive('setDefaultOption')->times(1);
        $client->shouldReceive('post->send')->times(1)->andReturn($postResponse);
        $client->shouldReceive('get->send')->times(1)->andReturn($getResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_username', $this->provider->getScreenName($token));
    }
}
