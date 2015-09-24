<?php
namespace Bolt\Controller\Async;

use Bolt\Pager;
use GuzzleHttp\Exception\RequestException;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            ->bind('dashboardnews');

        $c->get('/lastmodified/{contenttypeslug}/{contentid}', 'lastModified')
            ->value('contentid', '')
            ->bind('lastmodified');

        $c->get('/latestactivity', 'latestActivity')
            ->bind('latestactivity');

        $c->get('/makeuri', 'makeUri')
            ->bind('makeuri');

        $c->get('/omnisearch', 'omnisearch')
            ->bind('omnisearch');

        $c->get('/readme/{filename}', 'readme')
            ->assert('filename', '.+')
            ->bind('readme');

        $c->get('/populartags/{taxonomytype}', 'popularTags')
            ->bind('populartags');

        $c->get('/tags/{taxonomytype}', 'tags')
            ->bind('tags');

        $c->get('/widget/{key}', 'widget')
            ->bind('widget');
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
     * @param string  $contenttype
     * @param integer $contentid
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function changeLogRecord($contenttype, $contentid)
    {
        $options = [
            'contentid' => $contentid,
            'limit'     => 4,
            'order'     => 'date',
            'direction' => 'DESC'
        ];

        $context = [
            'contenttype' => $contenttype,
            'entries'     => $this->storage()->getRepository('Bolt\Storage\Entity\LogChange')->getChangeLogByContentType($contenttype, $options)
        ];

        return $this->render('@bolt/components/panel-change-record.twig', ['context' => $context]);
    }

    /**
     * News. Film at 11.
     *
     * @param Request $request
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function dashboardNews(Request $request)
    {
        $news = $this->getNews($request->getHost());

        // One 'alert' and one 'info' max. Regular info-items can be disabled,
        // but Alerts can't.
        $context = [
            'alert'       => empty($news['alert']) ? null : $news['alert'],
            'information' => empty($news['information']) ? null : $news['information'],
            'error'       => empty($news['error']) ? null : $news['error'],
            'disable'     => $this->getOption('general/backend/news/disable')
        ];

        $response = $this->render('@bolt/components/panel-news.twig', ['context' => $context]);
        $response->setCache(['s_maxage' => '3600', 'public' => true]);

        return $response;
    }

    /**
     * Latest {contenttype} to show a small listing in the sidebars.
     *
     * @param string       $contenttypeslug
     * @param integer|null $contentid
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function lastModified($contenttypeslug, $contentid = null)
    {
        // Let's find out how we should determine what the latest changes were:
        $contentLogEnabled = (bool) $this->getOption('general/changelog/enabled');

        if ($contentLogEnabled) {
            return $this->getLastmodifiedByContentLog($contenttypeslug, $contentid);
        } else {
            return $this->getLastmodifiedSimple($contenttypeslug);
        }
    }

    /**
     * Get the 'latest activity' for the dashboard.
     *
     * @param Request $request
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function latestActivity(Request $request)
    {
        // Test/get page number
        $param = Pager::makeParameterId('activity');
        $page = ($request->query) ? $request->query->get($param, $request->query->get('page', 1)) : 1;

        $change = $this->app['logger.manager']->getActivity('change', $page, 8);
        $system = $this->app['logger.manager']->getActivity('system', $page, 8, ['context' => ['authentication', 'security']]);

        $response = $this->render('@bolt/components/panel-activity.twig', ['context' => [
            'change' => $change,
            'system' => $system,
        ]]);
        $response->setPublic()->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * Generate a URI based on request parameters
     *
     * @param Request $request
     *
     * @return string
     */
    public function makeUri(Request $request)
    {
        return $this->storage()->getUri(
            $request->query->get('title'),
            $request->query->get('id'),
            $request->query->get('contenttypeslug'),
            $request->query->getBoolean('fulluri'),
            true,
            $request->query->get('slugfield') //for multipleslug support
        );
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
            ->select('name, COUNT(slug) AS count')
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->groupBy('slug')
            ->orderBy('count', 'DESC')
            ->setMaxResults($request->query->getInt('limit', 20))
            ->setParameters([
                ':taxonomytype' => $taxonomytype
            ]);

        $results = $query->execute()->fetchAll();

        usort(
            $results,
            function ($a, $b) {
                if ($a['name'] == $b['name']) {
                    return 0;
                }

                return ($a['name'] < $b['name']) ? -1 : 1;
            }
        );

        return $this->json($results);
    }

    /**
     * Render an extension's README.md file.
     *
     * @param string $filename
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function readme($filename)
    {
        $filename = $this->resources()->getPath('extensions/vendor/' . $filename);

        // don't allow viewing of anything but "readme.md" files.
        if (strtolower(basename($filename)) != 'readme.md') {
            $this->abort(Response::HTTP_UNAUTHORIZED, 'Not allowed');
        }
        if (!is_readable($filename)) {
            $this->abort(Response::HTTP_UNAUTHORIZED, 'Not readable');
        }

        $readme = file_get_contents($filename);

        // Parse the field as Markdown, return HTML
        $html = $this->app['markdown']->text($readme);

        return new Response($html, Response::HTTP_OK, ['Cache-Control' => 's-maxage=180, public']);
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
            ->select("DISTINCT $table.name")
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->orderBy('name', 'ASC')
            ->setParameters([
                ':taxonomytype' => $taxonomytype
            ]);

        $results = $query->execute()->fetchAll();

        return $this->json($results);
    }

    /**
     * Render a widget, and return the HTML, so it can be inserted in the page.
     *
     * @param string $key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function widget($key)
    {
        $html = $this->extensions()->renderWidget($key);

        return new Response($html, Response::HTTP_OK, ['Cache-Control' => 's-maxage=180, public']);
    }

    /**
     * Get the news from either cache or Bolt HQ.
     *
     * @param string $hostname
     *
     * @return array|string
     */
    private function getNews($hostname)
    {
        /** @var \Bolt\Cache $cache */
        $cache = $this->app['cache'];

        // Cached for two hours.
        $news = $cache->fetch('dashboardnews');

        // If not cached, get fresh news.
        if ($news !== false) {
            $this->app['logger.system']->info('Using cached data', ['event' => 'news']);

            return $news;
        } else {
            $source = $this->getOption('general/branding/news_source', 'http://news.bolt.cm/');
            $curl = $this->getDashboardCurlOptions($hostname, $source);

            $this->app['logger.system']->info('Fetching from remote server: ' . $source, ['event' => 'news']);

            try {
                $fetchedNewsData = $this->app['guzzle.client']->get($curl['url'], [], $curl['options'])->getBody(true);
                if ($this->getOption('general/branding/news_variable')) {
                    $newsVariable = $this->getOption('general/branding/news_variable');
                    $fetchedNewsItems = json_decode($fetchedNewsData)->$newsVariable;
                } else {
                    $fetchedNewsItems = json_decode($fetchedNewsData);
                }
                if ($fetchedNewsItems) {
                    $news = [];

                    // Iterate over the items, pick the first news-item that
                    // applies and the first alert we need to show
                    $version = $this->app['bolt_version'];
                    foreach ($fetchedNewsItems as $item) {
                        $type = ($item->type === 'alert') ? 'alert' : 'information';
                        if (!isset($news[$type])
                            && (empty($item->target_version) || version_compare($item->target_version, $version, '>'))
                        ) {
                            $news[$type] = $item;
                        }
                    }

                    $cache->save('dashboardnews', $news, 7200);
                } else {
                    $this->app['logger.system']->error('Invalid JSON feed returned', ['event' => 'news']);
                }

                return $news;
            } catch (RequestException $e) {
                $this->app['logger.system']->critical(
                    'Error occurred during newsfeed fetch',
                    ['event' => 'exception', 'exception' => $e]
                );

                return ['error' => ['type' => 'error', 'title' => 'Unable to fetch news!', 'teaser' => "<p>Unable to connect to $source</p>"]];
            }
        }
    }

    /**
     * Get the cURL options.
     *
     * @param string $hostname
     * @param string $source
     *
     * @return array
     */
    private function getDashboardCurlOptions($hostname, $source)
    {
        $driver = $this->app['db']->getDatabasePlatform()->getName();

        $url = sprintf(
            '%s?v=%s&p=%s&db=%s&name=%s',
            $source,
            rawurlencode($this->app['bolt_version']),
            phpversion(),
            $driver,
            base64_encode($hostname)
        );

        // Standard option(s)
        $options = ['CURLOPT_CONNECTTIMEOUT' => 5];

        // Options valid if using a proxy
        if ($this->getOption('general/httpProxy')) {
            $proxies = [
                'CURLOPT_PROXY'        => $this->getOption('general/httpProxy/host'),
                'CURLOPT_PROXYTYPE'    => 'CURLPROXY_HTTP',
                'CURLOPT_PROXYUSERPWD' => $this->getOption('general/httpProxy/user') . ':' .
                $this->getOption('general/httpProxy/password')
            ];
        }

        return [
            'url'     => $url,
            'options' => !empty($proxies) ? array_merge($options, $proxies) : $options
        ];
    }

    /**
     * Get last modified records from the content log.
     *
     * @param string  $contenttypeslug
     * @param integer $contentid
     *
     * @return \Bolt\Response\BoltResponse
     */
    private function getLastmodifiedByContentLog($contenttypeslug, $contentid)
    {
        // Get the proper contenttype.
        $contenttype = $this->getContentType($contenttypeslug);

        // get the changelog for the requested contenttype.
        $options = ['limit' => 5, 'order' => 'date', 'direction' => 'DESC'];

        if (intval($contentid) == 0) {
            $isFiltered = false;
        } else {
            $isFiltered = true;
            $options['contentid'] = intval($contentid);
        }

        $changelog = $this->storage()->getRepository('Bolt\Storage\Entity\LogChange')->getChangeLogByContentType($contenttype['slug'], $options);

        $context = [
            'changelog'   => $changelog,
            'contenttype' => $contenttype,
            'contentid'   => $contentid,
            'filtered'    => $isFiltered,
        ];

        $response = $this->render('@bolt/components/panel-lastmodified.twig', ['context' => $context]);
        $response->setPublic()->setSharedMaxAge(60);

        return $response;
    }

    /**
     * Only get latest {contenttype} record edits based on date changed.
     *
     * @param string $contenttypeslug
     *
     * @return \Bolt\Response\BoltResponse
     */
    private function getLastmodifiedSimple($contenttypeslug)
    {
        // Get the proper contenttype.
        $contenttype = $this->getContentType($contenttypeslug);

        // Get the 'latest' from the requested contenttype.
        $latest = $this->getContent($contenttype['slug'], ['limit' => 5, 'order' => 'datechanged DESC', 'hydrate' => false]);

        $context = [
            'latest'      => $latest,
            'contenttype' => $contenttype
        ];

        $response = $this->render('@bolt/components/panel-lastmodified.twig', ['context' => $context]);
        $response->setPublic()->setSharedMaxAge(60);

        return $response;
    }
}
