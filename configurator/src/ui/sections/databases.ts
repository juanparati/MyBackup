import { newDatabase, newTable } from '../../model/defaults.ts';
import { formatEnv, parseEnv } from '../../model/env.ts';
import { PLACEHOLDERS } from '../../model/placeholders.ts';
import { isEnv, type BackupPlan, type Database, type Table, type Value } from '../../model/types.ts';
import { changed } from '../../store.ts';
import { h, idFor } from '../dom.ts';
import { extraField, linesField, textField } from '../fields.ts';
import { itemButtons, section } from './common.ts';

type Mode = 'whole' | 'tables';

const COMMON_OPTIONS = ['--single-transaction', '--quick', '--compress', '--skip-lock-tables', '--insert-ignore', '--routines', '--triggers'];

/** Form-only state: the chosen mode, and the data of the hidden mode so switching back restores it. */
const modes = new WeakMap<Database, Mode>();
const stashed = new WeakMap<Database, { tables: Table[]; ignore: string[] }>();

export function databasesSection(plan: BackupPlan): HTMLElement {
    const list = h('div', { class: 'items' });

    const render = () => {
        list.replaceChildren(
            ...plan.databases.map((db, i) => databaseCard(plan, db, i, render)),
            h('div', { class: 'add-row' },
                h('button', {
                    type: 'button',
                    'data-path': 'databases',
                    onclick: () => {
                        plan.databases.push(newDatabase());
                        changed();
                        render();
                        list.querySelector<HTMLInputElement>(`#${idFor(`databases.${plan.databases.length - 1}.name`)}`)?.focus();
                    },
                }, '+ Add database'),
            ),
        );
    };

    render();

    return section('Databases', 'databases', 'Dump whole databases, or only some of their tables. Views are detected automatically and dumped after the tables.', list, true);
}

function databaseCard(plan: BackupPlan, db: Database, index: number, rerenderList: () => void): HTMLElement {
    const card = h('fieldset', { class: 'card' });
    const path = `databases.${index}`;

    const switchMode = (mode: Mode) => {
        const stash = stashed.get(db) ?? { tables: [], ignore: [] };

        if (mode === 'tables') {
            stash.ignore = db.ignore;
            db.ignore = [];
            db.tables = stash.tables.length ? stash.tables : [newTable()];
        } else {
            stash.tables = db.tables;
            db.tables = [];
            db.ignore = stash.ignore;
        }

        stashed.set(db, stash);
        modes.set(db, mode);
        changed();
        render();
    };

    const render = () => {
        const mode = modes.get(db) ?? (db.tables.length ? 'tables' : 'whole');
        modes.set(db, mode);

        card.replaceChildren(
            h('legend', {}, `Database ${index + 1}`),
            h('div', { class: 'card-head' },
                h('label', { for: idFor(`${path}.name`), class: 'visually-hidden' }, 'Database name'),
                h('input', {
                    id: idFor(`${path}.name`),
                    'data-path': `${path}.name`,
                    class: 'name',
                    value: db.name,
                    placeholder: 'database name',
                    spellcheck: 'false',
                    autocomplete: 'off',
                    oninput: (event: Event) => {
                        db.name = (event.target as HTMLInputElement).value.trim();
                        changed();
                    },
                }),
                itemButtons(plan.databases, index, `database ${db.name || index + 1}`, rerenderList),
            ),
            h('div', { class: 'mode', role: 'radiogroup', 'aria-label': 'What to dump' },
                (['whole', 'tables'] as const).map((value) => h('label', {},
                    h('input', {
                        type: 'radio',
                        name: `${path}.mode`,
                        value,
                        checked: mode === value,
                        onchange: () => switchMode(value),
                    }),
                    value === 'whole' ? ' Whole database' : ' Only some tables',
                )),
            ),
            h('div', { class: 'grid' },
                optionsField(db, path),
                textField({
                    label: 'Restore into database',
                    path: `${path}.to`,
                    placeholder: db.name || 'database',
                    hint: 'Adds "USE <name>;" before this database\'s data. Needed to restore a table-only dump, which doesn\'t select a database.',
                    get: () => db.to,
                    set: (value) => (db.to = value),
                }),
                mode === 'whole'
                    ? linesField({
                        label: 'Skip tables',
                        path: `${path}.ignore`,
                        placeholder: 'one table per line',
                        get: () => db.ignore,
                        set: (lines) => (db.ignore = lines),
                    })
                    : tablesEditor(db, path),
            ),
            extraField(`${path}.extra`, () => db.extra, (extra) => (db.extra = extra)),
        );
    };

    render();

    return card;
}

function optionsField(db: Database, path: string): HTMLElement {
    const wrap = h('div', { class: 'wide' });

    const render = () => {
        wrap.replaceChildren(
            linesField({
                label: 'mysqldump options',
                path: `${path}.options`,
                placeholder: 'one option per line, e.g. --single-transaction',
                rows: 2,
                hint: 'Passed to the shell as written.',
                get: () => db.options,
                set: (lines) => (db.options = lines),
            }),
            h('div', { class: 'chips', 'aria-label': 'Common options' },
                COMMON_OPTIONS.filter((option) => !db.options.includes(option)).map((option) => h('button', {
                    type: 'button',
                    class: 'chip',
                    title: `Add ${option}`,
                    onclick: () => {
                        db.options.push(option);
                        changed();
                        render();
                    },
                }, `+ ${option}`)),
            ),
        );
    };

    render();

    return wrap;
}

