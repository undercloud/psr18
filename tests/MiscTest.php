<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Undercloud\Psr18\Misc;

final class MiscTest extends TestCase
{
    public function testHeadSerializer()
    {
        $head = [
            'Host' => ['google.com'],
            'Content-Type' => [
                'application/json',
                'charset=utf-8'
            ]
        ];

        $inline = (
          "Host: google.com\r\n" .
          "Content-Type: application/json, charset=utf-8\r\n"
        );

        $this->assertEquals($inline, Misc::serializePsr7Headers($head));
    }

    public function testUriExtract()
    {
        $extract = Misc::extractRelativeUrlComponents('');
        $expect = ['/',''];

        $this->assertEquals($expect, $extract);

        $extract = Misc::extractRelativeUrlComponents('?foo=bar');
        $expect = ['/','foo=bar'];

        $this->assertEquals($expect, $extract);

        $extract = Misc::extractRelativeUrlComponents('/?foo=bar');
        $expect = ['/','foo=bar'];

        $this->assertEquals($expect, $extract);

        $extract = Misc::extractRelativeUrlComponents('/path/to?foo=bar');
        $expect = ['/path/to','foo=bar'];

        $this->assertEquals($expect, $extract);
    }

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