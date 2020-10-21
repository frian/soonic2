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
            ->setHelp(PHP_EOL."scan folders and create database".PHP_EOL)
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
        $fileCount = 0;
        $skipCount = 0;
        $loadCount = 0;
        // -- folder
        $currentFolder = null;
        $previousFolder = null;
        // -- artists ans album lists
        $songs = [];
        $artists = [];
        $albums = [];
        $folderAlbums = [];
        $albumId = 0;

        $albumsTags = [];
        $albumSingleTags = ['album', 'year', 'genre'];

        $status = 'same';

        $albumsSlugs = [];
        $artistsSlugs = [];

        $hasWarning = false;
        $warningTags = array();

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

$debugCnt = 0;
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


                $fileCount++;
                $file = str_replace('\\', '/', $file);
                $currentFolder = preg_replace("|^$webPath|", '', pathinfo($file, PATHINFO_DIRNAME));

                if ($currentFolder !== $previousFolder) {
                    $debugCnt++;
                    if ($previousFolder !== null) {

                        if (!empty($songs)) {
                            $results = $this->AddAlbumIds($songs, $albumId);
                            $albumId = $results['album_id'];
                            $songs = $results['songs'];
                            $this->buildAlbumTags($songs, $albumsSlugs);
                        }

                        if ($debugCnt == 6) {
                            unlink($lockFile);
                            return 0;
                        }

                        // $this->outputAlbumSql($albumsTags, $albumsSlugs, $artists, $sqlFile['album'], $sqlFile['artist_album']);
                        $songs = [];
                        $albumsTags = [];
                        $folderAlbums = [];
                    }
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
                    $skipCount = $this->skipFile("No tags - skipping $file", $file, $output, $verbosity, $logFile, $skipCount);
                    continue;
                }


                // -- store formatted tags
                $tags = [];

                if (!empty($trackTags['album'])) {
                    $tags['album'] = $trackTags['album'][0];
                }
                else {
                    $skipCount = $this->skipFile("No album tag - skipping $file", $file, $output, $verbosity, $logFile, $skipCount);
                    continue;
                }

                if (!empty($trackTags['artist'])) {
                    $tags['artist'] = $trackTags['artist'][0];
                }
                else {
                    $skipCount = $this->skipFile("No artist tag - skipping $file", $file, $output, $verbosity, $logFile, $skipCount);
                    continue;
                }

                if (!empty($trackTags['title'])) {
                    $tags['title'] = $trackTags['title'][0];
                }
                else {
                    $skipCount = $this->skipFile("No title tag - skipping $file", $file, $output, $verbosity, $logFile, $skipCount);
                    continue;
                }

                if (!empty($fileInfo['playtime_string'])) {
                    $tags['duration'] = $fileInfo['playtime_string'];
                }
                else {
                    $skipCount = $this->skipFile("No duration tag - skipping $file", $file, $output, $verbosity, $logFile, $skipCount);
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
                    $hasWarning = true;
                    array_push($warningTags, 'track_number');
                    $tags['track_number'] = null;
                }

                if (empty($trackTags['year'])) {
                    if ( !empty($trackTags['date']) ) {
                        $tags['year'] = $trackTags['date'][0];
                    }
                    else {
                        $hasWarning = true;
                        array_push($warningTags, 'year');
                        $tags['year'] = null;
                    }
                }
                else {
                    $tags['year'] = $trackTags['year'][0];
                }

                if (!empty($trackTags['genre'])) {
                    $tags['genre'] = $trackTags['genre'][0];
                }
                else {
                    $tags['genre'] = null;
                    $hasWarning = true;
                    array_push($warningTags, 'genre');
                }

                $tags['web_path'] = preg_replace("|^$webPath|", '', $file);
                $tags['path'] = $file;


                if ( !array_key_exists( 'artists_ids', $albumsTags) ) {
                    $albumsTags['artists_ids'] = array();
                }
                $tags['artist'] = mb_strtoupper($tags['artist']);
                if (!\array_key_exists($tags['artist'], $artists)) {
                    $artists[$tags['artist']] = 0;
                    $artistId = count($artists);
                }
                else {
                    $artistId = array_search($tags['artist'],array_keys($artists)) + 1;
                }

                if (!in_array($artistId, $albumsTags['artists_ids'])) {
                    array_push($albumsTags['artists_ids'], $artistId);
                }
                $tags['artist_id'] = $artistId;

                $tags['album'] = ucwords(mb_strtolower($tags['album']));

                $tags['web_path'] = preg_replace("|^$webPath|", '', $file);
                $tags['path'] = $file;

                $tags['album_path'] = preg_replace("|^$webPath|", '', pathinfo($file, PATHINFO_DIRNAME));


                if ($hasWarning) {
                    $this->printWarningMessage($warningTags, $file, $output, $verbosity);
                    $this->logWarningMessage($warningTags, $file, $logFile, $verbosity);
                }


                if ($currentFolder !== $previousFolder) {
                    // $previousFolder = $currentFolder;
                }
                else {

                }

                array_push($songs, $tags);

                $loadCount++;
            }
        }

        $this->outputAlbumSql($albumsTags, $albumsSlugs, $artists, $sqlFile['album'], $sqlFile['artist_album']);

        // -- write artist tags to sql file
        // -- name,artist_slug,album_count,cover_art_path
        foreach ($artists as $artist => $albumCount) {
            fwrite($sqlFile['artist'],';'.
                $artist.';'.
                $this->slugify($artist, $artistsSlugs).';'.
                $albumCount.';'.
                PHP_EOL
            );
        }


        /*
         * -- Build album tags ------------------------------------------------
         */
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

        // -- enable foreign keys checks
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


    private function _debug($string) {
        print "$string".PHP_EOL;
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

    // - Album tags to sql
    private function outputAlbumSql(array $albumsTags, array &$albumsSlugs, array &$artists, $sqlAlbumFile, $sqlArtistAlbumFile) {

        if (!empty($albumsTags)) {
            // -- write album tags to sql file
            // -- name,album_slug,song_count,duration,year,genre,path,cover_art_path
            // print_r($albumsTags);
            // print $albumsTags['album'].' - '.$albumsTags['path']."\n";
            fwrite($sqlAlbumFile,';'.
                $albumsTags['album'].';'.
                $this->slugify($albumsTags['album'], $albumsSlugs).';'.
                count($albumsTags['durations']).';'.
                $this->getAlbumDuration($albumsTags['durations']).';'.
                $albumsTags['year'].';'.
                $albumsTags['genre'].';'.
                $albumsTags['path'].';'.
                ';'. // -- covert art path
                PHP_EOL
            );

            // -- write artist_album to sql  file
            $keys = array_keys($artists);
            foreach ($albumsTags['artists_ids'] as $artistId) {
                // print $artistId.';'.$albumsTags['album_id'][0]."\n";
                // print $keys[$artistId - 1]."\n";
                // fwrite($sqlArtistAlbumFile, $artistId.';'.$albumsTags['album_id'] . PHP_EOL);
                $artists[$keys[$artistId - 1]]++;
            }
        }
    }

    private function getAlbumDuration(array $durations): string {
        $secs = 0;
        foreach ($durations as $duration) {
            $durationParts = explode(':', $duration);
            $numDurationParts = count($durationParts);
            if ($numDurationParts === 1) {
                $secs += (int) $durationParts[0];
            }
            elseif ($numDurationParts === 2) {
                $secs += (int) $durationParts[0] * 60;
                $secs += (int) $durationParts[1];
            }
            elseif ($numDurationParts === 3) {
                $secs += (int) $durationParts[0] * 3600;
                $secs += (int) $durationParts[1] * 60;
                $secs += (int) $durationParts[2];
            }
        }
        $secs = $secs > 9 ? $secs : 0 . $secs;

        return $secs;
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

    private function printErrorMessage(string $error, string $file, $output, int $verbosity) {
        if ($verbosity >= 64) {
            $output->writeln("<error>$error</error>");
        }
    }

    private function logErrorMessage(string $error, string $file, $logFile) {
        fwrite($logFile, "[error]$error;$file;skipping file".PHP_EOL);
    }

    private function printWarningMessage($warningTags, $file, OutputInterface $output, $verbosity) {
        if ($verbosity >= 128) {
            $warningOutput = 'no ';
            foreach ($warningTags as $key => $tag) {
                $warningOutput .= "<warning>$tag</warning> ";
            }
            $warningOutput .= "tag found for $file ";
            $output->writeln($warningOutput);
        }
    }

    private function logWarningMessage($warningTags, $file, $logFile) {
        $warningOutput = '[warning]no ';
        foreach ($warningTags as $key => $tag) {
            $warningOutput .= "$tag ";
        }
        $warningOutput .= "tag found;$file;";
        $warningOutput .= "\n";
        fwrite($logFile, $warningOutput);
    }

    private function skipFile(string $error, string $file, $output, int $verbosity, $logFile, $skipCount) {
        $this->printErrorMessage($error, $file, $output, $verbosity);
        $this->logErrorMessage($error, $file, $logFile, $verbosity);
        return ++$skipCount;
    }

    private function AddAlbumIds($songs, $albumId) {
        $albums = [];
        $ids = [];
        foreach ($songs as $song) {
            if (!in_array($song['album'], $albums)) {
                array_push($albums, $song['album']);
                $ids[$song['album']] = ++$albumId;
            }
        }

        $artistAlbumValues = [];
        foreach ($songs as &$song) {
            $song['album_id'] = $ids[$song['album']];

            $artistAlbumValue = $song['artist_id'].";".$song['album_id'];
            if (!in_array($artistAlbumValue, $artistAlbumValues)) {
                array_push($artistAlbumValues, $artistAlbumValue);
            }
            print_r($song);
        }
        // print_r($artistAlbumValues); // TODO : write to artist-album.sql
        return ['album_id' => $albumId, 'songs' => $songs];
    }

    private function buildAlbumTags($songs, $albumsSlugs) {
        $albumSingleTags = ['year', 'genre', 'album_path'];
        $albumsTags = [];
        foreach ($songs as $song) {

            if ( !array_key_exists( 'albums', $albumsTags) ) {
                $albumsTags['albums'] = array();
            }
            if ( !in_array($song['album'], $albumsTags['albums'] )) {
                array_push($albumsTags['albums'], $song['album']);
            }


            if ( !array_key_exists( 'artists', $albumsTags) ) {
                $albumsTags['artists'] = array();
            }
            if ( !in_array($song['artist'], $albumsTags['artists'] )) {
                array_push($albumsTags['artists'], $song['artist']);
            }


            if ( !array_key_exists( 'durations', $albumsTags) ) {
                $albumsTags['durations'] = array();
            }
            array_push($albumsTags['durations'], $song['duration']);
        }

        foreach ($albumsTags['albums'] as $album) {
            $albumTags = [];
            foreach ($songs as $song) {
                if ($song['album'] === $album) {
                    foreach ($albumSingleTags as $tag) {
                        if (!array_key_exists($song[$tag], $albumTags)) {
                            $albumTags[$tag] = $song[$tag];
                        }
                    }

                    $albumTags['album'] = $album;

                    if ( !array_key_exists( 'artists', $albumTags) ) {
                        $albumTags['artists'] = array();
                    }
                    if ( !in_array($song['artist'], $albumTags['artists'] )) {
                        array_push($albumTags['artists'], $song['artist']);
                    }

                    if ( !array_key_exists( 'durations', $albumTags) ) {
                        $albumTags['durations'] = array();
                    }
                    array_push($albumTags['durations'], $song['duration']);
                }
            }
            if (count($albumTags['artists']) > 1) {
                $albumTags['artist'] = 'Various';
            }
            else {
                $albumTags['artist'] = $albumTags['artists'][0];
            }

            print ';'.
                $albumTags['album'].';'.
                $this->slugify($albumTags['album'], $albumsSlugs).';'.
                count($albumTags['durations']).';'.
                $this->getAlbumDuration($albumTags['durations']).';'.
                $albumTags['year'].';'.
                $albumTags['genre'].';'.
                $albumTags['album_path'].';'.
                ';'. // -- covert art path
                PHP_EOL;
        }
    }
}
