import { DRIVERS } from '../../model/blocks.ts';
import type { BackupPlan, Filesystem } from '../../model/types.ts';
import { changed } from '../../store.ts';
import { h, idFor } from '../dom.ts';
import { blockFields, extraField, field } from '../fields.ts';
import { itemButtons, section } from './common.ts';

export function filesystemsSection(plan: BackupPlan): HTMLElement {
    const list = h('div', { class: 'items' });

    const render = () => {
        list.replaceChildren(
            ...plan.filesystems.map((fs, i) => filesystemCard(plan, fs, i, render)),
            h('div', { class: 'add-row' },
                h('button', {
                    type: 'button',
                    onclick: () => {
                        plan.filesystems.push({ name: '', driver: 's3', values: {}, extra: {} });
                        changed();
                        render();
                        list.querySelector<HTMLInputElement>(`#${idFor(`filesystems.${plan.filesystems.length - 1}.name`)}`)?.focus();
                    },
                }, '+ Add filesystem'),
            ),
        );
    };

    render();

    return section('Filesystems', 'filesystems', [
        'Storage that post actions copy snapshots to. A filesystem named ',
        h('code', {}, 'local'),
        ' always exists: it is the directory of the snapshot file.',
    ], list);
}

function filesystemCard(plan: BackupPlan, fs: Filesystem, index: number, rerenderList: () => void): HTMLElement {
    const card = h('fieldset', { class: 'card' });
    const path = `filesystems.${index}`;

    const render = () => {
        const driver = DRIVERS[fs.driver];
        const drivers = Object.entries(DRIVERS).map(([value, { label }]) => [value, label]);

        if (!driver) {
            drivers.push([fs.driver, `${fs.driver} (not supported by the form)`]);
        }

        card.replaceChildren(
            h('legend', {}, `Filesystem ${index + 1}`),
            h('div', { class: 'card-head' },
                h('label', { for: idFor(`${path}.name`), class: 'visually-hidden' }, 'Filesystem name'),
                h('input', {
                    id: idFor(`${path}.name`),
                    'data-path': `${path}.name`,
                    class: 'name',
                    value: fs.name,
                    placeholder: 'name, e.g. s3',
                    spellcheck: 'false',
                    autocomplete: 'off',
                    oninput: (event: Event) => {
                        fs.name = (event.target as HTMLInputElement).value.trim();
                        changed();
                    },
                }),
                itemButtons(plan.filesystems, index, `filesystem ${fs.name || index + 1}`, rerenderList),
            ),
            h('div', { class: 'grid' },
                field({ label: 'Driver', path: `${path}.driver` }, h('select', {
                    id: idFor(`${path}.driver`),
                    'data-path': `${path}.driver`,
                    onchange: (event: Event) => {
                        // Values of the previous driver stay in memory but are only written for the selected driver.
                        fs.driver = (event.target as HTMLSelectElement).value;
                        changed();
                        render();
                    },
                }, drivers.map(([value, label]) => h('option', { value, selected: value === fs.driver }, label)))),
                blockFields(fs, driver?.fields ?? [], path),
                extraField(`${path}.extra`, () => fs.extra, (extra) => (fs.extra = extra)),
            ),
        );
    };

    render();

    return card;
}
