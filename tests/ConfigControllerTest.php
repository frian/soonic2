<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfigControllerTest extends WebTestCase
{
    public function testEdit(): void
    {
        $url = '/config/1/edit';
        printf(PHP_EOL."%-35s %s".PHP_EOL, "testing Album controller with ", $url);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }
}
