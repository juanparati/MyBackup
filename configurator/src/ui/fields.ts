import { parse, stringify } from 'yaml';
import type { FieldDef } from '../model/blocks.ts';
import { ENV_CASTS } from '../model/env.ts';
import { ACTION_PLACEHOLDERS, PLACEHOLDERS, type PlaceholderInfo } from '../model/placeholders.ts';
import { isEnv, isMap, type Block, type EnvValue, type Extra, type Value } from '../model/types.ts';
import { changed } from '../store.ts';
import { h, idFor, nodes, type Child } from './dom.ts';

export interface FieldOptions {
    label: string;
    path: string;
    hint?: Child;
    required?: boolean;
    placeholder?: string;
}

export interface TextOptions extends FieldOptions {
    get: () => Value<string>;
    set: (value: Value<string>) => void;
    kind?: 'text' | 'secret' | 'number' | 'textarea';
    /** Allow switching to an environment variable (default: true). */
    env?: boolean;
    /** Cast preselected when switching to an environment variable. */
    cast?: string;
    placeholders?: PlaceholderInfo[];
    suggestions?: string[];
    list?: string;
}

/** Wrap a control with its label and hint. */
export function field(opts: FieldOptions, control: Child, wide = false): HTMLElement {
    return h('div', { class: wide ? 'field wide' : 'field' }, fieldParts(opts, control));
}

function fieldParts(opts: FieldOptions, control: Child): Child[] {
    return [
        h('label', { for: idFor(opts.path) }, opts.label, opts.required && h('span', { class: 'required', title: 'Required' }, ' *')),
        control,
        opts.hint && h('small', { class: 'hint' }, opts.hint),
    ];
}

export function textField(opts: TextOptions): HTMLElement {
    const wrap = h('div', { class: opts.kind === 'textarea' ? 'field wide' : 'field' });
    const listId = opts.suggestions ? `${idFor(opts.path)}-list` : opts.list;

    const render = (focus = false) => {
        const value = opts.get();
        const control = isEnv(value) ? envEditor(opts.label, opts.path, value, opts.set, opts.kind === 'secret') : plainInput(opts, value, listId);
        const toggle = opts.env !== false && h('button', {
            type: 'button',
            class: 'env-toggle',
            'aria-pressed': String(isEnv(value)),
            title: isEnv(value) ? 'Use a literal value' : 'Read the value from an environment variable',
            onclick: () => {
                opts.set(isEnv(opts.get()) ? '' : { env: '', ...(opts.cast ? { cast: opts.cast } : {}) });
                changed();
                render(true);
            },
        }, 'env');

        wrap.replaceChildren(...nodes(fieldParts(opts, h('div', { class: 'control' },
            control,
            !isEnv(value) && opts.placeholders && placeholderMenu(opts.placeholders, () => wrap.querySelector('input, textarea')),
            toggle,
            opts.suggestions && h('datalist', { id: listId }, opts.suggestions.map((s) => h('option', { value: s }))),
        ))));

        if (focus) {
            wrap.querySelector<HTMLElement>('input, textarea')?.focus();
        }
    };

    render();

    return wrap;
}

function plainInput(opts: TextOptions, value: string, listId?: string): HTMLElement {
    const attrs = {
        id: idFor(opts.path),
        'data-path': opts.path,
        value,
        placeholder: opts.placeholder,
        list: listId,
        spellcheck: 'false',
        autocomplete: opts.kind === 'secret' ? 'new-password' : 'off',
        oninput: (event: Event) => {
            opts.set((event.target as HTMLInputElement).value);
            changed();
        },
    };

    if (opts.kind === 'textarea') {
        return h('textarea', { ...attrs, rows: 4 });
    }

    return h('input', {
        ...attrs,
        type: opts.kind === 'secret' ? 'password' : 'text',
        inputmode: opts.kind === 'number' ? 'numeric' : undefined,
    });
}

