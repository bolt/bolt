<?php

namespace Bolt\Tests\Twig;

use Bolt\Asset\Snippet\Snippet;
use Bolt\Legacy\Content;
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
Along the desolate shore. The die was …
GRINGALET;

    /**
     * Empty route and content.
     */
    public function testCurrentEmptyParameter()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/');
        $app['request'] = $request;
        $handler = new RecordHandler($app);

        $result = $handler->current(null);

        $this->assertFalse($result);
    }

    public function testCurrentHomeConfigured()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/clippy-inc');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => '',
            'contenttypeslug' => '',
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/clippy-inc');
        $this->assertTrue($result);
    }

    public function testCurrentHomeMenu()
    {
        $app = $this->getApp();
        $app->flush();
        $app['config']->set('general/homepage', '/');
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => '',
            'contenttypeslug' => '',
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
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckArray()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current(['link' => '/pages/koala']);
        $this->assertTrue($result);
    }

    public function testCurrentNoMatchingSlugs()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current(['contenttype' => ['slug' => 'snails', 'slug' => 'singular_slug']]);
        $this->assertFalse($result);
    }

    public function testCurrentLinkToCheckContentObject()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);
        $content = new Content($app, 'pages', ['id' => 42, 'slug' => 'koala']);

        $result = $handler->current($content);
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckString()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckStringWithQuery()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala?page=42');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckStringSeparateRoute()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
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
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => null,
            'contenttypeslug' => null,
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
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;

        $handler = new RecordHandler($app);

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentFalse()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $request = (new Request())->create('/gum-tree/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'gum-tree',
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
        $this->assertArrayHasKey('page.twig', $result);
        $this->assertArrayHasKey('extrafields.twig', $result);
        $this->assertArrayHasKey('index.twig', $result);
        $this->assertArrayHasKey('listing.twig', $result);
        $this->assertArrayHasKey('record.twig', $result);
        $this->assertArrayHasKey('notfound.twig', $result);
        $this->assertArrayHasKey('search.twig', $result);
        $this->assertArrayHasKey('styleguide.twig', $result);
    }

    public function testListTemplatesAllTwig()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect', null);
        $handler = new RecordHandler($app);

        $result = $handler->listTemplates('*.twig', false);
        $this->assertArrayHasKey('page.twig', $result);
        $this->assertArrayHasKey('extrafields.twig', $result);
        $this->assertArrayHasKey('index.twig', $result);
        $this->assertArrayHasKey('listing.twig', $result);
        $this->assertArrayHasKey('record.twig', $result);
        $this->assertArrayHasKey('notfound.twig', $result);
        $this->assertArrayHasKey('search.twig', $result);
        $this->assertArrayHasKey('styleguide.twig', $result);
    }

    public function testListTemplatesLimitedTwig()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect', null);
        $handler = new RecordHandler($app);

        $result = $handler->listTemplates('s*.twig', false);
        $this->assertArrayHasKey('search.twig', $result);
        $this->assertArrayHasKey('styleguide.twig', $result);
        $this->assertArrayNotHasKey('index.twig', $result);
        $this->assertArrayNotHasKey('listing.twig', $result);
        $this->assertArrayNotHasKey('record.twig', $result);
    }

    public function testListTemplatesTemplateSelect()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect/templates', [
            'koala' => [
                'name'     => 'Koala',
                'filename' => 'extrafields.twig',
            ],
            'clippy' => [
                'name'     => 'Clippy',
                'filename' => 'styleguide.twig',
            ],
        ]);
        $handler = new RecordHandler($app);

        $result = $handler->listTemplates('*extra*', false);
        $this->assertArrayHasKey('extrafields.twig', $result);
        $this->assertContains('Koala', $result);

        $result = $handler->listTemplates('*styleguide*', false);
        $this->assertArrayHasKey('styleguide.twig', $result);
        $this->assertContains('Clippy', $result);

        $this->assertArrayNotHasKey('entry.twig', $result);
        $this->assertArrayNotHasKey('index.twig', $result);
        $this->assertArrayNotHasKey('listing.twig', $result);
        $this->assertArrayNotHasKey('record.twig', $result);
    }

    public function testPagerEmptyPager()
    {
        $app = $this->getApp();

        $pager = $this->getMock('\Bolt\Pager\PagerManager', ['isEmptyPager'], []);
        $pager
            ->expects($this->once())
            ->method('isEmptyPager')
            ->will($this->returnValue(true))
        ;
        $app['pager'] = $pager;

        $handler = new RecordHandler($app);
        $env = $app['twig'];
        $pagerName = 'Clippy';
        $surr = 4;
        $template = '_sub_pager.twig';
        $class = '';

        $result = $handler->pager($env, $pagerName, $surr, $template, $class);
        $this->assertSame('', $result);
    }

    public function testPager()
    {
        $app = $this->getApp();

        $manager = $this->getMock('\Bolt\Pager\PagerManager', ['isEmptyPager', 'getPager'], []);

        $pager = $this->getMock('\Bolt\Pager\Pager');
        $pager->for = $pagerName = 'Clippy';
        $pager->totalpages = $surr = 2;

        $manager
            ->expects($this->atLeastOnce())
            ->method('isEmptyPager')
            ->will($this->returnValue(false))
        ;
        $manager
            ->expects($this->atLeastOnce())
            ->method('getPager')
            ->will($this->returnValue($pager))
        ;
        $app['pager'] = $manager;

        $handler = new RecordHandler($app);
        $env = $app['twig'];
        $template = 'backend';
        $class = '';

        $result = $handler->pager($env, $pagerName, $surr, $template, $class);
        $this->assertInstanceOf('\Twig_Markup', $result);

        $this->assertRegExp('#<li ><a href="1">1</a></li>#', (string) $result);
        $this->assertRegExp('#<li ><a href="2">2</a></li>#', (string) $result);
    }

    public function testSelectFieldEmptyContentStartEmpty()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $result = $handler->selectField([], 'koala', true);
        $this->assertSame([], $result);
    }

    public function testSelectFieldEmptyContentStartFull()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);

        $result = $handler->selectField([], 'koala', false);
        $this->assertSame(['' => ''], $result);
    }

    public function testSelectFieldContentFieldString()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);
        $record1 = $app['storage']->getEmptyContent('pages');
        $record2 = $app['storage']->getEmptyContent('pages');

        $record1->setValues(['title' => 'Bruce', 'slug' => 'clippy', 'status' => 'published']);
        $record2->setValues(['title' => 'Johno', 'slug' => 'koala', 'status' => 'published']);

        $result = $handler->selectField([$record1, $record2], 'title', true, 'slug');
        $this->assertSame('Bruce', $result['clippy']);
        $this->assertSame('Johno', $result['koala']);
    }

    public function testSelectFieldContentFieldArray()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);
        $record1 = $app['storage']->getEmptyContent('pages');
        $record2 = $app['storage']->getEmptyContent('pages');

        $record1->setValues(['title' => 'Bruce', 'slug' => 'clippy', 'status' => 'published']);
        $record2->setValues(['title' => 'Johno', 'slug' => 'koala', 'status' => 'published']);

        $result = $handler->selectField([$record1, $record2], ['title'], true, 'slug');
        $this->assertSame('Bruce', $result['clippy'][0]);
        $this->assertSame('Johno', $result['koala'][0]);
    }

    public function testSelectFieldContentFieldArrayDerpy()
    {
        $app = $this->getApp();
        $handler = new RecordHandler($app);
        $record1 = $app['storage']->getEmptyContent('pages');
        $record2 = $app['storage']->getEmptyContent('pages');

        $record1->setValues(['title' => 'Bruce', 'slug' => 'clippy', 'status' => 'published']);
        $record2->setValues(['title' => 'Johno', 'slug' => 'koala', 'status' => 'published']);

        $result = $handler->selectField([$record1, $record2], ['title', 'derp'], true, 'slug');
        $this->assertSame('Bruce', $result['clippy'][0]);
        $this->assertNull($result['clippy'][1]);
        $this->assertSame('Johno', $result['koala'][0]);
        $this->assertNull($result['koala'][1]);
    }
}
