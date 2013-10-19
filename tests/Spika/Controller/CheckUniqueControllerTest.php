<?php
/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\WebTestCase;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class CheckUniqueControllerTest extends WebTestCase
{
    public function createApplication()
    {
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';

        $spikadb = $this->getMockBuilder('\Spika\SpikaDBHandler')
            ->setMethods(array('checkEmailIsUnique', 'checkUserNameIsUnique', 'checkGroupNameIsUnique'))
            ->disableOriginalConstructor()
            ->getMock();

        $spikadb->expects($this->any())
            ->method('checkEmailIsUnique')
            ->with('ken.yasue@clover-studio.com')
            ->will($this->returnValue('check by email'));

        $spikadb->expects($this->any())
            ->method('checkUserNameIsUnique')
            ->with('spikauser')
            ->will($this->returnValue('check by username'));

        $spikadb->expects($this->any())
            ->method('checkGroupNameIsUnique')
            ->with('spikagroup')
            ->will($this->returnValue('check by groupname'));

        $app['spikadb'] = $spikadb;

        return $app;
    }

    /** @test */
    public function checkUnique_checks_by_email_when_passed_email()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/checkUnique.php', array('email' => 'ken.yasue@clover-studio.com'));
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertSame('check by email', $client->getResponse()->getContent());
    }

    /** @test */
    public function checkUnique_checks_by_username_when_passed_username()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/checkUnique.php', array('email' => '', 'username' => 'spikauser'));
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertSame('check by username', $client->getResponse()->getContent());
    }

    /** @test */
    public function checkUnique_checks_by_groupname_when_passed_username()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/checkUnique.php', array('email' => '', 'username' => '', 'groupname' => 'spikagroup'));
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertSame('check by groupname', $client->getResponse()->getContent());
    }
}

