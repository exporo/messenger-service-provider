<?php
namespace Exporo\Messenger\Tests\Fixtures;

class Message
{
    private $data;

    public function __construct()
    {
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}