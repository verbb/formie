import React from 'react';

import { Button, Icon, TiptapContent } from '@verbb/plugin-kit-react/components';

import { cn } from '@verbb/plugin-kit-react/utils';
import { hasRichTextValue, normalizeRichTextValue } from '@form-builder/utils/richTextValue';

const FieldEditorNotices = ({
    field,
    isSyncedField,
    builderNote,
    isSettingsLocked,
    onUnlock,
}) => {
    const hasBuilderNote = hasRichTextValue(builderNote);

    return (
        <>
            {isSyncedField && (
                <div className="m-4 mb-0 flex shrink-0 items-start gap-2 rounded border border-[#f6ad55] bg-[#fffaf0] px-3 py-2 text-[12px] leading-normal text-[#b45309]">
                    <Icon icon="triangle-exclamation" className="mt-0.5 size-3 shrink-0" />
                    <p className="m-0">
                        <span>{Craft.t('formie', 'Warning: Currently editing synced field. Changes to this field will be applied to all instances of this field.')}</span>{' '}
                        <a
                            href={Craft.getCpUrl('formie/settings/synced-fields')}
                            className="font-bold underline"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {(field?.usageCount ?? 0) > 1
                                ? Craft.t('formie', 'View usage ({num} forms)', { num: field.usageCount })
                                : Craft.t('formie', 'View all synced fields')}
                        </a>
                    </p>
                </div>
            )}

            {hasBuilderNote && (
                <div className={cn(
                    /* v1 screen2: #fffaf0 / #f6ad55 / #b45309 @ 12px — color/size inherit into pk-tiptap-content. */
                    'mx-4 mb-0 flex shrink-0 flex-col gap-2 rounded border border-[#f6ad55] bg-[#fffaf0] px-3 py-2 text-[12px] leading-normal text-[#b45309]',
                    isSyncedField ? 'mt-2' : 'mt-4',
                )}>
                    <TiptapContent value={normalizeRichTextValue(builderNote)} />
                </div>
            )}

            {isSettingsLocked && (
                <div className={cn(
                    'mx-4 mb-0 flex shrink-0 items-center justify-between gap-2 rounded border border-[#cbd5e1] bg-[#f8fafc] px-3 py-2 text-[12px] text-[#475569]',
                    (isSyncedField || hasBuilderNote) ? 'mt-2' : 'mt-4',
                )}>
                    <div className="flex items-center gap-2">
                        <Icon icon="lock" className="size-3 shrink-0" />
                        <span>{Craft.t('formie', 'Field settings are locked. Unlock to edit or save changes.')}</span>
                    </div>

                    <Button
                        type="button"
                        size="sm"
                        onClick={onUnlock}
                    >
                        {Craft.t('formie', 'Unlock')}
                    </Button>
                </div>
            )}
        </>
    );
};

export { FieldEditorNotices };
