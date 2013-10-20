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

        $spikadb = $this->getMock('\Spika\Db\DbInterface');
        $spikadb->expects(any())
            ->method('checkEmailIsUnique')
            ->with('ken.yasue@clover-studio.com')
            ->will(returnValue('check by email'));

        $spikadb->expects(any())
            ->method('checkUserNameIsUnique')
            ->with('spikauser')
            ->will(returnValue('check by username'));

        $spikadb->expects(any())
            ->method('checkGroupNameIsUnique')
            ->with('spikagroup')
            ->will(returnValue('check by groupname'));

        $app['spikadb'] = $spikadb;

        return $app;
    }

    /** @test */
    public function checkUniqueChecksByEmailWhenPassedEmail()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/checkUnique.php', array('email' => 'ken.yasue@clover-studio.com'));
        assertSame(true, $client->getResponse()->isOk());
        assertSame('check by email', $client->getResponse()->getContent());
    }

    /** @test */
    public function checkUniqueChecksByUsernameWhenPassedUsername()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/checkUnique.php', array('email' => '', 'username' => 'spikauser'));
        assertSame(true, $client->getResponse()->isOk());
        assertSame('check by username', $client->getResponse()->getContent());
    }

    /** @test */
    public function checkUniqueChecksByGroupnameWhenPassedUsername()
    {
        $client = $this->createClient();
        $crawler = $client->request(
            'GET',
            '/api/checkUnique.php',
            array('email' => '', 'username' => '', 'groupname' => 'spikagroup')
        );
        assertSame(true, $client->getResponse()->isOk());
        assertSame('check by groupname', $client->getResponse()->getContent());
    }
}
