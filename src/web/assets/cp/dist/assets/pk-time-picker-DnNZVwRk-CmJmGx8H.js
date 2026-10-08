import{C as e,O as t,S as n,b as r,k as i,x as a}from"./utils-D_lXaCpY.js";import{a as o,c as s,i as c,n as l,o as u,r as d,s as f}from"./pk-state-panel-BTo2xiug-40Dks_zl.js";import{a as p,f as ee,o as te}from"./Field-D1GCLJtF.js";import{c as ne,i as m,l as h,n as re,r as g,s as _,t as v}from"./has-slot-DJv86HKx-D2K9y9lH.js";import{a as y,c as b,d as x,f as S,i as C,l as w,o as ie,p as T,s as E}from"./lit-C7H9X-yg.js";import{u as D}from"./pk-alert-BQAb4lJc-KYGPWJEN.js";import{n as ae,s as oe}from"./Input-BZ2ppwXm.js";var se=/^(\d{4})-(\d{2})-(\d{2})$/;function O(e){if(e==null||e===``)return null;if(e instanceof Date)return A(e);if(typeof e!=`string`)return null;let t=se.exec(e.trim());if(!t)return null;let n=Number(t[1]),r=Number(t[2]),i=Number(t[3]);if(r<1||r>12||i<1||i>31)return null;let a=new Date(n,r-1,i);return a.getFullYear()!==n||a.getMonth()!==r-1||a.getDate()!==i?null:a}function k(e){return!e||Number.isNaN(e.getTime())?``:`${String(e.getFullYear()).padStart(4,`0`)}-${String(e.getMonth()+1).padStart(2,`0`)}-${String(e.getDate()).padStart(2,`0`)}`}function A(e){return e==null?null:e instanceof Date?Number.isNaN(e.getTime())?null:new Date(e.getFullYear(),e.getMonth(),e.getDate()):O(String(e))}function j(e){if(!e)return{from:null,to:null};let t=e.split(`/`);if(t.length===1)return{from:O(t[0]),to:null};let n=O(t[0]),r=O(t[1]);return!n||!r||n.getTime()<=r.getTime()?{from:n,to:r}:{from:r,to:n}}function ce(e){if(!e)return``;let{from:t,to:n}=e;return!t&&!n?``:t&&!n?k(t):!t&&n?k(n):`${k(t)}/${k(n)}`}function M(e){if(!e)return[];let t=new Set,n=[];for(let r of e.split(`,`)){let e=O(r.trim());if(!e)continue;let i=k(e);t.has(i)||(t.add(i),n.push(e))}return n.sort((e,t)=>e.getTime()-t.getTime()),n}function le(e){let t=new Set;for(let n of e){let e=k(n??null);e&&t.add(e)}return[...t].sort().join(`,`)}function ue(e,t){if(!k(t))return e??``;let n=M(e);return le(n.some(e=>N(e,t))?n.filter(e=>!N(e,t)):[...n,t])}function N(e,t){return!e||!t?!1:e.getFullYear()===t.getFullYear()&&e.getMonth()===t.getMonth()&&e.getDate()===t.getDate()}function P(e,t){return e.getFullYear()===t.getFullYear()&&e.getMonth()===t.getMonth()}function F(e,t){return new Date(e.getFullYear(),e.getMonth(),e.getDate()+t)}function I(e,t){let n=new Date(e.getFullYear(),e.getMonth()+t,1),r=de(n.getFullYear(),n.getMonth());return new Date(n.getFullYear(),n.getMonth(),Math.min(e.getDate(),r))}function L(e,t){return I(e,t*12)}function de(e,t){return new Date(e,t+1,0).getDate()}function R(e){return new Date(e.getFullYear(),e.getMonth(),1)}function z(){let e=new Date;return new Date(e.getFullYear(),e.getMonth(),e.getDate())}function B(e,t){let n=e.getTime()-t.getTime();return Math.round(n/864e5)}function fe(e){let t=new Date(e.getFullYear(),e.getMonth(),e.getDate()),n=(t.getDay()+6)%7;t.setDate(t.getDate()-n+3);let r=new Date(t.getFullYear(),0,4),i=(r.getDay()+6)%7;return r.setDate(r.getDate()-i+3),1+Math.round((t.getTime()-r.getTime())/6048e5)}function V(e,t){return new Intl.DateTimeFormat(t||void 0,{year:`numeric`,month:`short`,day:`numeric`}).format(e)}function pe(e){let{min:t,max:n,disabledDates:r=[],disabledDaysOfWeek:i=[],disablePast:a=!1,disableFuture:o=!1,today:s,isDateDisabled:c}=e,l=t?.getTime()??-1/0,u=n?.getTime()??1/0,d=s.getTime(),f=new Set(i),p=new Set(r.map(e=>e.getTime()));return function(e){let t=e.getTime();return!!(t<l||t>u||a&&t<d||o&&t>d||f.size&&f.has(e.getDay())||p.size&&p.has(t)||c?.(e))}}function me(e){if(e==null||e===``)return[];let t=Array.isArray(e)?e:e.split(/\s+/),n=[];for(let e of t){if(e instanceof Date){Number.isNaN(e.getTime())||n.push(new Date(e.getFullYear(),e.getMonth(),e.getDate()));continue}let t=O(String(e).trim());t&&n.push(t)}return n}var H={sun:0,mon:1,tue:2,wed:3,thu:4,fri:5,sat:6};function he(e){if(e==null||e===``)return[];let t=String(e).toLowerCase().split(/\s+/).filter(Boolean),n=new Set;for(let e of t)e in H&&n.add(H[e]);return[...n]}function U(e,t,n){let r=e.getTime()<=t.getTime()?e:t,i=e.getTime()<=t.getTime()?t:e,a=new Date(r.getFullYear(),r.getMonth(),r.getDate());for(;a.getTime()<=i.getTime();){if(!n(a))return!1;a.setDate(a.getDate()+1)}return!0}var ge=new Set(`US.CA.MX.BR.JP.PH.IL.AU.NZ.ZA.CO.VE.PE.EC.GT.HN.NI.SV.CR.PA.DO.PR.JM.TT.BS.BB.BZ.BO.BM.TW.HK.MO.SG.TH.ET.KE`.split(`.`)),_e=new Set([`SA`,`AE`,`QA`,`KW`,`BH`,`OM`,`YE`,`JO`,`SY`,`IQ`,`EG`,`SD`,`DZ`,`LY`]),ve=new Set([`SA`,`AE`,`QA`,`KW`,`BH`,`OM`,`YE`,`JO`,`EG`,`SD`,`DZ`,`LY`,`SY`,`IQ`,`IL`]);function ye(e){try{return new Intl.Locale(e).maximize().region??null}catch{return null}}function be(e){let t=ye(e),n=1;t&&ge.has(t)?n=7:t&&_e.has(t)&&(n=6);let r=t&&ve.has(t)?[5,6]:[6,7];return{firstDay:n,weekend:r}}function W(e){try{let t=new Intl.Locale(e),n=typeof t.getWeekInfo==`function`?t.getWeekInfo():t.weekInfo;if(n&&typeof n.firstDay==`number`&&Array.isArray(n.weekend))return{firstDay:n.firstDay,weekend:n.weekend}}catch{}return be(e)}function G(e){return e===7?0:e}function xe(e){return e.map(G)}function K(e,t){return e===`auto`?G(W(t).firstDay):{sun:0,mon:1,tue:2,wed:3,thu:4,fri:5,sat:6}[e]}var Se=T`
    ${D}
    @layer pk-component {
        :host {
            display: inline-block;
            width: fit-content;
            max-width: 100%;
            color: var(--pk-color-gray-900);
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: 1;
            --pk-date-cell-size: 1.75rem;
            --pk-date-cell-radius: 100%;
            --pk-date-gap: 0.25rem;
            --pk-date-column-min: var(--pk-date-cell-size);
            --pk-date-column-gap: 0;
        }

        :host([weekday-format='short']) {
            --pk-date-column-min: 2.125rem;
            --pk-date-column-gap: 0.125rem;
        }

        :host([weekday-format='long']) {
            --pk-date-column-min: 3.375rem;
            --pk-date-column-gap: 0.125rem;
        }

        :host([weekday-format='long']) .weekday {
            font-size: 0.7rem;
        }

        :host([size='xs']) {
            --pk-date-cell-size: 1.5rem;
            font-size: 11px;
        }

        :host([size='sm']) {
            --pk-date-cell-size: 1.625rem;
            font-size: 12px;
        }

        :host([size='lg']) {
            --pk-date-cell-size: 2rem;
            font-size: 14px;
        }

        :host([size='xl']) {
            --pk-date-cell-size: 2.25rem;
            font-size: 15px;
        }

        :host([disabled]) {
            opacity: 0.5;
            pointer-events: none;
        }

        .base {
            display: flex;
            flex-direction: column;
            gap: var(--pk-date-gap);
            width: fit-content;
            padding: 0.5rem;
            border: var(--pk-calendar-border, var(--pk-input-border));
            border-radius: var(--pk-radius-md);
            background: var(--pk-calendar-background, var(--pk-color-white));
        }

        :host(:not([bordered])) {
            --pk-calendar-border: 0;
            --pk-calendar-background: transparent;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.25rem;
            min-height: var(--pk-date-cell-size);
            padding-inline: 0.125rem;
        }

        .title {
            flex: 1;
            margin: 0;
            padding: 0.25rem 0.5rem;
            border: 0;
            border-radius: var(--pk-radius-sm);
            background: transparent;
            color: var(--pk-color-gray-900);
            font: inherit;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.2;
            text-align: center;
            cursor: pointer;
            user-select: none;
        }

        .title:hover:not(:disabled) {
            background: var(--pk-color-slate-100);
        }

        .nav-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: var(--pk-date-cell-size);
            height: var(--pk-date-cell-size);
            margin: 0;
            padding: 0.25rem;
            border: 0;
            border-radius: var(--pk-radius-sm);
            background: transparent;
            color: var(--pk-color-gray-600);
            cursor: pointer;
        }

        .nav-button:hover:not(:disabled) {
            background: var(--pk-color-slate-100);
            color: var(--pk-color-gray-900);
        }

        .nav-button:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        .nav-button .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 12px;
            height: 12px;
            flex-shrink: 0;
        }

        .nav-button .icon svg {
            display: block;
            width: 12px;
            height: 12px;
        }

        .months {
            display: flex;
            gap: 1rem;
        }

        .month {
            display: flex;
            flex-direction: column;
            width: fit-content;
            min-width: calc(var(--pk-date-column-min) * 7 + var(--pk-date-column-gap) * 6);
        }

        :host([data-week-numbers]) .month {
            min-width: calc(
                var(--pk-date-cell-size) + var(--pk-date-column-gap) + var(--pk-date-column-min) * 7 + var(--pk-date-column-gap) * 6
            );
        }

        .month-label {
            margin-bottom: 0.5rem;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            color: var(--pk-color-gray-700);
        }

        .weekdays,
        .week {
            display: grid;
            grid-template-columns: repeat(7, minmax(var(--pk-date-column-min), 1fr));
            column-gap: var(--pk-date-column-gap);
            align-items: center;
            width: 100%;
        }

        :host([data-week-numbers]) .weekdays,
        :host([data-week-numbers]) .week {
            grid-template-columns: var(--pk-date-cell-size) repeat(7, minmax(var(--pk-date-column-min), 1fr));
        }

        .grid {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
            margin-top: 0.2rem;
        }

        .weeknumber-header,
        .weeknumber {
            display: flex;
            align-items: center;
            justify-content: center;
            height: var(--pk-date-cell-size);
            color: var(--pk-color-gray-500);
            font-size: 0.8rem;
            font-weight: 400;
            user-select: none;
        }

        .weekday {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: var(--pk-date-column-min);
            height: var(--pk-date-cell-size);
            padding-inline: 0.125rem;
            color: var(--pk-color-gray-500);
            font-size: 0.8rem;
            font-weight: 400;
            line-height: 1.1;
            text-align: center;
            white-space: nowrap;
            user-select: none;
        }

        .day,
        .day.is-placeholder {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            justify-self: center;
            width: var(--pk-date-cell-size);
            min-width: var(--pk-date-cell-size);
            max-width: var(--pk-date-cell-size);
            height: var(--pk-date-cell-size);
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: var(--pk-color-gray-900);
            font: inherit;
            font-size: 13px;
            font-weight: 400;
            line-height: 1;
            cursor: pointer;
        }

        .day.is-range-start,
        .day.is-range-end,
        .day.is-range-inner,
        .day.is-range-preview {
            justify-self: stretch;
            width: 100%;
            min-width: 0;
            max-width: none;
        }

        .day.is-range-start.is-range-end {
            justify-self: center;
            width: var(--pk-date-cell-size);
            min-width: var(--pk-date-cell-size);
            max-width: var(--pk-date-cell-size);
        }

        .day:focus-visible {
            outline: 2px solid var(--pk-color-blue-500);
            outline-offset: 1px;
            z-index: 1;
        }

        .day.is-outside {
            opacity: 0.6;
        }

        .day.is-disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Day grid — circular today ring (fixed cell size, not column width) */
        .day.is-today:not(.is-range-start):not(.is-range-end):not(.is-range-inner)::after {
            content: '';
            position: absolute;
            inset: 0;
            border: 1px solid var(--pk-color-blue-500);
            border-radius: var(--pk-date-cell-radius);
            pointer-events: none;
        }

        .day.is-selected:not(.is-range-start):not(.is-range-end):not(.is-range-inner) {
            background: var(--pk-color-gray-200);
            border-radius: var(--pk-date-cell-radius);
            color: var(--pk-color-gray-900);
            font-weight: 400;
        }

        .day.is-range-start,
        .day.is-range-end {
            background: var(--pk-color-gray-200);
            color: var(--pk-color-gray-900);
            font-weight: 400;
        }

        .day.is-range-start.is-range-end {
            border-radius: var(--pk-date-cell-radius);
        }

        .day.is-range-start:not(.is-range-end) {
            border-radius: var(--pk-date-cell-radius) 0 0 var(--pk-date-cell-radius);
        }

        .day.is-range-end:not(.is-range-start) {
            border-radius: 0 var(--pk-date-cell-radius) var(--pk-date-cell-radius) 0;
        }

        .day.is-range-inner {
            background: var(--pk-color-gray-200);
            border-radius: 0;
        }

        .day.is-range-preview:not(.is-range-start):not(.is-range-end) {
            background: var(--pk-color-gray-200);
            opacity: 0.7;
        }

        .day.is-placeholder {
            visibility: hidden;
            pointer-events: none;
        }

        .live-region {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        slot[name='footer']::slotted(*) {
            display: block;
            padding-top: 0.25rem;
        }

        .view-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(var(--pk-date-cell-size), 1fr));
            gap: 0.25rem;
            width: 100%;
            min-width: calc(var(--pk-date-column-min) * 7 + var(--pk-date-column-gap) * 6);
        }

        .view-row {
            display: contents;
        }

        .view-cell {
            display: contents;
        }

        .view-item {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: var(--pk-date-cell-size);
            margin: 0;
            padding: 0.375rem 0.5rem;
            border: 0;
            border-radius: var(--pk-radius-sm);
            background: transparent;
            color: var(--pk-color-gray-900);
            font: inherit;
            font-size: 13px;
            cursor: pointer;
        }

        .view-item.is-selected {
            background: var(--pk-color-gray-200);
            font-weight: 500;
        }

        /* Month/year grid — rectangular today outline */
        .view-item.is-today:not(.is-selected) {
            box-shadow: inset 0 0 0 1px var(--pk-color-blue-500);
            border-radius: var(--pk-radius-sm);
        }

        .view-item.is-disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .view-item:focus-visible {
            outline: 2px solid var(--pk-color-blue-500);
            outline-offset: 1px;
        }
    }
`,Ce=h(_.chevronLeft),we=h(_.chevronRight),q=class extends re{constructor(...e){super(...e),this.hasSlotController=new v(this,`footer`,`previous-icon`,`next-icon`),this.mode=`single`,this.size=`default`,this.value=``,this.min=``,this.max=``,this.today=``,this.view=`days`,this.months=1,this.pageBy=`months`,this.focusedDate=``,this.firstDayOfWeek=`auto`,this.withOutsideDays=!0,this.withWeekNumbers=!1,this.weekdayFormat=`narrow`,this.disabled=!1,this.readonly=!1,this.bordered=!0,this.disabledDatesRaw=``,this.disabledDaysOfWeek=``,this.disablePast=!1,this.disableFuture=!1,this.minRange=0,this.maxRange=0,this.locale=``,this.viewAnchor=R(z()),this.rangeAnchor=null,this.hoverDate=null,this.liveAnnouncement=``,this.focusedMonth=null,this.focusedYear=null,this.daySlotNames=[],this.handlePrevious=()=>{if(this.view===`days`){this.viewAnchor=I(this.viewAnchor,-this.pageStep);return}if(this.view===`months`){this.viewAnchor=L(this.viewAnchor,-1);return}this.viewAnchor=L(this.viewAnchor,-12)},this.handleNext=()=>{if(this.view===`days`){this.viewAnchor=I(this.viewAnchor,this.pageStep);return}if(this.view===`months`){this.viewAnchor=L(this.viewAnchor,1);return}this.viewAnchor=L(this.viewAnchor,12)},this.handleTitleClick=()=>{if(!this.disabled){if(this.view===`days`){this.setView(`months`),this.focusedMonth=this.resolvedFocusedDate.getMonth();return}this.view===`months`&&(this.setView(`years`),this.focusedYear=this.resolvedFocusedDate.getFullYear())}},this.handleGridMouseLeave=()=>{this.hoverDate=null}}static{this.styles=Se}connectedCallback(){super.connectedCallback(),this.syncViewAnchor(),this.syncCustomStates(),this.updateDaySlots(),this.childrenObserver=new MutationObserver(()=>this.updateDaySlots()),this.childrenObserver.observe(this,{childList:!0,attributes:!0,attributeFilter:[`slot`]})}disconnectedCallback(){this.childrenObserver?.disconnect(),super.disconnectedCallback()}willUpdate(e){(e.has(`value`)||e.has(`focusedDate`)||e.has(`mode`))&&this.syncViewAnchor(),(e.has(`disabled`)||e.has(`readonly`)||e.has(`mode`)||e.has(`withWeekNumbers`))&&this.syncCustomStates(),e.has(`view`)&&this.emitViewChange(),super.willUpdate(e)}updateDaySlots(){let e=[...this.children].map(e=>e.getAttribute(`slot`)).filter(e=>!!e?.startsWith(`day-`));e.join(`,`)!==this.daySlotNames.join(`,`)&&(this.daySlotNames=e)}syncCustomStates(){this.toggleAttribute(`data-range`,this.mode===`range`),this.toggleAttribute(`data-multiple`,this.mode===`multiple`),this.toggleAttribute(`data-week-numbers`,this.withWeekNumbers)}get resolvedLocale(){return this.locale||this.lang||document.documentElement.lang||`en`}get resolvedToday(){return O(this.today)??z()}get primarySelectedDate(){return this.mode===`single`?O(this.value):this.mode===`multiple`?M(this.value)[0]??null:j(this.value).from}get resolvedFocusedDate(){return O(this.focusedDate)??A(this.primarySelectedDate)??this.resolvedToday}get isDisabledMatcher(){return pe({min:O(this.min),max:O(this.max),disabledDates:me(this.disabledDatesRaw),disabledDaysOfWeek:he(this.disabledDaysOfWeek),disablePast:this.disablePast,disableFuture:this.disableFuture,today:this.resolvedToday,isDateDisabled:this.isDateDisabled})}get weekendDays(){return new Set(xe(W(this.resolvedLocale).weekend))}get pageStep(){return this.pageBy===`single`?1:this.months}get visibleMonthAnchors(){let e=[this.viewAnchor];return this.months===2&&e.push(R(I(this.viewAnchor,1))),e}syncViewAnchor(){let e=this.resolvedFocusedDate;this.visibleMonthAnchors.some(t=>P(e,t))||(this.viewAnchor=R(e))}get valueAsDate(){return this.mode===`single`?O(this.value):null}get valueAsRange(){return j(this.value)}get valueAsDates(){return this.mode===`multiple`?M(this.value):[]}focus(e){let t=this.view===`days`?`.day.is-roving`:(this.view,`.view-item.is-roving`);this.renderRoot.querySelector(t)?.focus(e)}goToDate(e){let t=A(e);t&&(this.viewAnchor=R(t),this.focusedDate=k(t),this.view=`days`)}goToToday(){this.goToDate(this.resolvedToday)}clear(){this.disabled||this.readonly||(this.value=``,this.rangeAnchor=null,this.hoverDate=null,this.emitInput(),this.emitChange())}emitInput(){this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0}))}emitChange(){this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}emitFocusDay(e){this.dispatchEvent(new CustomEvent(`pk-focus-day`,{detail:{date:e},bubbles:!0,composed:!0}))}emitViewChange(){this.dispatchEvent(new CustomEvent(`pk-view-change`,{detail:{view:this.view,date:this.resolvedFocusedDate},bubbles:!0,composed:!0}))}announce(e){this.liveAnnouncement=`${e}\u200B`}setView(e){this.view!==e&&(this.view=e)}handleDayClick(e,t){if(this.disabled||this.readonly||t)return;if(this.mode===`single`){this.value=k(e),this.focusedDate=k(e),this.emitInput(),this.emitChange(),this.announce(V(e,this.resolvedLocale));return}if(this.mode===`multiple`){let t=M(this.value).some(t=>N(t,e));this.value=ue(this.value,e),this.focusedDate=k(e),this.emitInput(),this.emitChange(),this.announce(`${t?`Removed`:`Added`} ${V(e,this.resolvedLocale)}`);return}if(!this.rangeAnchor){this.rangeAnchor=e,this.value=k(e),this.focusedDate=k(e),this.emitInput();return}let n=this.rangeAnchor,r=e,i=n.getTime()<=r.getTime()?{from:n,to:r}:{from:r,to:n};if(this.minRange>0&&B(i.to,i.from)+1<this.minRange){this.announce(`Range must be at least ${this.minRange} days`);return}if(this.maxRange>0&&B(i.to,i.from)+1>this.maxRange){this.announce(`Range must be at most ${this.maxRange} days`);return}this.value=ce(i),this.focusedDate=k(e),this.rangeAnchor=null,this.hoverDate=null,this.emitInput(),this.emitChange(),this.announce(`${V(i.from,this.resolvedLocale)} – ${V(i.to,this.resolvedLocale)}`)}handleDayHover(e){this.mode===`range`&&this.rangeAnchor&&(this.hoverDate=e,this.emitFocusDay(e))}handleMonthPick(e){if(this.disabled||this.readonly)return;let t=new Date(this.viewAnchor.getFullYear(),e,1);this.isMonthFullyDisabled(t)||(this.viewAnchor=t,this.focusedDate=k(t),this.setView(`days`))}handleYearPick(e){if(this.disabled||this.readonly)return;let t=new Date(e,this.viewAnchor.getMonth(),1);this.isYearFullyDisabled(e)||(this.viewAnchor=t,this.focusedDate=k(t),this.setView(`months`),this.focusedMonth=t.getMonth())}handleDayKeyDown(e,t,n){if(n)return;let r=null;switch(e.key){case`ArrowLeft`:r=F(t,-1);break;case`ArrowRight`:r=F(t,1);break;case`ArrowUp`:r=F(t,-7);break;case`ArrowDown`:r=F(t,7);break;case`PageUp`:r=I(t,-1);break;case`PageDown`:r=I(t,1);break;case`Home`:r=R(t);break;case`End`:r=new Date(t.getFullYear(),t.getMonth()+1,0);break;case`Enter`:case` `:e.preventDefault(),this.handleDayClick(t,n);return;case`Escape`:this.rangeAnchor&&(e.preventDefault(),this.rangeAnchor=null,this.hoverDate=null,this.requestUpdate());return;default:return}e.preventDefault(),r&&(this.focusedDate=k(r),this.visibleMonthAnchors.some(e=>P(r,e))||(this.viewAnchor=R(r)),this.emitFocusDay(r),this.requestUpdate(),queueMicrotask(()=>this.focus()))}isMonthFullyDisabled(e){return U(e,new Date(e.getFullYear(),e.getMonth()+1,0),this.isDisabledMatcher)}isYearFullyDisabled(e){return U(new Date(e,0,1),new Date(e,11,31),this.isDisabledMatcher)}formatWeekdayLabel(e){return this.weekdayFormat===`narrow`?new Intl.DateTimeFormat(this.resolvedLocale,{weekday:`short`}).format(e).slice(0,2):new Intl.DateTimeFormat(this.resolvedLocale,{weekday:this.weekdayFormat}).format(e)}buildWeekdayLabels(){let e=K(this.firstDayOfWeek,this.resolvedLocale),t=[];for(let n=0;n<7;n+=1){let r=(e+n)%7,i=new Date(2024,0,r===0?7:r);t.push(this.formatWeekdayLabel(i))}return t}buildMonthDays(e){let t=K(this.firstDayOfWeek,this.resolvedLocale),n=R(e),r=F(n,-((n.getDay()-t+7)%7)),i=[];for(let e=0;e<42;e+=1)i.push(F(r,e));return i}computeDayState(e,t){let n=this.isDisabledMatcher(e),r=j(this.value),i=this.mode===`single`&&N(O(this.value),e),a=this.mode===`multiple`&&M(this.value).some(t=>N(t,e)),o=this.mode===`range`&&N(r.from,e),s=this.mode===`range`&&N(r.to,e),c=this.mode===`range`&&r.from&&r.to&&e.getTime()>r.from.getTime()&&e.getTime()<r.to.getTime(),l=!1;if(this.mode===`range`&&this.rangeAnchor&&this.hoverDate){let t=this.rangeAnchor,n=this.hoverDate,r=t.getTime()<=n.getTime()?t:n,i=t.getTime()<=n.getTime()?n:t;l=e.getTime()>=r.getTime()&&e.getTime()<=i.getTime()}return{date:e,monthAnchor:t,outside:!P(e,t),today:N(e,this.resolvedToday),weekend:this.weekendDays.has(e.getDay()),disabled:n,selected:i||a||o||s,rangeStart:o,rangeEnd:s,rangeInner:!!c,rangePreview:l,roving:N(e,this.resolvedFocusedDate)}}renderDayContent(e){let t=`day-${k(e.date)}`;if(this.daySlotNames.includes(t))return S`<slot name=${t}></slot>`;let n=this.dayContent?.(e.date);return n?ie(n):S`<span part="day-label">${e.date.getDate()}</span>`}renderDay(e){if(!this.withOutsideDays&&e.outside)return S`<span part="day-placeholder" class="day is-placeholder" aria-hidden="true"></span>`;let t=V(e.date,this.resolvedLocale);return S`
            <button
                type="button"
                part="day"
                class=${C({day:!0,"is-outside":e.outside,"is-today":e.today,"is-weekend":e.weekend,"is-disabled":e.disabled,"is-selected":e.selected,"is-range-start":e.rangeStart,"is-range-end":e.rangeEnd,"is-range-inner":e.rangeInner,"is-range-preview":e.rangePreview,"is-roving":e.roving})}
                tabindex=${e.roving?`0`:`-1`}
                ?disabled=${e.disabled}
                aria-label=${t}
                aria-selected=${e.selected?`true`:`false`}
                aria-current=${e.today?`date`:x}
                @click=${()=>this.handleDayClick(e.date,e.disabled)}
                @mouseenter=${()=>this.handleDayHover(e.date)}
                @keydown=${t=>this.handleDayKeyDown(t,e.date,e.disabled)}
            >
                ${this.renderDayContent(e)}
            </button>
        `}getMonthWeeks(e){let t=this.buildMonthDays(e),n=[];for(let e=0;e<t.length;e+=7)n.push(t.slice(e,e+7));return this.withOutsideDays?n:n.filter(t=>t.some(t=>P(t,e)))}renderMonth(e,t=!1){let n=this.buildWeekdayLabels(),r=new Intl.DateTimeFormat(this.resolvedLocale,{month:`long`,year:`numeric`}).format(e),i=this.getMonthWeeks(e);return S`
            <div part="month" class="month">
                ${t?S`<div part="month-label" class="month-label">${r}</div>`:x}
                <div part="weekdays" class="weekdays" role="row">
                    ${this.withWeekNumbers?S`<span part="weeknumbers" class="weeknumber-header" role="columnheader">#</span>`:x}
                    ${n.map(e=>S`
                        <span part="weekday" class="weekday" role="columnheader">${e}</span>
                    `)}
                </div>
                <div
                    part="grid"
                    class="grid"
                    role="grid"
                    aria-label=${r}
                    @mouseleave=${this.handleGridMouseLeave}
                >
                    ${i.map(t=>S`
                        <div part="week" class="week" role="row">
                            ${this.withWeekNumbers?S`<span part="weeknumber" class="weeknumber" role="gridcell">${fe(t[0])}</span>`:x}
                            ${t.map(t=>this.renderDay(this.computeDayState(t,e)))}
                        </div>
                    `)}
                </div>
            </div>
        `}renderViewRows(e){let t=[];for(let n=0;n<e.length;n+=3)t.push(S`
                <div part="view-row" class="view-row" role="row">
                    ${e.slice(n,n+3)}
                </div>
            `);return t}renderMonthsView(){let e=this.viewAnchor.getFullYear(),t=new Intl.DateTimeFormat(this.resolvedLocale,{month:`long`}),n=this.primarySelectedDate?.getMonth(),r=this.focusedMonth??this.resolvedFocusedDate.getMonth(),i=[];for(let a=0;a<12;a+=1){let o=new Date(e,a,1),s=this.isMonthFullyDisabled(o),c=n===a,l=this.resolvedToday.getFullYear()===e&&this.resolvedToday.getMonth()===a,u=r===a;i.push(S`
                <div part="view-cell" class="view-cell" role="gridcell">
                    <button
                        type="button"
                        part="view-item ${l?`view-item-today`:``} ${c?`view-item-selected`:``} ${s?`view-item-disabled`:``}"
                        class=${C({"view-item":!0,"is-today":l,"is-selected":c,"is-disabled":s,"is-roving":u})}
                        tabindex=${u?`0`:`-1`}
                        ?disabled=${s}
                        @click=${()=>this.handleMonthPick(a)}
                    >
                        ${t.format(o)}
                    </button>
                </div>
            `)}return S`
            <div part="view-grid" class="view-grid" role="grid">
                ${this.renderViewRows(i)}
            </div>
        `}renderYearsView(){let e=this.viewAnchor.getFullYear(),t=Math.floor(e/12)*12,n=this.primarySelectedDate?.getFullYear(),r=this.focusedYear??this.resolvedFocusedDate.getFullYear(),i=[];for(let e=0;e<12;e+=1){let a=t+e,o=this.isYearFullyDisabled(a),s=n===a,c=this.resolvedToday.getFullYear()===a,l=r===a;i.push(S`
                <div part="view-cell" class="view-cell" role="gridcell">
                    <button
                        type="button"
                        part="view-item ${c?`view-item-today`:``} ${s?`view-item-selected`:``} ${o?`view-item-disabled`:``}"
                        class=${C({"view-item":!0,"is-today":c,"is-selected":s,"is-disabled":o,"is-roving":l})}
                        tabindex=${l?`0`:`-1`}
                        ?disabled=${o}
                        @click=${()=>this.handleYearPick(a)}
                    >
                        ${a}
                    </button>
                </div>
            `)}return S`
            <div part="view-grid" class="view-grid" role="grid">
                ${this.renderViewRows(i)}
            </div>
        `}renderHeaderTitle(){if(this.view===`months`)return String(this.viewAnchor.getFullYear());if(this.view===`years`){let e=this.viewAnchor.getFullYear(),t=Math.floor(e/12)*12;return`${t} – ${t+11}`}return this.months===2?`${new Intl.DateTimeFormat(this.resolvedLocale,{month:`long`,year:`numeric`}).format(this.viewAnchor)} – ${new Intl.DateTimeFormat(this.resolvedLocale,{month:`long`,year:`numeric`}).format(I(this.viewAnchor,1))}`:new Intl.DateTimeFormat(this.resolvedLocale,{month:`long`,year:`numeric`}).format(this.viewAnchor)}render(){let e=this.view===`days`&&this.months===2;return S`
            <div part="base" class="base">
                <div part="header" class="header">
                    <button
                        type="button"
                        part="previous"
                        class="nav-button"
                        aria-label="Previous"
                        ?disabled=${this.disabled}
                        @click=${this.handlePrevious}
                    >
                        <slot name="previous-icon">
                            <span class="icon" aria-hidden="true">${y(Ce)}</span>
                        </slot>
                    </button>
                    <button
                        type="button"
                        part="title"
                        class="title"
                        ?disabled=${this.disabled}
                        @click=${this.view===`years`?void 0:this.handleTitleClick}
                    >
                        ${this.renderHeaderTitle()}
                    </button>
                    <button
                        type="button"
                        part="next"
                        class="nav-button"
                        aria-label="Next"
                        ?disabled=${this.disabled}
                        @click=${this.handleNext}
                    >
                        <slot name="next-icon">
                            <span class="icon" aria-hidden="true">${y(we)}</span>
                        </slot>
                    </button>
                </div>

                <div part="months" class="months">
                    ${this.view===`days`?this.visibleMonthAnchors.map(t=>this.renderMonth(t,e)):this.view===`months`?this.renderMonthsView():this.renderYearsView()}
                </div>

                ${this.hasSlotController.test(`footer`)?S`<div part="footer"><slot name="footer"></slot></div>`:x}

                <div class="live-region" aria-live="polite" aria-atomic="true">
                    ${this.liveAnnouncement}
                </div>
            </div>
        `}};g([w({reflect:!0})],q.prototype,`mode`,void 0),g([w({reflect:!0})],q.prototype,`size`,void 0),g([w({reflect:!0})],q.prototype,`value`,void 0),g([w({reflect:!0})],q.prototype,`min`,void 0),g([w({reflect:!0})],q.prototype,`max`,void 0),g([w({reflect:!0})],q.prototype,`today`,void 0),g([w({reflect:!0})],q.prototype,`view`,void 0),g([w({type:Number,reflect:!0})],q.prototype,`months`,void 0),g([w({attribute:`page-by`,reflect:!0})],q.prototype,`pageBy`,void 0),g([w({attribute:`focused-date`,reflect:!0})],q.prototype,`focusedDate`,void 0),g([w({attribute:`first-day-of-week`,reflect:!0})],q.prototype,`firstDayOfWeek`,void 0),g([w({attribute:`with-outside-days`,type:Boolean,reflect:!0})],q.prototype,`withOutsideDays`,void 0),g([w({attribute:`with-week-numbers`,type:Boolean,reflect:!0})],q.prototype,`withWeekNumbers`,void 0),g([w({attribute:`weekday-format`,reflect:!0})],q.prototype,`weekdayFormat`,void 0),g([w({type:Boolean,reflect:!0})],q.prototype,`disabled`,void 0),g([w({type:Boolean,reflect:!0})],q.prototype,`readonly`,void 0),g([w({type:Boolean,reflect:!0})],q.prototype,`bordered`,void 0),g([w({attribute:`disabled-dates`})],q.prototype,`disabledDatesRaw`,void 0),g([w({attribute:`disabled-days-of-week`,reflect:!0})],q.prototype,`disabledDaysOfWeek`,void 0),g([w({attribute:`disable-past`,type:Boolean,reflect:!0})],q.prototype,`disablePast`,void 0),g([w({attribute:`disable-future`,type:Boolean,reflect:!0})],q.prototype,`disableFuture`,void 0),g([w({attribute:`min-range`,type:Number})],q.prototype,`minRange`,void 0),g([w({attribute:`max-range`,type:Number})],q.prototype,`maxRange`,void 0),g([w({reflect:!0})],q.prototype,`locale`,void 0),g([w({attribute:!1})],q.prototype,`isDateDisabled`,void 0),g([w({attribute:!1})],q.prototype,`dayContent`,void 0),g([b()],q.prototype,`viewAnchor`,void 0),g([b()],q.prototype,`rangeAnchor`,void 0),g([b()],q.prototype,`hoverDate`,void 0),g([b()],q.prototype,`liveAnnouncement`,void 0),g([b()],q.prototype,`focusedMonth`,void 0),g([b()],q.prototype,`focusedYear`,void 0),g([b()],q.prototype,`daySlotNames`,void 0),q=g([m(`pk-calendar`)],q);function J(){return globalThis.Craft}function Y(){return J()?.locale||document.documentElement.lang||`en-US`}function X(e,t){let n=J()?.formatDate;if(typeof n==`function`)try{return n(e)}catch{}let r=t||Y();return new Intl.DateTimeFormat(r,{year:`numeric`,month:`numeric`,day:`numeric`}).format(e)}var Te=T`
    ${D}
    @layer pk-component {
        :host {
            display: inline-block;
            position: relative;
            width: fit-content;
            max-width: 100%;
            color: var(--pk-color-gray-700);
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: var(--pk-line-height);
            --pk-date-picker-height: 2.125rem;
            --pk-date-picker-min-width: 8.125rem;
            --pk-date-picker-padding-inline: 10px;
            --pk-date-picker-font-size: var(--pk-font-size-base);
        }

        :host([width='full']) {
            display: block;
            width: 100%;
        }

        :host([width='full']) .control {
            width: 100%;
        }

        .control {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.5rem;
            width: fit-content;
            min-width: var(--pk-date-picker-min-width);
            max-width: 100%;
            height: var(--pk-date-picker-height);
            min-height: var(--pk-date-picker-height);
            margin: 0;
            padding: 0 var(--pk-date-picker-padding-inline);
            border: 1px solid var(--pk-color-slate-400);
            border-radius: var(--pk-radius-lg);
            background: transparent;
            color: inherit;
            font: inherit;
            font-size: var(--pk-date-picker-font-size);
            font-weight: 400;
            line-height: 1.2;
            cursor: default;
            outline: none;
            box-sizing: border-box;
            transition: background-color 0.12s ease, border-color 0.12s ease, box-shadow 0.12s ease;
        }

        .control[data-popup-open] {
            background: var(--pk-color-slate-150);
            border-color: var(--pk-color-slate-400);
            box-shadow: none;
        }

        :host(:not([disabled])) .control:hover:not(.is-disabled) {
            background: var(--pk-color-slate-50);
        }

        :host(:not([disabled])) .control[data-popup-open]:hover:not(.is-disabled),
        :host(:not([disabled])) .control:active:not(.is-disabled) {
            background: var(--pk-color-slate-150);
        }

        /* Outline-button focus — lighter than --pk-shadow-focus (see React DatePicker trigger). */
        :host(:not([invalid]):not(:state(user-invalid))) .control:focus-visible,
        :host(:not([invalid]):not(:state(user-invalid))[data-state='focus-visible']) .control {
            border-color: var(--pk-color-sky-600);
            box-shadow: var(--pk-input-focus-shadow);
        }

        :host(:not([invalid]):not(:state(user-invalid))) .control[data-popup-open]:focus-visible,
        :host(:not([invalid]):not(:state(user-invalid))[data-state='focus-visible']) .control[data-popup-open] {
            border-color: var(--pk-color-slate-400);
            box-shadow: none;
        }

        :host([invalid]) .control,
        :host(:state(user-invalid)) .control {
            border-color: var(--pk-color-rose-600);
        }

        :host([invalid]) .control:focus-visible,
        :host([invalid][data-state='focus-visible']) .control,
        :host(:state(user-invalid)) .control:focus-visible,
        :host(:state(user-invalid)[data-state='focus-visible']) .control {
            box-shadow: var(--pk-input-invalid-focus-shadow);
        }

        .control.is-disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .display-value {
            display: inline-flex;
            align-items: center;
            flex: 1;
            min-width: 0;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: left;
            color: inherit;
        }

        .display-value.is-placeholder {
            color: var(--pk-color-gray-400);
        }

        .calendar-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            line-height: 0;
            color: var(--pk-color-gray-400);
            pointer-events: none;
        }

        .calendar-icon .icon,
        .calendar-icon svg {
            display: block;
            width: 14px;
            height: 14px;
        }

        .icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-inline-start: auto;
            width: 1.25rem;
            height: 1.25rem;
            padding: 0;
            border: 0;
            border-radius: var(--pk-radius-sm);
            background: transparent;
            color: var(--pk-color-gray-500);
            cursor: pointer;
        }

        .icon-button:hover:not(:disabled) {
            color: var(--pk-color-gray-800);
            background: var(--pk-color-slate-100);
        }

        .icon-button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .icon-button .icon {
            width: 0.875rem;
            height: 0.875rem;
        }

        .panel {
            padding: 0;
            border: 0;
            border-radius: var(--pk-radius-md);
            background: var(--pk-color-white);
            box-shadow: var(--pk-shadow-popup);
        }

        .panel pk-calendar {
            display: block;
        }

        :host([size='xs']) {
            --pk-date-picker-height: 1.5rem;
            --pk-date-picker-padding-inline: 8px;
            --pk-date-picker-font-size: 11px;
        }

        :host([size='sm']) {
            --pk-date-picker-height: 1.625rem;
            --pk-date-picker-padding-inline: 9px;
            --pk-date-picker-font-size: 12px;
        }

        :host([size='lg']) {
            --pk-date-picker-height: 2.125rem;
            --pk-date-picker-padding-inline: 11px;
            --pk-date-picker-font-size: 14px;
        }

        :host([size='xl']) {
            --pk-date-picker-height: 2.375rem;
            --pk-date-picker-padding-inline: 12px;
            --pk-date-picker-font-size: 15px;
        }
    }
