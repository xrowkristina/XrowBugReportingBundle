<?php

namespace Xrow\BugReportingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use xrow\BugReportingBundle\Utils\BugReportingUtils;

class DefaultController extends Controller
{
    private $issue_number;
    private $summary = [];

    /**
     * { function_description }
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function indexAction()
    {
        $utils = $this->container->get('xrow.bug_reporting_utils');
        $utils->cleanZipFolder();
        $utils->collectEzLegacyFiles();

        return $this->render('BugReportingBundle:Default:index.html.twig', array(
            'summary' => $this->summary,
            'issue_number' => false
            )
        );
    }

    /**
     * { function_description }
     *
     * @param      string  $issue_number  (description)
     * @return     <type>  ( description_of_the_return_value )
     */
    public function createAction($issue_number)
    {
        $utils = $this->container->get('xrow.bug_reporting_utils');
        $utils->run($issue_number);
        $this->summary = $utils->getSummary();

        return $this->render('BugReportingBundle:Default:create.html.twig', array(
            'issue_number' => $issue_number,
            'summary' => $this->summary
            )
        );
    }

    /**
     * Creates a binary response for filename with complete path
     * @param  string $filename [description]
     * @return [type]           [description]
     */
    public function downloadAction($filename)
    {
        if(!$filename)
        return $this->render('BugReportingBundle:Default:downloaderror.html.twig', array(
            'filename' => $filename
            )
        );

        $zip_folder = $this->container->getParameter('xrow.bugreporting.zip_folder');
        $path = $this->get('kernel')->getRootDir(). $zip_folder;
        $filepath = $path."/".$filename;

        $response = new BinaryFileResponse($filepath);

        return $response;
    }

    /**
     * Debug any arguments passed
     */
    private function debug( $arg = false)
    {
        echo "<pre>";
        print_r($arg);
        echo "</pre>";
    }
}