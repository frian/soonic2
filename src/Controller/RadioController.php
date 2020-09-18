<?php

namespace App\Controller;

use App\Entity\Radio;
use App\Form\RadioType;
use App\Repository\RadioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/radio")
 */
class RadioController extends AbstractController
{
    /**
     * @Route("/", name="radio_index", methods={"GET"})
     */
    public function index(RadioRepository $radioRepository): Response
    {
        return $this->render('radio/index.html.twig', [
            'radios' => $radioRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="radio_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $radio = new Radio();
        $form = $this->createForm(RadioType::class, $radio);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($radio);
            $entityManager->flush();

            return $this->redirectToRoute('radio_index');
        }

        return $this->render('radio/new.html.twig', [
            'radio' => $radio,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="radio_show", methods={"GET"})
     */
    public function show(Radio $radio): Response
    {
        return $this->render('radio/show.html.twig', [
            'radio' => $radio,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="radio_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Radio $radio): Response
    {
        $form = $this->createForm(RadioType::class, $radio);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('radio_index');
        }

        return $this->render('radio/edit.html.twig', [
            'radio' => $radio,
            'edit_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="radio_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Radio $radio): Response
    {
        if ($this->isCsrfTokenValid('delete'.$radio->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($radio);
            $entityManager->flush();
        }

        return $this->redirectToRoute('radio_index');
    }
}
