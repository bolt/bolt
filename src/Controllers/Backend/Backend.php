<?php
namespace Bolt\Controllers\Backend;

use Bolt\Controllers\Base;
use Bolt\Translation\Translator as Trans;
use Guzzle\Http\Exception\RequestException as V3RequestException;
use GuzzleHttp\Exception\RequestException;
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
     * Generate Lorem Ipsum records in the database for given Contenttypes.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionPrefill(Request $request)
    {
        // Determine the Contenttypes that we're doing the prefill for
        $choices = array();
        foreach ($this->getOption('contenttypes') as $key => $cttype) {
            $namekey = 'contenttypes.' . $key . '.name.plural';
            $name = Trans::__($namekey, array(), 'contenttypes');
            $choices[$key] = ($name == $namekey) ? $cttype['name'] : $name;
        }

        // Create the form
        $form = $this->createBuilder('form')
            ->add('contenttypes', 'choice', array(
                'choices'  => $choices,
                'multiple' => true,
                'expanded' => true,
            ))
            ->getForm();

        if ($request->isMethod('POST') || $request->get('force') == 1) {
            $form->submit($request);
            $contenttypes = $form->get('contenttypes')->getData();

            try {
                $content = $this->app['storage']->preFill($contenttypes);
                $this->addFlash('success', $content);
            } catch (RequestException $e) {
                $msg = "Timeout attempting to the 'Lorem Ipsum' generator. Unable to add dummy content.";
                $this->addFlash('error', $msg);
                $this->app['logger.system']->error($msg, array('event' => 'storage'));
            } catch (V3RequestException $e) {
                /** @deprecated removed when PHP 5.3 support is dropped */
                $msg = "Timeout attempting to the 'Lorem Ipsum' generator. Unable to add dummy content.";
                $this->addFlash('error', $msg);
                $this->app['logger.system']->error($msg, array('event' => 'storage'));
            }

            return $this->redirectToRoute('prefill');
        }

        $context = array(
            'contenttypes' => $choices,
            'form'         => $form->createView(),
        );

        return $this->render('prefill/prefill.twig', array('context' => $context));
    }

    /**
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function action(Request $request)
    {
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
