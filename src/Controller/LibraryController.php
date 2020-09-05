<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class LibraryController extends AbstractController
{
    /**
     * @Route("/", name="library")
     */
    public function index(ArtistRepository $artistRepository)
    {
        return $this->render('library/screen.html.twig', [
            'artists' => $artistRepository->findAll(),
        ]);
    }

    /**
     * Find albums from an artist.
     *
     * @Route("/albums/{artistSlug}", name="artist_albums")
     * @Method("GET")
     */
    public function showArtistAlbums(Artist $artist) {

        $em = $this->getDoctrine()->getManager();

        $albums = $artist->getAlbums();

        return $this->render('library/album-nav-list.html.twig', array(
            'albums' => $albums,
            'artist' => $artist->getArtistSlug()
        ));
    }

    /**
     * Find songs from an album from an artist.
     *
     * @Route("/songs/{artistSlug}/{albumSlug}", name="artist_albums_songs")
     * @Method("GET")
     */
    public function showAlbumsSongs(Artist $artist, $albumSlug) {

        $em = $this->getDoctrine()->getManager();

        $album = $em->getRepository('App:Album')->findOneByAlbumSlug($albumSlug);

        $songs = $em->getRepository('App:Song')->findByArtistAndAlbum($artist->getName(), $album->getName());

        // $songs = $album->getSongs();

        return $this->render('common/songs-list.html.twig', array(
            'mediaFiles' => $songs
        ));
    }

    /**
     * List filtered artist entities.
     *
     * @Route("/artist/filter/", name="artist_filter_all")
     * @Route("/artist/filter/{filter}", name="artist_filter")
     * @Method("GET")
     */
    public function filterArtist($filter = null) {

        $em = $this->getDoctrine()->getManager();

        $artists = $em->getRepository('App:Artist')->findByFilter($filter);

        return $this->render('library/artist-nav-list.html.twig', array(
            'artists' => $artists,
        ));
    }
}
