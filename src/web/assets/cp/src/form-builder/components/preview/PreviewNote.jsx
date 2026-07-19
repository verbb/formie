import React from 'react';

import { cn } from '@verbb/plugin-kit-react/utils';
import { Icon } from '@verbb/plugin-kit-react/components';

const NOTE_STYLES = {
    tip: {
        icon: 'lightbulb',
        className: 'border-[#0ea5e9] bg-[#f0f9ff] text-[#0c4a6e]',
        iconClassName: 'text-[#0284c7]',
    },
    warning: {
        icon: 'triangle-exclamation',
        className: 'border-[#f59e0b] bg-[#fffbeb] text-[#92400e]',
        iconClassName: 'text-[#d97706]',
    },
    info: {
        icon: 'circle-info',
        className: 'border-[#94a3b8] bg-[#f8fafc] text-[#334155]',
        iconClassName: 'text-[#64748b]',
    },
    error: {
        icon: 'circle-exclamation',
        className: 'border-[#ef4444] bg-[#fef2f2] text-[#991b1b]',
        iconClassName: 'text-[#dc2626]',
    },
};

export const PreviewNote = ({ text = '', style = 'tip' }) => {
    const normalizedStyle = NOTE_STYLES[style] ? style : 'tip';
    const config = NOTE_STYLES[normalizedStyle];
    const displayText = String(text || '').trim();

    return (
        <div
            className={cn(
                'formie-field-preview-note',
                'flex items-start gap-3',
                'rounded-md border px-3 py-2.5',
                'text-[13px] leading-5',
                config.className,
            )}
        >
            <Icon
                icon={config.icon}
                className={cn('mt-0.5 size-4 shrink-0', config.iconClassName)}
            />

            {displayText ? (
                <div className="min-w-0 whitespace-pre-wrap break-words">
                    {displayText}
                </div>
            ) : null}
        </div>
    );
};
