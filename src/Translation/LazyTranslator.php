<?php

namespace Bolt\Translation;

/**
 * Lazy-loading Translator.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LazyTranslator
{
    /** @var string */
    protected $key;
    /** @var array */
    protected $params;
    /** @var string */
    protected $domain;
    /** @var string */
    protected $locale;

    /**
     * Constructor.
     *
     * @param string $key
     * @param array  $params
     * @param string $domain
     * @param string $locale
     */
    public function __construct($key, array $params, $domain, $locale)
    {
        $this->key = $key;
        $this->params = $params;
        $this->domain = $domain;
        $this->locale = $locale;
    }

    /**
     * @param string $key
     * @param array  $params
     * @param string $domain
     * @param null   $locale
     *
     * @return LazyTranslator
     */
    public static function /*@codingStandardsIgnoreStart*/__/*@codingStandardsIgnoreEnd*/($key, array $params = [], $domain = 'messages', $locale = null)
    {
        return new self($key, $params, $domain, $locale);
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        try {
            return Translator::__($this->key, $this->params, $this->domain, $this->locale);
        } catch (\Exception $e) {
            return (string) $this->key;
        }
    }
}
