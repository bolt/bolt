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
     * @param string $data
     *
     * @return mixed
     */
    public function unserialize($data)
    {
        return unserialize($data);
    }
}
