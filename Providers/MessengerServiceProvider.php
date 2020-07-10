<?php
namespace Exporo\Messenger\Providers;

use Exporo\Messenger\Transports\Serialization\TypeAwareSerializerFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\TransportFactory;

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * <code>
     * 'low_priority_queue' => [
     *     'dsn' => 'amqp://',
     *     'serializer' => [
     *         'factory' => TypeAwareSerializerFactory::class,
     *         'options' => [
     *             'type' => LowPriorityMessage::class
     *         ]
     *     ],
     *     'options' => []
     * ],
     * 'default_queue' => [
     *     'dsn' => 'amqp://',
     *     'serializer' => Serializer::class,
     *     'options' => []
     * ]
     * </code>
     *
     * @var array[]
     */
    protected $transports = [];

    /**
     * <code>
     * SomeMessage::class => [
     *     'some_transport'
     * ];
     * </code>
     *
     * @var array[]
     */
    protected $routing = [];

    /**
     * <code>
     * SomeMessage::class => [
     *     SomeHandler::class
     * ];
     * </code>
     *
     * @var array[]
     */
    protected $handlers = [];

    /**
     * List of FQCNs implementing Symfony\Component\Messenger\Transport\TransportFactoryInterface
     *
     * @var string[]
     */
    protected $transportFactories = [];

    /**
     * Registers message buses with given middleware
     *
     * <code>
     * MessageBusInterface::class => [
     *     YourMessageMiddleware::class
     * ],
     * 'sender_bus' => [
     *     SendMessageMiddleware::class
     * ],
     * 'handler_bus' => [
     *     HandleMessageMiddleware::class
     * ]
     * </code>
     *
     * @var array[]
     */
    protected $messageBuses = [];

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton('messenger.transport_factories', function () {
            return $this->transportFactories;
        });

        $this->app->singleton('messenger.transports', function () {
            return $this->transports;
        });

        $this->app->singleton('messenger.routing', function () {
            return $this->routing;
        });

        $this->app->singleton('messenger.handlers', function () {
            return $this->handlers;
        });

        $this->app->singleton('messenger.message_buses', function () {
            return $this->messageBuses;
        });

        $this->registerSerializers();
        $this->registerLocators();
        $this->registerMiddleware();
        $this->registerTransports();
        $this->registerMessageBuses();
    }

    /**
     * @return void
     */
    protected function registerSerializers()
    {
        $this->app->singleton(Serializer::class, function () {
            return Serializer::create();
        });

        $this->app->singleton(TypeAwareSerializerFactory::class, function (Container $c) {
            return new TypeAwareSerializerFactory(
                $c->get(Serializer::class)
            );
        });
    }

    /**
     * @return void
     */
    protected function registerLocators()
    {
        $this->app->singleton(SendersLocator::class, function (Container $c) {
            $transformer = function (array $transports) {
                return array_map(function ($name) {
                    return "messenger.transport.$name";
                }, $transports);
            };

            return new SendersLocator(array_map($transformer, $c->get('messenger.routing')), $c);
        });

        $this->app->singleton(HandlersLocator::class, function (Container $c) {
            $transformer = function (array $handlers) use ($c) {
                return array_map(function ($name) use ($c) {
                    return $c->get($name);
                }, $handlers);
            };

            return new HandlersLocator(array_map($transformer, $c->get('messenger.handlers')));
        });
    }

    /**
     * @return void
     */
    protected function registerMiddleware()
    {
        $this->app->singleton(SendMessageMiddleware::class, function (Container $c) {
            return new SendMessageMiddleware(
                $c->get(SendersLocator::class)
            );
        });

        $this->app->singleton(HandleMessageMiddleware::class, function (Container $c) {
            return new HandleMessageMiddleware(
                $c->get(HandlersLocator::class)
            );
        });
    }

    /**
     * @return void
     */
    protected function registerTransports()
    {
        $this->app->singleton(TransportFactory::class, function (Container $c) {
            return new TransportFactory(
                array_map(function ($factoryName) use ($c) {
                    return $c->get($factoryName);
                }, $this->transportFactories)
            );
        });
        
        foreach (array_keys($this->app->get('messenger.transports')) as $k => $name) {
            $this->registerTransport($name);
        }
    }

    /**
     * @param string $name
     */
    protected function registerTransport($name)
    {
        $this->app->singleton("messenger.transport.{$name}", function (Container $c) use ($name) {
            $config = $c->get('messenger.transports')[$name];

            return $c->get(TransportFactory::class)->createTransport(
                $config['dsn'] ?? '',
                $config['options'] ?? [],
                is_string($config['serializer'] ?? Serializer::class)
                    ? $c->get($config['serializer'])
                    : $c->get($config['serializer']['factory'])->create($config['serializer']['options'])
            );
        });
    }

    /**
     * @return void
     */
    protected function registerMessageBuses()
    {
        foreach (array_keys($this->messageBuses) as $k => $name) {
            $this->app->singleton($name, function (Container $c) use ($name) {
                $middleware = array_map(function ($middleware) use ($c) {
                    return $c->get($middleware);
                }, $c->get('messenger.message_buses')[$name]);

                return new MessageBus($middleware);
            });
        }
    }
}
