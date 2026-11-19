import { ACTIONS, initialValues } from '../../model/blocks.ts';
import type { BackupPlan, PostAction } from '../../model/types.ts';
import { changed } from '../../store.ts';
import { h } from '../dom.ts';
import { blockFields, extraField } from '../fields.ts';
import { itemButtons, section } from './common.ts';

export function postActionsSection(plan: BackupPlan): HTMLElement {
    const list = h('div', { class: 'items' });

    const render = () => {
        list.replaceChildren(
            ...plan.post_actions.map((action, i) => actionCard(plan, action, i, render)),
            h('div', { class: 'add-row' },
                Object.entries(ACTIONS).map(([type, { label, fields }]) => h('button', {
                    type: 'button',
                    onclick: () => {
                        plan.post_actions.push({ type, values: initialValues(fields), extra: {} });
                        changed();
                        render();
                    },
                }, `+ ${label}`)),
            ),
        );
    };

    render();

    return section('Post actions', 'post-actions', 'Run in order after a successful backup (not on dry runs), before old snapshots are rotated.', list);
}

function actionCard(plan: BackupPlan, action: PostAction, index: number, rerenderList: () => void): HTMLElement {
    const path = `post_actions.${index}`;
    const definition = ACTIONS[action.type];

    return h('fieldset', { class: 'card' },
        h('legend', { 'data-path': `${path}.type`, tabindex: -1 }, `${index + 1}. ${definition?.label ?? `Unknown action "${action.type}"`}`),
        h('div', { class: 'card-head' },
            h('p', { class: 'hint' }, definition?.hint ?? 'MyBackup skips actions it doesn\'t know. Kept as written.'),
            itemButtons(plan.post_actions, index, `action ${index + 1}`, rerenderList),
        ),
        h('div', { class: 'grid' },
            blockFields(action, definition?.fields ?? [], path),
            extraField(`${path}.extra`, () => action.extra, (extra) => (action.extra = extra)),
        ),
    );
}
