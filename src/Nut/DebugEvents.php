<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to dump system listened events, and target callable.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DebugEvents extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:events')
            ->setDescription('System events, and target callable debug dumper.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->displayListeners($output);
    }

    /**
     * Output a table of listeners, and their callbacks.
     *
     * @param OutputInterface $output
     */
    private function displayListeners(OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(array(
            'Event Name',
            'Priority',
            'Listener',
        ));
        $dispatcher = $this->app['dispatcher'];
        $listeners = $dispatcher->getListeners();

        foreach ($listeners as $eventName => $eventListeners) {
            foreach ($eventListeners as $callable) {
                $priority = $dispatcher->getListenerPriority($eventName, $callable);
                if (is_array($callable)) {
                    $table->addRow([
                        $eventName,
                        $priority,
                        sprintf('%s::%s', get_class($callable[0]), $callable[1]),
                    ]);
                } else {
                    $table->addRow([$eventName, $priority, get_class($callable)]);
                }
            }
        }

        $table->render();
    }
}
