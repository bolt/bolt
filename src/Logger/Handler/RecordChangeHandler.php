<?php

namespace Bolt\Logger\Handler;

use Doctrine\DBAL\Schema\Schema;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

use Bolt\Application;
use Bolt\Content;
use Bolt\DeepDiff;
use Bolt\Helpers\String;
use Bolt\Logger\Formatter\RecordChange;

/**
 * Monolog Database handler for record changes (changelog)
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordChangeHandler extends AbstractProcessingHandler
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
     * @var array
     */
    private $allowed;

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
        $this->formatter = new RecordChange();
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
            $content = $this->getContent($record['context']['contenttype'] . '/' . $record['context']['id']);
            $title = $content->getTitle();
        }
        $str = json_encode($data);

        try {
            $this->app['db']->insert($this->tablename, array(
                'date'          => $record['datetime']->format('Y-m-d H:i:s'),
                'ownerid'       => $this->app['users']->getCurrentUser(),
                'title'         => $title,
                'contenttype'   => $record['context']['contenttype'],
                'contentid'     => $record['context']['id'],
                'mutation_type' => $record['context']['action'],
                'diff'          => $str,
                'comment'       => $record['context']['comment']
            ));
        } catch (\Exception $e) {
            // Nothing..
        }
    }



    /**
     * Writes a content-changelog entry.
     *
     * @param string $action Must be one of 'INSERT', 'UPDATE', or 'DELETE'.
     * @param string $contenttype The contenttype setting to log.
     * @param int $contentid ID of the content item to log.
     * @param array $newContent For 'INSERT' and 'UPDATE', the new content;
     *                          null for 'DELETE'.
     * @param array $oldContent For 'UPDATE' and 'DELETE', the current content;
     *                          null for 'INSTERT'.
     * For the 'UPDATE' and 'DELETE' actions, this function fetches the
     * previous data from the database; this means that you must call it
     * _before_ running the actual update/delete query; for the 'INSERT'
     * action, this is not necessary, and since you really want to provide
     * an ID, you can only really call the logging function _after_ the update.
     * @param string $comment Add a comment to save on change log.
     * @throws \Exception
     */
    private function writeChangelog($action, $contenttype, $id, $newContent = null, $oldContent = null, $comment = null)
    {
        array(
            'action' => '',
            'contenttype' => '',
            'id' => '',
            'new' => '',
            'old' => '',
            'comment' => '',
        );

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
        $this->tablename = sprintf("%s%s", $this->app['config']->get('general/database/prefix', "bolt_"), 'content_changelog');
        $this->allowed = array('INSERT', 'UPDATE', 'DELETE');
        $this->initialized = true;
    }

    /**
     *
     */
//     public function getFormatter()
//     {
//         if (!$this->formatter) {
//             $this->formatter = new RecordChange();
//         }

//         return $this->formatter;
//     }
}
