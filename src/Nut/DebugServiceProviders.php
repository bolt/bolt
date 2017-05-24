<?php

namespace Bolt\Nut;

use Silex\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to dump system provider registration order.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DebugServiceProviders extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:service-providers')
            ->setAliases(['debug:providers'])
            ->setDescription('Dumps service providers and their order.')
            ->addOption('sort-class', null, InputOption::VALUE_NONE, 'Sort by provider class names.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $rightAligned = new TableStyle();
        $rightAligned->setPadType(STR_PAD_LEFT);

        $table->setHeaders([['Provider Class Name', 'Order']]);
        $table->setColumnStyle(1, $rightAligned);

        $reflect = new \ReflectionProperty(Application::class, 'providers');
        $reflect->setAccessible(true);
        $registrations = $reflect->getValue($this->app);

        if ($input->getOption('sort-class')) {
            uasort($registrations, function ($a, $b) {
                $a = get_class($a);
                $b = get_class($b);
                if ($a === $b) {
                    return 0;
                }

                return ($a < $b) ? -1 : 1;
            });
        }

        foreach ($registrations as $index => $registration) {
            $table->addRow([get_class($registration), $index + 1]);
        }

        $table->render();
    }
}
