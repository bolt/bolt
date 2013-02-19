<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            $app['extensions']->insertSnippet('aftermeta', '<meta name="generator" content="Bolt">');
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

