<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


$GLOBALS['TL_DCA']['tl_news']['config']['sql']['keys']['churchdesk_id'] = 'index';


$GLOBALS['TL_DCA']['tl_news']['fields']['churchdesk_id'] = [
    'exclude'      => true
,   'inputType'    => 'text'
,   'eval'         => ['doNotCopy'=>true, 'tl_class'=>'w50']
,   'sql'          => "int(10) unsigned NOT NULL default 0"
];