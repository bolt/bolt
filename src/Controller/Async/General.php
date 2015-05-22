<?php
namespace Bolt\Controller\Async;

use Bolt\Response\BoltResponse;
use Guzzle\Http\Exception\RequestException as V3RequestException;
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
     * @return string
     */
    public function changeLogRecord($contenttype, $contentid)
    {
        $options = array(
            'contentid' => $contentid,
            'limit'     => 4,
            'order'     => 'date',
            'direction' => 'DESC'
        );

        $context = array(
            'contenttype' => $contenttype,
            'entries'     => $this->app['logger.manager.change']->getChangelogByContentType($contenttype, $options)
        );

        return $this->render('components/panel-change-record.twig', array('context' => $context));
    }

    /**
     * News. Film at 11.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardNews(Request $request)
    {
        $news = $this->getNews($request->getHost());

        // One 'alert' and one 'info' max. Regular info-items can be disabled,
        // but Alerts can't.
        $context = array(
            'alert'       => empty($news['alert']) ? null : $news['alert'],
            'information' => empty($news['information']) ? null : $news['information'],
            'error'       => empty($news['error']) ? null : $news['error'],
            'disable'     => $this->getOption('general/backend/news/disable')
        );

        $response = $this->render('components/panel-news.twig', array('context' => $context));
        $response->setCache(array('s_maxage' => '3600', 'public' => true));

        return $response;
    }

    /**
     * Latest {contenttype} to show a small listing in the sidebars.
     *
     * @param string       $contenttypeslug
     * @param integer|null $contentid
     *
     * @return BoltResponse
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function latestActivity()
    {
        $change = $this->app['logger.manager']->getActivity('change', 8);
        $system = $this->app['logger.manager']->getActivity('system', 8, null, 'authentication');

        $response = $this->render('components/panel-activity.twig', array('context' => array(
            'change' => $change,
            'system' => $system,
        )));
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
        return $this->app['storage']->getUri(
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
            return $this->json(array());
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
     * @return integer|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function popularTags(Request $request, $taxonomytype)
    {
        $table = $this->getOption('general/database/prefix', 'bolt_');
        $table .= 'taxonomy';

        $query = $this->app['db']->createQueryBuilder()
            ->select('slug, COUNT(slug) AS count')
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->groupBy('slug')
            ->orderBy('count', 'DESC')
            ->setMaxResults($request->query->getInt('limit', 20))
            ->setParameters(array(
                ':taxonomytype' => $taxonomytype
            ));

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
     * @param string $filename
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function readme($filename)
    {
        $paths = $this->app['resources']->getPaths();

        $filename = $paths['extensionspath'] . '/vendor/' . $filename;

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

        return new Response($html, Response::HTTP_OK, array('Cache-Control' => 's-maxage=180, public'));
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
        $table = $this->getOption('general/database/prefix', 'bolt_');
        $table .= 'taxonomy';

        $query = $this->app['db']->createQueryBuilder()
            ->select("DISTINCT $table.slug")
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->orderBy('slug', 'ASC')
            ->setParameters(array(
                ':taxonomytype' => $taxonomytype
            ));

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
        $html = $this->app['extensions']->renderWidget($key);

        return new Response($html, Response::HTTP_OK, array('Cache-Control' => 's-maxage=180, public'));
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
        // Cached for two hours.
        $news = $this->app['cache']->fetch('dashboardnews');

        // If not cached, get fresh news.
        if ($news !== false) {
            $this->app['logger.system']->info('Using cached data', array('event' => 'news'));

            return $news;
        } else {
            $source = 'http://news.bolt.cm/';
            $curl = $this->getDashboardCurlOptions($hostname, $source);

            $this->app['logger.system']->info('Fetching from remote server: ' . $source, array('event' => 'news'));

            try {
                if ($this->app['deprecated.php']) {
                    $fetchedNewsData = $this->app['guzzle.client']->get($curl['url'], null, $curl['options'])->send()->getBody(true);
                } else {
                    $fetchedNewsData = $this->app['guzzle.client']->get($curl['url'], array(), $curl['options'])->getBody(true);
                }

                $fetchedNewsItems = json_decode($fetchedNewsData);

                if ($fetchedNewsItems) {
                    $news = array();

                    // Iterate over the items, pick the first news-item that
                    // applies and the first alert we need to show
                    $version = $this->app->getVersion();
                    foreach ($fetchedNewsItems as $item) {
                        $type = ($item->type === 'alert') ? 'alert' : 'information';
                        if (!isset($news[$type])
                            && (empty($item->target_version) || version_compare($item->target_version, $version, '>'))
                        ) {
                            $news[$type] = $item;
                        }
                    }

                    $this->app['cache']->save('dashboardnews', $news, 7200);
                } else {
                    $this->app['logger.system']->error('Invalid JSON feed returned', array('event' => 'news'));
                }

                return $news;
            } catch (RequestException $e) {
                $this->app['logger.system']->critical(
                    'Error occurred during newsfeed fetch',
                    array('event' => 'exception', 'exception' => $e)
                );

                return array('error' => array('type' => 'error', 'title' => 'Unable to fetch news!', 'teaser' => "<p>Unable to connect to $source</p>"));
            } catch (V3RequestException $e) {
                /** @deprecated remove with the end of PHP 5.3 support */
                $this->app['logger.system']->critical(
                    'Error occurred during newsfeed fetch',
                    array('event' => 'exception', 'exception' => $e)
                );

                return array('error' => array('type' => 'error', 'title' => 'Unable to fetch news!', 'teaser' => "<p>Unable to connect to $source</p>"));
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
            rawurlencode($this->app->getVersion()),
            phpversion(),
            $driver,
            base64_encode($hostname)
        );

        // Standard option(s)
        $options = array('CURLOPT_CONNECTTIMEOUT' => 5);

        // Options valid if using a proxy
        if ($this->getOption('general/httpProxy')) {
            $proxies = array(
                'CURLOPT_PROXY'        => $this->getOption('general/httpProxy/host'),
                'CURLOPT_PROXYTYPE'    => 'CURLPROXY_HTTP',
                'CURLOPT_PROXYUSERPWD' => $this->getOption('general/httpProxy/user') . ':' .
                $this->getOption('general/httpProxy/password')
            );
        }

        return array(
            'url'     => $url,
            'options' => $proxies ? array_merge($options, $proxies) : $options
        );
    }

    /**
     * Get last modified records from the content log.
     *
     * @param string  $contenttypeslug
     * @param integer $contentid
     *
     * @return BoltResponse
     */
    private function getLastmodifiedByContentLog($contenttypeslug, $contentid)
    {
        // Get the proper contenttype.
        $contenttype = $this->getContentType($contenttypeslug);

        // get the changelog for the requested contenttype.
        $options = array('limit' => 5, 'order' => 'date', 'direction' => 'DESC');

        if (intval($contentid) == 0) {
            $isFiltered = false;
        } else {
            $isFiltered = true;
            $options['contentid'] = intval($contentid);
        }

        $changelog = $this->app['logger.manager.change']->getChangelogByContentType($contenttype['slug'], $options);

        $context = array(
            'changelog'   => $changelog,
            'contenttype' => $contenttype,
            'contentid'   => $contentid,
            'filtered'    => $isFiltered,
        );

        $response = $this->render('components/panel-lastmodified.twig', array('context' => $context));
        $response->setPublic()->setSharedMaxAge(60);

        return $response;
    }

    /**
     * Only get latest {contenttype} record edits based on date changed.
     *
     * @param string $contenttypeslug
     *
     * @return BoltResponse
     */
    private function getLastmodifiedSimple($contenttypeslug)
    {
        // Get the proper contenttype.
        $contenttype = $this->getContentType($contenttypeslug);

        // Get the 'latest' from the requested contenttype.
        $latest = $this->getContent($contenttype['slug'], array('limit' => 5, 'order' => 'datechanged DESC', 'hydrate' => false));

        $context = array(
            'latest'      => $latest,
            'contenttype' => $contenttype
        );

        $response = $this->render('components/panel-lastmodified.twig', array('context' => $context));
        $response->setPublic()->setSharedMaxAge(60);

        return $response;
    }
}
