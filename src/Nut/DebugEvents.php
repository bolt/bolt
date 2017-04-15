<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->addOption('sort-listener', null, InputOption::VALUE_NONE, 'Sort events in order of callable name.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dispatcher = $this->app['dispatcher'];
        $listeners = $dispatcher->getListeners();
        $eventArg = $input->getArgument('event');

        $io = new SymfonyStyle($input, $output);

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
                $io->title('Registered Listeners for "' . $eventName . '" Event');
            } else {
                $io->section('"' . $eventName . '" event');
            }

            $i = 1;
            $table = $this->getTable($output);
            foreach ($eventListeners as $callable) {
                $priority = $dispatcher->getListenerPriority($eventName, $callable);
                if (is_array($callable)) {
                    $table->addRow([
                        '#' .  $i++,
                        sprintf('%s::%s()', get_class($callable[0]), $callable[1]),
                        $priority,
                    ]);
                } else {
                    $table->addRow(['#' .  $i++, get_class($callable), $priority]);
                }
            }
            $table->render();
            $output->writeln('');
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
