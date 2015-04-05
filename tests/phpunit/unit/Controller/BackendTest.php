<?php
namespace Bolt\Tests\Controller;

use Bolt\Configuration\ResourceManager;
use Bolt\Controllers\Backend;
use Bolt\Storage;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\LoripsumMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use  Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class to test correct operation of src/Controller/Backend.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/

class BackendTest extends BoltUnitTest
{
    public function testDashboard()
    {
        $this->resetDb();
        $app = $this->getApp();
        $this->addSomeContent();
        $twig = $this->getMockTwig();
        $phpunit = $this;
        $testHandler = function ($template, $context) use ($phpunit) {
            $phpunit->assertEquals('dashboard/dashboard.twig', $template);
            $phpunit->assertNotEmpty($context['context']);
            $phpunit->assertArrayHasKey('latest', $context['context']);
            $phpunit->assertArrayHasKey('suggestloripsum', $context['context']);

            return new Response();
        };

        $twig->expects($this->any())
            ->method('render')
            ->will($this->returnCallBack($testHandler));
        $this->allowLogin($app);
        $app['render'] = $twig;
        $request = Request::create('/bolt');
        $app->run($request);
    }

    public function testDbCheck()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('checkTablesIntegrity'), array($app));
        $check->expects($this->atLeastOnce())
            ->method('checkTablesIntegrity')
            ->will($this->returnValue(array('message', 'hint')));

        $app['integritychecker'] = $check;
        $request = Request::create('/bolt/dbcheck');
        $this->checkTwigForTemplate($app, 'dbcheck/dbcheck.twig');

        $app->run($request);
    }

    public function testDbUpdate()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $check = $this->getMock('Bolt\Database\IntegrityChecker', array('repairTables'), array($app));

        $check->expects($this->at(0))
            ->method('repairTables')
            ->will($this->returnValue(""));

        $check->expects($this->at(1))
            ->method('repairTables')
            ->will($this->returnValue("Testing"));

        $app['integritychecker'] = $check;
        ResourceManager::$theApp = $app;

        $request = Request::create('/bolt/dbupdate', 'POST', array('return' => 'edit'));
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/dbupdate', 'POST', array('return' => 'edit'));
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/file/edit/files/app/config/contenttypes.yml', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/dbupdate', "POST");
        $response = $app->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/bolt/dbupdate_result?messages=null', $response->getTargetUrl());
    }

    public function testDbUpdateResult()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $request = Request::create('/bolt/dbupdate_result');
        $this->checkTwigForTemplate($app, 'dbcheck/dbcheck.twig');

        $app->run($request);
    }

    public function testClearCache()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $cache = $this->getMock('Bolt\Cache', array('clearCache'), array(__DIR__, $app));
        $cache->expects($this->at(0))
            ->method('clearCache')
            ->will($this->returnValue(array('successfiles' => '1.txt', 'failedfiles' => '2.txt')));

        $cache->expects($this->at(1))
            ->method('clearCache')
            ->will($this->returnValue(array('successfiles' => '1.txt')));

        $app['cache'] = $cache;
        $request = Request::create('/bolt/clearcache');
        $this->checkTwigForTemplate($app, 'clearcache/clearcache.twig');
        $response = $app->handle($request);

        $this->assertNotEmpty($app['session']->getFlashBag()->get('error'));

        $request = Request::create('/bolt/clearcache');
        $this->checkTwigForTemplate($app, 'clearcache/clearcache.twig');
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));
    }

    public function testChangeLog()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $log = $this->getMock('Bolt\Logger\Manager', array('clear', 'trim'), array($app));
        $log->expects($this->once())
            ->method('clear')
            ->will($this->returnValue(true));

        $log->expects($this->once())
            ->method('trim')
            ->will($this->returnValue(true));

        $app['logger.manager'] = $log;

        ResourceManager::$theApp = $app;

        $request = Request::create('/bolt/changelog', 'GET', array('action' => 'trim'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/changelog', 'GET', array('action' => 'clear'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $this->assertEquals('/bolt/changelog', $response->getTargetUrl());

        $request = Request::create('/bolt/changelog');
        $this->checkTwigForTemplate($app, 'activity/changelog.twig');
        $app->run($request);
    }

    public function testSystemLog()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $log = $this->getMock('Bolt\Logger\Manager', array('clear', 'trim'), array($app));
        $log->expects($this->once())
            ->method('clear')
            ->will($this->returnValue(true));

        $log->expects($this->once())
            ->method('trim')
            ->will($this->returnValue(true));

        $app['logger.manager'] = $log;

        ResourceManager::$theApp = $app;

        $request = Request::create('/bolt/systemlog', 'GET', array('action' => 'trim'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $request = Request::create('/bolt/systemlog', 'GET', array('action' => 'clear'));
        $response = $app->handle($request);
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

        $this->assertEquals('/bolt/systemlog', $response->getTargetUrl());

        $request = Request::create('/bolt/systemlog');
        $this->checkTwigForTemplate($app, 'activity/systemlog.twig');
        $app->run($request);
    }

    public function testChangelogRecordAll()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $controller = new Backend();

        // First test tests without any changelogs available
        $app['request'] = $request = Request::create('/bolt/changelog/pages');
        $response = $controller->changelogRecordAll('pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals(0, count($context['context']['entries']));
        $this->assertNull($context['context']['content']);
        $this->assertEquals('Pages', $context['context']['title']);
        $this->assertEquals('pages', $context['context']['contenttype']['slug']);

        // Search for a specific record where the content object doesn't exist
        $app['request'] = $request = Request::create('/bolt/changelog/pages/1');
        $response = $controller->changelogRecordAll('pages', 200, $app, $request);
        $context = $response->getContext();
        $this->assertEquals("Page #200", $context['context']['title']);

        // This block generates a changelog on the page in question so we have something to test.
        $app['request'] = Request::create("/");
        $content = $app['storage']->getContent('pages/1');
        $content->setValues(array('status' => 'draft', 'ownerid' => 99));
        $app['storage']->saveContent($content, 'Test Suite Update');

        // Now handle all the other request variations
        $app['request'] = $request = Request::create('/bolt/changelog');
        $response = $controller->changelogRecordAll(null, null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('All content types', $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        $app['request'] = $request = Request::create('/bolt/changelog/pages');
        $response = $controller->changelogRecordAll('pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        $app['request'] = $request = Request::create('/bolt/changelog/pages/1');
        $response = $controller->changelogRecordAll('pages', '1', $app, $request);
        $context = $response->getContext();
        $this->assertEquals($content['title'], $context['context']['title']);
        $this->assertEquals(1, count($context['context']['entries']));
        $this->assertEquals(1, $context['context']['pagecount']);

        // Test pagination
        $app['request'] = $request = Request::create('/bolt/changelog/pages', 'GET', array('page' => 'all'));
        $response = $controller->changelogRecordAll('pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertNull($context['context']['currentpage']);
        $this->assertNull($context['context']['pagecount']);

        $app['request'] = $request = Request::create('/bolt/changelog/pages', 'GET', array('page' => '1'));
        $response = $controller->changelogRecordAll('pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals(1, $context['context']['currentpage']);

        // Finally we delete the original content record, but make sure the logs still show
        $originalTitle = $content['title'];
        $app['storage']->deleteContent('pages', 1);
        $app['request'] = $request = Request::create('/bolt/changelog/pages/1');
        $response = $controller->changelogRecordAll('pages', '1', $app, $request);
        $context = $response->getContext();
        $this->assertEquals($originalTitle, $context['context']['title']);
        // Note the delete generates an extra log, hence the extra count
        $this->assertEquals(2, count($context['context']['entries']));
    }

    public function testChangelogRecordSingle()
    {
        $app = $this->getApp();
        $app['config']->set('general/changelog/enabled', true);
        $controller = new Backend();

        $app['request'] = $request = Request::create('/bolt/changelog/pages/1/1');
        $response = $controller->changelogRecordSingle('pages', 1, 1, $app, $request);
        $context = $response->getContext();
        $this->assertInstanceOf('Bolt\Logger\ChangeLogItem', $context['context']['entry']);

        // Test non-existing entry
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'exist');
        $app['request'] = $request = Request::create('/bolt/changelog/pages/1/100');
        $response = $controller->changelogRecordSingle('pages', 1, 100, $app, $request);
        $context = $response->getContext();
    }

    public function testOmnisearch()
    {
        $app = $this->getApp();
        $this->allowLogin($app);

        $request = Request::create('/bolt/omnisearch', 'GET', array('q' => 'test'));
        $this->checkTwigForTemplate($app, 'omnisearch/omnisearch.twig');

        $app->run($request);
    }

    public function testPrefill()
    {
        $app = $this->getApp();
        $controller = new Backend();

        $app['request'] =  $request = Request::create('/bolt/prefill');
        $response = $controller->prefill($app, $request);
        $context = $response->getContext();
        $this->assertEquals(3, count($context['context']['contenttypes']));
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $context['context']['form']);

        // Test the post
        $app['request'] = $request = Request::create('/bolt/prefill', 'POST', array('contenttypes' => 'pages'));
        $response = $controller->prefill($app, $request);
        $this->assertEquals('/bolt/prefill', $response->getTargetUrl());

        // Test for the Exception if connection fails to the prefill service
        $store = $this->getMock('Bolt\Storage', array('preFill'), array($app));

        $this->markTestIncomplete(
            'Needs work.'
        );

        if ($app['deprecated.php']) {
            $store->expects($this->any())
                ->method('preFill')
                ->will($this->returnCallback(function () {
                    throw new \Guzzle\Http\Exception\RequestException();
            }));
        } else {
            $request = new \GuzzleHttp\Message\Request('GET', '');
            $store->expects($this->any())
                ->method('preFill')
                ->will($this->returnCallback(function () use ($request) {
                    throw new \GuzzleHttp\Exception\RequestException('', $request);
            }));
        }

        $app['storage'] = $store;

        $logger = $this->getMock('Monolog\Logger', array('error'), array('test'));
        $logger->expects($this->once())
            ->method('error')
            ->with("Timeout attempting to the 'Lorem Ipsum' generator. Unable to add dummy content.");
        $app['logger.system'] = $logger;

        $app['request'] = $request = Request::create('/bolt/prefill', 'POST', array('contenttypes' => 'pages'));
        $response = $controller->prefill($app, $request);
    }

    public function testOverview()
    {
        $app = $this->getApp();
        $controller = new Backend();

        $app['request'] = $request = Request::create('/bolt/overview/pages');
        $response = $controller->overview($app, 'pages');
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertGreaterThan(1, count($context['context']['multiplecontent']));

        // Test the the default records per page can be set
        $app['request'] = $request = Request::create('/bolt/overview/showcases');
        $response = $controller->overview($app, 'showcases');

        // Test redirect when user isn't allowed.
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        $users->expects($this->once())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/overview/pages');
        $response = $controller->overview($app, 'pages');
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }
    
    public function testOverviewFiltering()
    {
        $app = $this->getApp();
        $controller = new Backend();
        
        

        $app['request'] = $request = Request::create(
            '/bolt/overview/pages',
            'GET',
            array(
                'filter'=>'Lorem',
                'taxonomy-chapters'=>'main'
            )
        );
        $response = $controller->overview($app, 'pages');
        $context = $response->getContext();
        $this->assertArrayHasKey('filter', $context['context']);
        $this->assertEquals('Lorem', $context['context']['filter'][0]);
        $this->assertEquals('main', $context['context']['filter'][1]);
    }
    
    public function testRelatedTo()
    {
        $app = $this->getApp();
        $controller = new Backend();

        $app['request'] = $request = Request::create('/bolt/relatedto/showcases/1');
        $response = $controller->relatedTo('showcases', 1, $app, $request);
        $context = $response->getContext();
        $this->assertEquals(1, $context['context']['id']);
        $this->assertEquals('Showcase', $context['context']['name']);
        $this->assertEquals('Showcases', $context['context']['contenttype']['name']);
        $this->assertEquals(2, count($context['context']['relations']));
        // By default we show the first one
        $this->assertEquals('Entries', $context['context']['show_contenttype']['name']);

        // Now we specify we want to see pages
        $app['request'] = $request = Request::create('/bolt/relatedto/showcases/1', 'GET', array('show' => 'pages'));
        $response = $controller->relatedTo('showcases', 1, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['show_contenttype']['name']);

        // Try a request where there are no relations
        $app['request'] = $request = Request::create('/bolt/relatedto/pages/1');
        $response = $controller->relatedTo('pages', 1, $app, $request);
        $context = $response->getContext();
        $this->assertNull($context['context']['relations']);

        // Test redirect when user isn't allowed.
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        $users->expects($this->once())
            ->method('isAllowed')
            ->will($this->returnValue(false));
        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/relatedto/showcases/1');
        $response = $controller->relatedTo('showcases', 1, $app, $request);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }

    public function testEditContentGet()
    {
        $app = $this->getApp();
        $controller = new Backend();

        // First test will fail permission so we check we are kicked back to the dashboard
        $app['request'] = $request = Request::create('/bolt/editcontent/pages/4');
        $response = $controller->editContent('pages', 4, $app, $request);
        $this->assertEquals("/bolt", $response->getTargetUrl());

        // Since we're the test user we won't automatically have permission to edit.
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/pages/4');
        $response = $controller->editContent('pages', 4, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertInstanceOf('Bolt\Content', $context['context']['content']);

        // Test creation
        $app['request'] = $request = Request::create('/bolt/editcontent/pages');
        $response = $controller->editContent('pages', null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('Pages', $context['context']['contenttype']['name']);
        $this->assertInstanceOf('Bolt\Content', $context['context']['content']);
        $this->assertNull($context['context']['content']->id);

        // Test that non-existent throws a redirect
        $app['request'] = $request = Request::create('/bolt/editcontent/pages/310');
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'not-existing');
        $response = $controller->editContent('pages', 310, $app, $request);
    }

    public function testEditContentDuplicate()
    {
        $app = $this->getApp();
        $controller = new Backend();
        // Since we're the test user we won't automatically have permission to edit.
        $users = $this->getMock('Bolt\Users', array('isAllowed'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/pages/4', 'GET', array('duplicate' => true));
        $original = $app['storage']->getContent('pages/4');
        $response = $controller->editContent('pages', 4, $app, $request);
        $context = $response->getContext();

        // Check that correct fields are equal in new object
        $new = $context['context']['content'];
        $this->assertEquals($new['body'], $original['body']);
        $this->assertEquals($new['title'], $original['title']);
        $this->assertEquals($new['teaser'], $original['teaser']);

        // Check that some have been cleared.
        $this->assertEquals('', $new['id']);
        $this->assertEquals('', $new['slug']);
        $this->assertEquals('', $new['ownerid']);
    }

    public function testEditContentCSRF()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(false));

        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/showcases/3', 'POST');
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', 'Something went wrong');
        $response = $controller->editContent('showcases', 3, $app, $request);
    }

    public function testEditContentPermissions()
    {
        $app = $this->getApp();

        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->at(0))
            ->method('isAllowed')
            ->will($this->returnValue(true));

        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));

        $app['users'] = $users;

        // We should get kicked here because we dont have permissions to edit this
        $controller = new Backend();
        $app['request'] = $request = Request::create('/bolt/editcontent/showcases/3', 'POST');
        $response = $controller->editContent('showcases', 3, $app, $request);
        $this->assertEquals("/bolt", $response->getTargetUrl());
    }

    public function testEditContentPost()
    {
        $app = $this->getApp();
        $controller = new Backend();

        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));

        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/showcases/3', 'POST', array('floatfield' => 1.2));
        $original = $app['storage']->getContent('showcases/3');
        $response = $controller->editContent('showcases', 3, $app, $request);
        $this->assertEquals('/bolt/overview/showcases', $response->getTargetUrl());
    }

    public function testEditContentPostAjax()
    {
        $app = $this->getApp();
        $controller = new Backend();

        // Since we're the test user we won't automatically have permission to edit.
        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));

        $app['users'] = $users;

        $app['request'] = $request = Request::create('/bolt/editcontent/pages/4?returnto=ajax', 'POST');
        $original = $app['storage']->getContent('pages/4');
        $response = $controller->editContent('pages', 4, $app, $request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $returned = json_decode($response->getContent());
        $this->assertEquals($original['title'], $returned->title);
    }
    
    public function testDeleteContent()
    {
        $app = $this->getApp();
        $controller = new Backend();
        
        $app['request'] = $request = Request::create('/bolt/deletecontent/pages/4');
        $response = $controller->deleteContent($app, 'pages', 4);
        // This one should fail for permissions
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/denied/', $err[0]);
        
        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));        
        $app['users'] = $users;
        
        // This one should get killed by the anti CSRF check
        $response = $controller->deleteContent($app, 'pages', 4);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be deleted/', $err[0]);
        
        $app['users']->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true)); 

        $response = $controller->deleteContent($app, 'pages', 4);
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/has been deleted/', $err[0]);
    }
    
    public function testContentAction()
    {
        // Try status switches
        $app = $this->getApp();
        $controller = new Backend();
        
        $app['request'] = $request = Request::create('/bolt/content/held/pages/3');
        
        // This one should fail for lack of permission
        $response = $controller->contentAction($app, 'held','pages', 3);        
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/right privileges/', $err[0]);
        
        
        $users = $this->getMock('Bolt\Users', array('isAllowed', 'checkAntiCSRFToken', 'isContentStatusTransitionAllowed'), array($app));
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));        
        $app['users'] = $users;
        
        // This one should fail for the second permission check `isContentStatusTransitionAllowed`
        $response = $controller->contentAction($app, 'held','pages', 3);        
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/right privileges/', $err[0]);
        
        $app['users']->expects($this->any())
            ->method('isContentStatusTransitionAllowed')
            ->will($this->returnValue(true)); 
            
        $response = $controller->contentAction($app, 'held','pages', 3);        
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/has been changed/', $err[0]);
        
        // Test an invalid action fails
        $app['request'] = $request = Request::create('/bolt/content/fake/pages/3');
        $response = $controller->contentAction($app, 'fake','pages', 3);        
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/No such action/', $err[0]);
        
        // Test that any save error gets reported
        $app['request'] = $request = Request::create('/bolt/content/held/pages/3');
        
        $storage = $this->getMock('Bolt\Storage', array('updateSingleValue'), array($app));
        $storage->expects($this->once())
            ->method('updateSingleValue')
            ->will($this->returnValue(false));
            
        $app['storage'] = $storage;
        
        $response = $controller->contentAction($app, 'held','pages', 3);        
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be modified/', $err[0]);
        
        // Test the delete proxy action
        // Note that the response will be 'could not be deleted'. Since this just
        // passes on the the deleteContent method that is enough to indicate that
        // the work of this method is done. 
        $app['request'] = $request = Request::create('/bolt/content/delete/pages/3');
        $response = $controller->contentAction($app, 'delete','pages', 3);        
        $this->assertEquals('/bolt/overview/pages', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be deleted/', $err[0]);
    }
    
    public function testUsers()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $app['request'] = $request = Request::create('/bolt/users');
        $response = $controller->users($app);
        $context = $response->getContext();
        $this->assertNotNull($context['context']['users']);        
        $this->assertNotNull($context['context']['sessions']);        

    }
    
    public function testRoles()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $app['request'] = $request = Request::create('/bolt/roles');
        $response = $controller->roles($app);
        $context = $response->getContext();
        $this->assertEquals('roles/roles.twig', $response->getTemplateName());
        $this->assertNotEmpty($context['context']['global_permissions']);
        $this->assertNotEmpty($context['context']['effective_permissions']);
    }
    
    public function testUserEdit()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $user = $app['users']->getUser(9);
        $app['users']->currentuser = $user;
        $app['request'] = $request = Request::create('/bolt/useredit/9');
        
        // This one should redirect because of permission failure
        $response = $controller->userEdit(9, $app, $request);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        
        
        // Now we allow the permsission check to return true
        $perms = $this->getMock('Bolt\Permissions', array('isAllowedToManipulate'), array($app));
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $app['permissions'] = $perms;
        
        $response = $controller->userEdit(9, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('edit', $context['context']['kind']);
        $this->assertInstanceOf('Symfony\Component\Form\FormView', $context['context']['form']);
        $this->assertEquals('Admin', $context['context']['displayname']);
        
        // Test that an empty user gives a create form
        $app['request'] = $request = Request::create('/bolt/useredit');
        $response = $controller->userEdit(null, $app, $request);
        $context = $response->getContext();
        $this->assertEquals('create', $context['context']['kind']);

    }
    
    public function testUserEditPost()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $user = $app['users']->getUser(9);
        $app['users']->currentuser = $user;
        
        
        $perms = $this->getMock('Bolt\Permissions', array('isAllowedToManipulate'), array($app));
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $app['permissions'] = $perms;
        
        // Symfony forms normally need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('secret')); 
        $csrf->expects($this->once())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));
        $app['form.csrf_provider'] = $csrf;
        
        // Update the display name via a POST request
        $app['request'] = $request = Request::create(
            '/bolt/useredit/9', 
            'POST', 
            array(
                'form'=> array(
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'displayname' => "Admin Test", 
                    '_token' => 'xyz'
                )
            )
        );
        
        $response = $controller->userEdit(9, $app, $request);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
    }
    
    public function testUsernameEditKillsSession()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $user = $app['users']->getUser(9);

        $app['users']->currentuser = $user;
        
        $perms = $this->getMock('Bolt\Permissions', array('isAllowedToManipulate'), array($app));
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(true));
        $app['permissions'] = $perms;
        
        // Symfony forms normally need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('secret')); 
        $csrf->expects($this->once())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));
        $app['form.csrf_provider'] = $csrf;
        
        // Update the display name via a POST request
        $app['request'] = $request = Request::create(
            '/bolt/useredit/9', 
            'POST', 
            array(
                'form'=> array(
                    'id' => $user['id'],
                    'username' => 'admin2',
                    'email' => $user['email'],
                    'displayname' => $user['displayname'], 
                    '_token' => 'xyz'
                )
            )
        );
        $response = $controller->userEdit(9, $app, $request);
        $this->assertEquals('/bolt/login', $response->getTargetUrl());
    }

    public function testUserFirst()
    {
        $app = $this->getApp();
        $controller = new Backend();
        
        // Symfony forms need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('secret')); 
        $csrf->expects($this->any())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));
            
        $csrf->expects($this->any())
            ->method('generateCsrfToken')
            ->will($this->returnValue('xyz'));
            
        $app['form.csrf_provider'] = $csrf;
        
        // Because we have users in the database this should exit at first attempt
        $app['request'] = $request = Request::create('/bolt/userfirst');
        $response = $controller->userFirst($app, $request);
        $this->assertEquals('/bolt', $response->getTargetUrl());

        // Now we delete the users
        $res = $app['db']->executeQuery('DELETE FROM bolt_users;');
        $app['users']->users = array();
        
        $app['request'] = $request = Request::create('/bolt/userfirst');
        $response = $controller->userFirst($app, $request);
        $context = $response->getContext();
        $this->assertEquals('create', $context['context']['kind']);
        
        
        // This block attempts to create the user
        
        
        $app['request'] = $request = Request::create(
            '/bolt/userfirst', 
            'POST', 
            array(
                'form'=> array(
                    'username' => 'admin',
                    'email' => 'test@example.com',
                    'displayname' => 'Admin',
                    'password'=> 'password',
                    'password_confirmation'=>'password',
                    '_token' => 'xyz'
                )
            )
        );
        $response = $controller->userFirst($app, $request);
        $this->assertEquals('/bolt', $response->getTargetUrl());
    }
    
    public function testProfile()
    {
        $this->addSomeContent();
        $app = $this->getApp();
        $controller = new Backend();
        
        
         // Symfony forms need a CSRF token so we have to mock this too
        $this->removeCSRF($app);
        
        $user = $app['users']->getUser(2);
        $app['users']->currentuser = $user;
        $app['request'] = $request = Request::create('/bolt/profile');
        $response = $controller->profile($app, $request);
        $context = $response->getContext();
        $this->assertEquals('edituser/edituser.twig', $response->getTemplateName());
        $this->assertEquals('profile', $context['context']['kind']);
        
        
        // Now try a POST to update the profile
        $app['request'] = $request = Request::create(
            '/bolt/profile', 
            'POST', 
            array(
                'form'=> array(
                    'id' => 2,
                    'email' => $user['email'],
                    'password' => '',
                    'password_confirmation' => '',
                    'displayname' => "Admin Test", 
                    '_token' => 'xyz'
                )
            )
        );
        
        
        $response = $controller->profile($app, $request);
        $this->assertEquals('/bolt/profile', $response->getTargetUrl());
        $this->assertNotEmpty($app['session']->getFlashBag()->get('success'));

    }
    
    public function testAbout()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $app['request'] = $request = Request::create('/bolt/about');
        $response = $controller->about($app);
        $this->assertEquals('about/about.twig', $response->getTemplateName());
    }
    
    public function testFiles()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $this->removeCSRF($app);
        $app['request'] = $request = Request::create('/bolt/files');
        $response = $controller->files('files', '', $app, $request);
        $context = $response->getContext();
        $this->assertEquals('', $context['context']['path']);
        $this->assertEquals('files', $context['context']['namespace']);
        $this->assertEquals(array(), $context['context']['files']);
        
        // Try and upload a file
        
        $perms = $this->getMock('Bolt\Filesystem\FilePermissions', array('allowedUpload'), array($app));
        $perms->expects($this->any())
            ->method('allowedUpload')
            ->will($this->returnValue(true));
        $app['filepermissions'] = $perms;
        
        
        $app['request'] = $request = Request::create(
            '/upload/files',
            'POST',
            array(),
            array(),
            array(
                'form' => array(
                    'FileUpload' => array(
                        new UploadedFile(
                            PHPUNIT_ROOT . '/resources/generic-logo-evil.exe',
                            'logo.exe'
                        )
                    ),
                    '_token'     => 'xyz'
                )
            )
        );
        
        $response = $controller->files('files', '', $app, $request);        
    }
    
    public function testUserAction()
    {
        $app = $this->getApp();
        $controller = new Backend();
        
        // First test should exit/redirect with no anti CSRF token
        $app['request'] = $request = Request::create('/bolt/user/disable/2');
        $response = $controller->userAction($app, 'disable', 1);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/An error occurred/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        
        
        
        $app = $this->getApp();
        $controller = new Backend();
        
        // Now we mock the CSRF token to validate
        $users = $this->getMock('Bolt\Users', array('checkAntiCSRFToken'), array($app));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
        $app['users'] = $users;
        
        // This request should fail because the user doesnt exist.
        $app['request'] = $request = Request::create('/bolt/user/disable/2');
        $response = $controller->userAction($app, 'disable', 1);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/No such user/', $err[0]);
        
        // This check will fail because we are operating on the current user
        $user = $app['users']->getUser(2);
        $app['users']->currentuser = $user;
        $app['request'] = $request = Request::create('/bolt/user/disable/2');
        $response = $controller->userAction($app, 'disable', 2);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/yourself/', $err[0]);

        // We add a new user that isn't the current user and now perform operations.        
        $this->addNewUser($app, 'editor', 'Editor', 'editor');
        
        
        
           

        // And retry the operation that will work now
        $app['request'] = $request = Request::create('/bolt/user/disable/3');
        $response = $controller->userAction($app, 'disable', 3);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/is disabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());        
        
        // Now try to enable the user
        $app['request'] = $request = Request::create('/bolt/user/enable/3');
        $response = $controller->userAction($app, 'enable', 3);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/is enabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        
        // Try a non-existent action, make sure we get an error
        $app['request'] = $request = Request::create('/bolt/user/enhance/3');
        $response = $controller->userAction($app, 'enhance', 3);
        $info = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/No such action/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        
        
        // Now we run a delete action
        $app['request'] = $request = Request::create('/bolt/user/delete/3');
        $response = $controller->userAction($app, 'delete', 3);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/is deleted/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        
        // Finally we mock the permsission check to return false and check
        // we get a priileges error.
        $perms = $this->getMock('Bolt\Permissions', array('isAllowedToManipulate'), array($app));
        $perms->expects($this->any())
            ->method('isAllowedToManipulate')
            ->will($this->returnValue(false));
        $app['permissions'] = $perms;  
        
        $app['request'] = $request = Request::create('/bolt/user/disable/3');
        $response = $controller->userAction($app, 'disable', 3);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        $err = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/right privileges/', $err[0]);   
    }
    
    public function testUserActionFailures()
    {
        
        $app = $this->getApp();
        $controller = new Backend();
        
        // We add a new user that isn't the current user and now perform operations.        
        $this->addNewUser($app, 'editor', 'Editor', 'editor');
        
        // Now we mock the CSRF token to validate
        $users = $this->getMock('Bolt\Users', array('checkAntiCSRFToken', 'setEnabled', 'deleteUser'), array($app));
        $users->expects($this->any())
            ->method('checkAntiCSRFToken')
            ->will($this->returnValue(true));
            
        $users->expects($this->any())
            ->method('setEnabled')
            ->will($this->returnValue(false));
            
        $users->expects($this->any())
            ->method('deleteUser')
            ->will($this->returnValue(false));
            
        $app['users'] = $users;
        
        
        // Setup the current user
        $user = $app['users']->getUser(2);
        $app['users']->currentuser = $user;
        
        // This mocks a failure and ensures the error is reported
        $app['request'] = $request = Request::create('/bolt/user/disable/3');
        $response = $controller->userAction($app, 'disable', 3);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be disabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());  
        
        $app['request'] = $request = Request::create('/bolt/user/enable/3');
        $response = $controller->userAction($app, 'enable', 3);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be enabled/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());
        
        $app['request'] = $request = Request::create('/bolt/user/delete/3');
        $response = $controller->userAction($app, 'delete', 3);
        $info = $app['session']->getFlashBag()->get('info');
        $this->assertRegexp('/could not be deleted/', $info[0]);
        $this->assertEquals('/bolt/users', $response->getTargetUrl());  
    }
    
    
    public function testFileEdit()
    {
        $app = $this->getApp();
        $controller = new Backend();
        $app['request'] = $request = Request::create('/bolt/file/edit/config/config.yml');
        $response = $controller->fileedit('config', 'config.yml', $app, $request);
        $this->assertEquals('editfile/editfile.twig', $response->getTemplateName());
        
    }
    
    public function testTranslation()
    {
        // We make a new translation and ensure that the content is created.
        $app = $this->getApp();
        $controller = new Backend();
        $this->removeCSRF($app);
        $app['request'] = $request = Request::create('/bolt/tr/contenttypes/en_CY');
        $response = $controller->translation('contenttypes', 'en_CY', $app, $request);
        $context = $response->getContext();
        $this->assertEquals('contenttypes.en_CY.yml', $context['context']['basename']);
        
        // Now try and post the update
        $app['request'] = $request = Request::create(
            '/bolt/tr/contenttypes/en_CY',
            'POST',
            array(
                'form'=>array(
                    'contents' => 'test content at least 10 chars',
                    '_token' => 'xyz' 
                )
            )
        );
        $response = $controller->translation('contenttypes', 'en_CY', $app, $request);
        $context = $response->getContext();
        $this->assertEquals('editlocale/editlocale.twig', $response->getTemplateName());

        // Write isn't allowed initially so check the error
        $error = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/is not writable/', $error[0]);
        
        // Check that YML parse errors get caught
        $app['request'] = $request = Request::create(
            '/bolt/tr/contenttypes/en_CY',
            'POST',
            array(
                'form'=>array(
                    'contents' => "- this is invalid yaml markup: *thisref",
                    '_token' => 'xyz' 
                )
            )
        );
        $response = $controller->translation('contenttypes', 'en_CY', $app, $request);
        $info = $app['session']->getFlashBag()->get('error');
        $this->assertRegexp('/could not be saved/', $info[0]);
    }
    
    protected function addNewUser($app, $username, $displayname, $role) 
    {
        $user = array(
            'username'=>$username,
            'displayname' => $displayname,
            'email' => 'test@example.com',
            'password' => 'password',
            'roles' => array($role)
        );
        $app['users']->saveUser($user);
        $app['users']->users = array();
    }

    
    protected function removeCSRF($app) 
    {
         // Symfony forms need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('form')); 
        $csrf->expects($this->any())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));
            
        $csrf->expects($this->any())
            ->method('generateCsrfToken')
            ->will($this->returnValue('xyz'));
            
        $app['form.csrf_provider'] = $csrf;
    }
    
    
    protected function addSomeContent()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['config']->set('taxonomy/categories/options', array('news'));
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(array('showcases', 'pages'));
    }
}
