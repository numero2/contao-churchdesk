<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\Import;

use Contao\ContentModel;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\System;
use numero2\ChurchDeskBundle\API\ChurchDeskApi;


class NewsImport extends ChurchDeskImport{


    /**
     * Imports news from all configured archives
     */
    public function import(): void {

        $archives = NewsArchiveModel::findBy(["churchdesk_enable=?"], ['1']);

        if( $archives ) {

            $this->initIOProgressBar();
            $this->results[self::DATA_TOTAL] = 0;

            foreach( $archives as $archive ) {

                if( $archive->churchdesk_enable ) {
                    $this->importForArchive($archive);

                    $this->logResult('news archive ID: '. $archive->id .' ');
                }
            }

            $this->finishIOProgressBar();
        }
    }


    /**
     * Imports blog entries for the given news archive
     *
     * @param Contao\NewsArchiveModel $archive
     */
    public function importForArchive( NewsArchiveModel $archive ): void {

        $categories = StringUtil::deserialize($archive->churchdesk_categories, true);

        $news = [];

        for( $i=0; $i < ChurchDeskApi::MAX_PAGES; $i+=1 ) {

            $newsPage = $this->api->getBlogs($categories, $i);
            $news = [...$news, ...$newsPage];

            if( count($newsPage) < ChurchDeskApi::PAGE_SIZE ) {
                break;
            }
        }

        $this->results[self::STATUS_ERROR] = 0;
        $this->results[self::STATUS_NEW] = 0;
        $this->results[self::STATUS_UPDATE] = 0;

        if( $news ) {

            // initially hide all entries in current archive to make sure
            // deleted entries are not shown anymore
            $this->connection
                ->prepare("UPDATE ".NewsModel::getTable()." SET published=:notpublished WHERE churchdesk_id!=:churchdesk AND published=:published AND pid=:pid")
                ->execute([
                    'notpublished' => '',
                    'churchdesk' => 0,
                    'published' => 1,
                    'pid' => $archive->id,
                ])
            ;

            if( !array_key_exists(self::DATA_TOTAL, $this->results) ) {
                $this->results[self::DATA_TOTAL] = 0;
            }
            $this->results[self::DATA_TOTAL] += count($news);

            $this->setIOProgressBarMaxSteps($this->results[self::DATA_TOTAL]);

            foreach( $news as $new ) {

                $status = $this->importNews($new, $archive);
                $this->results[(int)$status]++;

                $this->advanceIOProgressBar();
            }
        }
    }


    /**
     * Imports one entry into the given news archive
     *
     * @param array $new
     * @param Contao\NewsArchiveModel $archive
     *
     * @return int|null
     */
    private function importNews( array $new, NewsArchiveModel $archive ): ?int {

        // find existing news...
        $news = NewsModel::findOneBy(['pid=? AND churchdesk_id=?'], [$archive->id, $new['id']]);

        //... or create a new one
        if( !$news ) {

            $news = new NewsModel();

            $news->pid = $archive->id;
            $news->churchdesk_id = $new['id'];
            $news->tstamp = time();
            $news->source = 'default';
            $news->published = '';
        }

        $isUpdate = (bool) $news->id;

        // set / update data
        $news->headline = $new['title'];
        $news->alias = $news->churchdesk_id.'-'.StringUtil::standardize($news->headline);
        $news->teaser = $new['summary'];
        $news->date = $news->time = strtotime($new['publishDate']);

        // set image
        $news->addImage = '';
        if( !empty($new['image']['span4_16-9']) ) {

            $uuid = self::downloadFileToDBAFS($new['image']['span4_16-9']);

            if( !empty($uuid) ) {
                $news->addImage = '1';
                $news->singleSRC = $uuid;
            }
        }

        // set content
        if( !empty($new['body']) ) {

            // make sure we have an id to work with
            if( !$news->id ) {
                $news->save();
            }

            // find existing Content Element...
            $content = ContentModel::findOneBy(['ptable=? AND pid=? AND type=?'], [NewsModel::getTable(), $news->id, 'text'], ['order' => 'sorting ASC']);

            // ... or create a new one
            if( !$content ) {

                $content = new ContentModel();
                $content->ptable = NewsModel::getTable();
                $content->pid = $news->id;
                $content->sorting = 128;
            }

            $content->tstamp = time();
            $content->type = 'text';

            $content->text = $new['body'];

            $content->save();
        }

        $news->published = 1;

        // HOOK: add custom logic
        if( isset($GLOBALS['TL_HOOKS']['parseChurchDeskEntry']) && \is_array($GLOBALS['TL_HOOKS']['parseChurchDeskEntry']) ) {

            foreach( $GLOBALS['TL_HOOKS']['parseChurchDeskEntry'] as $callback ) {
                System::importStatic($callback[0])->{$callback[1]}($news, $new, $isUpdate);
            }
        }

        $news->save();

        return $isUpdate ? self::STATUS_UPDATE : self::STATUS_NEW;
    }
}
