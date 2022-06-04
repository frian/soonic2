<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class SearchController extends AbstractController
{
    /**
     * @Route("/search", name="search")
     */
    public function showSearch(ManagerRegistry $doctrine, Request $request): Response
    {
        $form = $this->createFormBuilder()
             ->add('keyword', TextType::class)
             ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $results = $doctrine->getRepository('App\Entity\Song')->findByKeyword($data['keyword']);

            return $this->render('common/songs-list.html.twig', [
                 'songs' => $results,
             ]);
        }

        return $this->render('common/search.html.twig', [
             'form' => $form->createView(),
         ]);
    }
}
