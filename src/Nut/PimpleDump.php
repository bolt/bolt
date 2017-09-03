<?php

namespace Bolt\Nut;

use Sorien\Provider\PimpleDumpProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pimple container dumper command for PhpStorm & IntelliJ IDEA.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PimpleDump extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return class_exists(PimpleDumpProvider::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('pimple:dump')
            ->setDescription('Pimple container dumper for PhpStorm & IntelliJ IDEA.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Dumping Pimple application container');

        $app = $this->app;
        $app['debug'] = true;

        $dumper = new PimpleDumpProvider();
        $app->register($dumper);
        $dumper->boot($app);

        $request = Request::create('/');
        $response = $app->handle($request);
        $app->terminate($request, $response);

        $this->io->success('Dumped container information in pimple.json');

        return 0;
    }
}
