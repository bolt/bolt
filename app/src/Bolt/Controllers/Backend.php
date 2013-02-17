<?php

Namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\CallbackValidator;
use Symfony\Component\Validator\Constraints as Assert;

class Backend implements ControllerProviderInterface
{
    public function connect(Silex\Application $app)
    {
        $ctl = $app['controllers_factory'];

        $ctl->get("", array($this, 'dashboard'))
            ->before(array($this, 'before'))
            ->bind('dashboard')
        ;

        $ctl->match("/login", array($this, 'login'))
            ->method('GET|POST')
            ->before(array($this, 'before'))
            ->bind('login')
        ;

        $ctl->get("/logout", array($this, 'logout'))
            ->bind('logout')
        ;

        $ctl->get("/dbupdate", array($this, 'dbupdate'))
            ->before(array($this, 'before'))
            ->bind('dbupdate')
        ;

        $ctl->get("/clearcache", array($this, 'clearcache'))
            ->before(array($this, 'before'))
            ->bind('clearcache')
        ;

        $ctl->get("/prefill", array($this, 'prefill'))
            ->before(array($this, 'before'))
            ->bind('prefill')
        ;

        $ctl->get("/overview/{contenttypeslug}", array($this, 'overview'))
            ->before(array($this, 'before'))
            ->bind('overview')
        ;

        $ctl->match("/editcontent/{contenttypeslug}/{id}", array($this, 'editcontent'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET|POST')
            ->bind('editcontent')
        ;

        $ctl->get("/content/{action}/{contenttypeslug}/{id}", array($this, 'contentaction'))
            ->before(array($this, 'before'))
            ->bind('contentaction')
        ;

        $ctl->get("/users", array($this, 'users'))
            ->before(array($this, 'before'))
            ->bind('users')
        ;

        $ctl->match("/users/edit/{id}", array($this, 'useredit'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET|POST')
            ->bind('useredit')
        ;

        $ctl->get("/about", array($this, 'about'))
            ->before(array($this, 'before'))
            ->bind('about')
        ;

        $ctl->get("/extensions", array($this, 'extensions'))
            ->before(array($this, 'before'))
            ->bind('extensions')
        ;

        $ctl->get("/user/{action}/{id}", array($this, 'useraction'))
            ->before(array($this, 'before'))
            ->bind('useraction')
        ;

        $ctl->get("/files/{path}", array($this, 'files'))
            ->before(array($this, 'before'))
            ->assert('path', '.+')
            ->bind('files')
        ;

        $ctl->get("/activitylog", array($this, 'activitylog'))
            ->before(array($this, 'before'))
            ->bind('activitylog')
        ;

        $ctl->match("/file/edit/{file}", array($this, 'fileedit'))
            ->before(array($this, 'before'))
            ->assert('file', '.+')
            ->method('GET|POST')
            ->bind('fileedit')
        ;

        return $ctl;
    }

    /**
     * Dashboard or "root".
     */
    function dashboard(Silex\Application $app) {

        // Re-do getConfig. Mainly so we can log errors.
        getConfig();

        // Check DB-tables integrity
        if (!$app['storage']->checkTablesIntegrity()) {
            $app['session']->setFlash('error', "The database needs to be updated / repaired. Go to 'Settings' > 'Check Database' to do this now.");
        }

        $limit = $app['config']['general']['recordsperdashboardwidget'];

        $total = 0;
        // get the 'latest' from each of the content types.
        foreach ($app['config']['contenttypes'] as $key => $contenttype) {
            if ($app['users']->isAllowed('contenttype:'.$key) && $contenttype['show_on_dashboard']==true) {
                $latest[$key] = $app['storage']->getContent($key, array('limit' => $limit, 'order' => 'datechanged DESC'));
                $total += count($latest[$key]);
            }
        }


        // If there's nothing in the DB, suggest to create some dummy content.
        if ($total == 0) {
            $suggestloripsum = true;
        } else {
            $suggestloripsum = false;
        }

        $app['twig']->addGlobal('title', "Dashboard");

        return $app['twig']->render('dashboard.twig', array('latest' => $latest, 'suggestloripsum' => $suggestloripsum));

    }



    /**
     * Login page.
     */
    function login(Silex\Application $app, Request $request) {

        if ($request->getMethod() == "POST") {

            $result = $app['users']->login($request->get('username'), $request->get('password'));

            if ($result) {
                $app['log']->add("Login " . $request->get('username') , 3, '', 'login');
                return redirect('dashboard');
            }

        }

        $app['twig']->addGlobal('title', "Login");

        return $app['twig']->render('login.twig');

    }

    /**
     * Logout page.
     */
    function logout(Silex\Application $app) {

        $app['log']->add("Logout", 3, '', 'logout');

        $app['users']->logout();

        return redirect('login');

    }

    /**
     * Check the database, create tables, add missing/new columns to tables
     */
    function dbupdate(Silex\Application $app) {

        $output = $app['storage']->repairTables();

        if (empty($output)) {
            $content = "<p>Your database is already up to date.<p>";
        } else {
            $content = "<p>Modifications made to the database:<p>";
            $content .= implode("<br>", $output);
            $content .= "<p>Your database is now up to date.<p>";
        }

        $content .= "<br><br><p><b>Tip: </b>Add some sample <a href='".path('prefill')."'>Records with Loripsum text</a>.</p>";

        // If 'return=edit' is passed, we should return to the edit screen. We do redirect twice, yes,
        // but that's because the newly saved contenttype.yml needs to be re-read.
        $return = $app['request']->query->get('return');
        if ($return=="edit") {
            if (empty($output)) {
                $content = "Your database is already up to date.";
            } else {
                $content = "Your database is now up to date.";
            }
            $app['session']->setFlash('success', $content);

            return redirect('fileedit', array('file' => "app/config/contenttypes.yml"));
        }

        $app['twig']->addGlobal('title', "Database check / update");

        return $app['twig']->render('base.twig', array(
            'content' => $content,
            'active' => "settings"
        ));

    }


    /**
     * Clear the cache.
     */
    function clearcache(Silex\Application $app) {

        $result = $app['cache']->clearCache();

        $output = sprintf("Deleted %s files from cache.", $result['successfiles']);

        if (!empty($result['failedfiles'])) {
            $output .= sprintf(" %s files could not be deleted. You should delete them manually.", $result['failedfiles']);
            $app['session']->setFlash('error', $output);
        } else {
            $app['session']->setFlash('success', $output);
        }

        return redirect('dashboard');

    }


    /**
     * Show the activity-log.
     */
    function activitylog(Silex\Application $app) {

        $title = "Activity log";

        $action = $app['request']->query->get('action');

        if ($action=="clear") {
            $app['log']->clear();
            $app['session']->setFlash('success', "The activitylog has been cleared.");
        } else if ($action=="trim") {
            $app['log']->trim();
            $app['session']->setFlash('success', "The activitylog has been trimmed.");
        }

        $activity = $app['log']->getActivity(16);

        return $app['twig']->render('activity.twig', array('title' => $title, 'activity' => $activity));

    }



    /**
     * Generate some lipsum in the DB.
     */
    function prefill(Silex\Application $app) {

        $content = $app['storage']->preFill();

        $content .= "<br><br><p>Go <a href='". path('dashboard') ."'>back to the Dashboard</a>.<br>";
        $content .= "Or <a href='". path('prefill') ."'>add some more records</a>.</p>";

        $app['twig']->addGlobal('title', "Fill the database with Dummy Content");

        return $app['twig']->render('base.twig', array('content' => $content));

    }


    /**
     * Check the database, create tables, add missing/new columns to tables
     */
    function overview(Silex\Application $app, $contenttypeslug) {

        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$app['users']->isAllowed('contenttype:'.$contenttypeslug)) {
            $app['session']->setFlash('error', "You do not have the right privileges to view that page.");
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
            $limit = $app['config']['general']['recordsperpage'];
        }


        $multiplecontent = $app['storage']->getContent($contenttype['slug'],
            array('limit' => $limit, 'order' => $order, 'page' => $page, 'filter' => $filter), $pager);

        // @todo Do we need pager here?
        $app['pager'] = $pager;

        $app['twig']->addGlobal('title', "Overview » ". $contenttype['name']);

        return $app['twig']->render('overview.twig',
            array('contenttype' => $contenttype, 'multiplecontent' => $multiplecontent)
        );

    }


    /**
     * Edit a unit of content, or create a new one.
     */
    function editcontent($contenttypeslug, $id, Silex\Application $app, Request $request) {

        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$app['users']->isAllowed('contenttype:'.$contenttypeslug)) {
            $app['session']->setFlash('error', "You do not have the right privileges to edit that record.");
            return redirect('dashboard');
        }

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        if ($request->getMethod() == "POST") {

            $content = new \Bolt\Content($app, $contenttypeslug);
            $content->setFromPost($request->request->all(), $contenttype);

            // Don't try to spoof the $id..
            if (!empty($content['id']) && $id != $content['id']) {
				echo "$id is niet ". $content['id'];
				die();
                $app['session']->setFlash('error', "Don't try to spoof the id!");
                return redirect('dashboard');
            }

            if ($app['storage']->saveContent($content, $contenttype['slug'])) {

                if (!empty($id)) {
                    $app['session']->setFlash('success', "The changes to this " . $contenttype['singular_name'] . " have been saved.");
                } else {
                    $app['session']->setFlash('success', "The new " . $contenttype['singular_name'] . " has been saved.");
                }
                $app['log']->add($content->getTitle(), 3, $content, 'save content');

                return redirect('overview', array('contenttypeslug' => $contenttype['slug']));

            } else {
                $app['session']->setFlash('error', "There was an error saving this " . $contenttype['singular_name'] . ".");
                $app['log']->add("Save content error", 3, $content, 'error');
            }

        }

        if (!empty($id)) {
            $content = $app['storage']->getContent($contenttype['slug'], array('id' => $id));

            // Check if we're allowed to edit this content..
            if ( ($content['username'] != $app['users']->getCurrentUsername()) && !$app['users']->isAllowed('editcontent:all') ) {
                $app['session']->setFlash('error', "You do not have the right privileges to edit that record.");
                return redirect('dashboard');
            }

            $app['twig']->addGlobal('title', "Edit " . $contenttype['singular_name'] . " » ". $content->getTitle());
            $app['log']->add("Edit content", 1, $content, 'edit');
        } else {
            $content = $app['storage']->getEmptyContent($contenttype['slug']);
            $app['twig']->addGlobal('title', "New " . $contenttype['singular_name']);
            $app['log']->add("New content", 1, $content, 'edit');

        }

        $duplicate = $app['request']->query->get('duplicate');
        if (!empty($duplicate)) {
            $content->setValue('id', "");
            $content->setValue('datecreated', "");
            $content->setValue('datepublish', "");
            $content->setValue('datechanged', "");
            $content->setValue('username', "");
            $app['session']->setFlash('info', "Content was duplicated. Click 'Save " . $contenttype['singular_name'] . "' to finalize.");
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
    function contentaction(Silex\Application $app, $action, $contenttypeslug, $id, Request $request) {

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $content = $app['storage']->getContent($contenttype['slug']."/".$id);
        $title = $content->getTitle();

        // Check if we're allowed to edit this content..
        if ( ($content['username'] != $app['users']->getCurrentUsername()) && !$app['users']->isAllowed('editcontent:all') ) {
            $app['session']->setFlash('error', "You do not have the right privileges to edit that record.");
            return redirect('dashboard');
        }

        switch ($action) {

            case "held":
                if ($app['storage']->changeContent($contenttype['slug'], $id, 'status', 'held')) {
                    $app['session']->setFlash('info', "Content '$title' has been changed to 'held'");
                } else {
                    $app['session']->setFlash('info', "Content '$title' could not be modified.");
                }
                break;

            case "publish":
                if ($app['storage']->changeContent($contenttype['slug'], $id, 'status', 'published')) {
                    $app['session']->setFlash('info', "Content '$title' is published.");
                } else {
                    $app['session']->setFlash('info', "Content '$title' could not be modified.");
                }
                break;

            case "draft":
                if ($app['storage']->changeContent($contenttype['slug'], $id, 'status', 'draft')) {
                    $app['session']->setFlash('info', "Content '$title' has been changed to 'draft'.");
                } else {
                    $app['session']->setFlash('info', "Content '$title' could not be modified.");
                }
                break;

            case "delete":

                if (checkToken() && $app['storage']->deleteContent($contenttype['slug'], $id)) {
                    $app['session']->setFlash('info', "Content '$title' has been deleted.");
                } else {
                    $app['session']->setFlash('info', "Content '$title' could not be deleted.");
                }
                break;

            default:
                $app['session']->setFlash('error', "No such action for content.");

        }

        return redirect('overview', array('contenttypeslug' => $contenttype['slug']));

    }


    /**
     * Show a list of all available users.
     */
    function users(Silex\Application $app) {

        $title = "Users";
        $users = $app['users']->getUsers();
        $userlevels = $app['users']->getUserLevels();

        return $app['twig']->render('users.twig', array('users' => $users, 'title' => $title, 'userlevels' => $userlevels ));

    }

    function useredit($id, Silex\Application $app, Request $request) {

        // Get the user we want to edit (if any)
        if (!empty($id)) {
            $user = $app['users']->getUser($id);
            $title = "Edit a user";
        } else {
            $user = $app['users']->getEmptyUser();
            $title = "Create a new user";
        }

        $userlevels = $app['users']->getUserLevels();
        $enabledoptions = array(1 => 'yes', 0 => 'no');
        $contenttypes = makeValuepairs($app['config']['contenttypes'], 'slug', 'name');

        // If we're creating the first user, we should make sure that we can only create
        // a user that's allowed to log on.
        if (!$app['users']->getUsers()) {
            $firstuser = true;
            $title = "Create the first user";
            // If we get here, chances are we don't have the tables set up, yet.
            $app['storage']->repairTables();
        } else {
            $firstuser = false;
        }

        // Start building the form..
        $form = $app['form.factory']->createBuilder('form', $user)
            ->add('id', 'hidden')
            ->add('username', 'text', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(array('limit' => 2)))
        ))
            ->add('password', 'password', array(
                'required' => false
        ))
            ->add('password_confirmation', 'password', array(
            'required' => false,
            'label' => "Password (confirmation)"
        ))
            ->add('email', 'text', array(
            'constraints' => new Assert\Email(),
        ))
            ->add('displayname', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(array('limit' => 2)))
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
                'constraints' => new Assert\Choice(array_keys($userlevels))
            ))
                ->add('enabled', 'choice', array(
                'choices' => $enabledoptions,
                'expanded' => false,
                'constraints' => new Assert\Choice(array_keys($enabledoptions)),
                'label' => "User is enabled",
            ))
                ->add('contenttypes', 'choice', array(
                'choices' => $contenttypes,
                'expanded' => true,
                'multiple' => true,
                'label' => "Allowed contenttypes",
            ));
        }

