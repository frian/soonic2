<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/album/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.albums-view');
    }
}
