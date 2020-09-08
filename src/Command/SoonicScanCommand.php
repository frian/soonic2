<?php

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


require_once(dirname(__FILE__).'/../../vendor/james-heinrich/getid3/getid3/getid3.php');

class SoonicScanCommand extends Command
{
    protected static $defaultName = 'soonic:scan';

    private $entityManager;
    private $projectDir;

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
        $tables = array('song', 'album', 'artist', 'artist_album');

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
        $types = array("mp3", "mp4", "oga", "wma", "wav", "mpg", "mpc", "m4a", "m4p", "flac");
        // -- counters
        $fileCount = 0;
        $skipCount = 0;
        $loadCount = 0;
        // -- folder
        $folderFileCount = 0;
        $currentFolder = null;
        $previousFolder = null;
        // -- artists
        $artists = array();
        $albums = array();
        $albumTags = array();

        $currentFolderFilesTags = array();
        $previousFolderFilesTags = array();


        // -- open sql files
        $sqlFiles = array();
        $sqlFilesPathes = array();
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

                $fileCount++;
                $folderFileCount++;

                $hasWarning = false;
                $warningOutput = '  <warning>no ';
                $warningTags = array();
                $warningActions = array();
                $warningActionsResult = array();

                $getID3 = new \getID3;

                $fileInfo = $getID3->analyze($file);

                \getid3_lib::CopyTagsToComments($fileInfo);

                $fileInfoComments = array();

                // -- create tags array
                $tags = array();

                if (!empty($fileInfo['comments'])) {
                    $fileInfoComments = $fileInfo['comments'];
                }
                elseif (!empty($fileInfo['asf']['comments'])) {
                    $fileInfoComments = $fileInfo['asf']['comments'];
                }
                else {

                    if ($guess) {

                        $hasWarning = true;

                        $artist = $this->previousFolder($file, 2);

                        array_push($warningTags, 'artist');
                        array_push($warningActions, 'guessing artist tag');
                        if ($artist) {
                            $tags['artist'] = $artist;
                            array_push($warningActionsResult, $artist);
                        }
                        else {
                            $this->printErrorMessage('no artist tag found', $file, $output);
                            $this->logErrorMessage('no artist tag found', $file, $logFile);
                            $skipCount++;
                            continue;
                        }

                        $album = $this->previousFolder($file, 1);

                        array_push($warningTags, 'album');
                        array_push($warningActions, 'guessing album tag');
                        if ($album) {
                            $tags['album'] = $album;
                            array_push($warningActionsResult, $album);
                        }
                        else {
                            $this->printErrorMessage('no album tag found', $file, $output);
                            $this->logErrorMessage('no album tag found', $file, $logFile);
                            $skipCount++;
                            continue;
                        }

                        array_push($warningTags, 'title');
                        array_push($warningActions, 'guessing title name');
                        $title = pathinfo($file, PATHINFO_FILENAME);
                        $tags['title'] = $title;
                        array_push($warningActionsResult, $title);
                    }
                    else {
                        $this->printErrorMessage('no tag found', $file, $output);
                        $this->logErrorMessage('no tag found', $file, $logFile);
                        $skipCount++;
                        continue;
                    }

                }


                /*
                 * -- Handle album --------------------------------------------
                 */
                if (empty($fileInfoComments['album']) && empty($tags['album'])) {

                    if ($guess) {

                        $hasWarning = true;
                        array_push($warningTags, 'album');
                        array_push($warningActions, 'guessing album name');

                        $album = $this->previousFolder($file, 1);

                        if ($album) {
                            $tags['album'] = $album;
                            array_push($warningActionsResult, $album);
                        }
                        else {
                            $this->printErrorMessage('no album tag found', $file, $output);
                            $this->logErrorMessage('no album tag found', $file, $logFile);
                            $skipCount++;
                            continue;
                        }
                    }
                    else {
                        $this->printErrorMessage('no album tag found', $file, $output);
                        $this->logErrorMessage('no album tag found', $file, $logFile);
                        $skipCount++;
                        continue;
                    }

                }
                else {
                    if (!empty($fileInfoComments['album'])) {
                        $tags['album'] = $fileInfoComments['album'][0];
                    }
                }


