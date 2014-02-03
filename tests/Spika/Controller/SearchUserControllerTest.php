<?php
namespace Spika\Controller;

use Silex\Application;
use Silex\WebTestCase;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class SearchUserControllerTest extends WebTestCase
{

    const FIXTURE_TOKEN   = 'some_token';

    public function createApplication()
    {
        $pimple = new \Pimple;
        
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';

        $spikadb = $this->getMock('\Spika\Db\DbInterface');
        
        $spikadb->expects($this->any())
            ->method('searchUserByName')
            ->will($this->returnValue('[]'));

        $spikadb->expects($this->any())
            ->method('searchUserByGender')
            ->will($this->returnValue('[]'));
            
        $spikadb->expects($this->any())
            ->method('searchUserByAge')
            ->will($this->returnValue('[]'));
                        
        $spikadb->expects($this->any())
            ->method('searchUser')
            ->will($this->returnValue('[]'));
                        
        $app['spikadb'] = $spikadb;
        
        return $app;
    }
    
    /** @test */
    public function searchUserByName()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/searchUsers?n=test');
        assertRegExp('/\[\]/', $client->getResponse()->getContent());
    }

    /** @test */
    public function searchUserByGender()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/searchUsers?g=male');
        assertRegExp('/\[\]/', $client->getResponse()->getContent());
    }

    /** @test */
    public function searchUserByAge()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/searchUsers?af=30&at=35');
        assertRegExp('/\[\]/', $client->getResponse()->getContent());
    }
}
