import { exec, init as initPell } from 'pell';
import pellCss from 'pell/dist/pell.min.css?inline';
import tokensCss from '#theme-css/_tokens.css?inline';
import richTextCss from '#theme-css/fields/_rich-text.css?inline';
import alignCenterIcon from '#icons/rich-text/aligncenter.svg?raw';
import alignLeftIcon from '#icons/rich-text/alignleft.svg?raw';
import alignRightIcon from '#icons/rich-text/alignright.svg?raw';
import boldIcon from '#icons/rich-text/bold.svg?raw';
import clearIcon from '#icons/rich-text/clear.svg?raw';
import codeIcon from '#icons/rich-text/code.svg?raw';
import heading1Icon from '#icons/rich-text/heading1.svg?raw';
import heading2Icon from '#icons/rich-text/heading2.svg?raw';
import imageIcon from '#icons/rich-text/image.svg?raw';
import italicIcon from '#icons/rich-text/italic.svg?raw';
import lineIcon from '#icons/rich-text/line.svg?raw';
import linkIcon from '#icons/rich-text/link.svg?raw';
import olistIcon from '#icons/rich-text/olist.svg?raw';
import paragraphIcon from '#icons/rich-text/paragraph.svg?raw';
import quoteIcon from '#icons/rich-text/quote.svg?raw';
import strikethroughIcon from '#icons/rich-text/strikethrough.svg?raw';
import ulistIcon from '#icons/rich-text/ulist.svg?raw';
import underlineIcon from '#icons/rich-text/underline.svg?raw';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';

const CONTAINER_SELECTOR = '[data-formie-rich-text]';
const FIELD_SELECTOR = '[data-formie-field], [data-formie-field-handle]';
const INPUT_SELECTOR = 'textarea[data-formie-multi-line-text-input]';
const MODULE_ID = 'rich-text';

ensureModuleStyles(MODULE_ID, [tokensCss, pellCss, richTextCss]);

type RichTextOptions = {
    buttons?: string[];
};

type RichTextAction = {
    name: string;
    icon: string;
    title?: string;
    result?: () => void;
};

type PellEditor = {
    content: HTMLElement;
};

type RichTextInput = HTMLTextAreaElement & {
    richText?: PellEditor;
};

const RICH_TEXT_ICONS = {
    bold: boldIcon,
    italic: italicIcon,
    underline: underlineIcon,
    strikethrough: strikethroughIcon,
    heading1: heading1Icon,
    heading2: heading2Icon,
    paragraph: paragraphIcon,
    quote: quoteIcon,
    olist: olistIcon,
    ulist: ulistIcon,
    code: codeIcon,
    line: lineIcon,
    link: linkIcon,
    image: imageIcon,
    alignleft: alignLeftIcon,
    aligncenter: alignCenterIcon,
    alignright: alignRightIcon,
    clear: clearIcon,
} as const;

function hasRichTextField(target: Element): boolean {
    if (target.matches(FIELD_SELECTOR)) {
        return !!target.querySelector(CONTAINER_SELECTOR) && !!target.querySelector(INPUT_SELECTOR);
    }

    return Array.from(target.querySelectorAll(FIELD_SELECTOR)).some((field) => {
        return !!field.querySelector(CONTAINER_SELECTOR) && !!field.querySelector(INPUT_SELECTOR);
    });
}

