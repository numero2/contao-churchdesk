<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use numero2\ChurchDeskBundle\API\ChurchDeskApi;


class NewsArchiveListener {


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
     *
     * @Callback(table="tl_news_archive", target="fields.churchdesk_categories.options")
     */
    public function getCategoriesOptions( DataContainer $dc ): array {

        $categories = $this->api->getBlogCategories();

        $options = [];

        if( !empty($categories) ) {
            foreach( $categories as $value ) {
                $options[$value['id']] = $value['name'] . ' (ID: ' . $value['id'] . ')';
            }
        }

        return $options;
    }
}