                /*
                 * -- Handle artist -------------------------------------------
                 */
                if (empty($fileInfoComments['artist']) && empty($tags['artist'])) {

                    if ($guess) {

                        $hasWarning = true;
                        array_push($warningTags, 'artist');
                        array_push($warningActions, 'guessing artist name');

                        $artist = $this->previousFolder($file, 2);

                        if ($artist) {
                            $tags['artist'] = $artist;
                            array_push($warningActionsResult, $artist);
                        }
                        else {
                            $this->printErrorMessage('no artist tag found', $file, $output);
                            $this->logErrorMessage('no artist tag found', $file, $logFile);
                            $skipCount++;
                            continue;
                        }
                    }
                    else {
                        $this->logErrorMessage('no artist tag found', $file, $logFile);
                        $skipCount++;
                        continue;
                    }
                }
                else {
                    if (!empty($fileInfoComments['artist'])) {
                        $tags['artist'] = $fileInfoComments['artist'][0];
                    }
                }


                /*
                 * -- Handle title --------------------------------------------
                 */
                if (empty($fileInfoComments['title']) && empty($tags['title'])) {

                    if ($guess) {

                        $hasWarning = true;
                        array_push($warningTags, 'title');
                        array_push($warningActions, 'guessing title name');

                        $title = pathinfo($file, PATHINFO_FILENAME);

                        $tags['title'] = $title;
                        array_push($warningActionsResult, $title);
                    }
                    else {
                        $this->printErrorMessage('no title tag found', $file, $output);
                        $this->logErrorMessage('no title tag found', $file, $logFile);
                        $skipCount++;
                        continue;
                    }
                }
                else {
                    if (!empty($fileInfoComments['title'])) {
                        $tags['title'] = $fileInfoComments['title'][0];
                    }
                }


                /*
                 * -- Handle artist id------------------------------------------
                 */
                $tags['artist'] = \strtoupper($tags['artist']);
                if (!\array_key_exists($tags['artist'], $artists)) {
                    $artists[$tags['artist']] = 0;
                    $artistId = count($artists);
                    fwrite($sqlFile['artist'], ''.PHP_EOL); // empty line used for scsn progress
                }
                else {
                    $artistId = array_search($tags['artist'],array_keys($artists)) + 1;
                }
                $tags['artistId'] = $artistId;


                /*
                 * -- Handle album id -----------------------------------------
                 */
                $tags['album'] = \ucwords(\mb_strtolower($tags['album']));
                if (!\array_key_exists($tags['album'], $albums)) {
                    $albums[$tags['album']] = 0;
                    $albumId = count($albums);
                }
                else {
                    $albumId = array_search($tags['album'],array_keys($albums)) + 1;
                }
                $tags['albumId'] = $albumId;


                /*
                 * -- Handle track number -------------------------------------
                 */
                if (!empty($fileInfoComments['track_number'])) {
                    if (!\preg_match("/[^\d+$]/", $fileInfoComments['track_number'][0])) {
                        \preg_match("/(\d+)/", $fileInfoComments['track_number'][0], $matches);
                        $tags['track_number'] = $matches[0];
                    }
                    else {
                        $tags['track_number'] = $fileInfoComments['track_number'][0];
                    }
                }
                else {
                    $hasWarning = true;
                    array_push($warningTags, 'track_number');
                    $tags['track_number'] = null;
                }


                /*
                 * -- Handle year ---------------------------------------------
                 */
                if (empty($fileInfoComments['year'])) {
                    if ( !empty($fileInfoComments['date']) ) {
                        $tags['year'] = $fileInfoComments['date'][0];
                    }
                    else {
                        $hasWarning = true;
                        array_push($warningTags, 'year');
                        $tags['year'] = null;
                    }
                }
                else {
                    $tags['year'] = $fileInfoComments['year'][0];
                }


