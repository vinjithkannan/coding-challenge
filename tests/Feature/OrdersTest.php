<?php

namespace Tests\Feature;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrdersTest extends WebTestCase
{
    public function testGetOrders(): void
    {
        $client = static::createClient(array(),array('HTTPS' => true));
        $client->request('GET', '/orders');
        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent(), '');
    }

    public function testGetOrder(): void
    {
        $client = static::createClient(array(),array('HTTPS' => true));

        $client->request('GET', '/orders');
        $firstOrderElement = current(json_decode($client->getResponse()->getContent(), true));
        $client->request(
            'GET',
            '/order/' . $firstOrderElement['order_id']
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent(), '');
    }
}
