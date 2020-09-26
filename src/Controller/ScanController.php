<?php

namespace App\Controller;

// use AppBundle\Entity\Album;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;


/**
 * Album controller.
 *
 * @Route("scan")
 */
class ScanController extends AbstractController {

    /**
     * Scan
     *
     * @Route("/", name="scan", methods={"GET"})
     */
    public function scan() {

        $projectDir = $this->getParameter('kernel.project_dir');
        $lockFile = $projectDir.'/public/soonic.lock';

        $command = $projectDir.'/bin/console soonic:scan --guess';

        if (!file_exists($lockFile)) {
            Process::fromShellCommandline("/usr/bin/php $command")->start();
        }

        return new Response('');
    }

    /**
     * Scan progress
     *
     * @Route("/progress", name="scan_progress", methods={"GET"})
     */
    public function scanProgress() {

        $projectDir = $this->getParameter('kernel.project_dir');
        $lockFile = $projectDir.'/public/soonic.lock';
        $status = 'stopped';

        if (file_exists($lockFile)) {
            $status = 'running';
        }

        $files = array('song', 'artist', 'album');
        $data = array();
        foreach ($files as $file) {
            $file_handle = new \SplFileObject($projectDir.'/public/soonic-'.$file.'.sql', 'r');
            $file_handle->seek(PHP_INT_MAX);
            $data[$file] = $file_handle->key() - 1;
        }

        $response = ['status' => $status, 'data' => $data];
        return new JsonResponse($response);
    }

}
