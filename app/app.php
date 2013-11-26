<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();
if ($proxies = $app['config']->get('general/trustProxies')) {
    $request->setTrustedProxies($proxies);
}

// Mount the 'backend' on the branding:path setting. Defaults to '/bolt'.
$app->mount($app['config']->get('general/branding/path'), new Bolt\Controllers\Backend());
$app->mount('/async', new Bolt\Controllers\Async());

if ($app['config']->get('general/enforce_ssl')) {
    foreach ($app['routes']->getIterator() as $route) {
        $route->requireHttps();
    }
}


$app->mount('', new Bolt\Controllers\Routing());

$app->before(function (Request $request) use ($app) {
    $app['twig']->addGlobal('bolt_name', $app['bolt_name']);
    $app['twig']->addGlobal('bolt_version', $app['bolt_version']);

    $app['twig']->addGlobal('frontend', false);
    $app['twig']->addGlobal('backend', false);
    $app['twig']->addGlobal('async', false);
    $app['twig']->addGlobal($app['config']->getWhichEnd(), true);

    $app['twig']->addGlobal('user', $app['users']->getCurrentUser());
    $app['twig']->addGlobal('users', $app['users']->getUsers());
    $app['twig']->addGlobal('config', $app['config']);

    // Sanity checks for doubles in in contenttypes.
    // unfortunately this has to be done here, because the 'translator' classes need to be initialised.
    $app['config']->checkConfig();

});

// On 'finish' attach the debug-bar, if debug is enabled..
if ($app['debug'] && ($app['session']->has('user') || $app['config']->get('general/debug_show_loggedoff') ) ) {

    // Set the error_reporting to the level specified in config.yml
    error_reporting($app['config']->get('general/debug_error_level'));

    // Register Whoops, to handle errors for logged in users only.
    if ($app['config']->get('general/debug_enable_whoops')) {
        $app->register(new Whoops\Provider\Silex\WhoopsServiceProvider);
    }

    $app->register(new Silex\Provider\ServiceControllerServiceProvider);

    // Register the Silex/Symfony web debug toolbar.
    $app->register(new Silex\Provider\WebProfilerServiceProvider(), array(
        'profiler.cache_dir' => __DIR__.'/cache/profiler',
        'profiler.mount_prefix' => '/_profiler', // this is the default
    ));

    // Register the toolbar item for our Database query log.
    $app->register(new Bolt\Provider\DatabaseProfilerServiceProvider());

    // Register the toolbar item for our bolt nipple.
    $app->register(new Bolt\Provider\BoltProfilerServiceProvider());

    // Register the toolbar item for the Twig toolbar item.
    $app->register(new Bolt\Provider\TwigProfilerServiceProvider());

    $app['twig.loader.filesystem']->addPath(BOLT_PROJECT_ROOT_DIR . '/vendor/symfony/web-profiler-bundle/Symfony/Bundle/WebProfilerBundle/Resources/views', 'WebProfiler');
    $app['twig.loader.filesystem']->addPath(__DIR__ . '/view', 'BoltProfiler');

    $app->after(function () use ($app) {

        foreach(hackislyParseRegexTemplates($app['twig.loader.filesystem']) as $template) {
            $app['twig.logger']->collectTemplateData($template);
        }

    });

} else {
    error_reporting(E_ALL &~ E_NOTICE &~ E_DEPRECATED &~ E_USER_DEPRECATED);
}


$app->after(function (Request $request, Response $response) use ($app) {

    // true if we need to consider adding html snippets
    if (isset($app['htmlsnippets']) && ($app['htmlsnippets'] === true)) {

        // only add when content-type is text/html
        if (strpos($response->headers->get('Content-Type'), 'text/html') !== false) {

            // Add our meta generator tag..
            $app['extensions']->insertSnippet(\Bolt\Extensions\Snippets\Location::AFTER_META, '<meta name="generator" content="Bolt">');

            // Perhaps add a canonical link..

            if ($app['config']->get('general/canonical')) {
                $snippet = sprintf('<link rel="canonical" href="%s">', $app['paths']['canonicalurl']);
                $app['extensions']->insertSnippet(\Bolt\Extensions\Snippets\Location::AFTER_META, $snippet);
            }

            // Perhaps add a favicon..
            if ($app['config']->get('general/favicon')) {
                $snippet = sprintf('<link rel="shortcut icon" href="//%s%s%s">',
                    $app['paths']['canonical'],
                    $app['paths']['theme'],
                    $app['config']->get('general/favicon'));
                $app['extensions']->insertSnippet(\Bolt\Extensions\Snippets\Location::AFTER_META, $snippet);
            }

            $html = $response->getContent();

            $html = $app['extensions']->processSnippetQueue($html);

            $response->setContent($html);
        }
    }

});

use Symfony\Component\HttpKernel\Exception\HttpException;



/**
 * Error page.
 */
$app->error(function (\Exception $e) use ($app) {

    // If we are in maintenance mode and current user is not logged in, show maintenance notice.
    // @see /app/src/Bolt/Controllers/Frontend.php, Frontend::before()
    if ($app['config']->get('general/maintenance_mode')) {
        $user = $app['users']->getCurrentUser();
        if ($user['userlevel'] < 2) {
            $template = $app['config']->get('general/maintenance_template');
            $body = $app['twig']->render($template);
            return new Response($body, 503);
        }
    }

    $paths = getPaths($app['config']);

    $twigvars = array();

    $twigvars['class'] = get_class($e);
    $twigvars['message'] = $e->getMessage();
    $twigvars['code'] = $e->getCode();
    $twigvars['paths'] = $paths;

    $app['log']->add($twigvars['message'], 2, '', 'abort');

    $end = $app['config']->getWhichEnd();

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

    if ( ($e instanceof HttpException) && ($end == "frontend") ) {

        $content = $app['storage']->getContent($app['config']->get('general/notfound'), array('returnsingle' => true));

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

