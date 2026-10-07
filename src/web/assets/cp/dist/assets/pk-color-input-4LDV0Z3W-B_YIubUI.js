import{C as e,S as t,x as n}from"./utils-BTUGQuuI.js";import{i as r,r as i}from"./has-slot-9zjTXvea-D2K9y9lH.js";import{c as a,d as o,f as s,i as c,l,p as u,s as d}from"./lit-C7H9X-yg.js";var f=u`
    @layer pk-component {
        :host {
            display: inline-block;
            position: relative;
            /* Former lg min-width — default now matches input default chrome. */
            min-width: 6.75rem;
            font-family: var(--pk-font-family);
            vertical-align: middle;
        }

        :host([size='xs']) {
            min-width: 5.5rem;
        }

        :host([size='sm']) {
            min-width: 6.125rem;
        }

        :host([size='lg']),
        :host([size='xl']) {
            min-width: 7.375rem;
        }

        :host([fit-cell]) {
            display: block;
            width: 100%;
            min-width: 0;
            max-width: 100%;
            height: 100%;
        }

        :host([fit-cell]) .root {
            display: block;
            width: 100%;
            height: 100%;
        }

        .root {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .swatch {
            position: absolute;
            top: 50%;
            left: 0.5rem;
            z-index: 2;
            width: 1.25rem;
            height: 1.25rem;
            transform: translateY(-50%);
            border-radius: var(--pk-radius-sm);
        }

        :host([size='xs']) .swatch {
            left: 0.375rem;
            width: 1rem;
            height: 1rem;
        }

        :host([size='sm']) .swatch {
            left: 0.375rem;
            width: 1.25rem;
            height: 1.25rem;
        }

        :host([size='lg']) .swatch,
        :host([size='xl']) .swatch {
            left: 0.5rem;
            width: 1.5rem;
            height: 1.5rem;
        }

        :host([fit-cell]) .swatch {
            left: 0.5rem;
            width: 1rem;
            height: 1rem;
        }

        .swatch-preview {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            box-shadow: inset 0 0 0 1px rgb(0 0 0 / 0.15);
        }

        .swatch-preview.is-transparent {
            background-color: #fff;
            background-image:
                linear-gradient(45deg, #d1d5db 25%, transparent 25%),
                linear-gradient(-45deg, #d1d5db 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #d1d5db 75%),
                linear-gradient(-45deg, transparent 75%, #d1d5db 75%);
            background-size: 8px 8px;
            background-position: 0 0, 0 4px, 4px -4px, -4px 0;
        }

        .swatch-picker {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            opacity: 0;
            cursor: pointer;
            appearance: none;
        }

        .swatch-picker:disabled {
            cursor: not-allowed;
        }

        .hash {
            position: absolute;
            top: 50%;
            left: 2.125rem;
            z-index: 1;
            transform: translateY(-50%);
            color: var(--pk-color-gray-300);
            font-family: var(--pk-font-family-mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace);
            font-size: var(--pk-font-size-mono, 0.9em);
            line-height: var(--pk-line-height-mono, 1.5);
            pointer-events: none;
            user-select: none;
        }

        :host([size='xs']) .hash {
            left: 1.625rem;
        }

        :host([size='sm']) .hash {
            left: 2rem;
        }

        :host([size='lg']) .hash,
        :host([size='xl']) .hash {
            left: 2.5rem;
        }

        :host([fit-cell]) .hash {
            left: 1.75rem;
        }

        .hex-input {
            display: block;
            width: 100%;
            /* Former lg — matches pk-input default chrome (~34px). */
            height: 2.125rem;
            margin: 0;
            padding-inline: 3rem 0.75rem;
            border: var(--pk-input-border);
            border-radius: var(--pk-input-border-radius);
            background: var(--pk-input-bg);
            color: var(--pk-color-gray-700);
            font-family: var(--pk-font-family-mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace);
            font-size: var(--pk-font-size-mono, 0.9em);
            line-height: var(--pk-line-height-mono, 1.5);
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.12s ease, box-shadow 0.12s ease;
        }

        :host([size='xs']) .hex-input {
            height: 1.625rem;
            padding-inline: 2.25rem 0.625rem;
        }

        :host([size='sm']) .hex-input {
            height: 1.875rem;
            padding-inline: 2.75rem 0.75rem;
        }

        :host([size='lg']) .hex-input,
        :host([size='xl']) .hex-input {
            height: 2.375rem;
            padding-inline: 3.25rem 0.875rem;
        }

        :host([fit-cell]) .hex-input {
            width: 100%;
            max-width: 100%;
            height: 100%;
            padding-inline: 2.25rem 0.5rem;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        .hex-input:focus,
        .hex-input:focus-visible {
            border-color: var(--pk-color-sky-600);
            box-shadow: var(--pk-input-focus-shadow);
        }

        :host([invalid]) .hex-input:focus,
        :host([invalid]) .hex-input:focus-visible {
            border-color: var(--pk-color-rose-600);
            box-shadow: var(--pk-input-invalid-focus-shadow);
        }

        :host([fit-cell]:not([invalid])) .hex-input:focus,
        :host([fit-cell]:not([invalid])) .hex-input:focus-visible {
            box-shadow: inset 0 0 0 1px var(--pk-color-gray-200);
        }

        :host([fit-cell][invalid]) .hex-input,
        :host([fit-cell][invalid]) .hex-input:focus,
        :host([fit-cell][invalid]) .hex-input:focus-visible {
            box-shadow: inset 0 0 0 1px var(--pk-color-rose-600);
        }

        .hex-input:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        :host([invalid]) .hex-input {
            border-color: var(--pk-color-rose-600);
        }

        :host([disabled]) .swatch {
            opacity: 0.5;
        }
    }
`,p=`#000000`;function m(e){return String(e||``).replace(/^#/,``).replace(/[^0-9a-fA-F]/g,``).slice(0,6).toLowerCase()}function h(e){return e.length===3||e.length===6}function g(e){return e.length===3?e.split(``).map(e=>`${e}${e}`).join(``):e}function _(e){return e.length===6?`#${e}`:e.length===3?`#${g(e)}`:p}var v=class extends e{constructor(...e){super(...e),this.assumeInteractionOn=[`blur`,`input`],this.size=`default`,this.fitCell=!1,this.readonly=!1,this.invalid=!1,this.value=``,this.defaultValue=``,this.ariaLabel=null,this.hexValue=``}static{this.styles=f}static get validators(){return[...super.validators,t(),n()]}connectedCallback(){super.connectedCallback(),this.syncHexFromValue()}willUpdate(e){e.has(`value`)&&this.syncHexFromValue(),super.willUpdate(e)}syncHexFromValue(){this.hexValue=m(this.value)}get validationTarget(){return this.input}syncFormValue(){let e=this.hexValue?`#${this.hexValue}`:``;this.setFormValue(e,e)}resetToDefaultValue(){this.value=this.defaultValue,this.hexValue=m(this.defaultValue)}restoreFormState(e){typeof e==`string`&&(this.value=e,this.hexValue=m(e))}emitChange(){let e=this.hexValue?`#${this.hexValue}`:``;this.value=e,this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:e},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}handleHexInput(e){if(this.disabled||this.readonly)return;let t=m(e.target.value);this.hexValue=t,this.emitChange()}handlePickerChange(e){if(this.disabled||this.readonly)return;let t=m(e.target.value);this.hexValue=t,this.emitChange()}render(){let e=_(this.hexValue),t=!h(this.hexValue);return s`
            <div class="root">
                <div part="swatch" class="swatch">
                    <div
                        class=${c({"swatch-preview":!0,"is-transparent":t})}
                        style=${t?o:`background-color: ${e}`}
                    ></div>
                    <input
                        part="picker"
                        class="swatch-picker"
                        type="color"
                        .value=${e}
                        ?disabled=${this.disabled||this.readonly}
                        aria-label="Color picker"
                        @input=${this.handlePickerChange}
                    />
                </div>
                <span class="hash" aria-hidden="true">#</span>
                <input
                    part="input"
                    class="hex-input"
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    maxlength="6"
                    .value=${this.hexValue}
                    ?disabled=${this.disabled}
                    ?readonly=${this.readonly}
                    ?required=${this.required}
                    aria-label=${this.ariaLabel??o}
                    aria-invalid=${this.invalid?`true`:o}
                    @input=${this.handleHexInput}
                />
            </div>
        `}};i([l({reflect:!0})],v.prototype,`size`,void 0),i([l({type:Boolean,reflect:!0,attribute:`fit-cell`})],v.prototype,`fitCell`,void 0),i([l({type:Boolean,reflect:!0})],v.prototype,`readonly`,void 0),i([l({type:Boolean,reflect:!0})],v.prototype,`invalid`,void 0),i([l()],v.prototype,`value`,void 0),i([l({attribute:`default-value`})],v.prototype,`defaultValue`,void 0),i([l({attribute:`aria-label`})],v.prototype,`ariaLabel`,void 0),i([d(`.hex-input`)],v.prototype,`input`,void 0),i([a()],v.prototype,`hexValue`,void 0),v=i([r(`pk-color-input`)],v);export{v as t};