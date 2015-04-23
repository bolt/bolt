<?php
namespace Bolt\Controllers\Backend;

use Bolt\Controllers\Base;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for basic backend routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Backend extends Base
{
    /**
     * @see \Bolt\Controllers\Base::addControllers()
     *
     * @param ControllerCollection $c
     */
    protected function addControllers(ControllerCollection $c)
    {
        return $c;
    }

    /*
     * Routes
     */

    /**
     * About page route.
     *
     * @return \Twig_Markup
     */
    public function actionAbout()
    {
        $this->render('about/about.twig');
    }

    /**
     * Clear the cache.
     *
     * @return \Twig_Markup
     */
    public function actionClearCache()
    {
        $result = $this->app['cache']->clearCache();

        $output = Trans::__('Deleted %s files from cache.', array('%s' => $result['successfiles']));

        if (!empty($result['failedfiles'])) {
            $output .= ' ' . Trans::__('%s files could not be deleted. You should delete them manually.', array('%s' => $result['failedfiles']));
            $this->addFlash('error', $output);
        } else {
            $this->addFlash('success', $output);
        }

        return $this->render('clearcache/clearcache.twig');
    }

    /**
     * Dashboard or 'root' route.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup
     */
    public function actionDashboard(Request $request)
    {
        $context = $this->getLatest();

        return $this->render('dashboard/dashboard.twig', array('context' => $context));
    }

    /**
     * Show the Omnisearch results.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup
     */
    public function actionOmnisearch(Request $request)
    {
        $query = $request->query->get('q', '');
        $results = array();

        if (strlen($query) >= 3) {
            $results = $this->app['omnisearch']->query($query, true);
        }

        $context = array(
            'query'   => $query,
            'results' => $results
        );

        return $this->render('omnisearch/omnisearch.twig', array('context' => $context));
    }

    /**
     * Get the latest records for viewable contenttypes that a user has access
     * to.
     *
     * When there are no Contenttype records we will suggest to create some
     * dummy content.
     *
     * @param integer $limit
     *
     * @return array
     */
    private function getLatest($limit = null)
    {
        $total  = 0;
        $latest = array();
        $limit  = $limit ?: $this->getOption('general/recordsperdashboardwidget');

        // Get the 'latest' from each of the content types.
        foreach ($this->getOption('contenttypes') as $key => $contenttype) {
            if ($this->isAllowed('contenttype:' . $key) && $contenttype['show_on_dashboard'] === true) {
                $latest[$key] = $this->getContent($key, array(
                    'limit'   => $limit,
                    'order'   => 'datechanged DESC',
                    'hydrate' => false
                ));

                if (!empty($latest[$key])) {
                    $total += count($latest[$key]);
                }
            }
        }

        return array(
            'latest'          => $latest,
            'suggestloripsum' => ($total === 0),
        );
    }
}
