<?php
namespace Messenger\Transports\Serialization;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TypeAwareSerializer implements SerializerInterface
{
    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var string
     */
    protected $className;

    /**
     * @param Serializer $serializer
     * @param string $className
     */
    public function __construct(Serializer $serializer, $className)
    {
        $this->serializer = $serializer;
        $this->className = $className;
    }

    /**
     * @inheritDoc
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        $encodedEnvelope['headers']['type'] = $this->className;

        return $this->serializer->decode($encodedEnvelope);
    }

    /**
     * @inheritDoc
     */
    public function encode(Envelope $envelope): array
    {
        $encodedEnvelope = $this->serializer->encode($envelope);

        unset($encodedEnvelope['headers']['type']);

        return $encodedEnvelope;
    }
}
