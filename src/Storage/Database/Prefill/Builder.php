<?php

namespace Bolt\Storage\Database\Prefill;

use Bolt\Storage\EntityManager;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Exception\TableNotFoundException;
use GuzzleHttp\Exception\RequestException;

/**
 * Builder of pre-filled records for set of ContentTypes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Builder
{
    /** @var EntityManager */
    private $storage;
    /** @var callable */
    private $generatorFactory;
    /** @var int */
    private $maxCount;

    /**
     * Constructor.
     *
     * @param EntityManager $storage
     * @param callable      $generatorFactory
     * @param int           $maxCount
     */
    public function __construct(EntityManager $storage, callable $generatorFactory, $maxCount)
    {
        $this->storage = $storage;
        $this->generatorFactory = $generatorFactory;
        $this->maxCount = $maxCount;
    }

    /**
     * Build up-to 'n' number of pre-filled ContentType records.
     *
     * @param array $contentTypeNames
     * @param int   $count
     * @param bool  $canExceedMax
     *
     * @return array
     */
    public function build(array $contentTypeNames, $count, $canExceedMax = false)
    {
        $response = ['created' => null, 'errors' => null];
        foreach ($contentTypeNames as $contentTypeName) {
            try {
                $existingCount = $this->storage->getRepository($contentTypeName)->count();
            } catch (TableNotFoundException $e) {
                $response['errors'][$contentTypeName] = Trans::__(
                    'page.prefill.database-update-required',
                    ['%CONTENTTYPE%' => $contentTypeName]
                );

                continue;
            }

            // If we're over 'max' and we're not skipping "non empty" ContentTypes, show a notice and move on.
            if ($existingCount >= $this->maxCount && !$canExceedMax) {
                $response['errors'][$contentTypeName] = Trans::__(
                    'page.prefill.skipped-existing',
                    ['%key%' => $contentTypeName]
                );

                continue;
            }

            // Take the current amount of items into consideration, when adding more.
            $createCount = $canExceedMax ? $count : $count - $existingCount;
            if ($createCount < 1) {
                continue;
            }

            $recordContentGenerator = $this->createRecordContentGenerator($contentTypeName);
            try {
                $response['created'][$contentTypeName] = $recordContentGenerator->generate($createCount);
            } catch (RequestException $e) {
                $response['errors'][$contentTypeName] = Trans::__('page.prefill.connection-timeout');

                return $response;
            }
        }

        return $response;
    }

    /**
     * Return the maximum number of records allowed to exists before we stop
     * generating, or refuse to generate more records,
     *
     * @return int
     */
    public function getMaxCount()
    {
        return $this->maxCount;
    }

    /**
     * Override the maximum number of records allowed to exists before we stop
     * generating, or refuse to generate more records,
     *
     * @param int $maxCount
     *
     * @return Builder
     */
    public function setMaxCount($maxCount)
    {
        $this->maxCount = (int) $maxCount;

        return $this;
    }

    /**
     * Set a custom generator factory.
     *
     * @param callable $generatorFactory
     */
    public function setGeneratorFactory(callable $generatorFactory)
    {
        $this->generatorFactory = $generatorFactory;
    }

    /**
     * Create a generator for a specific ContentType, from the factory.
     *
     * @param string $contentTypeName
     *
     * @return RecordContentGenerator
     */
    protected function createRecordContentGenerator($contentTypeName)
    {
        $generatorFactory = $this->generatorFactory;

        return $generatorFactory($contentTypeName);
    }
}
