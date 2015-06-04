<?php
namespace Bolt\Session\Serializer;

interface SerializerInterface
{
    /**
     * Serializes session data to string
     *
     * @param mixed $data Session data
     *
     * @return string Serialized data
     */
    public function serialize($data);

    /**
     * Unserializes session data from string
     *
     * @param string $data Serialized data
     *
     * @throws \RuntimeException If unserialization fails
     *
     * @return mixed Session data
     */
    public function unserialize($data);
}
