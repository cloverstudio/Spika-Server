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
            ->will($this->returnValue('jR9hCaktyH51TOxG57J5jqcuymkSC2uWUDdwOy0m'));
        $app['spikadb'] = $spikadb;

        return $app;
    }

    /** @test */
    public function hookupAuthReturnsTheValueReturnedSpikadb()
    {
        $client = $this->createClient();
        $sendParams = array(
            'name' => 'spikaTarou',
            'email' => 'spikaTarou@clover-studio.com',
            'password' => 'testtest',
        );
        
        $crawler = $client->request(
            'POST',
            '/api/auth',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );

        assertRegExp("/[0-9a-zA-Z]{40}/", $client->getResponse()->getContent());
    }
}
