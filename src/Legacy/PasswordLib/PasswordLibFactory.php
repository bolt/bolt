<?php

namespace Bolt\Legacy\PasswordLib;

use PasswordLib\Password\AbstractPassword;
use PasswordLib\Password\Factory;

/**
 * A PasswordLib Factory that uses our random generator.
 *
 * @internal
 *
 * @deprecated Deprecated since 3.3, to be removed in 4.0.
 */
final class PasswordLibFactory extends Factory
{
    /** @var AbstractPassword[] */
    protected $implementations = [];
    /** @var PasswordLibRandomGenerator */
    private $generator;

    /**
     * {@inheritdoc}
     */
    public function createHash($password, $prefix = '$2a$', array $options = [])
    {
        if ($prefix === false) {
            throw new \DomainException('Unsupported Prefix Supplied');
        }
        foreach ($this->implementations as $impl) {
            if ($impl::getPrefix() == $prefix) {
                /** @var AbstractPassword $instance */
                $instance = new $impl();
                $instance->setGenerator($this->getGenerator());

                return $instance->create($password);
            }
        }
        throw new \DomainException('Unsupported Prefix Supplied');
    }

    /**
     * {@inheritdoc}
     */
    public function verifyHash($password, $hash)
    {
        foreach ($this->implementations as $impl) {
            if ($impl::detect($hash)) {
                /** @var AbstractPassword $instance */
                $instance = $impl::loadFromHash($hash);
                $instance->setGenerator($this->getGenerator());

                return $instance->verify($password, $hash);
            }
        }
        throw new \DomainException('Unsupported Password Hash Supplied');
    }

    private function getGenerator()
    {
        if (!$this->generator) {
            $this->generator = new PasswordLibRandomGenerator();
        }

        return $this->generator;
    }
}
