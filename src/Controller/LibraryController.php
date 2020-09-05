<?php

namespace App\Controller;

use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

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
}
