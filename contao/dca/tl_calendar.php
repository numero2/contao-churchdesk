<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;


$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'churchdesk_enable';

PaletteManipulator::create()
    ->addLegend('churchdesk_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['churchdesk_enable'], 'churchdesk_legend', PaletteManipulator::POSITION_APPEND )
    ->applyToPalette('default', 'tl_calendar')
;
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['churchdesk_enable'] = 'churchdesk_categories,churchdesk_parishes';


$GLOBALS['TL_DCA']['tl_calendar']['fields']['churchdesk_enable'] = [
    'exclude'      => true
,   'inputType'    => 'checkbox'
,   'filter'       => true
,   'eval'         => ['submitOnChange'=>true]
,   'sql'          => ['type'=>'boolean', 'default'=>false]
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['churchdesk_categories'] = [
    'exclude'      => true
,   'inputType'    => 'checkboxWizard'
,   'filter'       => true
,   'eval'         => ['multiple'=>true, 'tl_class'=>'w50']
,   'sql'           => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['churchdesk_parishes'] = [
    'exclude'      => true
,   'inputType'    => 'checkboxWizard'
,   'filter'       => true
,   'eval'         => ['multiple'=>true, 'tl_class'=>'w50']
,   'sql'           => "blob NULL"
];