<?php
namespace Exporo\Messenger\Transports\Serialization;

use Symfony\Component\Messenger\Transport\Serialization\Serializer;

class TypeAwareSerializerFactory
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param Serializer $serializer
     */
    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param array $options
     * @return TypeAwareSerializer
     */
    public function create(array $options)
    {
        if (!isset($options['type'])) {
            throw new \InvalidArgumentException('Option "type" was not set and should be a FQCN');
        }

        return new TypeAwareSerializer($this->serializer, $options['type']);
    }
}