                /*
                 * -- Handle duration -----------------------------------------
                 */
                if (!empty($fileInfo['playtime_string'])) {
                    $tags['duration'] = $fileInfo['playtime_string'];
                }
                else {
                    $tags['duration'] = null;
                    $hasWarning = true;
                    array_push($warningTags, 'duration');
                }


                /*
                 * -- Handle genre ---------------------------------------------
                 */
                 if (!empty($fileInfoComments['genre'])) {
                     $tags['genre'] = $fileInfoComments['genre'][0];
                 }
                 else {
                     $tags['genre'] = null;
                     $hasWarning = true;
                     array_push($warningTags, 'genre');
                 }


                /*
                 * -- Handle path and web path --------------------------------
                 */
                $tags['web_path'] = preg_replace("|^$webPath|", '', $file);
                $tags['path'] = $file;


                /*
                 * -- Build album list ----------------------------------------
                 */
                $folder = preg_replace("|^$webPath|", '', pathinfo($file, PATHINFO_DIRNAME));

                // -- add albumName key
                if ( !array_key_exists( 'albumName', $currentFolderFilesTags ) ) {
                    $currentFolderFilesTags['albumName'] = array();
                }

                // -- add album(s)
                if ( !array_key_exists( $tags['album'], $currentFolderFilesTags['albumName'] )) {
                    $currentFolderFilesTags['albumName'][$tags['album']] = array();
                }


                // -- add artistName key
                if ( !array_key_exists( 'artistName', $currentFolderFilesTags['albumName'][$tags['album']] )) {
                    $currentFolderFilesTags['albumName'][$tags['album']]['artistName'][$tags['artist']] = array();
                }

                // -- add artist(s)
                if ( !array_key_exists(
                    $tags['artist'],
                    $currentFolderFilesTags['albumName'][$tags['album']]['artistName'] )) {
                        $currentFolderFilesTags['albumName'][$tags['album']]['artistName'][$tags['artist']] = array();
                }

                // -- add titles key
                if ( !array_key_exists(
                    'titles',
                    $currentFolderFilesTags['albumName'][$tags['album']]['artistName'][$tags['artist']] ) ) {
                        $currentFolderFilesTags['albumName'][$tags['album']]['artistName'][$tags['artist']]['titles'] = array();
                }

                // -- add title(s)
                array_push(
                    $currentFolderFilesTags['albumName'][$tags['album']]['artistName'][$tags['artist']]['titles'],
                    $tags['title']);

                // -- add year key
                if ( !array_key_exists( 'years', $currentFolderFilesTags['albumName'][$tags['album']] ) ) {
                    $currentFolderFilesTags['albumName'][$tags['album']]['years'] = array();
                }

                // -- add year(s)
                if ($tags['year'] != null) {
                    if (!in_array($tags['year'], $currentFolderFilesTags['albumName'][$tags['album']]['years'])) {
                        array_push($currentFolderFilesTags['albumName'][$tags['album']]['years'],$tags['year']);
                    }
                }

                // -- add genre key
                if ( !array_key_exists( 'genres', $currentFolderFilesTags['albumName'][$tags['album']] ) ) {
                    $currentFolderFilesTags['albumName'][$tags['album']]['genres'] = array();
                }

                // -- add genre(s)
                if ($tags['genre'] != null) {
                    if (!in_array($tags['genre'], $currentFolderFilesTags['albumName'][$tags['album']]['genres'])) {
                        array_push($currentFolderFilesTags['albumName'][$tags['album']]['genres'],$tags['genre']);
                    }
                }

                // -- add duration key
                if ( !array_key_exists( 'durations', $currentFolderFilesTags['albumName'][$tags['album']] ) ) {
                    $currentFolderFilesTags['albumName'][$tags['album']]['durations'] = array();
                }

                // -- add durations
                if ($tags['duration'] != null) {
                    array_push($currentFolderFilesTags['albumName'][$tags['album']]['durations'],$tags['duration']);
                }

