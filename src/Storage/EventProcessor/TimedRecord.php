<?php

namespace Bolt\Storage\EventProcessor;

use Bolt\Events\StorageEvent;
use Bolt\Exception\InvalidRepositoryException;
use Bolt\Exception\StorageException;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManagerInterface;
use Bolt\Storage\Repository\ContentRepository;
use Carbon\Carbon;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Timed record (de)publishing handler.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TimedRecord
{
    const CACHE_KEY_PUBLISH = 'publish.timer.publish';
    const CACHE_KEY_HOLD = 'publish.timer.hold';

    /** @var array */
    protected $contentTypeNames;
    /** @var  EntityManagerInterface */
    protected $em;
    /** @var CacheProvider */
    protected $cache;
    /** @var EventDispatcherInterface */
    protected $dispatcher;
    /** @var LoggerInterface */
    protected $systemLogger;
    /** @var integer */
    protected $interval;

    /**
     * Constructor.
     *
     * @param array                    $contentTypeNames
     * @param EntityManagerInterface   $em
     * @param CacheProvider            $cache
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $systemLogger
     * @param integer                  $interval
     */
    public function __construct(
        array $contentTypeNames,
        EntityManagerInterface $em,
        CacheProvider $cache,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $systemLogger,
        $interval
    ) {
        $this->contentTypeNames = $contentTypeNames;
        $this->em = $em;
        $this->cache = $cache;
        $this->dispatcher = $dispatcher;
        $this->systemLogger = $systemLogger;
        $this->interval = $interval;
    }

    /**
     * Get the timer for publishing timed records
     */
    public function isDuePublish()
    {
        return !$this->cache->contains(self::CACHE_KEY_PUBLISH);
    }

    /**
     * Get the timer for publishing timed records
     */
    public function isDueHold()
    {
        return !$this->cache->contains(self::CACHE_KEY_HOLD);
    }

    /**
     * Check (and update) any records that need to be updated from "timed" to "published".
     */
    public function publishTimedRecords()
    {
        foreach ($this->contentTypeNames as $contentTypeName) {
            $this->timedHandleRecords($contentTypeName, 'timed');
        }
        $this->cache->save(self::CACHE_KEY_PUBLISH, true, $this->interval);
    }

    /**
     * Check (and update) any records that need to be updated from "published" to "held".
     */
    public function holdExpiredRecords()
    {
        foreach ($this->contentTypeNames as $contentTypeName) {
            $this->timedHandleRecords($contentTypeName, 'hold');
        }
        $this->cache->save(self::CACHE_KEY_HOLD, true, $this->interval);
    }

    /**
     * Handle any pending timed publish/hold transitions.
     *
     * @param string $contentTypeName
     * @param string $type
     */
    private function timedHandleRecords($contentTypeName, $type)
    {
        try {
            /** @var ContentRepository $contentRepo */
            $contentRepo = $this->em->getRepository($contentTypeName);
        } catch (InvalidRepositoryException $e) {
            // ContentType doesn't have a repository
            return;
        }

        $types = [
            'timed' => [
                'target' => 'published',
                'legacy' => 'publish',
            ],
            'hold' => [
                'target' => 'held',
                'legacy' => 'depublish',
            ],
        ];

        try {
            $records = $this->getTimedRecords($contentRepo, $type);
        } catch (TableNotFoundException $e) {
            return;
        }
        /** @var Content $content */
        foreach ($records as $content) {
            $content->set('status', $types[$type]['target']);
            $this->save($contentRepo, $content, $type, $types[$type]['legacy']);
        }
    }

    /**
     * Save a modified entity.
     *
     * @param ContentRepository $contentRepo
     * @param Content           $content
     * @param string            $type
     * @param string            $legacyType
     */
    private function save(ContentRepository $contentRepo, Content $content, $type, $legacyType)
    {
        try {
            $contentRepo->save($content);
            $this->dispatch($content, $type, $legacyType);
        } catch (DBALException $e) {
            $contentTypeName = $contentRepo->getClassMetadata()->getBoltName();
            $message = "Timed update of records for $contentTypeName failed: " . $e->getMessage();

            $this->systemLogger->critical($message, ['event' => 'exception', 'exception' => $e]);
        }
    }

    /**
     * Dispatch the update event.
     *
     * @param Content $content
     * @param string  $type
     * @param string  $legacyType
     */
    private function dispatch(Content $content, $type, $legacyType)
    {
        $event = new StorageEvent($content, ['contenttype' => $content->getContenttype(), 'create' => false]);
        try {
            $this->dispatcher->dispatch("timed.$type", $event);
        } catch (\Exception $e) {
            $this->systemLogger->critical(sprintf('Dispatch handling failed for %s.', $content->getContenttype()), ['event' => 'exception', 'exception' => $e]);
        }
        try {
            /** @deprecated Deprecated since 3.1, to be removed in 4.0. */
            $this->dispatcher->dispatch("timed.$legacyType", $event);
        } catch (\Exception $e) {
            $this->systemLogger->critical(sprintf('Dispatch handling failed for %s.', $content->getContenttype()), ['event' => 'exception', 'exception' => $e]);
        }
    }

    /**
     * Set the QueryBuilder where parameters.
     *
     * @param ContentRepository $contentRepo
     * @param string            $type
     *
     * @throws \Exception
     *
     * @return Content[]|false
     */
    private function getTimedRecords(ContentRepository $contentRepo, $type)
    {
        /** @var QueryBuilder $query */
        $query = $contentRepo->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('currenttime', Carbon::now(), Type::DATETIME)
        ;

        if ($type === 'timed') {
            $this->getTimedPublishQuery($query);
        } elseif ($type === 'hold') {
            $this->getHoldQuery($query);
        } else {
            throw new StorageException(sprintf('Invalid type "%s" for timed record processing.', $type));
        }

        return $contentRepo->findWith($query) ?: [];
    }

    /**
     * Set the QueryBuilder where parameters.
     *
     * @param QueryBuilder $query
     */
    private function getTimedPublishQuery(QueryBuilder $query)
    {
        $query
            ->where('status = :status')
            ->andWhere('datepublish < :currenttime')
            ->setParameter('status', 'timed')
        ;
    }

    /**
     * Set the QueryBuilder where parameters.
     *
     * @param QueryBuilder $query
     */
    private function getHoldQuery(QueryBuilder $query)
    {
        $query
            ->where('datedepublish <= :currenttime')
            ->andWhere('datedepublish > :zeroday')
            ->andWhere('datechanged < datedepublish')
            ->setParameter('status', 'published')
            ->setParameter('zeroday', '1900-01-01 00:00:01')
        ;
    }
}
