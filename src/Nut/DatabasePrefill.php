<?php

namespace Bolt\Nut;

use Bolt\Collection\Bag;
use Bolt\Exception\InvalidRepositoryException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Database pre-fill command.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabasePrefill extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:prefill')
            ->setDescription('Pre-fill the database with "Lorem Ipsum" records')
            ->addArgument('contenttypes', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'A list of ContentTypes to pre-fill. If this argument is empty, all ContentTypes are used.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contentTypeNames = (array) $input->getArgument('contenttypes');
        if (empty($contentTypeNames)) {
            $contentTypeNames = $this->getContentTypeNames();
        }

        if (!$input->getOption('no-interaction')) {
            $this->io->title('Creating dummy records for the following ContentTypes');
            $this->io->listing($contentTypeNames);
            if (!$this->io->confirm('Continue with this action?', false)) {
                return 0;
            }
        }

        $builder = $this->app['prefill.builder'];
        $results = $builder->build($contentTypeNames, 5);

        $this->auditLog(__CLASS__, 'Database pre-filled');

        $this->reportCreate($results);
        $this->reportError($results);
        $this->reportWarn($results);

        return $results->get('errors')->count();
    }

    /**
     * @param Bag $results
     */
    private function reportWarn(Bag $results)
    {
        /** @var Bag $warnings */
        $warnings = $results->get('warnings');
        if ($warnings->count() === 0) {
            return;
        }
        $this->io->note($warnings->map(function ($k, $v) { return strip_tags($v); })->toArray());
    }

    /**
     * @param Bag $results
     */
    private function reportError(Bag $results)
    {
        $errors = $results->get('errors');
        if ($errors->count() === 0) {
            return;
        }
        $this->io->error($errors->map(function ($k, $v) { return strip_tags($v); })->toArray());
    }

    /**
     * @param Bag $results
     */
    private function reportCreate(Bag $results)
    {
        if ($results->get('created') === null) {
            return;
        }
        $this->io->title('Database pre-filled with the following ContentTypes');
        foreach ($results->get('created') as $contentTypeName => $data) {
            $this->io->writeln(sprintf('<info>  - %s</info>', $contentTypeName));
            foreach ($data as $created) {
                $this->io->writeln(sprintf('<info>    - %s</info>', $created['title']));
            }
        }
    }

    /**
     * @return array
     */
    private function getContentTypeNames()
    {
        $contentTypes = $this->app['config']->get('contenttypes');
        $contentTypeNames = array_keys($contentTypes);

        foreach ($contentTypeNames as $key => $contentTypeName) {
            try {
                $this->app['storage']->getRepository($contentTypeName);
            } catch (InvalidRepositoryException $e) {
                unset($contentTypeNames[$key]);
                $message = sprintf(
                    'ContentType "%s" does not have a repository enabled. You many need to run database:update first.',
                    $contentTypeName
                );
                $this->io->note($message);
            }
        }

        return $contentTypeNames;
    }
}
