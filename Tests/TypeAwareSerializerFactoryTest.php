<?php
namespace Exporo\Client\Payment\Tests;

use Exporo\Messenger\Tests\Fixtures\Message;
use Exporo\Messenger\Transports\Serialization\TypeAwareSerializer;
use Exporo\Messenger\Transports\Serialization\TypeAwareSerializerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

class TypeAwareSerializerFactoryTest extends TestCase
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
    public function testCreateWillReturnSerializer()
    {
        $serializer = (new TypeAwareSerializerFactory(
            Serializer::create()
        ))->create([
            'type' => Message::class
        ]);

        $this->assertInstanceOf(TypeAwareSerializer::class, $serializer);
    }

    /**
     * @return void
     */
    public function testCreateWillThrowExceptionOnMissingOptions()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new TypeAwareSerializerFactory(
            Serializer::create()
        ))->create([]);
    }
}