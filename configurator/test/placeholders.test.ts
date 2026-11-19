import assert from 'node:assert/strict';
import { test } from 'node:test';
import { expandPlaceholders, formatPhpDate, leftoverPlaceholders } from '../src/model/placeholders.ts';

const NOW = new Date('2026-11-18T13:05:09Z');

const expand = (value: string, dictionary?: Record<string, string>) => expandPlaceholders(value, NOW, dictionary).value;

test('date and time placeholders use UTC', () => {
    assert.equal(expand('{{date}}'), '2026-11-18');
    assert.equal(expand('{{datetime}}'), '2026-11-18 13:05:09');
    assert.equal(expand('{{timestamp}}'), String(NOW.getTime() / 1000));
    assert.equal(expand('{{date:2024-01-01 01:00:00}}'), '2024-01-01');
    assert.equal(expand('{{datetime:2024-01-01}}'), '2024-01-01 00:00:00');
});

test('default snapshot name', () => {
    assert.equal(expand('/tmp/snapshot_{{numeric:{{datetime}}}}.sql'), '/tmp/snapshot_20261118130509.sql');
});

test('date_calc always subtracts and defaults to hours', () => {
    assert.equal(expand('{{date_calc:-2days}}'), '2026-11-16 13:05:09');
    assert.equal(expand('{{date_calc:+1month}}'), '2026-10-18 13:05:09');
    assert.equal(expand('{{date_calc:-3}}'), '2026-11-18 10:05:09');
    assert.equal(expand('{{date:{{date_calc:-7day}}}}'), '2026-11-11');
});

test('date_format only works as the whole value', () => {
    assert.equal(expand('{{date_format:2024-03-02|d-m-Y}}'), '02-03-2024');
    assert.equal(expand('{{date_format:{{date}}|Y}}'), '2026');
    assert.match(expandPlaceholders('snap_{{date_format:{{date}}|Y}}.sql', NOW).error ?? '', /whole value/);
});

test('basename and dictionary values', () => {
    assert.equal(expand('{{basename:{{snapshot_file}}}}', { snapshot_file: '/backups/snap.sql.gz' }), 'snap.sql.gz');
    assert.equal(expand('{{basename:/foo/var/file.sql}}'), 'file.sql');
});

test('uuid is a v7 UUID', () => {
    assert.match(expand('{{uuid}}') ?? '', /^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);
});

test('unknown placeholders are left over', () => {
    assert.deepEqual(leftoverPlaceholders(expand('{{snapshot_file}}_{{date}}') ?? ''), ['{{snapshot_file}}']);
});

test('PHP date format subset', () => {
    assert.equal(formatPhpDate(NOW, 'D, jS F Y \\a\\t g:i A (W)'), 'Wed, 18th November 2026 at 1:05 PM (47)');
});
