<?php

namespace Bolt\Nut;

use Silex\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to dump system service provider registration boot order.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DebugServiceRegistration extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:services')
            ->setDescription('System service provider registration boot order debug dumper.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->displayRegistration($output);
    }

    /**
     * @param OutputInterface $output
     */
    private function displayRegistration(OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            '#',
            'Provider',
        ]);

        $reflect = new \ReflectionProperty(Application::class, 'providers');
        $reflect->setAccessible(true);
        $registrations = $reflect->getValue($this->app);

        $i = 1;
        foreach ($registrations as $registration) {
            $table->addRow([$i++, get_class($registration)]);
        }

        $table->render();
    }
}
