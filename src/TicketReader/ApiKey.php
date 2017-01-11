<?php
// ApiKey.php

namespace TicketReader;

use PDO;
use Exception;
use RuntimeException;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\KeyProtectedByPassword as Key;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to add/replace an ARIN API key.
 */
class ApiKey extends CLI
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName(
                'api-key'
            )
            ->setDescription(
                'Set an API key and protect it by a user-provided password'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Short name for the account that uses this API key'
            )
            ->addArgument( 'key', InputArgument::REQUIRED, 
                'The API key from your ARIN-online account for accessing tickets'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $name = $input->getArgument( 'name' );
        $key  = $input->getArgument( 'key' );

        try {
            $this->validateKey( $key );
            $this->validateAlias( $name );

            $question = 'Please enter the password to protect your API key';
            $password = $this->io->ask( $question );

            $this->saveApiKey( $name, $key, $password );

            return 0;
        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }
    }

    /**
     * Check the format of the API key. The format is 'API' followed by four 
     * blocks of hex digits.
     * 
     * @param string $key API key.
     * @return void
     * @throws RuntimeException Incorrect key format.
     */
    private function validateKey( $key )
    {
        $regexp = '/^API(-[0-9A-F]{4}){4}$/';

        if ( preg_match( $regexp, $key ) === 0 ) {
            $msg = 'The API key does not have the expected format';
            throw new RuntimeException( $msg );
        }
    }

    /**
     * Check that the account alias does not contain whitespace characters.
     * 
     * @param string $alias Account alias.
     * @return void
     * @throws RuntimeException Invalid name.
     */
    private function validateAlias( $alias )
    {
        $regexp = '/\s/';

        if ( preg_match( $regexp, $alias ) > 0 ) {
            $msg = 'The account name must not contain whitespace characters';
            throw new RuntimeException( $msg );
        }
    }

    /**
     * Encrypt the API key and save it into the database.
     * 
     * @param string $account Account alias.
     * @param string $key API key.
     * @param string $password Password to protect the API key.
     * @return void
     */
    private function saveApiKey( $account, $key, $password )
    {
        $access_key = Key::createRandomPasswordProtectedKey( $password );
        $encrypted = Crypto::encrypt( $key, $access_key );

        $sql = 'INSERT OR REPLACE INTO account (alias, crypto_key, access_key ) VALUES (?, ?, ?)';
        $stmt = $this->db->prepare( $sql );
        $stmt->bindValue( 1, $account, PDO::PARAM_STR );
        $stmt->bindValue( 2, $encrypted, PDO::PARAM_STR );
        $stmt->bindValue( 3, $access_key->saveToAsciiSafeString(), PDO::PARAM_STR );
        $stmt->execute();
    }
}
