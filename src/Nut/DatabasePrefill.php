<?php

namespace Bolt\Nut;

use Bolt\Exception\InvalidRepositoryException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Database pre-fill command
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
            ->setDescription('Pre-fill the database Lorem Ipsum records')
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
            $contentTypeNames = $this->getContentTypeNames($output);
        }

        if (!$input->getOption('no-interaction')) {
            $output->writeln('<question>You are about to create dummy records for the following ContentTypes:</question>');
            foreach ($contentTypeNames as $contentTypeName) {
                $output->writeln(sprintf('<question>  - %s</question>', $contentTypeName));
            }
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>Continue with this action?</question> ', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        $builder = $this->app['prefill.builder'];
        $results = $builder->build($contentTypeNames, 5);

        $this->auditLog(__CLASS__, 'Database pre-filled');

        $this->reportCreate($results, $output);
        $this->reportError($results, $output);
    }

    /**
     * @param array           $results
     * @param OutputInterface $output
     */
    private function reportError(array $results, OutputInterface $output)
    {
        if ($results['errors'] === null) {
            return;
        }
        $output->writeln('<error>Failures:</error>');
        foreach ($results['errors'] as $errors) {
            $output->writeln(sprintf('<error>    - %s</error>', strip_tags($errors)));
        }
        $output->writeln('');
    }

    /**
     * @param array           $results
     * @param OutputInterface $output
     */
    private function reportCreate(array $results, OutputInterface $output)
    {
        if ($results['created'] === null) {
            return;
        }
        $output->writeln('<info>Database pre-filled with the following ContentTypes:</info>');
        foreach ($results['created'] as $contentTypeName => $data) {
            $output->writeln(sprintf('<info>  - %s</info>', $contentTypeName));

            foreach ($data as $created) {
                $output->writeln(sprintf('<info>    - %s</info>', $created['title']));
            }
        }
    }

    /**
     * @param OutputInterface $output
     *
     * @return array
     */
    private function getContentTypeNames(OutputInterface $output)
    {
        $contentTypes = $this->app['config']->get('contenttypes');
        $contentTypeNames = array_keys($contentTypes);

        foreach ($contentTypeNames as $key => $contentTypeName) {
            try {
                $this->app['storage']->getRepository($contentTypeName);
            } catch (InvalidRepositoryException $e) {
                unset($contentTypeNames[$key]);
                $message = sprintf(
                    '<error>%sContentType "%s" does not have a repository enabled. You many need to run database:update first.%s</error>',
                    PHP_EOL.
                    $contentTypeName.
                    PHP_EOL
                );
                $output->writeln($message);
            }
        }

        return $contentTypeNames;
    }
}
