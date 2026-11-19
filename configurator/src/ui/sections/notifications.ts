import { initialValues, MAIL_FIELDS, SLACK_FIELDS, type FieldDef } from '../../model/blocks.ts';
import type { BackupPlan, Block } from '../../model/types.ts';
import { changed } from '../../store.ts';
import { h, nodes } from '../dom.ts';
import { blockFields, extraField } from '../fields.ts';
import { section } from './common.ts';

type Channel = 'slack' | 'mail';

const CHANNELS: Record<Channel, { label: string; fields: FieldDef[]; extraHint: string }> = {
    slack: { label: 'Slack', fields: SLACK_FIELDS, extraHint: 'Other Slack settings, kept as written.' },
    mail: { label: 'E-mail (SMTP)', fields: MAIL_FIELDS, extraHint: 'Other Laravel mailer keys (local_domain, source_ip, reply_to…).' },
};

export function notificationsSection(plan: BackupPlan): HTMLElement {
    const body = h('div', { class: 'items' });
    /** Settings of a disabled channel, restored when it's enabled again. */
    const disabled: Partial<Record<Channel, Block>> = {};

    const render = () => {
        body.replaceChildren(...nodes([
            ...(Object.keys(CHANNELS) as Channel[]).map((channel) => {
                const { label, fields, extraHint } = CHANNELS[channel];
                const block = plan.notifications[channel];

                return h('fieldset', { class: 'card' },
                    h('legend', {}, h('label', { class: 'switch' },
                        h('input', {
                            type: 'checkbox',
                            checked: block !== null,
                            onchange: () => {
                                if (block) {
                                    disabled[channel] = block;
                                    plan.notifications[channel] = null;
                                } else {
                                    plan.notifications[channel] = disabled[channel] ?? { values: initialValues(fields), extra: {} };
                                }

                                changed();
                                render();
                            },
                        }),
                        ` ${label}`)),
                    block && h('div', { class: 'grid' },
                        blockFields(block, fields, `notifications.${channel}`),
                        extraField(`notifications.${channel}.extra`, () => block.extra, (extra) => (block.extra = extra), extraHint),
                    ),
                );
            }),
            Object.keys(plan.notifications.extra).length > 0 && extraField('notifications.extra',
                () => plan.notifications.extra, (extra) => (plan.notifications.extra = extra),
                'Only slack and mail are supported; any other key here makes notifications fail.'),
        ]));
    };

    render();

    return section('Notifications', 'notifications', 'Sent when a backup finishes and when it fails (not on dry runs). Both channels can be used at once.', body);
}
