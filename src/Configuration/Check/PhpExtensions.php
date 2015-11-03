<?php
namespace Bolt\Configuration\Check;

/**
 * Checks for PHP extension configuration.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PhpExtensions extends BaseCheck implements ConfigurationCheckInterface
{
    /** @var array */
    protected $options = [
        'extensions' => [
            'curl',
            'date',
            'dom',
            'exif',
            'gd',
            'gettext',
            'gmp',
            'hash',
            'iconv',
            'intl',
            'json',
            'libxml',
            'mbstring',
            'openssl',
            'pcre',
            'PDO',
            'posix',
            'readline',
            'Reflection',
            'session',
            'soap',
            'SPL',
            'tokenizer',
            'xml',
            'xsl',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function runCheck()
    {
        foreach ($this->options['extensions'] as $extension) {
            try {
                if (extension_loaded($extension)) {
                    $this->createResult()->pass()->setMessage("PHP extension $extension is loaded.");
                } else {
                    $this->createResult()->fail()->setMessage("PHP extension $extension NOT loaded.");
                }
            } catch (\Exception $e) {
                $this->createResult()->fail()->setMessage('PHP exception')->setException($e);
            }
        }

        return $this->results;
    }
}