/** Name, cast and default of an environment variable reference; the default of a secret is masked. */
function envEditor(label: string, path: string, value: EnvValue, set: (value: EnvValue) => void, secret = false): HTMLElement {
    let current = value;

    const update = (patch: Partial<EnvValue>) => {
        const next: EnvValue = { ...current, ...patch };

        for (const key of ['cast', 'default'] as const) {
            if (next[key] === undefined || next[key] === '') {
                delete next[key];
            }
        }

        current = next;
        set(next);
        changed();
    };

    return h('div', { class: 'env-editor' },
        h('span', { class: 'env-prefix', 'aria-hidden': 'true' }, '$'),
        h('input', {
            id: idFor(path),
            'data-path': path,
            value: value.env,
            placeholder: 'VARIABLE_NAME',
            'aria-label': `${label}: environment variable name`,
            spellcheck: 'false',
            autocomplete: 'off',
            oninput: (event: Event) => update({ env: (event.target as HTMLInputElement).value.trim() }),
        }),
        h('select', {
            'aria-label': `${label}: cast`,
            title: 'Cast the variable to this type',
            onchange: (event: Event) => update({ cast: (event.target as HTMLSelectElement).value }),
        }, h('option', { value: '' }, 'string'), ENV_CASTS.filter((c) => c !== 'string').map((cast) => h('option', {
            value: cast,
            selected: value.cast === cast,
        }, cast)), value.cast && !(ENV_CASTS as readonly string[]).includes(value.cast) && h('option', { value: value.cast, selected: true }, value.cast)),
        h('input', {
            type: secret ? 'password' : undefined,
            value: value.default ?? '',
            placeholder: 'default (optional)',
            'aria-label': `${label}: default value`,
            spellcheck: 'false',
            autocomplete: secret ? 'new-password' : 'off',
            oninput: (event: Event) => update({ default: (event.target as HTMLInputElement).value }),
        }),
    );
}

/** "Insert placeholder" menu for the input found by `target`. */
function placeholderMenu(placeholders: PlaceholderInfo[], target: () => HTMLInputElement | HTMLTextAreaElement | null): HTMLElement {
    return h('select', {
        class: 'insert',
        title: 'Insert a placeholder',
        'aria-label': 'Insert a placeholder',
        onchange: (event: Event) => {
            const select = event.target as HTMLSelectElement;
            const input = target();

            if (input && select.value) {
                const start = input.selectionStart ?? input.value.length;
                const end = input.selectionEnd ?? start;
                input.setRangeText(select.value, start, end, 'end');
                input.dispatchEvent(new Event('input'));
                input.focus();
            }

            select.value = '';
        },
    }, h('option', { value: '' }, '{{…}}'), placeholders.map((p) => h('option', { value: p.text }, `${p.text} — ${p.label}`)));
}

export interface BoolOptions extends FieldOptions {
    get: () => Value<boolean>;
    set: (value: Value<boolean>) => void;
}

/** Checkbox that can also read its value from an environment variable. */
export function boolField(opts: BoolOptions): HTMLElement {
    const wrap = h('div', { class: 'field check' });

    const render = () => {
        const value = opts.get();
        const control = isEnv(value)
            ? envEditor(opts.label, opts.path, value, opts.set)
            : h('input', {
                type: 'checkbox',
                id: idFor(opts.path),
                'data-path': opts.path,
                checked: value,
                onchange: (event: Event) => {
                    opts.set((event.target as HTMLInputElement).checked);
                    changed();
                },
            });

        wrap.replaceChildren(
            h('div', { class: 'control' },
                control,
                h('label', { for: idFor(opts.path) }, opts.label),
                h('button', {
                    type: 'button',
                    class: 'env-toggle',
                    'aria-pressed': String(isEnv(value)),
                    title: isEnv(value) ? 'Use a literal value' : 'Read the value from an environment variable',
                    onclick: () => {
                        opts.set(isEnv(opts.get()) ? false : { env: '', cast: 'bool' });
                        changed();
                        render();
                    },
                }, 'env'),
            ),
            opts.hint ? h('small', { class: 'hint' }, opts.hint) : '',
        );
    };

    render();

    return wrap;
}

export interface SelectOptions extends FieldOptions {
    options: [value: string, label: string][];
    get: () => string;
    set: (value: string) => void;
}

export function selectField(opts: SelectOptions): HTMLElement {
    const current = opts.get();
    const options = opts.options.some(([value]) => value === current) ? opts.options : [...opts.options, [current, current] as [string, string]];

    return field(opts, h('select', {
        id: idFor(opts.path),
        'data-path': opts.path,
        onchange: (event: Event) => {
            opts.set((event.target as HTMLSelectElement).value);
            changed();
        },
    }, options.map(([value, label]) => h('option', { value, selected: value === current }, label))));
}

export interface LinesOptions extends FieldOptions {
    get: () => string[];
    set: (lines: string[]) => void;
    placeholders?: PlaceholderInfo[];
    rows?: number;
}

