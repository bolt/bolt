<?php
namespace Bolt\Session\Serializer;

class NativeSerializer implements SerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize($data)
    {
        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($data)
    {
        // @codingStandardsIgnoreStart
        set_error_handler(function () {});
        // @codingStandardsIgnoreEnd
        $session = unserialize($data);
        restore_error_handler();
        if ($session === false) {
            throw new \RuntimeException('Unserialization failure');
        }
        return $session;
    }
}
