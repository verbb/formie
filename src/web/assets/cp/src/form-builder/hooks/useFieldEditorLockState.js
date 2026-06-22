import { useEffect, useState } from 'react';

const useFieldEditorLockState = (field) => {
    const [builderLockedLive, setBuilderLockedLive] = useState(Boolean(field?.builderLocked));
    const [builderNoteLive, setBuilderNoteLive] = useState(field?.builderNote);
    const [isSessionUnlocked, setIsSessionUnlocked] = useState(!field?.builderLocked);

    useEffect(() => {
        setBuilderLockedLive(Boolean(field?.builderLocked));
        setBuilderNoteLive(field?.builderNote);
        setIsSessionUnlocked(!field?.builderLocked);
    }, [field]);

    const isSettingsLocked = builderLockedLive && !isSessionUnlocked;

    const syncFromFormValues = (values) => {
        if (values && Object.prototype.hasOwnProperty.call(values, 'builderLocked')) {
            setBuilderLockedLive(Boolean(values.builderLocked));
        }

        if (values && Object.prototype.hasOwnProperty.call(values, 'builderNote')) {
            setBuilderNoteLive(values.builderNote);
        }
    };

    return {
        builderLockedLive,
        builderNoteLive,
        isSettingsLocked,
        syncFromFormValues,
        unlock: () => {
            setIsSessionUnlocked(true);
        },
    };
};

export { useFieldEditorLockState };
