import{r as e}from"./rolldown-runtime-hePW80VL.js";import"./Spinner-yuRF9Eet.js";import{T as t,w as n}from"./dndkit-Tbq_EQgB.js";import{F as r,N as i}from"./utils-DF6t9GV_.js";import{c as a,f as o,i as s,l as c,p as l}from"./lit-C7H9X-yg.js";import{It as u,Lt as d,Rt as f}from"./render-Dvc3MHQR-Byeexk_P.js";var p=e(t(),1),m=n(),h=l`
    @layer pk-component {
        :host {
            display: block;
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-sm);
            line-height: var(--pk-line-height);
        }

        :host([disabled]) {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: var(--pk-checkbox-select-gap, 0);
        }

        .options--horizontal {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            gap: var(--pk-checkbox-select-gap, 0);
        }

        ::slotted(pk-checkbox),
        pk-checkbox {
            display: block;
        }

        .options--horizontal pk-checkbox.all-option {
            width: 100%;
        }
    }
`,g={fromAttribute(e){if(!e)return[];try{let t=JSON.parse(e);return Array.isArray(t)?t.filter(e=>!!(e&&typeof e==`object`&&`value`in e)).map(e=>({label:String(e.label??e.value),value:String(e.value)})):[]}catch{return[]}},toAttribute(e){return JSON.stringify(e??[])}},_={fromAttribute(e){if(e==null||e===``)return[];if(e===`*`)return`*`;try{let t=JSON.parse(e);return t===`*`?`*`:Array.isArray(t)?t.map(String):[]}catch{return[]}},toAttribute(e){return e===`*`?`*`:JSON.stringify(e??[])}},v=class extends u{constructor(...e){super(...e),this.options=[],this.value=[],this.showAllOption=!1,this.allLabel=`All`,this.disabled=!1,this.orientation=`vertical`,this.ariaLabel=null,this.optionElements=[],this.allOptionElement=null,this.handleAllChange=e=>{e.stopPropagation(),this.value=e.detail.checked?`*`:[],this.dispatchValueChange()},this.handleItemChange=(e,t)=>{if(t.stopPropagation(),this.isAllSelected)return;let n=t.detail.checked,r=this.selectedValues;this.value=n?[...r,e]:r.filter(t=>t!==e),this.dispatchValueChange()}}static{this.styles=h}connectedCallback(){this.hasAttribute(`role`)||this.setAttribute(`role`,`group`),super.connectedCallback()}updated(e){if(e.has(`options`)||e.has(`showAllOption`)){this.rebuildOptionElements();return}(e.has(`value`)||e.has(`disabled`))&&this.updateOptionStates()}firstUpdated(){this.rebuildOptionElements()}focus(e){this.optionElements.find(e=>!e.disabled)?.focus(e)}get isAllSelected(){return this.value===`*`}get selectedValues(){return this.isAllSelected?this.options.map(e=>e.value):Array.isArray(this.value)?this.value:[]}dispatchValueChange(){let e=this.isAllSelected?`*`:[...this.selectedValues];this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:e},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}rebuildOptionElements(){let e=this.shadowRoot?.querySelector(`.options`);if(e){for(let e of this.optionElements)e.remove();if(this.optionElements=[],this.allOptionElement=null,this.showAllOption){let t=document.createElement(`pk-checkbox`);t.classList.add(`all-option`),t.append(this.allLabel),t.addEventListener(`pk-change`,this.handleAllChange),e.append(t),this.allOptionElement=t,this.optionElements.push(t)}for(let t of this.options){let n=document.createElement(`pk-checkbox`);n.checkboxValue=t.value,n.append(t.label),n.addEventListener(`pk-change`,e=>{this.handleItemChange(t.value,e)}),e.append(n),this.optionElements.push(n)}this.updateOptionStates()}}updateOptionStates(){this.allOptionElement&&(this.allOptionElement.checked=this.isAllSelected,this.allOptionElement.disabled=this.disabled);for(let e of this.options){let t=this.optionElements.find(t=>t!==this.allOptionElement&&t.checkboxValue===e.value);t&&(t.checked=this.isAllSelected||this.selectedValues.includes(e.value),t.disabled=this.disabled||this.isAllSelected)}}render(){return o`
            <div
                part="base"
                class=${s({options:!0,"options--horizontal":this.orientation===`horizontal`})}
            ></div>
        `}};d([c({attribute:`options`,converter:g})],v.prototype,`options`,void 0),d([c({attribute:`value`,converter:_})],v.prototype,`value`,void 0),d([c({type:Boolean,attribute:`show-all-option`})],v.prototype,`showAllOption`,void 0),d([c({attribute:`all-label`})],v.prototype,`allLabel`,void 0),d([c({type:Boolean,reflect:!0})],v.prototype,`disabled`,void 0),d([c({reflect:!0})],v.prototype,`orientation`,void 0),d([c({attribute:`aria-label`})],v.prototype,`ariaLabel`,void 0),d([a()],v.prototype,`optionElements`,void 0),v=d([f(`pk-checkbox-select`)],v);var y=r({tagName:`pk-checkbox-select`,elementClass:v,react:p.default,events:{onPkChange:`pk-change`,onNativeChange:`change`}}),b=(0,p.forwardRef)(function({disabled:e,onChange:t,onPkChange:n,...r},a){let o=(0,p.useCallback)(e=>{if(n?.(e),!t)return;let r=e.detail;r&&`value`in r&&t(r.value)},[t,n]);return(0,m.jsx)(y,{ref:a,...r,...i([`disabled`],{disabled:e}),...t||n?{onPkChange:o}:{}})});b.displayName=`CheckboxSelect`;export{b as t};