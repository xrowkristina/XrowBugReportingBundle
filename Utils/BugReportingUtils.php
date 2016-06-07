<?php

namespace Xrow\BugReportingBundle\Utils;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Connection;
use ZipArchive;
use ezcSystemInfo;
use Xrow\BugReportingBundle\Controller\DefaultController;

class BugReportingUtils
{
    private $app_folder = false;
    private $filetypes;
    private $app_root;
    private $destination_dir;
    private $zip_filename;
    private $file_list = [];
    private $container;
    private $stuff;


    public function __construct($container)
    {
        $this->container = $container;
        $this->file_suffix = date("Y.m.d.Hi");
        $this->zip_filename = $this->container->getParameter('xrow.bugreporting.zip_file');
        $this->app_root = $this->container->getParameter('kernel.root_dir');
        $this->destination_dir = $this->app_root . $this->container->getParameter('xrow.bugreporting.zip_folder');
    }

    public function run($issue_number)
    {
        $this->issue_number = $issue_number;
        $this->cleanZipFolder();
        $this->getInfoData();
        $this->createAdditionalFiles();
        $this->getRelevantFiles();
        $this->createZipFile( $this->file_list );
        $this->cleanUpFolder();
    }

    public function getInfoData()
    {
        $this->getEzPublishVersion();
        $this->getPhpVersion();
        $this->getMysqlVersion();
        $this->getMysqlServerVersion();
        $this->getPhpAcceleratorVersion();
        $this->getEzPublishComponents();
    }

    public function createAdditionalFiles()
    {
        $this->createQAfile();
        $this->createReadmeFile();

        $this->createTextFile("SystemInformation.txt", $this->summary);
        $this->createTextFile("phpinfo.html", $this->getPhpinfoHtml());
        $this->createTextFile("FolderPermissionRoot.txt", $this->runProcess("ls -lisa ".$this->app_root."/../"));
        $this->createTextFile("FolderPermissionApp.txt", $this->runProcess("ls -lisa ".$this->app_root));
        $this->createTextFile("FolderPermissionWeb.txt", $this->runProcess("ls -lisa ".$this->app_root."/../web"));
    }

    public function getRelevantFiles()
    {
        $this->collectInstalledComposerJson();
        $this->collectYamlFiles();
        $this->collectComposerJsonFile();
        $this->collectLogFiles();

        $this->collectFiles($this->destination_dir, array("md", "txt", "html"));
    }

    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Get the ez publish version.
     *
     * @return     <type>  Ez publish version.
     */
    public function getEzPublishVersion()
    {
        $this->summary["Date: "] = "Date: ". date("Y.m.d") . "\n";

        return $this->summary["eZPublish version:"] = "eZ Publish version: See ezsystems/ezpublish-kernel below. \n\n";
    }

    /**
     * [getPhpVersion description]
     * @return [type] [description]
     */
    public function getPhpVersion()
    {
        $info = ezcSystemInfo::phpversion();

        return $this->summary["PHP Version: "] = "PHP version: ".phpversion() ."\n\n";
    }

    /**
     * [getPhpAcceleratorVersion description]
     * @return [type] [description]
     */
    public function getPhpAcceleratorVersion()
    {
        $info = (array) ezcSystemInfo::phpAccelerator();

        $info = $this->makeString($info);

        return $this->summary["PHP Accelerator: "] =  "PHP Accelerator: \n". $info ."\n\n";
    }

    /**
     * Retrieve ezsystem information with "composer info"
     *
     * @return string [description]
     */
    public function getEzPublishComponents()
    {
        // Use parent dir, as composer.json is in parent dir and not in /app or /web
        $info = $this->runProcess("composer info --working-dir=../ | grep ez");

        $this->summary["eZPublish components:"] = $info."\n\n";
    }

    /**
     * Retrieve MySQL version with mysql command
     *
     * @return sring [description]
     */
    public function getMysqlVersion()
    {
        $info = $this->runProcess("mysql -V");

        $this->summary["MySQL version:"] = "MySQL version (mysql -V): " . $info ."\n\n";
    }

