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

        $this->app['db']->insert($this->tablename, array(
            'level'       => $record['level'],
            'date'        => $record['datetime']->format('U'),
            'message'     => $record['formatted'],
            'requesturi'  => $record[''],
            'route'       => $record[''],
            'ip'          => $record[''],
            'file'        => $record[''],
            'line'        => $record[''],
            'contenttype' => $record[''],
            'content_id'  => $record[''],
            'code'        => $record[''],
            'dump'        => $record['']
        ));

//         $this->statement->execute(array(
//             'channel' => $record['channel'],
//             'level' => $record['level'],
//             'message' => $record['formatted'],
//             'time' => $record['datetime']->format('U'),
//         ));
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
    public function getFormatter()
    {
        if (!$this->formatter) {
            $this->formatter = new System();
        }

        return $this->formatter;
    }
}
