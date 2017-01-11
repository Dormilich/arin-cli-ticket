<?php
// CLI.php

namespace TicketReader;

use PDO;
use Exception;
use ErrorException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Base class that defines dependencies and some useful functions.
 */
class CLI extends Command
{
    /**
     * @var PDO $db PDO connection object.
     */
    protected $db;

    /**
     * @var SymfonyStyle $io Output formatter.
     */
    protected $io;

    /**
     * Set up the command.
     * 
     * @param PDO $db PDO connection object.
     * @return self
     */
    public function __construct( PDO $db )
    {
        $this->db = $db;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        $this->io = new SymfonyStyle( $input, $output );
    }

    /**
     * Print the error message to stdout.
     * 
     * @param Exception $e 
     * @return void
     */
    protected function printError( Exception $e )
    {
        $method = ['note', 'caution', 'warning', 'error'];
        $severity = 3;

        if ( $e instanceof ErrorException ) {
            $severity = min( 3, $e->getSeverity() );
        }

        call_user_func( [$this->io, $method[ $severity ]], $e->getMessage() );

        if ( $this->io->isVerbose() ) {
            $this->io->writeln( $e->getTraceAsString() );
        }
    }
}
