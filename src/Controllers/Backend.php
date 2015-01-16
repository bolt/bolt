<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Yaml\Yaml;
use Bolt\Library as Lib;
use Bolt\Helpers\Input;
use Bolt\Translation\Translator as Trans;
use Bolt\Permissions;
use Bolt\Translation\TranslationFile;

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
    public function connect(Silex\Application $app)
    {
        /** @var $ctl \Silex\ControllerCollection */
        $ctl = $app['controllers_factory'];

        $ctl->get('', array($this, 'dashboard'))
            ->before(array($this, 'before'))
            ->bind('dashboard');

        $ctl->match('/login', array($this, 'getLogin'))
            ->method('GET')
            ->before(array($this, 'before'))
            ->bind('login');

        $ctl->match('/login', array($this, 'postLogin'))
            ->method('POST')
            ->before(array($this, 'before'))
            ->bind('postLogin');

        $ctl->get('/logout', array($this, 'logout'))
            ->method('POST')
            ->bind('logout');

        $ctl->match('/resetpassword', array($this, 'resetPassword'))
            ->bind('resetpassword')
            ->method('GET');

        $ctl->get('/dbcheck', array($this, 'dbCheck'))
            ->before(array($this, 'before'))
            ->bind('dbcheck');

        $ctl->get('/dbupdate', array($this, 'dbUpdate'))
            ->method('POST')
            ->before(array($this, 'before'))
            ->bind('dbupdate');

        $ctl->get('/dbupdate_result', array($this, 'dbUpdateResult'))
            ->method('GET')
            ->before(array($this, 'before'))
            ->bind('dbupdate_result');

        $ctl->get('/clearcache', array($this, 'clearCache'))
            ->before(array($this, 'before'))
            ->bind('clearcache');

        $ctl->match('/prefill', array($this, 'prefill'))
            ->before(array($this, 'before'))
            ->method('GET|POST')
            ->bind('prefill');

        $ctl->get('/overview/{contenttypeslug}', array($this, 'overview'))
            ->before(array($this, 'before'))
            ->bind('overview');

        $ctl->get('/relatedto/{contenttypeslug}/{id}', array($this, 'relatedTo'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->bind('relatedto');

        $ctl->match('/editcontent/{contenttypeslug}/{id}', array($this, 'editContent'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET|POST')
            ->bind('editcontent');

        $ctl->get('/content/deletecontent/{contenttypeslug}/{id}', array($this, 'deleteContent'))
            ->before(array($this, 'before'))
            ->bind('deletecontent');

        $ctl->get('/content/{action}/{contenttypeslug}/{id}', array($this, 'contentAction'))
            ->before(array($this, 'before'))
            ->method('POST')
            ->bind('contentaction');

        $ctl->get('/changelog/{contenttype}/{contentid}', array($this, 'changelogList'))
            ->before(array($this, 'before'))
            ->value('contentid', '0')
            ->value('contenttype', '')
            ->bind('changeloglist');

        $ctl->get('/changelog/{contenttype}/{contentid}/{id}', array($this, 'changelogDetails'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->bind('changelogdetails');

        $ctl->get('/users', array($this, 'users'))
            ->before(array($this, 'before'))
            ->bind('users');

        $ctl->match('/users/edit/{id}', array($this, 'userEdit'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET|POST')
            ->bind('useredit');

        $ctl->match('/profile', array($this, 'profile'))
            ->before(array($this, 'before'))
            ->method('GET|POST')
            ->bind('profile');

        $ctl->match('/roles', array($this, 'roles'))
            ->before(array($this, 'before'))
            ->method('GET')
            ->bind('roles');

        $ctl->get('/about', array($this, 'about'))
            ->before(array($this, 'before'))
            ->bind('about');

        $ctl->get('/user/{action}/{id}', array($this, 'userAction'))
            ->before(array($this, 'before'))
            ->method('POST')
            ->bind('useraction');

        $ctl->match('/files/{namespace}/{path}', array($this, 'files'))
            ->before(array($this, 'before'))
            ->assert('namespace', '[^/]+')
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('files');

        $ctl->get('/activitylog', array($this, 'activityLog'))
            ->before(array($this, 'before'))
            ->bind('activitylog');

        $ctl->match('/file/edit/{namespace}/{file}', array($this, 'fileEdit'))
            ->before(array($this, 'before'))
            ->assert('file', '.+')
            ->assert('namespace', '[^/]+')
            ->value('namespace', 'files')
            ->method('GET|POST')
            ->bind('fileedit');

        $ctl->match('/tr/{domain}/{tr_locale}', array($this, 'translation'))
            ->before(array($this, 'before'))
            ->assert('domain', 'messages|contenttypes|infos')
            ->value('domain', 'messages')
            ->value('tr_locale', $app['config']->get('general/locale'))
            ->method('GET|POST')
            ->bind('translation');

        $ctl->get('/omnisearch', array($this, 'omnisearch'))
            ->before(array($this, 'before'))
            ->bind('omnisearch');

        return $ctl;
    }

    /**
     * Dashboard or "root".
     *
     * @param \Bolt\Application $app The application/container
     * @return mixed
     */
    public function dashboard(\Bolt\Application $app)
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
            'latest' => $latest,
            'suggestloripsum' => ($total == 0), // Nothing in the DB, then suggest to create some dummy content.
        );

        return $app['render']->render('dashboard/dashboard.twig', array('context' => $context));
    }

    /**
     * Handle a login attempt.
     *
     * @param Silex\Application $app     The application/container
     * @param Request           $request The Symfony Request
     */
    public function postLogin(Silex\Application $app, Request $request)
    {
        switch ($request->get('action')) {
            case 'login':
                // Log in, if credentials are correct.
                $result = $app['users']->login($request->get('username'), $request->get('password'));

                if ($result) {
                    $app['log']->add('Login ' . $request->get('username'), 3, '', 'login');
                    $retreat = $app['session']->get('retreat');
                    $redirect = !empty($retreat) && is_array($retreat) ? $retreat : array('route' => 'dashboard', 'params' => array());

                    return Lib::redirect($redirect['route'], $redirect['params']);
                }

                return $this->getLogin($app, $request);

            case 'reset':
                // Send a password request mail, if username exists.
                $username = trim($request->get('username'));
                if (empty($username)) {
                    $app['users']->session->getFlashBag()->set('error', Trans::__('Please provide a username', array()));
                } else {
                    $app['users']->resetPasswordRequest($request->get('username'));

                    return Lib::redirect('login');
                }

                return $this->getLogin($app, $request);

            default:
                // Let's not disclose any internal information.
                $app->abort(400, 'Invalid request');
        }
    }

    /**
     * Login page and "Forgotten password" page.
     *
     * @param Silex\Application $app     The application/container
     * @param Request           $request The Symfony Request
     * @return string
     */
    public function getLogin(Silex\Application $app, Request $request)
    {
        if (!empty($app['users']->currentuser) && $app['users']->currentuser['enabled'] == 1) {
            return Lib::redirect('dashboard', array());
        }

        $context = array(
            'randomquote' => true,
        );

        return $app['render']->render('login/login.twig', array('context' => $context));
    }

    /**
     * Logout page.
     *
     * @param Silex\Application $app The application/container
     * @return string
     */
    public function logout(Silex\Application $app)
    {
        $app['log']->add('Logout', 3, '', 'logout');

        $app['users']->logout();

        return Lib::redirect('login');
    }

    /**
     * Reset the password. This controller is normally only reached when the user
     * clicks a "password reset" link in the email.
     *
     * @param Silex\Application $app     The application/container
     * @param Request           $request The Symfony Request
     * @return string
     */
    public function resetPassword(Silex\Application $app, Request $request)
    {
        $app['users']->resetPasswordConfirm($request->get('token'));

        return Lib::redirect('login');
    }

    /**
     * Check the database for missing tables and columns. Does not do actual repairs.
     *
     * @param \Bolt\Application $app The application/container
     * @return mixed
     */
    public function dbCheck(\Bolt\Application $app)
    {
        list($messages, $hints) = $app['integritychecker']->checkTablesIntegrity(true);

        $context = array(
            'modifications_made' => null,
            'modifications_required' => $messages,
            'modifications_hints' => $hints,
        );

        return $app['render']->render('dbcheck/dbcheck.twig', array('context' => $context));
    }

    /**
     * Check the database, create tables, add missing/new columns to tables.
     *
     * @param Silex\Application $app The application/container
     * @return string
     */
    public function dbUpdate(Silex\Application $app)
    {
        $output = $app['integritychecker']->repairTables();

        // If 'return=edit' is passed, we should return to the edit screen. We do redirect twice, yes,
        // but that's because the newly saved contenttype.yml needs to be re-read.
        $return = $app['request']->query->get('return');
        if ($return == 'edit') {
            if (empty($output)) {
                $content = Trans::__('Your database is already up to date.');
            } else {
                $content = Trans::__('Your database is now up to date.');
            }
            $app['session']->getFlashBag()->set('success', $content);

            return Lib::redirect('fileedit', array('file' => 'app/config/contenttypes.yml'));
        } else {
            return Lib::redirect('dbupdate_result', array('messages' => json_encode($output)));
        }
    }

    /**
     * Show the result of database updates.
     *
     * @param Silex\Application $app     The application/container
     * @param Request           $request The Symfony Request
     * @return mixed
     */
    public function dbUpdateResult(Silex\Application $app, Request $request)
    {
        $context = array(
            'modifications_made' => json_decode($request->get('messages')),
            'modifications_required' => null,
        );

        return $app['render']->render('dbcheck/dbcheck.twig', array('context' => $context));
    }

    /**
     * Clear the cache.
     *
     * @param Silex\Application $app The application/container
     * @return mixed
     */
    public function clearCache(Silex\Application $app)
    {
        $result = $app['cache']->clearCache();

        $output = Trans::__('Deleted %s files from cache.', array('%s' => $result['successfiles']));

        if (!empty($result['failedfiles'])) {
            $output .= ' ' . Trans::__('%s files could not be deleted. You should delete them manually.', array('%s' => $result['failedfiles']));
            $app['session']->getFlashBag()->set('error', $output);
        } else {
            $app['session']->getFlashBag()->set('success', $output);
        }

        return $app['render']->render('clearcache/clearcache.twig');
    }

    /**
     * Show the activity-log.
     *
     * @param Silex\Application $app The application/container
     * @return string
     */
    public function activityLog(Silex\Application $app)
    {
        $action = $app['request']->query->get('action');

        if ($action == 'clear') {
            $app['log']->clear();
            $app['session']->getFlashBag()->set('success', Trans::__('The activitylog has been cleared.'));

            return Lib::redirect('activitylog');
        } elseif ($action == 'trim') {
            $app['log']->trim();
            $app['session']->getFlashBag()->set('success', Trans::__('The activitylog has been trimmed.'));

            return Lib::redirect('activitylog');
        }

        $activity = $app['log']->getActivity(16);

        $context = array(
            'activity' => $activity
        );

        return $app['render']->render('activity/activity.twig', array('context' => $context));
    }

    /**
     * Show the Omnisearch results.
     *
     * @param Silex\Application $app The application/container
     * @return mixed
     */
    public function omnisearch(Silex\Application $app)
    {
        $query = $app['request']->query->get('q', '');
        $results = array();

        if (strlen($query) >= 3) {
            $results = $app['omnisearch']->query($query, true);
        }

        $context = array(
            'query' => $query,
            'results' => $results
        );

        return $app['render']->render('omnisearch/omnisearch.twig', array('context' => $context));
    }

    /**
     * Generate some lipsum in the DB.
     *
     * @param Silex\Application $app     The application/container
     * @param Request           $request The Symfony Request
     * @return string
     */
    public function prefill(Silex\Application $app, Request $request)
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
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
            ))
            ->getForm();

        if (($request->getMethod() == 'POST') || ($request->get('force') == 1)) {
            $form->bind($request);
            $ctypes = $form->get('contenttypes')->getData();
            $content = $app['storage']->preFill($ctypes);
            $app['session']->getFlashBag()->set('success', $content);

            return Lib::redirect('prefill');
        }

        $context = array(
            'contenttypes' => $choices,
            'form' => $form->createView(),
        );

        return $app['render']->render('prefill/prefill.twig', array('context' => $context));
    }

    /**
     * Content type overview page.
     *
     * @param Silex\Application $app             The application/container
     * @param string            $contenttypeslug The content type slug
     * @return mixed
     */
    public function overview(Silex\Application $app, $contenttypeslug)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$app['users']->isAllowed('contenttype:' . $contenttypeslug)) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to view that page.'));

            return Lib::redirect('dashboard');
        }

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // Order has to be set carefully. Either set it explicitly when the user
        // sorts, or fall back to what's defined in the contenttype. The exception
        // is a contenttype that has a "grouping taxonomy", because that should
        // override it. The exception is handled in $app['storage']->getContent().
        $order = $app['request']->query->get('order', $contenttype['sort']);

        $page = $app['request']->query->get('page');
        $filter = $app['request']->query->get('filter');

        // Set the amount of items to show per page.
        if (!empty($contenttype['recordsperpage'])) {
            $limit = $contenttype['recordsperpage'];
        } else {
            $limit = $app['config']->get('general/recordsperpage');
        }

        $multiplecontent = $app['storage']->getContent(
            $contenttype['slug'],
            array('limit' => $limit, 'order' => $order, 'page' => $page, 'filter' => $filter, 'paging' => true, 'hydrate' => true)
        );

        $context = array(
            'contenttype' => $contenttype,
            'multiplecontent' => $multiplecontent,
        );

        return $app['render']->render('overview/overview.twig', array('context' => $context));
    }

    /**
     * Get related records @todo
     *
     * @param string            $contenttypeslug The content type slug
     * @param integer           $id              The ID
     * @param Silex\Application $app             The application/container
     * @param Request           $request         The Symfony Request
     * @return mixed
     */
    public function relatedTo($contenttypeslug, $id, Silex\Application $app, Request $request)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes' for Editors.
        if (!$app['users']->isAllowed('contenttype:' . $contenttypeslug)) {
            $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to edit that record.'));

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
                    'name' => Trans::__($relatedtype['name']),
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
         * }
         */

        $content = $app['storage']->getContent($contenttypeslug, array('id' => $id));
        $relatedContent = $content->related($showContenttype['slug']);

        $context = array(
            'id' => $id,
            'name' => Trans::__($contenttype['singular_name']),
            'title' => $content['title'],
            'contenttype' => $contenttype,
            'relations' => $relations,
            'show_contenttype' => $showContenttype,
            'related_content' => $relatedContent,
        );

        return $app['twig']->render('relatedto/relatedto.twig', array('context' => $context));
    }

    /**
     * Show changelog entries.
     *
     * @param string            $contenttype The content type slug
     * @param integer           $contentid   The content ID
     * @param Silex\Application $app         The application/container
     * @param Request           $request     The Symfony Request
     * @return mixed
     */
    public function changelogList($contenttype, $contentid, Silex\Application $app, Request $request)
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
            'order' => 'date DESC',
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
            $logEntries = $app['storage']->getChangelog($options);
            // @todo: Unused in template. Leave it in for now
            $itemcount = $app['storage']->countChangelog($options);
        } else {
            // We have a content type, and possibly a contentid.
            $contenttypeObj = $app['storage']->getContentType($contenttype);
            if ($contentid) {
                $content = $app['storage']->getContent($contenttype, array('id' => $contentid, 'hydrate' => false));
                $options['contentid'] = $contentid;
            }
            // Getting a slice of data and the total count
            $logEntries = $app['storage']->getChangelogByContentType($contenttype, $options);
            // @todo: Unused in template. Leave it in for now
            $itemcount = $app['storage']->countChangelogByContentType($contenttype, $options);

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
            'entries' => $logEntries,
            'content' => $content,
            'title' => $title,
            'currentpage' => $page,
            'pagecount' => $pagecount
        );

        return $app['render']->render('changeloglist/changeloglist.twig', array('context' => $context));
    }

    /**
     * Show changelog details.
     *
     * @param string            $contenttype The content type slug
     * @param integer           $contentid   The content ID
     * @param integer           $id          The changelog entry ID
     * @param Silex\Application $app         The application/container
     * @param Request           $request     The Symfony Request
     * @return mixed
     */
    public function changelogDetails($contenttype, $contentid, $id, Silex\Application $app, Request $request)
    {
        $entry = $app['storage']->getChangelogEntry($contenttype, $contentid, $id);
        if (empty($entry)) {
            $error = Trans::__("The requested changelog entry doesn't exist.");
            $app->abort(404, $error);
        }
        $prev = $app['storage']->getPrevChangelogEntry($contenttype, $contentid, $id);
        $next = $app['storage']->getNextChangelogEntry($contenttype, $contentid, $id);

        $context = array(
            'contenttype' => array('slug' => $contenttype),
            'entry' => $entry,
            'next_entry' => $next,
            'prev_entry' => $prev,
            //'content' => $content,
        );

        return $app['render']->render('changelogdetails/changelogdetails.twig', array('context' => $context));
    }

    /**
     * Edit a unit of content, or create a new one.
     *
     * @param string            $contenttypeslug The content type slug
     * @param integer           $id              The content ID
     * @param Silex\Application $app             The application/container
     * @param Request           $request         The Symfony Request
     * @return mixed
     */
    public function editContent($contenttypeslug, $id, Silex\Application $app, Request $request)
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
            $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to edit that record.'));

            return Lib::redirect('dashboard');
        }

        // set the editreferrer in twig if it was not set yet.
        $tmp = parse_url($app['request']->server->get('HTTP_REFERER'));

        $tmpreferrer = $tmp['path'];
        if (!empty($tmp['query'])) {
            $tmpreferrer .= "?" . $tmp['query'];
        }

        if (strpos($tmpreferrer, '/overview/') !== false || ($tmpreferrer == $app['paths']['bolt'])) {
            $app['twig']->addGlobal('editreferrer', $tmpreferrer);
        }

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        if ($request->getMethod() == "POST") {
            if (!$app['users']->checkAntiCSRFToken()) {
                $app->abort(400, Trans::__('Something went wrong'));
            }
            if (!empty($id)) {
                // Check if we're allowed to edit this content..
                if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:edit:$id")) {
                    $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to edit that record.'));

                    return Lib::redirect('dashboard');
                }
            } else {
                // Check if we're allowed to create content..
                if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:create")) {
                    $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to create a new record.'));

                    return Lib::redirect('dashboard');
                }
            }

            // If we have an ID now, this is an existing record
            if ($id) {
                $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id));
                $oldStatus = $content['status'];
                $newStatus = $content['status'];
            } else {
                $content = $app['storage']->getContentObject($contenttypeslug);
                $oldStatus = '';
            }

            // Add non successfull control values to request values
            // http://www.w3.org/TR/html401/interact/forms.html#h-17.13.2
            $requestAll = $request->request->all();

            foreach ($contenttype['fields'] as $key => $values) {
                if (!isset($requestAll[$key])) {
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

            // Don't try to spoof the $id..
            if (!empty($content['id']) && $id != $content['id']) {
                $app['session']->getFlashBag()->set('error', "Don't try to spoof the id!");

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
                $app['log']->add($content->getTitle(), 3, $content, 'save content');

                if ($new) {
                    $app['session']->getFlashBag()->set('success', Trans::__('contenttypes.generic.saved-new', array('%contenttype%' => $contenttypeslug)));
                } else {
                    $app['session']->getFlashBag()->set('success', Trans::__('contenttypes.generic.saved-changes', array('%contenttype%' => $contenttype['slug'])));
                }

                /*
                 * We now only get a returnto parameter if we are saving a new
                 * record and staying on the same page, i.e. "Save {contenttype}"
                 */
                if ($app['request']->get('returnto')) {

                    $returnto = $app['request']->get('returnto');

                    if ($returnto == "new") {
                        return Lib::redirect('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => $id), '#' . $app['request']->get('returnto'));
                    } elseif ($returnto == "saveandnew") {
                        return Lib::redirect('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => 0), '#' . $app['request']->get('returnto'));
                    } elseif ($returnto == "ajax") {
                        /*
                         * Flush any buffers from saveConent() dispatcher hooks
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
                        $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id, 'returnsingle' => true));

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

                        // unset flashbag for ajax
                        $app['session']->getFlashBag()->clear('success');

                        return new JsonResponse($val);
                    }
                }

                // No returnto, so we go back to the 'overview' for this contenttype.
                // check if a pager was set in the referrer - if yes go back there
                $editreferrer = $app['request']->get('editreferrer');
                if ($editreferrer) {
                    Lib::simpleredirect($editreferrer);
                } else {
                    return Lib::redirect('overview', array('contenttypeslug' => $contenttype['slug']));
                }

            } else {
                $app['session']->getFlashBag()->set('error', Trans::__('contenttypes.generic.error-saving', array('%contenttype%' => $contenttype['slug'])));
                $app['log']->add("Save content error", 3, $content, 'error');
            }
        }

        // We're doing a GET
        if (!empty($id)) {
            $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id));

            if (empty($content)) {
                $app->abort(404, Trans::__('contenttypes.generic.not-existing', array('%contenttype%' => $contenttype['slug'])));
            }

            // Check if we're allowed to edit this content..
            if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:edit:{$content['id']}")) {
                $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to edit that record.'));

                return Lib::redirect('dashboard');
            }
            $app['log']->add("Edit content", 1, $content, 'edit');
        } else {
            // Check if we're allowed to create content..
            if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:create")) {
                $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to create a new record.'));

                return Lib::redirect('dashboard');
            }

            $content = $app['storage']->getEmptyContent($contenttype['slug']);
            $app['log']->add("New content", 1, $content, 'edit');
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
            $app['session']->getFlashBag()->set('info', Trans::__('contenttypes.generic.duplicated-finalize', array('%contenttype%' => $contenttype['slug'])));
        }

        // Set the users and the current owner of this content.
        if (empty($id)) {
            // For brand-new items, the creator becomes the owner.
            $contentowner = $app['users']->getCurrentUser();
        } else {
            // For existing items, we'll just keep the current owner.
            $contentowner = $app['users']->getUser($content['ownerid']);
        }

        $context = array(
            'contenttype' => $contenttype,
            'content' => $content,
            'allowed_status' => $allowedStatuses,
            'contentowner' => $contentowner,
            'fields' => $app['config']->fields->fields(),
            'canUpload' => $app['users']->isAllowed('files:uploads')
        );

        return $app['render']->render('editcontent/editcontent.twig', array('context' => $context));
    }

    /**
     * Deletes a content item.
     *
     * @param Silex\Application $app             The application/container
     * @param string            $contenttypeslug The content type slug
     * @param integer           $id              The content ID
     * @return string
     */
    public function deleteContent(Silex\Application $app, $contenttypeslug, $id)
    {
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $content = $app['storage']->getContent($contenttype['slug'] . "/" . $id);
        $title = $content->getTitle();

        if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:delete:$id")) {
            $app['session']->getFlashBag()->set('error', Trans::__('Permission denied', array()));
        } elseif ($app['users']->checkAntiCSRFToken() && $app['storage']->deleteContent($contenttype['slug'], $id)) {
            $app['session']->getFlashBag()->set('info', Trans::__("Content '%title%' has been deleted.", array('%title%' => $title)));
        } else {
            $app['session']->getFlashBag()->set('info', Trans::__("Content '%title%' could not be deleted.", array('%title%' => $title)));
        }

        return Lib::redirect('overview', array('contenttypeslug' => $contenttype['slug']));
    }

    /**
     * Perform actions on content.
     *
     * @param Silex\Application $app             The application/container
     * @param string            $action          The action
     * @param string            $contenttypeslug The content type slug
     * @param integer           $id              The content ID
     * @return string
     */
    public function contentAction(Silex\Application $app, $action, $contenttypeslug, $id)
    {
        if ($action === 'delete') {
            return $this->deleteContent($app, $contenttypeslug, $id);
        }
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $content = $app['storage']->getContent($contenttype['slug'] . "/" . $id);
        $title = $content->getTitle();

        // map actions to new statuses
        $actionStatuses = array(
            'held' => 'held',
            'publish' => 'published',
            'draft' => 'draft',
        );
        if (!isset($actionStatuses[$action])) {
            $app['session']->getFlashBag()->set('error', Trans::__('No such action for content.'));

            return Lib::redirect('overview', array('contenttypeslug' => $contenttype['slug']));
        }
        $newStatus = $actionStatuses[$action];

        if (!$app['users']->isAllowed("contenttype:{$contenttype['slug']}:edit:$id") ||
            !$app['users']->isContentStatusTransitionAllowed($content['status'], $newStatus, $contenttype['slug'], $id)) {
            $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to edit that record.'));

            return Lib::redirect('overview', array('contenttypeslug' => $contenttype['slug']));
        }

        if ($app['storage']->updateSingleValue($contenttype['slug'], $id, 'status', $newStatus)) {
            $app['session']->getFlashBag()->set('info', Trans::__("Content '%title%' has been changed to '%newStatus%'", array('%title%' => $title, '%newStatus%' => $newStatus)));
        } else {
            $app['session']->getFlashBag()->set('info', Trans::__("Content '%title%' could not be modified.", array('%title%' => $title)));
        }

        return Lib::redirect('overview', array('contenttypeslug' => $contenttype['slug']));
    }


    /**
     * Show a list of all available users.
     *
     * @param Silex\Application $app The application/container
     * @return mixed
     */
    public function users(Silex\Application $app)
    {
        $users = $app['users']->getUsers();
        $sessions = $app['users']->getActiveSessions();

        $context = array(
            'users' => $users,
            'sessions' => $sessions
        );

        return $app['render']->render('users/users.twig', array('context' => $context));
    }

    /**
     * Show the roles page.
     *
     * @param \Bolt\Application $app The application/container
     * @return mixed
     */
    public function roles(\Bolt\Application $app)
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
            'global_permissions' => $globalPermissions,
        );

        return $app['twig']->render('roles/roles.twig', array('context' => $context));
    }

    /**
     * Edit a user.
     *
     * @param integer           $id      The user ID
     * @param \Bolt\Application $app     The application/container
     * @param Request           $request The Symfony Request
     * @return mixed
     */
    public function userEdit($id, \Bolt\Application $app, Request $request)
    {
        // Get the user we want to edit (if any)
        $user = empty($id) ? $app['users']->getEmptyUser() : $app['users']->getUser($id);
        $note = '';

        $enabledoptions = array(
            1 => Trans::__('page.edit-users.activated.yes'),
            0 => Trans::__('page.edit-users.activated.no')
        );

        $allRoles = $app['permissions']->getDefinedRoles($app);
        $roles = array();

        foreach ($allRoles as $roleName => $role) {
            $roles[$roleName] = $role['label'];
        }

        // If we're creating the first user, we should make sure that we can only create
        // a user that's allowed to log on.
        if (!$app['users']->getUsers()) {
            $firstuser = true;

            // Add a note, if we're setting up the first user using SQLite..
            $dbdriver = $app['config']->get('general/database/driver');
            if ($dbdriver == 'sqlite' || $dbdriver == 'pdo_sqlite') {
                $note = Trans::__('page.edit-users.note-sqlite');
            }

            // If we get here, chances are we don't have the tables set up, yet.
            $app['integritychecker']->repairTables();
            // Grant 'root' to first user by default
            $user['roles'] = array(Permissions::ROLE_ROOT);
        } else {
            $firstuser = false;
        }

        // Start building the form..
        $form = $app['form.factory']->createBuilder('form', $user)
            ->add('id', 'hidden')
            ->add('username', 'text', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                'label' => Trans::__('page.edit-users.label.username'),
                'attr' => array(
                    'placeholder' => Trans::__('page.edit-users.placeholder.username')
                )
            ))
            ->add('password', 'password', array(
                'required' => false,
                'label' => Trans::__('page.edit-users.label.password'),
                'attr' => array(
                    'placeholder' => Trans::__('page.edit-users.placeholder.password')
                )

            ))
            ->add('password_confirmation', 'password', array(
                'required' => false,
                'label' => Trans::__('page.edit-users.label.password-confirm'),
                'attr' => array(
                    'placeholder' => Trans::__('page.edit-users.placeholder.password-confirm')
                )
            ))
            ->add('email', 'text', array(
                'constraints' => new Assert\Email(),
                'label' => Trans::__('page.edit-users.label.email'),
                'attr' => array(
                    'placeholder' => Trans::__('page.edit-users.placeholder.email')
                )
            ))
            ->add('displayname', 'text', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                'label' => Trans::__('page.edit-users.label.display-name'),
                'attr' => array(
                    'placeholder' => Trans::__('page.edit-users.placeholder.displayname')
                )
            ));

        // If we're adding the first user, add them as 'developer' by default, so don't
        // show them here..
        if (!$firstuser) {
            $form->add(
                'enabled',
                'choice',
                array(
                    'choices' => $enabledoptions,
                    'expanded' => false,
                    'constraints' => new Assert\Choice(array_keys($enabledoptions)),
                    'label' => Trans::__('page.edit-users.label.user-enabled'),
                )
            )->add(
                'roles',
                'choice',
                array(
                    'choices' => $roles,
                    'expanded' => true,
                    'multiple' => true,
                    'label' => Trans::__('page.edit-users.label.assigned-roles'),
                )
            );
        }

        // If we're adding a new user, these fields will be hidden.
        if (!empty($id)) {
            $form->add(
                'lastseen',
                'text',
                array(
                    'disabled' => true,
                    'label' => Trans::__('page.edit-users.label.last-seen')
                )
            )->add(
                'lastip',
                'text',
                array(
                    'disabled' => true,
                    'label' => Trans::__('page.edit-users.label.last-ip')
                )
            );
        }

        // Make sure the passwords are identical and some other check, with a custom validator..
        $form->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($app) {
                $form = $event->getForm();
                $id = $form['id']->getData();
                $pass1 = $form['password']->getData();
                $pass2 = $form['password_confirmation']->getData();

                // If adding a new user (empty $id) or if the password is not empty (indicating we want to change it),
                // then make sure it's at least 6 characters long.
                if ((empty($id) || !empty($pass1)) && strlen($pass1) < 6) {
                    $error = new FormError(Trans::__('page.edit-users.error.password-short'));
                    $form['password']->addError($error);
                }

                // Passwords must be identical..
                if ($pass1 != $pass2) {
                    $form['password_confirmation']->addError(new FormError(Trans::__('page.edit-users.error.password-mismatch')));
                }

                // Usernames must be unique..
                if (!$app['users']->checkAvailability('username', $form['username']->getData(), $id)) {
                    $form['username']->addError(new FormError(Trans::__('page.edit-users.error.username-used')));
                }

                // Email addresses must be unique..
                if (!$app['users']->checkAvailability('email', $form['email']->getData(), $id)) {
                    $form['email']->addError(new FormError(Trans::__('page.edit-users.error.email-used')));
                }

                // Displaynames must be unique..
                if (!$app['users']->checkAvailability('displayname', $form['displayname']->getData(), $id)) {
                    $form['displayname']->addError(new FormError(Trans::__('page.edit-users.error.displayname-used')));
                }
            }
        );

        /**
         * @var \Symfony\Component\Form\Form $form
         */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->getMethod() == 'POST') {
            $form->submit($app['request']->get($form->getName()));

            if ($form->isValid()) {

                $user = $form->getData();

                if ($firstuser) {
                    $user['roles'] = array(Permissions::ROLE_ROOT);
                }
                $res = $app['users']->saveUser($user);

                if ($user['id']) {
                    $app['log']->add(Trans::__('page.edit-users.log.user-updated', array('%user%' => $user['displayname'])), 3, '', 'user');
                } else {
                    $app['log']->add(Trans::__('page.edit-users.log.user-added', array('%user%' => $user['displayname'])), 3, '', 'user');
                }

                if ($res) {
                    $app['session']->getFlashBag()->set('success', Trans::__('page.edit-users.message.user-saved', array('%user%' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('error', Trans::__('page.edit-users.message.saving-user', array('%user%' => $user['displayname'])));
                }

                $currentuser = $app['users']->getCurrentUser();

                if ($firstuser) {
                    // To the dashboard, where 'login' will be triggered..
                    return Lib::redirect('dashboard');
                } else if (($user['id'] == $currentuser['id']) && ($user['username'] != $currentuser['username'])) {
                    // If the current user changed their own login name, the session is effectively
                    // invalidated. If so, we must redirect to the login page with a flash message.
                    $app['session']->getFlashBag()->set('error', Trans::__('page.edit-users.message.change-self'));
                    return Lib::redirect('login');
                } else {
                    // Return to the 'Edit users' screen.
                    return Lib::redirect('users');
                }

            }

        }

        $template = $firstuser ? 'firstuser/firstuser.twig' : 'edituser/edituser.twig';
        $context = array(
            'kind' => empty($id) ? 'create' : 'edit',
            'form' => $form->createView(),
            'note' => $note,
            'displayname' => $user['displayname'],
        );

        return $app['render']->render($template, array('context' => $context));
    }

    /**
     * User profile page.
     *
     * @param \Bolt\Application $app     The application/container
     * @param Request           $request The Symfony Request
     * @return string
     */
    public function profile(\Bolt\Application $app, Request $request)
    {
        $user = $app['users']->getCurrentUser();

        // Start building the form..
        $form = $app['form.factory']->createBuilder('form', $user)
            ->add('id', 'hidden')
            ->add('password', 'password', array(
                'required' => false,
                'label' => Trans::__('page.edit-users.label.password')
            ))
            ->add('password_confirmation', 'password', array(
                'required' => false,
                'label' => Trans::__('page.edit-users.label.password-confirm')
            ))
            ->add('email', 'text', array(
                'constraints' => new Assert\Email(),
                'label' => Trans::__('page.edit-users.label.email')
            ))
            ->add('displayname', 'text', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                'label' => Trans::__('page.edit-users.label.display-name')
            ));

        // Make sure the passwords are identical and some other check, with a custom validator.
        $form->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($app) {
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

                // Email addresses must be unique..
                if (!$app['users']->checkAvailability('email', $form['email']->getData(), $id)) {
                    $form['email']->addError(new FormError(Trans::__('page.edit-users.error.email-used')));
                }

                // Displaynames must be unique..
                if (!$app['users']->checkAvailability('displayname', $form['displayname']->getData(), $id)) {
                    $form['displayname']->addError(new FormError(Trans::__('page.edit-users.error.displayname-used')));
                }
            }
        );

        /**
         * @var \Symfony\Component\Form\Form $form
         */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->getMethod() == 'POST') {
            $form->submit($app['request']->get($form->getName()));

            if ($form->isValid()) {

                $user = $form->getData();

                $res = $app['users']->saveUser($user);
                $app['log']->add(Trans::__('page.edit-users.log.user-updated', array('%user%' => $user['displayname'])), 3, '', 'user');
                if ($res) {
                    $app['session']->getFlashBag()->set('success', Trans::__('page.edit-users.message.user-saved', array('%user%' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('error', Trans::__('page.edit-users.message.saving-user', array('%user%' => $user['displayname'])));
                }

                return Lib::redirect('profile');
            }
        }

        $context = array(
            'kind' => 'profile',
            'form' => $form->createView(),
            'note' => '',
            'displayname' => $user['displayname'],
        );

        return $app['render']->render('edituser/edituser.twig', array('context' => $context));
    }

    /**
     * Perform actions on users.
     *
     * @param Silex\Application $app    The application/container
     * @param string            $action The action
     * @param integer           $id     The user ID
     * @return string
     */
    public function userAction(Silex\Application $app, $action, $id)
    {
        if (!$app['users']->checkAntiCSRFToken()) {
            $app['session']->getFlashBag()->set('info', Trans::__('An error occurred.'));

            return Lib::redirect('users');
        }
        $user = $app['users']->getUser($id);

        if (!$user) {
            $app['session']->getFlashBag()->set('error', 'No such user.');

            return Lib::redirect('users');
        }

        switch ($action) {

            case "disable":
                if ($app['users']->setEnabled($id, 0)) {
                    $app['log']->add("Disabled user '" . $user['displayname'] . "'.", 3, '', 'user');

                    $app['session']->getFlashBag()->set('info', Trans::__("User '%s' is disabled.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('info', Trans::__("User '%s' could not be disabled.", array('%s' => $user['displayname'])));
                }
                break;

            case "enable":
                if ($app['users']->setEnabled($id, 1)) {
                    $app['log']->add("Enabled user '" . $user['displayname'] . "'.", 3, '', 'user');
                    $app['session']->getFlashBag()->set('info', Trans::__("User '%s' is enabled.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('info', Trans::__("User '%s' could not be enabled.", array('%s' => $user['displayname'])));
                }
                break;

            case "delete":

                if ($app['users']->checkAntiCSRFToken() && $app['users']->deleteUser($id)) {
                    $app['log']->add("Deleted user '" . $user['displayname'] . "'.", 3, '', 'user');
                    $app['session']->getFlashBag()->set('info', Trans::__("User '%s' is deleted.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('info', Trans::__("User '%s' could not be deleted.", array('%s' => $user['displayname'])));
                }
                break;

            default:
                $app['session']->getFlashBag()->set('error', Trans::__("No such action for user '%s'.", array('%s' => $user['displayname'])));

        }

        return Lib::redirect('users');
    }

    /**
     * Show the 'about' page.
     *
     * @param Silex\Application $app The application/container
     * @return mixed
     */
    public function about(Silex\Application $app)
    {
        return $app['render']->render('about/about.twig');
    }

    /**
     * The file browser.
     *
     * @param string            $namespace The filesystem namespace
     * @param string            $path      The path prefix
     * @param Silex\Application $app       The application/container
     * @param Request           $request   The Symfony Request
     * @return mixed
     */
    public function files($namespace, $path, Silex\Application $app, Request $request)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        $filesystem = $app['filesystem']->getManager($namespace);
        $fullPath = $filesystem->getAdapter()->applyPathPrefix($path);

        if (!$app['filepermissions']->authorized($fullPath)) {
            $error = Trans::__("You don't have the correct permissions to display the file or directory '%s'.", array('%s' => $path));
            $app->abort(403, $error);
        }

        $uploadview = true;
        if (!$app['users']->isAllowed("files:uploads")) {
            $uploadview = false;
        }

        try {
            $validFolder = true;
        } catch (\Exception $e) {
            $app['session']->getFlashBag()->set('error', Trans::__("The folder '%s' could not be found, or is not readable.", array('%s' => $path)));
            $formview = false;
            $validFolder = false;
        }

        if ($validFolder) {
            // Define the "Upload here" form.
            $form = $app['form.factory']
                ->createBuilder('form')
                ->add('FileUpload', 'file', array(
                    'label' => Trans::__('Upload a file to this folder'),
                    'attr' => array(
                    'data-filename-placement' => 'inside',
                    'title' => Trans::__('Select file …'))))
                ->getForm();

            // Handle the upload.
            if ($request->isMethod('POST')) {
                $form->bind($request);
                if ($form->isValid()) {
                    $files = $request->files->get($form->getName());

                    foreach ($files as $fileToProcess) {

                        $fileToProcess = array(
                            'name' => $fileToProcess->getClientOriginalName(),
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

                                $app['session']->getFlashBag()->set(
                                    'info',
                                    Trans::__("File '%file%' was uploaded successfully.", array('%file%' => $filename))
                                );

                                // Add the file to our stack..
                                $app['stack']->add($path . "/" . $filename);
                                $result->confirm();
                            }

                        } else {
                            $extensionList = array();
                            foreach ($app['filepermissions']->getAllowedUploadExtensions() as $extension) {
                                $extensionList[] = '<code>.' . htmlspecialchars($extension, ENT_QUOTES) . '</code>';
                            }
                            $extensionList = implode(' ', $extensionList);
                            $app['session']->getFlashBag()->set(
                                'error',
                                Trans::__("File '%file%' could not be uploaded (wrong/disallowed file type). Make sure the file extension is one of the following:", array('%file%' => $filename))
                                . $extensionList
                            );
                        }

                    }
                } else {
                    $app['session']->getFlashBag()->set(
                        'error',
                        Trans::__("File '%file%' could not be uploaded.", array('%file%' => $filename))
                    );
                }

                return Lib::redirect('files', array('path' => $path, 'namespace' => $namespace));
            }

            if ($uploadview === false) {
                $formview = false;
            } else {
                $formview = $form->createView();
            }
        }

        list($files, $folders) = $filesystem->browse($path, $app);

        // Get the pathsegments, so we can show the path as breadcrumb navigation..
        $pathsegments = array();
        $cumulative = "";
        if (!empty($path)) {
            foreach (explode("/", $path) as $segment) {
                $cumulative .= $segment . "/";
                $pathsegments[$cumulative] = $segment;
            }
        }

        // Select the correct template to render this. If we've got 'CKEditor' in the title, it's a dialog
        // from CKeditor to insert a file..
        if (!$request->query->has('CKEditor')) {
            $twig = 'files/files.twig';
        } else {
            $app['debugbar'] = false;
            $twig = 'files_ck/files_ck.twig';
        }

        $context = array(
            'path' => $path,
            'files' => $files,
            'folders' => $folders,
            'pathsegments' => $pathsegments,
            'form' => $formview,
            'namespace' => $namespace,
        );

        return $app['render']->render($twig, array('context' => $context));
    }

    /**
     * File editor.
     *
     * @param string            $namespace The filesystem namespace
     * @param string            $file      The file path
     * @param Silex\Application $app       The application/container
     * @param Request           $request   The Symfony Request
     * @return mixed
     */
    public function fileEdit($namespace, $file, Silex\Application $app, Request $request)
    {
        if ($namespace == 'app' && dirname($file) == "config") {
            // Special case: If requesting one of the major config files, like contenttypes.yml, set the path to the
            // correct dir, which might be 'app/config', but it might be something else.
            $filename = realpath($app['resources']->getPath('config') . "/" . basename($file));
        } else {
            // otherwise look up the namespace and use that as the base.
            try {
                // Catch-22: If namespace is 'theme', we actually want to have 'themebase'.
                if ($namespace == "theme") {
                    $path = $app['resources']->getPath("themebase");
                } else {
                    $path = $app['resources']->getPath($namespace);
                }

                $filename = realpath($path . "/" . $file);
            } catch (\Exception $e) {
                $path = $app['resources']->getPath('files');
                $filename = realpath($path . "/" . $file);
            }
        }

        if (! $app['filepermissions']->authorized($filename)) {
            $error = Trans::__("You don't have correct permissions to edit the file '%s'.", array('%s' => $file));
            $app->abort(403, $error);
        }

        $type = Lib::getExtension($filename);

        // Get the pathsegments, so we can show the path..
        $path = dirname($file);
        $pathsegments = array();
        $cumulative = "";
        if (!empty($path)) {
            foreach (explode("/", $path) as $segment) {
                $cumulative .= $segment . "/";
                $pathsegments[$cumulative] = $segment;
            }
        }

        if (!file_exists($filename) || !is_readable($filename)) {
            $error = Trans::__("The file '%s' doesn't exist, or is not readable.", array('%s' => $file));
            $app->abort(404, $error);
        }

        if (!is_writable($filename)) {
            $app['session']->getFlashBag()->set(
                'info',
                Trans::__(
                    "The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                    array('%s' => $file)
                )
            );
            $writeallowed = false;
        } else {
            $writeallowed = true;
        }

        // Gather the 'similar' files, if present.. i.e., if we're editing config.yml, we also want to check for
        // config.yml.dist and config_local.yml
        $basename = str_replace('.yml', '', str_replace('_local', '', $filename));
        $filegroup = array();
        if (is_readable($basename . '.yml')) {
            $filegroup[] = basename($basename . '.yml');
        }
        if (is_readable($basename . '_local.yml')) {
            $filegroup[] = basename($basename . '_local.yml');
        }

        $data['contents'] = file_get_contents($filename);

        $form = $app['form.factory']
            ->createBuilder('form', $data)
            ->add('contents', 'textarea')
            ->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->getMethod() == "POST") {
            $form->bind($app['request']->get($form->getName()));

            if ($form->isValid()) {

                $data = $form->getData();
                $contents = Input::cleanPostedData($data['contents']) . "\n";

                $ok = true;

                // Before trying to save a yaml file, check if it's valid.
                if ($type == "yml") {
                    $yamlparser = new \Symfony\Component\Yaml\Parser();
                    try {
                        $ok = $yamlparser->parse($contents);
                    } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                        $ok = false;
                        $app['session']->getFlashBag()->set('error', Trans::__("File '%s' could not be saved:", array('%s' => $file)) . $e->getMessage());
                    }
                }

                if ($ok) {
                    if (file_put_contents($filename, $contents)) {
                        $app['session']->getFlashBag()->set('info', Trans::__("File '%s' has been saved.", array('%s' => $file)));
                        // If we've saved a translation, back to it
                        if (preg_match('#resources/translations/(..)/(.*)\.yml$#', $filename, $m)) {
                            return Lib::redirect('translation', array('domain' => $m[2], 'tr_locale' => $m[1]));
                        }
                        Lib::redirect('fileedit', array('file' => $file), '');
                    } else {
                        $app['session']->getFlashBag()->set('error', Trans::__("File '%s' could not be saved, for some reason.", array('%s' => $file)));
                    }
                }
                // If we reach this point, the form will be shown again, with the error
                // in the input, so the user can try again.
            }
        }

        // For 'related' files we might need to keep track of the current dirname on top of the namespace.
        if (dirname($file) != '') {
            $additionalpath = dirname($file) . '/';
        } else {
            $additionalpath = '';
        }

        $context = array(
            'form' => $form->createView(),
            'filetype' => $type,
            'file' => $file,
            'basename' => basename($file),
            'pathsegments' => $pathsegments,
            'additionalpath' => $additionalpath,
            'namespace' => $namespace,
            'write_allowed' => $writeallowed,
            'filegroup' => $filegroup
        );

        return $app['render']->render('editfile/editfile.twig', array('context' => $context));
    }

    /**
     * Prepare/edit/save a translation
     *
     * @param string            $domain    The domain
     * @param string            $tr_locale The translation locale
     * @param Silex\Application $app       The application/container
     * @param Request           $request   The Symfony Request
     * @return mixed
     */
    public function translation($domain, $tr_locale, Silex\Application $app, Request $request)
    {
        $translation = new TranslationFile($app, $domain, $tr_locale);

        list($path, $shortPath) = $translation->path();

        $app['log']->add('Editing translation: ' . $shortPath, $app['debug'] ? 1 : 3);

        $data['contents'] = $translation->content();

        $writeallowed = $translation->isWriteAllowed();

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'contents',
                'textarea',
                array('constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 10))))
            )
            ->getForm();

        // Check if the form was POST-ed, and valid. If so, store the file.
        if ($request->getMethod() == 'POST') {
            $form->bind($app['request']->get($form->getName()));

            if ($form->isValid()) {

                $data = $form->getData();
                $contents = Input::cleanPostedData($data['contents']) . "\n";

                // Before trying to save a yaml file, check if it's valid.
                try {
                    $ok = Yaml::parse($contents);
                } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                    $ok = false;
                    $msg = Trans::__("File '%s' could not be saved:", array('%s' => $shortPath));
                    $app['session']->getFlashBag()->set('error', $msg . $e->getMessage());
                }

                // Before trying to save, check if it's writable.
                if ($ok) {
                    // clear any warning for file not found, we are creating it here
                    // we'll set an error if someone still submits the form and write is not allowed
                    $app['session']->getFlashBag()->clear('warning');

                    if (!$writeallowed) {
                        $msg = Trans::__("The file '%s' is not writable. You will have to use your own editor to make modifications to this file.", array('%s' => $shortPath));
                        $app['session']->getFlashBag()->set('error', $msg);
                    } else {
                        file_put_contents($path, $contents);
                        $msg = Trans::__("File '%s' has been saved.", array('%s' => $shortPath));
                        $app['session']->getFlashBag()->set('info', $msg);

                        return Lib::redirect('translation', array('domain' => $domain, 'tr_locale' => $tr_locale));
                    }
                }
            }
        }

        $context = array(
            'form' => $form->createView(),
            'basename' => basename($shortPath),
            'filetype' => 'yml',
            'write_allowed' => $writeallowed,
        );

        return $app['render']->render('editlocale/editlocale.twig', array('context' => $context));
    }

    /**
     * Middleware function to check whether a user is logged on.
     *
     * @param Request           $request The Symfony Request
     * @param \Bolt\Application $app     The application/container
     * @return mixed
     */
    public function before(Request $request, \Bolt\Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.backend.before');

        $route = $request->get('_route');

        $app['log']->setRoute($route);

        $app['debugbar'] = true;

        // If the users table is present, but there are no users, and we're on /bolt/useredit,
        // we let the user stay, because they need to set up the first user.
        if ($app['integritychecker']->checkUserTableIntegrity() && !$app['users']->getUsers() && $route == 'useredit') {
            $app['twig']->addGlobal('frontend', false);

            return;
        }

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['integritychecker']->checkUserTableIntegrity() || !$app['users']->getUsers()) {
            $app['integritychecker']->repairTables();
            $app['session']->getFlashBag()->set('info', Trans::__('There are no users in the database. Please create the first user.'));

            return Lib::redirect('useredit', array('id' => ""));
        }

        // Check if there's at least one 'root' user, and otherwise promote the current user.
        $app['users']->checkForRoot();

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        if (!$app['users']->isValidSession() && !$app['users']->isAllowed($route)) {
            $app['session']->getFlashBag()->set('info', Trans::__('Please log on.'));

            return Lib::redirect('login');
        } elseif (!$app['users']->isAllowed($route)) {
            $app['session']->getFlashBag()->set('error', Trans::__('You do not have the right privileges to view that page.'));

            return Lib::redirect('dashboard');
        }
        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');
    }
}