                // -- add pathes
                $currentFolderFilesTags['albumName'][$tags['album']]['web_path'] = $folder;
                $currentFolderFilesTags['albumName'][$tags['album']]['path'] = pathinfo($file, PATHINFO_DIRNAME);


                if ($hasWarning) {
                    $this->printWarningMessage($warningTags, $warningActions, $warningActionsResult, $file, $output);
                    $this->logWarningMessage($warningTags, $warningActions, $warningActionsResult, $file, $logFile);
                }

                // -- write to sql file
                fwrite(
                    $sqlFile['song'],';'
                    .$tags['albumId'].';'
                    .$tags['artistId'].';'
                    .$tags['path'].';'
                    .$tags['web_path'].';'
                    .$tags['title'].';'
                    .$tags['track_number'].';'
                    .$tags['year'].';'.$tags['genre'].';'.$tags['duration']
                    .PHP_EOL);

                $loadCount++;
            }
        }

        // -- output last folder
        $this->outputAlbumInfo($currentFolderFilesTags, $sqlFile['album'], $sqlFile['artist_album'], $artists, $albums);

        fclose($sqlFile['artist']);
        $sqlArtistFile = $this->openFile($sqlFilesPathes['artist'], $output, $lockFile);
        fwrite($sqlArtistFile, 'id,name,artist_slug,album_count,cover_art_path'.PHP_EOL);

        $slugs = array();
        foreach (array_keys($artists) as $artist) {
            $slug = $this->slugify($artist);
            $slugCount = 1;
            while (in_array($slug, $slugs)) {
                $slug = $slug . "_" . $slugCount;
                $slugCount++;
            }
            array_push($slugs, $slug);
            fwrite($sqlArtistFile, ';'.$artist. ';' . $slug. ';' .$artists[$artist].';'.PHP_EOL);
        }


        // -- disable foreign keys checks
        $query = 'SET FOREIGN_KEY_CHECKS=0;';
        $statement = $em->getConnection()->prepare($query)->execute();

        // -- enable local-infile
        $query = 'SET GLOBAL local_infile = true';
        $statement = $em->getConnection()->prepare($query)->execute();

        // -- bulk load collection
        foreach ($tables as $table) {
            $query = "LOAD DATA LOCAL INFILE '".$sqlFilesPathes[$table]."'".
                " INTO TABLE ". $table ." CHARACTER SET UTF8 FIELDS TERMINATED BY ';' " .
                " ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 1 ROWS;";
            $statement = $em->getConnection()->prepare($query)->execute();
        }

        // -- disable foreign keys checks
        $query = "SET FOREIGN_KEY_CHECKS=1;";
        $statement = $em->getConnection()->prepare($query)->execute();

        // -- disable local-infile
        $query = 'SET GLOBAL local_infile = false';
        $statement = $em->getConnection()->prepare($query)->execute();

        // -- final output
        if ($verbosity >= 64) {
            $output->writeln("analysed $fileCount files.");
            $output->writeln("loaded $loadCount files");
            $output->writeln("skipped $skipCount files");

            $end_time = microtime(true);
            $duration = $end_time - $start_time;

            if ($duration < 60) {
                $output_duration = gmdate('s\s', $duration);
            }
            elseif ($duration < 3600) {
                $output_duration = gmdate('i\ms\s', $duration);
            }
            else {
                $output_duration = gmdate('H\hi\ms\s', $duration);
            }

            $output->writeln("in $output_duration");
        }

        unlink($lockFile);

        return 0;
    }

    private function printErrorMessage($error, $file, $output) {
        $verbosity = $output->getVerbosity();
        if ($verbosity >= 64) {
            $warningOutput = '';
            $warningOutput .= "<error>$error</error>";
            $warningOutput .= " for ".$file;
            $warningOutput .= ' <error>-> skipping file.</error>';
            $output->writeln($warningOutput);
        }
    }

    private function logErrorMessage(string $error, string $file, $logFile) {
        fwrite($logFile, "[error]$error;$file;skipping file\n");
    }

    private function printWarningMessage($warningTags, $warningActions, $warningActionsResult, $file, OutputInterface $output) {

        $verbosity = $output->getVerbosity();
        if ($verbosity >= 128) {
            $warningOutput = 'no ';

            foreach ($warningTags as $key => $tag) {
                $warningOutput .= "<warning>$tag</warning> ";
            }

            $warningOutput .= "tag found for $file ";

            foreach ($warningActions as $key => $action) {
                $warningOutput .= "<warning>$action</warning> ";
                $warningOutput .= $warningActionsResult[$key]." ";
            }

            $output->writeln($warningOutput);
        }
    }

    private function logWarningMessage($warningTags, $warningActions, $warningActionsResult, $file, $logFile) {

        $warningOutput = '[warning]no ';

        foreach ($warningTags as $key => $tag) {
            $warningOutput .= "$tag ";
        }

        $warningOutput .= "tag found;$file;";

        foreach ($warningActions as $key => $action) {
            $warningOutput .= "$action;";
            $warningOutput .= $warningActionsResult[$key].";";
        }

        $warningOutput .= "\n";
        fwrite($logFile, $warningOutput);
    }

    private function previousFolder($file, $level) {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        $pathParts = explode('/', $path);
        for ($i = 1; $i <= $level ; $i++) {
            $folder = array_pop($pathParts);
            if (preg_match('/cd\d+/i', $folder)) {
                $folder = array_pop($pathParts);
            }
        }
        return $folder;
    }

    private function outputAlbumInfo($currentFolderFilesTags, $sqlAlbumFile, $sqlAlbumsArtists, &$artists, $albums) {

        $slugs = array();

        foreach (array_keys($currentFolderFilesTags['albumName']) as $album) {

            $songCount = 0;
            $albumArtist = '';
            foreach (array_keys($currentFolderFilesTags['albumName'][$album]['artistName']) as $index => $artist) {

                $artistId = array_search($artist,array_keys($artists)) + 1;
                $albumId = array_search($album,array_keys($albums)) + 1;

                fwrite($sqlAlbumsArtists, $artistId.";".$albumId.PHP_EOL);

                $artists[$artist]++;
                $songCount += count($currentFolderFilesTags['albumName'][$album]['artistName'][$artist]['titles']);
            }


            $albumYear = null;
            foreach ($currentFolderFilesTags['albumName'][$album]['years'] as $year) {
                $albumYear .= $year.',';
            }
            $albumYear = \preg_replace('/,$/', '', $albumYear);


            $albumGenre = null;
            foreach ($currentFolderFilesTags['albumName'][$album]['genres'] as $genre) {
                $albumGenre .= $genre.',';
            }
            $albumGenre = \preg_replace('/,$/', '', $albumGenre);


            $slug = $this->slugify($album);
            $slugCount = 1;
            while (in_array($slug, $slugs)) {
                $slug = $slug . "_" . $slugCount;
                $slugCount++;
            }
            array_push($slugs, $slug);

            //-- 'id,name,album_slug,song_count,duration,year,genre,path,cover_art_path'
            fwrite(
                $sqlAlbumFile,';'
                // print
                // ';'
                .$album.';'
                .$slug.';'
                .$songCount.';'
                .$this->getAlbumDuration($currentFolderFilesTags['albumName'][$album]['durations']).';'
                .$albumYear.';'
                .$albumGenre.';'
                .$currentFolderFilesTags['albumName'][$album]['web_path'].';'
                .';' // -- covert art path
                .PHP_EOL
            );
        }
        return $artists;
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

    private function slugify(string $string): string {
        $string = \mb_strtolower($string);
        $string = preg_replace('/\s+-\s+/', '-', $string);
        $string = preg_replace('/&/', 'and', $string);
        $string = preg_replace('|[\s+\/]|', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return $string;
    }

}
