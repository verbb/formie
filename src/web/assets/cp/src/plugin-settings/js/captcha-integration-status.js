function parseEnabledMenuValue(value) {
    const normalized = String(value ?? '').trim().toLowerCase();

    if (normalized === '1' || normalized === 'true') {
        return true;
    }

    if (normalized === '0' || normalized === 'false') {
        return false;
    }

    return null;
}

function updateSidebarStatus($container, handle, enabled) {
    $container
        .find(`[data-fui-captcha-status="${handle}"]`)
        .toggleClass('on', enabled)
        .toggleClass('disabled', !enabled);
}

function bindCaptchaIntegrationStatus(container) {
    const $ = window.jQuery;

    if (!$) {
        return;
    }

    const $container = $(container);

    if ($container.data('fuiCaptchaStatusBound')) {
        return;
    }

    $container.data('fuiCaptchaStatusBound', true);

    $container.on('change', '[data-fui-captcha-status-trigger]', function onEnabledChange() {
        const handle = $(this).data('fui-captcha-status-trigger');
        const enabled = parseEnabledMenuValue(this.value);

        if (!handle || enabled === null) {
            return;
        }

        updateSidebarStatus($container, handle, enabled);
    });
}

export function initCaptchaIntegrationStatus(root = document) {
    const $ = window.jQuery;

    if (!$) {
        return;
    }

    $(root).find('#fui-integrations-settings').each((_, container) => {
        bindCaptchaIntegrationStatus(container);
    });
}

function initWhenReady() {
    initCaptchaIntegrationStatus();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWhenReady);
} else {
    initWhenReady();
}
