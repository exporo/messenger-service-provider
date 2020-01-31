<?php
namespace Exporo\Messenger\Transports;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\MessengerAdapter\AmqpContextManager;
use Enqueue\MessengerAdapter\QueueInteropTransport;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AmqpTransportFactory implements TransportFactoryInterface
{
    /**
     * @param string $dsn
     * @param array $options
     * @param SerializerInterface $serializer
     * @return TransportInterface
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $manager = new AmqpContextManager(
            (new AmqpConnectionFactory(array_merge($options, [
                'dsn' => $dsn
            ])))->createContext()
        );

        return new QueueInteropTransport($serializer, $manager, $options);
    }

    /**
     * @param string $dsn
     * @param array $options
     * @return bool
     */
    public function supports(string $dsn, array $options): bool
    {
        return strpos($dsn, 'amqp://') === 0 ||
            strpos($dsn, 'amqps://') === 0 ||
            strpos($dsn, 'amqp+ext://') === 0 ||
            strpos($dsn, 'amqps+ext://') === 0;
    }
}
