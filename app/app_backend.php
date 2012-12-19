<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$backend = $app['controllers_factory'];

$backend->get("", '\Bolt\Controllers\Backend::dashboard')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('dashboard');

$backend->match("/login", '\Bolt\Controllers\Backend::login')
    ->method('GET|POST')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('login');

$backend->get("/logout", '\Bolt\Controllers\Backend::logout')
    ->bind('logout');

$backend->get("/dbupdate", '\Bolt\Controllers\Backend::dbupdate')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('dbupdate');

$backend->get("/clearcache", '\Bolt\Controllers\Backend::clearcache')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('clearcache');

$backend->get("/prefill", '\Bolt\Controllers\Backend::prefill')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('prefill');

$backend->get("/overview/{contenttypeslug}", '\Bolt\Controllers\Backend::overview')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('overview');

$backend->match("/editcontent/{contenttypeslug}/{id}", '\Bolt\Controllers\Backend::editcontent')
    ->before('\Bolt\Controllers\Backend::before')
    ->assert('id', '\d*')
    ->method('GET|POST')
    ->bind('editcontent');

$backend->get("/content/{action}/{contenttypeslug}/{id}", '\Bolt\Controllers\Backend::contentaction')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('contentaction');

$backend->get("/users", '\Bolt\Controllers\Backend::users')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('users');

$backend->match("/users/edit/{id}", '\Bolt\Controllers\Backend::useredit')
    ->before('\Bolt\Controllers\Backend::before')
    ->assert('id', '\d*')
    ->method('GET|POST')
    ->bind('useredit');

$backend->get("/about", '\Bolt\Controllers\Backend::about')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('about');

$backend->get("/extensions", '\Bolt\Controllers\Backend::extensions')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('extensions');

$backend->get("/user/{action}/{id}", '\Bolt\Controllers\Backend::extensions')
    ->before('\Bolt\Controllers\Backend::before')
    ->bind('useraction');

$backend->get("/files/{path}", '\Bolt\Controllers\Backend::files')
    ->before('\Bolt\Controllers\Backend::before')
    ->assert('path', '.+')
    ->bind('files');

$backend->match("/file/edit/{file}", '\Bolt\Controllers\Backend::fileedit')
    ->before('\Bolt\Controllers\Backend::before')
    ->assert('file', '.+')
    ->method('GET|POST')
    ->bind('fileedit');

$app->mount('/bolt', $backend);


$app->before(function () use ($app) {
    global $bolt_name, $bolt_version;

    $app['twig']->addGlobal('bolt_name', $bolt_name);
    $app['twig']->addGlobal('bolt_version', $bolt_version);

    $app['twig']->addGlobal('users', $app['users']->getUsers());
    $app['twig']->addGlobal('config', $app['config']);

});

// On 'finish' attach the debug-bar, if debug is enabled..
if ($app['debug'] && ($app['session']->has('user') || $app['config']['general']['debug_show_loggedoff'] ) ) {

    $logger = new Doctrine\DBAL\Logging\DebugStack();
    $app['db.config']->setSQLLogger($logger);

    // TODO: See if we can squeeze this into $app->after, instead of ->finish()
    $app->finish(function (Request $request, Response $response) use ($app, $logger) {

        $end = !empty($app['end']) ? $app['end'] : false;

        // Make sure debug is _still_ enabled, and we're not in the "async end".
        if (!$app['debug'] || $end == "asynchronous") {
            return "";
        }

        $queries = array();
        $querycount = 0;
        $querytime = 0;

        foreach ($logger->queries as $query) {

            // Skip "PRAGMA .." queries by SQLITE.
            if (strpos($query['sql'], "PRAGMA ")===0) {
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
    $end = !empty($app['end']) ? $app['end'] : false;

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



/**
 * Error page.
 */
$app->error(function (Exception $e) use ($app) {

    $paths = getPaths($app['config']);

    $twigvars = array();

    $twigvars['class'] = get_class($e);
    $twigvars['message'] = $e->getMessage();
    $twigvars['code'] = $e->getCode();
    $twigvars['paths'] = $paths;

    $trace = $e->getTrace();

    foreach ($trace as $key=>$value) {

        if (!empty($value['file']) && strpos($value['file'], "/vendor/") > 0 ) {
            unset($trace[$key]['args']);
        }

        // Don't display the full path..
        $trace[$key]['file'] = str_replace($paths['rootpath'], "[root]", $trace[$key]['file']);

    }

    $twigvars['trace'] = $trace;

    $twigvars['title'] = "An error has occured!";

    return $app['twig']->render('error.twig', $twigvars);

});

