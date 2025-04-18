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

use Contao\ArrayUtil;
use Contao\CalendarModel;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Exception;
use numero2\ChurchDeskBundle\Import\CalendarEventsImport;
use numero2\ChurchDeskBundle\Import\ChurchDeskImport;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;


class CalendarEventsListener {


    /**
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var Contao\CoreBundle\Routing\ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var numero2\ChurchDeskBundle\Import\CalendarEventsImport
     */
    private $importer;

    /**
     * @var Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;


    public function __construct( RequestStack $requestStack, ScopeMatcher $scopeMatcher, CalendarEventsImport $importer, TranslatorInterface $translator ) {

        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->importer = $importer;
        $this->translator = $translator;
    }


    /**
     * Adds an operation to manually start the import
     *
     * @param Contao\DataContainer $dc
     */
    #[AsCallback('tl_calendar_events', target:'config.onload')]
    public function addImportOperation( DataContainer $dc ): void {

        if( !$dc->id ) {
            return;
        }

        $calendar = CalendarModel::findOneById($dc->id);

        if( $calendar && $calendar->churchdesk_enable ) {

            ArrayUtil::arrayInsert($GLOBALS['TL_DCA']['tl_calendar_events']['list']['global_operations'], 1, [
                'churchdesk_import' => [
                    'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['churchdesk_import']
                ,   'href'      => 'key=churchdesk_import'
                ,   'icon'      => 'bundles/churchdesk/backend/img/import.svg'
                ]
            ]);
        }
    }


    /**
     * Starts an import from the backend for one calendar and displays a message
     */
    public function importFromBackend(): void {

        $id = Input::get('id');

        if( $this->requestStack->getCurrentRequest() && $this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest()) && $id ) {

            $calendar = CalendarModel::findOneById($id);

            if( $calendar && $calendar->churchdesk_enable ) {

                $this->importer->importForCalendar($calendar);

            } else {
                throw new Exception('Calendar ID ' . $id . ' is not configured for use with ChurchDesk');
            }

            $sum = ($this->importer->results[ChurchDeskImport::STATUS_ERROR] ?? 0) + ($this->importer->results[ChurchDeskImport::STATUS_NEW] ?? 0) + ($this->importer->results[ChurchDeskImport::STATUS_UPDATE] ?? 0);

            if( empty($sum) ) {

                Message::addError(
                    $this->translator->trans('ERR.general', [], 'contao_default')
                );

            } else {

                if( $this->importer->results[ChurchDeskImport::STATUS_ERROR] !== 0 ) {

                    Message::addError(
                        $this->translator->trans('churchdesk.msg.import_error', [], 'contao_default')
                    );
                }

                if( $this->importer->results[ChurchDeskImport::STATUS_NEW] || $this->importer->results[ChurchDeskImport::STATUS_UPDATE] ) {

                    Message::addInfo(sprintf(
                        $this->translator->trans('churchdesk.msg.import_success', [], 'contao_default')
                    ,   $this->importer->results[ChurchDeskImport::STATUS_NEW]
                    ,   $this->importer->results[ChurchDeskImport::STATUS_UPDATE]
                    ));
                }
            }

            Controller::redirect(Controller::getReferer());
        }
    }
}
