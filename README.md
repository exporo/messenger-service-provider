# Symfony Messenger for Lumen

## Usage

First, define your provider:

```
use Exporo\Messenger\Providers\MessengerServiceProvider;

class YourProvider extends MessengerServiceProvider
{
    protected $transports = [
    ];

    protected $routing = [
    ];

    protected $handlers = [
    ];

    protected $transportFactories = [
    ];

    protected $messageBuses = [
    ];
}

```

## Transports 

Now, add some transport factories:

```
protected $transportFactories = [
	Exporo\Messenger\Transports\AmqpTransportFactory::class,
	// ...
];

```

And declare your transports:

```
protected $transports = [
    'some_transport' => [
        'dsn' => 'dsn://', // Whatever supported by your registered transport factories
        'serializer' => Serializer::class,
        'options' => [
            'queue' => ['name' => 'something'],
            'topic' => ['name' => 'something'] // Also known as exchange
        ]
    ],
];
```

### TypeAwareSerializer

The default serializer will always add the FQCN of your message to the headers. To have fully language/platform agnostic queuing you can use the TypeAwareSerializer, which always expect the message to be from the given type:

```
protected $transports = [
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
            'topic' => ['name' => 'my_topic'] // Also known as exchange
        ]
    ],
];
```

## Sending messages

First, add some routing to let the messenger know which message should be go to which transport:

```
protected $routing = [
	SomeMessage::class => [
	    'some_transport'
	]
];

```

And declare a message bus for sending:

```
protected $messageBuses = [
	// Good for autowiring, but could be also "sender_bus" etc.
    MessengerInterface::class => [
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

First, declare a handler:

```
class SomeHandler implements MessageHandlerInterface
{
    public function __invoke(SomeMessage $message)
    {
        // ...
    }
}
```

And map your message to the handler:

```
protected $handlers = [
	SomeMessage::class => [
		SomeHandler::class
	]
];

```

Now, declare a message bus for handling:

```
protected $messageBuses = [
	// Good for autowiring, but could be also "receiver_bus" etc.
    MessengerInterface::class => [
        HandleMessageMiddleware::class // Default middleware for handling
    ]
];
```

### Using Symfony Worker

See https://symfony.com/doc/current/messenger.html#messenger-worker for details

```
$messenger = $container->get(MessengerInterface::class);
$transport = $container->get('messenger.transport.some_transport');

(new \Symfony\Component\Messenger\Worker([
    $transport
], $messenger))->run();
```

### BYO Worker

You can also build your own:

```
$messenger = $container->get(MessengerInterface::class);
$transport = $container->get('messenger.transport.some_transport');

while(true) {
	foreach($transport->get() as $k => $envelop) {
	    $receiver->ack($envelop);
	    $messenger->dispatch($envelop); // This will actually call the handlers, $envelop->getMessage() is already the deserialized object
	}
}

```

