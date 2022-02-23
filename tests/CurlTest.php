<?php

namespace Kiyoami\EnumTest;

use Kiyoami\Curl\Core;
use Kiyoami\Curl\Multi;
use Kiyoami\Curl\CurlException;
use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{
    public function testCore()
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

        $curl2 = clone($curl);
        $this->assertTrue($curl2 instanceof Core);
        try {
            $results = $curl2->exec()->results();
        } catch (CurlException $e) {
            $error = $curl2->error();
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

    public function testMulti()
    {
        $url_list = [
            'https://news.yahoo.co.jp/pickup/rss.xml',
            'http://weather.livedoor.com/forecast/rss/primary_area.xml',
        ];

        $curl_mulit = new Multi();
        foreach ($url_list as $url) {
            $curl = new Core();
            $curl->setoptArray([CURLOPT_URL => $url, CURLOPT_SSL_VERIFYPEER => false], true);
            $curl_mulit->add($curl);
        }

        do {
            $results = $curl_mulit->exec();
        } while ($results->status === CURLM_CALL_MULTI_PERFORM);
        while ($results->active && $results->status === CURLM_OK) {
            if ($curl_mulit->select() == -1) {
                usleep(1);
            }
            do {
                $results = $curl_mulit->exec();
            } while ($results->status === CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($curl_mulit as $curl) {
            $this->assertInstanceOf(Core::class, $curl);
            $file = $curl->temporaryFile();
            $this->assertIsString(\fgets($file));
        }

        $this->assertInstanceOf(Core::class, $curl_mulit[0]);
    }
}