    /**
     * Retrieve MySQL Server version from DB
     *
     * @return sring [description]
     */
    public function getMysqlServerVersion()
    {
        $connect = $this->container->get('doctrine.dbal.default_connection');
        $query = $connect->query("SHOW GLOBAL VARIABLES LIKE '%version%'");
        while ($row = $query->fetch()) {
            $result[$row["Variable_name"]] = $row['Value'];
        }
        $mysqlinfo = $this->makeString($result);

        $this->summary["MysqlServerVersion: "] = "MySQL Server Version: \n".$mysqlinfo ."\n\n";
    }

    /**
     * Retrieve yml files from /config. Exlcudindg parameters.yml
     *
     * @return array list of files
     */
    public function collectYamlFiles()
    {
        $folder = array($this->app_root.'/config');
        $files_found = $this->findFiles(
                $folder,
                array("yml"),
                array("parameters.yml")
            );

        if($files_found)
            $this->file_list = array_merge($this->file_list, $files_found);
    }

    /**
     * [collectInstalledComposerJson description]
     * @return [type] [description]
     */
    public function collectInstalledComposerJson()
    {
        $this->collectFiles(
            str_replace("/app", "/vendor/composer", $this->app_root),
            array("json")
        );
    }

    /**
     * Retrieves the composer.json file from
     * @return [type] adds it to the $file_list
     */
    public function collectComposerJsonFile()
    {
        $folder = str_replace("/app", "", $this->app_root);

        $files_found = $this->findSpecificFile($folder, "composer.json");
        if($files_found)
            $this->file_list = array_merge($this->file_list, $files_found);
    }

    /**
     * Retrieves all .log files from /logs folder
     * @return [type] [description]
     */
    public function collectLogFiles()
    {
        $folder = array($this->app_root.'/logs');
        $files_found = $this->findFiles($folder, array("log"));

        if($files_found)
            $this->file_list = array_merge($this->file_list, $files_found);
    }

    /**
     * Retrieves all .log files from /logs folder
     * @return [type] [description]
     */
    public function collectLogFiles()
    {
        $folder = array($this->app_root.'/ezpuiblish_legacy/settings');
        $files_found = $this->findFiles($folder, array("log"));

        if($files_found)
            $this->file_list = array_merge($this->file_list, $files_found);
    }
    /**
     * Collect and store all relevant information files
     */
    public function collectInfoFiles()
    {
        $files_found = $this->findFiles($this->destination_dir, array('md', '1st', 'txt'));

        if($files_found)
            $this->file_list = array_merge($this->file_list, $files_found);
    }

    /**
     * Retrieve files with certain extension from a specific folder
     *
     * @param  boolean $folder         folder to retrieve files
     * @param  array   $file_extension file extensions to retrieve
     * @param  array   $exclude_files  files to ignore
     */

    public function collectFiles($folder = false, $file_extension = [], $exclude_files = false)
    {
        $files_found = $this->findFiles($folder, $file_extension, $exclude_files);

        if($files_found)
            $this->file_list = array_merge($this->file_list, $files_found);
    }

    /**
     * Copy and create QA text file
     */
    public function createQAfile()
    {
        $originalFile = realpath(__DIR__ . '/../Resources/files/templates/QA-sample.txt');
        $newFile = $this->destination_dir.'/QA.txt';

        $this->createCopiedFile($originalFile, $newFile);
    }

    /**
     * Copies and creates README.md file
     */
    public function createReadmeFile()
    {
        $originalFile = realpath(__DIR__ . '/../Resources/files/templates/README');
        $newFile = $this->destination_dir.'/README.md';

        $this->createCopiedFile($originalFile, $newFile);
    }

    public function getPhpinfoHtml()
    {
    ob_start();
    phpinfo();
    $info = ob_get_contents();
    ob_end_clean();
    return $info;
    }

