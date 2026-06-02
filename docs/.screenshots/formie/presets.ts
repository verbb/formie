import type {
    ScreenshotStep,
    ScreenshotTarget,
    ScreenshotViewport,
} from '@verbb/docs-screenshots/types';
import {
    createCpDetailViewPreset as createBaseCpDetailViewPreset,
    createCpFocusedRegionPreset as createBaseCpFocusedRegionPreset,
    createCpFullScreenPreset as createBaseCpFullScreenPreset,
    createCpModalPreset as createBaseCpModalPreset,
} from '@verbb/docs-screenshots/presets';

// This file is the Formie-specific adaptation layer on top of the shared
// screenshot package. The shared package knows how to run screenshots; this
// file knows how to make Formie's builder screens behave predictably enough to
// be screenshot-friendly.
type CpFocusedRegionOptions = {
    selector?: string;
    viewport?: ScreenshotViewport;
    padding?: NonNullable<Extract<ScreenshotTarget, { type: 'selector' }>['padding']>;
    hidePlaceholder?: boolean;
};

type CpFullScreenOptions = {
    viewport?: ScreenshotViewport;
    hidePlaceholder?: boolean;
};

type CpModalOptions = {
    viewport?: ScreenshotViewport;
    selector?: string;
    padding?: NonNullable<Extract<ScreenshotTarget, { type: 'selector' }>['padding']>;
};

type CpDetailViewOptions = {
    viewport?: ScreenshotViewport;
    selector?: string;
    padding?: NonNullable<Extract<ScreenshotTarget, { type: 'selector' }>['padding']>;
    hidePlaceholder?: boolean;
};

type FormieBuilderFrameOptions = {
    fitToViewport?: boolean;
    hideExistingFieldsSidebar?: boolean;
    hideNewPageButton?: boolean;
    normalizeSettingsButton?: boolean;
    resetTabsScroller?: boolean;
};

const formieScrollResetSelectors = [
    'html',
    'body',
    '#content-container',
    '#main-content',
    '#content',
    '.content-pane',
    '.formie-form-page',
    '.formie-form-builder',
];

function buildFormieCleanupCss({ hidePlaceholder = true }: { hidePlaceholder?: boolean }) {
    // These rules intentionally strip away Craft chrome and other noisy layout
    // pieces while preserving enough spacing to make screenshots feel natural.
    // They are not intended to be "normal runtime CSS"; they are screenshot
    // composition helpers.
    const rules = [
        'craft-global-sidebar, footer#global-footer { display: none !important; }',
        'craft-global-sidebar { width: 0 !important; min-width: 0 !important; flex: 0 0 0 !important; }',
        '#global-header * { display: none !important; }',
        '#details-container { position: static !important; }',
        'body.fixed-header #header { position: static !important; top: auto !important; }',
        'body.fixed-header #content-container { padding-top: 0 !important; }',
        '#content-container, #main-content, #content { max-width: none !important; }',
        '#content-container { padding: 24px !important; }',
        '#main-content { padding-top: 0 !important; }',
        '#page-container, #content-container, #main-content, #content, .content-pane, .formie-form-page { left: 0 !important; margin-left: 0 !important; }',
        'html, body, * { scrollbar-width: none !important; -ms-overflow-style: none !important; }',
        'html::-webkit-scrollbar, body::-webkit-scrollbar, *::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }',
        '.formie-form-builder .overflow-y-auto, .formie-form-builder [class*="overflow-y-auto"], .formie-form-builder .overflow-x-auto, .formie-form-builder [class*="overflow-x-auto"] { overflow: hidden !important; }',
    ];

    if (hidePlaceholder) {
        rules.push('.formie-form-builder__placeholder { display: none !important; }');
    }

    return rules.join('\n');
}

function buildFormieShadowCleanupCss() {
    // Formie's builder uses open shadow roots in some places, so scrollbar
    // cleanup has to be mirrored there as well or the screenshots end up with
    // inconsistent rails/thumbs even when the light DOM looks clean.
    return [
        ':host, :host * { scrollbar-width: none !important; -ms-overflow-style: none !important; }',
        ':host::-webkit-scrollbar, :host *::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }',
        '.overflow-y-auto, [class*="overflow-y-auto"], .overflow-x-auto, [class*="overflow-x-auto"] { overflow: hidden !important; }',
    ].join('\n');
}

function buildFormieCleanupStep(options: { hidePlaceholder?: boolean }): ScreenshotStep {
    return {
        type: 'evaluate',
        expression: `
            (() => {
                // Re-inject on every run so preview iterations do not accumulate
                // stale style tags from previous attempts.
                const presetCss = ${JSON.stringify(buildFormieCleanupCss(options))};
                const shadowCss = ${JSON.stringify(buildFormieShadowCleanupCss())};
                const existing = document.querySelector('style[data-formie-screenshot-preset="cp"]');

                if (existing) {
                    existing.remove();
                }

                const style = document.createElement('style');
                style.dataset.formieScreenshotPreset = 'cp';
                style.textContent = presetCss;
                document.head.appendChild(style);

                const shadowHosts = document.querySelectorAll('.formie-form-builder');

                shadowHosts.forEach((host, index) => {
                    if (!(host instanceof HTMLElement) || !host.shadowRoot) {
                        return;
                    }

                    host.shadowRoot.querySelectorAll('style[data-formie-screenshot-shadow-preset="cp"]').forEach((node) => node.remove());

                    const shadowStyle = document.createElement('style');
                    shadowStyle.dataset.formieScreenshotShadowPreset = 'cp';
                    shadowStyle.dataset.formieScreenshotShadowIndex = String(index);
                    shadowStyle.textContent = shadowCss;
                    host.shadowRoot.appendChild(shadowStyle);
                });
            })();
        `,
    };
}

