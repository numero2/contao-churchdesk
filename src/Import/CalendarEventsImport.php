<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\Import;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\ContentModel;
use Contao\Date;
use Contao\StringUtil;
use numero2\ChurchDeskBundle\API\ChurchDeskApi;
use numero2\ChurchDeskBundle\Event\ChurchDeskEvents;
use numero2\ChurchDeskBundle\Event\ImportEntryEvent;


class CalendarEventsImport extends ChurchDeskImport{


    /**
     * Imports events from all configured calendars
     */
    public function import(): void {

        $calendars = CalendarModel::findBy(["churchdesk_enable=?"], [1]);

        if( $calendars ) {

            $this->initIOProgressBar();
            $this->results[self::DATA_TOTAL] = 0;

            foreach( $calendars as $calendar ) {

                if( $calendar->churchdesk_enable ) {
                    $this->importForCalendar($calendar);

                    $this->logResult('events calendar ID: '. $calendar->id .' ');
                }
            }

            $this->finishIOProgressBar();
        }
    }


    /**
     * Imports events for the given calendar
     *
     * @param Contao\CalendarModel $calendar
     */
    public function importForCalendar( CalendarModel $calendar ): void {

        $categories = StringUtil::deserialize($calendar->churchdesk_categories, true);
        $parishes = StringUtil::deserialize($calendar->churchdesk_parishes, true);

        $events = [];

        for( $i=0; $i < ChurchDeskApi::MAX_PAGES; $i+=1 ) {

            $eventsPage = $this->api->getEvents($categories, $i);
            $eventsFiltered = $eventsPage;

            // filter events by parishes
            if( !empty($parishes) ) {

                $eventsFiltered = array_filter($eventsFiltered, function( $event ) use ( $parishes ) {

                    if( !empty($event['parishes']) ) {
                        foreach( $event['parishes'] as $parish ) {
                            if( in_array($parish['id'], $parishes) ) {
                                return true;
                            }
                        }
                    }

                    return false;
                });
            }

            $events = [...$events, ...$eventsFiltered];

            if( count($eventsPage) < ChurchDeskApi::PAGE_SIZE ) {
                break;
            }
        }

        $this->results[self::STATUS_ERROR] = 0;
        $this->results[self::STATUS_NEW] = 0;
        $this->results[self::STATUS_UPDATE] = 0;

        if( $events ) {

            // initially hide all entries in current calendar to make sure
            // deleted entries are not shown anymore
            $this->connection->executeStatement(
                "UPDATE ".CalendarEventsModel::getTable()." SET published=:notpublished WHERE churchdesk_id!=:churchdesk AND published=:published AND pid=:pid"
            ,   [
                    'notpublished' => 0,
                    'churchdesk' => 0,
                    'published' => 1,
                    'pid' => $calendar->id,
                ]
            );

            if( !array_key_exists(self::DATA_TOTAL, $this->results) ) {
                $this->results[self::DATA_TOTAL] = 0;
            }
            $this->results[self::DATA_TOTAL] += count($events);

            $this->setIOProgressBarMaxSteps($this->results[self::DATA_TOTAL]);

            foreach( $events as $event ) {

                $status = $this->importEvent($event, $calendar);
                $this->results[(int)$status]++;

                $this->advanceIOProgressBar();
            }
        }
    }


    /**
     * Imports one entry into the given calendar
     *
     * @param array $new
     * @param Contao\CalendarModel $calendar
     *
     * @return int|null
     */
    private function importEvent( array $new, CalendarModel $calendar ): ?int {

        // find existing event...
        $event = CalendarEventsModel::findOneBy(['pid=? AND churchdesk_id=?'], [$calendar->id, $new['id']]);

        //... or create a new one
        if( !$event ) {

            $event = new CalendarEventsModel();

            $event->pid = $calendar->id;
            $event->churchdesk_id = $new['id'];
            $event->tstamp = time();
            $event->published = 0;
        }

        $isUpdate = (bool) $event->id;

        // set / update data
        $event->title = $new['title'];
        $event->alias = $event->churchdesk_id.'-'.StringUtil::standardize($event->title);
        $event->teaser = $new['summary'];

        $startDate = new Date(strtotime($new['startDate']));
        $event->startDate = $startDate->dayBegin;
        $event->startTime = $event->startDate;

        $endDate = new Date(strtotime($new['endDate']));
        $event->endDate = $endDate->dayBegin;
        $event->endTime = $event->endDate;

        if( $event->startDate === $event->endDate ) {
            $event->endDate = null;
            $event->endTime = $event->startDate;
        }

        $event->addTime = $new['allDay'] ? 0 : 1;
        if( $event->addTime ) {

            $event->startTime = strtotime($new['startDate']);
            $event->endTime = strtotime(date('Y-m-d', $event->endDate ?? $event->startDate). ' ' .date('H:i:s',$event->startTime));

            if( $new['showEndtime'] ) {
                $event->endTime = strtotime($new['endDate']);
            }

        } else {

            if( (strlen($event->endDate) && $event->endDate == $event->endTime) || $event->startTime == $event->endTime ) {
                $event->endTime = (strtotime('+1 day', $event->endTime) - 1);
            }
        }

        $event->location = $new['locationName'] ?? '';
        $event->address = $new['locationObj']['address'] ?? '';

        // set image
        $event->addImage = 0;
        if( !empty($new['image']['16-9']) ) {

            $uuid = self::downloadFileToDBAFS($new['image']['16-9']);

            if( !empty($uuid) ) {
                $event->addImage = 1;
                $event->singleSRC = $uuid;
            }
        }

        // set content
        if( !empty($new['description']) ) {

            // make sure we have an id to work with
            if( !$event->id ) {
                $event->save();
            }

            // find existing Content Element...
            $content = ContentModel::findOneBy(['ptable=? AND pid=? AND type=?'], [CalendarEventsModel::getTable(), $event->id, 'text'], ['order' => 'sorting ASC']);

            // ... or create a new one
            if( !$content ) {

                $content = new ContentModel();
                $content->ptable = CalendarEventsModel::getTable();
                $content->pid = $event->id;
                $content->sorting = 128;
            }

            $content->tstamp = time();
            $content->type = 'text';

            $content->text = $new['description'];

            $content->save();
        }

        $event->published = 1;

        // Event: add custom logic
        $importEvent = new ImportEntryEvent($event, $new, $isUpdate);
        $this->eventDispatcher->dispatch($importEvent, ChurchDeskEvents::IMPORT_ENTRY);

        $event = $importEvent->getModel();
        $isUpdate = $importEvent->getIsUpdate();

        $event->save();

        return $isUpdate ? self::STATUS_UPDATE : self::STATUS_NEW;
    }
}
