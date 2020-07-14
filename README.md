# Symfony Messenger for Lumen

This package is intended to be used with Laravel/Lumen.

## Service Provider

First, you need to declare a config like `config/messenger.php`
```
return [
    'transports' => [],
    'routing' => [],
    'handlers' => [],
    'transport_factories' => [],
    'message_buses' => []
];
```

For Lumen, you also need to register the config:

```
use Exporo\Messenger\Providers\MessengerServiceProvider as MessengerProvider;

// Ensures the config is available before register
class MessengerServiceProvider extends MessengerProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/messenger.php', 'messenger');
        parent::register();
    }
}
```

Don't forget to register it in your bootstrapping process.

## Transports 

Add one or more transport factories to the config. They depend on `\Symfony\Component\Messenger\Transport\TransportFactoryInterface`, so you can also easily add your own.

```
'transport_factories' = [
    Exporo\Messenger\Transports\AmqpTransportFactory::class,
    // ...
];

```

Built in factories:
- AmqpTransportFactory
- KafkaTransportFactory
- SqsTransportFactory


Now, after you added support for some specific transport protocol(s), you can define your transport:

```
'transports' = [
    'some_transport' => [
        'dsn' => 'dsn://', // Whatever supported by your registered transport factories
        'serializer' => Serializer::class,
        'options' => [
            'queue' => ['name' => 'something'],
            'topic' => ['name' => 'something']
        ]
    ],
];
```

This will be the actual sender/receiver. You _may_ want to use it directly, so you can get an instance of `\Symfony\Component\Messenger\Transport\TransportInterface` from the container with `messenger.transport.some_transport`.

### TypeAwareSerializer

The default serializer will always add the FQCN of your message to the headers and will fail if it's missing on deserialize. In distributed systems, you normally don't want to have this dependency. For that you can use the TypeAwareSerializer. It will assume that all your messages on the given transport are from the same type, but also removes the need for additional message headers.

```
'transports' = [
    'agnostic_' => [
        'dsn' => 'dsn://', // Whatever supported by your registered transport factories
        'serializer' => [
            'factory' => TypeAwareSerializerFactory::class,
            'options' => [
                'type' => SomeMessage::class
            ]
        ],
        'options' => [
            'queue' => ['name' => 'my_queue'],
            'topic' => ['name' => 'my_topic']
        ]
    ],
];
```

## Sending messages

Let's add some routing to let the messenger know which message should be go to which transport. Your message class can be any POPO.

```
'routing' = [
    SomeMessage::class => [
        'some_transport'
    ]
];

```

Now, we declare our sending message bus. 

```
'message_buses' = [
    // Good for autowiring, but could be also "sender_bus" etc.
    MessageBusInterface::class => [
        SendMessageMiddleware::class // Default middleware for sending
    ]
];

```

Finally, just use it like this:

```
$messenger = $container->get(MessengerInterface::class);

$messenger->dispatch(new SomeMessage()); // Will dispatch "SomeMessage" on "some_transport" (as defined in routing)
```

## Consuming messages

For consuming, we need to declare a `MessageHandlerInterface`:

```
class SomeHandler implements MessageHandlerInterface
{
    public function __invoke(SomeMessage $message)
    {
        // ...
    }
}
```

To make the Messenger know which message should be handles by which handler(s), we have to add it like this:

```
'handlers' = [
    SomeMessage::class => [
        SomeHandler::class
    ]
];

```

As with sending, we need to declare a message bus for handling too:

```
'message_buses' = [
    // Good for autowiring, but could be also "receiver_bus" etc.
    MessageBusInterface::class => [
        HandleMessageMiddleware::class // Default middleware for handling
    ]
];
```

### Using Symfony Worker

This is the easiest way to create a message consumer.

See https://symfony.com/doc/current/messenger.html#messenger-worker for details.

```
$messenger = $container->get(MessageBusInterface::class);
$transport = $container->get('messenger.transport.some_transport');

(new \Symfony\Component\Messenger\Worker([
    $transport
], $messenger))->run();
```

### BYO Worker

You can also build your own. As you can see in this example, the message bus is actually only do the handling, the receiving is done through the transport self.

```
$messenger = $container->get(MessageBusInterface::class);
$transport = $container->get('messenger.transport.some_transport');

while(true) {
    foreach($transport->get() as $k => $envelope) {
        $receiver->ack($envelope);
        $messenger->dispatch($envelope); // This will actually call the handlers, $envelope->getMessage() is already the deserialized object
    }
}

```

