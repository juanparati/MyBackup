<?php

namespace Tests\Unit;

use App\Models\Placeholder;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class PlaceholderTest extends TestCase
{
    protected array $dictionary = [
        'snapshot_file' => '/foo/bar/file123.sql',
    ];

    protected Placeholder $placeholder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->placeholder = new Placeholder($this->dictionary);
    }

    public function test_replace_from_dictionary(): void
    {
        $repString = '/one/two space/%s_file';

        $this->assertEquals(
            basename($this->dictionary['snapshot_file']),
            $this->placeholder->replace('{{basename:{{snapshot_file}}}}')
        );

        $this->assertEquals(
            sprintf($repString, basename($this->dictionary['snapshot_file'])),
            $this->placeholder->replace(sprintf($repString, '{{basename:{{snapshot_file}}}}'))
        );
    }

    public function test_replace_by_date(): void
    {
        $this->assertEquals(
            now()->toDateString(),
            $this->placeholder->replace('{{date}}')
        );

        $this->assertEquals(
            '2024-01-01',
            $this->placeholder->replace('{{date:2024-01-01 03:33:12}}')
        );
    }

    public function test_replace_by_date_time(): void
    {
        $this->assertEquals(
            now()->toDateTimeString(),
            $this->placeholder->replace('{{datetime}}')
        );

        $this->assertEquals(
            '2024-01-01 00:00:00',
            $this->placeholder->replace('{{datetime:2024-01-01}}')
        );
    }

    public function test_replace_by_timestamp(): void
    {
        $this->assertEquals(
            (string) now()->timestamp,
            $this->placeholder->replace('{{timestamp}}')
        );

        $this->assertEquals(
            (string) now()->parse('2024-01-01 00:00:00')->timestamp,
            $this->placeholder->replace('{{timestamp:2024-01-01 00:00:00}}')
        );
    }

    public function test_by_numeric(): void
    {
        $this->assertEquals(
            preg_replace("~\D~", '', now()->toDateTimeString()),
            $this->placeholder->replace('{{numeric:{{datetime}}}}')
        );

        $this->assertEquals(
            '123',
            $this->placeholder->replace('{{numeric:1j2f3c}}')
        );
    }

    public function test_replace_by_relative_time(): void
    {
        foreach (DeclarativeHumanDateTest::TIME_REFERENCES as $timeReference) {
            foreach ([$timeReference, str($timeReference)->plural()] as $k => $reference) {
                $method = 'sub'.ucfirst($reference);
                $date = ($k ? now()->{$method}($k) : now()->{$method}())->toDateTimeString();

                $this->assertEquals(
                    $date,
                    $this->placeholder->replace("{{date_calc:-1$timeReference}}"),
                    "Replace relative $timeReference"
                );
            }
        }
    }

    public function test_placeholder_combination(): void
    {
        $repString = 'The %s was generated at %s';

        $this->assertEquals(
            sprintf($repString, basename($this->dictionary['snapshot_file']), '20240101'),
            $this->placeholder->replace(
                sprintf($repString, '{{basename:{{snapshot_file}}}}', '{{numeric:{{date:2024-01-01 00:00:00}}}}')
            )
        );
    }

    public function test_placeholder_combination_with_same_pattern()
    {
        $basename = basename($this->dictionary['snapshot_file']);
        $repString = '%s and %s';

        $this->assertEquals(
            sprintf($repString, $basename, $basename),
            $this->placeholder->replace(
                sprintf($repString, '{{basename:{{snapshot_file}}}}', '{{basename:{{snapshot_file}}}}')
            )
        );
    }

    public function test_placeholder_uuid_combination()
    {
        $datetime = '2024-01-01 00:00:00';

        $this->assertStringStartsWith(
            '018cc251-f400-',
            $this->placeholder->replace("{{uuid:$datetime}}")
        );

        $this->assertTrue(
            Uuid::isValid($this->placeholder->replace('{{uuid:{{date_calc:-1week}}}}'))
        );
    }

    public function test_placeholder_dateformat()
    {
        $this->assertEquals(
            '2024-05-01',
            $this->placeholder->replace('{{date_format:2024-05-30|Y-m-01}}')
        );

        $this->assertEquals(
            '2024-10-01',
            $this->placeholder->replace('{{date_format:2024-05-30 01:02:03|Y-10-01}}')
        );

        $this->assertEquals(
            '0',
            $this->placeholder->replace('{{date_format:{{date_calc:-1week}}|0}}')
        );
    }
}
