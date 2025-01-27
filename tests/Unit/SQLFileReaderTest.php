<?php

namespace Tests\Unit;

use App\Helpers\SQLFileReader;
use Tests\TestCase;

class SQLFileReaderTest extends TestCase
{

    protected const DUMP_FILE = __DIR__ . '/../Fixtures/dump.sql';


    public function test_statement_read_plain()
    {
        $this->run_read_test(new SQLFileReader(static::DUMP_FILE));
    }

    public function test_statement_read_gzip()
    {
        $this->run_read_test(new SQLFileReader(static::DUMP_FILE . '.gz'));
    }

    protected function run_read_test(SqlFileReader $file) {
        $this->assertGreaterThan(0, $file->getFileSize());
        $this->assertEquals(0, $file->getTotalRead());

        $totalStatements = 0;
        $lastTotalRead = 0;

        foreach ($file->readStatement() as $statement) {
            $totalStatements++;
            $this->assertNotEmpty($statement);
            $this->assertGreaterThan($lastTotalRead, $file->getTotalRead());
            $lastTotalRead = $file->getTotalRead();
        }

        $fileStatements = substr_count(file_get_contents(static::DUMP_FILE), ";\n");
        $this->assertEquals($fileStatements, $totalStatements);
    }

}
