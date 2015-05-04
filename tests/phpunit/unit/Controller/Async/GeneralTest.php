<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Response\BoltResponse;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Controller\ControllerUnitTest;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Backend/Async.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class GeneralTest extends ControllerUnitTest
{
    public function testAddStack()
    {
        $this->getService('users')->currentuser = $this->getService('users')->getUser('admin');
        $this->setRequest(Request::create('/async/addstack/foo'));

        $response = $this->controller()->actionAddStack($this->getRequest());

        $this->assertTrue($response);
    }

    public function testChangeLogRecord()
    {
        $this->setRequest(Request::create('/async/changelog/page/1'));

        $response = $this->controller()->actionChangeLogRecord('page', 1);

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('components/panel-change-record.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDashboardNews()
    {
        $this->setRequest(Request::create('/async/dashboardnews'));

        $response = $this->controller()->actionDashboardNews($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('components/panel-news.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testEmailNotification()
    {
        $this->getService('users')->currentuser = $this->getService('users')->getUser('admin');
        $this->setRequest(Request::create('/async/email/test/admin'));

        $response = $this->controller()->actionEmailNotification($this->getRequest(), 'test');

        $this->assertTrue($response instanceof Response);
        $this->assertSame('Done', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLastModified()
    {
        $this->setRequest(Request::create('/async/lastmodified/page/1'));

        $response = $this->controller()->actionLastModified('page', 1);

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('components/panel-lastmodified.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLatestactivity()
    {
        $this->setRequest(Request::create('/async/latestactivity'));

        $response = $this->controller()->actionLatestActivity($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('components/panel-activity.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \Bolt\Storage::getUri
     * @covers \Bolt\Controller\Async\General::actionMakeUri
     */
    public function testMakeUri()
    {
        // Set up a fake request for getContent()'s sake
        $this->setRequest(Request::create('/'));
        $record = $this->getService('storage')->getContent('pages/1');
        $this->setRequest(Request::create('/async/makeuri', 'GET', array(
            'title'           => $record->values['title'],
            'id'              => $record->values['id'],
            'contenttypeslug' => 'pages',
            'fulluri'         => true,
        )));

        $response = $this->controller()->actionMakeUri($this->getRequest());

        $this->assertSame('/page/' . $record->values['slug'], $response);
    }

    public function testOmnisearch()
    {
        $this->setRequest(Request::create('/async/omnisearch', 'GET', array(
            'q' => 'sho'
        )));

        $response = $this->controller()->actionOmnisearch($this->getRequest());

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

        $response = $this->controller()->actionPopularTags($this->getRequest(), 'tags');

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $json = json_decode($response->getContent());
        $tags = $this->getDefaultTags();

        $this->assertCount(20, $json);
        $this->assertTrue(in_array($json[0]->slug, $tags));
    }

    public function testReadme()
    {
    }

    public function testShowStack()
    {
        $this->getService('users')->currentuser = $this->getService('users')->getUser('admin');
        $this->setRequest(Request::create('/async/showstack'));

        $response = $this->controller()->actionShowStack($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('components/panel-stack.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

//     public function testTags()
//     {
//         $this->setRequest(Request::create('/async/tags/tags'));
//         $response = $this->controller()->actionTags($this->getRequest(), 'tags');

//         $this->assertTrue($response instanceof JsonResponse);
//         $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

//         $json = json_decode($response->getContent());
//         $tags = $this->getDefaultTags();

//         $this->assertCount(20, $json);
//         $this->assertTrue(in_array($json[0]->slug, $tags));
//     }

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
        return array('action', 'adult', 'adventure', 'alpha', 'animals', 'animation', 'anime', 'architecture', 'art',
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
            'videogames', 'war', 'web', 'women', 'world', 'writing', 'wtf', 'zombies');
    }
}
