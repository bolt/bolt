<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class Backend implements ControllerProviderInterface
{
    public function connect(Silex\Application $app)
    {
        $ctl = $app['controllers_factory'];

        $ctl->get("", array($this, 'dashboard'))
            ->before(array($this, 'before'))
            ->bind('dashboard');

        $ctl->match("/login", array($this, 'getLogin'))
            ->method('GET')
            ->before(array($this, 'before'))
            ->bind('login');

        $ctl->match("/login", array($this, 'postLogin'))
            ->method('POST')
            ->before(array($this, 'before'))
            ->bind('postLogin');

        $ctl->get("/logout", array($this, 'logout'))
            ->method('POST')
            ->bind('logout');

        $ctl->match("/resetpassword", array($this, 'resetpassword'))
            ->bind('resetpassword')
            ->method('GET');

        $ctl->get("/dbcheck", array($this, 'dbcheck'))
            ->before(array($this, 'before'))
            ->bind('dbcheck');

        $ctl->get("/dbupdate", array($this, 'dbupdate'))
            ->method('POST')
            ->before(array($this, 'before'))
            ->bind('dbupdate');

        $ctl->get("/dbupdate_result", array($this, 'dbupdate_result'))
            ->method('GET')
            ->before(array($this, 'before'))
            ->bind('dbupdate_result');

        $ctl->get("/clearcache", array($this, 'clearcache'))
            ->before(array($this, 'before'))
            ->bind('clearcache');

        $ctl->match("/prefill", array($this, 'prefill'))
            ->before(array($this, 'before'))
            ->method('GET|POST')
            ->bind('prefill');

        $ctl->get("/overview/{contenttypeslug}", array($this, 'overview'))
            ->before(array($this, 'before'))
            ->bind('overview');

        $ctl->match("/editcontent/{contenttypeslug}/{id}", array($this, 'editcontent'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET|POST')
            ->bind('editcontent');

        $ctl->get("/content/{action}/{contenttypeslug}/{id}", array($this, 'contentaction'))
            ->before(array($this, 'before'))
            ->bind('contentaction');

        $ctl->get("/changelog/{contenttype}/{contentid}", array($this, 'changelogList'))
            ->before(array($this, 'before'))
            ->value('contentid', '0')
            ->value('contenttype', '')
            ->bind('changeloglist');

        $ctl->get("/changelog/{contenttype}/{contentid}/{id}", array($this, 'changelogDetails'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->bind('changelogdetails');

        $ctl->get("/users", array($this, 'users'))
            ->before(array($this, 'before'))
            ->bind('users');

        $ctl->match("/users/edit/{id}", array($this, 'useredit'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET|POST')
            ->bind('useredit');

        $ctl->get("/about", array($this, 'about'))
            ->before(array($this, 'before'))
            ->bind('about');

        $ctl->get("/extensions", array($this, 'extensions'))
            ->before(array($this, 'before'))
            ->bind('extensions');

        $ctl->get("/user/{action}/{id}", array($this, 'useraction'))
            ->before(array($this, 'before'))
            ->bind('useraction');

        $ctl->match("/files/{path}", array($this, 'files'))
            ->before(array($this, 'before'))
            ->assert('path', '.+')
            ->bind('files');

        $ctl->get("/activitylog", array($this, 'activitylog'))
            ->before(array($this, 'before'))
            ->bind('activitylog');

        $ctl->match("/file/edit/{file}", array($this, 'fileedit'))
            ->before(array($this, 'before'))
            ->assert('file', '.+')
            ->method('GET|POST')
            ->bind('fileedit');

        $ctl->match("/tr/{domain}/{tr_locale}", array($this, 'translation'))
            ->before(array($this, 'before'))
            ->assert('domain', 'messages|contenttypes|infos')
            ->value('domain', 'messages')
            ->value('tr_locale', $app['config']->get('general/locale'))
            ->method('GET|POST')
            ->bind('translation');

        return $ctl;
    }

    /**
     * Dashboard or "root".
     */
    public function dashboard(\Bolt\Application $app)
    {

        $limit = $app['config']->get('general/recordsperdashboardwidget');

        $total = 0;
        $latest = array();
        // get the 'latest' from each of the content types.
        foreach ($app['config']->get('contenttypes') as $key => $contenttype) {
            if ($app['users']->isAllowed('contenttype:' . $key) && $contenttype['show_on_dashboard'] == true) {
                $latest[$key] = $app['storage']->getContent($key, array('limit' => $limit, 'order' => 'datechanged DESC'));
                if (!empty($latest[$key])) {
                    $total += count($latest[$key]);
                }
            }
        }

        // If there's nothing in the DB, suggest to create some dummy content.
        if ($total == 0) {
            $suggestloripsum = true;
        } else {
            $suggestloripsum = false;
        }

        $app['twig']->addGlobal('title', __("Dashboard"));

        return $app['twig']->render('dashboard.twig', array('latest' => $latest, 'suggestloripsum' => $suggestloripsum));

    }


    public function postLogin(Silex\Application $app, Request $request)
    {
        switch ($request->get('action')) {
            case 'login':
                // Log in, if credentials are correct.
                $result = $app['users']->login($request->get('username'), $request->get('password'));

                if ($result) {
                    $app['log']->add("Login " . $request->get('username'), 3, '', 'login');

                    return redirect('dashboard');
                }
                return $this->getLogin($app, $request);

            case 'reset':
                // Send a password request mail, if username exists.
                $username = trim($request->get('username'));
                if (empty($username)) {
                    $app['users']->session->getFlashBag()->set('error', __("Please provide a username", array()));
                }
                else {
                    $app['users']->resetPasswordRequest($request->get('username'));
                    return redirect('login');
                }
                return $this->getLogin($app, $request);

            default:
                // Let's not disclose any internal information.
                return abort(400, 'Invalid request');
        }
    }

    /**
     * Login page and "Forgotten password" page.
     */
    public function getLogin(Silex\Application $app, Request $request)
    {
        $app['twig']->addGlobal('title', "Login");
        return $app['twig']->render('login.twig');
    }

    /**
     * Logout page.
     */
    public function logout(Silex\Application $app)
    {

        $app['log']->add("Logout", 3, '', 'logout');

        $app['users']->logout();

        return redirect('login');

    }


    /**
     * Reset the password. This controller is normally only reached when the user
     * clicks a "password reset" link in the email.
     *
     * @param Silex\Application $app
     * @param Request $request
     * @return string
     */
    public function resetpassword(Silex\Application $app, Request $request)
    {

        $app['users']->resetPasswordConfirm($request->get('token'));

        return redirect('login');

    }


    /**
     * Check the database for missing tables and columns. Does not do actual repairs
     */
    public function dbcheck(\Bolt\Application $app)
    {

        $output = $app['storage']->getIntegrityChecker()->checkTablesIntegrity();

        $app['twig']->addGlobal('title', __("Database check / update"));

        return $app['twig']->render('dbcheck.twig', array(
            'required_modifications' => $output,
            'active' => "settings"
        ));

    }

    /**
     * Check the database, create tables, add missing/new columns to tables
     */
    public function dbupdate(Silex\Application $app)
    {

        $output = $app['storage']->getIntegrityChecker()->repairTables();

        // If 'return=edit' is passed, we should return to the edit screen. We do redirect twice, yes,
        // but that's because the newly saved contenttype.yml needs to be re-read.
        $return = $app['request']->query->get('return');
        if ($return == "edit") {
            if (empty($output)) {
                $content = __("Your database is already up to date.");
            } else {
                $content = __("Your database is now up to date.");
            }
            $app['session']->getFlashBag()->set('success', $content);

            return redirect('fileedit', array('file' => "app/config/contenttypes.yml"));
        }
        else {
            return redirect('dbupdate_result', array('messages' => json_encode($output)));
        }
    }

    public function dbupdate_result(Silex\Application $app, Request $request) {
        $output = json_decode($request->get('messages'));

        $app['twig']->addGlobal('title', __("Database check / update"));

        return $app['twig']->render('dbcheck.twig', array(
            'modifications' => $output,
            'active' => "settings"
        ));

    }


    /**
     * Clear the cache.
     */
    public function clearcache(Silex\Application $app)
    {

        $result = $app['cache']->clearCache();

        $output = __("Deleted %s files from cache.", array('%s' => $result['successfiles']));

        if (!empty($result['failedfiles'])) {
            $output .= " " . __("%s files could not be deleted. You should delete them manually.", array('%s' => $result['failedfiles']));
            $app['session']->getFlashBag()->set('error', $output);
        } else {
            $app['session']->getFlashBag()->set('success', $output);
        }

        $app['twig']->addGlobal('title', __("Clear the cache"));

        $content = "<p><a href='" . path('clearcache') . "' class='btn btn-primary'>" . __("Clear cache again") . "</a></p>";

        return $app['twig']->render('base.twig', array(
            'content' => $content,
            'active' => "settings"
        ));

    }


    /**
     * Show the activity-log.
     */
    public function activitylog(Silex\Application $app)
    {

        $title = __('Activity log');

        $action = $app['request']->query->get('action');

        if ($action == "clear") {
            $app['log']->clear();
            $app['session']->getFlashBag()->set('success', __('The activitylog has been cleared.'));

            return redirect('activitylog');
        } elseif ($action == "trim") {
            $app['log']->trim();
            $app['session']->getFlashBag()->set('success', __('The activitylog has been trimmed.'));

            return redirect('activitylog');
        }

        $activity = $app['log']->getActivity(16);

        return $app['twig']->render('activity.twig', array('title' => $title, 'activity' => $activity));

    }


    /**
     * Generate some lipsum in the DB.
     */
    public function prefill(Silex\Application $app, Request $request)
    {

        $choices = array();
        foreach ($app['config']->get('contenttypes') as $key => $cttype) {
            $choices[$key] = __('%contenttypes%', array('%contenttypes%' => $cttype['name']));
        }
        $form = $app['form.factory']->createBuilder('form')
            ->add('contenttypes', 'choice', array(
                'label' => '**ignored, see base.twig**',
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
            ))
            ->getForm();

        if (($request->getMethod() == "POST") || ($request->get('force') == 1)) {
            $form->bind($request);
            $ctypes = $form->get('contenttypes')->getData();
            $content = $app['storage']->preFill($ctypes);
            $app['session']->getFlashBag()->set('success', $content);

            return redirect('prefill');
        }

        $app['twig']->addGlobal('title', __('Fill the database with Dummy Content'));

        return $app['twig']->render('base.twig', array(
            'content' => '',
            'contenttypes' => $choices,
            'form' => $form->createView()
        ));

    }


    /**
     * Check the database, create tables, add missing/new columns to tables
     */
    public function overview(Silex\Application $app, $contenttypeslug)
    {

        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$app['users']->isAllowed('contenttype:' . $contenttypeslug)) {
            $app['session']->getFlashBag()->set('error', __('You do not have the right privileges to view that page.'));

            return redirect('dashboard');
        }

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $order = $app['request']->query->get('order', '');
        $page = $app['request']->query->get('page');
        $filter = $app['request']->query->get('filter');

        // Set the amount of items to show per page.
        if (!empty($contenttype['recordsperpage'])) {
            $limit = $contenttype['recordsperpage'];
        } else {
            $limit = $app['config']->get('general/recordsperpage');
        }


        $multiplecontent = $app['storage']->getContent($contenttype['slug'],
            array('limit' => $limit, 'order' => $order, 'page' => $page, 'filter' => $filter));

        // @todo Do we need pager here?
        $app['pager'] = $pager;

        $title = sprintf("<strong>%s</strong> » %s", __('Overview'), $contenttype['name']);
        $app['twig']->addGlobal('title', $title);

        return $app['twig']->render('overview.twig',
            array('contenttype' => $contenttype, 'multiplecontent' => $multiplecontent)
        );

    }

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
            }
            else {
                $page = intval($page);
            }
        }
        else {
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
            $title = __('All content types');
            $logEntries = $app['storage']->getChangelog($options);
            $itemcount = $app['storage']->countChangelog($options);
        }
        else {
            // We have a content type, and possibly a contentid.
            $contenttypeObj = $app['storage']->getContentType($contenttype);
            if ($contentid) {
                $content = $app['storage']->getContent($contenttype, array('id' => $contentid));
                $options['contentid'] = $contentid;
            }
            // Getting a slice of data and the total count
            $logEntries = $app['storage']->getChangelogByContentType($contenttype, $options);
            $itemcount = $app['storage']->countChangelogByContentType($contenttype, $options);

            // The page title we're sending to the template depends on a few
            // things: if no contentid is given, we'll use the plural form
            // of the content type; otherwise, we'll derive it from the
            // changelog or content item itself.
            if ($contentid) {
                if ($content) {
                    // content item is available: get the current title
                    $title = $content->getTitle();
                }
                else {
                    // content item does not exist (anymore).
                    if (empty($logEntries)) {
                        // No item, no entries - phew. Content type name and ID
                        // will have to do.
                        $title = $contenttypeObj['singular_name'] . ' #' . $contentid;
                    }
                    else {
                        // No item, but we can use the most recent title.
                        $title = $logEntries[0]['title'];
                    }
                }
            }
            else {
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
        if ($limit) {
            $pagecount = ceil($itemcount / $limit);
        }
        else {
            $pagecount = null;
        }

        $renderVars = array(
            'contenttype' => $contenttype,
            'entries' => $logEntries,
            'content' => $content,
            'title' => $title,
            'itemcount' => $itemcount,
            'pagecount' => $pagecount,
            'currentpage' => $page,
            );
        return $app['twig']->render('changeloglist.twig', $renderVars);
    }

    public function changelogDetails($contenttype, $contentid, $id, Silex\Application $app, Request $request)
    {
        $entry = $app['storage']->getChangelogEntry($contenttype, $contentid, $id);
        if (empty($entry)) {
            $error = __("The requested changelog entry doesn't exist.");
            $app->abort(404, $error);
        }
        $prev = $app['storage']->getPrevChangelogEntry($contenttype, $contentid, $id);
        $next = $app['storage']->getNextChangelogEntry($contenttype, $contentid, $id);
        $content = $app['storage']->getContent($contenttype, array('id' => $contentid));
        $renderVars = array(
            'contenttype' => $contenttype,
            'entry' => $entry,
            'nextEntry' => $next,
            'prevEntry' => $prev,
            'content' => $content,
            );
        return $app['twig']->render('changelogdetails.twig', $renderVars);
    }

    /**
     * Edit a unit of content, or create a new one.
     */
    public function editcontent($contenttypeslug, $id, Silex\Application $app, Request $request)
    {

        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$app['users']->isAllowed('contenttype:' . $contenttypeslug)) {
            $app['session']->getFlashBag()->set('error', __('You do not have the right privileges to edit that record.'));

            return redirect('dashboard');
        }

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        if ($request->getMethod() == "POST") {

            $content = $app['storage']->getContentObject($contenttypeslug);
            $content->setFromPost($request->request->all(), $contenttype);

            // Don't try to spoof the $id..
            if (!empty($content['id']) && $id != $content['id']) {
                $app['session']->getFlashBag()->set('error', "Don't try to spoof the id!");

                return redirect('dashboard');
            }

            // Save the record, and return to the overview screen, or to the record (if we clicked 'save and continue')
            if ($app['storage']->saveContent($content, $contenttype['slug'])) {

                if (!empty($id)) {
                    $app['session']->getFlashBag()->set('success', __('The changes to this %contenttype% have been saved.', array('%contenttype%' => $contenttype['singular_name'])));
                } else {
                    $app['session']->getFlashBag()->set('success', __('The new %contenttype% has been saved.', array('%contenttype%' => $contenttype['singular_name'])));
                }
                $app['log']->add($content->getTitle(), 3, $content, 'save content');

                // If 'returnto is set', we return to the edit page, with the correct anchor.
                if ($app['request']->get('returnto')) {

                    // We must 'return to' the edit page. In which case we must know the Id, so let's fetch it.
                    if (empty($id)) {
                        $id = $app['storage']->getLatestId($contenttype['slug']);
                    }

                    return redirect('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => $id), "#".$app['request']->get('returnto'));

                }

                // No returnto, so we go back to the 'overview' for this contenttype.
                return redirect('overview', array('contenttypeslug' => $contenttype['slug']));

            } else {
                $app['session']->getFlashBag()->set('error', __('There was an error saving this %contenttype%.', array('%contenttype%' => $contenttype['singular_name'])));
                $app['log']->add("Save content error", 3, $content, 'error');
            }

        }

        if (!empty($id)) {
            $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id));
            if (empty($content)) {
                $app->abort(404, __('The %contenttype% you were looking for does not exist. It was probably deleted, or it never existed.', array('%contenttype%' => $contenttype['singular_name'])));
            }

            // Check if we're allowed to edit this content..
            if (($content['username'] != $app['users']->getCurrentUsername()) && !$app['users']->isAllowed('editcontent:all')) {
                $app['session']->getFlashBag()->set('error', __('You do not have the right privileges to edit that record.'));

                return redirect('dashboard');
            }

            $title = sprintf("<strong>%s</strong> » %s", __('Edit %contenttype%', array('%contenttype%' => $contenttype['singular_name'])), $content->getTitle());
            $app['log']->add("Edit content", 1, $content, 'edit');
        } else {
            $content = $app['storage']->getEmptyContent($contenttype['slug']);
            $title = sprintf("<strong>%s</strong>", __('New %contenttype%', array('%contenttype%' => $contenttype['singular_name'])));
            $app['log']->add("New content", 1, $content, 'edit');
        }


        $app['twig']->addGlobal('title', $title);

        $duplicate = $app['request']->query->get('duplicate');
        if (!empty($duplicate)) {
            $content->setValue('id', "");
            $content->setValue('slug', "");
            $content->setValue('datecreated', "");
            $content->setValue('datepublish', "");
            $content->setValue('datedepublish', "");
            $content->setValue('datechanged', "");
            $content->setValue('username', "");
            $app['session']->getFlashBag()->set('info', __("Content was duplicated. Click 'Save %contenttype%' to finalize.", array('%contenttype%' => $contenttype['singular_name'])));
        }

        // Set the users and the current owner of this content.

        if ($content->get('username') != "") {
            $contentowner = $content->get('username');
        } else {
            $user = $app['session']->get('user');
            $contentowner = $user['username'];
        }

        return $app['twig']->render('editcontent.twig', array(
            'contenttype' => $contenttype,
            'content' => $content,
            'contentowner' => $contentowner
        ));

    }


    /**
     * Perform actions on content.
     */
    public function contentaction(Silex\Application $app, $action, $contenttypeslug, $id)
    {

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $content = $app['storage']->getContent($contenttype['slug'] . "/" . $id);
        $title = $content->getTitle();

        // Check if we're allowed to edit this content..
        if (($content['username'] != $app['users']->getCurrentUsername()) && !$app['users']->isAllowed('editcontent:all')) {
            $app['session']->getFlashBag()->set('error', __('You do not have the right privileges to edit that record.'));

            return redirect('dashboard');
        }

        switch ($action) {

            case "held":
                if ($app['storage']->updateSingleValue($contenttype['slug'], $id, 'status', 'held')) {
                    $app['session']->getFlashBag()->set('info', __("Content '%title%' has been changed to 'held'", array('%title%' => $title)));
                } else {
                    $app['session']->getFlashBag()->set('info', __("Content '%title%' could not be modified.", array('%title%' => $title)));
                }
                break;

            case "publish":
                if ($app['storage']->updateSingleValue($contenttype['slug'], $id, 'status', 'published')) {
                    $app['session']->getFlashBag()->set('info', __("Content '%title%' is published.", array('%title%' => $title)));
                } else {
                    $app['session']->getFlashBag()->set('info', __("Content '%title%' could not be modified.", array('%title%' => $title)));
                }
                break;

            case "draft":
                if ($app['storage']->updateSingleValue($contenttype['slug'], $id, 'status', 'draft')) {
                    $app['session']->getFlashBag()->set('info', __("Content '%title%' has been changed to 'draft'.", array('%title%' => $title)));
                } else {
                    $app['session']->getFlashBag()->set('info', __("Content '%title%' could not be modified.", array('%title%' => $title)));
                }
                break;

            case "delete":

                if (checkToken() && $app['storage']->deleteContent($contenttype['slug'], $id)) {
                    $app['session']->getFlashBag()->set('info', __("Content '%title%' has been deleted.", array('%title%' => $title)));
                } else {
                    $app['session']->getFlashBag()->set('info', __("Content '%title%' could not be deleted.", array('%title%' => $title)));
                }
                break;

            default:
                $app['session']->getFlashBag()->set('error', __('No such action for content.'));

        }

        return redirect('overview', array('contenttypeslug' => $contenttype['slug']));

    }

    /**
     * Show a list of all available users.
     */
    public function users(Silex\Application $app)
    {

        $users = $app['users']->getUsers();
        $sessions = $app['users']->getActiveSessions();
        $userlevels = $app['users']->getUserLevels();

        return $app['twig']->render(
            'users.twig',
            array('users' => $users, 'userlevels' => $userlevels, 'sessions' => $sessions)
        );

    }

    public function useredit($id, \Bolt\Application $app, Request $request)
    {

        // Get the user we want to edit (if any)
        if (!empty($id)) {
            $user = $app['users']->getUser($id);
            $title = "<strong>" . __('Edit user') . "</strong> » " . $user['displayname'];
        } else {
            $user = $app['users']->getEmptyUser();
            $title = "<strong>" . __('Create a new user') . "</strong>";
        }

        $userlevels = $app['users']->getUserLevels();
        $enabledoptions = array(1 => 'yes', 0 => 'no');
        $contenttypes = makeValuepairs($app['config']->get('contenttypes'), 'slug', 'name');

        // If we're creating the first user, we should make sure that we can only create
        // a user that's allowed to log on.
        if (!$app['users']->getUsers()) {
            $firstuser = true;
            $title = __('Create the first user');
            // If we get here, chances are we don't have the tables set up, yet.
            $app['storage']->getIntegrityChecker()->repairTables();
        } else {
            $firstuser = false;
        }

        // Start building the form..
        $form = $app['form.factory']->createBuilder('form', $user)
            ->add('id', 'hidden')
            ->add('username', 'text', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                'label' => __('Username')
            ))
            ->add('password', 'password', array(
                'required' => false,
                'label' => __('Password')
            ))
            ->add('password_confirmation', 'password', array(
                'required' => false,
                'label' => __("Password (confirmation)")
            ))
            ->add('email', 'text', array(
                'constraints' => new Assert\Email(),
                'label' => __('Email')
            ))
            ->add('displayname', 'text', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2, 'max' => 32))),
                'label' => __('Display name')
            ));

        // If we're adding the first user, add them as 'developer' by default, so don't
        // show them here..
        if ($firstuser) {
            $form->add('userlevel', 'hidden', array(
                'data' => \util::array_last_key($userlevels) // last element, highest userlevel..
            ));
        } else {
            $form->add('userlevel', 'choice', array(
                'choices' => $userlevels,
                'expanded' => false,
                'constraints' => new Assert\Choice(array_keys($userlevels)),
                'label' => __('User level')
            ))
                ->add('enabled', 'choice', array(
                    'choices' => $enabledoptions,
                    'expanded' => false,
                    'constraints' => new Assert\Choice(array_keys($enabledoptions)),
                    'label' => __("User is enabled"),
                ))
                ->add('contenttypes', 'choice', array(
                    'choices' => $contenttypes,
                    'expanded' => true,
                    'multiple' => true,
                    'label' => __("Allowed contenttypes"),
                ));
        }

        // If we're adding a new user, these fields will be hidden.
        if (!empty($id)) {
            $form->add('lastseen', 'text', array(
                    'disabled' => true,
                    'label' => __('Last seen')
                ))
                ->add('lastip', 'text', array(
                    'disabled' => true,
                    'label' => __('Last IP')
                ));
        }

        // Make sure the passwords are identical and some other check, with a custom validator..
        $form->addEventListener(FormEvents::POST_BIND, function (FormEvent $event) use ($app) {

            $form = $event->getForm();
            $id = $form['id']->getData();
            $pass1 = $form['password']->getData();
            $pass2 = $form['password_confirmation']->getData();

            // If adding a new user (empty $id) or if the password is not empty (indicating we want to change it),
            // then make sure it's at least 6 characters long.
            if ((empty($id) || !empty($pass1)) && strlen($pass1) < 6) {
                // screw it. Let's just not translate this message for now. Damn you, stupid non-cooperative translation thingy.
                //$error = new FormError("This value is too short. It should have {{ limit }} characters or more.", array('{{ limit }}' => 6), 2);
                $error = new FormError(__("This value is too short. It should have 6 characters or more."));
                $form['password']->addError($error);
            }

            // Passwords must be identical..
            if ($pass1 != $pass2) {
                $form['password_confirmation']->addError(new FormError(__('Passwords must match.')));
            }

            // Usernames must be unique..
            if (!$app['users']->checkAvailability('username', $form['username']->getData(), $id)) {
                $form['username']->addError(new FormError(__('This username is already in use. Choose another username.')));
            }

            // Email addresses must be unique..
            if (!$app['users']->checkAvailability('email', $form['email']->getData(), $id)) {
                $form['email']->addError(new FormError(__('This email address is already in use. Choose another email address.')));
            }

            // Displaynames must be unique..
            if (!$app['users']->checkAvailability('displayname', $form['displayname']->getData(), $id)) {
                $form['displayname']->addError(new FormError(__('This displayname is already in use. Choose another displayname.')));
            }

        });

        /**
         * @var \Symfony\Component\Form\Form $form
         */
        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->getMethod() == "POST") {
            //$form->bindRequest($request);
            $form->bind($app['request']->get($form->getName()));

            if ($form->isValid()) {

                $user = $form->getData();

                $res = $app['users']->saveUser($user);
                $app['log']->add(__("Added user '%s'.", array('%s' => $user['displayname'])), 3, '', 'user');
                if ($res) {
                    $app['session']->getFlashBag()->set('success', __('User %s has been saved.', array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('error', __('User %s could not be saved, or nothing was changed.', array('%s' => $user['displayname'])));
                }

                return redirect('users');

            }
        }

        return $app['twig']->render('edituser.twig', array(
            'form' => $form->createView(),
            'title' => $title
        ));

    }

    /**
     * Perform actions on users.
     */
    public function useraction(Silex\Application $app, $action, $id)
    {

        $user = $app['users']->getUser($id);

        if (!$user) {
            $app['session']->getFlashBag()->set('error', "No such user.");

            return redirect('users');
        }

        switch ($action) {

            case "disable":
                if ($app['users']->setEnabled($id, 0)) {
                    $app['log']->add("Disabled user '" . $user['displayname'] . "'.", 3, '', 'user');

                    $app['session']->getFlashBag()->set('info', __("User '%s' is disabled.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('info', __("User '%s' could not be disabled.", array('%s' => $user['displayname'])));
                }
                break;

            case "enable":
                if ($app['users']->setEnabled($id, 1)) {
                    $app['log']->add("Enabled user '" . $user['displayname'] . "'.", 3, '', 'user');
                    $app['session']->getFlashBag()->set('info', __("User '%s' is enabled.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('info', __("User '%s' could not be enabled.", array('%s' => $user['displayname'])));
                }
                break;

            case "delete":

                if (checkToken() && $app['users']->deleteUser($id)) {
                    $app['log']->add("Deleted user '" . $user['displayname'] . "'.", 3, '', 'user');
                    $app['session']->getFlashBag()->set('info', __("User '%s' is deleted.", array('%s' => $user['displayname'])));
                } else {
                    $app['session']->getFlashBag()->set('info', __("User '%s' could not be deleted.", array('%s' => $user['displayname'])));
                }
                break;

            default:
                $app['session']->getFlashBag()->set('error', __("No such action for user '%s'.", array('%s' => $user['displayname'])));

        }

        return redirect('users');

    }

    /**
     * Show the 'about' page
     */
    public function about(Silex\Application $app)
    {
        return $app['twig']->render('about.twig');

    }

    /**
     * Show a list of all available extensions.
     */
    public function extensions(Silex\Application $app)
    {

        $title = "Extensions";

        $extensions = $app['extensions']->getInfo();

        return $app['twig']->render('extensions.twig', array('extensions' => $extensions, 'title' => $title));

    }

    public function files($path, Silex\Application $app, Request $request)
    {

        $files = array();
        $folders = array();

        $basefolder = __DIR__ . "/../../../../";
        $path = stripTrailingSlash(str_replace("..", "", $path));
        $currentfolder = realpath($basefolder . $path);

        if (is_writable($currentfolder)) {

            // Define the "Upload here" form.
            $form = $app['form.factory']
                ->createBuilder('form')
                ->add('FileUpload', 'file', array('label' => __("Upload a file to this folder:")))
                ->getForm();

            // Handle the upload.
            if ($request->isMethod('POST')) {
                $form->bind($request);
                if ($form->isValid()) {
                    $files = $request->files->get($form->getName());
                    /* Make sure that Upload Directory is properly configured and writable */
                    $filename = $files['FileUpload']->getClientOriginalName();
                    $files['FileUpload']->move($currentfolder, $filename);
                    echo "path: $path";
                    $app['session']->getFlashBag()->set('info', __("File '%file%' was uploaded successfully.", array('%file%' => $filename)));

                    // Add the file to our stack..
                    $app['stack']->add($path . "/" . $filename);
                } else {
                    $app['session']->getFlashBag()->set('error', __("File '%file%' could not be uploaded.", array('%file%' => $filename)));
                }
                return redirect('files', array('path' => $path));
            }

            $formview = $form->createView();

        } else {
            // Folder not writable, don't show an upload.
            $formview = false;
        }


        $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");

        // Get the pathsegments, so we can show the path..
        $pathsegments = array();
        $cumulative = "";
        if (!empty($path)) {
            foreach (explode("/", $path) as $segment) {
                $cumulative .= $segment . "/";
                $pathsegments[$cumulative] = $segment;
            }
        }

        if (file_exists($currentfolder)) {

            $d = dir($currentfolder);

            while (false !== ($entry = $d->read())) {

                if (in_array($entry, $ignored)) {
                    continue;
                }

                $fullfilename = $currentfolder . "/" . $entry;

                if (is_file($fullfilename)) {
                    $files[$entry] = array(
                        'path' => $path,
                        'filename' => $entry,
                        'newpath' => $path . "/" . $entry,
                        'writable' => is_writable($fullfilename),
                        'readable' => is_readable($fullfilename),
                        'type' => getExtension($entry),
                        'filesize' => formatFilesize(filesize($fullfilename)),
                        'modified' => date("Y/m/d H:i:s", filemtime($fullfilename)),
                        'permissions' => \util::full_permissions($fullfilename)
                    );

                    if (in_array(getExtension($entry), array('gif', 'jpg', 'png', 'jpeg'))) {
                        $size = getimagesize($fullfilename);
                        $files[$entry]['imagesize'] = sprintf("%s × %s", $size[0], $size[1]);
                    }
                }

                if (is_dir($fullfilename)) {
                    $folders[$entry] = array(
                        'path' => $path,
                        'foldername' => $entry,
                        'newpath' => $path . "/" . $entry,
                        'writable' => is_writable($fullfilename),
                        'modified' => date("Y/m/d H:i:s", filemtime($fullfilename))
                    );
                }

            }

            $d->close();

        } else {
            $app['session']->getFlashBag()->set('error', __("Folder '%s' could not be found, or is not readable.", array('%s' => $path)));
        }

        $app['twig']->addGlobal('title', __("Files in %s", array('%s' => $path)));

        // Make sure the files and folders are sorted properly.
        ksort($files);
        ksort($folders);

        // Select the correct template to render this. If we've got 'CKEditor' in the title, it's a dialog
        // from CKeditor to insert a file..
        if (!$request->query->has('CKEditor')) {
            $twig = 'files.twig';
        } else {
            $twig = 'files_ck.twig';
        }

        return $app['twig']->render($twig, array(
            'path' => $path,
            'files' => $files,
            'folders' => $folders,
            'pathsegments' => $pathsegments,
            'form' => $formview
        ));

    }

    public function fileedit($file, Silex\Application $app, Request $request)
    {

        if (dirname($file) == "app/config") {
            // Special case: If requesting one of the major config files, like contenttypes.yml, set the path to the
            // correct BOLT_CONFIG_DIR, which might be 'app/config', but it might be something else.
            $filename = realpath(BOLT_CONFIG_DIR . "/" . basename($file));
        } else {
            // otherwise just realpath it, relative to the 'webroot'.
            $filename = realpath(__DIR__ . "/../../../../" . $file);
        }

        $type = getExtension($filename);

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
            $error = __("The file '%s' doesn't exist, or is not readable.", array('%s' => $file));
            $app->abort(404, $error);
        }

        if (!is_writable($filename)) {
            $app['session']->getFlashBag()->set('info', __(
                "The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                array('%s' => $file)
            ));
            $writeallowed = false;
            $title = sprintf("<strong>%s</strong> » %s", __('View file'), basename($file));
        } else {
            $writeallowed = true;
            $title = sprintf("<strong>%s</strong> » %s", __('Edit file'), basename($file));
        }

        $data['contents'] = file_get_contents($filename);

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add('contents', 'textarea', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 10)))
            ));

        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->getMethod() == "POST") {
            $form->bind($app['request']->get($form->getName()));

            if ($form->isValid()) {

                $data = $form->getData();
                $contents = cleanPostedData($data['contents']) . "\n";

                $ok = true;

                // Before trying to save a yaml file, check if it's valid.
                if ($type == "yml") {
                    $yamlparser = new \Symfony\Component\Yaml\Parser();
                    try {
                        $ok = $yamlparser->parse($contents);
                    } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                        $ok = false;
                        $app['session']->getFlashBag()->set('error', __("File '%s' could not be saved: ", array('%s' => $file)) . $e->getMessage());
                    }

                }

                if ($ok) {
                    if (file_put_contents($filename, $contents)) {
                        $app['session']->getFlashBag()->set('info', __("File '%s' has been saved.", array('%s' => $file)));
                        // If we've saved a translation, back to it
                        if (preg_match('#resources/translations/(..)/(.*)\.yml$#', $filename, $m)) {
                            return redirect('translation', array('domain' => $m[2], 'tr_locale' => $m[1]));
                        }
                        redirect('fileedit', array('file' => $file), '');
                    } else {
                        $app['session']->getFlashBag()->set('error', __("File '%s' could not be saved, for some reason.", array('%s' => $file)));
                    }
                }

                // If we reach this point, the form will be shown again, with the error
                // in the input, so the user can try again.

            }
        }

        return $app['twig']->render('editconfig.twig', array(
            'form' => $form->createView(),
            'title' => $title,
            'filetype' => $type,
            'file' => $file,
            'pathsegments' => $pathsegments,
            'writeallowed' => $writeallowed
        ));

    }

    /**
     * Prepare/edit/save a translation
     */
    public function translation($domain, $tr_locale, Silex\Application $app, Request $request)
    {

        $short_locale = substr($tr_locale, 0, 2);
        $type = 'yml';
        $file = "app/resources/translations/$short_locale/$domain.$short_locale.$type";
        $filename = realpath(__DIR__ . "/../../../..") . "/$file";

        $app['log']->add("Editing translation: $file", $app['debug'] ? 1 : 3);

        if ($domain == 'infos') {
            // no gathering here : if the file doesn't exist yet, we load a
            // copy from the locale_fallback version (en)
            if (!file_exists($filename) || filesize($filename) < 10) {
                $srcfile = "app/resources/translations/$locale_fb/$domain.en.$type";
                $srcfilename = realpath(__DIR__ . "/../../../..") . "/$srcfile";
                $content = file_get_contents($srcfilename);
            } else {
                $content = file_get_contents($filename);
            }
        } else {
            $translated = array();
            if (is_file($filename) && is_readable($filename)) {
                try {
                    $translated = Yaml::parse($filename);
                } catch (ParseException $e) {
                    $app['session']->getFlashBag()->set('error', printf("Unable to parse the YAML translations: %s", $e->getMessage()));
                }
            }
            list($msg, $ctype) = gatherTranslatableStrings($tr_locale, $translated);
            $ts = date("Y/m/d H:i:s");
            $content = "# $file -- generated on $ts\n";
            if ($domain == 'messages') {
                $cnt = count($msg['not_translated']);
                if ($cnt) {
                    $content .= sprintf("# %d untranslated strings\n\n", $cnt);
                    foreach ($msg['not_translated'] as $key) {
                        $content .= "$key:  #\n";
                    }
                    $content .= "\n#-----------------------------------------\n";
                } else {
                    $content .= "# no untranslated strings; good ;-)\n\n";
                }
                $cnt = count($msg['translated']);
                $content .= sprintf("# %d translated strings\n\n", $cnt);
                foreach ($msg['translated'] as $key => $trans) {
                    $content .= "$key: $trans\n";
                }
            } else {
                $cnt = count($ctype['not_translated']);
                if ($cnt) {
                    $content .= sprintf("# %d untranslated strings\n\n", $cnt);
                    foreach ($ctype['not_translated'] as $key) {
                        $content .= "$key:  #\n";
                    }
                    $content .= "\n#-----------------------------------------\n";
                } else {
                    $content .= "# no untranslated strings: good ;-)\n\n";
                }
                $cnt = count($ctype['translated']);
                $content .= sprintf("# %d translated strings\n\n", $cnt);
                foreach ($ctype['translated'] as $key => $trans) {
                    $content .= "$key: $trans\n";
                }
            }
            //==========================
            //$file = "app/resources/translations/$short_locale/$domain.yml";
            //$filename = realpath(__DIR__."/../../../..")."/$file";
            //$type = 'yml';
        }
        // maybe no translations yet
        if (!file_exists($filename) && !is_writable(dirname($filename))) {
            $app['session']->getFlashBag()->set('info', __(
                "The translations file '%s' can't be created. You will have to use your own editor to make modifications to this file.",
                array('%s' => $file)
            ));
            $writeallowed = false;
            $title = __("View translations file '%s'.", array('%s' => $file));
        } elseif (file_exists($filename) && !is_readable($filename)) {
            $error = __("The translations file '%s' is not readable.", array('%s' => $file));
            $app->abort(404, $error);
        } elseif (!is_writable($filename)) {
            $app['session']->getFlashBag()->set('warning', __(
                "The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                array('%s' => $file)
            ));
            $writeallowed = false;
            $title = __("View file '%s'.", array('%s' => $file));
        } else {
            $writeallowed = true;
            $title = __("Edit translations file '%s'.", array('%s' => $file));
        }

        $data['contents'] = $content;
        $form = $app['form.factory']->createBuilder('form', $data)
            ->add('contents', 'textarea', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 10)))
            ));

        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the file.
        if ($request->getMethod() == "POST") {
            $form->bind($app['request']->get($form->getName()));

            if ($form->isValid()) {

                $data = $form->getData();
                $contents = cleanPostedData($data['contents']) . "\n";

                $ok = true;

                // Before trying to save a yaml file, check if it's valid.
                if ($type == "yml") {
                    //$yamlparser = new \Symfony\Component\Yaml\Parser();
                    try {
                        //$ok = $yamlparser->parse($contents);
                        $ok = Yaml::parse($contents);
                    } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                        $ok = false;
                        $app['session']->getFlashBag()->set('error', __("File '%s' could not be saved: ", array('%s' => $file)) . $e->getMessage());
                    }
                }

                if ($ok) {
                    if (file_put_contents($filename, $contents)) {
                        $app['session']->getFlashBag()->set('info', __("File '%s' has been saved.", array('%s' => $file)));

                        return redirect('translation', array('domain' => $domain, 'tr_locale' => $tr_locale));
                    } else {
                        $app['session']->getFlashBag()->set('error', __("File '%s' could not be saved, for some reason.", array('%s' => $file)));
                    }
                }

            }
        }

        return $app['twig']->render('editlocale.twig', array(
            'form' => $form->createView(),
            'title' => $title,
            'filetype' => $type,
            'writeallowed' => $writeallowed
        ));

    }

    /**
     * Middleware function to check whether a user is logged on.
     */
    public function before(Request $request, \Bolt\Application $app)
    {

        $route = $request->get('_route');

        $app['log']->setRoute($route);

        $app['debugbar'] = true;

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        if (!$app['users']->isValidSession() && !$app['users']->isAllowed($route)) {
            $app['session']->getFlashBag()->set('info', __("Please log on."));

            return redirect('login');
        } elseif (!$app['users']->isAllowed($route)) {
            $app['session']->getFlashBag()->set('error', __("You do not have the right privileges to view that page."));

            return redirect('dashboard');
        }

        // If the users table is present, but there are no users, and we're on /bolt/useredit,
        // we let the user stay, because they need to set up the first user.
        if ($app['storage']->getIntegrityChecker()->checkUserTableIntegrity() && !$app['users']->getUsers() && $route == 'useredit') {
            $app['twig']->addGlobal('frontend', false);

            return;
        }

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['storage']->getIntegrityChecker()->checkUserTableIntegrity() || !$app['users']->getUsers()) {
            $app['storage']->getIntegrityChecker()->repairTables();
            $app['session']->getFlashBag()->set('info', __("There are no users in the database. Please create the first user."));

            return redirect('useredit', array('id' => ""));
        }

    }

}
