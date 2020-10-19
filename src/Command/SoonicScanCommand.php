<?php

// declare(strict_types=1);

namespace App\Command;


// use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

use Doctrine\ORM\EntityManagerInterface;

if (ob_get_level())
   ob_end_clean();
require_once(dirname(__FILE__).'/../../vendor/james-heinrich/getid3/getid3/getid3.php');

class SoonicScanCommand extends Command
{
    protected static $defaultName = 'soonic:scan';

    private $entityManager;
    private $projectDir;
    private $filfileSystem;

    public function __construct(EntityManagerInterface $entityManager, string $projectDir, Filesystem $fileSystem)
    {
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
        $this->fileSystem = $fileSystem;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('soonic:scan')
            ->setDescription('scan folders')
            ->setHelp("\nscan folders and create database\n")
            ->addOption('guess', null, InputOption::VALUE_NONE, 'If defined, guess tags')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start_time = microtime(true);

        $webPath = str_replace('\\', '/', $this->projectDir.'/public');
        $lockFile = $webPath.'/soonic.lock';

        // -- exit if there is a lock file
        if (file_exists($lockFile)) {
            $output->writeln("  <info>already running");
            exit(1);
        }

        // -- create loack file
        try {
            touch($lockFile);
        }
        catch(Exception $e) {
            $output->writeln('<error>'.$e->getMessage());
            exit(1);
        }


        // -- add style
        $style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('error', $style);

        $style = new OutputFormatterStyle('white', 'magenta');
        $output->getFormatter()->setStyle('warning', $style);


        // -- open log file
        $logFile = $this->openFile($webPath.'/soonic.log', $output, $lockFile);


        // -- get verbosity
		$verbosity = $output->getVerbosity();

        // get --guess option
        $guess = $input->getOption('guess');

		// -- get entity manager
		$em = $this->entityManager;

        // -- clear media file table
        $tables = ['song', 'album', 'artist', 'artist_album'];

        foreach ($tables as $table) {
            if ($verbosity >= 128) {
                $output->write("  clear table $table .");
            }

            $query = "DELETE FROM $table";
            $statement = $em->getConnection()->prepare($query)->execute();
            if ($verbosity >= 128) {
                $output->write('.');
            }

            if ($table != 'albums_artists') {
                $query = "ALTER TABLE $table AUTO_INCREMENT = 1;";
                $statement = $em->getConnection()->prepare($query)->execute();
            }


            if ($verbosity >= 128) {
                $output->writeln('. done');
            }
        }


        /*
         * -- Scan variables
         */
        // -- folder to scan
        $root = $webPath.'/music';

        if (!$this->fileSystem->exists($root)) {
            $output->writeln('<error>  music folder not found');
            unlink($lockFile);
            exit(1);
        }

        // -- file types
        $types = ["mp3", "mp4", "oga", "wma", "wav", "mpg", "mpc", "m4a", "m4p", "flac"];
        // -- counters
        // $fileCount = 0;
        // $skipCount = 0;
        // $loadCount = 0;
        // -- folder
        // $folderFileCount = 0;
        $currentFolder = null;
        $previousFolder = null;
        // -- artists
        $artists = [];
        $albums = [];

        $albumTags = [];
        $albumSingleTags = ['album', 'year', 'genre'];
        // $currentFolderFilesTags = [];
        // $previousFolderFilesTags = [];

        $status = 'same';

        $albumsSlugs = [];


        // -- open sql files
        $sqlFiles = [];
        $sqlFilesPathes = [];
        foreach ($tables as $table) {
            $sqlFilesPathes[$table] = str_replace('\\', '/', $webPath.'/soonic-'.$table.'.sql');
            $sqlFile[$table] = $this->openFile($sqlFilesPathes[$table], $output, $lockFile);
        }


        // -- write headers
        fwrite($sqlFile['song'],
            'id,album_id,artist_id,path,web_path,title,track_number,year,genre,duration'.PHP_EOL);
        fwrite($sqlFile['album'], 'id,name,album_slug,song_count,duration,year,genre,path,cover_art_path'.PHP_EOL);
        fwrite($sqlFile['artist'], PHP_EOL); // empty line used for scsn progress
        fwrite($sqlFile['artist_album'], 'artist_id,album_id'.PHP_EOL);




        // -- scan
        try {
            $di = new \RecursiveDirectoryIterator($root,\RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        }
        catch(Exception $e) {
            $output->writeln('<error> !!! '.$e->getMessage());
            unlink($lockFile);
            exit(1);
        }

        $it = new \RecursiveIteratorIterator($di);


        foreach($it as $file) {
            if ( in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $types) ) {

                $file = str_replace('\\', '/', $file);
                $currentFolder = preg_replace("|^$webPath|", '', pathinfo($file, PATHINFO_DIRNAME));
                if ($currentFolder !== $previousFolder) {
                    if ($previousFolder !== null) {
                        $this->outputAlbumSql($albumTags, $albumsSlugs);
                        $albumTags = [];
                    }
                    $this->_debug('New folder : '.$currentFolder);
                    $previousFolder = $currentFolder;
                    $status = 'new';
                }
                else {
                    $status = 'same';
                }


                // -- get track tags
                $getID3 = new \getID3;

                $fileInfo = $getID3->analyze($file);

                \getid3_lib::CopyTagsToComments($fileInfo);


                /*
                 * -- Build track tags ----------------------------------------
                 */
                $trackTags = [];

                // -- copy tags or skip file
                if (!empty($fileInfo['comments'])) {
                    $trackTags = $fileInfo['comments'];
                }
                elseif (!empty($fileInfo['asf']['comments'])) {
                    $trackTags = $fileInfo['asf']['comments'];
                }
                else {
                    print "No tags - skipping $file\n";
                    continue;
                }


                // -- store formatted tags
                $tags = [];

                if (!empty($trackTags['album'])) {
                    $tags['album'] = $trackTags['album'][0];
                }
                else {
                    print "No album tag - skipping \n";
                    continue;
                }


                if (!empty($trackTags['artist'])) {
                    $tags['artist'] = $trackTags['artist'][0];
                }
                else {
                    print "No artist tag - skipping \n";
                    continue;
                }


                if (!empty($trackTags['title'])) {
                    $tags['title'] = $trackTags['title'][0];
                }
                else {
                    print "No title tag - skipping \n";
                    continue;
                }


                if (!empty($trackTags['track_number'])) {
                    if (!preg_match("/[^\d+$]/", $trackTags['track_number'][0])) {
                        preg_match("/(\d+)/", $trackTags['track_number'][0], $matches);
                        $tags['track_number'] = $matches[0];
                    }
                    else {
                        $tags['track_number'] = $trackTags['track_number'][0];
                    }
                }
                else {
                    print "No track_number tag - skipping \n";
                    continue;
                }


                if (empty($trackTags['year'])) {
                    if ( !empty($trackTags['date']) ) {
                        $tags['year'] = $trackTags['date'][0];
                    }
                    else {
                        print "No track_number tag - skipping \n";
                        continue;
                    }
                }
                else {
                    $tags['year'] = $trackTags['year'][0];
                }


                if (!empty($trackTags['genre'])) {
                    $tags['genre'] = $trackTags['genre'][0];
                }
                else {
                    print "No genre tag - skipping \n";
                    continue;
                }


                if (!empty($fileInfo['playtime_string'])) {
                    $tags['duration'] = $fileInfo['playtime_string'];
                }
                else {
                    print "No duration tag - skipping \n";
                    continue;
                }


                $tags['web_path'] = preg_replace("|^$webPath|", '', $file);
                $tags['path'] = $file;



                $tags['artist'] = mb_strtoupper($tags['artist']);
                if (!in_array($tags['artist'], $artists)) {
                    array_push($artists, $tags['artist']);
                    $artistId = count($artists);
                    // fwrite($sqlFile['artist'], ''.PHP_EOL); // empty line used for scan progress
                }
                else {
                    $artistId = array_search($tags['artist'], $artists) + 1;
                }
                $tags['artist_id'] = $artistId;


                $tags['album'] = ucwords(mb_strtolower($tags['album']));
                if (!in_array($tags['album'], $albums) || $status === 'new') {
                    array_push($albums, $tags['album']);
                }
                $tags['album_id'] = count($albums);

                $tags['web_path'] = preg_replace("|^$webPath|", '', $file);
                $tags['path'] = $file;



                /*
                 * -- Build album tags ----------------------------------------
                 */

                foreach ($albumSingleTags as $tag) {
                    if (!array_key_exists($tags[$tag], $albumTags)) {
                        $albumTags[$tag] = $tags[$tag];
                    }
                }


                if ( !array_key_exists( 'artists', $albumTags) ) {
                    $albumTags['artists'] = array();
                }
                if ( !in_array($tags['artist'], $albumTags['artists'] )) {
                    // $albumTags['artists'][$tags['artist']] = null;
                    array_push($albumTags['artists'], $tags['artist']);
                }
                // array_push($albumTags['artists'], $tags['artist']);




                if ( !array_key_exists( 'durations', $albumTags) ) {
                    $albumTags['durations'] = array();
                }
                array_push($albumTags['durations'], $tags['duration']);



                $albumTags['path'] = preg_replace("|^$webPath|", '', pathinfo($file, PATHINFO_DIRNAME));




                if ($currentFolder !== $previousFolder) {
                    // $previousFolder = $currentFolder;
                }
                else {

                }

                // print_r($tags);

            }
        }

        $this->outputAlbumSql($albumTags, $albumsSlugs);
// print_r($artists);
// print_r($albums);

        unlink($lockFile);
        return 0;
    }


