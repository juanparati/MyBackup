import assert from 'node:assert/strict';
import { test } from 'node:test';
import { initPlan, newDatabase, newTable } from '../src/model/defaults.ts';
import { EXAMPLE_YAML } from '../src/model/example.ts';
import { fromYaml } from '../src/model/parse.ts';
import type { BackupPlan } from '../src/model/types.ts';
import { validate, type Issue } from '../src/model/validate.ts';

const NOW = new Date('2026-11-18T13:05:09Z');

function planWith(change: (plan: BackupPlan) => void): BackupPlan {
    const plan = initPlan();
    plan.databases = [newDatabase('shop')];
    change(plan);

    return plan;
}

function issuesAt(plan: BackupPlan, path: string): Issue[] {
    return validate(plan, NOW).filter((issue) => issue.path === path);
}

test('init defaults with a database are valid', () => {
    assert.deepEqual(validate(planWith(() => undefined), NOW), []);
});

test('the bundled example imports cleanly and is valid', () => {
    const { plan, warnings } = fromYaml(EXAMPLE_YAML);

    assert.deepEqual(warnings, []);
    assert.deepEqual(validate(plan, NOW), []);
});

test('required general settings', () => {
    const plan = planWith((p) => {
        p.catalog_file = '';
        p.snapshot_file = '';
        p.connection.host = '';
        p.connection.port = 'abc';
    });

    for (const path of ['catalog_file', 'snapshot_file', 'connection.host', 'connection.port']) {
        assert.equal(issuesAt(plan, path)[0]?.level, 'error', path);
    }
});

test('snapshot file needs a time placeholder', () => {
    assert.equal(issuesAt(planWith((p) => (p.snapshot_file = 'backup.sql')), 'snapshot_file')[0]?.level, 'warning');
    assert.equal(issuesAt(planWith((p) => (p.snapshot_file = '{{snapshot_file}}_{{date}}.sql')), 'snapshot_file')[0]?.level, 'error');
    assert.equal(issuesAt(planWith((p) => (p.snapshot_file = 'snap_{{date_format:{{date}}|Y}}.sql')), 'snapshot_file')[0]?.level, 'error');
});

test('backup rotation', () => {
    assert.equal(issuesAt(planWith((p) => (p.backup_rotation = '0')), 'backup_rotation')[0]?.level, 'error');
    assert.equal(issuesAt(planWith((p) => (p.backup_rotation = '0 days')), 'backup_rotation')[0]?.level, 'error');
    assert.equal(issuesAt(planWith((p) => (p.backup_rotation = '2days')), 'backup_rotation')[0]?.level, 'error');
    assert.equal(issuesAt(planWith((p) => (p.backup_rotation = '2 fortnights')), 'backup_rotation')[0]?.level, 'warning');
    assert.deepEqual(issuesAt(planWith((p) => (p.backup_rotation = '3 Weeks')), 'backup_rotation'), []);
});

test('compression level range', () => {
    assert.equal(issuesAt(planWith((p) => (p.compress = 12)), 'compress')[0]?.level, 'error');
    assert.deepEqual(issuesAt(planWith((p) => (p.compress = 9)), 'compress'), []);
});

test('databases and tables', () => {
    const plan = planWith((p) => {
        const db = newDatabase('shop');
        const table = newTable('orders');
        table.where = 'created_at >= "{{date_calc:+1day}}"';
        table.only_struct = true;
        db.tables = [table, newTable('')];
        db.ignore = ['logs'];
        db.options = ['single-transaction'];
        p.databases = [db, newDatabase('')];
    });

    assert.equal(issuesAt(plan, 'databases.1.name')[0]?.level, 'error');
    assert.equal(issuesAt(plan, 'databases.0.tables.1.name')[0]?.level, 'error');
    assert.equal(issuesAt(plan, 'databases.0.ignore')[0]?.level, 'warning');
    assert.equal(issuesAt(plan, 'databases.0.options')[0]?.level, 'warning');
    assert.deepEqual(issuesAt(plan, 'databases.0.tables.0.where').map((i) => i.level), ['warning', 'warning']);
    assert.equal(issuesAt(planWith((p) => (p.databases = [])), 'databases')[0]?.level, 'warning');
});

test('encryption key is required when enabled', () => {
    assert.equal(issuesAt(planWith((p) => (p.encryption = { enabled: true, key: '', extra: {} })), 'encryption.key')[0]?.level, 'error');
});

test('filesystems and post actions', () => {
    const { plan } = fromYaml([
        'catalog_file: c.sqlite',
        'snapshot_file: "s_{{date}}.sql"',
        'connection: { host: db, port: 3306 }',
        'mysqldump_path: mysqldump',
        'databases: [ shop ]',
        'filesystems:',
        '    local: { driver: local, root: /x }',
        '    s3: { driver: s3, bucket: b }',
        'post_actions:',
        '    - copy: { filesystem: gcs, source: "{{snapshot_file}}", destination: "{{basename:{{snapshot_file}}}}" }',
        '    - delete_old: { filesystem: s3, period: 3 }',
        '    - run: { command: "" }',
    ].join('\n'));
    const issues = validate(plan, NOW);
    const at = (path: string) => issues.filter((i) => i.path === path).map((i) => i.level);

    assert.deepEqual(at('filesystems.0.name'), ['error']);
    assert.deepEqual(at('filesystems.1.region'), ['error']);
    assert.deepEqual(at('post_actions.0.filesystem'), ['error']);
    assert.deepEqual(at('post_actions.0.source'), []);
    assert.deepEqual(at('post_actions.1.period'), ['error']);
    assert.deepEqual(at('post_actions.2.command'), ['error']);
});

test('notifications', () => {
    const { plan } = fromYaml([
        'notifications:',
        '    slack: { webhook: hooks.slack.com/x }',
        '    mail: { address: a@b.c, transport: smtp }',
        '    teams: { url: x }',
    ].join('\n'));
    const paths = validate(plan, NOW).map((i) => i.path);

    for (const path of ['notifications.slack.webhook', 'notifications.mail.from.address', 'notifications.mail.host', 'notifications.mail.port', 'notifications.extra']) {
        assert.ok(paths.includes(path), path);
    }
});

test('env references', () => {
    const plan = planWith((p) => {
        p.connection.password = { env: '' };
        p.connection.username = 'user_%env(SUFFIX)%';
        p.catalog_file = 'catalog_{{date}}.sqlite';
    });

    assert.equal(issuesAt(plan, 'connection.password')[0]?.level, 'error');
    assert.equal(issuesAt(plan, 'connection.username')[0]?.level, 'warning');
    assert.equal(issuesAt(plan, 'catalog_file')[0]?.level, 'warning');
});
