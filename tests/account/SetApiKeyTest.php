<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SetApiKeyTest extends TestCase
{
    private function defineDB()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $account = <<<SQL
CREATE TABLE account
(
    id INTEGER PRIMARY KEY,
    alias TEXT NOT NULL,
    crypto_key TEXT NOT NULL,
    UNIQUE (alias),
    CHECK( instr(alias, ' ') = 0 )
)
SQL;
        $pdo->exec( $account );

        return $pdo;
    }

    public function testSetKey()
    {
        $db = $this->defineDB();
        $cmd = new TicketReader\ApiKey( $db );

        $tester = new CommandTester( $cmd );
        $tester->setInputs([
            'das leben ist kein ponyhof',
        ]);
        $exit = $tester->execute([
            'name' => 'phpunit',
            'key'  => 'API-1234-5678-90AB-CDEF',
        ]);

        $this->assertSame(0, $exit);

        $display = $tester->getDisplay();

        $line = 'API Key saved in account phpunit';
        $this->assertNotFalse( strpos( $display, $line ) );

        $sql = 'SELECT crypto_key FROM account WHERE alias = "phpunit" ';
        $key = $db->query( $sql )->fetchColumn();

        $this->assertNotFalse( $key );
        $this->assertNotEquals( 'API-1234-5678-90AB-CDEF', $key );
    }

    public function testAccountNameWithSpaceFails()
    {
        $db = new PDO('sqlite::memory:');
        $cmd = new TicketReader\ApiKey( $db );

        $tester = new CommandTester( $cmd );
        $exit = $tester->execute([
            'name' => 'php unit',
            'key'  => 'API-1234-5678-90AB-CDEF',
        ]);

        $this->assertSame(1, $exit);

        $display = $tester->getDisplay();

        $line = 'The account name must not contain whitespace characters';
        $this->assertNotFalse( strpos( $display, $line ) );
    }

    public function testSetInvalidKeyFails()
    {
        $db = new PDO('sqlite::memory:');
        $cmd = new TicketReader\ApiKey( $db );

        $tester = new CommandTester( $cmd );
        $exit = $tester->execute([
            'name' => 'phpunit',
            'key'  => 'fizz-buzz',
        ]);

        $this->assertSame(1, $exit);

        $display = $tester->getDisplay();

        $line = 'The API key does not have the expected format';
        $this->assertNotFalse( strpos( $display, $line ) );
    }

    public function testSetKeyOnExistingAccountMakesUpdate()
    {
        $db = $this->defineDB();
        $db->exec('INSERT INTO account (alias, crypto_key) VALUES ("phpunit", "test")');
        $cmd = new TicketReader\ApiKey( $db );

        $tester = new CommandTester( $cmd );
        $tester->setInputs([
            'das leben ist kein ponyhof',
        ]);
        $exit = $tester->execute([
            'name' => 'phpunit',
            'key'  => 'API-1234-5678-90AB-CDEF',
        ]);

        $this->assertSame(0, $exit);

        $sql = 'SELECT crypto_key FROM account WHERE alias = "phpunit" ';
        $key = $db->query( $sql )->fetchColumn();

        $this->assertNotFalse( $key );
        $this->assertNotEquals( 'test', $key );
        $this->assertNotEquals( 'API-1234-5678-90AB-CDEF', $key );
    }
}
