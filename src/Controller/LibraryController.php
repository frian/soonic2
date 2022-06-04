<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class LibraryController extends AbstractController
{
    /**
     * @Route("/", name="library", methods={"GET"})
     */
    public function library(ArtistRepository $artistRepository, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            return $this->render('library/screen-content.html.twig', [
                'artists' => $artistRepository->findAll(),
            ]);
        }

        return $this->render('library/screen.html.twig', [
            'artists' => $artistRepository->findAll(),
        ]);
    }

    /**
     * Find albums from an artist.
     *
     * @Route("/albums/{artistSlug}", name="artist_albums", methods={"GET"})
     */
    public function showArtistAlbums(Artist $artist): Response
    {
        $albums = $artist->getAlbums();

        return $this->render('library/album-nav-list.html.twig', [
            'albums' => $albums,
            'artist' => $artist->getArtistSlug(),
        ]);
    }

    /**
     * Find songs from an album from an artist.
     *
     * @Route("/songs/{artistSlug}/{albumSlug}", name="artist_albums_songs", methods={"GET"})
     */
    public function showAlbumsSongs(ManagerRegistry $doctrine, Artist $artist, $albumSlug): Response
    {
        $album = $doctrine->getRepository('App\Entity\Album')->findOneByAlbumSlug($albumSlug);

        $songs = $doctrine->getRepository('App\Entity\Song')->findByArtistAndAlbum($artist->getName(), $album->getName());

        return $this->render('common/songs-list.html.twig', [
            'songs' => $songs,
        ]);
    }

    /**
     * List filtered artist entities.
     *
     * @Route("/artist/filter/", name="artist_filter_all", methods={"GET"})
     * @Route("/artist/filter/{filter}", name="artist_filter", methods={"GET"})
     */
    public function filterArtist(ManagerRegistry $doctrine, $filter = null): Response
    {
        $artists = $doctrine->getRepository('App\Entity\Artist')->findByFilter($filter);

        return $this->render('library/artist-nav-list.html.twig', [
            'artists' => $artists,
        ]);
    }

    /**
     * Load random songs.
     *
     * @Route("/songs/random", name="random_songs", methods={"GET"})
     */
    public function randomSongs(ManagerRegistry $doctrine, $number = 20): Response
    {
        $songs = $doctrine->getRepository('App:Song')->getRandom($number);

        return $this->render('common/songs-list.html.twig', [
            'songs' => $songs,
        ]);
    }
}
