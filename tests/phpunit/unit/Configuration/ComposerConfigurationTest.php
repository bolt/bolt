<?php

namespace Bolt\Tests\Configuration;

use Bolt\Configuration\Composer;
use Bolt\Configuration\ComposerChecks;
use Bolt\Exception\BootException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Extension_FunctionMocker as FunctionMocker;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @group legacy
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 * @runTestsInSeparateProcesses
 */
class ComposerConfigurationTest extends TestCase
{
    /** @var MockObject */
    protected $php;

    public function setup()
    {
        $this->php = FunctionMocker::start($this, 'Bolt\Configuration')
            ->mockFunction('is_writable')
            ->mockFunction('is_dir')
            ->getMock();
    }

    public function tearDown()
    {
        \PHPUnit_Extension_FunctionMocker::tearDown();
    }

    public function testComposerCustomConfig()
    {
        $config = new Composer(TEST_ROOT);
        $this->assertEquals('/bolt-public/', $config->getUrl('app'));
    }

    public function testComposerVerification()
    {
        $config = new Composer(TEST_ROOT);
        $verifier = new ComposerChecks($config);

        // Return true for all theses checks
        $this->php
            ->expects($this->any())
            ->method('is_dir')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->any())
            ->method('is_writable')
            ->will($this->returnValue(true));

        $config->setVerifier($verifier);
        $config->verify();
    }

    public function testCheckSummary()
    {
        $config = new Composer(TEST_ROOT);
        $verifier = new ComposerChecks($config);
        $config->setPathResolver($config->getPathResolverFactory()->create());

        try {
            $verifier->checkDir('/non/existent/path');
            $this->fail(sprintf('%s not thrown', BootException::class));
        } catch (BootException $e) {
            $message = strip_tags($e->getMessage());
            $this->assertRegExp("/The default folder \/non\/existent\/path doesn't exist/", $message);
            $this->assertRegExp('/When using Bolt as a Composer package it will need to have access to the following folders/', $message);
            //$this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testCheckSummaryReportsError()
    {
        $config = new Composer(TEST_ROOT);
        $config->setPath('database', '/path/to/nowhere');
        $verifier = new ComposerChecks($config);
        $config->setPathResolver($config->getPathResolverFactory()->create());

        try {
            $verifier->checkDir('/path/to/nowhere');
            $this->fail(sprintf('%s not thrown', BootException::class));
        } catch (BootException $e) {
            $message = strip_tags($e->getMessage());
            $this->assertRegExp("/The default folder \/path\/to\/nowhere doesn't exist/", $message);
            $this->assertRegExp('/When using Bolt as a Composer package it will need to have access to the following folders/', $message);
            //$this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }

    public function testCheckDir()
    {
        $fakeLocation = '/path/to/nowhere';
        $config = new Composer(TEST_ROOT);
        $verifier = new ComposerChecks($config);
        $config->setPathResolver($config->getPathResolverFactory()->create());

        $app['resources'] = $config;

        // Check we get an exception if the directory isn't writable
        $this->php
            ->expects($this->at(0))
            ->method('is_dir')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->at(1))
            ->method('is_dir')
            ->will($this->returnValue(true));

        $this->php
            ->expects($this->any())
            ->method('is_writable')
            ->will($this->returnValue(false));

        try {
            $verifier->checkDir($fakeLocation);
            $this->fail(sprintf('%s not thrown', BootException::class));
        } catch (BootException $e) {
            $message = strip_tags($e->getMessage());
            $this->assertRegExp("/The default folder \/path\/to\/nowhere isn't writable. Make sure it's writable to the user that the web server is using/", $message);
            $this->assertRegExp('/When using Bolt as a Composer package it will need to have access to the following folders/', $message);
            //$this->assertRegExp('/Bolt - Fatal error/', $e::$screen);
        }
    }
}