export function createFormieScrollResetStep(extraSelectors: string[] = []): ScreenshotStep {
    return {
        type: 'evaluate',
        expression: `
            (() => {
                // Scroll position is one of the biggest sources of screenshot
                // drift when iterating quickly in preview mode. Keeping this as
                // a named helper makes the intent obvious in scenarios.
                window.scrollTo(0, 0);

                const selectors = ${JSON.stringify([...formieScrollResetSelectors, ...extraSelectors])};

                for (const selector of selectors) {
                    const element = document.querySelector(selector);

                    if (element instanceof HTMLElement) {
                        element.scrollTop = 0;
                        element.scrollLeft = 0;
                    }
                }
            })();
        `,
    };
}

export function createFormieBuilderFrameStep(options: FormieBuilderFrameOptions = {}): ScreenshotStep {
    return {
        type: 'evaluate',
        expression: `
            (() => {
                // This helper centralizes the Formie-builder-only "make the UI
                // screenshotable" behavior so scenarios can ask for outcomes
                // like "hide the existing fields sidebar" instead of carrying
                // around DOM surgery inline.
                window.scrollTo(0, 0);

                const selectors = ${JSON.stringify(formieScrollResetSelectors)};

                for (const selector of selectors) {
                    const element = document.querySelector(selector);

                    if (element instanceof HTMLElement) {
                        element.scrollTop = 0;
                        element.scrollLeft = 0;
                    }
                }

                const builder = document.querySelector('.formie-form-builder');

                if (!(builder instanceof HTMLElement)) {
                    return;
                }

                builder.style.position = 'relative';
                builder.style.left = '0';
                builder.style.transform = 'none';
                builder.style.transformOrigin = 'top left';

                const options = ${JSON.stringify(options)};

                if (options.fitToViewport) {
                    // Builder screens can be wider/taller than the authoring
                    // viewport. We fit them down only when explicitly asked,
                    // because some screenshots prefer native scale plus a
                    // tighter crop instead.
                    const builderRect = builder.getBoundingClientRect();
                    const availableWidth = Math.max(1, window.innerWidth - 48);
                    const availableHeight = Math.max(1, window.innerHeight - builderRect.top - 24);
                    const widthScale = availableWidth / Math.max(1, builderRect.width);
                    const heightScale = availableHeight / Math.max(1, builderRect.height);
                    const scale = Math.min(1, widthScale, heightScale);
                    const offsetLeft = Math.max(0, 24 - builderRect.x);

                    builder.style.left = offsetLeft + 'px';
                    builder.style.transform = \`scale(\${scale})\`;
                    builder.style.transformOrigin = 'top left';
                }

                if (options.hideNewPageButton) {
                    const quickAddButton = document.querySelector('button[title="New Page"]');

                    if (quickAddButton instanceof HTMLElement) {
                        quickAddButton.style.display = 'none';
                    }
                }

                if (options.normalizeSettingsButton) {
                    const settingsButton = document.querySelector('button[title="Page Settings"]');

                    if (settingsButton instanceof HTMLElement) {
                        settingsButton.style.marginRight = '0';
                    }
                }

                if (options.hideExistingFieldsSidebar) {
                    // The sidebar is useful in some builder screenshots and
                    // distracting in others. We support both patterns without
                    // forcing each scenario to rediscover the sidebar DOM.
                    const sidebarHeading = Array.from(builder.querySelectorAll('h4'))
                        .find((heading) => heading.textContent?.trim() === 'Existing Fields');

                    if (sidebarHeading instanceof HTMLElement) {
                        const panel = sidebarHeading.closest('aside, section, div');

                        if (panel instanceof HTMLElement) {
                            panel.style.display = 'none';
                        }
                    }

                    const existingFieldsButton = Array.from(builder.querySelectorAll('button'))
                        .find((button) => button.textContent?.trim() === 'Add existing fields');

                    if (existingFieldsButton instanceof HTMLElement) {
                        const panel = existingFieldsButton.closest('aside, section, div');

                        if (panel instanceof HTMLElement) {
                            panel.style.display = 'none';
                        }
                    }
                }

                if (options.resetTabsScroller) {
                    const tabsScroller = builder.querySelector('[data-orientation="horizontal"][role="tablist"]')?.parentElement;

                    if (tabsScroller instanceof HTMLElement) {
                        tabsScroller.scrollLeft = 0;
                        tabsScroller.style.overflow = 'hidden';
                    }
                }
            })();
        `,
    };
}

export function createCpFocusedRegionPreset(options: CpFocusedRegionOptions = {}) {
    const preset = createBaseCpFocusedRegionPreset(options);

    return {
        ...preset,
        steps: [
            buildFormieCleanupStep({ hidePlaceholder: options.hidePlaceholder }),
            ...preset.steps,
        ] satisfies ScreenshotStep[],
    };
}

export function createCpFullScreenPreset(options: CpFullScreenOptions = {}) {
    const preset = createBaseCpFullScreenPreset(options);

    return {
        ...preset,
        steps: [
            buildFormieCleanupStep({ hidePlaceholder: options.hidePlaceholder }),
            ...preset.steps,
        ] satisfies ScreenshotStep[],
    };
}

export function createCpModalPreset(options: CpModalOptions = {}) {
    return createBaseCpModalPreset(options);
}

export function createCpDetailViewPreset(options: CpDetailViewOptions = {}) {
    const preset = createBaseCpDetailViewPreset(options);

    return {
        ...preset,
        steps: [
            buildFormieCleanupStep({ hidePlaceholder: options.hidePlaceholder }),
            ...preset.steps,
        ] satisfies ScreenshotStep[],
    };
}
