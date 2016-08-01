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

    /**
     * @param \Exception $exception
     * @param string     $message
     *
     * @return Response
     */
    public function generalException(\Exception $exception, $message = null)
    {
        if ($this->app === null) {
            throw new \RuntimeException('Exception controller being used outside of request cycle.');
        }

        $context = $this->getContextArray($exception);
        $context['type'] = 'general';
        $context['message'] = $message;

        $html = $this->app['twig']->render('@bolt/exception/general.twig', $context);
        $response = new Response($html);

        return new Response($response, Response::HTTP_OK);
    }

    /**
     * @param string     $platform
     * @param \Exception $previous
     *
     * @return Response
     */
    public function databaseConnect($platform, \Exception $previous)
    {
        if ($this->app === null) {
            throw new \RuntimeException('Exception controller being used outside of request cycle.');
        }

        $context = $this->getContextArray($previous);
        $context['type'] = 'connect';
        $context['platform'] = $platform;
        $context['exception'] = $previous;

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

        $context = $this->getContextArray();
        $context['type'] = 'driver';
        $context['subtype'] = $subtype;
        $context['name'] = $name;
        $context['driver'] = $driver;
        $context['parameter'] = $parameter;

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

        $context = $this->getContextArray();
        $context['type'] = 'path';
        $context['subtype'] = $subtype;
        $context['path'] = $path;
        $context['error'] = $error;

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

    /**
     * Get the exception trace that is safe to display publicly.
     *
     * @param \Exception  $exception
     *
     * @return array
     */
    protected function getSafeTrace(\Exception $exception)
    {
        if (!$this->app['debug'] && !($this->app['session']->isStarted() && $this->app['session']->has('authentication'))) {
            return [];
        }

        $rootPath = $this->app['resources']->getPath('root');
        $trace = $exception->getTrace();
        foreach ($trace as $key => $value) {
            unset($trace[$key]['args']);

            // Don't display the full path.
            if (isset($trace[$key]['file'])) {
                $trace[$key]['file'] = str_replace($rootPath, '[root]', $trace[$key]['file']);
            }
        }

        return $trace;
    }

    /**
     * Get a pre-packaged Twig context array.
     *
     * @param \Exception $exception
     *
     * @return array
     */
    protected function getContextArray(\Exception $exception = null)
    {
        return [
            'config'    => $this->app['config'],
            'paths'     => $this->app['resources']->getPaths(),
            'debug'     => $this->app['debug'],
            'exception' => [
                'object' => $exception,
                'class'  => $exception ? get_class($exception) : null,
                'file'   => $exception ? basename($exception->getFile()) : null,
                'trace'  => $exception ? $this->getSafeTrace($exception) : null,
            ],
        ];
    }
}
