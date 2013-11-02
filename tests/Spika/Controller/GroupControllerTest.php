<?php

namespace Spika\Controller;

use Silex\Application;
use Silex\WebTestCase;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class GroupControllerTest extends WebTestCase
{
    public function createApplication()
    {
        $pimple = new \Pimple;

        $dependencies = array(
            'beforeTokenChecker' => $pimple->protect(function () {
            }),
            'currentUser' => array("_id"=>"testid","token"=>"testtoken")
        );

        require realpath(__DIR__ . '/../../../') . '/etc/app.php';
 
        $spikadb = $this->getMock('\Spika\Db\DbInterface');
        $spikadb->expects($this->any())
            ->method('createGroup')
            ->will($this->returnValue('OK'));
            
        $spikadb->expects($this->any())
            ->method('findGroupById')
            ->will($this->returnValue('OK'));
            
        $app['spikadb'] = $spikadb;

        return $app;
    }

    /** @test */
    public function createGroupRegularCaseTest()
    {
        $client = $this->createClient();
        
        $sendParams = array(
            'name' => 'testGroup',
        );
        
        $crawler = $client->request(
            'POST',
            '/api/createGroup',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );

        assertRegExp("/OK/", $client->getResponse()->getContent());
    }
    

    /** @test */
    public function findGroupIdTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/findGroup/id/test');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }
}
