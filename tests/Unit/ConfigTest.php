<?php

namespace Tests\Unit;

use App\Models\Config;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function testParseEnv()
    {
        $reflection = new \ReflectionClass(Config::class);
        $method = $reflection->getMethod('parseEnv');
        $method->setAccessible(true);

        putenv('TWO=1');
        putenv('FOUR=4');
        putenv('JSON={"foo":"bar"}');

        $config = [
            'one' => 'test',
            'one_default' => '%env(ONE:bar)%',
            'two_int' => '%env(int->FOUR:bar)%',
            'two_bool' => '%env(bool->FOUR)%',
            'three_float_default' => '%env(float->THREE:3)%',
            'three_none' => '%env(THREE)%',
            'four' => [
                'four_none' => '%env(THREE)%',
                'four_default' => '%env(THREE:three)%',
                'four_result' => '%env(FOUR)%',
                'four_json' => '%env(json->JSON:4)%',
            ],
        ];

        $config = $method->invokeArgs(null, [$config]);

        $this->assertEquals(
            [
                'one' => 'test',
                'one_default' => 'bar',
                'two_int' => 4,
                'two_bool' => false,
                'three_float_default' => 3.0,
                'three_none' => '',
                'four' => [
                    'four_none' => '',
                    'four_default' => 'three',
                    'four_result' => '4',
                    'four_json' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
            $config
        );
    }
}
