import { Button } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faCircleCheck,
    faCircleInfo,
    faEmptySet,
    faExclamationTriangle,
} from '@fortawesome/pro-solid-svg-icons';

const VARIANT_CONFIG = {
    empty: {
        icon: faEmptySet,
        iconColor: 'text-slate-500',
        iconContainer: 'bg-slate-200/55',
        titleClassName: 'text-base font-medium text-gray-900',
        messageClassName: 'text-sm text-gray-500',
    },
    error: {
        icon: faExclamationTriangle,
        iconColor: 'text-rose-600',
        iconContainer: 'bg-rose-500/12',
        titleClassName: 'text-base font-medium text-gray-900',
        messageClassName: 'text-sm text-gray-500',
    },
    success: {
        icon: faCircleCheck,
        iconColor: 'text-emerald-600',
        iconContainer: 'bg-slate-100',
        titleClassName: 'text-base font-medium text-gray-900',
        messageClassName: 'text-sm text-gray-500',
    },
    info: {
        icon: faCircleInfo,
        iconColor: 'text-sky-600',
        iconContainer: 'bg-slate-100',
        titleClassName: 'text-base font-medium text-gray-900',
        messageClassName: 'text-sm text-gray-500',
    },
};

const StatePanel = ({
    variant = 'empty',
    icon = null,
    title = null,
    message = null,
    primaryAction = null,
    secondaryAction = null,
    children = null,
    containerClassName = 'flex flex-1 items-center justify-center py-12',
    contentClassName = 'flex w-[90%] max-w-[560px] flex-col items-center text-center',
    titleClassName = '',
    messageClassName = '',
    showIcon = true,
}) => {
    const config = VARIANT_CONFIG[variant] || VARIANT_CONFIG.empty;
    const resolvedIcon = icon || config.icon;

    return (
        <div className={containerClassName}>
            <div className={contentClassName}>
                {showIcon && resolvedIcon ? (
                    <div className={cn('mb-3 flex size-10 items-center justify-center rounded-[10px]', config.iconContainer)}>
                        <FontAwesomeIcon icon={resolvedIcon} className={cn('size-5', config.iconColor)} />
                    </div>
                ) : null}

                {title ? (
                    <h2 className={cn('mb-2', config.titleClassName, titleClassName)}>
                        {title}
                    </h2>
                ) : null}

                {message ? (
                    <p className={cn('mb-4 max-w-[560px]', config.messageClassName, messageClassName)}>
                        {message}
                    </p>
                ) : null}

                {children}

                {(primaryAction || secondaryAction) ? (
                    <div className="mt-2 flex items-center justify-center gap-2">
                        {secondaryAction?.label && secondaryAction?.onClick ? (
                            <Button type="button" variant={secondaryAction.variant || 'secondary'} onClick={secondaryAction.onClick}>
                                {secondaryAction.label}
                            </Button>
                        ) : null}

                        {primaryAction?.label && primaryAction?.onClick ? (
                            <Button type="button" variant={primaryAction.variant || 'primary'} onClick={primaryAction.onClick}>
                                {primaryAction.label}
                            </Button>
                        ) : null}
                    </div>
                ) : null}
            </div>
        </div>
    );
};

export { StatePanel };
