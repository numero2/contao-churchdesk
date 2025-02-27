<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use numero2\ChurchDeskBundle\API\ChurchDeskApi;


class CalendarListener {


    /**
     * @var numero2\ChurchDeskBundle\API\ChurchDeskApi
     */
    private $api;


    public function __construct( ChurchDeskApi $api ) {

        $this->api = $api;
    }


    /**
     * Get categories from ChurchDesk API
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    #[AsCallback('tl_calendar', target:'fields.churchdesk_categories.options')]
    public function getCategoriesOptions( DataContainer $dc ): array {

        $categories = $this->api->getEventsCategories();

        $options = [];

        if( !empty($categories) ) {
            foreach( $categories as $value ) {
                $options[$value['id']] = $value['name'] . ' (ID: ' . $value['id'] . ')';
            }
        }

        return $options;
    }


    /**
     * Get parishes from ChurchDesk API
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    #[AsCallback('tl_calendar', target:'fields.churchdesk_parishes.options')]
    public function getParishesOptions( DataContainer $dc ): array {

        $parishes = $this->api->getEventsParishes();

        $options = [];

        if( !empty($parishes) ) {
            foreach( $parishes as $value ) {
                $options[$value['id']] = $value['name'] . ' (ID: ' . $value['id'] . ')';
            }
        }

        return $options;
    }
}
