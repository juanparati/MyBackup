import { PLACEHOLDERS } from '../../model/placeholders.ts';
import { isEnv, type BackupPlan, type EnvValue } from '../../model/types.ts';
import { changed } from '../../store.ts';
import { h, idFor, nodes } from '../dom.ts';
import { boolField, field, linesField, textField } from '../fields.ts';
import { section } from './common.ts';

type RotationMode = 'off' | 'count' | 'age' | 'env';

export function outputSection(plan: BackupPlan): HTMLElement {
    const body = h('div', { class: 'grid' });
    let rotationMode: RotationMode = rotationModeOf(plan.backup_rotation);

    const render = () => {
        body.replaceChildren(...nodes([
            compressField(plan, render),
            boolField({
                label: 'Check that the read replica is running',
                path: 'is_replica',
                hint: 'Stops the backup when the replica isn\'t replicating. Reads the slave_running status from information_schema, which MariaDB provides but MySQL 8 doesn\'t.',
                get: () => plan.is_replica,
                set: (value) => (plan.is_replica = value),
            }),
            field({ label: 'Rotation', path: 'backup_rotation-mode', hint: 'Deletes old local snapshots registered in the catalog, after a successful backup.' },
                h('select', {
                    id: idFor('backup_rotation-mode'),
                    onchange: (event: Event) => {
                        rotationMode = (event.target as HTMLSelectElement).value as RotationMode;
                        plan.backup_rotation = { off: null, count: '7', age: '7 days', env: { env: '' } }[rotationMode];
                        changed();
                        render();
                    },
                }, ([
                    ['off', 'Keep every snapshot'],
                    ['count', 'Keep the last N snapshots'],
                    ['age', 'Delete snapshots older than…'],
                    ['env', 'From an environment variable'],
                ] as const).map(([value, label]) => h('option', { value, selected: rotationMode === value }, label)))),
            rotationMode === 'count' && textField({
                label: 'Snapshots to keep',
                path: 'backup_rotation',
                kind: 'number',
                env: false,
                hint: 'Includes the snapshot just created.',
                get: () => plan.backup_rotation ?? '',
                set: (value) => (plan.backup_rotation = value),
            }),
            rotationMode === 'age' && textField({
                label: 'Older than',
                path: 'backup_rotation',
                env: false,
                suggestions: ['1 day', '7 days', '2 weeks', '1 month', '3 months'],
                hint: '"N unit": minutes, hours, days, weeks, months or years.',
                get: () => plan.backup_rotation ?? '',
                set: (value) => (plan.backup_rotation = value),
            }),
            rotationMode === 'env' && textField({
                label: 'Rotation variable',
                path: 'backup_rotation',
                env: false,
                hint: 'The variable holds a number of snapshots or a period such as "7 days".',
                get: () => plan.backup_rotation ?? { env: '' },
                set: (value) => (plan.backup_rotation = value),
            }),
            h('label', { class: 'switch wide' },
                h('input', {
                    type: 'checkbox',
                    checked: plan.encryption.enabled,
                    onchange: () => {
                        plan.encryption.enabled = !plan.encryption.enabled;
                        changed();
                        render();
                    },
                }),
                ' Encrypt the snapshot (AES-128-CBC)'),
            plan.encryption.enabled && textField({
                label: 'Encryption key',
                path: 'encryption.key',
                kind: 'secret',
                required: true,
                hint: 'Needed to decrypt and restore, so keep it safe. ".aes" is appended to the snapshot name.',
                get: () => plan.encryption.key,
                set: (value) => (plan.encryption.key = value),
            }),
        ]));
    };

    render();

    return section('Compression, encryption & rotation', 'output', null, body, true);
}

function compressField(plan: BackupPlan, rerender: () => void): HTMLElement {
    if (isEnv(plan.compress)) {
        return textField({
            label: 'Compression',
            path: 'compress',
            hint: 'Use the bool cast for on/off, or int for a gzip level (1-9).',
            get: () => plan.compress as EnvValue,
            set: (value) => {
                plan.compress = isEnv(value) ? value : false;

                if (!isEnv(value)) {
                    rerender();
                }
            },
        });
    }

    const current = String(plan.compress);
    const levels = Array.from({ length: 9 }, (_, i) => [String(i + 1), `gzip, level ${i + 1}${i === 0 ? ' (fastest)' : i === 8 ? ' (smallest)' : ''}`]);

    return field({ label: 'Compression', path: 'compress', hint: 'The dump is gzipped while it is written, without an intermediate file. ".gz" is appended.' },
        h('select', {
            id: idFor('compress'),
            'data-path': 'compress',
            onchange: (event: Event) => {
                const value = (event.target as HTMLSelectElement).value;
                plan.compress = value === 'env' ? { env: '', cast: 'bool' } : value === 'true' ? true : value === 'false' ? false : Number(value);
                changed();

                if (value === 'env') {
                    rerender();
                }
            },
        }, [['false', 'Off'], ['true', 'gzip (default level 6)'], ...levels, ['env', 'From an environment variable']]
            .map(([value, label]) => h('option', { value, selected: value === current }, label))));
}

function rotationModeOf(value: BackupPlan['backup_rotation']): RotationMode {
    if (value === null) {
        return 'off';
    }

    if (isEnv(value)) {
        return 'env';
    }

    return /^\s*\d+\s*$/.test(value) ? 'count' : 'age';
}

export function dumpSection(plan: BackupPlan): HTMLElement {
    return section('Dump text', 'dump', 'Text added to the beginning and the end of the snapshot, one line each. It is compressed and encrypted with the dump; statements at the beginning run in the same session on restore.',
        h('div', { class: 'grid' },
            linesField({
                label: 'Beginning',
                path: 'dump.begin',
                placeholder: 'SET SESSION sql_log_bin=0;',
                placeholders: PLACEHOLDERS,
                get: () => plan.dump.begin,
                set: (lines) => (plan.dump.begin = lines),
            }),
            linesField({
                label: 'End',
                path: 'dump.end',
                placeholder: '-- File generated by MyBackup at {{datetime}}',
                placeholders: PLACEHOLDERS,
                get: () => plan.dump.end,
                set: (lines) => (plan.dump.end = lines),
            }),
        ));
}
