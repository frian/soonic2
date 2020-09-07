<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SearchController extends AbstractController
{
    /**
     * @Route("/search", name="search")
     */
     public function showSearch(Request $request) {

         $form = $this->createFormBuilder()
             ->add('keyword', TextType::class)
             ->getForm();

         $form->handleRequest($request);

         if ($form->isSubmitted() && $form->isValid()) {

             $data = $form->getData();

             $em = $this->getDoctrine()->getManager();

             $results = $em->getRepository('App:Song')->findByKeyword($data['keyword']);

             return $this->render('common/songs-list.html.twig', array(
                 'mediaFiles' => $results,
             ));
         }

         return $this->render('common/search.html.twig', array(
             'form' => $form->createView(),
         ));
     }
}
