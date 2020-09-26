<?php

namespace App\Controller;

use App\Entity\Config;
use App\Form\ConfigType;
use App\Repository\ConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/config")
 */
class ConfigController extends AbstractController
{
    /**
     * @Route("/{id}/edit", name="config_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Config $config): Response
    {
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('settings_index');
        }

        return $this->render('config/edit.html.twig', [
            'config' => $config,
            'form' => $form->createView(),
        ]);
    }
}
