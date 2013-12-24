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
            ->will($this->returnValue(array('id'=>'testGroup')));
            
        $spikadb->expects($this->any())
            ->method('updateGroup')
            ->will($this->returnValue('OK'));
            
        $spikadb->expects($this->any())
            ->method('deleteGroup')
            ->will($this->returnValue('OK'));
  

        $spikadb->expects($this->any())
            ->method('findGroupById')
            ->will($this->returnValue(array(
                'user_id' => 'testid'
            )));
            
        $spikadb->expects($this->any())
            ->method('findGroupByName')
            ->will($this->returnValue(array(
                'user_id' => 'testid'
            )));
            
        $spikadb->expects($this->any())
            ->method('findGroupsByName')
            ->will($this->returnValue('OK'));

        $spikadb->expects($this->any())
            ->method('subscribeGroup')
            ->will($this->returnValue(true));

        $spikadb->expects($this->any())
            ->method('unSubscribeGroup')
            ->will($this->returnValue(true));

        $spikadb->expects($this->any())
            ->method('findUserById')
            ->will($this->returnValue('OK'));
            
        $spikadb->expects($this->any())
            ->method('findGroupByCategoryId')
            ->will($this->returnValue('OK'));
            
        $spikadb->expects($this->any())
            ->method('findAllGroupCategory')
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

        assertRegExp("/testGroup/", $client->getResponse()->getContent());
    }
    
    /** @test */
    public function updateGroupRegularCaseTest()
    {
        $client = $this->createClient();
        
        $sendParams = array(
             '_id' => 'test',
            'name' => 'testGroup',
        );
        
        $crawler = $client->request(
            'POST',
            '/api/updateGroup',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );

        assertRegExp("/OK/", $client->getResponse()->getContent());
    }
    
    /** @test */
    public function deleteGroupRegularCaseTest()
    {
        $client = $this->createClient();
        
        $sendParams = array(
             '_id' => 'test',
        );
        
        $crawler = $client->request(
            'POST',
            '/api/deleteGroup',
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
        assertRegExp('/user_id/', $client->getResponse()->getContent());
    }

    /** @test */
    public function findGroupNameTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/findGroup/name/test');
        assertRegExp('/user_id/', $client->getResponse()->getContent());
    }

    /** @test */
    public function serachGroupByNameTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/searchGroups/name/test');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }
    
    /** @test */
    public function findAllGroupCategoryTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/findAllGroupCategory');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }
    
    /** @test */
    public function findGroupByCategoryId()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/findGroup/categoryId/test');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }
    
    /** @test */
    public function subscribeTest()
    {
        $client = $this->createClient();
        
        $sendParams = array(
             'group_id' => 'test'
        );
        
        $crawler = $client->request(
            'POST',
            '/api/subscribeGroup',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );

        assertRegExp("/OK/", $client->getResponse()->getContent());

    }

    /** @test */
    public function unSubscribeTest()
    {
        $client = $this->createClient();
        
        $sendParams = array(
             'group_id' => 'test'
        );
        
        $crawler = $client->request(
            'POST',
            '/api/unSubscribeGroup',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );

        assertRegExp("/OK/", $client->getResponse()->getContent());

    }
}
