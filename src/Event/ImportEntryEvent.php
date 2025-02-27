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

use Contao\Model;
use Symfony\Contracts\EventDispatcher\Event;


class ImportEntryEvent extends Event {


    /**
     * @var Contao\Model
     */
    private Model $model;

    /**
     * @var array
     */
    private array $entry;

    /**
     * @var bool
     */
    private bool $isUpdate;


    public function __construct( Model $model, array $entry, bool $isUpdate ) {

        $this->model = $model;
        $this->entry = $entry;
        $this->isUpdate = $isUpdate;
    }


    /**
     * @return Contao\Model|null
     */
    public function getModel(): ?Model {

        return $this->model;
    }


    /**
     * @param Contao\Model $model
     *
     * @return numero2\SpreadsheetCatalogBundle\Event\DataParseEvent
     */
    public function setModel( Model $model ): self {

        $this->model = $model;

        return $this;
    }


    /**
     * @return array
     */
    public function getEntry(): array {

        return $this->entry;
    }


    /**
     * @return Contao\PageModel|null
     */
    public function getIsUpdate(): bool {

        return $this->isUpdate;
    }


    /**
     * @param bool $isUpdate
     *
     * @return numero2\SpreadsheetCatalogBundle\Event\DataParseEvent
     */
    public function setIsUpdate( bool $isUpdate ): self {

        $this->isUpdate = $isUpdate;

        return $this;
    }
}