    private function _debug($string) {
        print "$string\n";
    }


    private function openFile($filePath, OutputInterface $output, $lockFile) {
        try {
            $file = fopen($filePath, 'w');
            return $file;
        }
        catch(Exception $e) {
            $output->writeln('<error>'.$e->getMessage());
            unlink($lockFile);
            exit(1);
        }
    }

    private function outputAlbumSql(array $albumTags, array &$albumsSlugs) {

        print "Album Name        : " . $albumTags['album'] . "\n";
        print "Album Year        : " . $albumTags['year'] . "\n";
        print "Album Genre       : " . $albumTags['genre'] . "\n";
        print "Album Path        : " . $albumTags['path'] . "\n";
        print "Album Duration    : " . $this->getAlbumDuration($albumTags['durations']) . "\n";
        print "Album Song Count  : " . count($albumTags['durations']) . "\n";
        print "Album Slug        : " . $this->slugify($albumTags['album'], $albumsSlugs) . "\n";

    }

    private function getAlbumDuration(array $durations): string {
        $hrs = 0;
        $mins = 0;
        $secs = 0;
        foreach ($durations as $duration) {
            $durationParts = explode(':', $duration);
            $numDurationParts = count($durationParts);
            if ($numDurationParts === 1) {
                $secs += (int) $durationParts[0];
            }
            elseif ($numDurationParts === 2) {
                $mins += (int) $durationParts[0];
                $secs += (int) $durationParts[1];
            }
            elseif ($numDurationParts === 3) {
                $hrs += (int) $durationParts[0];
                $mins += (int) $durationParts[1];
                $secs += (int) $durationParts[2];
            }
            // Convert each 60 minutes to an hour
            if ($mins >= 60) {
                $hrs++;
                $mins -= 60;
            }
            // Convert each 60 seconds to a minute
            if ($secs >= 60) {
                $mins++;
                $secs -= 60;
            }
        }
        $hrs = $hrs > 9 ? $hrs : 0 . $hrs;
        $mins = $mins > 9 ? $mins : 0 . $mins;
        $secs = $secs > 9 ? $secs : 0 . $secs;
        $returnValue = $secs;
        if ($hrs != 0) {
            $returnValue =  $hrs.":".$mins.":".$returnValue;
        }
        else {
            if ($mins != 0) {
                $returnValue =  $mins.":".$returnValue;
            }
        }
        return $returnValue;
    }


    private function slugify(string $string, array &$slugs): string {
        $string = mb_strtolower($string);
        $string = preg_replace('/\s+-\s+/', '-', $string);
        $string = preg_replace('/&/', 'and', $string);
        $string = preg_replace('|[\s+\/]|', '-', $string);
        $string = preg_replace('/-+/', '-', $string);

        $slug = $string;
        $slugCount = 1;
        while (in_array($slug, $slugs)) {
            $slug = $string . "-" . $slugCount;
            $slugCount++;
        }
        array_push($slugs, $slug);
        return $slug;
    }
}
