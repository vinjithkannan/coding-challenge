<?php

namespace Tests\Service;

use App\Service\ParseJsonLines;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Rs\JsonLines\JsonLines;

class ParseJsonLinesTest extends TestCase
{

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerObject;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $rsJsonLineObject;

    /**
     * @var ParseJsonLines
     */
    private $parseJsonLinesService;



    public function setUp(): void
    {
        $this->rsJsonLineObject = $this->createMock(JsonLines::class);
        $this->loggerObject = $this->createMock(LoggerInterface::class);
        $this->parseJsonLinesService = new ParseJsonLines(
            $this->rsJsonLineObject,
            $this->loggerObject
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(ParseJsonLines::class, $this->parseJsonLinesService);
    }


    public function testDelineFromFile(): void
    {
        $filePath = '/srv/app/public/storage/orders.jsonl';
        $orders = '[{"order_id":"88948419","order_datetime":"2022-03-27T11:14:17Z","customer":{"id":"07719092",
        "first_name":"Wolfgang","last_name":"Armsden","email":"warmsden0@unc.edu","phone":"+61 200 337 4648",
        "shipping_address":{"street":"692 Mayfield Alley","postcode":"1315","suburb":"Eastern Suburbs Mc",
        "state":"New South Wales"}},"items":[{"quantity":4,"unit_price":118.27,"product":{"product_id":"08394534",
        "title":"Pork - Backfat","image":"http:\/\/dummyimage.com\/169x241.png\/dddddd\/000000","upc":"761192524918",
        "created_at":"2021-05-26T02:08:55Z","brand":{"brand_id":"79738842","name":"dapibus"}}},{"quantity":1,
        "unit_price":100.83,"product":{"product_id":"85011589","title":"Cucumber - English",
        "image":"http:\/\/dummyimage.com\/208x200.png\/5fa2dd\/ffffff","upc":"986968073176",
        "created_at":"2021-01-26T22:18:13Z","brand":{"brand_id":"70338137","name":"urna"}}},{"quantity":5,
        "unit_price":105.97,"product":{"product_id":"99259476","title":"Dr. Pepper - 355ml",
        "image":"http:\/\/dummyimage.com\/175x211.png\/cc0000\/ffffff","upc":"618501630123",
        "created_at":"2021-03-25T13:53:58Z","brand":{"brand_id":"99400014","name":"posuere"}}},{"quantity":2,
        "unit_price":50.88,"product":{"product_id":"79561529","title":"Water - Spring 1.5lit",
        "image":"http:\/\/dummyimage.com\/208x180.png\/dddddd\/000000","upc":"442011983978",
        "created_at":"2020-05-06T10:19:28Z","brand":{"brand_id":"83541501","name":"adipiscing"}}}],"shipping_price":0,
        "discounts":[]}]';

        $expected = json_decode($orders, true);

        $this->rsJsonLineObject->expects($this->once())
            ->method('delineFromfile')
            ->with($filePath)
            ->willReturn($orders);

        $fileContentArray = $this->parseJsonLinesService->delineFromFile($filePath);
        $this->assertEquals($expected, $fileContentArray);
    }
    public function testDelineFromFileFailure(): void
    {
        $filePath = '/srv/app/public/storage/orders_all.jsonl';
        $expected = [
            'error' => [
                'code' => 0,
                'message' => 'File not found or empty file'
                ]
        ];
        $this->rsJsonLineObject->expects($this->any())
            ->method('delineFromfile')
            ->with($filePath)
            ->willReturn([]);

        $fileContentArray = $this->parseJsonLinesService->delineFromFile($filePath);
        $this->assertEquals($expected, $fileContentArray);

        $fileContentArray = $this->parseJsonLinesService->delineEachLineFromFile($filePath);
        $this->assertEquals($expected, $fileContentArray);
    }

    public function testDelineEachLineFromFile(): void
    {
         $filePath = '/srv/app/public/storage/orders.jsonl';
         $orders = '[{"order_id":"88948419","order_datetime":"2022-03-27T11:14:17Z","customer":{"id":"07719092",
        "first_name":"Wolfgang","last_name":"Armsden","email":"warmsden0@unc.edu","phone":"+61 200 337 4648",
        "shipping_address":{"street":"692 Mayfield Alley","postcode":"1315","suburb":"Eastern Suburbs Mc",
        "state":"New South Wales"}},"items":[{"quantity":4,"unit_price":118.27,"product":{"product_id":"08394534",
        "title":"Pork - Backfat","image":"http:\/\/dummyimage.com\/169x241.png\/dddddd\/000000","upc":"761192524918",
        "created_at":"2021-05-26T02:08:55Z","brand":{"brand_id":"79738842","name":"dapibus"}}},{"quantity":1,
        "unit_price":100.83,"product":{"product_id":"85011589","title":"Cucumber - English",
        "image":"http:\/\/dummyimage.com\/208x200.png\/5fa2dd\/ffffff","upc":"986968073176",
        "created_at":"2021-01-26T22:18:13Z","brand":{"brand_id":"70338137","name":"urna"}}},{"quantity":5,
        "unit_price":105.97,"product":{"product_id":"99259476","title":"Dr. Pepper - 355ml",
        "image":"http:\/\/dummyimage.com\/175x211.png\/cc0000\/ffffff","upc":"618501630123",
        "created_at":"2021-03-25T13:53:58Z","brand":{"brand_id":"99400014","name":"posuere"}}},{"quantity":2,
        "unit_price":50.88,"product":{"product_id":"79561529","title":"Water - Spring 1.5lit",
        "image":"http:\/\/dummyimage.com\/208x180.png\/dddddd\/000000","upc":"442011983978",
        "created_at":"2020-05-06T10:19:28Z","brand":{"brand_id":"83541501","name":"adipiscing"}}}],"shipping_price":0,
        "discounts":[]}]';

         $expected = json_decode($orders, true);

         $this->rsJsonLineObject->expects($this->once())
             ->method('delineEachLineFromFile')
             ->with($filePath)
             ->willReturn($orders);

         $fileContentArray = $this->parseJsonLinesService->delineEachLineFromFile($filePath);
         $this->assertEquals($expected, $fileContentArray);
    }
}
