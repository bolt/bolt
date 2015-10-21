<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\RecordHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test Bolt\Twig\Handler\RecordHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordHandlerTest extends BoltUnitTest
{
    /**
     * Empty route and content.
     */
    public function testCurrentHomeEmptyParameter()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/');
        $app['request'] = $request;
        $handler = new RecordHandler($app);

        $result = $handler->current(null);

        $this->assertTrue($result);
    }

    public function testCurrentHomeConfigured()
    {
        $app = $this->getApp();
        $app['config']->set('general/homepage', '/clippy-inc');
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/clippy-inc');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => '',
            'contenttypeslug' => ''
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/clippy-inc');
        $this->assertTrue($result);
    }

    public function testCurrentHomeMenu()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => '',
            'contenttypeslug' => ''
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current([
            'label' => 'Home',
            'title' => 'This is the first menu item.',
            'path'  => 'homepage',
            'class' => 'first',
            'link'  => '/',
        ]);
        $this->assertTrue($result);
    }

    public function testCurrentContentNameInRoute()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckArray()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current(['link' => '/pages/koala']);
        $this->assertTrue($result);
    }

    public function testCurrentNoMatchingSlugs()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current(['contenttype' => ['slug' => 'snails', 'slug' => 'singular_slug']]);
        $this->assertFalse($result);
    }

    public function testCurrentLinkToCheckContentObject()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);
        $content = $this->getMock('\Bolt\Legacy\Content', ['link'], [$app]);
        $content->expects($this->atLeastOnce())
            ->method('link')
            ->will($this->returnValue('/pages/koala'))
        ;

        $result = $handler->current($content);
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckString()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckStringWithQuery()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala?page=42');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckStringSeparateRoute()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentEmptyRecordSlugEmptyContentTypeSlug()
    {
        $this->resetDb();
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => null,
            'contenttypeslug' => null
        ]);
        $app['request'] = $request;
        $repo = $app['storage']->getRepository('pages');
        $content = $repo->find(1);

        $handler = new RecordHandler($app);

        $result = $handler->current($content);
        $this->assertFalse($result);
    }

    public function testCurrentContentTypeSlugContentSlug()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentTheFinalCountdown()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/gum-tree/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'gum-tree'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);
        $content = [
            'slug'        => 'koala',
            'contenttype' => [
                'slug'          => 'gum-trees',
                'singular_slug' => 'gum-tree'
            ]
        ];

        $result = $handler->current($content);
        $this->assertTrue($result);
    }

    public function testCurrentTheFinalCountdownRadioEdit()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/gum-trees/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'gum-trees'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);
        $content = [
            'slug'        => 'koala',
            'contenttype' => [
                'slug'          => 'gum-trees',
                'singular_slug' => 'gum-tree'
            ]
        ];

        $result = $handler->current($content);
        $this->assertTrue($result);
    }

    public function testCurrentFalse()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/gum-tree/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'gum-tree'
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);
        $content = ['slug' => 'clippy'];

        $result = $handler->current($content);
        $this->assertFalse($result);
    }
}