        // If we're adding a new user, these fields will be hidden.
        if (!empty($id)) {
            $form->add('lastseen', 'text', array('disabled' => true))
                ->add('lastip', 'text', array('disabled' => true));
        }

        // @todo CallbackValidator is deprecated. see: symfony/form/Symfony/Component/Form/CallbackValidator.php

        // Make sure the passwords are identical with a custom validator..
        $form->addValidator(new CallbackValidator(function ($form) {

            $id = $form['id']->getData();
            $pass1 = $form['password']->getData();
            $pass2 = $form['password_confirmation']->getData();

            // If adding a new user (empty $id) or if the password is not empty (indicating we want to change it),
            // then make sure it's at least 6 characters long.
            if ( (empty($id) || !empty($pass1) ) && strlen($pass1) < 6) {
                // screw it. Let's just not translate this message for now. Damn you, stupid non-cooperative translation thingy.
                //$error = new FormError("This value is too short. It should have {{ limit }} characters or more.", array('{{ limit }}' => 6), 2);
                $error = new FormError("This value is too short. It should have 6 characters or more.");
                $form['password']->addError($error);
            }

            // Passwords must be identical..
            if ($pass1 != $pass2) {
                $form['password_confirmation']->addError(new FormError('Passwords must match.'));
            }

        }));

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

