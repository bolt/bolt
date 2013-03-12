<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (! function_exists('__')) {
    /**
     * localization made right, first attempt...
     *
     * we need to check the array passed as second or third argument,
     * and for every occurence of, say, %contenttype%, generate a new string
     * with the remplaced type, for example:
     * __("This %contenttype% can't be saved.",array('%contenttype%'=>$contenttype['singular']))
     * would become:
     * __("This page can't be saved.") // if contenttype is a page
     *
     *
     * A french example of why:
     *
     * contenttype = page (female)
     * "Cette page ne peut être enregistrée."
     * "Ces pages ne peuvent être enregistrées."
     *
     * contenttype = article (male)
     * "Cet article ne peut être enregistré."
     * "Ces articles ne peuvent être enregistrés."
     *
     * contenttype: lieu (male)
     * "Ce lieu ne peut être enregistré."
     * "Ces lieux ne peuvent être enregistrés."
     *
     */
    function __() {
        global $app;
        $num_args = func_num_args();
        if (0==$num_args) {
            return null;
        }
        $args = func_get_args();
        if ($num_args > 4) {
            $fn = 'transChoice';
            //
        } elseif ($num_args == 1 || is_array($args[1])) {
            // if only 1 arg or 2nd arg is an array call trans
            $fn = 'trans';
        } else {
            $fn = 'transChoice';
        }
        $tr_args=null;
        if ( $fn == 'trans' && $num_args > 1) {
            $tr_args = &$args[1];
        } elseif ($fn == 'transChoice' && $num_args > 2) {
            $tr_args = &$args[2];
        }
        if ($tr_args) {
            $keytype='%contenttype%';
            if (array_key_exists($keytype,$tr_args)) {
                $args[0]=str_replace($keytype,$tr_args[$keytype],$args[0]);
                unset($tr_args[$keytype]);
                echo "<!-- replaced: htmlentities($args[0]) -->\n";
            }
        }

        //try {
        switch($num_args) {
            case 5:
                return $app['translator']->transChoice($args[0],$args[1],$args[2],$args[3],$args[4]);
            case 4:
                //echo "<!-- 4. call: $fn($args[0],$args[1],$args[2],$args[3]) -->\n";
                return $app['translator']->$fn($args[0],$args[1],$args[2],$args[3]);
            case 3:
                //echo "<!-- 3. call: $fn($args[0],$args[1],$args[2]) -->\n";
                return $app['translator']->$fn($args[0],$args[1],$args[2]);
            case 2:
                //echo "<!-- 2. call: $fn($args[0],$args[1] -->\n";
                return $app['translator']->$fn($args[0],$args[1]);
            case 1:
                //echo "<!-- 1. call: $fn($args[0]) -->\n";
                return $app['translator']->$fn($args[0]);
        }
        /*}
        catch (\Exception $e) {
            echo "<!-- ARGHH !!! -->\n";
            //return $args[0];
            die($e->getMessage());
        }*/
    }
} else {
    die('function __() already defined!!');
}

$app->mount('/bolt', new Bolt\Controllers\Backend());
$app->mount('/async', new Bolt\Controllers\Async());
$app->mount('', new Bolt\Controllers\Frontend());

$app->before(function () use ($app) {

    $app['twig']->addGlobal('bolt_name', $app['bolt_name']);
    $app['twig']->addGlobal('bolt_version', $app['bolt_version']);

    $app['twig']->addGlobal('frontend', false);
    $app['twig']->addGlobal('backend', false);
    $app['twig']->addGlobal('async', false);
    $app['twig']->addGlobal(getWhichEnd(), true);

    $app['twig']->addGlobal('user', $app['users']->getCurrentUser());
    $app['twig']->addGlobal('users', $app['users']->getUsers());
    $app['twig']->addGlobal('config', $app['config']);

});

