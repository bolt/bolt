<?php
namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Configurable routes controller
 *
 * Read and add routes based on a routes.yml file.
 */
class Routes implements ControllerProviderInterface
{
    /**
     * Connect this controller to the application
     */
    public function connect(Silex\Application $app)
    {
        $ctr = false;

        $routes = $app['config']['routes'];
        if (is_array($routes)) {
            $ctr = $this->addRoutes($app, $routes);
        }

        if ($ctr === false) {
            $ctr = $app['controllers_factory'];
        }

        return $ctr;
    }

    /**
     * Add routes based on the parsed array
     */
    private function addRoutes(Silex\Application $app, array $routes)
    {
        $ctr = $app['controllers_factory'];

        foreach($routes as $binding => $route) {
            $path         = false;
            $to           = false;
            $controller   = false;
            $host         = false;
            $_controller  = false;
            $_before      = false;
            $_after       = false;
            $defaults     = array();
            $requirements = array();


            // parse YAML structure

            if (isset($route['path'])) {
                $path = $route['path'];
            }
            if (isset($route['defaults'])) {
                $defaults = $route['defaults'];
                if (isset($defaults['_controller'])) {
                    $to = $defaults['_controller'];
                    if (strpos($to, '::') > 0) {
                        $_controller = explode('::', $defaults['_controller']);

                        if (class_exists($_controller[0])) {
                            $instance = new $_controller[0];

                            $to = array($instance, $_controller[1]);
                        }
                    }
                    unset($defaults['_controller']);
                }
                if (isset($defaults['_before'])) {
                    if ((substr($defaults['_before'] ,0, 2) == '::') && (is_array($to))) {
                        //$_before = $_controller[0].$defaults['_before'];
                        $_before = array($to[0], substr($defaults['_before'], 2));
                    }
                    else {
                        $_before = $defaults['_before'];
                    }
                    unset($defaults['_before']);
                }
                if (isset($defaults['_after'])) {
                    if ((substr($defaults['_after'] ,0, 2) == '::') && (is_array($to))) {
                        //$_after = $_controller[0].$defaults['_after'];
                        $_after = array($to[0], substr($defaults['_after'], 2));
                    }
                    else {
                        $_after = $defaults['_after'];
                    }
                    unset($defaults['_after']);
                }
            }
            if (isset($route['requirements']) && (is_array($route['requirements']))) {
                $requirements = $route['requirements'];
            }
            if (isset($route['host'])) {
                $host = $route['host'];
            }


            // add Route

            if (($path !== false) && ($to !== false)) {
                $controller = $ctr->match($path, $to);
            }
            if ($controller !== false) {
                if ($_before !== false) {
                    $controller->before($_before);
                }
                if ($_after !== false) {
                    $controller->after($_after);
                }
            
                foreach($requirements as $variable => $regexp) {
                    $controller->assert($variable, $regexp);
                }
                foreach($defaults as $variable => $default) {
                    $controller->value($variable, $default);
                }

                $controller->bind($binding);
            }
        }

        return $ctr;
    }

    function before(Request $request, \Bolt\Application $app)
    {

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['storage']->getIntegrityChecker()->checkUserTableIntegrity() || !$app['users']->getUsers()) {
            $app['session']->getFlashBag()->set('info', __("There are no users in the database. Please create the first user."));
            return redirect('useredit', array('id' => ""));
        }

        $app['debugbar']     = true;
        $app['htmlsnippets'] = true;

        // If we are in maintenance mode and current user is not logged in, show maintenance notice.
        if ($app['config']['general']['maintenance_mode']) {

            $user = $app['users']->getCurrentUser();
            $template = $app['config']['general']['maintenance_template'];
            $body = $app['twig']->render($template);

            if($user['userlevel'] < 2) {
                return new Response($body, 503);
            }
        }
    }
}
