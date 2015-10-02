<?php

namespace Bolt\Provider;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Query\QueryParameterParser;
use Bolt\Storage\Query\SearchConfig;
use Bolt\Storage\Query\SearchQuery;
use Bolt\Storage\Query\SearchWeighter;
use Bolt\Storage\Query\SelectQuery;
use Silex\Application;
use Silex\ServiceProviderInterface;

class QueryServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['query'] = function ($app) {
            $runner = new Query($app['query.parser']);

            return $runner;
        };

        $app['query.parser'] = $app->share(
            function ($app) {
                $parser = new ContentQueryParser($app['storage']);
                $parser->addService('select', $app['query.select']);
                $parser->addService('search', $app['query.search']);
                $parser->addService('search_weighter', $app['query.search_weighter']);
                $parser->addService('search_config', $app['query.search_config']);

                return $parser;
            }
        );

        $app['query.parser.handler'] = $app->share(
            function ($app) {
                return new QueryParameterParser($app['storage']->createExpressionBuilder());
            }
        );

        $app['query.select'] = $app->share(
            function ($app) {
                return new SelectQuery($app['storage']->createQueryBuilder(), $app['query.parser.handler']);
            }
        );

        $app['query.search'] = $app->share(
            function ($app) {
                return new SearchQuery($app['storage']->createQueryBuilder(), $app['query.parser.handler'], $app['query.search_config']);
            }
        );

        $app['query.search_config'] = $app->share(
            function ($app) {
                return new SearchConfig($app['config']);
            }
        );

        $app['query.search_weighter'] = $app->share(
            function ($app) {
                return new SearchWeighter($app['query.search_config']);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
