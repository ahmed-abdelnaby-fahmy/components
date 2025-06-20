<?php

declare(strict_types=1);

namespace Hypervel\Tests\Socialite;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Hypervel\Context\Context;
use Hypervel\Http\Contracts\RequestContract;
use Hypervel\Http\Contracts\ResponseContract;
use Hypervel\Socialite\Contracts\User as UserContract;
use Hypervel\Socialite\Two\LinkedInOpenIdProvider;
use Hypervel\Socialite\Two\User;
use Hypervel\Tests\TestCase;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 * @coversNothing
 */
class LinkedInOpenIdProviderTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        Context::destroyAll();
    }

    public function testResponse()
    {
        $user = $this->fromResponse([
            'sub' => 'asdfgh',
            'given_name' => 'Nuno',
            'picture' => 'https://media.licdn.com/dms/image/D4D03AQmZFgJNqeNNk',
            'name' => 'Nuno Maduro',
            'family_name' => 'Maduro',
            'email' => 'nuno@laravel.com',
            'email_verified' => true,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('asdfgh', $user->getId());
        $this->assertNull($user->getNickname());
        $this->assertSame('Nuno Maduro', $user->getName());
        $this->assertSame('nuno@laravel.com', $user->getEmail());
        $this->assertSame('https://media.licdn.com/dms/image/D4D03AQmZFgJNqeNNk', $user->getAvatar());

        $this->assertSame([
            'id' => 'asdfgh',
            'nickname' => null,
            'name' => 'Nuno Maduro',
            'first_name' => 'Nuno',
            'last_name' => 'Maduro',
            'email' => 'nuno@laravel.com',
            'email_verified' => true,
            'avatar' => 'https://media.licdn.com/dms/image/D4D03AQmZFgJNqeNNk',
            'avatar_original' => 'https://media.licdn.com/dms/image/D4D03AQmZFgJNqeNNk',
        ], $user->attributes);
    }

    public function testMissingEmailAndAvatar()
    {
        $user = $this->fromResponse([
            'sub' => 'asdfgh',
            'given_name' => 'Nuno',
            'name' => 'Nuno Maduro',
            'family_name' => 'Maduro',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('asdfgh', $user->getId());
        $this->assertNull($user->getNickname());
        $this->assertSame('Nuno Maduro', $user->getName());
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getAvatar());

        $this->assertSame([
            'id' => 'asdfgh',
            'nickname' => null,
            'name' => 'Nuno Maduro',
            'first_name' => 'Nuno',
            'last_name' => 'Maduro',
            'email' => null,
            'email_verified' => null,
            'avatar' => null,
            'avatar_original' => null,
        ], $user->attributes);
    }

    protected function fromResponse(array $response): UserContract
    {
        $request = m::mock(RequestContract::class);
        $request->allows('input')->with('code')->andReturns('fake-code');

        $stream = m::mock(StreamInterface::class);
        $stream->allows('__toString')->andReturns(json_encode(['access_token' => 'fake-token']));

        $accessTokenResponse = m::mock(ResponseInterface::class);
        $accessTokenResponse->allows('getBody')->andReturns($stream);

        $basicProfileStream = m::mock(StreamInterface::class);
        $basicProfileStream->allows('__toString')->andReturns(json_encode($response));

        $basicProfileResponse = m::mock(ResponseInterface::class);
        $basicProfileResponse->allows('getBody')->andReturns($basicProfileStream);

        $guzzle = m::mock(Client::class);
        $guzzle->expects('post')->andReturns($accessTokenResponse);
        $guzzle->allows('get')->with('https://api.linkedin.com/v2/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer fake-token',
                'X-RestLi-Protocol-Version' => '2.0.0',
            ],
            RequestOptions::QUERY => [
                'projection' => '(sub,email,email_verified,name,given_name,family_name,picture)',
            ],
        ])->andReturns($basicProfileResponse);

        $provider = new LinkedInOpenIdProvider(
            $request,
            m::mock(ResponseContract::class),
            'client_id',
            'client_secret',
            'redirect'
        );
        $provider->stateless();
        Context::set(
            'socialite.providers.' . LinkedInOpenIdProvider::class . '.httpClient',
            $guzzle
        );

        return $provider->user();
    }
}
