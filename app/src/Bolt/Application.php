<?php

namespace Bolt;

use Silex\Application as BaseApplication;

/**
 * @property-read string $bolt_version
 * @property-read string $bolt_name
 *
 * @property array $config
 * @property string $dbPrefix
 * @property \Doctrine\DBAL\Connection $db
 * @property \Symfony\Component\HttpFoundation\Session\Session $session
 * @property \Twig_Environment $twig
 * @property bool $debugbar
 * @property bool $debug
 * @property array $paths
 * @property string $end
 * @property string $editlink
 * @property Log $log
 * @property Extensions $extensions
 * @property Storage $storage
 * @property Users $users
 * @property Content $content
 * @property Cache $cache
 * @property \Symfony\Component\HttpFoundation\Request $request
 * @property \Symfony\Component\Translation\Translator $translator
 * @property array $pager
 * @property \Swift_Mailer $mailer
 * @property \Symfony\Component\Routing\Generator\UrlGenerator $url_generator
 * @property \Symfony\Component\Validator\Validator $validator
 */
class Application extends BaseApplication
{
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '1.1';
        $values['bolt_name'] = 'prerelease';

        parent::__construct($values);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        if (isset($name)) {
            return $this[$name];
        }

        if (strcasecmp('dbPrefix', $name) == 0) {
            return $this->config['general']['database']['prefix'];
        }

        throw new \InvalidArgumentException;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return (array_key_exists($name, $this));
    }

    public function getVersion($long = true) {

        if ($long) {
            return $this->bolt_version . " " . $this->bolt_name;
        } else {
            return $this->bolt_version;
        }

    }

}
