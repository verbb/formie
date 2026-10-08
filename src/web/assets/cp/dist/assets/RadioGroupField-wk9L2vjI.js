import{r as e}from"./rolldown-runtime-hePW80VL.js";import{F as t}from"./registerFormieOwnedSchemaFields-DuD11Iu1.js";import{T as n,w as r}from"./dndkit-Tbq_EQgB.js";import{A as i,C as a,M as o,b as s,k as c,x as l,y as u,z as d}from"./utils-D_lXaCpY.js";import{t as f}from"./Field-D1GCLJtF.js";import{i as p,n as m,r as h,t as g}from"./has-slot-DJv86HKx-D2K9y9lH.js";import{c as _,d as v,f as y,i as b,l as x,p as S,s as C}from"./lit-C7H9X-yg.js";import{n as w}from"./pk-change-BMLA71i0.js";var T=e(n(),1),E=r(),D=S`
    @layer pk-component {
        :host {
            display: inline-flex;
            vertical-align: middle;
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: var(--pk-line-height);
        }

        :host([disabled]) {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .item {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
            position: relative;
            margin: 0;
        }

        :host([disabled]) .item {
            cursor: not-allowed;
        }

        .item--with-label {
            gap: 0.5rem;
        }

        .control {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1rem;
            height: 1rem;
            border: 1px solid var(--pk-color-slate-400);
            border-radius: 9999px;
            background: var(--pk-color-white);
            transition: border-color 0.12s ease, box-shadow 0.12s ease;
        }

        .item:focus-visible .control,
        :host([data-focus-visible]) .control {
            border-color: var(--pk-color-sky-600);
            box-shadow: 0 0 0 1px var(--pk-color-sky-600), 0 0 4px 0 hsl(from var(--pk-color-sky-600) h s l / 0.7);
        }

        :host([invalid]) .control {
            border-color: var(--pk-color-rose-600);
        }

        :host([invalid]:focus-visible) .control,
        :host([invalid][data-focus-visible]) .control {
            box-shadow: 0 0 0 1px var(--pk-color-rose-600), 0 0 4px 0 hsl(from var(--pk-color-rose-600) h s l / 0.7);
        }

        :host([checked]) .control {
            background: var(--pk-color-gray-50);
            color: #1f2933;
        }

        .indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: currentcolor;
        }

        .indicator-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 9999px;
            background: currentcolor;
            transition: opacity 0.12s ease, transform 0.12s ease;
        }

        :host(:not([checked])) .indicator-dot {
            opacity: 0;
            transform: scale(0);
        }

        .input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
            opacity: 0;
            appearance: none;
        }

        .label {
            line-height: var(--pk-line-height);
            /* Match checkbox / form-control labels (gray-700). */
            color: var(--pk-color-gray-700);
        }

        .label.is-empty {
            display: none;
        }
    }
`,O=class extends m{constructor(...e){super(...e),this.value=``,this.checked=!1,this.disabled=!1,this.invalid=!1,this.required=!1,this.tabIndex=-1,this.ariaLabel=null,this.forceDisabled=!1,this.hasDefaultSlotContent=!1}static{this.styles=D}updated(e){this.input&&e.has(`checked`)&&(this.input.checked=this.checked),this.input&&e.has(`tabIndex`)&&(this.input.tabIndex=this.tabIndex)}focusControl(e){this.input.focus(e)}defaultSlotChanged(e){let t=e.target;this.hasDefaultSlotContent=t.assignedNodes({flatten:!0}).some(e=>e.nodeType===Node.TEXT_NODE?e.textContent?.trim():e.nodeType===Node.ELEMENT_NODE)}handleChange(e){e.stopPropagation();let t=e.target;this.disabled||this.forceDisabled||!t.checked||this.dispatchEvent(new CustomEvent(`pk-radio-select`,{detail:{value:this.value},bubbles:!0,composed:!0}))}render(){let e=this.disabled||this.forceDisabled;return y`
            <label
                part="base"
                class=${b({item:!0,"pk-radio-group__item":!0,"item--with-label":this.hasDefaultSlotContent})}
                data-state=${this.checked?`checked`:v}
                ?data-disabled=${e}
                aria-disabled=${e?`true`:v}
            >
                <span part="control" class="control pk-radio-group__control">
                    <span part="indicator" class="indicator pk-radio-group__indicator">
                        <span class="indicator-dot pk-radio-group__indicator-dot"></span>
                    </span>
                </span>
                <input
                    part="input"
                    class="input"
                    type="radio"
                    .checked=${this.checked}
                    ?disabled=${e}
                    ?required=${this.required}
                    value=${this.value}
                    tabindex=${this.tabIndex}
                    aria-label=${this.ariaLabel??v}
                    aria-invalid=${this.invalid?`true`:v}
                    aria-checked=${this.checked?`true`:`false`}
                    @change=${this.handleChange}
                />
                <span
                    class=${b({label:!0,"is-empty":!this.hasDefaultSlotContent})}
                >
                    <slot @slotchange=${this.defaultSlotChanged}></slot>
                </span>
            </label>
        `}};h([x()],O.prototype,`value`,void 0),h([x({type:Boolean,reflect:!0})],O.prototype,`checked`,void 0),h([x({type:Boolean,reflect:!0})],O.prototype,`disabled`,void 0),h([x({type:Boolean,reflect:!0})],O.prototype,`invalid`,void 0),h([x({type:Boolean,reflect:!0})],O.prototype,`required`,void 0),h([x({type:Number})],O.prototype,`tabIndex`,void 0),h([x({attribute:`aria-label`})],O.prototype,`ariaLabel`,void 0),h([x({type:Boolean,attribute:!1})],O.prototype,`forceDisabled`,void 0),h([C(`.input`)],O.prototype,`input`,void 0),h([_()],O.prototype,`hasDefaultSlotContent`,void 0),O=h([p(`pk-radio`)],O);var k=S`
    @layer pk-component {
        :host {
            display: block;
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: var(--pk-line-height);
        }

        .group {
            display: grid;
            gap: 0.375rem;
        }

        .group--horizontal {
            grid-auto-flow: column;
            grid-auto-columns: max-content;
            align-items: center;
        }
    }
`,A=class extends a{constructor(...e){super(...e),this.assumeInteractionOn=[`change`],this.hasSlotController=new g(this,`instructions`,`hint`,`label`),this._value=null,this.defaultValue=``,this.orientation=`vertical`,this.invalid=!1,this.label=``,this.instructions=``,this.ariaLabel=null,this.items=[],this.syncItems=()=>{this.items=this.getAllRadios(),this.applySelection()},this.handleRadioClick=e=>{let t=e.target.closest(`pk-radio`);if(!t||t.disabled||t.forceDisabled||this.disabled)return;let n=this.value;this.value=t.value,this.applySelection(),this.value!==n&&this.emitValueChange()},this.handleKeyDown=e=>{if(![`ArrowUp`,`ArrowDown`,`ArrowLeft`,`ArrowRight`,` `,`Home`,`End`].includes(e.key)||this.disabled)return;let t=this.getEnabledItems();if(t.length===0)return;e.preventDefault();let n=this.value,r=t.find(e=>e.checked)??t[0],i=t.indexOf(r);if(e.key!==` `){if(e.key===`Home`)i=0;else if(e.key===`End`)i=t.length-1;else{let n=[`ArrowUp`,`ArrowLeft`].includes(e.key)?-1:1;i+=n,i<0&&(i=t.length-1),i>=t.length&&(i=0)}}this.value=t[i].value,this.applySelection(),t[i].focusControl(),this.value!==n&&this.emitValueChange()}}static{this.shadowRootOptions={mode:`open`,delegatesFocus:!0}}static{this.styles=[c,k]}static get validators(){return[...super.validators,l()]}get value(){return this.valueHasChanged?this._value??``:this._value??this.defaultValue??``}set value(e){this._value=e==null?null:String(e),this.valueHasChanged=!0}syncFormValue(){this.setFormValue(this.value||null)}resetToDefaultValue(){this._value=null,this.applySelection()}restoreFormState(e){typeof e==`string`&&(this.value=e,this.applySelection())}get validationTarget(){return this.getAllRadios().find(e=>!e.disabled)??this.getAllRadios()[0]}connectedCallback(){this.instructions=s(this,this.instructions),super.connectedCallback(),this.addEventListener(`keydown`,this.handleKeyDown),this.addEventListener(`click`,this.handleRadioClick)}disconnectedCallback(){this.removeEventListener(`keydown`,this.handleKeyDown),this.removeEventListener(`click`,this.handleRadioClick),super.disconnectedCallback()}updated(e){(e.has(`value`)||e.has(`disabled`)||e.has(`invalid`)||e.has(`name`))&&this.applySelection(),super.updated(e)}formResetCallback(){this._value=null,super.formResetCallback(),this.applySelection()}focus(e){if(this.disabled)return;let t=this.getEnabledItems();(t.find(e=>e.checked)??t[0])?.focusControl(e)}getAllRadios(){return[...this.querySelectorAll(`pk-radio`)]}getEnabledItems(){return this.items.filter(e=>!e.disabled&&!this.disabled)}applySelection(){let e=this.getAllRadios();this.items=e;let t=this.getEnabledItems(),n=t.find(e=>e.value===this.value);for(let t of e){let e=t.value===this.value,n=t.hasAttribute(`disabled`)||t.disabled&&!t.forceDisabled;t.checked=e,t.disabled=this.disabled||n,t.invalid=this.invalid,t.required=this.required,t.forceDisabled=this.disabled}if(this.disabled){for(let t of e)t.tabIndex=-1;return}if(n)for(let e of t)e.tabIndex=e.checked?0:-1;else t.length>0&&t.forEach((e,t)=>{e.tabIndex=t===0?0:-1});for(let t of e.filter(e=>e.disabled))t.tabIndex=-1}emitValueChange(){this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:this.value},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}render(){let e=this.hasSlotController.test(`label`),t=u((e,t)=>this.hasSlotController.test(e,t),this.instructions),n=!!this.label||e;return y`
            <div part="form-control" class="form-control">
                ${n?y`
                        <div part="label" class="form-control__label" id="label">
                            <slot name="label">${this.label}</slot>
                        </div>
                    `:v}

                ${t?y`
                        <div part="instructions" class="form-control__instructions" id="instructions">
                            <slot name="instructions">${this.instructions}</slot>
                            <slot name="hint"></slot>
                        </div>
                    `:v}

                <div
                    part="radios"
                    class=${b({group:!0,"pk-radio-group":!0,"group--horizontal":this.orientation===`horizontal`,"pk-radio-group--horizontal":this.orientation===`horizontal`})}
                    role="radiogroup"
                    aria-labelledby=${n?`label`:v}
                    aria-label=${n?v:this.ariaLabel??v}
                    aria-describedby=${t?`instructions`:v}
                    aria-invalid=${this.invalid?`true`:v}
                    aria-required=${this.required?`true`:v}
                >
                    <slot @slotchange=${this.syncItems}></slot>
                </div>
            </div>
        `}};h([_()],A.prototype,`value`,null),h([x({attribute:`value`,reflect:!0})],A.prototype,`defaultValue`,void 0),h([x({reflect:!0})],A.prototype,`orientation`,void 0),h([x({type:Boolean,reflect:!0})],A.prototype,`invalid`,void 0),h([x()],A.prototype,`label`,void 0),h([x()],A.prototype,`instructions`,void 0),h([x({attribute:`aria-label`})],A.prototype,`ariaLabel`,void 0),h([C(`slot:not([name])`)],A.prototype,`defaultSlot`,void 0),h([_()],A.prototype,`items`,void 0),A=h([p(`pk-radio-group`)],A);var j=o({tagName:`pk-radio-group`,elementClass:A,react:T.default,events:{onPkChange:`pk-change`,onInput:`input`,onChange:`change`}}),M=o({tagName:`pk-radio`,elementClass:O,react:T.default,events:{onPkRadioSelect:`pk-radio-select`}}),N=(0,T.forwardRef)(function(e,t){let{disabled:n,invalid:r,required:a,...o}=e;return(0,E.jsx)(j,{ref:t,...o,...i([`disabled`,`invalid`,`required`],{disabled:n,invalid:r,required:a})})});N.displayName=`RadioGroup`;var P=(0,T.forwardRef)(function(e,t){let{disabled:n,invalid:r,required:a,checked:o,...s}=e;return(0,E.jsx)(M,{ref:t,...s,...i([`disabled`,`invalid`,`required`,`checked`],{disabled:n,invalid:r,required:a,checked:o})})});P.displayName=`Radio`;var F=e=>e==null?``:String(e);function I({options:e,value:t,onChange:n,onPkChange:r,...i}){let a=t=>{if(r?.(t),!n)return;let i=w(t),a=e.find(e=>F(e.value)===i);n(a?a.value:i)};return(0,E.jsx)(N,{...i,value:F(t),onPkChange:a,children:e.map(e=>(0,E.jsx)(P,{value:F(e.value),disabled:e.disabled,children:e.label},F(e.value)))})}var L=({form:e,field:n})=>{let{value:r,setValue:i,setTouched:a,errors:o}=t(e,n.name),s=(0,T.useSyncExternalStore)(e.store.subscribe.bind(e.store),()=>e.store.state.values,()=>e.store.state.values),c=(0,T.useMemo)(()=>{let t=typeof n._scopePath==`string`?n._scopePath:``,r=t?e?.getFieldValue?.(t):null,i=r&&typeof r==`object`?r:{},a=n._data&&typeof n._data==`object`?n._data:{};return{...s||{},...i,...a}},[n,e,s]),l=(0,T.useMemo)(()=>(Array.isArray(n.options)?n.options:[]).filter(e=>!e?.if||d(e.if,c)),[c,n.options]);return(0,T.useEffect)(()=>{if(r==null||r===``||l.some(e=>String(e?.value)===String(r)))return;let e=l.find(e=>e?.value!==void 0&&e?.disabled!==!0);i(e?e.value:``)},[l,i,r]),(0,E.jsx)(f,{name:n.name,label:n.label,instructions:n.instructions,warning:n.warning,required:n.required,errors:o,children:(0,E.jsx)(I,{name:n.name,value:r,options:l.map(e=>({value:e.value,label:e.label,disabled:e.disabled})),onChange:e=>{i(e),a()},disabled:n.disabled,"aria-label":n.label})})};export{L as RadioGroupField};