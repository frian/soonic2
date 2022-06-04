<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Settings controller.
 *
 * @Route("settings")
 */
class SettingsController extends AbstractController
{
    /**
     * Show settings page.
     *
     * @Route("/", name="settings_index", methods={"GET"})
     */
    public function index(ManagerRegistry $doctrine, Request $request): Response
    {
        // -- get collection infos
        $tables = ['song', 'artist', 'album'];
        $infos = [];

        foreach ($tables as $table) {
            $query = "select max(id) from $table";
            $statement = $doctrine->getConnection()->prepare($query);
            $result = $statement->execute();
            $result = $result->fetchAssociative()['max(id)'];
            if ($result === null) {
                $result = 0;
            }
            \array_push($infos, $result);
        }

        // -- get config form
        $config = $doctrine->getRepository('App\Entity\Config')->find(1);
        if (! $config) {
            die;
        }
        $editForm = $this->createForm('App\Form\ConfigType', $config);
        $editForm->handleRequest($request);

        return $this->renderForm('settings/index.html.twig', [
            'infos' => $infos,
            'edit_form' => $editForm,
        ]);
    }
}
