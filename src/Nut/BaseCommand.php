<?php

namespace Bolt\Nut;

use Silex\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;

/**
 * Nut building block
 */
abstract class BaseCommand extends Command
{
    /** @var Application */
    protected $app;
    /** @var SymfonyStyle */
    protected $io;

    /**
     * @param \Silex\Application $app
     * @param Request            $request Reserved for tests
     */
    public function __construct(Application $app, Request $request = null)
    {
        parent::__construct();
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Log a Nut execution if auditing is on
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