`,Ee=h(_.calendar),De=h(_.xmark),Z=class extends e{constructor(...e){super(...e),this.assumeInteractionOn=[`input`],this.hasSlotController=new v(this,`label`,`instructions`,`hint`,`start`,`end`,`footer`),this.controlId=t(`pk-date-picker`),this.open=!1,this.placement=`bottom`,this.sideOffset=4,this.size=`default`,this.mode=`single`,this.value=``,this.defaultValue=``,this.label=``,this.instructions=``,this.placeholder=``,this.withClear=!1,this.readonly=!1,this.invalid=!1,this.min=``,this.max=``,this.locale=``,this.disablePast=!1,this.disableFuture=!1,this.disabledDates=``,this.disabledDaysOfWeek=``,this.firstDayOfWeek=`auto`,this.withOutsideDays=!0,this.withWeekNumbers=!1,this.months=1,this.pageBy=`months`,this.minRange=0,this.maxRange=0,this.withLabel=!1,this.withInstructions=!1,this.ariaLabel=null,this.dismissRegistered=!1,this.daySlotNames=[],this.handleDocumentPointerDown=e=>{this.open&&u(this)&&(this.isPointerInside(e)||this.closePanel(`light-dismiss`))},this.handleDocumentKeyDown=e=>{this.open&&e.key===`Escape`&&(e.preventDefault(),this.closePanel(`escape`))},this.handleControlClick=()=>{if(!this.disabled){if(this.open){this.closePanel(`api`);return}this.openPanel()}},this.handleControlKeyDown=e=>{if(!this.disabled){if(e.key===`ArrowDown`&&e.altKey){e.preventDefault(),this.openPanel(),queueMicrotask(()=>this.calendarElement?.focus());return}(e.key===`Enter`||e.key===` `)&&(e.preventDefault(),this.handleControlClick())}},this.handleClearClick=e=>{e.preventDefault(),e.stopPropagation(),this.clear()},this.handleCalendarChange=e=>{let t=e.target;this.value=t.value,this.emitValueChange(),this.mode===`single`&&t.value&&this.closePanel(`api`),this.mode===`range`&&j(t.value).from&&j(t.value).to&&this.closePanel(`api`)},this.handleCalendarInput=e=>{let t=e.target;this.value=t.value,this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0}))}}static{this.styles=[i,Te]}static get validators(){return[...super.validators,n(),a()]}connectedCallback(){this.instructions=r(this,this.instructions),this.hasAttribute(`with-hint`)&&(this.withInstructions=!0),super.connectedCallback(),this.toggleAttribute(`data-has-value`,!!this.value),this.setState(`blank`,!this.value),this.updateDaySlots(),this.childrenObserver=new MutationObserver(()=>this.updateDaySlots()),this.childrenObserver.observe(this,{childList:!0,attributes:!0,attributeFilter:[`slot`]})}disconnectedCallback(){this.childrenObserver?.disconnect(),this.closePanel(`api`),super.disconnectedCallback()}willUpdate(e){e.has(`value`)&&(this.value instanceof Date&&(this.value=k(A(this.value))),this.toggleAttribute(`data-has-value`,!!this.value),this.setState(`blank`,!this.value)),e.has(`open`)&&(this.setState(`open`,this.open),this.controlElement?.toggleAttribute(`data-popup-open`,this.open)),e.has(`mode`)&&(this.setState(`range`,this.mode===`range`),this.setState(`multiple`,this.mode===`multiple`)),super.willUpdate(e)}updateDaySlots(){let e=[...this.children].map(e=>e.getAttribute(`slot`)).filter(e=>!!e?.startsWith(`day-`));e.join(`,`)!==this.daySlotNames.join(`,`)&&(this.daySlotNames=e)}get valueString(){return this.value instanceof Date?k(A(this.value)):this.value}syncFormValue(){this.setValue(this.valueString||``)}resetToDefaultValue(){this.value=this.defaultValue}restoreFormState(e){typeof e==`string`&&(this.value=e)}get resolvedLocale(){return this.locale||this.lang||Y()}get displayText(){if(!this.value)return this.placeholder;if(this.mode===`multiple`){let e=M(this.valueString).length;return e===0?this.placeholder:`${e} date${e===1?``:`s`} selected`}if(this.mode===`range`){let e=j(this.valueString);return e.from&&e.to?`${X(e.from,this.resolvedLocale)} – ${X(e.to,this.resolvedLocale)}`:e.from?X(e.from,this.resolvedLocale):this.placeholder}let e=O(this.value);return e?X(e,this.resolvedLocale):this.placeholder}get valueAsDate(){return this.mode===`single`?O(this.value):null}get valueAsRange(){return j(this.valueString)}get valueAsDates(){return this.mode===`multiple`?M(this.valueString):[]}async show(){await this.openPanel()}async hide(){await this.closePanel(`api`)}clear(){this.disabled||this.readonly||!this.value||(this.value=``,this.dispatchEvent(new ae),this.emitValueChange(),this.controlElement?.focus())}emitValueChange(){this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:this.value},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}async openPanel(){this.disabled||this.open||this.dispatchEvent(new o)&&(this.open=!0,this.registerDismissHandlers(),await this.updateComplete,await oe(this.popupElement,this.placement),this.dispatchEvent(new d))}async closePanel(e=`unknown`){if(!this.open)return;let t=new c(e);this.dispatchEvent(t)&&(this.open=!1,this.unregisterDismissHandlers(),this.dispatchEvent(new l))}registerDismissHandlers(){this.dismissRegistered||=(f(this),document.addEventListener(`pointerdown`,this.handleDocumentPointerDown,!0),document.addEventListener(`keydown`,this.handleDocumentKeyDown,!0),!0)}unregisterDismissHandlers(){this.dismissRegistered&&=(s(this),document.removeEventListener(`pointerdown`,this.handleDocumentPointerDown,!0),document.removeEventListener(`keydown`,this.handleDocumentKeyDown,!0),!1)}isPointerInside(e){return ee(e,{host:this,panel:this.popupElement?.querySelector(`.panel`)??void 0})}renderClearButton(){return!this.withClear||!this.value||this.disabled?x:S`
            <button
                type="button"
                class="icon-button clear-button"
                part="clear-button"
                aria-label="Clear date"
                ?disabled=${this.disabled}
                @click=${this.handleClearClick}
            >
                <slot name="clear-icon">
                    <span class="icon" aria-hidden="true">${y(De)}</span>
                </slot>
            </button>
        `}renderCalendarIcon(){return S`
            <span class="calendar-icon" part="expand-icon" aria-hidden="true">
                <slot name="expand-icon">
                    <span class="icon">${y(Ee)}</span>
                </slot>
            </span>
        `}render(){let e=!!this.value,t=this.displayText,n=!e;return S`
            <div part="form-control" class="form-control">
                ${this.label||this.hasSlotController.test(`label`)?S`
                        <label part="label" class="label" for=${this.controlId}>
                            <slot name="label">${this.label}</slot>
                        </label>
                    `:x}

                <div part="form-control-input" class="form-control-input">
                    <input
                        class="value-input"
                        type="hidden"
                        .value=${this.value}
                        ?required=${this.required}
                    />

                    <div
                        part="base"
                        id=${this.controlId}
                        class=${C({control:!0,"is-disabled":this.disabled})}
                        role="combobox"
                        aria-expanded=${this.open?`true`:`false`}
                        aria-haspopup="dialog"
                        aria-label=${this.ariaLabel??x}
                        tabindex=${this.disabled?`-1`:`0`}
                        @click=${this.handleControlClick}
                        @keydown=${this.handleControlKeyDown}
                    >
                        ${this.hasSlotController.test(`start`)?S`<span part="start" class="control-start"><slot name="start"></slot></span>`:x}

                        ${this.renderCalendarIcon()}

                        <span
                            part="input"
                            class=${C({"display-value":!0,"is-placeholder":n})}
                        >
                            ${t}
                        </span>

                        ${this.renderClearButton()}

                        ${this.hasSlotController.test(`end`)?S`<span part="end" class="control-end"><slot name="end"></slot></span>`:x}
                    </div>

                    <pk-popup
                        .active=${this.open}
                        .anchor=${this.controlElement??``}
                        .placement=${this.placement}
                        .distance=${this.sideOffset}
                    >
                        <div part="popup" class="panel" role="dialog" aria-label="Choose date">
                            <pk-calendar
                                part="calendar"
                                .bordered=${!1}
                                .mode=${this.mode}
                                .value=${this.value}
                                .min=${this.min}
                                .max=${this.max}
                                .locale=${this.resolvedLocale}
                                .months=${this.months}
                                .pageBy=${this.pageBy}
                                .firstDayOfWeek=${this.firstDayOfWeek}
                                .withOutsideDays=${this.withOutsideDays}
                                .withWeekNumbers=${this.withWeekNumbers}
                                .weekdayFormat=${this.weekdayFormat??x}
                                .minRange=${this.minRange}
                                .maxRange=${this.maxRange}
                                .disablePast=${this.disablePast}
                                .disableFuture=${this.disableFuture}
                                .disabledDatesRaw=${this.disabledDates}
                                .disabledDaysOfWeek=${this.disabledDaysOfWeek}
                                .isDateDisabled=${this.isDateDisabled}
                                .dayContent=${this.dayContent}
                                .disabled=${this.disabled}
                                .readonly=${this.readonly}
                                @change=${this.handleCalendarChange}
                                @input=${this.handleCalendarInput}
                            >
                                ${this.daySlotNames.map(e=>S`
                                    <slot name=${e} slot=${e}></slot>
                                `)}
                                <slot name="footer" slot="footer"></slot>
                            </pk-calendar>
                        </div>
                    </pk-popup>
                </div>

                ${this.instructions||this.hasSlotController.test(`instructions`)||this.hasSlotController.test(`hint`)?S`
                        <div part="instructions" class="instructions">
                            <slot name="instructions">
                                <slot name="hint">${this.instructions}</slot>
                            </slot>
                        </div>
                    `:x}
            </div>
        `}};g([w({type:Boolean,reflect:!0})],Z.prototype,`open`,void 0),g([w({reflect:!0})],Z.prototype,`placement`,void 0),g([w({attribute:`side-offset`,type:Number})],Z.prototype,`sideOffset`,void 0),g([w({reflect:!0})],Z.prototype,`size`,void 0),g([w({reflect:!0})],Z.prototype,`mode`,void 0),g([w()],Z.prototype,`value`,void 0),g([w({attribute:`default-value`})],Z.prototype,`defaultValue`,void 0),g([w()],Z.prototype,`label`,void 0),g([w()],Z.prototype,`instructions`,void 0),g([w()],Z.prototype,`placeholder`,void 0),g([w({attribute:`with-clear`,type:Boolean})],Z.prototype,`withClear`,void 0),g([w({type:Boolean,reflect:!0})],Z.prototype,`readonly`,void 0),g([w({type:Boolean,reflect:!0})],Z.prototype,`invalid`,void 0),g([w({reflect:!0})],Z.prototype,`min`,void 0),g([w({reflect:!0})],Z.prototype,`max`,void 0),g([w({reflect:!0})],Z.prototype,`locale`,void 0),g([w({attribute:`disable-past`,type:Boolean,reflect:!0})],Z.prototype,`disablePast`,void 0),g([w({attribute:`disable-future`,type:Boolean,reflect:!0})],Z.prototype,`disableFuture`,void 0),g([w({attribute:`disabled-dates`})],Z.prototype,`disabledDates`,void 0),g([w({attribute:`disabled-days-of-week`,reflect:!0})],Z.prototype,`disabledDaysOfWeek`,void 0),g([w({attribute:`first-day-of-week`,reflect:!0})],Z.prototype,`firstDayOfWeek`,void 0),g([w({attribute:`with-outside-days`,type:Boolean,reflect:!0})],Z.prototype,`withOutsideDays`,void 0),g([w({attribute:`with-week-numbers`,type:Boolean,reflect:!0})],Z.prototype,`withWeekNumbers`,void 0),g([w({attribute:`weekday-format`,reflect:!0})],Z.prototype,`weekdayFormat`,void 0),g([w({type:Number,reflect:!0})],Z.prototype,`months`,void 0),g([w({attribute:`page-by`,reflect:!0})],Z.prototype,`pageBy`,void 0),g([w({attribute:`min-range`,type:Number})],Z.prototype,`minRange`,void 0),g([w({attribute:`max-range`,type:Number})],Z.prototype,`maxRange`,void 0),g([w({attribute:!1})],Z.prototype,`isDateDisabled`,void 0),g([w({attribute:!1})],Z.prototype,`dayContent`,void 0),g([w({attribute:`with-label`,type:Boolean})],Z.prototype,`withLabel`,void 0),g([w({attribute:`with-instructions`,type:Boolean})],Z.prototype,`withInstructions`,void 0),g([w({attribute:`aria-label`})],Z.prototype,`ariaLabel`,void 0),g([w({reflect:!0})],Z.prototype,`width`,void 0),g([E(`.value-input`)],Z.prototype,`input`,void 0),g([E(`pk-popup`)],Z.prototype,`popupElement`,void 0),g([E(`pk-calendar`)],Z.prototype,`calendarElement`,void 0),g([E(`.control`)],Z.prototype,`controlElement`,void 0),g([b()],Z.prototype,`daySlotNames`,void 0),Z=g([m(`pk-date-picker`)],Z);var Q=null;function Oe(){let e=globalThis.Craft?.timepicker;return{timeFormat:e?.timeFormat||`g:i A`,lang:{AM:e?.lang?.AM||`AM`,PM:e?.lang?.PM||`PM`},locale:e?.locale||document.documentElement.lang||`en-US`}}function ke(){if(Q)return Q;let e=[],{timeFormat:t,lang:n,locale:r}=Oe();for(let i=0;i<24;i+=1)for(let a=0;a<60;a+=30){let o=`${i.toString().padStart(2,`0`)}:${a.toString().padStart(2,`0`)}`,s;if(t===`g:i A`){let e=i;i===0?e=12:i>12&&(e=i-12);let t=i>=12?n.PM:n.AM;s=`${e}:${a.toString().padStart(2,`0`)} ${t}`}else s=t===`G:i`?o:new Date(`2000-01-01T${o}:00`).toLocaleTimeString(r,{hour:`numeric`,minute:`2-digit`,hour12:t.includes(`A`)});e.push({value:o,label:s})}return Q=e,e}var Ae=T`
    @layer pk-component {
        :host {
            display: inline-block;
            width: 8.125rem;
            min-width: 8.125rem;
            color: var(--pk-color-gray-700);
            --pk-select-trigger-border-width: 1px;
            --pk-select-item-min-height: 2.125rem;
            --pk-select-item-padding-block: 0;
            --pk-select-item-padding-inline: 10px;
            --pk-select-item-font-size: var(--pk-font-size-base);
        }

        .control {
            justify-content: flex-start;
            gap: 0.5rem;
            width: 100%;
            height: 2.125rem;
            min-height: 2.125rem;
            border-color: var(--pk-color-slate-400) !important;
            border-radius: var(--pk-radius-lg) !important;
            background: transparent !important;
            background-color: transparent !important;
            text-align: left;
            font-weight: 400;
            line-height: 1.2;
            cursor: default;
        }

        :host([open]) .control {
            background: var(--pk-color-slate-150, var(--pk-color-slate-100)) !important;
            background-color: var(--pk-color-slate-150, var(--pk-color-slate-100)) !important;
            border-color: var(--pk-color-slate-400) !important;
            box-shadow: none;
        }

        :host(:not([disabled])) .control:hover:not(:disabled) {
            background: var(--pk-color-slate-50) !important;
            background-color: var(--pk-color-slate-50) !important;
        }

        :host(:not([disabled])[open]) .control:hover:not(:disabled),
        :host(:not([disabled])) .control:active:not(:disabled) {
            background: var(--pk-color-slate-150, var(--pk-color-slate-100)) !important;
            background-color: var(--pk-color-slate-150, var(--pk-color-slate-100)) !important;
        }

        :host(:not([invalid]):not(:state(user-invalid))) .control:focus-visible,
        :host(:not([invalid]):not(:state(user-invalid))[data-state='focus-visible']) .control {
            border-color: var(--pk-color-sky-600) !important;
            box-shadow: var(--pk-input-focus-shadow);
        }

        :host(:not([invalid]):not(:state(user-invalid))[open]) .control:focus-visible,
        :host(:not([invalid]):not(:state(user-invalid))[open][data-state='focus-visible']) .control {
            border-color: var(--pk-color-slate-400) !important;
            box-shadow: none;
        }

        :host([invalid]) .control,
        :host(:state(user-invalid)) .control {
            border-color: var(--pk-color-rose-600) !important;
        }

        :host([invalid]) .control:focus-visible,
        :host([invalid][data-state='focus-visible']) .control,
        :host(:state(user-invalid)) .control:focus-visible,
        :host(:state(user-invalid)[data-state='focus-visible']) .control {
            box-shadow: var(--pk-input-invalid-focus-shadow);
        }

        .control-start {
            color: var(--pk-color-gray-400);
            pointer-events: none;
        }

        .control-start svg {
            display: block;
            width: 14px;
            height: 14px;
        }

        .value {
            flex: 1;
            line-height: 1.2;
            color: inherit;
        }

        .value.is-placeholder {
            color: var(--pk-color-gray-400);
        }

        .icon {
            margin-inline-start: auto;
            color: var(--pk-color-gray-600);
        }

        .panel {
            min-width: 8rem;
            max-height: 15rem;
        }

        /* Editable-table cells: flush fill — must live here so !important beats the
         * standalone trigger chrome (external ::part cannot override it). */
        :host(.cell-pk-control) {
            display: block;
            width: 100%;
            min-width: 0;
            height: 100%;
            --pk-select-trigger-border-width: 0;
            --pk-select-item-min-height: 100%;
            --pk-select-item-padding-block: 0;
        }

        :host(.cell-pk-control) .control {
            width: 100%;
            height: 100% !important;
            min-height: 100% !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            background-color: transparent !important;
        }

        :host(.cell-pk-control:not([disabled])) .control:hover:not(:disabled),
        :host(.cell-pk-control[open]) .control,
        :host(.cell-pk-control:not([disabled])[open]) .control:hover:not(:disabled),
        :host(.cell-pk-control:not([disabled])) .control:active:not(:disabled) {
            border-radius: 0 !important;
        }

        :host(.cell-pk-control:not([invalid]):not(:state(user-invalid))) .control:focus-visible,
        :host(.cell-pk-control:not([invalid]):not(:state(user-invalid))[data-state='focus-visible']) .control {
            border: 0 !important;
            box-shadow: inset 0 0 0 1px var(--pk-color-gray-200, #e5e7eb);
        }
    }
`,$=class extends p{constructor(...e){super(...e),this.optionsSeeded=!1}static{this.styles=[...te,Ae]}connectedCallback(){this.ensureTimeOptions(),this.ensureClockIcon(),super.connectedCallback()}ensureTimeOptions(){if(this.optionsSeeded||this.querySelector(`pk-option`)){this.optionsSeeded=!0;return}for(let e of ke()){let t=document.createElement(`pk-option`);t.value=e.value,t.textContent=e.label,this.append(t)}this.optionsSeeded=!0}ensureClockIcon(){if(this.querySelector(`[slot="start"]`))return;let e=ne(_.clock);e.setAttribute(`slot`,`start`),this.prepend(e)}};$=g([m(`pk-time-picker`)],$);export{Z as n,$ as t};