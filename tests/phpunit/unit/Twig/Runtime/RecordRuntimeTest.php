<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Asset\Snippet\Snippet;
use Bolt\Legacy\Content;
use Bolt\Pager\Pager;
use Bolt\Pager\PagerManager;
use Bolt\Storage\Repository;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\RecordRuntime;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test Bolt\Twig\Runtime\RecordRuntime.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordRuntimeTest extends BoltUnitTest
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
        $request = (new Request())->create('/');
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current(null);

        $this->assertFalse($result);
    }

    public function testCurrentHomeConfigured()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/clippy-inc');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => '',
            'contenttypeslug' => '',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current('/clippy-inc');
        $this->assertTrue($result);
    }

    public function testCurrentHomeMenu()
    {
        $app = $this->getApp();
        $app->flush();
        $app['config']->set('general/homepage', '/');
        $this->addDefaultUser($app);
        $request = (new Request())->create('/');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => '',
            'contenttypeslug' => '',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

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
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current('koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckArray()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current(['link' => '/pages/koala']);
        $this->assertTrue($result);
    }

    public function testCurrentNoMatchingSlugs()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current(['contenttype' => ['slug' => 'snails', 'slug' => 'singular_slug']]);
        $this->assertFalse($result);
    }

    public function testCurrentLinkToCheckContentObject()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);

        $handler = $this->getRecordRuntime();
        $content = $this->getMockBuilder(Content::class)
            ->setMethods(['link'])
            ->setConstructorArgs([$app])
            ->getMock()
        ;
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
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckStringWithQuery()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/pages/koala?page=42');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentLinkToCheckStringSeparateRoute()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentEmptyRecordSlugEmptyContentTypeSlug()
    {
        $this->resetDb();
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => null,
            'contenttypeslug' => null,
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        /** @var Repository $repo */
        $repo = $app['storage']->getRepository('pages');
        $content = $repo->find(1);

        $handler = $this->getRecordRuntime();

        $result = $handler->current($content);
        $this->assertFalse($result);
    }

    public function testCurrentContentTypeSlugContentSlug()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/pages/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'pages',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();

        $result = $handler->current('/pages/koala');
        $this->assertTrue($result);
    }

    public function testCurrentFalse()
    {
        $app = $this->getApp();
        $app->flush();
        $this->addDefaultUser($app);
        $request = (new Request())->create('/gum-tree/koala');
        $request->query->set('_route_params', [
            'zone'            => 'frontend',
            'slug'            => 'koala',
            'contenttypeslug' => 'gum-tree',
        ]);
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRecordRuntime();
        $content = ['slug' => 'clippy'];

        $result = $handler->current($content);
        $this->assertFalse($result);
    }

    public function testExcerptContentClassObject()
    {
        $app = $this->getApp();
        $handler = $this->getRecordRuntime();

        /** @var Content $content */
        $content = $app['storage']->getEmptyContent('pages');
        $content->setValue('body', $this->original);

        $result = $handler->excerpt($content, 87);
        $this->assertSame($this->excerpt, (string) $result);
    }

    public function testExcerptNonContentClassObject()
    {
        $handler = $this->getRecordRuntime();

        $content = (new Snippet())->setCallback($this->original);

        $result = $handler->excerpt($content, 87);
        $this->assertSame($this->excerpt, (string) $result);
    }

    public function testExcerptArray()
    {
        $handler = $this->getRecordRuntime();

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
        $handler = $this->getRecordRuntime();

        $result = $handler->excerpt($this->original, 87);
        $this->assertSame($this->excerpt, $result);
    }

    public function testExcerptNull()
    {
        $handler = $this->getRecordRuntime();

        $result = $handler->excerpt(null);
        $this->assertSame('', $result);
    }

    public function testExcerptContentClassObjectStripFields()
    {
        $app = $this->getApp();
        $handler = $this->getRecordRuntime();

        /** @var Content $content */
        $content = $app['storage']->getEmptyContent('pages');
        $content->setValue('body', 'This should not appear in the excerpt');

        $result = $handler->excerpt($content, 200, null, ['body']);
        $this->assertNotContains('This should not appear in the excerpt', (string) $result);
    }

    public function testExcerptArrayStripFields()
    {
        $handler = $this->getRecordRuntime();

        $content = [
            'dummy'       => 'This should not appear in the excerpt',
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

        $result = $handler->excerpt($content, 200, null, ['dummy']);
        $this->assertNotContains('This should not appear in the excerpt', (string) $result);
    }

    public function testListTemplatesAll()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect', null);
        $handler = $this->getRecordRuntime();

        $result = $handler->listTemplates(null);
        $this->assertArrayHasKey('index.twig', $result);
        $this->assertArrayHasKey('listing.twig', $result);
        $this->assertArrayHasKey('not-found.twig', $result);
        $this->assertArrayHasKey('page.twig', $result);
        $this->assertArrayHasKey('record.twig', $result);
        $this->assertArrayHasKey('search.twig', $result);
    }

    public function testListTemplatesAllTwig()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect', null);
        $handler = $this->getRecordRuntime();

        $result = $handler->listTemplates('*.twig');
        $this->assertArrayHasKey('index.twig', $result);
        $this->assertArrayHasKey('listing.twig', $result);
        $this->assertArrayHasKey('not-found.twig', $result);
        $this->assertArrayHasKey('page.twig', $result);
        $this->assertArrayHasKey('record.twig', $result);
        $this->assertArrayHasKey('search.twig', $result);
    }

    public function testListTemplatesLimitedTwig()
    {
        $app = $this->getApp();
        $app['config']->set('theme/templateselect', null);
        $handler = $this->getRecordRuntime();

        $result = $handler->listTemplates('s*.twig');
        $this->assertArrayHasKey('search.twig', $result);
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
                'filename' => 'index.twig',
            ],
            'clippy' => [
                'name'     => 'Clippy',
                'filename' => 'listing.twig',
            ],
        ]);
        $handler = $this->getRecordRuntime();

        $result = $handler->listTemplates('*i*.twig');
        $this->assertArrayHasKey('index.twig', $result);
        $this->assertContains('Koala', $result);
        $this->assertArrayHasKey('listing.twig', $result);
        $this->assertContains('Clippy', $result);

        $this->assertArrayNotHasKey('not-found.twig', $result);
        $this->assertArrayNotHasKey('page.twig', $result);
        $this->assertArrayNotHasKey('record.twig', $result);
        $this->assertArrayNotHasKey('search.twig', $result);
    }

    public function testPagerEmptyPager()
    {
        $app = $this->getApp();
        $pager = $this->getMockBuilder(PagerManager::class)
            ->setMethods(['isEmptyPager'])
            ->getMock()
        ;
        $pager
            ->expects($this->once())
            ->method('isEmptyPager')
            ->will($this->returnValue(true))
        ;
        $this->setService('pager', $pager);

        $handler = $this->getRecordRuntime();
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

        $pager = $this->getMockBuilder(Pager::class)
            ->getMock()
        ;
        $pager->for = $pagerName = 'Clippy';
        $pager->totalpages = $surr = 2;

        $manager = $this->getMockBuilder(PagerManager::class)
            ->setMethods(['isEmptyPager', 'getPager'])
            ->getMock()
        ;
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
        $this->setService('pager', $manager);

        $handler = $this->getRecordRuntime();
        $env = $app['twig'];
        $template = 'backend';
        $class = '';

        $result = $handler->pager($env, $pagerName, $surr, $template, $class);

        $this->assertRegExp('#<a href="1"#', (string) $result);
        $this->assertRegExp('#<a href="2"#', (string) $result);
    }

    public function testSelectFieldEmptyContentStartEmpty()
    {
        $handler = $this->getRecordRuntime();

        $result = $handler->selectField([], 'koala', true);
        $this->assertSame([], $result);
    }

    public function testSelectFieldEmptyContentStartFull()
    {
        $handler = $this->getRecordRuntime();

        $result = $handler->selectField([], 'koala', false);
        $this->assertSame(['' => ''], $result);
    }

    public function testSelectFieldContentFieldString()
    {
        $app = $this->getApp();
        $handler = $this->getRecordRuntime();
        /** @var Content $record1 */
        $record1 = $app['storage']->getEmptyContent('pages');
        /** @var Content $record2 */
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
        $handler = $this->getRecordRuntime();
        /** @var Content $record1 */
        $record1 = $app['storage']->getEmptyContent('pages');
        /** @var Content $record2 */
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
        $handler = $this->getRecordRuntime();
        /** @var Content $record1 */
        $record1 = $app['storage']->getEmptyContent('pages');
        /** @var Content $record2 */
        $record2 = $app['storage']->getEmptyContent('pages');

        $record1->setValues(['title' => 'Bruce', 'slug' => 'clippy', 'status' => 'published']);
        $record2->setValues(['title' => 'Johno', 'slug' => 'koala', 'status' => 'published']);

        $result = $handler->selectField([$record1, $record2], ['title', 'derp'], true, 'slug');
        $this->assertSame('Bruce', $result['clippy'][0]);
        $this->assertNull($result['clippy'][1]);
        $this->assertSame('Johno', $result['koala'][0]);
        $this->assertNull($result['koala'][1]);
    }

    /**
     * @return RecordRuntime
     */
    protected function getRecordRuntime()
    {
        $app = $this->getApp();

        return $app['twig.runtime.bolt_record'];
    }
}
