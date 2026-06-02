<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useData, useRoute } from 'vitepress';

import formieBaseCss from '../../../../formie-browser/src/css/formie-base.css?raw';
import formieThemePrimitivesCss from '../../../../formie-browser/src/css/theme-base/_primitives.css?raw';
import formieThemeControlsCss from '../../../../formie-browser/src/css/theme-base/_controls.css?raw';
import formieTokensCss from '../../../../formie-browser/src/css/theme/_tokens.css?raw';
import formieTypographyCss from '../../../../formie-browser/src/css/theme/_typography.css?raw';
import formieMessagesCss from '../../../../formie-browser/src/css/theme/_messages.css?raw';
import formieButtonsCss from '../../../../formie-browser/src/css/theme/_buttons.css?raw';
import formieLoadingCss from '../../../../formie-browser/src/css/theme/_loading.css?raw';
import formieProgressCss from '../../../../formie-browser/src/css/theme/_progress.css?raw';
import formieFormCss from '../../../../formie-browser/src/css/theme/forms/_form.css?raw';
import formieFieldCss from '../../../../formie-browser/src/css/theme/forms/_field.css?raw';
import formieGroupCss from '../../../../formie-browser/src/css/theme/fields/_group.css?raw';
import formieInputCss from '../../../../formie-browser/src/css/theme/fields/_input.css?raw';
import formieAddressCss from '../../../../formie-browser/src/css/theme/fields/_address.css?raw';
import formieFileCss from '../../../../formie-browser/src/css/theme/fields/_file.css?raw';
import formieCheckRadioCss from '../../../../formie-browser/src/css/theme/fields/_check-radio.css?raw';
import formieNestedCss from '../../../../formie-browser/src/css/theme/fields/_nested.css?raw';
import formieRepeaterCss from '../../../../formie-browser/src/css/theme/fields/_repeater.css?raw';
import formieRichTextCss from '../../../../formie-browser/src/css/theme/fields/_rich-text.css?raw';
import formieSelectCss from '../../../../formie-browser/src/css/theme/fields/_select.css?raw';
import formieSignatureCss from '../../../../formie-browser/src/css/theme/fields/_signature.css?raw';
import formieSummaryCss from '../../../../formie-browser/src/css/theme/fields/_summary.css?raw';
import formieTableCss from '../../../../formie-browser/src/css/theme/fields/_table.css?raw';
import formieTextLimitCss from '../../../../formie-browser/src/css/theme/fields/_text-limit.css?raw';
import formieAccessibilityCss from '../../../../formie-browser/src/css/theme/utilities/_accessibility.css?raw';
import previewGalleryCss from '../preview-gallery.css?raw';
import { initFormiePreviewClient } from './formiePreviewClient';
import { resolvePreviewSource, type FormiePreviewSourceDefinition } from './previewSources';

const PREVIEW_HEIGHT_BUFFER_PX = 8;

const props = withDefaults(defineProps<{
    markup?: string;
    minHeight?: number;
    src?: string;
}>(), {
    minHeight: 120,
});

const route = useRoute();
const { site } = useData();
const iframeRef = ref<HTMLIFrameElement | null>(null);
const previewSource = ref<FormiePreviewSourceDefinition | null>(null);
const frameHeight = ref(props.minHeight);

let requestId = 0;

watch(() => [route.path, props.src, site.value.base], async() => {
    if (!props.src) {
        previewSource.value = null;
        return;
    }

    const nextRequestId = ++requestId;
    const resolvedSource = await resolvePreviewSource(props.src, route.path, site.value.base);

    if (nextRequestId !== requestId) {
        return;
    }

    previewSource.value = resolvedSource;
}, { immediate: true });

const resolvedMarkup = computed(() => {
    return previewSource.value?.markup ?? props.markup ?? '';
});

const resolvedMinHeight = computed(() => {
    return previewSource.value?.minHeight ?? props.minHeight;
});