function tablesEditor(db: Database, dbPath: string): HTMLElement {
    const wrap = h('div', { class: 'wide tables' });

    const render = () => {
        const bulk = h('textarea', { rows: 4, placeholder: 'one table name per line', spellcheck: 'false', 'aria-label': 'Table names to add' });

        wrap.replaceChildren(
            h('table', {},
                h('thead', {}, h('tr', {},
                    h('th', { scope: 'col' }, 'Table or view'),
                    h('th', { scope: 'col' }, 'Where condition'),
                    h('th', { scope: 'col', title: 'Dump the structure without data' }, 'Structure only'),
                    h('th', { scope: 'col' }, h('span', { class: 'visually-hidden' }, 'Actions')),
                )),
                h('tbody', {}, db.tables.map((table, i) => tableRow(db, table, i, `${dbPath}.tables.${i}`, render))),
            ),
            h('div', { class: 'add-row' },
                h('button', {
                    type: 'button',
                    onclick: () => {
                        db.tables.push(newTable());
                        changed();
                        render();
                        wrap.querySelector<HTMLInputElement>(`#${idFor(`${dbPath}.tables.${db.tables.length - 1}.name`)}`)?.focus();
                    },
                }, '+ Add table'),
                h('details', { class: 'bulk' },
                    h('summary', {}, 'Add several'),
                    bulk,
                    h('button', {
                        type: 'button',
                        onclick: () => {
                            const names = bulk.value.split(/[\s,]+/).filter((name) => name !== '');
                            db.tables = db.tables.filter((table) => table.name !== '');
                            db.tables.push(...names.map((name) => newTable(name)));
                            changed();
                            render();
                        },
                    }, 'Add tables'),
                ),
            ),
        );
    };

    render();

    return wrap;
}

function tableRow(db: Database, table: Table, index: number, path: string, rerender: () => void): HTMLElement {
    const whereMenu = h('select', {
        class: 'insert',
        title: 'Insert a placeholder',
        'aria-label': 'Insert a placeholder',
        onchange: () => {
            const input = row.querySelector<HTMLInputElement>(`#${idFor(`${path}.where`)}`)!;
            input.setRangeText(whereMenu.value, input.selectionStart ?? input.value.length, input.selectionEnd ?? input.value.length, 'end');
            input.dispatchEvent(new Event('input'));
            input.focus();
            whereMenu.value = '';
        },
    }, h('option', { value: '' }, '{{…}}'), PLACEHOLDERS.map((p) => h('option', { value: p.text }, `${p.text} — ${p.label}`)));

    const row = h('tr', {},
        h('td', {}, h('input', {
            id: idFor(`${path}.name`),
            'data-path': `${path}.name`,
            value: table.name,
            placeholder: 'table',
            'aria-label': 'Table name',
            spellcheck: 'false',
            autocomplete: 'off',
            oninput: (event: Event) => {
                table.name = (event.target as HTMLInputElement).value.trim();
                changed();
            },
        })),
        h('td', {}, h('div', { class: 'control' },
            h('input', {
                id: idFor(`${path}.where`),
                'data-path': `${path}.where`,
                value: inlineText(table.where),
                placeholder: 'all rows',
                'aria-label': `Where condition for ${table.name || 'table'}`,
                spellcheck: 'false',
                autocomplete: 'off',
                oninput: (event: Event) => {
                    table.where = inlineValue((event.target as HTMLInputElement).value);
                    changed();
                },
            }),
            whereMenu,
        )),
        h('td', { class: 'center' }, isEnv(table.only_struct)
            ? h('input', {
                'data-path': `${path}.only_struct`,
                value: formatEnv(table.only_struct),
                'aria-label': 'Structure only',
                oninput: (event: Event) => {
                    const value = (event.target as HTMLInputElement).value;
                    table.only_struct = parseEnv(value) ?? value === 'true';
                    changed();
                },
            })
            : h('input', {
                type: 'checkbox',
                'data-path': `${path}.only_struct`,
                checked: table.only_struct,
                'aria-label': `Structure only for ${table.name || 'table'}`,
                onchange: (event: Event) => {
                    table.only_struct = (event.target as HTMLInputElement).checked;
                    changed();
                },
            })),
        h('td', {}, h('button', {
            type: 'button',
            class: 'icon danger',
            title: 'Remove table',
            'aria-label': `Remove table ${table.name}`,
            onclick: () => {
                db.tables.splice(index, 1);
                changed();
                rerender();
            },
        }, '✕')),
    );

    return row;
}

/** Env references are edited inline as their %env(…)% text. */
function inlineText(value: Value<string>): string {
    return isEnv(value) ? formatEnv(value) : value;
}

function inlineValue(text: string): Value<string> {
    return parseEnv(text) ?? text;
}
