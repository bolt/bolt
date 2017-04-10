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
     *
     * @return null
     */
    public function build(array $contentTypeNames, $count)
    {
        $response = ['created' => null, 'errors' => null];
        foreach ($contentTypeNames as $contentTypeName) {
            try {
                $existingCount = $this->storage->getRepository($contentTypeName)->count();
            } catch (TableNotFoundException $e) {
                $response['errors'][$contentTypeName] = Trans::__(
                    'Table not found for ContentType %CONTENTTYPE%, a database update is probably required.',
                    ['%CONTENTTYPE%' => $contentTypeName]
                );

                continue;
            }

            if ($existingCount >= $this->maxCount) {
                $response['errors'][$contentTypeName] = Trans::__(
                    'Skipped <tt>%key%</tt> (already has records)',
                    ['%key%' => $contentTypeName]
                );

                continue;
            }

            $count -= $existingCount;
            if ($count > 0) {
                $recordContentGenerator = $this->createRecordContentGenerator($contentTypeName);
                try {
                    $response['created'][$contentTypeName] = $recordContentGenerator->generate($count);
                } catch (RequestException $e) {
                    $response['errors'][$contentTypeName] = Trans::__(
                        "Timeout attempting connection to the 'Lorem Ipsum' generator. Unable to add dummy content."
                    );

                    return $response;
                }
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
