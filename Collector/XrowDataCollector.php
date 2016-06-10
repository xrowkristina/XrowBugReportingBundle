<?php

namespace Xrow\BugReportingBundle\Collector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class XrowDataCollector extends DataCollector
{
    private $container;
    private $folder;

    public function __construct($container)
    {
        $this->container = $container;
        $this->folder = $this->container->getParameter('kernel.root_dir').$this->container->getParameter('xrow.bugreporting.zip_folder');
        $this->data = [
            'collectors' => [],
            'panelTemplates' => [],
            'toolbarTemplates' => [],
            'zipfiles' => [],
        ];
    }

    public function collectZipfiles()
    {
        $found_files = [];
        $folder = $this->folder;
        $finder = new Finder();
        $fs = new Filesystem();
        if ($fs->exists($folder)) {
            $files = $finder->files()->ignoreUnreadableDirs()->in($folder);
            foreach ($files as $file) {
                if( preg_match('/zip$/i', $file)) {
                    $found_files["name"] = $file->getFileName();
                    $found_files['relativepath'] = $file->getRealpath();
                }
            }
        }
        return $found_files;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'method' => $request->getMethod(),
            'acceptable_content_types' => $request->getAcceptableContentTypes(),
            'zipfiles' => $this->collectZipfiles(),
        );
    }

    public function getMethod()
    {
        return $this->data['method'];
    }

    public function getAcceptableContentTypes()
    {
        return $this->data['acceptable_content_types'];
    }

    public function getName()
    {
        return 'xrow.bug_data_collector';
    }

    public function getZipfiles()
    {
        return $this->data['zipfiles'];
    }

    /**
     * Custom debug function
     */
    private function debug( $arg = false)
    {
        echo "<pre>";
        print_r($arg);
        echo "</pre>";
    }

}