    /**
     * [runProcess description]
     * @param  boolean $command [description]
     * @return [type]           [description]
     */
    public function runProcess($command = false)
    {
        if(! getenv("HOME")) {
            putenv ("HOME=/var/www/sites/ezstudio");
        }
        $process = new Process($command);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * Search and save filetype in folder
     * @param  array  $directory    folder to find files
     * @param  array  $filetype     filetype to match
     * @param  array  $ignore_files list of files to ignore
     * @return array                list of found files with matching criteria
     */
    public function findFiles($directory = [], $filetype = [], $ignore_files = [])
    {
        $found_files = false;
        $filetype = is_array($filetype) ? implode('|', $filetype) : $filetype;
        $ignore_files = is_array($ignore_files) ? $ignore_files : [];

        $finder = new Finder();
        $finder = $finder->files()->ignoreUnreadableDirs()->in($directory);

            foreach ($finder as $file) {
                if( preg_match('/^.*\.('.$filetype.')$/i', $file)) {
                    if(!in_array(basename($file), $ignore_files))
                        $found_files[] = $file->getRealpath();
                }
            }

        return $found_files;
    }

    /**
     * { function_description }
     *
     * @param      array         $directory  (description)
     * @param      array|string  $filetype   (description)
     *
     * @return     <type>        ( description_of_the_return_value )
     */
    public function findSpecificFile($directory, $fileName = false)
    {
        $found_files = false;
        $finder = new Finder();
        $finder = $finder->files()->name($fileName)->in($directory);

            foreach ($finder as $file) {
                if( $directory."/".$fileName == $file) {
                    $found_files[] = $file->getRealpath();
                }
            }

        return $found_files;
    }

    /**
     * Create ZIP file
     * @param  boolean $file_list list of files to add
     */
    public function createZipFile($file_list = false)
    {
        $zip = new ZipArchive;
        $this->modifyZipfile();
        $zip_file = $this->destination_dir.'/'.$this->zip_filename;

        if ($zip->open($zip_file, ZIPARCHIVE::CREATE) === true) {
          foreach ($file_list as $file) {
            if ($file !== $zip_file && is_file($file)) {
                if(preg_match("/config|logs/", $file)) {
                    $destFileFolder = str_replace($this->app_root."/", "", $file);
                    $zip->addFile($file, $destFileFolder);
                } else {
                    $zip->addFile($file, basename($file));
              }
            }
          }
        $zip->close();
        }
    }

    /**
     * Filter to modify the ZIP file
     * @return string   new file name
     */
    public function modifyZipfile()
    {
        $this->zip_filename  = str_replace(".zip", $this->file_suffix.".zip", $this->zip_filename);
        if($this->issue_number !== 'none')
            $this->zip_filename  = str_replace("-xrow-", "-xrow-issue".$this->issue_number."-", $this->zip_filename);
    }

    /**
     * Copy and create new file
     * @param  [type] $source      File to copy
     * @param  [type] $destination New file to creat
     * @return [type]              [description]
     */
    public function createCopiedFile($source, $destination)
    {
        $fs = new Filesystem();
        try {
            $fs->copy($source, $destination, true);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while copying file to ".$e->getPath();
        }
    }

    /**
     * Dump data/content into file
     * @param  string           $filename file to create
     * @param  string           $content  content
     * @param  string|boolean   $suffix   file suffix
     * @return [type]                     [description]
     */
    public function createTextFile($filename, $content = "", $suffix = false)
    {
        $fs = new Filesystem();

        $filename = $suffix ? str_replace(".", "_".$this->file_suffix.".", $filename) : $filename;
        $folder = $this->destination_dir;

            try {
                $fs->exists($folder) ? false : $fs->mkdir($folder, 0775 );
                $fs->exists($folder.'/'.$filename) ? false : $fs->dumpFile($folder.'/'.$filename, $content);

            } catch (IOExceptionInterface $e) {
                echo "An error occurred while creating your directory at ".$e->getPath();
            }
    }

    /**
     * Convert Array to string
     * @param  array $array    Data to convert to string
     * @return string $outval  Output value as string data
     */
    public function makeString($array)
    {
      $outval = '';
      foreach ($array as $key => $value) {
        if(is_array($value)) {
            $outval .= !is_numeric($key) ? "$key" : false;
            $outval .= $this->makestring($value);
        } else {
          $outval .= (string) $key .": ".$value ."\n";
        }
      }

      return $outval;
    }

    /**
     * Clean destination folder and leave zip-file
     */
    public function cleanUpFolder()
    {
        $fs = new Filesystem();

        foreach($this->file_list as $file) :
            if ( eregi($this->destination_dir, $file))
                $fs->remove($file);
        endforeach;
    }

    /**
     * Clean empty zip/destination folder
     */
    public function cleanZipFolder()
    {
        foreach(glob("{$this->destination_dir}/*") as $file)
        {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Custom debug function
     */
    private function debug( $arg = false) {
        echo "<pre>";
        print_r($arg);
        echo "</pre>";
    }
}