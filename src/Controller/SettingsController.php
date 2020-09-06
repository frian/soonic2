<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Settings controller.
 *
 * @Route("settings")
 */

class SettingsController extends AbstractController
{
    /**
     * Show settings page
     *
     * @Route("/", name="settings_index")
     * @Method("GET")
     */
    public function index(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // -- get collection infos
        $tables = array('song', 'artist', 'album');
        $infos = array();

        foreach ($tables as $table) {
            $query = "select max(id) from $table";
            $statement = $em->getConnection()->prepare($query);
            $statement->execute();
            $result = $statement->fetch()['max(id)'];
            if ($result === null) {
                $result = 0;
            }
            \array_push($infos, $result);
        }

        // -- get config form
        $config = $em->getRepository('App:Config')->find(1);
        $editForm = $this->createForm('App\Form\ConfigType', $config);
        $editForm->handleRequest($request);

        return $this->render('settings/index.html.twig', array(
            'infos' => $infos,
            'edit_form' => $editForm->createView()
        ));
    }
}
