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
     * @var string
     */
    private $kernelProjectDir;
    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }
    /**
     * Scan
     *
     * @Route("/", name="scan", methods={"GET"})
     */
    public function scan(): \Symfony\Component\HttpFoundation\Response {

        $projectDir = $this->kernelProjectDir;
        $lockFile = $projectDir.'/public/soonic.lock';

        $command = $projectDir.'/bin/console soonic:scan --guess';

        if (!file_exists($lockFile)) {
            exec("/usr/bin/php $command > /dev/null 2>&1 &");
        }

        return new Response('');
    }

    /**
     * Scan progress
     *
     * @Route("/progress", name="scan_progress", methods={"GET"})
     */
    public function scanProgress(): \Symfony\Component\HttpFoundation\Response {

        $projectDir = $this->kernelProjectDir;
        $lockFile = $projectDir.'/public/soonic.lock';
        $status = 'stopped';

        if (file_exists($lockFile)) {
            $status = 'running';
        }

        $files = array('song', 'artist', 'album');
        $data = array();
        foreach ($files as $file) {
            $filePath = $projectDir.'/public/soonic-'.$file.'.sql';
            if (file_exists($filePath)) {
                $file_handle = new \SplFileObject($projectDir.'/public/soonic-'.$file.'.sql', 'r');
                $file_handle->seek(PHP_INT_MAX);
                $data[$file] = $file_handle->key() - 1;
            }
            else {
                $data[$file] = 0;
            }
        }

        $response = ['status' => $status, 'data' => $data];
        return new JsonResponse($response);
    }

}
