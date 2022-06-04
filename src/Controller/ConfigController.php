<?php

namespace App\Controller;

use App\Entity\Config;
use App\Form\ConfigType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @Route("/config")
 */
class ConfigController extends AbstractController
{
    /**
     * @Route("/{id}/edit", name="config_edit", methods={"GET","POST"})
     */
    public function edit(ManagerRegistry $doctrine, Request $request, Config $config): JsonResponse
    {
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $config = $request->request->get('config');

            $theme = $doctrine->getRepository('App\Entity\Theme')->find($config['theme']);
            $theme = $theme->getName();

            $lang = $doctrine->getRepository('App\Entity\Language')->find($config['language']);
            $lang = $lang->getCode();

            $translations = Yaml::parse(file_get_contents('../translations/messages.'.$lang.'.yml'));

            $config = [];
            $config['theme'] = $theme;
            $config['translations'] = $translations;

            return new JsonResponse(
                ['data' => 'success',
                'config' => $config,
            ]);
        }

        return new JsonResponse(['data' => 'error']);
    }
}
