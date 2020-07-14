<?php

return [
    /**
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
     */
    'transports' => [],

    /**
     * SomeMessage::class => [
     *     'some_transport'
     * ];
     */
    'routing' => [],

    /**
     * SomeMessage::class => [
     *     SomeHandler::class
     * ];
     */
    'handlers' => [],

    /**
     * Symfony\Component\Messenger\Transport\TransportFactoryInterface[]
     */
    'transport_factories' => [],

    /**
     * MessageBusInterface::class => [
     *     YourMessageMiddleware::class
     * ],
     * 'sender_bus' => [
     *     SendMessageMiddleware::class
     * ],
     * 'handler_bus' => [
     *     HandleMessageMiddleware::class
     * ]
     */
    'message_buses' => []
];