const runtimeConfig = computed(() => {
    const modules = previewSource.value?.modules;

    return JSON.stringify({
        modules: modules?.length ? modules : undefined,
    }).replaceAll('<', '\\u003c');
});

watch(resolvedMinHeight, (value) => {
    frameHeight.value = value;
}, { immediate: true });

watch(() => [resolvedMarkup.value, resolvedMinHeight.value], (_value, previousValue) => {
    if (!previousValue || previousValue[0] !== resolvedMarkup.value || previousValue[1] !== resolvedMinHeight.value) {
        frameHeight.value = resolvedMinHeight.value;
    }
});

function updateFrameHeight(nextHeight: number): void {
    if (!Number.isFinite(nextHeight) || nextHeight <= 0) {
        return;
    }

    frameHeight.value = Math.ceil(nextHeight + PREVIEW_HEIGHT_BUFFER_PX);
}

function measureIframeContentHeight(): number {
    const contentDocument = iframeRef.value?.contentDocument;
    const body = contentDocument?.body;
    const iframeHTMLElement = contentDocument?.defaultView?.HTMLElement;

    if (!body) {
        return resolvedMinHeight.value;
    }

    const bodyRect = body.getBoundingClientRect();
    const bodyStyle = contentDocument.defaultView?.getComputedStyle(body);
    const paddingTop = parseFloat(bodyStyle?.paddingTop || '0') || 0;
    const paddingBottom = parseFloat(bodyStyle?.paddingBottom || '0') || 0;
    const contentBottom = Array.from(body.children).reduce((max, child) => {
        if (!iframeHTMLElement || !(child instanceof iframeHTMLElement) || child.tagName === 'SCRIPT') {
            return max;
        }

        const rect = child.getBoundingClientRect();
        return Math.max(max, rect.bottom - bodyRect.top);
    }, paddingTop);

    return Math.ceil(contentBottom + paddingBottom);
}

function handleMessage(event: MessageEvent): void {
    if (event.data?.type !== 'formie-preview:height') {
        return;
    }

    if (event.source !== iframeRef.value?.contentWindow) {
        return;
    }

    updateFrameHeight(Number(event.data.height));
}

function handleFrameLoad(): void {
    const contentWindow = iframeRef.value?.contentWindow;

    if (!contentWindow) {
        return;
    }

    updateFrameHeight(measureIframeContentHeight());
    void initFormiePreviewClient(contentWindow, updateFrameHeight);
}

onMounted(() => {
    window.addEventListener('message', handleMessage);
});

onBeforeUnmount(() => {
    window.removeEventListener('message', handleMessage);
});

const srcdoc = computed(() => {
    const frameworkCss = [
        formieBaseCss,
        formieThemePrimitivesCss,
        formieThemeControlsCss,
        formieTokensCss,
        formieTypographyCss,
        formieMessagesCss,
        formieButtonsCss,
        formieLoadingCss,
        formieProgressCss,
        formieFormCss,
        formieFieldCss,
        formieGroupCss,
        formieInputCss,
        formieAddressCss,
        formieFileCss,
        formieCheckRadioCss,
        formieNestedCss,
        formieRepeaterCss,
        formieRichTextCss,
        formieSelectCss,
        formieSignatureCss,
        formieSummaryCss,
        formieTableCss,
        formieTextLimitCss,
        formieAccessibilityCss,
        previewGalleryCss,
    ].join('\n');

    return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    ${frameworkCss}
    body { margin: 0; padding: 16px; background: #fff; }
  </style>
</head>
<body>
  <script id="formie-preview-config" type="application/json">${runtimeConfig.value}<\/script>
  ${resolvedMarkup.value}
</body>
</html>`;
});
</script>

<template>
    <iframe
        ref="iframeRef"
        class="formie-preview-frame"
        :style="{ height: `${frameHeight}px` }"
        :srcdoc="srcdoc"
        title="Formie preview"
        loading="lazy"
        @load="handleFrameLoad"
    />
</template>
