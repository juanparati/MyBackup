import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { parse } from 'yaml';
import { initPlan } from '../src/model/defaults.ts';
import { fromYaml, YamlError } from '../src/model/parse.ts';
import { toYaml } from '../src/model/serialize.ts';

const fixture = (name: string) => readFileSync(new URL(`fixtures/${name}`, import.meta.url), 'utf8');

for (const name of ['readme_example.yaml', 'config_base.yaml', 'config_simple_backup.yaml']) {
    test(`${name} exports the same text after a re-import`, () => {
        const exported = toYaml(fromYaml(fixture(name)).plan);

        assert.equal(toYaml(fromYaml(exported).plan), exported);
    });
}

test('the README example exports the same data, plus defaults', () => {
    const data = parse(fixture('readme_example.yaml'));
    const exported = parse(toYaml(fromYaml(fixture('readme_example.yaml')).plan));

    // Empty S3 credentials are omitted; Laravel treats missing and empty the same way.
    delete data.filesystems.s3.key;
    delete data.filesystems.s3.secret;

    assert.deepEqual(exported, data);
});

test('the simple backup fixture exports the same data', () => {
    assert.deepEqual(parse(toYaml(fromYaml(fixture('config_simple_backup.yaml')).plan)), parse(fixture('config_simple_backup.yaml')));
});

test('init defaults match mybackup init', () => {
    assert.deepEqual(parse(toYaml(initPlan())), {
        name: 'Backup plan',
        catalog_file: 'catalog.sqlite',
        snapshot_file: 'snapshot_{{numeric:{{datetime}}}}.sql',
        connection: { driver: 'mysql', host: 'localhost', port: 3306, username: 'root', password: '' },
        mysqldump_path: 'mysqldump',
        backup_rotation: 1,
        compress: false,
        is_replica: false,
        databases: [],
    });
});

test('booleans, numbers and env values are typed correctly', () => {
    const plan = initPlan();
    plan.compress = 7;
    plan.is_replica = { env: 'IS_REPLICA', cast: 'bool' };
    plan.connection.port = { env: 'DB_PORT', cast: 'int', default: '3306' };
    plan.connection.password = { env: 'DB_PASSWORD' };
    plan.backup_rotation = '2 days';
    const data = parse(toYaml(plan));

    assert.equal(data.compress, 7);
    assert.equal(data.is_replica, '%env(bool->IS_REPLICA)%');
    assert.equal(data.connection.port, '%env(int->DB_PORT:3306)%');
    assert.equal(data.connection.password, '%env(DB_PASSWORD)%');
    assert.equal(data.backup_rotation, '2 days');
});

test('prompting for the password omits the key', () => {
    const plan = initPlan();
    plan.connection.password = null;

    assert.equal('password' in parse(toYaml(plan)).connection, false);
});

test('whole connection from a JSON env variable', () => {
    const { plan } = fromYaml("connection: '%env(json->CONNECTION)%'\n");

    assert.deepEqual(plan.connection_env, { env: 'CONNECTION', cast: 'json' });
    assert.equal(parse(toYaml(plan)).connection, '%env(json->CONNECTION)%');
});

test('unknown keys are kept everywhere', () => {
    const yaml = [
        'custom: { a: 1 }',
        'connection: { host: db, port: 3306, charset: utf8mb4 }',
        'databases:',
        '    - shop: { tables: [ orders ], custom_db: x }',
        'filesystems:',
        '    s3: { driver: s3, bucket: b, region: r, options: { ACL: private } }',
        '    other: { driver: scoped, disk: s3, prefix: x }',
        'notifications:',
        '    mail: { address: a@b.c, from: { address: f@b.c, extra: y }, transport: smtp, host: h, port: 25, encryption: tls }',
        'post_actions:',
        '    - move: { from: a, to: b }',
    ].join('\n');
    const { plan, warnings } = fromYaml(yaml);
    const data = parse(toYaml(plan));

    assert.deepEqual(data.custom, { a: 1 });
    assert.equal(data.connection.charset, 'utf8mb4');
    assert.deepEqual(data.databases, [{ shop: { tables: ['orders'], custom_db: 'x' } }]);
    assert.deepEqual(data.filesystems.s3.options, { ACL: 'private' });
    assert.deepEqual(data.filesystems.other, { driver: 'scoped', disk: 's3', prefix: 'x' });
    assert.deepEqual(data.notifications.mail.from, { address: 'f@b.c', extra: 'y' });
    assert.equal(data.notifications.mail.encryption, 'tls');
    assert.deepEqual(data.post_actions, [{ move: { from: 'a', to: 'b' } }]);
    assert.ok(warnings.some((w) => w.includes('"custom"')));
    assert.ok(warnings.some((w) => w.includes('scoped')));
    assert.ok(warnings.some((w) => w.includes('mail.encryption')));
});

test('mail without transport gets smtp', () => {
    const { plan, warnings } = fromYaml('notifications:\n    mail: { address: a@b.c }\n');

    assert.equal(parse(toYaml(plan)).notifications.mail.transport, 'smtp');
    assert.ok(warnings.some((w) => w.includes('transport')));
});

test('quoted booleans follow PHP truthiness', () => {
    const { plan, warnings } = fromYaml('compress: "false"\nis_replica: "0"\n');

    assert.equal(plan.compress, true);
    assert.equal(plan.is_replica, false);
    assert.equal(warnings.length, 2);
});

test('GCS service account key is a map in YAML and JSON in the form', () => {
    const { plan } = fromYaml('filesystems:\n    gcs: { driver: gcs, bucket: b, key_file: { type: service_account, private_key: "-----BEGIN-----\\nabc\\n" } }\n');

    assert.equal(JSON.parse(plan.filesystems[0].values.key_file as string).type, 'service_account');
    assert.equal(parse(toYaml(plan)).filesystems.gcs.key_file.private_key, '-----BEGIN-----\nabc\n');
});

test('delete_old always has a pattern', () => {
    const { plan } = fromYaml('post_actions:\n    - delete_old: { filesystem: local, period: 3 days }\n');

    assert.equal(parse(toYaml(plan)).post_actions[0].delete_old.pattern, '');
});

test('invalid YAML and non-map documents are rejected', () => {
    assert.throws(() => fromYaml('name: [unclosed'), YamlError);
    assert.throws(() => fromYaml('- a\n- b\n'), YamlError);
});
