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
        $user = $this->createFixtureUser();
        $db   = $this->createMockDb();

        $db->expects(any())
            ->method('findUserById')
            ->will(returnValue($user));

        $checker = $this->createTokenChecker($db);
        $request = $this->createValidRequest();

        $this->assertNull($checker($request));
    }

    /**
     * @test
     */
    public function whenCreateUserRequestIsGiven()
    {
        $db      = $this->createMockDb();
        $checker = $this->createTokenChecker($db);
        $request = $this->createValidRequest();

        $request->server->set('REQUEST_METHOD', 'POST');
        $request->headers->set('user_id', 'create_user');

        $this->assertNull($checker($request));
    }

    /**
     * @test
     */
    public function whenRequestWithoutUserIdIsGiven()
    {
        $db      = $this->createMockDb();
        $checker = $this->createTokenChecker($db);
        $request = $this->createValidRequest();

        $request->headers->set('user_id', null);

        $this->assertErrorResponse(
            403,
            'No token sent',
            $checker($request)
        );
    }

    /**
     * @test
     */
    public function whenNoUserIsFound()
    {
        $db      = $this->createMockDb();
        $checker = $this->createTokenChecker($db);
        $request = $this->createValidRequest();

        $this->assertErrorResponse(
            403,
            'No token sent',
            $checker($request)
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

    private function createValidRequest()
    {
        return new Request(
            array(),
            array(),
            array(),
            array(),
            array(),
            array(
                'HTTP_USER_ID' => self::FIXTURE_USER_ID,
                'HTTP_TOKEN'   => self::FIXTURE_TOKEN,
            )
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
