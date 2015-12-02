<?php

namespace Bolt\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Silex\Application;

/**
 * Monolog Database handler for system logging.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SystemHandler extends AbstractProcessingHandler
{
    /** @var Application */
    private $app;

    /** @var boolean */
    private $initialized = false;

    /** @var string */
    private $tablename;

    /**
     * @param Application $app
     * @param integer     $level
     * @param boolean     $bubble
     */
    public function __construct(Application $app, $level = Logger::DEBUG, $bubble = true)
    {
        $this->app = $app;
        parent::__construct($level, $bubble);
    }

    /**
     * Handle.
     *
     * @param array $record
     *
     * @return boolean
     */
    public function handle(array $record)
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $record = $this->processRecord($record);
        $record['formatted'] = $this->getFormatter()->format($record);

        try {
            $this->write($record);
        } catch (\Exception $e) {
            // Nothing.
        }

        return false === $this->bubble;
    }

    protected function write(array $record)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (isset($record['context']['exception'])
            && ($e = $record['context']['exception'])
            && $e instanceof \Exception
        ) {
            $trace = $e->getTrace();
            $source = json_encode(
                [
                    'file'     => $e->getFile(),
                    'line'     => $e->getLine(),
                    'class'    => isset($trace['class']) ? $trace['class'] : '',
                    'function' => isset($trace['function']) ? $trace['function'] : '',
                    'message'  => $e->getMessage(),
                ]
            );
        } elseif ($this->app['debug']) {
            $backtrace = debug_backtrace();
            $backtrace = $backtrace[3];

            $source = json_encode(
                [
                    'file'     => str_replace($this->app['resources']->getPath('root'), '', $backtrace['file']),
                    'line'     => $backtrace['line'],
                ]
            );
        } else {
            $source = '';
        }

        // Only get a user session if it's started
        if ($this->app['session']->isStarted()) {
            $user = $this->app['session']->get('authentication');
            $user = $user ? $user->getUser()->toArray() : null;
        }

        // Get request data if available
        $request = $this->app['request_stack']->getCurrentRequest();
        $requestUri = $request ? $request->getRequestUri() : '';
        $requestRoute = $request ? $request->get('_route') : '';
        $requestIp = $request ? $request->getClientIp() : '127.0.0.1';

        $this->app['db']->insert(
            $this->tablename,
            [
                'level'      => $record['level'],
                'date'       => $record['datetime']->format('Y-m-d H:i:s'),
                'message'    => $record['message'],
                'ownerid'    => isset($user['id']) ? $user['id'] : 0,
                'requesturi' => $requestUri,
                'route'      => $requestRoute,
                'ip'         => $requestIp,
                'context'    => isset($record['context']['event']) ? $record['context']['event'] : '',
                'source'     => $source,
            ]
        );
    }

    /**
     * Initialize class parameters.
     */
    private function initialize()
    {
        $this->tablename = sprintf('%s%s', $this->app['config']->get('general/database/prefix'), 'log_system');
        $this->initialized = true;
    }
}
