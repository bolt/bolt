<?php
namespace Bolt\Controller\Backend;

use Bolt\Helpers\Input;
use Bolt\Translation\TranslationFile;
use Bolt\Translation\Translator as Trans;
use Guzzle\Http\Exception\RequestException as V3RequestException;
use GuzzleHttp\Exception\RequestException;
use Silex\ControllerCollection;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * General controller for basic backend routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class General extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/about', 'actionAbout')
            ->bind('about');

        $c->get('/clearcache', 'actionClearCache')
            ->bind('clearcache');

        $c->get('/', 'actionDashboard')
            ->bind('dashboard');

        $c->get('/omnisearch', 'actionOmnisearch')
            ->bind('omnisearch');

        $c->match('/prefill', 'actionPrefill')
            ->bind('prefill');

        $c->match('/tr/{domain}/{tr_locale}', 'actionTranslation')
            ->bind('translation')
            ->assert('domain', 'messages|contenttypes|infos')
            ->value('domain', 'messages')
            ->value('tr_locale', $this->app['locale']);
    }

    /**
     * About page route.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function actionAbout()
    {
        return $this->render('about/about.twig');
    }

    /**
     * Clear the cache.
     *
     * @return \Bolt\Response\BoltResponse
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
     * @return \Bolt\Response\BoltResponse
     */
    public function actionDashboard(Request $request)
    {
        $context = $this->getLatest();

        return $this->render('dashboard/dashboard.twig', $context);
    }

    /**
     * Show the Omnisearch results.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse
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

        return $this->render('omnisearch/omnisearch.twig', $context);
    }

    /**
     * Generate Lorem Ipsum records in the database for given Contenttypes.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
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
        $form = $this->createFormBuilder('form')
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

        return $this->render('prefill/prefill.twig', $context);
    }

    /**
     * Prepare/edit/save a translation.
     *
     * @param Request $request   The Symfony Request
     * @param string  $domain    The domain
     * @param string  $tr_locale The translation locale
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionTranslation(Request $request, $domain, $tr_locale)
    {
        $tr = array(
            'domain' => $domain,
            'locale' => $tr_locale
        );

        // Get the translation data
        $data = $this->getTranslationData($tr);

        // Create the form
        $form = $this->createFormBuilder('form', $data)
            ->add(
                'contents',
                'textarea',
                array('constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' => 10))
            )))
            ->getForm();

        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            if ($response = $this->saveTranslationFile($data['contents'], $tr)) {
                return $response;
            }
        }

        $context = array(
            'form'          => $form->createView(),
            'basename'      => basename($tr['shortPath']),
            'filetype'      => 'yml',
            'write_allowed' => $tr['writeallowed'],
        );

        return $this->render('editlocale/editlocale.twig', $context);
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

    /**
     * Get the translation data.
     *
     * @param array $tr
     *
     * @return string
     */
    private function getTranslationData(array &$tr)
    {
        $translation = new TranslationFile($this->app, $tr['domain'], $tr['locale']);

        list($tr['path'], $tr['shortPath']) = $translation->path();

        $this->app['logger.system']->info('Editing translation: ' . $tr['shortPath'], array('event' => 'translation'));

        $tr['writeallowed'] = $translation->isWriteAllowed();

        return array('contents' => $translation->content());
    }

    /**
     * Attempt to save the POST data for a translation file edit.
     *
     * @param string $contents
     * @param array  $tr
     *
     * @return boolean|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function saveTranslationFile($contents, array &$tr)
    {
        $contents = Input::cleanPostedData($contents) . "\n";

        // Before trying to save a yaml file, check if it's valid.
        try {
            Yaml::parse($contents);
        } catch (ParseException $e) {
            $msg = Trans::__("File '%s' could not be saved: ", array('%s' => $tr['shortPath']));
            $this->addFlash('error', $msg . $e->getMessage());

            return false;
        }

        // Clear any warning for file not found, we are creating it here
        // we'll set an error if someone still submits the form and write is not allowed
        $this->app['logger.flash']->clear();

        try {
            $fs = new Filesystem();
            $fs->dumpFile($tr['path'], $contents);
        } catch (IOException $e) {
            $msg = Trans::__("The file '%s' is not writable. You will have to use your own editor to make modifications to this file.", array('%s' => $tr['shortPath']));
            $this->addFlash('error', $msg);
            $tr['writeallowed'] = false;
            return false;
        }

        $msg = Trans::__("File '%s' has been saved.", array('%s' => $tr['shortPath']));
        $this->addFlash('info', $msg);

        return $this->redirectToRoute('translation', array('domain' => $tr['domain'], 'tr_locale' => $tr['locale']));
    }
}
