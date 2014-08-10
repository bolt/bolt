<?php

namespace Authenticate;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Global controller
 *
 * This handles the front end for the extension:
 *  - logging in,
 *  - initializing sessions
 *  - output for the twig extension class
 *  - pages for loggin in, loggin out
 *  - the endpoint for hybridauth
 */
class Controller
{
    public function __construct(Silex\Application $app, $config)
    {
        $this->config = $config;
        $this->config["base_url"] =
            $app['paths']['hosturl'] . '/' .
            $this->config['basepath'] . '/endpoint';
        $this->current_visitor = null;
        $this->app = $app;
    }

    /**
     * Check who the visitor is
     */
    public function checkvisitor(Silex\Application $app = null)
    {

        // In case we're calling statically, we need to have $app
        if (!$app) {
            $app = $this->app;
        }

        $session = new Session($app);
        $token = $app['session']->get('visitortoken');
        $current = $session->load($token);
        $visitor = new Visitor($app);
        $this->current_visitor = $visitor->load_by_id($current['visitor_id']);
        if ($this->current_visitor) {
            // Set the Apptoken
            $this->current_visitor['apptoken'] = $visitor->check_app_token();

            // Guess the 'avatar' image from the present data.
            $profile = $this->current_visitor['providerdata'];

            if (!empty($profile->photoURL)) {
                $this->current_visitor['avatar'] = $profile->photoURL;
            }

            // Add frontend role if set up
            if (isset($this->config['role'])) {
                $this->setvisitorrole();
            }
        }

        return $this->current_visitor;
    }

    /**
     * Set configured frontend role.  Should match one from permissions.yml
     */
    private function setvisitorrole() {
        // Safe-guard against the 'root' role being applied
        if ($this->config['role'] == 'root') {
            return;
        }

        if (empty($this->app['users']->currentuser)) {
            $this->app['users']->currentuser = array('roles' => array(
                $this->config['role'],
                'everyone'));
        } else {
            array_push($this->app['users']->currentuser['roles'], $this->config['role']);
        }
    }

    private function load_hybrid_auth()
    {
        $stem = dirname(__DIR__) . '/lib/Hybrid';
        require_once("$stem/Auth.php" );
        require_once("$stem/Endpoint.php" );
        require_once("$stem/User_Profile.php");
    }

    /**
     * Login visitor page
     *
     * Prepare the visitor login from hybridauth
     */
    public function login(Silex\Application $app, Request $request)
    {
        $title = "login page";

        $recognizedvisitor = $this->checkvisitor($app);
        if($recognizedvisitor) {
            // already logged in - show the account
            $returnpage = $app['request']->headers->get('referer');
            $returnpage = str_replace($app['paths']['hosturl'], '', $returnpage);
            if($returnpage) {
                simpleredirect($returnpage);
                exit;
            } else {
                return redirect('homepage');
            }
        }

        $provider = isset($_GET['provider']) ? $_GET['provider'] : false;

        if($provider) {
            $this->load_hybrid_auth();

            try {
                // initialize Hybrid_Auth with a given file

                // get the type early - because we might need to enable it
                if (isset($this->config['providers'][$provider]['type'])) {
                    $providertype = $this->config['providers'][$provider]['type'];
                } else {
                    $providertype = $provider;
                }

                // enable OpenID
                if($providertype == 'OpenID' && $this->config['providers'][$provider]['enabled'] == true) {
                    $this->config['providers']['OpenID']['enabled'] = true;
                }

                // initialize the authentication with the modified config
                $hybridauth = new \Hybrid_Auth($this->config);
                $provideroptions = array();
                if($providertype=='OpenID' && !empty($this->config['providers'][$provider]['openid_identifier'])) {
                    // try to authenticate with the selected OpenID provider
                    $providerurl = $this->config['providers'][$provider]['openid_identifier'];
                    $provideroptions["openid_identifier"] = $providerurl;
                }
                // try to authenticate with the selected provider
                $adapter = $hybridauth->authenticate($providertype, $provideroptions);

                // then grab the user profile
                $user_profile = $adapter->getUserProfile();

                if($user_profile) {
                    $visitor = new Visitor($app);
                    $visitor->setProvider( $provider );
                    $visitor->setProfile( $user_profile );

                    // check if user profile is known internally - and load it
                    $known_visitor = $visitor->checkExisting();

                    // create a new user profile if it does not exist yet - and load it
                    if(!$known_visitor) {
                        $new_visitor = $visitor->save();

                        // check if user profile is known internally
                        // and load it again with the new data
                        $known_visitor = $visitor->checkExisting();
                    }

                    $session = new Session($app);
                    $token = $session->login($known_visitor['id']);
                    $returnpage = $app['request']->headers->get('referer');
                    $returnpage = str_replace($app['paths']['hosturl'], '', $returnpage);

                    if($returnpage) {
                        simpleredirect($returnpage);
                        exit;
                    } else {
                       return redirect('homepage');
                    }
                }

            } catch(Exception $e) {
                echo "Error: please try again!";
                echo "Original error message: " . $e->getMessage();
            }
        } else {
            $markup = $this->showvisitorlogin();
            //$markup = new \Twig_Markup($markup, 'UTF-8');
        }

        return $this->page($title, $markup);
    }

