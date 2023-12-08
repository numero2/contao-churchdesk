<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;


class ChurchDeskBundle extends Bundle {


    public function getPath(): string {

        return \dirname(__DIR__);
    }
}
