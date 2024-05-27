<?php
use \App\Models\FilePath;

uses(\Illuminate\Support\Facades\File::class)->in('Unit');

test('replace by home', function () {
    $home = getenv('HOME');

    expect(FilePath::fromPath('~')->path())
        ->toBe($home)
        ->and(FilePath::fromPath('~/foo/var')->path())
        ->toBe($home . '/foo/var');
});

