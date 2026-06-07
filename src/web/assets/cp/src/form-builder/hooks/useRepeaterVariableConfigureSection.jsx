import { useCallback } from 'react';
import { RepeaterRowTargetingControls } from '@form-builder/fields/components/RepeaterRowTargetingControls';
import {
    applyRepeaterRowTargetingToToken,
    createSyntheticRepeaterSubFieldOption,
    isRepeaterScopedFieldToken,
    isRepeaterSubFieldOption,
    parseRepeaterRowTargeting,
    shouldShowRepeaterRowTargeting,
} from '@form-builder/fields/utils/repeaterRowTargeting';

export function useRepeaterVariableConfigureSection() {
    return useCallback(({
        tokenValue,
        variableOption,
        onPendingTokenChange,
        configureResetKey = '',
        configureStateRef = null,
        prepareSaveRef = null,
        getPendingTokenValue = null,
    }) => {
        const resolveLatestToken = () => {
            return String(getPendingTokenValue?.() || tokenValue || '');
        };

        const resolveOption = (latestToken) => {
            if (isRepeaterSubFieldOption(variableOption)) {
                return variableOption;
            }

            if (isRepeaterScopedFieldToken(latestToken)) {
                return createSyntheticRepeaterSubFieldOption(latestToken, variableOption?.label || '');
            }

            return null;
        };

        const latestToken = resolveLatestToken();
        const resolvedOption = resolveOption(latestToken);

        if (!shouldShowRepeaterRowTargeting(latestToken, resolvedOption)) {
            if (prepareSaveRef) {
                prepareSaveRef.current = null;
            }

            return null;
        }

        const applyTargeting = (targeting) => {
            const currentToken = resolveLatestToken();
            const nextToken = applyRepeaterRowTargetingToToken(currentToken, targeting);

            onPendingTokenChange?.(nextToken);
        };

        const flushPendingToken = () => {
            const currentToken = resolveLatestToken();
            const targeting = configureStateRef?.current || parseRepeaterRowTargeting(currentToken);

            applyTargeting(targeting);
        };

        if (prepareSaveRef) {
            prepareSaveRef.current = flushPendingToken;
        }

        return (
            <RepeaterRowTargetingControls
                key={configureResetKey}
                tokenValue={latestToken}
                variableOption={resolvedOption}
                targetingRef={configureStateRef}
                resetKey={configureResetKey}
                onTargetingChange={(targeting) => {
                    if (configureStateRef) {
                        configureStateRef.current = targeting;
                    }

                    applyTargeting(targeting);
                }}
            />
        );
    }, []);
}
