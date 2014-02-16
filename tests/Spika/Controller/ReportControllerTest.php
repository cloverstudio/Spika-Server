<?php
namespace Spika\Controller;

use Silex\Application;
use Silex\WebTestCase;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class ReportControllerTest extends WebTestCase
{
    public function createApplication()
    {
        $pimple = new \Pimple;

        $dependencies = array(
            'beforeTokenChecker' => $pimple->protect(function () {
            })
        );

        require SPIKA_ROOT . '/etc/app.php';

        $mailer = $this->getMockBuilder('\Silex\Provider\SwiftmailerServiceProvider')
            ->setMethods(array('send'))
            ->disableOriginalConstructor()
            ->getMock();
        $mailer->expects(once())
            ->method('send')
            ->with(isInstanceOf('Swift_Message'));
        $app['mailer'] = $mailer;

        return $app;
    }

    /** @test */
    public function reportViolationSendsMailAndReturnsOK()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/reportViolation.php', array('docment_id' => 'testid'));
        assertSame('OK', $client->getResponse()->getContent());
    }
}
