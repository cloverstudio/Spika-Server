<?php

namespace Spika\Controller;

use Silex\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function createApplication()
    {
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';

        $spikadb = $this->getMock('\Spika\Db\DbInterface');
        $spikadb->expects($this->once())
            ->method('doSpikaAuth')
            ->will($this->returnValue('auth result'));
        $app['spikadb'] = $spikadb;

        return $app;
    }

    /** @test */
    public function hookupAuthReturnsTheValueReturnedSpikadb()
    {
        $client = $this->createClient();
        $crawler = $client->request('POST', '/api/hookup-auth.php');
        assertSame(true, $client->getResponse()->isOk());
        assertSame('auth result', $client->getResponse()->getContent());
    }
}
