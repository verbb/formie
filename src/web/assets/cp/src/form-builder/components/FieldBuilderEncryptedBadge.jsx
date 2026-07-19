import { cn } from '@verbb/plugin-kit-react/utils';
import { Icon } from '@verbb/plugin-kit-react/components';

const FieldBuilderEncryptedBadge = ({ enabled, className }) => {
    if (!enabled) {
        return null;
    }

    return (
        <div
            className={cn(
                'inline-flex items-center gap-1',
                'rounded-[10px] border border-[#9333ea] bg-[#faf5ff]',
                'px-[6px] py-[3px]',
                'text-[10px] font-medium text-[#7e22ce]',
                className,
            )}
            title={Craft.t('formie', 'Enable Content Encryption')}
        >
            <Icon icon="lock" className="size-2.5" />
            <span>{Craft.t('formie', 'Encrypted')}</span>
        </div>
    );
};

export { FieldBuilderEncryptedBadge };
