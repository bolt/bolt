<?php

namespace Bolt\Tests\Twig;

use Bolt\Asset\Snippet\Snippet;
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
    protected $original = <<<GRINGALET
But Gawain chose the lower road, and passed
Along the desolate shore. The die was cast.
The western skies, as the red sun sank low,
Cast purple shades across the drifted snow,
And Gawain knew that the dread hour was come
For the fulfillment of his martyrdom.
GRINGALET;
    protected $excerpt = <<<GRINGALET
But Gawain chose the lower road, and passed
Along the desolate shore. The die was cast…
GRINGALET;

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

    public function testExcerptContentClassObject()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $content = $app['storage']->getEmptyContent('pages');
        $content->setValue('body', $this->original);

        $result = $handler->excerpt($content, 87);
        $this->assertSame($this->excerpt, (string) $result);
    }

    public function testExcerptNonContentClassObject()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $content = (new Snippet())->setCallback($this->original);

        $result = $handler->excerpt($content, 87);
        $this->assertSame($this->excerpt, (string) $result);
    }

    public function testExcerptArray()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $content = [
            'id'          => 42,
            'slug'        => 'clippy-inc',
            'datecreated' => null,
            'datechanged' => null,
            'username'    => 'clippy',
            'ownerid'     => null,
            'title'       => 'Attack of the Drop Bear',
            'contenttype' => 'koala',
            'status'      => 'published',
            'taxonomy'    => null,
            'body'        => $this->original,
        ];

        $result = $handler->excerpt($content, 87);
        $this->assertSame($this->excerpt, (string) $result);
    }

    public function testExcerptString()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $result = $handler->excerpt($this->original, 87);
        $this->assertSame($this->excerpt, $result);
    }

    public function testExcerptNull()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $result = $handler->excerpt(null);
        $this->assertSame('', $result);
    }

    public function testTrim()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $result = $handler->trim($this->original, 87);
        $this->assertSame($this->excerpt, $result);
    }

    public function testListTemplatesSafe()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $result = $handler->listTemplates('*.twig', true);
        $this->assertNull($result);
    }

    public function testListTemplatesAll()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect', null);
        $handler = new RecordHandler($app);

        $result = $handler->listTemplates(null, false);
        $this->assertArrayHasKey('entry.twig', $result);
        $this->assertArrayHasKey('extrafields.twig', $result);
        $this->assertArrayHasKey('index.twig', $result);
        $this->assertArrayHasKey('listing.twig', $result);
        $this->assertArrayHasKey('record.twig', $result);
    }

    public function testListTemplatesAllTwig()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect', null);
        $handler = new RecordHandler($app);

        $result = $handler->listTemplates('*.twig', false);
        $this->assertArrayHasKey('entry.twig', $result);
        $this->assertArrayHasKey('extrafields.twig', $result);
        $this->assertArrayHasKey('index.twig', $result);
        $this->assertArrayHasKey('listing.twig', $result);
        $this->assertArrayHasKey('record.twig', $result);
    }

    public function testListTemplatesLimitedTwig()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect', null);
        $handler = new RecordHandler($app);

        $result = $handler->listTemplates('e*.twig', false);
        $this->assertArrayHasKey('entry.twig', $result);
        $this->assertArrayHasKey('extrafields.twig', $result);
        $this->assertArrayNotHasKey('index.twig', $result);
        $this->assertArrayNotHasKey('listing.twig', $result);
        $this->assertArrayNotHasKey('record.twig', $result);
    }

    public function testListTemplatesTemplateSelect()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect/templates', [
            'koala' => [
                'name' => 'koala.twig',
                'filename' => 'koala.twig',
            ],
            'clippy' => [
                'name' => 'clippy.twig',
                'filename' => 'clippy.twig',
            ]
        ]);
        $handler = new RecordHandler($app);

        $result = $handler->listTemplates('*.twig', false);
        $this->assertArrayHasKey('koala.twig', $result);
        $this->assertArrayHasKey('clippy.twig', $result);

        $this->assertArrayNotHasKey('entry.twig', $result);
        $this->assertArrayNotHasKey('extrafields.twig', $result);
        $this->assertArrayNotHasKey('index.twig', $result);
        $this->assertArrayNotHasKey('listing.twig', $result);
        $this->assertArrayNotHasKey('record.twig', $result);
    }
}
