<?php

namespace Spika\Controller;

use Silex\WebTestCase;

class CreateUserControllerTest extends WebTestCase
{
    public function createApplication()
    {
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';
 
        $spikadb = $this->getMock('\Spika\Db\DbInterface');
        $spikadb->expects($this->once())
            ->method('createUser')
            ->will($this->returnValue('tempip'));
            
        $app['spikadb'] = $spikadb;

        return $app;
    }

    /** @test */
    public function createUserRegularCaseTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('POST', '/api/createUser');
        
        assertRegExp("/ok.+true/", $client->getResponse()->getContent());
    }
}
