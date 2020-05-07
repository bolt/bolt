<?php

namespace Bolt\Controller\Async;

use Bolt;
use Bolt\Common\Exception\ParseException;
use Bolt\Common\Json;
use Bolt\Extension\ExtensionInterface;
use Bolt\Filesystem;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository;
use GuzzleHttp\Exception\RequestException;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Async controller for general async routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class General extends AsyncBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/', 'async')
            ->bind('async');

        $c->get('/changelog/{contenttype}/{contentid}', 'changeLogRecord')
            ->value('contenttype', '')
            ->value('contentid', '0')
            ->bind('changelogrecord');

        $c->get('/dashboardnews', 'dashboardNews')
            ->bind('dashboardnews')
            ->after(function (Request $request, Response $response) {
                $response->setSharedMaxAge(3600);
            })
        ;

        $c->get('/lastmodified/{contenttypeslug}/{contentid}', 'lastModified')
            ->value('contentid', '')
            ->bind('lastmodified')
            ->after(function (Request $request, Response $response) {
                $response->setSharedMaxAge(60);
            })
        ;

        $c->get('/latestactivity', 'latestActivity')
            ->bind('latestactivity')
            ->after(function (Request $request, Response $response) {
                $response->setSharedMaxAge(3600);
            })
        ;

        $c->get('/makeuri', 'makeUri')
            ->bind('makeuri');

        $c->get('/omnisearch', 'omnisearch')
            ->bind('omnisearch');

        $c->get('/readme/{extension}', 'readme')
            ->assert('extension', '.+')
            ->bind('readme')
            ->convert('extension', function ($id) {
                $extension = $this->extensions()->get($id);
                if ($extension === null) {
                    throw new NotFoundHttpException('Not Found');
                }

                return $extension;
            })
            ->after(function (Request $request, Response $response) {
                $response->setSharedMaxAge(180);
            })
        ;

        $c->get('/populartags/{taxonomytype}', 'popularTags')
            ->bind('populartags');

        $c->get('/tags/{taxonomytype}', 'tags')
            ->bind('tags');
    }

    /**
     * Default route binder for asynchronous requests.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function async()
    {
        $confirm = ['OK'];

        return $this->json($confirm);
    }

    /**
     * Generate the change log box for a single record in edit.
     *
     * @param string $contenttype
     * @param int    $contentid
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function changeLogRecord($contenttype, $contentid)
    {
        $options = [
            'contentid' => $contentid,
            'limit'     => 4,
            'order'     => 'date',
            'direction' => 'DESC',
        ];

        /** @var Repository\LogChangeRepository $repo */
        $repo = $this->storage()->getRepository(Entity\LogChange::class);

        $context = [
            'contenttype' => $contenttype,
            'entries'     => $repo->getChangeLogByContentType($contenttype, $options),
        ];

        return $this->render('@bolt/components/panel-change-record.twig', ['context' => $context]);
    }

    /**
     * News. Film at 11.
     *
     * @param Request $request
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function dashboardNews(Request $request)
    {
        $news = $this->getNews($request->getHost());

        // One 'alert' and one 'info' max. Regular info-items can be disabled,
        // but Alerts can't.
        $context = [
            'alert'       => empty($news['alert']) ? null : $news['alert'],
            'news'        => empty($news['news']) ? null : $news['news'],
            'information' => empty($news['information']) ? null : $news['information'],
            'error'       => empty($news['error']) ? null : $news['error'],
            'disable'     => $this->getOption('general/backend/news/disable'),
        ];

        $response = $this->render('@bolt/components/panel-news.twig', ['context' => $context]);

        return $response;
    }

    /**
     * Latest {contenttype} to show a small listing in the sidebars.
     *
     * @param string   $contenttypeslug
     * @param int|null $contentid
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function lastModified($contenttypeslug, $contentid = null)
    {
        // Let's find out how we should determine what the latest changes were:
        $contentLogEnabled = (bool) $this->getOption('general/changelog/enabled');

        if ($contentLogEnabled) {
            return $this->getLastmodifiedByContentLog($contenttypeslug, $contentid);
        }

        return $this->getLastmodifiedSimple($contenttypeslug);
    }

    /**
     * Get the 'latest activity' for the dashboard.
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function latestActivity()
    {
        // Test/get page number
        $page = $this->app['pager']->getCurrentPage('activity');

        if ($this->app['config']->get('general/changelog/enabled')) {
            $change = $this->app['logger.manager']->getActivity('change', $page, 8);
        } else {
            $change = null;
        }
        $system = $this->app['logger.manager']->getActivity('system', $page, 8, ['context' => ['authentication', 'security']]);

        $response = $this->render(
            '@bolt/components/panel-activity.twig',
            [
                'context' => [
                    'change' => $change,
                    'system' => $system,
                ],
            ]
        );

        return $response;
    }

    /**
     * Generate a URI based on request parameters.
     *
     * @param Request $request
     *
     * @return string
     */
    public function makeUri(Request $request)
    {
        $content = $this->storage()->create(
            $request->query->get('contenttypeslug'),
            $request->query->all()
        );

        // Spoof the title, for contenttypes that have a different `uses:`
        $content->set('title', $request->query->get('title'));

        $uri = $content->getUri(
            $request->query->get('id'),
            $request->query->getBoolean('fulluri'),
            $request->query->get('slugfield') //for multipleslug support
        );

        return $uri;
    }

    /**
     * Perform an OmniSearch search and return the results.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function omnisearch(Request $request)
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 3) {
            return $this->json([]);
        }

        $options = $this->app['omnisearch']->query($query);

        return $this->json($options);
    }

    /**
     * Fetch a JSON encoded set of the most popular taxonomy specific tags.
     *
     * @param Request $request
     * @param string  $taxonomytype
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function popularTags(Request $request, $taxonomytype)
    {
        $table = $this->getOption('general/database/prefix');
        $table .= 'taxonomy';

        $query = $this->createQueryBuilder()
            ->select('slug, name, COUNT(slug) AS count')
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->groupBy('name, slug')
            ->orderBy('count', 'DESC')
            ->setMaxResults($request->query->getInt('limit', 20))
            ->setParameters([
                ':taxonomytype' => $taxonomytype,
            ]);

        $results = $query->execute()->fetchAll();

        usort(
            $results,
            function ($a, $b) {
                if ($a['slug'] == $b['slug']) {
                    return 0;
                }

                return ($a['slug'] < $b['slug']) ? -1 : 1;
            }
        );

        return $this->json($results);
    }

    /**
     * Render an extension's README.md file.
     *
     * @param ExtensionInterface $extension
     *
     * @return Response
     */
    public function readme(ExtensionInterface $extension)
    {
        $file = null;
        foreach (['README.md', 'readme.md'] as $possibleName) {
            $file = $extension->getBaseDirectory()->getFile($possibleName);
            if ($file->exists()) {
                break;
            }
        }
        if ($file === null) {
            throw new NotFoundHttpException('Not Found');
        }

        try {
            $readme = $file->read();
        } catch (Filesystem\Exception\IOException $e) {
            throw new NotFoundHttpException('Not Found');
        }

        // Parse the field as Markdown, return HTML
        $html = $this->app['markdown']->text($readme);

        $response = new Response($html);

        return $response;
    }

    /**
     * Fetch a JSON encoded set of taxonomy specific tags.
     *
     * @param string $taxonomytype
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function tags($taxonomytype)
    {
        $table = $this->getOption('general/database/prefix');
        $table .= 'taxonomy';

        $query = $this->createQueryBuilder()
            ->select("DISTINCT slug, name")
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->orderBy('name', 'ASC')
            ->setParameters([
                ':taxonomytype' => $taxonomytype,
            ]);

        $results = $query->execute()->fetchAll();

        return $this->json($results);
    }

    /**
     * Get the news from Bolt HQ (with caching).
     *
     * @param string $hostname
     *
     * @return array
     */
    private function getNews($hostname)
    {
        // Cached for two hours.
        $news = $this->app['cache']->fetch('dashboardnews');
        if ($news !== false) {
            $this->app['logger.system']->info('Using cached data', ['event' => 'news']);

            return $news;
        }

        // If not cached, get fresh news.
        $news = $this->fetchNews($hostname);

        $this->app['cache']->save('dashboardnews', $news, 7200);

        return $news;
    }

    /**
     * Get the news from Bolt HQ.
     *
     * @param string $hostname
     *
     * @return array
     */
    private function fetchNews($hostname)
    {
        $source = $this->getOption('general/branding/news_source', 'https://news.bolt.cm/');
        $options = $this->fetchNewsOptions($hostname);

        $this->app['logger.system']->info('Fetching from remote server: ' . $source, ['event' => 'news']);

        try {
            $fetchedNewsData = (string) $this->app['guzzle.client']->get($source, $options)->getBody();
        } catch (RequestException $e) {
            $this->app['logger.system']->error(
                'Error occurred during newsfeed fetch',
                ['event' => 'exception', 'exception' => $e]
            );

            return [
                'error' => [
                    'type'   => 'error',
                    'title'  => 'Unable to fetch news!',
                    'teaser' => "<p>Unable to connect to $source</p>",
                ],
            ];
        }

        try {
            $fetchedNewsItems = Json::parse($fetchedNewsData);
        } catch (ParseException $e) {
            // Just move on, a user-friendly notice is returned below.
            $fetchedNewsItems = [];
        }

        $newsVariable = $this->getOption('general/branding/news_variable');
        if ($newsVariable && array_key_exists($newsVariable, $fetchedNewsItems)) {
            $fetchedNewsItems = $fetchedNewsItems[$newsVariable];
        }

        $news = [];

        // Iterate over the items, pick the first news-item that
        // applies and the first alert we need to show
        foreach ($fetchedNewsItems as $item) {
            $type = isset($item->type) ? $item->type : 'information';
            if (!isset($news[$type])
                && (empty($item->target_version) || Bolt\Version::compare($item->target_version, '>'))
            ) {
                $news[$type] = $item;
            }
        }

        if ($news) {
            return $news;
        }
        $this->app['logger.system']->error('Invalid JSON feed returned', ['event' => 'news']);

        return [
            'error' => [
                'type'   => 'error',
                'title'  => 'Unable to fetch news!',
                'teaser' => "<p>Invalid JSON feed returned by $source</p>",
            ],
        ];
    }

    /**
     * Get the guzzle options.
     *
     * @param string $hostname
     *
     * @return array
     */
    private function fetchNewsOptions($hostname)
    {
        $driver = $this->app['db']->getDatabasePlatform()->getName();

        $options = [
            'query' => [
                'v'    => Bolt\Version::VERSION,
                'p'    => PHP_VERSION,
                'db'   => $driver,
                'name' => $hostname,
            ],
            'connect_timeout' => 5,
            'timeout'         => 10,
        ];

        if ($this->getOption('general/httpProxy')) {
            if ($this->getOption('general/httpProxy/user')) {
                $options['proxy'] = sprintf(
                    '%s:%s@%s',
                    $this->getOption('general/httpProxy/user'),
                    $this->getOption('general/httpProxy/password'),
                    $this->getOption('general/httpProxy/host')
                );
            } else {
                $options['proxy'] = $this->getOption('general/httpProxy/host');
            }
        }

        return $options;
    }

    /**
     * Get last modified records from the content log.
     *
     * @param string $contenttypeslug
     * @param int    $contentid
     *
     * @return \Bolt\Response\TemplateResponse
     */
    private function getLastmodifiedByContentLog($contenttypeslug, $contentid)
    {
        // Get the proper ContentType.
        $contenttype = $this->getContentType($contenttypeslug);

        // get the changelog for the requested ContentType.
        $options = ['limit' => 5, 'order' => 'date', 'direction' => 'DESC'];

        if ((int) $contentid == 0) {
            $isFiltered = false;
        } else {
            $isFiltered = true;
            $options['contentid'] = (int) $contentid;
        }

        /** @var Repository\LogChangeRepository $repo */
        $repo = $this->storage()->getRepository(Entity\LogChange::class);
        $changelog = $repo->getChangeLogByContentType($contenttype['slug'], $options);

        $context = [
            'changelog'   => $changelog,
            'contenttype' => $contenttype,
            'contentid'   => $contentid,
            'filtered'    => $isFiltered,
        ];

        return $this->render('@bolt/components/panel-lastmodified.twig', ['context' => $context]);
    }

    /**
     * Only get latest {contenttype} record edits based on date changed.
     *
     * @param string $contenttypeslug
     *
     * @return \Bolt\Response\TemplateResponse
     */
    private function getLastmodifiedSimple($contenttypeslug)
    {
        // Get the proper ContentType.
        $contenttype = $this->getContentType($contenttypeslug);

        // Get the 'latest' from the requested ContentType.
        $latest = $this->getContent($contenttype['slug'], ['limit' => 5, 'order' => '-datechanged', 'hydrate' => false]);

        $context = [
            'latest'      => $latest,
            'contenttype' => $contenttype,
        ];

        return $this->render('@bolt/components/panel-lastmodified.twig', ['context' => $context]);
    }
}
