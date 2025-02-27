<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\Event;


final class ChurchDeskEvents {


    /**
     * The contao.churchdesk_import_entry event is triggered during importing one ChurchDesk entry.
     *
     * @see numero2\ChurchDeskBundle\Event\ImportEntryEvent
     */
    public const IMPORT_ENTRY = 'contao.churchdesk_import_entry';
}
