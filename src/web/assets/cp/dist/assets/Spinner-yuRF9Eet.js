import{r as e}from"./rolldown-runtime-hePW80VL.js";import{a as t,c as n,d as r,i,n as a,r as o,t as s,u as c}from"./dist-C5F3WOie.js";import{T as l,w as u}from"./dndkit-Tbq_EQgB.js";import{A as d,C as f,E as p,F as m,N as h,O as ee,S as te,T as g,b as ne,j as re,k as _,v as ie,w as ae,x as v,y as oe}from"./utils-DF6t9GV_.js";import{a as se,r as y}from"./pk-status-BehQARDv-UCn-zswF.js";import{c as ce,d as le,f as ue,l as de,s as fe,u as pe}from"./Field-F5nY6ns6.js";import{c as me,d as he,h as ge,i as _e,m as ve,n as ye,p as be,r as xe,t as Se,u as Ce}from"./overlay-lifecycle-D0pkTQyI-BDCiftP5.js";import{a as b,c as x,d as S,f as C,i as w,l as T,p as E,s as D}from"./lit-C7H9X-yg.js";import{It as O,Lt as k,Rt as A,n as j}from"./render-Dvc3MHQR-Byeexk_P.js";import"./pk-dropdown-separator-BZcZUZgv.js";import{t as we}from"./pk-dialog-VMQqLW1f-BSujVpCq.js";var M=e=>!!e&&typeof e==`object`,Te=(e,t)=>!t||!e.length||e[e.length-1].type!==`variableTag`?e:[...e,{type:`text`,text:t}],N=(e,{trailingCursorText:t}={})=>Array.isArray(e)?Te(e.flatMap(e=>{if(!e)return[];if(Array.isArray(e))return N(e,{trailingCursorText:t});if(typeof e!=`object`)return[];if(!(`type`in e))return M(e)&&Array.isArray(e.content)?N(e.content,{trailingCursorText:t}):[];if(M(e)&&e.type===`text`&&typeof e.text==`string`){let t=e.text.replace(/[\u200B\u2060]/g,``);return t?[{...e,text:t}]:[]}if(M(e)&&Array.isArray(e.content)){let n=N(e.content,{trailingCursorText:t});return[{...e,content:n}]}return M(e)?[e]:[]}),t):[],P=(e,t={})=>{if(!e)return null;if(Array.isArray(e)){let n=N(e,t);return n.length?{type:`doc`,content:n}:null}if(M(e)){if(e.type===`doc`&&Array.isArray(e.content)){let n=N(e.content,t);return n.length?{...e,content:n}:null}if(Array.isArray(e.content)){let n=N(e.content,t);return n.length?{type:`doc`,content:n}:null}}if(typeof e==`string`)try{let n=JSON.parse(e);if(Array.isArray(n)){let e=N(n,t);return e.length?{type:`doc`,content:e}:null}if(M(n)){if(n.type===`doc`&&Array.isArray(n.content)){let e=N(n.content,t);return e.length?{...n,content:e}:null}if(Array.isArray(n.content)){let e=N(n.content,t);return e.length?{type:`doc`,content:e}:null}}}catch{return null}return null},Ee=e=>{let t=P(e);if(!t)return``;let n=e=>{if(!e||typeof e!=`object`)return!1;let t=e;return t.type===`text`&&typeof t.text==`string`&&t.text===``?!0:Array.isArray(t.content)?t.content.some(e=>n(e)):!1};return n(t)?`This field contains invalid rich-text content. The editor could not fully parse the stored document, so some content may not be shown until it is repaired and saved again.`:``},De=`transform=`;function Oe(e){let t=e.match(/^\{([^}]*)\}$/);if(!t)return{tokenWithoutDefault:e};let n=t[1]??``,r;if(n.includes(`|`)){let e=n.split(`|`);n=e.shift()??``,r=e.join(`|`).trim()||void 0}let i=n.split(`;`).map(e=>e.trim()).filter(Boolean),a=[],o,s,c={},l=!1;return i.forEach(e=>{if(e.startsWith(De)){o=decodeURIComponent(e.slice(10)).trim()||void 0,l=!0;return}if(e.includes(`=`)){let[t,...n]=e.split(`=`),r=(t??``).trim().toLowerCase();if(!r)return;let i=decodeURIComponent(n.join(`=`).trim());if(l){s||={},s[r]=i;return}c[r]=i,a.push(`${r}=${encodeURIComponent(i)}`);return}a.push(e)}),{tokenWithoutDefault:`{${a.join(`;`)}}`,defaultIfEmpty:r,transformerId:o,transformerParams:s,referenceParams:c}}function ke(e){return/^\{[a-zA-Z][a-zA-Z0-9_]*(?::[^}]*)?\}$/.test(e)}var Ae=/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;function je(e){return Ae.test(String(e||``).trim())}function Me(e){return e.replace(/([a-z0-9])([A-Z])/g,`$1 $2`).replace(/[_-]+/g,` `).trim().replace(/\s+/g,` `).replace(/\b\w/g,e=>e.toUpperCase())}function Ne(e){if(e.match(/^\{field:([^}]+)\}$/))return`Unknown field`;let[t=``,n=``,r=``]=e.replace(/^\{|\}$/g,``).split(`:`),i=String(r||n||t).split(`;`)[0]?.trim()??``;return je(i)||je(n)?`Unknown variable`:t&&Me(i)||`Unknown variable`}function Pe(e,t){let n=e.match(/^\{([^}]*)\}$/);if(!n)return e;let r=[n[1]],i=t.transformerId?.trim(),a=t.transformerParams&&typeof t.transformerParams==`object`?Object.entries(t.transformerParams):[];i&&(r.push(`${De}${encodeURIComponent(i)}`),a.forEach(([e,t])=>{let n=String(e??``).trim();if(!n||n===`transform`)return;let i=t==null?``:String(t);r.push(`${n}=${encodeURIComponent(i)}`)}));let o=r.filter(Boolean).join(`;`),s=t.defaultIfEmpty?.trim();return s?`{${o}|${s}}`:`{${o}}`}function Fe(e){let t=[],n=new Set;return e.forEach(e=>{let r=String(e?.value??``)||`__label:${String(e?.label??``)}`;n.has(r)||(n.add(r),t.push(e))}),t}function Ie(e){let t=[],n=e=>{e.forEach(e=>{t.push(e),Array.isArray(e.children)&&e.children.length>0&&n(e.children)})};return n(e),t}function Le(e,t=e,n={}){let r=n.label??t?.label??e?.label??``,i=n.value??t?.value??e?.value??``,a=n.defaultIfEmpty?.trim(),o={label:r,value:i,openOnInsert:n.openOnInsert??!1};return a&&(o.default=a),n.transformerId?.trim()&&(o.transformerId=n.transformerId.trim()),n.transformerParams&&typeof n.transformerParams==`object`&&(o.transformerParams=n.transformerParams),o}function Re(e){let t=Oe(e);return[t.tokenWithoutDefault,t.defaultIfEmpty]}function ze(e){let[t]=Re(e),n=t.match(/^\{([^}]*)\}$/);if(!n)return t;let r=n[1].split(`;`)[0]?.trim()??``;return r?`{${r}}`:t}function Be(e,t){return!e||!t?!1:e===t||ze(e)===ze(t)}function F(e,t){let{tokenWithoutDefault:n}=Oe(String(e||``));return t?.label?t.label:Ne(n)}function Ve(e,t){let n=null;for(let r of e){let e=Array.isArray(r.children)?r.children:[],i=String(r.value??``);if(i===t)return r;if(e.length){let r=Ve(e,t);if(r?.value===t)return r;r&&!n&&(n=r)}Be(t,i)&&(n||=r)}return n}function He(e,t,n){let{tokenWithoutDefault:r,defaultIfEmpty:i,transformerId:a,transformerParams:o}=Oe(e),s=r,c=Ve(t,r);if(c)return Le(c,c,{defaultIfEmpty:i,transformerId:a,transformerParams:o,label:F(s,c),value:s});let l=Ve(n,r);return l?Le(l,l,{defaultIfEmpty:i,transformerId:a,transformerParams:o,label:F(s,l),value:s}):ke(r)?{label:F(r,null),value:r,openOnInsert:!1,unresolved:!0,...i?{default:i}:{},...a?{transformerId:a}:{},...o?{transformerParams:o}:{}}:null}function Ue(e,t,n,r=`​`){if(!e)return null;let i=e.split(/({.*?})/).flatMap(e=>{if(e.includes(`{`)){let r=He(e,t,n);if(r)return[{type:`variableTag`,attrs:r}]}return e?[{type:`text`,text:e}]:[]});return r&&i.length&&i[i.length-1].type===`variableTag`&&i.push({type:`text`,text:r}),{type:`doc`,content:i}}function We(e){if(!e)return``;let t=Array.isArray(e)?e:[],n=``,r=e=>{e.forEach(e=>{if(!e||typeof e!=`object`)return;let t=e;if(t.type===`paragraph`&&Array.isArray(t.content))r(t.content);else if(t.type===`text`)n+=(t.text??``).replace(/[\u200B\u2060]/g,``);else if(t.type===`variableTag`){let e=t.attrs?.value??``,r=t.attrs?.default?.trim(),i=t.attrs?.transformerId?.trim(),a=t.attrs?.transformerParams;n+=Pe(e,{defaultIfEmpty:r,transformerId:i,transformerParams:a})}})};return r(t),n.replace(/[\r\n]+/g,` `)}function Ge(e){return Array.isArray(e)&&e.length>0}function Ke(e){if(!e)return[];if(Ge(e))return e;let t=[];return e.linkToEntry&&t.push({...e.linkToEntry,optionTitle:`Link to an entry`}),e.linkToAsset&&t.push({...e.linkToAsset,optionTitle:`Link to an asset`}),e.linkToCategory&&t.push({...e.linkToCategory,optionTitle:`Link to a category`}),t}function qe(e){if(e&&!Ge(e))return e.elementSiteId}function Je(e,t){return`${e.url||``}#${t}:${e.id}@${e.siteId}`}function Ye(e,t){return`${e}.${t}`}function Xe({config:e,elementSiteId:t,linkSelectorStorageKeyPrefix:n,getSelectedText:r,onSelect:i,host:a}){if(!n)throw Error(`Craft element links require "linkSelectorStorageKeyPrefix".`);a.openElementSelector(e.elementType,{storageKey:Ye(n,e.elementType),sources:e.sources,criteria:e.criteria,defaultSiteId:t,autoFocusSearchBox:!1,onSelect:t=>{if(!t?.length)return;let[n]=t;i({url:Je(n,e.refHandle),text:r()||n.label||``})},closeOtherModals:!1})}function Ze(e,t){let n={href:e};return t&&(n.target=`_blank`),n}function Qe(e){let{from:t,to:n}=e.state.selection;return e.state.doc.textBetween(t,n,` `)}function $e(e){return e.isActive(`link`)&&e.getAttributes(`link`).target===`_blank`}function et(e){let{href:t}=e.getAttributes(`link`),{state:n}=e,r=n.schema.marks.link,i=c(n.selection.$from,r),a=i?.from??n.selection.from,o=i?.to??n.selection.to,s=e.state.doc.textBetween(a,o,` `);return{from:a,to:o,href:t??``,text:s,openInNewTab:$e(e)}}function tt(e,t){let{url:n,text:r,openInNewTab:i,from:a,to:o}=t,s=e.chain().focus(),c=Ze(n,i),l={type:`text`,text:r.trim()||n,marks:[{type:`link`,attrs:c}]};if(typeof a==`number`&&typeof o==`number`&&a!==o){s.insertContentAt({from:a,to:o},[l]).run();return}let{from:u,to:d}=e.state.selection;if(e.state.doc.textBetween(u,d,` `)){s.extendMarkRange(`link`).setLink(c).run();return}s.insertContent([l]).run()}function nt(e){e.chain().focus().extendMarkRange(`link`).unsetLink().run()}var rt=[1,2,3,4,5,6];function I(e){return t(e)||rt.some(t=>e===`h${t}`)?!0:`bold.italic.underline.strikethrough.subscript.superscript.small-caps.font-family.font-size.text-color.line-height.unordered-list.ordered-list.blockquote.highlight.code.code-block.hr.line-break.align-left.align-center.align-right.align-justify.clear-format.undo.redo.link.table.variableTag`.split(`.`).includes(e)}function it(e,t){let n=i(t);if(n)return n.isActive?.(e)??!1;if(!I(t))return!1;let r=t.match(/^h([1-6])$/);if(r){let t=Number(r[1]);return e.isActive(`heading`,{level:t})}switch(t){case`bold`:return e.isActive(`bold`);case`italic`:return e.isActive(`italic`);case`underline`:return e.isActive(`underline`);case`strikethrough`:return e.isActive(`strike`);case`subscript`:return e.isActive(`subscript`);case`superscript`:return e.isActive(`superscript`);case`small-caps`:return e.isActive(`textStyle`,{fontVariantCaps:`small-caps`});case`unordered-list`:return e.isActive(`bulletList`);case`ordered-list`:return e.isActive(`orderedList`);case`blockquote`:return e.isActive(`blockquote`);case`highlight`:return e.isActive(`highlight`);case`code`:return e.isActive(`code`);case`code-block`:return e.isActive(`codeBlock`);case`align-left`:return e.isActive({textAlign:`left`});case`align-center`:return e.isActive({textAlign:`center`});case`align-right`:return e.isActive({textAlign:`right`});case`align-justify`:return e.isActive({textAlign:`justify`});case`link`:return e.isActive(`link`);case`variableTag`:return e.isActive(`variableTag`);default:return!1}}function at(e,t,n={}){let r=i(t);if(r)return r.run(e)!==!1;if(!I(t))return!1;let a=e.chain().focus(),o=t.match(/^h([1-6])$/);if(o){let e=Number(o[1]);return a.toggleHeading({level:e}).run()}switch(t){case`bold`:return a.toggleBold().run();case`italic`:return a.toggleItalic().run();case`underline`:return a.toggleUnderline().run();case`strikethrough`:return a.toggleStrike().run();case`subscript`:return a.toggleSubscript().run();case`superscript`:return a.toggleSuperscript().run();case`small-caps`:return a.toggleSmallCaps().run();case`unordered-list`:return a.toggleBulletList().run();case`ordered-list`:return a.toggleOrderedList().run();case`blockquote`:return a.toggleBlockquote().run();case`highlight`:return a.toggleHighlight().run();case`code`:return a.toggleCode().run();case`code-block`:return a.toggleCodeBlock().run();case`hr`:return a.setHorizontalRule().run();case`line-break`:return a.setHardBreak().run();case`align-left`:return a.setTextAlign(`left`).run();case`align-center`:return a.setTextAlign(`center`).run();case`align-right`:return a.setTextAlign(`right`).run();case`align-justify`:return a.setTextAlign(`justify`).run();case`clear-format`:return a.clearNodes().unsetAllMarks().run();case`undo`:return a.undo().run();case`redo`:return a.redo().run();case`table`:{let e=n.tableOptions??{};return a.insertTable({rows:e.rows??3,cols:e.cols??3,withHeaderRow:e.withHeaderRow??!0}).run()}case`link`:case`variableTag`:return!1;default:return!1}}var ot=[1,2,3,4],st={formatting:`paragraph`,headings:`heading`,lists:`unordered-list`,align:`align-left`},ct=new Set([`formatting`,`headings`,`lists`,`align`]);function lt(e){return e===`formatting`}function ut(e){return e===`headings`}function dt(e){return e.preset?st[e.preset]:ht(e)[0]??`bold`}var ft=new Set([`|`,`separator`]);function pt(e){return ft.has(e)}function mt(e){if(e.items?.length)return e.items.filter(e=>typeof e==`string`&&I(e));switch(e.preset){case`formatting`:return[...(e.headingLevels??ot).map(e=>`h${e}`),`blockquote`,`code-block`];case`headings`:return(e.headingLevels??ot).map(e=>`h${e}`);case`lists`:return[`unordered-list`,`ordered-list`];case`align`:return[`align-left`,`align-center`,`align-right`,`align-justify`];default:return[]}}function ht(e){return mt(e)}function gt(e){let t=[];return e.forEach(e=>{if(e.type===`button`){t.push(e.name);return}e.type===`group`&&t.push(...ht(e.group))}),t}function _t(e,t){return gt(e).includes(t)}function vt(e){if(typeof e==`string`){let t=e.trim();return t?pt(t)?t:t===`paragraph`?`paragraph`:L(t):null}if(!e||typeof e!=`object`)return null;let t=e;return t.type===`separator`?{type:`separator`}:t.type===`item`&&typeof t.name==`string`?t.name===`paragraph`?`paragraph`:L(t.name):null}function yt(e){return e.map(e=>vt(e)).filter(e=>e!==null)}function bt(e){return e===`|`||e===`separator`||typeof e==`object`&&e.type===`separator`}function xt(e){return!bt(e)}function St(e){return e.flatMap(e=>bt(e)?[{type:`separator`}]:xt(e)?[{type:`item`,name:e}]:[])}function Ct(e){return Nt(e).filter(e=>e.type===`item`).map(e=>e.name)}function L(e){return I(e)?e:null}function wt(e){if(!Array.isArray(e))return;let t=e.map(e=>Number(e)).filter(e=>[1,2,3,4,5,6].includes(e));return t.length>0?t:void 0}function Tt(e){let t=typeof e.group==`object`&&e.group!==null?e.group:e,n=typeof t.preset==`string`?t.preset:void 0,r=typeof t.label==`string`?t.label:void 0,i=typeof t.icon==`string`?t.icon:void 0,a=i?L(i)??void 0:void 0,o=wt(t.headingLevels),s=Array.isArray(t.items)?yt(t.items):void 0;return!n&&!s?.length||n&&!ct.has(n)?null:{type:`group`,group:{...r?{label:r}:{},...a?{icon:a}:{},...n?{preset:n}:{},...s?.length?{items:s}:{},...o?{headingLevels:o}:{}}}}function Et(e){if(typeof e==`string`){let t=e.trim();if(!t)return null;if(pt(t))return{type:`separator`};let n=L(t);return n?{type:`button`,name:n}:null}if(!e||typeof e!=`object`)return null;let t=e;if(t.type===`separator`)return{type:`separator`};if(t.type===`button`&&typeof t.name==`string`){let e=L(t.name);return e?{type:`button`,name:e}:null}if(t.type===`group`||t.preset||t.items||t.group)return Tt(t);if(typeof t.button==`string`){let e=L(t.button);return e?{type:`button`,name:e}:null}return null}function Dt(e){return e.map(e=>Et(e)).filter(e=>e!==null)}function R(e){if(e==null||e===``)return[{type:`button`,name:`bold`},{type:`button`,name:`italic`}];if(Array.isArray(e)){let t=Dt(e);return t.length>0?t:[{type:`button`,name:`bold`},{type:`button`,name:`italic`}]}if(typeof e==`string`){let t=e.trim();if(!t)return[{type:`button`,name:`bold`},{type:`button`,name:`italic`}];if(t.startsWith(`[`))try{let e=JSON.parse(t);return R(Array.isArray(e)?e:null)}catch{return R(null)}return R(t.split(`,`).map(e=>e.trim()).filter(Boolean))}return R(null)}function Ot(e,t,n={}){return t===`paragraph`?e.chain().focus().setParagraph().run():at(e,t,n)}function z(e,t){return t===`paragraph`?e.isActive(`paragraph`)&&!e.isActive(`heading`):it(e,t)}function kt(e){for(let t of[1,2,3,4,5,6])if(e.isActive(`heading`,{level:t}))return t;return null}function At(e,t){let n=t.icon??dt(t),r=Ct(t);if(ut(t.preset)){let t=kt(e);if(t){let e=`h${t}`;return{activeName:e,label:`H${t}`,isActive:!0,icon:e}}return{activeName:null,label:``,isActive:!1,icon:`heading`}}if(lt(t.preset)){let t=kt(e);if(t){let e=`h${t}`;return{activeName:e,label:`H${t}`,isActive:!0,icon:e}}return z(e,`blockquote`)?{activeName:`blockquote`,label:``,isActive:!0,icon:`blockquote`}:z(e,`code-block`)?{activeName:`code-block`,label:``,isActive:!0,icon:`code-block`}:{activeName:`paragraph`,label:`Text`,isActive:z(e,`paragraph`),icon:`paragraph`}}let i=r.find(t=>z(e,t))??null;if(i){let e=i===`paragraph`?`paragraph`:I(i)?i:n;return{activeName:i===`paragraph`?`paragraph`:i,label:i===`paragraph`?`Text`:t.label??i,isActive:!0,icon:e}}return{activeName:null,label:t.label??``,isActive:!1,icon:n}}function jt(e){return e.headingLevels??ot}function Mt(e){return[{type:`item`,name:`paragraph`},{type:`separator`},...jt(e).map(e=>({type:`item`,name:`h${e}`})),{type:`separator`},{type:`item`,name:`blockquote`},{type:`item`,name:`code-block`}]}function Nt(e){return e.items?.length?St(e.items):lt(e.preset)?Mt(e):ht(e).map(e=>({type:`item`,name:e}))}var B=e=>e.target===e.currentTarget,V=e(l(),1),H=u(),Pt=[me(),te,E`
        @layer pk-component {
            :host {
                /* Flex column parents stretch cross-axis size — pin to content.
                   (inline-block + align-self; same class of fix as dialog / dropdown.) */
                display: inline-block;
                max-width: 100%;
                align-self: flex-start;
                flex: none;
                vertical-align: middle;
            }

            :host([data-pk-group-orientation]) {
                display: inline-flex;
                vertical-align: middle;
                flex: 0 0 auto;
                align-self: auto;
            }

            :host([data-pk-group-orientation]) ::slotted([slot='trigger']) {
                --pk-bg-start-start-radius: inherit;
                --pk-bg-start-end-radius: inherit;
                --pk-bg-end-start-radius: inherit;
                --pk-bg-end-end-radius: inherit;
            }

            :host([data-pk-group-orientation='horizontal'][data-pk-group-join]) {
                margin-inline-start: var(--pk-bg-horizontal-indent, 0);
            }

            :host([data-pk-group-orientation='vertical'][data-pk-group-join]) {
                margin-block-start: var(--pk-bg-vertical-indent, 0);
            }

            :host([data-pk-group-orientation='horizontal'][data-pk-group-join]:has([slot='trigger'][variant='outline'], [slot='trigger'][variant='dashed'])) {
                margin-inline-start: var(--pk-bg-horizontal-indent-outlined, 0);
            }

            :host([data-pk-group-orientation='vertical'][data-pk-group-join]:has([slot='trigger'][variant='outline'], [slot='trigger'][variant='dashed'])) {
                margin-block-start: var(--pk-bg-vertical-indent-outlined, 0);
            }

            /* Match pk-popup's arrow fill to the panel surface when with-arrow is on. */
            :host([with-arrow]) {
                --pk-popup-arrow-color: var(--pk-color-white);
                --pk-popup-arrow-size: 8px;
            }

            .panel {
                box-sizing: border-box;
                width: 18rem;
                padding: 1rem;
                border-radius: var(--pk-radius-md);
                background: var(--pk-color-white);
                box-shadow: var(--pk-shadow-popover);
                /* Craft CP body text (~gray-700), not gray-900. */
                color: var(--pk-color-gray-700);
            }

            /* Flush panels for command/menu chrome that owns its own inset (variable picker, etc.).
               Match kit v1 PopoverContent min-w 260px / max-w 360px: without min-width,
               width max-content shrinks to short labels and looks narrower than the old picker. */
            :host([flush]) .panel {
                width: max-content;
                min-width: var(--pk-popover-flush-min-width, 16.25rem);
                max-width: min(var(--pk-popover-flush-max-width, 22.5rem), 100vw - 1rem);
                padding: 0;
            }

            .panel[hidden] {
                display: none !important;
            }
        }
    `],U=class extends O{constructor(...e){super(...e),this.open=!1,this.placement=`bottom`,this.sideOffset=4,this.flush=!1,this.withArrow=!1,this.for=``,this.anchor=null,this.triggerElement=null,this.closing=!1,this.panelAnimated=!1,this.triggerId=_(`pk-popover-trigger`),this.dismissRegistered=!1,this.syncingOpenSideEffects=!1,this.exitAnimationPromise=null,this.handleToggleClick=e=>{e.preventDefault(),e.stopPropagation(),!this.closing&&(this.open=!this.open)},this.onDocumentPointerDown=e=>{this.isPointerInside(e)||this.closing||this.closePopover(`light-dismiss`)},this.onDocumentKeyDown=e=>{e.key===`Escape`&&be(this)&&!this.closing&&(e.preventDefault(),e.stopPropagation(),this.closePopover(`escape`))}}static{this.styles=Pt}get panelElement(){return this.popupElement?.getContentElement()??null}disconnectedCallback(){this.closePopover(`api`,!0),super.disconnectedCallback()}willUpdate(e){e.has(`open`)&&this.open===!1&&e.get(`open`)===!0&&!this.syncingOpenSideEffects&&!this.closing&&(this.closing=!0,this.panelAnimated=!1)}async updated(e){if(super.updated(e),!e.has(`open`)||this.syncingOpenSideEffects)return;let t=e.get(`open`);t!==this.open&&(t!==void 0||this.open!==!1)&&(this.open?await this.openPopover():await this.closePopover(`api`))}onTriggerSlotChange(e){let[t]=e.target.assignedElements({flatten:!0});this.unbindTrigger(this.triggerElement),this.triggerElement=t??null,this.bindTrigger(this.triggerElement)}bindTrigger(e){e&&(e.id||=this.triggerId,e.setAttribute(`aria-haspopup`,`dialog`),e.addEventListener(`click`,this.handleToggleClick),this.syncExpanded())}unbindTrigger(e){e?.removeEventListener(`click`,this.handleToggleClick)}async openPopover(){if(!this.getAnchor())return;if(this.exitAnimationPromise&&await this.exitAnimationPromise,this.dismissRegistered&&this.open){this.panelElement&&(this.panelElement.hidden=!1),this.syncExpanded();return}if(!this.dispatchEvent(new _e)){this.syncingOpenSideEffects=!0,this.open=!1,this.syncingOpenSideEffects=!1;return}this.syncingOpenSideEffects=!0,this.open=!0,this.syncingOpenSideEffects=!1,this.closing=!1,this.panelAnimated=!1,this.panelElement&&(this.panelElement.hidden=!1,g(this.panelElement,this.placement)),this.syncExpanded(),this.registerDismissHandlers(),await this.updateComplete;let e=await p(this.popupElement,this.placement);this.panelElement&&g(this.panelElement,e),this.panelAnimated=!0,this.dispatchEvent(new ye),this.dispatchEvent(new CustomEvent(`pk-open-change`,{detail:{open:!0},bubbles:!0,composed:!0}))}async closePopover(e=`unknown`,t=!1){if(this.exitAnimationPromise)return this.exitAnimationPromise;if(!this.dismissRegistered&&!this.closing&&!this.open)return;let n=new xe(e);if(!this.dispatchEvent(n)){this.syncingOpenSideEffects=!0,this.open=!0,this.syncingOpenSideEffects=!1,this.closing=!1,this.panelAnimated=!0;return}this.unregisterDismissHandlers(),this.closing=!0,this.panelAnimated=!1,this.open&&(this.syncingOpenSideEffects=!0,this.open=!1,this.syncingOpenSideEffects=!1);let r=async()=>{t||(await this.updateComplete,await this.waitForExitAnimation()),this.closing=!1,this.panelAnimated=!1,this.panelElement&&(this.panelElement.hidden=!0,this.panelElement.removeAttribute(`data-side`)),this.syncExpanded(),this.dispatchEvent(new Se),this.dispatchEvent(new CustomEvent(`pk-open-change`,{detail:{open:!1},bubbles:!0,composed:!0}))};return this.exitAnimationPromise=r().finally(()=>{this.exitAnimationPromise=null}),this.exitAnimationPromise}waitForExitAnimation(){let e=this.panelElement;return e?new Promise(t=>{let n=!1,r=()=>{n||(n=!0,e.removeEventListener(`animationend`,i),window.clearTimeout(a),e.classList.remove(`closing`),t())},i=t=>{t.target===e&&t.animationName.startsWith(`pk-popup-content-out`)&&r()};e.classList.add(`closing`),e.addEventListener(`animationend`,i);let a=window.setTimeout(r,150)}):Promise.resolve()}getAnchor(){return this.anchor?this.anchor:this.for?f(this,this.for):this.triggerElement?this.triggerElement:null}registerDismissHandlers(){this.dismissRegistered||(ve(this),this.dismissRegistered=!0,document.addEventListener(`pointerdown`,this.onDocumentPointerDown,!0),document.addEventListener(`keydown`,this.onDocumentKeyDown,!0))}unregisterDismissHandlers(){this.dismissRegistered&&=(ge(this),!1),document.removeEventListener(`pointerdown`,this.onDocumentPointerDown,!0),document.removeEventListener(`keydown`,this.onDocumentKeyDown,!0)}isPointerInside(e){return ue(e,{host:this,anchor:this.getAnchorElement(),panel:this.panelElement})}getAnchorElement(){return this.anchor instanceof HTMLElement?this.anchor:this.triggerElement?this.triggerElement:this.for?f(this,this.for):null}syncExpanded(){this.triggerElement?.setAttribute(`aria-expanded`,this.open?`true`:`false`)}render(){let e=this.getAnchor();return C`
            <slot name="trigger" @slotchange=${this.onTriggerSlotChange}></slot>
            <pk-popup
                .active=${this.open||this.closing}
                .anchor=${e??``}
                .placement=${this.placement}
                .distance=${this.sideOffset}
                .arrow=${this.withArrow}
                flip
                shift
            >
                <div
                    part="panel"
                    class=${w({panel:!0,"pk-popup-content":!0,closing:this.closing})}
                    ?hidden=${!this.open&&!this.closing}
                    data-open=${this.panelAnimated&&!this.closing?``:S}
                    tabindex=${this.open?`-1`:S}
                >
                    <slot></slot>
                </div>
            </pk-popup>
        `}};k([T({type:Boolean,reflect:!0})],U.prototype,`open`,void 0),k([T({reflect:!0})],U.prototype,`placement`,void 0),k([T({attribute:`side-offset`,type:Number})],U.prototype,`sideOffset`,void 0),k([T({type:Boolean,reflect:!0})],U.prototype,`flush`,void 0),k([T({attribute:`with-arrow`,type:Boolean,reflect:!0})],U.prototype,`withArrow`,void 0),k([T({reflect:!0})],U.prototype,`for`,void 0),k([T({attribute:!1})],U.prototype,`anchor`,void 0),k([D(`pk-popup`)],U.prototype,`popupElement`,void 0),k([x()],U.prototype,`triggerElement`,void 0),k([x()],U.prototype,`closing`,void 0),k([x()],U.prototype,`panelAnimated`,void 0),U=k([A(`pk-popover`)],U);var Ft=m({tagName:`pk-popover`,elementClass:U,react:V.default,events:{onPkShow:`pk-show`,onPkAfterShow:`pk-after-show`,onPkHide:`pk-hide`,onPkAfterHide:`pk-after-hide`,onPkOpenChange:`pk-open-change`}}),W=e=>{if(e)return t=>{B(t)&&e(t)}},It=V.forwardRef(function(e,t){let{open:n,flush:r,withArrow:i,onPkShow:a,onPkAfterShow:o,onPkHide:s,onPkAfterHide:c,onPkOpenChange:l,...u}=e;return(0,H.jsx)(Ft,{ref:t,...u,...n===void 0?{}:{open:n},...h([`flush`,`withArrow`],{flush:r,withArrow:i}),...a?{onPkShow:W(a)}:{},...o?{onPkAfterShow:W(o)}:{},...s?{onPkHide:W(s)}:{},...c?{onPkAfterHide:W(c)}:{},...l?{onPkOpenChange:W(l)}:{}})});It.displayName=`Popover`;var Lt=[E`
    @layer pk-component {
        .content.pk-popup-content {
            transform-origin: var(--pk-transform-origin, top);
        }

        .content.pk-popup-content[data-open] {
            animation: pk-tooltip-in 150ms ease forwards;
        }

        .content.pk-popup-content[data-open][data-side='top'] {
            animation-name: pk-tooltip-in-top;
        }

        .content.pk-popup-content[data-open][data-side='bottom'] {
            animation-name: pk-tooltip-in-bottom;
        }

        .content.pk-popup-content[data-open][data-side='left'] {
            animation-name: pk-tooltip-in-left;
        }

        .content.pk-popup-content[data-open][data-side='right'] {
            animation-name: pk-tooltip-in-right;
        }

        .content.pk-popup-content.closing {
            animation: pk-tooltip-out 150ms ease;
        }

        @keyframes pk-tooltip-in {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pk-tooltip-out {
            from {
                opacity: 1;
                transform: scale(1);
            }

            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }

        @keyframes pk-tooltip-in-top {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes pk-tooltip-in-bottom {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-0.5rem);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes pk-tooltip-in-left {
            from {
                opacity: 0;
                transform: scale(0.95) translateX(0.5rem);
            }

            to {
                opacity: 1;
                transform: scale(1) translateX(0);
            }
        }

        @keyframes pk-tooltip-in-right {
            from {
                opacity: 0;
                transform: scale(0.95) translateX(-0.5rem);
            }

            to {
                opacity: 1;
                transform: scale(1) translateX(0);
            }
        }
    }
`,E`
    @layer pk-component {
        :host {
            display: inline-block;
            max-width: 100%;
            align-self: flex-start;
            flex: none;
            vertical-align: middle;
            --pk-popup-z-index: 250;
            --pk-tooltip-arrow-size: 9px;
            --pk-tooltip-arrow-inset: 1px;
        }

        .content:not([data-open]):not(.closing) {
            opacity: 0;
            pointer-events: none;
        }

        .content[data-open]:not(.closing) {
            opacity: 1;
        }

        .content {
            position: relative;
            isolation: isolate;
            overflow: visible;
            width: fit-content;
            max-width: 20rem;
            padding: 4px 8px;
            border-radius: var(--pk-radius-sm);
            background: #1c2e36;
            color: var(--pk-color-white);
            font-family: var(--pk-font-family);
            font-size: 12px;
            line-height: 1.4;
            pointer-events: none;
        }

        .content::after {
            content: '';
            position: absolute;
            z-index: -1;
            width: var(--pk-tooltip-arrow-size);
            height: var(--pk-tooltip-arrow-size);
            background: #1c2e36;
            border-radius: 2px;
            rotate: 45deg;
            pointer-events: none;
        }

        .content[data-side='top']::after {
            left: calc(50% - var(--pk-tooltip-arrow-size) / 2);
            bottom: calc(var(--pk-tooltip-arrow-size) * -0.5 + var(--pk-tooltip-arrow-inset));
        }

        .content[data-side='bottom']::after {
            left: calc(50% - var(--pk-tooltip-arrow-size) / 2);
            top: calc(var(--pk-tooltip-arrow-size) * -0.5 + var(--pk-tooltip-arrow-inset));
        }

        .content[data-side='left']::after {
            top: calc(50% - var(--pk-tooltip-arrow-size) / 2);
            right: calc(var(--pk-tooltip-arrow-size) * -0.5 + var(--pk-tooltip-arrow-inset));
        }

        .content[data-side='right']::after {
            top: calc(50% - var(--pk-tooltip-arrow-size) / 2);
            left: calc(var(--pk-tooltip-arrow-size) * -0.5 + var(--pk-tooltip-arrow-inset));
        }

        .content[hidden] {
            display: none !important;
        }
    }
    `],G=class extends O{constructor(...e){super(...e),this.placement=`top`,this.trigger=`hover focus`,this.disabled=!1,this.openDelay=0,this.closeDelay=0,this.content=``,this.for=``,this.open=!1,this.triggerElement=null,this.contentAnimated=!1,this.closing=!1,this.contentSide=null,this.hasSlottedBody=!1,this.triggerId=_(`pk-tooltip-trigger`),this.tooltipId=_(`pk-tooltip`),this.showGeneration=0,this.exitGeneration=0,this.syncPlacementAnimation=()=>{let e=this.popupElement?.getAttribute(`data-current-placement`)??this.placement,t=this.popupElement?.getContentElement();this.contentSide=e?ae(e):null,g(this.popupElement,e),t&&g(t,e)},this.onBodySlotChange=e=>{let t=e.target;this.hasSlottedBody=t.assignedNodes({flatten:!0}).some(e=>e.nodeType===Node.ELEMENT_NODE||e.nodeType===Node.TEXT_NODE&&!!e.textContent?.trim())},this.scheduleShow=()=>{this.usesPointerTrigger()&&(window.clearTimeout(this.closeTimer),window.clearTimeout(this.openTimer),this.openTimer=window.setTimeout(()=>this.showTooltip(),this.openDelay))},this.scheduleHide=()=>{this.usesPointerTrigger()&&(window.clearTimeout(this.openTimer),window.clearTimeout(this.closeTimer),this.closeTimer=window.setTimeout(()=>this.hideTooltip(),this.closeDelay))}}static{this.styles=Lt}disconnectedCallback(){this.popupElement?.removeEventListener(`pk-reposition`,this.syncPlacementAnimation),this.clearTimers(),this.hideTooltip(!0),super.disconnectedCallback()}updated(e){super.updated(e),(e.has(`trigger`)||e.has(`disabled`)||e.has(`for`))&&this.rebindTrigger(),this.disabled&&this.open&&this.hideTooltip(!0)}firstUpdated(){this.popupElement.addEventListener(`pk-reposition`,this.syncPlacementAnimation),this.for&&queueMicrotask(()=>{this.resolveExternalTrigger()})}async show(){this.disabled||(this.clearTimers(),this.showTooltip(!0),await this.updateComplete)}async hide(){this.clearTimers(),this.hideTooltip(!0),await this.updateComplete}clearTimers(){window.clearTimeout(this.openTimer),window.clearTimeout(this.closeTimer)}resolveExternalTrigger(){this.triggerElement=f(this,this.for),this.rebindTrigger()}onTriggerSlotChange(e){let[t]=e.target.assignedElements({flatten:!0});this.unbindTrigger(this.triggerElement),this.triggerElement=t??null,this.rebindTrigger(),this.requestUpdate()}rebindTrigger(){this.unbindTrigger(this.triggerElement),this.for&&(this.triggerElement=f(this,this.for)),this.bindTrigger(this.triggerElement)}usesPointerTrigger(){return!this.disabled&&this.trigger!==`manual`}bindTrigger(e){e&&this.usesPointerTrigger()&&(e.id||=this.triggerId,e.setAttribute(`aria-describedby`,this.tooltipId),e.addEventListener(`mouseenter`,this.scheduleShow),e.addEventListener(`mouseleave`,this.scheduleHide),e.addEventListener(`focus`,this.scheduleShow),e.addEventListener(`blur`,this.scheduleHide))}unbindTrigger(e){e&&(e.removeAttribute(`aria-describedby`),e.removeEventListener(`mouseenter`,this.scheduleShow),e.removeEventListener(`mouseleave`,this.scheduleHide),e.removeEventListener(`focus`,this.scheduleShow),e.removeEventListener(`blur`,this.scheduleHide))}getAnchor(){return this.for?f(this,this.for):this.triggerElement?this.triggerElement:null}prepareContentForEnter(e){e&&(e.classList.remove(`closing`),e.style.animation=`none`,e.getBoundingClientRect(),e.style.removeProperty(`animation`),e.style.removeProperty(`opacity`),e.style.removeProperty(`transform`))}showTooltip(e=!1){if(!this.getAnchor()||this.open&&this.contentAnimated&&!this.closing&&!e)return;this.open||this.dispatchEvent(new _e);let t=++this.showGeneration;this.exitGeneration+=1,this.closing=!1,this.open=!0,this.contentAnimated=!1,this.prepareContentForEnter(this.popupElement?.getContentElement()),this.updateComplete.then(async()=>{t===this.showGeneration&&(await p(this.popupElement,this.placement),t===this.showGeneration&&(this.syncPlacementAnimation(),this.prepareContentForEnter(this.popupElement?.getContentElement()),this.contentAnimated=!0,this.dispatchEvent(new ye),this.dispatchEvent(new CustomEvent(`pk-open-change`,{detail:{open:!0},bubbles:!0,composed:!0}))))})}hideTooltip(e=!1,t=!1){if((this.open||this.closing||e)&&(!this.closing||e)){if(t){let e=new xe(`api`);if(!this.dispatchEvent(e))return}if(this.showGeneration+=1,e){this.finishHide();return}if(!this.contentAnimated){this.finishHide();return}this.playExitAnimation()}}async playExitAnimation(){if(!this.open)return;let e=this.exitGeneration+1;this.exitGeneration=e;let t=this.popupElement?.getContentElement();this.closing=!0,this.contentAnimated=!1,t&&await this.waitForExitAnimation(t),e===this.exitGeneration&&this.finishHide()}waitForExitAnimation(e){return new Promise(t=>{let n=!1,r=()=>{n||(n=!0,e.removeEventListener(`animationend`,i),window.clearTimeout(a),e.classList.remove(`closing`),t())},i=t=>{t.target===e&&t.animationName.startsWith(`pk-tooltip-out`)&&r()};e.classList.add(`closing`),e.addEventListener(`animationend`,i);let a=window.setTimeout(r,200)})}finishHide(){let e=this.popupElement?.getContentElement();this.closing=!1,this.contentAnimated=!1,this.contentSide=null,this.open=!1,this.popupElement?.removeAttribute(`data-side`),this.prepareContentForEnter(e),this.dispatchEvent(new Se),this.dispatchEvent(new CustomEvent(`pk-open-change`,{detail:{open:!1},bubbles:!0,composed:!0}))}render(){let e=this.getAnchor();return C`
            <slot name="trigger" @slotchange=${this.onTriggerSlotChange}></slot>
            <pk-popup
                .active=${this.open||this.closing}
                .anchor=${e??``}
                .placement=${this.placement}
                .distance=${4}
                hover-bridge
                flip
                shift
            >
                <div
                    part="content"
                    class=${w({content:!0,"pk-popup-content":!0,closing:this.closing})}
                    id=${this.tooltipId}
                    role="tooltip"
                    ?hidden=${!this.open&&!this.closing}
                    data-open=${this.contentAnimated&&!this.closing?``:S}
                    data-side=${this.contentSide??S}
                >
                    <slot @slotchange=${this.onBodySlotChange}></slot>
                    ${this.hasSlottedBody?S:this.content||S}
                </div>
            </pk-popup>
        `}};k([T({reflect:!0})],G.prototype,`placement`,void 0),k([T({reflect:!0})],G.prototype,`trigger`,void 0),k([T({type:Boolean,reflect:!0})],G.prototype,`disabled`,void 0),k([T({type:Number,attribute:`open-delay`})],G.prototype,`openDelay`,void 0),k([T({type:Number,attribute:`close-delay`})],G.prototype,`closeDelay`,void 0),k([T()],G.prototype,`content`,void 0),k([T({reflect:!0})],G.prototype,`for`,void 0),k([D(`pk-popup`)],G.prototype,`popupElement`,void 0),k([x()],G.prototype,`open`,void 0),k([x()],G.prototype,`contentAnimated`,void 0),k([x()],G.prototype,`closing`,void 0),k([x()],G.prototype,`contentSide`,void 0),k([x()],G.prototype,`hasSlottedBody`,void 0),G=k([A(`pk-tooltip`)],G);function Rt(){return C`
        <svg xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" viewBox="0 0 640 640">
            <path
                fill="currentColor"
                d="M557.5 192L534.9 214.6L278.9 470.6C266.4 483.1 246.1 483.1 233.6 470.6L105.6 342.6L83 320L128.3 274.7C129.6 276 172.3 318.7 256.3 402.7L489.7 169.3L512.3 146.7L557.6 192z"
            />
        </svg>
    `}function zt(){return C`
        <svg xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" viewBox="0 0 640 640">
            <path fill="currentColor" d="M96 352V288H544V352H96z" />
        </svg>
    `}var Bt=[E`
    @layer pk-component {
        .control {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            position: relative;
            box-sizing: border-box;
            width: var(--pk-checkbox-size);
            height: var(--pk-checkbox-size);
            border: 1px solid var(--pk-checkbox-border-color, #c0cbd9);
            border-radius: var(--pk-radius-sm);
            background: var(--pk-color-white);
            cursor: pointer;
            transition: border-color 0.12s ease, box-shadow 0.12s ease;
        }

        :host([disabled]) .control {
            cursor: not-allowed;
        }

        .input:focus-visible + .control {
            border-color: var(--pk-color-sky-600);
            box-shadow: 0 0 0 1px var(--pk-color-sky-600), 0 0 4px 0 hsl(from var(--pk-color-sky-600) h s l / 0.7);
        }

        :host([invalid]) .control,
        .input[aria-invalid='true'] + .control {
            border-color: var(--pk-color-rose-600);
        }

        :host([invalid]) .input:focus-visible + .control,
        .input[aria-invalid='true']:focus-visible + .control {
            border-color: var(--pk-color-rose-600);
            box-shadow: 0 0 0 1px var(--pk-color-rose-600), 0 0 4px 0 hsl(from var(--pk-color-rose-600) h s l / 0.7);
        }

        .indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--pk-color-gray-900);
        }

        .icon-check,
        .icon-indeterminate {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
        }

        .icon-check svg {
            width: 14px;
            height: 14px;
            transform: translateY(1px) scale(1.2);
        }

        .icon-indeterminate svg {
            width: 12px;
            height: 12px;
        }

        :host([checked]) .icon-check,
        .input:checked + .control .icon-check {
            opacity: 1;
        }

        :host([indeterminate]) .icon-check,
        .input:indeterminate + .control .icon-check {
            opacity: 0;
        }

        :host([indeterminate]) .icon-indeterminate,
        .input:indeterminate + .control .icon-indeterminate {
            opacity: 1;
        }
    }
`,E`
    @layer pk-component {
        :host {
            display: inline-flex;
            vertical-align: middle;
            /* Hit target is the content-sized .root label (Craft checkbox-select), not the host. */
            cursor: default;
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: var(--pk-line-height);
        }

        :host([disabled]) {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .root {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: flex-start;
            gap: var(--pk-control-label-gap);
            /* Content-sized like Craft's <label> beside the checkbox — not full-row. */
            width: fit-content;
            max-width: 100%;
            margin: 0;
            min-height: 0;
            cursor: pointer;
            user-select: none;
            position: relative;
        }

        :host([disabled]) .root {
            cursor: not-allowed;
        }

        .root--with-hint {
            align-items: flex-start;
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

        .text {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
            min-width: 0;
        }

        .label {
            line-height: max(1rem, var(--pk-checkbox-size));
            /* Match form-control / Craft body labels (gray-700), not gray-900. */
            color: var(--pk-color-gray-700);
            cursor: pointer;
        }

        :host([disabled]) .label {
            cursor: not-allowed;
        }

        :host(.all-option) .label {
            font-weight: 700;
        }

        .hint {
            margin: 0;
            color: var(--pk-color-gray-500);
            font-size: var(--pk-font-size-sm);
            line-height: var(--pk-line-height);
        }

        .hint:empty {
            display: none;
        }
    }
`],K=class extends v{constructor(...e){super(...e),this.assumeInteractionOn=[`change`],this.hasSlotController=new re(this,`hint`),this.checked=!1,this.indeterminate=!1,this.disabled=!1,this.invalid=!1,this.checkboxValue=`on`,this.defaultChecked=!1,this.ariaLabel=null,this.hint=``,this.withHint=!1,this.hasDefaultSlotContent=!1}static{this.shadowRootOptions={mode:`open`,delegatesFocus:!0}}static{this.styles=Bt}static get validators(){return[...super.validators,oe({validationProperty:`checked`})]}get validationTarget(){return this.input}syncFormValue(){this.setFormValue(this.checked?this.checkboxValue:null,this.checked?`on`:`off`)}resetToDefaultValue(){this.checked=this.defaultChecked,this.indeterminate=!1}restoreFormState(e){this.checked=e===`on`||e===this.checkboxValue}updated(e){if(!this.input){super.updated(e);return}(e.has(`indeterminate`)||e.has(`checked`))&&(this.input.indeterminate=this.indeterminate,this.input.checked=this.checked),super.updated(e)}defaultSlotChanged(e){let t=e.target;this.hasDefaultSlotContent=t.assignedNodes({flatten:!0}).some(e=>e.nodeType===Node.TEXT_NODE?e.textContent?.trim():e.nodeType===Node.ELEMENT_NODE)}handleChange(e){let t=e.target;this.checked=t.checked,this.indeterminate=!1,this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{checked:this.checked},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}render(){let e=this.hasDefaultSlotContent,t=!!this.hint||this.hasSlotController.test(`hint`,this.withHint);return C`
            <label
                part="base"
                class=${w({root:!0,"root--with-hint":t})}
            >
                <input
                    part="input"
                    class="input"
                    type="checkbox"
                    .checked=${this.checked}
                    ?disabled=${this.disabled}
                    ?required=${this.required}
                    name=${this.name??S}
                    value=${this.checkboxValue}
                    aria-labelledby=${e?`label`:S}
                    aria-describedby=${t?`hint`:S}
                    aria-label=${e?S:this.ariaLabel??S}
                    aria-invalid=${this.invalid?`true`:S}
                    @change=${this.handleChange}
                />
                <span part="control" class="control">
                    <span part="checked-icon" class="icon-check">${Rt()}</span>
                    <span part="indeterminate-icon" class="icon-indeterminate">${zt()}</span>
                </span>
                ${e||t?C`
                        <span class="text">
                            ${e?C`
                                    <span part="label" class="label" id="label">
                                        <slot @slotchange=${this.defaultSlotChanged}></slot>
                                    </span>
                                `:C`<slot @slotchange=${this.defaultSlotChanged} hidden></slot>`}
                            ${t?C`
                                    <span part="hint" class="hint" id="hint">
                                        <slot name="hint">${this.hint}</slot>
                                    </span>
                                `:S}
                        </span>
                    `:C`<slot @slotchange=${this.defaultSlotChanged} hidden></slot>`}
            </label>
        `}};k([T({type:Boolean,reflect:!0})],K.prototype,`checked`,void 0),k([T({type:Boolean,reflect:!0})],K.prototype,`indeterminate`,void 0),k([T({type:Boolean,reflect:!0})],K.prototype,`disabled`,void 0),k([T({type:Boolean,reflect:!0})],K.prototype,`invalid`,void 0),k([T()],K.prototype,`checkboxValue`,void 0),k([T({attribute:`default-checked`,type:Boolean})],K.prototype,`defaultChecked`,void 0),k([T({attribute:`aria-label`})],K.prototype,`ariaLabel`,void 0),k([T()],K.prototype,`hint`,void 0),k([T({type:Boolean,attribute:`with-hint`})],K.prototype,`withHint`,void 0),k([D(`.input`)],K.prototype,`input`,void 0),k([x()],K.prototype,`hasDefaultSlotContent`,void 0),K=k([A(`pk-checkbox`)],K);function Vt(e,t=150){return e?new Promise(n=>{let r=!1,i=()=>{r||(r=!0,e.removeEventListener(`animationend`,a),window.clearTimeout(o),e.classList.remove(`closing`),n())},a=t=>{t.target===e&&t.animationName.startsWith(`pk-popup-content-out`)&&i()};e.classList.add(`closing`),e.addEventListener(`animationend`,a);let o=window.setTimeout(i,t)}):Promise.resolve()}function Ht(e){let{host:t,options:n,visible:r,listboxId:i,filterQuery:a,isSelected:o}=e;for(let e of n)e.selected=o(e.value),e.hidden=!r.includes(e),e.optionId=`${i}-option-${e.value}`,e.matchQuery=a;for(let e of t.querySelectorAll(`pk-option-group`)){let t=[...e.querySelectorAll(`pk-option`)],n=t.length>0&&t.every(e=>e.hidden);e.toggleAttribute(`data-pk-filter-empty`,n)}pe(t)}var Ut=class{constructor(e,t){this.getHandler=e,this.timer=null,this.requestId=0,this.abortController=null,this.callbacks=t,this.debounceMs=t.debounceMs??200,this.errorLabel=t.errorLabel??`options`}schedule(e){this.timer!==null&&window.clearTimeout(this.timer),this.timer=window.setTimeout(()=>{this.timer=null,this.run(e)},this.debounceMs)}cancel(){this.timer!==null&&(window.clearTimeout(this.timer),this.timer=null),this.abortController?.abort(),this.abortController=null}async run(e){let t=this.getHandler();if(!t)return;let n=++this.requestId;if(this.abortController?.abort(),this.abortController=new AbortController,!e){this.callbacks.onEmptyQuery?.();return}this.callbacks.onLoading?.();try{let r=await t(e,this.abortController.signal);if(n!==this.requestId)return;this.callbacks.onResults(r)}catch(e){if(this.abortController?.signal.aborted||n!==this.requestId||e instanceof DOMException&&e.name===`AbortError`)return;console.error(`Failed to load ${this.errorLabel}:`,e),this.callbacks.onError?.(`Failed to load options. Please try again.`),this.callbacks.onResults([])}finally{n===this.requestId&&this.callbacks.onSettled?.()}}};function Wt(e,t){let n=e.getLabel().toLowerCase(),r=e.value.toLowerCase(),i=(e.getSearchText?.()??n).toLowerCase();return n.includes(t)||r.includes(t)||i.includes(t)}function Gt(e,t,n){return n?n(e,t):Wt(e,t)}var Kt=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="10" height="10" aria-hidden="true" focusable="false">
  <path fill="currentColor" d="M2.1 1.4 5 4.3l2.9-2.9.7.7L5.7 5l2.9 2.9-.7.7L5 5.7l-2.9 2.9-.7-.7L4.3 5 1.4 2.1z"/>
