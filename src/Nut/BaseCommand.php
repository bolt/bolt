<?php

namespace Bolt\Nut;

use Bolt\Nut\Helper\ContainerHelper;
use Bolt\Nut\Style\NutStyle;
use Pimple as Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut building block.
 */
abstract class BaseCommand extends Command
{
    /** @var Container */
    protected $app;
    /** @var NutStyle */
    protected $io;

    /**
     * Constructor.
     *
     * @param Container|null $app
     */
    public function __construct(Container $app = null)
    {
        parent::__construct();
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (!$this->app) {
            /** @var ContainerHelper $helper */
            $helper = $this->getHelper('container');
            $this->app = $helper->getContainer();
        }

        $this->io = new NutStyle($input, $output);
    }

    /**
     * Log a Nut execution if auditing is on.
     *
     * @param string $source  __CLASS__ of caller
     * @param string $message Message to log
     */
    protected function auditLog($source, $message)
    {
        if ($this->app['config']->get('general/auditlog/enabled', true)) {
            $this->app['logger.system']->info($message, ['event' => 'nut', 'source' => $source]);
        }
    }
}
