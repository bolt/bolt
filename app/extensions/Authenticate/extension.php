<?php

namespace Authenticate;

require_once dirname(__FILE__) . '/src/Controller.php';
require_once dirname(__FILE__) . '/src/Session.php';
require_once dirname(__FILE__) . '/src/Visitor.php';
require_once dirname(__FILE__) . '/src/VisitorsTwigExtension.php';
require_once dirname(__FILE__) . '/lib/Hybrid/User_Profile.php';

class extension extends \Bolt\BaseExtension
{
    public function info()
    {
        $data = array(
            'name' => "Authenticate",
            'description' => "An extension to authenticate visitors on your Boltsite",
            'author' => "TwoKings (Lodewijk Evers, Tobias Dammers, Bob den Otter)",
            'version' => "1.0.1",
            'required_bolt_version' => "1.5.0",
            'highest_bolt_version' => "1.5.0",
            'type' => "General",
            'first_releasedate' => "2014-02-11",
            'latest_releasedate' => "2014-02-21",
        );

        return $data;
    }

    /**
     * Initialize Authenticate. Called during bootstrap phase.
     *
     * Checks if a visitor is known, and loads the associated visitor
     * Also handles the routing for login, logout and view
     */
    public function initialize()
    {
        if (empty($this->config['basepath'])) {
            $this->config['basepath'] = "visitors";
        }
        $basepath = $this->config['basepath'];

        # apparently "A set of identifiers that identify a setting in the listing". Ok, whatever, HybridAuth.
        $this->config['identifier'] = "key";

        // If debug is set, also set the path for the debug log.
        if ($this->config['debug_mode']) {
            $this->config['debug_file'] = BOLT_CACHE_DIR . "/authenticate.log";
            @touch($this->config['debug_file']);
        }

        // Set up database schema
        $table_prefix = $this->app['config']->get('general/database/prefix', "bolt_");

        // CREATE TABLE 'bolt_visitors'
        $this->app['integritychecker']->registerExtensionTable(
            function ($schema) use ($table_prefix) {
                $table = $schema->createTable($table_prefix . "visitors");
                $table->addColumn("id", "integer", array('autoincrement' => true));
                $table->setPrimaryKey(array("id"));
                $table->addColumn("username", "string", array("length" => 64));
                $table->addColumn("provider", "string", array("length" => 64));
                $table->addColumn("providerdata", "text");
                $table->addColumn("apptoken", "string", array("length" => 64));

                return $table;
            }
        );

        // CREATE TABLE 'bolt_visitors_sessions'
        $this->app['integritychecker']->registerExtensionTable(
            function ($schema) use ($table_prefix) {
                $table = $schema->createTable($table_prefix . "visitors_sessions");
                $table->addColumn("id", "integer", array('autoincrement' => true));
                $table->setPrimaryKey(array("id"));
                $table->addColumn("visitor_id", "integer");
                $table->addColumn("sessiontoken", "string", array('length' => 64));
                $table->addColumn("lastseen", "datetime");
                $table->addIndex(array("visitor_id"));
                $table->addIndex(array("sessiontoken"));

                return $table;
            }
        );

        $this->controller = new Controller($this->app, $this->config);
        $recognizedvisitor = $this->controller->checkvisitor($this->app);

        // define twig functions and vars
        $this->app['twig']->addExtension(new VisitorsTwigExtension($this->controller));
        $this->app['twig']->addGlobal('visitor', $recognizedvisitor);

        $routes = array(
            array('', 'view', 'visitorsroot'),
            array('/login', 'login', 'visitorslogin'),
            array('/logout', 'logout', 'visitorslogout'),
            array('/endpoint', 'endpoint', 'visitorsendpoint'),
        );

        $visitors_routes = $this->app['controllers_factory'];

        foreach ($routes as $route) {
            list($path, $method, $binding) = $route;
            $visitors_routes
                ->match($path, array($this->controller, $method))
                ->bind($binding);
        }
        $this->app->mount("/{$basepath}", $visitors_routes);

    }

}
