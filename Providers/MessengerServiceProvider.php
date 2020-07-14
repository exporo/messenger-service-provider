<?php
namespace Exporo\Messenger\Providers;

use Exporo\Messenger\Transports\Serialization\TypeAwareSerializerFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Config;
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
     * @return void
     */
    public function register()
    {
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

            $routing = $c->get('config')
                ->get('messenger.routing', []);

            return new SendersLocator(array_map($transformer, $routing), $c);
        });

        $this->app->singleton(HandlersLocator::class, function (Container $c) {
            $transformer = function (array $handlers) use ($c) {
                return array_map(function ($name) use ($c) {
                    return $c->get($name);
                }, $handlers);
            };

            $handlers = $c->get('config')
                ->get('messenger.handlers', []);

            return new HandlersLocator(array_map($transformer, $handlers));
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
            $factories = $c->get('config')
                ->get('messenger.transport_factories', []);

            return new TransportFactory(
                array_map(function ($factoryName) use ($c) {
                    return $c->get($factoryName);
                }, $factories)
            );
        });

        foreach (array_keys(Config::get('messenger.transports', [])) as $k => $name) {
            $this->registerTransport($name);
        }
    }

    /**
     * @param string $name
     */
    protected function registerTransport($name)
    {
        $this->app->singleton("messenger.transport.{$name}", function (Container $c) use ($name) {
            $config = $c->get('config')
                ->get('messenger.transports')[$name];

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
        foreach (array_keys(Config::get('messenger.message_buses', [])) as $k => $name) {
            $this->app->singleton($name, function (Container $c) use ($name) {
                $config = $c->get('config')
                    ->get('messenger.message_buses')[$name];

                $middleware = array_map(function ($middleware) use ($c) {
                    return $c->get($middleware);
                }, $config);

                return new MessageBus($middleware);
            });
        }
    }
}
