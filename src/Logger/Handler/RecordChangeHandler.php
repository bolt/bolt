<?php

namespace Bolt\Logger\Handler;

use Bolt\Application;
use Bolt\Content;
use Bolt\DeepDiff;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Monolog Database handler for record changes (changelog).
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordChangeHandler extends AbstractProcessingHandler
{
    /** @var Application */
    private $app;

    /** @var boolean */
    private $initialized = false;

    /** @var string */
    private $tablename;

    /** @var array */
    private $allowed;

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
        $this->write($record);

        return false === $this->bubble;
    }

    protected function write(array $record)
    {
        // Simply exit if we're not enabled
        if (!$this->app['config']->get('general/changelog/enabled')) {
            return;
        }

        // Initialise ourselves if not already
        if (!$this->initialized) {
            $this->initialize();
        }

        // Check for a valid call
        if (!in_array($record['context']['action'], $this->allowed)) {
            throw new \Exception("Invalid action '{$record['context']['action']}' specified for changelog (must be one of [ " . implode(', ', $this->allowed) . " ])");
        }
        if (empty($record['context']['old']) && empty($record['context']['new'])) {
            throw new \Exception("Tried to log something that cannot be: both old and new content are empty");
        }
        if (empty($record['context']['old']) && in_array($record['context']['action'], array('UPDATE', 'DELETE'))) {
            throw new \Exception("Cannot log action '{$record['context']['action']}' when old content doesn't exist");
        }
        if (empty($record['context']['new']) && in_array($record['context']['action'], array('INSERT', 'UPDATE'))) {
            throw new \Exception("Cannot log action '{$record['context']['action']}' when new content is empty");
        }

        $data = array();
        switch ($record['context']['action']) {
            case 'UPDATE':
                $diff = DeepDiff::diff($record['context']['old'], $record['context']['new']);
                foreach ($diff as $item) {
                    list($k, $old, $new) = $item;
                    if (isset($record['context']['new'][$k])) {
                        $data[$k] = array($old, $new);
                    }
                }
                break;
            case 'INSERT':
                foreach ($record['context']['new'] as $k => $val) {
                    $data[$k] = array(null, $val);
                }
                break;
            case 'DELETE':
                foreach ($record['context']['old'] as $k => $val) {
                    $data[$k] = array($val, null);
                }
                break;
        }

        if ($record['context']['new']) {
            $content = new Content($this->app, $record['context']['contenttype'], $record['context']['new']);
        } else {
            $content = new Content($this->app, $record['context']['contenttype'], $record['context']['old']);
        }

        $title = $content->getTitle();
        if (empty($title)) {
            /** @var \Bolt\Content $content */
            $content = $this->app['storage']->getContent($record['context']['contenttype'] . '/' . $record['context']['id']);
            $title = $content->getTitle();
        }

        // Don't store datechanged, or records that are only datechanged
        unset($data['datechanged']);
        if (empty($data)) {
            return;
        }

        $str = json_encode($data);
        $user = $this->app['users']->getCurrentUser();

        try {
            $this->app['db']->insert(
                $this->tablename,
                array(
                    'date'          => $record['datetime']->format('Y-m-d H:i:s'),
                    'ownerid'       => $user['id'],
                    'title'         => $title,
                    'contenttype'   => $record['context']['contenttype'],
                    'contentid'     => $record['context']['id'],
                    'mutation_type' => $record['context']['action'],
                    'diff'          => $str,
                    'comment'       => $record['context']['comment']
                )
            );
        } catch (\Exception $e) {
            // Nothing.
        }
    }

    /**
     * Initialize class parameters.
     */
    private function initialize()
    {
        $this->tablename = sprintf("%s%s", $this->app['config']->get('general/database/prefix', "bolt_"), 'log_change');
        $this->allowed = array('INSERT', 'UPDATE', 'DELETE');
        $this->initialized = true;
    }
}
