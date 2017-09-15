<?php

namespace Bolt\Provider;

use Bolt\Twig;
use Bolt\Twig\ArrayAccessSecurityProxy;
use Bolt\Twig\Extension;
use Bolt\Twig\FilesystemLoader;
use Bolt\Twig\SecurityPolicy;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\ChainLoader;

class TwigServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        if (!isset($app['twig'])) {
            $app->register(new \Silex\Provider\TwigServiceProvider());
        }

        // Set authentication providers before security extension is invoked.
        $factory = $app->raw('twig');
        $app['twig'] = function ($app) use ($factory) {
            $app['security.firewall'];

            return $factory($app);
        };

        // Twig runtime handlers
        $app['twig.runtime.bolt_admin'] = function ($app) {
            return new Twig\Runtime\AdminRuntime($app['config'], $app['stack'], $app['url_generator'], $app);
        };
        $app['twig.runtime.bolt'] = function ($app) {
            return new Twig\Runtime\BoltRuntime($app['query']);
        };
        $app['twig.runtime.bolt_html'] = function ($app) {
            return new Twig\Runtime\HtmlRuntime(
                $app['config'],
                $app['markdown'],
                $app['menu'],
                $app['storage']
            );
        };
        $app['twig.runtime.bolt_image'] = function ($app) {
            return new Twig\Runtime\ImageRuntime(
                $app['config'],
                $app['url_generator'],
                $app['filesystem'],
                $app['filesystem.matcher']
            );
        };
        $app['twig.runtime.bolt_record'] = function ($app) {
            return new Twig\Runtime\RecordRuntime(
                $app['request_stack'],
                $app['pager'],
                $app['filesystem']->getDir('theme://' . $app['config']->get('theme/template_directory')),
                $app['config']->get('theme/templateselect/templates', []),
                $app['config']->get('general/compatibility/twig_globals', true)
            );
        };
        $app['twig.runtime.bolt_routing'] = function ($app) {
            return new Twig\Runtime\RoutingRuntime($app['canonical'], $app['request_stack'], $app['locale']);
        };
        $app['twig.runtime.bolt_text'] = function ($app) {
            return new Twig\Runtime\TextRuntime($app['logger.system'], $app['slugify']);
        };
        $app['twig.runtime.bolt_user'] = function ($app) {
            return new Twig\Runtime\UserRuntime($app['users'], $app['csrf.token_manager']);
        };
        $app['twig.runtime.bolt_utils'] = function ($app) {
            return new Twig\Runtime\UtilsRuntime(
                $app['logger.firebug'],
                $app['debug'],
                (bool) $app['users']->getCurrentUser() ?: false,
                $app['config']->get('general/debug_show_loggedoff', false)
            );
        };
        $app['twig.runtime.bolt_widget'] = function ($app) {
            return new Twig\Runtime\WidgetRuntime($app['asset.queue.widget']);
        };
        $app['twig.runtime.dump'] = function ($app) {
            return new Twig\Runtime\DumpRuntime(
                $app['var_dumper.cloner'],
                $app['var_dumper.html_dumper'],
                $app['users'],
                $app['config']->get('general/debug_show_loggedoff', false)
            );
        };

        $app['twig.runtimes'] = $app->extend(
            'twig.runtimes',
            function (array $runtimes) {
                return $runtimes + [
                    Twig\Runtime\AdminRuntime::class   => 'twig.runtime.bolt_admin',
                    Twig\Runtime\BoltRuntime::class    => 'twig.runtime.bolt',
                    Twig\Runtime\HtmlRuntime::class    => 'twig.runtime.bolt_html',
                    Twig\Runtime\ImageRuntime::class   => 'twig.runtime.bolt_image',
                    Twig\Runtime\RecordRuntime::class  => 'twig.runtime.bolt_record',
                    Twig\Runtime\RoutingRuntime::class => 'twig.runtime.bolt_routing',
                    Twig\Runtime\TextRuntime::class    => 'twig.runtime.bolt_text',
                    Twig\Runtime\UserRuntime::class    => 'twig.runtime.bolt_user',
                    Twig\Runtime\UtilsRuntime::class   => 'twig.runtime.bolt_utils',
                    Twig\Runtime\WidgetRuntime::class  => 'twig.runtime.bolt_widget',
                    Twig\Runtime\DumpRuntime::class    => 'twig.runtime.dump',
                ];
            }
        );

        $app['twig.loader.bolt_filesystem'] = function ($app) {
            $loader = new FilesystemLoader($app['filesystem']);

            $loader->addPath('bolt://templates/defaults', 'theme');
            $loader->addPath('bolt://templates/bolt', 'bolt');

            $loader->addPath('bolt://templates/defaults');
            $loader->addPath('bolt://templates/bolt');

            return $loader;
        };

        // Insert our filesystem loader before native one
        $app['twig.loader'] = function ($app) {
            return new ChainLoader(
                [
                    $app['twig.loader.array'],
                    $app['twig.loader.bolt_filesystem'],
                    $app['twig.loader.filesystem'],
                ]
            );
        };

        $this->registerSandbox($app);

        // Add the Bolt Twig Extension.
        $app['twig'] = $app->extend(
            'twig',
            function (Environment $twig, $app) {
                $twig->addExtension($app['twig.extension.bolt']);
                $twig->addExtension($app['twig.extension.bolt_admin']);
                $twig->addExtension($app['twig.extension.bolt_array']);
                $twig->addExtension($app['twig.extension.bolt_html']);
                $twig->addExtension($app['twig.extension.bolt_image']);
                $twig->addExtension($app['twig.extension.bolt_record']);
                $twig->addExtension($app['twig.extension.bolt_routing']);
                $twig->addExtension($app['twig.extension.bolt_text']);
                $twig->addExtension($app['twig.extension.bolt_users']);
                $twig->addExtension($app['twig.extension.bolt_utils']);
                $twig->addExtension($app['twig.extension.bolt_widget']);

                $twig->addExtension($app['twig.extension.asset']);
                $twig->addExtension($app['twig.extension.string_loader']);

                $twig->addExtension($app['twig.extension.dump']);

                $sandbox = $app['twig.extension.sandbox'];
                $twig->addExtension($sandbox);
                $twig->addGlobal('app', new ArrayAccessSecurityProxy($app, $sandbox));

                return $twig;
            }
        );

        $app['twig.extension.bolt'] = function ($app) {
            return new Extension\BoltExtension($app['storage.lazy'], $app['config']);
        };

        $app['twig.extension.bolt_admin'] = function () {
            return new Extension\AdminExtension();
        };

        $app['twig.extension.bolt_array'] = function () {
            return new Extension\ArrayExtension();
        };

        $app['twig.extension.bolt_html'] = function () {
            return new Extension\HtmlExtension();
        };

        $app['twig.extension.bolt_image'] = function () {
            return new Extension\ImageExtension();
        };

        $app['twig.extension.bolt_record'] = function () {
            return new Extension\RecordExtension();
        };

        $app['twig.extension.bolt_routing'] = function () {
            return new Extension\RoutingExtension();
        };

        $app['twig.extension.bolt_text'] = function () {
            return new Extension\TextExtension();
        };

        $app['twig.extension.bolt_users'] = function () {
            return new Extension\UserExtension();
        };

        $app['twig.extension.bolt_utils'] = function () {
            return new Extension\UtilsExtension();
        };

        $app['twig.extension.bolt_widget'] = function () {
            return new Extension\WidgetExtension();
        };

        $app['twig.extension.asset'] = function ($app) {
            return new AssetExtension($app['asset.packages']);
        };

        $app['twig.extension.dump'] = function () {
            return new Extension\DumpExtension();
        };

        $app['twig.extension.string_loader'] = function () {
            return new StringLoaderExtension();
        };

        // Twig options
        $app['twig.options'] = $app->factory(
            function () use ($app) {
                $options = [];

                // Should we cache or not?
                if ($app['config']->get('general/caching/templates')) {
                    $key = hash('md5', $app['config']->get('general/theme'));
                    $options['cache'] = $app['path_resolver']->resolve('%cache%/' . $app['environment'] . '/twig/' . $key);
                }

                if (($strict = $app['config']->get('general/strict_variables')) !== null) {
                    $options['strict_variables'] = $strict;
                }

            return $options;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        // Add path here since "theme" filesystem isn't mounted until boot.
        $themePath = 'theme://' . $app['config']->get('theme/template_directory');
        $app['twig.loader.bolt_filesystem']->prependPath($themePath, 'theme');
        $app['twig.loader.bolt_filesystem']->prependPath($themePath);
    }

    protected function registerSandbox(Application $app)
    {
        $app['twig.extension.sandbox'] = function ($app) {
            return new SandboxExtension($app['twig.sandbox.policy']);
        };

        $app['twig.sandbox.policy'] = function ($app) {
            return new SecurityPolicy(
                $app['twig.sandbox.policy.tags'],
                $app['twig.sandbox.policy.filters'],
                $app['twig.sandbox.policy.methods'],
                $app['twig.sandbox.policy.properties'],
                $app['twig.sandbox.policy.functions']
            );
        };

        $app['twig.sandbox.policy.tags'] = function () {
            return [
                // Core
                'for',
                'if',
                'block',
                'filter',
                'macro',
                'set',
                'spaceless',
                'do',

                // Translation Extension
                'trans',
                'transchoice',
                'trans_default_domain',
            ];
        };

        $app['twig.sandbox.policy.functions'] = function () {
            return [
                // Core
                'max',
                'min',
                'range',
                'constant',
                'cycle',
                'random',
                'date',

                // Asset Extension
                'asset',
                'asset_version',

                // Bolt Extension
                '__',
                'backtrace',
                'buid',
                'canonical',
                'countwidgets',
                'current',
                'data',
                'dump',
                'excerpt',
                'fancybox',
                'fields',
                //'file_exists',
                'firebug',
                'first',
                'getuser',
                'getuserid',
                'getwidgets',
                'haswidgets',
                'hattr',
                'hclass',
                'htmllang',
                'image',
                //'imageinfo',
                'isallowed',
                'ischangelogenabled',
                'ismobileclient',
                'last',
                'link',
                //'listtemplates',
                'markdown',
                //'menu',
                'pager',
                'popup',
                'print',
                'randomquote',
                //'redirect',
                //'request',
                'showimage',
                'stack',
                'thumbnail',
                'token',
                'trimtext',
                'unique',
                'widgets',

                // Routing Extension
                'url',
                'path',
            ];
        };

        $app['twig.sandbox.policy.filters'] = function () {
            return [
                // Core
                'date',
                'date_modify',
                'format',
                'replace',
                'number_format',
                'abs',
                'round',
                'url_encode',
                'json_encode',
                'convert_encoding',
                'title',
                'capitalize',
                'upper',
                'lower',
                'striptags',
                'trim',
                'nl2br',
                'join',
                'split',
                'sort',
                'merge',
                'batch',
                'reverse',
                'length',
                'slice',
                'first',
                'last',
                'default',
                'keys',
                'escape',
                'e',

                // Bolt Extension
                '__',
                'current',
                //'editable',
                'excerpt',
                'fancybox',
                'image',
                //'imageinfo',
                'json_decode',
                'localdate',
                'localedatetime',
                'loglevel',
                'markdown',
                'order',
                'popup',
                'preg_replace',
                'safestring',
                'selectfield',
                'showimage',
                'shuffle',
                'shy',
                'slug',
                'thumbnail',
                'trimtext',
                'tt',
                'twig',
                'ucfirst',
                //'ymllink',

                // Form Extension
                'humanize',

                // Translation Extension
                'trans',
                'transchoice',
            ];
        };

        $app['twig.sandbox.policy.methods'] = [];
        $app['twig.sandbox.policy.properties'] = [];
    }
}
