<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LibraryControllerTest extends WebTestCase
{
    public function testLibrary(): void
    {
        $url = '/';
        printf(PHP_EOL."%-35s %s".PHP_EOL, "testing Library controller with ", $url);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('.topbar');

        $this->assertSelectorExists('#songsSection');
    }

    public function testShowArtistAlbums(): void
    {
        $url = '/albums/abba';
        printf(PHP_EOL."%-35s %s".PHP_EOL, "testing Library controller with ", $url);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('#album-nav');

        // $node = $crawler->filterXPath('//section[@id="songsSection"]');
        // $this->assertTrue($node->count() == 1);
    }

    public function testShowAlbumsSongs(): void
    {
        $url = '/songs/abba/misc';
        printf(PHP_EOL."%-35s %s".PHP_EOL, "testing Library controller with ", $url);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('i.icon-plus');
    }

    public function testFilterArtist(): void
    {
        $url = '/artist/filter/';
        printf(PHP_EOL."%-35s %s".PHP_EOL, "testing Library controller with ", $url);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('#artists-nav');
        $this->assertSelectorExists('a.artist');
    }

    public function testFilterArtistWithParam(): void
    {
        $url = '/artist/filter/abba';
        printf(PHP_EOL."%-35s %s".PHP_EOL, "testing Library controller with ", $url);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('#artists-nav');
        $this->assertSelectorExists('a.artist');

        $this->assertSelectorTextSame('a.artist', 'ABBA');
    }

    public function testRandomSongs(): void
    {
        $url = '/songs/random';
        printf(PHP_EOL."%-35s %s".PHP_EOL, "testing Library controller with ", $url);

        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $node = $crawler->filterXPath('//i[@class="icon-plus"]');
        $this->assertTrue($node->count() == 20);
    }
}
