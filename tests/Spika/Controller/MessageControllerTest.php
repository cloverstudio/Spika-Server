<?php
namespace Spika\Controller;

use Silex\Application;
use Silex\WebTestCase;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class MessageControllerTest extends WebTestCase
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
            ->method('getEmoticons')
            ->will($this->returnValue(array('rows'=>array())));
                        
        $spikadb->expects($this->any())
            ->method('getEmoticonImage')
            ->will($this->returnValue('OK'));
                        
        $app['spikadb'] = $spikadb;
        
        return $app;
    }

    /** @test */
    public function loadEmoticonsTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/Emoticons');
        assertRegExp('/rows/', $client->getResponse()->getContent());
    }
    
    /** @test */
    public function loadEmoticonTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/Emoticon/test');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }
}
