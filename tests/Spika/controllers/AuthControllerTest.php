<?php
namespace Spika\Controllers;
use Silex\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function createApplication()
    {
        require realpath(__DIR__ . '/../../../') . '/wwwroot/api.php';

        $spikadb = $this->getMockBuilder('\Spika\SpikaDBHandler')
            ->setMethods(array('doSpikaAuth'))
            ->disableOriginalConstructor()
            ->getMock();
        $spikadb->expects($this->once())
            ->method('doSpikaAuth')
            ->will($this->returnValue('auth result'));
        $app['spikadb'] = $spikadb;

        return $app;
    }

    public function testHookupAuth()
    {
        $client = $this->createClient();
        $crawler = $client->request('POST', '/api/hookup-auth.php');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertSame('auth result', $client->getResponse()->getContent());
    }
}

