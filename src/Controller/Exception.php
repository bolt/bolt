<?php

namespace Bolt\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception controller.
 *
 * @internal Do not extend this class! (yet)
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Exception extends Base implements ExceptionControllerInterface
{
    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->value(Zone::KEY, Zone::FRONTEND);
    }

    public function databaseConnect($platform, \Exception $previous)
    {
        if ($this->app === null) {
            throw new \RuntimeException('Exception controller being used outside of request cycle.');
        }
        $context = [
            'config'    => $this->app['config'],
            'paths'     => $this->app['resources']->getPaths(),
            'debug'     => $this->app['debug'],
            'type'      => 'connect',
            'platform'  => $platform,
            'exception' => $previous,
        ];
        $html = $this->app['twig']->render('@bolt/exception/database/exception.twig', $context);
        $response = new Response($html);

        return new Response($response, Response::HTTP_OK);
    }

    /**
     * @param string $subtype
     * @param string $name
     * @param string $driver
     * @param string $parameter
     *
     * @return Response
     */
    public function databaseDriver($subtype, $name, $driver, $parameter = null)
    {
        if ($this->app === null) {
            throw new \RuntimeException('Exception controller being used outside of request cycle.');
        }
        $context = [
            'config'    => $this->app['config'],
            'paths'     => $this->app['resources']->getPaths(),
            'debug'     => $this->app['debug'],
            'type'      => 'driver',
            'subtype'   => $subtype,
            'name'      => $name,
            'driver'    => $driver,
            'parameter' => $parameter,
        ];
        $html = $this->app['twig']->render('@bolt/exception/database/exception.twig', $context);

        return new Response($html, Response::HTTP_OK);
    }

    /**
     * @param string $subtype
     * @param string $path
     * @param string $error
     *
     * @return Response
     */
    public function databasePath($subtype, $path, $error)
    {
        if ($this->app === null) {
            throw new \RuntimeException('Exception controller being used outside of request cycle.');
        }

        $context = [
            'config'    => $this->app['config'],
            'paths'     => $this->app['resources']->getPaths(),
            'debug'     => $this->app['debug'],
            'type'      => 'path',
            'subtype'   => $subtype,
            'path'      => $path,
            'error'     => $error,
        ];
        $html = $this->app['twig']->render('@bolt/exception/database/exception.twig', $context);

        return new Response($html, Response::HTTP_OK);
    }

    /**
     * System check exceptions.
     *
     * @param string $type
     * @param array  $messages
     * @param array  $context
     *
     * @return Response
     */
    public function systemCheck($type, $messages = [], $context = [])
    {
        if ($this->app === null) {
            throw new \RuntimeException('Exception controller being used outside of request cycle.');
        }

        $context['config'] = $this->app['config'];
        $context['paths'] = $this->app['resources']->getPaths();
        $context['debug'] = $this->app['debug'];
        $context['type'] = $type;
        $context['messages'] = $messages;

        $html = $this->app['twig']->render('@bolt/exception/system/exception.twig', $context);

        return new Response($html, Response::HTTP_OK);
    }
}
