<?php

namespace Bolt\EventListener;

use Bolt\Events\QueryEvent;
use Bolt\Events\QueryEvents;
use Bolt\Pager\PagerManager;
use Bolt\Storage\Query\QueryResultset;
use Bolt\Storage\Query\SearchQueryResultset;
use Bolt\Storage\Query\TaxonomyQueryResultset;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PagerListener.
 *
 * @author Rix Beck <rix@neologik.hu>
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PagerListener implements EventSubscriberInterface
{
    protected $manager;

    /**
     * PagerListener constructor.
     *
     * @param PagerManager $manager
     */
    public function __construct(PagerManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Resume the session if it has been started previously or debugging is enabled.
     *
     * @param GetResponseEvent $event
     *
     * @throws \Bolt\Exception\PagerOverrideException
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->manager->initialize($event->getRequest());
    }

    public function onQueryExecute(QueryEvent $event)
    {
        $results = $event->getResult();
        if (!$results instanceof QueryResultset || $this->manager === null) {
            return;
        }
        if ($results instanceof TaxonomyQueryResultset) {
            $this->setTaxonomyPager($results);

            return;
        }

        $maxResults = 0;
        $maxPager = null;

        foreach ($results->getOriginalQueries() as $pagerName => $query) {
            if ($query->getMaxResults() === null) {
                continue;
            }

            $limitedResults = count($results->get($pagerName));
            if ($limitedResults < $query->getMaxResults()) {
                return;
            }

            $totalResults = count($this->getCountQuery($query));
            if ($totalResults > $maxResults) {
                $maxResults = $totalResults;
                $maxPager = $pagerName;
            }

            $this->setPager($query, $pagerName, $limitedResults, $totalResults);
        }

        if ($results instanceof SearchQueryResultset && $maxPager !== null) {
            $this->manager['search'] = $this->manager[$maxPager];
            $this->manager['search']->setFor('search');
            unset($this->manager[$maxPager]);
        }
    }

    /**
     * @param QueryBuilder $query
     * @param string       $pagerName
     * @param int          $limitedResults
     * @param int          $totalResults
     */
    private function setPager(QueryBuilder $query, $pagerName, $limitedResults, $totalResults)
    {
        $start = $query->getFirstResult() ?: 0;
        $currentPage = ($start + $query->getMaxResults()) / $query->getMaxResults();

        $this->manager->createPager($pagerName)
            ->setCount($totalResults)
            ->setTotalpages(ceil($totalResults / $query->getMaxResults()))
            ->setCurrent($currentPage)
            ->setShowingFrom($start + 1)
            ->setShowingTo($start + $limitedResults)
        ;
    }

    /**
     * @param TaxonomyQueryResultset $results
     */
    private function setTaxonomyPager(TaxonomyQueryResultset $results)
    {
        $originalQuery = $results->getOriginalQuery();
        $totalResults = $this->getCountQuery($originalQuery);
        if (count($totalResults)) {
            $totalResults = (int) array_values($totalResults[0])[0];
        }
        $start = $originalQuery->getFirstResult() ?: 0;
        $currentPage = ($start + $originalQuery->getMaxResults()) / $originalQuery->getMaxResults();

        $this->manager->createPager('taxonomy')
            ->setCount($totalResults)
            ->setTotalpages(ceil($totalResults / $originalQuery->getMaxResults()))
            ->setCurrent($currentPage)
            ->setShowingFrom($start + 1)
            ->setShowingTo($start + count($results->get()))
        ;
    }

    /**
     * @param QueryBuilder $query
     *
     * @return array
     */
    private function getCountQuery(QueryBuilder $query)
    {
        $queryCopy = clone $query;
        $queryCopy
            ->select('count(*)')
            ->setMaxResults(null)
            ->setFirstResult(null)
            ->resetQueryPart('orderBy')
        ;

        return $queryCopy->execute()->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest'],
            ],
            QueryEvents::EXECUTE => [
                ['onQueryExecute'],
            ],
        ];
    }
}
