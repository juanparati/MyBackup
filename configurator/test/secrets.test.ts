import assert from 'node:assert/strict';
import { test } from 'node:test';
import { parse } from 'yaml';
import { EXAMPLE_YAML } from '../src/model/example.ts';
import { fromYaml } from '../src/model/parse.ts';
import { MASK, replaceSecrets, writtenSecrets } from '../src/model/secrets.ts';
import { toYaml } from '../src/model/serialize.ts';
import { validate } from '../src/model/validate.ts';

const PLAN = [
    'connection: { host: db, port: 3306, username: backup, password: "db-pass" }',
    'encryption: { method: AES-128-CBC, key: "%env(KEY:enc-default)%" }',
    'notifications:',
    '    slack: { webhook: "https://hooks.slack.com/services/T0/B0/token" }',
    '    mail: { address: a@b.c, from: { address: f@b.c }, transport: smtp, host: h, port: 587, username: u, password: "mail-pass" }',
    'filesystems:',
    '    s3: { driver: s3, key: AKIAID, secret: "s3-secret", region: r, bucket: b }',
    '    gcs: { driver: gcs, bucket: b, key_file: { type: service_account, private_key: "gcs-key" } }',
    '    sftp: { driver: sftp, host: h, username: u, privateKey: "-----BEGIN OPENSSH PRIVATE KEY-----\\nssh-key\\n", passphrase: "ssh-pass" }',
    '    sftp_path: { driver: sftp, host: h, username: u, privateKey: /home/backup/.ssh/id_rsa }',
].join('\n');

const SECRETS = ['db-pass', 'enc-default', 'T0/B0/token', 'mail-pass', 's3-secret', 'gcs-key', 'ssh-key', 'ssh-pass'];

test('finds every credential written in the plan', () => {
    assert.deepEqual(writtenSecrets(fromYaml(PLAN).plan).map((secret) => [secret.path, secret.isDefault]), [
        ['connection.password', false],
        ['encryption.key', true],
        ['notifications.slack.webhook', false],
        ['notifications.mail.password', false],
        ['filesystems.0.secret', false],
        ['filesystems.1.key_file', false],
        ['filesystems.2.privateKey', false],
        ['filesystems.2.passphrase', false],
    ]);
});

test('masking hides secrets and keeps everything else', () => {
    const { plan } = fromYaml(PLAN);
    const masked = toYaml(replaceSecrets(plan, MASK));
    const data = parse(masked);

    for (const secret of SECRETS) {
        assert.ok(!masked.includes(secret), secret);
    }

    assert.equal(data.connection.password, MASK);
    assert.equal(data.encryption.key, `%env(KEY:${MASK})%`);
    assert.equal(data.filesystems.s3.key, 'AKIAID');
    assert.equal(data.filesystems.sftp_path.privateKey, '/home/backup/.ssh/id_rsa');
    assert.ok(toYaml(plan).includes('db-pass'), 'the plan itself is not changed');
});

test('clearing leaves secrets out and keeps env references', () => {
    const cleared = toYaml(replaceSecrets(fromYaml(PLAN).plan));
    const data = parse(cleared);

    for (const secret of SECRETS) {
        assert.ok(!cleared.includes(secret), secret);
    }

    assert.equal(data.connection.password, '');
    assert.equal(data.encryption.key, '%env(KEY)%');
    assert.equal(data.filesystems.s3.secret, undefined);
    assert.equal(data.filesystems.gcs.key_file, undefined);
});

test('secrets written in the file are flagged', () => {
    const paths = validate(fromYaml(PLAN).plan).filter((issue) => issue.message.includes('plain text')).map((issue) => issue.path);

    assert.deepEqual(paths, writtenSecrets(fromYaml(PLAN).plan).map((secret) => secret.path));
});

test('env references without defaults are not secrets', () => {
    assert.deepEqual(writtenSecrets(fromYaml(EXAMPLE_YAML).plan), []);
});

test('credentials that are not written are ignored', () => {
    const { plan } = fromYaml('connection: "%env(json->DB)%"\nencryption: { key: "enc" }\nfilesystems:\n    "": { driver: s3, secret: x }\n');
    plan.connection.password = 'unused';
    plan.encryption.enabled = false;

    assert.deepEqual(writtenSecrets(plan), []);
});
