<?php

use App\Models\Placeholder;

$dictionary = [
    'snapshot_file' => '/foo/bar/file123.sql',
];

$placeholder = new Placeholder($dictionary);

test('replace from dictionary', function () use ($placeholder, $dictionary) {
    expect($placeholder->replace('{{snapshot_file}}'))
        ->toBe($dictionary['snapshot_file']);
});

test('replace and process from dictionary', function () use ($placeholder, $dictionary) {
    $repString = '/one/two space/%s_file';

    expect($placeholder->replace('{{basename:{{snapshot_file}}}}'))
        ->toBe(basename($dictionary['snapshot_file']))
        ->and($placeholder->replace(sprintf($repString, '{{basename:{{snapshot_file}}}}')))
        ->toBe(sprintf($repString, basename($dictionary['snapshot_file'])));
});

test('replace by date', function () use ($placeholder) {
    expect($placeholder->replace('{{date}}'))
        ->toBe(now()->toDateString())
        ->and($placeholder->replace('{{date:2024-01-01 03:33:12}}'))
        ->toBe('2024-01-01');
});

test('replace by datetime', function () use ($placeholder) {
    expect($placeholder->replace('{{datetime}}'))
        ->toBe(now()->toDateTimeString())
        ->and($placeholder->replace('{{datetime:2024-01-01}}'))
        ->toBe('2024-01-01 00:00:00');
});

test('replace by timestamp', function () use ($placeholder) {
    expect($placeholder->replace('{{timestamp}}'))
        ->toBe((string) now()->timestamp)
        ->and($placeholder->replace('{{timestamp:2024-01-01 00:00:00}}'))
        ->toBe((string) now()->parse('2024-01-01 00:00:00')->timestamp);
});

test('replace by numeric', function () use ($placeholder) {
    expect($placeholder->replace('{{numeric:{{datetime}}}}'))
        ->toBe(preg_replace("~\D~", '', now()->toDateTimeString()))
        ->and($placeholder->replace('{{numeric:1j2f3c}}'))
        ->toBe('123');
});

test('replace by relative time', function () use ($placeholder) {
    expect($placeholder->replace('{{date_calc:-1hour}}'))
        ->toBe(now()->subHour()->toDateTimeString());
});

test('replace by uuid', function () use ($placeholder) {
    expect($placeholder->replace('{{uuid}}'))
        ->toBeUuid()
        ->and($placeholder->replace('{{uuid:2024-01-01 00:00:00}}'))
        ->toBeUuid();
});

test('placeholder combination', function () use ($placeholder, $dictionary) {
    $repString = 'The %s was generated at %s';
    expect($placeholder->replace(
        sprintf($repString, '{{basename:{{snapshot_file}}}}', '{{numeric:{{date:2024-01-01 00:00:00}}}}')
    ))
        ->toBe(sprintf($repString, basename($dictionary['snapshot_file']), '20240101'));
});

test('placeholder combination with same pattern', function () use ($placeholder, $dictionary) {
    $basename = basename($dictionary['snapshot_file']);
    $repString = '%s and %s';

    expect($placeholder->replace(
        sprintf($repString, '{{basename:{{snapshot_file}}}}', '{{basename:{{snapshot_file}}}}'))
    )
        ->toBe(sprintf($repString, $basename, $basename));
});
