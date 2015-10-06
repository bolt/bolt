<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Controller\Zone;
use Bolt\Response\BoltResponse;
use Bolt\Tests\Controller\ControllerUnitTest;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class to test correct operation of src/Controller/Async/General.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class GeneralTest extends ControllerUnitTest
{
    /**
     * @covers \Bolt\Controller\Zone::get
     * @covers \Bolt\Controller\Zone::isAsync
     */
    public function testControllerZone()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $this->setRequest(Request::create('/async'));
        $request = $this->getRequest();

        $kernel = $this->getMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');
        $app['dispatcher']->dispatch(KernelEvents::REQUEST, new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertEquals('async', Zone::get($request));
        $this->assertTrue(Zone::isAsync($request));
    }

    public function testAsyncBaseRoute()
    {
        $app = $this->getApp();
        $this->allowLogin($app);
        $this->setRequest(Request::create('/async'));

        $response = $this->controller()->async();

        $this->assertJson($response->getContent());
        $this->assertEquals('["OK"]', $response->getContent());
    }

    public function testChangeLogRecord()
    {
        $this->setRequest(Request::create('/async/changelog/page/1'));

        $response = $this->controller()->changeLogRecord('pages', 1);

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('@bolt/components/panel-change-record.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDashboardNewsWithInvalidRequest()
    {
        $this->setRequest(Request::create('/async/dashboardnews'));
        $app = $this->getApp();
        $testGuzzle = $this->getMock('GuzzleHttp\Client', ['get'], []);

        $guzzleInterface = $this->getMock('GuzzleHttp\Message\RequestInterface');
        $testGuzzle->expects($this->at(0))->method("get")->will($this->throwException(new RequestException('Mock Fail', $guzzleInterface)));
        $app['guzzle.client'] = $testGuzzle;

        $changeRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogChange');
        $systemRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogSystem');
        $logger = $this->getMock('Bolt\Logger\Manager', ['info', 'critical'], [$app, $changeRepository, $systemRepository]);

        $logger->expects($this->at(1))
            ->method('critical')
            ->with($this->stringContains('Error occurred'));
        $app['logger.system'] = $logger;
        $response = $this->controller()->dashboardNews($this->getRequest());
    }

    public function testDashboardNewsWithInvalidJson()
    {
        $this->setRequest(Request::create('/async/dashboardnews'));
        $app = $this->getApp();
        $testGuzzle = $this->getMock('GuzzleHttp\Client', ['get'], []);
        $testRequest = $this->getMock('GuzzleHttp\Message', ['getBody']);
        $testRequest->expects($this->any())
                    ->method('getBody')
                    ->will($this->returnValue('invalidstring'));
        $testGuzzle->expects($this->any())
                    ->method('get')
                    ->will($this->returnValue($testRequest));
        $app['guzzle.client'] = $testGuzzle;

        $changeRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogChange');
        $systemRepository = $this->getService('storage')->getRepository('Bolt\Storage\Entity\LogSystem');
        $logger = $this->getMock('Bolt\Logger\Manager', ['info', 'error'], [$app, $changeRepository, $systemRepository]);

        $logger->expects($this->at(1))
            ->method('error')
            ->with($this->stringContains('Invalid JSON'));
        $app['logger.system'] = $logger;
        $response = $this->controller()->dashboardNews($this->getRequest());
    }

    public function testDashboardNewsWithVariable()
    {
        $app = $this->getApp();
        $app['cache']->clearCache();
        $this->setRequest(Request::create('/async/dashboardnews'));
        $app['config']->set('general/branding/news_variable', 'testing');

        $testGuzzle = $this->getMock('GuzzleHttp\Client', ['get'], []);
        $testRequest = $this->getMock('GuzzleHttp\Message', ['getBody']);
        $testRequest->expects($this->any())
                    ->method('getBody')
                    ->will($this->returnValue('{"testing":[{"item":"one"},{"item":"two"},{"item":"three"}]}'));
        $testGuzzle->expects($this->any())
                    ->method('get')
                    ->will($this->returnValue($testRequest));
        $app['guzzle.client'] = $testGuzzle;

        $response = $this->controller()->dashboardNews($this->getRequest());

        $context = $response->getContext();
        $this->assertEquals(['item' => 'one'], (array)$context['context']['information']);
    }

    public function testDashboardNews()
    {
        $this->setRequest(Request::create('/async/dashboardnews'));

        $response = $this->controller()->dashboardNews($this->getRequest());
        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('@bolt/components/panel-news.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLastModified()
    {
        $this->setRequest(Request::create('/async/lastmodified/page/1'));

        $response = $this->controller()->lastModified('page', 1);

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('@bolt/components/panel-lastmodified.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLatestactivity()
    {
        $this->setRequest(Request::create('/async/latestactivity'));

        $response = $this->controller()->latestActivity($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('@bolt/components/panel-activity.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \Bolt\Storage::getUri
     * @covers \Bolt\Controller\Async\General::makeUri
     */
    public function testMakeUri()
    {
        // Set up a fake request for getContent()'s sake
        $this->setRequest(Request::create('/'));
        $record = $this->getService('storage')->getContent('pages/1');
        $this->setRequest(Request::create('/async/makeuri', 'GET', [
            'title'           => $record->values['title'],
            'id'              => $record->values['id'],
            'contenttypeslug' => 'pages',
            'fulluri'         => true,
        ]));

        $response = $this->controller()->makeUri($this->getRequest());

        $this->assertSame('/page/' . $record->values['slug'], $response);
    }

    public function testOmnisearch()
    {
        $this->setRequest(Request::create('/async/omnisearch', 'GET', [
            'q' => 'sho'
        ]));

        $response = $this->controller()->omnisearch($this->getRequest());

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $json = json_decode($response->getContent());

        $this->assertSame('Omnisearch', $json[0]->label);
        $this->assertSame('New Showcase', $json[1]->label);
        $this->assertSame('View Showcases', $json[2]->label);
    }

    public function testPopularTags()
    {
        $this->setRequest(Request::create('/async/populartags'));

        $response = $this->controller()->popularTags($this->getRequest(), 'tags');

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $json = json_decode($response->getContent());
        $tags = $this->getDefaultTags();

        $this->assertCount(20, $json);
        $this->assertTrue(in_array($json[0]->name, $tags));
    }

    public function testReadme()
    {
    }

    public function testTags()
    {
        //         $this->setRequest(Request::create('/async/tags/tags'));
//         $response = $this->controller()->tags($this->getRequest(), 'tags');

//         $this->assertTrue($response instanceof JsonResponse);
//         $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

//         $json = json_decode($response->getContent());
//         $tags = $this->getDefaultTags();

//         $this->assertCount(20, $json);
//         $this->assertTrue(in_array($json[0]->name, $tags));
    }

    public function testWidget()
    {
    }

    /**
     * @return \Bolt\Controller\Async\General
     */
    protected function controller()
    {
        return $this->getService('controller.async.general');
    }

    private function getDefaultTags()
    {
        return ['action', 'adult', 'adventure', 'alpha', 'animals', 'animation', 'anime', 'architecture', 'art',
            'astronomy', 'baby', 'batshitinsane', 'biography', 'biology', 'book', 'books', 'business', 'business',
            'camera', 'cars', 'cats', 'cinema', 'classic', 'comedy', 'comics', 'computers', 'cookbook', 'cooking',
            'crime', 'culture', 'dark', 'design', 'digital', 'documentary', 'dogs', 'drama', 'drugs', 'education',
            'environment', 'evolution', 'family', 'fantasy', 'fashion', 'fiction', 'film', 'fitness', 'food',
            'football', 'fun', 'gaming', 'gift', 'health', 'hip', 'historical', 'history', 'horror', 'humor',
            'illustration', 'inspirational', 'internet', 'journalism', 'kids', 'language', 'law', 'literature', 'love',
            'magic', 'math', 'media', 'medicine', 'military', 'money', 'movies', 'mp3', 'murder', 'music', 'mystery',
            'news', 'nonfiction', 'nsfw', 'paranormal', 'parody', 'philosophy', 'photography', 'photos', 'physics',
            'poetry', 'politics', 'post-apocalyptic', 'privacy', 'psychology', 'radio', 'relationships', 'research',
            'rock', 'romance', 'rpg', 'satire', 'science', 'sciencefiction', 'scifi', 'security', 'self-help',
            'series', 'software', 'space', 'spirituality', 'sports', 'story', 'suspense', 'technology', 'teen',
            'television', 'terrorism', 'thriller', 'travel', 'tv', 'uk', 'urban', 'us', 'usa', 'vampire', 'video',
            'videogames', 'war', 'web', 'women', 'world', 'writing', 'wtf', 'zombies'];
    }
}
