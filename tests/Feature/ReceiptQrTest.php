<?php

namespace Amarenkov\MutableContentFinance\Tests\Feature;

use Amarenkov\MutableContentFinance\Support\ReceiptQr;

use Amarenkov\MutableContentFinance\Tests\TestCase;

class ReceiptQrTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('mutable-content.timezone', 'Europe/Moscow');
    }

    public function test_fiscal_receipt_is_parsed(): void
    {
        $receipt = ReceiptQr::parse('t=20210307T120900&s=9680.84&fn=9285000100186208&i=10337&fp=1079745789&n=1');

        $this->assertSame('2021-03-07 09:09:00', $receipt->time->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('9680.84', $receipt->total);
        $this->assertSame('9285000100186208/10337/1079745789', $receipt->key());
        $this->assertSame(ReceiptQr::TYPE_INCOME, $receipt->type);
    }

    public function test_time_without_seconds_and_other_codes(): void
    {
        $this->assertSame('2022-09-23 00:20', ReceiptQr::parse('t=20220923T0020&s=3000.15&fn=1&i=2&fp=3&n=1')->time->format('Y-m-d H:i'));
        $this->assertNull(ReceiptQr::parse('https://example.com'));
        $this->assertNull(ReceiptQr::parse('t=soon&s=1&fn=1&i=2&fp=3'));
        $this->assertNull(ReceiptQr::parse('t=20220923T0020&s=ten&fn=1&i=2&fp=3'));
    }
}
