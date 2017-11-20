<?php

namespace Bolt\Logger\Handler;

use Bolt\Common\Json;
use Bolt\Exception\StorageException;
use Bolt\Legacy\Content;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Silex\Application;

/**
 * Monolog Database handler for record changes (changelog).
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordChangeHandler extends AbstractProcessingHandler
{
    /** @var Application */
    private $app;
    /** @var bool */
    private $initialized = false;
    /** @var string */
    private $tablename;
    /** @var array */
    private $allowed;

    /**
     * Constructor.
     *
     * @param Application $app
     * @param bool|int    $level
     * @param bool        $bubble
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
     * @return bool
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

        return $this->bubble === false;
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
        $title = $context['new'] ? $context['new']['title'] : $context['old']['title'];
        unset($data['bolt_csrf_token']);

        $contenttype = $context['contenttype'];

        // Don't store datechanged, or records that are only datechanged
        unset($data['datechanged']);
        if (empty($data)) {
            return;
        }

        $str = Json::dump($data);
        $user = $this->app['users']->getCurrentUser();

        $this->app['db']->insert(
            $this->tablename,
            [
                'date'          => $record['datetime']->format('Y-m-d H:i:s'),
                'ownerid'       => $user['id'],
                'title'         => $title,
                'contenttype'   => is_array($contenttype) ? $contenttype['slug'] : $contenttype,
                'contentid'     => $context['id'],
                'mutation_type' => $context['action'],
                'diff'          => $str,
                'comment'       => $context['comment'],
            ]
        );
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
            throw new \UnexpectedValueException("Invalid action '{$context['action']}' specified for changelog (must be one of [ " . implode(', ', $this->allowed) . ' ])');
        }
        if (empty($context['old']) && empty($context['new'])) {
            throw new \UnexpectedValueException('Tried to log something that cannot be: both old and new content are empty');
        }
        if (empty($context['old']) && in_array($context['action'], ['UPDATE', 'DELETE'])) {
            throw new \UnexpectedValueException("Cannot log action '{$context['action']}' when old content doesn't exist");
        }
        if (empty($context['new']) && in_array($context['action'], ['INSERT', 'UPDATE'])) {
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
        $data = [];
        switch ($context['action']) {
            case 'UPDATE':
                $diff = $this->diff($context['old'], $context['new']);
                foreach ($diff as $item) {
                    list($k, $old, $new) = $item;
                    if (isset($context['new'][$k])) {
                        $data[$k] = [$old, $new];
                    }
                }
                break;
            case 'INSERT':
                foreach ($context['new'] as $k => $val) {
                    $data[$k] = [null, $val];
                }
                break;
            case 'DELETE':
                foreach ($context['old'] as $k => $val) {
                    $data[$k] = [$val, null];
                }
                break;
        }

        return $data;
    }

    /**
     * Get the content object.
     *
     * @deprecated Deprecated since 3.3. To be removed in v4.
     *
     * @param array $contenttype
     * @param array $values
     *
     * @throws StorageException
     *
     * @return Content
     */
    protected function getContentObject(array $contenttype, array $values)
    {
        if (empty($contenttype['class'])) {
            return new Content($this->app, $contenttype, $values);
        }

        if (class_exists($contenttype['class'])) {
            $content = new $contenttype['class']($this->app, $contenttype, $values);

            if (!($content instanceof Content)) {
                throw new StorageException($contenttype['class'] . ' does not extend \\Bolt\\Content.');
            }

            return $content;
        }

        $msg = sprintf('The ContentType %s has an invalid class specified "%s". Unable to log the changes to its records', $contenttype['slug'], $contenttype['class']);
        $this->app['logger.system']->error($msg, ['event' => 'content']);

        throw new StorageException($msg);
    }

    /**
     * Initialize class parameters.
     */
    private function initialize()
    {
        $this->tablename = sprintf('%s%s', $this->app['config']->get('general/database/prefix', 'bolt_'), 'log_change');
        $this->allowed = ['INSERT', 'UPDATE', 'DELETE'];
        $this->initialized = true;
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return array [key, left, right][]
     */
    private function diff(array $a, array $b)
    {
        if (empty($a)) {
            $a = [];
        }
        if (empty($b)) {
            $b = [];
        }
        $keys = array_keys($a + $b);
        $result = [];

        foreach ($keys as $k) {
            if (empty($a[$k])) {
                $l = null;
            } else {
                $l = $a[$k];
            }
            if (empty($b[$k])) {
                $r = null;
            } else {
                $r = $b[$k];
            }
            if ($l != $r) {
                $result[] = [$k, $l, $r];
            }
        }

        return $result;
    }
}
