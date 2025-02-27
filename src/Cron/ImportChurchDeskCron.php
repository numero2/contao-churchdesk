<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\Cron;

use Contao\CoreBundle\ServiceAnnotation\CronJob;
use numero2\ChurchDeskBundle\Import\CalendarEventsImport;
use numero2\ChurchDeskBundle\Import\NewsImport;


/**
 * @CronJob("daily")
 */
class ImportChurchDeskCron {


    /**
     * @var numero2\ChurchDeskBundle\Import\CalendarEventsImport
     */
    private $importerEvents;

    /**
     * @var numero2\ChurchDeskBundle\Import\NewsImport
     */
    private $importerNews;


    public function __construct( CalendarEventsImport $importerEvents, NewsImport $importerNews ) {

        $this->importerEvents = $importerEvents;
        $this->importerNews = $importerNews;
    }

    public function __invoke(): void {

        $this->importerEvents->import();
        $this->importerNews->import();
    }
}