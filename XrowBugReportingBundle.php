<?php

namespace Xrow\BugReportingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class XrowBugReportingBundle extends Bundle
{
    protected $name = 'BugReportingBundle';
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
