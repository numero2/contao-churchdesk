<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


/**
 * BACK END MODULES
 */
$GLOBALS['BE_MOD']['content']['news']['churchdesk_import'] = ['numero2_churchdesk.listener.data_container.news', 'importFromBackend'];
$GLOBALS['BE_MOD']['content']['calendar']['churchdesk_import'] = ['numero2_churchdesk.listener.data_container.calendar_events', 'importFromBackend'];