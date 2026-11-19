type Attr = string | number | boolean | null | undefined | EventListener;
export type Child = Node | string | null | undefined | false | Child[];

/** Create an element. `on*` attributes add listeners; `false`/`null` attributes are skipped. */
export function h<K extends keyof HTMLElementTagNameMap>(
    tag: K,
    attrs: Record<string, Attr> = {},
    ...children: Child[]
): HTMLElementTagNameMap[K] {
    const el = document.createElement(tag);

    for (const [name, value] of Object.entries(attrs)) {
        if (value === false || value === null || value === undefined) {
            continue;
        }

        if (typeof value === 'function') {
            el.addEventListener(name.slice(2).toLowerCase(), value);
        } else if (name === 'value' || name === 'checked') {
            (el as unknown as Record<string, unknown>)[name] = value;
        } else {
            el.setAttribute(name, value === true ? '' : String(value));
        }
    }

    el.append(...nodes(children));

    return el;
}

/** Flatten children, dropping empty ones. */
export function nodes(children: Child[]): (Node | string)[] {
    return children.flatMap((child) => {
        if (Array.isArray(child)) {
            return nodes(child);
        }

        return child === null || child === undefined || child === false ? [] : [child];
    });
}

/** Stable element id for a model path. */
export function idFor(path: string): string {
    return `f-${path.replace(/[^\w-]/g, '-')}`;
}

/** Move an item in a list; returns false when it can't move. */
export function move<T>(list: T[], index: number, offset: number): boolean {
    const target = index + offset;

    if (target < 0 || target >= list.length) {
        return false;
    }

    [list[index], list[target]] = [list[target], list[index]];

    return true;
}