</svg>`,qt=`pk-variable-tag-configure`,Jt=e=>{let t=e;for(;t;){if(t instanceof HTMLElement&&t.localName.startsWith(`pk-tiptap`))return t;t=t.parentNode instanceof ShadowRoot?t.parentNode.host:t.parentNode}return null},Yt=(e,t)=>{let n=e.view.dom,r=Jt(n),i=r&&`variableTagConfigure`in r?r.variableTagConfigure:null;typeof i==`function`&&i(t),(r??n).dispatchEvent(new CustomEvent(qt,{bubbles:!0,composed:!0,detail:t}))};function Xt(e,t){let n=()=>{let e=typeof t==`function`?t():t;return typeof e==`number`?e:void 0};return{deleteNode:()=>{let t=n();if(typeof t!=`number`)return;let r=e.state.doc.nodeAt(t);r&&r.type.name===`variableTag`&&e.chain().focus().deleteRange({from:t,to:t+r.nodeSize}).run()},updateAttributes:t=>{let r=n();if(typeof r!=`number`)return;let i=e.state.doc.nodeAt(r);i&&i.type.name===`variableTag`&&e.chain().command(({tr:e,dispatch:n})=>(n&&e.setNodeMarkup(r,void 0,{...i.attrs,...t}),!0)).run()},resolvePos:n}}function Zt(){return e=>{let{editor:t,getPos:n}=e,{deleteNode:r,updateAttributes:i,resolvePos:a}=Xt(t,n),o=e.node,s=e=>{let t=!!e.unresolved,n=String(e.value??``),r=String(e.label||e.value||``);if(c.classList.toggle(`pk-variable-tag--unresolved`,t),t){c.dataset.unresolved=`true`;let e=n?`Unknown or missing reference — ${n}`:`Unknown or missing reference`;c.setAttribute(`aria-description`,e),l.setAttribute(`aria-invalid`,`true`)}else delete c.dataset.unresolved,c.removeAttribute(`aria-description`),l.removeAttribute(`aria-invalid`);l.textContent=r},c=document.createElement(`span`);c.className=`pk-variable-tag`,c.id=_(`pk-variable-tag`),c.setAttribute(`contenteditable`,`false`),c.setAttribute(`data-drag-handle`,``),c.dataset.label=String(o.attrs.label??``),c.dataset.variableValue=String(o.attrs.value??``);let l=document.createElement(`button`);l.type=`button`,l.className=`pk-variable-tag__label`,l.draggable=!1,c.append(l),s(o.attrs);let u=null,d=!1,f=0,p=()=>{let e=a();if(typeof e==`number`){let n=t.state.doc.nodeAt(e);if(n?.type.name===`variableTag`)return{...n.attrs}}return{...o.attrs}},m=()=>{if(!t.isEditable)return;let e=Date.now();e-f<300||(f=e,c.removeAttribute(`draggable`),Yt(t,{editor:t,anchor:c,attrs:p(),getPos:a,updateAttributes:i,deleteNode:r}))};l.addEventListener(`pointerdown`,e=>{e.button===0&&(e.preventDefault(),e.stopPropagation(),m())}),l.addEventListener(`click`,e=>{e.preventDefault(),e.stopPropagation(),m()});let h=e=>{if(e){if(u)return;u=document.createElement(`button`),u.type=`button`,u.className=`pk-variable-tag__remove`,u.setAttribute(`aria-label`,`Remove`),u.draggable=!1,u.innerHTML=Kt,u.addEventListener(`pointerdown`,e=>{e.preventDefault(),e.stopPropagation()}),u.addEventListener(`click`,e=>{e.preventDefault(),e.stopPropagation(),r()}),c.append(u);return}u?.remove(),u=null};return h(t.isEditable),o.attrs.openOnInsert&&t.isEditable&&!d&&(d=!0,queueMicrotask(()=>{i({openOnInsert:!1}),m()})),{dom:c,stopEvent:e=>{let t=e.target;return t instanceof Element&&!!(t.closest(`.pk-variable-tag__label`)||t.closest(`.pk-variable-tag__remove`))},selectNode:()=>{c.classList.add(`ProseMirror-selectednode`),c.removeAttribute(`draggable`)},deselectNode:()=>{c.classList.remove(`ProseMirror-selectednode`),c.removeAttribute(`draggable`)},update(e){return e.type.name===`variableTag`&&(o=e,c.dataset.label=String(e.attrs.label??``),c.dataset.variableValue=String(e.attrs.value??``),s(e.attrs),h(t.isEditable),!0)}}}}var Qt=E`
    .pk-variable-tag {
        position: relative;
        display: inline-flex;
        align-items: stretch;
        max-width: 100%;
        margin-inline: 1px;
        margin-block-start: -3px;
        padding: 0;
        border-radius: 2px;
        background: #5c6bc0;
        color: #fff;
        font-size: 11px;
        font-weight: 400;
        line-height: 1;
        white-space: nowrap;
        vertical-align: middle;
        overflow: hidden;
        cursor: default;
        box-sizing: border-box;
    }

    /* Unresolved / missing reference — muted grey (not alarm yellow). */
    .pk-variable-tag--unresolved {
        background: var(--pk-color-gray-100, #f3f4f6);
        color: var(--pk-color-gray-600, #4b5563);
        box-shadow: inset 0 0 0 1px var(--pk-color-gray-300, #d1d5db);
    }

    .pk-variable-tag--unresolved.ProseMirror-selectednode {
        box-shadow:
            inset 0 0 0 1px var(--pk-color-gray-400, #9ca3af),
            0 0 0 2px rgba(156, 163, 175, 0.45);
    }

    .pk-variable-tag.ProseMirror-selectednode {
        outline: none;
        box-shadow: 0 0 0 2px rgba(123, 140, 232, 0.5);
    }

    .pk-variable-tag__label {
        display: inline-flex;
        align-items: center;
        max-width: 220px;
        margin: 0;
        padding: 4px 5px;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-size: inherit;
        line-height: inherit;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        user-select: none;
    }

    .pk-variable-tag__remove {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        align-self: stretch;
        margin: 0;
        padding: 4px 5px 4px 4px;
        border: 0;
        background: transparent;
        color: inherit;
        cursor: pointer;
        line-height: 0;
        appearance: none;
    }

    .pk-variable-tag__remove svg {
        display: block;
        width: 10px;
        height: 10px;
        pointer-events: none;
    }
`,$t=E`
    @layer pk-component {
        .ProseMirror {
            outline: none;
            min-height: 2rem;
            padding: 1rem;
            background: rgb(251, 252, 254);
            /* Craft CP body text (~gray-700), not gray-900. */
            color: var(--pk-color-gray-700);
            font-family: var(--pk-font-family);
            /* Match pk-input / body (14px). sm (13px) matched field instructions and looked undersized vs v1. */
            font-size: var(--pk-font-size-base);
            line-height: 1.5;
            white-space: pre-wrap;
            box-sizing: border-box;
        }

        .ProseMirror p {
            margin: 0 0 0.5rem;
        }

        .ProseMirror p:last-child {
            margin-bottom: 0;
        }

        .ProseMirror h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
        }

        .ProseMirror h2 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
        }

        .ProseMirror h3,
        .ProseMirror h4,
        .ProseMirror h5,
        .ProseMirror h6 {
            font-weight: 600;
            margin: 0 0 0.5rem;
        }

        .ProseMirror a {
            color: var(--pk-color-blue-600);
            text-decoration: underline;
            cursor: pointer;
        }

        .ProseMirror ul,
        .ProseMirror ol {
            margin: 0 0 0.5rem;
            padding-left: 1.25rem;
        }

        .ProseMirror blockquote {
            margin: 0 0 0.5rem;
            padding-left: 0.75rem;
            border-left: 3px solid var(--pk-color-gray-300);
            color: var(--pk-color-gray-600);
        }

        .ProseMirror code {
            font-family: var(--pk-font-family-mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace);
            font-size: var(--pk-font-size-mono, 0.9em);
            line-height: var(--pk-line-height-mono, 1.5);
            background: var(--pk-color-gray-100);
            border-radius: 0.2rem;
            padding: 0.1rem 0.25rem;
        }

        .ProseMirror pre {
            margin: 0 0 0.5rem;
            padding: 0.75rem;
            background: var(--pk-color-gray-900);
            color: var(--pk-color-gray-50);
            border-radius: var(--pk-radius-md);
            overflow-x: auto;
            font-family: var(--pk-font-family-mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace);
            font-size: var(--pk-font-size-mono, 0.9em);
            line-height: var(--pk-line-height-mono, 1.5);
        }

        ${Qt}
    }
`,en=E`
    @layer pk-component {
        .ProseMirror {
            outline: none;
            margin: 0;
            /* Match stock pk-input padding by default. Hosts can override density via
             * --pk-tiptap-input-* (light DOM cannot style .ProseMirror in the shadow tree).
             */
            padding-block: var(--pk-tiptap-input-padding-block, 6px);
            padding-inline-start: var(--pk-tiptap-input-padding-inline-start, 8px);
            padding-inline-end: var(--pk-tiptap-input-padding-inline-end, 8px);
            /* One-liner clip: padding + control line-height (v1 text-sm), not a fixed shell token. */
            height: var(--pk-tiptap-input-height, calc(var(--pk-input-control-line-height, 1.25rem) + 12px));
            max-height: var(--pk-tiptap-input-height, calc(var(--pk-input-control-line-height, 1.25rem) + 12px));
            background: var(--pk-input-bg);
            /* Craft CP body / field value text. */
            color: var(--pk-color-gray-700);
            font-family: var(--pk-font-family);
            font-size: var(--pk-tiptap-input-font-size, var(--pk-font-size-base));
            line-height: var(--pk-tiptap-input-line-height, var(--pk-input-control-line-height, 1.25rem));
            white-space: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            box-sizing: border-box;
            scrollbar-width: none;
        }

        .ProseMirror::-webkit-scrollbar {
            display: none;
        }

        /* OneLinerDocument is inline-only; keep p inline if a schema ever wraps text. */
        .ProseMirror p {
            margin: 0;
            display: inline;
            line-height: inherit;
        }

        /* Read-only display: no input padding/height box (v1 skipped chrome when readOnly).
         * Inherit color/weight so list name links (text-blue-600 font-bold) show through. */
        :host([readonly]) .ProseMirror {
            padding-block: 0;
            padding-inline: 0;
            height: auto;
            max-height: none;
            background: transparent;
            overflow: visible;
            white-space: normal;
            color: inherit;
            font-weight: inherit;
            font-size: inherit;
        }

        ${Qt}
    }
`,tn=E`
    @layer pk-component {
        /* Inherit host color/size so light-DOM wrappers (warning banners, badges)
           can restyle read-only TipTap without piercing the shadow tree. */
        .ProseMirror {
            outline: none;
            color: inherit;
            font-family: inherit;
            font-size: inherit;
            line-height: inherit;
        }

        .ProseMirror p {
            margin: 0;
        }
    }
`,nn=E`
    @layer pk-component {
        :host {
            display: block;
            width: 100%;
            /* Inherit light-DOM color/size (warning banners, badges). Tokens override when set. */
            color: var(--pk-tiptap-content-color, inherit);
            font-family: var(--pk-font-family);
            font-size: var(--pk-tiptap-content-font-size, inherit);
            line-height: var(--pk-tiptap-content-line-height, 1.4);
        }
    }
`;function rn(e){return e?Array.isArray(e.content)?JSON.stringify(e.content):JSON.stringify(e):`[]`}var q=class extends O{constructor(...e){super(...e),this.editor=null,this.value=``}static{this.styles=[nn,tn]}disconnectedCallback(){this.editor?.destroy(),this.editor=null,super.disconnectedCallback()}updated(e){e.has(`value`)&&this.editor&&this.syncContent(),super.updated(e)}firstUpdated(){this.mountEditor()}mountEditor(){this.editor=new n({element:this.editorMount,extensions:a({variableTagNodeView:Zt(),surface:`content`}),content:P(this.value),editable:!1})}syncContent(){if(!this.editor)return;let e=P(this.value)??{type:`doc`,content:[]};rn(P(this.value))!==rn(this.editor.getJSON())&&this.editor.commands.setContent(e)}render(){return C`<div class="editor-mount" part="content"></div>`}};k([D(`.editor-mount`)],q.prototype,`editorMount`,void 0),k([T({attribute:`value`})],q.prototype,`value`,void 0),q=k([A(`pk-tiptap-content`)],q);function an(e){return JSON.stringify(N(e))}function on(e){return e==null?`[]`:typeof e==`string`?e:Array.isArray(e)?JSON.stringify(e):`[]`}function sn(e){if(e==null||e===``)return[];if(Array.isArray(e))return e;if(typeof e!=`string`)return[];try{let t=JSON.parse(e);return Array.isArray(t)?t:[]}catch{return[]}}var cn=class{constructor(){this.editor=null,this.lastEmitted=null,this.suppressUpdateEmission=!1}mount({element:e,extensions:t,content:r,editable:i,trailingCursorText:o=`⁠`,minHeight:s,onUpdate:c}){let l=P(r,{trailingCursorText:o});this.editor=new n({element:e,extensions:t??a({trailingCursorText:o}),content:l,editable:i,onUpdate:({editor:e})=>{let t=N(e.getJSON().content),n=JSON.stringify(t);this.lastEmitted=n,!this.suppressUpdateEmission&&c?.(t)}}),s&&(this.editor.view.dom.style.minHeight=s)}setContent(e,t={}){if(!this.editor)return;let{trailingCursorText:n=`⁠`,respectFocus:r=!0}=t;if(r&&this.editor.isFocused)return;let i=P(e,{trailingCursorText:n}),a=JSON.stringify(i?.content??[]);if(a!==JSON.stringify(this.editor.getJSON().content??[])&&a!==this.lastEmitted){this.suppressUpdateEmission=!0;try{this.editor.commands.setContent(i??{type:`doc`,content:[]})}finally{this.suppressUpdateEmission=!1}}}destroy(){this.editor?.destroy(),this.editor=null,this.lastEmitted=null,this.suppressUpdateEmission=!1}};function ln(){return{openElementSelector:(e,t)=>{let n=window.Craft?.createElementSelectorModal;if(!n)throw Error(`Craft element selector is not available in this environment.`);n(e,t)}}}function un(e){if(e)try{return JSON.parse(e)}catch{return}}function dn(e,t){return Array.isArray(e)||typeof e==`string`&&e.trim()?R(e):R(Array.isArray(t)?t.join(`,`):t??`bold,italic`)}var fn=j(y.chevronDown),pn={bold:j(y.bold),italic:j(y.italic),underline:j(y.underline),strikethrough:j(y.strikethrough),subscript:j(y.subscript),superscript:j(y.superscript),"small-caps":j(y.smallCaps),code:j(y.bracketsCurly),"code-block":j(y.code),highlight:j(y.highlighter),h1:j(y.h1),h2:j(y.h2),h3:j(y.h3),h4:j(y.h4),h5:j(y.h5),h6:j(y.h6),heading:j(y.heading),paragraph:j(y.paragraph),"unordered-list":j(y.listUl),"ordered-list":j(y.listOl),blockquote:j(y.quoteRight),"align-left":j(y.alignLeft),"align-center":j(y.alignCenter),"align-right":j(y.alignRight),"align-justify":j(y.alignJustify),"clear-format":j(y.textSlash),hr:j(y.minus),"line-break":j(y.fileDashedLine),link:j(y.link),table:j(y.table),undo:j(y.arrowRotateLeft),redo:j(y.arrowRotateRight)},mn={bold:`Bold`,italic:`Italic`,underline:`Underline`,strikethrough:`Strikethrough`,subscript:`Subscript`,superscript:`Superscript`,"small-caps":`Small caps`,"font-family":`Font family`,"font-size":`Font size`,"text-color":`Text and background color`,"line-height":`Line height`,code:`Inline code`,"code-block":`Code block`,highlight:`Highlight`,h1:`Heading 1`,h2:`Heading 2`,h3:`Heading 3`,h4:`Heading 4`,h5:`Heading 5`,h6:`Heading 6`,paragraph:`Paragraph`,"unordered-list":`Bullet list`,"ordered-list":`Numbered list`,blockquote:`Blockquote`,"align-left":`Align left`,"align-center":`Align center`,"align-right":`Align right`,"align-justify":`Justify`,"clear-format":`Clear format`,hr:`Horizontal rule`,"line-break":`Line break`,link:`Link`,table:`Table`,undo:`Undo`,redo:`Redo`};function hn(e){return mn[e]??i(e)?.label??e}function gn(e){let t=i(e)?.icon;return pn[e]??(t?j(t):void 0)}function _n(e){let t=gn(e);if(!t)return null;let n=document.createElement(`template`);n.innerHTML=t.trim();let r=n.content.firstElementChild;return r instanceof SVGSVGElement?(r.setAttribute(`slot`,`prefix`),r.setAttribute(`aria-hidden`,`true`),r.setAttribute(`width`,`12`),r.setAttribute(`height`,`12`),r.classList.add(`pk-dropdown-item__prefix-icon`),r):null}var vn=E`
    @layer pk-component {
        :host {
            display: block;
            width: 100%;
            font-family: var(--pk-font-family);
        }

        .shell {
            overflow: hidden;
            border: var(--pk-input-border);
            border-radius: var(--pk-radius-md);
            background: #fff;
        }

        :host([invalid]) .shell,
        :host(:state(user-invalid)) .shell {
            border-color: var(--pk-color-rose-600);
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-bottom: 1px solid rgba(96, 125, 159, 0.4);
            background: #fff;
            box-shadow: 0 2px 3px rgba(49, 49, 93, 0.07);
        }

        .toolbar-item {
            display: inline-flex;
        }

        .toolbar-item pk-tooltip {
            display: contents;
        }

        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: var(--pk-radius-md);
            background: transparent;
            color: #1c2e36;
            cursor: pointer;
        }

        .toolbar-btn:hover:not(:disabled) {
            background: rgb(241, 245, 249);
        }

        .toolbar-btn[data-state='active'] {
            background: rgb(226, 232, 240);
        }

        .toolbar-btn:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .toolbar-btn svg {
            display: block;
            width: 1rem;
            height: 1rem;
            pointer-events: none;
        }

        .toolbar-btn--menu {
            width: auto;
            min-width: 2rem;
            gap: 0.125rem;
            padding: 0 0.375rem;
        }

        .toolbar-btn__trigger-label {
            font-size: 0.625rem;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .toolbar-btn__chevron {
            display: inline-flex;
            flex-shrink: 0;
            opacity: 0.7;
        }

        .toolbar-btn__chevron svg {
            width: 0.5rem;
            height: 0.5rem;
        }

        .toolbar-separator {
            align-self: center;
            flex-shrink: 0;
            width: 1px;
            height: 1.25rem;
            margin: 0 0.125rem;
            background: rgba(96, 125, 159, 0.35);
        }

        .toolbar-btn__label {
            font-size: 0.625rem;
            font-weight: 600;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .toolbar-btn--text-style {
            max-width: 9rem;
            padding-inline: 0.5rem;
        }

        .toolbar-btn__text-style-value {
            overflow: hidden;
            font-size: var(--pk-font-size-xs);
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .toolbar-btn--color .toolbar-btn__text-style-value {
            min-width: 1rem;
            border-bottom: 2px solid currentColor;
            font-weight: 700;
            text-align: center;
        }

        .text-style-swatch {
            display: inline-block;
            width: 0.875rem;
            height: 0.875rem;
            border: 1px solid rgba(15, 23, 42, 0.2);
            border-radius: 50%;
            background: var(--text-style-swatch, transparent);
        }

        .text-style-swatch[data-empty] {
            background: linear-gradient(to bottom right, transparent 45%, #ef4444 46%, #ef4444 54%, transparent 55%);
        }

        /* Full toolbar replace (slot=toolbar on host when hasCustomToolbar). */
        .toolbar ::slotted([slot='toolbar']) {
            display: contents;
        }

        /* Append lane inside the stock toolbar — one flex item per slotted root (same gap). */
        .toolbar ::slotted([slot='toolbar-end']) {
            display: inline-flex;
            align-items: center;
        }

        .editor-mount {
            position: relative;
        }

        .content-error {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-bottom: 1px solid var(--pk-color-rose-200);
            background: var(--pk-color-rose-50);
            color: var(--pk-color-rose-800);
            font-size: var(--pk-font-size-sm);
        }

        .mirror-input {
            display: none;
        }

        /* No trigger slot — collapse the host so it does not reserve shell space.
           Toolbar refresh while open is still gated by linkDialogBusy / open. */
        .link-dialog {
            display: contents;
        }

        .link-dialog__fields {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .link-bubble {
            position: absolute;
            z-index: 250;
            display: flex;
            align-items: center;
            gap: 0;
            width: max-content;
            max-width: calc(100% - 1rem);
            padding: 0;
            border: 0;
            border-radius: var(--pk-radius-md);
            background: #1c2e36;
            color: #fff;
            font-size: 12px;
            line-height: 1.5;
            overflow: visible;
            pointer-events: auto;
            white-space: nowrap;
        }

        .link-bubble__arrow {
            position: absolute;
            left: 50%;
            bottom: -0.25rem;
            width: 0.5rem;
            height: 0.5rem;
            background: #1c2e36;
            transform: translateX(-50%) rotate(45deg);
            pointer-events: none;
        }

        .link-bubble__url,
        .link-bubble__action {
            box-sizing: border-box;
            padding: 6px 8px;
            font-size: 12px;
            line-height: 1.5;
        }

        .link-bubble__url {
            display: inline-flex;
            align-items: center;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .link-bubble__divider {
            flex-shrink: 0;
            align-self: center;
            width: 1px;
            height: 12px;
            background: #616d73;
        }

        .link-bubble__action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            border: 0;
            border-radius: var(--pk-radius-sm);
            background: transparent;
            color: #fff;
            font-family: inherit;
            font-weight: inherit;
            white-space: nowrap;
            cursor: pointer;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            min-height: 0;
            transition: color 0.15s ease;
        }

        .link-bubble__action:hover {
            color: rgb(255 255 255 / 0.7);
        }
    }
`,J=class extends v{constructor(...e){super(...e),this.assumeInteractionOn=[`blur`,`input`],this.linkUrlInputId=_(`pk-tiptap-link-url`),this.linkTextInputId=_(`pk-tiptap-link-text`),this.host=new cn,this._value=null,this.buttons=`bold,italic`,this.toolbar=null,this.textStyleOptions=null,this.linkOptions=null,this.rows=4,this.placeholder=``,this.readonly=!1,this.invalid=!1,this.variableTagConfigure=null,this.invalidContentMessage=``,this.toolbarTooltips=!0,this.linkDialogBusy=!1,this.linkDialogSeed={},this.linkDialogTitle=`Insert Link`,this.linkDialogSubmitLabel=`Insert`,this.linkDialogMounted=!1,this.linkBubbleVisible=!1,this.linkBubbleHref=``,this.linkBubbleTop=0,this.linkBubbleLeft=0,this.hasCustomToolbar=!1,this.linkButtonId=_(`pk-tiptap-link-btn`),this.toolbarButtonIds=new Map,this.toolbarGroupIds=new Map,this.selectionListenerAttached=!1,this.defaultValue=`[]`,this.handleLinkBubbleEdit=e=>{e.preventDefault();let t=this.host.editor;if(!t)return;let n=et(t);this.linkBubbleVisible=!1,this.openLinkDialog({url:n.href,text:n.text,openInNewTab:n.openInNewTab,from:n.from,to:n.to})},this.handleLinkBubbleUnlink=e=>{e.preventDefault();let t=this.host.editor;t&&(nt(t),this.linkBubbleVisible=!1)},this.handleLinkDialogAfterHide=()=>{this.linkDialogBusy=!1,queueMicrotask(()=>{this.isConnected&&!this.linkDialog?.open&&this.requestUpdate()})},this.handleLinkUrlInput=()=>{this.updateLinkDialogSubmitState()},this.handleLinkDialogKeyDown=e=>{if(e.key!==`Enter`)return;let t=e.composedPath();t.some(e=>e instanceof HTMLElement&&e.localName===`pk-checkbox`)||t.some(e=>e instanceof HTMLElement&&e.localName===`pk-button`)||t.some(e=>e instanceof HTMLButtonElement)||(e.preventDefault(),e.stopPropagation(),this.submitLinkDialog())},this.handleLinkDialogSubmit=()=>{this.submitLinkDialog()}}static{this.styles=[d,vn,$t]}static get validators(){return[...super.validators,ne()]}get value(){return this.valueHasChanged?this._value??`[]`:this._value??this.defaultValue??`[]`}set value(e){let t=on(e);this._value!==t&&(this.valueHasChanged=!0,this._value=t)}get editor(){return this.host.editor}get toolbarNodes(){let e=Array.isArray(this.buttons)?this.buttons.join(`,`):this.buttons;return dn(this.toolbar,e).filter(e=>e.type!==`button`||e.name!==`variableTag`)}get buttonNames(){return this.toolbarNodes.filter(e=>e.type===`button`).map(e=>e.name)}toolbarHasLink(){return _t(this.toolbarNodes,`link`)}resolveLinkOptions(){return un(this.linkOptions)}get contentError(){return this.resolveContentError()}syncFormValue(){this.setValue(this.value||`[]`),this.input&&(this.input.value=this.value||`[]`)}resetToDefaultValue(){this.valueHasChanged=!1,this._value=null,this.syncEditorContent()}restoreFormState(e){typeof e==`string`&&(this.value=e,this.syncEditorContent())}connectedCallback(){super.connectedCallback(),this.hasCustomToolbar=!!this.querySelector(`[slot="toolbar"]`)}disconnectedCallback(){this.linkDialog?.forceOverlayReset(),this.linkDialogBusy=!1,this.host.destroy(),this.selectionListenerAttached=!1,super.disconnectedCallback()}updated(e){e.has(`value`)&&!e.has(`defaultValue`)&&this.syncEditorContent(),e.has(`defaultValue`)&&!this.valueHasChanged&&this.syncEditorContent(),(e.has(`disabled`)||e.has(`readonly`))&&this.host.editor?.setEditable(!this.disabled&&!this.readonly),super.updated(e)}firstUpdated(){queueMicrotask(()=>{this.mountEditor(),this.syncFormValue()})}resolveContentError(){let e=Ee(this.value);return e?this.invalidContentMessage||e:``}mountEditor(){let e=this.contentError?null:sn(this.value),t=`${Math.max(1,Number(this.rows||0))*24+32}px`;this.host.mount({element:this.editorMount,extensions:a({trailingCursorText:`⁠`,variableTagNodeView:Zt()}),content:e,editable:!this.disabled&&!this.readonly&&!this.contentError,trailingCursorText:`⁠`,minHeight:t,onUpdate:e=>{let t=an(e);this.value=t,this.input&&(this.input.value=t),this.syncFormValue(),this.emitValueChange(t)}}),queueMicrotask(()=>{this.attachSelectionListeners(),this.requestUpdate()})}attachSelectionListeners(){let e=this.host.editor;if(!e||this.selectionListenerAttached)return;let t=()=>{if(this.linkDialogBusy||this.linkDialog?.open){this.linkBubbleVisible=!1;return}this.updateLinkBubble(),queueMicrotask(()=>{this.isConnected&&!this.linkDialogBusy&&!this.linkDialog?.open&&this.requestUpdate()})};e.on(`selectionUpdate`,t),e.on(`transaction`,t),e.on(`focus`,t),e.on(`blur`,()=>{this.linkBubbleVisible=!1,this.requestUpdate()}),this.selectionListenerAttached=!0}updateLinkBubble(){let e=this.host.editor;if(!e||this.disabled||this.readonly||this.linkDialogBusy||this.linkDialog?.open||!e.isFocused||!e.isActive(`link`)){this.linkBubbleVisible=!1;return}let t=e.state.schema.marks.link,n=c(e.state.selection.$from,t);if(!n){this.linkBubbleVisible=!1;return}let i=r(e.view,n.from,n.to),a=this.editorMount.getBoundingClientRect();this.linkBubbleHref=String(e.getAttributes(`link`).href??``),this.linkBubbleTop=i.top-a.top-8,this.linkBubbleLeft=i.left-a.left+i.width/2,this.linkBubbleVisible=!0}renderLinkBubble(){return C`
            <div
                class="link-bubble"
                role="toolbar"
                aria-label="Link actions"
                tabindex="0"
                style=${`top:${this.linkBubbleTop}px;left:${this.linkBubbleLeft}px;transform:translate(-50%, -100%);`}
            >
                <span class="link-bubble__arrow" aria-hidden="true"></span>
                <span class="link-bubble__url">${this.linkBubbleHref}</span>
                <span class="link-bubble__divider" aria-hidden="true"></span>
                <button
                    type="button"
                    class="link-bubble__action"
                    @mousedown=${e=>e.preventDefault()}
                    @click=${this.handleLinkBubbleEdit}
                >Edit</button>
                <span class="link-bubble__divider" aria-hidden="true"></span>
                <button
                    type="button"
                    class="link-bubble__action"
                    @mousedown=${e=>e.preventDefault()}
                    @click=${this.handleLinkBubbleUnlink}
                >Unlink</button>
            </div>
        `}syncEditorContent(){let e=this.contentError?null:sn(this.value);this.host.setContent(e,{trailingCursorText:`⁠`}),this.input&&(this.input.value=this.value||`[]`)}emitValueChange(e){this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:e},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}handleToolbarCommand(e){let t=this.host.editor;t&&Ot(t,e)}openLinkMenu(){let e=this.host.editor;if(!e)return;let t=Qe(e),{from:n,to:r}=e.state.selection,i=this.shadowRoot?.querySelector(`pk-dropdown-menu[for="${this.linkButtonId}"]`);(async()=>{await i?.whenClosed(),this.openLinkDialog(n===r?void 0:{text:t,from:n,to:r})})()}openLinkDialog(e){let t=this.host.editor,n=e?.url??``;this.linkDialogBusy=!0,this.linkBubbleVisible=!1,this.linkDialogSeed={url:n,text:e?.text??``,openInNewTab:e?.openInNewTab??(t?$e(t):!1),from:e?.from,to:e?.to},this.linkDialogTitle=n.trim()?`Update Link`:`Insert Link`,this.linkDialogSubmitLabel=n.trim()?`Update`:`Insert`,this.linkDialogMounted||=!0,queueMicrotask(async()=>{await this.updateComplete,await this.linkDialog?.updateComplete,this.syncLinkDialogFields(),await this.linkDialog?.show()})}closeLinkDialog(){this.linkDialog?.hide(`close-button`)}syncLinkDialogFields(){let e=this.linkDialogSeed;this.linkUrlInput&&(this.linkUrlInput.value=e.url??``),this.linkTextInput&&(this.linkTextInput.value=e.text??``),this.linkNewTabCheckbox&&(this.linkNewTabCheckbox.checked=!!e.openInNewTab),this.updateLinkDialogSubmitState()}updateLinkDialogSubmitState(){let e=!!this.linkUrlInput?.value.trim();this.linkSubmitButton&&(this.linkSubmitButton.disabled=!e)}submitLinkDialog(){let e=this.host.editor,t=this.linkUrlInput?.value.trim()??``;e&&t&&(tt(e,{url:t,text:this.linkTextInput?.value??``,openInNewTab:!!this.linkNewTabCheckbox?.checked,from:this.linkDialogSeed.from,to:this.linkDialogSeed.to}),this.closeLinkDialog())}isLinkActive(){let e=this.host.editor;return e?it(e,`link`):!1}renderLinkDialog(){return C`
            <pk-dialog
                class="link-dialog"
                size="wide"
                label=${this.linkDialogTitle}
                @pk-after-hide=${this.handleLinkDialogAfterHide}
                @keydown=${this.handleLinkDialogKeyDown}
            >
                <div class="link-dialog__fields">
                    <pk-field label="URL" required .for=${this.linkUrlInputId}>
                        <pk-input
                            id=${this.linkUrlInputId}
                            class="link-dialog__url-input"
                            type="url"
                            placeholder="https://"
                            autofocus
                            @input=${this.handleLinkUrlInput}
                        ></pk-input>
                    </pk-field>
                    <pk-field label="Text" .for=${this.linkTextInputId}>
                        <pk-input
                            id=${this.linkTextInputId}
                            class="link-dialog__text-input"
                            type="text"
                        ></pk-input>
                    </pk-field>
                    <pk-checkbox>Open link in new tab</pk-checkbox>
                </div>
                <pk-button slot="footer" data-dialog-close>Cancel</pk-button>
                <pk-button
                    slot="footer"
                    class="link-dialog__submit"
                    variant="primary"
                    disabled
                    @click=${this.handleLinkDialogSubmit}
                >
                    ${this.linkDialogSubmitLabel}
                </pk-button>
            </pk-dialog>
        `}getToolbarButtonId(e){let t=this.toolbarButtonIds.get(e);return t||(t=_(`pk-tiptap-toolbar-${e}`),this.toolbarButtonIds.set(e,t)),t}getToolbarButtonLabel(e){return mn[e]??i(e)?.label??e}renderToolbarTooltip(e,t){return this.toolbarTooltips?C`
            <pk-tooltip for=${e} content=${t} placement="top"></pk-tooltip>
        `:S}renderToolbarButton(e){let t=this.host.editor,n=i(e);if(n?.isVisible&&(!t||!n.isVisible(t)))return S;let r=gn(e),a=this.getToolbarButtonLabel(e),o=this.getToolbarButtonId(e),s=t?it(t,e):!1;return C`
            <div class="toolbar-item">
                <button
                    id=${o}
                    type="button"
                    class="toolbar-btn"
                    aria-label=${a}
                    ?disabled=${this.disabled||this.readonly}
                    data-state=${s?`active`:S}
                    @click=${()=>this.handleToolbarCommand(e)}
                >
                    ${r?b(r):C`<span class="toolbar-btn__label">${a}</span>`}
                </button>
                ${this.renderToolbarTooltip(o,a)}
            </div>
        `}get textStyleToolbarConfig(){return s(this.textStyleOptions)}getTextStyleValue(e){let t=this.host.editor?.getAttributes(`textStyle`)[e];return typeof t==`string`&&t?t:null}setTextStyleValue(e,t){let n=this.host.editor;if(!n)return;let r=n.chain().focus();switch(e){case`fontFamily`:t?r.setFontFamily(t).run():r.unsetFontFamily().run();break;case`fontSize`:t?r.setFontSize(t).run():r.unsetFontSize().run();break;case`color`:t?r.setColor(t).run():r.unsetColor().run();break;case`backgroundColor`:t?r.setBackgroundColor(t).run():r.unsetBackgroundColor().run();break;case`lineHeight`:t?r.setLineHeight(t).run():r.unsetLineHeight().run()}}getTextStyleOptionLabel(e,t){return e.find(e=>e.value===t)?.label??e.find(e=>e.value===null)?.label??`Default`}renderTextStyleOptions(e,t,n=!1){let r=this.getTextStyleValue(e);return t.map(t=>C`
            <pk-dropdown-item
                type="radio"
                radio-group=${e}
                value=${t.value??``}
                ?checked=${t.value===r}
                @click=${()=>this.setTextStyleValue(e,t.value)}
            >
                ${n?C`
                    <span
                        slot="start"
                        class="text-style-swatch"
                        style=${t.value?`--text-style-swatch:${t.value}`:``}
                        data-empty=${t.value===null?``:S}
                    ></span>
                `:S}
                ${t.label}
            </pk-dropdown-item>
        `)}renderTextStyleToolbar(e){let t=this.textStyleToolbarConfig,n=this.getToolbarButtonId(e),r=``,i=S;e===`font-family`?(r=this.getTextStyleOptionLabel(t.fontFamilies,this.getTextStyleValue(`fontFamily`)),i=C`${this.renderTextStyleOptions(`fontFamily`,t.fontFamilies)}`):e===`font-size`?(r=this.getTextStyleOptionLabel(t.fontSizes,this.getTextStyleValue(`fontSize`)),i=C`${this.renderTextStyleOptions(`fontSize`,t.fontSizes)}`):e===`line-height`?(r=this.getTextStyleOptionLabel(t.lineHeights,this.getTextStyleValue(`lineHeight`)),i=C`${this.renderTextStyleOptions(`lineHeight`,t.lineHeights)}`):(r=`A`,i=C`
                <pk-dropdown-label>Text color</pk-dropdown-label>
                ${this.renderTextStyleOptions(`color`,t.textColors,!0)}
                <pk-dropdown-separator></pk-dropdown-separator>
                <pk-dropdown-label>Background color</pk-dropdown-label>
                ${this.renderTextStyleOptions(`backgroundColor`,t.backgroundColors,!0)}
            `);let a=this.getToolbarButtonLabel(e);return C`
            <div class="toolbar-item">
                <button
                    id=${n}
                    type="button"
                    class="toolbar-btn toolbar-btn--menu toolbar-btn--text-style ${e===`text-color`?`toolbar-btn--color`:``}"
                    aria-label=${a}
                    aria-haspopup="menu"
                    ?disabled=${this.disabled||this.readonly}
                >
                    <span class="toolbar-btn__text-style-value">${r}</span>
                    <span class="toolbar-btn__chevron">${b(fn)}</span>
                </button>
                ${this.renderToolbarTooltip(n,a)}
                <pk-dropdown-menu for=${n} placement="bottom-start">
                    ${i}
                </pk-dropdown-menu>
            </div>
        `}handleCraftLink(e){let t=this.host.editor;t&&Xe({config:e,elementSiteId:qe(this.resolveLinkOptions()),linkSelectorStorageKeyPrefix:this.linkSelectorStorageKeyPrefix,getSelectedText:()=>Qe(t),onSelect:({url:e,text:t})=>{this.openLinkDialog({url:e,text:t})},host:ln()})}getToolbarGroupId(e){let t=this.toolbarGroupIds.get(e);return t||(t=_(`pk-tiptap-toolbar-group`),this.toolbarGroupIds.set(e,t)),t}renderToolbarSeparator(){return C`<span class="toolbar-separator" aria-hidden="true"></span>`}renderGroupTriggerContent(e,t,n){let r=gn(n);return e.label&&t?C`
                <span class="toolbar-btn__trigger-label">${t}</span>
                <span class="toolbar-btn__chevron">${b(fn)}</span>
            `:C`
            ${r?b(r):C`<span class="toolbar-btn__trigger-label">${t}</span>`}
            <span class="toolbar-btn__chevron">${b(fn)}</span>
        `}renderToolbarGroup(e,t){let n=this.host.editor,r=this.getToolbarGroupId(t),a=n?At(n,e):{activeName:null,label:e.label??``,isActive:!1,icon:e.icon??dt(e)},o=Nt(e).filter(e=>{if(e.type===`separator`)return!0;let t=i(e.name);return!t?.isVisible||!!(n&&t.isVisible(n))});if(!o.some(e=>e.type===`item`))return S;let s=e.label??(ut(e.preset)?`Headings`:lt(e.preset)?`Formatting`:e.preset===`lists`?`Lists`:e.preset===`align`?`Alignment`:`More`);return C`
            <div class="toolbar-item">
                <button
                    id=${r}
                    type="button"
                    class="toolbar-btn toolbar-btn--menu"
                    aria-label=${s}
                    aria-haspopup="menu"
                    ?disabled=${this.disabled||this.readonly}
                    data-state=${a.isActive?`active`:S}
                >
                    ${this.renderGroupTriggerContent(e,a.label,a.icon)}
                </button>
                ${this.renderToolbarTooltip(r,s)}
                <pk-dropdown-menu for=${r} placement="bottom-start">
                    ${o.map((e,r)=>{if(e.type===`separator`)return C`<pk-dropdown-separator key=${`${t}-sep-${r}`}></pk-dropdown-separator>`;let i=e.name,a=n?z(n,i):!1,o=_n(i),s=hn(i);return C`
                            <pk-dropdown-item
                                key=${`${t}-${i}-${r}`}
                                ?data-active=${a||S}
                                @click=${()=>this.handleToolbarCommand(i)}
                            >
                                ${o??S}
                                ${s}
                            </pk-dropdown-item>
                        `})}
                </pk-dropdown-menu>
            </div>
        `}renderToolbarNode(e,t){return e.type===`separator`?this.renderToolbarSeparator():e.type===`group`?this.renderToolbarGroup(e.group,`group-${t}`):e.name===`link`?this.renderLinkToolbarButton():[`font-family`,`font-size`,`text-color`,`line-height`].includes(e.name)?this.renderTextStyleToolbar(e.name):this.renderToolbarButton(e.name)}renderDefaultToolbar(){return C`
            <div class="toolbar" part="toolbar">
                ${this.toolbarNodes.map((e,t)=>this.renderToolbarNode(e,t))}
                <slot name="toolbar-end"></slot>
            </div>
        `}renderLinkToolbarButton(){let e=this.host.editor,t=Ke(this.resolveLinkOptions()),n=this.getToolbarButtonLabel(`link`),r=this.isLinkActive();return C`
            <div class="toolbar-item">
                <button
                    id=${this.linkButtonId}
                    type="button"
                    class="toolbar-btn"
                    aria-label=${n}
                    aria-haspopup="menu"
                    ?disabled=${this.disabled||this.readonly}
                    data-state=${r?`active`:S}
                >
                    ${b(pn.link)}
                </button>
                ${this.renderToolbarTooltip(this.linkButtonId,n)}
                <pk-dropdown-menu for=${this.linkButtonId} placement="bottom-start">
                    ${t.map(e=>C`
                        <pk-dropdown-item @click=${()=>this.handleCraftLink(e)}>
                            ${e.optionTitle}
                        </pk-dropdown-item>
                    `)}
                    ${t.length>0?C`<pk-dropdown-separator></pk-dropdown-separator>`:S}
                    <pk-dropdown-item @click=${()=>this.openLinkMenu()}>Insert Link</pk-dropdown-item>
                    <pk-dropdown-item
                        ?disabled=${!r}
                        @click=${()=>e&&nt(e)}
                    >Unlink</pk-dropdown-item>
                </pk-dropdown-menu>
            </div>
        `}render(){return C`
            <div class="shell" part="shell">
                ${this.hasCustomToolbar?C`<slot name="toolbar"></slot>`:this.renderDefaultToolbar()}

                <div class="editor-mount" part="editor">
                    ${this.contentError?C`
                        <div class="content-error">
                            <span>${b(j(y.triangleExclamation))}</span>
                            <span>${this.contentError}</span>
                        </div>
                    `:S}
                    ${this.toolbarHasLink()&&this.linkBubbleVisible?this.renderLinkBubble():S}
                </div>

                <input class="mirror-input" type="text" .value=${this.value} readonly tabindex="-1" aria-hidden="true" />
                ${this.toolbarHasLink()&&this.linkDialogMounted?this.renderLinkDialog():S}
            </div>
        `}};k([D(`.editor-mount`)],J.prototype,`editorMount`,void 0),k([D(`.mirror-input`)],J.prototype,`input`,void 0),k([D(`.link-dialog`)],J.prototype,`linkDialog`,void 0),k([D(`pk-input.link-dialog__url-input`)],J.prototype,`linkUrlInput`,void 0),k([D(`pk-input.link-dialog__text-input`)],J.prototype,`linkTextInput`,void 0),k([D(`pk-checkbox`)],J.prototype,`linkNewTabCheckbox`,void 0),k([D(`pk-button.link-dialog__submit`)],J.prototype,`linkSubmitButton`,void 0),k([T({attribute:`buttons`,converter:{fromAttribute:e=>e??`bold,italic`,toAttribute:e=>e==null?`bold,italic`:Array.isArray(e)?e.join(`,`):e}})],J.prototype,`buttons`,void 0),k([T({attribute:`toolbar`,converter:{fromAttribute:e=>e,toAttribute:e=>e==null?null:typeof e==`string`?e:JSON.stringify(e)}})],J.prototype,`toolbar`,void 0),k([T({attribute:`text-style-options`,converter:{fromAttribute:e=>{if(!e)return null;try{return JSON.parse(e)}catch{return null}},toAttribute:e=>e?JSON.stringify(e):null}})],J.prototype,`textStyleOptions`,void 0),k([T({attribute:`link-options`})],J.prototype,`linkOptions`,void 0),k([T({attribute:`link-selector-storage-key-prefix`})],J.prototype,`linkSelectorStorageKeyPrefix`,void 0),k([T({type:Number})],J.prototype,`rows`,void 0),k([T()],J.prototype,`placeholder`,void 0),k([T({type:Boolean,reflect:!0})],J.prototype,`readonly`,void 0),k([T({type:Boolean,reflect:!0})],J.prototype,`invalid`,void 0),k([T({attribute:!1})],J.prototype,`variableTagConfigure`,void 0),k([T({attribute:`invalid-content-message`})],J.prototype,`invalidContentMessage`,void 0),k([T({type:Boolean,attribute:`toolbar-tooltips`})],J.prototype,`toolbarTooltips`,void 0),k([x()],J.prototype,`linkDialogTitle`,void 0),k([x()],J.prototype,`linkDialogSubmitLabel`,void 0),k([x()],J.prototype,`linkDialogMounted`,void 0),k([x()],J.prototype,`linkBubbleVisible`,void 0),k([x()],J.prototype,`linkBubbleHref`,void 0),k([x()],J.prototype,`linkBubbleTop`,void 0),k([x()],J.prototype,`linkBubbleLeft`,void 0),k([x()],J.prototype,`value`,null),k([T({attribute:`value`,reflect:!0})],J.prototype,`defaultValue`,void 0),J=k([A(`pk-tiptap-editor`)],J);var yn=E`
    @layer pk-component {
        :host {
            display: block;
            width: 100%;
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: 1.4;
        }

        /* Same chrome as pk-input .input — border/radius live on the shell. */
        .shell {
            box-sizing: border-box;
            border: var(--pk-input-border);
            border-radius: var(--pk-input-border-radius, var(--pk-radius-sm));
            background: var(--pk-input-bg);
            overflow: hidden;
        }

        :host([invalid]) .shell,
        :host(:state(user-invalid)) .shell {
            border-color: var(--pk-color-rose-600);
        }

        /* Editable-table cells (v1): flush TipTap shell — chips sit in the row, not a boxed field. */
        :host([fit-cell]),
        :host([data-editable-table-input]) {
            display: block;
            height: 100%;
            min-height: 100%;
            box-sizing: border-box;
        }

        :host([fit-cell]) .shell,
        :host([data-editable-table-input]) .shell {
            border: none;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            height: 100%;
            min-height: 100%;
        }

        /* Read-only display (lists, picker cards): v1 skipped input chrome when readOnly. */
        :host([readonly]) .shell {
            border: none;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            overflow: visible;
        }

        :host([fit-cell][invalid]) .shell,
        :host([fit-cell]:state(user-invalid)) .shell,
        :host([data-editable-table-input][invalid]) .shell,
        :host([data-editable-table-input]:state(user-invalid)) .shell {
            border: none;
            box-shadow: inset 0 0 0 1px var(--pk-color-rose-600);
        }

        .editor-mount {
            display: block;
            min-width: 0;
        }

        .mirror-input {
            display: none;
        }
    }
`,Y=class extends v{constructor(...e){super(...e),this.assumeInteractionOn=[`blur`,`input`],this.editor=null,this.lastEmitted=null,this.suppressUpdateEmission=!1,this._value=null,this.variableCategories={},this.variableTagConfigure=null,this.readonly=!1,this.invalid=!1,this.fitCell=!1,this.defaultValue=``}static{this.styles=[d,yn,en]}static get validators(){return[...super.validators,ne()]}get value(){return this.valueHasChanged?this._value??``:this._value??this.defaultValue??``}set value(e){let t=e??``;this._value!==t&&(this.valueHasChanged=!0,this._value=t)}get editorInstance(){return this.editor}resolveVariableLists(){let e=Fe(Object.values(this.variableCategories??{}).flatMap(e=>Array.isArray(e)?e:[]));return{topLevel:e,all:Ie(e)}}resolveEditorContent(){let{topLevel:e,all:t}=this.resolveVariableLists();return Ue(this.value,e,t)}syncFormValue(){this.setValue(this.value||``),this.input&&(this.input.value=this.value||``)}resetToDefaultValue(){this.valueHasChanged=!1,this._value=null,this.syncEditorContent(!0)}restoreFormState(e){typeof e==`string`&&(this.value=e,this.syncEditorContent(!0))}disconnectedCallback(){this.editor?.destroy(),this.editor=null,super.disconnectedCallback()}updated(e){e.has(`value`)&&!e.has(`defaultValue`)&&this.syncEditorContent(),e.has(`defaultValue`)&&!this.valueHasChanged&&this.syncEditorContent(),e.has(`variableCategories`)&&this.editor&&this.syncEditorContent(!0),(e.has(`disabled`)||e.has(`readonly`))&&this.editor?.setEditable(!this.disabled&&!this.readonly),super.updated(e)}firstUpdated(){this.mountEditor(),this.syncFormValue()}mountEditor(){let e=this.resolveEditorContent();this.editor=new n({element:this.editorMount,extensions:o({variableTagNodeView:Zt()}),content:e,editable:!this.disabled&&!this.readonly,onUpdate:({editor:e})=>{let t=We(e.getJSON().content);this.lastEmitted=t,!this.suppressUpdateEmission&&(this.value=t,this.input&&(this.input.value=t),this.syncFormValue(),this.emitValueChange(t))}})}syncEditorContent(e=!1){if(!this.editor||!e&&!this.readonly&&this.editor.isFocused)return;let t=this.resolveEditorContent(),n=this.editor.getJSON();if(JSON.stringify(t)!==JSON.stringify(n)&&(e||this.lastEmitted!==this.value)){this.suppressUpdateEmission=!0;try{this.editor.commands.setContent(t??{type:`doc`,content:[]})}finally{this.suppressUpdateEmission=!1}}}emitValueChange(e){this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:e},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}render(){return C`
            <div class="shell" part="shell">
                <div class="editor-mount" part="editor"></div>
                <input class="mirror-input" type="text" .value=${this.value} readonly tabindex="-1" aria-hidden="true" />
            </div>
        `}};k([D(`.editor-mount`)],Y.prototype,`editorMount`,void 0),k([D(`.mirror-input`)],Y.prototype,`input`,void 0),k([T({attribute:!1})],Y.prototype,`variableCategories`,void 0),k([T({attribute:!1})],Y.prototype,`variableTagConfigure`,void 0),k([T({type:Boolean,reflect:!0})],Y.prototype,`readonly`,void 0),k([T({type:Boolean,reflect:!0})],Y.prototype,`invalid`,void 0),k([T({type:Boolean,reflect:!0,attribute:`fit-cell`})],Y.prototype,`fitCell`,void 0),k([x()],Y.prototype,`value`,null),k([T({attribute:`value`,reflect:!0})],Y.prototype,`defaultValue`,void 0),Y=k([A(`pk-tiptap-input`)],Y);var bn=m({tagName:`pk-tiptap-editor`,elementClass:J,react:V.default,events:{onPkChange:`pk-change`,onInput:`input`,onPkVariableTagConfigure:`pk-variable-tag-configure`}}),xn=m({tagName:`pk-tiptap-input`,elementClass:Y,react:V.default,events:{onPkChange:`pk-change`,onInput:`input`,onPkVariableTagConfigure:`pk-variable-tag-configure`}}),Sn=m({tagName:`pk-tiptap-content`,elementClass:q,react:V.default});function Cn(e){return e==null?`[]`:typeof e==`string`?e:Array.isArray(e)?JSON.stringify(e):`[]`}function wn(e){let t=e.detail;return typeof t?.value==`string`?t.value:void 0}function Tn({value:e,onChange:t,onPkChange:n,disabled:r,invalid:i,readonly:a,readOnly:o,...s}){let c=!!(a??o);return(0,H.jsx)(bn,{...s,value:Cn(e),onPkChange:e=>{n?.(e);let r=wn(e);r!==void 0&&t?.(r)},...h([`disabled`,`invalid`,`readonly`],{disabled:r,invalid:i,readonly:c})})}function En({onChange:e,onPkChange:t,disabled:n,invalid:r,readonly:i,readOnly:a,fitCell:o,...s}){let c=!!(i??a);return(0,H.jsx)(xn,{...s,onPkChange:n=>{t?.(n);let r=wn(n);r!==void 0&&e?.(r)},...h([`disabled`,`invalid`,`readonly`,`fitCell`],{disabled:n,invalid:r,readonly:c,fitCell:o})})}var Dn=Sn,On=class extends Event{constructor(e){super(`pk-create`,{bubbles:!0,cancelable:!0,composed:!0}),this.inputValue=e}},kn=[te,E`
    ${Ce}
    @layer pk-component {
        :host {
            display: inline-block;
            position: relative;
            width: fit-content;
            max-width: 100%;
            align-self: flex-start;
            flex: none;
            color: var(--pk-color-gray-700);
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: var(--pk-line-height);
            --pk-combobox-min-height: 0;
            --pk-combobox-trigger-min-height: var(--pk-btn-height-default);
            --pk-combobox-padding-block: 6px;
            --pk-combobox-padding-inline: 10px;
            --pk-combobox-font-size: var(--pk-font-size-base);
            --pk-combobox-line-height: var(--pk-input-control-line-height, 1.25rem);
            --pk-combobox-decoration-size: 0.875rem;
            --pk-select-item-min-height: 0;
            --pk-select-item-padding-block: 6px;
            --pk-select-item-padding-inline: 10px;
            --pk-select-item-padding-inline-end: 2rem;
            --pk-select-item-font-size: 14px;
            --pk-select-item-line-height: 1.4;
            --pk-select-item-indicator-size: 0.75rem;
            --pk-select-item-indicator-inset: 0.5rem;
            /* v1 ComboboxLabel default: text-xs → 12px (was 11px). */
            --pk-select-group-label-font-size: 12px;
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
            --pk-combobox-control-gap: 0.5rem;
            gap: var(--pk-combobox-control-gap);
            /* Fill the host — consumers set min-width/width on :host; fit-content here
               left a dead hit strip beside the painted field (same class of bug as dropdown). */
            width: 100%;
            max-width: 100%;
            min-width: 0;
            min-height: var(--pk-combobox-min-height);
            margin: 0;
            padding: var(--pk-combobox-padding-block) var(--pk-combobox-padding-inline);
            border: 1px solid transparent;
            border-radius: var(--pk-radius-lg);
            --pk-combobox-fill: var(--pk-color-slate-250);
            --pk-combobox-fill-hover: var(--pk-color-slate-300);
            background: var(--pk-combobox-fill);
            color: var(--pk-color-gray-700);
            font: inherit;
            font-size: var(--pk-combobox-font-size);
            line-height: var(--pk-input-control-line-height, 1.25rem);
            white-space: nowrap;
            cursor: text;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
        }

        .control[data-popup-open] {
            border-color: var(--pk-color-sky-600);
            box-shadow: var(--pk-input-focus-shadow);
            background: var(--pk-color-white);
        }

        :host(:not([disabled])) .control:hover:not(.is-disabled) {
            background: var(--pk-combobox-fill-hover);
        }

        :host(:not([disabled])) .control[data-popup-open]:hover:not(.is-disabled),
        :host(:not([disabled])) .control[data-popup-open]:focus-within:not(.is-disabled) {
            border-color: var(--pk-color-sky-600);
            box-shadow: var(--pk-input-focus-shadow);
            background: var(--pk-color-white);
        }

        :host(:not([invalid]):not(:state(user-invalid))) .control:focus-within,
        :host(:not([invalid]):not(:state(user-invalid))[data-state='focus-visible']) .control {
            border-color: var(--pk-color-sky-600);
            box-shadow: var(--pk-input-focus-shadow);
            background: var(--pk-color-white);
        }

        .control.is-disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .control-start,
        .control-end {
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
            line-height: 0;
            color: var(--pk-color-gray-600);
        }

        slot[name='start']::slotted(svg),
        slot[name='end']::slotted(svg) {
            width: var(--pk-combobox-decoration-size);
            height: var(--pk-combobox-decoration-size);
        }

        .combobox-input {
            flex: 1 1 auto;
            width: 100%;
            min-width: 0;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            line-height: var(--pk-input-control-line-height, 1.25rem);
            outline: none;
        }

        .combobox-input::placeholder {
            color: currentColor;
        }

        :host([data-has-value]) .combobox-input::placeholder,
        .control[data-popup-open] .combobox-input::placeholder,
        .control:focus-within .combobox-input::placeholder {
            color: var(--pk-color-gray-400);
        }

        /* Expand/clear: the button box IS the hit target. Negative margins cancel the
           control padding / half-gap in layout, while matching extra width/height keeps
           the painted (and clickable) box flush to the field edge — so flex centering
           places the glyph in the middle of the real hit area. */
        .icon-button,
        .clear-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            align-self: stretch;
            box-sizing: border-box;
            width: calc(var(--pk-combobox-decoration-size) + var(--pk-combobox-control-gap));
            height: auto;
            min-height: var(--pk-combobox-decoration-size);
            margin-block: calc(-1 * var(--pk-combobox-padding-block));
            margin-inline: calc(-0.5 * var(--pk-combobox-control-gap));
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: var(--pk-color-gray-600);
            cursor: pointer;
            outline: none;
        }

        /* Trailing control absorbs the control's inline-end padding into its hit box,
           with the same 4px glyph inset as pk-copy-button[slot=end]. */
        .control > .expand-button,
        .control > .clear-button:last-child {
            width: calc(
                var(--pk-combobox-decoration-size) + (0.5 * var(--pk-combobox-control-gap)) +
                    var(--pk-combobox-padding-inline)
            );
            margin-inline-start: calc(-0.5 * var(--pk-combobox-control-gap));
            margin-inline-end: calc(-1 * var(--pk-combobox-padding-inline) + 4px);
        }

        .icon-button:disabled,
        .clear-button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            line-height: 0;
            pointer-events: none;
            color: var(--pk-color-gray-600);
        }

        .icon svg {
            display: block;
            width: 0.75rem;
            height: 0.75rem;
        }

        .clear-button-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
            pointer-events: none;
        }

        .clear-button-icon svg {
            display: block;
            width: 0.75rem;
            height: 0.75rem;
        }

        .control--multiple {
            --pk-combobox-control-gap: 0.25rem;
            flex-wrap: wrap;
            align-items: center;
            align-content: center;
            width: 100%;
            max-width: 100%;
            height: auto;
            min-height: 0;
            padding: var(--pk-combobox-padding-block) var(--pk-combobox-padding-inline);
            gap: var(--pk-combobox-control-gap);
            border: var(--pk-input-border);
            border-radius: var(--pk-input-border-radius);
            background: var(--pk-input-bg);
            cursor: text;
        }

        :host([multiple][width='full']) .control--multiple {
            width: 100%;
        }

        :host([multiple]) .control:hover:not(.is-disabled) {
            background: var(--pk-input-bg);
        }

        :host([multiple]:not([invalid]):not(:state(user-invalid))) .control[data-popup-open],
        :host([multiple]:not([invalid]):not(:state(user-invalid))) .control:focus-within,
        :host([multiple]:not([invalid]):not(:state(user-invalid))[data-state='focus-visible']) .control {
            border-color: var(--pk-color-sky-600);
            box-shadow: var(--pk-input-focus-shadow);
            background: var(--pk-input-bg);
        }

        .chips {
            display: flex;
            flex: 0 1 auto;
            flex-wrap: wrap;
            gap: 0.25rem;
            align-items: center;
            min-width: 0;
        }

        .tag {
            /* v1 ComboboxChip: text-xs + py-[2px] → 20px; face color gray-700. */
            --pk-combobox-tag-height: 20px;
            --pk-combobox-tag-padding-inline-start: 6px;
            --pk-combobox-tag-remove-width: 1.25rem;
            display: inline-flex;
            box-sizing: border-box;
            align-items: center;
            justify-content: center;
            gap: 0.125rem;
            max-width: 100%;
            height: var(--pk-combobox-tag-height);
            padding-block: 0;
            padding-inline: var(--pk-combobox-tag-padding-inline-start) 0;
            border-radius: var(--pk-radius-sm);
            background: var(--pk-color-slate-200);
            color: var(--pk-color-gray-700);
            font-size: 12px;
            font-weight: 500;
            line-height: 1rem;
            white-space: nowrap;
        }

        .tag-label {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Chip remove: fill the chip end so the glyph centers in the real target. */
        .tag-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            align-self: stretch;
            box-sizing: border-box;
            width: var(--pk-combobox-tag-remove-width);
            height: auto;
            min-height: 0;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: inherit;
            cursor: pointer;
            opacity: 0.5;
            outline: none;
        }

        .tag-remove:hover {
            opacity: 1;
        }

        .tag-remove-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
            pointer-events: none;
        }

        .tag-remove-icon svg {
            display: block;
            width: 0.625rem;
            height: 0.625rem;
        }

        .combobox-input--inline {
            flex: 1 1 4rem;
            width: auto;
            min-width: 4rem;
            padding: 0;
        }

        :host([multiple]) .control:not([data-popup-open]):not(:focus-within) {
            background: var(--pk-input-bg);
        }

        :host([multiple]) .combobox-input::placeholder {
            color: var(--pk-color-gray-400);
        }

        .create-option {
            display: flex;
            align-items: center;
            width: 100%;
            margin: 0;
            min-height: var(--pk-select-item-min-height);
            padding-block: var(--pk-select-item-padding-block);
            padding-inline: var(--pk-select-item-padding-inline);
            border: 0;
            background: transparent;
            color: var(--pk-color-gray-700);
            font: inherit;
            font-size: var(--pk-select-item-font-size);
            line-height: var(--pk-select-item-line-height);
            text-align: left;
            cursor: pointer;
            outline: none;
            box-sizing: border-box;
        }

        .create-option:hover,
        .create-option.is-highlighted {
            background: var(--pk-color-slate-100);
        }

        .value-input {
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

        .panel ::slotted(pk-separator) {
            margin: 4px 0;
        }

        .panel {
            width: max-content;
            min-width: var(--pk-combobox-anchor-width, 8rem);
            padding: 0;
            border: 0;
            border-radius: var(--pk-radius-md);
            background: var(--pk-color-white);
            box-shadow: var(--pk-shadow-popup);
            color: var(--pk-color-gray-700);
            outline: none;
        }

        .panel-body {
            max-height: 16rem;
            overflow: auto;
        }

        .panel--popup {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .panel--popup .panel-body {
            flex: 1 1 auto;
            min-height: 0;
        }

        :host([popup-mode]) .control--popup {
            display: inline-flex;
            width: 100%;
            max-width: 100%;
            min-height: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            gap: 0;
            cursor: default;
        }

        :host([popup-mode]) .control--popup:hover:not(.is-disabled) {
            background: transparent;
        }

        /* Popup mode paints chrome on the trigger / panel input — do not keep the
           shared .control[data-popup-open] focus ring around the closed-state button. */
        :host([popup-mode]) .control--popup[data-popup-open],
        :host([popup-mode]) .control--popup[data-popup-open]:hover:not(.is-disabled),
        :host([popup-mode]) .control--popup[data-popup-open]:focus-within:not(.is-disabled),
        :host([popup-mode]:not([invalid]):not(:state(user-invalid))) .control--popup:focus-within,
        :host([popup-mode]:not([invalid]):not(:state(user-invalid))[data-state='focus-visible']) .control--popup {
            border: 0;
            box-shadow: none;
            background: transparent;
        }

        .popup-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            min-width: 12rem;
            max-width: 100%;
            min-height: var(--pk-combobox-trigger-min-height);
            margin: 0;
            padding: var(--pk-combobox-padding-block) var(--pk-combobox-padding-inline);
            border: 1px solid transparent;
            border-radius: var(--pk-input-border-radius);
            /* Match input-mode fill / v1 default Button — not a white outlined field. */
            background: var(--pk-combobox-fill, var(--pk-color-slate-250));
            color: var(--pk-color-gray-700);
            font: inherit;
            font-size: var(--pk-combobox-font-size);
            font-weight: 400;
            line-height: var(--pk-combobox-line-height);
            text-align: left;
            white-space: nowrap;
            cursor: pointer;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
        }

        .popup-trigger:hover:not(:disabled) {
            background: var(--pk-combobox-fill-hover, var(--pk-color-slate-300));
        }

        .popup-trigger:active:not(:disabled),
        .control--popup[data-popup-open] .popup-trigger:not(:disabled) {
            background: var(--pk-combobox-fill-hover, var(--pk-color-slate-300));
        }

        .control--popup[data-popup-open] .popup-trigger:not(:disabled) {
            border-color: transparent;
            box-shadow: none;
        }

        .control--popup:not([data-popup-open]) .popup-trigger:focus-visible,
        :host([data-state='focus-visible']) .control--popup:not([data-popup-open]) .popup-trigger {
            border-color: var(--pk-color-sky-600);
            box-shadow: var(--pk-input-focus-shadow);
            background: var(--pk-color-white);
        }

        .popup-trigger:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .popup-trigger-value {
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .popup-trigger-value.is-placeholder {
            /* Trigger label is button text, not an input placeholder — keep it readable. */
            color: var(--pk-color-gray-700);
        }

        .popup-trigger-icon {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            line-height: 0;
        }

        .popup-trigger-icon svg {
            display: block;
            width: 0.75rem;
            height: 0.75rem;
        }

        .panel-search {
            flex: none;
            padding: 0.25rem;
        }

        .panel-input {
            display: block;
            width: 100%;
            min-width: 0;
            margin: 0;
            padding: 6px 8px;
            border: var(--pk-input-border);
            border-radius: var(--pk-input-border-radius);
            background: color-mix(in srgb, var(--pk-input-bg) 30%, transparent);
            color: var(--pk-color-gray-700);
            font: inherit;
            font-size: var(--pk-combobox-font-size);
            line-height: 1.4;
            outline: none;
            box-sizing: border-box;
        }

        .panel-input::placeholder {
            color: var(--pk-color-gray-400);
        }

        .panel-input:focus {
            border-color: var(--pk-color-sky-600);
            box-shadow: var(--pk-input-focus-shadow);
            background: var(--pk-color-white);
        }

        :host([popup-mode][size='xs']) .popup-trigger-icon svg {
            width: 0.625rem;
            height: 0.625rem;
        }

        :host([popup-mode][size='xs']) .panel-input {
            padding: 4px 8px;
            font-size: 11px;
        }

        :host([popup-mode][size='sm']) .panel-input {
            font-size: 12px;
        }

        :host([popup-mode][size='lg']) .panel-input {
            padding-block: 8px;
            padding-inline: 12px;
        }

        :host([popup-mode][width='full']) .popup-trigger {
            width: 100%;
        }

        .panel:not([data-open]):not(.closing) {
            opacity: 0;
            pointer-events: none;
        }

        .panel[data-open]:not(.closing) {
            opacity: 1;
            pointer-events: auto;
        }

        .panel[hidden] {
            display: none !important;
        }

        .empty {
            display: flex;
            align-items: center;
            margin: 0;
            min-height: var(--pk-select-item-min-height, var(--pk-input-height));
            padding-block: var(--pk-select-item-padding-block);
            padding-inline: var(--pk-select-item-padding-inline);
            border: var(--pk-select-trigger-border-width, 1px) solid transparent;
            box-sizing: border-box;
            color: var(--pk-color-gray-500);
            font-size: var(--pk-select-item-font-size);
            line-height: var(--pk-select-item-line-height);
        }

        .async-status {
            display: flex;
            align-items: center;
            margin: 0;
            min-height: var(--pk-select-item-min-height, var(--pk-input-height));
            padding-block: var(--pk-select-item-padding-block);
            padding-inline: var(--pk-select-item-padding-inline);
            border: var(--pk-select-trigger-border-width, 1px) solid transparent;
            box-sizing: border-box;
            color: var(--pk-color-gray-500);
            font-size: var(--pk-select-item-font-size);
            line-height: var(--pk-select-item-line-height);
        }

        :host([invalid]) .control,
        :host(:state(user-invalid)) .control {
            border-color: var(--pk-color-rose-600);
        }

        :host([multiple][invalid]) .control,
        :host([multiple]:state(user-invalid)) .control {
            border-color: var(--pk-color-rose-600);
        }

        :host([invalid]) .control:focus-within,
        :host([invalid][data-state='focus-visible']) .control,
        :host(:state(user-invalid)) .control:focus-within,
        :host(:state(user-invalid)[data-state='focus-visible']) .control {
            border-color: var(--pk-color-rose-600);
            box-shadow: var(--pk-input-invalid-focus-shadow);
        }

        :host([size='xs']) {
            --pk-combobox-min-height: 0;
            --pk-combobox-trigger-min-height: var(--pk-btn-height-xs);
            --pk-combobox-padding-block: 4px;
            --pk-combobox-padding-inline: 8px;
            --pk-combobox-font-size: 11px;
            --pk-combobox-decoration-size: 0.625rem;
            --pk-select-item-min-height: 0;
            --pk-select-item-padding-block: 4px;
            --pk-select-item-padding-inline: 8px;
            --pk-select-item-padding-inline-end: 1.75rem;
            --pk-select-item-font-size: 11px;
            --pk-select-item-indicator-size: 0.75rem;
            --pk-select-item-indicator-inset: 0.5rem;
            /* v1 ComboboxLabel xs: text-[11px] */
            --pk-select-group-label-font-size: 11px;
        }

        :host([size='xs']) .control {
            border-radius: var(--pk-radius-sm);
        }

        :host([size='xs']) .icon svg,
        :host([size='xs']) .clear-button-icon svg {
            width: 0.625rem;
            height: 0.625rem;
        }

        :host([size='sm']) {
            --pk-combobox-min-height: 0;
            --pk-combobox-trigger-min-height: var(--pk-btn-height-sm);
            --pk-combobox-padding-block: 6px;
            --pk-combobox-padding-inline: 10px;
            --pk-combobox-font-size: 12px;
            --pk-combobox-decoration-size: 0.6875rem;
            --pk-select-item-min-height: 0;
            --pk-select-item-padding-block: 6px;
            --pk-select-item-padding-inline: 10px;
            --pk-select-item-padding-inline-end: 1.75rem;
            --pk-select-item-font-size: 12px;
            --pk-select-item-indicator-inset: 0.625rem;
            /* v1 ComboboxLabel sm: text-[12px] — empty dropzone field picker uses sm. */
            --pk-select-group-label-font-size: 12px;
        }

        :host([size='sm']) .control {
            border-radius: var(--pk-radius-md);
        }

        :host([size='sm']) .popup-trigger {
            border-radius: var(--pk-radius-md);
        }

        :host([size='sm']) .icon svg,
        :host([size='sm']) .clear-button-icon svg {
            width: 0.6875rem;
            height: 0.6875rem;
        }

        :host([size='lg']) {
            --pk-combobox-trigger-min-height: var(--pk-btn-height-lg);
            --pk-combobox-padding-block: 8px;
            --pk-combobox-padding-inline: 12px;
            --pk-combobox-font-size: var(--pk-font-size-base);
            --pk-combobox-decoration-size: 1rem;
            --pk-select-item-padding-block: 8px;
            --pk-select-item-padding-inline: 12px;
            --pk-select-item-font-size: 14px;
            --pk-select-item-indicator-inset: 0.75rem;
            /* v1 ComboboxLabel lg: text-sm → 14px */
            --pk-select-group-label-font-size: 14px;
        }

        :host([size='xl']) {
            --pk-combobox-trigger-min-height: var(--pk-btn-height-xl);
            --pk-combobox-padding-block: 10px;
            --pk-combobox-padding-inline: 14px;
            --pk-combobox-font-size: var(--pk-font-size-base);
            --pk-combobox-decoration-size: 1.125rem;
            --pk-select-item-padding-block: 10px;
            --pk-select-item-padding-inline: 14px;
            --pk-select-item-padding-inline-end: 2.25rem;
            --pk-select-item-font-size: 14px;
            --pk-select-item-indicator-inset: 0.875rem;
            /* v1 ComboboxLabel xl: text-base → 16px */
            --pk-select-group-label-font-size: 16px;
        }

        :host([size='xl']) .icon svg,
        :host([size='xl']) .clear-button-icon svg {
            width: 0.875rem;
            height: 0.875rem;
        }
    }
`],An=j(y.chevronDown),jn=j(y.xmark),X=class extends v{constructor(...e){super(...e),this.assumeInteractionOn=[`blur`,`input`],this.open=!1,this.multiple=!1,this.placement=`bottom-start`,this.sideOffset=6,this.clearable=!1,this.withClear=!1,this.allowCreate=!1,this.allowCustomValue=!1,this.autoHighlight=!1,this.popupMode=!1,this.searchPlaceholder=`Search`,this.invalid=!1,this.size=`default`,this.placeholder=``,this.emptyMessage=`No options found.`,this.value=``,this.defaultValue=``,this.values=[],this.defaultValues=[],this.label=``,this.instructions=``,this.ariaLabel=null,this.loopFocus=!0,this.filter=null,this.async=!1,this.loadingMessage=`Searching…`,this.startTypingMessage=`Start typing to search…`,this.fetchOptions=null,this.hasSlotController=new re(this,`start`,`end`),this.listboxId=_(`pk-combobox-listbox`),this.inputId=_(`pk-combobox-input`),this.createOptionId=_(`pk-combobox-create`),this.options=[],this.inputValue=``,this.hasInputSinceOpening=!1,this.highlightedIndex=-1,this.createOptionHighlighted=!1,this.closing=!1,this.panelAnimated=!1,this.dismissRegistered=!1,this.panelEventTarget=null,this.selectedOptionMeta=null,this.asyncFetcher=null,this.asyncLoading=!1,this.asyncError=null,this.handleOptionsMutation=(e={})=>{let t=this.getOptionElements(),n=t.length!==this.options.length||t.some((e,t)=>e!==this.options[t]);this.options=t,this.applySelection(),e.render!==!1&&n&&this.requestUpdate()},this.syncOptions=()=>{this.handleOptionsMutation({render:!0})},this.togglePanel=e=>{e?.preventDefault(),e?.stopPropagation(),!this.disabled&&(this.open||this.closing?this.closePanel(`api`):this.openPanel())},this.onDocumentPointerDown=e=>{this.isPointerInside(e)||this.closePanel(`light-dismiss`)},this.onDocumentKeyDown=e=>{if(!this.open)return;if(e.key===`Escape`){if(!be(this))return;e.preventDefault(),e.stopPropagation(),this.closePanel(`escape`);return}let t=this.panelInput;if(t&&e.composedPath().includes(t)||!(fe.has(e.key)||de(e)))return;let n=this.panelElement,r=e.composedPath();n&&r.includes(n)&&le(e,{anchor:this.controlElement,panel:n})&&(e.preventDefault(),e.stopPropagation(),this.onListboxKeyDown(e))},this.handleOptionSelect=e=>{let{value:t}=e.detail;if(this.multiple){this.values=this.values.includes(t)?this.values.filter(e=>e!==t):[...this.values,t],this.inputValue=``,this.applySelection(),this.emitValueChange(),this.activeInput?.focus({preventScroll:!0});return}this.value=t,this.syncSelectedOptionMeta(),this.applySelection(),this.closePanel(`api`),this.emitValueChange()},this.handleOptionHighlight=e=>{if(!this.open)return;let t=this.getEnabledVisibleOptions().findIndex(t=>t.value===e.detail.value);t!==-1&&t!==this.highlightedIndex&&(this.highlightedIndex=t,this.syncHighlight())},this.handleControlMouseDown=e=>{if(this.disabled||this.usesPopupMode||e.composedPath().some(e=>e instanceof HTMLElement?e.classList.contains(`icon-button`)||e.classList.contains(`clear-button`)||e.classList.contains(`tag-remove`):!1))return;let t=e.target===this.activeInput;if(!this.open&&!this.closing){t||e.preventDefault(),this.activeInput?.focus({preventScroll:!0}),this.openPanel();return}t||(e.preventDefault(),this.activeInput?.focus({preventScroll:!0}))},this.handleTriggerKeyDown=e=>{if(!this.disabled){if(e.key===`Enter`||e.key===` `){e.preventDefault(),this.togglePanel(e);return}e.key===`ArrowDown`&&!this.open&&(e.preventDefault(),this.openPanel())}},this.handleListboxKeyDownEvent=e=>{this.open&&this.onListboxKeyDown(e.detail.keyboardEvent)},this.handleCreateMouseEnter=()=>{if(!this.open)return;let e=this.getEnabledVisibleOptions();this.highlightedIndex=e.length,this.syncHighlight()},this.handleCreateKeyDown=e=>{e.preventDefault(),e.stopPropagation(),this.onListboxKeyDown(e)}}static{this.styles=kn}static get validators(){return[...super.validators,ne(),{observedAttributes:[`required`],checkValidity:e=>{let t=e,n={message:`Please select an item in the list.`,isValid:!0,invalidKeys:[]};return!t.required||!(t.multiple?t.values.length===0:!t.value)?n:(n.isValid=!1,n.invalidKeys.push(`valueMissing`),n)}}]}get panelElement(){return this.popupElement?.getContentElement()??null}get panelInput(){return this.panelElement?.querySelector(`.panel-input`)}get panelBodyElement(){return this.panelElement?.querySelector(`.panel-body`)}get usesPopupMode(){return this.popupMode&&!this.multiple}get activeInput(){return this.usesPopupMode?this.panelInput:this.controlInput}keepsFocusOnInput(){return!!this.activeInput}maintainInputFocus(){this.activeInput?.focus({preventScroll:!0})}get listScrollContainer(){return this.panelBodyElement??this.panelElement??this}connectedCallback(){this.instructions=this.getAttribute(`hint`)??this.instructions,this.refreshOptions(),super.connectedCallback(),this.syncHasValueAttribute(),this.addEventListener(`pk-listbox-keydown`,this.handleListboxKeyDownEvent),this.optionsObserver=new MutationObserver(()=>{this.handleOptionsMutation({render:!0})}),this.optionsObserver.observe(this,{childList:!0,subtree:!0})}disconnectedCallback(){this.unbindPanelEvents(),this.removeEventListener(`pk-listbox-keydown`,this.handleListboxKeyDownEvent),this.optionsObserver?.disconnect(),this.liveRegion?.destroy(),this.liveRegion=void 0,this.asyncFetcher?.cancel(),this.closePanel(`api`),super.disconnectedCallback()}updated(e){(e.has(`value`)||e.has(`values`)||e.has(`multiple`))&&(this.syncHasValueAttribute(),this.syncSelectedOptionMeta(),this.applySelection()),super.updated(e)}get validationTarget(){return this.activeInput??this.popupTrigger??this.controlElement}getAriaMirrorTarget(){return this.activeInput??this.popupTrigger??this.controlElement??null}syncFormValue(){if(!this.name){this.setFormValue(null);return}if(this.multiple){let e=new FormData;for(let t of this.values)e.append(this.name,t);this.setFormValue(e);return}this.setFormValue(this.value||``)}resetToDefaultValue(){this.multiple?this.values=[...this.defaultValues]:this.value=this.defaultValue,this.inputValue=``,this.applySelection()}restoreFormState(e){if(e instanceof FormData&&this.name){this.values=e.getAll(this.name).map(String);return}typeof e==`string`&&(this.value=e)}syncHasValueAttribute(){this.toggleAttribute(`data-has-value`,this.hasSelection())}getOptionElements(){let e=this.popupElement?.getContentElement()?.querySelectorAll(`pk-option`);return e&&e.length>0?[...e]:[...this.querySelectorAll(`pk-option`)]}refreshOptions(){this.handleOptionsMutation({render:!1})}bindPanelEvents(){let e=this.panelElement;e&&e!==this.panelEventTarget&&(this.unbindPanelEvents(),this.panelEventTarget=e,e.addEventListener(`pk-option-select`,this.handleOptionSelect),e.addEventListener(`pk-option-highlight`,this.handleOptionHighlight),e.addEventListener(`pk-listbox-keydown`,this.handleListboxKeyDownEvent))}unbindPanelEvents(){this.panelEventTarget&&=(this.panelEventTarget.removeEventListener(`pk-option-select`,this.handleOptionSelect),this.panelEventTarget.removeEventListener(`pk-option-highlight`,this.handleOptionHighlight),this.panelEventTarget.removeEventListener(`pk-listbox-keydown`,this.handleListboxKeyDownEvent),null)}isOptionInHiddenGroup(e){return!!e.closest(`pk-option-group`)?.hidden}matchesFilter(e,t){return Gt(e,t,this.filter)}getFilterQuery(){return!this.open||!this.multiple&&!this.hasInputSinceOpening&&!this.usesPopupMode?``:this.inputValue.trim().toLowerCase()}getVisibleOptions(){if(this.usesAsyncSearch)return this.options.filter(e=>!this.isOptionInHiddenGroup(e));let e=this.getFilterQuery();return this.options.filter(t=>this.isOptionInHiddenGroup(t)?!1:!e||this.matchesFilter(t,e))}getEnabledVisibleOptions(){return this.getVisibleOptions().filter(e=>!e.disabled)}getSelectedOptions(){if(this.multiple){let e=new Map(this.options.map(e=>[e.value,e]));return this.values.map(t=>e.get(t)).filter(e=>e!==void 0)}let e=this.options.find(e=>e.value===this.value);return e?[e]:[]}getSelectedOption(){return this.options.find(e=>e.value===this.value)}get usesAsyncSearch(){return this.async&&!!this.fetchOptions&&!this.multiple&&!this.usesPopupMode}getSelectedLabel(){return this.getSelectedOption()?.getLabel()??this.selectedOptionMeta?.label??this.value}clearAsyncOptionNodes(){this.querySelectorAll(`:scope > pk-option, :scope > pk-option-group, :scope > pk-separator`).forEach(e=>e.remove())}renderAsyncOptionNodes(e){let t=this.mergeAsyncItems(e);this.clearAsyncOptionNodes();for(let e of t){let t=document.createElement(`pk-option`);t.value=e.value,t.textContent=e.label,this.append(t)}this.handleOptionsMutation({render:!0})}mergeAsyncItems(e){if(!this.value)return e;let t=this.selectedOptionMeta??{value:this.value,label:this.getSelectedOption()?.getLabel()??this.value};return e.some(e=>e.value===t.value)?e:[...e,t]}syncSelectedOptionMeta(){if(!this.value){this.selectedOptionMeta=null;return}let e=this.getSelectedOption();e&&(this.selectedOptionMeta={value:e.value,label:e.getLabel()})}scheduleAsyncFetch(e){this.ensureAsyncFetcher().schedule(e)}ensureAsyncFetcher(){return this.asyncFetcher||=new Ut(()=>this.fetchOptions,{errorLabel:`combobox options`,onLoading:()=>{this.asyncLoading=!0,this.asyncError=null},onResults:e=>{this.renderAsyncOptionNodes(e)},onError:e=>{this.asyncError=e},onSettled:()=>{this.asyncLoading=!1},onEmptyQuery:()=>{this.asyncLoading=!1,this.asyncError=null,this.renderAsyncOptionNodes(this.value&&this.selectedOptionMeta?[this.selectedOptionMeta]:[])}}),this.asyncFetcher}getAsyncStatusMessage(){if(!this.usesAsyncSearch||!this.open)return null;if(this.asyncLoading)return this.loadingMessage;if(this.asyncError)return this.asyncError;let e=this.inputValue.trim();return e?this.getEnabledVisibleOptions().length===0&&!this.shouldShowCreateOption()?`No matches for "${e}".`:null:this.value?null:this.startTypingMessage}isSelected(e){return this.multiple?this.values.includes(e):this.value===e}getDisplayInputValue(){return this.usesPopupMode||this.multiple||this.open?this.inputValue:this.hasSelection()?this.getSelectedLabel():``}getTriggerDisplayValue(){return this.hasSelection()?this.getSelectedLabel():this.placeholder}isTriggerPlaceholder(){return!this.hasSelection()}hasSelection(){return this.multiple?this.values.length>0:!!(this.getSelectedOption()||this.selectedOptionMeta||this.value)}shouldShowCreateOption(){if(!this.allowCreate||!this.open||!this.multiple&&!this.hasInputSinceOpening)return!1;let e=this.inputValue.trim();if(!e)return!1;let t=e.toLowerCase();return!this.options.some(e=>e.getLabel().toLowerCase()===t||e.value.toLowerCase()===t)}getListboxNavItems(){let e=this.getEnabledVisibleOptions();return this.shouldShowCreateOption()&&this.createOptionElement?[...e,this.createOptionElement]:e}applySelection(){let e=this.getVisibleOptions(),t=this.open?this.getFilterQuery():``;Ht({host:this,options:this.options,visible:e,listboxId:this.listboxId,filterQuery:t,isSelected:e=>this.isSelected(e)}),this.syncValueInput(),this.open&&(this.syncHighlight(),this.announceFilterResults())}syncValueInput(){this.input&&(this.input.value=this.multiple?this.values.join(`,`):this.value,this.input.required=this.required)}syncHighlightedIndexToSelection(){if(this.multiple)return;let e=this.getEnabledVisibleOptions();if(!this.value||e.length===0)return;let t=e.findIndex(e=>e.value===this.value);t>=0&&(this.highlightedIndex=t)}resetHighlightedIndexOnOpen(){if(this.autoHighlight){if(this.value){this.syncHighlightedIndexToSelection();return}this.highlightedIndex=0;return}this.highlightedIndex=-1}syncHighlight(){let e=this.getEnabledVisibleOptions(),t=this.shouldShowCreateOption(),n=e.length+ +!!t;for(let e of this.options)e.highlighted=!1,e.focusIndex=-1;if(this.createOptionHighlighted=!1,n===0||this.highlightedIndex<0)return;if(this.highlightedIndex>=n&&(this.highlightedIndex=n-1),t&&this.highlightedIndex===e.length){this.createOptionHighlighted=!0,this.keepsFocusOnInput()||this.createOptionElement?.focus({preventScroll:!0}),se(this.createOptionElement,this.listScrollContainer,`vertical`,`auto`),this.keepsFocusOnInput()&&this.maintainInputFocus();return}let r=e[this.highlightedIndex];r&&(r.highlighted=!0,r.focusIndex=this.keepsFocusOnInput()?-1:0,se(r,this.listScrollContainer,`vertical`,`auto`),this.keepsFocusOnInput()&&this.maintainInputFocus())}getActiveDescendantId(){let e=this.getEnabledVisibleOptions();return this.shouldShowCreateOption()&&this.highlightedIndex===e.length?this.createOptionId:e[this.highlightedIndex]?.optionId||null}announceFilterResults(){this.liveRegion||=new ee(`polite`);let e=this.getEnabledVisibleOptions().length,t=this.getFilterQuery();if(t){if(this.shouldShowCreateOption()){this.liveRegion.announce(`Create ${t}`);return}this.liveRegion.announce(e===0?`${this.emptyMessage}`:`${e} ${e===1?`result`:`results`} available`)}}async show(){this.open||this.closing||this.disabled||await this.openPanel()}async hide(e=`api`){this.open&&!this.closing&&await this.closePanel(e)}openPanel(){let e=this.controlElement;if(!e)return Promise.resolve();if(this.open)return this.activeInput?.focus({preventScroll:!0}),Promise.resolve();if(this.closing)return Promise.resolve();this.dispatchEvent(new _e),this.closing=!1,this.panelAnimated=!1,this.open=!0,this.hasInputSinceOpening=!1,this.inputValue=this.usesPopupMode?``:!this.multiple&&this.hasSelection()?this.getSelectedLabel():``,this.applySelection(),this.resetHighlightedIndexOnOpen(),this.usesAsyncSearch&&(this.syncSelectedOptionMeta(),this.asyncError=null,this.asyncLoading=!1,this.renderAsyncOptionNodes(this.selectedOptionMeta?[this.selectedOptionMeta]:[]));let t=e.getBoundingClientRect().width;return this.style.setProperty(`--pk-combobox-anchor-width`,`${t}px`),this.popupElement.active=!0,this.panelElement&&(this.panelElement.hidden=!1,g(this.panelElement,this.placement)),this.registerDismissHandlers(),this.syncHighlight(),this.usesPopupMode?this.popupTrigger?.blur():this.activeInput?.focus({preventScroll:!0}),this.updateComplete.then(async()=>{let e=await p(this.popupElement,this.placement,300,{requireEvent:!0});if(this.panelElement&&g(this.panelElement,e),this.panelAnimated=!0,this.bindPanelEvents(),this.refreshOptions(),this.activeInput?.focus({preventScroll:!0}),this.highlightedIndex>=0&&!this.keepsFocusOnInput()){let e=this.getEnabledVisibleOptions(),t=this.highlightedIndex;this.shouldShowCreateOption()&&t===e.length?this.createOptionElement?.focus({preventScroll:!0}):e[t]?.focusControl()}this.dispatchEvent(new ye),this.dispatchEvent(new CustomEvent(`pk-open-change`,{detail:{open:!0},bubbles:!0,composed:!0}))})}commitCustomValueIfAllowed(){if(this.multiple||!this.allowCustomValue)return!1;let e=this.inputValue.trim();if(!e)return!1;let t=this.options.find(t=>t.getLabel().toLowerCase()===e.toLowerCase()||t.value.toLowerCase()===e.toLowerCase())?.value??e;return this.value!==t&&(this.value=t,!0)}commitInputOnClose(e){return this.multiple||this.usesPopupMode?!1:this.hasInputSinceOpening?this.inputValue.trim()?this.shouldCommitCustomValueOnClose(e)?this.commitCustomValueIfAllowed():!1:this.value?(this.value=``,!0):!1:this.shouldCommitCustomValueOnClose(e)?this.commitCustomValueIfAllowed():!1}shouldCommitCustomValueOnClose(e){return e===`light-dismiss`||e===`pointer-dismiss`}async closePanel(e=`unknown`){if(!this.open||this.closing)return;let t=new xe(e);if(!this.dispatchEvent(t))return;let n=this.commitInputOnClose(e);this.unbindPanelEvents(),this.closing=!0,this.panelAnimated=!1,await Vt(this.panelElement),this.open=!1,this.closing=!1,this.panelAnimated=!1,this.hasInputSinceOpening=!1,this.inputValue=``,this.panelElement&&(this.panelElement.hidden=!0,this.panelElement.removeAttribute(`data-side`)),this.popupElement.active=!1,this.unregisterDismissHandlers(),this.applySelection(),this.usesAsyncSearch&&(this.asyncFetcher?.cancel(),this.asyncLoading=!1,this.asyncError=null,this.renderAsyncOptionNodes(this.selectedOptionMeta?[this.selectedOptionMeta]:[])),n&&(this.syncHasValueAttribute(),this.emitValueChange()),this.shouldReturnFocusToInput(e)?this.usesPopupMode?this.popupTrigger?.focus({preventScroll:!0}):this.activeInput?.focus({preventScroll:!0}):(this.activeInput?.blur(),this.popupTrigger?.blur()),this.dispatchEvent(new Se),this.dispatchEvent(new CustomEvent(`pk-open-change`,{detail:{open:!1},bubbles:!0,composed:!0}))}shouldReturnFocusToInput(e){return e!==`light-dismiss`&&e!==`pointer-dismiss`}registerDismissHandlers(){ve(this),this.dismissRegistered=!0,document.addEventListener(`pointerdown`,this.onDocumentPointerDown,!0),document.addEventListener(`keydown`,this.onDocumentKeyDown,!0)}unregisterDismissHandlers(){this.dismissRegistered&&=(ge(this),!1),document.removeEventListener(`pointerdown`,this.onDocumentPointerDown,!0),document.removeEventListener(`keydown`,this.onDocumentKeyDown,!0)}isPointerInside(e){return ue(e,{anchor:this.controlElement,panel:this.panelElement})}handleCreateOption(){let e=this.inputValue.trim();if(!e)return;let t=new On(e);if(!this.dispatchEvent(t))return;let n=document.createElement(`pk-option`);if(n.value=e,n.textContent=e,this.append(n),this.multiple){this.values.includes(e)||(this.values=[...this.values,e]),this.inputValue=``,this.applySelection(),this.emitValueChange(),this.activeInput?.focus({preventScroll:!0});return}this.value=e,this.applySelection(),this.closePanel(`api`),this.emitValueChange()}removeTag(e,t){t.preventDefault(),t.stopPropagation(),this.values=this.values.filter(t=>t!==e),this.applySelection(),this.emitValueChange(),this.activeInput?.focus({preventScroll:!0})}handleClear(e){e.preventDefault(),e.stopPropagation(),this.multiple?this.values=[]:this.value=``,this.inputValue=``,this.selectedOptionMeta=null,this.usesAsyncSearch&&this.renderAsyncOptionNodes([]),this.applySelection(),this.dispatchEvent(new ie),this.emitValueChange(),this.activeInput?.focus()}emitValueChange(){this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:this.multiple?[...this.values]:this.value},bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}handleInput(e){this.hasInputSinceOpening=!0,this.inputValue=e.target.value,this.highlightedIndex=this.autoHighlight?0:-1,this.applySelection(),this.usesAsyncSearch&&(this.asyncError=null,this.scheduleAsyncFetch(this.inputValue.trim())),this.open||this.openPanel()}handleInputKeyDown(e){if(e.key===`Backspace`&&this.multiple&&!this.inputValue&&this.values.length>0){e.preventDefault(),this.values=this.values.slice(0,-1),this.applySelection(),this.emitValueChange();return}if(e.key===`Escape`&&this.open){if(e.preventDefault(),this.hasInputSinceOpening&&this.inputValue){this.hasInputSinceOpening=!1,this.inputValue=this.usesPopupMode?``:!this.multiple&&this.hasSelection()?this.getSelectedLabel():``,this.highlightedIndex=this.autoHighlight?0:-1,this.applySelection();return}this.closePanel(`escape`);return}if(e.key===`ArrowDown`&&!this.open){e.preventDefault(),this.openPanel();return}if(e.key===`Tab`&&this.open){let e=!1;this.multiple||(e=this.commitCustomValueIfAllowed()),this.closePanel(`api`),e&&(this.syncHasValueAttribute(),this.emitValueChange());return}if(this.open&&e.key===`Enter`&&!this.multiple&&this.getEnabledVisibleOptions().length===0&&this.allowCustomValue&&this.inputValue.trim()&&!this.shouldShowCreateOption()){e.preventDefault();let t=this.commitCustomValueIfAllowed();this.closePanel(`api`),t&&(this.syncHasValueAttribute(),this.emitValueChange());return}this.open&&this.onListboxKeyDown(e)}onListboxKeyDown(e){let t=this.getListboxNavItems(),n=this.getEnabledVisibleOptions();if(this.highlightedIndex<0){if(e.key===`ArrowDown`||e.key===`ArrowRight`){(n.length>0||this.shouldShowCreateOption())&&(e.preventDefault(),this.highlightedIndex=0,this.syncHighlight());return}if(e.key===`ArrowUp`||e.key===`ArrowLeft`){(n.length>0||this.shouldShowCreateOption())&&(e.preventDefault(),this.highlightedIndex=this.shouldShowCreateOption()?n.length:Math.max(n.length-1,0),this.syncHighlight());return}if(e.key===`Enter`||e.key===` `)return}if(e.key===`Enter`&&this.shouldShowCreateOption()&&this.highlightedIndex===n.length){e.preventDefault(),this.handleCreateOption();return}if(this.multiple&&(e.key===`Enter`||e.key===` `)){let t=n[this.highlightedIndex];t&&(e.preventDefault(),t.dispatchEvent(new CustomEvent(`pk-option-select`,{detail:{value:t.value},bubbles:!0,composed:!0})));return}this.highlightedIndex=ce(e,{items:t,currentIndex:this.highlightedIndex,multiselect:this.multiple,loop:this.loopFocus,onSelect:e=>{this.highlightedIndex=e,this.syncHighlight()},focusItem:e=>{if(!this.keepsFocusOnInput()){if(this.shouldShowCreateOption()&&e===n.length){this.createOptionElement?.focus({preventScroll:!0});return}n[e]?.focusControl()}},onClose:()=>{this.closePanel(`escape`)}})}renderHostDecorationSlot(e){return this.hasSlotController.test(e)?C`
            <span part=${e} class=${e===`start`?`control-start`:`control-end`}>
                <slot name=${e}></slot>
            </span>
        `:C`<slot name=${e} hidden></slot>`}renderChevronButton(){return C`
            <button
                type="button"
                class="icon-button expand-button"
                part="expand-button"
                aria-label="Toggle options"
                ?disabled=${this.disabled}
                @click=${this.togglePanel}
            >
                <span class="icon" aria-hidden="true">${b(An)}</span>
            </button>
        `}renderTags(){return this.getSelectedOptions().map(e=>C`
            <span class="tag" part="tag">
                <span class="tag-label">${e.getLabel()}</span>
                <button
                    type="button"
                    class="tag-remove"
                    part="tag-remove"
                    aria-label=${`Remove ${e.getLabel()}`}
                    ?disabled=${this.disabled}
                    @click=${t=>this.removeTag(e.value,t)}
                >
                    <span class="tag-remove-icon" aria-hidden="true">${b(jn)}</span>
                </button>
            </span>
        `)}shouldShowPlaceholder(){return!this.inputValue.trim()&&!this.hasSelection()}renderInput(){let e=this.open?this.getActiveDescendantId():null,t=this.shouldShowPlaceholder();return C`
            <input
                part="input"
                class=${w({"combobox-input":!0,"control-input":!0,"combobox-input--inline":this.multiple})}
                type="text"
                role="combobox"
                id=${this.inputId}
                .value=${this.getDisplayInputValue()}
                placeholder=${t?this.placeholder:S}
                ?disabled=${this.disabled}
                aria-label=${this.ariaLabel??S}
                aria-expanded=${this.open?`true`:`false`}
                aria-controls=${this.listboxId}
                aria-autocomplete="list"
                aria-activedescendant=${e??S}
                @input=${this.handleInput}
                @keydown=${this.handleInputKeyDown}
            />
        `}renderPanelInput(){let e=this.open?this.getActiveDescendantId():null;return C`
            <div part="panel-search" class="panel-search">
                <input
                    part="panel-input"
                    class="combobox-input panel-input"
                    type="text"
                    role="combobox"
                    id=${this.inputId}
                    .value=${this.inputValue}
                    placeholder=${this.searchPlaceholder}
                    ?disabled=${this.disabled}
                    aria-label=${this.ariaLabel??this.searchPlaceholder}
                    aria-expanded="true"
                    aria-controls=${this.listboxId}
                    aria-autocomplete="list"
                    aria-activedescendant=${e??S}
                    @input=${this.handleInput}
                    @keydown=${this.handleInputKeyDown}
                />
            </div>
        `}renderPopupTrigger(){return C`
            <button
                type="button"
                part="trigger"
                class="popup-trigger"
                ?disabled=${this.disabled}
                aria-label=${this.ariaLabel??S}
                aria-haspopup="listbox"
                aria-expanded=${this.open?`true`:`false`}
                aria-controls=${this.listboxId}
                @click=${this.togglePanel}
                @keydown=${this.handleTriggerKeyDown}
            >
                <span
                    class=${w({"popup-trigger-value":!0,"is-placeholder":this.isTriggerPlaceholder()})}
                >
                    ${this.getTriggerDisplayValue()}
                </span>
                <span class="icon popup-trigger-icon" aria-hidden="true">${b(An)}</span>
            </button>
        `}renderControlContent(){if(this.usesPopupMode)return this.renderPopupTrigger();let e=(this.clearable||this.withClear)&&this.hasSelection()&&!this.disabled;return this.multiple?C`
                ${this.renderHostDecorationSlot(`start`)}
                <div class="chips" part="tags">
                    ${this.renderTags()}
                    ${this.renderInput()}
                </div>
                ${this.renderHostDecorationSlot(`end`)}
                ${e?C`
                        <button
                            type="button"
                            class="clear-button"
                            part="clear-button"
                            aria-label="Clear selection"
                            ?disabled=${this.disabled}
                            @click=${this.handleClear}
                        >
                            <span class="clear-button-icon" aria-hidden="true">${b(jn)}</span>
                        </button>
                    `:S}
            `:C`
            ${this.renderHostDecorationSlot(`start`)}
            ${this.renderInput()}
            ${this.renderHostDecorationSlot(`end`)}
            ${e?C`
                    <button
                        type="button"
                        class="clear-button"
                        part="clear-button"
                        aria-label="Clear selection"
                        ?disabled=${this.disabled}
                        @click=${this.handleClear}
                    >
                        <span class="clear-button-icon" aria-hidden="true">${b(jn)}</span>
                    </button>
                `:S}
            ${this.renderChevronButton()}
        `}render(){let e=this.getEnabledVisibleOptions(),t=this.shouldShowCreateOption(),n=this.open&&!this.usesAsyncSearch&&e.length===0&&!t,r=this.getAsyncStatusMessage(),i=this.inputValue.trim();return C`
            <input
                class="value-input"
                part="value-input"
                tabindex="-1"
                aria-hidden="true"
                .value=${this.multiple?this.values.join(`,`):this.value}
                ?required=${this.required}
                @input=${()=>this.updateValidity()}
            />
            <div
                part="control"
                class=${w({control:!0,"is-disabled":this.disabled,"control--multiple":this.multiple,"control--popup":this.usesPopupMode})}
                data-popup-open=${this.open?``:S}
                @mousedown=${this.handleControlMouseDown}
            >
                ${this.renderControlContent()}
            </div>
            <pk-popup
                .active=${this.open||this.closing}
                .anchor=${this.controlElement??``}
                .placement=${this.placement}
                .distance=${this.sideOffset}
                .sync=${`width`}
                flip
                shift
            >
                <div
                    part="panel"
                    class=${w({panel:!0,"pk-popup-content":!0,closing:this.closing,"panel--popup":this.usesPopupMode})}
                    tabindex="-1"
                    ?hidden=${!this.open&&!this.closing}
                    data-open=${this.panelAnimated&&!this.closing?``:S}
                >
                    ${this.usesPopupMode?this.renderPanelInput():S}
                    <div
                        part="panel-body"
                        class="panel-body"
                        id=${this.listboxId}
                        role="listbox"
                        aria-multiselectable=${this.multiple?`true`:`false`}
                        aria-busy=${this.usesAsyncSearch&&this.asyncLoading?`true`:S}
                        @slotchange=${this.syncOptions}
                    >
                        <slot></slot>
                        ${r?C`
                                <div part="async-status" class="async-status" role="status">${r}</div>
                            `:S}
                        ${t?C`
                                <button
                                    type="button"
                                    part="create-option"
                                    class=${w({"create-option":!0,"is-highlighted":this.createOptionHighlighted})}
                                    id=${this.createOptionId}
                                    role="option"
                                    aria-selected="false"
                                    tabindex="-1"
                                    @click=${this.handleCreateOption}
                                    @mouseenter=${this.handleCreateMouseEnter}
                                    @keydown=${this.handleCreateKeyDown}
                                >
                                    Create "${i}"
                                </button>
                            `:S}
                        ${n?C`
                                <div part="empty" class="empty">${this.emptyMessage}</div>
                            `:S}
                    </div>
                </div>
            </pk-popup>
        `}};k([T({type:Boolean,reflect:!0})],X.prototype,`open`,void 0),k([T({type:Boolean,reflect:!0})],X.prototype,`multiple`,void 0),k([T({reflect:!0})],X.prototype,`placement`,void 0),k([T({attribute:`side-offset`,type:Number})],X.prototype,`sideOffset`,void 0),k([T({type:Boolean,reflect:!0})],X.prototype,`clearable`,void 0),k([T({attribute:`with-clear`,type:Boolean})],X.prototype,`withClear`,void 0),k([T({attribute:`allow-create`,type:Boolean})],X.prototype,`allowCreate`,void 0),k([T({attribute:`allow-custom-value`,type:Boolean})],X.prototype,`allowCustomValue`,void 0),k([T({attribute:`auto-highlight`,type:Boolean})],X.prototype,`autoHighlight`,void 0),k([T({attribute:`popup-mode`,type:Boolean,reflect:!0})],X.prototype,`popupMode`,void 0),k([T({attribute:`search-placeholder`})],X.prototype,`searchPlaceholder`,void 0),k([T({type:Boolean,reflect:!0})],X.prototype,`invalid`,void 0),k([T({reflect:!0})],X.prototype,`size`,void 0),k([T({reflect:!0})],X.prototype,`width`,void 0),k([T()],X.prototype,`placeholder`,void 0),k([T({attribute:`empty-message`})],X.prototype,`emptyMessage`,void 0),k([T()],X.prototype,`value`,void 0),k([T({attribute:`default-value`})],X.prototype,`defaultValue`,void 0),k([T({type:Array,attribute:!1})],X.prototype,`values`,void 0),k([T({attribute:!1})],X.prototype,`defaultValues`,void 0),k([T()],X.prototype,`label`,void 0),k([T()],X.prototype,`instructions`,void 0),k([T({attribute:`aria-label`})],X.prototype,`ariaLabel`,void 0),k([T({attribute:`loop-focus`,type:Boolean})],X.prototype,`loopFocus`,void 0),k([T({attribute:!1})],X.prototype,`filter`,void 0),k([T({type:Boolean,reflect:!0})],X.prototype,`async`,void 0),k([T({attribute:`loading-message`})],X.prototype,`loadingMessage`,void 0),k([T({attribute:`start-typing-message`})],X.prototype,`startTypingMessage`,void 0),k([T({attribute:!1})],X.prototype,`fetchOptions`,void 0),k([D(`pk-popup`)],X.prototype,`popupElement`,void 0),k([D(`.control`)],X.prototype,`controlElement`,void 0),k([D(`.control-input`)],X.prototype,`controlInput`,void 0),k([D(`.popup-trigger`)],X.prototype,`popupTrigger`,void 0),k([D(`.create-option`)],X.prototype,`createOptionElement`,void 0),k([D(`.value-input`)],X.prototype,`input`,void 0),k([x()],X.prototype,`inputValue`,void 0),k([x()],X.prototype,`highlightedIndex`,void 0),k([x()],X.prototype,`createOptionHighlighted`,void 0),k([x()],X.prototype,`closing`,void 0),k([x()],X.prototype,`panelAnimated`,void 0),k([x()],X.prototype,`asyncLoading`,void 0),k([x()],X.prototype,`asyncError`,void 0),X=k([A(`pk-combobox`)],X);var Mn=m({tagName:`pk-combobox`,elementClass:X,react:V.default,events:{onPkChange:`pk-change`,onPkClear:`pk-clear`,onPkCreate:`pk-create`,onInput:`input`,onChange:`change`,onPkShow:`pk-show`,onPkAfterShow:`pk-after-show`,onPkHide:`pk-hide`,onPkAfterHide:`pk-after-hide`,onPkOpenChange:`pk-open-change`}}),Nn=(0,V.forwardRef)(function(e,t){let{disabled:n,invalid:r,clearable:i,multiple:a,open:o,popupMode:s,allowCreate:c,allowCustomValue:l,...u}=e;return(0,H.jsx)(Mn,{ref:t,...u,...h([`disabled`,`invalid`,`clearable`,`multiple`,`open`,`popupMode`,`allowCreate`,`allowCustomValue`],{disabled:n,invalid:r,clearable:i,multiple:a,open:o,popupMode:s,allowCreate:c,allowCustomValue:l})})});Nn.displayName=`Combobox`;var Pn=m({tagName:`pk-dialog`,elementClass:we,react:V.default,events:{onPkShow:`pk-show`,onPkAfterShow:`pk-after-show`,onPkHide:`pk-hide`,onPkAfterHide:`pk-after-hide`,onPkOpenChange:`pk-open-change`}}),Z=e=>{if(e)return t=>{B(t)&&e(t)}},Fn=V.forwardRef(function(e,t){let{open:n,disablePointerDismissal:r,withoutHeader:i,withoutBodyPadding:a,disableScrollLock:o,onPkShow:s,onPkAfterShow:c,onPkHide:l,onPkAfterHide:u,onPkOpenChange:d,...f}=e;return(0,H.jsx)(Pn,{ref:t,...f,open:n,...h([`disablePointerDismissal`,`withoutHeader`,`withoutBodyPadding`,`disableScrollLock`],{disablePointerDismissal:r,withoutHeader:i,withoutBodyPadding:a,disableScrollLock:o}),...s?{onPkShow:Z(s)}:{},...c?{onPkAfterShow:Z(c)}:{},...l?{onPkHide:Z(l)}:{},...u?{onPkAfterHide:Z(u)}:{},...d?{onPkOpenChange:Z(d)}:{}})});Fn.displayName=`Dialog`;var Q=class extends O{constructor(...e){super(...e),this.value=``,this.disabled=!1,this.selected=!1,this.focusIndex=-1}focusControl(){this.shadowRoot?.querySelector(`.trigger`)?.focus()}handleClick(){this.disabled||this.dispatchEvent(new CustomEvent(`pk-tab-select`,{detail:{value:this.value},bubbles:!0,composed:!0}))}handleKeyDown(e){this.dispatchEvent(new CustomEvent(`pk-tab-keydown`,{detail:{event:e,value:this.value},bubbles:!0,composed:!0}))}renderTrigger(e){return C`
            <button
                part="trigger"
                type="button"
                class=${e}
                role="tab"
                ?disabled=${this.disabled}
                aria-disabled=${this.disabled?`true`:S}
                aria-selected=${this.selected?`true`:`false`}
                tabindex=${this.focusIndex}
                aria-controls=${this.panelId??S}
                @click=${this.handleClick}
                @keydown=${this.handleKeyDown}
            >
                <span part="icon" class="icon">
                    <slot name="icon"></slot>
                </span>
                <span part="label" class="label">
                    <slot></slot>
                </span>
                <span part="status" class="status">
                    <slot name="status"></slot>
                </span>
            </button>
        `}};k([T()],Q.prototype,`value`,void 0),k([T({type:Boolean,reflect:!0})],Q.prototype,`disabled`,void 0),k([T({type:Boolean,reflect:!0})],Q.prototype,`selected`,void 0),k([T({type:Number,attribute:`focus-index`})],Q.prototype,`focusIndex`,void 0),k([T()],Q.prototype,`panelId`,void 0);var In=E`
    @layer pk-component {
        :host {
            /* Size to the shadow trigger. Prefer flex-start so a short list line
             * (or host utilities like Tailwind items-center) cannot stretch the
             * host shorter than the trigger and clip the modal active underline. */
            display: inline-flex;
            flex-shrink: 0;
            align-self: flex-start;
            height: auto;
            min-height: auto;
            align-items: stretch;
            /* Pin type metrics for slotted labels — vars cascade from pk-tabs. */
            font-family: var(--pk-font-family);
            font-size: var(--pk-tabs-trigger-font-size, 13px);
            font-weight: var(--pk-tabs-trigger-font-weight, 400);
            line-height: var(--pk-tabs-trigger-line-height, 1.4);
            color: var(--pk-tabs-trigger-color, inherit);
        }

        .trigger {
            position: relative;
            display: var(--pk-tabs-trigger-display, inline-flex);
            align-items: center;
            justify-content: var(--pk-tabs-trigger-justify, center);
            gap: var(--pk-tabs-trigger-gap, 0.5rem);
            width: var(--pk-tabs-trigger-width, auto);
            min-height: var(--pk-tabs-trigger-min-height, 2rem);
            padding: var(--pk-tabs-trigger-padding-block, 0.375rem)
                var(--pk-tabs-trigger-padding-inline, 0.75rem);
            border: 0;
            border-top: var(--pk-tabs-trigger-border-top, 0 solid transparent);
            border-radius: var(--pk-tabs-trigger-radius, var(--pk-radius-sm));
            background: transparent;
            color: inherit;
            font: inherit;
            font-family: var(--pk-font-family);
            font-size: var(--pk-tabs-trigger-font-size, 13px);
            font-weight: var(--pk-tabs-trigger-font-weight, 400);
            line-height: var(--pk-tabs-trigger-line-height, 1.4);
            text-align: var(--pk-tabs-trigger-text-align, center);
            text-transform: var(--pk-tabs-trigger-text-transform, none);
            white-space: nowrap;
            cursor: pointer;
            outline: none;
            box-shadow: none;
            box-sizing: border-box;
            transition: background-color 0.12s ease, color 0.12s ease, box-shadow 0.12s ease, border-color 0.12s ease;
        }

        /* Collapse optional icon/status lanes when nothing is slotted. */
        .icon,
        .status {
            display: none;
            flex: none;
            align-items: center;
            justify-content: center;
            line-height: 0;
        }

        .icon:has(::slotted(*)),
        .status:has(::slotted(*)) {
            display: inline-flex;
        }

        .icon {
            width: var(--pk-tabs-trigger-icon-size, 1.125rem);
            height: var(--pk-tabs-trigger-icon-size, 1.125rem);
            font-size: var(--pk-tabs-trigger-icon-size, 1.125rem);
            color: var(--pk-tabs-trigger-icon-color, inherit);
        }

        .icon ::slotted(*) {
            display: block;
            max-width: 100%;
            max-height: 100%;
            /* Kill pk-icon text-baseline nudge so logos/icons sit on the flex midline. */
            vertical-align: 0;
        }

        .label {
            display: inline-flex;
            flex: var(--pk-tabs-trigger-label-flex, 0 1 auto);
            align-items: center;
            min-width: 0;
            line-height: inherit;
        }

        .status {
            margin-inline-start: var(--pk-tabs-trigger-status-margin, 0);
            color: var(--pk-tabs-trigger-status-color, inherit);
        }

        .trigger:hover:not(:disabled):not([aria-disabled='true']):not([aria-selected='true']) {
            border-top-color: transparent;
            background: var(--pk-tabs-trigger-hover-bg, rgb(255 255 255 / 0.7));
            color: var(--pk-tabs-trigger-hover-color, var(--pk-color-gray-700));
        }

        /*
         * Focus ring is only for :focus-visible on a non-selected tab (manual
         * activation). Selected + focus-visible must NOT draw a ring — active
         * chrome is the underline (mouse) or is replaced by the modal focus box
         * via --pk-tabs-trigger-focus-selected-shadow when set.
         */
        .trigger:focus-visible:not([aria-selected='true']) {
            box-shadow: inset 0 0 0 2px var(--pk-color-sky-600);
        }

        .trigger:disabled,
        .trigger[aria-disabled='true'] {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .trigger[aria-selected='true'],
        :host([selected]) .trigger {
            border-top: var(--pk-tabs-trigger-selected-border-top, var(--pk-tabs-trigger-border-top, 0 solid transparent));
            border-radius: var(--pk-tabs-trigger-selected-radius, var(--pk-radius-sm));
            background: var(--pk-tabs-trigger-selected-bg, var(--pk-color-white));
            color: var(--pk-tabs-trigger-selected-color, var(--pk-color-gray-800));
            box-shadow: var(--pk-tabs-trigger-selected-shadow, 0 1px 2px rgba(31, 41, 51, 0.12));
        }

        .trigger[aria-selected='true']:hover,
        :host([selected]) .trigger:hover {
            background: var(--pk-tabs-trigger-selected-hover-bg, var(--pk-tabs-trigger-selected-bg, var(--pk-color-white)));
            color: var(--pk-tabs-trigger-selected-hover-color, var(--pk-tabs-trigger-selected-color, var(--pk-color-gray-800)));
        }

        /* screen3: keyboard focus on the active tab → full inset box, no underline. */
        .trigger[aria-selected='true']:focus-visible,
        :host([selected]) .trigger:focus-visible {
            box-shadow: var(
                --pk-tabs-trigger-focus-selected-shadow,
                var(--pk-tabs-trigger-selected-shadow, 0 0 #0000)
            );
        }

        .trigger[aria-selected='true']:focus-visible::after,
        :host([selected]) .trigger:focus-visible::after {
            height: var(--pk-tabs-trigger-focus-selected-underline-height, var(--pk-tabs-trigger-underline-height, 0));
        }

        .trigger[aria-selected='true']::after,
        :host([selected]) .trigger::after {
            content: '';
            position: absolute;
            right: var(--pk-tabs-trigger-underline-inset, 15px);
            bottom: 0;
            left: var(--pk-tabs-trigger-underline-inset, 15px);
            height: var(--pk-tabs-trigger-underline-height, 0);
            /* Keep the bar above the list hairline when both meet at the clip edge. */
            z-index: 1;
            background: var(--pk-color-sky-600);
            pointer-events: none;
        }

        /*
         * Validation error chrome (v1 ModalTabs / PaneTabs text-error).
         * Host sets data-has-errors; light-DOM text-* cannot pierce the trigger.
         */
        :host([data-has-errors]) {
            --pk-tabs-trigger-color: var(--pk-color-error, #d81f23);
            --pk-tabs-trigger-hover-color: var(--pk-color-rose-700, #be123c);
            --pk-tabs-trigger-selected-color: var(--pk-color-error, #d81f23);
            --pk-tabs-trigger-selected-hover-color: var(--pk-color-rose-700, #be123c);
            --pk-tabs-trigger-icon-color: inherit;
            --pk-tabs-trigger-status-color: inherit;
        }
    }
`,Ln=class extends Q{static{this.styles=In}render(){return this.renderTrigger(`trigger pk-tabs__trigger`)}};Ln=k([A(`pk-tab`)],Ln);var Rn=E`
    @layer pk-component {
        :host {
            display: block;
            flex-shrink: 0;
            width: 100%;
        }

        .heading {
            margin: 0;
            padding: var(--pk-tabs-heading-padding, 0.75rem 0.5rem 0.375rem);
            color: var(--pk-tabs-heading-color, var(--pk-color-gray-400));
            font-family: var(--pk-font-family);
            font-size: var(--pk-tabs-heading-font-size, 11px);
            font-weight: var(--pk-tabs-heading-font-weight, 600);
            line-height: 1.3;
            letter-spacing: var(--pk-tabs-heading-letter-spacing, 0.04em);
            text-transform: var(--pk-tabs-heading-text-transform, uppercase);
            user-select: none;
            pointer-events: none;
        }
    }
`,zn=class extends O{static{this.styles=Rn}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`presentation`)}render(){return C`
            <div part="heading" class="heading">
                <slot></slot>
            </div>
        `}};zn=k([A(`pk-tab-heading`)],zn);var Bn=class extends O{constructor(...e){super(...e),this.value=``,this.hidden=!0}renderPanel(e){return C`
            <div
                part="content"
                class=${e}
                role="tabpanel"
                id=${this.tabId??S}
                aria-labelledby=${this.tabId??S}
                aria-hidden=${this.hidden?`true`:`false`}
                tabindex=${this.hidden?S:`0`}
            >
                <slot></slot>
            </div>
        `}};k([T()],Bn.prototype,`value`,void 0),k([T({type:Boolean,reflect:!0})],Bn.prototype,`hidden`,void 0),k([T()],Bn.prototype,`tabId`,void 0);var Vn=E`
    @layer pk-component {
        :host {
            /* Flex column so .content can own overflow when the host is height-capped
             * by a modal/pane parent (flex: 1 1 0% + min-height: 0). */
            display: flex;
            flex-direction: column;
            flex: var(--pk-tabs-panel-flex, none);
            min-height: var(--pk-tabs-panel-min-height, 0);
            min-width: 0;
            overflow: hidden;
        }

        :host([hidden]) {
            display: none !important;
        }

        .content {
            flex: 1 1 auto;
            min-height: 0;
            padding: var(--pk-tabs-panel-padding, 0);
            overflow-y: auto;
            border-radius: var(--pk-tabs-panel-radius, 0);
            background: var(--pk-tabs-panel-bg, transparent);
            outline: none;
            font-family: var(--pk-font-family);
            font-size: var(--pk-tabs-panel-font-size, var(--pk-font-size-base));
            line-height: var(--pk-line-height);
        }

        .content:focus-visible {
            box-shadow: inset 0 0 0 2px var(--pk-color-sky-600);
            border-radius: var(--pk-radius-sm);
        }
    }
`,Hn=class extends Bn{static{this.styles=Vn}render(){return this.renderPanel(`content pk-tabs__content`)}};Hn=k([A(`pk-tab-panel`)],Hn);var Un=E`
    @layer pk-component {
        :host {
            display: block;
            max-width: 100%;
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: var(--pk-line-height);
            /* Frame chrome on the host (matches v1 PaneTabs root) so consumer
             * overflow utilities on the host do not clip the pane shadow. */
            border-radius: var(--pk-tabs-root-radius);
            box-shadow: var(--pk-tabs-root-shadow);
            overflow: var(--pk-tabs-root-overflow);

            /* Root */
            --pk-tabs-root-gap: 0.75rem;
            --pk-tabs-root-height: auto;
            --pk-tabs-root-radius: 0;
            --pk-tabs-root-shadow: none;
            --pk-tabs-root-overflow: visible;

            /* List */
            --pk-tabs-list-display: inline-flex;
            --pk-tabs-list-width: fit-content;
            --pk-tabs-list-align-self: flex-start;
            --pk-tabs-list-align-items: center;
            --pk-tabs-list-padding: 2px;
            --pk-tabs-list-border-width: 1px;
            --pk-tabs-list-border-color: var(--pk-color-gray-150);
            --pk-tabs-list-border-bottom: var(--pk-tabs-list-border-width) solid var(--pk-tabs-list-border-color);
            --pk-tabs-list-radius: var(--pk-radius-md);
            --pk-tabs-list-bg: color-mix(in oklab, var(--pk-color-gray-100) 90%, transparent);
            --pk-tabs-list-shadow: 0 1px 2px rgba(31, 41, 51, 0.06);
            /* Transparent no-op — keyword none in a multi-shadow list invalidates the whole property. */
            --pk-tabs-list-inset-shadow: 0 0 #0000;
            --pk-tabs-list-color: var(--pk-color-gray-500);
            --pk-tabs-list-overflow-x: auto;
            --pk-tabs-list-overflow-y: visible;

            /* Trigger (inherited by pk-tab) */
            --pk-tabs-trigger-display: inline-flex;
            --pk-tabs-trigger-justify: center;
            --pk-tabs-trigger-width: auto;
            --pk-tabs-trigger-gap: 0.5rem;
            --pk-tabs-trigger-min-height: 2rem;
            --pk-tabs-trigger-padding-block: 0.375rem;
            --pk-tabs-trigger-padding-inline: 0.75rem;
            --pk-tabs-trigger-radius: var(--pk-radius-sm);
            --pk-tabs-trigger-color: inherit;
            --pk-tabs-trigger-font-size: 13px;
            --pk-tabs-trigger-font-weight: 400;
            --pk-tabs-trigger-text-align: center;
            --pk-tabs-trigger-text-transform: none;
            --pk-tabs-trigger-border-top: 0 solid transparent;
            --pk-tabs-trigger-hover-bg: rgb(255 255 255 / 0.7);
            --pk-tabs-trigger-hover-color: var(--pk-color-gray-700);
            --pk-tabs-trigger-selected-hover-bg: var(--pk-tabs-trigger-selected-bg, var(--pk-color-white));
            --pk-tabs-trigger-selected-hover-color: var(--pk-tabs-trigger-selected-color, var(--pk-color-gray-800));
            --pk-tabs-trigger-selected-bg: var(--pk-color-white);
            --pk-tabs-trigger-selected-color: var(--pk-color-gray-800);
            --pk-tabs-trigger-selected-shadow: 0 1px 2px rgba(31, 41, 51, 0.12);
            --pk-tabs-trigger-selected-radius: var(--pk-radius-sm);
            --pk-tabs-trigger-selected-border-top: 0 solid transparent;
            --pk-tabs-trigger-underline-height: 0;
            --pk-tabs-trigger-underline-inset: 15px;
            --pk-tabs-trigger-label-flex: 0 1 auto;
            --pk-tabs-trigger-icon-size: 1.125rem;
            --pk-tabs-trigger-icon-color: inherit;
            --pk-tabs-trigger-status-margin: 0;
            --pk-tabs-trigger-status-color: inherit;

            /* Group headings (pk-tab-heading) */
            --pk-tabs-heading-padding: 0.75rem 0.5rem 0.375rem;
            --pk-tabs-heading-color: var(--pk-color-gray-400);
            --pk-tabs-heading-font-size: 11px;
            --pk-tabs-heading-font-weight: 600;
            --pk-tabs-heading-letter-spacing: 0.04em;
            --pk-tabs-heading-text-transform: uppercase;

            /* Panel (inherited by pk-tab-panel) */
            --pk-tabs-panel-flex: none;
            --pk-tabs-panel-min-height: 0;
            --pk-tabs-panel-padding: 0;
            --pk-tabs-panel-bg: transparent;
            --pk-tabs-panel-radius: 0;
            --pk-tabs-panel-font-size: var(--pk-font-size-base);
        }

        :host([variant='pane']),
        :host([variant='modal']),
        :host([variant='sidebar']) {
            height: 100%;
            min-height: 0;
            --pk-tabs-root-height: 100%;
        }

        .tabs {
            display: flex;
            flex-direction: column;
            gap: var(--pk-tabs-root-gap);
            height: var(--pk-tabs-root-height);
            min-height: 0;
            /* Radius/shadow/overflow live on :host — keep the layout shell fill-only. */
        }

        .tabs[data-placement='bottom'] {
            flex-direction: column-reverse;
        }

        .tabs[data-placement='start'],
        .tabs[data-placement='end'] {
            flex-direction: row;
            align-items: flex-start;
            gap: 1rem;
        }

        .tabs[data-placement='end'] {
            flex-direction: row-reverse;
        }

        .tabs[data-placement='start'] .list,
        .tabs[data-placement='end'] .list {
            flex-direction: column;
            align-self: stretch;
        }

        .list {
            display: var(--pk-tabs-list-display);
            width: var(--pk-tabs-list-width);
            max-width: 100%;
            align-self: var(--pk-tabs-list-align-self);
            align-items: var(--pk-tabs-list-align-items);
            justify-content: flex-start;
            /* Tab strip must not shrink when panels flex-fill the column — otherwise
             * overflow-y clips trigger padding and the modal active underline. */
            flex-shrink: 0;
            position: relative;
            z-index: var(--pk-tabs-list-z-index, auto);
            isolation: isolate;
            padding: var(--pk-tabs-list-padding);
            border: var(--pk-tabs-list-border-width) solid var(--pk-tabs-list-border-color);
            border-bottom: var(--pk-tabs-list-border-bottom, var(--pk-tabs-list-border-width) solid var(--pk-tabs-list-border-color));
            border-radius: var(--pk-tabs-list-radius);
            background: var(--pk-tabs-list-bg);
            box-shadow: var(--pk-tabs-list-shadow), var(--pk-tabs-list-inset-shadow);
            color: var(--pk-tabs-list-color);
            overflow-x: var(--pk-tabs-list-overflow-x, auto);
            overflow-y: var(--pk-tabs-list-overflow-y, visible);
        }

        /* Pane — matches plugin-kit-react PaneTabs */
        :host([variant='pane']) {
            --pk-tabs-root-gap: 0;
            --pk-tabs-root-radius: var(--pk-radius-lg);
            --pk-tabs-root-shadow:
                0 0 0 1px var(--pk-color-gray-200),
                0 2px 12px rgb(205 216 228 / 50%);
            --pk-tabs-root-overflow: visible;

            --pk-tabs-list-display: flex;
            --pk-tabs-list-width: auto;
            --pk-tabs-list-align-self: stretch;
            --pk-tabs-list-align-items: flex-end;
            --pk-tabs-list-padding: 0;
            --pk-tabs-list-border-width: 0;
            --pk-tabs-list-radius: var(--pk-radius-lg) var(--pk-radius-lg) 0 0;
            --pk-tabs-list-bg: var(--pk-color-gray-50);
            /* Must not use keyword none — box-shadow: none, inset … is invalid and drops the hairline. */
            --pk-tabs-list-shadow: inset 0 -1px 0 0 rgb(154 165 177 / 25%);
            --pk-tabs-list-inset-shadow: 0 0 #0000;
            --pk-tabs-list-overflow-x: auto;
            --pk-tabs-list-overflow-y: visible;

            --pk-tabs-trigger-display: flex;
            --pk-tabs-trigger-justify: flex-start;
            --pk-tabs-trigger-min-height: 45px;
            --pk-tabs-trigger-padding-block: 0;
            --pk-tabs-trigger-padding-inline: 24px;
            --pk-tabs-trigger-radius: 0;
            --pk-tabs-trigger-border-top: 0 solid transparent;
            --pk-tabs-trigger-color: var(--pk-color-gray-550);
            --pk-tabs-trigger-font-size: var(--pk-font-size-base);
            --pk-tabs-trigger-font-weight: 400;
            --pk-tabs-trigger-hover-bg: var(--pk-color-slate-100);
            --pk-tabs-trigger-hover-color: var(--pk-color-gray-550);
            --pk-tabs-trigger-selected-bg: var(--pk-color-white);
            --pk-tabs-trigger-selected-color: var(--pk-color-gray-700);
            --pk-tabs-trigger-selected-border-top: 0 solid transparent;
            /* Match Craft .pane-tabs [role=tab].sel — inset top accent + elevation. */
            --pk-tabs-trigger-selected-shadow:
                inset 0 2px 0 var(--pk-color-gray-500),
                0 0 0 1px rgb(51 64 77 / 10%),
                0 2px 12px rgb(205 216 228 / 90%);
            --pk-tabs-trigger-selected-radius: 2px 2px 0 0;

            /* 0% basis — panel fills leftover height and scrolls; auto basis grew with
             * content and clipped under dialog overflow:hidden (Edit Buttons Appearance). */
            --pk-tabs-panel-flex: 1 1 0%;
            --pk-tabs-panel-min-height: 0;
            /*
             * No built-in panel inset — matches v1 PaneTabsContent (padding came from
             * the consumer: ReportTabPanel / FormBuilderTabContent / DefaultsPanel p-6).
             * A non-zero value here double-pads those surfaces.
             */
            --pk-tabs-panel-padding: 0;
            --pk-tabs-panel-bg: var(--pk-color-white);
            --pk-tabs-panel-radius: 0 0 var(--pk-radius-lg) var(--pk-radius-lg);
            --pk-tabs-panel-font-size: var(--pk-font-size-sm, 14px);
        }

        /* Craft bumps the first tab start corner to --radius-lg so the inset
         * accent follows the pane radius instead of reading as clipped at 2px.
         */
        :host([variant='pane']) ::slotted(pk-tab:first-child) {
            --pk-tabs-trigger-selected-radius: var(--pk-radius-lg) 2px 0 0;
        }

        /* Modal — matches plugin-kit-react ModalTabs */
        :host([variant='modal']) {
            --pk-tabs-root-gap: 0;
            --pk-tabs-root-height: 100%;
            /* Dialog already rounds the panel — host radius + overflow clips the
             * first tab’s focus ring into a one-corner “rounded border”. */
            --pk-tabs-root-radius: 0;
            /* Clip to the height chain so panels scroll inside, not through the footer. */
            --pk-tabs-root-overflow: hidden;

            --pk-tabs-list-display: flex;
            --pk-tabs-list-width: 100%;
            --pk-tabs-list-align-self: stretch;
            --pk-tabs-list-align-items: stretch;
            --pk-tabs-list-padding: 0;
            --pk-tabs-list-border-width: 0;
            --pk-tabs-list-border-bottom: 1px solid var(--pk-color-gray-100);
            --pk-tabs-list-radius: 0;
            --pk-tabs-list-bg: var(--pk-color-white);
            --pk-tabs-list-shadow: 0 1px 5px #cdd8e440;
            /* Transparent no-op — keyword none in a multi-shadow list invalidates the whole property. */
            --pk-tabs-list-inset-shadow: 0 0 #0000;
            --pk-tabs-list-color: inherit;
            --pk-tabs-list-overflow-x: auto;
            /* auto (not hidden): overflow-x:auto + overflow-y:hidden clips ~1 device
             * pixel of the bottom active underline, so the 2px sky bar reads as 1px. */
            --pk-tabs-list-overflow-y: auto;
            /* v1 ModalTabsList z-11 — keep the strip above scrolling panel content
             * (editable-table action columns, etc.) when body/panel scrolls. */
            --pk-tabs-list-z-index: 11;

            --pk-tabs-trigger-display: inline-flex;
            --pk-tabs-trigger-justify: center;
            --pk-tabs-trigger-min-height: auto;
            --pk-tabs-trigger-padding-block: 15px;
            --pk-tabs-trigger-padding-inline: 15px;
            --pk-tabs-trigger-radius: 0;
            --pk-tabs-trigger-color: #64788d;
            --pk-tabs-trigger-font-size: 12px;
            --pk-tabs-trigger-font-weight: 500;
            --pk-tabs-trigger-text-transform: uppercase;
            --pk-tabs-trigger-hover-bg: transparent;
            --pk-tabs-trigger-hover-color: var(--pk-color-sky-600);
            --pk-tabs-trigger-selected-bg: transparent;
            --pk-tabs-trigger-selected-color: #64788d;
            --pk-tabs-trigger-selected-hover-bg: transparent;
            --pk-tabs-trigger-selected-hover-color: var(--pk-color-sky-600);
            /* Active (mouse) = 15px-inset underline. Active + :focus-visible = screen3 box. */
            --pk-tabs-trigger-selected-shadow: none;
            --pk-tabs-trigger-selected-radius: 0;
            --pk-tabs-trigger-underline-height: 2px;
            --pk-tabs-trigger-underline-inset: 15px;
            --pk-tabs-trigger-focus-selected-shadow: inset 0 0 0 2px var(--pk-color-sky-600);
            --pk-tabs-trigger-focus-selected-underline-height: 0;

            /* 0% basis — panel fills leftover height and scrolls; auto basis grew with
             * content and clipped under dialog overflow:hidden (Edit Buttons Appearance). */
            --pk-tabs-panel-flex: 1 1 0%;
            --pk-tabs-panel-min-height: 0;
            --pk-tabs-panel-padding: 1rem;
            --pk-tabs-panel-bg: transparent;
            --pk-tabs-panel-radius: 0;
            --pk-tabs-panel-font-size: var(--pk-font-size-sm, 14px);
        }

        /* Sidebar — vertical nav list with optional icons, status, and headings */
        :host([variant='sidebar']) {
            --pk-tabs-root-gap: 0;
            --pk-tabs-root-radius: 0;
            --pk-tabs-root-shadow: none;
            --pk-tabs-root-overflow: visible;

            --pk-tabs-list-display: flex;
            --pk-tabs-list-width: var(--pk-tabs-sidebar-width, 14rem);
            --pk-tabs-list-align-self: stretch;
            --pk-tabs-list-align-items: stretch;
            --pk-tabs-list-padding: 0.5rem;
            --pk-tabs-list-border-width: 0;
            --pk-tabs-list-border-bottom: 0 solid transparent;
            --pk-tabs-list-radius: 0;
            --pk-tabs-list-bg: var(--pk-color-gray-100);
            --pk-tabs-list-shadow: 0 0 #0000;
            --pk-tabs-list-inset-shadow: 0 0 #0000;
            --pk-tabs-list-color: var(--pk-color-gray-600);
            --pk-tabs-list-overflow-x: hidden;
            --pk-tabs-list-overflow-y: auto;

            --pk-tabs-trigger-display: flex;
            --pk-tabs-trigger-justify: flex-start;
            --pk-tabs-trigger-width: 100%;
            /* Match Craft/Formie integrations nav: padding 7px 10px, 16px icons, content-sized height. */
            --pk-tabs-trigger-gap: 10px;
            --pk-tabs-trigger-min-height: 0;
            --pk-tabs-trigger-padding-block: 7px;
            --pk-tabs-trigger-padding-inline: 10px;
            --pk-tabs-trigger-radius: var(--pk-radius-md);
            --pk-tabs-trigger-color: var(--pk-color-gray-700);
            --pk-tabs-trigger-font-size: 13px;
            --pk-tabs-trigger-font-weight: 400;
            --pk-tabs-trigger-line-height: 1.2;
            --pk-tabs-trigger-text-align: start;
            --pk-tabs-trigger-text-transform: none;
            --pk-tabs-trigger-border-top: 0 solid transparent;
            --pk-tabs-trigger-hover-bg: color-mix(in oklab, var(--pk-color-gray-200) 70%, transparent);
            --pk-tabs-trigger-hover-color: var(--pk-color-gray-800);
            --pk-tabs-trigger-selected-bg: var(--pk-color-gray-500);
            --pk-tabs-trigger-selected-color: var(--pk-color-white);
            --pk-tabs-trigger-selected-hover-bg: var(--pk-color-gray-500);
            --pk-tabs-trigger-selected-hover-color: var(--pk-color-white);
            --pk-tabs-trigger-selected-shadow: none;
            --pk-tabs-trigger-selected-radius: var(--pk-radius-md);
            --pk-tabs-trigger-selected-border-top: 0 solid transparent;
            --pk-tabs-trigger-underline-height: 0;
            --pk-tabs-trigger-label-flex: 1 1 auto;
            --pk-tabs-trigger-icon-size: 16px;
            --pk-tabs-trigger-status-margin: auto;
            --pk-tabs-trigger-status-color: var(--pk-color-gray-400);

            --pk-tabs-heading-padding: 14px 10px 5px;
            --pk-tabs-heading-color: var(--pk-color-gray-400);
            --pk-tabs-heading-font-size: 11px;
            --pk-tabs-heading-font-weight: 600;
            --pk-tabs-heading-letter-spacing: 0.04em;
            --pk-tabs-heading-text-transform: uppercase;

            /* 0% basis — panel fills leftover height and scrolls; auto basis grew with
             * content and clipped under dialog overflow:hidden (Edit Buttons Appearance). */
            --pk-tabs-panel-flex: 1 1 0%;
            --pk-tabs-panel-min-height: 0;
            --pk-tabs-panel-padding: 1.25rem;
            --pk-tabs-panel-bg: var(--pk-color-white);
            --pk-tabs-panel-radius: 0;
            --pk-tabs-panel-font-size: var(--pk-font-size-base);
        }

        :host([variant='sidebar']) .tabs {
            flex-direction: row;
            align-items: stretch;
            gap: 0;
        }

        :host([variant='sidebar']) .tabs[data-placement='end'] {
            flex-direction: row-reverse;
        }

        :host([variant='sidebar']) .list {
            flex-direction: column;
            gap: 0.125rem;
            flex: none;
        }

        :host([variant='sidebar']) ::slotted(pk-tab) {
            display: block;
            width: 100%;
        }

        :host([variant='sidebar']) ::slotted(pk-tab-heading) {
            display: block;
            width: 100%;
        }

        /* First group heading sits closer to the list top edge */
        :host([variant='sidebar']) ::slotted(pk-tab-heading:first-child) {
            --pk-tabs-heading-padding: 10px 10px 5px;
        }

        /* Hollow inactive dots read better on the selected dark pill */
        :host([variant='sidebar']) ::slotted(pk-tab[selected]) {
            --pk-tabs-trigger-status-color: var(--pk-color-gray-300);
        }
    }
`,$=class extends O{constructor(...e){super(...e),this.value=``,this.variant=`default`,this.orientation=`horizontal`,this.placement=`top`,this.activation=`manual`,this.disabled=!1,this.ariaLabel=null,this.baseId=_(`pk-tabs`),this.tabs=[],this.panels=[],this.focusedValue=``,this.syncTabs=()=>{let e=this.shadowRoot?.querySelector(`slot[name="nav"]`);e&&(this.tabs=e.assignedElements({flatten:!0}).filter(e=>e.tagName===`PK-TAB`),this.ensureDefaultValue(),this.applySelection())},this.syncPanels=()=>{let e=this.shadowRoot?.querySelector(`slot:not([name])`);e&&(this.panels=e.assignedElements({flatten:!0}).filter(e=>e.tagName===`PK-TAB-PANEL`),this.applySelection())},this.handleTabSelect=e=>{if(!this.isOwnTabEvent(e)||this.disabled)return;e.stopPropagation();let{value:t}=e.detail;if(t===this.value&&this.activation===`manual`){this.focusedValue=t,this.applySelection();return}t!==this.value&&this.selectTab(t)},this.handleTabKeyDown=e=>{if(!this.isOwnTabEvent(e))return;e.stopPropagation();let t=e.detail.event,n=this.getEnabledTabs();if(n.length===0)return;let r=n.findIndex(t=>t.value===e.detail.value);if(r<0)return;let i=r,a=this.getEffectiveOrientation()===`horizontal`;switch(t.key){case`ArrowDown`:if(a)return;t.preventDefault(),i=r>=n.length-1?0:r+1;break;case`ArrowUp`:if(a)return;t.preventDefault(),i=r<=0?n.length-1:r-1;break;case`ArrowRight`:if(!a)return;t.preventDefault(),i=r>=n.length-1?0:r+1;break;case`ArrowLeft`:if(!a)return;t.preventDefault(),i=r<=0?n.length-1:r-1;break;case`Home`:t.preventDefault(),i=0;break;case`End`:t.preventDefault(),i=n.length-1;break;default:return}let o=n[i];o&&(this.activation===`auto`?o.value===this.value?o.focusControl():this.selectTab(o.value):(this.focusedValue=o.value,this.applySelection(),o.focusControl()))}}static{this.styles=Un}connectedCallback(){super.connectedCallback(),this.addEventListener(`pk-tab-select`,this.handleTabSelect),this.addEventListener(`pk-tab-keydown`,this.handleTabKeyDown)}disconnectedCallback(){this.removeEventListener(`pk-tab-select`,this.handleTabSelect),this.removeEventListener(`pk-tab-keydown`,this.handleTabKeyDown),super.disconnectedCallback()}updated(e){(e.has(`value`)||e.has(`disabled`)||e.has(`activation`))&&(e.has(`value`)&&(this.focusedValue=this.value),this.applySelection())}ensureDefaultValue(){if(this.value||this.tabs.length===0)return;let e=this.tabs.find(e=>!e.disabled&&!this.disabled);e&&(this.value=e.value,this.focusedValue=this.value)}getEnabledTabs(){return this.tabs.filter(e=>!e.disabled&&!this.disabled)}getEffectiveOrientation(){return this.variant===`sidebar`?`vertical`:this.orientation}getEffectivePlacement(){return this.variant===`sidebar`&&(this.placement===`top`||this.placement===`bottom`)?`start`:this.placement}applySelection(){let e=this.getAttribute(`data-current-value`)??``,t=this.activation===`manual`?this.focusedValue:this.value;for(let e of this.tabs){let n=e.value===this.value,r=`${this.baseId}-tab-${e.value}`,i=`${this.baseId}-panel-${e.value}`;e.selected=n,e.disabled=this.disabled||e.hasAttribute(`disabled`),e.focusIndex=e.value===t?0:-1,e.panelId=i,e.id=r}for(let t of this.panels){let n=t.value===this.value,r=`${this.baseId}-tab-${t.value}`,i=`${this.baseId}-panel-${t.value}`;t.hidden!==!n&&(n?this.dispatchEvent(new CustomEvent(`pk-tab-show`,{detail:{value:t.value},bubbles:!0,composed:!0})):e===t.value&&this.dispatchEvent(new CustomEvent(`pk-tab-hide`,{detail:{value:t.value},bubbles:!0,composed:!0}))),t.hidden=!n,t.tabId=r,t.id=i}this.setAttribute(`data-current-value`,this.value)}isOwnTabEvent(e){let t=e.target;return t instanceof HTMLElement&&t.tagName===`PK-TAB`&&this.tabs.includes(t)}selectTab(e){this.value=e,this.focusedValue=e,this.applySelection(),this.dispatchEvent(new CustomEvent(`pk-change`,{detail:{value:this.value},bubbles:!0,composed:!0}))}render(){let e=this.getEffectiveOrientation();return C`
            <div part="base" class="tabs pk-tabs" data-placement=${this.getEffectivePlacement()}>
                <div
                    part="list"
                    class="list pk-tabs__list"
                    role="tablist"
                    aria-orientation=${e}
                    aria-label=${this.ariaLabel??S}
                    @slotchange=${this.syncTabs}
                >
                    <slot name="nav"></slot>
                </div>
                <slot @slotchange=${this.syncPanels}></slot>
            </div>
        `}};k([T()],$.prototype,`value`,void 0),k([T({reflect:!0})],$.prototype,`variant`,void 0),k([T({reflect:!0})],$.prototype,`orientation`,void 0),k([T({reflect:!0})],$.prototype,`placement`,void 0),k([T({reflect:!0})],$.prototype,`activation`,void 0),k([T({type:Boolean,reflect:!0})],$.prototype,`disabled`,void 0),k([T({attribute:`aria-label`})],$.prototype,`ariaLabel`,void 0),k([x()],$.prototype,`tabs`,void 0),k([x()],$.prototype,`panels`,void 0),k([x()],$.prototype,`focusedValue`,void 0),$=k([A(`pk-tabs`)],$);var Wn=m({tagName:`pk-tabs`,elementClass:$,react:V.default,events:{onPkChange:`pk-change`,onPkTabShow:`pk-tab-show`,onPkTabHide:`pk-tab-hide`}}),Gn=m({tagName:`pk-tab`,elementClass:Ln,react:V.default,events:{onPkTabSelect:`pk-tab-select`,onPkTabKeydown:`pk-tab-keydown`}});m({tagName:`pk-tab-heading`,elementClass:zn,react:V.default});var Kn=m({tagName:`pk-tab-panel`,elementClass:Hn,react:V.default});function qn({onPkChange:e,onPkTabShow:t,onPkTabHide:n,...r}){return(0,H.jsx)(Wn,{...r,...e?{onPkChange:t=>{B(t)&&e(t)}}:{},...t?{onPkTabShow:e=>{B(e)&&t(e)}}:{},...n?{onPkTabHide:e=>{B(e)&&n(e)}}:{}})}var Jn=Gn,Yn=Kn,Xn=m({tagName:`pk-spinner`,elementClass:he,react:V.default});function Zn({variant:e=`default`,size:t=`sm`,tone:n,centered:r=!1,...i}){return(0,H.jsx)(Xn,{variant:e,size:t,...n?{tone:n}:{},...r?{centered:!0}:{},...i})}export{Le as _,Fn as a,F as b,Tn as c,Gt as d,Ht as f,It as g,G as h,qn as i,En as l,K as m,Jn as n,Nn as o,Vt as p,Yn as r,Dn as s,Zn as t,Ut as u,We as v,Re as y};