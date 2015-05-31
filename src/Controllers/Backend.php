<?php

namespace Bolt\Controllers;

use Bolt\Helpers\Input;
use Bolt\Library as Lib;
use Bolt\Permissions;
use Bolt\Translation\TranslationFile;
use Bolt\Translation\Translator as Trans;
use Cocur\Slugify\Slugify;
use Guzzle\Http\Exception\RequestException as V3RequestException;
use GuzzleHttp\Exception\RequestException;
use Silex;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use League\Flysystem\FileNotFoundException;

/**
 * Backend controller grouping.
 *
 * This implements the Silex\ControllerProviderInterface to connect the controller
 * methods here to whatever back-end route prefix was chosen in your config. This
 * will usually be "/bolt".
 */
class Backend implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        /** @var $ctl \Silex\ControllerCollection */
        $ctl = $app['controllers_factory'];

        $ctl->before(array($this, 'before'));
        $ctl->method('GET|POST');

        $ctl->get('', array($this, 'dashboard'))
            ->bind('dashboard');

        $ctl->get('/dbcheck', array($this, 'dbCheck'))
            ->bind('dbcheck');

        $ctl->post('/dbupdate', array($this, 'dbUpdate'))
            ->bind('dbupdate');

        $ctl->get('/dbupdate_result', array($this, 'dbUpdateResult'))
            ->bind('dbupdate_result');

        $ctl->get('/clearcache', array($this, 'clearCache'))
            ->bind('clearcache');

        $ctl->match('/prefill', array($this, 'prefill'))
            ->bind('prefill');

        $ctl->get('/overview/{contenttypeslug}', array($this, 'overview'))
            ->bind('overview');

        $ctl->get('/relatedto/{contenttypeslug}/{id}', array($this, 'relatedTo'))
            ->assert('id', '\d*')
            ->bind('relatedto');

        $ctl->match('/editcontent/{contenttypeslug}/{id}', array($this, 'editContent'))
            ->assert('id', '\d*')
            ->value('id', '')
            ->bind('editcontent');

        $ctl->get('/content/deletecontent/{contenttypeslug}/{id}', array($this, 'deleteContent'))
            ->bind('deletecontent');

        $ctl->post('/content/{action}/{contenttypeslug}/{id}', array($this, 'contentAction'))
            ->bind('contentaction');

        $ctl->get('/systemlog', array($this, 'systemLog'))
            ->bind('systemlog');

        $ctl->get('/changelog', array($this, 'changeLog'))
            ->bind('changelog');

        $ctl->get('/changelog/{contenttype}/{contentid}', array($this, 'changelogRecordAll'))
            ->value('contentid', '0')
            ->value('contenttype', '')
            ->bind('changelogrecordall');

        $ctl->get('/changelog/{contenttype}/{contentid}/{id}', array($this, 'changelogRecordSingle'))
            ->assert('id', '\d*')
            ->bind('changelogrecordsingle');

        $ctl->get('/users', array($this, 'users'))
            ->bind('users');

        $ctl->match('/users/edit/{id}', array($this, 'userEdit'))
            ->assert('id', '\d*')
            ->bind('useredit');

        $ctl->match('/userfirst', array($this, 'userFirst'))
            ->bind('userfirst');

        $ctl->match('/profile', array($this, 'profile'))
            ->bind('profile');

        $ctl->get('/roles', array($this, 'roles'))
            ->bind('roles');

        $ctl->get('/about', array($this, 'about'))
            ->bind('about');

        $ctl->post('/user/{action}/{id}', array($this, 'userAction'))
            ->bind('useraction');

        $ctl->match('/files/{namespace}/{path}', array($this, 'files'))
            ->assert('namespace', '[^/]+')
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('files');

        $ctl->match('/file/edit/{namespace}/{file}', array($this, 'fileEdit'))
            ->assert('file', '.+')
            ->assert('namespace', '[^/]+')
            ->value('namespace', 'files')
            ->bind('fileedit')
            // Middleware to disable browser XSS protection whilst we throw code around
            ->after(function(Request $request, Response $response) {
                if ($request->getMethod() == "POST") {
                    $response->headers->set('X-XSS-Protection', '0');
                }
            });

        $ctl->match('/tr/{domain}/{tr_locale}', array($this, 'translation'))
            ->assert('domain', 'messages|contenttypes|infos')
            ->value('domain', 'messages')
            ->value('tr_locale', $app['locale'])
            ->bind('translation');

        $ctl->get('/omnisearch', array($this, 'omnisearch'))
            ->bind('omnisearch');

        return $ctl;
    }

    /**
     * Dashboard or "root".
     *
     * @param Application $app The application/container
     *
     * @return mixed
     */
    public function dashboard(Application $app)
    {
        $limit = $app['config']->get('general/recordsperdashboardwidget');

        $total = 0;
        $latest = array();
        // get the 'latest' from each of the content types.
        foreach ($app['config']->get('contenttypes') as $key => $contenttype) {
            if ($app['users']->isAllowed('contenttype:' . $key) && $contenttype['show_on_dashboard'] === true) {
                $latest[$key] = $app['storage']->getContent($key, array('limit' => $limit, 'order' => 'datechanged DESC', 'hydrate' => false));
                if (!empty($latest[$key])) {
                    $total += count($latest[$key]);
                }
            }
        }

        $context = array(
            'latest'          => $latest,
            'suggestloripsum' => ($total == 0), // Nothing in the DB, then suggest to create some dummy content.
        );

        return $app['render']->render('dashboard/dashboard.twig', array('context' => $context));
    }

    /**
     * Check the database for missing tables and columns. Does not do actual repairs.
     *
     * @param Application $app The application/container
     *
     * @return mixed
     */
    public function dbCheck(Application $app)
    {
        list($messages, $hints) = $app['integritychecker']->checkTablesIntegrity(true, $app['logger']);

        $context = array(
            'modifications_made'     => null,
            'modifications_required' => $messages,
            'modifications_hints'    => $hints,
        );

        return $app['render']->render('dbcheck/dbcheck.twig', array('context' => $context));
    }

    /**
     * Check the database, create tables, add missing/new columns to tables.
     *
     * @param Application $app The application/container
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function dbUpdate(Application $app)
    {
        $output = $app['integritychecker']->repairTables();

        // If 'return=edit' is passed, we should return to the edit screen. We do redirect twice, yes,
        // but that's because the newly saved contenttype.yml needs to be re-read.
        $return = $app['request']->get('return');
        if ($return == 'edit') {
            if (empty($output)) {
                $content = Trans::__('Your database is already up to date.');
            } else {
                $content = Trans::__('Your database is now up to date.');
            }
            $app['session']->getFlashBag()->add('success', $content);

            return Lib::redirect('fileedit', array('file' => 'app/config/contenttypes.yml'));
        } else {
            return Lib::redirect('dbupdate_result', array('messages' => json_encode($output)));
        }
    }

    /**
     * Show the result of database updates.
     *
     * @param Application $app     The application/container
     * @param Request     $request The Symfony Request
     *
     * @return \Twig_Markup
     */
    public function dbUpdateResult(Application $app, Request $request)
    {
        $context = array(
            'modifications_made'     => json_decode($request->get('messages')),
            'modifications_required' => null,
        );

        return $app['render']->render('dbcheck/dbcheck.twig', array('context' => $context));
    }

    /**
     * Clear the cache.
     *
     * @param Application $app The application/container
     *
     * @return \Twig_Markup
     */
    public function clearCache(Application $app)
    {
        $result = $app['cache']->clearCache();

        $output = Trans::__('Deleted %s files from cache.', array('%s' => $result['successfiles']));

        if (!empty($result['failedfiles'])) {
            $output .= ' ' . Trans::__('%s files could not be deleted. You should delete them manually.', array('%s' => $result['failedfiles']));
            $app['session']->getFlashBag()->add('error', $output);
        } else {
            $app['session']->getFlashBag()->add('success', $output);
        }

        return $app['render']->render('clearcache/clearcache.twig');
    }

    /**
     * Show the system log.
     *
     * @param \Silex\Application $app The application/container
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function systemLog(Application $app)
    {
        $action = $app['request']->query->get('action');

        if ($action == 'clear') {
            $app['logger.manager']->clear('system');
            $app['session']->getFlashBag()->add('success', Trans::__('The system log has been cleared.'));

            return Lib::redirect('systemlog');
        } elseif ($action == 'trim') {
            $app['logger.manager']->trim('system');
            $app['session']->getFlashBag()->add('success', Trans::__('The system log has been trimmed.'));

            return Lib::redirect('systemlog');
        }

        $level = $app['request']->query->get('level');
        $context = $app['request']->query->get('context');

        $activity = $app['logger.manager']->getActivity('system', 16, $level, $context);

        return $app['render']->render('activity/systemlog.twig', array('entries' => $activity));
    }

    /**
     * Show the change log.
     *
     * @param Application $app The application/container
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changeLog(Application $app)
    {
        $action = $app['request']->query->get('action');

        if ($action == 'clear') {
            $app['logger.manager']->clear('change');
            $app['session']->getFlashBag()->add('success', Trans::__('The change log has been cleared.'));

            return Lib::redirect('changelog');
        } elseif ($action == 'trim') {
            $app['logger.manager']->trim('change');
            $app['session']->getFlashBag()->add('success', Trans::__('The change log has been trimmed.'));

            return Lib::redirect('changelog');
        }

        $activity = $app['logger.manager']->getActivity('change', 16);

        return $app['render']->render('activity/changelog.twig', array('entries' => $activity));
    }

    /**
     * Show changelog entries.
     *
     * @param string             $contenttype The content type slug
     * @param integer            $contentid   The content ID
     * @param \Silex\Application $app         The application/container
     * @param Request            $request     The Symfony Request
     *
     * @return \Twig_Markup
     */
    public function changelogRecordAll($contenttype, $contentid, Application $app, Request $request)
    {
        // We have to handle three cases here:
        // - $contenttype and $contentid given: get changelog entries for *one* content item
        // - only $contenttype given: get changelog entries for all items of that type
        // - neither given: get all changelog entries

        // But first, let's get some pagination stuff out of the way.
        $limit = 5;
        if ($page = $request->get('page')) {
            if ($page === 'all') {
                $limit = null;
                $page = null;
            } else {
                $page = intval($page);
            }
        } else {
            $page = 1;
        }

        // Some options that are the same for all three cases
        $options = array(
            'order'     => 'date',
            'direction' => 'DESC'
            );
        if ($limit) {
            $options['limit'] = $limit;
        }
        if ($page > 0 && $limit) {
            $options['offset'] = ($page - 1) * $limit;
        }

        $content = null;

        // Now here things diverge.

        if (empty($contenttype)) {
            // Case 1: No content type given, show from *all* items.
            // This is easy:
            $title = Trans::__('All content types');
            $logEntries = $app['logger.manager.change']->getChangelog($options);
            $itemcount = $app['logger.manager.change']->countChangelog($options);
        } else {
            // We have a content type, and possibly a contentid.
            $contenttypeObj = $app['storage']->getContentType($contenttype);
            if ($contentid) {
                $content = $app['storage']->getContent($contenttype, array('id' => $contentid, 'hydrate' => false));
                $options['contentid'] = $contentid;
            }
            // Getting a slice of data and the total count
            $logEntries = $app['logger.manager.change']->getChangelogByContentType($contenttype, $options);
            $itemcount = $app['logger.manager.change']->countChangelogByContentType($contenttype, $options);

            // The page title we're sending to the template depends on a few
            // things: if no contentid is given, we'll use the plural form
            // of the content type; otherwise, we'll derive it from the
            // changelog or content item itself.
            if ($contentid) {
                if ($content) {
                    // content item is available: get the current title
                    $title = $content->getTitle();
                } else {
                    // content item does not exist (anymore).
                    if (empty($logEntries)) {
                        // No item, no entries - phew. Content type name and ID
                        // will have to do.
                        $title = $contenttypeObj['singular_name'] . ' #' . $contentid;
                    } else {
                        // No item, but we can use the most recent title.
                        $title = $logEntries[0]['title'];
                    }
                }
            } else {
                // We're displaying all changes for the entire content type,
                // so the plural name is most appropriate.
                $title = $contenttypeObj['name'];
            }
        }

        // Now calculate number of pages.
        // We can't easily do this earlier, because we only have the item count
        // now.
        // Note that if either $limit or $pagecount is empty, the template will
        // skip rendering the pager.
        $pagecount = $limit ? ceil($itemcount / $limit) : null;

        $context = array(
            'contenttype' => array('slug' => $contenttype),
            'entries'     => $logEntries,
            'content'     => $content,
            'title'       => $title,
            'currentpage' => $page,
            'pagecount'   => $pagecount
        );

        return $app['render']->render('changelog/changelogrecordall.twig', array('context' => $context));
    }

    /**
     * Show changelog details.
     *
     * @param string             $contenttype The content type slug
     * @param integer            $contentid   The content ID
     * @param integer            $id          The changelog entry ID
     * @param \Silex\Application $app         The application/container
     *
     * @return \Twig_Markup|null
     */
    public function changelogRecordSingle($contenttype, $contentid, $id, Application $app)
    {
        $entry = $app['logger.manager.change']->getChangelogEntry($contenttype, $contentid, $id);
        if (empty($entry)) {
            $error = Trans::__("The requested changelog entry doesn't exist.");
            return $app->abort(Response::HTTP_NOT_FOUND, $error);
        }
        $prev = $app['logger.manager.change']->getPrevChangelogEntry($contenttype, $contentid, $id);
        $next = $app['logger.manager.change']->getNextChangelogEntry($contenttype, $contentid, $id);

        $context = array(
            'contenttype' => array('slug' => $contenttype),
            'entry'       => $entry,
            'next_entry'  => $next,
            'prev_entry'  => $prev
        );

        return $app['render']->render('changelog/changelogrecordsingle.twig', array('context' => $context));
    }

    /**
     * Show the Omnisearch results.
     *
     * @param Application $app The application/container
     *
     * @return \Twig_Markup
     */
    public function omnisearch(Application $app)
    {
        $query = $app['request']->query->get('q', '');
        $results = array();

        if (strlen($query) >= 3) {
            $results = $app['omnisearch']->query($query, true);
        }

        $context = array(
            'query'   => $query,
            'results' => $results
        );

        return $app['render']->render('omnisearch/omnisearch.twig', array('context' => $context));
    }

    /**
     * Generate some lipsum in the DB.
     *
     * @param Application $app     The application/container
     * @param Request     $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function prefill(Application $app, Request $request)
    {
        $choices = array();
        foreach ($app['config']->get('contenttypes') as $key => $cttype) {
            $namekey = 'contenttypes.' . $key . '.name.plural';
            $name = Trans::__($namekey, array(), 'contenttypes');
            $choices[$key] = ($name == $namekey) ? $cttype['name'] : $name;
        }
        $form = $app['form.factory']
            ->createBuilder('form')
            ->add('contenttypes', 'choice', array(
                'choices'  => $choices,
                'multiple' => true,
                'expanded' => true,
            ))
            ->getForm();

        if ($request->isMethod('POST') || ($request->get('force') == 1)) {
            $form->submit($request);
            $ctypes = $form->get('contenttypes')->getData();

            try {
                $content = $app['storage']->preFill($ctypes);
                $app['session']->getFlashBag()->add('success', $content);
            } catch (RequestException $e) {
                $msg = "Timeout attempting to the 'Lorem Ipsum' generator. Unable to add dummy content.";
                $app['session']->getFlashBag()->add('error', $msg);
                $app['logger.system']->error($msg, array('event' => 'storage'));
            } catch (V3RequestException $e) {
                /** @deprecated removed when PHP 5.3 support is dropped */
                $msg = "Timeout attempting to the 'Lorem Ipsum' generator. Unable to add dummy content.";
                $app['session']->getFlashBag()->add('error', $msg);
                $app['logger.system']->error($msg, array('event' => 'storage'));
            }

            return Lib::redirect('prefill');
        }

        $context = array(
            'contenttypes' => $choices,
            'form'         => $form->createView(),
        );

        return $app['render']->render('prefill/prefill.twig', array('context' => $context));
    }

    /**
     * Content type overview page.
     *
     * @param Application $app             The application/container
     * @param string      $contenttypeslug The content type slug
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function overview(Application $app, $contenttypeslug)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$app['users']->isAllowed('contenttype:' . $contenttypeslug)) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to view that page.'));

            return Lib::redirect('dashboard');
        }

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $filter = array();

        $contentparameters = array('paging' => true, 'hydrate' => true);

        // Order has to be set carefully. Either set it explicitly when the user
        // sorts, or fall back to what's defined in the contenttype. The exception
        // is a contenttype that has a "grouping taxonomy", because that should
        // override it. The exception is handled in $app['storage']->getContent().
        $contentparameters['order'] = $app['request']->query->get('order', $contenttype['sort']);
        $contentparameters['page'] = $app['request']->query->get('page');

        if ($app['request']->query->get('filter')) {
            $contentparameters['filter'] = $app['request']->query->get('filter');
            $filter[] = $app['request']->query->get('filter');
        }

        // Set the amount of items to show per page.
        if (!empty($contenttype['recordsperpage'])) {
            $contentparameters['limit'] = $contenttype['recordsperpage'];
        } else {
            $contentparameters['limit'] = $app['config']->get('general/recordsperpage');
        }

        // Perhaps also filter on taxonomies
        foreach ($app['config']->get('taxonomy') as $taxonomykey => $taxonomy) {
            if ($app['request']->query->get('taxonomy-' . $taxonomykey)) {
                $contentparameters[$taxonomykey] = $app['request']->query->get('taxonomy-' . $taxonomykey);
                $filter[] = $app['request']->query->get('taxonomy-' . $taxonomykey);
            }
        }

        $multiplecontent = $app['storage']->getContent($contenttype['slug'], $contentparameters);

        $context = array(
            'contenttype'     => $contenttype,
            'multiplecontent' => $multiplecontent,
            'filter'          => $filter
        );

        return $app['render']->render('overview/overview.twig', array('context' => $context));
    }

    /**
     * Get related records @todo.
     *
     * @param string      $contenttypeslug The content type slug
     * @param integer     $id              The ID
     * @param Application $app             The application/container
     * @param Request     $request         The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function relatedTo($contenttypeslug, $id, Application $app, Request $request)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes' for Editors.
        if (!$app['users']->isAllowed('contenttype:' . $contenttypeslug)) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to edit that record.'));

            return Lib::redirect('dashboard');
        }

        $showContenttype = null;

        // Get contenttype config from $contenttypeslug
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // Get relations
        if (isset($contenttype['relations'])) {
            $relations = $contenttype['relations'];

            // Which related contenttype is to be shown?
            // If non is selected or selection does not exist, take the first one
            $showSlug = $request->get('show') ? $request->get('show') : null;
            if (!isset($relations[$showSlug])) {
                reset($relations);
                $showSlug = key($relations);
            }

            foreach (array_keys($relations) as $relatedslug) {
                $relatedtype = $app['storage']->getContentType($relatedslug);
                if ($relatedtype['slug'] == $showSlug) {
                    $showContenttype = $relatedtype;
                }
                $relations[$relatedslug] = array(
                    'name'   => Trans::__($relatedtype['name']),
                    'active' => ($relatedtype['slug'] == $showSlug),
                );
            }
        } else {
            $relations = null;
        }

        /**
         * TODO: Set the amount of items to show per page.
         * if (empty($contenttype['recordsperpage'])) {
         *     $limit = $app['config']->get('general/recordsperpage');
         * } else {
         *    $limit = $contenttype['recordsperpage'];
         * }.
         */

        $content = $app['storage']->getContent($contenttypeslug, array('id' => $id));

        $context = array(
            'id'               => $id,
            'name'             => Trans::__($contenttype['singular_name']),
            'title'            => $content['title'],
            'contenttype'      => $contenttype,
            'relations'        => $relations,
            'show_contenttype' => $showContenttype,
            'related_content'  => is_null($relations) ? null : $content->related($showContenttype['slug']),
        );

        return $app['render']->render('relatedto/relatedto.twig', array('context' => $context));
    }

    /**
     * Edit a unit of content, or create a new one.
     *
     * @param string      $contenttypeslug The content type slug
     * @param integer     $id              The content ID
     * @param Application $app             The application/container
     * @param Request     $request         The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editContent($contenttypeslug, $id, Application $app, Request $request)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (empty($id)) {
            $perm = "contenttype:$contenttypeslug:create";
            $new = true;
        } else {
            $perm = "contenttype:$contenttypeslug:edit:$id";
            $new = false;
        }
        if (!$app['users']->isAllowed($perm)) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to edit that record.'));

            return Lib::redirect('dashboard');
        }

        // set the editreferrer in twig if it was not set yet.
        $tmp = parse_url($app['request']->server->get('HTTP_REFERER'));

        $tmpreferrer = $tmp['path'];
        if (!empty($tmp['query'])) {
            $tmpreferrer .= '?' . $tmp['query'];
        }

        if (strpos($tmpreferrer, '/overview/') !== false || ($tmpreferrer == $app['paths']['bolt'])) {
            $app['twig']->addGlobal('editreferrer', $tmpreferrer);
        }

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        if ($request->isMethod('POST')) {
            if (!$app['users']->checkAntiCSRFToken()) {
                $app->abort(Response::HTTP_BAD_REQUEST, Trans::__('Something went wrong'));
            }
            if (!empty($id)) {
                // Check if we're allowed to edit this content.
                if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:edit:$id")) {
                    $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to edit that record.'));

                    return Lib::redirect('dashboard');
                }
            } else {
                // Check if we're allowed to create content.
                if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:create")) {
                    $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to create a new record.'));

                    return Lib::redirect('dashboard');
                }
            }

            // If we have an ID now, this is an existing record
            if ($id) {
                $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id, 'status' => '!undefined'));
                $oldStatus = $content['status'];
            } else {
                $content = $app['storage']->getContentObject($contenttypeslug);
                $oldStatus = '';
            }

            // Add non successfull control values to request values
            // http://www.w3.org/TR/html401/interact/forms.html#h-17.13.2
            // Also do some corrections
            $requestAll = $request->request->all();

            foreach ($contenttype['fields'] as $key => $values) {
                if (isset($requestAll[$key])) {
                    switch ($values['type']) {
                        case 'float':
                            // We allow ',' and '.' as decimal point and need '.' internally
                            $requestAll[$key] = str_replace(',', '.', $requestAll[$key]);
                            break;
                    }
                } else {
                    switch ($values['type']) {
                        case 'select':
                            if (isset($values['multiple']) && $values['multiple'] === true) {
                                $requestAll[$key] = array();
                            }
                            break;

                        case 'checkbox':
                            $requestAll[$key] = 0;
                            break;
                    }
                }
            }

            // To check whether the status is allowed, we act as if a status
            // *transition* were requested.
            $content->setFromPost($requestAll, $contenttype);
            $newStatus = $content['status'];

            // Don't try to spoof the $id.
            if (!empty($content['id']) && $id != $content['id']) {
                $app['session']->getFlashBag()->add('error', "Don't try to spoof the id!");

                return Lib::redirect('dashboard');
            }

            // Save the record, and return to the overview screen, or to the record (if we clicked 'save and continue')
            $statusOK = $app['users']->isContentStatusTransitionAllowed($oldStatus, $newStatus, $contenttype['slug'], $id);
            if ($statusOK) {
                // Get the associate record change comment
                $comment = $request->request->get('changelog-comment');

                // Save the record
                $id = $app['storage']->saveContent($content, $comment);

                // Log the change
                if ($new) {
                    $app['session']->getFlashBag()->add('success', Trans::__('contenttypes.generic.saved-new', array('%contenttype%' => $contenttypeslug)));
                    $app['logger.system']->info('Created: ' . $content->getTitle(), array('event' => 'content'));
                } else {
                    $app['session']->getFlashBag()->add('success', Trans::__('contenttypes.generic.saved-changes', array('%contenttype%' => $contenttype['slug'])));
                    $app['logger.system']->info('Saved: ' . $content->getTitle(), array('event' => 'content'));
                }

                /*
                 * We now only get a returnto parameter if we are saving a new
                 * record and staying on the same page, i.e. "Save {contenttype}"
                 */
                if ($app['request']->get('returnto')) {
                    $returnto = $app['request']->get('returnto');

                    if ($returnto === 'new') {
                        return Lib::redirect('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => $id), '#' . $app['request']->get('returnto'));
                    } elseif ($returnto == 'saveandnew') {
                        return Lib::redirect('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => 0), '#' . $app['request']->get('returnto'));
                    } elseif ($returnto === 'ajax') {
                        /*
                         * Flush any buffers from saveContent() dispatcher hooks
                         * and make sure our JSON output is clean.
                         *
                         * Currently occurs due to a 404 exception being generated
                         * in \Bolt\Storage::saveContent() dispatchers:
                         *     $this->app['dispatcher']->dispatch(StorageEvents::PRE_SAVE, $event);
                         *     $this->app['dispatcher']->dispatch(StorageEvents::POST_SAVE, $event);
                         */
                        if (ob_get_length()) {
                            ob_end_clean();
                        }

                        // Get our record after POST_SAVE hooks are dealt with and return the JSON
                        $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id, 'returnsingle' => true, 'status' => '!undefined'));

                        $val = array();

                        foreach ($content->values as $key => $value) {
                            // Some values are returned as \Twig_Markup and JSON can't deal with that
                            if (is_array($value)) {
                                foreach ($value as $subkey => $subvalue) {
                                    if (gettype($subvalue) == 'object' && get_class($subvalue) == 'Twig_Markup') {
                                        $val[$key][$subkey] = $subvalue->__toString();
                                    }
                                }
                            } else {
                                $val[$key] = $value;
                            }
                        }

                        if (isset($val['datechanged'])) {
                            $val['datechanged'] = date_format(new \DateTime($val['datechanged']), 'c');
                        }

                        $lc = localeconv();
                        foreach ($contenttype['fields'] as $key => $values) {
                            switch ($values['type']) {
                                case 'float':
                                    // Adjust decimal point dependent on locale
                                    if ($lc['decimal_point'] === ',') {
                                        $val[$key] = str_replace('.', ',', $val[$key]);
                                    }
                                    break;
                            }
                        }

                        // unset flashbag for ajax
                        $app['session']->getFlashBag()->clear('success');

                        return new JsonResponse($val);
                    }
                }

                // No returnto, so we go back to the 'overview' for this contenttype.
                // check if a pager was set in the referrer - if yes go back there
                $editreferrer = $app['request']->get('editreferrer');
                if ($editreferrer) {
                    Lib::simpleredirect($editreferrer, true);
                } else {
                    return Lib::redirect('overview', array('contenttypeslug' => $contenttype['slug']));
                }
            } else {
                $app['session']->getFlashBag()->add('error', Trans::__('contenttypes.generic.error-saving', array('%contenttype%' => $contenttype['slug'])));
                $app['logger.system']->error('Save error: ' . $content->getTitle(), array('event' => 'content'));
            }
        }

        // We're doing a GET
        if (!empty($id)) {
            $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id));

            if (empty($content)) {
                return $app->abort(Response::HTTP_NOT_FOUND, Trans::__('contenttypes.generic.not-existing', array('%contenttype%' => $contenttype['slug'])));
            }

            // Check if we're allowed to edit this content.
            if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:edit:{$content['id']}")) {
                $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to edit that record.'));

                return Lib::redirect('dashboard');
            }
        } else {
            // Check if we're allowed to create content.
            if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:create")) {
                $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to create a new record.'));

                return Lib::redirect('dashboard');
            }

            $content = $app['storage']->getEmptyContent($contenttype['slug']);
        }

        $oldStatus = $content['status'];
        $allStatuses = array('published', 'held', 'draft', 'timed');
        $allowedStatuses = array();
        foreach ($allStatuses as $status) {
            if ($app['users']->isContentStatusTransitionAllowed($oldStatus, $status, $contenttype['slug'], $id)) {
                $allowedStatuses[] = $status;
            }
        }

        $duplicate = $app['request']->query->get('duplicate');
        if (!empty($duplicate)) {
            $content->setValue('id', '');
            $content->setValue('slug', '');
            $content->setValue('datecreated', '');
            $content->setValue('datepublish', '');
            $content->setValue('datedepublish', null);
            $content->setValue('datechanged', '');
            $content->setValue('username', '');
            $content->setValue('ownerid', '');
            // $content->setValue('templatefields', array());
            $app['session']->getFlashBag()->add('info', Trans::__('contenttypes.generic.duplicated-finalize', array('%contenttype%' => $contenttype['slug'])));
        }

        // Set the users and the current owner of this content.
        if (empty($id) || $duplicate) {
            // For brand-new and duplicated items, the creator becomes the owner.
            $contentowner = $app['users']->getCurrentUser();
        } else {
            // For existing items, we'll just keep the current owner.
            $contentowner = $app['users']->getUser($content['ownerid']);
        }

        $filesystem = $app['filesystem']->getFilesystem();

        // Test write access for uploadable fields
        foreach ($contenttype['fields'] as $key => &$values) {
            if (isset($values['upload'])) {
                $values['canUpload'] = $filesystem->has($values['upload']) && $filesystem->getVisibility($values['upload']);
            } else {
                $values['canUpload'] = true;
            }
        }

        if ((!empty($content['templatefields'])) && (!empty($content['templatefields']->contenttype['fields']))) {
            foreach ($content['templatefields']->contenttype['fields'] as $key => &$values) {
                if (isset($values['upload'])) {
                    $values['canUpload'] = $filesystem->has($values['upload']) && $filesystem->getVisibility($values['upload']);
                } else {
                    $values['canUpload'] = true;
                }
            }
        }

        // Determine which templates will result in templatefields
        $templateFieldTemplates = array();
        if ($templateFieldsConfig = $app['config']->get('theme/templatefields')) {
            $templateFieldTemplates = array_keys($templateFieldsConfig);
            // Special case for default template
            $toRepair = array();
            foreach ($contenttype['fields'] as $name => $field) {
                if ($field['type'] == 'templateselect' && !empty($content->values[$name])) {
                    $toRepair[$name] = $content->values[$name];
                    $content->setValue($name, '');
                }
            }
            if ($content->hasTemplateFields()) {
                $templateFieldTemplates[] = '';
            }

            foreach ($toRepair as $name => $value) {
                $content->setValue($name, $value);
            }
        }

        // Info
        $hasIncomingRelations = is_array($content->relation);
        $hasRelations = isset($contenttype['relations']);
        $hasTabs = $contenttype['groups'] !== false;
        $hasTaxonomy = isset($contenttype['taxonomy']);
        $hasTemplateFields = $content->hasTemplateFields();

        // Generate tab groups
        $groups = array();
        $groupIds = array();

        $addGroup = function ($group, $label) use (&$groups, &$groupIds) {
            $nr = count($groups) + 1;
            $id = rtrim('tab-' . Slugify::create()->slugify($group), '-');
            if (isset($groupIds[$id]) || $id == 'tab') {
                $id .= '-' . $nr;
            }
            $groups[$group] = array(
                'label'     => $label,
                'id'        => $id,
                'is_active' => $nr === 1,
            );
            $groupIds[$id] = 1;
        };

        foreach ($contenttype['groups'] ? $contenttype['groups'] : array('ungrouped') as $group) {
            if ($group === 'ungrouped') {
                $addGroup($group, Trans::__('contenttypes.generic.group.ungrouped'));
            } elseif ($group !== 'meta' && $group !== 'relations' && $group !== 'taxonomy') {
                $default = array('DEFAULT' => ucfirst($group));
                $key = array('contenttypes', $contenttype['slug'], 'group', $group);
                $addGroup($group, Trans::__($key, $default));
            }
        }
        if ($hasRelations || $hasIncomingRelations) {
            $addGroup('relations', Trans::__('contenttypes.generic.group.relations'));
        }
        if ($hasTaxonomy || (is_array($contenttype['groups']) && in_array('taxonomy', $contenttype['groups']))) {
            $addGroup('taxonomy', Trans::__('contenttypes.generic.group.taxonomy'));
        }
        if ($hasTemplateFields || (is_array($contenttype['groups']) && in_array('template', $contenttype['groups']))) {
            $addGroup('template', Trans::__('Template'));
        }

        $addGroup('meta', Trans::__('contenttypes.generic.group.meta'));

        // Render

        $context = array(
            'contenttype'    => $contenttype,
            'content'        => $content,
            'allowed_status' => $allowedStatuses,
            'contentowner'   => $contentowner,
            'fields'         => $app['config']->fields->fields(),
            'fieldtemplates' => $templateFieldTemplates,
            'can_upload'     => $app['users']->isAllowed('files:uploads'),
            'groups'         => $groups,
            'has'            => array(
                'incoming_relations' => $hasIncomingRelations,
                'relations'          => $hasRelations,
                'tabs'               => $hasTabs,
                'taxonomy'           => $hasTaxonomy,
                'templatefields'     => $hasTemplateFields,
            ),
        );

        return $app['render']->render('editcontent/editcontent.twig', array('context' => $context));
    }

    /**
     * Deletes a content item.
     *
     * @param Application    $app             The application/container
     * @param string         $contenttypeslug The content type slug
     * @param integer|string $id              The content ID or comma-delimited list of IDs
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteContent(Application $app, $contenttypeslug, $id)
    {
        $ids = explode(',', $id);
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        foreach ($ids as $id) {
            $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id, 'status' => '!undefined'));
            $title = $content->getTitle();

            if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:delete:$id")) {
                $app['session']->getFlashBag()->add('error', Trans::__('Permission denied', array()));
            } elseif ($app['users']->checkAntiCSRFToken() && $app['storage']->deleteContent($contenttype['slug'], $id)) {
                $app['session']->getFlashBag()->add('info', Trans::__("Content '%title%' has been deleted.", array('%title%' => $title)));
            } else {
                $app['session']->getFlashBag()->add('info', Trans::__("Content '%title%' could not be deleted.", array('%title%' => $title)));
            }
        }

        // get the parameters from the URL of the previous page, so we can return to it.
        $redirectParameters = Lib::getQueryParameters($app['request']->server->get('HTTP_REFERER'));
        $redirectParameters['contenttypeslug'] = $contenttype['slug'];

        return Lib::redirect('overview', $redirectParameters);
    }

    /**
     * Perform actions on content.
     *
     * @param Application $app             The application/container
     * @param string      $action          The action
     * @param string      $contenttypeslug The content type slug
     * @param integer     $id              The content ID
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function contentAction(Application $app, $action, $contenttypeslug, $id)
    {
        if ($action === 'delete') {
            return $this->deleteContent($app, $contenttypeslug, $id);
        }
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $content = $app['storage']->getContent($contenttype['slug'] . '/' . $id);
        $title = $content->getTitle();

        // get the parameters from the URL of the previous page, so we can return to it.
        $redirectParameters = Lib::getQueryParameters($app['request']->server->get('HTTP_REFERER'));
        $redirectParameters['contenttypeslug'] = $contenttype['slug'];

        // map actions to new statuses
        $actionStatuses = array(
            'held'    => 'held',
            'publish' => 'published',
            'draft'   => 'draft',
        );
        if (!isset($actionStatuses[$action])) {
            $app['session']->getFlashBag()->add('error', Trans::__('No such action for content.'));

            return Lib::redirect('overview', $redirectParameters);
        }
        $newStatus = $actionStatuses[$action];

        if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:edit:$id") ||
            !$app['users']->isContentStatusTransitionAllowed($content['status'], $newStatus, $contenttype['slug'], $id)) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to edit that record.'));

            return Lib::redirect('overview', $redirectParameters);
        }

        if ($app['storage']->updateSingleValue($contenttype['slug'], $id, 'status', $newStatus)) {
            $app['session']->getFlashBag()->add('info', Trans::__("Content '%title%' has been changed to '%newStatus%'", array('%title%' => $title, '%newStatus%' => $newStatus)));
        } else {
            $app['session']->getFlashBag()->add('info', Trans::__("Content '%title%' could not be modified.", array('%title%' => $title)));
        }

        return Lib::redirect('overview', $redirectParameters);
    }

    /**
     * Show a list of all available users.
     *
     * @param Application $app The application/container
     *
     * @return \Twig_Markup
     */
    public function users(Application $app)
    {
        $currentuser = $app['users']->getCurrentUser();
        $users = $app['users']->getUsers();
        $sessions = $app['users']->getActiveSessions();

        foreach ($users as $name => $user) {
            if (($key = array_search(Permissions::ROLE_EVERYONE, $user['roles'], true)) !== false) {
                unset($users[$name]['roles'][$key]);
            }
        }

        $context = array(
            'currentuser' => $currentuser,
            'users'       => $users,
            'sessions'    => $sessions
        );

        return $app['render']->render('users/users.twig', array('context' => $context));
    }

    /**
     * Show the roles page.
     *
     * @param Application $app The application/container
     *
     * @return \Twig_Markup
     */
    public function roles(Application $app)
    {
        $contenttypes = $app['config']->get('contenttypes');
        $permissions = array('view', 'edit', 'create', 'publish', 'depublish', 'change-ownership');
        $effectivePermissions = array();
        foreach ($contenttypes as $contenttype) {
            foreach ($permissions as $permission) {
                $effectivePermissions[$contenttype['slug']][$permission] =
                    $app['permissions']->getRolesByContentTypePermission($permission, $contenttype['slug']);
            }
        }
        $globalPermissions = $app['permissions']->getGlobalRoles();

        $context = array(
            'effective_permissions' => $effectivePermissions,
            'global_permissions'    => $globalPermissions,
        );

        return $app['render']->render('roles/roles.twig', array('context' => $context));
    }

    /**
     * Edit a user.
     *
     * @param integer     $id      The user ID
     * @param Application $app     The application/container
     * @param Request     $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function userEdit($id, Application $app, Request $request)
    {
        $currentuser = $app['users']->getCurrentUser();

        // Get the user we want to edit (if any)
        if (!empty($id)) {
            $user = $app['users']->getUser($id);

            if (is_array($user)) {
                // Verify the current user has access to edit this user
                if (!$app['permissions']->isAllowedToManipulate($user, $currentuser)) {
                    $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to edit that user.'));

                    return Lib::redirect('users');
                }
            } else {
                $app['session']->getFlashBag()->add('error', Trans::__('No such user.'));

                return Lib::redirect('users');
            }
        } else {
            $user = $app['users']->getEmptyUser();
        }

        $enabledoptions = array(
            1 => Trans::__('page.edit-users.activated.yes'),
            0 => Trans::__('page.edit-users.activated.no')
        );

        $roles = array_map(
            function ($role) {
                return $role['label'];
            },
            $app['permissions']->getDefinedRoles()
        );

        $form = $this->getUserForm($app, $user, true);

        // New users and the current users don't need to disable themselves
        if ($currentuser['id'] != $id) {
            $form->add(
                'enabled',
                'choice',
                array(
                    'choices'     => $enabledoptions,
                    'expanded'    => false,
                    'constraints' => new Assert\Choice(array_keys($enabledoptions)),
                    'label'       => Trans::__('page.edit-users.label.user-enabled'),
                )
            );
        }

        $form
            ->add(
                'roles',
                'choice',
                array(
                    'choices'  => $roles,
                    'expanded' => true,
                    'multiple' => true,
                    'label'    => Trans::__('page.edit-users.label.assigned-roles')
                )
            )
            ->add(
                'lastseen',
                'text',
                array(
                    'disabled' => true,
                    'label'    => Trans::__('page.edit-users.label.last-seen')
                )
            )
            ->add(
                'lastip',
                'text',
                array(
                    'disabled' => true,
                    'label'    => Trans::__('page.edit-users.label.last-ip')
                )
            );

        // Set the validation
        $form = $this->setUserFormValidation($app, $form, true);

        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST')) {
            $user = $this->validateUserForm($app, $form);

            $currentuser = $app['users']->getCurrentUser();

            if ($user !== false && $user['id'] === $currentuser['id'] && $user['username'] !== $currentuser['username']) {
                // If the current user changed their own login name, the session is effectively
                // invalidated. If so, we must redirect to the login page with a flash message.
                $app['session']->getFlashBag()->add('error', Trans::__('page.edit-users.message.change-self'));

                return Lib::redirect('login');
            } elseif ($user !== false) {
                // Return to the 'Edit users' screen.
                return Lib::redirect('users');
            }
        }

        /** @var \Symfony\Component\Form\FormView|\Symfony\Component\Form\FormView[] $formView */
        $formView = $form->createView();

        $manipulatableRoles = $app['permissions']->getManipulatableRoles($currentuser);
        foreach ($formView['roles'] as $role) {
            if (!in_array($role->vars['value'], $manipulatableRoles)) {
                $role->vars['attr']['disabled'] = 'disabled';
            }
        }

        $context = array(
            'kind'        => empty($id) ? 'create' : 'edit',
            'form'        => $formView,
            'note'        => '',
            'displayname' => $user['displayname'],
        );

        return $app['render']->render('edituser/edituser.twig', array('context' => $context));
    }

    /**
     * Create the first user.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function userFirst(Application $app, Request $request)
    {
        // We should only be here for creating the first user
        if ($app['integritychecker']->checkUserTableIntegrity() && $app['users']->hasUsers()) {
            return Lib::redirect('dashboard');
        }

        // Get and empty user array
        $user = $app['users']->getEmptyUser();

        // Add a note, if we're setting up the first user using SQLite.
        $dbdriver = $app['config']->get('general/database/driver');
        if ($dbdriver === 'sqlite' || $dbdriver === 'pdo_sqlite') {
            $note = Trans::__('page.edit-users.note-sqlite');
        } else {
            $note = '';
        }

        // If we get here, chances are we don't have the tables set up, yet.
        $app['integritychecker']->repairTables();

        // Grant 'root' to first user by default
        $user['roles'] = array(Permissions::ROLE_ROOT);

        // Get the form
        $form = $this->getUserForm($app, $user, true);

        // Set the validation
        $form = $this->setUserFormValidation($app, $form, true);

        /** @var \Symfony\Component\Form\Form */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST')) {
            if ($this->validateUserForm($app, $form, true)) {
                // To the dashboard, where 'login' will be triggered
                return $app->redirect(Lib::path('dashboard'));
            }
        }

        $context = array(
            'kind'        => 'create',
            'form'        => $form->createView(),
            'note'        => $note,
            'displayname' => $user['displayname'],
        );

        return $app['render']->render('firstuser/firstuser.twig', array('context' => $context));
    }

    /**
     * Handle a POST from user edit or first user creation.
     *
     * @param \Silex\Application          $app
     * @param Symfony\Component\Form\Form $form      A Symfony form
     * @param boolean                     $firstuser If this is a first user set up
     *
     * @return array|boolean An array of user elements, otherwise false
     */
    private function validateUserForm(Application $app, Form $form, $firstuser = false)
    {
        $form->submit($app['request']->get($form->getName()));

        if ($form->isValid()) {
            $user = $form->getData();

            if ($firstuser) {
                $user['roles'] = array(Permissions::ROLE_ROOT);
            } else {
                $id = isset($user['id']) ? $user['id'] : null;
                $user['roles'] = $app['users']->filterManipulatableRoles($id, $user['roles']);
            }

            $res = $app['users']->saveUser($user);

            if ($user['id']) {
                $app['logger.system']->info(Trans::__('page.edit-users.log.user-updated', array('%user%' => $user['displayname'])), array('event' => 'security'));
            } else {
                $app['logger.system']->info(Trans::__('page.edit-users.log.user-added', array('%user%' => $user['displayname'])), array('event' => 'security'));

                // Create a welcome email
                $mailhtml = $app['render']->render(
                    'email/firstuser.twig',
                    array(
                        'sitename' => $app['config']->get('general/sitename')
                    )
                )->getContent();

                try {
                    // Send a welcome email
                    $message = $app['mailer']
                        ->createMessage('message')
                        ->setSubject(Trans::__('New Bolt site has been set up'))
                        ->setFrom(array($app['config']->get('general/mailoptions/senderMail', $user['email']) => $app['config']->get('general/mailoptions/senderName', $app['config']->get('general/sitename'))))
                        ->setTo(array($user['email']   => $user['displayname']))
                        ->setBody(strip_tags($mailhtml))
                        ->addPart($mailhtml, 'text/html');

                    $app['mailer']->send($message);
                } catch (\Exception $e) {
                    // Sending message failed. What else can we do, sending with snailmail?
                    $app['logger.system']->error("The 'mailoptions' need to be set in app/config/config.yml", array('event' => 'config'));
                }
            }

            if ($res) {
                $app['session']->getFlashBag()->add('success', Trans::__('page.edit-users.message.user-saved', array('%user%' => $user['displayname'])));
            } else {
                $app['session']->getFlashBag()->add('error', Trans::__('page.edit-users.message.saving-user', array('%user%' => $user['displayname'])));
            }

            return $user;
        }

        return false;
    }

    /**
     * User profile page.
     *
     * @param Application $app     The application/container
     * @param Request     $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function profile(Application $app, Request $request)
    {
        $user = $app['users']->getCurrentUser();

        // Get the form
        $form = $this->getUserForm($app, $user);

        // Set the validation
        $form = $this->setUserFormValidation($app, $form);

        /** @var \Symfony\Component\Form\Form */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST')) {
            $form->submit($app['request']->get($form->getName()));

            if ($form->isValid()) {
                $user = $form->getData();

                $res = $app['users']->saveUser($user);
                $app['logger.system']->info(Trans::__('page.edit-users.log.user-updated', array('%user%' => $user['displayname'])), array('event' => 'security'));
                if ($res) {
                    $app['session']->getFlashBag()->add('success', Trans::__('page.edit-users.message.user-saved', array('%user%' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->add('error', Trans::__('page.edit-users.message.saving-user', array('%user%' => $user['displayname'])));
                }

                return Lib::redirect('profile');
            }
        }

        $context = array(
            'kind'        => 'profile',
            'form'        => $form->createView(),
            'note'        => '',
            'displayname' => $user['displayname'],
        );

        return $app['render']->render('edituser/edituser.twig', array('context' => $context));
    }

    /**
     * Perform actions on users.
     *
     * @param Application $app    The application/container
     * @param string      $action The action
     * @param integer     $id     The user ID
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function userAction(Application $app, $action, $id)
    {
        if (!$app['users']->checkAntiCSRFToken()) {
            $app['session']->getFlashBag()->add('info', Trans::__('An error occurred.'));

            return Lib::redirect('users');
        }
        $user = $app['users']->getUser($id);

        if (!$user) {
            $app['session']->getFlashBag()->add('error', Trans::__('No such user.'));

            return Lib::redirect('users');
        }

        // Prevent the current user from enabling, disabling or deleting themselves
        $currentuser = $app['users']->getCurrentUser();
        if ($currentuser['id'] == $user['id']) {
            $app['session']->getFlashBag()->add('error', Trans::__("You cannot '%s' yourself.", array('%s', $action)));

            return Lib::redirect('users');
        }

        // Verify the current user has access to edit this user
        if (!$app['permissions']->isAllowedToManipulate($user, $currentuser)) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to edit that user.'));

            return Lib::redirect('users');
        }

        switch ($action) {

            case 'disable':
                if ($app['users']->setEnabled($id, 0)) {
                    $app['logger.system']->info("Disabled user '{$user['displayname']}'.", array('event' => 'security'));

                    $app['session']->getFlashBag()->add('info', Trans::__("User '%s' is disabled.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->add('info', Trans::__("User '%s' could not be disabled.", array('%s' => $user['displayname'])));
                }
                break;

            case 'enable':
                if ($app['users']->setEnabled($id, 1)) {
                    $app['logger.system']->info("Enabled user '{$user['displayname']}'.", array('event' => 'security'));
                    $app['session']->getFlashBag()->add('info', Trans::__("User '%s' is enabled.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->add('info', Trans::__("User '%s' could not be enabled.", array('%s' => $user['displayname'])));
                }
                break;

            case 'delete':

                if ($app['users']->checkAntiCSRFToken() && $app['users']->deleteUser($id)) {
                    $app['logger.system']->info("Deleted user '{$user['displayname']}'.", array('event' => 'security'));
                    $app['session']->getFlashBag()->add('info', Trans::__("User '%s' is deleted.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->add('info', Trans::__("User '%s' could not be deleted.", array('%s' => $user['displayname'])));
                }
                break;

            default:
                $app['session']->getFlashBag()->add('error', Trans::__("No such action for user '%s'.", array('%s' => $user['displayname'])));

        }

        return Lib::redirect('users');
    }

    /**
     * Show the 'about' page.
     *
     * @param Application $app The application/container
     *
     * @return \Twig_Markup
     */
    public function about(Application $app)
    {
        return $app['render']->render('about/about.twig');
    }

    /**
     * The file browser.
     *
     * @param string      $namespace The filesystem namespace
     * @param string      $path      The path prefix
     * @param Application $app       The application/container
     * @param Request     $request   The Symfony Request
     *
     * @return \Twig_Markup
     */
    public function files($namespace, $path, Application $app, Request $request)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        // Defaults
        $files      = array();
        $folders    = array();
        $formview   = false;
        $uploadview = true;

        $filesystem = $app['filesystem']->getFilesystem($namespace);

        if (!$filesystem->authorized($path)) {
            $error = Trans::__("You don't have the correct permissions to display the file or directory '%s'.", array('%s' => $path));
            $app->abort(Response::HTTP_FORBIDDEN, $error);
        }

        if (!$app['users']->isAllowed('files:uploads')) {
            $uploadview = false;
        }

        try {
            $visibility = $filesystem->getVisibility($path);
        } catch (FileNotFoundException $fnfe) {
            $visibility = false;
        }

        if ($visibility === 'public') {
            $validFolder = true;
        } elseif ($visibility === 'readonly') {
            $validFolder = true;
            $uploadview = false;
        } else {
            $app['session']->getFlashBag()->add('error', Trans::__("The folder '%s' could not be found, or is not readable.", array('%s' => $path)));
            $formview = false;
            $validFolder = false;
        }

        if ($validFolder) {
            // Define the "Upload here" form.
            $form = $app['form.factory']
                ->createBuilder('form')
                ->add(
                    'FileUpload',
                    'file',
                    array(
                        'label'    => Trans::__('Upload a file to this folder'),
                        'multiple' => true,
                        'attr'     => array(
                        'data-filename-placement' => 'inside',
                        'title'                   => Trans::__('Select file …'))
                    )
                )
                ->getForm();

            // Handle the upload.
            if ($request->isMethod('POST')) {
                $form->submit($request);
                if ($form->isValid()) {
                    $files = $request->files->get($form->getName());
                    $files = $files['FileUpload'];

                    foreach ($files as $fileToProcess) {
                        $fileToProcess = array(
                            'name'     => $fileToProcess->getClientOriginalName(),
                            'tmp_name' => $fileToProcess->getPathName()
                        );

                        $originalFilename = $fileToProcess['name'];
                        $filename = preg_replace('/[^a-zA-Z0-9_\\.]/', '_', basename($originalFilename));

                        if ($app['filepermissions']->allowedUpload($filename)) {
                            $app['upload.namespace'] = $namespace;
                            $handler = $app['upload'];
                            $handler->setPrefix($path . '/');
                            $result = $handler->process($fileToProcess);

                            if ($result->isValid()) {
                                $app['session']->getFlashBag()->add(
                                    'info',
                                    Trans::__("File '%file%' was uploaded successfully.", array('%file%' => $filename))
                                );

                                // Add the file to our stack.
                                $app['stack']->add($path . '/' . $filename);
                                $result->confirm();
                            } else {
                                foreach ($result->getMessages() as $message) {
                                    $app['session']->getFlashBag()->add(
                                        'error',
                                        $message->__toString()
                                    );
                                }
                            }
                        } else {
                            $extensionList = array();
                            foreach ($app['filepermissions']->getAllowedUploadExtensions() as $extension) {
                                $extensionList[] = '<code>.' . htmlspecialchars($extension, ENT_QUOTES) . '</code>';
                            }
                            $extensionList = implode(' ', $extensionList);
                            $app['session']->getFlashBag()->add(
                                'error',
                                Trans::__("File '%file%' could not be uploaded (wrong/disallowed file type). Make sure the file extension is one of the following:", array('%file%' => $filename))
                                . $extensionList
                            );
                        }
                    }
                } else {
                    $app['session']->getFlashBag()->add(
                        'error',
                        Trans::__("File '%file%' could not be uploaded.", array('%file%' => $filename))
                    );
                }

                return Lib::redirect('files', array('path' => $path, 'namespace' => $namespace));
            }

            if ($uploadview !== false) {
                $formview = $form->createView();
            }

            list($files, $folders) = $filesystem->browse($path, $app);
        }

        // Get the pathsegments, so we can show the path as breadcrumb navigation.
        $pathsegments = array();
        $cumulative = '';
        if (!empty($path)) {
            foreach (explode('/', $path) as $segment) {
                $cumulative .= $segment . '/';
                $pathsegments[$cumulative] = $segment;
            }
        }

        // Select the correct template to render this. If we've got 'CKEditor' in the title, it's a dialog
        // from CKeditor to insert a file.
        if (!$request->query->has('CKEditor')) {
            $twig = 'files/files.twig';
        } else {
            $app['debugbar'] = false;
            $twig = 'files_ck/files_ck.twig';
        }

        $context = array(
            'path'         => $path,
            'files'        => $files,
            'folders'      => $folders,
            'pathsegments' => $pathsegments,
            'form'         => $formview,
            'namespace'    => $namespace,
        );

        return $app['render']->render($twig, array('context' => $context));
    }

    /**
     * File editor.
     *
     * @param string      $namespace The filesystem namespace
     * @param string      $file      The file path
     * @param Application $app       The application/container
     * @param Request     $request   The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function fileEdit($namespace, $file, Application $app, Request $request)
    {
        if ($namespace == 'app' && dirname($file) == 'config') {
            // Special case: If requesting one of the major config files, like contenttypes.yml, set the path to the
            // correct dir, which might be 'app/config', but it might be something else.
            $namespace = 'config';
        }

        /** @var \League\Flysystem\FilesystemInterface $filesystem */
        $filesystem = $app['filesystem']->getFilesystem($namespace);

        if (!$filesystem->authorized($file)) {
            $error = Trans::__("You don't have correct permissions to edit the file '%s'.", array('%s' => $file));
            $app->abort(Response::HTTP_FORBIDDEN, $error);
        }

        /** @var \League\Flysystem\File $file */
        $file = $filesystem->get($file);
        $datechanged = date_format(new \DateTime('@' . $file->getTimestamp()), 'c');
        $type = Lib::getExtension($file->getPath());

        // Get the pathsegments, so we can show the path.
        $path = dirname($file->getPath());
        $pathsegments = array();
        $cumulative = '';
        if (!empty($path)) {
            foreach (explode('/', $path) as $segment) {
                $cumulative .= $segment . '/';
                $pathsegments[$cumulative] = $segment;
            }
        }

        $contents = null;
        if (!$file->exists() || !($contents = $file->read())) {
            $error = Trans::__("The file '%s' doesn't exist, or is not readable.", array('%s' => $file->getPath()));
            $app->abort(Response::HTTP_NOT_FOUND, $error);
        }

        if (!$file->update($contents)) {
            $app['session']->getFlashBag()->add(
                'info',
                Trans::__(
                    "The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                    array('%s' => $file->getPath())
                )
            );
            $writeallowed = false;
        } else {
            $writeallowed = true;
        }

        // Gather the 'similar' files, if present.. i.e., if we're editing config.yml, we also want to check for
        // config.yml.dist and config_local.yml
        $basename = str_replace('.yml', '', str_replace('_local', '', $file->getPath()));
        $filegroup = array();
        if ($filesystem->has($basename . '.yml')) {
            $filegroup[] = basename($basename . '.yml');
        }
        if ($filesystem->has($basename . '_local.yml')) {
            $filegroup[] = basename($basename . '_local.yml');
        }

        $data = array('contents' => $contents);

        /** @var Form $form */
        $form = $app['form.factory']
            ->createBuilder('form', $data)
            ->add('contents', 'textarea')
            ->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->isMethod('POST')) {
            $form->submit($app['request']->get($form->getName()));

            if ($form->isValid()) {
                $data = $form->getData();
                $contents = Input::cleanPostedData($data['contents']) . "\n";

                $result = array('ok' => true, 'msg' => 'Unhandled state.');

                // Before trying to save a yaml file, check if it's valid.
                if ($type === 'yml') {
                    $yamlparser = new Yaml\Parser();
                    try {
                        $yamlparser->parse($contents);
                    } catch (ParseException $e) {
                        $result['ok'] = false;
                        $result['msg'] = Trans::__("File '%s' could not be saved:", array('%s' => $file->getPath())) . $e->getMessage();
                    }
                }

                if ($result['ok']) {
                    // Remove ^M (or \r) characters from the file.
                    $contents = str_ireplace("\x0D", '', $contents);
                    if ($file->update($contents)) {
                        $result['msg'] = Trans::__("File '%s' has been saved.", array('%s' => $file->getPath()));
                        $result['datechanged'] = date_format(new \DateTime('@' . $file->getTimestamp()), 'c');
                    } else {
                        $result['msg'] = Trans::__("File '%s' could not be saved, for some reason.", array('%s' => $file->getPath()));
                    }
                }
            } else {
                $result = array(
                    'ok' => false,
                    'msg' => Trans::__("File '%s' could not be saved, because the form wasn't valid.", array('%s' => $file->getPath()))
                );
            }

            return new JsonResponse($result);
        }

        // For 'related' files we might need to keep track of the current dirname on top of the namespace.
        if (dirname($file->getPath()) != '') {
            $additionalpath = dirname($file->getPath()) . '/';
        } else {
            $additionalpath = '';
        }

        $context = array(
            'form'           => $form->createView(),
            'filetype'       => $type,
            'file'           => $file->getPath(),
            'basename'       => basename($file->getPath()),
            'pathsegments'   => $pathsegments,
            'additionalpath' => $additionalpath,
            'namespace'      => $namespace,
            'write_allowed'  => $writeallowed,
            'filegroup'      => $filegroup,
            'datechanged'    => $datechanged
        );

        return $app['render']->render('editfile/editfile.twig', array('context' => $context));
    }

    /**
     * Prepare/edit/save a translation.
     *
     * @param string      $domain    The domain
     * @param string      $tr_locale The translation locale
     * @param Application $app       The application/container
     * @param Request     $request   The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function translation($domain, $tr_locale, Application $app, Request $request)
    {
        $translation = new TranslationFile($app, $domain, $tr_locale);

        list($path, $shortPath) = $translation->path();

        $app['logger.system']->info('Editing translation: ' . $shortPath, array('event' => 'translation'));

        $data = array('contents' => $translation->content());

        $writeallowed = $translation->isWriteAllowed();

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'contents',
                'textarea',
                array('constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 10))))
            )
            ->getForm();

        // Check if the form was POST-ed, and valid. If so, store the file.
        if ($request->isMethod('POST')) {
            $form->submit($app['request']->get($form->getName()));

            if ($form->isValid()) {
                $data = $form->getData();
                $contents = Input::cleanPostedData($data['contents']) . "\n";

                // Before trying to save a yaml file, check if it's valid.
                try {
                    $ok = Yaml\Yaml::parse($contents);
                } catch (ParseException $e) {
                    $ok = false;
                    $msg = Trans::__("File '%s' could not be saved:", array('%s' => $shortPath));
                    $app['session']->getFlashBag()->add('error', $msg . $e->getMessage());
                }

                // Before trying to save, check if it's writable.
                if ($ok) {
                    // clear any warning for file not found, we are creating it here
                    // we'll set an error if someone still submits the form and write is not allowed
                    $app['session']->getFlashBag()->clear('warning');

                    if (!$writeallowed) {
                        $msg = Trans::__("The file '%s' is not writable. You will have to use your own editor to make modifications to this file.", array('%s' => $shortPath));
                        $app['session']->getFlashBag()->add('error', $msg);
                    } else {
                        file_put_contents($path, $contents);
                        $msg = Trans::__("File '%s' has been saved.", array('%s' => $shortPath));
                        $app['session']->getFlashBag()->add('info', $msg);

                        return Lib::redirect('translation', array('domain' => $domain, 'tr_locale' => $tr_locale));
                    }
                }
            }
        }

        $context = array(
            'form'          => $form->createView(),
            'basename'      => basename($shortPath),
            'filetype'      => 'yml',
            'write_allowed' => $writeallowed,
        );

        return $app['render']->render('editlocale/editlocale.twig', array('context' => $context));
    }

    /**
     * Middleware function to check whether a user is logged on.
     *
     * @param Request     $request The Symfony Request
     * @param Application $app     The application/container
     *
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function before(Request $request, Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.backend.before');

        $route = $request->get('_route');

        $app['debugbar'] = true;

        // Sanity checks for doubles in in contenttypes.
        // unfortunately this has to be done here, because the 'translator' classes need to be initialised.
        $app['config']->checkConfig();

        // If we had to reload the config earlier on because we detected a version change, display a notice.
        if ($app['config']->notify_update) {
            $notice = Trans::__("Detected Bolt version change to <b>%VERSION%</b>, and the cache has been cleared. Please <a href=\"%URI%\">check the database</a>, if you haven't done so already.",
                array('%VERSION%' => $app->getVersion(), '%URI%' => $app['resources']->getUrl('bolt') . 'dbcheck'));
            $app['logger.system']->notice(strip_tags($notice), array('event' => 'config'));
            $app['session']->getFlashBag()->add('info', $notice);
        }

        // Check the database users table exists
        $tableExists = $app['integritychecker']->checkUserTableIntegrity();

        // Test if we have a valid users in our table
        $hasUsers = false;
        if ($tableExists) {
            $hasUsers = $app['users']->hasUsers();
        }

        // If the users table is present, but there are no users, and we're on /bolt/userfirst,
        // we let the user stay, because they need to set up the first user.
        if ($tableExists && !$hasUsers && $route == 'userfirst') {
            return null;
        }

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$tableExists || !$hasUsers) {
            $app['integritychecker']->repairTables();
            $app['session']->getFlashBag()->add('info', Trans::__('There are no users in the database. Please create the first user.'));

            return Lib::redirect('userfirst');
        }

        // Confirm the user is enabled or bounce them
        if ($app['users']->getCurrentUser() && !$app['users']->isEnabled() && $route !== 'userfirst' && $route !== 'login' && $route !== 'postLogin' && $route !== 'logout') {
            $app['session']->getFlashBag()->add('error', Trans::__('Your account is disabled. Sorry about that.'));

            return Lib::redirect('logout');
        }

        // Check if there's at least one 'root' user, and otherwise promote the current user.
        $app['users']->checkForRoot();

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        if (!$app['users']->isValidSession() && !$app['users']->isAllowed($route)) {
            $app['session']->getFlashBag()->add('info', Trans::__('Please log on.'));

            return Lib::redirect('login');
        } elseif (!$app['users']->isAllowed($route)) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to view that page.'));

            return Lib::redirect('dashboard');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');

        return null;
    }

    /**
     * Create a user form with the form builder.
     *
     * @param Application $app
     * @param array       $user
     * @param boolean     $editusername
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function getUserForm(Application $app, array $user, $editusername = false)
    {
        // Start building the form
        $form = $app['form.factory']->createBuilder('form', $user);

        // Username goes first (editable when not viewing own profile)
        if ($editusername) {
            $form->add(
                'username',
                'text',
                array(
                    'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                    'label'       => Trans::__('page.edit-users.label.username'),
                    'attr'        => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.username')
                    )
                )
            );
        } else {
            $form->add(
                'username',
                'text',
                array(
                    'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                    'label'       => Trans::__('page.edit-users.label.username'),
                    'attr'        => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.username')
                    ),
                    'read_only'   => true
                )
            );
        }


        // Add the other fields
        $form
            ->add('id', 'hidden')
            ->add(
                'password',
                'password',
                array(
                    'required' => false,
                    'label'    => Trans::__('page.edit-users.label.password'),
                    'attr'     => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.password')
                    )
                )
            )
            ->add(
                'password_confirmation',
                'password',
                array(
                    'required' => false,
                    'label'    => Trans::__('page.edit-users.label.password-confirm'),
                    'attr'     => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.password-confirm')
                    )
                )
            )
            ->add(
                'email',
                'text',
                array(
                    'constraints' => new Assert\Email(),
                    'label'       => Trans::__('page.edit-users.label.email'),
                    'attr'        => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.email')
                    )
                )
            )
            ->add(
                'displayname',
                'text',
                array(
                    'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                    'label'       => Trans::__('page.edit-users.label.display-name'),
                    'attr'        => array(
                        'placeholder' => Trans::__('page.edit-users.placeholder.displayname')
                    )
                )
            );

        return $form;
    }

    /**
     * Validate the user form.
     *
     * Use a custom validator to check:
     *   * Passwords are identical
     *   * Username is unique
     *   * Email is unique
     *   * Displaynames are unique
     *
     * @param Application                         $app
     * @param \Symfony\Component\Form\FormBuilder $form
     * @param boolean                             $addusername
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    private function setUserFormValidation(Application $app, FormBuilder $form, $addusername = false)
    {
        $form->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($app, $addusername) {
                $form = $event->getForm();
                $id = $form['id']->getData();
                $pass1 = $form['password']->getData();
                $pass2 = $form['password_confirmation']->getData();

                // If adding a new user (empty $id) or if the password is not empty (indicating we want to change it),
                // then make sure it's at least 6 characters long.
                if ((empty($id) || !empty($pass1)) && strlen($pass1) < 6) {
                    // screw it. Let's just not translate this message for now. Damn you, stupid non-cooperative
                    // translation thingy. $error = new FormError("This value is too short. It should have {{ limit }}
                    // characters or more.", array('{{ limit }}' => 6), 2);
                    $error = new FormError(Trans::__('page.edit-users.error.password-short'));
                    $form['password']->addError($error);
                }

                // Passwords must be identical.
                if ($pass1 != $pass2) {
                    $form['password_confirmation']->addError(new FormError(Trans::__('page.edit-users.error.password-mismatch')));
                }

                if ($addusername) {
                    // Usernames must be unique.
                    if (!$app['users']->checkAvailability('username', $form['username']->getData(), $id)) {
                        $form['username']->addError(new FormError(Trans::__('page.edit-users.error.username-used')));
                    }
                }

                // Issue 3491 : Password must be different from username
                $username = $form['username']->getData();
                if (!empty($username) && $pass1 === $username) {
                    $form['password']->addError(new FormError(Trans::__('page.edit-users.error.password-different-username')));
                }

                // Email addresses must be unique.
                if (!$app['users']->checkAvailability('email', $form['email']->getData(), $id)) {
                    $form['email']->addError(new FormError(Trans::__('page.edit-users.error.email-used')));
                }

                // Displaynames must be unique.
                if (!$app['users']->checkAvailability('displayname', $form['displayname']->getData(), $id)) {
                    $form['displayname']->addError(new FormError(Trans::__('page.edit-users.error.displayname-used')));
                }
            }
        );

        return $form;
    }
}
