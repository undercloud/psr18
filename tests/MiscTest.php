<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Undercloud\Psr18\Misc;

final class MiscTest extends TestCase
{
    public function testRelativeUrl()
    {
        $this->assertTrue(Misc::isRelativeUrl('/path'));
        $this->assertFalse(Misc::isRelativeUrl('http://google.com/'));
    }

    public function testConvertSslOptionsKeys()
    {
        $actual = Misc::convertSslOptionsKeys([
            'verifyPeer' => false,
            'SNIEnabled' => false
        ]);
        
        $expected = [
            'verify_peer' => false,
            'SNI_enabled' => false
        ];

        $this->assertEquals($expected, $actual);
    }
}