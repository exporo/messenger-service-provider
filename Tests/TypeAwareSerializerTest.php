<?php
namespace Exporo\Client\Payment\Tests;

use Exporo\Messenger\Tests\Fixtures\Message;
use Exporo\Messenger\Transports\Serialization\TypeAwareSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

class TypeAwareSerializerTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
    }

    /**
     * @return void
     */
    public function testDecodeReturnsInstanceOfSuppliedType()
    {
        $serializer = (new TypeAwareSerializer(
            Serializer::create(),
            Message::class
        ));

        $message = $serializer->decode([
            'body' => '{"data":"Hello World"}'
        ]);

        $this->assertInstanceOf(Message::class, $message->getMessage());
    }

    /**
     * @return void
     */
    public function testEncodeDoesNotContainTypeHeader()
    {
        $serializer = (new TypeAwareSerializer(
            Serializer::create(),
            Message::class
        ));

        $message = new Message();
        $message->setData('Hello World');

        $encoded = $serializer->encode(new Envelope(
            $message
        ));

        $this->assertNotContains('type', $encoded['headers']);
    }
}