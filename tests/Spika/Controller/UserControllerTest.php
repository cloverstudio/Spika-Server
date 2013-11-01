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
            ->method('findUserById')
            ->will($this->returnValue('OK'));

        $spikadb->expects($this->any())
            ->method('findUserByEmail')
            ->will($this->returnValue('OK'));
            
        $spikadb->expects($this->any())
            ->method('findUserByName')
            ->will($this->returnValue('OK'));
                        
        $spikadb->expects($this->any())
            ->method('getActivitySummary')
            ->will($this->returnValue('total_rows'));
                        
        $app['spikadb'] = $spikadb;
        
        return $app;
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
    public function findActivitySummaryTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/activitySummary');
        assertRegExp('/total_rows/', $client->getResponse()->getContent());
    }
}
