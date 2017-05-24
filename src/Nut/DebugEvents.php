<?php

namespace Bolt\Nut;

use Closure;
use ReflectionFunction;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setDescription('Dumps event listeners.')
            ->addArgument('event', InputArgument::OPTIONAL, 'An event name')
            ->addOption('sort-listener', null, InputOption::VALUE_NONE, 'Sort events in order of callable name')
            ->addOption('summary', null, InputOption::VALUE_NONE, 'Summary list of the event names listened to')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dispatcher = $this->app['dispatcher'];
        $listeners = $dispatcher->getListeners();

        if ($input->getOption('summary')) {
            $this->io->title('Event Names Registered on Dispatcher');
            $this->io->listing(array_keys($listeners));

            return 0;
        }

        $eventArg = $input->getArgument('event');
        foreach ($listeners as $eventName => $eventListeners) {
            if ($eventArg && $eventName !== $eventArg) {
                continue;
            }

            if ($input->getOption('sort-listener')) {
                uasort($eventListeners, function ($a, $b) {
                    $a = is_array($a) ? get_class($a[0]) : get_class($a);
                    $b = is_array($b) ? get_class($b[0]) : get_class($b);
                    if ($a === $b) {
                        return 0;
                    }

                    return ($a < $b) ? -1 : 1;
                });
            }

            if ($eventArg) {
                $this->io->title('Registered Listeners for "' . $eventName . '" Event');
            } else {
                $this->io->section('"' . $eventName . '" event');
            }

            $table = $this->getTable($output);
            foreach ($eventListeners as $order => $callable) {
                ++$order;
                $priority = $dispatcher->getListenerPriority($eventName, $callable);
                if (is_array($callable)) {
                    $table->addRow([
                        '#' . $order,
                        sprintf('%s::%s()', get_class($callable[0]), $callable[1]),
                        $priority,
                    ]);
                } elseif ($callable instanceof Closure) {
                    $r = new ReflectionFunction($callable);
                    $originClass = $r->getClosureScopeClass()->getName() . ' ' . $r->getShortName();
                    $table->addRow(['#' . $order, $originClass, $priority]);
                } else {
                    $table->addRow(['#' . $order, get_class($callable), $priority]);
                }
            }
            $table->render();
            $this->io->writeln('');
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     *
     * @return Table
     */
    protected function getTable(OutputInterface $output)
    {
        $table = new Table($output);

        $leftAligned = new TableStyle();
        $leftAligned->setPadType(STR_PAD_LEFT);
        $table->setColumnStyle(0, $leftAligned);

        $rightAligned = new TableStyle();
        $rightAligned->setPadType(STR_PAD_LEFT);
        $table->setColumnStyle(2, $rightAligned);

        $table->setHeaders([
            ['Order', 'Callable', 'Priority'],
        ]);

        return $table;
    }
}
