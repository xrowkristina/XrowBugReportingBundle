<?php

namespace Xrow\BugReportingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use xrow\BugReportingBundle\Utils\BugReportingUtils;

class DefaultController extends Controller
{
    /**
     * { function_description }
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function createAction()
    {
        $utils = $this->container->get('xrow.bug_reporting_utils');
        $utils->cleanZipFolder();
        $utils->run();

        return $this->render('BugReportingBundle:Default:create.html.twig');
    }

    /**
     * Creates a binary response for filename with complete path
     * @param  string $filename [description]
     * @return [type]           [description]
     */
    public function downloadAction($filename)
    {
    	$utils = $this->container->get('xrow.bug_reporting_utils');
        try {
        	$utils->cleanZipFolder();
        	$utils->run();
        	
        	$zip_folder = $this->container->getParameter('xrow.bugreporting.zip_folder');
        	$path = $this->get('kernel')->getRootDir(). $zip_folder;
        	$filepath = $path ."/". $utils->getZipfileName();
        	
        	$response = new BinaryFileResponse($filepath);
        	$response->setContentDisposition(
        			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        			$utils->getZipfileName()
        			);
        	return $response;
        } catch (Exception $e) {
            return $this->render('BugReportingBundle:Default:downloaderror.html.twig');
        }
    }
}