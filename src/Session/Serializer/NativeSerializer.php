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
        $ex = null;
        set_error_handler(
            function () use (&$ex) {
                $ex = new \RuntimeException('Unable to unserialize session data.');
            }
        );

        $session = unserialize($data);
        restore_error_handler();

        if ($ex instanceof \Exception) {
            throw $ex;
        }

        return $session;
    }
}
