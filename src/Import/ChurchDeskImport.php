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

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Dbafs;
use Contao\File;
use Contao\Folder;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use numero2\ChurchDeskBundle\API\ChurchDeskApi;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


abstract class ChurchDeskImport {


    /**
     * @var int
     */
    public const STATUS_ERROR = 0;
    public const STATUS_NEW = 1;
    public const STATUS_UPDATE = 2;
    public const DATA_TOTAL = 3;

    /**
     * @var Doctrine\DBAL\Connection
     */
    protected Connection $connection;

    /**
     * @var Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @var numero2\ChurchDeskBundle\API\ChurchDeskApi
     */
    protected ChurchDeskApi $api;

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var array
     */
    public $results;

    /**
     * @var array
     */
    protected $cache;

    /**
    * @var array
    */
    protected $io;


    public function __construct( Connection $connection, EventDispatcherInterface $eventDispatcher, ChurchDeskApi $api, LoggerInterface $logger ) {

        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
        $this->api = $api;
        $this->logger = $logger;

        $this->results = [];
        $this->cache = [];
        $this->io = [];
    }


    /**
     * Start the import
     */
    abstract public function import(): void;


    /**
     * Log the current results
     *
     * @param string $identifier
     */
    public function logResult( string $identifier ): void {

        $sum = ($this->results[self::STATUS_ERROR] ?? 0) + ($this->results[self::STATUS_NEW] ?? 0) + ($this->results[self::STATUS_UPDATE] ?? 0);

        if( !$sum ) {

            $this->logger->log(LogLevel::ERROR, 'Could not import entries for ' . $identifier, ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);

        } else {

            if( $this->results[self::STATUS_ERROR] !== 0 ) {

                $this->logger->log(LogLevel::ERROR, 'Failed to import ' .$this->results[self::STATUS_ERROR]. ' entries for '. $identifier, ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);
            }

            if( $this->results[self::STATUS_NEW] || $this->results[self::STATUS_UPDATE] ) {

                $this->logger->log(LogLevel::INFO, 'Successfully imported entries for '. $identifier . ' (' .$this->results[self::STATUS_NEW]. ' new / ' .$this->results[self::STATUS_UPDATE]. ' updated)', ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]);
            }
        }
    }


    /**
     * Set input / output to be used during import
     *
     * @param Symfony\Component\Console\Output\OutputInterface $output
     */
    public function setIO( OutputInterface $output ): void {

        $this->io = [
            'output' => $output
        ];
    }


    /**
     * Initialize the progress bar
     */
    protected function initIOProgressBar(): void {

        if( !empty($this->io['output']) ) {
            $progressBar = new ProgressBar($this->io['output'], 0);
            $this->io['progressBar'] = $progressBar;
        }
    }


    /**
     * Set the progress bar max steps
     *
     * @param int $count
     */
    protected function setIOProgressBarMaxSteps( int $count ): void {

        if( !empty($this->io['progressBar']) ) {
            $this->io['progressBar']->setMaxSteps($count);
        }
    }


    /**
     * Advance the progress bar
     */
    protected function advanceIOProgressBar(): void {

        if( !empty($this->io['progressBar']) ) {
            $this->io['progressBar']->advance();
        }
    }


    /**
     * Finish the progress bar and print empty lines
     */
    protected function finishIOProgressBar(): void {

        if( !empty($this->io['progressBar']) ) {
            $this->io['progressBar']->finish();
            unset($this->io['progressBar']);

            if( !empty($this->io['output']) ) {
                $this->io['output']->writeln('');
                $this->io['output']->writeln('');
            }
        }
    }


    /**
     * Copy one file to contaos files folder and add it to the DBAFS and return its uuid.
     *
     * @param string $url
     * @param string $folder
     *
     * @return string|null
     */
    public static function downloadFileToDBAFS( string $url, string $folder='files/churchdesk' ): ?string {

        if( empty($url) || empty($folder) ) {
            return null;
        }

        $folder = new Folder($folder);

        if( !$folder ) {
            return null;
        }

        $filename = parse_url($url)['path'];
        $fileext = end(explode('.', $filename));
        $filename = basename($filename, $fileext);
        $filename = StringUtil::standardize($filename) .'.'. $fileext;

        $dest = $folder->dirname . '/' . $folder->basename . '/' . $filename;
        $success = @file_put_contents($dest, file_get_contents($url));

        if( $success ) {

            $file = new File($folder->path . '/' . $filename);

            $model = $file->getModel();
            if( !$model ) {
                Dbafs::addResource($folder->path . '/' . $filename);
            }

            $model = $file->getModel();
            if( $model ) {
                return $model->uuid;
            }
        }

        return null;
    }
}
