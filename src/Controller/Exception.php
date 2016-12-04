<?php

namespace Bolt\Controller;

use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\File;
use Bolt\Helpers\Html;
use Carbon\Carbon;
use Cocur\Slugify\Slugify;
use Silex\ControllerCollection;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->value(Zone::KEY, Zone::FRONTEND);
    }

    /**
     * @param \Exception $exception
     *
     * @return Response
     */
    public function genericException(\Exception $exception)
    {
        if ($this->app === null) {
            throw new \RuntimeException('Exception controller being used outside of request cycle.');
        }

        $message = $exception->getMessage();
        $context = $this->getContextArray($exception);
        $context['message'] = $message;

        $html = $this->app['twig']->render('@bolt/exception/general.twig', $context);
        $response = new Response($html, Response::HTTP_OK);
        $response->headers->set('X-Debug-Exception-Handled', time());

        return $response;
    }

    /**
     * Route for kernel exception handling.
     *
     * @param GetResponseForExceptionEvent $event
     *
     * @return Response
     */
    public function kernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->app === null) {
            throw new \RuntimeException('Exception controller being used outside of request cycle.');
        }

        $exception = $event->getException();
        $message = $exception->getMessage();
        if ($exception instanceof HttpExceptionInterface && !Zone::isBackend($event->getRequest())) {
            $message = "The page could not be found, and there is no 'notfound' set in 'config.yml'. Sorry about that.";
        }

        $context = $this->getContextArray($exception);
        $context['type'] = 'general';
        $context['message'] = $message;

        $html = $this->app['twig']->render('@bolt/exception/general.twig', $context);
        $response = new Response($html, Response::HTTP_OK);
        $response->headers->set('X-Debug-Exception-Handled', time());

        return $response;
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
     * Get a pre-packaged Twig context array.
     *
     * @param \Exception $exception
     *
     * @return array
     */
    protected function getContextArray(\Exception $exception = null)
    {
        if ($exception) {
            try {
                $this->saveException($exception);
            } catch (IOException $e) {
                //
            }
        }

        $loggedOnUser = (bool) $this->app['users']->getCurrentUser() ?: false;
        $showLoggedOff = (bool) $this->app['config']->get('general/debug_show_loggedoff', false);

        // Grab a section of the file that threw the exception, so we can show it.
        $filePath = $exception ? $exception->getFile() : null;
        $lineNumber = $exception ? $exception->getLine() : null;

        if ($filePath && $lineNumber) {
            $phpFile = file($filePath) ?: [];
            $snippet = implode('', array_slice($phpFile, max(0, $lineNumber - 6), 11));
        } else {
            $snippet = false;
        }

        // We might or might not have $this->app['request'] yet, which is used in the
        // template to show the request variables. Use it, or grab what we can get.
        $request = $this->app['request_stack']->getCurrentRequest() ?: Request::createFromGlobals();

        return [
            'debug'     => ($this->app['debug'] && ($loggedOnUser || $showLoggedOff)),
            'request'   => $request,
            'exception' => [
                'object'     => $exception,
                'class_name' => $exception ? (new \ReflectionClass($exception))->getShortName() : null,
                'class_fqn'  => $exception ? get_class($exception) : null,
                'file_path'  => $filePath,
                'file_name'  => basename($filePath),
                'trace'      => $exception ? $this->getSafeTrace($exception) : null,
                'snippet'    => $snippet,
            ],
        ];
    }

    /**
     * Get an exception trace that is safe to display publicly.
     *
     * @param \Exception $exception
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
            $trace[$key]['args_safe'] = $this->getSafeArguments($trace[$key]['args']);

            // Don't display the full path, trim 64-char hexadecimal file names.
            if (isset($trace[$key]['file'])) {
                $trace[$key]['file'] = str_replace($rootPath, '[root]', $trace[$key]['file']);
                $trace[$key]['file'] = preg_replace('/([0-9a-f]{16})[0-9a-f]{48}/i', '\1…', $trace[$key]['file']);
            }
        }

        return $trace;
    }

    /**
     * Get an array of safe (sanitised) function arguments from a trace entry.
     *
     * @param array $args
     *
     * @return array
     */
    protected function getSafeArguments(array $args)
    {
        $argsSafe = [];
        foreach ($args as $arg) {
            $type = gettype($arg);
            switch ($type) {
                case 'string':
                    $argsSafe[] = sprintf('<span>"%s"</span>', Html::trimText($arg, 30));
                    break;

                case 'integer':
                case 'float':
                    $argsSafe[] = sprintf('<span>%s</span>', $arg);
                    break;

                case 'object':
                    $className = get_class($arg);
                    $shortName = (new \ReflectionClass($arg))->getShortName();
                    $argsSafe[] = sprintf('<abbr title="%s">%s</abbr>', $className, $shortName);
                    break;

                case 'boolean':
                    $argsSafe[] = $arg ? '[true]' : '[false]';
                    break;

                default:
                    $argsSafe[] = '[' . $type . ']';
            }
        }

        return $argsSafe;
    }

    /**
     * Attempt to save the serialised exception if in debug mode.
     *
     * @param \Exception $exception
     */
    protected function saveException(\Exception $exception)
    {
        if ($this->app['debug'] !== true) {
            return;
        }

        $environment = $this->app['environment'];
        $serialised = serialize(FlattenException::create($exception));

        $sourceFile = Slugify::create()->slugify($exception->getFile());
        $fileName = sprintf('%s-%s.exception', Carbon::now()->format('Ymd-Hmi'), substr($sourceFile, -102));
        $fullPath = sprintf('%s/exception/%s', $environment, $fileName);

        $cacheFilesystem = $this->app['filesystem']->getFilesystem('cache');
        $file = new File($cacheFilesystem, $fullPath);
        $file->write($serialised);
    }
}