// On 'finish' attach the debug-bar, if debug is enabled..
if ($app['debug'] && ($app['session']->has('user') || $app['config']['general']['debug_show_loggedoff'] ) ) {

    $logger = new Doctrine\DBAL\Logging\DebugStack();
    $app['db.config']->setSQLLogger($logger);

    // @todo See if we can squeeze this into $app->after, instead of ->finish()
    $app->finish(function (Request $request, Response $response) use ($app, $logger) {

        // Make sure debug is _still_ enabled, and/or the debugbar isn't turned off in code.
        if (!$app['debug'] || !$app['debugbar']) {
            return "";
        }

        $queries = array();
        $querycount = 0;
        $querytime = 0;

        foreach ($logger->queries as $query) {

            // Skip "PRAGMA .." and similar queries by SQLITE.
            if ( (strpos($query['sql'], "PRAGMA ")===0) || (strpos($query['sql'], "SELECT DISTINCT k.`CONSTRAINT_NAME`")===0) ||
            (strpos($query['sql'], "SELECT TABLE_NAME AS `Table`")===0) ||   (strpos($query['sql'], "SELECT COLUMN_NAME AS Field")===0)  ){
                continue;
            }

            $queries[] = array(
                'query' => $query['sql'],
                'params' => $query['params'],
                'types' => $query['types'],
                'duration' => sprintf("%0.2f", $query['executionMS'])
            );

            $querycount++;
            $querytime += $query['executionMS'];

        }


        $twig = $app['twig.loader'];
        $templates = hackislyParseRegexTemplates($twig);

        $route = $request->get('_route') ;
        $route_params = $request->get('_route_params') ;

        $log = $app['log']->getMemorylog();

        // echo "<pre>\n" . util::var_dump($log, true) . "</pre>\n";

        $servervars = array(
            'cookies <small>($_COOKIES)</small>' => $request->cookies->all(),
            'headers' => makeValuepairs($request->headers->all(), '', '0'),
            'query <small>($_GET)</small>' => $request->query->all(),
            'request <small>($_POST)</small>' => $request->request->all(),
            'session <small>($_SESSION)</small>' => $request->getSession()->all(),
            'server <small>($_SERVER)</small>' => $request->server->all(),
            'response' => makeValuepairs($response->headers->all(), '', '0'),
            'statuscode' => $response->getStatusCode()
        );

        echo $app['twig']->render('debugbar.twig', array(
            'timetaken' => timeTaken(),
            'memtaken' => getMem(),
            'maxmemtaken' => getMaxMem(),
            'querycount' => $querycount,
            'querytime' => sprintf("%0.2f", $querytime),
            'queries' => $queries,
            'servervars' => $servervars,
            'templates' => $templates,
            'log' => $log,
            'route' => "/".$route,
            'route_params' => $route_params,
            'editlink' => $app['editlink'],
            'paths' => getPaths($app['config']),
            'logvalues' => $app['log']->getValues()
        ));

    });
}


$app->after(function (Request $request, Response $response) use ($app) {

    $end = getWhichEnd();

    if ($end == "frontend") {

        $html = $response->getContent();

        // Insert our 'generator' after the last <meta ..> tag.
        // @todo Find a neat solution for this
        if (stripos($response->headers->get('Content-Type'), 'xml') === false){
            $app['extensions']->insertSnippet(\Bolt\Extensions\Snippets\Location::AFTER_META, '<meta name="generator" content="Bolt">');
        }

        $html = $app['extensions']->processSnippetQueue($html);

        $response->setContent($html);

    }

});

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Error page.
 */
$app->error(function (\Exception $e) use ($app) {

    $paths = getPaths($app['config']);

    $twigvars = array();

    $twigvars['class'] = get_class($e);
    $twigvars['message'] = $e->getMessage();
    $twigvars['code'] = $e->getCode();
    $twigvars['paths'] = $paths;

    $app['log']->add($twigvars['message'], 2, '', 'abort');

    $end = getWhichEnd();

    $trace = $e->getTrace();

    foreach ($trace as $key=>$value) {

        if (!empty($value['file']) && strpos($value['file'], "/vendor/") > 0 ) {
            unset($trace[$key]['args']);
        }

        // Don't display the full path..
        if ( isset( $trace[$key]['file'] ) )
        {
            $trace[$key]['file'] = str_replace(BOLT_PROJECT_ROOT_DIR, "[root]", $trace[$key]['file']);
        }

    }

    $twigvars['trace'] = $trace;
    $twigvars['title'] = "An error has occured!";

    if ( ($e instanceof NotFoundHttpException) && ($end == "frontend") ) {

        $content = $app['storage']->getContent($app['config']['general']['notfound'], array('returnsingle' => true));

        // Then, select which template to use, based on our 'cascading templates rules'
        if ($content instanceof \Bolt\Content && !empty($content->id)) {
            $template = $content->template();

            return $app['twig']->render($template, array(
                'record' => $content,
                $content->contenttype['singular_slug'] => $content // Make sure we can also access it as {{ page.title }} for pages, etc.
            ));
        } else {
            $twigvars['message'] = "The page could not be found, and there is no 'notfound' set in 'config.yml'. Sorry about that.";
        }

    }

    return $app['twig']->render('error.twig', $twigvars);


});