/** Textarea holding one list item per line. */
export function linesField(opts: LinesOptions): HTMLElement {
    const textarea = h('textarea', {
        id: idFor(opts.path),
        'data-path': opts.path,
        rows: opts.rows ?? 3,
        placeholder: opts.placeholder,
        spellcheck: 'false',
        value: opts.get().join('\n'),
        oninput: () => {
            opts.set(textarea.value.split('\n').map((line) => line.trim()).filter((line) => line !== ''));
            changed();
        },
    });

    return field(opts, h('div', { class: 'control' }, textarea, opts.placeholders && placeholderMenu(opts.placeholders, () => textarea)), true);
}

/** Collapsible YAML editor for keys the form doesn't cover. */
export function extraField(path: string, get: () => Extra, set: (extra: Extra) => void, hint = 'Keys not covered by the form, kept as written.'): HTMLElement {
    const extra = get();
    const error = h('small', { class: 'field-error', role: 'alert' });
    const textarea = h('textarea', {
        id: idFor(path),
        'data-path': path,
        rows: 3,
        spellcheck: 'false',
        placeholder: 'key: value',
        value: Object.keys(extra).length ? stringify(extra, { indent: 4 }).trimEnd() : '',
        oninput: () => {
            try {
                const value: unknown = textarea.value.trim() === '' ? {} : parse(textarea.value);

                if (!isMap(value)) {
                    throw new Error('Expected key: value pairs.');
                }

                set(value);
                error.textContent = '';
                changed();
            } catch (e) {
                error.textContent = (e as Error).message.split('\n')[0];
            }
        },
    });

    return h('details', { class: 'extra', open: Object.keys(extra).length > 0 },
        h('summary', {}, 'Extra keys (YAML)'),
        h('small', { class: 'hint' }, hint),
        textarea,
        error,
    );
}

/** Fields of a block described by FieldDefs (filesystem drivers, post actions, notifications). */
export function blockFields(block: Block, fields: FieldDef[], path: string): HTMLElement[] {
    return fields.map((def) => {
        const opts = {
            label: def.label,
            path: `${path}.${def.key}`,
            hint: def.hint,
            required: def.required,
            placeholder: def.placeholder,
        };

        if (def.kind === 'bool') {
            return triStateField({
                ...opts,
                get: () => block.values[def.key] as Value<boolean> | undefined,
                set: (value) => (block.values[def.key] = value),
            });
        }

        return textField({
            ...opts,
            kind: def.kind === 'json' ? 'textarea' : def.kind,
            cast: def.kind === 'number' ? 'int' : def.kind === 'json' ? 'json' : undefined,
            get: () => (block.values[def.key] as Value<string> | undefined) ?? '',
            set: (value) => (block.values[def.key] = value),
            placeholders: def.placeholders ? [...ACTION_PLACEHOLDERS, ...PLACEHOLDERS] : undefined,
            suggestions: def.suggestions,
            list: def.list,
        });
    });
}

interface TriStateOptions extends FieldOptions {
    get: () => Value<boolean> | undefined;
    set: (value: Value<boolean> | undefined) => void;
}

/** Optional boolean: unset (driver default), yes, no or an environment variable. */
function triStateField(opts: TriStateOptions): HTMLElement {
    const wrap = h('div');

    const render = () => {
        const value = opts.get();

        if (isEnv(value)) {
            wrap.replaceChildren(textField({
                ...opts,
                get: () => opts.get() as EnvValue,
                set: (next) => {
                    opts.set(isEnv(next) ? next : undefined);

                    if (!isEnv(next)) {
                        render();
                    }
                },
            }));

            return;
        }

        wrap.replaceChildren(field(opts, h('div', { class: 'control' },
            h('select', {
                id: idFor(opts.path),
                'data-path': opts.path,
                onchange: (event: Event) => {
                    const selected = (event.target as HTMLSelectElement).value;
                    opts.set(selected === '' ? undefined : selected === 'true');
                    changed();
                },
            },
            h('option', { value: '' }, 'Default'),
            h('option', { value: 'true', selected: value === true }, 'Yes'),
            h('option', { value: 'false', selected: value === false }, 'No')),
            h('button', {
                type: 'button',
                class: 'env-toggle',
                'aria-pressed': 'false',
                title: 'Read the value from an environment variable',
                onclick: () => {
                    opts.set({ env: '', cast: 'bool' });
                    changed();
                    render();
                },
            }, 'env'),
        )));
    };

    render();

    return wrap;
}
