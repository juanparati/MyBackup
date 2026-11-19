import assert from 'node:assert/strict';
import { test } from 'node:test';
import { formatEnv, hasEmbeddedEnv, parseEnv } from '../src/model/env.ts';

test('parses a plain env reference', () => {
    assert.deepEqual(parseEnv('%env(DB_PASSWORD)%'), { env: 'DB_PASSWORD' });
});

test('parses cast and default', () => {
    assert.deepEqual(parseEnv('%env(bool->COMPRESS:true)%'), { env: 'COMPRESS', cast: 'bool', default: 'true' });
});

test('keeps colons inside the default', () => {
    assert.deepEqual(parseEnv('%env(DB_URL:mysql://root:secret@localhost)%'), { env: 'DB_URL', default: 'mysql://root:secret@localhost' });
});

test('an empty default is kept', () => {
    assert.deepEqual(parseEnv('%env(DB_PASSWORD:)%'), { env: 'DB_PASSWORD', default: '' });
});

test('only whole values are env references', () => {
    assert.equal(parseEnv('prefix_%env(X)%'), null);
    assert.equal(parseEnv('plain text'), null);
    assert.equal(hasEmbeddedEnv('prefix_%env(X)%'), true);
    assert.equal(hasEmbeddedEnv('%env(X)%'), false);
});

test('formats back to the same text', () => {
    for (const text of ['%env(A)%', '%env(json->CONNECTION)%', '%env(int->PORT:3306)%', '%env(X:)%']) {
        assert.equal(formatEnv(parseEnv(text)!), text);
    }
});
