<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setDescription('Events, and target callable, debug dumper.')
            ->addOption('sort-callable', null, InputOption::VALUE_NONE, 'Sort events in order of callable name.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            [new TableCell('Events, and target callable', ['colspan' => 3])],
            ['Event Name', 'Priority', 'Callable'],
        ]);
        $dispatcher = $this->app['dispatcher'];
        $listeners = $dispatcher->getListeners();

        foreach ($listeners as $eventName => $eventListeners) {
            if ($input->getOption('sort-callable')) {
                uasort($eventListeners, function ($a, $b) {
                    $a = is_array($a) ? get_class($a[0]) : get_class($a);
                    $b = is_array($b) ? get_class($b[0]) : get_class($b);
                    if ($a === $b) {
                        return 0;
                    }

                    return ($a < $b) ? -1 : 1;
                });
            }
            foreach ($eventListeners as $callable) {
                $priority = $dispatcher->getListenerPriority($eventName, $callable);
                if (is_array($callable)) {
                    $table->addRow([
                        $eventName,
                        $priority,
                        sprintf('%s::%s()', get_class($callable[0]), $callable[1]),
                    ]);
                } else {
                    $table->addRow([$eventName, $priority, get_class($callable)]);
                }
            }
        }

        $table->render();
    }
}