function getActionDefinitions(): RichTextAction[] {
    return [
        { name: 'bold', icon: RICH_TEXT_ICONS.bold },
        { name: 'italic', icon: RICH_TEXT_ICONS.italic },
        { name: 'underline', icon: RICH_TEXT_ICONS.underline },
        { name: 'strikethrough', icon: RICH_TEXT_ICONS.strikethrough },
        { name: 'heading1', icon: RICH_TEXT_ICONS.heading1 },
        { name: 'heading2', icon: RICH_TEXT_ICONS.heading2 },
        { name: 'paragraph', icon: RICH_TEXT_ICONS.paragraph },
        { name: 'quote', icon: RICH_TEXT_ICONS.quote },
        { name: 'olist', icon: RICH_TEXT_ICONS.olist },
        { name: 'ulist', icon: RICH_TEXT_ICONS.ulist },
        { name: 'code', icon: RICH_TEXT_ICONS.code },
        { name: 'line', icon: RICH_TEXT_ICONS.line },
        { name: 'link', icon: RICH_TEXT_ICONS.link },
        { name: 'image', icon: RICH_TEXT_ICONS.image },
        { name: 'alignleft', icon: RICH_TEXT_ICONS.alignleft, title: 'Align Left', result: () => exec('justifyLeft', '') },
        { name: 'aligncenter', icon: RICH_TEXT_ICONS.aligncenter, title: 'Align Center', result: () => exec('justifyCenter', '') },
        { name: 'alignright', icon: RICH_TEXT_ICONS.alignright, title: 'Align Right', result: () => exec('justifyRight', '') },
        {
            name: 'clear',
            icon: RICH_TEXT_ICONS.clear,
            title: 'Clear',
            result: () => {
                const selection = window.getSelection()?.toString() || '';
                if (selection) {
                    const linesToDelete = selection.split('\n').join('<br>');
                    exec('formatBlock', '<p>');
                    document.execCommand('insertHTML', false, linesToDelete);
                    return;
                }

                exec('formatBlock', '<p>');
            },
        },
    ];
}

function getActions(buttons?: string[]): Array<string | RichTextAction> {
    const selectedButtons = buttons?.length ? buttons : ['bold', 'italic'];
    const definitions = getActionDefinitions();

    return selectedButtons.map((button) => {
        return definitions.find((definition) => {
            return definition.name === button;
        });
    }).filter((definition): definition is RichTextAction => {
        return !!definition;
    });
}

function initRichTextField(container: HTMLElement, input: RichTextInput, options: RichTextOptions): () => void {
    const pellOptions = {
        element: container,
        defaultParagraphSeparator: 'p',
        styleWithCSS: true,
        actions: getActions(options.buttons),
        onChange: (html: string) => {
            const emptyParagraph = '<p><br></p>';
            // Treat Pell's empty document as blank so required validation matches
            // a never-touched textarea (and so clearing the editor can fail required).
            input.value = (!html || html === emptyParagraph) ? '' : html;
            // Bubble both generic input and a namespaced rich-text change hook.
            input.dispatchEvent(new Event('input', { bubbles: true }));
            dispatchFieldEvent(input, MODULE_ID, 'populate', {
                richText: input,
                value: input.value,
            });
        },
        classes: {
            actionbar: 'formie-rich-text-toolbar',
            button: 'formie-rich-text-button',
            content: 'formie-input formie-rich-text-content',
            selected: 'formie-rich-text-selected',
        },
    };

    dispatchFieldEvent(input, MODULE_ID, 'before-init', {
        richText: input,
        options: pellOptions,
    });

    const editor = initPell(pellOptions) as PellEditor;
    input.richText = editor;
    editor.content.innerHTML = input.value || '';

    if (input.placeholder) {
        editor.content.setAttribute('data-placeholder', input.placeholder);
    }

    dispatchFieldEvent(input, MODULE_ID, 'after-init', {
        richText: editor,
    });

    return () => {
        container.innerHTML = '';
        delete input.richText;
    };
}

export const richTextModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return ctx.target instanceof HTMLElement && hasRichTextField(ctx.target);
    },
    setup: async(ctx) => {
        const options = (ctx.options || {}) as RichTextOptions;
        const fields = getModuleFieldContainers(ctx);
        const cleanups = fields.map((field) => {
            const container = field.querySelector(CONTAINER_SELECTOR);
            const input = field.querySelector(INPUT_SELECTOR);

            if (!(container instanceof HTMLElement) || !(input instanceof HTMLTextAreaElement)) {
                return () => {};
            }

            return initRichTextField(container, input as RichTextInput, options);
        });

        await ctx.emit('formie:module:rich-text:init', {
            count: cleanups.length,
        });

        return {
            destroy: () => {
                cleanups.forEach((cleanup) => {
                    cleanup();
                });

                void ctx.emit('formie:module:rich-text:destroy', {});
            },
        };
    },
};
