<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;


$GLOBALS['TL_DCA']['tl_news_archive']['palettes']['__selector__'][] = 'churchdesk_enable';

PaletteManipulator::create()
    ->addLegend('churchdesk_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['churchdesk_enable'], 'churchdesk_legend', PaletteManipulator::POSITION_APPEND )
    ->applyToPalette('default', 'tl_news_archive')
;
$GLOBALS['TL_DCA']['tl_news_archive']['subpalettes']['churchdesk_enable'] = 'churchdesk_categories';


$GLOBALS['TL_DCA']['tl_news_archive']['fields']['churchdesk_enable'] = [
    'exclude'      => true
,   'inputType'    => 'checkbox'
,   'filter'       => true
,   'eval'         => ['submitOnChange'=>true]
,   'sql'          => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['churchdesk_categories'] = [
    'exclude'      => true
,   'inputType'    => 'checkboxWizard'
,   'filter'       => true
,   'eval'         => ['multiple'=>true, 'tl_class'=>'w50']
,   'sql'           => "blob NULL"
];