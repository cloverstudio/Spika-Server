<?php
namespace Spika\Controller;

use Silex\Application;
use Silex\WebTestCase;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class UserControllerTest extends WebTestCase
{

    const FIXTURE_TOKEN   = 'some_token';

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
            ->method('doSpikaAuth')
            ->will($this->returnValue('jR9hCaktyH51TOxG57J5jqcuymkSC2uWUDdwOy0m'));
        $app['spikadb'] = $spikadb;
        
        $spikadb->expects($this->any())
            ->method('createUser')
            ->will($this->returnValue('tempip'));
            
        $spikadb->expects($this->any())
            ->method('findUserById')
            ->will($this->returnValue('OK'));

        $spikadb->expects($this->any())
            ->method('findUserByEmail')
            ->will($this->returnValue('OK'));
            
        $spikadb->expects($this->any())
            ->method('findUserByName')
            ->will($this->returnValue('OK'));
                        
        $spikadb->expects($this->any())
            ->method('addContact')
            ->will($this->returnValue('OK'));
                        
        $spikadb->expects($this->any())
            ->method('removeContact')
            ->will($this->returnValue('OK'));
                        
        $spikadb->expects($this->any())
            ->method('getActivitySummary')
            ->will($this->returnValue('total_rows'));
                        
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
    
    /** @test */
    public function createUserRegularCaseTest()
    {
        $client = $this->createClient();
        
        $sendParams = array(
            'name' => 'spikaTarou',
            'email' => 'spikaTarou@clover-studio.com',
            'password' => 'testtest',
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


    /** @test */
    public function findUserEmailTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/findUser/email/test');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }

    /** @test */
    public function findUserNameTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/findUser/name/test');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }

    /** @test */
    public function findUserIdTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/findUser/id/test');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }


    /** @test */
    public function addContactTest()
    {

        $client = $this->createClient();
        
        $sendParams = array(
            'user_id' => 'test'
        );
        
        $crawler = $client->request(
            'POST',
            '/api/addContact',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );


        assertRegExp('/OK/', $client->getResponse()->getContent());
    }


    /** @test */
    public function removeContactTest()
    {

        $client = $this->createClient();
        
        $sendParams = array(
            'user_id' => 'test'
        );
        
        $crawler = $client->request(
            'POST',
            '/api/removeContact',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );



        assertRegExp('/OK/', $client->getResponse()->getContent());
    }

    /** @test */
    public function findActivitySummaryTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/activitySummary');
        assertRegExp('/total_rows/', $client->getResponse()->getContent());
    }
}
