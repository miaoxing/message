<?php

namespace MiaoxingTest\Message\Service;

use Miaoxing\Plugin\Test\BaseTestCase;

/**
 * 演示服务
 */
class MessageRecordTest extends BaseTestCase
{
    /**
     * 获取名称
     */
    public function testGetName()
    {
        $this->assertSame('message', wei()->messageRecord->getName());
    }
}
