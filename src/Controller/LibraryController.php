<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use App\Repository\AlbumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class LibraryController extends AbstractController
{
    /**
     * @Route("/", name="library", methods={"GET"})
     */
    public function library(ArtistRepository $artistRepository, Request $request): \Symfony\Component\HttpFoundation\Response {

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
    public function showArtistAlbums(Artist $artist): \Symfony\Component\HttpFoundation\Response {

        $albums = $artist->getAlbums();

        return $this->render('library/album-nav-list.html.twig', array(
            'albums' => $albums,
            'artist' => $artist->getArtistSlug()
        ));
    }

    /**
     * Find songs from an album from an artist.
     *
     * @Route("/songs/{artistSlug}/{albumSlug}", name="artist_albums_songs", methods={"GET"})
     */
    public function showAlbumsSongs(Artist $artist, $albumSlug): \Symfony\Component\HttpFoundation\Response {

        $em = $this->getDoctrine()->getManager();

        $album = $em->getRepository('App:Album')->findOneByAlbumSlug($albumSlug);

        $songs = $em->getRepository('App:Song')->findByArtistAndAlbum($artist->getName(), $album->getName());

        return $this->render('common/songs-list.html.twig', array(
            'songs' => $songs
        ));
    }

    /**
     * List filtered artist entities.
     *
     * @Route("/artist/filter/", name="artist_filter_all", methods={"GET"})
     * @Route("/artist/filter/{filter}", name="artist_filter", methods={"GET"})
     */
    public function filterArtist($filter = null): \Symfony\Component\HttpFoundation\Response {

        $em = $this->getDoctrine()->getManager();

        $artists = $em->getRepository('App:Artist')->findByFilter($filter);

        return $this->render('library/artist-nav-list.html.twig', array(
            'artists' => $artists,
        ));
    }

    /**
     * Load random songs.
     *
     * @Route("/songs/random", name="random_songs", methods={"GET"})
     */
    public function randomSongs($number = 20): \Symfony\Component\HttpFoundation\Response {

        $em = $this->getDoctrine()->getManager();

        $songs = $em->getRepository('App:Song')->getRandom($number);

        return $this->render('common/songs-list.html.twig', array(
            'songs' => $songs
        ));
    }

}
