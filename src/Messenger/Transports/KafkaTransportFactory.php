<?php
namespace App\Messenger\Transports;

use Enqueue\MessengerAdapter\AmqpContextManager;
use Enqueue\MessengerAdapter\QueueInteropTransport;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class KafkaTransportFactory implements TransportFactoryInterface
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
            (new RdKafkaConnectionFactory(array_merge($options, [
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
        return strpos($dsn, 'kafka://') === 0;
    }
}
