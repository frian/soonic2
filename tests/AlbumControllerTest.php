<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $url = '/album/';
        printf(PHP_EOL."%-35s %s".PHP_EOL, "testing Album controller with ", $url);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.albums-view');

        // print PHP_EOL.PHP_EOL;
    }
}
