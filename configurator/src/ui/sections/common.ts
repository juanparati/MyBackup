import { changed } from '../../store.ts';
import { h, move, type Child } from '../dom.ts';

/** A collapsible form section. */
export function section(title: string, id: string, intro: Child, body: Child, open = false): HTMLElement {
    return h('details', { class: 'section', id: `section-${id}`, open },
        h('summary', {}, h('h2', {}, title)),
        intro && h('p', { class: 'intro' }, intro),
        body,
    );
}

/** Move up / move down / remove buttons for an item of `list`. */
export function itemButtons<T>(list: T[], index: number, label: string, rerender: () => void): HTMLElement {
    const act = (fn: () => boolean | void) => () => {
        if (fn() !== false) {
            changed();
            rerender();
        }
    };

    return h('div', { class: 'item-buttons' },
        h('button', { type: 'button', class: 'icon', title: `Move ${label} up`, 'aria-label': `Move ${label} up`, disabled: index === 0, onclick: act(() => move(list, index, -1)) }, '↑'),
        h('button', { type: 'button', class: 'icon', title: `Move ${label} down`, 'aria-label': `Move ${label} down`, disabled: index === list.length - 1, onclick: act(() => move(list, index, 1)) }, '↓'),
        h('button', { type: 'button', class: 'icon danger', title: `Remove ${label}`, 'aria-label': `Remove ${label}`, onclick: act(() => void list.splice(index, 1)) }, '✕'),
    );
}
