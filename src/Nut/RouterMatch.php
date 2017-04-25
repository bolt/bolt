<?php

namespace Bolt\Nut;

use Silex\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Matcher\TraceableUrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * A console command to test route matching.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RouterMatch extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('router:match')
            ->setDescription('Helps debug routes by simulating a URI path match')
            ->addArgument('path_info', InputArgument::REQUIRED, 'A relative URI path')
            ->addOption('method', null, InputOption::VALUE_REQUIRED, 'Sets the HTTP method')
            ->addOption('scheme', null, InputOption::VALUE_REQUIRED, 'Sets the URI scheme (usually http or https)')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Sets the URI host')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> shows which routes match a given request and which don't and for what reason:

  <info>php %command.full_name% /foo</info>

or

  <info>php %command.full_name% /foo --method POST --scheme https --host bolt.cm --verbose</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->app instanceof Application) {
            $this->app->flush();
        }

        /** @var RouteCollection $router */
        $router = $this->app['routes'];
        $context = new RequestContext($input->getArgument('path_info'));
        if (null !== $method = $input->getOption('method')) {
            $context->setMethod($method);
        }
        if (null !== $scheme = $input->getOption('scheme')) {
            $context->setScheme($scheme);
        }
        if (null !== $host = $input->getOption('host')) {
            $context->setHost($host);
        }

        $matcher = new TraceableUrlMatcher($router, $context);

        $traces = $matcher->getTraces($input->getArgument('path_info'));

        $this->io->newLine();

        $matches = false;
        foreach ($traces as $trace) {
            if (TraceableUrlMatcher::ROUTE_ALMOST_MATCHES == $trace['level']) {
                $this->io->text(sprintf('Route <info>"%s"</> almost matches but %s', $trace['name'], lcfirst($trace['log'])));
            } elseif (TraceableUrlMatcher::ROUTE_MATCHES == $trace['level']) {
                $this->io->success(sprintf('Route "%s" matches', $trace['name']));

                $routerDebugCommand = $this->getApplication()->find('debug:router');
                $routerDebugCommand->run(new ArrayInput(['name' => $trace['name']]), $output);

                $matches = true;
            } elseif ($input->getOption('verbose')) {
                $this->io->text(sprintf('Route "%s" does not match: %s', $trace['name'], $trace['log']));
            }
        }

        if (!$matches) {
            $this->io->error(sprintf('None of the routes match the path "%s"', $input->getArgument('path_info')));

            return 1;
        }

        return 0;
    }
}
