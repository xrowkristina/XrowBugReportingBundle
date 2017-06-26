<?php

namespace Xrow\BugReportingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use xrow\BugReportingBundle\Utils\BugReportingUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/ezbugreport/create", name="bugreport_create")
     */
    public function createAction()
    {
        if ( $this->getParameter("kernel.environment") != "dev" ){
            throw $this->createAccessDeniedException('You cannot access this page!');
        }
        $utils = $this->container->get('xrow.bug_reporting_utils');
        $utils->cleanZipFolder();
        $utils->run();

        return $this->render('BugReportingBundle:Default:create.html.twig');
    }

    /**
     * Creates a binary response for filename with complete path
     * @Route("/ezbugreport/download", name="bugreport_download")
     */
    public function downloadAction()
    {
        if ( $this->getParameter("kernel.environment") != "dev" ){
            throw $this->createAccessDeniedException('You cannot access this page!');
        }
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