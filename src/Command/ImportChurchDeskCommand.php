<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\Command;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use numero2\ChurchDeskBundle\Import\CalendarEventsImport;
use numero2\ChurchDeskBundle\Import\NewsImport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'contao:churchdesk:import',
    description: 'Peforms an import of the needed data from ChurchDesk',
)]
class ImportChurchDeskCommand extends Command implements FrameworkAwareInterface {

    use FrameworkAwareTrait;


    /**
     * @var numero2\ChurchDeskBundle\Import\CalendarEventsImport
     */
    private $importerEvents;

    /**
     * @var numero2\ChurchDeskBundle\Import\NewsImport
     */
    private $importerNews;


    public function __construct( CalendarEventsImport $importerEvents, NewsImport $importerNews ) {

        $this->importerEvents = $importerEvents;
        $this->importerNews = $importerNews;

        parent::__construct();
    }


    protected function configure(): void {

        $this
            ->addOption('events', null, InputOption::VALUE_NONE, 'Import the ChurchDesk event entries.')
            ->addOption('news', null, InputOption::VALUE_NONE, 'Import the ChurchDesk news entries.')
        ;
    }


    protected function execute( InputInterface $input, OutputInterface $output ): int {

        set_time_limit(0);

        // init the contao framework as we use the contao models
        $this->framework->initialize();

        $io = new SymfonyStyle($input, $output);
        $this->importerEvents->setIO($output);
        $this->importerNews->setIO($output);

        $flagEvents = $input->getOption('events');
        $flagNews = $input->getOption('news');

        $flagAll = !$flagEvents && !$flagNews;

        $io->section('Import events from ChurchDesk');
        if( $flagAll || $flagEvents ) {
            $this->importerEvents->import();
            $io->success('Import events finished.');
        } else {
            $io->note('Import events skipped.');
        }

        $io->section('Import news from ChurchDesk');
        if( $flagAll || $flagNews ) {
            $this->importerNews->import();
            $io->success('Import news finished.');
        } else {
            $io->note('Import news skipped.');
        }

        $io->success('All imports finished.');
        return 0;
    }
}
