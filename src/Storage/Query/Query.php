<?php

namespace Bolt\Storage\Query;

use Bolt\Storage\Entity\Content;
use Bolt\Twig\TwigRecordsView;

class Query
{
    /** @var ContentQueryParser */
    protected $parser;
    /** @var array */
    protected $scopes;
    /** @var TwigRecordsView */
    protected $recordsView;

    /**
     * Constructor.
     *
     * @param ContentQueryParser $parser
     * @param TwigRecordsView    $recordsView
     */
    public function __construct(ContentQueryParser $parser, TwigRecordsView $recordsView)
    {
        $this->parser = $parser;
        $this->recordsView = $recordsView;
    }

    /**
     * @param string              $name
     * @param QueryScopeInterface $scope
     */
    public function addScope($name, QueryScopeInterface $scope)
    {
        $this->scopes[$name] = $scope;
    }

    /**
     * @param string $name
     *
     * @return QueryScopeInterface|null
     */
    public function getScope($name)
    {
        if (array_key_exists($name, $this->scopes)) {
            return $this->scopes[$name];
        }

        return null;
    }

    /**
     * getContent based on a 'human readable query'.
     *
     * Used by the twig command {% setcontent %} but also directly.
     * For reference refer to @link https://docs.bolt.cm/templating/content-fetching
     *
     * @param string       $textQuery
     * @param array|string $parameters
     *
     * @return QueryResultset|Content|null
     */
    public function getContent($textQuery, array $parameters = [])
    {
        $this->parser->setQuery($textQuery);
        $this->parser->setParameters($parameters);

        return $this->parser->fetch();
    }

    /**
     * @param string $scopeName
     * @param string $textQuery
     * @param array  $parameters
     *
     * @return QueryResultset|null
     */
    public function getContentByScope($scopeName, $textQuery, array $parameters = [])
    {
        if ($scope = $this->getScope($scopeName)) {
            $this->parser->setQuery($textQuery);
            $this->parser->setParameters($parameters);
            $this->parser->setScope($scope);

            return $this->parser->fetch();
        }

        return null;
    }

    /**
     * Helper to be called from Twig that is passed via a TwigRecordsView rather than the raw records.
     *
     * @param $textQuery
     * @param array $parameters
     *
     * @return QueryResultset|null
     */
    public function getContentForTwig($textQuery, array $parameters = [])
    {
        // fix BC break
        if (func_num_args() === 3) {
            $whereparameters = func_get_arg(2);
            if (is_array($whereparameters) && !empty($whereparameters)) {
                $parameters = array_merge($parameters, $whereparameters);
            }
        }
        return $this->recordsView->createView(
            $this->getContentByScope('frontend', $textQuery, $parameters)
        );
    }
}
