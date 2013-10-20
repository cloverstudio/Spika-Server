<?php

namespace Spika\Controller;

use Silex\WebTestCase;

class CreateUserControllerTest extends WebTestCase
{
    public function createApplication()
    {
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';
 
        $spikadb = $this->getMock('\Spika\Db\DbInterface');
        $spikadb->expects($this->any())
            ->method('createUser')
            ->will($this->returnValue('tempip'));
            
        $app['spikadb'] = $spikadb;

        return $app;
    }

    /** @test */
    public function createUserRegularCaseTest()
    {
        $client = $this->createClient();
        
        $sendParams = array(
            'name' => 'spikaTarou',
            'email' => 'spikaTarou@clover-studio.com',
            'password' => 'testtest',
            'type' => 'user',
            'online_status'=>'online',
            'max_contact_count'=>'20',
            'max_favorite_count'=>'10'
        );
        
        $crawler = $client->request(
            'POST',
            '/api/createUser',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );
        
        assertRegExp("/ok.+true/", $client->getResponse()->getContent());
    }
    
    /** @test */
    public function createUserIregularCaseTest()
    {
        $client = $this->createClient();
        
        $sendParams = array(
            'name' => 'spikaTarou',
        );
        
        $crawler = $client->request(
            'POST',
            '/api/createUser',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );
        
        assertRegExp("/error/", $client->getResponse()->getContent());
    }
}
