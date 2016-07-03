<?php
namespace Bolt\Storage\Entity;

/**
 * Trait class for ContentType search.
 *
 * This is a breakout of the old Bolt\Content class and serves two main purposes:
 *   * Maintain backward compatibility for Bolt\Content through the remainder of
 *     the 2.x development/release life-cycle
 *   * Attempt to break up former functionality into sections of code that more
 *     resembles Single Responsibility Principles
 *
 * These traits should be considered transitional, the functionality in the
 * process of refactor, and not representative of a valid approach.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentSearchTrait
{
    /** @var integer The last time we weight a searchresult */
    protected $lastWeight = 0;

    /**
     * Get the content's query weight… and something to eat… it looks hungry.
     *
     * @return integer
     */
    public function getSearchResultWeight()
    {
        return $this->lastWeight;
    }

    /**
     * Weigh this content against a query.
     *
     * The query is assumed to be in a format as returned by decode Storage->decodeSearchQuery().
     *
     * @param array $query Query to weigh against
     *
     * @return void
     */
    public function weighSearchResult($query)
    {
        static $contenttypeFields = null;
        static $contenttypeTaxonomies = null;

        $ct = $this->contenttype['slug'];
        if ((is_null($contenttypeFields)) || (!isset($contenttypeFields[$ct]))) {
            // Should run only once per contenttype (e.g. singular_name)
            $contenttypeFields[$ct] = $this->getFieldWeights();
            $contenttypeTaxonomies[$ct] = $this->getTaxonomyWeights();
        }

        $weight = 0;

        // Go over all field, and calculate the overall weight.
        foreach ($contenttypeFields[$ct] as $key => $fieldWeight) {
            $value = $this->values[$key];
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $weight += $this->weighQueryText($value, $query['use_q'], $query['words'], $fieldWeight);
        }

        // Go over all taxonomies, and calculate the overall weight.
        foreach ($contenttypeTaxonomies[$ct] as $key => $taxonomy) {
            // skip empty taxonomies.
            if (empty($this->taxonomy[$key])) {
                continue;
            }
            $weight += $this->weighQueryText(implode(' ', $this->taxonomy[$key]), $query['use_q'], $query['words'], $taxonomy);
        }

        $this->lastWeight = $weight;
    }

    /**
     * Calculate the default field weights.
     *
     * This gives more weight to the 'slug pointer fields'.
     *
     * @return array
     */
    private function getFieldWeights()
    {
        // This could be more configurable
        // (see also Storage->searchSingleContentType)
        $searchableTypes = ['text', 'textarea', 'html', 'markdown'];

        $fields = [];
        $slugFields = [];

        // The field(s) that are used by the slug need to be bumped, unless configured explicitly
        foreach ($this->contenttype['fields'] as $config) {
            if ($config['type'] === 'slug' && isset($config['uses'])) {
                $slugFields = (array) $config['uses'];
            }
        }

        // Set the searchweights to the configured value, otherwise default to '50' or '100'
        foreach ($this->contenttype['fields'] as $key => $config) {
            if (in_array($config['type'], $searchableTypes)) {
                $defaultValue = in_array($key, $slugFields) ? 100 : 50;
                $fields[$key] = isset($config['searchweight']) ? $config['searchweight'] : $defaultValue;
            }
        }

        return $fields;
    }

    /**
     * Calculate the default taxonomy weights.
     *
     * Adds weights to taxonomies that behave like tags.
     *
     * @return array
     */
    private function getTaxonomyWeights()
    {
        $taxonomies = [];

        if (isset($this->contenttype['taxonomy'])) {
            foreach ($this->contenttype['taxonomy'] as $key) {
                if ($this->app['config']->get('taxonomy/' . $key . '/behaves_like') === 'tags') {
                    $taxonomies[$key] = $this->app['config']->get('taxonomy/' . $key . '/searchweight', 75);
                }
            }
        }

        return $taxonomies;
    }

    /**
     * Weight a text part relative to some other part.
     *
     * @param string  $subject  The subject to search in.
     * @param string  $complete The complete search term (lowercased).
     * @param array   $words    All the individual search terms (lowercased).
     * @param integer $max      Maximum number of points to return.
     *
     * @return integer The weight
     */
    private function weighQueryText($subject, $complete, $words, $max)
    {
        $lowSubject = mb_strtolower(trim($subject));

        if ($lowSubject === $complete) {
            // a complete match is 100% of the maximum
            return round((100 / 100) * $max);
        }
        if (strstr($lowSubject, $complete)) {
            // when the whole query is found somewhere is 70% of the maximum
            return round((70 / 100) * $max);
        }

        $wordMatches = 0;
        $cntWords = count($words);
        for ($i = 0; $i < $cntWords; $i++) {
            if (strstr($lowSubject, $words[$i])) {
                $wordMatches++;
            }
        }
        if ($wordMatches > 0) {
            // marcel: word matches are maximum of 50% of the maximum per word
            // xiao: made (100/100) instead of (50/100).
            return round(($wordMatches / $cntWords) * (100 / 100) * $max);
        }

        return 0;
    }
}
