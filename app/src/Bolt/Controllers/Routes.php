<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Routes implements ControllerProviderInterface
{
    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];

        $yamlparser = new \Symfony\Component\Yaml\Parser();
        $routes = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/routes.yml.dist') . "\n");

        if (is_array($routes)) {
            $ctr = $this->addRoutes($app, $routes);
        }

        return $ctr;
    }

    private function addRoutes(Silex\Application $app, array $routes)
    {
        $ctr = $app['controllers_factory'];

        foreach($routes as $binding => $route) {
            $path         = false;
            $to           = false;
            $controller   = false;
            $_controller  = false;
            $_before      = false;
            $_after       = false;
            $requirements = array();
            if (isset($route['path'])) {
                $path = $route['path'];
            }
            if (isset($route['defaults'])) {
                $default = $route['defaults'];
                if (isset($default['_controller'])) {
                    $to = $default['_controller'];
                    if (strpos($to, '::') > 0) {
                        $_controller = explode('::', $default['_controller']);
                    }
                    /*
                    $_controller = explode('::', $default['_controller']);
                    if (class_exists($_controller[0])) {
                        $instance = new $_controller[0];
                        $to       = array($instance, $_controller[1]);
                    }
                    //*/
                }
                if (isset($default['_before'])) {
                    if ((substr($default['_before'] ,0, 2) == '::') && (is_array($_controller))) {
                        $_before = $_controller[0].$default['_before'];
                    }
                    else {
                        $_before = $default['_before'];
                    }
                }
                if (isset($default['_after'])) {
                    if ((substr($default['_after'] ,0, 2) == '::') && (is_array($_controller))) {
                        $_after = $_controller[0].$default['_after'];
                    }
                    else {
                        $_after = $default['_after'];
                    }
                }
            }
            if (isset($route['requirements']) && (is_array($route['requirements']))) {
                $requirements = $route['requirements'];
            }

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
