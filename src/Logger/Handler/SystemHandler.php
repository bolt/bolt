<?php

namespace Bolt\Logger\Handler;

use Doctrine\DBAL\Schema\Schema;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

use Bolt\Application;
use Bolt\Helpers\String;
use Bolt\Logger\Formatter\System;

/**
 * Monolog Database handler for system logging
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SystemHandler extends AbstractProcessingHandler
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var boolean
     */
    private $initialized = false;

    /**
     * @var string
     */
    private $tablename;

    /**
     *
     * @param Application $app
     * @param string      $logger
     * @param integer     $level
     * @param boolean     $bubble
     */
    public function __construct(Application $app, $level = Logger::DEBUG, $bubble = true)
    {
        $this->app = $app;
        parent::__construct($level, $bubble);
    }

    /**
     * Handle
     *
     * @param  array   $record
     * @return boolean
     */
    public function handle(array $record)
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $record = $this->processRecord($record);
        $record['formatted'] = $this->getFormatter()->format($record);
        $this->write($record);

        return false === $this->bubble;
    }

    protected function write(array $record)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $backtrace = debug_backtrace();
        $filename = str_replace($this->app['resources']->getPath('root'), "", $backtrace[0]['file']);

        $this->user = $this->app['session']->get('user');
        $username = isset($this->user['username']) ? $this->user['username'] : "";

        if (is_object($record['context']['content'])) {
            $contenttype = $record['context']['content']->contenttype['slug'];
            $content_id  = intval($record['context']['content']->id);
        } else {
            $contenttype = '';
            $content_id  = 0;
        }

        /*
         * To kill list:
         *  - code
         *  - dump
         */
        try {
            $this->app['db']->insert($this->tablename, array(
                'level'       => $record['level'],
                'date'        => $record['datetime']->format('Y-m-d H:i:s'),
                'message'     => $record['message'],
                'username'    => $username,
                'requesturi'  => $this->app['request']->getRequestUri(),
                'route'       => $this->app['request']->get('_route'),
                'ip'          => $this->app['request']->getClientIp(),
                'file'        => $filename,
                'line'        => $backtrace[0]['line'],
                'contenttype' => $contenttype,
                'content_id'  => $content_id,
                'code'        => isset($record['context']['event']) ? $record['context']['event'] : '',
                'dump'        => ''
            ));
        } catch (\Exception $e) {
            // Nothing..
        }
    }

    /**
     * Processes a record.
     *
     * @param  array $record
     * @return array
     */
    protected function processRecord(array $record)
    {
        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }

        return $record;
    }

    /**
     * Initialize
     */
    private function initialize()
    {
        $this->tablename = sprintf("%s%s", $this->app['config']->get('general/database/prefix', "bolt_"), 'log');
        $this->initialized = true;
    }

    /**
     *
     */
//     public function getFormatter()
//     {
//         if (!$this->formatter) {
//             $this->formatter = new System();
//         }

//         return $this->formatter;
//     }
}
