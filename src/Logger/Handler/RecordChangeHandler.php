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

        // Get the context values
        $context = $record['context'];

        // Check for a valid call
        $this->checkTransaction($context);

        // Get the context data
        $data = $this->getData($context);

        // Get the ContentType
        $contenttype = $context['contenttype'];
        if (!is_array($contenttype)) {
            $contenttype = $this->app['storage']->getContentType($contenttype);
        }

        // Get the content object.
        $values = $context['new'] ?: $context['old'];
        $content = $this->getContentObject($contenttype, $values);

        $title = $content->getTitle();
        if (empty($title)) {
            /** @var \Bolt\Content $content */
            $content = $this->app['storage']->getContent($context['contenttype'] . '/' . $context['id']);
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
                    'contenttype'   => $context['contenttype'],
                    'contentid'     => $context['id'],
                    'mutation_type' => $context['action'],
                    'diff'          => $str,
                    'comment'       => $context['comment']
                )
            );
        } catch (\Exception $e) {
            // Nothing.
        }
    }

    /**
     * Check that the requested log transaction is valid.
     *
     * @param array $context
     *
     * @throws \UnexpectedValueException
     */
    protected function checkTransaction(array $context)
    {
        if (!in_array($context['action'], $this->allowed)) {
            throw new \UnexpectedValueException("Invalid action '{$context['action']}' specified for changelog (must be one of [ " . implode(', ', $this->allowed) . " ])");
        }
        if (empty($context['old']) && empty($context['new'])) {
            throw new \UnexpectedValueException("Tried to log something that cannot be: both old and new content are empty");
        }
        if (empty($context['old']) && in_array($context['action'], array('UPDATE', 'DELETE'))) {
            throw new \UnexpectedValueException("Cannot log action '{$context['action']}' when old content doesn't exist");
        }
        if (empty($context['new']) && in_array($context['action'], array('INSERT', 'UPDATE'))) {
            throw new \UnexpectedValueException("Cannot log action '{$context['action']}' when new content is empty");
        }
    }

    /**
     * Get the context data.
     *
     * @param array $context
     *
     * @return array
     */
    protected function getData(array $context)
    {
        $data = array();
        switch ($context['action']) {
            case 'UPDATE':
                $diff = DeepDiff::diff($context['old'], $context['new']);
                foreach ($diff as $item) {
                    list($k, $old, $new) = $item;
                    if (isset($context['new'][$k])) {
                        $data[$k] = array($old, $new);
                    }
                }
                break;
            case 'INSERT':
                foreach ($context['new'] as $k => $val) {
                    $data[$k] = array(null, $val);
                }
                break;
            case 'DELETE':
                foreach ($context['old'] as $k => $val) {
                    $data[$k] = array($val, null);
                }
                break;
        }

        return $data;
    }

    /**
     * Get the content object.
     *
     * @param array $contenttype
     * @param array $values
     *
     * @return \Bolt\Content
     */
    protected function getContentObject(array $contenttype, array $values)
    {
        if (!empty($contenttype['class'])) {
            if (class_exists($contenttype['class'])) {
                $content = new $contenttype['class']($this->app, $contenttype, $values);

                if (!($content instanceof Content)) {
                    throw new \Exception($contenttype['class'] . ' does not extend \\Bolt\\Content.');
                }

                return $content;
            }

            $msg = sprintf('The ContentType %s has an invalid class specified. Unable to log the changes to its records', $contenttype['slug'], $contenttype['class']);
            $this->app['logger.system']->error($msg, array('event' => 'content'));
        } else {
            return new Content($this->app, $contenttype, $values);
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
