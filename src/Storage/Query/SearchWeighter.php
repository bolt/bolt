<?php

namespace Bolt\Storage\Query;

/**
 * This class takes a fetched resultset and sorts them based on the weighting
 * settings in the SearchConfig class.
 */
class SearchWeighter
{
    protected $config;

    protected $results;

    protected $searchWords;

    protected $contenttype;

    /**
     * Constructor takes a compiled SearchConfig which is essentially an array
     * of fields that we will search for text content, along with their corresponding
     * weighting score.
     *
     * @param SearchConfig $config
     */
    public function __construct(SearchConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Sets an iterable group of results, this normally comes directly
     * from the database query.
     *
     * @param QueryResultset $results
     */
    public function setResults(array $results)
    {
        $this->results = $results;
    }

    /**
     * Sets the contenttype that we are weighting, that is, what type the results
     * array is. That allows us to map against the configuration to see which fields
     * to scan for relevant text.
     *
     * @param string $type
     */
    public function setContentType($type)
    {
        $this->contenttype = $type;
    }

    /**
     * Sets the words that we want to query against. Normally this comes from the
     * filter in a search, exploded into an array so the words are separated.
     *
     * @param array $words
     */
    public function setSearchWords(array $words)
    {
        $this->searchWords = $words;
    }

    /**
     * This is the public method that gets a score for a the set of results.
     *
     * @return array An array of scores for each of the corresponding results
     */
    public function weight()
    {
        $scores = [];
        foreach ($this->results as $result) {
            $scores[] = $this->getResultScore($result);
        }

        return $scores;
    }

    /**
     * Helper method to fetch the fields for an individual contenttype.
     *
     * @return [type] [description]
     */
    protected function getContentFields()
    {
        return $this->config->getConfig($this->contenttype);
    }

    /**
     * This is a simple version of the Vector Space Model.
     *
     *   @link https://en.wikipedia.org/wiki/Vector_space_model
     *
     * The goal is to determine a relavance score for a corpus of values
     * based on both the existence of a word or words but also based on
     * how important the words are.
     *
     * For example, when querying results against a search of 'lorem ipsum';
     * a result with the title 'Lorem Ipsum' should score higher
     * than a result with the title 'An article about robots and lorem ipsum'
     *
     * The ratio of the appearance of the query words to the overall size of
     * the document is used to produce a better score.
     *
     * @param Object $result A single result to score
     *
     * @return array An array consisting of a count / dictionary of words
     */
    protected function buildResultIndex($result)
    {
        $corpus = [];

        foreach ($this->getContentFields() as $field => $weightings) {
            $textualContent = $result->{$field};

            // This is to handle taxonomies that need to be converted from an array
            // into a string of words.
            if (is_array($textualContent)) {
                $textualContent = implode(' ', $textualContent);
            }

            $textualContent = strip_tags($textualContent);
            $textualContent = preg_replace('/[^\w\s]/', '', $textualContent);
            $textualContent = mb_strtolower($textualContent);
            $corpus[$field] = $textualContent;
        }

        $dictionary = [];
        $count = [];

        foreach ($corpus as $id => $doc) {
            $terms = explode(' ', $doc);
            $count[$id] = count($terms);

            foreach ($terms as $term) {
                if (!isset($dictionary[$term])) {
                    $dictionary[$term] = ['frequency' => 0, 'postings' => []];
                }
                if (!isset($dictionary[$term]['postings'][$id])) {
                    $dictionary[$term]['frequency']++;
                    $dictionary[$term]['postings'][$id] = ['frequency' => 0];
                }

                $dictionary[$term]['postings'][$id]['frequency']++;
            }
        }

        return ['count' => $count, 'dictionary' => $dictionary];
    }

    /**
     * This method uses the index built in the method above to do some quick
     * score calculations for each word of the query, versus each word of the
     * index dictionary.
     *
     * @param Object $result
     *
     * @return float
     */
    protected function getResultScore($result)
    {
        $output = [];

        $corpus = $this->buildResultIndex($result);
        $count = count($corpus['count']);

        // This block iterates the search query words and checks both their
        // existence and frequency in the index.
        //
        // The score is passed through log(x, 2) to reduce the smooth the difference.
        //
        foreach ($this->searchWords as $word) {
            $word = mb_strtolower($word);
            if (isset($corpus['dictionary'][$word])) {
                $entry = $corpus['dictionary'][$word];
                foreach ($entry['postings'] as $field => $posting) {

                    //get term frequency–inverse document frequency
                    $score = $posting['frequency'] * log($count + 1 / $entry['frequency'] + 1, 2);

                    if (isset($output[$field])) {
                        $output[$field] += $score;
                    } else {
                        $output[$field] = $score;
                    }
                }
            }
        }

        // length normalise, we do this to stop smaller amounts of text having
        // a disproportionate effect on the score.
        foreach ($output as $field => $score) {
            $output[$field] = $score / $corpus['count'][$field];
        }

        // Finally this weights by using the field specific weighting value that
        // is set inside `contenttypes.yml` This uses a weighting factor from 0 to
        // 100 that alters the score accordingly.
        $weights = $this->getContentFields();
        foreach ($output as $field => &$score) {
            if (isset($weights[$field]['weight'])) {
                $multiplier = $weights[$field]['weight'] / 100;

                if ($multiplier > 0) {
                    $score = $score * $multiplier;
                }
            }
        }

        return array_sum($output);
    }
}
