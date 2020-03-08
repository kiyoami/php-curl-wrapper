<?php

namespace Kiyoami\Test;

use Kiyoami\Curl\Core;
use Kiyoami\Curl\CurlException;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    public function testCurl()
    {
        $this->assertIsArray(Core::version());

        $curl = new Core('https://google.com');
        $this->assertTrue($curl instanceof Core);
        try {
            $results = $curl->exec()->results();
        } catch (CurlException $e) {
            $error = $curl->error();
            $this->assertEquals($error['errno'], $e->getCode());
            $this->assertEquals($error['error'], $e->getMessage());
        }
        $this->assertTrue(empty($results));

        $file = $curl->reset()
            ->setopt(CURLOPT_URL, 'https://google.com')
            ->setopt(CURLOPT_SSL_VERIFYPEER, false)
            ->exec()
            ->temporaryFile();
        $this->assertIsString(fread($file, 1000));

        $results = $curl->reset()
            ->setoptArray([
                CURLOPT_URL => 'https://google.com',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
            ])
            ->exec()
            ->results();
        $this->assertIsString($results);

        $results = $curl->request([
            CURLOPT_URL => 'https://google.com',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
        ])->results();
        $this->assertIsString($results);
    }
}
