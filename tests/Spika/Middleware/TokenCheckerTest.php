<?php
namespace Spika\Middleware;

use Spika\Middleware\TokenChecker;
use Spika\Db\DbInterface;
use Symfony\Component\HttpFoundation\Request;

class TokenCheckerTest extends \PHPUnit_Framework_TestCase
{
    const FIXTURE_USER_ID = '123';
    const FIXTURE_TOKEN   = 'some_token';

    /**
     * @test
     */
    public function whenValidRequestIsGiven()
    {
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';
        
        $user = $this->createFixtureUser();
        $db   = $this->createMockDb();

        $db->expects(any())
            ->method('findUserByToken')
            ->will(returnValue($user));

        $checker = $this->createTokenChecker($db);
        $request = $this->createValidRequest();

        $this->assertNull($checker($request,$app));
    }

    /**
     * @test
     */
    public function whenTokenIsNotMatched()
    {
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';
        $db   = $this->createMockDb();
        $user = array_merge(
            $this->createFixtureUser(),
            array('token' => 'token_not_matched')
        );

        $db->expects(any())
            ->method('findUserByToken')
            ->will(returnValue($user));

        $checker = $this->createTokenChecker($db);
        $request = $this->createValidRequest();

        $this->assertErrorResponse(
            403,
            'Invalid token',
            $checker($request,$app)
        );
    }

    /**
     * @test
     */
    public function whenTokenExpirationIsLeftOneSecond()
    {
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';
        $db   = $this->createMockDb();
        $user = array_merge(
            $this->createFixtureUser(),
            array('token_timestamp' => time() - TOKEN_VALID_TIME)
        );

        $db->expects(any())
            ->method('findUserByToken')
            ->will(returnValue($user));

        $checker = $this->createTokenChecker($db);
        $request = $this->createValidRequest();

        $this->assertNull($checker($request,$app));
    }

    /**
     * @test
     */
    public function whenTokenIsExpired()
    {
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';
        $db   = $this->createMockDb();
        $user = array_merge(
            $this->createFixtureUser(),
            array('token_timestamp' => time() - TOKEN_VALID_TIME - 1)
        );

        $db->expects(any())
            ->method('findUserByToken')
            ->will(returnValue($user));

        $checker = $this->createTokenChecker($db);
        $request = $this->createValidRequest();

        $this->assertErrorResponse(
            403,
            'Token expired',
            $checker($request,$app)
        );
    }

    private function createTokenChecker(DbInterface $db)
    {
        return new TokenChecker(
            $db,
            $this->getMock('Psr\Log\LoggerInterface')
        );
    }

    private function createMockDb()
    {
        return $this->getMock('Spika\Db\DbInterface');
    }

    private function createFixtureUser()
    {
         return array(
            '_id'             => self::FIXTURE_USER_ID,
            'token'           => self::FIXTURE_TOKEN,
            'token_timestamp' => time(),
        );
    }

    private function createValidRequest($path = "", $method = "GET", $requestBody = "")
    {
    
        return Request::create(
            $path,
            $method,
            array(),
            array(),
            array(),
            array(
                'HTTP_TOKEN'   => self::FIXTURE_TOKEN,
            ),
            $requestBody
        );

    }

    private function assertErrorResponse($expectedStatus, $expectedMessage, $response)
    {
        $this->assertInstanceOf(
            'Symfony\Component\HttpFoundation\Response',
            $response
        );
        $this->assertSame($expectedStatus, $response->getStatusCode());

        $message = json_decode($response->getContent())->message;

        $this->assertSame($expectedMessage, $message);
    }
}
