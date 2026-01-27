<?php

/**
 * Kernel – RMDB Hospital Symfony app
 *
 * We use the standard Symfony HTTP kernel and MicroKernelTrait for our
 * application entry point.
 */

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