                $res = $app['users']->saveUser( $user );
                $app['log']->add("Added user '". $user['displayname']."'.", 3, '', 'user');
                if ($res) {
                    $app['session']->setFlash('success', "User " . $user['displayname'] . " has been saved.");
                } else {
                    $app['session']->setFlash('error', "User " . $user['displayname'] . " could not be saved, or nothing was changed.");
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
    function useraction(Silex\Application $app, $action, $id) {

        $user = $app['users']->getUser($id);

        if (!$user) {
            $app['session']->setFlash('error', "No such user.");

            return redirect('users');
        }

        switch ($action) {

            case "disable":
                if ($app['users']->setEnabled($id, 0)) {
                    $app['log']->add("Disabled user '". $user['displayname']."'.", 3, '', 'user');

                    $app['session']->setFlash('info', "User '{$user['displayname']}' is disabled.");
                } else {
                    $app['session']->setFlash('info', "User '{$user['displayname']}' could not be disabled.");
                }
                break;

            case "enable":
                if ($app['users']->setEnabled($id, 1)) {
                    $app['log']->add("Enabled user '". $user['displayname']."'.", 3, '', 'user');
                    $app['session']->setFlash('info', "User '{$user['displayname']}' is enabled.");
                } else {
                    $app['session']->setFlash('info', "User '{$user['displayname']}' could not be enabled.");
                }
                break;

            case "delete":

                if (checkToken() && $app['users']->deleteUser($id)) {
                    $app['log']->add("Deleted user '". $user['displayname']."'.", 3, '', 'user');
                    $app['session']->setFlash('info', "User '{$user['displayname']}' is deleted.");
                } else {
                    $app['session']->setFlash('info', "User '{$user['displayname']}' could not be deleted.");
                }
                break;

            default:
                $app['session']->setFlash('error', "No such action for user '{$user['displayname']}'.");

        }

        return redirect('users');

    }


    /**
     * Show the 'about' page
     */
    function about(Silex\Application $app) {
        return $app['twig']->render('about.twig');

    }


    /**
     * Show a list of all available extensions.
     */
    function extensions(Silex\Application $app) {

        $title = "Extensions";

        $extensions = $app['extensions']->getInfo();

        return $app['twig']->render('extensions.twig', array('extensions' => $extensions, 'title' => $title));

    }


    function files($path, Silex\Application $app, Request $request) {

        $files = array();
        $folders = array();

        $basefolder = __DIR__."/../../../../";
        $path = stripTrailingSlash(str_replace("..", "", $path));
        $currentfolder = realpath($basefolder.$path);

        $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");

        // Get the pathsegments, so we can show the path..
        $pathsegments = array();
        $cumulative = "";
        if (!empty($path)) {
            foreach (explode("/", $path) as $segment) {
                $cumulative .= $segment . "/";
                $pathsegments[ $cumulative ] = $segment;
            }
        }

        if (file_exists($currentfolder)) {

            $d = dir($currentfolder);

            while (false !== ($entry = $d->read())) {

                if (in_array($entry, $ignored)) { continue; }

                $fullfilename = $currentfolder."/".$entry;

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
            $app['session']->setFlash('error', "File '" .$file."' could not be saved: not valid YAML.");
        }

        $app['twig']->addGlobal('title', "Files in ". $path);

        // Make sure the files and folders are sorted properly.
        ksort($files);
        ksort($folders);

        return $app['twig']->render('files.twig', array(
            'path' => $path,
            'files' => $files,
            'folders' => $folders,
            'pathsegments' => $pathsegments
        ));

    }


    function fileedit($file, Silex\Application $app, Request $request) {

        $title = "Edit file '$file'.";

        $filename = realpath(__DIR__."/../../../../".$file);
        $type = getExtension($filename);

        if (!file_exists($filename) || !is_readable($filename)) {
            $error = sprintf("file '%s/config/%s' doesn't exist, or is not readable." , basename(__DIR__), $file);
            $app->abort(404, $error);
        }

        if (!is_writable($filename)) {
            $app['session']->setFlash('error', sprintf("The file '%s/config/%s' is not writable. You will not be able to save your changes, until you fix this." , basename(__DIR__), $file));
            $writeallowed = false;
        } else {
            $writeallowed = true;
        }

        $data['contents'] = file_get_contents($filename);

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add('contents', 'textarea', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(10))
        ));

        $form = $form->getForm();

        // Check if the form was POST-ed, and valid. If so, store the user.
        if ($request->getMethod() == "POST") {
            //$form->bindRequest($request);
            $form->bind($app['request']->get($form->getName()));

            if ($form->isValid()) {

                $data = $form->getData();
                $contents = cleanPostedData($data['contents']) ."\n";

                $ok = true;

                // Before trying to save a yaml file, check if it's valid.
                if ($type == "yml") {
                    $yamlparser = new \Symfony\Component\Yaml\Parser();
                    try {
                        $ok = $yamlparser->parse($contents);
                    } catch (Exception $e) {
                        $ok = false;
                        $app['session']->setFlash('error', "File '" .$file."' could not be saved: not valid YAML.");
                    }
                }

                if ($ok) {
                    if (file_put_contents($filename, $contents)) {
                        $app['session']->setFlash('info', "File '" .$file."' has been saved.");
                        // If we've saved contenttypes.yml, update the database..
                        if (basename($file) == "contenttypes.yml") {
                            return redirect('dbupdate', '', "?return=edit");
                        }
                    } else {
                        $app['session']->setFlash('error', "File '" .$file."' could not be saved, for some reason.");
                    }
                }

                return redirect('fileedit', array('file' => $file));

            }
        }

        return $app['twig']->render('editconfig.twig', array(
            'form' => $form->createView(),
            'title' => $title,
            'filetype' => $type,
            'writeallowed' => $writeallowed
        ));

    }

    /**
     * Middleware function to check whether a user is logged on.
     */
    function before(Request $request, Silex\Application $app)
    {

        $route = $request->get('_route');

        $app['log']->setRoute($route);

        $app['debugbar'] = true;

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        if (!$app['users']->checkValidSession() && !$app['users']->isAllowed($route) ) {
            $app['session']->setFlash('info', "Please log on.");
            return redirect('login');
        } else if (!$app['users']->isAllowed($route)) {
            $app['session']->setFlash('error', "You do not have the right privileges to view that page.");
            return redirect('dashboard');
        }

        // If the users table is present, but there are no users, and we're on /bolt/useredit,
        // we let the user stay, because they need to set up the first user.
        if ($app['storage']->checkUserTableIntegrity() && !$app['users']->getUsers() && $request->getPathInfo()=="/bolt/users/edit/") {
            $app['twig']->addGlobal('frontend', false);
            return;
        }

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['storage']->checkUserTableIntegrity() || !$app['users']->getUsers()) {
            $app['storage']->repairTables();
            $app['session']->setFlash('info', "There are no users in the database. Please create the first user.");
            return redirect('useredit', array('id' => ""));
        }

    }

}