    /**
     * Returns a list of links to all enabled login options
     */
    public function showvisitorlogin(Silex\Application $app = null)
    {

        $buttons = array();

        foreach($this->config['providers'] as $provider => $values) {
            if($values['enabled']==true) {
                $label = !empty($values['label'])?$values['label']:$provider;
                $buttons[] = $this->formatButton(
                    $this->app['paths']['root'] . $this->config['basepath']. '/login?provider='. $provider,
                    $label);
            }
        }

        $markup = join("\n", $buttons);

        return new \Twig_Markup($markup, 'UTF-8');
    }

    /**
     * Link to the logout page
     */
    public function showvisitorlogout($label = "Logout")
    {
        $logoutlink = $this->formatButton(
            $this->app['paths']['root'] . $this->config['basepath'].'/logout',
            $label);

        return new \Twig_Markup($logoutlink, 'UTF-8');
    }

    /**
     * The rendered profile
     */
    public function showvisitorprofile(Silex\Application $app = null)
    {
        if (!$app) {
            $app = $this->app;
        }

        $recognizedvisitor = $this->checkvisitor($app);
        if($recognizedvisitor) {
            $visitor_profile = $recognizedvisitor['providerdata'];

            $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__)."/assets");
            $template = $this->config['template']['profile'];
            $context = array(
                           'profile' => $visitor_profile,
                           'visitor' => $recognizedvisitor
                       );

            $markup = $this->app['render']->render($template, $context);

            return new \Twig_Markup($markup, 'UTF-8');
        }

        return false;
    }

    /**
     * [settingslist description]
     * @param  [type] $app [description]
     * @return [type]      [description]
     */
    public function settingslist(Silex\Application $app = null)
    {
        return $this->showvisitorprofile($app);
    }

    /**
     * Hybrid auth endpoint
     *
     * This endpoint passes all login requests to hybridauth
     */
    public function endpoint()
    {
        $this->load_hybrid_auth();
        \Hybrid_Endpoint::process();
    }

    /**
     * Logout visitor page
     *
     * Remove / Reset a visitor session
     */
    public function logout(Silex\Application $app = null)
    {
        if (!$app) {
            $app = $this->app;
        }

        $returnpage = $app['request']->headers->get('referer');
        $returnpage = str_replace($app['paths']['hosturl'], '', $returnpage);

        $token = $app['session']->get('visitortoken');
        $session = new Session($app);
        $session->clear($token);

        if($returnpage) {
            simpleredirect($returnpage);
            exit;
        } else {
           return redirect('homepage');
        }
    }

    /**
     * View visitor page
     *
     * View the current visitor
     */
    public function view(Silex\Application $app, Request $request)
    {
        $markup = '';

        // login the visitor
        $recognizedvisitor = $this->checkvisitor($app);

        if ($recognizedvisitor) {
            $title = $recognizedvisitor['username'];
            $markup = $this->showvisitorprofile();
        } else {
            // go directly to login page
            $path = '/'.$this->config['basepath'].'/login';
            // TODO: This has some problems when the path is not initialized right
            //return redirect($path);

            // fallback by usting some old html
            $markup = '<p>You need to log in first.</p>';
            $markup .= '<a href="'.htmlspecialchars($path, ENT_QUOTES).'">Login here</a>';

            $markup = new \Twig_Markup($markup, 'UTF-8');
        }

        return $this->page($title, $markup);
    }


    /**
     * Output the results in the default template
     */
    private function page($title, $markup)
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__)."/assets");
        $template = 'base.twig';
        $context = array('title' => $title, 'markup' => $markup);

        return $this->app['render']->render($template, $context);
    }

    private function json(Silex\Application $app, Request $request, $responseData, $code = 200)
    {
        $response = $app->json($responseData, $code);
        if ($callback = $request->get('callback')) {
            $response->setCallback($callback);
        }

        return $response;
    }

    public function tokenlogin(\Silex\Application $app, Request $request)
    {
        $username = $request->get('username');
        $apptoken = $request->get('apptoken');
        $visitor = new Visitor($app);
        $known_visitor = $visitor->checkByAppToken($username, $apptoken);
        if (!$known_visitor) {
            return $this->json($app, $request, array('error' => 'Access denied'), 403);
        }
        $session = new Session($app);
        $token = $session->login($known_visitor['id']);

        return $this->json($app, $request, array('status' => 'OK'), 200);
    }

    /**
     * Simple function to format the HTML for a button.
     */
    private function formatButton($link, $label)
    {
        $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__)."/assets");
        $template = $this->config['template']['button'];
        $context = array(
                       'link' => $link,
                       'label' => $label,
                       'class' => strtolower(safeString($label))
                   );

        $markup = $this->app['render']->render($template, $context);

        return new \Twig_Markup($markup, 'UTF-8');
    }
}
