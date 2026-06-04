import useAppStore from '@form-builder/hooks/useAppStore';

const renderUser = (user) => {
    if (!user?.name) {
        return Craft.t('app', 'Unknown');
    }

    if (user.url) {
        return (
            <a href={user.url} className="text-blue-600 no-underline hover:underline" target="_blank" rel="noopener noreferrer">
                {user.name}
            </a>
        );
    }

    return user.name;
};

const MetaRow = ({ label, user, date }) => {
    if (!date && !user?.name) {
        return null;
    }

    return (
        <p className="m-0">
            <span className="text-gray-600">{label}:</span>
            {' '}
            {renderUser(user)}
            {date ? (
                <>
                    {' '}
                    {Craft.t('formie', 'at')}
                    {' '}
                    {date}
                </>
            ) : null}
        </p>
    );
};

const FormMetaDetails = () => {
    const formId = useAppStore((state) => { return state.formId; });
    const formMeta = useAppStore((state) => { return state.formMeta; });

    if (!formId || !formMeta) {
        return null;
    }

    return (
        <div className="space-y-1.5 text-sm leading-relaxed text-gray-500">
            <MetaRow
                label={Craft.t('formie', 'Created by')}
                user={formMeta.createdBy}
                date={formMeta.dateCreated}
            />
            <MetaRow
                label={Craft.t('formie', 'Last updated by')}
                user={formMeta.updatedBy}
                date={formMeta.dateUpdated}
            />
        </div>
    );
};

export { FormMetaDetails };
