<?php

namespace Bolt\Storage\Database\Prefill;

use Bolt\Collection\Bag;
use Bolt\Collection\MutableBag;
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
    /** @var Bag */
    private $contentTypes;

    /**
     * Constructor.
     *
     * @param EntityManager $storage
     * @param callable      $generatorFactory
     * @param int           $maxCount
     * @param Bag           $contentTypes
     */
    public function __construct(EntityManager $storage, callable $generatorFactory, $maxCount, Bag $contentTypes)
    {
        $this->storage = $storage;
        $this->generatorFactory = $generatorFactory;
        $this->maxCount = $maxCount;
        $this->contentTypes = $contentTypes;
    }

    /**
     * Build up-to 'n' number of pre-filled ContentType records.
     *
     * @param array $contentTypeNames
     * @param int   $count
     * @param bool  $canExceedMax
     *
     * @return MutableBag
     */
    public function build(array $contentTypeNames, $count, $canExceedMax = false)
    {
        $response = MutableBag::fromRecursive(['created' => [], 'errors' => [], 'warnings' => []]);
        foreach ($contentTypeNames as $contentTypeName) {
            try {
                $existingCount = $this->storage->getRepository($contentTypeName)->count();
            } catch (TableNotFoundException $e) {
                $msg = Trans::__('page.prefill.database-update-required', ['%CONTENTTYPE%' => $contentTypeName]);
                $response->setPath('errors/' . $contentTypeName, $msg);

                continue;
            }

            // If we're over 'max' and we're not skipping "non empty" ContentTypes, show a notice and move on.
            if ($existingCount >= $this->maxCount && !$canExceedMax) {
                $msg = Trans::__('page.prefill.skipped-existing', ['%key%' => $contentTypeName]);
                $response->setPath('warnings/' . $contentTypeName, $msg);

                continue;
            }

            // Singletons are always limited to 1 item max.
            if ($this->contentTypes->getPath($contentTypeName . '/singleton')) {
                if ($existingCount > 0) {
                    $msg = Trans::__('page.prefill.skipped-singleton', ['%key%' => $contentTypeName]);
                    $response->setPath('warnings/' . $contentTypeName, $msg);
                } else {
                    $this->doBuild($contentTypeName, 1, $response);
                }
                continue;
            }

            $createCount = $canExceedMax ? $this->maxCount : $this->maxCount - $existingCount;
            $this->doBuild($contentTypeName, $createCount, $response);
        }

        return $response;
    }

    /**
     * @param string     $contentTypeName
     * @param int        $createCount
     * @param MutableBag $response
     */
    private function doBuild($contentTypeName, $createCount, MutableBag $response)
    {
        try {
            $recordContentGenerator = $this->createRecordContentGenerator($contentTypeName);
            $response->setPath('created/' . $contentTypeName, $recordContentGenerator->generate($createCount));
        } catch (RequestException $e) {
            $response->setPath('errors/' . $contentTypeName, Trans::__('page.prefill.connection-timeout'));
        }
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
