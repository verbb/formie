const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/chunks/address-finder.DG_xxYZl.js","assets/chunks/scripts.B1PcyFNa.js","assets/chunks/framework.BBqb3frr.js","assets/chunks/google-address.CQ1hu-4Q.js","assets/chunks/loqate.A_1LGF1B.js","assets/chunks/place-kit.C4xW7SZX.js","assets/chunks/styles.DqkRI_my.js","assets/chunks/captcha-eu.Bu3-IL9V.js","assets/chunks/friendly-captcha-v1.D7ei7qks.js","assets/chunks/friendly-captcha-v2.0m4sTsrI.js","assets/chunks/hcaptcha.Bix7uUo6.js","assets/chunks/recaptcha-enterprise.DFCXHaZF.js","assets/chunks/recaptcha-shared.BU6wX0Pb.js","assets/chunks/recaptcha-v2-checkbox.Ga86USoS.js","assets/chunks/recaptcha-v2-invisible.BZWI9MYk.js","assets/chunks/recaptcha-v3.b0B9CpfI.js","assets/chunks/snaptcha.DxtPFxDX.js","assets/chunks/turnstile.DeUITnUx.js","assets/chunks/calculations.BbwT2hZA.js","assets/chunks/shared.D5c7EeUl.js","assets/chunks/checkbox-radio.yfbCR0qN.js","assets/chunks/combobox.C4TR9Wx6.js","assets/chunks/conditions.vQcmDxpm.js","assets/chunks/custom-google-maps.BaKdbh6M.js","assets/chunks/custom-link.CjgQ5viK.js","assets/chunks/custom-maps.CkG5ECfh.js","assets/chunks/date-picker.CyaTeySe.js","assets/chunks/file-upload.H7pIk1qp.js","assets/chunks/upload-manager.CzPng_5C.js","assets/chunks/hidden.FYzesiH2.js","assets/chunks/phone-country.UgY7eZbe.js","assets/chunks/country-from-ip.TqmP5LDW.js","assets/chunks/password-validation.xFZzM-rE.js","assets/chunks/address-country.RHFd8MwL.js","assets/chunks/address-state.BwoqPlg9.js","assets/chunks/repeater.D01gbmjU.js","assets/chunks/rich-text.CLF7PZb5.js","assets/chunks/signature.D8n-JgVN.js","assets/chunks/summary.DYS5h9bF.js","assets/chunks/survey-likert.Cs3kPu_e.js","assets/chunks/survey-presentations.3wSESoEp.js","assets/chunks/survey-rank.DYPLwHBB.js","assets/chunks/survey-rating.DTYdjT6S.js","assets/chunks/table.CBksq9L-.js","assets/chunks/text-limit.DuCivewJ.js","assets/chunks/bpoint.C48Q_S-w.js","assets/chunks/eway.DZEPLzoC.js","assets/chunks/go-cardless.04dlsmfK.js","assets/chunks/mollie.DIuEjMBU.js","assets/chunks/moneris.JzvHD_pk.js","assets/chunks/opayo.rv15XskG.js","assets/chunks/paddle.DPaR9LHX.js","assets/chunks/paypal.BpIp-XGo.js","assets/chunks/payway.BbtgluQO.js","assets/chunks/square.CR5QhijA.js","assets/chunks/stripe.B43jDneW.js","assets/chunks/categories.preview.ixyBoeER.js","assets/chunks/elementDisplayPreview.BQWAWWZ5.js","assets/chunks/entries.preview.vVoUh2wl.js","assets/chunks/recipients.preview.BWBx9rU1.js"])))=>i.map(i=>d[i]);
var ds=Object.defineProperty;var fs=(t,e,r)=>e in t?ds(t,e,{enumerable:!0,configurable:!0,writable:!0,value:r}):t[e]=r;var Ln=(t,e,r)=>fs(t,typeof e!="symbol"?e+"":e,r);import{u as Vt,w as pt,a as di,o as zt,b as jo,c as fe,r as $e,d as gt,e as j,f as H,n as Pe,F as Xe,g as it,h as we,i as fi,j as F,t as Ae,k as ct,l as Br,m as Tt,p as qo,q as Zt,s as ve,v as En,x as Ho,y as It,z as Nr,_ as q,A as ms,B as Gi,C as hs,D as ps,E as gs,G as Xt,H as Ji,I as bs,J as vs,K as ys,L as Zr,M as ws,N as xs,T as Es,O as Yn,P as ks,Q as Ss,R as _s,S as As,U as Zi,V as Ts,W as Cs,X as Is,Y as Ls,Z as Ms,$ as Uo,a0 as Ns,a1 as Qi}from"./framework.BBqb3frr.js";const Rs=/#.*$/,Os=/[?#].*$/,Ps=/(?:(^|\/)index)?\.(?:md|html)$/;function Xi(t){return decodeURI(t).replace(Os,"").replace(Ps,"$1")}function eo(t){return/^\//.test(t)?t:`/${t}`}function sn(t,e){return t.map(r=>{const n={...r},i=n.base||e;return i&&n.link&&(n.link=`${i}${n.link}`),n.items&&(n.items=sn(n.items,i)),n})}function kn(t,e,r=!1){if(e===void 0)return!1;const n=Xi(`/${t}`);if(r)return new RegExp(e).test(n);if(Xi(e)!==n)return!1;const i=e.match(Rs);return i?typeof window<"u"&&window.location.hash===i[0]:!0}function qr(t,e){var r;return e?kn(t,e.link)?!0:((r=e.items)==null?void 0:r.some(n=>qr(t,n)))??!1:!1}function $s(t,e){if(Array.isArray(t))return sn(t);if(!t)return[];const r=eo(e),n=Object.keys(t).sort((a,o)=>o.split("/").length-a.split("/").length).find(a=>r.startsWith(eo(a))),i=n?t[n]:[];return Array.isArray(i)?sn(i):sn(i.items,i.base)}function Fs(t){const e=[];let r=0;for(const n of t){if(n.items){e.push({text:n.text,icon:n.icon,items:n.items}),r=e.length-1;continue}e[r]||(e.push({items:[]}),r=e.length-1),e[r].items.push(n)}return e}function mi(){const{frontmatter:t,page:e,theme:r}=Vt(),n=$e(!1),i=fe(()=>$s(r.value.sidebar,e.value.relativePath)),a=fe(()=>Fs(i.value)),o=fe(()=>t.value.sidebar!==!1&&t.value.layout!=="home"&&i.value.length>0);pt(o,g=>{g||(n.value=!1)}),di(g=>{if(typeof document>"u")return;const b=document.body.style.overflow;n.value&&typeof window<"u"&&window.innerWidth<1024&&(document.body.style.overflow="hidden"),g(()=>{document.body.style.overflow=b})});function s(){n.value=!0}function u(){n.value=!1}function f(){n.value=!n.value}return{isOpen:n,sidebar:i,sidebarGroups:a,hasSidebar:o,open:s,close:u,toggle:f}}function Ds(t,e){let r=null;di(()=>{r=t.value?document.activeElement:null});const n=i=>{i.key==="Escape"&&t.value&&(e(),r instanceof HTMLElement&&r.focus())};zt(()=>{window.addEventListener("keyup",n)}),jo(()=>{window.removeEventListener("keyup",n)})}const zs=["d","fill"],Sn=gt({__name:"DocsIcon",props:{name:{default:""},class:{default:"size-4"}},setup(t){const e=t,r={"play-circle":{paths:[{d:"M8 14.25A6.25 6.25 0 1 0 8 1.75a6.25 6.25 0 0 0 0 12.5"},{d:"M6.25 5.75 10.25 8l-4 2.25V5.75",fill:"currentColor"}]},"app-window":{paths:[{d:"M2.75 4.25A1.5 1.5 0 0 1 4.25 2.75h7.5a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5 1.5h-7.5a1.5 1.5 0 0 1-1.5-1.5v-7.5Z"},{d:"M2.75 5.5h10.5"},{d:"M5 4.125h.01M7 4.125h.01M9 4.125h.01"}]},blocks:{paths:[{d:"M2.75 3.25h4.5v4.5h-4.5z"},{d:"M8.75 3.25h4.5v4.5h-4.5z"},{d:"M5.75 8.75h4.5v4.5h-4.5z"}]},"clipboard-list":{paths:[{d:"M5.25 3.25h5.5a1.5 1.5 0 0 1 1.5 1.5v7a1.5 1.5 0 0 1-1.5 1.5h-5.5a1.5 1.5 0 0 1-1.5-1.5v-7a1.5 1.5 0 0 1 1.5-1.5Z"},{d:"M6.25 2.75h3.5v1.5h-3.5z"},{d:"M6 6.5h3.75M6 8.5h3.75M6 10.5h3.75"},{d:"M5 6.5h.01M5 8.5h.01M5 10.5h.01"}]},"layout-template":{paths:[{d:"M2.75 3.25h10.5v9.5H2.75z"},{d:"M6.25 3.25v9.5"},{d:"M6.25 6.75h7"}]},"rows-3":{paths:[{d:"M3 4.5h1.5M6 4.5h7"},{d:"M3 8h1.5M6 8h7"},{d:"M3 11.5h1.5M6 11.5h7"}]},"square-terminal":{paths:[{d:"M3.25 3.25h9.5v9.5h-9.5z"},{d:"M5.25 6.25 7 8l-1.75 1.75"},{d:"M8.75 9.75h2.25"}]},"flask-conical":{paths:[{d:"M6 2.75h4"},{d:"M7 2.75v2.5l-3 5.25a1.5 1.5 0 0 0 1.3 2.25h5.4A1.5 1.5 0 0 0 12 10.5L9 5.25v-2.5"},{d:"M5.5 9h5"}]}},n=fe(()=>r[e.name]??null);return(i,a)=>n.value?(j(),H("svg",{key:0,viewBox:"0 0 16 16",fill:"none",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round",class:Pe(e.class),"aria-hidden":"true"},[(j(!0),H(Xe,null,it(n.value.paths,o=>(j(),H("path",{key:o.d,d:o.d,fill:o.fill??"none"},null,8,zs))),128))],2)):we("",!0)}}),Vs={class:"relative"},js={class:"min-w-0 flex-1 break-words"},qs=["href"],Hs={class:"flex min-w-0 flex-1 items-start gap-x-2.5"},Us={class:"flex min-w-0 flex-1 flex-wrap items-center gap-1.5 [word-break:break-word]"},Bs={class:"min-w-0 max-w-full break-words"},Ys=gt({__name:"DocsMobileMenuNode",props:{item:{},depth:{default:0}},emits:["navigate"],setup(t,{emit:e}){const r=t,n=e,{page:i}=Vt(),a=fi(),o=fe(()=>{var y;return!!((y=r.item.items)!=null&&y.length)}),s=fe(()=>kn(i.value.relativePath,r.item.link)),u=fe(()=>{var y;return((y=r.item.items)==null?void 0:y.some(E=>qr(i.value.relativePath,E)))??!1}),f=$e(o.value?!r.item.collapsed||u.value:!1);pt(u,y=>{y&&(f.value=!0)});function g(y){return y?Tt(y):"#"}async function b(y,E){E&&(y.preventDefault(),await a.go(g(E)),n("navigate"))}function p(){o.value&&(f.value=!f.value)}return(y,E)=>{const T=qo("DocsMobileMenuNode",!0);return j(),H("li",Vs,[o.value?(j(),H("button",{key:0,type:"button",class:Pe(["group flex w-full cursor-pointer items-center py-0.5 pr-2 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",u.value?"text-docs-primary":"text-slate-700"]),onClick:p},[F("span",js,Ae(t.item.text),1),(j(),H("svg",{viewBox:"0 0 640 640",class:Pe(["size-3 shrink-0 transition-transform",f.value?"rotate-90":"rotate-0"]),"aria-hidden":"true"},[...E[2]||(E[2]=[F("path",{d:"M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"},null,-1)])],2))],2)):(j(),H("a",{key:1,href:g(t.item.link),class:Pe(["group flex w-full cursor-pointer items-center py-0.5 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",s.value?"text-docs-primary":"text-slate-700"]),onClick:E[0]||(E[0]=v=>b(v,t.item.link))},[F("div",Hs,[t.item.icon?(j(),ct(Sn,{key:0,name:t.item.icon,class:"mt-1 size-4 shrink-0 text-slate-500 group-hover:text-slate-700"},null,8,["name"])):we("",!0),F("div",Us,[F("span",Bs,Ae(t.item.text),1)])])],10,qs)),o.value&&f.value?(j(),H("ul",{key:2,style:Br({marginLeft:t.depth===0?"1rem":"1.25rem"})},[(j(!0),H(Xe,null,it(t.item.items,v=>(j(),ct(T,{key:v.link??`${v.text}-${v.icon??""}`,item:v,depth:t.depth+1,onNavigate:E[1]||(E[1]=k=>n("navigate"))},null,8,["item","depth"]))),128))],4)):we("",!0)])}}}),Ws={class:"min-h-full bg-white"},Ks={class:"border-b border-slate-200/80 px-4 pb-4 pt-5"},Gs={class:"flex min-w-0 items-center gap-3"},Js=["src"],Zs={key:1,class:"min-w-0 truncate text-base font-semibold tracking-[-0.01em] text-slate-900"},Qs={class:"px-4 pb-6 pt-6"},Xs={"aria-label":"Sidebar navigation",class:"text-sm"},el={key:0,class:"mb-3 flex items-center gap-2.5 text-sm font-medium text-slate-900"},tl={class:"space-y-px"},rl=gt({__name:"DocsMobileMenu",props:{logoSrc:{},siteTitle:{}},emits:["navigate"],setup(t){const{sidebarGroups:e}=mi(),r=fe(()=>e.value.filter(n=>{var i;return(i=n.items)==null?void 0:i.length}));return(n,i)=>(j(),H("div",Ws,[F("div",Ks,[F("div",Gs,[t.logoSrc?(j(),H("img",{key:0,src:t.logoSrc,alt:"",class:"block h-7 w-auto max-w-[156px] shrink-0 object-contain"},null,8,Js)):(j(),H("div",Zs,Ae(t.siteTitle),1))])]),F("div",Qs,[F("nav",Xs,[(j(!0),H(Xe,null,it(r.value,a=>{var o,s;return j(),H("section",{key:a.text??((s=(o=a.items)==null?void 0:o[0])==null?void 0:s.link),class:"mt-6 first:mt-0"},[a.text?(j(),H("h2",el,[a.icon?(j(),ct(Sn,{key:0,name:a.icon,class:"size-4 text-slate-600"},null,8,["name"])):we("",!0),Zt(" "+Ae(a.text),1)])):we("",!0),F("ul",tl,[(j(!0),H(Xe,null,it(a.items,u=>(j(),ct(Ys,{key:u.link??`${u.text}-${u.icon??""}`,item:u,onNavigate:i[0]||(i[0]=f=>n.$emit("navigate"))},null,8,["item"]))),128))])])}),128))])])]))}}),nl={class:"flex min-h-[calc(100dvh-14rem)] w-full flex-col items-center justify-center px-6 py-16 text-center sm:px-8 sm:py-24 lg:min-h-[calc(100dvh-10rem)]"},il={class:"text-6xl font-semibold tracking-[-0.04em] text-slate-900 sm:text-7xl"},ol={class:"mt-3 text-2xl font-semibold tracking-[-0.03em] text-slate-900 sm:text-3xl"},al={class:"mt-5 max-w-sm text-sm leading-6 text-slate-600"},sl=["href","aria-label"],ll=gt({__name:"DocsNotFound",setup(t){const{theme:e}=Vt(),r=fe(()=>{var s;return((s=e.value.notFound)==null?void 0:s.code)??"404"}),n=fe(()=>{var s;return((s=e.value.notFound)==null?void 0:s.title)??"Page not found"}),i=fe(()=>{var s;return((s=e.value.notFound)==null?void 0:s.quote)??"The page you requested does not exist or may have moved."}),a=fe(()=>{var s;return((s=e.value.notFound)==null?void 0:s.linkLabel)??"Go to home"}),o=fe(()=>{var s;return((s=e.value.notFound)==null?void 0:s.linkText)??"Take me home"});return(s,u)=>(j(),H("section",nl,[F("p",il,Ae(r.value),1),F("h1",ol,Ae(n.value),1),u[0]||(u[0]=F("div",{class:"mt-6 h-px w-16 bg-slate-200"},null,-1)),F("p",al,Ae(i.value),1),F("a",{href:ve(Tt)("/"),"aria-label":a.value,class:"mt-7 inline-flex items-center rounded-xl border border-docs-primary-border bg-docs-primary-soft px-4 py-2 text-sm font-medium text-docs-primary transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft-hover"},Ae(o.value),9,sl)]))}}),cl={id:"table-of-contents-content",class:"toc"},ul=["data-depth","data-active","data-active-deepest"],dl=["href","onClick"],fl=gt({__name:"DocsOutlineItem",props:{items:{}},setup(t){const e=t;function r(n,i){const a=i.replace(/^#/,""),o=document.getElementById(a);o&&(n.preventDefault(),o.scrollIntoView({block:"start",behavior:"smooth"}),window.location.hash=i)}return(n,i)=>(j(),H("ul",cl,[(j(!0),H(Xe,null,it(e.items,a=>(j(),H("li",{key:a.link,class:Pe(["toc-item relative",a.depth>0?a.active?"border-l pl-4 border-docs-primary hover:border-docs-primary":"border-l pl-4 border-slate-950/5 hover:border-slate-950/20":""]),"data-depth":a.depth,"data-active":a.active||void 0,"data-active-deepest":a.activeDeepest||void 0},[F("a",{href:a.link,style:Br(a.depth>0?"padding-left:1rem":void 0),class:Pe(["break-words py-1",[a.depth>0?"group flex items-start whitespace-pre-wrap":"block border-l pl-4 font-medium",a.active?a.depth>0?"text-docs-primary":"text-docs-primary border-docs-primary hover:border-docs-primary":a.depth>0?"text-gray-500 hover:text-gray-900":"border-slate-950/5 hover:border-slate-950/20 hover:text-gray-900"]]),onClick:o=>r(o,a.link)},Ae(a.title),15,dl)],10,ul))),128))]))}}),ml={key:0,id:"table-of-contents","aria-label":"On this page",class:"space-y-2"},hl={type:"button",class:"flex cursor-pointer items-center space-x-2 text-sm font-medium text-slate-700 transition-colors hover:text-slate-900"},pl=gt({__name:"DocsOutline",setup(t){const{frontmatter:e,theme:r}=Vt(),n=fe(()=>{const k=r.value.outline;return typeof k=="object"&&!Array.isArray(k)&&(k==null?void 0:k.label)||r.value.outlineTitle||"On this page"}),i=$e(null),a=$e([]),o=fe(()=>{var A;const k=E(a.value,i.value),C=new Set(k.map(L=>L.link)),S=((A=k.at(-1))==null?void 0:A.link)??null;return y(a.value).map(L=>({...L,depth:Math.max(L.level-2,0),active:C.has(L.link),activeDeepest:L.link===S}))});function s(){return document.getElementById("docs-scroll-container")??document.getElementById("content-container")}function u(k){const C=Number.parseFloat(getComputedStyle(document.documentElement).getPropertyValue("--scroll-mt"));return Number.isFinite(C)?C:Math.min(Math.max(k.clientHeight*.18,56),120)}function f(k){if(k===!1)return null;const C=(typeof k=="object"&&!Array.isArray(k)&&k&&"level"in k?k.level:k)??2;return C==="deep"?[2,6]:Array.isArray(C)?[C[0],C[1]]:[C,C]}function g(){const k=f(e.value.outline??r.value.outline);return k||null}function b(k){const C=/\b(?:VPBadge|header-anchor|footnote-ref|ignore-header)\b/;let S="";for(const A of k.childNodes)if(A.nodeType===Node.ELEMENT_NODE){const L=A;if(C.test(L.className))continue;S+=L.textContent??""}else A.nodeType===Node.TEXT_NODE&&(S+=A.textContent??"");return S.trim()}function p(){const k=g();if(!k){a.value=[];return}const[C,S]=k,A=Array.from(document.querySelectorAll(".vp-doc :where(h1,h2,h3,h4,h5,h6)")).filter(N=>N instanceof HTMLElement&&!!N.id).map(N=>{const ie=Number(N.tagName.slice(1));return{title:b(N),slug:N.id,link:`#${N.id}`,level:ie,children:[]}}).filter(N=>N.title&&N.level>=C&&N.level<=S),L=[],P=[];for(const N of A){for(;P.length&&P[P.length-1].level>=N.level;)P.pop();P.length?P[P.length-1].children.push(N):L.push(N),P.push(N)}a.value=L}function y(k){return k.flatMap(C=>[C,...y(C.children??[])])}function E(k,C){var S;if(!C)return[];for(const A of k){if(A.link===C)return[A];if((S=A.children)!=null&&S.length){const L=E(A.children,C);if(L.length)return[A,...L]}}return[]}function T(){var V,K,Y;const k=y(a.value),C=s();if(!k.length||!C){i.value=null;return}const S=C.scrollTop,A=C.clientHeight,L=C.scrollHeight,P=u(C),N=Math.abs(S+A-L)<1;if(S<1){const I=window.location.hash,R=k.some(U=>U.link===I)?I:null;i.value=R??((V=k[0])==null?void 0:V.link)??null;return}if(N){i.value=((K=k[k.length-1])==null?void 0:K.link)??null;return}const ie=window.location.hash,te=k.some(I=>I.link===ie)?ie:null;let ue=null;for(const I of k){const R=document.getElementById(I.slug);if(!R)continue;const U=C.getBoundingClientRect().top;if(S+R.getBoundingClientRect().top-U>S+P)break;ue=I.link}i.value=ue??te??((Y=k[0])==null?void 0:Y.link)??null}const v=()=>{T()};return zt(()=>{const k=s();requestAnimationFrame(()=>{p(),T()}),k==null||k.addEventListener("scroll",v,{passive:!0}),window.addEventListener("hashchange",v,{passive:!0})}),En(()=>{const k=s();k==null||k.removeEventListener("scroll",v),window.removeEventListener("hashchange",v)}),Ho(async()=>{await It(),p(),T()}),(k,C)=>a.value.length?(j(),H("nav",ml,[F("button",hl,[C[0]||(C[0]=F("svg",{viewBox:"0 0 16 16",fill:"none",stroke:"currentColor","stroke-width":"2",class:"h-3 w-3","aria-hidden":"true"},[F("path",{d:"M2.5 3.5h11M2.5 8h7M2.5 12.5h11","stroke-linecap":"round"})],-1)),F("span",null,Ae(n.value),1)]),Nr(fl,{items:o.value},null,8,["items"])])):we("",!0)}}),gl={root:()=>q(()=>import("./@localSearchIndexroot.DUtbrjUH.js"),[])};/*!
* tabbable 6.5.0
* @license MIT, https://github.com/focus-trap/tabbable/blob/master/LICENSE
*/var Bo=["input:not([inert]):not([inert] *)","select:not([inert]):not([inert] *)","textarea:not([inert]):not([inert] *)","a[href]:not([inert]):not([inert] *)","area[href]:not([inert]):not([inert] *)","button:not([inert]):not([inert] *)","[tabindex]:not(slot):not([inert]):not([inert] *)","audio[controls]:not([inert]):not([inert] *)","video[controls]:not([inert]):not([inert] *)",'[contenteditable]:not([contenteditable="false"]):not([inert]):not([inert] *)',"details>summary:first-of-type:not([inert]):not([inert] *)","details:not([inert]):not([inert] *)"],dn=Bo.join(","),Yo=typeof Element>"u",tr=Yo?function(){}:Element.prototype.matches||Element.prototype.msMatchesSelector||Element.prototype.webkitMatchesSelector,fn=!Yo&&Element.prototype.getRootNode?function(t){var e;return t==null||(e=t.getRootNode)===null||e===void 0?void 0:e.call(t)}:function(t){return t==null?void 0:t.ownerDocument},mn=function(e,r){var n;r===void 0&&(r=!0);var i=e==null||(n=e.getAttribute)===null||n===void 0?void 0:n.call(e,"inert"),a=i===""||i==="true",o=a||r&&e&&(typeof e.closest=="function"?e.closest("[inert]"):mn(e.parentNode));return o},bl=function(e){var r,n=e==null||(r=e.getAttribute)===null||r===void 0?void 0:r.call(e,"contenteditable");return n===""||n==="true"},Wo=function(e,r,n){if(mn(e))return[];var i=Array.prototype.slice.apply(e.querySelectorAll(dn));return r&&tr.call(e,dn)&&i.unshift(e),i=i.filter(n),i},hn=function(e,r,n){for(var i=[],a=Array.from(e);a.length;){var o=a.shift();if(!mn(o,!1))if(o.tagName==="SLOT"){var s=o.assignedElements(),u=s.length?s:o.children,f=hn(u,!0,n);n.flatten?i.push.apply(i,f):i.push({scopeParent:o,candidates:f})}else{var g=tr.call(o,dn);g&&n.filter(o)&&(r||!e.includes(o))&&i.push(o);var b=o.shadowRoot||typeof n.getShadowRoot=="function"&&n.getShadowRoot(o),p=!mn(b,!1)&&(!n.shadowRootFilter||n.shadowRootFilter(o));if(b&&p){var y=hn(b===!0?o.children:b.children,!0,n);n.flatten?i.push.apply(i,y):i.push({scopeParent:o,candidates:y})}else a.unshift.apply(a,o.children)}}return i},Ko=function(e){return!isNaN(parseInt(e.getAttribute("tabindex"),10))},Qt=function(e){if(!e)throw new Error("No node provided");return e.tabIndex<0&&(/^(AUDIO|VIDEO|DETAILS)$/.test(e.tagName)||bl(e))&&!Ko(e)?0:e.tabIndex},vl=function(e,r){var n=Qt(e);return n<0&&r&&!Ko(e)?0:n},yl=function(e,r){return e.tabIndex===r.tabIndex?e.documentOrder-r.documentOrder:e.tabIndex-r.tabIndex},Go=function(e){return e.tagName==="INPUT"},wl=function(e){return Go(e)&&e.type==="hidden"},xl=function(e){var r=e.tagName==="DETAILS"&&Array.prototype.slice.apply(e.children).some(function(n){return n.tagName==="SUMMARY"});return r},El=function(e,r){for(var n=0;n<e.length;n++)if(e[n].checked&&e[n].form===r)return e[n]},kl=function(e){if(!e.name)return!0;var r=e.form||fn(e),n=function(s){return r.querySelectorAll('input[type="radio"][name="'+s+'"]')},i;if(typeof window<"u"&&typeof window.CSS<"u"&&typeof window.CSS.escape=="function")i=n(window.CSS.escape(e.name));else try{i=n(e.name)}catch(o){return console.error("Looks like you have a radio button with a name attribute containing invalid CSS selector characters and need the CSS.escape polyfill: %s",o.message),!1}var a=El(i,e.form);return!a||a===e},Sl=function(e){return Go(e)&&e.type==="radio"},_l=function(e){return Sl(e)&&!kl(e)},Al=function(e){var r,n=e&&fn(e),i=(r=n)===null||r===void 0?void 0:r.host,a=!1;if(n&&n!==e){var o,s,u;for(a=!!((o=i)!==null&&o!==void 0&&(s=o.ownerDocument)!==null&&s!==void 0&&s.contains(i)||e!=null&&(u=e.ownerDocument)!==null&&u!==void 0&&u.contains(e));!a&&i;){var f,g,b;n=fn(i),i=(f=n)===null||f===void 0?void 0:f.host,a=!!((g=i)!==null&&g!==void 0&&(b=g.ownerDocument)!==null&&b!==void 0&&b.contains(i))}}return a},to=function(e){var r=e.getBoundingClientRect(),n=r.width,i=r.height;return n===0&&i===0},Tl=function(e,r){var n=r.displayCheck,i=r.getShadowRoot;if(n==="full-native"&&"checkVisibility"in e){var a=e.checkVisibility({checkOpacity:!1,opacityProperty:!1,contentVisibilityAuto:!0,visibilityProperty:!0,checkVisibilityCSS:!0});return!a}var o=getComputedStyle(e),s=o.visibility;if(s==="hidden"||s==="collapse")return!0;var u=tr.call(e,"details>summary:first-of-type"),f=u?e.parentElement:e;if(tr.call(f,"details:not([open]) *"))return!0;if(!n||n==="full"||n==="full-native"||n==="legacy-full"){if(typeof i=="function"){for(var g=e;e;){var b=e.parentElement,p=fn(e);if(b&&!b.shadowRoot&&i(b)===!0)return to(e);e.assignedSlot?e=e.assignedSlot:!b&&p!==e.ownerDocument?e=p.host:e=b}e=g}if(Al(e))return!e.getClientRects().length;if(n!=="legacy-full")return!0}else if(n==="non-zero-area")return to(e);return!1},Cl=function(e){if(/^(INPUT|BUTTON|SELECT|TEXTAREA)$/.test(e.tagName))for(var r=e.parentElement;r;){if(r.tagName==="FIELDSET"&&r.disabled){for(var n=0;n<r.children.length;n++){var i=r.children.item(n);if(i.tagName==="LEGEND")return tr.call(r,"fieldset[disabled] *")?!0:!i.contains(e)}return!0}r=r.parentElement}return!1},pn=function(e,r){return!(r.disabled||wl(r)||Tl(r,e)||xl(r)||Cl(r))},Wn=function(e,r){return!(_l(r)||Qt(r)<0||!pn(e,r))},Il=function(e){var r=parseInt(e.getAttribute("tabindex"),10);return!!(isNaN(r)||r>=0)},Jo=function(e){var r=[],n=[];return e.forEach(function(i,a){var o=!!i.scopeParent,s=o?i.scopeParent:i,u=vl(s,o),f=o?Jo(i.candidates):s;u===0?o?r.push.apply(r,f):r.push(s):n.push({documentOrder:a,tabIndex:u,item:i,isScope:o,content:f})}),n.sort(yl).reduce(function(i,a){return a.isScope?i.push.apply(i,a.content):i.push(a.content),i},[]).concat(r)},Ll=function(e,r){r=r||{};var n;return r.getShadowRoot?n=hn([e],r.includeContainer,{filter:Wn.bind(null,r),flatten:!1,getShadowRoot:r.getShadowRoot,shadowRootFilter:Il}):n=Wo(e,r.includeContainer,Wn.bind(null,r)),Jo(n)},Ml=function(e,r){r=r||{};var n;return r.getShadowRoot?n=hn([e],r.includeContainer,{filter:pn.bind(null,r),flatten:!0,getShadowRoot:r.getShadowRoot}):n=Wo(e,r.includeContainer,pn.bind(null,r)),n},cr=function(e,r){if(r=r||{},!e)throw new Error("No node provided");return tr.call(e,dn)===!1?!1:Wn(r,e)},Nl=Bo.concat("iframe:not([inert]):not([inert] *)").join(","),Mn=function(e,r){if(r=r||{},!e)throw new Error("No node provided");return tr.call(e,Nl)===!1?!1:pn(r,e)};/*!
* focus-trap 7.8.0
* @license MIT, https://github.com/focus-trap/focus-trap/blob/master/LICENSE
*/function Kn(t,e){(e==null||e>t.length)&&(e=t.length);for(var r=0,n=Array(e);r<e;r++)n[r]=t[r];return n}function Rl(t){if(Array.isArray(t))return Kn(t)}function ro(t,e){var r=typeof Symbol<"u"&&t[Symbol.iterator]||t["@@iterator"];if(!r){if(Array.isArray(t)||(r=Zo(t))||e){r&&(t=r);var n=0,i=function(){};return{s:i,n:function(){return n>=t.length?{done:!0}:{done:!1,value:t[n++]}},e:function(u){throw u},f:i}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var a,o=!0,s=!1;return{s:function(){r=r.call(t)},n:function(){var u=r.next();return o=u.done,u},e:function(u){s=!0,a=u},f:function(){try{o||r.return==null||r.return()}finally{if(s)throw a}}}}function Ol(t,e,r){return(e=zl(e))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}function Pl(t){if(typeof Symbol<"u"&&t[Symbol.iterator]!=null||t["@@iterator"]!=null)return Array.from(t)}function $l(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function no(t,e){var r=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);e&&(n=n.filter(function(i){return Object.getOwnPropertyDescriptor(t,i).enumerable})),r.push.apply(r,n)}return r}function io(t){for(var e=1;e<arguments.length;e++){var r=arguments[e]!=null?arguments[e]:{};e%2?no(Object(r),!0).forEach(function(n){Ol(t,n,r[n])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(r)):no(Object(r)).forEach(function(n){Object.defineProperty(t,n,Object.getOwnPropertyDescriptor(r,n))})}return t}function Fl(t){return Rl(t)||Pl(t)||Zo(t)||$l()}function Dl(t,e){if(typeof t!="object"||!t)return t;var r=t[Symbol.toPrimitive];if(r!==void 0){var n=r.call(t,e);if(typeof n!="object")return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}function zl(t){var e=Dl(t,"string");return typeof e=="symbol"?e:e+""}function Zo(t,e){if(t){if(typeof t=="string")return Kn(t,e);var r={}.toString.call(t).slice(8,-1);return r==="Object"&&t.constructor&&(r=t.constructor.name),r==="Map"||r==="Set"?Array.from(t):r==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?Kn(t,e):void 0}}var Lt={getActiveTrap:function(e){return(e==null?void 0:e.length)>0?e[e.length-1]:null},activateTrap:function(e,r){var n=Lt.getActiveTrap(e);r!==n&&Lt.pauseTrap(e);var i=e.indexOf(r);i===-1||e.splice(i,1),e.push(r)},deactivateTrap:function(e,r){var n=e.indexOf(r);n!==-1&&e.splice(n,1),Lt.unpauseTrap(e)},pauseTrap:function(e){var r=Lt.getActiveTrap(e);r==null||r._setPausedState(!0)},unpauseTrap:function(e){var r=Lt.getActiveTrap(e);r&&!r._isManuallyPaused()&&r._setPausedState(!1)}},Vl=function(e){return e.tagName&&e.tagName.toLowerCase()==="input"&&typeof e.select=="function"},jl=function(e){return(e==null?void 0:e.key)==="Escape"||(e==null?void 0:e.key)==="Esc"||(e==null?void 0:e.keyCode)===27},Or=function(e){return(e==null?void 0:e.key)==="Tab"||(e==null?void 0:e.keyCode)===9},ql=function(e){return Or(e)&&!e.shiftKey},Hl=function(e){return Or(e)&&e.shiftKey},oo=function(e){return setTimeout(e,0)},Lr=function(e){for(var r=arguments.length,n=new Array(r>1?r-1:0),i=1;i<r;i++)n[i-1]=arguments[i];return typeof e=="function"?e.apply(void 0,n):e},Qr=function(e){return e.target.shadowRoot&&typeof e.composedPath=="function"?e.composedPath()[0]:e.target},Ul=[],Bl=function(e,r){var n=(r==null?void 0:r.document)||document,i=(r==null?void 0:r.trapStack)||Ul,a=io({returnFocusOnDeactivate:!0,escapeDeactivates:!0,delayInitialFocus:!0,isolateSubtrees:!1,isKeyForward:ql,isKeyBackward:Hl},r),o={containers:[],containerGroups:[],tabbableGroups:[],adjacentElements:new Set,alreadySilent:new Set,nodeFocusedBeforeActivation:null,mostRecentlyFocusedNode:null,active:!1,paused:!1,manuallyPaused:!1,delayInitialFocusTimer:void 0,recentNavEvent:void 0},s,u=function(I,R,U){return I&&I[R]!==void 0?I[R]:a[U||R]},f=function(I,R){var U=typeof(R==null?void 0:R.composedPath)=="function"?R.composedPath():void 0;return o.containerGroups.findIndex(function(J){var W=J.container,me=J.tabbableNodes;return W.contains(I)||(U==null?void 0:U.includes(W))||me.find(function(se){return se===I})})},g=function(I){var R=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},U=R.hasFallback,J=U===void 0?!1:U,W=R.params,me=W===void 0?[]:W,se=a[I];if(typeof se=="function"&&(se=se.apply(void 0,Fl(me))),se===!0&&(se=void 0),!se){if(se===void 0||se===!1)return se;throw new Error("`".concat(I,"` was specified but was not a node, or did not return a node"))}var D=se;if(typeof se=="string"){try{D=n.querySelector(se)}catch(z){throw new Error("`".concat(I,'` appears to be an invalid selector; error="').concat(z.message,'"'))}if(!D&&!J)throw new Error("`".concat(I,"` as selector refers to no known node"))}return D},b=function(){var I=g("initialFocus",{hasFallback:!0});if(I===!1)return!1;if(I===void 0||I&&!Mn(I,a.tabbableOptions))if(f(n.activeElement)>=0)I=n.activeElement;else{var R=o.tabbableGroups[0],U=R&&R.firstTabbableNode;I=U||g("fallbackFocus")}else I===null&&(I=g("fallbackFocus"));if(!I)throw new Error("Your focus-trap needs to have at least one focusable element");return I},p=function(){if(o.containerGroups=o.containers.map(function(I){var R=Ll(I,a.tabbableOptions),U=Ml(I,a.tabbableOptions),J=R.length>0?R[0]:void 0,W=R.length>0?R[R.length-1]:void 0,me=U.find(function(z){return cr(z)}),se=U.slice().reverse().find(function(z){return cr(z)}),D=!!R.find(function(z){return Qt(z)>0});return{container:I,tabbableNodes:R,focusableNodes:U,posTabIndexesFound:D,firstTabbableNode:J,lastTabbableNode:W,firstDomTabbableNode:me,lastDomTabbableNode:se,nextTabbableNode:function(Q){var Se=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!0,re=R.indexOf(Q);return re<0?Se?U.slice(U.indexOf(Q)+1).find(function(he){return cr(he)}):U.slice(0,U.indexOf(Q)).reverse().find(function(he){return cr(he)}):R[re+(Se?1:-1)]}}}),o.tabbableGroups=o.containerGroups.filter(function(I){return I.tabbableNodes.length>0}),o.tabbableGroups.length<=0&&!g("fallbackFocus"))throw new Error("Your focus-trap must have at least one container with at least one tabbable node in it at all times");if(o.containerGroups.find(function(I){return I.posTabIndexesFound})&&o.containerGroups.length>1)throw new Error("At least one node with a positive tabindex was found in one of your focus-trap's multiple containers. Positive tabindexes are only supported in single-container focus-traps.")},y=function(I){var R=I.activeElement;if(R)return R.shadowRoot&&R.shadowRoot.activeElement!==null?y(R.shadowRoot):R},E=function(I){if(I!==!1&&I!==y(document)){if(!I||!I.focus){E(b());return}I.focus({preventScroll:!!a.preventScroll}),o.mostRecentlyFocusedNode=I,Vl(I)&&I.select()}},T=function(I){var R=g("setReturnFocus",{params:[I]});return R||(R===!1?!1:I)},v=function(I){var R=I.target,U=I.event,J=I.isBackward,W=J===void 0?!1:J;R=R||Qr(U),p();var me=null;if(o.tabbableGroups.length>0){var se=f(R,U),D=se>=0?o.containerGroups[se]:void 0;if(se<0)W?me=o.tabbableGroups[o.tabbableGroups.length-1].lastTabbableNode:me=o.tabbableGroups[0].firstTabbableNode;else if(W){var z=o.tabbableGroups.findIndex(function(ge){var _e=ge.firstTabbableNode;return R===_e});if(z<0&&(D.container===R||Mn(R,a.tabbableOptions)&&!cr(R,a.tabbableOptions)&&!D.nextTabbableNode(R,!1))&&(z=se),z>=0){var Q=z===0?o.tabbableGroups.length-1:z-1,Se=o.tabbableGroups[Q];me=Qt(R)>=0?Se.lastTabbableNode:Se.lastDomTabbableNode}else Or(U)||(me=D.nextTabbableNode(R,!1))}else{var re=o.tabbableGroups.findIndex(function(ge){var _e=ge.lastTabbableNode;return R===_e});if(re<0&&(D.container===R||Mn(R,a.tabbableOptions)&&!cr(R,a.tabbableOptions)&&!D.nextTabbableNode(R))&&(re=se),re>=0){var he=re===o.tabbableGroups.length-1?0:re+1,ke=o.tabbableGroups[he];me=Qt(R)>=0?ke.firstTabbableNode:ke.firstDomTabbableNode}else Or(U)||(me=D.nextTabbableNode(R))}}else me=g("fallbackFocus");return me},k=function(I){var R=Qr(I);if(!(f(R,I)>=0)){if(Lr(a.clickOutsideDeactivates,I)){s.deactivate({returnFocus:a.returnFocusOnDeactivate});return}Lr(a.allowOutsideClick,I)||I.preventDefault()}},C=function(I){var R=Qr(I),U=f(R,I)>=0;if(U||R instanceof Document)U&&(o.mostRecentlyFocusedNode=R);else{I.stopImmediatePropagation();var J,W=!0;if(o.mostRecentlyFocusedNode)if(Qt(o.mostRecentlyFocusedNode)>0){var me=f(o.mostRecentlyFocusedNode),se=o.containerGroups[me].tabbableNodes;if(se.length>0){var D=se.findIndex(function(z){return z===o.mostRecentlyFocusedNode});D>=0&&(a.isKeyForward(o.recentNavEvent)?D+1<se.length&&(J=se[D+1],W=!1):D-1>=0&&(J=se[D-1],W=!1))}}else o.containerGroups.some(function(z){return z.tabbableNodes.some(function(Q){return Qt(Q)>0})})||(W=!1);else W=!1;W&&(J=v({target:o.mostRecentlyFocusedNode,isBackward:a.isKeyBackward(o.recentNavEvent)})),E(J||o.mostRecentlyFocusedNode||b())}o.recentNavEvent=void 0},S=function(I){var R=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;o.recentNavEvent=I;var U=v({event:I,isBackward:R});U&&(Or(I)&&I.preventDefault(),E(U))},A=function(I){(a.isKeyForward(I)||a.isKeyBackward(I))&&S(I,a.isKeyBackward(I))},L=function(I){jl(I)&&Lr(a.escapeDeactivates,I)!==!1&&(I.preventDefault(),s.deactivate())},P=function(I){var R=Qr(I);f(R,I)>=0||Lr(a.clickOutsideDeactivates,I)||Lr(a.allowOutsideClick,I)||(I.preventDefault(),I.stopImmediatePropagation())},N=function(){if(o.active)return Lt.activateTrap(i,s),o.delayInitialFocusTimer=a.delayInitialFocus?oo(function(){E(b())}):E(b()),n.addEventListener("focusin",C,!0),n.addEventListener("mousedown",k,{capture:!0,passive:!1}),n.addEventListener("touchstart",k,{capture:!0,passive:!1}),n.addEventListener("click",P,{capture:!0,passive:!1}),n.addEventListener("keydown",A,{capture:!0,passive:!1}),n.addEventListener("keydown",L),s},ie=function(I){o.active&&!o.paused&&s._setSubtreeIsolation(!1),o.adjacentElements.clear(),o.alreadySilent.clear();var R=new Set,U=new Set,J=ro(I),W;try{for(J.s();!(W=J.n()).done;){var me=W.value;R.add(me);for(var se=typeof ShadowRoot<"u"&&me.getRootNode()instanceof ShadowRoot,D=me;D;){R.add(D);var z=D.parentElement,Q=[];z?Q=z.children:!z&&se&&(Q=D.getRootNode().children,z=D.getRootNode().host,se=typeof ShadowRoot<"u"&&z.getRootNode()instanceof ShadowRoot);var Se=ro(Q),re;try{for(Se.s();!(re=Se.n()).done;){var he=re.value;U.add(he)}}catch(ke){Se.e(ke)}finally{Se.f()}D=z}}}catch(ke){J.e(ke)}finally{J.f()}R.forEach(function(ke){U.delete(ke)}),o.adjacentElements=U},te=function(){if(o.active)return n.removeEventListener("focusin",C,!0),n.removeEventListener("mousedown",k,!0),n.removeEventListener("touchstart",k,!0),n.removeEventListener("click",P,!0),n.removeEventListener("keydown",A,!0),n.removeEventListener("keydown",L),s},ue=function(I){var R=I.some(function(U){var J=Array.from(U.removedNodes);return J.some(function(W){return W===o.mostRecentlyFocusedNode})});R&&E(b())},V=typeof window<"u"&&"MutationObserver"in window?new MutationObserver(ue):void 0,K=function(){V&&(V.disconnect(),o.active&&!o.paused&&o.containers.map(function(I){V.observe(I,{subtree:!0,childList:!0})}))};return s={get active(){return o.active},get paused(){return o.paused},activate:function(I){if(o.active)return this;var R=u(I,"onActivate"),U=u(I,"onPostActivate"),J=u(I,"checkCanFocusTrap"),W=Lt.getActiveTrap(i),me=!1;if(W&&!W.paused){var se;(se=W._setSubtreeIsolation)===null||se===void 0||se.call(W,!1),me=!0}try{J||p(),o.active=!0,o.paused=!1,o.nodeFocusedBeforeActivation=y(n),R==null||R();var D=function(){J&&p(),N(),K(),a.isolateSubtrees&&s._setSubtreeIsolation(!0),U==null||U()};if(J)return J(o.containers.concat()).then(D,D),this;D()}catch(Q){if(W===Lt.getActiveTrap(i)&&me){var z;(z=W._setSubtreeIsolation)===null||z===void 0||z.call(W,!0)}throw Q}return this},deactivate:function(I){if(!o.active)return this;var R=io({onDeactivate:a.onDeactivate,onPostDeactivate:a.onPostDeactivate,checkCanReturnFocus:a.checkCanReturnFocus},I);clearTimeout(o.delayInitialFocusTimer),o.delayInitialFocusTimer=void 0,o.paused||s._setSubtreeIsolation(!1),o.alreadySilent.clear(),te(),o.active=!1,o.paused=!1,K(),Lt.deactivateTrap(i,s);var U=u(R,"onDeactivate"),J=u(R,"onPostDeactivate"),W=u(R,"checkCanReturnFocus"),me=u(R,"returnFocus","returnFocusOnDeactivate");U==null||U();var se=function(){oo(function(){me&&E(T(o.nodeFocusedBeforeActivation)),J==null||J()})};return me&&W?(W(T(o.nodeFocusedBeforeActivation)).then(se,se),this):(se(),this)},pause:function(I){return o.active?(o.manuallyPaused=!0,this._setPausedState(!0,I)):this},unpause:function(I){return o.active?(o.manuallyPaused=!1,i[i.length-1]!==this?this:this._setPausedState(!1,I)):this},updateContainerElements:function(I){var R=[].concat(I).filter(Boolean);return o.containers=R.map(function(U){return typeof U=="string"?n.querySelector(U):U}),a.isolateSubtrees&&ie(o.containers),o.active&&(p(),a.isolateSubtrees&&!o.paused&&s._setSubtreeIsolation(!0)),K(),this}},Object.defineProperties(s,{_isManuallyPaused:{value:function(){return o.manuallyPaused}},_setPausedState:{value:function(I,R){if(o.paused===I)return this;if(o.paused=I,I){var U=u(R,"onPause"),J=u(R,"onPostPause");U==null||U(),te(),K(),s._setSubtreeIsolation(!1),J==null||J()}else{var W=u(R,"onUnpause"),me=u(R,"onPostUnpause");W==null||W(),s._setSubtreeIsolation(!0),p(),N(),K(),me==null||me()}return this}},_setSubtreeIsolation:{value:function(I){a.isolateSubtrees&&o.adjacentElements.forEach(function(R){var U;if(I)switch(a.isolateSubtrees){case"aria-hidden":(R.ariaHidden==="true"||((U=R.getAttribute("aria-hidden"))===null||U===void 0?void 0:U.toLowerCase())==="true")&&o.alreadySilent.add(R),R.setAttribute("aria-hidden","true");break;default:(R.inert||R.hasAttribute("inert"))&&o.alreadySilent.add(R),R.setAttribute("inert",!0);break}else if(!o.alreadySilent.has(R))switch(a.isolateSubtrees){case"aria-hidden":R.removeAttribute("aria-hidden");break;default:R.removeAttribute("inert");break}})}}}),s.updateContainerElements(e),s};function Yl(t,e={}){let r;const{immediate:n,...i}=e,a=Xt(!1),o=Xt(!1),s=p=>r&&r.activate(p),u=p=>r&&r.deactivate(p),f=()=>{r&&(r.pause(),o.value=!0)},g=()=>{r&&(r.unpause(),o.value=!1)},b=fe(()=>{const p=Gi(t);return hs(p).map(y=>{const E=Gi(y);return typeof E=="string"?E:ps(E)}).filter(gs)});return pt(b,p=>{p.length&&(r=Bl(p,{...i,onActivate(){a.value=!0,e.onActivate&&e.onActivate()},onDeactivate(){a.value=!1,e.onDeactivate&&e.onDeactivate()}}),n&&s())},{flush:"post"}),ms(()=>u()),{hasFocus:a,isPaused:o,activate:s,deactivate:u,pause:f,unpause:g}}var Fp=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function Wl(t){return t&&t.__esModule&&Object.prototype.hasOwnProperty.call(t,"default")?t.default:t}var ln={exports:{}};/*!***************************************************
* mark.js v8.11.1
* https://markjs.io/
* Copyright (c) 2014–2018, Julian Kühnel
* Released under the MIT license https://git.io/vwTVl
*****************************************************/var Kl=ln.exports,ao;function Gl(){return ao||(ao=1,(function(t,e){(function(r,n){t.exports=n()})(Kl,(function(){class r{constructor(o,s=!0,u=[],f=5e3){this.ctx=o,this.iframes=s,this.exclude=u,this.iframesTimeout=f}static matches(o,s){const u=typeof s=="string"?[s]:s,f=o.matches||o.matchesSelector||o.msMatchesSelector||o.mozMatchesSelector||o.oMatchesSelector||o.webkitMatchesSelector;if(f){let g=!1;return u.every(b=>f.call(o,b)?(g=!0,!1):!0),g}else return!1}getContexts(){let o,s=[];return typeof this.ctx>"u"||!this.ctx?o=[]:NodeList.prototype.isPrototypeOf(this.ctx)?o=Array.prototype.slice.call(this.ctx):Array.isArray(this.ctx)?o=this.ctx:typeof this.ctx=="string"?o=Array.prototype.slice.call(document.querySelectorAll(this.ctx)):o=[this.ctx],o.forEach(u=>{const f=s.filter(g=>g.contains(u)).length>0;s.indexOf(u)===-1&&!f&&s.push(u)}),s}getIframeContents(o,s,u=()=>{}){let f;try{const g=o.contentWindow;if(f=g.document,!g||!f)throw new Error("iframe inaccessible")}catch{u()}f&&s(f)}isIframeBlank(o){const s="about:blank",u=o.getAttribute("src").trim();return o.contentWindow.location.href===s&&u!==s&&u}observeIframeLoad(o,s,u){let f=!1,g=null;const b=()=>{if(!f){f=!0,clearTimeout(g);try{this.isIframeBlank(o)||(o.removeEventListener("load",b),this.getIframeContents(o,s,u))}catch{u()}}};o.addEventListener("load",b),g=setTimeout(b,this.iframesTimeout)}onIframeReady(o,s,u){try{o.contentWindow.document.readyState==="complete"?this.isIframeBlank(o)?this.observeIframeLoad(o,s,u):this.getIframeContents(o,s,u):this.observeIframeLoad(o,s,u)}catch{u()}}waitForIframes(o,s){let u=0;this.forEachIframe(o,()=>!0,f=>{u++,this.waitForIframes(f.querySelector("html"),()=>{--u||s()})},f=>{f||s()})}forEachIframe(o,s,u,f=()=>{}){let g=o.querySelectorAll("iframe"),b=g.length,p=0;g=Array.prototype.slice.call(g);const y=()=>{--b<=0&&f(p)};b||y(),g.forEach(E=>{r.matches(E,this.exclude)?y():this.onIframeReady(E,T=>{s(E)&&(p++,u(T)),y()},y)})}createIterator(o,s,u){return document.createNodeIterator(o,s,u,!1)}createInstanceOnIframe(o){return new r(o.querySelector("html"),this.iframes)}compareNodeIframe(o,s,u){const f=o.compareDocumentPosition(u),g=Node.DOCUMENT_POSITION_PRECEDING;if(f&g)if(s!==null){const b=s.compareDocumentPosition(u),p=Node.DOCUMENT_POSITION_FOLLOWING;if(b&p)return!0}else return!0;return!1}getIteratorNode(o){const s=o.previousNode();let u;return s===null?u=o.nextNode():u=o.nextNode()&&o.nextNode(),{prevNode:s,node:u}}checkIframeFilter(o,s,u,f){let g=!1,b=!1;return f.forEach((p,y)=>{p.val===u&&(g=y,b=p.handled)}),this.compareNodeIframe(o,s,u)?(g===!1&&!b?f.push({val:u,handled:!0}):g!==!1&&!b&&(f[g].handled=!0),!0):(g===!1&&f.push({val:u,handled:!1}),!1)}handleOpenIframes(o,s,u,f){o.forEach(g=>{g.handled||this.getIframeContents(g.val,b=>{this.createInstanceOnIframe(b).forEachNode(s,u,f)})})}iterateThroughNodes(o,s,u,f,g){const b=this.createIterator(s,o,f);let p=[],y=[],E,T,v=()=>({prevNode:T,node:E}=this.getIteratorNode(b),E);for(;v();)this.iframes&&this.forEachIframe(s,k=>this.checkIframeFilter(E,T,k,p),k=>{this.createInstanceOnIframe(k).forEachNode(o,C=>y.push(C),f)}),y.push(E);y.forEach(k=>{u(k)}),this.iframes&&this.handleOpenIframes(p,o,u,f),g()}forEachNode(o,s,u,f=()=>{}){const g=this.getContexts();let b=g.length;b||f(),g.forEach(p=>{const y=()=>{this.iterateThroughNodes(o,p,s,u,()=>{--b<=0&&f()})};this.iframes?this.waitForIframes(p,y):y()})}}class n{constructor(o){this.ctx=o,this.ie=!1;const s=window.navigator.userAgent;(s.indexOf("MSIE")>-1||s.indexOf("Trident")>-1)&&(this.ie=!0)}set opt(o){this._opt=Object.assign({},{element:"",className:"",exclude:[],iframes:!1,iframesTimeout:5e3,separateWordSearch:!0,diacritics:!0,synonyms:{},accuracy:"partially",acrossElements:!1,caseSensitive:!1,ignoreJoiners:!1,ignoreGroups:0,ignorePunctuation:[],wildcards:"disabled",each:()=>{},noMatch:()=>{},filter:()=>!0,done:()=>{},debug:!1,log:window.console},o)}get opt(){return this._opt}get iterator(){return new r(this.ctx,this.opt.iframes,this.opt.exclude,this.opt.iframesTimeout)}log(o,s="debug"){const u=this.opt.log;this.opt.debug&&typeof u=="object"&&typeof u[s]=="function"&&u[s](`mark.js: ${o}`)}escapeStr(o){return o.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")}createRegExp(o){return this.opt.wildcards!=="disabled"&&(o=this.setupWildcardsRegExp(o)),o=this.escapeStr(o),Object.keys(this.opt.synonyms).length&&(o=this.createSynonymsRegExp(o)),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.setupIgnoreJoinersRegExp(o)),this.opt.diacritics&&(o=this.createDiacriticsRegExp(o)),o=this.createMergedBlanksRegExp(o),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.createJoinersRegExp(o)),this.opt.wildcards!=="disabled"&&(o=this.createWildcardsRegExp(o)),o=this.createAccuracyRegExp(o),o}createSynonymsRegExp(o){const s=this.opt.synonyms,u=this.opt.caseSensitive?"":"i",f=this.opt.ignoreJoiners||this.opt.ignorePunctuation.length?"\0":"";for(let g in s)if(s.hasOwnProperty(g)){const b=s[g],p=this.opt.wildcards!=="disabled"?this.setupWildcardsRegExp(g):this.escapeStr(g),y=this.opt.wildcards!=="disabled"?this.setupWildcardsRegExp(b):this.escapeStr(b);p!==""&&y!==""&&(o=o.replace(new RegExp(`(${this.escapeStr(p)}|${this.escapeStr(y)})`,`gm${u}`),f+`(${this.processSynomyms(p)}|${this.processSynomyms(y)})`+f))}return o}processSynomyms(o){return(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.setupIgnoreJoinersRegExp(o)),o}setupWildcardsRegExp(o){return o=o.replace(/(?:\\)*\?/g,s=>s.charAt(0)==="\\"?"?":""),o.replace(/(?:\\)*\*/g,s=>s.charAt(0)==="\\"?"*":"")}createWildcardsRegExp(o){let s=this.opt.wildcards==="withSpaces";return o.replace(/\u0001/g,s?"[\\S\\s]?":"\\S?").replace(/\u0002/g,s?"[\\S\\s]*?":"\\S*")}setupIgnoreJoinersRegExp(o){return o.replace(/[^(|)\\]/g,(s,u,f)=>{let g=f.charAt(u+1);return/[(|)\\]/.test(g)||g===""?s:s+"\0"})}createJoinersRegExp(o){let s=[];const u=this.opt.ignorePunctuation;return Array.isArray(u)&&u.length&&s.push(this.escapeStr(u.join(""))),this.opt.ignoreJoiners&&s.push("\\u00ad\\u200b\\u200c\\u200d"),s.length?o.split(/\u0000+/).join(`[${s.join("")}]*`):o}createDiacriticsRegExp(o){const s=this.opt.caseSensitive?"":"i",u=this.opt.caseSensitive?["aàáảãạăằắẳẵặâầấẩẫậäåāą","AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćč","CÇĆČ","dđď","DĐĎ","eèéẻẽẹêềếểễệëěēę","EÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïī","IÌÍỈĨỊÎÏĪ","lł","LŁ","nñňń","NÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøō","OÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rř","RŘ","sšśșş","SŠŚȘŞ","tťțţ","TŤȚŢ","uùúủũụưừứửữựûüůū","UÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿ","YÝỲỶỸỴŸ","zžżź","ZŽŻŹ"]:["aàáảãạăằắẳẵặâầấẩẫậäåāąAÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćčCÇĆČ","dđďDĐĎ","eèéẻẽẹêềếểễệëěēęEÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïīIÌÍỈĨỊÎÏĪ","lłLŁ","nñňńNÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøōOÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rřRŘ","sšśșşSŠŚȘŞ","tťțţTŤȚŢ","uùúủũụưừứửữựûüůūUÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿYÝỲỶỸỴŸ","zžżźZŽŻŹ"];let f=[];return o.split("").forEach(g=>{u.every(b=>{if(b.indexOf(g)!==-1){if(f.indexOf(b)>-1)return!1;o=o.replace(new RegExp(`[${b}]`,`gm${s}`),`[${b}]`),f.push(b)}return!0})}),o}createMergedBlanksRegExp(o){return o.replace(/[\s]+/gmi,"[\\s]+")}createAccuracyRegExp(o){const s="!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~¡¿";let u=this.opt.accuracy,f=typeof u=="string"?u:u.value,g=typeof u=="string"?[]:u.limiters,b="";switch(g.forEach(p=>{b+=`|${this.escapeStr(p)}`}),f){case"partially":default:return`()(${o})`;case"complementary":return b="\\s"+(b||this.escapeStr(s)),`()([^${b}]*${o}[^${b}]*)`;case"exactly":return`(^|\\s${b})(${o})(?=$|\\s${b})`}}getSeparatedKeywords(o){let s=[];return o.forEach(u=>{this.opt.separateWordSearch?u.split(" ").forEach(f=>{f.trim()&&s.indexOf(f)===-1&&s.push(f)}):u.trim()&&s.indexOf(u)===-1&&s.push(u)}),{keywords:s.sort((u,f)=>f.length-u.length),length:s.length}}isNumeric(o){return Number(parseFloat(o))==o}checkRanges(o){if(!Array.isArray(o)||Object.prototype.toString.call(o[0])!=="[object Object]")return this.log("markRanges() will only accept an array of objects"),this.opt.noMatch(o),[];const s=[];let u=0;return o.sort((f,g)=>f.start-g.start).forEach(f=>{let{start:g,end:b,valid:p}=this.callNoMatchOnInvalidRanges(f,u);p&&(f.start=g,f.length=b-g,s.push(f),u=b)}),s}callNoMatchOnInvalidRanges(o,s){let u,f,g=!1;return o&&typeof o.start<"u"?(u=parseInt(o.start,10),f=u+parseInt(o.length,10),this.isNumeric(o.start)&&this.isNumeric(o.length)&&f-s>0&&f-u>0?g=!0:(this.log(`Ignoring invalid or overlapping range: ${JSON.stringify(o)}`),this.opt.noMatch(o))):(this.log(`Ignoring invalid range: ${JSON.stringify(o)}`),this.opt.noMatch(o)),{start:u,end:f,valid:g}}checkWhitespaceRanges(o,s,u){let f,g=!0,b=u.length,p=s-b,y=parseInt(o.start,10)-p;return y=y>b?b:y,f=y+parseInt(o.length,10),f>b&&(f=b,this.log(`End range automatically set to the max value of ${b}`)),y<0||f-y<0||y>b||f>b?(g=!1,this.log(`Invalid range: ${JSON.stringify(o)}`),this.opt.noMatch(o)):u.substring(y,f).replace(/\s+/g,"")===""&&(g=!1,this.log("Skipping whitespace only range: "+JSON.stringify(o)),this.opt.noMatch(o)),{start:y,end:f,valid:g}}getTextNodes(o){let s="",u=[];this.iterator.forEachNode(NodeFilter.SHOW_TEXT,f=>{u.push({start:s.length,end:(s+=f.textContent).length,node:f})},f=>this.matchesExclude(f.parentNode)?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT,()=>{o({value:s,nodes:u})})}matchesExclude(o){return r.matches(o,this.opt.exclude.concat(["script","style","title","head","html"]))}wrapRangeInTextNode(o,s,u){const f=this.opt.element?this.opt.element:"mark",g=o.splitText(s),b=g.splitText(u-s);let p=document.createElement(f);return p.setAttribute("data-markjs","true"),this.opt.className&&p.setAttribute("class",this.opt.className),p.textContent=g.textContent,g.parentNode.replaceChild(p,g),b}wrapRangeInMappedTextNode(o,s,u,f,g){o.nodes.every((b,p)=>{const y=o.nodes[p+1];if(typeof y>"u"||y.start>s){if(!f(b.node))return!1;const E=s-b.start,T=(u>b.end?b.end:u)-b.start,v=o.value.substr(0,b.start),k=o.value.substr(T+b.start);if(b.node=this.wrapRangeInTextNode(b.node,E,T),o.value=v+k,o.nodes.forEach((C,S)=>{S>=p&&(o.nodes[S].start>0&&S!==p&&(o.nodes[S].start-=T),o.nodes[S].end-=T)}),u-=T,g(b.node.previousSibling,b.start),u>b.end)s=b.end;else return!1}return!0})}wrapMatches(o,s,u,f,g){const b=s===0?0:s+1;this.getTextNodes(p=>{p.nodes.forEach(y=>{y=y.node;let E;for(;(E=o.exec(y.textContent))!==null&&E[b]!=="";){if(!u(E[b],y))continue;let T=E.index;if(b!==0)for(let v=1;v<b;v++)T+=E[v].length;y=this.wrapRangeInTextNode(y,T,T+E[b].length),f(y.previousSibling),o.lastIndex=0}}),g()})}wrapMatchesAcrossElements(o,s,u,f,g){const b=s===0?0:s+1;this.getTextNodes(p=>{let y;for(;(y=o.exec(p.value))!==null&&y[b]!=="";){let E=y.index;if(b!==0)for(let v=1;v<b;v++)E+=y[v].length;const T=E+y[b].length;this.wrapRangeInMappedTextNode(p,E,T,v=>u(y[b],v),(v,k)=>{o.lastIndex=k,f(v)})}g()})}wrapRangeFromIndex(o,s,u,f){this.getTextNodes(g=>{const b=g.value.length;o.forEach((p,y)=>{let{start:E,end:T,valid:v}=this.checkWhitespaceRanges(p,b,g.value);v&&this.wrapRangeInMappedTextNode(g,E,T,k=>s(k,p,g.value.substring(E,T),y),k=>{u(k,p)})}),f()})}unwrapMatches(o){const s=o.parentNode;let u=document.createDocumentFragment();for(;o.firstChild;)u.appendChild(o.removeChild(o.firstChild));s.replaceChild(u,o),this.ie?this.normalizeTextNode(s):s.normalize()}normalizeTextNode(o){if(o){if(o.nodeType===3)for(;o.nextSibling&&o.nextSibling.nodeType===3;)o.nodeValue+=o.nextSibling.nodeValue,o.parentNode.removeChild(o.nextSibling);else this.normalizeTextNode(o.firstChild);this.normalizeTextNode(o.nextSibling)}}markRegExp(o,s){this.opt=s,this.log(`Searching with expression "${o}"`);let u=0,f="wrapMatches";const g=b=>{u++,this.opt.each(b)};this.opt.acrossElements&&(f="wrapMatchesAcrossElements"),this[f](o,this.opt.ignoreGroups,(b,p)=>this.opt.filter(p,b,u),g,()=>{u===0&&this.opt.noMatch(o),this.opt.done(u)})}mark(o,s){this.opt=s;let u=0,f="wrapMatches";const{keywords:g,length:b}=this.getSeparatedKeywords(typeof o=="string"?[o]:o),p=this.opt.caseSensitive?"":"i",y=E=>{let T=new RegExp(this.createRegExp(E),`gm${p}`),v=0;this.log(`Searching with expression "${T}"`),this[f](T,1,(k,C)=>this.opt.filter(C,E,u,v),k=>{v++,u++,this.opt.each(k)},()=>{v===0&&this.opt.noMatch(E),g[b-1]===E?this.opt.done(u):y(g[g.indexOf(E)+1])})};this.opt.acrossElements&&(f="wrapMatchesAcrossElements"),b===0?this.opt.done(u):y(g[0])}markRanges(o,s){this.opt=s;let u=0,f=this.checkRanges(o);f&&f.length?(this.log("Starting to mark with the following ranges: "+JSON.stringify(f)),this.wrapRangeFromIndex(f,(g,b,p,y)=>this.opt.filter(g,b,p,y),(g,b)=>{u++,this.opt.each(g,b)},()=>{this.opt.done(u)})):this.opt.done(u)}unmark(o){this.opt=o;let s=this.opt.element?this.opt.element:"*";s+="[data-markjs]",this.opt.className&&(s+=`.${this.opt.className}`),this.log(`Removal selector "${s}"`),this.iterator.forEachNode(NodeFilter.SHOW_ELEMENT,u=>{this.unwrapMatches(u)},u=>{const f=r.matches(u,s),g=this.matchesExclude(u);return!f||g?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT},this.opt.done)}}function i(a){const o=new n(a);return this.mark=(s,u)=>(o.mark(s,u),this),this.markRegExp=(s,u)=>(o.markRegExp(s,u),this),this.markRanges=(s,u)=>(o.markRanges(s,u),this),this.unmark=s=>(o.unmark(s),this),this}return i}))})(ln)),ln.exports}var Jl=Gl();const Zl=Wl(Jl),Ql="ENTRIES",Qo="KEYS",Xo="VALUES",je="";class Nn{constructor(e,r){const n=e._tree,i=Array.from(n.keys());this.set=e,this._type=r,this._path=i.length>0?[{node:n,keys:i}]:[]}next(){const e=this.dive();return this.backtrack(),e}dive(){if(this._path.length===0)return{done:!0,value:void 0};const{node:e,keys:r}=ur(this._path);if(ur(r)===je)return{done:!1,value:this.result()};const n=e.get(ur(r));return this._path.push({node:n,keys:Array.from(n.keys())}),this.dive()}backtrack(){if(this._path.length===0)return;const e=ur(this._path).keys;e.pop(),!(e.length>0)&&(this._path.pop(),this.backtrack())}key(){return this.set._prefix+this._path.map(({keys:e})=>ur(e)).filter(e=>e!==je).join("")}value(){return ur(this._path).node.get(je)}result(){switch(this._type){case Xo:return this.value();case Qo:return this.key();default:return[this.key(),this.value()]}}[Symbol.iterator](){return this}}const ur=t=>t[t.length-1],Xl=(t,e,r)=>{const n=new Map;if(e===void 0)return n;const i=e.length+1,a=i+r,o=new Uint8Array(a*i).fill(r+1);for(let s=0;s<i;++s)o[s]=s;for(let s=1;s<a;++s)o[s*i]=s;return ea(t,e,r,n,o,1,i,""),n},ea=(t,e,r,n,i,a,o,s)=>{const u=a*o;e:for(const f of t.keys())if(f===je){const g=i[u-1];g<=r&&n.set(s,[t.get(f),g])}else{let g=a;for(let b=0;b<f.length;++b,++g){const p=f[b],y=o*g,E=y-o;let T=i[y];const v=Math.max(0,g-r-1),k=Math.min(o-1,g+r);for(let C=v;C<k;++C){const S=p!==e[C],A=i[E+C]+ +S,L=i[E+C+1]+1,P=i[y+C]+1,N=i[y+C+1]=Math.min(A,L,P);N<T&&(T=N)}if(T>r)continue e}ea(t.get(f),e,r,n,i,g,o,s+f)}};class Dt{constructor(e=new Map,r=""){this._size=void 0,this._tree=e,this._prefix=r}atPrefix(e){if(!e.startsWith(this._prefix))throw new Error("Mismatched prefix");const[r,n]=gn(this._tree,e.slice(this._prefix.length));if(r===void 0){const[i,a]=hi(n);for(const o of i.keys())if(o!==je&&o.startsWith(a)){const s=new Map;return s.set(o.slice(a.length),i.get(o)),new Dt(s,e)}}return new Dt(r,e)}clear(){this._size=void 0,this._tree.clear()}delete(e){return this._size=void 0,ec(this._tree,e)}entries(){return new Nn(this,Ql)}forEach(e){for(const[r,n]of this)e(r,n,this)}fuzzyGet(e,r){return Xl(this._tree,e,r)}get(e){const r=Gn(this._tree,e);return r!==void 0?r.get(je):void 0}has(e){const r=Gn(this._tree,e);return r!==void 0&&r.has(je)}keys(){return new Nn(this,Qo)}set(e,r){if(typeof e!="string")throw new Error("key must be a string");return this._size=void 0,Rn(this._tree,e).set(je,r),this}get size(){if(this._size)return this._size;this._size=0;const e=this.entries();for(;!e.next().done;)this._size+=1;return this._size}update(e,r){if(typeof e!="string")throw new Error("key must be a string");this._size=void 0;const n=Rn(this._tree,e);return n.set(je,r(n.get(je))),this}fetch(e,r){if(typeof e!="string")throw new Error("key must be a string");this._size=void 0;const n=Rn(this._tree,e);let i=n.get(je);return i===void 0&&n.set(je,i=r()),i}values(){return new Nn(this,Xo)}[Symbol.iterator](){return this.entries()}static from(e){const r=new Dt;for(const[n,i]of e)r.set(n,i);return r}static fromObject(e){return Dt.from(Object.entries(e))}}const gn=(t,e,r=[])=>{if(e.length===0||t==null)return[t,r];for(const n of t.keys())if(n!==je&&e.startsWith(n))return r.push([t,n]),gn(t.get(n),e.slice(n.length),r);return r.push([t,e]),gn(void 0,"",r)},Gn=(t,e)=>{if(e.length===0||t==null)return t;for(const r of t.keys())if(r!==je&&e.startsWith(r))return Gn(t.get(r),e.slice(r.length))},Rn=(t,e)=>{const r=e.length;e:for(let n=0;t&&n<r;){for(const a of t.keys())if(a!==je&&e[n]===a[0]){const o=Math.min(r-n,a.length);let s=1;for(;s<o&&e[n+s]===a[s];)++s;const u=t.get(a);if(s===a.length)t=u;else{const f=new Map;f.set(a.slice(s),u),t.set(e.slice(n,n+s),f),t.delete(a),t=f}n+=s;continue e}const i=new Map;return t.set(e.slice(n),i),i}return t},ec=(t,e)=>{const[r,n]=gn(t,e);if(r!==void 0){if(r.delete(je),r.size===0)ta(n);else if(r.size===1){const[i,a]=r.entries().next().value;ra(n,i,a)}}},ta=t=>{if(t.length===0)return;const[e,r]=hi(t);if(e.delete(r),e.size===0)ta(t.slice(0,-1));else if(e.size===1){const[n,i]=e.entries().next().value;n!==je&&ra(t.slice(0,-1),n,i)}},ra=(t,e,r)=>{if(t.length===0)return;const[n,i]=hi(t);n.set(i+e,r),n.delete(i)},hi=t=>t[t.length-1],pi="or",na="and",tc="and_not";class br{constructor(e){if((e==null?void 0:e.fields)==null)throw new Error('MiniSearch: option "fields" must be provided');const r=e.autoVacuum==null||e.autoVacuum===!0?$n:e.autoVacuum;this._options={...Pn,...e,autoVacuum:r,searchOptions:{...so,...e.searchOptions||{}},autoSuggestOptions:{...ac,...e.autoSuggestOptions||{}}},this._index=new Dt,this._documentCount=0,this._documentIds=new Map,this._idToShortId=new Map,this._fieldIds={},this._fieldLength=new Map,this._avgFieldLength=[],this._nextId=0,this._storedFields=new Map,this._dirtCount=0,this._currentVacuum=null,this._enqueuedVacuum=null,this._enqueuedVacuumConditions=Zn,this.addFields(this._options.fields)}add(e){const{extractField:r,stringifyField:n,tokenize:i,processTerm:a,fields:o,idField:s}=this._options,u=r(e,s);if(u==null)throw new Error(`MiniSearch: document does not have ID field "${s}"`);if(this._idToShortId.has(u))throw new Error(`MiniSearch: duplicate ID ${u}`);const f=this.addDocumentId(u);this.saveStoredFields(f,e);for(const g of o){const b=r(e,g);if(b==null)continue;const p=i(n(b,g),g),y=this._fieldIds[g],E=new Set(p).size;this.addFieldLength(f,y,this._documentCount-1,E);for(const T of p){const v=a(T,g);if(Array.isArray(v))for(const k of v)this.addTerm(y,f,k);else v&&this.addTerm(y,f,v)}}}addAll(e){for(const r of e)this.add(r)}addAllAsync(e,r={}){const{chunkSize:n=10}=r,i={chunk:[],promise:Promise.resolve()},{chunk:a,promise:o}=e.reduce(({chunk:s,promise:u},f,g)=>(s.push(f),(g+1)%n===0?{chunk:[],promise:u.then(()=>new Promise(b=>setTimeout(b,0))).then(()=>this.addAll(s))}:{chunk:s,promise:u}),i);return o.then(()=>this.addAll(a))}remove(e){const{tokenize:r,processTerm:n,extractField:i,stringifyField:a,fields:o,idField:s}=this._options,u=i(e,s);if(u==null)throw new Error(`MiniSearch: document does not have ID field "${s}"`);const f=this._idToShortId.get(u);if(f==null)throw new Error(`MiniSearch: cannot remove document with ID ${u}: it is not in the index`);for(const g of o){const b=i(e,g);if(b==null)continue;const p=r(a(b,g),g),y=this._fieldIds[g],E=new Set(p).size;this.removeFieldLength(f,y,this._documentCount,E);for(const T of p){const v=n(T,g);if(Array.isArray(v))for(const k of v)this.removeTerm(y,f,k);else v&&this.removeTerm(y,f,v)}}this._storedFields.delete(f),this._documentIds.delete(f),this._idToShortId.delete(u),this._fieldLength.delete(f),this._documentCount-=1}removeAll(e){if(e)for(const r of e)this.remove(r);else{if(arguments.length>0)throw new Error("Expected documents to be present. Omit the argument to remove all documents.");this._index=new Dt,this._documentCount=0,this._documentIds=new Map,this._idToShortId=new Map,this._fieldLength=new Map,this._avgFieldLength=[],this._storedFields=new Map,this._nextId=0}}discard(e){const r=this._idToShortId.get(e);if(r==null)throw new Error(`MiniSearch: cannot discard document with ID ${e}: it is not in the index`);this._idToShortId.delete(e),this._documentIds.delete(r),this._storedFields.delete(r),(this._fieldLength.get(r)||[]).forEach((n,i)=>{this.removeFieldLength(r,i,this._documentCount,n)}),this._fieldLength.delete(r),this._documentCount-=1,this._dirtCount+=1,this.maybeAutoVacuum()}maybeAutoVacuum(){if(this._options.autoVacuum===!1)return;const{minDirtFactor:e,minDirtCount:r,batchSize:n,batchWait:i}=this._options.autoVacuum;this.conditionalVacuum({batchSize:n,batchWait:i},{minDirtCount:r,minDirtFactor:e})}discardAll(e){const r=this._options.autoVacuum;try{this._options.autoVacuum=!1;for(const n of e)this.discard(n)}finally{this._options.autoVacuum=r}this.maybeAutoVacuum()}replace(e){const{idField:r,extractField:n}=this._options,i=n(e,r);this.discard(i),this.add(e)}vacuum(e={}){return this.conditionalVacuum(e)}conditionalVacuum(e,r){return this._currentVacuum?(this._enqueuedVacuumConditions=this._enqueuedVacuumConditions&&r,this._enqueuedVacuum!=null?this._enqueuedVacuum:(this._enqueuedVacuum=this._currentVacuum.then(()=>{const n=this._enqueuedVacuumConditions;return this._enqueuedVacuumConditions=Zn,this.performVacuuming(e,n)}),this._enqueuedVacuum)):this.vacuumConditionsMet(r)===!1?Promise.resolve():(this._currentVacuum=this.performVacuuming(e),this._currentVacuum)}async performVacuuming(e,r){const n=this._dirtCount;if(this.vacuumConditionsMet(r)){const i=e.batchSize||Jn.batchSize,a=e.batchWait||Jn.batchWait;let o=1;for(const[s,u]of this._index){for(const[f,g]of u)for(const[b]of g)this._documentIds.has(b)||(g.size<=1?u.delete(f):g.delete(b));this._index.get(s).size===0&&this._index.delete(s),o%i===0&&await new Promise(f=>setTimeout(f,a)),o+=1}this._dirtCount-=n}await null,this._currentVacuum=this._enqueuedVacuum,this._enqueuedVacuum=null}vacuumConditionsMet(e){if(e==null)return!0;let{minDirtCount:r,minDirtFactor:n}=e;return r=r||$n.minDirtCount,n=n||$n.minDirtFactor,this.dirtCount>=r&&this.dirtFactor>=n}get isVacuuming(){return this._currentVacuum!=null}get dirtCount(){return this._dirtCount}get dirtFactor(){return this._dirtCount/(1+this._documentCount+this._dirtCount)}has(e){return this._idToShortId.has(e)}getStoredFields(e){const r=this._idToShortId.get(e);if(r!=null)return this._storedFields.get(r)}search(e,r={}){const{searchOptions:n}=this._options,i={...n,...r},a=this.executeQuery(e,r),o=[];for(const[s,{score:u,terms:f,match:g}]of a){const b=f.length||1,p={id:this._documentIds.get(s),score:u*b,terms:Object.keys(g),queryTerms:f,match:g};Object.assign(p,this._storedFields.get(s)),(i.filter==null||i.filter(p))&&o.push(p)}return e===br.wildcard&&i.boostDocument==null||o.sort(co),o}autoSuggest(e,r={}){r={...this._options.autoSuggestOptions,...r};const n=new Map;for(const{score:a,terms:o}of this.search(e,r)){const s=o.join(" "),u=n.get(s);u!=null?(u.score+=a,u.count+=1):n.set(s,{score:a,terms:o,count:1})}const i=[];for(const[a,{score:o,terms:s,count:u}]of n)i.push({suggestion:a,terms:s,score:o/u});return i.sort(co),i}get documentCount(){return this._documentCount}get termCount(){return this._index.size}static loadJSON(e,r){if(r==null)throw new Error("MiniSearch: loadJSON should be given the same options used when serializing the index");return this.loadJS(JSON.parse(e),r)}static async loadJSONAsync(e,r){if(r==null)throw new Error("MiniSearch: loadJSON should be given the same options used when serializing the index");return this.loadJSAsync(JSON.parse(e),r)}static getDefault(e){if(Pn.hasOwnProperty(e))return On(Pn,e);throw new Error(`MiniSearch: unknown option "${e}"`)}static loadJS(e,r){const{index:n,documentIds:i,fieldLength:a,storedFields:o,serializationVersion:s}=e,u=this.instantiateMiniSearch(e,r);u._documentIds=Xr(i),u._fieldLength=Xr(a),u._storedFields=Xr(o);for(const[f,g]of u._documentIds)u._idToShortId.set(g,f);for(const[f,g]of n){const b=new Map;for(const p of Object.keys(g)){let y=g[p];s===1&&(y=y.ds),b.set(parseInt(p,10),Xr(y))}u._index.set(f,b)}return u}static async loadJSAsync(e,r){const{index:n,documentIds:i,fieldLength:a,storedFields:o,serializationVersion:s}=e,u=this.instantiateMiniSearch(e,r);u._documentIds=await en(i),u._fieldLength=await en(a),u._storedFields=await en(o);for(const[g,b]of u._documentIds)u._idToShortId.set(b,g);let f=0;for(const[g,b]of n){const p=new Map;for(const y of Object.keys(b)){let E=b[y];s===1&&(E=E.ds),p.set(parseInt(y,10),await en(E))}++f%1e3===0&&await ia(0),u._index.set(g,p)}return u}static instantiateMiniSearch(e,r){const{documentCount:n,nextId:i,fieldIds:a,averageFieldLength:o,dirtCount:s,serializationVersion:u}=e;if(u!==1&&u!==2)throw new Error("MiniSearch: cannot deserialize an index created with an incompatible version");const f=new br(r);return f._documentCount=n,f._nextId=i,f._idToShortId=new Map,f._fieldIds=a,f._avgFieldLength=o,f._dirtCount=s||0,f._index=new Dt,f}executeQuery(e,r={}){if(e===br.wildcard)return this.executeWildcardQuery(r);if(typeof e!="string"){const p={...r,...e,queries:void 0},y=e.queries.map(E=>this.executeQuery(E,p));return this.combineResults(y,p.combineWith)}const{tokenize:n,processTerm:i,searchOptions:a}=this._options,o={tokenize:n,processTerm:i,...a,...r},{tokenize:s,processTerm:u}=o,b=s(e).flatMap(p=>u(p)).filter(p=>!!p).map(oc(o)).map(p=>this.executeQuerySpec(p,o));return this.combineResults(b,o.combineWith)}executeQuerySpec(e,r){const n={...this._options.searchOptions,...r},i=(n.fields||this._options.fields).reduce((T,v)=>({...T,[v]:On(n.boost,v)||1}),{}),{boostDocument:a,weights:o,maxFuzzy:s,bm25:u}=n,{fuzzy:f,prefix:g}={...so.weights,...o},b=this._index.get(e.term),p=this.termResults(e.term,e.term,1,e.termBoost,b,i,a,u);let y,E;if(e.prefix&&(y=this._index.atPrefix(e.term)),e.fuzzy){const T=e.fuzzy===!0?.2:e.fuzzy,v=T<1?Math.min(s,Math.round(e.term.length*T)):T;v&&(E=this._index.fuzzyGet(e.term,v))}if(y)for(const[T,v]of y){const k=T.length-e.term.length;if(!k)continue;E==null||E.delete(T);const C=g*T.length/(T.length+.3*k);this.termResults(e.term,T,C,e.termBoost,v,i,a,u,p)}if(E)for(const T of E.keys()){const[v,k]=E.get(T);if(!k)continue;const C=f*T.length/(T.length+k);this.termResults(e.term,T,C,e.termBoost,v,i,a,u,p)}return p}executeWildcardQuery(e){const r=new Map,n={...this._options.searchOptions,...e};for(const[i,a]of this._documentIds){const o=n.boostDocument?n.boostDocument(a,"",this._storedFields.get(i)):1;r.set(i,{score:o,terms:[],match:{}})}return r}combineResults(e,r=pi){if(e.length===0)return new Map;const n=r.toLowerCase(),i=rc[n];if(!i)throw new Error(`Invalid combination operator: ${r}`);return e.reduce(i)||new Map}toJSON(){const e=[];for(const[r,n]of this._index){const i={};for(const[a,o]of n)i[a]=Object.fromEntries(o);e.push([r,i])}return{documentCount:this._documentCount,nextId:this._nextId,documentIds:Object.fromEntries(this._documentIds),fieldIds:this._fieldIds,fieldLength:Object.fromEntries(this._fieldLength),averageFieldLength:this._avgFieldLength,storedFields:Object.fromEntries(this._storedFields),dirtCount:this._dirtCount,index:e,serializationVersion:2}}termResults(e,r,n,i,a,o,s,u,f=new Map){if(a==null)return f;for(const g of Object.keys(o)){const b=o[g],p=this._fieldIds[g],y=a.get(p);if(y==null)continue;let E=y.size;const T=this._avgFieldLength[p];for(const v of y.keys()){if(!this._documentIds.has(v)){this.removeTerm(p,v,r),E-=1;continue}const k=s?s(this._documentIds.get(v),r,this._storedFields.get(v)):1;if(!k)continue;const C=y.get(v),S=this._fieldLength.get(v)[p],A=ic(C,E,this._documentCount,S,T,u),L=n*i*b*k*A,P=f.get(v);if(P){P.score+=L,sc(P.terms,e);const N=On(P.match,r);N?N.push(g):P.match[r]=[g]}else f.set(v,{score:L,terms:[e],match:{[r]:[g]}})}}return f}addTerm(e,r,n){const i=this._index.fetch(n,uo);let a=i.get(e);if(a==null)a=new Map,a.set(r,1),i.set(e,a);else{const o=a.get(r);a.set(r,(o||0)+1)}}removeTerm(e,r,n){if(!this._index.has(n)){this.warnDocumentChanged(r,e,n);return}const i=this._index.fetch(n,uo),a=i.get(e);a==null||a.get(r)==null?this.warnDocumentChanged(r,e,n):a.get(r)<=1?a.size<=1?i.delete(e):a.delete(r):a.set(r,a.get(r)-1),this._index.get(n).size===0&&this._index.delete(n)}warnDocumentChanged(e,r,n){for(const i of Object.keys(this._fieldIds))if(this._fieldIds[i]===r){this._options.logger("warn",`MiniSearch: document with ID ${this._documentIds.get(e)} has changed before removal: term "${n}" was not present in field "${i}". Removing a document after it has changed can corrupt the index!`,"version_conflict");return}}addDocumentId(e){const r=this._nextId;return this._idToShortId.set(e,r),this._documentIds.set(r,e),this._documentCount+=1,this._nextId+=1,r}addFields(e){for(let r=0;r<e.length;r++)this._fieldIds[e[r]]=r}addFieldLength(e,r,n,i){let a=this._fieldLength.get(e);a==null&&this._fieldLength.set(e,a=[]),a[r]=i;const s=(this._avgFieldLength[r]||0)*n+i;this._avgFieldLength[r]=s/(n+1)}removeFieldLength(e,r,n,i){if(n===1){this._avgFieldLength[r]=0;return}const a=this._avgFieldLength[r]*n-i;this._avgFieldLength[r]=a/(n-1)}saveStoredFields(e,r){const{storeFields:n,extractField:i}=this._options;if(n==null||n.length===0)return;let a=this._storedFields.get(e);a==null&&this._storedFields.set(e,a={});for(const o of n){const s=i(r,o);s!==void 0&&(a[o]=s)}}}br.wildcard=Symbol("*");const On=(t,e)=>Object.prototype.hasOwnProperty.call(t,e)?t[e]:void 0,rc={[pi]:(t,e)=>{for(const r of e.keys()){const n=t.get(r);if(n==null)t.set(r,e.get(r));else{const{score:i,terms:a,match:o}=e.get(r);n.score=n.score+i,n.match=Object.assign(n.match,o),lo(n.terms,a)}}return t},[na]:(t,e)=>{const r=new Map;for(const n of e.keys()){const i=t.get(n);if(i==null)continue;const{score:a,terms:o,match:s}=e.get(n);lo(i.terms,o),r.set(n,{score:i.score+a,terms:i.terms,match:Object.assign(i.match,s)})}return r},[tc]:(t,e)=>{for(const r of e.keys())t.delete(r);return t}},nc={k:1.2,b:.7,d:.5},ic=(t,e,r,n,i,a)=>{const{k:o,b:s,d:u}=a;return Math.log(1+(r-e+.5)/(e+.5))*(u+t*(o+1)/(t+o*(1-s+s*n/i)))},oc=t=>(e,r,n)=>{const i=typeof t.fuzzy=="function"?t.fuzzy(e,r,n):t.fuzzy||!1,a=typeof t.prefix=="function"?t.prefix(e,r,n):t.prefix===!0,o=typeof t.boostTerm=="function"?t.boostTerm(e,r,n):1;return{term:e,fuzzy:i,prefix:a,termBoost:o}},Pn={idField:"id",extractField:(t,e)=>t[e],stringifyField:(t,e)=>t.toString(),tokenize:t=>t.split(lc),processTerm:t=>t.toLowerCase(),fields:void 0,searchOptions:void 0,storeFields:[],logger:(t,e)=>{typeof(console==null?void 0:console[t])=="function"&&console[t](e)},autoVacuum:!0},so={combineWith:pi,prefix:!1,fuzzy:!1,maxFuzzy:6,boost:{},weights:{fuzzy:.45,prefix:.375},bm25:nc},ac={combineWith:na,prefix:(t,e,r)=>e===r.length-1},Jn={batchSize:1e3,batchWait:10},Zn={minDirtFactor:.1,minDirtCount:20},$n={...Jn,...Zn},sc=(t,e)=>{t.includes(e)||t.push(e)},lo=(t,e)=>{for(const r of e)t.includes(r)||t.push(r)},co=({score:t},{score:e})=>e-t,uo=()=>new Map,Xr=t=>{const e=new Map;for(const r of Object.keys(t))e.set(parseInt(r,10),t[r]);return e},en=async t=>{const e=new Map;let r=0;for(const n of Object.keys(t))e.set(parseInt(n,10),t[n]),++r%1e3===0&&await ia(0);return e},ia=t=>new Promise(e=>setTimeout(e,t)),lc=/[\n\r\p{Z}\p{P}]+/u,oa=Vt;class cc{constructor(e=10){Ln(this,"max");Ln(this,"cache");this.max=e,this.cache=new Map}get(e){let r=this.cache.get(e);return r!==void 0&&(this.cache.delete(e),this.cache.set(e,r)),r}set(e,r){this.cache.has(e)?this.cache.delete(e):this.cache.size===this.max&&this.cache.delete(this.first()),this.cache.set(e,r)}first(){return this.cache.keys().next().value}clear(){this.cache.clear()}}function uc(t){const{localeIndex:e,theme:r}=oa();function n(i){var E,T,v;const a=i.split("."),o=(E=r.value.search)==null?void 0:E.options,s=o&&typeof o=="object",u=s&&((v=(T=o.locales)==null?void 0:T[e.value])==null?void 0:v.translations)||null,f=s&&o.translations||null;let g=u,b=f,p=t;const y=a.pop();for(const k of a){let C=null;const S=p==null?void 0:p[k];S&&(C=p=S);const A=b==null?void 0:b[k];A&&(C=b=A);const L=g==null?void 0:g[k];L&&(C=g=L),S||(p=C),A||(b=C),L||(g=C)}return(g==null?void 0:g[y])??(b==null?void 0:b[y])??(p==null?void 0:p[y])??""}return n}const dc=["aria-owns"],fc={class:"shell"},mc=["title"],hc={class:"search-actions before"},pc=["title"],gc=["aria-activedescendant","aria-controls","placeholder"],bc={class:"search-actions"},vc=["title"],yc=["disabled","title"],wc=["id","role","aria-labelledby"],xc=["id","aria-selected"],Ec=["href","aria-label","onMouseenter","onFocusin","data-index"],kc={class:"titles"},Sc=["innerHTML"],_c={class:"title main"},Ac=["innerHTML"],Tc={key:0,class:"excerpt-wrapper"},Cc={key:0,class:"excerpt",inert:""},Ic=["innerHTML"],Lc={key:0,class:"no-results"},Mc={class:"search-keyboard-shortcuts"},Nc=["aria-label"],Rc=["aria-label"],Oc=["aria-label"],Pc=["aria-label"],$c=gt({__name:"VPLocalSearchBox",emits:["close"],setup(t,{emit:e}){var me,se;const r=e,n=Xt(),i=Xt(),a=Xt(gl),o=oa(),{activate:s}=Yl(n,{immediate:!0,allowOutsideClick:!0,clickOutsideDeactivates:!0,escapeDeactivates:!0}),{localeIndex:u,theme:f}=o,g=Ji(async()=>{var D,z,Q,Se,re,he,ke,ge,_e;return Zi(br.loadJSON((Q=await((z=(D=a.value)[u.value])==null?void 0:z.call(D)))==null?void 0:Q.default,{fields:["title","titles","text"],storeFields:["title","titles"],searchOptions:{fuzzy:.2,prefix:!0,boost:{title:4,text:2,titles:1},...((Se=f.value.search)==null?void 0:Se.provider)==="local"&&((he=(re=f.value.search.options)==null?void 0:re.miniSearch)==null?void 0:he.searchOptions)},...((ke=f.value.search)==null?void 0:ke.provider)==="local"&&((_e=(ge=f.value.search.options)==null?void 0:ge.miniSearch)==null?void 0:_e.options)}))}),p=fe(()=>{var D,z;return((D=f.value.search)==null?void 0:D.provider)==="local"&&((z=f.value.search.options)==null?void 0:z.disableQueryPersistence)===!0}).value?$e(""):bs("vitepress:local-search-filter",""),y=vs("vitepress:local-search-detailed-list",((me=f.value.search)==null?void 0:me.provider)==="local"&&((se=f.value.search.options)==null?void 0:se.detailedView)===!0),E=fe(()=>{var D,z,Q;return((D=f.value.search)==null?void 0:D.provider)==="local"&&(((z=f.value.search.options)==null?void 0:z.disableDetailedView)===!0||((Q=f.value.search.options)==null?void 0:Q.detailedView)===!1)}),T=fe(()=>{var z,Q,Se,re,he,ke,ge;const D=((z=f.value.search)==null?void 0:z.options)??f.value.algolia;return((he=(re=(Se=(Q=D==null?void 0:D.locales)==null?void 0:Q[u.value])==null?void 0:Se.translations)==null?void 0:re.button)==null?void 0:he.buttonText)||((ge=(ke=D==null?void 0:D.translations)==null?void 0:ke.button)==null?void 0:ge.buttonText)||"Search"});di(()=>{E.value&&(y.value=!1)});const v=Xt([]),k=$e(!1);pt(p,()=>{k.value=!1});const C=Ji(async()=>{if(i.value)return Zi(new Zl(i.value))},null),S=new cc(16);ys(()=>[g.value,p.value,y.value],async([D,z,Q],Se,re)=>{var qe,Je,Ze,ut;(Se==null?void 0:Se[0])!==D&&S.clear();let he=!1;if(re(()=>{he=!0}),!D)return;v.value=D.search(z).slice(0,16),k.value=!0;const ke=Q?await Promise.all(v.value.map(Me=>A(Me.id))):[];if(he)return;for(const{id:Me,mod:et}of ke){const He=Me.slice(0,Me.indexOf("#"));let Ue=S.get(He);if(Ue)continue;Ue=new Map,S.set(He,Ue);const Be=et.default??et;if(Be!=null&&Be.render||Be!=null&&Be.setup){const ot=Ts(Be);ot.config.warnHandler=()=>{},ot.provide(Cs,o),Object.defineProperties(ot.config.globalProperties,{$frontmatter:{get(){return o.frontmatter.value}},$params:{get(){return o.page.value.params}}});const Mt=document.createElement("div");ot.mount(Mt),Mt.querySelectorAll("h1, h2, h3, h4, h5, h6").forEach(dt=>{var nr;const ft=(nr=dt.querySelector("a"))==null?void 0:nr.getAttribute("href"),wt=(ft==null?void 0:ft.startsWith("#"))&&ft.slice(1);if(!wt)return;let jt="";for(;(dt=dt.nextElementSibling)&&!/^h[1-6]$/i.test(dt.tagName);)jt+=dt.outerHTML;Ue.set(wt,jt)}),ot.unmount()}if(he)return}const ge=new Set;if(v.value=v.value.map(Me=>{const[et,He]=Me.id.split("#"),Ue=S.get(et),Be=(Ue==null?void 0:Ue.get(He))??"";for(const ot in Me.match)ge.add(ot);return{...Me,text:Be}}),await It(),he)return;await new Promise(Me=>{var et;(et=C.value)==null||et.unmark({done:()=>{var He;(He=C.value)==null||He.markRegExp(J(ge),{done:Me})}})});const _e=((qe=n.value)==null?void 0:qe.querySelectorAll(".result .excerpt"))??[];for(const Me of _e)(Je=Me.querySelector('mark[data-markjs="true"]'))==null||Je.scrollIntoView({block:"center"});(ut=(Ze=i.value)==null?void 0:Ze.firstElementChild)==null||ut.scrollIntoView({block:"start"})},{debounce:200,immediate:!0});async function A(D){const z=Is(D.slice(0,D.indexOf("#")));try{if(!z)throw new Error(`Cannot find file for id: ${D}`);return{id:D,mod:await import(z)}}catch(Q){return console.error(Q),{id:D,mod:{}}}}const L=$e(),P=fe(()=>{var D;return((D=p.value)==null?void 0:D.length)<=0});function N(D=!0){var z,Q;(z=L.value)==null||z.focus(),D&&((Q=L.value)==null||Q.select())}zt(()=>{N()});function ie(D){D.pointerType==="mouse"&&N()}const te=$e(-1),ue=$e(!0);pt(v,D=>{te.value=D.length?0:-1,V()});function V(){It(()=>{const D=document.querySelector(".result.selected");D==null||D.scrollIntoView({block:"nearest"})})}Zr("ArrowUp",D=>{D.preventDefault(),te.value--,te.value<0&&(te.value=v.value.length-1),ue.value=!0,V()}),Zr("ArrowDown",D=>{D.preventDefault(),te.value++,te.value>=v.value.length&&(te.value=0),ue.value=!0,V()});const K=fi();Zr("Enter",D=>{if(D.isComposing||D.target instanceof HTMLButtonElement&&D.target.type!=="submit")return;const z=v.value[te.value];if(D.target instanceof HTMLInputElement&&!z){D.preventDefault();return}z&&(K.go(z.id),r("close"))}),Zr("Escape",()=>{r("close")});const I=uc({modal:{displayDetails:"Display detailed list",resetButtonTitle:"Reset search",backButtonTitle:"Close search",noResultsText:"No results for",footer:{selectText:"to select",selectKeyAriaLabel:"enter",navigateText:"to navigate",navigateUpKeyAriaLabel:"up arrow",navigateDownKeyAriaLabel:"down arrow",closeText:"to close",closeKeyAriaLabel:"escape"}}});zt(()=>{window.history.pushState(null,"",null)}),ws("popstate",D=>{D.preventDefault(),r("close")});const R=xs(As?document.body:null);zt(()=>{It(()=>{R.value=!0,It().then(()=>s())})}),En(()=>{R.value=!1});function U(){p.value="",It().then(()=>N(!1))}function J(D){return new RegExp([...D].sort((z,Q)=>Q.length-z.length).map(z=>`(${Ls(z)})`).join("|"),"gi")}function W(D){var Se;if(!ue.value)return;const z=(Se=D.target)==null?void 0:Se.closest(".result"),Q=Number.parseInt(z==null?void 0:z.dataset.index);Q>=0&&Q!==te.value&&(te.value=Q),ue.value=!1}return(D,z)=>{var Q,Se,re,he,ke;return j(),ct(Es,{to:"body"},[F("div",{ref_key:"el",ref:n,role:"button","aria-owns":(Q=v.value)!=null&&Q.length?"localsearch-list":void 0,"aria-expanded":"true","aria-haspopup":"listbox","aria-labelledby":"localsearch-label",class:"VPLocalSearchBox"},[F("div",{class:"backdrop",onClick:z[0]||(z[0]=ge=>D.$emit("close"))}),F("div",fc,[F("form",{class:"search-bar",onPointerup:z[4]||(z[4]=ge=>ie(ge)),onSubmit:z[5]||(z[5]=Yn(()=>{},["prevent"]))},[F("label",{title:T.value,id:"localsearch-label",for:"localsearch-input"},[...z[7]||(z[7]=[F("span",{"aria-hidden":"true",class:"vpi-search search-icon local-search-icon"},null,-1)])],8,mc),F("div",hc,[F("button",{class:"back-button",title:ve(I)("modal.backButtonTitle"),onClick:z[1]||(z[1]=ge=>D.$emit("close"))},[...z[8]||(z[8]=[F("span",{class:"vpi-arrow-left local-search-icon"},null,-1)])],8,pc)]),ks(F("input",{ref_key:"searchInput",ref:L,"onUpdate:modelValue":z[2]||(z[2]=ge=>_s(p)?p.value=ge:null),"aria-activedescendant":te.value>-1?"localsearch-item-"+te.value:void 0,"aria-autocomplete":"both","aria-controls":(Se=v.value)!=null&&Se.length?"localsearch-list":void 0,"aria-labelledby":"localsearch-label",autocapitalize:"off",autocomplete:"off",autocorrect:"off",class:"search-input",id:"localsearch-input",enterkeyhint:"go",maxlength:"64",placeholder:T.value,spellcheck:"false",type:"search"},null,8,gc),[[Ss,ve(p)]]),F("div",bc,[E.value?we("",!0):(j(),H("button",{key:0,class:Pe(["toggle-layout-button",{"detailed-list":ve(y)}]),type:"button",title:ve(I)("modal.displayDetails"),onClick:z[3]||(z[3]=ge=>te.value>-1&&(y.value=!ve(y)))},[...z[9]||(z[9]=[F("span",{class:"vpi-layout-list local-search-icon"},null,-1)])],10,vc)),F("button",{class:"clear-button",type:"reset",disabled:P.value,title:ve(I)("modal.resetButtonTitle"),onClick:U},[...z[10]||(z[10]=[F("span",{class:"vpi-delete local-search-icon"},null,-1)])],8,yc)])],32),F("ul",{ref_key:"resultsEl",ref:i,id:(re=v.value)!=null&&re.length?"localsearch-list":void 0,role:(he=v.value)!=null&&he.length?"listbox":void 0,"aria-labelledby":(ke=v.value)!=null&&ke.length?"localsearch-label":void 0,class:"results",onMousemove:W},[(j(!0),H(Xe,null,it(v.value,(ge,_e)=>(j(),H("li",{key:ge.id,id:"localsearch-item-"+_e,"aria-selected":te.value===_e?"true":"false",role:"option"},[F("a",{href:ge.id,class:Pe(["result",{selected:te.value===_e}]),"aria-label":[...ge.titles,ge.title].join(" > "),onMouseenter:qe=>!ue.value&&(te.value=_e),onFocusin:qe=>te.value=_e,onClick:z[6]||(z[6]=qe=>D.$emit("close")),"data-index":_e},[F("div",null,[F("div",kc,[z[12]||(z[12]=F("span",{class:"title-icon"},"#",-1)),(j(!0),H(Xe,null,it(ge.titles,(qe,Je)=>(j(),H("span",{key:Je,class:"title"},[F("span",{class:"text",innerHTML:qe},null,8,Sc),z[11]||(z[11]=F("span",{class:"vpi-chevron-right local-search-icon"},null,-1))]))),128)),F("span",_c,[F("span",{class:"text",innerHTML:ge.title},null,8,Ac)])]),ve(y)?(j(),H("div",Tc,[ge.text?(j(),H("div",Cc,[F("div",{class:"vp-doc",innerHTML:ge.text},null,8,Ic)])):we("",!0),z[13]||(z[13]=F("div",{class:"excerpt-gradient-bottom"},null,-1)),z[14]||(z[14]=F("div",{class:"excerpt-gradient-top"},null,-1))])):we("",!0)])],42,Ec)],8,xc))),128)),ve(p)&&!v.value.length&&k.value?(j(),H("li",Lc,[Zt(Ae(ve(I)("modal.noResultsText"))+' "',1),F("strong",null,Ae(ve(p)),1),z[15]||(z[15]=Zt('" ',-1))])):we("",!0)],40,wc),F("div",Mc,[F("span",null,[F("kbd",{"aria-label":ve(I)("modal.footer.navigateUpKeyAriaLabel")},[...z[16]||(z[16]=[F("span",{class:"vpi-arrow-up navigate-icon"},null,-1)])],8,Nc),F("kbd",{"aria-label":ve(I)("modal.footer.navigateDownKeyAriaLabel")},[...z[17]||(z[17]=[F("span",{class:"vpi-arrow-down navigate-icon"},null,-1)])],8,Rc),Zt(" "+Ae(ve(I)("modal.footer.navigateText")),1)]),F("span",null,[F("kbd",{"aria-label":ve(I)("modal.footer.selectKeyAriaLabel")},[...z[18]||(z[18]=[F("span",{class:"vpi-corner-down-left navigate-icon"},null,-1)])],8,Oc),Zt(" "+Ae(ve(I)("modal.footer.selectText")),1)]),F("span",null,[F("kbd",{"aria-label":ve(I)("modal.footer.closeKeyAriaLabel")},"esc",8,Pc),Zt(" "+Ae(ve(I)("modal.footer.closeText")),1)])])])],8,dc)])}}}),Fc=Ms($c,[["__scopeId","data-v-42e65fb9"]]),vr=Xt(null);function Dc(t){vr.value=t}function zc(t){vr.value===t&&(vr.value=null)}function Vc(){var t;(t=vr.value)==null||t.call(vr)}const jc=gt({__name:"DocsSearchProvider",setup(t){const e=$e(!1);function r(){e.value=!0}function n(){e.value=!1}function i(o){const s=o.target,u=s.tagName;return s.isContentEditable||u==="INPUT"||u==="SELECT"||u==="TEXTAREA"}function a(o){(o.key.toLowerCase()==="k"&&(o.metaKey||o.ctrlKey)||!i(o)&&o.key==="/")&&(o.preventDefault(),r())}return zt(()=>{Dc(r),window.addEventListener("keydown",a)}),jo(()=>{zc(r),window.removeEventListener("keydown",a)}),(o,s)=>e.value?(j(),ct(Fc,{key:0,onClose:n})):we("",!0)}}),qc={class:"relative"},Hc={class:"min-w-0 flex-1 break-words"},Uc=["href"],Bc={class:"flex min-w-0 flex-1 items-start gap-x-2.5"},Yc={class:"flex min-w-0 flex-1 flex-wrap items-center gap-1.5 [word-break:break-word]"},Wc={class:"min-w-0 max-w-full break-words"},Kc=gt({__name:"DocsSidebarNode",props:{item:{},depth:{default:0}},emits:["navigate"],setup(t,{emit:e}){const r=t,n=e,{page:i}=Vt(),a=fi(),o=fe(()=>{var y;return!!((y=r.item.items)!=null&&y.length)}),s=fe(()=>kn(i.value.relativePath,r.item.link)),u=fe(()=>{var y;return((y=r.item.items)==null?void 0:y.some(E=>qr(i.value.relativePath,E)))??!1}),f=$e(o.value?!r.item.collapsed||u.value:!1);pt(u,y=>{y&&(f.value=!0)});function g(y){return y?Tt(y):"#"}async function b(y,E){E&&(y.preventDefault(),await a.go(g(E)),n("navigate"))}function p(){o.value&&(f.value=!f.value)}return(y,E)=>{const T=qo("DocsSidebarNode",!0);return j(),H("li",qc,[o.value?(j(),H("button",{key:0,type:"button",class:Pe(["group flex w-full cursor-pointer items-center py-0.5 pr-2 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",u.value?"text-docs-primary":"text-slate-700"]),onClick:p},[F("span",Hc,Ae(t.item.text),1),(j(),H("svg",{viewBox:"0 0 640 640",class:Pe(["size-3 shrink-0",f.value?"rotate-90":"rotate-0"]),"aria-hidden":"true"},[...E[2]||(E[2]=[F("path",{d:"M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"},null,-1)])],2))],2)):(j(),H("a",{key:1,href:g(t.item.link),class:Pe(["group flex w-full cursor-pointer items-center py-0.5 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",s.value?"text-docs-primary":"text-slate-700"]),onClick:E[0]||(E[0]=v=>b(v,t.item.link))},[F("div",Bc,[t.item.icon?(j(),ct(Sn,{key:0,name:t.item.icon,class:"mt-1 size-4 shrink-0 text-slate-500 group-hover:text-slate-700"},null,8,["name"])):we("",!0),F("div",Yc,[F("span",Wc,Ae(t.item.text),1)])])],10,Uc)),o.value&&f.value?(j(),H("ul",{key:2,style:Br({marginLeft:t.depth===0?"1rem":"1.25rem"})},[(j(!0),H(Xe,null,it(t.item.items,v=>(j(),ct(T,{key:v.link??`${v.text}-${v.icon??""}`,item:v,depth:t.depth+1,onNavigate:E[1]||(E[1]=k=>n("navigate"))},null,8,["item","depth"]))),128))],4)):we("",!0)])}}}),Gc={"aria-label":"Sidebar navigation",class:"text-sm"},Jc={key:0,class:"mb-3 flex items-center gap-2.5 text-sm font-medium text-slate-900 lg:mb-2"},Zc={class:"space-y-px"},Qc=gt({__name:"DocsSidebar",emits:["navigate"],setup(t){const{sidebarGroups:e}=mi(),r=fe(()=>e.value.filter(n=>{var i;return(i=n.items)==null?void 0:i.length}));return(n,i)=>(j(),H("nav",Gc,[(j(!0),H(Xe,null,it(r.value,a=>{var o,s;return j(),H("section",{key:a.text??((s=(o=a.items)==null?void 0:o[0])==null?void 0:s.link),class:"mt-6 first:mt-0 lg:mt-6 lg:first:mt-0"},[a.text?(j(),H("h2",Jc,[a.icon?(j(),ct(Sn,{key:0,name:a.icon,class:"size-4 text-slate-600"},null,8,["name"])):we("",!0),Zt(" "+Ae(a.text),1)])):we("",!0),F("ul",Zc,[(j(!0),H(Xe,null,it(a.items,u=>(j(),ct(Kc,{key:u.link??`${u.text}-${u.icon??""}`,item:u,onNavigate:i[0]||(i[0]=f=>n.$emit("navigate"))},null,8,["item"]))),128))])])}),128))]))}}),Xc={key:0,class:"min-h-screen bg-slate-50 text-slate-900 lg:h-screen lg:overflow-hidden"},eu={class:"max-lg:contents lg:flex-1 lg:min-w-0 lg:overflow-x-clip"},tu={id:"navbar",class:"peer fixed top-0 z-30 w-full"},ru={class:"relative z-10 mx-auto max-w-[96rem] px-4"},nu={class:"relative"},iu={class:"lg:hidden"},ou={class:"flex h-14 items-center justify-between gap-3"},au={href:"/",class:"flex min-w-0 items-center gap-3 select-none"},su=["src"],lu={key:1,class:"min-w-0 truncate text-[15px] font-semibold tracking-[-0.01em] text-slate-900"},cu={class:"flex items-center gap-1.5"},uu=["href","aria-label"],du={key:0,viewBox:"0 0 24 24",fill:"currentColor",class:"h-[18px] w-[18px]","aria-hidden":"true"},fu=["aria-expanded"],mu={class:"min-w-0 truncate"},hu=["aria-label"],pu=["href","aria-selected"],gu=["aria-expanded"],bu={class:"ml-4 flex min-w-0 items-center space-x-3 overflow-hidden text-sm leading-6 whitespace-nowrap"},vu={key:0,class:"flex shrink-0 items-center space-x-3 text-slate-500"},yu={class:"min-w-0 flex-1 truncate font-semibold text-slate-900"},wu={class:"relative hidden h-14 min-w-0 flex-1 items-center gap-x-4 lg:flex lg:border-none"},xu={class:"flex min-w-0 flex-1 items-center gap-x-4"},Eu={href:"/",class:"flex min-w-0 items-center gap-3 select-none"},ku=["src"],Su={key:1,class:"min-w-0 truncate text-[15px] font-semibold tracking-[-0.01em] text-slate-900"},_u=["aria-expanded"],Au={class:"truncate"},Tu=["aria-label"],Cu=["href","aria-selected"],Iu={class:"flex items-center gap-4"},Lu={class:"flex items-center gap-2"},Mu=["href","aria-label"],Nu={key:0,viewBox:"0 0 24 24",fill:"currentColor",class:"h-[18px] w-[18px]","aria-hidden":"true"},Ru={class:"scroll-mt-[var(--scroll-mt)] fixed top-[7rem] w-full pb-2 pt-0 lg:top-[3.5rem]"},Ou={key:0,id:"sidebar-content",class:"hidden min-h-0 lg:flex lg:flex-col"},Pu={class:"flex h-full min-h-0 flex-col gap-4 text-sm"},$u={class:"relative z-20 hidden items-center gap-2.5 mr-4 mt-2 mb-2 lg:flex"},Fu={class:"min-w-0 h-full min-h-0"},Du={class:"mx-auto w-full max-w-[88rem] xl:grid xl:grid-cols-[minmax(0,52rem)_16.5rem] xl:justify-center xl:gap-x-12"},zu={id:"content-area",class:"w-full min-w-0 overflow-x-visible"},Vu={key:0,class:"eyebrow mb-2.5 h-5 text-sm font-semibold text-docs-primary"},ju={key:1,class:"mt-12 border-t border-slate-200 pt-6"},qu={key:0,class:"flex flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between"},Hu=["href"],Uu={key:1},Bu={key:1,class:"mt-6 grid gap-3 sm:grid-cols-2"},Yu=["href"],Wu={class:"mt-1 text-sm font-medium text-slate-900 group-hover:text-docs-primary-strong"},Ku={key:1,class:"hidden sm:block"},Gu=["href"],Ju={class:"mt-1 text-sm font-medium text-slate-900 group-hover:text-docs-primary-strong"},Zu={key:0,id:"content-side-layout",class:"hidden xl:block"},Qu={class:"sticky top-0 pt-1"},Xu={id:"table-of-contents-shell",class:"max-h-[calc(100dvh-7rem)] w-[16.5rem] overflow-y-auto space-y-2 pb-4 text-sm leading-6 text-slate-600"},ed=gt({__name:"Layout",setup(t){const e=Uo(),{frontmatter:r,page:n,site:i,theme:a}=Vt(),{close:o,hasSidebar:s,isOpen:u,toggle:f,sidebarGroups:g}=mi(),b=$e(null),p=$e(null),y=$e(!1),E=$e(null),T=$e(null),v=$e(!1),k=$e(!1);Ds(u,o),pt(()=>e.path,o),pt(()=>e.path,async(M,O)=>{await ft(M,O)});const C=fe(()=>a.value.logo?typeof a.value.logo=="string"?Tt(a.value.logo):Tt(a.value.logo.src):null),S=fe(()=>r.value.layout===!1||r.value.layout==="home"||r.value.aside===!1?!1:(r.value.outline??a.value.outline)!==!1),A=fe(()=>typeof a.value.siteTitle=="string"&&a.value.siteTitle.trim()?a.value.siteTitle:i.value.title),L=fe(()=>a.value.socialLinks??[]),P=fe(()=>g.value.find(M=>{var O;return(O=M.items)==null?void 0:O.some(Z=>qr(n.value.relativePath,Z))})??null),N=fe(()=>r.value.title??n.value.title??A.value),ie=fe(()=>{const M=[ge.value,N.value].filter(O=>!!(O!=null&&O.trim()));return M.filter((O,Z)=>O!==M[Z-1])}),te=fe(()=>ie.value.length>1?ie.value[0]:null),ue=fe(()=>ie.value.at(-1)??A.value),V=fe(()=>{var O;const M=a.value;return!!((O=M.search)!=null&&O.provider||M.algolia)}),K=fe(()=>a.value.docsTheme??{}),Y=fe(()=>a.value.editLink),I=fe(()=>a.value.lastUpdatedText??"Last updated");function R(M){var Z;const O=[];for(const ee of M)ee.link&&O.push(ee),(Z=ee.items)!=null&&Z.length&&O.push(...R(ee.items));return O}function U(M,O){var Z;for(const ee of M){if(ee.link&&qr(O,ee))return[ee];if(!((Z=ee.items)!=null&&Z.length))continue;const ce=U(ee.items,O);if(ce)return[ee,...ce]}return null}function J(M){var Z;const O=[];for(const ee of M)ee.text&&ee.link&&O.push({text:ee.text,link:ee.link,activeMatch:ee.activeMatch}),(Z=ee.items)!=null&&Z.length&&O.push(...J(ee.items));return O}function W(M){const O=decodeURI(M).split(/[?#]/,1)[0]||"/";if(O==="/")return"/";const ee=O.replace(/\/index(?:\.html)?$/,"/").replace(/\.html$/,"");return ee==="/"?"/":ee.replace(/\/+$/,"")}const me=fe(()=>W(e.path)),se=fe(()=>{const M=Array.isArray(a.value.nav)?a.value.nav:[];return J(M)});function D(M){if(M.activeMatch)return new RegExp(M.activeMatch).test(me.value);const O=W(M.link);return O==="/"?me.value==="/":me.value===O||me.value.startsWith(`${O}/`)}const z=fe(()=>se.value.find(M=>D(M))??null),Q=fe(()=>{var M,O;return((M=z.value)==null?void 0:M.text)??((O=se.value[0])==null?void 0:O.text)??"Documentation"});function Se(){v.value=!1,k.value=!1}function re(){v.value=!v.value,k.value=!1}function he(){k.value=!k.value,v.value=!1}function ke(M){const O=M.target;v.value&&E.value&&!E.value.contains(O)&&(v.value=!1),k.value&&T.value&&!T.value.contains(O)&&(k.value=!1)}pt(()=>e.path,Se);const ge=fe(()=>{var Z,ee;const M=P.value;if(!((Z=M==null?void 0:M.items)!=null&&Z.length))return(M==null?void 0:M.text)??null;const O=U(M.items,n.value.relativePath);return!(O!=null&&O.length)||O.length===1?M.text??null:((ee=O.at(-2))==null?void 0:ee.text)??M.text??null}),_e=fe(()=>{var M,O;return(O=(M=P.value)==null?void 0:M.items)!=null&&O.length?R(P.value.items):[]}),qe=fe(()=>_e.value.findIndex(M=>kn(n.value.relativePath,M.link))),Je=fe(()=>{const M=qe.value;return M>0?_e.value[M-1]:null}),Ze=fe(()=>{const M=qe.value;return M>=0&&M<_e.value.length-1?_e.value[M+1]:null}),ut=fe(()=>{var Z,ee;if(r.value.editLink===!1)return null;const M=(Z=Y.value)==null?void 0:Z.pattern,O=n.value.filePath;return!M||!O?null:{text:((ee=Y.value)==null?void 0:ee.text)??"Edit this page",href:M.replace(":path",O)}}),Me=fe(()=>{if(r.value.lastUpdated===!1)return null;const M=n.value.lastUpdated;return M?new Intl.DateTimeFormat(i.value.lang||void 0,{dateStyle:"medium",timeStyle:"short"}).format(M):null}),et=fe(()=>{var O,Z,ee,ce,Ye,Le;const M=((O=K.value.primary)==null?void 0:O.trim())||"#0f766e";return{"--docs-primary":M,"--docs-primary-strong":((Z=K.value.primaryStrong)==null?void 0:Z.trim())||`color-mix(in oklab, ${M} 82%, black)`,"--docs-primary-soft":((ee=K.value.primarySoft)==null?void 0:ee.trim())||`color-mix(in oklab, ${M} 12%, white)`,"--docs-primary-soft-hover":((ce=K.value.primarySoftHover)==null?void 0:ce.trim())||`color-mix(in oklab, ${M} 16%, white)`,"--docs-primary-border":((Ye=K.value.primaryBorder)==null?void 0:Ye.trim())||`color-mix(in oklab, ${M} 18%, white)`,"--docs-primary-border-strong":((Le=K.value.primaryBorderStrong)==null?void 0:Le.trim())||`color-mix(in oklab, ${M} 28%, white)`}});function He(){const M=p.value;if(!M){y.value=!1;return}y.value=M.scrollTop>4}function Ue(M){return M.split("#")[0]??M}function Be(M){const O=M.indexOf("#");return O>=0?decodeURIComponent(M.slice(O+1)):""}function ot(M){const O=Be(M);return O||(typeof window<"u"?decodeURIComponent(window.location.hash.replace(/^#/,"")):"")}function Mt(){return b.value??document.getElementById("docs-scroll-container")??document.getElementById("content-container")}function Yr(){var M;(M=Mt())==null||M.scrollTo({top:0,left:0,behavior:"auto"}),window.scrollTo({top:0,left:0,behavior:"auto"})}function dt(M){if(!M)return!1;const O=document.getElementById(M),Z=Mt();if(!(O instanceof HTMLElement))return!1;if(!(Z instanceof HTMLElement))return O.scrollIntoView({block:"start"}),!0;const ee=Z.scrollTop+O.getBoundingClientRect().top-Z.getBoundingClientRect().top-24;return Z.scrollTo({top:Math.max(0,ee),left:0,behavior:"auto"}),!0}async function ft(M,O){await It(),He(),Ue(M)!==Ue(O??"")&&Yr();const Z=ot(M);Z&&(await It(),dt(Z))}function wt(M,O,Z){const ee=(Z==null?void 0:Z.size)??18,ce=(Z==null?void 0:Z.strokeWidth)??1.5,Ye=(Z==null?void 0:Z.viewBox)??`0 0 ${ee} ${ee}`,Le=document.createElementNS("http://www.w3.org/2000/svg","svg");Le.setAttribute("width",String(ee)),Le.setAttribute("height",String(ee)),Le.setAttribute("viewBox",Ye),Le.setAttribute("fill","none"),Le.setAttribute("aria-hidden","true"),Le.setAttribute("class",O);for(const or of M.split("||")){const Qe=document.createElementNS("http://www.w3.org/2000/svg","path");Qe.setAttribute("d",or),Z!=null&&Z.fill?Qe.setAttribute("fill","currentColor"):(Qe.setAttribute("stroke","currentColor"),Qe.setAttribute("stroke-width",String(ce)),Qe.setAttribute("stroke-linecap","round"),Qe.setAttribute("stroke-linejoin","round")),Le.append(Qe)}return Le}async function jt(M){var Z;try{if((Z=navigator.clipboard)!=null&&Z.writeText)return await navigator.clipboard.writeText(M),!0}catch{}const O=document.createElement("textarea");O.value=M,O.setAttribute("readonly",""),O.style.position="fixed",O.style.opacity="0",O.style.pointerEvents="none",document.body.append(O),O.select(),O.setSelectionRange(0,M.length);try{return document.execCommand("copy")}finally{O.remove()}}function nr(M){const O=Array.from(M.querySelectorAll("pre code .line"));if(O.length)return O.map(ee=>ee.textContent??"").join(`
`).replace(/\n$/,"");const Z=M.querySelector("pre code");return((Z==null?void 0:Z.textContent)??"").replace(/\n$/,"")}function Wr(M){const O=new URL(window.location.href);return O.hash=M,O}function Kr(){const M=window.getSelection();return!!(M&&M.type==="Range"&&M.toString().trim())}function ir(){document.querySelectorAll(".vp-doc h2[id], .vp-doc h3[id], .vp-doc h4[id], .vp-doc h5[id], .vp-doc h6[id]").forEach(M=>{if(M.dataset.docsHeadingCopyBound==="true")return;const O=M.querySelector(".header-anchor");if(!O)return;M.dataset.docsHeadingCopyBound="true",M.classList.add("docs-copyable-heading");const Z=document.createElement("span");for(Z.className="anchor-heading__content";M.childNodes.length>0;){const ce=M.firstChild;if(ce===O)break;Z.appendChild(ce)}const ee=document.createElement("div");ee.className="anchor-heading__icon-wrap",ee.tabIndex=-1,ee.appendChild(O),M.prepend(ee),M.appendChild(Z),O.replaceChildren(wt("M0 256C0 167.6 71.6 96 160 96h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H160C98.1 144 48 194.1 48 256s50.1 112 112 112h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H160C71.6 416 0 344.4 0 256zm576 0c0 88.4-71.6 160-160 160H344c-13.3 0-24-10.7-24-24s10.7-24 24-24h72c61.9 0 112-50.1 112-112s-50.1-112-112-112H344c-13.3 0-24-10.7-24-24s10.7-24 24-24h72c88.4 0 160 71.6 160 160zM184 232H392c13.3 0 24 10.7 24 24s-10.7 24-24 24H184c-13.3 0-24-10.7-24-24s10.7-24 24-24z","docs-heading-anchor__icon",{fill:!0,size:12,viewBox:"0 0 576 512"})),M.addEventListener("click",ce=>{if(Kr()||ce.target instanceof HTMLElement&&ce.target.closest("a:not(.header-anchor)"))return;const Ye=ce.target instanceof HTMLElement?ce.target:null,Le=Ye==null?void 0:Ye.closest(".anchor-heading__content"),or=Ye==null?void 0:Ye.closest(".header-anchor");if(!Le&&!or)return;ce.preventDefault();const Qe=Wr(M.id);window.history.replaceState(null,"",`${Qe.pathname}${Qe.search}${Qe.hash}`),dt(M.id),jt(Qe.toString())})})}function xt(){document.querySelectorAll('.vp-doc [class*="language-"] > button.copy').forEach(M=>{if(M.querySelector(".docs-copy-button__icon")){if(M.dataset.docsCopyBound==="true")return}else{const O=wt("M14.25 5.25H7.25C6.14543 5.25 5.25 6.14543 5.25 7.25V14.25C5.25 15.3546 6.14543 16.25 7.25 16.25H14.25C15.3546 16.25 16.25 15.3546 16.25 14.25V7.25C16.25 6.14543 15.3546 5.25 14.25 5.25Z||M2.80103 11.998L1.77203 5.07397C1.61003 3.98097 2.36403 2.96397 3.45603 2.80197L10.38 1.77297C11.313 1.63397 12.19 2.16297 12.528 3.00097","docs-copy-button__icon"),Z=wt("M2.75 9.25L6.75 13.25L15.25 4.75","docs-copy-button__icon docs-copy-button__icon--copied",{size:18,strokeWidth:2,viewBox:"0 0 18 18"}),ee=document.createElement("span");ee.className="sr-only",ee.textContent=M.title||"Copy code",M.replaceChildren(O,Z,ee)}M.dataset.docsCopyBound="true",M.type="button",M.addEventListener("click",async O=>{O.preventDefault(),O.stopPropagation();const Z=M.closest('[class*="language-"]');if(!(Z instanceof HTMLElement))return;const ee=nr(Z);!ee||!await jt(ee)||(M.classList.add("copied"),window.setTimeout(()=>{M.classList.remove("copied")},1500))})})}function bt(){Vc()}return zt(async()=>{var M;document.addEventListener("pointerdown",ke),await ft(e.path),xt(),ir(),(M=p.value)==null||M.addEventListener("scroll",He,{passive:!0})}),Ho(async()=>{await It(),xt(),ir(),dt(ot(e.path))}),En(()=>{var M;document.removeEventListener("pointerdown",ke),(M=p.value)==null||M.removeEventListener("scroll",He)}),(M,O)=>{var Z,ee;return ve(r).layout!==!1?(j(),H("div",Xc,[V.value?(j(),ct(jc,{key:0})):we("",!0),F("div",{class:"max-lg:contents lg:flex lg:w-full","data-docs-theme":"almond",style:Br(et.value)},[F("div",eu,[F("header",tu,[F("div",ru,[F("div",nu,[F("div",{class:Pe(["transition-opacity duration-200",ve(u)?"max-lg:pointer-events-none max-lg:opacity-0":""])},[F("div",iu,[F("div",ou,[F("a",au,[C.value?(j(),H("img",{key:0,src:C.value,alt:"",class:"relative block h-6 w-auto max-w-[156px] shrink-0 object-contain"},null,8,su)):we("",!0),C.value?we("",!0):(j(),H("div",lu,Ae(A.value),1))]),F("div",cu,[V.value?(j(),H("button",{key:0,type:"button",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":"Open search",onClick:bt},[...O[3]||(O[3]=[F("svg",{viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round",class:"h-[18px] w-[18px]","aria-hidden":"true"},[F("circle",{cx:"11",cy:"11",r:"8"}),F("path",{d:"m21 21-4.3-4.3"})],-1)])])):we("",!0),(j(!0),H(Xe,null,it(L.value,ce=>(j(),H("a",{key:`mobile-${ce.link}`,href:ce.link,target:"_blank",rel:"noreferrer",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":ce.icon},[ce.icon==="github"?(j(),H("svg",du,[...O[4]||(O[4]=[F("path",{d:"M12 .5a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58l-.02-2.04c-3.34.73-4.04-1.42-4.04-1.42-.54-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.9 0-1.3.46-2.36 1.23-3.19-.12-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.22a11.5 11.5 0 0 1 6 0c2.3-1.54 3.3-1.22 3.3-1.22.65 1.65.24 2.87.12 3.17.76.83 1.22 1.89 1.22 3.19 0 4.58-2.81 5.59-5.49 5.89.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.22.7.83.58A12 12 0 0 0 12 .5Z"},null,-1)])])):we("",!0)],8,uu))),128))])]),se.value.length?(j(),H("div",{key:0,ref_key:"topNavRootMobile",ref:T,class:"relative border-t border-slate-100 px-1 py-2 lg:hidden"},[F("button",{type:"button",class:"flex w-full items-center justify-between gap-2 rounded-lg border border-slate-200/80 bg-white/80 px-3 py-2 text-left text-sm font-medium text-slate-800","aria-expanded":k.value,"aria-haspopup":"listbox","aria-label":"Documentation section",onClick:Yn(he,["stop"])},[F("span",mu,Ae(Q.value),1),(j(),H("svg",{class:Pe(["h-4 w-4 shrink-0 text-slate-500 transition-transform",k.value?"rotate-180":""]),viewBox:"0 0 20 20",fill:"none","aria-hidden":"true"},[...O[5]||(O[5]=[F("path",{d:"M5 7.5L10 12.5L15 7.5",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round","stroke-linejoin":"round"},null,-1)])],2))],8,fu),k.value?(j(),H("div",{key:0,class:"absolute left-1 right-1 top-full z-[100] mt-1 max-h-[min(60vh,24rem)] overflow-y-auto rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg",role:"listbox","aria-label":`${Q.value} options`},[(j(!0),H(Xe,null,it(se.value,ce=>(j(),H("a",{key:ce.link,href:ve(Tt)(ce.link),role:"option",class:Pe(["block truncate px-3 py-2 text-sm transition",D(ce)?"bg-docs-primary-soft font-medium text-docs-primary-strong":"text-slate-700 hover:bg-slate-50"]),"aria-selected":D(ce),onClick:Se},Ae(ce.text),11,pu))),128))],8,hu)):we("",!0)],512)):we("",!0),ve(s)?(j(),H("button",{key:1,type:"button",class:"flex h-14 w-full items-center px-1 text-left cursor-pointer focus:outline-0","aria-label":"Open navigation menu","aria-expanded":ve(u),onClick:O[0]||(O[0]=(...ce)=>ve(f)&&ve(f)(...ce))},[O[7]||(O[7]=F("div",{class:"text-slate-500 transition hover:text-slate-600"},[F("span",{class:"sr-only"},"Navigation"),F("svg",{class:"h-4",fill:"currentColor",viewBox:"0 0 448 512","aria-hidden":"true"},[F("path",{d:"M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"})])],-1)),F("div",bu,[te.value?(j(),H("div",vu,[F("span",null,Ae(te.value),1),O[6]||(O[6]=F("svg",{width:"3",height:"24",viewBox:"0 -9 3 24",class:"h-5 overflow-visible text-slate-400","aria-hidden":"true"},[F("path",{d:"M0 0L3 3L0 6",fill:"none",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round"})],-1))])):we("",!0),F("div",yu,Ae(ue.value),1)])],8,gu)):we("",!0)]),F("div",wu,[F("div",xu,[F("a",Eu,[C.value?(j(),H("img",{key:0,src:C.value,alt:"",class:"relative block h-6 w-auto max-w-[156px] shrink-0 object-contain"},null,8,ku)):we("",!0),C.value?we("",!0):(j(),H("div",Su,Ae(A.value),1))]),se.value.length?(j(),H("div",{key:0,ref_key:"topNavRootDesktop",ref:E,class:"relative hidden min-w-0 shrink lg:block"},[F("button",{type:"button",class:"inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200/80 bg-white/70 px-3 py-1.5 text-sm font-medium text-slate-800 backdrop-blur transition hover:bg-slate-100/90","aria-expanded":v.value,"aria-haspopup":"listbox","aria-label":"Documentation section",onClick:Yn(re,["stop"])},[F("span",Au,Ae(Q.value),1),(j(),H("svg",{class:Pe(["h-4 w-4 shrink-0 text-slate-500 transition-transform",v.value?"rotate-180":""]),viewBox:"0 0 20 20",fill:"none","aria-hidden":"true"},[...O[8]||(O[8]=[F("path",{d:"M5 7.5L10 12.5L15 7.5",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round","stroke-linejoin":"round"},null,-1)])],2))],8,_u),v.value?(j(),H("div",{key:0,class:"absolute left-0 top-full z-[100] mt-1 min-w-[12rem] max-w-[min(100vw-2rem,22rem)] rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg",role:"listbox","aria-label":`${Q.value} options`},[(j(!0),H(Xe,null,it(se.value,ce=>(j(),H("a",{key:ce.link,href:ve(Tt)(ce.link),role:"option",class:Pe(["block truncate px-3 py-2 text-sm transition",D(ce)?"bg-docs-primary-soft font-medium text-docs-primary-strong":"text-slate-700 hover:bg-slate-50"]),"aria-selected":D(ce),onClick:Se},Ae(ce.text),11,Cu))),128))],8,Tu)):we("",!0)],512)):we("",!0)]),F("div",Iu,[F("div",Lu,[(j(!0),H(Xe,null,it(L.value,ce=>(j(),H("a",{key:ce.link,href:ce.link,target:"_blank",rel:"noreferrer",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":ce.icon},[ce.icon==="github"?(j(),H("svg",Nu,[...O[9]||(O[9]=[F("path",{d:"M12 .5a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58l-.02-2.04c-3.34.73-4.04-1.42-4.04-1.42-.54-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.9 0-1.3.46-2.36 1.23-3.19-.12-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.22a11.5 11.5 0 0 1 6 0c2.3-1.54 3.3-1.22 3.3-1.22.65 1.65.24 2.87.12 3.17.76.83 1.22 1.89 1.22 3.19 0 4.58-2.81 5.59-5.49 5.89.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.22.7.83.58A12 12 0 0 0 12 .5Z"},null,-1)])])):we("",!0)],8,Mu))),128))])])])],2)])])]),F("div",Ru,[ve(s)?(j(),H("div",{key:0,class:Pe(["fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-md transition-opacity duration-300 lg:hidden",ve(u)?"pointer-events-auto opacity-100":"pointer-events-none opacity-0"]),onClick:O[1]||(O[1]=(...ce)=>ve(o)&&ve(o)(...ce))},null,2)):we("",!0),ve(s)?(j(),H("button",{key:1,type:"button",class:Pe(["fixed right-4 top-5 z-[60] inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/70 bg-white/95 text-slate-500 shadow-[0_8px_24px_rgba(15,23,42,0.16)] backdrop-blur transition duration-300 lg:hidden",ve(u)?"pointer-events-auto opacity-100 scale-100":"pointer-events-none opacity-0 scale-95"]),"aria-label":"Close navigation menu",onClick:O[2]||(O[2]=(...ce)=>ve(o)&&ve(o)(...ce))},[...O[10]||(O[10]=[F("svg",{viewBox:"0 0 20 20",fill:"none",class:"h-5 w-5","aria-hidden":"true"},[F("path",{d:"M5 5L15 15M15 5L5 15",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round"})],-1)])],2)):we("",!0),ve(s)?(j(),H("aside",{key:2,class:Pe(["fixed inset-y-0 left-0 z-50 w-[min(22rem,calc(100vw-2.5rem))] max-w-full overflow-y-auto overscroll-contain border-r border-slate-200/80 bg-white shadow-[0_28px_90px_rgba(15,23,42,0.22)] transition-transform duration-300 lg:hidden",ve(u)?"translate-x-0":"-translate-x-[105%]"])},[Nr(rl,{"logo-src":C.value,"site-title":A.value,onNavigate:ve(o)},null,8,["logo-src","site-title","onNavigate"])],2)):we("",!0),F("div",{class:Pe(["mx-auto grid h-[calc(100dvh-8rem)] min-h-0 w-full max-w-[96rem] rounded-2xl px-2 lg:h-[calc(100dvh-4rem)] lg:grid-cols-[16.5rem_minmax(0,1fr)] lg:gap-x-2 lg:px-4",ve(s)?"":"lg:grid-cols-[minmax(0,1fr)]"])},[ve(s)?(j(),H("div",Ou,[F("div",Pu,[F("div",$u,[V.value?(j(),H("button",{key:0,type:"button",class:"group/search flex h-9 w-full items-center justify-between gap-2 rounded-lg bg-white pl-3.5 pr-3 text-left text-sm leading-6 text-gray-500 ring-1 ring-gray-400/30 transition-[color,box-shadow] hover:text-gray-800 hover:ring-gray-600/30","aria-label":"Open search",onClick:bt},[...O[11]||(O[11]=[Ns('<div class="flex min-w-0 items-center gap-2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 min-w-4 flex-none text-gray-700" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg><div class="min-w-0 truncate">Search...</div></div><span class="flex-none text-xs">⌘K</span>',2)])])):we("",!0)]),F("div",{id:"navigation-items",ref_key:"navigationItems",ref:p,class:Pe(["stable-scrollbar-gutter pb-4 min-h-0 flex-1 overflow-y-auto",y.value?"[mask-image:linear-gradient(transparent,black_32px)] [-webkit-mask-image:linear-gradient(transparent,black_32px)]":""])},[Nr(Qc)],2)])])):we("",!0),F("div",Fu,[F("div",{id:"docs-scroll-container",ref_key:"docsScrollContainer",ref:b,class:"stable-scrollbar-gutter h-full overflow-y-auto rounded-xl border border-gray-400/30 bg-white px-8 pt-8 pb-10 lg:px-10 lg:pt-10"},[F("div",Du,[F("main",zu,[ve(n).isNotFound?(j(),ct(ll,{key:0})):(j(),H(Xe,{key:1},[ge.value?(j(),H("div",Vu,Ae(ge.value),1)):we("",!0),Nr(ve(Qi),{class:"vp-doc mdx-content relative prose prose-gray [contain:inline-size] isolate"}),ut.value||Me.value||Je.value||Ze.value?(j(),H("div",ju,[ut.value||Me.value?(j(),H("div",qu,[ut.value?(j(),H("a",{key:0,href:ut.value.href,target:"_blank",rel:"noreferrer",class:"font-medium text-docs-primary transition hover:text-docs-primary-strong"},Ae(ut.value.text),9,Hu)):we("",!0),Me.value?(j(),H("div",Uu,Ae(I.value)+": "+Ae(Me.value),1)):we("",!0)])):we("",!0),Je.value||Ze.value?(j(),H("div",Bu,[(Z=Je.value)!=null&&Z.link?(j(),H("a",{key:0,href:ve(Tt)(Je.value.link),class:"group rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft/50"},[O[12]||(O[12]=F("div",{class:"text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"},"Previous",-1)),F("div",Wu,Ae(Je.value.text),1)],8,Yu)):(j(),H("div",Ku)),(ee=Ze.value)!=null&&ee.link?(j(),H("a",{key:2,href:ve(Tt)(Ze.value.link),class:"group rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft/50 sm:text-right"},[O[13]||(O[13]=F("div",{class:"text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"},"Next",-1)),F("div",Ju,Ae(Ze.value.text),1)],8,Gu)):we("",!0)])):we("",!0)])):we("",!0)],64))]),S.value?(j(),H("aside",Zu,[F("div",Qu,[F("div",Xu,[Nr(pl)])])])):we("",!0)])],512)])],2)])])],4)])):(j(),ct(ve(Qi),{key:1}))}}});function td(t={}){return{Layout:ed,async enhanceApp(e){var r;await((r=t.enhanceApp)==null?void 0:r.call(t,e))}}}const rd=`@layer formie-base, formie-theme-base, formie-theme;

@layer formie-base {

    .formie-form,
    .formie-form fieldset,
    .formie-form legend,
    .formie-form h1,
    .formie-form h2,
    .formie-form h3,
    .formie-form h4,
    .formie-form h5,
    .formie-form h6,
    .formie-form p,
    .formie-form ul,
    .formie-form ol,
    .formie-form menu,
    .formie-form dl,
    .formie-form dd,
    .formie-form blockquote,
    .formie-form figure {
        margin: 0;
        padding: 0;
    }

    .formie-form,
    .formie-form *,
    .formie-form *::before,
    .formie-form *::after {
        box-sizing: border-box;
    }

    .formie-page-container,
    .formie-field-layout,
    .formie-form fieldset,
    .formie-form legend {
        border: 0;
        min-inline-size: 0;
    }

    /* Fix for Firefox display issue in fieldset */
    body:not(:-moz-handler-blocked) .formie-form fieldset {
        display: table-cell;
    }

    .formie-form legend {
        /* legend should be \`display: contents\` to work with grid */
        display: contents;
    }

    .formie-form legend * {
        /* legend should be \`display: contents\` to work with grid */
        display: contents;
    }

    .formie-sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
        display: block !important;
    }

    [data-formie-conditionally-hidden],
    [data-formie-page-hidden],
    [data-formie-row-hidden],
    .formie-conditionally-hidden,
    .formie-page-hidden,
    .formie-row-hidden,
    [hidden] {
        display: none !important;
    }
}`,nd=`@layer formie-theme-base {
    .formie-form button,
    .formie-form input,
    .formie-form select,
    .formie-form optgroup,
    .formie-form textarea,
    .formie-form ::file-selector-button {
        margin: 0;
        font: inherit;
        font-feature-settings: inherit;
        font-variation-settings: inherit;
        line-height: inherit;
        letter-spacing: inherit;
        color: inherit;
        border-radius: 0;
        background-color: transparent;
        opacity: 1;
    }

    .formie-form a,
    .formie-link,
    .formie-address-location {
        color: var(--formie-color-primary);
        text-decoration: underline;
        text-underline-offset: var(--formie-link-underline-offset);
        transition: color 150ms ease;
    }

    .formie-form a:hover,
    .formie-link:hover,
    .formie-address-location:hover {
        color: var(--formie-color-primary-hover);
    }
}
`,id=`@layer formie-theme-base {
    .formie-button {
        appearance: none;
        cursor: pointer;
        user-select: none;
    }

    .formie-input,
    .formie-textarea,
    .formie-select {
        box-sizing: border-box;
        width: 100%;
        padding: var(--formie-control-padding-y) var(--formie-control-padding-x);
        font-size: var(--formie-control-font-size);
        line-height: var(--formie-line-height-tight);
        min-height: var(--formie-control-height);
    }

    .formie-textarea {
        min-height: var(--formie-textarea-min-height);
        resize: vertical;
    }

    /* Prevent Mobile Safari auto-zoom on focus for input/select controls. */
    @supports (-webkit-touch-callout: none) {

        .formie-input,
        .formie-select {
            font-size: 16px;
        }
    }

    .formie-checkbox-input,
    .formie-radio-input {
        width: var(--formie-font-size-base);
        height: var(--formie-font-size-base);
        margin: calc(var(--formie-space-1) * 0.8) 0 0;
        padding: 0;
        flex: 0 0 auto;
    }
}`,od=`@layer formie-theme {
    .formie-form {
        --formie-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;

        --formie-font-size-xs: 0.75rem;
        --formie-font-size-sm: 0.875rem;
        --formie-font-size-base: 1rem;
        --formie-font-size-lg: 1.125rem;
        --formie-font-size-xl: 1.375rem;
        --formie-font-size-2xl: 1.75rem;

        --formie-font-weight-normal: 400;
        --formie-font-weight-medium: 500;
        --formie-font-weight-semibold: 600;
        --formie-font-weight-bold: 700;

        --formie-line-height-tight: 1.25;
        --formie-line-height-base: 1.5;
        --formie-line-height-relaxed: 1.4;
        --formie-letter-spacing-tight: -0.02em;

        --formie-space-1: 0.25rem;
        --formie-space-1-5: 0.375rem;
        --formie-space-2: 0.5rem;
        --formie-space-2-5: 0.625rem;
        --formie-space-3: 0.75rem;
        --formie-space-3-5: 0.875rem;
        --formie-space-4: 1rem;
        --formie-space-4-5: 1.125rem;
        --formie-space-5: 1.25rem;
        --formie-space-5-5: 1.375rem;
        --formie-space-6: 1.5rem;
        --formie-space-7: 1.75rem;
        --formie-space-8: 2rem;
        --formie-space-9: 2.25rem;
        --formie-space-10: 2.5rem;
        --formie-space-11: 2.75rem;
        --formie-space-12: 3rem;

        --formie-radius-sm: 0.25rem;
        --formie-radius-md: 0.375rem;
        --formie-radius-lg: 0.5rem;
        --formie-radius-full: 999px;
        --formie-border-width: 1px;

        /* Color palette */
        --formie-black: #000000;
        --formie-white: #ffffff;

        --formie-neutral-50: #f8fafc;
        --formie-neutral-100: #f1f5f9;
        --formie-neutral-200: #e2e8f0;
        --formie-neutral-300: #cbd5e1;
        --formie-neutral-400: #94a3b8;
        /* Lightest slate step that meets ~3:1 non-text contrast on white (WCAG 1.4.11). */
        --formie-neutral-450: #8796ac;
        --formie-neutral-500: #64748b;
        --formie-neutral-600: #475569;
        --formie-neutral-700: #334155;
        --formie-neutral-800: #1e293b;
        --formie-neutral-900: #0f172a;
        --formie-neutral-950: #020617;

        --formie-primary-50: #e8ecfc;
        --formie-primary-100: #d2d9f9;
        --formie-primary-200: #a4b3f4;
        --formie-primary-300: #778dee;
        --formie-primary-400: #4967e9;
        --formie-primary-500: #1c41e3;
        --formie-primary-600: #1634b6;
        --formie-primary-700: #112788;
        --formie-primary-800: #0b1a5b;
        --formie-primary-900: #060d2d;
        --formie-primary-950: #040920;

        --formie-danger-50: #fef2f2;
        --formie-danger-100: #fee2e2;
        --formie-danger-200: #fecaca;
        --formie-danger-300: #fca5a5;
        --formie-danger-400: #f87171;
        --formie-danger-500: #ef4444;
        --formie-danger-600: #dc2626;
        --formie-danger-700: #b91c1c;
        --formie-danger-800: #991b1b;
        --formie-danger-900: #7f1d1d;
        --formie-danger-950: #450a0a;

        --formie-success-50: #f0fdf4;
        --formie-success-100: #dcfce7;
        --formie-success-200: #bbf7d0;
        --formie-success-300: #86efac;
        --formie-success-400: #4ade80;
        --formie-success-500: #22c55e;
        --formie-success-600: #16a34a;
        --formie-success-700: #15803d;
        --formie-success-800: #166534;
        --formie-success-900: #14532d;
        --formie-success-950: #052e16;

        /* Semantic color aliases */
        --formie-color-background: var(--formie-white);
        --formie-color-surface: var(--formie-white);
        --formie-color-surface-subtle: var(--formie-neutral-50);
        --formie-color-surface-muted: var(--formie-neutral-100);
        --formie-color-text: var(--formie-neutral-700);
        --formie-color-text-muted: var(--formie-neutral-600);
        --formie-color-heading: var(--formie-neutral-900);
        /* Structural chrome: tabs, groups, dividers. */
        --formie-color-border: var(--formie-neutral-300);
        /* Interactive controls: inputs, selects, checkboxes. Border-only; use focus ring for interaction contrast. */
        --formie-color-border-control: var(--formie-neutral-400);
        --formie-color-border-soft: var(--formie-neutral-200);
        --formie-color-primary: var(--formie-primary-400);
        --formie-color-primary-hover: var(--formie-primary-500);
        --formie-color-primary-border: var(--formie-primary-500);
        --formie-color-primary-soft: var(--formie-primary-100);
        --formie-color-focus-ring: var(--formie-primary-300);
        --formie-color-danger: var(--formie-danger-600);
        --formie-color-danger-soft: var(--formie-danger-50);
        --formie-color-danger-dark: var(--formie-danger-900);
        --formie-color-success: var(--formie-success-500);
        --formie-color-success-soft: var(--formie-success-50);
        --formie-color-success-dark: var(--formie-success-900);
        --formie-color-button-text: var(--formie-color-surface);

        --formie-focus-ring-border-color: var(--formie-color-focus-ring);
        --formie-shadow-focus: 0 0 0 3px rgba(119, 141, 238, 0.45);
        --formie-shadow-danger-focus: 0 0 0 3px rgba(248, 180, 180, 0.45);

        /* Form */
        --formie-title-form-size: 1.4rem;
        --formie-body-size: 0.9375rem;
        --formie-gap-form: 0;
        --formie-gap-form-header: var(--formie-space-4);
        --formie-gap-form-messages: var(--formie-space-4);
        --formie-gap-form-navigation: var(--formie-space-4);
        --formie-gap-form-body: 0;
        --formie-gap-form-footer: var(--formie-space-4);

        /* Messages */
        --formie-message-padding: var(--formie-space-4);
        --formie-message-margin-bottom: var(--formie-space-4);
        --formie-message-size: var(--formie-font-size-sm);
        --formie-message-line-height: var(--formie-line-height-relaxed);

        /* Buttons */
        --formie-button-border: var(--formie-border-width) solid var(--formie-color-border);
        --formie-button-border-hover: var(--formie-button-secondary-border-hover);
        --formie-button-border-radius: var(--formie-radius-sm);
        --formie-button-background: var(--formie-neutral-100);
        --formie-button-background-hover: var(--formie-neutral-200);
        --formie-button-text-color: var(--formie-color-heading);
        --formie-button-color: var(--formie-button-text-color);
        --formie-button-line-height: var(--formie-line-height-tight);
        --formie-button-font-weight: var(--formie-font-weight-medium);
        --formie-button-min-height: var(--formie-space-10);
        --formie-button-padding-y: var(--formie-space-2);
        --formie-button-padding-x: var(--formie-space-4);
        --formie-button-font-size: var(--formie-font-size-sm);
        --formie-button-gap: var(--formie-space-2);
        --formie-button-icon-size: 0.9375rem;
        --formie-button-icon-button-size: 1.875rem;
        --formie-button-icon-border-radius: var(--formie-radius-full);
        --formie-button-icon-background: var(--formie-neutral-100);
        --formie-button-icon-background-hover: var(--formie-neutral-200);
        --formie-button-icon-border: var(--formie-border-width) solid var(--formie-color-border-control);
        --formie-button-icon-border-hover: var(--formie-border-width) solid var(--formie-neutral-450);
        --formie-button-icon-color: var(--formie-neutral-950);
        --formie-button-opacity-disabled: 0.7;
        --formie-button-shadow-focus: 0 0 0 3px var(--formie-color-border-soft);

        /* Icons */
        --formie-icon-mask-plus: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='%23000' d='M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z'/%3E%3C/svg%3E");
        --formie-icon-mask-arrow-left: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23000' d='M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z'/%3E%3C/svg%3E");
        --formie-icon-mask-arrow-right: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23000' d='M278.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z'/%3E%3C/svg%3E");
        --formie-icon-mask-arrow-up: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='%23000' d='M201.4 137.4c12.5-12.5 32.8-12.5 45.3 0l160 160c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L224 205.3 86.6 342.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l160-160z'/%3E%3C/svg%3E");
        --formie-icon-mask-arrow-down: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='%23000' d='M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z'/%3E%3C/svg%3E");
        --formie-icon-mask-close: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'%3E%3Cpath fill='%23000' d='M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z'/%3E%3C/svg%3E");

        --formie-button-primary-background: var(--formie-color-primary);
        --formie-button-primary-background-hover: var(--formie-color-primary-hover);
        --formie-button-primary-text-color: var(--formie-white);
        --formie-button-primary-border: var(--formie-border-width) solid transparent;
        --formie-button-primary-border-hover: var(--formie-border-width) solid var(--formie-color-primary-hover);
        --formie-button-primary-shadow-focus: 0 0 0 3px var(--formie-primary-300);

        --formie-button-secondary-border: var(--formie-border-width) solid var(--formie-color-border);
        --formie-button-secondary-border-hover: var(--formie-button-secondary-border);
        --formie-button-secondary-background: var(--formie-color-surface);
        --formie-button-secondary-background-hover: var(--formie-neutral-100);
        --formie-button-secondary-text-color: var(--formie-color-heading);

        --formie-button-ghost-border: var(--formie-border-width) solid transparent;
        --formie-button-ghost-border-hover: var(--formie-button-ghost-border);
        --formie-button-ghost-background: transparent;
        --formie-button-ghost-background-hover: var(--formie-neutral-100);
        --formie-button-ghost-text-color: var(--formie-color-heading);
        --formie-button-ghost-shadow-focus: var(--formie-button-shadow-focus);

        --formie-button-link-text-color: var(--formie-color-primary);
        --formie-button-link-text-color-hover: var(--formie-color-primary-hover);

        /* Navigation */
        --formie-tab-padding-y: var(--formie-space-2);
        --formie-tab-padding-x: var(--formie-space-4);
        --formie-tab-font-size: var(--formie-font-size-sm);
        --formie-gap-tabs: var(--formie-space-4);

        /* Progress */
        --formie-progress-height: 1.2rem;
        --formie-progress-padding: var(--formie-space-4);
        --formie-progress-size: 0.8rem;

        /* Loading */
        --formie-loading-size: var(--formie-space-4);
        --formie-loading-margin-top: calc(var(--formie-loading-size) * -0.5);
        --formie-loading-margin-left: calc(var(--formie-loading-size) * -0.5);
        --formie-loading-border-width: 2px;
        --formie-loading-animation: loading 0.5s infinite linear;
        --formie-loading-left: 50%;
        --formie-loading-top: 50%;
        --formie-loading-z-index: 1;

        /* Pages */
        --formie-gap-pages: 0;
        --formie-gap-page: var(--formie-space-4);
        --formie-gap-page-container: 0;
        --formie-gap-page-header: var(--formie-space-4);
        --formie-gap-page-body: var(--formie-space-4);
        --formie-gap-page-footer: var(--formie-space-4);
        --formie-gap-page-buttons: var(--formie-space-4);

        /* Page */
        --formie-title-page-size: var(--formie-font-size-lg);

        /* Rows */
        --formie-gap-rows: var(--formie-space-4);
        --formie-gap-row: var(--formie-space-4);
        --formie-gap-subfield-rows: var(--formie-space-2);
        --formie-gap-subfield-row: var(--formie-space-2);
        --formie-gap-nested-field-rows: var(--formie-space-2);
        --formie-gap-nested-field-row: var(--formie-space-2);
        --formie-subfield-row-column-min-width: 12rem;
        --formie-nested-field-row-column-min-width: 16rem;

        /* Row fields */
        --formie-gap-errors: var(--formie-space-2);
        --formie-gap-field-errors: var(--formie-space-2);

        /* Field */
        --formie-label-size: var(--formie-font-size-sm);
        --formie-meta-size: var(--formie-font-size-sm);
        --formie-control-height: 2.375rem;
        --formie-control-padding-y: var(--formie-space-2);
        --formie-control-padding-x: var(--formie-space-3);
        --formie-control-font-size: var(--formie-font-size-sm);
        --formie-textarea-min-height: 9rem;
        --formie-select-indicator-size: 1.4rem;
        --formie-list-indent: var(--formie-space-5);
        --formie-link-underline-offset: 0.15em;
        --formie-gap-field: var(--formie-space-2);
        --formie-gap-field-layout: var(--formie-space-2);
        --formie-gap-field-content: var(--formie-space-2);
        --formie-gap-field-control: var(--formie-space-2);
        --formie-gap-options: var(--formie-space-2);

        /* Field: summary */
        --formie-summary-padding: var(--formie-space-4);
        --formie-gap-summary: var(--formie-space-4);

        --formie-file-summary-padding: var(--formie-space-4);
        --formie-gap-file-summary: var(--formie-space-3);

        /* Field: rich text */
        --formie-rich-text-min-height: 12rem;

        /* Field: signature */
        --formie-signature-width: 100%;
        --formie-signature-height: 8rem;
        --formie-signature-background: var(--formie-color-surface-subtle);
        --formie-signature-border: 1px solid var(--formie-color-border-control);
        --formie-signature-border-radius: var(--formie-radius-sm);

        --formie-signature-remove-button-top: 0;
        --formie-signature-remove-button-right: -14px;
        --formie-signature-remove-button-transform: translate(0, -50%);

        /* Field: check/radio */
        --formie-check-font-size: var(--formie-font-size-sm);
        --formie-check-line-height: var(--formie-line-height-base);
        --formie-check-margin-bottom: var(--formie-space-2);
        --formie-check-margin-right: var(--formie-space-4);
        --formie-check-background-color: var(--formie-color-surface-muted);
        --formie-check-size: var(--formie-space-4);
        --formie-check-label-padding-left: var(--formie-space-6);
        --formie-check-label-line-height: var(--formie-space-6);
        --formie-check-label-top: 0.3125rem;
        --formie-check-label-transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        --formie-check-label-background-color: var(--formie-color-surface);
        --formie-check-check-border-radius: 2px;
        --formie-check-check-background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3E%3Cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3E%3C/svg%3E");
        --formie-check-check-background-size: 8px auto;
        --formie-check-radio-border-radius: 50%;
        --formie-check-radio-background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%23fff'/%3E%3C/svg%3E");
        --formie-check-radio-background-size: 8px auto;

        /* Field: group */
        --formie-group-border: 1px solid var(--formie-color-border);
        --formie-group-border-radius: var(--formie-radius-sm);
        --formie-group-padding: var(--formie-space-4);

        /* Field: repeater */
        --formie-repeater-add-button-padding-left: var(--formie-space-8);
        --formie-repeater-add-button-icon-mask: var(--formie-icon-mask-plus);
        --formie-repeater-add-button-height: 14px;
        --formie-repeater-add-button-width: 14px;
        --formie-repeater-add-button-left: var(--formie-space-3);

        --formie-repeater-remove-button-top: 0;
        --formie-repeater-remove-button-right: -14px;
        --formie-repeater-remove-button-transform: translate(0, -50%);

        --formie-table-width: 100%;
        --formie-table-margin-bottom: 1rem;
        --formie-table-border-collapse: collapse;

        --formie-table-row-padding: 0.2rem;
        --formie-table-th-text-align: inherit;
        --formie-table-th-font-size: 0.75rem;
        --formie-table-th-font-weight: 600;

        --formie-table-add-button-padding-left: var(--formie-space-8);
        --formie-table-add-button-icon-mask: var(--formie-icon-mask-plus);
        --formie-table-add-button-height: 14px;
        --formie-table-add-button-width: 14px;
        --formie-table-add-button-left: var(--formie-space-3);

        --formie-table-remove-button-top: 0;
        --formie-table-remove-button-right: -14px;
        --formie-table-remove-button-transform: translate(0, -50%);


        /* --formie-table-add-btn-padding-left: 2rem;

        --formie-table-add-btn-top: 0.75rem;
        --formie-table-add-btn-left: 0.75rem;
        --formie-table-add-btn-width: 14px;
        --formie-table-add-btn-height: 14px;
        --formie-table-add-btn-bg-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'%3E%3Cpath fill='currentColor' d='M368 224H224V80c0-8.84-7.16-16-16-16h-32c-8.84 0-16 7.16-16 16v144H16c-8.84 0-16 7.16-16 16v32c0 8.84 7.16 16 16 16h144v144c0 8.84 7.16 16 16 16h32c8.84 0 16-7.16 16-16V288h144c8.84 0 16-7.16 16-16v-32c0-8.84-7.16-16-16-16z'%3E%3C/path%3E%3C/svg%3E"); */

        /* --formie-table-remove-btn-border-radius: 50%;
        --formie-table-remove-btn-padding: 13px;
        --formie-table-remove-btn-text-indent: -9999px;
        --formie-table-remove-btn-top: 50%;
        --formie-table-remove-btn-left: 50%;
        --formie-table-remove-btn-width: 9px;
        --formie-table-remove-btn-height: 14px;
        --formie-table-remove-btn-transform: translate(-50%, -50%);
        --formie-table-remove-btn-bg-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='currentColor' d='M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z'%3E%3C/path%3E%3C/svg%3E"); */

        font-family: var(--formie-font-family);
        font-size: var(--formie-body-size);
        line-height: var(--formie-line-height-base);
        color: var(--formie-color-text);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
}`,ad=`@layer formie-theme {
    .formie-form-title {
        color: var(--formie-color-heading);
        margin: 0 0 var(--formie-space-4);
        font-size: var(--formie-title-form-size);
        font-weight: var(--formie-font-weight-bold);
        letter-spacing: var(--formie-letter-spacing-tight);
    }

    .formie-page-title {
        color: var(--formie-color-heading);
        margin: 0 0 var(--formie-space-4);
        font-size: var(--formie-title-page-size);
        font-weight: var(--formie-font-weight-semibold);
    }

    .formie-label,
    .formie-field-label,
    .formie-field-option-label,
    .formie-summary-label {
        color: var(--formie-color-heading);
        font-size: var(--formie-label-size);
        font-weight: var(--formie-font-weight-medium);
        line-height: var(--formie-line-height-tight);
    }

    label.formie-field-label {
        /* legend should be \`display: contents\` to work with grid */
        /* so only apply this to label elements */
        display: block;
    }

    .formie-form label,
    .formie-form legend {
        color: var(--formie-color-heading);
    }

    .formie-field-has-error .formie-label,
    .formie-field-has-error .formie-field-label,
    .formie-field-has-error .formie-field-option-label,
    .formie-field-has-error .formie-summary-label,
    .formie-field-has-error label,
    .formie-field-has-error legend {
        color: var(--formie-color-danger-dark);
    }

    .formie-instructions {
        color: var(--formie-color-text-muted);
        font-size: var(--formie-meta-size);
        line-height: var(--formie-line-height-relaxed);
        margin-top: calc(var(--formie-space-1) * -1);
    }

    .formie-instructions p {
        margin: 0;
        padding: 0;
    }

    .formie-field-note {
        color: var(--formie-color-text-muted);
        font-size: var(--formie-meta-size);
        line-height: var(--formie-line-height-relaxed);
    }

    .formie-form p,
    .formie-form ul {
        margin-top: 0;
    }

}`,sd=`@layer formie-theme {
    .formie-field-required {
        color: var(--formie-color-danger);
    }

    .formie-errors {
        margin-bottom: var(--formie-space-4);
    }

    .formie-field-error,
    .formie-error {
        display: block;
        color: var(--formie-color-danger);
        font-size: var(--formie-meta-size);
    }

    .formie-message {
        margin-bottom: var(--formie-message-margin-bottom);
        padding: var(--formie-message-padding);
        border-radius: var(--formie-radius-sm);
        font-size: var(--formie-message-size);
        font-weight: var(--formie-font-weight-medium);
        line-height: var(--formie-message-line-height);
    }

    .formie-message-error {
        background: var(--formie-color-danger-soft);
        color: var(--formie-color-danger-dark);
    }

    .formie-message-error .formie-error {
        color: var(--formie-color-danger-dark);
    }

    .formie-message-success {
        background: var(--formie-color-success-soft);
        color: var(--formie-color-success-dark);
    }
}
`,ld=`@layer formie-theme {
    .formie-page-buttons {
        display: grid;
        gap: var(--formie-gap-field);
    }

    .formie-button-container {
        display: flex;
        flex-wrap: wrap;
        gap: var(--formie-gap-field);
        width: 100%;
        align-items: center;
        position: relative;
    }

    .formie-button {
        --formie-loading-color: var(--formie-button-color);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: var(--formie-button-gap);
        min-height: var(--formie-button-min-height);
        flex-shrink: 0;
        border: var(--formie-button-border);
        border-radius: var(--formie-button-border-radius);
        background-color: var(--formie-button-background);
        color: var(--formie-button-color);
        padding: var(--formie-button-padding-y) var(--formie-button-padding-x);
        font-size: var(--formie-button-font-size);
        line-height: var(--formie-button-line-height);
        font-weight: var(--formie-button-font-weight);
        position: relative;
        white-space: nowrap;
        text-decoration: none;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        transition: background-color 150ms ease, border-color 150ms ease, box-shadow 150ms ease, color 150ms ease, opacity 150ms ease;
    }

    .formie-button:hover {
        background-color: var(--formie-button-background-hover);
        border: var(--formie-button-border-hover);
    }

    .formie-button:focus {
        outline: 0;
    }

    .formie-button:focus-visible {
        outline: 0;
        box-shadow: var(--formie-button-shadow-focus);
    }

    .formie-button:disabled {
        opacity: var(--formie-button-opacity-disabled);
        pointer-events: none;
        cursor: not-allowed;
    }

    .formie-button-primary {
        --formie-button-color: var(--formie-button-primary-text-color);
        background-color: var(--formie-button-primary-background);
        border: var(--formie-button-primary-border);
    }

    .formie-button-primary:hover {
        background-color: var(--formie-button-primary-background-hover);
        border: var(--formie-button-primary-border-hover);
    }

    .formie-button-primary:focus-visible {
        box-shadow: var(--formie-button-primary-shadow-focus);
    }

    .formie-button-secondary {
        --formie-button-color: var(--formie-button-secondary-text-color);
        background-color: var(--formie-button-secondary-background);
        border: var(--formie-button-secondary-border);
    }

    .formie-button-secondary:hover {
        background-color: var(--formie-button-secondary-background-hover);
        border: var(--formie-button-secondary-border-hover);
    }

    .formie-button-secondary:focus-visible {
        box-shadow: var(--formie-button-shadow-focus);
    }

    .formie-button-ghost {
        --formie-button-color: var(--formie-button-ghost-text-color);
        background-color: var(--formie-button-ghost-background);
        border: var(--formie-button-ghost-border);
    }

    .formie-button-ghost:hover {
        background-color: var(--formie-button-ghost-background-hover);
        border: var(--formie-button-ghost-border-hover);
    }

    .formie-button-ghost:focus-visible {
        box-shadow: var(--formie-button-ghost-shadow-focus);
    }

    .formie-button-icon {
        --formie-button-color: var(--formie-button-icon-color);
        width: var(--formie-button-icon-button-size);
        min-width: var(--formie-button-icon-button-size);
        height: var(--formie-button-icon-button-size);
        min-height: var(--formie-button-icon-button-size);
        padding: 0;
        border: var(--formie-button-icon-border);
        border-radius: var(--formie-button-icon-border-radius);
        background-color: var(--formie-button-icon-background);
        font-size: 0;
        line-height: 0;
        text-indent: -9999px;
        overflow: hidden;
        white-space: nowrap;
    }

    .formie-button-icon:hover {
        background-color: var(--formie-button-icon-background-hover);
        border: var(--formie-button-icon-border-hover);
    }

    .formie-button-icon::after {
        position: absolute;
        top: 50%;
        left: 50%;
        display: block;
        content: '';
        width: var(--formie-button-icon-size);
        height: var(--formie-button-icon-size);
        transform: translate(-50%, -50%);
        background-color: currentColor;
        -webkit-mask-image: var(--formie-button-icon-mask);
        mask-image: var(--formie-button-icon-mask);
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
        -webkit-mask-size: contain;
        mask-size: contain;
    }

    .formie-button-icon[data-formie-icon="plus"] {
        --formie-button-icon-mask: var(--formie-icon-mask-plus);
    }

    .formie-button-icon[data-formie-icon="arrow-left"] {
        --formie-button-icon-mask: var(--formie-icon-mask-arrow-left);
    }

    .formie-button-icon[data-formie-icon="arrow-right"] {
        --formie-button-icon-mask: var(--formie-icon-mask-arrow-right);
    }

    .formie-button-icon[data-formie-icon="arrow-up"] {
        --formie-button-icon-mask: var(--formie-icon-mask-arrow-up);
    }

    .formie-button-icon[data-formie-icon="arrow-down"] {
        --formie-button-icon-mask: var(--formie-icon-mask-arrow-down);
    }

    .formie-button-icon[data-formie-icon="close"] {
        --formie-button-icon-mask: var(--formie-icon-mask-close);
    }

    .formie-button-text-icon {
        width: var(--formie-button-icon-size);
        height: var(--formie-button-icon-size);
        flex-shrink: 0;
        background-color: currentColor;
        -webkit-mask-image: var(--formie-button-icon-mask);
        mask-image: var(--formie-button-icon-mask);
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
        -webkit-mask-size: contain;
        mask-size: contain;
    }

    .formie-button-text-icon[data-formie-icon="plus"] {
        --formie-button-icon-mask: var(--formie-icon-mask-plus);
    }

    .formie-button-text-icon[data-formie-icon="arrow-left"] {
        --formie-button-icon-mask: var(--formie-icon-mask-arrow-left);
    }

    .formie-button-text-icon[data-formie-icon="arrow-right"] {
        --formie-button-icon-mask: var(--formie-icon-mask-arrow-right);
    }

    .formie-button-text-icon[data-formie-icon="arrow-up"] {
        --formie-button-icon-mask: var(--formie-icon-mask-arrow-up);
    }

    .formie-button-text-icon[data-formie-icon="arrow-down"] {
        --formie-button-icon-mask: var(--formie-icon-mask-arrow-down);
    }

    .formie-button-text-icon[data-formie-icon="close"] {
        --formie-button-icon-mask: var(--formie-icon-mask-close);
    }

    .formie-button-back {
        order: 0;
    }

    .formie-button-submit {
        order: 10;
    }

    .formie-button-save {
        order: 20;
    }

    .formie-page-buttons[data-formie-buttons-position="left"] .formie-button-container {
        justify-content: flex-start;
    }

    .formie-page-buttons[data-formie-buttons-position="right"] .formie-button-container {
        justify-content: flex-end;
    }

    .formie-page-buttons[data-formie-buttons-position="center"] .formie-button-container {
        justify-content: center;
    }

    .formie-page-buttons[data-formie-buttons-position="left-right"] .formie-button-back {
        margin-inline-end: auto;
    }

    .formie-page-buttons[data-formie-buttons-position="save-right"] .formie-button-save {
        margin-inline-start: auto;
    }

    .formie-page-buttons[data-formie-buttons-position="save-left"] .formie-button-save {
        order: -10;
        margin-inline-end: auto;
    }

    .formie-page-buttons[data-formie-buttons-position="right-save-left"] .formie-button-save,
    .formie-page-buttons[data-formie-buttons-position="center-save-left"] .formie-button-save {
        order: -10;
    }

    .formie-page-buttons[data-formie-buttons-position="right-save-left"] .formie-button-container {
        justify-content: flex-end;
    }

    .formie-page-buttons[data-formie-buttons-position="center-save-left"] .formie-button-container,
    .formie-page-buttons[data-formie-buttons-position="center-save-right"] .formie-button-container {
        justify-content: center;
    }

    .formie-button[data-formie-loading="true"] {
        pointer-events: none;
    }

}`,cd=`@layer formie-theme {
    .formie-loading {
        position: relative;
        pointer-events: none;
        color: transparent !important;
    }

    .formie-loading::after {
        position: absolute;
        display: block;
        height: var(--formie-loading-size);
        width: var(--formie-loading-size);
        margin-top: var(--formie-loading-margin-top);
        margin-left: var(--formie-loading-margin-left);
        border-width: var(--formie-loading-border-width);
        border-style: solid;
        border-radius: 9999px;
        border-color: var(--formie-loading-color, var(--formie-color-primary));
        animation: var(--formie-loading-animation);
        border-right-color: transparent;
        border-top-color: transparent;
        content: "";
        left: var(--formie-loading-left);
        top: var(--formie-loading-top);
        z-index: var(--formie-loading-z-index);
    }

    @keyframes loading {
        0% {
            transform: rotate(0)
        }

        100% {
            transform: rotate(360deg)
        }
    }
}`,ud=`@layer formie-theme {
    .formie-progress-wrapper[data-formie-progress-position="start"] {
        padding-bottom: var(--formie-progress-padding);
    }

    .formie-progress-wrapper[data-formie-progress-position="end"] {
        padding-top: var(--formie-progress-padding);
    }

    .formie-progress {
        display: flex;
        align-items: center;
        position: relative;
        background: var(--formie-color-surface-muted);
        border-radius: var(--formie-radius-full);
        min-height: var(--formie-progress-height);
        overflow: hidden;
    }

    .formie-progress-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background: var(--formie-color-primary);
        color: var(--formie-color-button-text);
        font-size: var(--formie-progress-size);
        font-weight: var(--formie-font-weight-medium);
        min-height: var(--formie-progress-height);
        transition: width 0.3s ease;
    }

    .formie-progress-value {
        line-height: 1;
    }

    .formie-progress-bar > .formie-progress-value {
        position: absolute;
        top: 50%;
        right: 0;
        transform: translate(50%, -50%);
        white-space: nowrap;
        padding: 0 var(--formie-space-2);
        min-height: var(--formie-progress-height);
        display: inline-flex;
        align-items: center;
        border-radius: var(--formie-radius-full);
        background: var(--formie-color-primary);
        color: var(--formie-color-button-text);
    }

    .formie-progress-bar[data-formie-progress-state="start"] > .formie-progress-value {
        left: 0;
        right: auto;
        transform: translate(0, -50%);
    }

    .formie-progress-bar[data-formie-progress-state="end"] > .formie-progress-value {
        right: 0;
        transform: translate(0, -50%);
    }
}
`,dd=`@layer formie-theme {
    .formie-form {
        display: grid;
        gap: var(--formie-gap-form);
    }

    .formie-form-header {
        display: grid;
        gap: var(--formie-gap-form-header);
    }

    .formie-form-messages {
        display: grid;
        gap: var(--formie-gap-form-messages);
    }

    .formie-form-messages[data-formie-form-messages-bottom]:not(:empty) {
        padding-top: var(--formie-space-4);
    }

    .formie-form-navigation {
        display: grid;
        gap: var(--formie-gap-form-navigation);
    }

    .formie-form-body {
        display: grid;
        gap: var(--formie-gap-form-body);
    }

    .formie-form-footer {
        display: grid;
        gap: var(--formie-gap-form-footer);
    }

    .formie-pages {
        display: grid;
        gap: var(--formie-gap-pages);
    }

    .formie-page {
        display: grid;
        gap: var(--formie-gap-page);
    }

    .formie-page-container {
        display: grid;
        gap: var(--formie-gap-page-container);
    }

    .formie-page-header {
        display: grid;
        gap: var(--formie-gap-page-header);
    }

    .formie-page-body {
        display: grid;
        gap: var(--formie-gap-page-body);
    }

    .formie-page-footer {
        display: grid;
        gap: var(--formie-gap-page-footer);
    }

    .formie-page-buttons {
        display: grid;
        gap: var(--formie-gap-page-buttons);
    }

    .formie-rows {
        display: grid;
        gap: var(--formie-gap-rows);
    }

    .formie-row {
        display: grid;
        gap: var(--formie-gap-row);
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
    }

    /* Collapse row wrappers once every field inside them is hidden. */
    .formie-row:not(:has(> [data-formie-field]:not([data-formie-conditionally-hidden], [data-formie-page-hidden], [data-formie-row-hidden], [hidden]))),
    .formie-subfield-row:not(:has(> [data-formie-field]:not([data-formie-conditionally-hidden], [data-formie-page-hidden], [data-formie-row-hidden], [hidden]))),
    .formie-nested-field-row:not(:has(> [data-formie-field]:not([data-formie-conditionally-hidden], [data-formie-page-hidden], [data-formie-row-hidden], [hidden]))) {
        display: none;
    }

    @media (min-width: 40rem) {
        .formie-row[data-formie-field-count="2"] {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 56rem) {
        .formie-row[data-formie-field-count="3"] {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .formie-row[data-formie-field-count="4"] {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .formie-row[data-formie-field-count="5"] {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
    }

    .formie-row[data-formie-row-submit-inline] > .formie-row-submit {
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    @media (min-width: 40rem) {
        .formie-row[data-formie-row-submit-inline] {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            gap: var(--formie-gap-row);
        }

        .formie-row[data-formie-row-submit-inline] > [data-formie-field] {
            flex: 1 1 0;
            min-width: 0;
        }

        .formie-row[data-formie-row-submit-inline] > .formie-row-submit {
            flex: 0 0 auto;
        }
    }

    .formie-errors {
        display: grid;
        gap: var(--formie-gap-errors);
    }

    .formie-successes {
        display: grid;
        gap: var(--formie-gap-errors);
    }

    .formie-field-errors {
        display: grid;
        gap: var(--formie-gap-field-errors);
    }

    .formie-field-errors:empty {
        position: absolute;
    }

    .formie-page-tabs {
        display: flex;
        flex-wrap: wrap;
        margin: 0 0 var(--formie-gap-tabs);
        gap: 0;
        border-bottom: var(--formie-border-width) solid var(--formie-color-border);
        font-size: var(--formie-tab-font-size);
    }

    .formie-tab {
        margin-bottom: calc(-1 * var(--formie-border-width));
        color: var(--formie-color-text-muted);
        border: var(--formie-border-width) solid transparent;
    }

    .formie-tab-link {
        display: block;
        padding: var(--formie-tab-padding-y) var(--formie-tab-padding-x);
        color: inherit;
        text-decoration: none;
    }

    .formie-tab-link:hover {
        color: var(--formie-color-heading);
        text-decoration: none;
    }

    .formie-tab-current {
        color: var(--formie-color-heading);
        background: var(--formie-color-surface);
        border-color: var(--formie-color-border);
        border-bottom-color: var(--formie-color-surface);
        border-radius: var(--formie-radius-sm) var(--formie-radius-sm) 0 0;
        font-weight: var(--formie-font-weight-medium);
    }

    .formie-tab-error {
        color: var(--formie-color-danger);
    }

    .formie-tab-error .formie-tab-link:hover,
    .formie-tab-error.formie-tab-current .formie-tab-link,
    .formie-tab-error.formie-tab-current .formie-tab-link:hover {
        color: var(--formie-color-danger);
    }
}
`,fd=`@layer formie-theme {
    .formie-field-options {
        display: flex;
        flex-wrap: wrap;
        gap: var(--formie-gap-options);
    }

    .formie-field {
        display: grid;
        gap: var(--formie-gap-field);
    }

    .formie-field-layout {
        display: grid;
        gap: var(--formie-gap-field-layout);
    }

    .formie-field-layout[data-formie-label-position="left"] {
        grid-template-columns: fit-content(12rem) minmax(0, 1fr);
        align-items: start;
    }

    .formie-field-layout[data-formie-label-position="right"] {
        grid-template-columns: minmax(0, 1fr) fit-content(12rem);
        align-items: start;
    }

    .formie-field-layout[data-formie-label-position="left"] > label.formie-field-label,
    .formie-field-layout[data-formie-label-position="right"] > label.formie-field-label {
        display: block;
        min-inline-size: 0;
        max-inline-size: 100%;
        align-self: center;
    }

    .formie-field-content {
        display: grid;
        gap: var(--formie-gap-field-content);
        min-inline-size: 0;
    }

    .formie-field-control {
        display: grid;
        gap: var(--formie-gap-field-control);
    }

    .formie-layout-horizontal {
        flex-direction: row;
        align-items: flex-start;
    }

    .formie-layout-vertical {
        flex-direction: column;
        align-items: stretch;
    }

    .formie-field-option {
        display: inline-flex;
        align-items: flex-start;
        gap: var(--formie-gap-options);
    }
}`,md=`@layer formie-theme {
    .formie-subfield-rows {
        display: grid;
        gap: var(--formie-gap-subfield-rows);
    }

    .formie-subfield-row {
        display: grid;
        gap: var(--formie-gap-subfield-row);
        grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--formie-subfield-row-column-min-width)), 1fr));
        align-items: start;
    }
}
`,hd=`@layer formie-theme {

    .formie-input,
    .formie-textarea {
        border: var(--formie-border-width) solid var(--formie-color-border-control);
        border-radius: var(--formie-radius-sm);
        background: var(--formie-color-surface);
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    .formie-input-error,
    .formie-field-has-error .formie-input,
    .formie-field-has-error .formie-select,
    .formie-field-has-error .formie-textarea {
        border-color: var(--formie-color-danger);
    }

    .formie-input:focus,
    .formie-textarea:focus {
        outline: 0;
    }

    .formie-input:focus-visible,
    .formie-textarea:focus-visible {
        outline: 0;
        border-color: var(--formie-color-focus-ring);
        box-shadow: var(--formie-shadow-focus);
    }

    .formie-input-error:focus-visible,
    .formie-field-has-error .formie-input:focus-visible,
    .formie-field-has-error .formie-textarea:focus-visible {
        border-color: var(--formie-color-danger);
        box-shadow: var(--formie-shadow-danger-focus);
    }

    /* Fix Safari date/time input inner control height quirks. */
    input::-webkit-datetime-edit {
        display: block;
        padding: 0;
        margin-bottom: -2px;
    }

    /* Fix mobile Safari date/time values appearing vertically shrunk. */
    input::-webkit-date-and-time-value {
        height: 1.5em;
    }
}`,pd=`@layer formie-theme {
    .formie-address-location {
        font-weight: 500;
    }

    .formie-autocomplete-wrapper {
        position: relative;
    }

    .formie-autocomplete-placeholder {
        position: absolute;
        left: 0;
        top: 0;
        pointer-events: none;
        z-index: 1;
    }
}
`,gd=`@layer formie-theme {
    .formie-file-input {
        padding: var(--formie-space-1);
        line-height: var(--formie-line-height-base);
        cursor: pointer;
    }

    .formie-file-input::file-selector-button,
    .formie-file-input::-webkit-file-upload-button {
        appearance: none;
        -webkit-appearance: none;
        margin-inline-end: var(--formie-space-2);
        padding: calc(var(--formie-control-padding-y) - 1px) var(--formie-space-2);
        min-height: calc(var(--formie-control-height) - (var(--formie-space-1) * 2));
        border: var(--formie-border-width) solid var(--formie-color-border-control);
        border-radius: calc(var(--formie-radius-sm) - 1px);
        background: var(--formie-color-surface-subtle);
        color: var(--formie-color-heading);
        font-weight: var(--formie-font-weight-normal);
        font-size: var(--formie-font-size-xs);
        line-height: 1.1;
        white-space: nowrap;
        cursor: pointer;
        transition: border-color 150ms ease, background-color 150ms ease, color 150ms ease, box-shadow 150ms ease;
    }

    .formie-file-input:hover::file-selector-button,
    .formie-file-input:hover::-webkit-file-upload-button {
        border-color: color-mix(in srgb, var(--formie-color-border-control) 70%, var(--formie-color-heading) 30%);
        background: var(--formie-color-surface-muted);
    }

    .formie-file-input:focus {
        outline: 0;
    }

    .formie-file-input:focus-visible::file-selector-button,
    .formie-file-input:focus-visible::-webkit-file-upload-button {
        border-color: var(--formie-color-focus-ring);
    }

    .formie-field-has-error .formie-file-input::file-selector-button,
    .formie-field-has-error .formie-file-input::-webkit-file-upload-button {
        border-color: var(--formie-color-danger);
    }

    .formie-file-summary {
        padding: var(--formie-file-summary-padding);
        gap: var(--formie-gap-file-summary);
        border: var(--formie-border-width) solid var(--formie-color-border-control);
        border-radius: var(--formie-radius-sm);
    }

    .formie-file-summary-container {
        margin: 0;
        padding-left: var(--formie-list-indent);
    }
}`,bd=`@layer formie-theme {

    .formie-checkboxes-options,
    .formie-radio-options,
    .formie-agree-options {
        gap: var(--formie-check-margin-bottom) var(--formie-check-margin-right);
        margin-top: var(--formie-space-1);
    }

    .formie-checkbox-option,
    .formie-radio-option {
        position: relative;
        margin: 0;
        font-size: var(--formie-check-font-size);
        line-height: var(--formie-check-line-height);
    }

    .formie-checkbox-input,
    .formie-radio-input {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        clip-path: inset(50%);
        white-space: nowrap;
        border: 0;
    }

    .formie-checkbox-option-label,
    .formie-radio-option-label {
        position: relative;
        display: inline-block;
        padding-left: var(--formie-check-label-padding-left);
        font-size: var(--formie-check-font-size);
        font-weight: var(--formie-font-weight-normal);
        line-height: var(--formie-check-size);
        user-select: none;
        transition: var(--formie-check-label-transition);
    }

    .formie-checkbox-option-label::before,
    .formie-radio-option-label::before {
        position: absolute;
        top: 0;
        left: 0;
        display: block;
        width: var(--formie-check-size);
        height: var(--formie-check-size);
        content: "";
        cursor: pointer;
        border: var(--formie-border-width) solid var(--formie-color-border-control);
        background-color: var(--formie-check-label-background-color);
        background-repeat: no-repeat;
        background-position: center center;
        background-size: 50% 50%;
        transition: var(--formie-check-label-transition);
    }

    .formie-checkbox-option-label::before {
        border-radius: var(--formie-check-check-border-radius);
    }

    .formie-radio-option-label::before {
        border-radius: var(--formie-check-radio-border-radius);
    }

    .formie-checkbox-input:focus-visible+.formie-checkbox-option-label::before,
    .formie-radio-input:focus-visible+.formie-radio-option-label::before {
        border-color: var(--formie-color-focus-ring);
        box-shadow: var(--formie-shadow-focus);
    }

    .formie-checkbox-input:checked+.formie-checkbox-option-label::before,
    .formie-radio-input:checked+.formie-radio-option-label::before {
        background-color: var(--formie-color-primary);
        border-color: var(--formie-color-primary);
    }

    .formie-checkbox-input:checked+.formie-checkbox-option-label::before {
        background-image: var(--formie-check-check-background-image);
        background-size: var(--formie-check-check-background-size);
    }

    .formie-radio-input:checked+.formie-radio-option-label::before {
        background-image: var(--formie-check-radio-background-image);
        background-size: var(--formie-check-radio-background-size);
    }

    .formie-checkbox-input:disabled+.formie-checkbox-option-label,
    .formie-radio-input:disabled+.formie-radio-option-label {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .formie-checkbox-input:disabled+.formie-checkbox-option-label::before,
    .formie-radio-input:disabled+.formie-radio-option-label::before {
        background-color: var(--formie-check-background-color);
        cursor: not-allowed;
    }

    .formie-field-has-error .formie-checkbox-input:focus-visible+.formie-checkbox-option-label::before,
    .formie-field-has-error .formie-radio-input:focus-visible+.formie-radio-option-label::before {
        box-shadow: var(--formie-shadow-danger-focus);
    }

    .formie-other-option-text {
        display: none;
        flex: 0 0 100%;
        width: 100%;
        max-width: 100%;
        margin-top: var(--formie-space-1);
    }

    .formie-field-option:has(> input[data-formie-other-option]),
    .formie-other-option {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        width: 100%;
    }

    .formie-field-option:has(> input[data-formie-other-option]:checked) > .formie-other-option-text,
    .formie-field-option:has(> input[data-formie-other-option]:checked) > label ~ .formie-other-option-text,
    .formie-other-option:has(> input[data-formie-other-option]:checked) > .formie-other-option-text {
        display: block;
    }

    .formie-layout-horizontal .formie-field-option:has(> input[data-formie-other-option]),
    .formie-layout-horizontal .formie-other-option {
        flex: 1 1 100%;
    }
}`,vd=`@layer formie-theme {
    .formie-group-field-layout>.formie-field-content {
        border: var(--formie-group-border);
        border-radius: var(--formie-group-border-radius);
        padding: var(--formie-group-padding);
    }

    .formie-nested-field-rows {
        display: grid;
        gap: var(--formie-gap-nested-field-rows);
    }

    .formie-nested-field-row {
        display: grid;
        gap: var(--formie-gap-nested-field-row);
        grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--formie-nested-field-row-column-min-width)), 1fr));
        align-items: start;
    }
}`,yd=`@layer formie-theme {
    .formie-repeater-container {
        display: grid;
        gap: var(--formie-space-4);
    }

    .formie-repeater-item-wrapper {
        position: relative;
        display: grid;
        gap: var(--formie-space-4);
        padding: var(--formie-space-4);
        border: var(--formie-border-width) solid var(--formie-color-border-control);
        border-radius: var(--formie-radius-md);
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    .formie-repeater-item-wrapper:focus-within {
        border-color: var(--formie-focus-ring-border-color);
        box-shadow: var(--formie-shadow-focus);
    }

    .formie-field-has-error .formie-repeater-item-wrapper {
        border-color: var(--formie-color-danger);
    }

    .formie-field-has-error .formie-repeater-item-wrapper:focus-within {
        box-shadow: var(--formie-shadow-danger-focus);
    }

    .formie-repeater-item-wrapper>.formie-repeater-remove-button {
        position: absolute;
        top: var(--formie-repeater-remove-button-top);
        right: var(--formie-repeater-remove-button-right);
        transform: var(--formie-repeater-remove-button-transform);
        font-size: 0;
        line-height: 0;
    }

    .formie-button.formie-repeater-add-button {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto;
        max-width: 100%;
        justify-self: start;
        padding-left: var(--formie-repeater-add-button-padding-left);
    }

    .formie-button.formie-repeater-add-button::before {
        position: absolute;
        content: "";
        display: block;
        width: var(--formie-repeater-add-button-width);
        height: var(--formie-repeater-add-button-height);
        left: var(--formie-repeater-add-button-left);
        top: 50%;
        transform: translate(0, -50%);
        background-color: currentColor;
        -webkit-mask-image: var(--formie-repeater-add-button-icon-mask);
        mask-image: var(--formie-repeater-add-button-icon-mask);
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
        -webkit-mask-size: contain;
        mask-size: contain;
    }
}`,wd=`@layer formie-theme {
    .formie-rich-text {
        border: var(--formie-border-width) solid var(--formie-color-border-control);
        border-radius: var(--formie-radius-sm);
        background: var(--formie-color-surface);
        box-sizing: border-box;
        overflow: hidden;
        padding: 0;
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    .formie-rich-text:focus-within {
        border-color: var(--formie-color-focus-ring);
        box-shadow: var(--formie-shadow-focus);
    }

    .formie-field-has-error .formie-rich-text {
        border-color: var(--formie-color-danger);
    }

    .formie-field-has-error .formie-rich-text:focus-within {
        box-shadow: var(--formie-shadow-danger-focus);
    }

    .formie-rich-text-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0;
        padding: var(--formie-space-1);
        border-bottom: var(--formie-border-width) solid var(--formie-color-border);
        background: #fff;
        box-shadow: 0 1px 2px rgba(17, 24, 39, 0.06);
    }

    .formie-rich-text .formie-rich-text-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--formie-space-8);
        height: var(--formie-space-8);
        margin: 0;
        padding: 0;
        border: 0;
        border-radius: var(--formie-radius-sm);
        background: transparent;
        color: var(--formie-color-heading);
        font-size: var(--formie-font-size-sm);
        line-height: 1;
        cursor: pointer;
        box-shadow: none;
        transition: background-color 150ms ease, color 150ms ease, box-shadow 150ms ease;
    }

    .formie-rich-text .formie-rich-text-button:hover,
    .formie-rich-text .formie-rich-text-button.formie-rich-text-selected {
        background: var(--formie-color-surface-muted);
    }

    .formie-rich-text .formie-rich-text-button:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 2px var(--formie-color-surface), 0 0 0 4px color-mix(in srgb, var(--formie-color-focus-ring) 60%, transparent);
    }

    .formie-rich-text [contenteditable="true"] {
        min-height: var(--formie-rich-text-min-height);
        padding: var(--formie-space-3) calc(var(--formie-space-3) + var(--formie-space-1) / 2);
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        outline: 0;
        overflow-wrap: anywhere;
        line-height: var(--formie-line-height-base);
        color: var(--formie-color-text);
    }

    .formie-rich-text [contenteditable="true"]> :first-child {
        margin-top: 0;
    }

    .formie-rich-text [contenteditable="true"]> :last-child {
        margin-bottom: 0;
    }

    .formie-rich-text-content p,
    .formie-rich-text-content ul,
    .formie-rich-text-content ol,
    .formie-rich-text-content blockquote,
    .formie-rich-text-content dl,
    .formie-rich-text-content dd,
    .formie-rich-text-content figure,
    .formie-rich-text-content hr,
    .formie-rich-text-content pre {
        margin: 0 0 var(--formie-space-4);
    }

    .formie-rich-text-content h1,
    .formie-rich-text-content h2,
    .formie-rich-text-content h3,
    .formie-rich-text-content h4,
    .formie-rich-text-content h5,
    .formie-rich-text-content h6 {
        margin: 0 0 var(--formie-space-3);
        color: var(--formie-color-heading);
        font-weight: var(--formie-font-weight-semibold);
        line-height: var(--formie-line-height-tight);
    }

    .formie-rich-text-content h1 {
        font-size: var(--formie-font-size-2xl);
    }

    .formie-rich-text-content h2 {
        font-size: var(--formie-font-size-xl);
    }

    .formie-rich-text-content h3 {
        font-size: var(--formie-font-size-lg);
    }

    .formie-rich-text-content h4 {
        font-size: var(--formie-font-size-base);
    }

    .formie-rich-text-content h5,
    .formie-rich-text-content h6 {
        font-size: var(--formie-font-size-sm);
    }

    .formie-rich-text-content ul,
    .formie-rich-text-content ol {
        padding-inline-start: var(--formie-list-indent);
    }

    .formie-rich-text-content ul {
        list-style: disc;
    }

    .formie-rich-text-content ol {
        list-style: decimal;
    }

    .formie-rich-text-content li+li {
        margin-top: var(--formie-space-1);
    }

    .formie-rich-text-content a {
        color: var(--formie-color-primary);
        text-decoration: underline;
        text-underline-offset: var(--formie-link-underline-offset);
    }

    .formie-rich-text-content blockquote {
        padding-inline-start: var(--formie-space-4);
        color: var(--formie-color-text-muted);
        border-inline-start: 4px solid var(--formie-color-border-soft);
    }

    .formie-rich-text-content pre {
        padding: var(--formie-space-4);
        overflow-x: auto;
        border-radius: var(--formie-radius-md);
        background: var(--formie-color-surface-muted);
    }

    .formie-rich-text-content code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.95em;
    }

    .formie-rich-text-content :not(pre)>code {
        padding: 0.12em 0.35em;
        border-radius: var(--formie-radius-sm);
        background: var(--formie-color-surface-muted);
    }

    .formie-rich-text-content pre code {
        padding: 0;
        border-radius: 0;
        background: transparent;
    }

    .formie-rich-text-content hr {
        height: 0;
        border: 0;
        border-top: var(--formie-border-width) solid var(--formie-color-border);
    }

    .formie-rich-text-content img {
        display: block;
        max-width: 100%;
        height: auto;
    }

    .formie-rich-text-content[data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: var(--formie-color-text-muted);
        pointer-events: none;
    }
}`,xd=`@layer formie-theme {
    .formie-select {
        border: var(--formie-border-width) solid var(--formie-color-border-control);
        border-radius: var(--formie-radius-sm);
        background: var(--formie-color-surface);
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
        appearance: none;
    }

    .formie-select:not([multiple]):not([size]),
    .formie-select[size="1"] {
        padding-right: calc(var(--formie-control-padding-x) * 3);
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M7 7l3-3 3 3m0 6l-3 3-3-3' stroke='%239CA3AF' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-position: right var(--formie-space-2) center;
        background-repeat: no-repeat;
        background-size: var(--formie-select-indicator-size) var(--formie-select-indicator-size);
    }

    .formie-select:focus {
        outline: 0;
    }

    .formie-select:focus-visible {
        outline: 0;
        border-color: var(--formie-color-focus-ring);
        box-shadow: var(--formie-shadow-focus);
    }

    .formie-field-has-error .formie-select {
        border-color: var(--formie-color-danger);
    }

    .formie-field-has-error .formie-select:focus-visible {
        border-color: var(--formie-color-danger);
        box-shadow: var(--formie-shadow-danger-focus);
    }

    /* Tom Select copies native select classes onto its wrapper; keep combobox chrome on .ts-control only. */
    .formie-field .ts-wrapper.formie-combobox,
    .formie-field .ts-wrapper.formie-combobox.formie-select,
    .formie-field .ts-wrapper.formie-combobox.formie-dropdown-input {
        border: 0;
        padding: 0;
        min-height: 0;
        background: none;
        background-image: none;
        box-shadow: none;
        appearance: none;
    }
}`,Ed=`@layer formie-theme {
    [data-formie-field-type="signature"] .formie-field-control {
        position: relative;
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    [data-formie-field-type="signature"] .formie-field-control:focus-within .formie-signature-canvas {
        border-color: var(--formie-focus-ring-border-color);
        box-shadow: var(--formie-shadow-focus);
    }

    .formie-field-has-error[data-formie-field-type="signature"] .formie-signature-canvas {
        border-color: var(--formie-color-danger);
    }

    .formie-field-has-error[data-formie-field-type="signature"] .formie-field-control:focus-within .formie-signature-canvas {
        box-shadow: var(--formie-shadow-danger-focus);
    }

    [data-formie-field-type="signature"] .formie-signature-canvas {
        display: block;
        width: var(--formie-signature-width);
        min-height: var(--formie-signature-height);
        height: auto;
        border: var(--formie-signature-border);
        background: var(--formie-signature-background);
        border-radius: var(--formie-signature-border-radius);
        touch-action: none;
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    [data-formie-field-type="signature"] .formie-signature-remove-button {
        position: absolute;
        top: var(--formie-signature-remove-button-top);
        right: var(--formie-signature-remove-button-right);
        transform: var(--formie-signature-remove-button-transform);
        font-size: 0;
        line-height: 0;
    }

    [data-formie-field-type="signature"] .formie-signature-pad {
        position: relative;
    }

    [data-formie-field-type="signature"] .formie-signature-message {
        margin: 0;
        padding: var(--formie-space-3);
        border: var(--formie-signature-border);
        border-radius: var(--formie-signature-border-radius);
        background: var(--formie-signature-background);
        color: var(--formie-color-text-muted);
        font-size: var(--formie-font-size-sm);
        line-height: var(--formie-line-height-base);
    }

    [data-formie-field-type="signature"].formie-signature-has-message .formie-signature-canvas {
        display: none;
    }
}`,kd=`@layer formie-theme {
    .formie-summary-container {
        padding: var(--formie-summary-padding);
        border: var(--formie-border-width) solid var(--formie-color-border);
        border-radius: var(--formie-radius-sm);
    }

    .formie-summary-heading {
        color: var(--formie-color-heading);
    }

    .formie-summary-blocks {
        display: grid;
        gap: var(--formie-gap-summary);
    }

    .formie-summary-blocks[data-formie-loading="true"] {
        position: relative;
        min-height: calc(var(--formie-loading-size) + var(--formie-space-4));
    }

    .formie-summary-blocks[data-formie-loading="true"] > * {
        opacity: 0;
        pointer-events: none;
    }

    .formie-summary-blocks[data-formie-loading="true"]::before {
        position: absolute;
        inset: 0;
        content: "";
        display: block;
        background: var(--formie-color-bg);
        border-radius: inherit;
        z-index: 1;
    }

    .formie-summary-blocks[data-formie-loading="true"]::after {
        position: absolute;
        top: 50%;
        left: 50%;
        width: var(--formie-loading-size);
        height: var(--formie-loading-size);
        content: "";
        display: block;
        border: var(--formie-loading-border-width) solid var(--formie-loading-color);
        border-top-color: transparent;
        border-right-color: transparent;
        border-radius: var(--formie-radius-full);
        transform: translate(-50%, -50%);
        z-index: 2;
        animation: formie-loading-spin var(--formie-loading-speed) linear infinite;
    }
}`,Sd=`@layer formie-theme {

    .formie-table-wrapper {
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .formie-table {
        width: var(--formie-table-width);
        margin-bottom: var(--formie-table-margin-bottom);
        border-collapse: var(--formie-table-border-collapse);
    }

    .formie-table th {
        text-align: var(--formie-table-th-text-align);
        font-size: var(--formie-table-th-font-size);
        font-weight: var(--formie-table-th-font-weight);
        color: var(--formie-table-th-color, var(--formie-color-text-muted));
    }

    .formie-table th,
    .formie-table td {
        padding: var(--formie-table-row-padding);
        vertical-align: top;
    }

    .formie-table th:first-child,
    .formie-table td:first-child {
        padding-left: 0;
    }

    .formie-table th:last-child,
    .formie-table td:last-child {
        padding-right: 0;
    }

    .formie-table [data-col-remove] {
        width: calc(var(--formie-button-icon-button-size) + (var(--formie-table-row-padding) * 2));
        min-width: calc(var(--formie-button-icon-button-size) + (var(--formie-table-row-padding) * 2));
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
    }

    .formie-table [data-formie-table-column-type="checkbox"] {
        text-align: center;
        vertical-align: middle;
    }

    .formie-table [data-formie-table-column-type="checkbox"] .formie-checkbox-option {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: var(--formie-check-size);
        margin: 0;
    }

    .formie-table [data-formie-table-column-type="checkbox"] .formie-checkbox-option-label {
        display: block;
        width: var(--formie-check-size);
        min-width: var(--formie-check-size);
        height: var(--formie-check-size);
        margin: 0 auto;
        padding-left: 0;
        font-size: 0;
        line-height: 0;
    }

    .formie-table [data-formie-table-column-type="checkbox"] .formie-checkbox-option-label::before {
        position: static;
    }

    .formie-table-color-input {
        min-width: 4rem;
        padding: var(--formie-space-1);
    }

    .formie-table-multiline-input {
        min-height: calc(var(--formie-control-height) + var(--formie-space-2));
    }

    .formie-table-remove-button {
        display: inline-flex;
        vertical-align: middle;
    }

    .formie-button.formie-table-add-button {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto;
        max-width: 100%;
        justify-self: start;
        padding-left: var(--formie-table-add-button-padding-left);
    }

    .formie-button.formie-table-add-button::before {
        position: absolute;
        content: "";
        display: block;
        width: var(--formie-table-add-button-width);
        height: var(--formie-table-add-button-height);
        left: var(--formie-table-add-button-left);
        top: 50%;
        transform: translate(0, -50%);
        background-color: currentColor;
        -webkit-mask-image: var(--formie-table-add-button-icon-mask);
        mask-image: var(--formie-table-add-button-icon-mask);
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
        -webkit-mask-size: contain;
        mask-size: contain;
    }

}`,_d=`@layer formie-theme {
    .formie-limit-number {
        font-weight: var(--formie-font-weight-semibold);
        color: var(--formie-color-text);
    }

    .formie-limit-number-error {
        color: var(--formie-color-danger);
    }
}`,Ad=`@layer formie-theme {
    .formie-sr-only {
        position: absolute !important;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
}
`,Td=`.preview-gallery-page {
    max-width: 980px;
    margin: 0 auto;
    padding: 2rem 1rem 3rem;
    color: #171717;
}

.preview-gallery-stack,
.preview-gallery-flow {
    display: grid;
    gap: 2rem;
}

.preview-gallery-header,
.preview-gallery-section,
.preview-gallery-section-title {
    display: grid;
    gap: 0.5rem;
}

.preview-gallery-header h1,
.preview-gallery-header p,
.preview-gallery-section-title h3,
.preview-gallery-section-title p {
    margin: 0;
}

.preview-gallery-header h1 {
    font-size: clamp(2rem, 4vw, 2.75rem);
    line-height: 1;
    letter-spacing: -0.03em;
    font-weight: 600;
    color: #171717;
}

.preview-gallery-header p,
.preview-gallery-section-title p {
    max-width: 52rem;
    color: #44403c;
    font-size: 1rem;
    line-height: 1.7;
}

.preview-gallery-section {
    gap: 1rem;
}

.preview-gallery-section + .preview-gallery-section {
    padding-top: 2rem;
    border-top: 1px solid #ece7e1;
}

.preview-gallery-section-title h3 {
    margin: 0;
    font-size: 1.45rem;
    line-height: 1.15;
    letter-spacing: -0.02em;
    font-weight: 600;
    color: #171717;
}

.preview-gallery-window {
    padding: 1.75rem;
    border-radius: 0.75rem;
    background: #fff;
    box-shadow: rgba(0, 0, 0, 0.1) 0px 1px 3px 0px;
    border: 1px solid rgba(38, 74, 115, 0.15);
}

.preview-gallery-window > .formie-form,
.preview-gallery-stack-block {
    display: grid;
    gap: 1rem;
}

.preview-gallery-note {
    margin: 0;
    color: #475569;
    font-size: 0.95rem;
}

.preview-gallery-inline-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.9em;
}

.preview-gallery-card {
    display: grid;
    gap: 0.75rem;
}

.preview-gallery-card > * + * {
    margin-top: 0;
}

@media (max-width: 900px) {
    .preview-gallery-page {
        padding: 1.5rem 0 2rem;
    }
}
`,aa=new WeakMap;function Cd(t,e){aa.set(t,{...e}),t.dataset.formieRequestProfile=e.profile??"same-origin-browser"}function Dp(t){return t&&aa.get(t)||{profile:t==null?void 0:t.dataset.formieRequestProfile}}var Id=Object.create,sa=Object.defineProperty,Ld=Object.getOwnPropertyDescriptor,Md=Object.getOwnPropertyNames,Nd=Object.getPrototypeOf,Rd=Object.prototype.hasOwnProperty,Od=(t,e)=>()=>(e||(t((e={exports:{}}).exports,e),t=null),e.exports),Pd=(t,e,r,n)=>{if(e&&typeof e=="object"||typeof e=="function")for(var i=Md(e),a=0,o=i.length,s;a<o;a++)s=i[a],!Rd.call(t,s)&&s!==r&&sa(t,s,{get:(u=>e[u]).bind(null,s),enumerable:!(n=Ld(e,s))||n.enumerable});return t},$d=(t,e,r)=>(r=t==null?{}:Id(Nd(t)),Pd(sa(r,"default",{value:t,enumerable:!0}),t));function zp(t,e,r){let n=e.scope;if(n==="count")return{value:t.length};if(n==="all")return{value:t};if(n==="first")return{value:t[0]??null};if(n==="last")return{value:t[t.length-1]??null};if(n==="current"||n==="index"){let i=n==="current"?r:/^[0-9]+$/.test(e.index??"")?Number(e.index):void 0;return i!==void 0&&i>=0&&i<t.length?{value:t[i]}:{diagnostic:"invalidRowScope"}}if(n==="rows"){let i=e.rows??"";if(!/^(?:even|odd|every:[1-9]\d*|[1-9]\d*(?:\s*-\s*[1-9]\d*)?(?:\s*,\s*[1-9]\d*(?:\s*-\s*[1-9]\d*)?)*)$/.test(i))return{diagnostic:"invalidRowScope"};let a=t.filter((o,s)=>i==="even"?s%2==1:i==="odd"?s%2==0:i.startsWith("every:")?s%Number(i.slice(6))===0:i.split(",").some(u=>{let[f,g=f]=u.split("-").map(Number);return s+1>=Math.min(f,g)&&s+1<=Math.max(f,g)}));return{value:a.length===1?a[0]:a}}return{diagnostic:"missingRowScope"}}var Fd={operators:{"=":["text","number","boolean","date","time","datetime","collection"],"!=":["text","number","boolean","date","time","datetime","collection"],">":["text","number","date","time","datetime"],"<":["text","number","date","time","datetime"],contains:["text","collection"],notContains:["text","collection"],startsWith:["text"],endsWith:["text"],empty:["text","number","boolean","date","time","datetime","collection"],notEmpty:["text","number","boolean","date","time","datetime","collection"]}},hr=t=>({value:null,diagnostics:[{code:t}]}),Pr=t=>typeof t=="number"&&!Number.isFinite(t)?null:t==null?"":["string","number","boolean"].includes(typeof t)?String(t):null,gi=t=>t.replace(/^[ \t\r\n\v\f]+|[ \t\r\n\v\f]+$/g,"");function la(t){if(!["number","string"].includes(typeof t)||!/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/.test(gi(String(t))))return null;let e=Number(t);return Number.isFinite(e)?e:null}function $r(t,e){if(e==="text")return Pr(t);if(e==="number")return la(t);if(e==="boolean"){if(typeof t=="boolean")return t;let o=gi(Pr(t)??"").toLowerCase();return["true","1","yes","on"].includes(o)?!0:!["false","0","no","off"].includes(o)&&null}if(t&&typeof t=="object"&&!Array.isArray(t)){let o=t;if((e==="date"?["year","month","day"]:e==="time"?["hour","minute"]:["year","month","day","hour","minute"]).some(g=>!/^[0-9]+$/.test(String(o[g])))||"_input"in o||o.second!=null&&!/^[0-9]+$/.test(String(o.second)))return null;let s=Number(o.hour??0);if(o.ampm!=null){if(!["AM","PM"].includes(String(o.ampm))||s<1||s>12)return null;s=s%12+(o.ampm==="PM"?12:0)}let u=`${String(o.year??1970).padStart(4,"0")}-${String(o.month??1).padStart(2,"0")}-${String(o.day??1).padStart(2,"0")}`,f=`${String(s).padStart(2,"0")}:${String(o.minute??0).padStart(2,"0")}:${String(o.second??0).padStart(2,"0")}`;if(e==="datetime"){if($r(u,"date")===null||$r(f,"time")===null)return null;let g=String(o.timezone??"UTC");if(/^(Z|[+-][0-9]{2}:[0-9]{2})$/.test(g))return $r(`${u}T${f}${g}`,"datetime");try{let b=Date.parse(`${u}T${f}Z`),p=new Intl.DateTimeFormat("en-GB",{timeZone:g,year:"numeric",month:"2-digit",day:"2-digit",hour:"2-digit",minute:"2-digit",second:"2-digit",hourCycle:"h23"}),y=T=>{let v=Object.fromEntries(p.formatToParts(T).map(k=>[k.type,k.value]));return Date.parse(`${v.year.padStart(4,"0")}-${v.month}-${v.day}T${v.hour}:${v.minute}:${v.second}Z`)},E=new Set([-864e5,0,864e5].map(T=>b-(y(b+T)-(b+T))).filter(T=>y(T)===b));return E.size===1?[...E][0]/1e3:null}catch{return null}}t=e==="date"?u:f}if(typeof t!="string")return null;if(e==="time"){let o=t.match(/^([0-9]{2}):([0-9]{2})(?::([0-9]{2}))?$/);return o&&Number(o[1])<24&&Number(o[2])<60&&Number(o[3]??0)<60?Number(o[1])*3600+Number(o[2])*60+Number(o[3]??0):null}let r=t.match(e==="date"?/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/:/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(Z|[+-][0-9]{2}:[0-9]{2})$/);if(!r||Number(r[1])<1)return null;let n=Number(r[1]),i=[31,n%4==0&&(n%100!=0||n%400==0)?29:28,31,30,31,30,31,31,30,31,30,31];if(Number(r[2])<1||Number(r[2])>12||Number(r[3])<1||Number(r[3])>i[Number(r[2])-1]||e==="datetime"&&(Number(r[4])>23||Number(r[5])>59||Number(r[6])>59||r[7]!=="Z"&&(Number(r[7].slice(1,3))>23||Number(r[7].slice(4))>59)))return null;let a=Date.parse(e==="date"?`${t}T00:00:00Z`:t);return Number.isFinite(a)?a/1e3:null}function ca(t){return t&&typeof t=="object"?Object.values(t).flatMap(ca):[t]}function Vp(t,e,r,n="text"){var s;if(!((s=Fd.operators[t])!=null&&s.includes(n)))return hr("unsupportedOperator");if(t==="empty"||t==="notEmpty"){if(e&&typeof e=="object"&&!Array.isArray(e)&&!["collection","date","time","datetime"].includes(n))return hr("invalidValue");let u=e==null||typeof e=="object"&&!!e&&Object.keys(e).length===0||typeof e=="string"&&gi(e)==="";return{value:t==="empty"?u:!u,diagnostics:[]}}if(n==="collection"){if(e!=null&&typeof e!="object")return hr("invalidValue");let u=ca(e??[]),f=Array.isArray(r)?r:[r];if([...u,...f].some(b=>Pr(b)===null))return hr("invalidValue");let g=u.some(b=>f.some(p=>Pr(b)===Pr(p)));return{value:["!=","notContains"].includes(t)?!g:g,diagnostics:[]}}let i=$r(e,n),a=$r(r,n);if(i===null||a===null)return hr("invalidValue");let o=n==="text"?((u,f)=>{let g=Array.from(u,p=>p.codePointAt(0)),b=Array.from(f,p=>p.codePointAt(0));for(let p=0;p<Math.min(g.length,b.length);p++)if(g[p]!==b[p])return g[p]-b[p];return g.length-b.length})(String(i),String(a)):i===a?0:i>a?1:-1;return{value:{"=":i===a,"!=":i!==a,">":o>0,"<":o<0,contains:String(i).includes(String(a)),notContains:!String(i).includes(String(a)),startsWith:String(i).startsWith(String(a)),endsWith:String(i).endsWith(String(a))}[t],diagnostics:[]}}function Dd(t,e){let r=e.flatMap((n,i)=>n.diagnostics.map(a=>({...a,rule:i})));return["all","any"].includes(t)?r.length||e.some(n=>n.value===null)?{value:null,diagnostics:r}:{value:t==="all"?e.every(n=>n.value===!0):e.some(n=>n.value===!0),diagnostics:[]}:hr("invalidSchema")}function jp(t,e){let r=Dd(t.conditionRule,e.map(i=>typeof i=="boolean"?{value:i,diagnostics:[]}:i)),n=r.value===!0;return{finalResult:n,shouldHide:["show","enable"].includes(t.showRule)?!n:n,evaluation:r}}var zd=new Map;function Vd(t){return t==null||t===!1||typeof t=="string"&&t.trim()===""||Array.isArray(t)&&t.length===0}function xr(t,e,r={}){var o;let n=r.empty??Vd(t),i=r.label??"This field",a=(s,u)=>{var f;return((f=e.messages)==null?void 0:f[s])??e.message??u};if(e.type==="required")return n?a("required",`${i} cannot be blank.`):null;if(n)return null;if(e.type==="email")return typeof t=="string"&&/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)?null:a("email",`${i} is not a valid email address.`);if(e.type==="number"){let s=la(t);return s===null?a("number",`${i} is not a valid number.`):e.min!=null&&s<e.min?a("numberMin",`${i} must be no less than ${e.min}.`):e.max!=null&&s>e.max?a("numberMax",`${i} must be no greater than ${e.max}.`):null}if(e.type==="url"){try{let s=new URL(String(t));if(["http:","https:"].includes(s.protocol))return null}catch{}return a("url",`${i} is not a valid URL.`)}if(e.type==="pattern")try{let s=e.pattern instanceof RegExp?e.pattern:typeof e.pattern=="string"?RegExp(`^(?:${e.pattern})$`):null;return s?(s.lastIndex=0,s.test(String(t))?null:a("pattern",`${i} is not a valid format.`)):null}catch{return null}if(e.type==="match")return r.comparison===void 0||t===r.comparison?null:a("match",`${i} must match ${r.comparisonLabel??"the other field"}.`);if(e.type==="minmaxOptions"&&Array.isArray(t)){if(e.min!=null&&t.length<e.min)return a("minOptions",`Please select at least ${e.min} options.`);if(e.max!=null&&t.length>e.max)return a("maxOptions",`Please select no more than ${e.max} options.`)}return((o=zd.get(e.type))==null?void 0:o(t,e,r))??null}function Fr(t){let e=t;if(!e||e.contractVersion!==2||!["server-rendered","client-rendered","cp-edit"].includes(e.surface)||!Array.isArray(e.entries))throw Error("Unsupported browser module contractVersion. Update Formie and its browser packages together.");let r=new Set;for(let n of e.entries){if(!n||typeof n.key!="string"||!n.key||r.has(n.key)||typeof n.moduleId!="string"||!/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/.test(n.moduleId)||"src"in n||!Array.isArray(n.targets)||n.targets.length===0||typeof n.required!="boolean"||!n.config||typeof n.config!="object"||Array.isArray(n.config)||!["field","captcha","payment","address","core"].includes(n.kind))throw Error("Invalid browser module entry. Check the registered module ID, occurrence key, kind and targets.");for(let i of n.targets){let a=i&&typeof i=="object"?Object.keys(i).length:0;if(!(i&&(i.type==="form"&&a===1||i.type==="field"&&a===2&&typeof i.uid=="string"&&i.uid!==""||i.type==="page"&&a===2&&typeof i.id=="string"&&i.id!==""||i.type==="action"&&a===2&&typeof i.action=="string"&&i.action!==""||i.type==="selector"&&a===2&&typeof i.selector=="string"&&i.selector!=="")))throw Error("Invalid browser module target. Fields require a form-field instance UID.")}r.add(n.key)}}var fo=$d(Od(((t,e)=>{(function(r,n){typeof t=="object"&&e!==void 0?n(t):typeof define=="function"&&define.amd?define(["exports"],n):n((r=typeof globalThis<"u"?globalThis:r||self).ExpressionLanguage={})})(t,function(r){function n(h,c,l){return(c=(function(d){var m=(function(w,x){if(typeof w!="object"||!w)return w;var _=w[Symbol.toPrimitive];if(_!==void 0){var $=_.call(w,x);if(typeof $!="object")return $;throw TypeError("@@toPrimitive must return a primitive value.")}return(x==="string"?String:Number)(w)})(d,"string");return typeof m=="symbol"?m:m+""})(c))in h?Object.defineProperty(h,c,{value:l,enumerable:!0,configurable:!0,writable:!0}):h[c]=l,h}let i=function(h,c){if(h.length===0)return c.length;if(c.length===0)return h.length;let l,d,m=[];for(l=0;l<=c.length;l++)m[l]=[l];for(d=0;d<=h.length;d++)m[0]===void 0&&(m[0]=[]),m[0][d]=d;for(l=1;l<=c.length;l++)for(d=1;d<=h.length;d++)c.charAt(l-1)===h.charAt(d-1)?m[l][d]=m[l-1][d-1]:m[l][d]=Math.min(m[l-1][d-1]+1,Math.min(m[l][d-1]+1,m[l-1][d]+1));return m[c.length]===void 0&&(m[c.length]=[]),m[c.length][h.length]};class a extends Error{constructor(c,l,d,m,w){super(c),this.name="SyntaxError",this.cursor=l,this.expression=d,this.subject=m,this.proposals=w}toString(){let c=`${this.name}: ${this.message} around position ${this.cursor}`;if(this.expression&&(c+=` for expression \`${this.expression}\``),c+=".",this.subject&&this.proposals){let l=9007199254740991,d=null;for(let m of this.proposals){let w=i(this.subject,m);w<l&&(d=m,l=w)}d!==null&&l<3&&(c+=` Did you mean "${d}"?`)}return c}}class o{constructor(c,l){n(this,"next",()=>{if(this.position+=1,this.tokens[this.position]===void 0)throw new a("Unexpected end of expression",this.last.cursor,this.expression)}),n(this,"expect",(d,m,w)=>{let x=this.current;if(!x.test(d,m)){let _="";w&&(_=w+". ");let $="";throw m&&($=` with value "${m}"`),_+=`Unexpected token "${x.type}" of value "${x.value}" ("${d}" expected${$})`,new a(_,x.cursor,this.expression)}this.next()}),n(this,"isEOF",()=>s.EOF_TYPE===this.current.type),n(this,"isEqualTo",d=>{if(d==null||!(d instanceof o)||d.tokens.length!==this.tokens.length)return!1;let m=d.position;d.position=0;let w=!0;for(let x of this.tokens){if(!d.current.isEqualTo(x)){w=!1;break}d.position<d.tokens.length-1&&d.next()}return d.position=m,w}),n(this,"diff",d=>{let m=[];if(!this.isEqualTo(d)){let w=0,x=d.position;d.position=0;for(let _ of this.tokens){let $=_.diff(d.current);$.length>0&&m.push({index:w,diff:$}),d.position<d.tokens.length-1&&d.next(),w++}d.position=x}return m}),this.expression=c,this.position=0,this.tokens=l}get current(){return this.tokens[this.position]}get last(){return this.tokens[this.position-1]}toString(){return this.tokens.join(`
`)}}class s{constructor(c,l,d){n(this,"test",(m,w=null)=>this.type===m&&(w===null||this.value===w)),n(this,"isEqualTo",m=>m!=null&&m instanceof s&&m.value==this.value&&m.type===this.type&&m.cursor===this.cursor),n(this,"diff",m=>{let w=[];return this.isEqualTo(m)||(m.value!==this.value&&w.push(`Value: ${m.value} != ${this.value}`),m.cursor!==this.cursor&&w.push(`Cursor: ${m.cursor} != ${this.cursor}`),m.type!==this.type&&w.push(`Type: ${m.type} != ${this.type}`)),w}),this.value=l,this.type=c,this.cursor=d}toString(){return`${this.cursor} [${this.type}] ${this.value}`}}function u(h){let c=0,l=[],d=[],m=(h=h.replace(/\r|\n|\t|\v|\f/g," ")).length;for(;c<m;){if(h[c]===" "){++c;continue}if(h.substr(c,2)==="/*"){let x=h.indexOf("*/",c+2);if(x===-1){c=m;break}c=x+2;continue}let w=f(h.substr(c));if(w!==null){let x=w.length,_=w.replace(/_/g,"");w=_.indexOf(".")===-1&&_.indexOf("e")===-1&&_.indexOf("E")===-1?parseInt(_,10):parseFloat(_),l.push(new s(s.NUMBER_TYPE,w,c+1)),c+=x}else if("([{".indexOf(h[c])>=0)d.push([h[c],c]),l.push(new s(s.PUNCTUATION_TYPE,h[c],c+1)),++c;else if(")]}".indexOf(h[c])>=0){if(d.length===0)throw new a(`Unexpected "${h[c]}"`,c,h);let[x,_]=d.pop(),$=x.replace("(",")").replace("{","}").replace("[","]");if(h[c]!==$)throw new a(`Unclosed "${x}"`,_,h);l.push(new s(s.PUNCTUATION_TYPE,h[c],c+1)),++c}else{let x=p(h.substr(c));if(x!==null)l.push(new s(s.STRING_TYPE,x.captured,c+1)),c+=x.length;else if(h.substr(c,2)==="\\\\")l.push(new s(s.PUNCTUATION_TYPE,"\\",c+1)),c+=2;else{let _=l.length>0?l[l.length-1]:null;if(_&&_.type===s.PUNCTUATION_TYPE&&(_.value==="."||_.value==="?.")){let $=v(h.substr(c));if($)l.push(new s(s.NAME_TYPE,$,c+1)),c+=$.length;else{let oe=T(h.substr(c));if(oe)l.push(new s(s.OPERATOR_TYPE,oe,c+1)),c+=oe.length;else if(h.substr(c,2)==="?."||h.substr(c,2)==="??")l.push(new s(s.PUNCTUATION_TYPE,h.substr(c,2),c+1)),c+=2;else{if(!(".,?:".indexOf(h[c])>=0))throw new a(`Unexpected character "${h[c]}"`,c,h);l.push(new s(s.PUNCTUATION_TYPE,h[c],c+1)),++c}}}else{let $=T(h.substr(c));if($)l.push(new s(s.OPERATOR_TYPE,$,c+1)),c+=$.length;else if(h.substr(c,2)==="?."||h.substr(c,2)==="??")l.push(new s(s.PUNCTUATION_TYPE,h.substr(c,2),c+1)),c+=2;else if(".,?:".indexOf(h[c])>=0)l.push(new s(s.PUNCTUATION_TYPE,h[c],c+1)),++c;else{let oe=v(h.substr(c));if(!oe)throw new a(`Unexpected character "${h[c]}"`,c,h);l.push(new s(s.NAME_TYPE,oe,c+1)),c+=oe.length}}}}}if(l.push(new s(s.EOF_TYPE,null,c+1)),d.length>0){let[w,x]=d.pop();throw new a(`Unclosed "${w}"`,x,h)}return new o(h,l)}function f(h){let c=null,l=h.match(/^(?:((?:\d(?:_?\d)*)\.(?:\d(?:_?\d)*)|\.(?:\d(?:_?\d)*)|(?:\d(?:_?\d)*))(?:[eE][+-]?\d(?:_?\d)*)?)/);return l&&l.length>0&&(c=l[0]),c}n(s,"EOF_TYPE","end of expression"),n(s,"NAME_TYPE","name"),n(s,"NUMBER_TYPE","number"),n(s,"STRING_TYPE","string"),n(s,"OPERATOR_TYPE","operator"),n(s,"PUNCTUATION_TYPE","punctuation");let g=/^"([^"\\]*(?:\\.[^"\\]*)*)"|'([^'\\]*(?:\\.[^'\\]*)*)'/s;function b(h,c){return c==='"'?h=h.replace(/\\\"/g,'"'):c==="'"&&(h=h.replace(/\\'/g,"'")),h=h.replace(/\\\\/g,"\\")}function p(h){let c=null;if(["'",'"'].indexOf(h.substr(0,1))===-1)return c;let l=g.exec(h);return l!==null&&l.length>0&&(c=l[1]===void 0?{captured:b(l[2],"'")}:{captured:b(l[1],'"')},c.length=l[0].length),c}let y="&&,and,||,or,+,-,**,*,/,%,&,|,^,>>,<<,===,!==,!=,==,<=,>=,<,>,contains,matches,starts with,ends with,not in,in,not,!,xor,~,..".split(","),E=["and","or","matches","contains","starts with","ends with","not in","in","not","xor"];function T(h){let c=null;for(let l of y)if(h.substr(0,l.length)===l){E.indexOf(l)>=0?h.substr(0,l.length+1)===l+" "&&(c=l):c=l;break}return c}function v(h){let c=null,l=h.match(/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/);return l&&l.length>0&&(c=l[0]),c}function k(h){return/boolean|number|string/.test(typeof h)}function C(h,c){var l="",d=[],m=0,w=0,x="",_="",$="",oe="",B="",pe=0,ye=0,De=0,rt=0,Bt=0,kt=[],Rt="",St=/%([\dA-Fa-f]+)/g,Yt=function(at,st){return(at+="").length<st?Array(++st-at.length).join("0")+at:at};for(m=0;m<c.length;m++)if(x=c.charAt(m),_=c.charAt(m+1),x==="\\"&&_&&/\d/.test(_)){if(rt=m+(De=($=c.slice(m+1).match(/^\d+/)[0]).length)+1,c.charAt(rt)+c.charAt(rt+1)===".."){if(pe=$.charCodeAt(0),/\\\d/.test(c.charAt(rt+2)+c.charAt(rt+3)))oe=c.slice(rt+3).match(/^\d+/)[0],m+=1;else{if(!c.charAt(rt+2))throw Error("Range with no end point");oe=c.charAt(rt+2)}if((ye=oe.charCodeAt(0))>pe)for(w=pe;w<=ye;w++)d.push(String.fromCharCode(w));else d.push(".",$,oe);m+=oe.length+2}else B=String.fromCharCode(parseInt($,8)),d.push(B);m+=De}else if(_+c.charAt(m+2)===".."){if(pe=($=x).charCodeAt(0),/\\\d/.test(c.charAt(m+3)+c.charAt(m+4)))oe=c.slice(m+4).match(/^\d+/)[0],m+=1;else{if(!c.charAt(m+3))throw Error("Range with no end point");oe=c.charAt(m+3)}if((ye=oe.charCodeAt(0))>pe)for(w=pe;w<=ye;w++)d.push(String.fromCharCode(w));else d.push(".",$,oe);m+=oe.length+2}else d.push(x);for(m=0;m<h.length;m++)if(x=h.charAt(m),d.indexOf(x)!==-1)if(l+="\\",(Bt=x.charCodeAt(0))<32||Bt>126)switch(x){case`
`:l+="n";break;case"	":l+="t";break;case"\r":l+="r";break;case"\x07":l+="a";break;case"\v":l+="v";break;case"\b":l+="b";break;case"\f":l+="f";break;default:for(Rt=encodeURIComponent(x),(kt=St.exec(Rt))!==null&&(l+=Yt(parseInt(kt[1],16).toString(8),3));(kt=St.exec(Rt))!==null;)l+="\\"+Yt(parseInt(kt[1],16).toString(8),3)}else l+=x;else l+=x;return l}class S{constructor(c={},l={}){n(this,"compile",d=>{for(let m of Object.values(this.nodes))m.compile(d)}),n(this,"evaluate",(d,m)=>{let w=[];for(let x of Object.values(this.nodes))w.push(x.evaluate(d,m));return w}),n(this,"toArray",()=>{throw Error(`Dumping a "${this.name}" instance is not supported yet.`)}),n(this,"dump",()=>{let d="";for(let m of this.toArray())d+=k(m)?m:m.dump();return d}),n(this,"dumpString",d=>`"${C(d,'\0	"\\')}"`),n(this,"isHash",d=>{let m=0;for(let w of Object.keys(d))if(w=parseInt(w),w!==m++)return!0;return!1}),this.name="Node",this.nodes=c,this.attributes=l}toString(){let c=[];for(let d of Object.keys(this.attributes)){let m="null";this.attributes[d]&&(m=this.attributes[d].toString()),c.push(`${d}: '${m}'`)}let l=[this.name+"("+c.join(", ")];if(this.nodes.length>0){for(let d of Object.values(this.nodes)){let m=d.toString().split(`
`);for(let w of m)l.push("    "+w)}l.push(")")}else l[0]+=")";return l.join(`
`)}}class A extends S{constructor(c,l,d){super({left:l,right:d},{operator:c}),n(this,"compile",m=>{let w=this.attributes.operator;if(w!=="matches")if(w!=="contains")if(w!=="starts with")if(w!=="ends with"){if(w==="in"||w==="not in"){let x=w==="not in"?"=== -1":">= 0";m.raw("(function(__l, __r){return __r.indexOf(__l) "+x+";})(").compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")");return}w===".."?m.raw("(function(__s, __e){var __r=[];for(var __i=__s;__i<=__e;__i++){__r.push(__i);}return __r;})(").compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")"):A.functions[w]===void 0?(A.operators[w]!==void 0&&(w=A.operators[w]),m.raw("(").compile(this.nodes.left).raw(" ").raw(w).raw(" ").compile(this.nodes.right).raw(")")):m.raw(`${A.functions[w]}(`).compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")")}else m.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().endsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())");else m.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().startsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())");else m.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().includes(").compile(this.nodes.right).raw(".toString().toLowerCase())");else m.compile(this.nodes.right).raw(".test(").compile(this.nodes.left).raw(")")}),n(this,"evaluate",(m,w)=>{let x=this.attributes.operator,_=this.nodes.left.evaluate(m,w);if(A.functions[x]!==void 0){let oe=this.nodes.right.evaluate(m,w);switch(x){case"not in":return oe.indexOf(_)===-1;case"in":return oe.indexOf(_)>=0;case"..":return(function(B,pe){let ye=[];for(let De=B;De<=pe;De++)ye.push(De);return ye})(_,oe);case"**":return _**+oe}}let $=null;switch(x){case"or":case"||":return _||($=this.nodes.right.evaluate(m,w)),_||$;case"and":case"&&":return _&&($=this.nodes.right.evaluate(m,w)),_&&$;case"xor":return $=this.nodes.right.evaluate(m,w),$&&!_||_&&!$;case"<<":return $=this.nodes.right.evaluate(m,w),_<<$;case">>":return $=this.nodes.right.evaluate(m,w),_>>$}switch($=this.nodes.right.evaluate(m,w),x){case"|":return _|$;case"^":return _^$;case"&":return _&$;case"==":return _==$;case"===":return _===$;case"!=":return _!=$;case"!==":return _!==$;case"<":return _<$;case">":return _>$;case">=":return _>=$;case"<=":return _<=$;case"not in":return $.indexOf(_)===-1;case"in":return $.indexOf(_)>=0;case"+":return _+$;case"-":return _-$;case"~":return _.toString()+$.toString();case"*":return _*$;case"/":return _/$;case"%":return _%$;case"matches":if(_==null)return!1;let oe=$.match(A.regex_expression);return new RegExp(oe[1],oe[2]).test(_);case"contains":return _.toString().toLowerCase().includes($.toString().toLowerCase());case"starts with":return _.toString().toLowerCase().startsWith($.toString().toLowerCase());case"ends with":return _.toString().toLowerCase().endsWith($.toString().toLowerCase())}}),n(this,"toArray",()=>["(",this.nodes.left," "+this.attributes.operator+" ",this.nodes.right,")"]),this.name="BinaryNode"}}n(A,"regex_expression",/\/(.+)\/(.*)/),n(A,"operators",{"~":".",and:"&&",or:"||",xor:"xor","<<":"<<",">>":">>"}),n(A,"functions",{"**":"Math.pow","..":"range",in:"includes","not in":"!includes"});class L extends S{constructor(c,l){super({node:l},{operator:c}),n(this,"compile",d=>{d.raw("(").raw(L.operators[this.attributes.operator]).compile(this.nodes.node).raw(")")}),n(this,"evaluate",(d,m)=>{let w=this.nodes.node.evaluate(d,m);switch(this.attributes.operator){case"not":case"!":return!w;case"-":return-w;case"~":return~w}return w}),n(this,"toArray",()=>["(",this.attributes.operator+" ",this.nodes.node,")"]),this.name="UnaryNode"}}n(L,"operators",{"!":"!",not:"!","+":"+","-":"-","~":"~"});class P extends S{constructor(c,l=!1,d=!1){super({},{value:c}),n(this,"compile",m=>{m.repr(this.attributes.value,this.isIdentifier)}),n(this,"evaluate",(m,w)=>this.attributes.value),n(this,"toArray",()=>{let m=[],w=this.attributes.value;if(this.isIdentifier)m.push(w);else if(w===!0)m.push("true");else if(w===!1)m.push("false");else if(w===null)m.push("null");else if(typeof w=="number")m.push(w);else if(typeof w=="string")m.push(this.dumpString(w));else if(Array.isArray(w)){for(let x of w)m.push(","),m.push(new P(x));m[0]="[",m.push("]")}else if(this.isHash(w)){for(let x of Object.keys(w))m.push(", "),m.push(new P(x)),m.push(": "),m.push(new P(w[x]));m[0]="{",m.push("}")}return m}),this.isIdentifier=l,this.isNullSafe=d,this.name="ConstantNode"}}class N extends S{constructor(c,l,d){super({expr1:c,expr2:l,expr3:d}),n(this,"compile",m=>{m.raw("((").compile(this.nodes.expr1).raw(") ? (").compile(this.nodes.expr2).raw(") : (").compile(this.nodes.expr3).raw("))")}),n(this,"evaluate",(m,w)=>this.nodes.expr1.evaluate(m,w)?this.nodes.expr2.evaluate(m,w):this.nodes.expr3.evaluate(m,w)),n(this,"toArray",()=>["(",this.nodes.expr1," ? ",this.nodes.expr2," : ",this.nodes.expr3,")"]),this.name="ConditionalNode"}}class ie extends S{constructor(c,l){super({fnArguments:l},{name:c}),n(this,"compile",d=>{let m=[];for(let x of Object.values(this.nodes.fnArguments.nodes))m.push(d.subcompile(x));let w=d.getFunction(this.attributes.name);d.raw(w.compiler.apply(null,m))}),n(this,"evaluate",(d,m)=>{let w=[m];for(let x of Object.values(this.nodes.fnArguments.nodes))w.push(x.evaluate(d,m));return d[this.attributes.name].evaluator.apply(null,w)}),n(this,"toArray",()=>{let d=[];d.push(this.attributes.name);for(let m of Object.values(this.nodes.fnArguments.nodes))d.push(", "),d.push(m);return d[1]="(",d.push(")"),d}),this.name="FunctionNode"}}class te extends S{constructor(c){super({},{name:c}),n(this,"compile",l=>{l.raw(this.attributes.name)}),n(this,"evaluate",(l,d)=>d[this.attributes.name]),n(this,"toArray",()=>[this.attributes.name]),this.name="NameNode"}}class ue extends S{constructor(){super(),n(this,"addElement",(c,l=null)=>{l===null?l=new P(++this.index):this.type==="Array"&&(this.type="Object"),this.nodes[(++this.keyIndex).toString()]=l,this.nodes[(++this.keyIndex).toString()]=c}),n(this,"compile",c=>{this.type==="Object"?c.raw("{"):c.raw("["),this.compileArguments(c,this.type!=="Array"),this.type==="Object"?c.raw("}"):c.raw("]")}),n(this,"evaluate",(c,l)=>{let d;if(this.type==="Array"){d=[];for(let m of this.getKeyValuePairs())d.push(m.value.evaluate(c,l))}else{d={};for(let m of this.getKeyValuePairs())d[m.key.evaluate(c,l)]=m.value.evaluate(c,l)}return d}),n(this,"toArray",()=>{let c={};for(let d of this.getKeyValuePairs())c[d.key.attributes.value]=d.value;let l=[];if(this.isHash(c)){for(let d of Object.keys(c))l.push(", "),l.push(new P(d)),l.push(": "),l.push(c[d]);l[0]="{",l.push("}")}else{for(let d of Object.values(c))l.push(", "),l.push(d);l[0]="[",l.push("]")}return l}),n(this,"getKeyValuePairs",()=>{let c,l,d,m=[],w=Object.values(this.nodes);for(c=0,l=w.length;c<l;c+=2)d=w.slice(c,c+2),m.push({key:d[0],value:d[1]});return m}),n(this,"compileArguments",(c,l=!0)=>{let d=!0;for(let m of this.getKeyValuePairs())d||c.raw(", "),d=!1,l&&c.compile(m.key).raw(": "),c.compile(m.value)}),this.name="ArrayNode",this.type="Array",this.index=-1,this.keyIndex=-1}}class V extends ue{constructor(){super(),n(this,"compile",c=>{this.compileArguments(c,!1)}),n(this,"toArray",()=>{let c=[];for(let l of this.getKeyValuePairs())c.push(l.value),c.push(", ");return c.pop(),c}),this.name="ArgumentsNode"}}class K extends S{constructor(c,l,d,m){super({node:c,attribute:l,fnArguments:d},{type:m,is_null_coalesce:!1,is_short_circuited:!1}),n(this,"compile",w=>{let x=this.nodes.attribute instanceof P&&this.nodes.attribute.isNullSafe;switch(this.attributes.type){case K.PROPERTY_CALL:w.compile(this.nodes.node).raw(x?"?.":".").raw(this.nodes.attribute.attributes.value);break;case K.METHOD_CALL:w.compile(this.nodes.node).raw(x?"?.":".").raw(this.nodes.attribute.attributes.value).raw("(").compile(this.nodes.fnArguments).raw(")");break;case K.ARRAY_CALL:w.compile(this.nodes.node).raw("[").compile(this.nodes.attribute).raw("]")}}),n(this,"evaluate",(w,x)=>{switch(this.attributes.type){case K.PROPERTY_CALL:let _=this.nodes.node.evaluate(w,x);if(_===null&&(this.nodes.attribute.isNullSafe||this.attributes.is_null_coalesce))return this.attributes.is_short_circuited=!0,null;if(_===null&&this.isShortCircuited())return null;let $=this.nodes.attribute.attributes.value;if(typeof _!="object")throw Error(`Unable to get property "${$}" on a non-object: `+typeof _);return this.attributes.is_null_coalesce?_[$]??null:_[$];case K.METHOD_CALL:let oe=this.nodes.node.evaluate(w,x);if(oe===null&&this.nodes.attribute.isNullSafe)return this.attributes.is_short_circuited=!0,null;if(oe===null&&this.isShortCircuited())return null;let B=this.nodes.attribute.attributes.value;if(typeof oe!="object")throw Error(`Unable to call method "${B}" on a non-object: `+typeof oe);if(oe[B]===void 0)throw Error(`Method "${B}" is undefined on object.`);if(typeof oe[B]!="function")throw Error(`Method "${B}" is not a function on object.`);let pe=this.nodes.fnArguments.evaluate(w,x);return oe[B].apply(null,pe);case K.ARRAY_CALL:let ye=this.nodes.node.evaluate(w,x);if(ye===null&&this.isShortCircuited())return null;if(!(Array.isArray(ye)||typeof ye=="object"||ye===null&&this.attributes.is_null_coalesce))throw Error("Unable to get an item on a non-array: "+typeof ye);return this.attributes.is_null_coalesce?ye?ye[this.nodes.attribute.evaluate(w,x)]??null:null:ye[this.nodes.attribute.evaluate(w,x)]}}),n(this,"toArray",()=>{let w=this.nodes.attribute instanceof P&&this.nodes.attribute.isNullSafe;switch(this.attributes.type){case K.PROPERTY_CALL:return[this.nodes.node,w?"?.":".",this.nodes.attribute];case K.METHOD_CALL:return[this.nodes.node,w?"?.":".",this.nodes.attribute,"(",this.nodes.fnArguments,")"];case K.ARRAY_CALL:return[this.nodes.node,"[",this.nodes.attribute,"]"]}}),this.name="GetAttrNode"}isShortCircuited(){return this.attributes.is_short_circuited||this.nodes.node instanceof K&&this.nodes.node.isShortCircuited()}}n(K,"PROPERTY_CALL",1),n(K,"METHOD_CALL",2),n(K,"ARRAY_CALL",3);class Y extends S{constructor(c,l){super({expr1:c,expr2:l}),n(this,"compile",d=>{d.raw("((").compile(this.nodes.expr1).raw(") ?? (").compile(this.nodes.expr2).raw("))")}),n(this,"evaluate",(d,m)=>(this.nodes.expr1 instanceof K&&this._addNullCoalesceAttributeToGetAttrNodes(this.nodes.expr1),this.nodes.expr1.evaluate(d,m)??this.nodes.expr2.evaluate(d,m))),n(this,"toArray",()=>["(",this.nodes.expr1,") ?? (",this.nodes.expr2,")"]),n(this,"_addNullCoalesceAttributeToGetAttrNodes",d=>{if(d instanceof K){d.attributes.is_null_coalesce=!0;for(let m of Object.values(d.nodes))this._addNullCoalesceAttributeToGetAttrNodes(m)}}),this.name="NullCoalesceNode"}}class I extends S{constructor(c){super({},{name:c}),n(this,"compile",l=>{l.raw(this.attributes.name+" ?? null")}),n(this,"evaluate",(l,d)=>null),n(this,"toArray",()=>[this.attributes.name+" ?? null"]),this.name="NullCoalescedNameNode"}}class R{constructor(c={}){n(this,"functions",{}),n(this,"unaryOperators",{not:{precedence:50},"!":{precedence:50},"-":{precedence:500},"+":{precedence:500},"~":{precedence:500}}),n(this,"binaryOperators",{or:{precedence:10,associativity:1},"||":{precedence:10,associativity:1},xor:{precedence:12,associativity:1},and:{precedence:15,associativity:1},"&&":{precedence:15,associativity:1},"|":{precedence:16,associativity:1},"^":{precedence:17,associativity:1},"&":{precedence:18,associativity:1},"==":{precedence:20,associativity:1},"===":{precedence:20,associativity:1},"!=":{precedence:20,associativity:1},"!==":{precedence:20,associativity:1},"<":{precedence:20,associativity:1},">":{precedence:20,associativity:1},">=":{precedence:20,associativity:1},"<=":{precedence:20,associativity:1},"not in":{precedence:20,associativity:1},in:{precedence:20,associativity:1},matches:{precedence:20,associativity:1},contains:{precedence:20,associativity:1},"starts with":{precedence:20,associativity:1},"ends with":{precedence:20,associativity:1},"..":{precedence:25,associativity:1},"<<":{precedence:25,associativity:1},">>":{precedence:25,associativity:1},"+":{precedence:30,associativity:1},"-":{precedence:30,associativity:1},"~":{precedence:40,associativity:1},"*":{precedence:60,associativity:1},"/":{precedence:60,associativity:1},"%":{precedence:60,associativity:1},"**":{precedence:200,associativity:2}}),n(this,"parse",(l,d=[],m=0)=>{this.tokenStream=l,this.names=d,this.objectMatches={},this.cachedNames=null,this.nestedExecutions=0,this.flags=m;let w=this.parseExpression();if(!this.tokenStream.isEOF())throw new a(`Unexpected token "${this.tokenStream.current.type}" of value "${this.tokenStream.current.value}"`,this.tokenStream.current.cursor,this.tokenStream.expression);return w}),n(this,"lint",(l,d=[],m=0)=>{d===null&&(console.log('Deprecated: passing "null" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument'),m|=1,d=[]),this.parse(l,d,m)}),n(this,"parseExpression",(l=0)=>{let d=this.getPrimary(),m=this.tokenStream.current;if(this.nestedExecutions++,this.nestedExecutions>1e3)throw Error("Too many executions on '"+m.toString()+"' of '"+this.tokenStream.toString()+"'");for(;m.test(s.OPERATOR_TYPE)&&this.binaryOperators[m.value]!==void 0&&this.binaryOperators[m.value]!==null&&this.binaryOperators[m.value].precedence>=l;){let w=this.binaryOperators[m.value];this.tokenStream.next();let x=this.parseExpression(w.associativity===1?w.precedence+1:w.precedence);d=new A(m.value,d,x),m=this.tokenStream.current}return l===0?this.parseConditionalExpression(d):d}),n(this,"getPrimary",()=>{let l=this.tokenStream.current;if(l.test(s.OPERATOR_TYPE)&&this.unaryOperators[l.value]!==void 0&&this.unaryOperators[l.value]!==null){let d=this.unaryOperators[l.value];this.tokenStream.next();let m=this.parseExpression(d.precedence);return this.parsePostfixExpression(new L(l.value,m))}if(l.test(s.PUNCTUATION_TYPE,"(")){this.tokenStream.next();let d=this.parseExpression();return this.tokenStream.expect(s.PUNCTUATION_TYPE,")","An opened parenthesis is not properly closed"),this.parsePostfixExpression(d)}return this.parsePrimaryExpression()}),n(this,"hasVariable",l=>this.getNames().indexOf(l)>=0),n(this,"getNames",()=>{if(this.cachedNames!==null)return this.cachedNames;if(this.names&&this.names.length>0){let l=[],d=0;this.objectMatches={};for(let m of this.names)typeof m=="object"?(this.objectMatches[Object.values(m)[0]]=d,l.push(Object.keys(m)[0]),l.push(Object.values(m)[0])):l.push(m),d++;return this.cachedNames=l,l}return[]}),n(this,"parseArrayExpression",()=>{this.tokenStream.expect(s.PUNCTUATION_TYPE,"[","An array element was expected");let l=new ue,d=!0;for(;!this.tokenStream.current.test(s.PUNCTUATION_TYPE,"]")&&(d||(this.tokenStream.expect(s.PUNCTUATION_TYPE,",","An array element must be followed by a comma"),!this.tokenStream.current.test(s.PUNCTUATION_TYPE,"]")));)d=!1,l.addElement(this.parseExpression());return this.tokenStream.expect(s.PUNCTUATION_TYPE,"]","An opened array is not properly closed"),l}),n(this,"parseHashExpression",()=>{this.tokenStream.expect(s.PUNCTUATION_TYPE,"{","A hash element was expected");let l=new ue,d=!0;for(;!this.tokenStream.current.test(s.PUNCTUATION_TYPE,"}")&&(d||(this.tokenStream.expect(s.PUNCTUATION_TYPE,",","A hash value must be followed by a comma"),!this.tokenStream.current.test(s.PUNCTUATION_TYPE,"}")));){d=!1;let m=null;if(this.tokenStream.current.test(s.STRING_TYPE)||this.tokenStream.current.test(s.NAME_TYPE)||this.tokenStream.current.test(s.NUMBER_TYPE))m=new P(this.tokenStream.current.value),this.tokenStream.next();else{if(!this.tokenStream.current.test(s.PUNCTUATION_TYPE,"(")){let x=this.tokenStream.current;throw new a(`A hash key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "${x.type}" of value "${x.value}"`,x.cursor,this.tokenStream.expression)}m=this.parseExpression()}this.tokenStream.expect(s.PUNCTUATION_TYPE,":","A hash key must be followed by a colon (:)");let w=this.parseExpression();l.addElement(w,m)}return this.tokenStream.expect(s.PUNCTUATION_TYPE,"}","An opened hash is not properly closed"),l}),n(this,"parsePostfixExpression",l=>{let d=this.tokenStream.current;for(;s.PUNCTUATION_TYPE===d.type;){if(d.value==="."||d.value==="?."){let m=d.value==="?.";if(this.tokenStream.next(),d=this.tokenStream.current,this.tokenStream.next(),s.NAME_TYPE!==d.type&&(s.OPERATOR_TYPE!==d.type||!/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/.test(d.value)))throw new a("Expected name",d.cursor,this.tokenStream.expression);let w=new P(d.value,!0,m),x=new V,_=null;if(this.tokenStream.current.test(s.PUNCTUATION_TYPE,"(")){_=K.METHOD_CALL;for(let $ of Object.values(this.parseArguments().nodes))x.addElement($)}else _=K.PROPERTY_CALL;l=new K(l,w,x,_)}else{if(d.value!=="[")break;{this.tokenStream.next();let m=this.parseExpression();this.tokenStream.expect(s.PUNCTUATION_TYPE,"]"),l=new K(l,m,new V,K.ARRAY_CALL)}}d=this.tokenStream.current}return l}),n(this,"parseArguments",()=>{let l=[];for(this.tokenStream.expect(s.PUNCTUATION_TYPE,"(","A list of arguments must begin with an opening parenthesis");!this.tokenStream.current.test(s.PUNCTUATION_TYPE,")");)l.length!==0&&this.tokenStream.expect(s.PUNCTUATION_TYPE,",","Arguments must be separated by a comma"),l.push(this.parseExpression());return this.tokenStream.expect(s.PUNCTUATION_TYPE,")","A list of arguments must be closed by a parenthesis"),new S(l)}),this.functions=c,this.tokenStream=null,this.names=null,this.objectMatches={},this.cachedNames=null,this.nestedExecutions=0,this.flags=0}parseConditionalExpression(c){var l;for(;this.tokenStream.current.test(s.PUNCTUATION_TYPE,"??");){this.tokenStream.next();let d=this.parseExpression();c=new Y(c,d)}for(;this.tokenStream.current.test(s.PUNCTUATION_TYPE,"?");){let d,m;this.tokenStream.next(),this.tokenStream.current.test(s.PUNCTUATION_TYPE,":")?(this.tokenStream.next(),d=c,m=this.parseExpression()):(d=this.parseExpression(),this.tokenStream.current.test(s.PUNCTUATION_TYPE,":")?(this.tokenStream.next(),m=this.parseExpression()):d instanceof P&&typeof((l=d.attributes)==null?void 0:l.value)=="string"?m=new P(""):d instanceof N?(m=d.nodes.expr3,d=d.nodes.expr2):(m=d,d=c)),c=new N(c,d,m)}return c}parsePrimaryExpression(){let c=this.tokenStream.current,l=null;switch(c.type){case s.NAME_TYPE:switch(this.tokenStream.next(),c.value){case"true":case"TRUE":return new P(!0);case"false":case"FALSE":return new P(!1);case"null":case"NULL":return new P(null);default:if(this.tokenStream.current.value==="("){if(this.functions[c.value]===void 0&&!(2&this.flags))throw new a(`The function "${c.value}" does not exist`,c.cursor,this.tokenStream.expression,c.values,Object.keys(this.functions));l=new ie(c.value,this.parseArguments())}else{let d=null;if(1&this.flags)d=c.value;else{if(!this.hasVariable(c.value)){if(this.tokenStream.current.test(s.PUNCTUATION_TYPE,"??"))return new I(c.value);throw new a(`Variable "${c.value}" is not valid`,c.cursor,this.tokenStream.expression,c.value,this.getNames())}d=c.value,this.objectMatches[d]!==void 0&&(d=this.getNames()[this.objectMatches[d]])}l=new te(d)}}break;case s.NUMBER_TYPE:case s.STRING_TYPE:return this.tokenStream.next(),new P(c.value);default:if(c.test(s.PUNCTUATION_TYPE,"["))l=this.parseArrayExpression();else{if(!c.test(s.PUNCTUATION_TYPE,"{"))throw new a(`Unexpected token "${c.type}" of value "${c.value}"`,c.cursor,this.tokenStream.expression);l=this.parseHashExpression()}}return this.parsePostfixExpression(l)}}class U{constructor(c){n(this,"getFunction",l=>this.functions[l]),n(this,"getSource",()=>this.source),n(this,"reset",()=>(this.source="",this)),n(this,"compile",l=>(l.compile(this),this)),n(this,"subcompile",l=>{let d=this.source;this.source="",l.compile(this);let m=this.source;return this.source=d,m}),n(this,"raw",l=>(this.source+=l,this)),n(this,"string",l=>(this.source+='"'+C(l,'\0	"$\\')+'"',this)),n(this,"repr",(l,d=!1)=>{if(d)this.raw(l);else if(Number.isInteger(l)||+l===l&&(!isFinite(l)||l%1))this.raw(l);else if(l===null)this.raw("null");else if(typeof l=="boolean")this.raw(l?"true":"false");else if(Array.isArray(l)){this.raw("[");let m=!0;for(let w of l)m||this.raw(", "),m=!1,this.repr(w);this.raw("]")}else if(typeof l=="object"){this.raw("{");let m=!0;for(let w of Object.keys(l))m||this.raw(", "),m=!1,this.repr(w),this.raw(":"),this.repr(l[w]);this.raw("}")}else this.string(l);return this}),this.source="",this.functions=c}}class J{constructor(c){this.expression=c}toString(){return this.expression}}class W extends J{constructor(c,l){super(c),n(this,"getNodes",()=>this.nodes),this.nodes=l}static fromJSON(c){let l=typeof c=="string"?JSON.parse(c):c,d=x=>{var _,$,oe,B,pe,ye,De,rt,Bt,kt,Rt,St,Yt,at,st,Tr,sr,Wt,Ne;if(x==null||x instanceof S||typeof x!="object"||!x.name)return x;switch(x.name){case"ConstantNode":return new P((_=x.attributes)==null?void 0:_.value,!!x.isIdentifier,!!x.isNullSafe);case"NameNode":return new te(($=x.attributes)==null?void 0:$.name);case"NullCoalescedNameNode":return new I((oe=x.attributes)==null?void 0:oe.name);case"UnaryNode":return new L((B=x.attributes)==null?void 0:B.operator,d((pe=x.nodes)==null?void 0:pe.node));case"BinaryNode":return new A((ye=x.attributes)==null?void 0:ye.operator,d((De=x.nodes)==null?void 0:De.left),d((rt=x.nodes)==null?void 0:rt.right));case"ConditionalNode":return new N(d((Bt=x.nodes)==null?void 0:Bt.expr1),d((kt=x.nodes)==null?void 0:kt.expr2),d((Rt=x.nodes)==null?void 0:Rt.expr3));case"NullCoalesceNode":return new Y(d((St=x.nodes)==null?void 0:St.expr1),d((Yt=x.nodes)==null?void 0:Yt.expr2));case"ArgumentsNode":{let ae=new V;typeof x.type=="string"&&(ae.type=x.type),typeof x.index=="number"&&(ae.index=x.index),typeof x.keyIndex=="number"&&(ae.keyIndex=x.keyIndex),ae.nodes={};for(let Ke of Object.keys(x.nodes||{}))ae.nodes[Ke]=d(x.nodes[Ke]);return ae}case"ArrayNode":{let ae=new ue;typeof x.type=="string"&&(ae.type=x.type),typeof x.index=="number"&&(ae.index=x.index),typeof x.keyIndex=="number"&&(ae.keyIndex=x.keyIndex),ae.nodes={};for(let Ke of Object.keys(x.nodes||{}))ae.nodes[Ke]=d(x.nodes[Ke]);return ae}case"FunctionNode":{let ae=d((at=x.nodes)==null?void 0:at.fnArguments);return new ie((st=x.attributes)==null?void 0:st.name,ae)}case"GetAttrNode":{let ae=new K(d((Tr=x.nodes)==null?void 0:Tr.node),d((sr=x.nodes)==null?void 0:sr.attribute),d((Wt=x.nodes)==null?void 0:Wt.fnArguments),(Ne=x.attributes)==null?void 0:Ne.type);return x.attributes&&typeof x.attributes.is_null_coalesce=="boolean"&&(ae.attributes.is_null_coalesce=x.attributes.is_null_coalesce),x.attributes&&typeof x.attributes.is_short_circuited=="boolean"&&(ae.attributes.is_short_circuited=x.attributes.is_short_circuited),ae}case"Node":{let ae=new S;if(Array.isArray(x.nodes))ae.nodes=x.nodes.map(d);else{ae.nodes={};for(let Ke of Object.keys(x.nodes||{}))ae.nodes[Ke]=d(x.nodes[Ke])}return ae.attributes=x.attributes||{},ae}default:{let ae=new S;if(ae.name=x.name,Array.isArray(x.nodes))ae.nodes=x.nodes.map(d);else{ae.nodes={};for(let Ke of Object.keys(x.nodes||{}))ae.nodes[Ke]=d(x.nodes[Ke])}return ae.attributes=x.attributes||{},ae}}},m=l.expression,w=(x=>{if(x==null)return x;if(x.name)return d(x);if(Array.isArray(x))return x.map(d);if(typeof x=="object"){let _={};for(let $ of Object.keys(x))_[$]=d(x[$]);return _}return x})(l.nodes);return new W(m,w)}}var me;class se{constructor(c=0){n(this,"createCacheItem",(l,d,m)=>{let w=new D;return w.key=l,w.value=d,w.isHit=m,w.defaultLifetime=this.defaultLifetime,w}),n(this,"get",(l,d,m=null,w=null)=>{let x=this.getItem(l);return x.isHit||this.save(x.set(d(x,!0))),x.get()}),n(this,"getItem",l=>{let d=this.hasItem(l),m=null;return d?m=this.values[l]:this.values[l]=null,(0,this.createCacheItem)(l,m,d)}),n(this,"getItems",l=>{for(let d of l)typeof d=="string"||this.expiries[d]||D.validateKey(d);return this.generateItems(l,new Date().getTime()/1e3,this.createCacheItem)}),n(this,"deleteItems",l=>{for(let d of l)this.deleteItem(d);return!0}),n(this,"save",l=>l instanceof D&&(l.expiry!==null&&l.expiry<=new Date().getTime()/1e3?(this.deleteItem(l.key),!0):(l.expiry===null&&0<l.defaultLifetime&&(l.expiry=new Date().getTime()/1e3+l.defaultLifetime),this.values[l.key]=l.value,this.expiries[l.key]=l.expiry||2**53-1,!0))),n(this,"saveDeferred",l=>this.save(l)),n(this,"commit",()=>!0),n(this,"delete",l=>this.deleteItem(l)),n(this,"getValues",()=>this.values),n(this,"hasItem",l=>!!(typeof l=="string"&&this.expiries[l]&&this.expiries[l]>new Date().getTime()/1e3)||(D.validateKey(l),!!this.expiries[l]&&!this.deleteItem(l))),n(this,"clear",()=>(this.values={},this.expiries={},!0)),n(this,"deleteItem",l=>(typeof l=="string"&&this.expiries[l]||D.validateKey(l),delete this.values[l],delete this.expiries[l],!0)),n(this,"reset",()=>{this.clear()}),n(this,"generateItems",(l,d,m)=>{let w=[];for(let x of l){let _=null,$=!!this.expiries[x];$||!(this.expiries[x]>d)&&this.deleteItem(x)?_=this.values[x]:this.values[x]=null,w[x]=m(x,_,$)}return w}),this.defaultLifetime=c,this.values={},this.expiries={}}}class D{constructor(){n(this,"getKey",()=>this.key),n(this,"get",()=>this.value),n(this,"set",c=>(this.value=c,this)),n(this,"expiresAt",c=>{if(c===null)this.expiry=this.defaultLifetime>0?Date.now()/1e3+this.defaultLifetime:null;else{if(!(c instanceof Date))throw Error(`Expiration date must be instance of Date or be null, "${c.name}" given`);this.expiry=c.getTime()/1e3}return this}),n(this,"expiresAfter",c=>{if(c===null)this.expiry=this.defaultLifetime>0?Date.now()/1e3+this.defaultLifetime:null;else{if(!Number.isInteger(c))throw Error(`Expiration date must be an integer or be null, "${c.name}" given`);this.expiry=new Date().getTime()/1e3+c}return this}),n(this,"tag",c=>{if(!this.isTaggable)throw Error(`Cache item "${this.key}" comes from a non tag-aware pool: you cannot tag it.`);Array.isArray(c)||(c=[c]);for(let l of c){if(typeof l!="string")throw Error(`Cache tag must by a string, "${typeof l}" given.`);if(this.newMetadata.tags[l]&&l==="")throw Error("Cache tag length must be greater than zero");this.newMetadata.tags[l]=l}return this}),n(this,"getMetadata",()=>this.metadata),this.key=null,this.value=null,this.isHit=!1,this.expiry=null,this.defaultLifetime=null,this.metadata={},this.newMetadata={},this.innerItem=null,this.poolHash=null,this.isTaggable=!1}}me=D,n(D,"METADATA_EXPIRY_OFFSET",1527506807),n(D,"RESERVED_CHARACTERS",["{","}","(",")","/","\\","@",":"]),n(D,"validateKey",h=>{if(typeof h!="string")throw Error(`Cache key must be string, "${typeof h}" given.`);if(h==="")throw Error("Cache key length must be greater than zero");for(let c of me.RESERVED_CHARACTERS)if(h.indexOf(c)>=0)throw Error(`Cache key "${h}" contains reserved character "${c}".`);return h});class z extends Error{constructor(c){super(c),this.name="LogicException"}toString(){return`${this.name}: ${this.message}`}}class Q{constructor(c,l,d){n(this,"getName",()=>this.name),n(this,"getCompiler",()=>this.compiler),n(this,"getEvaluator",()=>this.evaluator),this.name=c,this.compiler=l,this.evaluator=d}static fromJavascript(c,l=null){if(typeof c!="string"||c.length===0)throw TypeError("A JavaScript function name (string) must be provided.");let d=c.replace(/^\/+/,""),m=d.split("."),w=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:{};for(let x of m){if(w==null)break;w=w[x]}if(typeof w!="function")throw Error(`JavaScript function "${d}" does not exist.`);if(!l&&m.length>1)throw Error(`An expression function name must be defined when JavaScript function "${d}" is namespaced.`);return new this(l||m[m.length-1],(...x)=>`${d}(${x.join(", ")})`,(x,..._)=>w(..._))}}class Se{constructor(c=null,l=[]){n(this,"compile",(d,m=[])=>this.getCompiler().compile(this.parse(d,m).getNodes()).getSource()),n(this,"evaluate",(d,m={})=>this.parse(d,Object.keys(m)).getNodes().evaluate(this.functions,m)),n(this,"parse",(d,m=[],w=0)=>{if(d instanceof W)return d;m.sort((oe,B)=>{let pe=oe,ye=B;return typeof oe=="object"&&(pe=Object.values(oe)[0]),typeof B=="object"&&(ye=Object.values(B)[0]),pe.localeCompare(ye)});let x=[];for(let oe of m){let B=oe;typeof oe=="object"&&(B=Object.keys(oe)[0]+":"+Object.values(oe)[0]),x.push(B)}let _=this.cache.getItem(this.fixedEncodeURIComponent(d+"//"+x.join("|"))),$=_.get();if($===null){let oe=this.getParser().parse(this.getLexer().tokenize(d),m,w);$=new W(d,oe),_.set($),this.cache.save(_)}return $}),n(this,"lint",(d,m=null,w=0)=>{m===null&&(console.log('Deprecated: passing "null" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument'),w|=1,m=[]),d instanceof W||this.getParser().lint(this.getLexer().tokenize(d),m,w)}),n(this,"fixedEncodeURIComponent",d=>encodeURIComponent(d).replace(/[!'()*]/g,function(m){return"%"+m.charCodeAt(0).toString(16)})),n(this,"register",(d,m,w)=>{if(this.parser!==null)throw new z("Registering functions after calling evaluate(), compile(), or parse() is not supported.");this.functions[d]={compiler:m,evaluator:w}}),n(this,"addFunction",d=>{this.register(d.getName(),d.getCompiler(),d.getEvaluator())}),n(this,"registerProvider",d=>{for(let m of d.getFunctions())this.addFunction(m)}),n(this,"getLexer",()=>(this.lexer===null&&(this.lexer={tokenize:u}),this.lexer)),n(this,"getParser",()=>(this.parser===null&&(this.parser=new R(this.functions)),this.parser)),n(this,"getCompiler",()=>(this.compiler===null&&(this.compiler=new U(this.functions)),this.compiler.reset())),this.functions=[],this.lexer=null,this.parser=null,this.compiler=null,this.cache=c||new se,this._registerBuiltinFunctions();for(let d of l)this.registerProvider(d)}_registerBuiltinFunctions(){let c=Q.fromJavascript("Math.min","min"),l=Q.fromJavascript("Math.max","max");this.addFunction(c),this.addFunction(l),this.addFunction(new Q("constant",function(d){return`(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));return __n.split('.').reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${d})`},function(d,m){if(typeof m!="string"||!m)return;let w=(x=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:{},m.split(".").reduce((_,$)=>_==null?void 0:_[$],x));var x;return w===void 0&&d&&Object.prototype.hasOwnProperty.call(d,m)&&(w=d[m]),w})),this.addFunction(new Q("enum",function(d){return`(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));if(typeof __n!=='string'||!__n)return undefined;var s=String(__n);var keys=[],buf='';for(var i=0;i<s.length;i++){var c=s.charCodeAt(i);if(c===46||c===92){if(buf){keys.push(buf);buf='';}continue;}if(c===58){if(i+1<s.length&&s.charCodeAt(i+1)===58){if(buf){keys.push(buf);buf='';}i++;continue;}}buf+=s[i];}if(buf)keys.push(buf);return keys.reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${d})`},function(d,m){if(typeof m!="string"||!m)return;let w=String(m).replace(/\\/g,".").replace(/::/g,".");var x;return w?(x=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:{},w.split(".").reduce((_,$)=>_==null?void 0:_[$],x)):void 0}))}}class re{getFunctions(){throw Error("getFunctions must be implemented by "+this.constructor.name)}}let he=new Q("isset",function(h){if(/^(?:"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')$/.test(h))throw Error('isset() does not support compile() when called with a string-literal path (e.g. isset("foo.bar")). Use an expression path instead (e.g. isset(foo.bar) or isset(foo?.bar)), or call evaluate() directly.');return`(function(){try{var __v=(${h});return __v!==null&&__v!==undefined;}catch(e){return false;}})()`},function(h,c){if(typeof c!="string")return c!=null;if(!(c.split(/[.\[]/)[0]in h))return!0;let l="",d=[],m="",w="";for(let x=0;x<c.length;x++){let _=c[x];if(_!=="]")if(_!=="["){if(m==="object"&&(!/[A-z0-9_]/.test(_)||x===c.length-1)){let $=!1;if(x===c.length-1&&(w+=_,$=!0),m="",d.push({type:"object",attribute:w}),w="",$)continue}_==="."?(m="object",w=""):m?w+=_:l+=_}else m="array",w="";else m="",d.push({type:"array",index:w.replace(/"/g,"").replace(/'/g,"")}),w=""}if(d.length>0){if(h[l]!==void 0){let x=h[l];for(let _ of d){if(_.type==="array"){if(x[_.index]===void 0)return!1;x=x[_.index]}if(_.type==="object"){if(x[_.attribute]===void 0)return!1;x=x[_.attribute]}}return!0}return!1}return h[l]!==void 0});function ke(...h){let[c,l,d]=h,m=c,w=l;if(h.length<2||m===void 0||w===void 0)return null;if(m===""||m===!1||m===null)return!1;if(typeof m=="function"||typeof m=="object"||typeof w=="function"||typeof w=="object")return{0:""};m===!0&&(m="1");let x=m+"",_=(w+"").split(x);return d===void 0?_:(d===0&&(d=1),d>0?d>=_.length?_:_.slice(0,d-1).concat([_.slice(d-1).join(x)]):-d>=_.length?[]:(_.splice(_.length+d),_))}let ge=h=>Object.entries(h);function _e(h){return typeof h=="object"&&!!h}function qe(h){return _e(h)&&!(function(c){return Array.isArray(c)})(h)}function Je(h){return(function(c){return _e(c)})(h)?h:{}}let Ze=typeof window=="object"&&window!==null?window:typeof global=="object"&&global!==null?global:{};function ut(){let h=(()=>{let B=Ze.$locutus;typeof B=="object"&&B||(B={},Ze.$locutus=B);let pe=B.php;return typeof pe=="object"&&pe||(pe={},B.php=pe),pe})(),c=h.ini,l=h.locales,d=h.localeCategories,m=h.pointers,w=qe(c)?c:{},x=(B=>qe(B))(l)?l:{},_=(B=>qe(B))(d)?d:{},$=Array.isArray(m)?m:[];c!==w&&(h.ini=w),l!==x&&(h.locales=x),d!==_&&(h.localeCategories=_),m!==$&&(h.pointers=$);let oe=h.locale_default;return{ini:w,locales:x,localeCategories:_,pointers:$,locale_default:typeof oe=="string"?oe:void 0}}function Me(h){let c=ut().ini[h];return c&&c.local_value!==void 0?c.local_value===null?"":String(c.local_value):""}function et(h){if(h===void 0)throw Error("strlen() expects exactly 1 argument, 0 given");let c=h+"";if((Me("unicode.semantics")||"off")==="off")return c.length;let l=0,d=0,m=function(w,x){let _=w.charCodeAt(x);if(_>=55296&&_<=56319){if(w.length<=x+1)throw Error("High surrogate without following low surrogate");let $=w.charCodeAt(x+1);if($<56320||$>57343)throw Error("High surrogate without following low surrogate");return w.charAt(x)+w.charAt(x+1)}if(_>=56320&&_<=57343){if(x===0)throw Error("Low surrogate without preceding high surrogate");let $=w.charCodeAt(x-1);if($<55296||$>56319)throw Error("Low surrogate without preceding high surrogate");return!1}return w.charAt(x)};for(l=0,d=0;l<c.length;l++)m(c,l)!==!1&&d++;return d}function He(h){if(h===void 0)throw Error("strtolower() expects exactly 1 argument, 0 given");return(h+"").toLowerCase()}function Ue(h){if(h===void 0)throw Error("strtoupper() expects exactly 1 argument, 0 given");return(h+"").toUpperCase()}function Be(h,c,l){let d=(function(_){if(typeof _=="boolean")return _?"1":"";if(typeof _=="string")return _;if(typeof _=="number")return isNaN(_)?"NAN":isFinite(_)?_+"":(_<0?"-":"")+"INF";if(_===void 0)return"";if(typeof _=="object")return Array.isArray(_)?"Array":_===null?"":"Object";throw Error("Unsupported value type")})(h),m=Me("unicode.semantics")==="on"?d.match(/[\uD800-\uDBFF][\uDC00-\uDFFF]|[\s\S]/g)||[]:null,w=m?m.length:d.length,x=w;return c<0&&(c+=x),l!==void 0&&(x=l<0?l+x:l+c),!(c>w||c<0||c>x)&&(m?m.slice(c,x).join(""):d.slice(c,x))}function ot(h,c,l){let d=0;return d=(h+="").indexOf(c),d!==-1&&(l?h.substr(0,d):h.slice(d))}function Mt(h,c,l){let d=0;return d=(h+="").toLowerCase().indexOf((c+"").toLowerCase()),d!==-1&&(l?h.substr(0,d):h.slice(d))}function Yr(h,...c){let l={};if(c.length<1)return l;let d=Je(h);e:for(let[m,w]of ge(d)){for(let x of c){let _=Je(x),$=!1;for(let[,oe]of ge(_))if(oe===w){$=!0;break}if(!$)continue e}l[m]=w}return l}let dt=h=>{if(!h||typeof h!="object")return!1;let c=Object.getPrototypeOf(h);return c===Array.prototype||c===Object.prototype};function ft(h,c=0){let l=0;if(h==null)return 0;if(typeof h!="object")return 1;let d=Object.getPrototypeOf(h);if(d!==Array.prototype&&d!==Object.prototype)return 1;let m=c==="COUNT_RECURSIVE"||c===1;if(Array.isArray(h)){for(let w of Object.keys(h)){l++;let x=h[Number(w)];m&&dt(x)&&(l+=ft(x,1))}return l}for(let w of Object.values(h))l++,m&&dt(w)&&(l+=ft(w,1));return l}function wt(...h){let c,l="",d="",m="";if(h.length===1){let[w]=h;c=w}else{let[w,x]=h;m=String(w??""),c=x}if(typeof c=="object"&&c){if(Array.isArray(c))return c.join(m);for(let w in c)l+=d+c[w],d=m;return l}return String(c)}let jt=new Q("implode",function(h,c){return`__runtime.implode(${h}, ${c})`},function(h,c,l){return wt(c,l)}),nr=new Q("count",function(h,c){let l="";return c&&(l=`, ${c}`),`__runtime.count(${h}${l})`},function(h,c,l){return ft(c,l)}),Wr=new Q("array_intersect",function(h,...c){let l="";return c.length>0&&(l=", "+c.join(", ")),`__runtime.array_intersect(${h}${l})`},function(h){let c=[],l=!0;for(let m=1;m<arguments.length;m++)c.push(arguments[m]),Array.isArray(arguments[m])||(l=!1);let d=Yr.apply(null,c);return l?Object.values(d):d});function Kr(h,c){let l,d=new Date,m=["Sun","Mon","Tues","Wednes","Thurs","Fri","Satur","January","February","March","April","May","June","July","August","September","October","November","December"],w=/\\?(.?)/gi,x=function(B,pe){return ye=B,Object.hasOwn(l,ye)?String(l[B]()):pe;var ye},_=function(B,pe){let ye=String(B);for(;ye.length<pe;)ye="0"+ye;return ye};return l={d:function(){return _(l.j(),2)},D:function(){return String(l.l()).slice(0,3)},j:function(){return d.getDate()},l:function(){return(m[Number(l.w())]??"")+"day"},N:function(){return Number(l.w())||7},S:function(){let B=Number(l.j()),pe=B%10;return pe<=3&&Number.parseInt(String(B%100/10),10)===1&&(pe=0),["st","nd","rd"][pe-1]||"th"},w:function(){return d.getDay()},z:function(){let B=new Date(Number(l.Y()),Number(l.n())-1,Number(l.j())),pe=new Date(Number(l.Y()),0,1);return Math.round((B.getTime()-pe.getTime())/864e5)},W:function(){let B=new Date(Number(l.Y()),Number(l.n())-1,Number(l.j())-Number(l.N())+3),pe=new Date(B.getFullYear(),0,4);return _(1+Math.round((B.getTime()-pe.getTime())/864e5/7),2)},F:function(){return m[6+Number(l.n())]??""},m:function(){return _(l.n(),2)},M:function(){return String(l.F()).slice(0,3)},n:function(){return d.getMonth()+1},t:function(){return new Date(Number(l.Y()),Number(l.n()),0).getDate()},L:function(){let B=Number(l.Y());return+(B%4==0&&B%100!=0||B%400==0)},o:function(){let B=Number(l.n()),pe=Number(l.W());return Number(l.Y())+(B===12&&pe<9?1:B===1&&pe>9?-1:0)},Y:function(){return d.getFullYear()},y:function(){return String(l.Y()).slice(-2)},a:function(){return d.getHours()>11?"pm":"am"},A:function(){return String(l.a()).toUpperCase()},B:function(){let B=3600*d.getUTCHours(),pe=60*d.getUTCMinutes(),ye=d.getUTCSeconds();return _(Math.floor((B+pe+ye+3600)/86.4)%1e3,3)},g:function(){return Number(l.G())%12||12},G:function(){return d.getHours()},h:function(){return _(l.g(),2)},H:function(){return _(l.G(),2)},i:function(){return _(d.getMinutes(),2)},s:function(){return _(d.getSeconds(),2)},u:function(){return _(1e3*d.getMilliseconds(),6)},e:function(){throw Error("Not supported (see source code of date() for timezone on how to add support)")},I:function(){let B=new Date(Number(l.Y()),0),pe=Date.UTC(Number(l.Y()),0),ye=new Date(Number(l.Y()),6),De=Date.UTC(Number(l.Y()),6);return B.getTime()-pe===ye.getTime()-De?0:1},O:function(){let B=d.getTimezoneOffset(),pe=Math.abs(B);return(B>0?"-":"+")+_(100*Math.floor(pe/60)+pe%60,4)},P:function(){let B=String(l.O());return B.slice(0,3)+":"+B.slice(3,5)},T:function(){return"UTC"},Z:function(){return 60*-d.getTimezoneOffset()},c:function(){return"Y-m-d\\TH:i:sP".replace(w,x)},r:function(){return"D, d M Y H:i:s O".replace(w,x)},U:function(){return d.getTime()/1e3|0}},$=h,d=(oe=c)===void 0?new Date:oe instanceof Date?new Date(oe):new Date(1e3*Number(oe)),$.replace(w,x);var $,oe}let ir="[ \\t]+",xt="[ \\t]*",bt="(?:([ap])\\.?m\\.?([\\t ]|$))",M="(2[0-4]|[01]?[0-9])",O="([01][0-9]|2[0-4])",Z="(0?[1-9]|1[0-2])",ee="([0-5]?[0-9])",ce="([0-5][0-9])",Ye="(60|[0-5]?[0-9])",Le="(60|[0-5][0-9])",or="(?:\\.([0-9]+))",Qe="sunday|monday|tuesday|wednesday|thursday|friday|saturday|sun|mon|tue|wed|thu|fri|sat|weekdays?",qi="next|last|previous|this",Hi="(?:second|sec|minute|min|hour|day|fortnight|forthnight|month|year)s?|weeks|"+Qe,Sr="([0-9]{1,4})",We="([0-9]{4})",Nt="(1[0-2]|0?[0-9])",qt="(0[0-9]|1[0-2])",mt="(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)",Et="(0[0-9]|[1-2][0-9]|3[01])",Ui="january|february|march|april|may|june|july|august|september|october|november|december",_r="jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec",ar="("+Ui+"|"+_r+"|i[vx]|vi{0,3}|xi{0,2}|i{1,3})",An="((?:GMT)?([+-])"+M+":?"+ee+"?)",Ar=ar+"[ .\\t-]*"+mt+"[,.stndrh\\t ]*";function Ht(h,c){switch(c==null?void 0:c.toLowerCase()){case"a":h+=h===12?-12:0;break;case"p":h+=h===12?0:12}return h}function Ut(h){let c=+h;return h.length<4&&c<100&&(c+=c<70?2e3:1900),c}function tt(h){return{jan:0,january:0,i:0,feb:1,february:1,ii:1,mar:2,march:2,iii:2,apr:3,april:3,iv:3,may:4,v:4,jun:5,june:5,vi:5,jul:6,july:6,vii:6,aug:7,august:7,viii:7,sep:8,sept:8,september:8,ix:8,oct:9,october:9,x:9,nov:10,november:10,xi:10,dec:11,december:11,xii:11}[h.toLowerCase()]??NaN}function Tn(h,c=0){return{mon:1,monday:1,tue:2,tuesday:2,wed:3,wednesday:3,thu:4,thursday:4,fri:5,friday:5,sat:6,saturday:6,sun:0,sunday:0}[h.toLowerCase()]||c}function Cn(h,c=NaN){let l=h==null?void 0:h.match(/(?:GMT)?([+-])(\d+)(:?)(\d{0,2})/i);if(!l)return c;let d=l[1]==="-"?-1:1,m=+(l[2]??0),w=+(l[4]??0);return l[4]||l[3]||(w=Math.floor(m%100),m=Math.floor(m/100)),d*(60*m+w)*60}let os={acdt:37800,acst:34200,addt:-7200,adt:-10800,aedt:39600,aest:36e3,ahdt:-32400,ahst:-36e3,akdt:-28800,akst:-32400,amt:-13840,apt:-10800,ast:-14400,awdt:32400,awst:28800,awt:-10800,bdst:7200,bdt:-36e3,bmt:-14309,bst:3600,cast:34200,cat:7200,cddt:-14400,cdt:-18e3,cemt:10800,cest:7200,cet:3600,cmt:-15408,cpt:-18e3,cst:-21600,cwt:-18e3,chst:36e3,dmt:-1521,eat:10800,eddt:-10800,edt:-14400,eest:10800,eet:7200,emt:-26248,ept:-14400,est:-18e3,ewt:-14400,ffmt:-14660,fmt:-4056,gdt:39600,gmt:0,gst:36e3,hdt:-34200,hkst:32400,hkt:28800,hmt:-19776,hpt:-34200,hst:-36e3,hwt:-34200,iddt:14400,idt:10800,imt:25025,ist:7200,jdt:36e3,jmt:8440,jst:32400,kdt:36e3,kmt:5736,kst:30600,lst:9394,mddt:-18e3,mdst:16279,mdt:-21600,mest:7200,met:3600,mmt:9017,mpt:-21600,msd:14400,msk:10800,mst:-25200,mwt:-21600,nddt:-5400,ndt:-9052,npt:-9e3,nst:-12600,nwt:-9e3,nzdt:46800,nzmt:41400,nzst:43200,pddt:-21600,pdt:-25200,pkst:21600,pkt:18e3,plmt:25590,pmt:-13236,ppmt:-17340,ppt:-25200,pst:-28800,pwt:-25200,qmt:-18840,rmt:5794,sast:7200,sdmt:-16800,sjmt:-20173,smt:-13884,sst:-39600,tbmt:10751,tmt:12344,uct:0,utc:0,wast:7200,wat:3600,wemt:7200,west:3600,wet:0,wib:25200,wita:28800,wit:32400,wmt:5040,yddt:-25200,ydt:-28800,ypt:-28800,yst:-32400,ywt:-28800,a:3600,b:7200,c:10800,d:14400,e:18e3,f:21600,g:25200,h:28800,i:32400,k:36e3,l:39600,m:43200,n:-3600,o:-7200,p:-10800,q:-14400,r:-18e3,s:-21600,t:-25200,u:-28800,v:-32400,w:-36e3,x:-39600,y:-43200,z:0},le={yesterday:{regex:/^yesterday/i,name:"yesterday",callback(){return--this.rd,this.resetTime()}},now:{regex:/^now/i,name:"now"},noon:{regex:/^noon/i,name:"noon",callback(){return this.resetTime()&&this.time(12,0,0,0)}},midnightOrToday:{regex:/^(midnight|today)/i,name:"midnight | today",callback(){return this.resetTime()}},tomorrow:{regex:/^tomorrow/i,name:"tomorrow",callback(){return this.rd+=1,this.resetTime()}},timestamp:{regex:/^@(-?\d+)/i,name:"timestamp",callback(h,c){return this.rs+=+c,this.y=1970,this.m=0,this.d=1,this.dates=0,this.resetTime()&&this.zone(0)}},firstOrLastDay:{regex:/^(first|last) day of/i,name:"firstdayof | lastdayof",callback(h,c){this.firstOrLastDayOfMonth=c.toLowerCase()==="first"?1:-1}},backOrFrontOf:{regex:RegExp("^(back|front) of "+M+xt+bt+"?","i"),name:"backof | frontof",callback(h,c,l,d){let m=+l,w=15;return c.toLowerCase()==="back"||(--m,w=45),m=Ht(m,d),this.resetTime()&&this.time(m,w,0,0)}},mssqltime:{regex:RegExp("^"+Z+":"+ce+":"+Le+"[:.]([0-9]+)"+bt,"i"),name:"mssqltime",callback(h,c,l,d,m,w){return this.time(Ht(+c,w),+l,+d,+m.substr(0,3))}},oracledate:{regex:/^(\d{2})-([A-Z]{3})-(\d{2})$/i,name:"d-M-y",callback(h,c,l,d){let m={JAN:0,FEB:1,MAR:2,APR:3,MAY:4,JUN:5,JUL:6,AUG:7,SEP:8,OCT:9,NOV:10,DEC:11}[l.toUpperCase()]??NaN;return this.ymd(2e3+parseInt(d,10),m,parseInt(c,10))}},timeLong12:{regex:RegExp("^"+Z+"[:.]"+ee+"[:.]"+Le+xt+bt,"i"),name:"timelong12",callback(h,c,l,d,m){return this.time(Ht(+c,m),+l,+d,0)}},timeShort12:{regex:RegExp("^"+Z+"[:.]"+ce+xt+bt,"i"),name:"timeshort12",callback(h,c,l,d){return this.time(Ht(+c,d),+l,0,0)}},timeTiny12:{regex:RegExp("^"+Z+xt+bt,"i"),name:"timetiny12",callback(h,c,l){return this.time(Ht(+c,l),0,0,0)}},soap:{regex:RegExp("^"+We+"-"+qt+"-"+Et+"T"+O+":"+ce+":"+Le+or+An+"?","i"),name:"soap",callback(h,c,l,d,m,w,x,_,$){return this.ymd(+c,l-1,+d)&&this.time(+m,+w,+x,+_.substr(0,3))&&this.zone(Cn($))}},wddx:{regex:RegExp("^"+We+"-"+Nt+"-"+mt+"T"+M+":"+ee+":"+Ye),name:"wddx",callback(h,c,l,d,m,w,x){return this.ymd(+c,l-1,+d)&&this.time(+m,+w,+x,0)}},exif:{regex:RegExp("^"+We+":"+qt+":"+Et+" "+O+":"+ce+":"+Le,"i"),name:"exif",callback(h,c,l,d,m,w,x){return this.ymd(+c,l-1,+d)&&this.time(+m,+w,+x,0)}},xmlRpc:{regex:RegExp("^"+We+qt+Et+"T"+M+":"+ce+":"+Le),name:"xmlrpc",callback(h,c,l,d,m,w,x){return this.ymd(+c,l-1,+d)&&this.time(+m,+w,+x,0)}},xmlRpcNoColon:{regex:RegExp("^"+We+qt+Et+"[Tt]"+M+ce+Le),name:"xmlrpcnocolon",callback(h,c,l,d,m,w,x){return this.ymd(+c,l-1,+d)&&this.time(+m,+w,+x,0)}},clf:{regex:RegExp("^"+mt+"/("+_r+")/"+We+":"+O+":"+ce+":"+Le+ir+An,"i"),name:"clf",callback(h,c,l,d,m,w,x,_){return this.ymd(+d,tt(l),+c)&&this.time(+m,+w,+x,0)&&this.zone(Cn(_))}},iso8601long:{regex:RegExp("^t?"+M+"[:.]"+ee+"[:.]"+Ye+or,"i"),name:"iso8601long",callback(h,c,l,d,m){return this.time(+c,+l,+d,+m.substr(0,3))}},dateTextual:{regex:RegExp("^"+ar+"[ .\\t-]*"+mt+"[,.stndrh\\t ]+"+Sr,"i"),name:"datetextual",callback(h,c,l,d){return this.ymd(Ut(d),tt(c),+l)}},pointedDate4:{regex:RegExp("^"+mt+"[.\\t-]"+Nt+"[.-]"+We),name:"pointeddate4",callback(h,c,l,d){return this.ymd(+d,l-1,+c)}},pointedDate2:{regex:RegExp("^"+mt+"[.\\t]"+Nt+"\\.([0-9]{2})"),name:"pointeddate2",callback(h,c,l,d){return this.ymd(Ut(d),l-1,+c)}},timeLong24:{regex:RegExp("^t?"+M+"[:.]"+ee+"[:.]"+Ye),name:"timelong24",callback(h,c,l,d){return this.time(+c,+l,+d,0)}},dateNoColon:{regex:RegExp("^"+We+qt+Et),name:"datenocolon",callback(h,c,l,d){return this.ymd(+c,l-1,+d)}},pgydotd:{regex:RegExp("^"+We+"\\.?(00[1-9]|0[1-9][0-9]|[12][0-9][0-9]|3[0-5][0-9]|36[0-6])"),name:"pgydotd",callback(h,c,l){return this.ymd(+c,0,+l)}},timeShort24:{regex:RegExp("^t?"+M+"[:.]"+ee,"i"),name:"timeshort24",callback(h,c,l){return this.time(+c,+l,0,0)}},iso8601noColon:{regex:RegExp("^t?"+O+ce+Le,"i"),name:"iso8601nocolon",callback(h,c,l,d){return this.time(+c,+l,+d,0)}},iso8601dateSlash:{regex:RegExp("^"+We+"/"+qt+"/"+Et+"/"),name:"iso8601dateslash",callback(h,c,l,d){return this.ymd(+c,l-1,+d)}},dateSlash:{regex:RegExp("^"+We+"/"+Nt+"/"+mt),name:"dateslash",callback(h,c,l,d){return this.ymd(+c,l-1,+d)}},american:{regex:RegExp("^"+Nt+"/"+mt+"/"+Sr),name:"american",callback(h,c,l,d){return this.ymd(Ut(d),c-1,+l)}},americanShort:{regex:RegExp("^"+Nt+"/"+mt),name:"americanshort",callback(h,c,l){return this.ymd(this.y,c-1,+l)}},gnuDateShortOrIso8601date2:{regex:RegExp("^"+Sr+"-"+Nt+"-"+mt),name:"gnudateshort | iso8601date2",callback(h,c,l,d){return this.ymd(Ut(c),l-1,+d)}},iso8601date4:{regex:RegExp("^([+-]?[0-9]{4})-"+qt+"-"+Et),name:"iso8601date4",callback(h,c,l,d){return this.ymd(+c,l-1,+d)}},gnuNoColon:{regex:RegExp("^t?"+O+ce,"i"),name:"gnunocolon",callback(h,c,l){switch(this.times){case 0:return this.time(+c,+l,0,this.f);case 1:return this.y=100*c+ +l,this.times++,!0;default:return!1}}},gnuDateShorter:{regex:RegExp("^"+We+"-"+Nt),name:"gnudateshorter",callback(h,c,l){return this.ymd(+c,l-1,1)}},pgTextReverse:{regex:RegExp("^(\\d{3,4}|[4-9]\\d|3[2-9])-("+_r+")-"+Et,"i"),name:"pgtextreverse",callback(h,c,l,d){return this.ymd(Ut(c),tt(l),+d)}},dateFull:{regex:RegExp("^"+mt+"[ \\t.-]*"+ar+"[ \\t.-]*"+Sr,"i"),name:"datefull",callback(h,c,l,d){return this.ymd(Ut(d),tt(l),+c)}},dateNoDay:{regex:RegExp("^"+ar+"[ .\\t-]*"+We,"i"),name:"datenoday",callback(h,c,l){return this.ymd(+l,tt(c),1)}},dateNoDayRev:{regex:RegExp("^"+We+"[ .\\t-]*"+ar,"i"),name:"datenodayrev",callback(h,c,l){return this.ymd(+c,tt(l),1)}},pgTextShort:{regex:RegExp("^("+_r+")-"+Et+"-"+Sr,"i"),name:"pgtextshort",callback(h,c,l,d){return this.ymd(Ut(d),tt(c),+l)}},dateNoYear:{regex:RegExp("^"+Ar,"i"),name:"datenoyear",callback(h,c,l){return this.ymd(this.y,tt(c),+l)}},dateNoYearRev:{regex:RegExp("^"+mt+"[ .\\t-]*"+ar,"i"),name:"datenoyearrev",callback(h,c,l){return this.ymd(this.y,tt(l),+c)}},isoWeekDay:{regex:RegExp("^"+We+"-?W(0[1-9]|[1-4][0-9]|5[0-3])(?:-?([0-7]))?"),name:"isoweekday | isoweek",callback(h,c,l,d){let m=d?+d:1;if(!this.ymd(+c,0,1))return!1;let w=new Date(this.y,this.m,this.d).getDay();return w=0-(w>4?w-7:w),this.rd+=w+7*(l-1)+m,!0}},relativeText:{regex:RegExp("^(first|second|third|fourth|fifth|sixth|seventh|eighth?|ninth|tenth|eleventh|twelfth|"+qi+")"+ir+"("+Hi+")","i"),name:"relativetext",callback(h,c,l){let{amount:d}=(function(m){let w=m.toLowerCase();return{amount:{last:-1,previous:-1,this:0,first:1,next:1,second:2,third:3,fourth:4,fifth:5,sixth:6,seventh:7,eight:8,eighth:8,ninth:9,tenth:10,eleventh:11,twelfth:12}[w]??0,behavior:{this:1}[w]||0}})(c);switch(l.toLowerCase()){case"sec":case"secs":case"second":case"seconds":this.rs+=d;break;case"min":case"mins":case"minute":case"minutes":this.ri+=d;break;case"hour":case"hours":this.rh+=d;break;case"day":case"days":this.rd+=d;break;case"fortnight":case"fortnights":case"forthnight":case"forthnights":this.rd+=14*d;break;case"week":case"weeks":this.rd+=7*d;break;case"month":case"months":this.rm+=d;break;case"year":case"years":this.ry+=d;break;case"mon":case"monday":case"tue":case"tuesday":case"wed":case"wednesday":case"thu":case"thursday":case"fri":case"friday":case"sat":case"saturday":case"sun":case"sunday":this.resetTime(),this.weekday=Tn(l,7),this.weekdayBehavior=1,this.rd+=7*(d>0?d-1:d)}}},relative:{regex:RegExp("^([+-]*)[ \\t]*(\\d+)[ \\t]*("+Hi+"|week)","i"),name:"relative",callback(h,c,l,d){let m=c.replace(/[^-]/g,"").length,w=+l*(-1)**m;switch(d.toLowerCase()){case"sec":case"secs":case"second":case"seconds":this.rs+=w;break;case"min":case"mins":case"minute":case"minutes":this.ri+=w;break;case"hour":case"hours":this.rh+=w;break;case"day":case"days":this.rd+=w;break;case"fortnight":case"fortnights":case"forthnight":case"forthnights":this.rd+=14*w;break;case"week":case"weeks":this.rd+=7*w;break;case"month":case"months":this.rm+=w;break;case"year":case"years":this.ry+=w;break;case"mon":case"monday":case"tue":case"tuesday":case"wed":case"wednesday":case"thu":case"thursday":case"fri":case"friday":case"sat":case"saturday":case"sun":case"sunday":this.resetTime(),this.weekday=Tn(d,7),this.weekdayBehavior=1,this.rd+=7*(w>0?w-1:w)}}},dayText:{regex:RegExp("^("+Qe+")","i"),name:"daytext",callback(h,c){this.resetTime(),this.weekday=Tn(c,0),this.weekdayBehavior!==2&&(this.weekdayBehavior=1)}},relativeTextWeek:{regex:RegExp("^("+qi+")"+ir+"week","i"),name:"relativetextweek",callback(h,c){switch(this.weekdayBehavior=2,c.toLowerCase()){case"this":this.rd+=0;break;case"next":this.rd+=7;break;case"last":case"previous":this.rd-=7}isNaN(this.weekday)&&(this.weekday=1)}},monthFullOrMonthAbbr:{regex:RegExp("^("+Ui+"|"+_r+")","i"),name:"monthfull | monthabbr",callback(h,c){return this.ymd(this.y,tt(c),this.d)}},tzCorrection:{regex:RegExp("^"+An,"i"),name:"tzcorrection",callback(h){return this.zone(Cn(h))}},tzAbbr:{regex:RegExp("^\\(?([a-zA-Z]{1,6})\\)?"),name:"tzabbr",callback(h,c){let l=os[c.toLowerCase()];return l!=null&&!Number.isNaN(l)&&this.zone(l)}},ago:{regex:/^ago/i,name:"ago",callback(){this.ry=-this.ry,this.rm=-this.rm,this.rd=-this.rd,this.rh=-this.rh,this.ri=-this.ri,this.rs=-this.rs,this.rf=-this.rf}},year4:{regex:RegExp("^"+We),name:"year4",callback(h,c){return this.y=+c,!0}},whitespace:{regex:/^[ .,\t]+/,name:"whitespace"},dateShortWithTimeLong:{regex:RegExp("^"+Ar+"t?"+M+"[:.]"+ee+"[:.]"+Ye,"i"),name:"dateshortwithtimelong",callback(h,c,l,d,m,w){return this.ymd(this.y,tt(c),+l)&&this.time(+d,+m,+w,0)}},dateShortWithTimeLong12:{regex:RegExp("^"+Ar+Z+"[:.]"+ee+"[:.]"+Le+xt+bt,"i"),name:"dateshortwithtimelong12",callback(h,c,l,d,m,w,x){return this.ymd(this.y,tt(c),+l)&&this.time(Ht(+d,x),+m,+w,0)}},dateShortWithTimeShort:{regex:RegExp("^"+Ar+"t?"+M+"[:.]"+ee,"i"),name:"dateshortwithtimeshort",callback(h,c,l,d,m){return this.ymd(this.y,tt(c),+l)&&this.time(+d,+m,0,0)}},dateShortWithTimeShort12:{regex:RegExp("^"+Ar+Z+"[:.]"+ce+xt+bt,"i"),name:"dateshortwithtimeshort12",callback(h,c,l,d,m,w){return this.ymd(this.y,tt(c),+l)&&this.time(Ht(+d,w),+m,0,0)}}},as={y:NaN,m:NaN,d:NaN,h:NaN,i:NaN,s:NaN,f:NaN,ry:0,rm:0,rd:0,rh:0,ri:0,rs:0,rf:0,weekday:NaN,weekdayBehavior:0,firstOrLastDayOfMonth:0,z:NaN,dates:0,times:0,zones:0,ymd(h,c,l){return!(this.dates>0)&&(this.dates++,this.y=h,this.m=c,this.d=l,!0)},time(h,c,l,d){return!(this.times>0)&&(this.times++,this.h=h,this.i=c,this.s=l,this.f=d,!0)},resetTime(){return this.h=0,this.i=0,this.s=0,this.f=0,this.times=0,!0},zone(h){return this.zones<=1&&(this.zones++,this.z=h,!0)},toDate(h){switch(this.dates&&!this.times&&(this.h=this.i=this.s=this.f=0),isNaN(this.y)&&(this.y=h.getFullYear()),isNaN(this.m)&&(this.m=h.getMonth()),isNaN(this.d)&&(this.d=h.getDate()),isNaN(this.h)&&(this.h=h.getHours()),isNaN(this.i)&&(this.i=h.getMinutes()),isNaN(this.s)&&(this.s=h.getSeconds()),isNaN(this.f)&&(this.f=h.getMilliseconds()),this.firstOrLastDayOfMonth){case 1:this.d=1;break;case-1:this.d=0,this.m+=1}if(!isNaN(this.weekday)){let l=new Date(h.getTime());l.setFullYear(this.y,this.m,this.d),l.setHours(this.h,this.i,this.s,this.f);let d=l.getDay();if(this.weekdayBehavior===2)d===0&&this.weekday!==0&&(this.weekday=-6),this.weekday===0&&d!==0&&(this.weekday=7),this.d-=d,this.d+=this.weekday;else{let m=this.weekday-d;(this.rd<0&&m<0||this.rd>=0&&m<=-this.weekdayBehavior)&&(m+=7),this.weekday>=0?this.d+=m:this.d-=7-(Math.abs(this.weekday)-d),this.weekday=NaN}}this.y+=this.ry,this.m+=this.rm,this.d+=this.rd,this.h+=this.rh,this.i+=this.ri,this.s+=this.rs,this.f+=this.rf,this.ry=this.rm=this.rd=0,this.rh=this.ri=this.rs=this.rf=0;let c=new Date(h.getTime());switch(c.setFullYear(this.y,this.m,this.d),c.setHours(this.h,this.i,this.s,this.f),this.firstOrLastDayOfMonth){case 1:c.setDate(1);break;case-1:c.setMonth(c.getMonth()+1,0)}return isNaN(this.z)||c.getTimezoneOffset()===this.z||(c.setUTCFullYear(c.getFullYear(),c.getMonth(),c.getDate()),c.setUTCHours(c.getHours(),c.getMinutes(),c.getSeconds()-this.z,c.getMilliseconds())),c}};function Bi(h,c){let l=c??Math.floor(Date.now()/1e3),d=[le.yesterday,le.now,le.noon,le.midnightOrToday,le.tomorrow,le.timestamp,le.firstOrLastDay,le.backOrFrontOf,le.timeTiny12,le.timeShort12,le.timeLong12,le.mssqltime,le.oracledate,le.timeShort24,le.timeLong24,le.iso8601long,le.gnuNoColon,le.iso8601noColon,le.americanShort,le.american,le.iso8601date4,le.iso8601dateSlash,le.dateSlash,le.gnuDateShortOrIso8601date2,le.gnuDateShorter,le.dateFull,le.pointedDate4,le.pointedDate2,le.dateNoDay,le.dateNoDayRev,le.dateTextual,le.dateNoYear,le.dateNoYearRev,le.dateNoColon,le.xmlRpc,le.xmlRpcNoColon,le.soap,le.wddx,le.exif,le.pgydotd,le.isoWeekDay,le.pgTextShort,le.pgTextReverse,le.clf,le.year4,le.ago,le.dayText,le.relativeTextWeek,le.relativeText,le.monthFullOrMonthAbbr,le.tzCorrection,le.tzAbbr,le.dateShortWithTimeShort12,le.dateShortWithTimeLong12,le.dateShortWithTimeShort,le.dateShortWithTimeLong,le.relative,le.whitespace],m={...as};for(;h.length;){let w=null,x=null;for(let _ of d){let $=h.match(_.regex);$&&(!w||$[0].length>w[0].length)&&(w=$,x=_)}if(!x||!w||x.callback&&x.callback.apply(m,w)===!1)return!1;h=h.substr(w[0].length),x=null,w=null}return Math.floor(m.toDate(new Date(1e3*l)).getTime()/1e3)}function ss(h){return h&&h.__esModule&&Object.prototype.hasOwnProperty.call(h,"default")?h.default:h}var Yi,Wi={exports:{}},ls=ss((Yi||(Yi=1,(function(h){h.exports=(function(){var c=1e3,l=6e4,d=36e5,m="millisecond",w="second",x="minute",_="hour",$="day",oe="week",B="month",pe="quarter",ye="year",De="date",rt="Invalid Date",Bt=/^(\d{4})[-/]?(\d{1,2})?[-/]?(\d{0,2})[Tt\s]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?[.:]?(\d+)?$/,kt=/\[([^\]]+)]|YYYY|YY|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g,Rt={name:"en",weekdays:"Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),months:"January_February_March_April_May_June_July_August_September_October_November_December".split("_"),ordinal:function(be){var ne=["th","st","nd","rd"],G=be%100;return"["+be+(ne[(G-20)%10]||ne[G]||ne[0])+"]"}},St=function(be,ne,G){var de=String(be);return!de||de.length>=ne?be:""+Array(ne+1-de.length).join(G)+be},Yt={s:St,z:function(be){var ne=-be.utcOffset(),G=Math.abs(ne),de=Math.floor(G/60),X=G%60;return(ne<=0?"+":"-")+St(de,2,"0")+":"+St(X,2,"0")},m:function be(ne,G){if(ne.date()<G.date())return-be(G,ne);var de=12*(G.year()-ne.year())+(G.month()-ne.month()),X=ne.clone().add(de,B),xe=G-X<0,Ee=ne.clone().add(de+(xe?-1:1),B);return+(-(de+(G-X)/(xe?X-Ee:Ee-X))||0)},a:function(be){return be<0?Math.ceil(be)||0:Math.floor(be)},p:function(be){return{M:B,y:ye,w:oe,d:$,D:De,h:_,m:x,s:w,ms:m,Q:pe}[be]||String(be||"").toLowerCase().replace(/s$/,"")},u:function(be){return be===void 0}},at="en",st={};st[at]=Rt;var Tr="$isDayjsObject",sr=function(be){return be instanceof Ke||!(!be||!be[Tr])},Wt=function be(ne,G,de){var X;if(!ne)return at;if(typeof ne=="string"){var xe=ne.toLowerCase();st[xe]&&(X=xe),G&&(st[xe]=G,X=xe);var Ee=ne.split("-");if(!X&&Ee.length>1)return be(Ee[0])}else{var Te=ne.name;st[Te]=ne,X=Te}return!de&&X&&(at=X),X||!de&&at},Ne=function(be,ne){if(sr(be))return be.clone();var G=typeof ne=="object"?ne:{};return G.date=be,G.args=arguments,new Ke(G)},ae=Yt;ae.l=Wt,ae.i=sr,ae.w=function(be,ne){return Ne(be,{locale:ne.$L,utc:ne.$u,x:ne.$x,$offset:ne.$offset})};var Ke=(function(){function be(G){this.$L=Wt(G.locale,null,!0),this.parse(G),this.$x=this.$x||G.x||{},this[Tr]=!0}var ne=be.prototype;return ne.parse=function(G){this.$d=(function(de){var X=de.date,xe=de.utc;if(X===null)return new Date(NaN);if(ae.u(X))return new Date;if(X instanceof Date)return new Date(X);if(typeof X=="string"&&!/Z$/i.test(X)){var Ee=X.match(Bt);if(Ee){var Te=Ee[2]-1||0,Re=(Ee[7]||"0").substring(0,3);return xe?new Date(Date.UTC(Ee[1],Te,Ee[3]||1,Ee[4]||0,Ee[5]||0,Ee[6]||0,Re)):new Date(Ee[1],Te,Ee[3]||1,Ee[4]||0,Ee[5]||0,Ee[6]||0,Re)}}return new Date(X)})(G),this.init()},ne.init=function(){var G=this.$d;this.$y=G.getFullYear(),this.$M=G.getMonth(),this.$D=G.getDate(),this.$W=G.getDay(),this.$H=G.getHours(),this.$m=G.getMinutes(),this.$s=G.getSeconds(),this.$ms=G.getMilliseconds()},ne.$utils=function(){return ae},ne.isValid=function(){return this.$d.toString()!==rt},ne.isSame=function(G,de){var X=Ne(G);return this.startOf(de)<=X&&X<=this.endOf(de)},ne.isAfter=function(G,de){return Ne(G)<this.startOf(de)},ne.isBefore=function(G,de){return this.endOf(de)<Ne(G)},ne.$g=function(G,de,X){return ae.u(G)?this[de]:this.set(X,G)},ne.unix=function(){return Math.floor(this.valueOf()/1e3)},ne.valueOf=function(){return this.$d.getTime()},ne.startOf=function(G,de){var X=this,xe=!!ae.u(de)||de,Ee=ae.p(G),Te=function(Gt,nt){var Ot=ae.w(X.$u?Date.UTC(X.$y,nt,Gt):new Date(X.$y,nt,Gt),X);return xe?Ot:Ot.endOf($)},Re=function(Gt,nt){return ae.w(X.toDate()[Gt].apply(X.toDate("s"),(xe?[0,0,0,0]:[23,59,59,999]).slice(nt)),X)},ze=this.$W,Ge=this.$M,ht=this.$D,lr="set"+(this.$u?"UTC":"");switch(Ee){case ye:return xe?Te(1,0):Te(31,11);case B:return xe?Te(1,Ge):Te(0,Ge+1);case oe:var Kt=this.$locale().weekStart||0,Cr=(ze<Kt?ze+7:ze)-Kt;return Te(xe?ht-Cr:ht+(6-Cr),Ge);case $:case De:return Re(lr+"Hours",0);case _:return Re(lr+"Minutes",1);case x:return Re(lr+"Seconds",2);case w:return Re(lr+"Milliseconds",3);default:return this.clone()}},ne.endOf=function(G){return this.startOf(G,!1)},ne.$set=function(G,de){var X,xe=ae.p(G),Ee="set"+(this.$u?"UTC":""),Te=(X={},X[$]=Ee+"Date",X[De]=Ee+"Date",X[B]=Ee+"Month",X[ye]=Ee+"FullYear",X[_]=Ee+"Hours",X[x]=Ee+"Minutes",X[w]=Ee+"Seconds",X[m]=Ee+"Milliseconds",X)[xe],Re=xe===$?this.$D+(de-this.$W):de;if(xe===B||xe===ye){var ze=this.clone().set(De,1);ze.$d[Te](Re),ze.init(),this.$d=ze.set(De,Math.min(this.$D,ze.daysInMonth())).$d}else Te&&this.$d[Te](Re);return this.init(),this},ne.set=function(G,de){return this.clone().$set(G,de)},ne.get=function(G){return this[ae.p(G)]()},ne.add=function(G,de){var X,xe=this;G=Number(G);var Ee=ae.p(de),Te=function(Ge){var ht=Ne(xe);return ae.w(ht.date(ht.date()+Math.round(Ge*G)),xe)};if(Ee===B)return this.set(B,this.$M+G);if(Ee===ye)return this.set(ye,this.$y+G);if(Ee===$)return Te(1);if(Ee===oe)return Te(7);var Re=(X={},X[x]=l,X[_]=d,X[w]=c,X)[Ee]||1,ze=this.$d.getTime()+G*Re;return ae.w(ze,this)},ne.subtract=function(G,de){return this.add(-1*G,de)},ne.format=function(G){var de=this,X=this.$locale();if(!this.isValid())return X.invalidDate||rt;var xe=G||"YYYY-MM-DDTHH:mm:ssZ",Ee=ae.z(this),Te=this.$H,Re=this.$m,ze=this.$M,Ge=X.weekdays,ht=X.months,lr=X.meridiem,Kt=function(nt,Ot,Ir,Jr){return nt&&(nt[Ot]||nt(de,xe))||Ir[Ot].slice(0,Jr)},Cr=function(nt){return ae.s(Te%12||12,nt,"0")},Gt=lr||function(nt,Ot,Ir){var Jr=nt<12?"AM":"PM";return Ir?Jr.toLowerCase():Jr};return xe.replace(kt,function(nt,Ot){return Ot||(function(Ir){switch(Ir){case"YY":return String(de.$y).slice(-2);case"YYYY":return ae.s(de.$y,4,"0");case"M":return ze+1;case"MM":return ae.s(ze+1,2,"0");case"MMM":return Kt(X.monthsShort,ze,ht,3);case"MMMM":return Kt(ht,ze);case"D":return de.$D;case"DD":return ae.s(de.$D,2,"0");case"d":return String(de.$W);case"dd":return Kt(X.weekdaysMin,de.$W,Ge,2);case"ddd":return Kt(X.weekdaysShort,de.$W,Ge,3);case"dddd":return Ge[de.$W];case"H":return String(Te);case"HH":return ae.s(Te,2,"0");case"h":return Cr(1);case"hh":return Cr(2);case"a":return Gt(Te,Re,!0);case"A":return Gt(Te,Re,!1);case"m":return String(Re);case"mm":return ae.s(Re,2,"0");case"s":return String(de.$s);case"ss":return ae.s(de.$s,2,"0");case"SSS":return ae.s(de.$ms,3,"0");case"Z":return Ee}return null})(nt)||Ee.replace(":","")})},ne.utcOffset=function(){return 15*-Math.round(this.$d.getTimezoneOffset()/15)},ne.diff=function(G,de,X){var xe,Ee=this,Te=ae.p(de),Re=Ne(G),ze=(Re.utcOffset()-this.utcOffset())*l,Ge=this-Re,ht=function(){return ae.m(Ee,Re)};switch(Te){case ye:xe=ht()/12;break;case B:xe=ht();break;case pe:xe=ht()/3;break;case oe:xe=(Ge-ze)/6048e5;break;case $:xe=(Ge-ze)/864e5;break;case _:xe=Ge/d;break;case x:xe=Ge/l;break;case w:xe=Ge/c;break;default:xe=Ge}return X?xe:ae.a(xe)},ne.daysInMonth=function(){return this.endOf(B).$D},ne.$locale=function(){return st[this.$L]},ne.locale=function(G,de){if(!G)return this.$L;var X=this.clone(),xe=Wt(G,de,!0);return xe&&(X.$L=xe),X},ne.clone=function(){return ae.w(this.$d,this)},ne.toDate=function(){return new Date(this.valueOf())},ne.toJSON=function(){return this.isValid()?this.toISOString():null},ne.toISOString=function(){return this.$d.toISOString()},ne.toString=function(){return this.$d.toUTCString()},be})(),Ki=Ke.prototype;return Ne.prototype=Ki,[["$ms",m],["$s",w],["$m",x],["$H",_],["$W",$],["$M",B],["$y",ye],["$D",De]].forEach(function(be){Ki[be[1]]=function(ne){return this.$g(ne,be[0],be[1])}}),Ne.extend=function(be,ne){return be.$i||(be.$i=(be(ne,Ke,Ne),!0)),Ne},Ne.locale=Wt,Ne.isDayjs=sr,Ne.unix=function(be){return Ne(1e3*be)},Ne.en=st[at],Ne.Ls=st,Ne.p={},Ne})()})(Wi)),Wi.exports));let Gr=h=>typeof h=="string",In=(h,c)=>h.format(c),cs={isString:Gr,strLen:h=>Gr(h)?h.length:0,isEmail:h=>!!Gr(h)&&/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(h),isPhone:h=>!!Gr(h)&&(h.substring(0,2)==="+1"&&(h=h.substring(2)),/^\d{10}$/.test(h.replace(/\D/g,""))),isNull:h=>h===null,isCurrency:h=>/(?=.*?\d)^\$?(([1-9]\d{0,2}(,\d{3})*)|\d+)?(\.\d{1,2})?$/.test(h),now:()=>ls(),dateFormat:In,year:h=>In(h,"YYYY"),date:h=>In(h,"YYYY-MM-DD"),string:h=>h==null||h.toString===void 0?"":h.toString(),int:h=>parseInt(h)},us={strtolower:He,strtoupper:Ue,explode:ke,strlen:et,strstr:ot,stristr:Mt,substr:Be,implode:wt,count:ft,array_intersect:(...h)=>Wr.getEvaluator()(null,...h),date:Kr,strtotime:Bi};r.AbstractProvider=re,r.ArrayAdapter=se,r.ArrayProvider=class extends re{getFunctions(){return[jt,nr,Wr]}},r.BasicProvider=class extends re{getFunctions(){return[he]}},r.CacheItem=D,r.CompileRuntime=us,r.Compiler=U,r.DateProvider=class extends re{getFunctions(){return[new Q("date",function(h,c){let l="";return c&&(l=`, ${c}`),`__runtime.date(${h}${l})`},function(h,c,l){return Kr(c,l)}),new Q("strtotime",function(h,c){let l="";return c&&(l=`, ${c}`),`__runtime.strtotime(${h}${l})`},function(h,c,l){return Bi(c,l)})]}},r.Expression=J,r.ExpressionFunction=Q,r.ExpressionLanguage=Se,r.IGNORE_UNKNOWN_FUNCTIONS=2,r.IGNORE_UNKNOWN_VARIABLES=1,r.Node=S,r.OPERATOR_LEFT=1,r.OPERATOR_RIGHT=2,r.ParsedExpression=W,r.Parser=R,r.StringProvider=class extends re{getFunctions(){return[new Q("strtolower",h=>"__runtime.strtolower("+h+")",(h,c)=>He(c)),new Q("strtoupper",h=>"__runtime.strtoupper("+h+")",(h,c)=>Ue(c)),new Q("explode",(h,c,l="null")=>`__runtime.explode(${h}, ${c}, ${l})`,(h,c,l,d=null)=>ke(c,l,d)),new Q("strlen",function(h){return`__runtime.strlen(${h})`},function(h,c){return et(c)}),new Q("strstr",function(h,c,l){let d="";return l&&(d=`, ${l}`),`__runtime.strstr(${h}, ${c}${d})`},function(h,c,l,d){return ot(c,l,d)}),new Q("stristr",function(h,c,l){let d="";return l&&(d=`, ${l}`),`__runtime.stristr(${h}, ${c}${d})`},function(h,c,l,d){return Mt(c,l,d)}),new Q("substr",function(h,c,l){let d="";return l&&(d=`, ${l}`),`__runtime.substr(${h}, ${c}${d})`},function(h,c,l,d){return Be(c,l,d)})]}},r.SyntaxError=a,r.Token=s,r.TokenStream=o,r.default=Se,r.defaultCustomFunctions=cs,r.tokenize=u,Object.defineProperty(r,"__esModule",{value:!0})}),(function(r){var n=r.ExpressionLanguage;if(n&&typeof n.ExpressionLanguage=="function"){var i=n.ExpressionLanguage;Object.keys(n).forEach(function(a){a in i||(i[a]=n[a])}),r.ExpressionLanguage=i}})(typeof globalThis<"u"?globalThis:typeof self<"u"?self:t)}))()),mo=null;function jd(){let t=fo,e=t.ExpressionLanguage||t.default||fo;if(typeof e!="function")throw TypeError("Unable to resolve expression-language constructor.");return e}function qd(){return mo??(mo=new(jd())),mo}function qp(t){var e,r;return(((e=t.formula)==null?void 0:e.expression)||((r=t.formula)==null?void 0:r.formula)||"").trim()}function Hp(t){var e;return Object.entries(((e=t.formula)==null?void 0:e.variables)||{}).filter(r=>{var n;return!!((n=r[1])!=null&&n.sourceKey)})}function Up(t,e){return Object.entries(t).forEach(([r,n])=>{if(Array.isArray(n)){let i=n.map(o=>typeof o=="string"&&o.trim()!==""&&!Number.isNaN(Number(o))?Number(o):o),a=i.filter(o=>typeof o=="number");t[r]=a.length===i.length&&i.length>0?a.reduce((o,s)=>o+Number(s||0),0):i;return}typeof n=="string"&&n.trim()!==""&&!Number.isNaN(Number(n))&&(t[r]=Number(n))}),t}function Hd(t,e){if(e.formatting!=="number")return typeof t=="number"||typeof t=="string"?t:"";let r=t;Array.isArray(r)&&(r=r.reduce((a,o)=>a+Number(o||0),0));let n=typeof e.decimals=="number"?e.decimals:0,i=Number(r||0).toFixed(n);return`${e.prefix||""}${i}${e.suffix||""}`}function Bp(t,e){var n,i;let r=(n=t.type)==null?void 0:n.endsWith("\\Number");return(i=t.type)!=null&&i.endsWith("\\Checkboxes")?Array.isArray(e)?e.length?e:"":e?[e]:"":Array.isArray(e)?e.length?r?e.map(a=>Number(a||0)):e:"":r?Number(e||0):e}function Yp(t,e,r){return Hd(qd().evaluate(t,e),r)}var ua=new Map;async function bi(t,e,r={}){let n=r.profile??"same-origin-browser",i=Ud(t,r,e.headers),a=await fetch(t,{...e,headers:i,credentials:n==="cross-origin-public"?"omit":"same-origin"}),o=new URL(t,typeof location>"u"?"http://localhost":location.href).origin,s=a.headers.get("X-Formie-Session");return n==="cross-origin-public"&&s&&ua.set(o,s),a}function Ud(t,e={},r){let n=e.profile??"same-origin-browser";if(!["same-origin-browser","cross-origin-public"].includes(n))throw Error("Client-rendered forms require a public browser profile. Use administrative API mutations for trusted administration.");let i=new Headers(r);i.set("X-Formie-Profile",n);let a=new URL(t,typeof location>"u"?"http://localhost":location.href).origin;if(n==="cross-origin-public"){let o=e.publicSession??ua.get(a);o&&i.set("X-Formie-Session",o)}return i}var ho=(()=>{let t=Intl.Segmenter;return t?new t(void 0,{granularity:"grapheme"}):null})(),Bd=/[\p{L}\p{N}\p{M}]+(?:['’._-][\p{L}\p{N}\p{M}]+)*/gu;function Yd(t){return typeof DOMParser<"u"?new DOMParser().parseFromString(t,"text/html").body.textContent||"":t.replace(/<[^>]*>/g," ")}function da(t){return Yd(t)}function Wd(t){return da(t).replace(/[\s\t\n\r]+/g," ").trim()}function Kd(t){return ho?Array.from(ho.segment(t)).length:Array.from(t).length}function Gd(t){var e;return((e=t.match(Bd))==null?void 0:e.length)||0}function Wp(t){let e=da(t),r=Wd(t);return{graphemeCount:Kd(e),wordCount:Gd(r)}}var Dr={username:"user:name"};Object.entries({form:["name","handle"],submission:["id","uid","title","url","date","site","status"],site:["id","name","handle","url","language"]}).forEach(([t,e])=>{e.forEach(r=>{Dr[`${t}.${r}`]=`${t}:${r}`})}),Object.entries({form:["Name","Handle"],submission:["Title","Url","Id","Uid","Date","Site","Status"],system:["Name","Email","ReplyTo"],site:["Name","Handle","Url","Id","Language"],user:["Ip","Id","Email","FullName","FirstName","LastName"]}).forEach(([t,e])=>{e.forEach(r=>{Dr[t+r]=`${t}:${r[0].toLowerCase()}${r.slice(1)}`})}),Object.entries({dateUs:"m/d/Y",dateInt:"d/m/Y",time12:"h:i a",time24:"H:i"}).forEach(([t,e])=>{Dr[t]=`timestamp;transform=format;preset=custom;pattern=${encodeURIComponent(e)}`});function vi(t){let e={raw:t,target:"",identifier:"",selector:"",default:"",transformerId:"",transformerParams:{},version:1,isValid:!1},r=p=>({...e,diagnostic:p}),n=t.trim().match(/^\{([^{}]+)\}$/);if(!n)return r("invalidSyntax");let i=n[1].replace(/^field\./,"field:"),a=i.indexOf("|"),o=a<0?"":i.slice(a+1);i=a<0?i:i.slice(0,a);let s=i.indexOf(";"),u=s<0?i:i.slice(0,s);i=(Object.prototype.hasOwnProperty.call(Dr,u)?Dr[u]:u)+(s<0?"":i.slice(s));let[f,...g]=i.split(";"),b=Object.create(null);try{for(let k of g){let C=k.match(/^([a-zA-Z][a-zA-Z0-9_]*)=(.*)$/);if(!C||Object.prototype.hasOwnProperty.call(b,C[1]))return r("invalidMetadata");b[C[1]]=decodeURIComponent(C[2])}if((b.v??"1")!=="1")return r("unsupportedVersion");delete b.v;let p=f.indexOf(":"),y=p<0?f:f.slice(0,p),E=p<0?"":f.slice(p+1);if(!/^[a-zA-Z][a-zA-Z0-9_-]*$/.test(y))return r("invalidSource");let T="";if(y==="field"&&E.includes(":")){let k=E.indexOf(":");T=E.slice(k+1),E=E.slice(0,k)}if(!["timestamp","allFields","allContentFields","allVisibleFields"].includes(y)&&!E||/[\s{}]/.test(E+T))return r("invalidIdentifier");let v=b.transform??"";return delete b.transform,{...e,target:y,identifier:decodeURIComponent(E),selector:decodeURIComponent(T),default:decodeURIComponent(o),transformerId:v,transformerParams:b,isValid:!0}}catch{return r("invalidEncoding")}}function Jd(t,e){var u,f;let r=vi(t);if(!r.isValid)return{expression:r,diagnostic:"invalidExpression"};let n=`${r.target}:${r.identifier}`,i=(g,b)=>Object.prototype.hasOwnProperty.call(g,b);if(!i(e.definitions,n))return{expression:r,diagnostic:r.target==="field"?"missingField":"unknownSource"};let a=e.definitions[n];if(!a.availability.browser)return{expression:r,diagnostic:"forbiddenSource"};if(Object.keys(r.transformerParams).some(g=>["scope","index","rows"].includes(g)))return{expression:r,diagnostic:"invalidRowScope"};if(r.selector&&!((u=a.selectors)!=null&&u.includes(r.selector)))return{expression:r,diagnostic:"invalidSelector"};let o=r.selector?`${n}:${r.selector}`:n;if(!i(e.values,o))return{expression:r,diagnostic:"missingField"};let s=e.values[o];if(r.transformerId){let g=(f=e.transforms)==null?void 0:f[r.transformerId];if(!g)return{expression:r,diagnostic:"unknownTransform"};if(!g.browser)return{expression:r,diagnostic:"forbiddenSource"};if(a.transforms&&!a.transforms.includes(r.transformerId)||!g.accepts(s)||Object.keys(r.transformerParams).some(b=>!g.parameters.includes(b))||(s=g.resolve(s,r.transformerParams),!g.acceptsOutput(s)))return{expression:r,diagnostic:"invalidType"}}else if(Object.keys(r.transformerParams).length)return{expression:r,diagnostic:"invalidExpression"};return(s===""||s==null||Array.isArray(s)&&s.length===0)&&r.default&&(s=r.default),{expression:r,value:s}}const Zd=[{legacyEvent:"onFormieLoaded",canonicalEvent:"formie:mount:after",disposition:"approximate",target:"document"},{legacyEvent:"onFormieInit",canonicalEvent:"formie:mount:after",disposition:"approximate",target:"document"},{legacyEvent:"onFormieReady",canonicalEvent:"formie:mount:after",disposition:"safe"},{legacyEvent:"onAfterFormieSubmit",canonicalEvent:"formie:submit:result",disposition:"safe"},{legacyEvent:"onFormieSubmitError",canonicalEvent:"formie:submit:result",disposition:"safe"},{legacyEvent:"onFormiePageToggle",canonicalEvent:"formie:page:navigate:after",disposition:"safe"},{legacyEvent:"onBeforeFormieSubmit",canonicalEvent:"formie:submit:before",disposition:"approximate"},{legacyEvent:"onFormieValidate",canonicalEvent:"formie:stage:validate:before",disposition:"approximate"},{legacyEvent:"onAfterFormieValidate",canonicalEvent:"formie:stage:validate:after",disposition:"approximate"},{legacyEvent:"onFormieSubmit",canonicalEvent:"formie:submit:after",disposition:"approximate"}];function Qd(t){if(!t)return{enabled:!1,legacyDomEvents:!1,legacyValidatorEvents:!1};if(t===!0)return{enabled:!0,legacyDomEvents:!0,legacyValidatorEvents:!0};const e=t.legacyDomEvents??!0,r=t.legacyValidatorEvents??!0;return{enabled:e||r,legacyDomEvents:e,legacyValidatorEvents:r}}function Qn(t){return t}function Kp(t,e){return`formie:field:${t}:${e}`}function tn(t){return`formie:validator:${t}`}function Gp(t,e){return`formie:address:${t}:${e}`}function Jp(t){return`formie:file-upload:${t}`}function Zp(t,e){return`formie:payment:${t}:${e}`}function Xn(t){return`formie:state:${t}`}function Xd(t,e,r){t.dispatchEvent(new CustomEvent(e,{bubbles:!0,detail:r}))}function ef(t,e){if(t.canonicalEvent!=="formie:submit:result")return!0;const r=e;return t.legacyEvent==="onAfterFormieSubmit"?!!(r!=null&&r.ok):t.legacyEvent==="onFormieSubmitError"?(r==null?void 0:r.ok)===!1:!0}function tf(t,e){const r=e&&typeof e=="object"?e:{},n=typeof r.pageId=="string"?r.pageId:"",i=Array.from(t.querySelectorAll("[data-formie-page-id]")),a=i.findIndex(o=>o.getAttribute("data-formie-page-id")===n);return{data:{nextPageId:n,nextPageIndex:a,totalPages:i.length}}}function rf(t,e,r,n,i){const a=globalThis.Formie||i;return t.legacyEvent==="onFormieLoaded"?{formie:a}:t.legacyEvent==="onFormieInit"?{formie:a,form:i,$form:n,formId:i.id}:t.legacyEvent==="onFormieReady"?{...e&&typeof e=="object"?e:{},form:n,target:r,instance:i}:t.legacyEvent==="onFormiePageToggle"?tf(n,e):e}function nf({target:t,form:e,instance:r,options:n,unbinds:i}){n.legacyDomEvents&&Zd.forEach(a=>{const o=s=>{if(!(s instanceof CustomEvent)||!ef(a,s.detail))return;const u=a.target==="document"?document:e;Xd(u,a.legacyEvent,rf(a,s.detail,t,e,r))};t.addEventListener(Qn(a.canonicalEvent),o),i.push(()=>{t.removeEventListener(Qn(a.canonicalEvent),o)})})}function rn(t,e,r){t.dispatchEvent(new CustomEvent(e,{bubbles:!0,detail:r}))}function Fn(t,e){return!!t&&typeof t=="object"&&t.validator===e}function of({target:t,form:e,validatorDetail:r,options:n,unbinds:i}){if(!n.legacyValidatorEvents||!r)return;const{validator:a,addValidator:o,removeValidator:s}=r,u={...r,form:e,target:t};rn(document,"formieValidatorInitialized",u);const f=p=>{!(p instanceof CustomEvent)||!Fn(p.detail,a)||rn(document,"formieValidatorDestroyed",{...u,...p.detail})},g=p=>{!(p instanceof CustomEvent)||!Fn(p.detail,a)||!(p.target instanceof Element)||e.contains(p.target)&&rn(p.target,"formieValidatorShowError",{...p.detail,addValidator:o,removeValidator:s,form:e,target:t})},b=p=>{!(p instanceof CustomEvent)||!Fn(p.detail,a)||!(p.target instanceof Element)||e.contains(p.target)&&rn(p.target,"formieValidatorClearError",{...p.detail,addValidator:o,removeValidator:s,form:e,target:t})};document.addEventListener("formie:validator:destroy",f),document.addEventListener("formie:validator:show-error",g),document.addEventListener("formie:validator:clear-error",b),i.push(()=>{document.removeEventListener("formie:validator:destroy",f),document.removeEventListener("formie:validator:show-error",g),document.removeEventListener("formie:validator:clear-error",b)})}function Ie(t,e,r){t.dispatchEvent(new CustomEvent(Qn(e),{bubbles:!0,detail:r}))}function yi(t){const e=(t.dataset.formieErrorAriaLive||"polite").trim().toLowerCase();return e==="assertive"||e==="off"?e:"polite"}function af(t,e){return t==="off"?null:e?t:"polite"}function fa(t){return t==="off"?null:t}function wi(t,e){if(e){t.setAttribute("aria-live",e),t.setAttribute("aria-atomic","true");return}t.removeAttribute("aria-live"),t.removeAttribute("aria-atomic")}function ma(){return globalThis}function ha(){return ma().__FORMIE_DEBUG__===!0}function sf(t){ma().__FORMIE_DEBUG__=t}function lf(t,e,r){if(ha()){if(typeof r>"u"){console.log(`[formie:${t}] ${e}`);return}console.log(`[formie:${t}] ${e}`,r)}}function cf(t,e,r){if(ha()){if(typeof r>"u"){console.warn(`[formie:${t}] ${e}`);return}console.warn(`[formie:${t}] ${e}`,r)}}function yt(t,e){const r=e?`${t}:${e}`:t;return{log:(n,i)=>{lf(r,n,i)},warn:(n,i)=>{cf(r,n,i)}}}const Hr=yt("general","page-client-event"),uf="data-formie-client-event",po="data-formie-pending-client-events";function df(t){var e;return typeof window<"u"&&((e=window.CSS)!=null&&e.escape)?window.CSS.escape(t):t.replace(/\\/g,"\\\\").replace(/"/g,'\\"')}function ff(t){var o,s,u;const e=t.querySelector('input[name="pageId"]'),r=(o=e==null?void 0:e.value)==null?void 0:o.trim();if(r)return r;const n=t.querySelector("[data-formie-page]:not([data-formie-page-hidden])"),i=(s=n==null?void 0:n.getAttribute("data-formie-page-id"))==null?void 0:s.trim();if(i)return i;const a=t.querySelector("[data-formie-page]");return((u=a==null?void 0:a.getAttribute("data-formie-page-id"))==null?void 0:u.trim())||null}function mf(t){if(!(t!=null&&t.trim()))return null;try{const e=JSON.parse(t);return e&&typeof e=="object"?e:null}catch{return Hr.warn("Invalid data-formie-client-event JSON.",{rawPreview:t.slice(0,80)}),null}}function hf(t){const e={};return t.forEach(r=>{const n=typeof r.label=="string"?r.label.trim():"";n&&(e[n]=typeof r.value=="string"?r.value:"")}),e}function pf(t){return Array.isArray(t)?t.map(e=>{if(!e||typeof e!="object")return null;const r=e,n=typeof r.event=="string"?r.event.trim():"",i=r.payload&&typeof r.payload=="object"?r.payload:null;return!n||!i?null:{event:n,payload:i}}).filter(e=>e!==null):[]}function xi(t,e){if(!e.length)return;const r=window;r.dataLayer=r.dataLayer||[],e.forEach(n=>{r.dataLayer.push(n.payload),t.dispatchEvent(new CustomEvent("formie:client-event",{bubbles:!0,detail:{event:n.event,payload:n.payload}}))}),Hr.log("Dispatched resolved client events.",{count:e.length,events:e.map(n=>n.event)})}function gf(t){const e=t.getAttribute(po);if(e!=null&&e.trim())try{const r=JSON.parse(e),n=pf(r);n.length&&xi(t,n)}catch{Hr.warn("Invalid pending client events JSON on form element.")}finally{t.removeAttribute(po)}}function pa(t,e){if(e!=="submit")return;const r=ff(t);if(!r){Hr.log("No submitted page id; skipping client event.");return}const n=t.querySelector(`[data-formie-page][data-formie-page-id="${df(r)}"]`);if(!n){Hr.log("No page section for id; skipping client event.",{pageId:r});return}const i=n.getAttribute(uf);if(i===null)return;const a=mf(i);if(!a||!Array.isArray(a.fields))return;const o=hf(a.fields);xi(t,[{event:typeof o.event=="string"&&o.event!==""?o.event:"formPageSubmission",payload:o}])}const bn=new WeakMap,bf="[data-formie-form], [data-formie], form";function vf(t){return t?(Array.isArray(t)?t:[t]).flatMap(r=>String(r).split(/\s+/)).map(r=>r.trim()).filter(Boolean):[]}function Ei(t){return Array.from(new Set(t))}function yf(t){if(!t)return{};const e=bn.get(t);if(e)return e;const r=t.closest(bf);return r?bn.get(r)||{}:{}}function wf(t){const e={};return Object.entries(t||{}).forEach(([r,n])=>{const i=Ei(vf(n));i.length&&(e[r]=i)}),e}function go(t,e,r){const n=wf(e),i=r||(t instanceof HTMLFormElement?t:t.querySelector("form"));return bn.set(t,n),i&&bn.set(i,n),n}function ki(t,e){return yf(t)[e]||[]}function Fe(t,e,...r){const n=Ei(r.flatMap(i=>ki(e,i)));n.length&&t.classList.add(...n)}function Er(t,e,...r){const n=Ei(r.flatMap(i=>ki(e,i)));n.length&&t.classList.remove(...n)}function yr(t,e,r,n){ki(e,r).forEach(i=>{t.classList.toggle(i,n)})}function xf(t,e){if(yr(t,t,"tabError",e),e){t.setAttribute("data-formie-tab-error","true");return}t.removeAttribute("data-formie-tab-error")}function rr(t){const e=new Set;t.querySelectorAll("[data-formie-page]").forEach(r=>{const n=r,i=n.getAttribute("data-formie-page-id");i&&n.querySelector("[data-formie-field-has-error]")&&e.add(i)}),t.querySelectorAll("[data-formie-tab]").forEach(r=>{const n=r,i=n.getAttribute("data-formie-page-id");xf(n,!!i&&e.has(i))})}function Si(t,e){const r=(t.getAttribute("aria-describedby")||"").trim(),n=r?r.split(/\s+/):[];n.includes(e)||n.push(e),t.setAttribute("aria-describedby",n.join(" ").trim())}function Ef(t,e=document){const r=(t.getAttribute("aria-describedby")||"").trim();if(!r)return;const n=r.split(/\s+/).filter(i=>!!i&&!!e.getElementById(i));if(n.length){t.setAttribute("aria-describedby",n.join(" "));return}t.removeAttribute("aria-describedby")}function _i(t,e){t.setAttribute("aria-errormessage",e),Si(t,e)}function ga(t,e=[]){e.forEach(r=>{t.getAttribute("aria-errormessage")===r&&t.removeAttribute("aria-errormessage")}),!e.length&&t.hasAttribute("aria-errormessage")&&t.removeAttribute("aria-errormessage"),Ef(t)}const kf="data-formie-validation-skip";function vt(t){return!!t&&t.hasAttribute(kf)}function ba(t){return Array.from(t.querySelectorAll("[data-formie-field-handle]")).find(r=>r.getAttribute("data-formie-field-has-error")==="true"?!0:r.querySelector("[data-formie-field-error]")!==null)||null}function Sf(t){const e=Array.from(t.querySelectorAll('[aria-invalid="true"]')).find(r=>!vt(r));return e||(Array.from(t.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')).find(r=>!vt(r))??null)}function va(t){return t.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]")}function _f(t){t.querySelectorAll("[data-formie-field-handle]").forEach(e=>{const r=e;if(!(r.getAttribute("data-formie-field-has-error")==="true"||r.querySelector("[data-formie-field-error]")!==null))return;r.setAttribute("data-formie-field-has-error","true"),Fe(r,t,"fieldLayoutError");const i=r.querySelector("[data-formie-field-error]"),a=(i==null?void 0:i.id)||"";r.querySelectorAll("input, select, textarea").forEach(o=>{const s=o;if(vt(s))return;s.setAttribute("aria-invalid","true"),Fe(s,t,"fieldControlError"),s.setAttribute("data-formie-input-has-error","true"),a&&_i(s,a);const u=r.querySelector("[data-formie-instructions]");u!=null&&u.id&&Si(s,u.id)})})}function Af(t){return!!ba(t)||!!va(t)}function Ai(t){const e=ba(t);if(e){const n=Sf(e);if(n){if(n.scrollIntoView({behavior:"smooth",block:"center"}),typeof n.focus=="function")try{n.focus({preventScroll:!0})}catch{n.focus()}return!0}return e.scrollIntoView({behavior:"smooth",block:"center"}),!0}const r=va(t);return r?(r.scrollIntoView({behavior:"smooth",block:"center"}),!0):!1}class Tf{constructor(){this.listeners=new Map}on(e,r){var n;return this.listeners.has(e)||this.listeners.set(e,new Set),(n=this.listeners.get(e))==null||n.add(r),()=>{var i;(i=this.listeners.get(e))==null||i.delete(r)}}async emit(e,r){const n=this.listeners.get(e);if(!(!n||n.size===0))for(const i of n)await i(r)}async emitSafe(e,r){const n=this.listeners.get(e),i={eventName:e,total:(n==null?void 0:n.size)||0,succeeded:0,failed:[]};if(!n||n.size===0)return i;let a=0;for(const o of n){try{await o(r),i.succeeded+=1}catch(s){i.failed.push({index:a,error:s})}a+=1}return i}async emitParallelSafe(e,r){const n=this.listeners.get(e),i={eventName:e,total:(n==null?void 0:n.size)||0,succeeded:0,failed:[]};return!n||n.size===0||(await Promise.allSettled(Array.from(n).map(async o=>o(r)))).forEach((o,s)=>{if(o.status==="fulfilled"){i.succeeded+=1;return}i.failed.push({index:s,error:o.reason})}),i}clear(){this.listeners.clear()}}const ya="CRAFT_CSRF_TOKEN",wa="data-formie-csrf-param",Cf="data-formie-csrf";function xa(){const t=globalThis.Craft,e=t==null?void 0:t.csrfTokenName;return typeof e=="string"&&e.trim()?e.trim():null}function If(t){return typeof CSS<"u"&&typeof CSS.escape=="function"?CSS.escape(t):t.replace(/\\/g,"\\\\").replace(/"/g,'\\"')}function Dn(t,e){const r=t.querySelector(`input[name="${If(e)}"]`);return r instanceof HTMLInputElement?r:null}function Lf(t){var n;if(!t)return null;const e=t.querySelector(`input[${Cf}]`);if(e instanceof HTMLInputElement&&e.name.trim())return e;if(t instanceof Element){const i=(n=t.getAttribute(wa))==null?void 0:n.trim();if(i){const a=Dn(t,i);if(a)return a}}const r=xa();if(r){const i=Dn(t,r);if(i)return i}return Dn(t,ya)}function Ti(t){var i,a;const e=Lf(t),r=((i=e==null?void 0:e.name)==null?void 0:i.trim())||"",n=((a=e==null?void 0:e.value)==null?void 0:a.trim())||"";return!r||!n?null:{name:r,value:n}}function Ea(t,e){const r=Ti(e);r&&t.append(r.name,r.value)}function Qp(t,e){const r=Ti(e);r&&(t[r.name]=r.value)}function Mf(t,e){var a;const r=t.endsWith("[]")?t.slice(0,-2):t;if(!r)return!1;if(r===ya)return!0;const n=xa();if(n&&r===n)return!0;if(e instanceof Element){const o=(a=e.getAttribute(wa))==null?void 0:a.trim();if(o&&r===o)return!0}const i=Ti(e);return!!i&&r===i.name}async function Ci(t,e={}){const r={Accept:"application/json",...e.headers||{}};return delete r["X-Requested-With"],delete r["x-requested-with"],bi(String(t),{method:e.method||"GET",body:e.body??null,signal:e.signal,cache:"no-store",headers:r},e)}async function Ii(t,e={}){const r=await Ci(t,e);if(!r.ok)throw new Error(`Request failed (${r.status}) for ${String(t)}`);return r.json()}async function Xp(t,e={}){const r=await Ci(t,e);if(!r.ok)throw new Error(`Request failed (${r.status}) for ${String(t)}`);return r.text()}const lt=yt("general","transport");function Nf(t){const e={};return["theme","themeConfig","locale","siteId"].forEach(r=>{t[r]!==void 0&&(e[r]=t[r])}),e}function Rf(t,e){const r=t.success===!0,n=t.keepSubmitLoading===!0,i=t.errors,a=Object.fromEntries(Object.entries(i&&typeof i=="object"?i:{}).map(([b,p])=>[b,Array.isArray(p)?p.filter(y=>typeof y=="string"):[]])),o=a.form||[],s={};Object.entries(a).forEach(([b,p])=>{b!=="form"&&(s[b]=p)});const u=!r&&o.length===0&&Object.keys(s).length>0?[e||"Submission failed."]:o,f=!r&&n&&u.length===0&&Object.keys(s).length===0;return{ok:r,outcome:typeof t.outcome=="string"?t.outcome:void 0,version:typeof t.version=="number"?t.version:null,submissionUid:typeof t.submissionUid=="string"?t.submissionUid:null,errors:t.errors,session:t.session,completion:t.completion,action:t.submitAction==="back"||t.submitAction==="save"||t.submitAction==="submit"?t.submitAction:void 0,message:t.submitActionMessage||(r?"Submission completed.":f?"":u[0]||"Submission failed."),code:r?void 0:String(t.code||"SUBMIT_ERROR"),keepSubmitLoading:n,fieldErrors:Object.keys(s).length?s:void 0,formErrors:u.length?u:void 0,nextPage:t.nextPageId?{id:String(t.nextPageId)}:null,redirect:t.redirectUrl?{url:String(t.redirectUrl),target:t.submitActionTab==="new-tab"?"new-tab":"same-tab"}:null,submitData:Array.isArray(t.submitData)?t.submitData:void 0,clientEvents:Array.isArray(t.clientEvents)?t.clientEvents:void 0,meta:t}}async function Of(t,e,r={},n={}){const i=JSON.stringify({handle:e,renderOptions:r});lt.log("requestRender start.",{endpoint:t,handle:e});const a=await Ii(t,{...n,method:"POST",body:i,headers:{"Content-Type":"application/json"}});return lt.log("requestRender complete.",{hasHtml:!!a.html}),a}async function Pf(t,e,r={},n={}){var u;const a=JSON.stringify({query:`
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`,variables:{handle:e,input:Nf(r)}});lt.log("requestGraphqlRender start.",{endpoint:t,handle:e});const o=await Ii(t,{...n,method:"POST",body:a,headers:{"Content-Type":"application/json"}});if(Array.isArray(o.errors)&&o.errors.length>0)throw new Error(o.errors.map(f=>f.message||"Unknown GraphQL error").join("; "));if(!((u=o.data)!=null&&u.formieHtmlForm))throw new Error(`Form not found for handle "${e}".`);const s=o.data.formieHtmlForm;return lt.log("requestGraphqlRender complete.",{hasHtml:!!s.html}),s}async function Li(t,e,r,n={},i){const a=new URL(t,window.location.origin);a.searchParams.set("handle",e),r&&a.searchParams.set("renderId",r),i&&a.searchParams.set("requestToken",i),lt.log("requestRefreshTokens start.",{endpoint:a.toString(),handle:e,hasRenderId:!!r});const o=await Ii(a.toString(),n);return lt.log("requestRefreshTokens complete.",{hasRefreshTokens:!!o.refreshTokens}),o.refreshTokens||o}async function $f(t,e,r){var s;const n=new URL(t,window.location.origin),i=new FormData;if(r&&i.append("pageId",r),e){["handle","renderId","draftContextToken","draftContext","progressId","requestToken","expectedVersion"].forEach(f=>{var p;const g=e.querySelector(`input[name="${f}"]`),b=(p=g==null?void 0:g.value)==null?void 0:p.trim();b&&i.append(f,b)}),Ea(i,e);for(const[f,g]of new FormData(e))f.startsWith("fields[")&&i.append(f,g)}lt.log("requestSetPage start.",{requestUrl:n.toString(),pageId:r||null});const o=await(await Ci(n.toString(),{method:"POST",body:i,profile:e==null?void 0:e.dataset.formieRequestProfile})).json();if(e&&o.session){const u=o.session,f=e.querySelector('input[name="expectedVersion"]'),g=e.querySelector('input[name="requestToken"]');f&&(f.value=String(u.version)),g&&((s=u.tokens)!=null&&s.request)&&(g.value=u.tokens.request)}return lt.log("requestSetPage complete.",o),o}function Ff(t,e){const r=new URL(t,window.location.origin),n=new FormData;["handle","renderId","draftContextToken","draftContext"].forEach(a=>{var u;const o=e.querySelector(`input[name="${a}"]`),s=(u=o==null?void 0:o.value)==null?void 0:u.trim();s&&n.append(a,s)}),Ea(n,e),lt.log("clearSubmissionOnUnload start.",{requestUrl:r.toString()});try{if(e.dataset.formieRequestProfile!=="cross-origin-public"&&typeof navigator.sendBeacon=="function"&&navigator.sendBeacon(r.toString(),n))return}catch{}bi(r.toString(),{method:"POST",body:n,keepalive:!0,headers:{Accept:"application/json"}},{profile:e.dataset.formieRequestProfile})}async function Df(t,e){var f,g;const r=(t.getAttribute("method")||"POST").toUpperCase(),n=t.getAttribute("action")||window.location.href,i=((f=t.dataset.formieErrorMessage)==null?void 0:f.trim())||"Submission failed.";lt.log("submitForm start.",{method:r,action:n,submitAction:e.get("submitAction")});const a=await bi(n,{method:r,body:e,headers:{Accept:"application/json"}},{profile:t.dataset.formieRequestProfile}),o=a.headers.get("content-type")||"";if(!o.includes("application/json"))return a.ok?(lt.log("submitForm non-JSON success response.",{status:a.status,contentType:o}),{ok:!0,message:"Submission completed."}):(lt.warn("submitForm non-JSON HTTP error.",{status:a.status,contentType:o}),{ok:!1,code:"HTTP_ERROR",message:`Request failed (${a.status}).`,formErrors:[`Request failed (${a.status}).`]});const s=await a.json(),u=Rf(s,i);return lt.log("submitForm JSON response normalized.",{ok:u.ok,code:u.code,hasRedirect:!!((g=u.redirect)!=null&&g.url),hasSubmitData:Array.isArray(u.submitData)&&u.submitData.length>0}),u}function Mi(t){return Array.from(t.querySelectorAll("[data-formie-page]"))}function _n(t){const e=Mi(t);if(!e.length)return{scope:t,final:!0};const r=e.find(n=>!n.hasAttribute("data-formie-page-hidden"))||e[e.length-1];return{scope:r,final:r===e[e.length-1]}}const zf=["prepare","validate","challenge","payment","send","result"],Vf=["prepare","validate","challenge","payment"],Oe=yt("general","pipeline");function zn(t,e){return{ok:!1,stage:t,code:"ABORTED",message:e||"Submission aborted.",formErrors:[e||"Submission aborted."]}}function ka(t){return t instanceof HTMLInputElement||t instanceof HTMLSelectElement||t instanceof HTMLTextAreaElement}function Sa(t){return!(!t.name||t.disabled||t instanceof HTMLInputElement&&(t.type==="submit"||t.type==="button"||t.type==="reset"||t.type==="image"||(t.type==="checkbox"||t.type==="radio")&&!t.checked||t.type==="file"&&(!t.files||t.files.length===0)))}function _a(t,e){if(e instanceof HTMLInputElement){if(e.type==="file"){Array.from(e.files||[]).forEach(r=>{t.append(e.name,r)});return}t.append(e.name,e.value);return}if(e instanceof HTMLSelectElement&&e.multiple){Array.from(e.selectedOptions).forEach(r=>{t.append(e.name,r.value)});return}t.append(e.name,e.value)}function jf(t,e){e.querySelectorAll("input, select, textarea").forEach(r=>{const n=ka(r)?r:null;!n||n.closest("[data-formie-page]")||Sa(n)&&_a(t,n)})}function qf(t,e){const r=new Set;return e.querySelectorAll("input, select, textarea").forEach(n=>{const i=ka(n)?n:null;!i||!i.name||i.disabled||i instanceof HTMLInputElement&&(i.type==="submit"||i.type==="button"||i.type==="reset"||i.type==="image")||(i.name.startsWith("fields[")&&r.add(i.name),Sa(i)&&_a(t,i))}),r}function Hf(t,e){e.forEach(r=>{t.has(r)||t.append(r,"")})}function bo(t,e){const r=Mi(t),n=r.find(o=>!o.hasAttribute("data-formie-page-hidden"))||null;if(!r.length||!n){const o=new FormData(t);return o.set("submitAction",e),o}const i=new FormData;jf(i,t);const a=qf(i,n);return Hf(i,a),i.set("submitAction",e),i}function Uf(t,e){if(e!=="submit")return!1;const r=Mi(t);return r.length?(r.find(i=>!i.hasAttribute("data-formie-page-hidden"))||r[r.length-1])===r[r.length-1]:!0}async function Aa(t,e,r,n={}){Oe.log("Starting submit pipeline.",{action:e,preflightOnly:n.preflightOnly===!0});let i=!1,a,o=null;const s=Uf(t,e),u={form:t,action:e,formData:bo(t,e),abort:p=>{i=!0,a=p,Oe.warn("Pipeline aborted.",{reason:p})},isAborted:()=>i,abortReason:()=>a},f={prepare:async p=>{const y=p.form.querySelector('input[name="submitAction"]');return y&&(y.value=p.action),p.formData.set("submitAction",p.action),null},validate:async p=>{var y;if(p.action!=="submit"||n.validateOnSubmit===!1)return null;if(n.validator){const{scope:E,final:T}=_n(p.form),v=n.validator.submit(T?p.form:E,{final:T});if(v.length>0){const k=(y=v[0])==null?void 0:y.input;if(k){k.scrollIntoView({behavior:"smooth",block:"center"});try{k.focus({preventScroll:!0})}catch{k.focus()}}return{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:n.validator.config.errorMessage||"Validation failed.",fieldErrors:n.validator.getFieldErrors(v),formErrors:[n.validator.config.errorMessage||"Validation failed."]}}return null}if(!p.form.checkValidity()){const E=p.form.querySelector(":invalid");return E==null||E.focus(),{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:"Validation failed.",formErrors:["Validation failed."]}}return null},challenge:async()=>null,payment:async()=>null,send:async p=>{p.formData=bo(p.form,p.action);const y=await Df(p.form,p.formData);return o=y,y},result:async p=>{var y;return o&&o.ok&&(y=o.redirect)!=null&&y.url&&(o.redirect.target==="new-tab"?window.open(o.redirect.url,"_blank","noopener,noreferrer"):window.location.href=o.redirect.url),null}};{const p=await r.emitSafe("formie:submit:before",u);p.failed.length>0&&Oe.warn("Submit before listeners failed.",{eventName:p.eventName,failed:p.failed.length})}if(s){const p=await r.emitSafe("formie:submit:final:before",u);p.failed.length>0&&Oe.warn("Final submit before listeners failed.",{eventName:p.eventName,failed:p.failed.length})}const g=n.preflightOnly?Vf:zf;for(const p of g){if(Oe.log("Stage start.",{stage:p,action:e}),i)return Oe.warn("Stage skipped due to abort.",{stage:p,reason:a}),zn(p,a);{const E=await r.emitSafe(`formie:stage:${p}:before`,{...u,stage:p});E.failed.length>0&&Oe.warn("Stage before listeners failed.",{stage:p,failed:E.failed.length})}if(i){const E=zn(p,a);{const T=await r.emitSafe("formie:submit:after",E);T.failed.length>0&&Oe.warn("Submit after listeners failed (abort before stage).",{stage:p,failed:T.failed.length})}if(s){const T=await r.emitSafe("formie:submit:final:after",E);T.failed.length>0&&Oe.warn("Final submit after listeners failed (abort before stage).",{stage:p,failed:T.failed.length})}return Oe.warn("Aborted after stage before-hooks.",{stage:p,reason:a}),E}const y=await f[p](u);Oe.log("Stage runner complete.",{stage:p,hasResult:!!y,ok:y?y.ok:void 0,code:y==null?void 0:y.code});{const E=await r.emitSafe(`formie:stage:${p}:after`,{...u,stage:p,result:y});E.failed.length>0&&Oe.warn("Stage after listeners failed.",{stage:p,failed:E.failed.length})}if(i){const E=zn(p,a);{const T=await r.emitSafe("formie:submit:after",E);T.failed.length>0&&Oe.warn("Submit after listeners failed (abort after stage).",{stage:p,failed:T.failed.length})}if(s){const T=await r.emitSafe("formie:submit:final:after",E);T.failed.length>0&&Oe.warn("Final submit after listeners failed (abort after stage).",{stage:p,failed:T.failed.length})}return Oe.warn("Aborted after stage after-hooks.",{stage:p,reason:a}),E}if(y&&!y.ok){{const E=await r.emitSafe("formie:submit:after",y);E.failed.length>0&&Oe.warn("Submit after listeners failed (failed stage).",{stage:p,failed:E.failed.length})}if(s){const E=await r.emitSafe("formie:submit:final:after",y);E.failed.length>0&&Oe.warn("Final submit after listeners failed (failed stage).",{stage:p,failed:E.failed.length})}return Oe.warn("Pipeline short-circuited by failed stage.",{stage:p,code:y.code,message:y.message}),y}}const b=o||{ok:!0,stage:n.preflightOnly?"payment":"result",code:n.preflightOnly?"PREFLIGHT_COMPLETE":void 0,message:n.preflightOnly?"Submission preflight completed.":"Submission completed."};{const p=await r.emitSafe("formie:submit:after",b);p.failed.length>0&&Oe.warn("Submit after listeners failed (success).",{failed:p.failed.length})}if(s){const p=await r.emitSafe("formie:submit:final:after",b);p.failed.length>0&&Oe.warn("Final submit after listeners failed (success).",{failed:p.failed.length})}return Oe.log("Pipeline completed.",{ok:b.ok,stage:b.stage,code:b.code}),b}function Bf(t){var n;const e=t.querySelector("[data-formie-field-layout]");return((n=e==null?void 0:e.getAttribute("data-formie-error-position"))==null?void 0:n.trim())==="above"?"above":"below"}function Ta(t,e){const r=t.querySelector("[data-formie-field-errors]");if(r)return r;const n=t.querySelector("[data-formie-field-content]"),i=t.querySelector("[data-formie-field-control]"),a=Bf(t),o=document.createElement("div");return o.setAttribute("data-formie-field-errors","true"),e==null||e(o),n&&i?a==="above"?n.insertBefore(o,i):n.appendChild(o):t.appendChild(o),o}const Yf={rule:({input:t,getRule:e})=>{const r=e("email");return!r||xr(t.value,{...typeof r=="object"?r:{},type:"email"})===null},message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-validation-email-message")??t.getAttribute("data-formie-pattern-email-message")??t.getAttribute("data-pattern-email-message")??r("{label} is not a valid email address.",{label:e})};function Wf(t){var e,r,n;return((n=(r=(e=t==null?void 0:t.querySelector("[data-formie-field-label]"))==null?void 0:e.childNodes[0])==null?void 0:r.textContent)==null?void 0:n.trim())||""}function vo(t){const e=t.getRule("match");if(!e||e===!0||typeof e!="object"||!t.field)return null;const r=typeof e.fieldHandle=="string"?e.fieldHandle.trim():"";if(!r)return null;const n=t.form.querySelector(`[data-formie-field-handle="${r}"]`);return n?Array.from(n.querySelectorAll(t.config.fieldsSelector)).find(i=>(i instanceof HTMLInputElement||i instanceof HTMLSelectElement||i instanceof HTMLTextAreaElement)&&!vt(i))??null:null}const Kf={rule:t=>{const e=vo(t);return e?xr(t.input.value,{type:"match"},{comparison:e.value})===null:!0},message:t=>{const e=vo(t),r=e==null?void 0:e.closest("[data-formie-field-handle]"),n=Wf(r);return t.input.getAttribute("data-formie-validation-match-message")??t.t("{label} must match {value}.",{label:t.label,value:n})}},Gf={rule:({input:t,getRule:e})=>{const r=e("number");return!r||xr(t.value,{...typeof r=="object"?r:{},type:"number"})===null},message:({input:t,label:e,getRule:r,t:n})=>{const i=r("number");return xr(t.value,{...typeof i=="object"?i:{},type:"number"},{label:e})??n("{label} is not a valid number.",{label:e})}},Jf={rule:({input:t,getRule:e})=>{var r;if(!e("required")||t.type==="hidden")return!0;if(t.type==="checkbox"||t.type==="radio"){const n=((r=t.form)==null?void 0:r.querySelectorAll(`[name="${t.name}"]:not([type="hidden"]):not([disabled])`))||[];return n.length?Array.from(n).some(i=>i instanceof HTMLInputElement&&i.checked):t instanceof HTMLInputElement?t.checked:!0}return xr(t.value,{type:"required"})===null},message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-required-message")??t.getAttribute("data-required-message")??r("{label} cannot be blank.",{label:e})},Zf={rule:({input:t,getRule:e})=>{const r=e("url");return!r||xr(t.value,{...typeof r=="object"?r:{},type:"url"})===null},message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-pattern-url-message")??t.getAttribute("data-pattern-url-message")??r("{label} is not a valid URL.",{label:e})},Qf={required:Jf,email:Yf,url:Zf,number:Gf,match:Kf};function Ca(){return window.FormieTranslations||{}}function Xf(){var r;if(typeof document>"u")return;const t=Array.from(document.querySelectorAll('script[type="application/json"][data-formie-translations]:not([data-formie-translations-loaded="true"])'));if(t.length===0)return;let e=null;for(const n of t){n.dataset.formieTranslationsLoaded="true";const i=(r=n.textContent)==null?void 0:r.trim();if(i)try{const a=JSON.parse(i);if(!a||Array.isArray(a)||typeof a!="object")continue;e={...e??Ca(),...a}}catch{continue}}e&&(window.FormieTranslations=e)}function em(){return Xf(),Ca()}function tm(t){const e={};let r=0;for(;r<t.length;){for(;r<t.length&&/\s/.test(t[r]);)r++;if(r>=t.length)break;const n=t.slice(r).match(/^(\w+|=\d+)\{/);if(!n)break;const i=n[1];r+=n[0].length;let a=1;const o=r;for(;r<t.length&&a>0;)t[r]==="{"?a++:t[r]==="}"&&a--,a>0&&r++;e[i]=t.slice(o,r),r++}return e}function rm(t,e){const r=`=${t}`;if(Object.prototype.hasOwnProperty.call(e,r))return e[r];if(typeof Intl<"u"&&typeof Intl.PluralRules=="function"){const i=new Intl.PluralRules().select(t);if(Object.prototype.hasOwnProperty.call(e,i))return e[i]}if(t===1&&Object.prototype.hasOwnProperty.call(e,"one"))return e.one;if(Object.prototype.hasOwnProperty.call(e,"other"))return e.other;const n=Object.keys(e)[0];return n?e[n]:""}function nm(t,e){const r=t.slice(e).match(/^\{(\w+),\s*plural,\s*/);if(!r)return null;const n=r[1],i=e+r[0].length;let a=i;for(;a<t.length;){for(;a<t.length&&/\s/.test(t[a]);)a++;if(a>=t.length||t[a]==="}")break;const o=t.slice(a).match(/^(\w+|=\d+)\{/);if(!o)return null;a+=o[0].length;let s=1;for(;a<t.length&&s>0;)t[a]==="{"?s++:t[a]==="}"&&s--,s>0&&a++;a++}return a>=t.length||t[a]!=="}"?null:{param:n,body:t.slice(i,a),endIndex:a}}function im(t,e){let r="",n=0;for(;n<t.length;){if(t[n]!=="{"){r+=t[n],n++;continue}const i=nm(t,n);if(!i){r+=t[n],n++;continue}const a=e[i.param],o=typeof a=="number"?a:Number.parseInt(String(a??""),10)||0,s=tm(i.body);let u=rm(o,s);u=u.replace(/#/g,String(o)),r+=u,n=i.endIndex+1}return r}function om(t,e){return t.replace(/\{(\w+),\s*number\}/g,(r,n)=>{if(!Object.prototype.hasOwnProperty.call(e,n))return r;const i=e[n];return typeof i=="number"?i.toLocaleString():String(i)})}function am(t,e){return t.replace(/\{(\w+)\}/g,(r,n)=>Object.prototype.hasOwnProperty.call(e,n)?String(e[n]):r)}function Ct(t,e={}){let r=em()[t]||t;return r=im(r,e),r=om(r,e),r=am(r,e),r}const sm={email:/^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,url:/^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,number:/^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,color:/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,date:/(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,time:/^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,month:/^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/},Jt=yt("general","validator");function Mr(t){return!!t&&(t instanceof HTMLInputElement||t instanceof HTMLSelectElement||t instanceof HTMLTextAreaElement)}function yo(t){return!!(t.offsetWidth||t.offsetHeight||t.getClientRects().length)}class lm{constructor(e,r={}){this.errors=[],this.validators={},this.boundListeners=!1,this.activated=new WeakSet,this.submitted=!1,this.initialValues=new WeakMap,this.form=e,this.onBlur=this.blurHandler.bind(this),this.onChange=this.changeHandler.bind(this),this.onInput=this.inputHandler.bind(this),this.config={live:!1,errorAriaLive:"polite",errorMessage:"",fieldContainerErrorClass:[],inputErrorClass:[],messagesClass:[],messageClass:[],fieldsSelector:'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',patterns:sm,...r},Object.entries(Qf).forEach(([n,i])=>{this.addValidator(n,i.rule,i.message)}),this.init()}init(){Jt.log("Initializing validator.",{formId:this.form.id||null,live:this.config.live}),this.form.setAttribute("novalidate","true"),this.inputs().forEach(e=>{this.initialValues.set(e,this.getInputValue(e))}),this.config.live&&this.addEventListeners(),this.emitEvent(document,tn("ready"),{validator:this})}inputs(e=null){if(Mr(e))return vt(e)?[]:[e];const r=e||this.form;return Array.from(r.querySelectorAll(this.config.fieldsSelector)).filter(n=>Mr(n)&&!vt(n))}getInputValue(e){var r;return e instanceof HTMLInputElement&&(e.type==="checkbox"||e.type==="radio")?e.checked:e instanceof HTMLInputElement&&e.type==="file"?(r=e.files)!=null&&r.length?Array.from(e.files).map(n=>n.name).join("|"):"":e.value??""}isDirty(e){return this.initialValues.has(e)?this.getInputValue(e)!==this.initialValues.get(e):(this.initialValues.set(e,this.getInputValue(e)),!1)}shouldShowError(e){return this.submitted||this.activated.has(e)}isValid(e=null,r={}){return this.validate(e,r).length===0}validate(e=null,r={}){this.errors=[];const n=new Set;return this.inputs(e).forEach(i=>{let a=!1;if(!this.isVisible(i,r))return;const o=i.closest("[data-formie-field-handle]"),s=i instanceof HTMLInputElement&&(i.type==="checkbox"||i.type==="radio")?`${(o==null?void 0:o.getAttribute("data-formie-field-handle"))||""}:${i.name}`:null;if(s){if(n.has(s))return;n.add(s)}this.shouldShowError(i)&&this.removeError(i);const u=this.getValidatorCallbackOptions(i);Object.entries(this.validators).forEach(([f,g])=>{var p;if(!g.validate(u)){const y=this.getErrorMessage(i,f,g,u);this.shouldShowError(i)&&!a&&this.showError(i,f,y),this.errors.push({input:i,field:u.field,validator:f,message:y,handle:((p=u.field)==null?void 0:p.getAttribute("data-formie-field-handle"))||null,result:!1}),a=!0}}),!a&&this.shouldShowError(i)&&this.removeError(i)}),Jt.log("Validation pass complete.",{errorCount:this.errors.length,includeHiddenPages:r.includeHiddenPages===!0}),this.errors}removeAllErrors(){this.inputs().forEach(e=>{this.removeError(e)})}removeError(e){var a;const r=e.closest("[data-formie-field-handle]");if(!r){e.removeAttribute("aria-invalid");return}const n=r.querySelector("[data-formie-field-errors]"),i=Array.from(r.querySelectorAll("[data-formie-field-error]")).map(o=>o.id).filter(Boolean);r.querySelectorAll("[data-formie-field-error]").forEach(o=>{o.remove()}),n&&(n.innerHTML=""),r.querySelectorAll("input, select, textarea").forEach(o=>{const s=o;s.removeAttribute("aria-invalid"),this.config.inputErrorClass.length&&s.classList.remove(...this.config.inputErrorClass),s.removeAttribute("data-formie-input-has-error"),ga(s,i)});for(let o=r;o;o=(a=o.parentElement)==null?void 0:a.closest("[data-formie-field-handle]"))this.config.fieldContainerErrorClass.length&&o.classList.remove(...this.config.fieldContainerErrorClass),o.removeAttribute("data-formie-field-has-error");this.emitEvent(e,tn("clear-error"),{validator:this}),rr(this.form)}showError(e,r,n){var f;const i=e.closest("[data-formie-field-handle]");if(!i)return;let a=i.querySelector("[data-formie-field-errors]");a||(a=Ta(i,g=>{this.config.messagesClass.length&&g.classList.add(...this.config.messagesClass)})),this.config.messagesClass.length&&a.classList.add(...this.config.messagesClass),a.innerHTML="";const o=i.getAttribute("data-formie-field-handle")||"field",s=`${o}-error`;a.id=a.id||`${o}-errors`,wi(a,af(this.config.errorAriaLive,this.submitted));const u=document.createElement("div");u.setAttribute("data-formie-field-error","true"),u.setAttribute(`data-formie-field-error-${r}`,"true"),u.setAttribute("id",s),this.config.messageClass.length&&u.classList.add(...this.config.messageClass),u.textContent=n,a.appendChild(u),i.setAttribute("data-formie-field-has-error","true"),i.querySelectorAll("input, select, textarea").forEach(g=>{const b=g;vt(b)||(b.setAttribute("aria-invalid","true"),this.config.inputErrorClass.length&&b.classList.add(...this.config.inputErrorClass),b.setAttribute("data-formie-input-has-error","true"),_i(b,s))});for(let g=i;g;g=(f=g.parentElement)==null?void 0:f.closest("[data-formie-field-handle]"))this.config.fieldContainerErrorClass.length&&g.classList.add(...this.config.fieldContainerErrorClass),g.setAttribute("data-formie-field-has-error","true");this.emitEvent(e,tn("show-error"),{validator:this,validatorName:r,errorMessage:n}),rr(this.form)}getValidatorCallbackOptions(e){var a,o,s;const r=e.closest("[data-formie-field-handle]"),n=((s=(o=(a=r==null?void 0:r.querySelector("[data-formie-field-label]"))==null?void 0:a.childNodes[0])==null?void 0:o.textContent)==null?void 0:s.trim())??"",i=this.parseValidationRules(r==null?void 0:r.getAttribute("data-formie-validation"));return{t:Ct,input:e,label:n,field:r,form:this.form,config:this.config,rules:i,getRule:u=>this.getRule(r,u)}}getErrorMessage(e,r,n,i){return(typeof n.errorMessage=="function"?n.errorMessage(i):n.errorMessage)??Ct("{label} is invalid.",{label:i.label})}getErrors(){return this.errors}getFieldErrors(e=this.errors){const r={};return e.forEach(n=>{var i;!n.handle||(i=r[n.handle])!=null&&i.length||(r[n.handle]=[n.message])}),r}getRule(e,r){if(!e)return!1;const n=this.parseValidationRules(e.getAttribute("data-formie-validation"));return Object.prototype.hasOwnProperty.call(n,r)?n[r]:!1}parseValidationRules(e){const r={};if(!e)return r;let n=null;try{n=JSON.parse(e)}catch{return Jt.warn("Invalid validation rules payload.",{formId:this.form.id||null}),r}return Array.isArray(n)&&n.forEach(i=>{if(!i||typeof i!="object"||Array.isArray(i))return;const a=i,o=typeof a.type=="string"?a.type.trim():"";o&&(r[o]=a)}),r}destroy(){Jt.log("Destroying validator.",{formId:this.form.id||null}),this.removeEventListeners(),this.form.removeAttribute("novalidate"),this.emitEvent(document,tn("destroy"),{validator:this})}isVisible(e,r={}){if(e.disabled||e.hasAttribute("data-formie-conditions-disabled")||e.closest("[data-formie-conditions-disabled]")||e.closest("[data-formie-conditionally-hidden]"))return!1;if(e.closest("[data-formie-page-hidden]"))return!!r.includeHiddenPages;const n=e.closest("[data-formie-field-handle]"),i=n==null?void 0:n.querySelector("[data-formie-rich-text]");return i instanceof HTMLElement?yo(i):yo(e)}blurHandler(e){var r;!(e.target instanceof HTMLElement)||!Mr(e.target)||vt(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form))||e instanceof CustomEvent||e.target instanceof HTMLInputElement&&e.target.type==="file"||e.target instanceof HTMLInputElement&&(e.target.type==="checkbox"||e.target.type==="radio")||(this.isDirty(e.target)&&this.activated.add(e.target),this.shouldShowError(e.target)&&this.validate(e.target))}changeHandler(e){var r;if(!(!(e.target instanceof HTMLElement)||!Mr(e.target)||vt(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form)))&&!(e instanceof CustomEvent)){if(e.target instanceof HTMLSelectElement){this.activated.add(e.target),this.validate(e.target);return}e.target instanceof HTMLInputElement&&(e.target.type!=="file"&&e.target.type!=="checkbox"&&e.target.type!=="radio"||(this.activated.add(e.target),this.validate(e.target)))}}inputHandler(e){var r;!(e.target instanceof HTMLElement)||!Mr(e.target)||vt(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form))||e instanceof CustomEvent||e.target instanceof HTMLInputElement&&(e.target.type==="checkbox"||e.target.type==="radio")||this.shouldShowError(e.target)&&this.validate(e.target)}submit(e=null,{final:r=!1}={}){return this.submitted=!0,Jt.log("Submit validation requested.",{final:r}),this.boundListeners||this.addEventListeners(),this.removeAllErrors(),this.validate(e,{includeHiddenPages:r})}resetLiveState(){this.submitted=!1,this.activated=new WeakSet,this.errors=[],this.removeAllErrors()}addEventListeners(){this.boundListeners||(this.form.addEventListener("blur",this.onBlur,!0),this.form.addEventListener("change",this.onChange,!1),this.form.addEventListener("input",this.onInput,!1),this.boundListeners=!0,Jt.log("Event listeners attached."))}removeEventListeners(){this.form.removeEventListener("blur",this.onBlur,!0),this.form.removeEventListener("change",this.onChange,!1),this.form.removeEventListener("input",this.onInput,!1),this.boundListeners=!1,Jt.log("Event listeners removed.")}emitEvent(e,r,n={}){e.dispatchEvent(new CustomEvent(r,{bubbles:!0,detail:n}))}addValidator(e,r,n){this.validators[e]={validate:r,errorMessage:n}}removeValidator(e){delete this.validators[e]}}const nn="data-formie-submit-validation-disabled",Vn="data-formie-preserve-disabled",cm="data-formie-submit-ready";function Ia(t){return t.dataset.formieDisableSubmitUntilValid==="true"}function um(t){return Array.from(t.querySelectorAll('button[data-formie-action="submit"]')).filter(e=>e instanceof HTMLButtonElement)}function dm(t){return!t.hasAttribute("data-formie-conditionally-hidden")&&!t.closest("[data-formie-conditionally-hidden]")}function Ni(t,e){if(!Ia(t)||t.getAttribute("data-formie-loading")==="true")return;const{scope:r,final:n}=_n(t),i=e.isValid(r,{includeHiddenPages:n});t.setAttribute(cm,i?"true":"false"),um(t).forEach(a=>{if(dm(a)){if(i){if(!a.hasAttribute(nn))return;a.hasAttribute(Vn)?(a.disabled=!0,a.removeAttribute(Vn)):a.disabled=!1,a.removeAttribute(nn);return}a.hasAttribute(nn)||(a.disabled&&a.setAttribute(Vn,"true"),a.setAttribute(nn,"true")),a.disabled=!0}})}function fm(t,e,r){if(!Ia(t))return()=>{};let n=!1;const i=()=>{n||(n=!0,queueMicrotask(()=>{n=!1,Ni(t,e)}))};i();const a=()=>{i()};t.addEventListener("input",a,!0),t.addEventListener("change",a,!0);const o=()=>{window.setTimeout(()=>{i()},0)};t.addEventListener("reset",o);const s=()=>{i()};r.addEventListener("formie:conditions:evaluated",s);const u=new MutationObserver(f=>{f.some(b=>{if(b.type==="attributes"){const p=b.attributeName||"";return p==="data-formie-page-hidden"||p==="data-formie-conditionally-hidden"||p==="data-formie-loading"||p==="disabled"}return b.type==="childList"})&&i()});return u.observe(t,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["data-formie-page-hidden","data-formie-conditionally-hidden","data-formie-loading","disabled"]}),()=>{t.removeEventListener("input",a,!0),t.removeEventListener("change",a,!0),t.removeEventListener("reset",o),r.removeEventListener("formie:conditions:evaluated",s),u.disconnect()}}const mm="STALE_SUBMISSION_STATE",wo=new WeakMap,vn=new WeakMap,Ft=yt("general","submit-result");function zr(t,e,r){let n=t.querySelector(`input[name="${e}"]`);n||(n=document.createElement("input"),n.type="hidden",n.name=e,t.appendChild(n)),n.value=r}function xo(t,e){t.setAttribute("data-formie-internal-navigation",e)}function Rr(t,e){const r=t.querySelector(`input[name="${e}"]`);r==null||r.remove()}function hm(t,e){try{const r=new URL(t,window.location.href);return r.searchParams.delete(e),r.toString()}catch{return t}}function pm(t){try{return new URL(t,window.location.href).origin===window.location.origin}catch{return!1}}function La(t){return Array.from(t.querySelectorAll("[data-formie-page]"))}function gm(t){return Array.from(t.querySelectorAll("[data-formie-tab]"))}function bm(t,e,r){return e<0||r<1?0:(t.dataset.formieProgressCalculation==="page-position"?"page-position":"completion")==="page-position"?Math.round((e+1)/r*100):Math.round(e/r*100)}function vm(t){return t<=0?"start":t>=100?"end":"middle"}function ym(t){return(t.dataset.formieSubmitAction||"").trim()}function Eo(t,e){var n;if(e.completion)return String(e.completion.behavior);const r=(n=e.meta)==null?void 0:n.effectiveSubmitAction;return typeof r=="string"&&r.trim()!==""?r.trim():ym(t)}function ko(t){const e=t.dataset.formieSubmitActionFormHide;if(e===void 0)return!1;const r=e.trim().toLowerCase();return r==="true"||r==="1"||r===""}function Ri(t,e){const r=["[data-formie-form-header]","[data-formie-form-navigation]","[data-formie-form-body]","[data-formie-form-footer]"];t.toggleAttribute("data-formie-form-hidden",e),r.forEach(n=>{t.querySelectorAll(n).forEach(i=>{const a=i;e?a.hidden=!0:a.hidden=!1})})}function At(t){const e=wo.get(t);typeof e=="number"&&(window.clearTimeout(e),wo.delete(t))}function wm(t,e){vn.has(t)||vn.set(t,t.innerHTML),t.textContent=e}function ei(t){const e=vn.get(t);e!==void 0&&(t.innerHTML=e,vn.delete(t))}function xm(t,e){const r=t.querySelector("[data-formie-progress-bar]"),n=t.querySelector("[data-formie-progress-value]");r&&(r.style.width=`${e}%`,r.setAttribute("aria-valuenow",`${e}`),r.setAttribute("data-formie-progress-state",vm(e)),n&&(n.textContent=`${e}%`,n.setAttribute("data-formie-progress-value",`${e}`)))}function Em(t,e){var n;if(!e)return;const r=(t.dataset.formieLoadingIndicator||"").trim();if(r){if(e.setAttribute("data-formie-loading-indicator",r),r==="spinner"){yr(e,t,"loading",!0),ei(e),e.removeAttribute("data-formie-loading-text");return}if(r==="text"){const i=(t.dataset.formieLoadingIndicatorText||"").trim(),a=((n=e.textContent)==null?void 0:n.trim())||"",o=i||a;e.setAttribute("data-formie-loading-text",o),wm(e,o);return}ei(e),e.removeAttribute("data-formie-loading-text")}}function Ma(t){return Array.from(t.querySelectorAll("[data-formie-action]"))}function Na(t,e){if(t.getAttribute("data-formie-loading")==="true")return;t.setAttribute("data-formie-loading","true"),Ma(t).forEach(n=>{"disabled"in n&&(n.disabled?n.setAttribute("data-formie-was-disabled","true"):n.removeAttribute("data-formie-was-disabled"),n.disabled=!0)}),e&&(e.setAttribute("data-formie-loading","true"),Em(t,e))}function yn(t){if(t.removeAttribute("data-formie-loading"),Ma(t).forEach(r=>{if("disabled"in r){const n=r,i=n.getAttribute("data-formie-was-disabled")==="true";n.disabled=i}ei(r),r.removeAttribute("data-formie-was-disabled"),r.removeAttribute("data-formie-loading"),yr(r,t,"loading",!1),r.removeAttribute("data-formie-loading-indicator"),r.removeAttribute("data-formie-loading-text")}),t.dataset.formieDisableSubmitUntilValid==="true"){const r=t;r.formieValidation&&Ni(t,r.formieValidation)}}function Oi(t,e){const r=La(t),n=gm(t),i=r.findIndex(a=>a.getAttribute("data-formie-page-id")===e);if(r.forEach(a=>{a.getAttribute("data-formie-page-id")===e?(a.removeAttribute("data-formie-page-hidden"),Er(a,t,"pageHidden")):(a.setAttribute("data-formie-page-hidden","true"),Fe(a,t,"pageHidden"))}),n.forEach((a,o)=>{const s=a.getAttribute("data-formie-page-id")===e,u=i>-1&&o<i;yr(a,t,"tabCurrent",s),yr(a,t,"tabComplete",u);const f=a.querySelector("[data-formie-tab-link]");f&&(yr(f,t,"tabLinkCurrent",s),s?Er(f,t,"tabLinkInactive"):Fe(f,t,"tabLinkInactive")),s?a.setAttribute("aria-current","page"):a.removeAttribute("aria-current"),u?a.setAttribute("data-formie-tab-complete","true"):a.removeAttribute("data-formie-tab-complete")}),i>-1&&r.length>0){const a=bm(t,i,r.length);xm(t,a)}if(zr(t,"pageId",e),rr(t),t.dataset.formieDisableSubmitUntilValid==="true"){const a=t;a.formieValidation&&Ni(t,a.formieValidation)}}function km(t,e){var o,s,u,f,g,b;const r=(o=e.meta)==null?void 0:o.session,n=(r==null?void 0:r.version)??((s=e.meta)==null?void 0:s.version);typeof n=="number"&&zr(t,"expectedVersion",String(n));const i=(u=e.meta)==null?void 0:u.submissionUid;typeof i=="string"&&i.trim()!==""&&zr(t,"submissionUid",i);const a=(b=(g=(f=e.meta)==null?void 0:f.session)==null?void 0:g.continuation)==null?void 0:b.progressId;typeof a=="string"&&a.trim()!==""?zr(t,"progressId",a):Rr(t,"progressId")}function Sm(t){const e=t.getAttribute("action");e&&t.setAttribute("action",hm(e,"resumeToken"));try{const r=new URL(window.location.href);if(!r.searchParams.has("resumeToken"))return;r.searchParams.delete("resumeToken"),window.history.replaceState({},document.title,`${r.pathname}${r.search}${r.hash}`)}catch{}}function _m(t,e){var a;const r=(a=e.meta)==null?void 0:a.resumeUrl;if(typeof r!="string"||r.trim()==="")return;const n=r.trim();if(!pm(n))return;t.getAttribute("action")&&t.setAttribute("action",n);try{const o=new URL(n,window.location.href);window.history.replaceState({},document.title,`${o.pathname}${o.search}${o.hash}`)}catch{}}function on(t,e={}){var a;const n=t.formieValidation,i=(a=La(t)[0])==null?void 0:a.getAttribute("data-formie-page-id");if(At(t),t.reset(),e.preserveHiddenState||Ri(t,!1),Rr(t,"submissionId"),zr(t,"expectedVersion","0"),Rr(t,"submissionUid"),Rr(t,"progressId"),Rr(t,"pageId"),Sm(t),n==null||n.resetLiveState(),i){Oi(t,i),t.dispatchEvent(new CustomEvent(Xn("reset"),{bubbles:!0}));return}rr(t),t.dispatchEvent(new CustomEvent(Xn("reset"),{bubbles:!0}))}function Am(t){var e;return t.code===mm||((e=t.meta)==null?void 0:e.resetState)===!0}function Tm(t,e){const r=e.submitData,n=new Set;let i=!1;if(Array.isArray(r)&&r.length>0){const g=r.filter(b=>typeof b=="object"&&b!==null&&"event"in b&&typeof b.event=="string");for(const b of g){const p=b.event;n.add(p),Ft.log("Dispatching submitData event.",{eventName:p}),p.startsWith("formie:payment:")&&(i=!0),t.dispatchEvent(new CustomEvent(p,{bubbles:!0,detail:{data:b.data}}))}}const a=e.meta||{},o=(a.paymentAction&&typeof a.paymentAction=="object"?a.paymentAction:null)||(a.paymentDecision&&typeof a.paymentDecision=="object"?a.paymentDecision.action:null),s=o?String(o.event||""):"",u=o?o.payload:void 0,f=s;return f&&!n.has(f)&&(f.startsWith("formie:payment:")&&(i=!0),t.dispatchEvent(new CustomEvent(f,{bubbles:!0,detail:{data:u}})),Ft.log("Dispatching fallback payment action event.",{eventName:f})),{hasPaymentFollowUpEvent:i}}function Cm(t,e,r){var i,a,o,s,u;if(Ft.log("Applying submit result state.",{ok:e.ok,action:r,code:e.code,hasRedirect:!!((i=e.redirect)!=null&&i.url),hasSubmitData:Array.isArray(e.submitData)&&e.submitData.length>0}),Am(e)){on(t),Ft.log("Resetting state due to stale/reset marker.");return}const n=Tm(t,e);if(!e.ok&&((a=e.redirect)!=null&&a.url)&&!n.hasPaymentFollowUpEvent){Ft.log("Applying redirect fallback for failed result.",{url:e.redirect.url,target:e.redirect.target}),At(t),e.redirect.target==="new-tab"?window.open(e.redirect.url,"_blank","noopener,noreferrer"):(xo(t,"redirect"),window.location.href=e.redirect.url);return}if(km(t,e),!e.ok){Ft.log("Non-redirect failure; keeping current form state."),At(t);return}if(Array.isArray(e.clientEvents)&&e.clientEvents.length>0?xi(t,e.clientEvents):pa(t,r),(o=e.nextPage)!=null&&o.id){At(t);const g=t.formieValidation;g==null||g.resetLiveState(),Oi(t,e.nextPage.id),Ie(t,"formie:page:navigate:after",{pageId:e.nextPage.id}),Ft.log("Advanced to next page.",{nextPageId:e.nextPage.id});return}if(r==="save"){At(t),_m(t,e),Ft.log("Applied save/resume token state.");return}if(e.completion&&r==="submit"&&!((s=e.redirect)!=null&&s.url)){const f=Eo(t,e),g=f==="message"&&ko(t);if(f==="reload"){At(t),xo(t,"reload"),window.location.reload();return}if(f==="reset"){on(t);return}At(t),on(t,{preserveHiddenState:g});return}if(r==="submit"&&((u=e.redirect)!=null&&u.url)&&e.redirect.target==="new-tab"){const g=Eo(t,e)==="message"&&ko(t);At(t),on(t,{preserveHiddenState:g});return}At(t)}const wn=new WeakMap;function Ra(t){return(t.dataset.formieSubmitAction||"").trim()}function Im(t){return(t.dataset.formieErrorMessagePosition||"top-form").trim()||"top-form"}function Oa(t){return(t.dataset.formieSubmitActionMessagePosition||"").trim()}function Lm(t){const e=(t.dataset.formieSubmitActionMessageTimeout||"").trim();if(!e)return null;const r=Number.parseFloat(e);return!Number.isFinite(r)||r<0?null:Math.round(r*1e3)}function Pi(t){const e=t.dataset.formieSubmitActionFormHide;if(e===void 0)return!1;const r=e.trim().toLowerCase();return r==="true"||r==="1"||r===""}function Mm(t){const e=wn.get(t);typeof e=="number"&&(window.clearTimeout(e),wn.delete(t))}function Pa(t){return t.querySelector("[data-formie-form-messages-top]")||t}function $a(t){return t.querySelector("[data-formie-form-messages-bottom]")||t}function Nm(t,e){return e==="bottom-form"?$a(t):Pa(t)}function Rm(t,e){return e==="top-form"?Pa(t):e==="bottom-form"&&!Pi(t)?$a(t):t}function Fa(t){const e=Im(t),r=Nm(t,e);let n=r.querySelector("[data-formie-error-container], [data-formie-errors]");return n||(n=document.createElement("div"),n.setAttribute("data-formie-errors","true"),Fe(n,t,"errors")),n.setAttribute("data-formie-error-container","true"),e==="bottom-form"?r.append(n):r.prepend(n),n}function Da(t,e){let r=e.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");return r||(r=document.createElement("div"),r.setAttribute("data-formie-error-message-container","true"),e.appendChild(r)),r.setAttribute("data-formie-message","true"),r.setAttribute("data-formie-message-error","true"),Fe(r,t,"message","messageError"),r.setAttribute("role","alert"),wi(r,fa(yi(t))),r}function Om(t,e){let r=t.querySelector("[data-formie-success-container]");const n=Rm(t,e);return r||(r=document.createElement("div"),r.setAttribute("data-formie-success-container","true"),Fe(r,t,"successes")),e==="bottom-form"?n.append(r):n.prepend(r),r}function Pm(t){return Ta(t,e=>{Fe(e,t,"fieldErrors")})}function za(t){t.querySelectorAll("[data-formie-field-handle]").forEach(e=>{const r=e,n=r.querySelector("[data-formie-field-errors]"),i=Array.from(r.querySelectorAll("[data-formie-field-error]")).map(a=>a.id).filter(Boolean);Er(r,t,"fieldLayoutError"),r.removeAttribute("data-formie-field-has-error"),r.querySelectorAll("[data-formie-field-error]").forEach(a=>{a.remove()}),n&&!n.querySelector("[data-formie-field-error]")&&(n.innerHTML=""),r.querySelectorAll("input, select, textarea").forEach(a=>{const o=a;o.removeAttribute("aria-invalid"),Er(o,t,"fieldControlError"),o.removeAttribute("data-formie-input-has-error"),ga(o,i)})}),rr(t)}function Va(t){t.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach(e=>{const r=e;r.querySelectorAll("[data-formie-error]").forEach(n=>{n.remove()}),Er(r,t,"message","messageError"),r.removeAttribute("data-formie-message"),r.removeAttribute("data-formie-message-error"),r.removeAttribute("role"),r.removeAttribute("aria-live"),r.removeAttribute("aria-atomic"),r.querySelector("[data-formie-error]")||(r.innerHTML="")})}function $i(t){Mm(t),t.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach(e=>{e.remove()}),t.querySelectorAll("[data-formie-success-container]").forEach(e=>{const r=e;r.querySelectorAll("[data-formie-success]").forEach(n=>{n.remove()}),Er(r,t,"message","messageSuccess"),r.removeAttribute("data-formie-message"),r.removeAttribute("data-formie-message-success"),r.removeAttribute("role"),r.removeAttribute("aria-live"),r.removeAttribute("aria-atomic"),r.querySelector("[data-formie-success]")||(r.innerHTML="")}),Ra(t)==="message"&&Pi(t)||Ri(t,!1)}function ja(t){t.querySelectorAll('[aria-invalid="true"]').forEach(e=>{e.removeAttribute("aria-invalid")})}function qa(t,e){const r=fa(yi(t));Object.entries(e).forEach(([n,i])=>{var b;const a=`fields[${n.split(".").join("][")}]`,o=t.querySelector(`[name="${CSS.escape(a)}"], [name="${CSS.escape(a+"[]")}"]`),s=(o==null?void 0:o.closest("[data-formie-field-handle]"))||t.querySelector(`[data-formie-field-handle="${CSS.escape(n)}"]`);if(!s)return;const u=Pm(s),f=u.id&&u.id.trim()?u.id:`${n}-errors`;u.id=f,wi(u,r),Fe(s,t,"fieldLayoutError"),s.setAttribute("data-formie-field-has-error","true"),i.forEach((p,y)=>{const E=document.createElement("div");E.setAttribute("data-formie-field-error","true"),E.id=`${f}-${y+1}`,Fe(E,t,"fieldError"),E.textContent=p,u.appendChild(E)});const g=(b=u.querySelector("[data-formie-field-error]"))==null?void 0:b.id;s.querySelectorAll("input, select, textarea").forEach(p=>{const y=p;y.setAttribute("aria-invalid","true"),Fe(y,t,"fieldControlError"),y.setAttribute("data-formie-input-has-error","true"),g&&_i(y,g);const E=s.querySelector("[data-formie-instructions]");E!=null&&E.id&&Si(y,E.id)})}),rr(t)}function ti(t,e){const r=Fa(t),n=Da(t,r);Fe(r,t,"errors"),e.forEach(i=>{const a=document.createElement("div");a.setAttribute("data-formie-error","true"),a.setAttribute("role","alert"),Fe(a,t,"error"),a.innerHTML=i,n.appendChild(a)})}function $m(t){if(t.ok||t.keepSubmitLoading!==!0)return!1;const e=t.meta||{},r=String(e.paymentStatus||"");return r==="actionRequired"||r==="pending"||r==="unknown"}function Fm(t,e){const r=Fa(t),n=Da(t,r);Fe(r,t,"errors");const i=document.createElement("div");i.setAttribute("data-formie-notice","true"),i.setAttribute("role","status"),Fe(i,t,"message"),i.textContent=e,n.appendChild(i)}function Dm(t,e){return!e.message||e.nextPage||e.redirect?!1:e.action==="save"?!0:Ra(t)==="message"&&Oa(t)!==""}function zm(t,e){const r=Oa(t);if(!r)return;const n=Om(t,r);Fe(n,t,"message","messageSuccess"),n.setAttribute("data-formie-message","true"),n.setAttribute("data-formie-message-success","true"),n.setAttribute("role","status"),n.setAttribute("aria-live","polite"),n.setAttribute("aria-atomic","true");const i=document.createElement("div");i.setAttribute("data-formie-success","true"),Fe(i,t,"success"),i.innerHTML=e,n.appendChild(i),Pi(t)&&Ri(t,!0);const a=Lm(t);if(a!==null){const o=window.setTimeout(()=>{wn.delete(t),$i(t)},a);wn.set(t,o)}}function Vr(t,e){var r;if(za(t),Va(t),$i(t),ja(t),e.ok){Dm(t,e)&&zm(t,e.message||"");return}if(!e.ok){if($m(e)){const n=e.meta||{},i=String(n.paymentMessage||"").trim();i&&Fm(t,i);return}e.fieldErrors&&qa(t,e.fieldErrors),(r=e.formErrors)!=null&&r.length?ti(t,e.formErrors):!e.fieldErrors&&e.message&&ti(t,[e.message]),Ai(t)}}const Vm=yt("general","submit-flow");function jm(t){return!(!t.ok&&t.stage==="validate")}function Ha(t){var e;return t?!!(t.keepSubmitLoading===!0||t.ok&&((e=t.redirect)!=null&&e.url)&&t.redirect.target!=="new-tab"):!1}function ri(t){za(t),Va(t),$i(t),ja(t)}async function Ua(t){const{id:e,target:r,form:n,bus:i,validator:a,validateOnSubmit:o,action:s,submitter:u,waitForSubmitDelay:f,onRefreshTokensAfterSubmit:g,dispatchSubmitResult:b}=t;ri(n),Na(n,u||null);let p={ok:!1,code:"SUBMIT_ERROR",message:"Submission failed.",formErrors:["Submission failed."]};try{await f(n),p=await Aa(n,s,i,{validator:a,validateOnSubmit:o}),Vr(n,p),b(p),Cm(n,p,s),jm(p)&&await g(p)}catch(y){p={ok:!1,code:"SUBMIT_ERROR",message:y instanceof Error?y.message:"Submission failed.",formErrors:[y instanceof Error?y.message:"Submission failed."]},Vr(n,p),b(p),Vm.warn("Submit failed with exception.",{id:e,action:s,target:r,error:y instanceof Error?y.message:y})}finally{Ha(p)||yn(n)}return p}class qm{constructor(){this.modules=new Map}register(e,r={}){if(!/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/.test(e.moduleId)||e.version!==2||!Array.isArray(e.surfaces)||e.surfaces.length===0||e.surfaces.some(i=>!["server-rendered","client-rendered","cp-edit"].includes(i))||!["field","captcha","payment","address","core"].includes(e.kind)||typeof e.match!="function"||typeof e.setup!="function")throw new Error("Unsupported browser module definition. Register a namespaced moduleId compatible with version 2.");const n=this.modules.get(e.moduleId);return n===e?!0:n&&!r.replace?(console.warn(`[formie] Module "${e.moduleId}" is already registered. Pass { replace: true } to override the existing definition.`),!1):(this.modules.set(e.moduleId,e),!0)}unregister(e){this.modules.delete(e)}get(e){return this.modules.get(e)||null}getAll(){return Array.from(this.modules.values())}}const Hm={"address-finder":()=>q(()=>import("./address-finder.DG_xxYZl.js"),__vite__mapDeps([0,1,2])).then(t=>t.addressFinderModule),"google-address":()=>q(()=>import("./google-address.CQ1hu-4Q.js"),__vite__mapDeps([3,1,2])).then(t=>t.googleAddressModule),loqate:()=>q(()=>import("./loqate.A_1LGF1B.js"),__vite__mapDeps([4,1,2])).then(t=>t.loqateModule),"place-kit":()=>q(()=>import("./place-kit.C4xW7SZX.js"),__vite__mapDeps([5,2,6])).then(t=>t.placeKitModule)},Um={"captcha-eu":()=>q(()=>import("./captcha-eu.Bu3-IL9V.js"),__vite__mapDeps([7,1,2])).then(t=>t.captchaEuModule),"friendly-captcha-v1":()=>q(()=>import("./friendly-captcha-v1.D7ei7qks.js"),__vite__mapDeps([8,2])).then(t=>t.friendlyCaptchaV1Module),"friendly-captcha-v2":()=>q(()=>import("./friendly-captcha-v2.0m4sTsrI.js"),__vite__mapDeps([9,2])).then(t=>t.friendlyCaptchaV2Module),hcaptcha:()=>q(()=>import("./hcaptcha.Bix7uUo6.js"),__vite__mapDeps([10,1,2])).then(t=>t.hcaptchaModule),"recaptcha-enterprise":()=>q(()=>import("./recaptcha-enterprise.DFCXHaZF.js"),__vite__mapDeps([11,12,1,2])).then(t=>t.recaptchaEnterpriseModule),"recaptcha-v2-checkbox":()=>q(()=>import("./recaptcha-v2-checkbox.Ga86USoS.js"),__vite__mapDeps([13,12,1,2])).then(t=>t.recaptchaV2CheckboxModule),"recaptcha-v2-invisible":()=>q(()=>import("./recaptcha-v2-invisible.BZWI9MYk.js"),__vite__mapDeps([14,12,1,2])).then(t=>t.recaptchaV2InvisibleModule),"recaptcha-v3":()=>q(()=>import("./recaptcha-v3.b0B9CpfI.js"),__vite__mapDeps([15,12,1,2])).then(t=>t.recaptchaV3Module),snaptcha:()=>q(()=>import("./snaptcha.DxtPFxDX.js"),__vite__mapDeps([16,2])).then(t=>t.snaptchaModule),turnstile:()=>q(()=>import("./turnstile.DeUITnUx.js"),__vite__mapDeps([17,1,2])).then(t=>t.turnstileModule)},Bm={calculations:()=>q(()=>import("./calculations.BbwT2hZA.js"),__vite__mapDeps([18,19,2])).then(t=>t.calculationsModule),"checkbox-radio":()=>q(()=>import("./checkbox-radio.yfbCR0qN.js"),__vite__mapDeps([20,19,2])).then(t=>t.checkboxRadioModule),combobox:()=>q(()=>import("./combobox.C4TR9Wx6.js"),__vite__mapDeps([21,19,6,2])).then(t=>t.comboboxModule),conditions:()=>q(()=>import("./conditions.vQcmDxpm.js"),__vite__mapDeps([22,19,2])).then(t=>t.conditionsModule),"custom-google-maps":()=>q(()=>import("./custom-google-maps.BaKdbh6M.js"),__vite__mapDeps([23,19,2])).then(t=>t.customGoogleMapsModule),"custom-link":()=>q(()=>import("./custom-link.CjgQ5viK.js"),__vite__mapDeps([24,19,2])).then(t=>t.customLinkModule),"custom-maps":()=>q(()=>import("./custom-maps.CkG5ECfh.js"),__vite__mapDeps([25,2,19,6])).then(t=>t.customMapsModule),"date-picker":()=>q(()=>import("./date-picker.CyaTeySe.js"),__vite__mapDeps([26,19,6,2])).then(t=>t.datePickerModule),"file-upload":()=>q(()=>import("./file-upload.H7pIk1qp.js"),__vite__mapDeps([27,19,6,2])).then(t=>t.fileUploadModule),"upload-manager":()=>q(()=>import("./upload-manager.CzPng_5C.js"),__vite__mapDeps([28,19,6,2])).then(t=>t.uploadManagerModule),hidden:()=>q(()=>import("./hidden.FYzesiH2.js"),__vite__mapDeps([29,19,2])).then(t=>t.hiddenModule),"phone-country":()=>q(()=>import("./phone-country.UgY7eZbe.js"),__vite__mapDeps([30,2,19,6,31])).then(t=>t.phoneCountryModule),"password-validation":()=>q(()=>import("./password-validation.xFZzM-rE.js"),__vite__mapDeps([32,19,2])).then(t=>t.passwordValidationModule),"address-country":()=>q(()=>import("./address-country.RHFd8MwL.js"),__vite__mapDeps([33,19,31,2])).then(t=>t.addressCountryModule),"address-state":()=>q(()=>import("./address-state.BwoqPlg9.js"),__vite__mapDeps([34,21,19,6,2])).then(t=>t.addressStateModule),repeater:()=>q(()=>import("./repeater.D01gbmjU.js"),__vite__mapDeps([35,19,6,2])).then(t=>t.repeaterModule),"rich-text":()=>q(()=>import("./rich-text.CLF7PZb5.js"),__vite__mapDeps([36,19,6,2])).then(t=>t.richTextModule),signature:()=>q(()=>import("./signature.D8n-JgVN.js"),__vite__mapDeps([37,19,6,2])).then(t=>t.signatureModule),summary:()=>q(()=>import("./summary.DYS5h9bF.js"),__vite__mapDeps([38,19,6,2])).then(t=>t.summaryModule),"survey-likert":()=>q(()=>import("./survey-likert.Cs3kPu_e.js"),__vite__mapDeps([39,40,6,2])).then(t=>t.surveyLikertModule),"survey-rank":()=>q(()=>import("./survey-rank.DYPLwHBB.js"),__vite__mapDeps([41,40,19,6,2])).then(t=>t.surveyRankModule),"survey-rating":()=>q(()=>import("./survey-rating.DTYdjT6S.js"),__vite__mapDeps([42,40,19,6,2])).then(t=>t.surveyRatingModule),table:()=>q(()=>import("./table.CBksq9L-.js"),__vite__mapDeps([43,19,6,2])).then(t=>t.tableModule),"text-limit":()=>q(()=>import("./text-limit.DuCivewJ.js"),__vite__mapDeps([44,19,6,2])).then(t=>t.textLimitModule)},Ym={bpoint:()=>q(()=>import("./bpoint.C48Q_S-w.js"),__vite__mapDeps([45,2])).then(t=>t.bpointModule),eway:()=>q(()=>import("./eway.DZEPLzoC.js"),__vite__mapDeps([46,1,2])).then(t=>t.ewayModule),"go-cardless":()=>q(()=>import("./go-cardless.04dlsmfK.js"),__vite__mapDeps([47,2])).then(t=>t.goCardlessModule),mollie:()=>q(()=>import("./mollie.DIuEjMBU.js"),__vite__mapDeps([48,2])).then(t=>t.mollieModule),moneris:()=>q(()=>import("./moneris.JzvHD_pk.js"),__vite__mapDeps([49,2])).then(t=>t.monerisModule),opayo:()=>q(()=>import("./opayo.rv15XskG.js"),__vite__mapDeps([50,6,1,2])).then(t=>t.opayoModule),paddle:()=>q(()=>import("./paddle.DPaR9LHX.js"),__vite__mapDeps([51,1,2])).then(t=>t.paddleModule),paypal:()=>q(()=>import("./paypal.BpIp-XGo.js"),__vite__mapDeps([52,6,1,2])).then(t=>t.paypalModule),payway:()=>q(()=>import("./payway.BbtgluQO.js"),__vite__mapDeps([53,6,1,2])).then(t=>t.paywayModule),square:()=>q(()=>import("./square.CR5QhijA.js"),__vite__mapDeps([54,1,2])).then(t=>t.squareModule),stripe:()=>q(()=>import("./stripe.B43jDneW.js"),__vite__mapDeps([55,6,1,2])).then(t=>t.stripeModule)},So={...Bm,...Hm,...Um,...Ym},an=new Map;async function Wm(t,e){const r=e.get(t);if(r)return r;const n=t.startsWith("formie:")&&Object.prototype.hasOwnProperty.call(So,t.slice(7))?So[t.slice(7)]:void 0;if(!n)throw new Error(`Browser module ${t} is not registered.`);an.has(t)||an.set(t,n().catch(a=>{throw an.delete(t),a}));const i=await an.get(t);if(i.moduleId!==t)throw new Error(`Module definition does not match ${t}.`);return e.register(i),i}function Km(t,e,r){return[...new Set(t.targets.flatMap(n=>{if(n.type==="form")return[r||e];const i=n.type==="selector"?n.selector:n.type==="field"?`[data-formie-field-uid="${CSS.escape(n.uid)}"]`:n.type==="page"?`[data-formie-page-id="${CSS.escape(n.id)}"]`:`[data-formie-action="${CSS.escape(n.action)}"]`;return[...e.matches(i)?[e]:[],...e.querySelectorAll(i)]}))].filter(n=>!n.closest('[hidden], [data-formie-hidden="true"], [data-formie-conditionally-hidden], [data-formie-page-hidden]'))}async function Gm(t,e){Fr(t);const r=t.surface;if(e.matchContext.surface&&e.matchContext.surface!==r)throw new Error(`Browser module manifest surface ${r} cannot mount as ${e.matchContext.surface}.`);const{root:n,form:i}=e.setupContext,a=new Map,o=new Map,s=[];let u=!1,f=Promise.resolve(),g=!1;const b=async(S,A)=>{o.set(S.key,S);const L={key:S.key,moduleId:S.moduleId,required:S.required,surface:r,code:"MODULE_UNAVAILABLE",message:"A form feature could not start. Reload the page or contact the site administrator."};console.error("[formie] Browser module failure",L,A),await e.setupContext.emit("formie:browser:module:error",L)},p=async S=>{try{await S.destroy()}catch(A){console.error("[formie] Browser module disposal failed",A),await e.setupContext.emit("formie:browser:module:error",{key:S.key,moduleId:S.moduleId,surface:r,code:"MODULE_DISPOSE_FAILED",message:"A form feature could not clean up. Reload the page before continuing."})}},y=()=>[...o.values()].some(S=>S.required),E=()=>{if(!i||i.querySelector("[data-formie-module-error]"))return;const S=document.createElement("div");S.dataset.formieModuleError="true",S.setAttribute("role","alert"),S.textContent="A required form feature could not start. Reload the page or contact the site administrator.",i.prepend(S)},T=S=>{y()&&(S.preventDefault(),S.stopImmediatePropagation(),E())},v=async()=>{var L,P;const S=new Set(t.entries.map(N=>N.key));for(const N of o.keys())S.has(N)||o.delete(N);const A=[];for(const[N,ie]of a)S.has(N)||(A.push(...Array.from(ie.values(),({instance:te})=>te)),a.delete(N),o.delete(N));for(const N of A.reverse())await p(N),s.splice(s.indexOf(N),1);for(const N of t.entries){if(u)continue;o.has(N.key)&&o.set(N.key,N);let ie;try{ie=Km(N,n,i)}catch(Y){o.has(N.key)||await b(N,Y);continue}const te=a.get(N.key)??new Map;a.set(N.key,te);for(const[Y,I]of Array.from(te.entries()).reverse())ie.includes(Y)||(await p(I.instance),te.delete(Y),s.splice(s.indexOf(I.instance),1));let ue;try{ue=await Wm(N.moduleId,e.registry)}catch(Y){o.has(N.key)||await b(N,Y);continue}let V=!1,K=!1;for(const Y of ie){if(u)return;const I=JSON.stringify([N.moduleId,N.config,N.required]),R=te.get(Y);if((R==null?void 0:R.config)===I)continue;const U={...e.setupContext,target:Y,entryKey:N.key,surface:r,scope:((L=N.targets[0])==null?void 0:L.type)??"form",options:N.config};try{if(R){if(R.instance.update&&R.moduleId===N.moduleId&&R.required===N.required){await R.instance.update(U),R.config=I;continue}await p(R.instance),te.delete(Y),s.splice(s.indexOf(R.instance),1)}if(ue.surfaces&&!ue.surfaces.includes(r))throw new Error(`Module ${N.moduleId} does not support ${r}.`);if(ue.kind!==N.kind)throw new Error(`Module ${N.moduleId} is registered as ${ue.kind}, not ${N.kind}.`);if(!ue.match({...e.matchContext,mode:"server-rendered",target:Y,scope:U.scope,manifestItem:N}))throw new Error(`Module ${N.moduleId} does not support the rendered target.`);const J=await ue.setup(U);if(!J)throw new Error(`Module ${N.moduleId} did not initialize.`);if(u||!n.contains(Y)&&Y!==n){await p(J);continue}J.key=N.key,J.moduleId=N.moduleId,J.kind=N.kind,J.target=Y;const W=J.assertReady;J.assertReady=()=>{try{W==null||W()}catch(D){if(b(N,D),N.required)throw new Error("A required form feature could not start.")}};const me=J.beforeSubmit,se=J.afterSubmit;J.beforeSubmit=async D=>{try{await(me==null?void 0:me(D))}catch(z){await b(N,z),N.required&&D.abort("A required form feature could not complete. Reload the page or contact the site administrator.")}},J.afterSubmit=async(D,z)=>{try{await(se==null?void 0:se(D,z))}catch(Q){await b(N,Q)}},te.set(Y,{instance:J,config:I,moduleId:N.moduleId,required:N.required}),s.push(J),K=!0,await e.setupContext.emit("formie:browser:module:mount",{key:N.key,moduleId:N.moduleId,target:Y})}catch(J){V=!0,o.has(N.key)||await b(N,J)}}!V&&(K||ie.length===0)&&o.delete(N.key)}y()?E():(P=i==null?void 0:i.querySelector("[data-formie-module-error]"))==null||P.remove()},k=()=>{g||u||(g=!0,f=f.then(async()=>{g=!1,u||await v()}),f.catch(S=>console.error("[formie] Module reconciliation failed",S)))},C=new MutationObserver(k);return s.push({assertReady:()=>{if(y())throw new Error("A required form feature could not start. Reload the page or contact the site administrator.")},destroy:async()=>{u=!0,C.disconnect(),i==null||i.removeEventListener("submit",T,!0),await f;for(const S of Array.from(a.values()).reverse())for(const{instance:A}of Array.from(S.values()).reverse())await p(A);a.clear(),s.splice(1)},beforeSubmit:S=>{y()&&S.abort("A required form feature could not start. Reload the page or contact the site administrator.")}}),s.updateManifest=async S=>{if(Fr(S),S.surface!==r)throw new Error("A mounted browser module runtime cannot change surfaces.");t=S,f=f.then(v),await f},i==null||i.addEventListener("submit",T,!0),await v(),C.observe(n,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["hidden","data-formie-hidden","data-formie-conditionally-hidden","data-formie-page-hidden","data-formie-field-uid","data-formie-page-id","data-formie-action"]}),s}const Jm="formie:formStartedAt:";function Zm(t){var o;const e=t.querySelector('input[name="formStartedAt"]');if(!e)return;const r=t.querySelector('input[name="renderId"]'),n=((o=r==null?void 0:r.value)==null?void 0:o.trim())??"",i=n?`${Jm}${n}`:null;let a=i?sessionStorage.getItem(i):null;a||(a=String(Date.now()),i&&sessionStorage.setItem(i,a)),e.value=a}const Qm=new Set(["action","redirect","requestToken","renderId","formStartedAt","submitAction","pageId","draftContextToken","draftContext","progressId"]);function ni(t,e){if(t==null)return String(t);if(typeof t=="string")return JSON.stringify(t);if(typeof t=="number"||typeof t=="boolean")return String(t);if(typeof t=="function")return"[function]";if(typeof File<"u"&&t instanceof File)return`[file:${t.name}:${t.size}:${t.type}]`;if(typeof Blob<"u"&&t instanceof Blob)return`[blob:${t.size}:${t.type}]`;if(Array.isArray(t))return`[${t.map(r=>ni(r,e)).join(",")}]`;if(typeof t=="object"){if(e.has(t))return"[circular]";e.add(t);const r=Object.entries(t).sort(([n],[i])=>n.localeCompare(i)).map(([n,i])=>`${JSON.stringify(n)}:${ni(i,e)}`);return e.delete(t),`{${r.join(",")}}`}return JSON.stringify(String(t))}function Xm(t){return ni(t,new WeakSet)}function eh(t,e){if(!t)return!1;const r=t.endsWith("[]")?t.slice(0,-2):t;return Mf(r,e)?!1:!Qm.has(r)}function _o(t){const e=Array.from(new FormData(t).entries()).filter(([r])=>eh(String(r||""),t));return Xm(e)}function th(t,e={}){let r=null,n=!1,i=!1,a=null,o=null,s=null;const u=()=>{a!==null&&(window.cancelAnimationFrame(a),a=null),o!==null&&(window.clearTimeout(o),o=null),s!==null&&(window.clearTimeout(s),s=null)},f=()=>n?(i=_o(t)!==r,i):!1,g=()=>{r=_o(t),n=!0,i=!1},b=()=>{u(),n=!1,a=window.requestAnimationFrame(()=>{a=null,s=window.setTimeout(()=>{s=null,g()},0)})},p=()=>{o!==null&&window.clearTimeout(o),o=window.setTimeout(()=>{o=null,f()},120)},y=E=>{e.shouldWarn&&!e.shouldWarn()||f()&&(E.preventDefault(),E.returnValue="")};return t.addEventListener("input",p),t.addEventListener("change",p),window.addEventListener("beforeunload",y),b(),{captureBaseline:g,scheduleBaselineCapture:b,refreshDirtyState:f,destroy:()=>{u(),t.removeEventListener("input",p),t.removeEventListener("change",p),window.removeEventListener("beforeunload",y)}}}function rh(t){return t.hasAttribute("data-formie-conditionally-hidden")||!!t.closest("[data-formie-conditionally-hidden]")||t.hasAttribute("data-formie-page-hidden")||!!t.closest("[data-formie-page-hidden]")}function nh(t,e){const r=t.querySelectorAll(`[data-formie-action="${e}"]`);return Array.from(r).some(n=>!rh(n))}function ih(t){const{final:e}=_n(t);return"submit"}function oh(t){const e=ih(t);return!nh(t,e)}function ah(t){const e=r=>{if(r.key!=="Enter"||r.defaultPrevented)return;const n=r.target;(n instanceof HTMLInputElement||n instanceof HTMLSelectElement)&&(n instanceof HTMLInputElement&&(n.type==="button"||n.type==="submit"||n.type==="reset"||n.type==="file")||oh(t)&&r.preventDefault())};return t.addEventListener("keydown",e,!0),()=>{t.removeEventListener("keydown",e,!0)}}const dr='[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])',sh=300,lh="/actions/formie/server/forms/render",Ao="/api",ch="/actions/formie/server/forms/refresh-tokens",uh="/actions/formie/server/submissions/submit",dh="/actions/formie/server/submissions/set-page",fh="/actions/formie/server/submissions/clear-submission",mh="/actions/formie/file-upload/hydrate",Ce=yt("general","client"),To=new Set;function Ur(t,e){if(t==null||t==="")return e;const r=t.toLowerCase();return!(r==="false"||r==="0"||r==="off")}function ii(t){return t.formieRefreshTokens!=null?Ur(t.formieRefreshTokens,!0):t.formieStaticCache!=null?Ur(t.formieStaticCache,!0):!1}function fr(t){const e=t instanceof HTMLElement?t.dataset:{};return{mode:"server-rendered",transport:e.formieTransport||"rest",profile:e.formieRequestProfile,formHandle:e.formieHandle,endpoint:e.formieEndpoint,staticCache:ii(e),autoVisible:Ur(e.formieAutoVisible,!0),compatibility:Ur(e.formieCompatibility,!1)}}function Ba(t){if(t&&t!=="server-rendered")throw new Error("@verbb/formie-browser enhances server-rendered HTML only. Use @verbb/formie-core for client-rendered forms.");return"server-rendered"}function Ya(t){return t||"rest"}function oi(t){return t instanceof HTMLFormElement?t:t.querySelector("form")}function hh(t,e){To.has(t)||(To.add(t),Ce.warn(e))}function Wa(t,e){if(!t)return t;try{return new URL(t).toString()}catch{}if(!e)return t;try{return new URL(t,e).toString()}catch{return t}}function wr(t,e){const r=(t||"").trim();return r?r.includes(e)?r:Wa(e,r):e}function ph(t,e){return wr(t.endpoint||e.dataset.formieEndpoint,lh)}function gh(t,e){const r=(t.endpoint||e.dataset.formieEndpoint||"").trim();return r?r.includes("/graphql")||r.endsWith("/api")||r.includes("/actions/graphql/")?r:Wa(Ao,r):Ao}function Fi(t,e){return wr(e.dataset.formieRefreshTokensEndpoint||t.endpoint||e.dataset.formieEndpoint,ch)}function Co(t,e){if(!t)return e;try{const r=new URL(t,window.location.origin),n=new URL(e,window.location.origin);return r.searchParams.forEach((i,a)=>{n.searchParams.has(a)||n.searchParams.set(a,i)}),n.toString()}catch{return e}}function bh(t,e,r){const n=r.endpoint||t.dataset.formieEndpoint,i=wr(n,uh),a=e.getAttribute("action");e.setAttribute("action",Co(a,i)),e.querySelectorAll("[data-formie-tab-link]").forEach(o=>{const s=o.getAttribute("href"),u=wr(n,dh);o.setAttribute("href",Co(s,u))}),e.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach(o=>{o.setAttribute("data-formie-file-upload-hydrate-endpoint",wr(n,mh))})}function Di(t){if(t==null)return!1;const e=t.trim().toLowerCase();return e==="true"||e==="1"||e===""}function vh(t){return Ur(t.dataset.formieAutomaticSubmissionState,!0)}function yh(t,e,r){return wr(r.dataset.formieClearSubmissionEndpoint||t.endpoint||e.dataset.formieEndpoint,fh)}function wh(t){return Di(t.dataset.formieUnloadWarning)}function Io(t,e){t.setAttribute("data-formie-internal-navigation",e)}function jn(t){t.removeAttribute("data-formie-internal-navigation")}function Lo(t){return t.getAttribute("data-formie-internal-navigation")!==null}function Mo(t,e){if(!t)return!1;try{return new URL(t,window.location.origin).searchParams.has(e)}catch{return!1}}function xh(t){return Mo(window.location.href,"resumeToken")||Mo(t.getAttribute("action"),"resumeToken")}function Eh(t){return t instanceof MouseEvent?t.button===0&&!t.metaKey&&!t.ctrlKey&&!t.shiftKey&&!t.altKey:!0}function kh(t,e=0){if(!t)return e;const r=Number.parseInt(t,10);return Number.isFinite(r)?r:e}function Sh(t){return Math.max(0,kh(t.dataset.formieSubmitDelay,sh))}function cn(t){return Di(t.dataset.formieValidationOnSubmit)}async function ai(t){const e=Sh(t);e<1||await new Promise(r=>{window.setTimeout(r,e)})}function qn(t,e){var n;const r=(n=t==null?void 0:t.getAttribute(e))==null?void 0:n.trim();if(!r)return null;try{return JSON.parse(r)}catch(i){if(e==="data-formie-modules")throw new Error("Invalid browser-module manifest JSON. Update Formie and its browser packages together.");return console.error(`[formie] Failed to parse ${e}.`,i),null}}function No(t,e){const r=e||(t instanceof HTMLFormElement?t:null);if(!r)return null;const n=qn(r,"data-formie-modules"),i=qn(r,"data-formie-theme-classes")||qn(r,"data-formie-theme");return!n&&!i?null:{modules:n||void 0,theme:i||void 0}}function _h(t){if(!(t instanceof HTMLElement))return!0;if(!t.isConnected||t.hidden||t.closest("[hidden]"))return!1;const e=window.getComputedStyle(t);return e.display==="none"||e.visibility==="hidden"?!1:t.getClientRects().length>0}function Ah(t,e){return e===document?!0:e instanceof Element?e===t||e.contains(t):!0}function Ve(t){var a;const e=t,r=e.id?`#${e.id}`:"",n=(a=e.dataset)!=null&&a.formieHandle?`[handle="${e.dataset.formieHandle}"]`:"";return`${e.tagName?e.tagName.toLowerCase():"element"}${r}${n}`}function zi(t,e){var r,n;if(e){if((r=e.csrf)!=null&&r.param&&((n=e.csrf)!=null&&n.token)){let i=t.querySelector(`input[name="${e.csrf.param}"]`);i?i.value=e.csrf.token:(i=document.createElement("input"),i.type="hidden",i.name=e.csrf.param,i.value=e.csrf.token,i.setAttribute("autocomplete","off"),i.setAttribute("data-formie-csrf",""),t.prepend(i))}if(e.requestToken){const i=t.querySelector('input[name="requestToken"]');i&&(i.value=e.requestToken)}if(e.renderId){const i=t.querySelector('input[name="renderId"]');i&&(i.value=e.renderId)}if(e.uploadCreateToken){let i=t.querySelector('input[name="uploadCreateToken"]');i||(i=document.createElement("input"),i.type="hidden",i.name="uploadCreateToken",t.append(i)),i.value=e.uploadCreateToken}e.captchas&&typeof e.captchas=="object"&&Object.values(e.captchas).forEach(i=>{if(!i||typeof i!="object")return;const a=i;if(!a.sessionKey)return;const o=t.querySelector(`input[name="${a.sessionKey}"]`);o&&typeof a.value=="string"&&(o.value=a.value)})}}async function Th(t,e){const r=Ba(e.mode),n=Ya(e.transport);if(e.payload)return e.payload.html&&(t.innerHTML=e.payload.html),e.payload;const i=!!oi(t),a=e.formHandle||t.dataset.formieHandle;if(i||!a)return null;const o={mode:r,endpoint:e.endpoint,locale:e.locale,siteId:e.siteId,theme:e.theme,themeConfig:e.themeConfig},s=n==="graphql"?gh(e,t):ph(e,t),u=n==="graphql"?await Pf(s,a,o,e):await Of(s,a,{...o,endpoint:s},e);return u!=null&&u.html&&(t.innerHTML=u.html),u}async function Ka(t,e,r){var u;if(e.refreshTokens===!1)return;const n=e.formHandle||t.dataset.formieHandle;if(!n)return;const i=Fi(e,t),a=r.querySelector('input[name="renderId"]'),o=(a==null?void 0:a.value)||void 0,s=await Li(i,n,o,e,(u=r==null?void 0:r.querySelector('input[name="requestToken"]'))==null?void 0:u.value);zi(r,s),Ie(t,"formie:refresh-tokens:refreshed",s)}function Ch(t,e,r,n,i,a){e.dataset.formieRequestProfile=r.profile??"same-origin-browser",r.profile==="cross-origin-public"&&(e.dataset.formieSubmitMethod="ajax");const o=String(e.dataset.formieSubmitMethod||"").trim().toLowerCase(),s=yh(r,t,e);let u=!1;const f=e.querySelectorAll("[data-formie-action]"),g=y=>{if(y){e.setAttribute("data-formie-pending-action",y);return}e.removeAttribute("data-formie-pending-action")};if(wh(e)){const y=th(e,{shouldWarn:()=>!Lo(e)}),E=v=>{if(!(v instanceof CustomEvent))return;const k=v.detail;k!=null&&k.ok&&k.action==="save"&&y.scheduleBaselineCapture()},T=()=>{y.scheduleBaselineCapture()};t.addEventListener("formie:submit:result",E),e.addEventListener("formie:state:reset",T),a.push(()=>{t.removeEventListener("formie:submit:result",E),e.removeEventListener("formie:state:reset",T),y.destroy()})}if(f.forEach(y=>{const E=T=>{const v=T.currentTarget.getAttribute("data-formie-action"),k=e.querySelector('input[name="submitAction"]');g(v),v&&k&&(k.value=v)};y.addEventListener("click",E),a.push(()=>{y.removeEventListener("click",E)})}),e.querySelectorAll("[data-formie-tab-link]").forEach(y=>{const E=async T=>{if(o!=="ajax"){Eh(T)&&Io(e,"set-page");return}T.preventDefault();const v=T.currentTarget,k=v==null?void 0:v.getAttribute("data-formie-page-id"),C=v==null?void 0:v.getAttribute("href");if(!(!k||!C)){Ie(t,"formie:page:navigate",{pageId:k,href:C});try{const S=await $f(C,e,k);if(S.pageId&&Oi(e,String(S.pageId)),!S.success){const{form:A=[],...L}=S.errors??{};ri(e),qa(e,L),ti(e,A),Ai(e);return}Ie(t,"formie:page:navigate:after",{pageId:k,href:C,response:S})}catch(S){console.error("[formie] Failed to persist page navigation state.",S),Ie(t,"formie:page:navigate:error",{pageId:k,href:C,error:S})}}};y.addEventListener("click",E),a.push(()=>{y.removeEventListener("click",E)})}),!vh(e)){let y=!1;const E=()=>{y||Lo(e)||xh(e)||(y=!0,Ff(s,e))};window.addEventListener("pagehide",E),window.addEventListener("beforeunload",E),a.push(()=>{window.removeEventListener("pagehide",E),window.removeEventListener("beforeunload",E)})}const p=async y=>{if(u)return;const E=o==="ajax";if(y.preventDefault(),e.getAttribute("data-formie-loading")==="true"){if(!(e.getAttribute("data-formie-internal-resubmit")==="true"))return;e.removeAttribute("data-formie-internal-resubmit")}else e.removeAttribute("data-formie-internal-resubmit");const v=y.submitter,k=v==null?void 0:v.getAttribute("data-formie-action"),C=e.getAttribute("data-formie-pending-action"),S=e.querySelector('input[name="submitAction"]'),A=k||C||(S==null?void 0:S.value)||"submit";let L=null,P=!1;try{if(E)L=await Ua({target:t,form:e,bus:n,validator:i,validateOnSubmit:cn(e),action:A,submitter:v,waitForSubmitDelay:ai,onRefreshTokensAfterSubmit:async()=>{await Ka(t,r,e)},dispatchSubmitResult:N=>{Ie(t,"formie:submit:result",N)}});else{if(ri(e),Na(e,v),await ai(e),L=await Aa(e,A,n,{validator:i,validateOnSubmit:cn(e),preflightOnly:!0}),L.ok){pa(e,A),u=!0,Io(e,"submit"),g(null);let N=!1;const ie=()=>{if(N=!0,u=!1,jn(e),yn(e),i&&cn(e)){const{scope:te,final:ue}=_n(e),V=i.submit(ue?e:te,{final:ue});V.length>0&&Vr(e,{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:i.config.errorMessage||"Validation failed.",fieldErrors:i.getFieldErrors(V),formErrors:[i.config.errorMessage||"Validation failed."]})}};if(typeof e.requestSubmit=="function"){e.addEventListener("invalid",ie,!0);try{e.requestSubmit()}finally{e.removeEventListener("invalid",ie,!0)}}else e.submit();if(N)return;P=!0;return}Vr(e,L),Ie(t,"formie:submit:result",L),jn(e)}}catch(N){u=!1,L={ok:!1,code:"SUBMIT_ERROR",message:N instanceof Error?N.message:"Submission failed.",formErrors:[N instanceof Error?N.message:"Submission failed."]},Vr(e,L),Ie(t,"formie:submit:result",L),jn(e)}finally{g(null),!E&&!P&&!Ha(L)&&yn(e)}};e.addEventListener("submit",p),a.push(()=>{e.removeEventListener("submit",p)})}async function Ih(t,e,r){var u;if(e.refreshTokens===!1||!e.staticCache)return;const n=e.formHandle||t.dataset.formieHandle,i=Fi(e,t),a=r==null?void 0:r.querySelector('input[name="renderId"]'),o=(a==null?void 0:a.value)||void 0;if(!n)return;const s=await Li(i,n,o,e,(u=r==null?void 0:r.querySelector('input[name="requestToken"]'))==null?void 0:u.value);!s||!r||(zi(r,s),Ie(t,"formie:refresh-tokens:after",s))}function Lh(){const t=new Map,e=new qm,r=new Map,n=new Map,i=["prepare","normalize","validate","challenge","payment","send","result"],a=async v=>{const k=n.get(v);if(k){await k;return}const C=(async()=>{var L,P;Ce.log("Unmount requested.",{target:Ve(v)});const S=r.get(v);S&&(S(),r.delete(v));const A=t.get(v);if(!A){Ce.log("Unmount skipped (no mounted state).",{target:Ve(v)});return}Ie(v,"formie:unmount:before",{id:A.instance.id}),A.unbinds.forEach(N=>{N()}),A.unbinds=[],(L=A.validator)==null||L.destroy(),A.validator=null,await((P=A.modules[0])==null?void 0:P.destroy()),A.modules=[],A.bus.clear(),t.delete(v),Ie(v,"formie:unmount:after",{id:A.instance.id}),Ce.log("Unmount complete.",{id:A.instance.id,target:Ve(v)})})().finally(()=>{n.delete(v)});n.set(v,C),await C},o=async(v,k)=>{Ce.log("Mount requested.",{target:Ve(v),mode:k.mode,autoVisible:k.autoVisible});const C=r.get(v);C&&(C(),r.delete(v));const S=t.get(v);if(S)return Ce.log("Mount skipped (already mounted).",{id:S.instance.id,target:Ve(v)}),S.instance;const A=new Tf,L=[],P=(v==null?void 0:v.id)||`formie-${t.size+1}`,N=fr(v),ie={...N,...k,mode:Ba(k.mode??N.mode),transport:Ya(k.transport??N.transport)},te=Qd(ie.compatibility),ue=await Th(v,ie),V=oi(v);V&&Cd(V,ie),ie.staticCache=k.staticCache??ii(V?V.dataset:v.dataset);let K;try{K=No(v,V),ue!=null&&ue.modules&&Fr(ue.modules),K!=null&&K.modules&&Fr(K.modules)}catch(re){if(V){V.addEventListener("submit",ke=>{ke.preventDefault(),ke.stopImmediatePropagation()},!0);const he=document.createElement("div");he.setAttribute("role","alert"),he.textContent="This form requires a compatible Formie browser package. Update Formie and its browser packages together.",V.prepend(he)}throw re}const Y=ue||K?{...ue||{},...K||{}}:null,I=Y==null?void 0:Y.theme,R={},U=(Y==null?void 0:Y.modules)??{contractVersion:2,surface:"server-rendered",entries:[]};Fr(U),Ce.log("Resolved mount payload.",{target:Ve(v),hasRenderPayload:!!ue,hasEmbeddedPayload:!!K,moduleCount:U.entries.length});const J=go(v,I,V),W=V?new lm(V,{live:Di(V.dataset.formieValidationOnFocus),errorAriaLive:yi(V),errorMessage:V.dataset.formieErrorMessage||"",fieldContainerErrorClass:J.fieldLayoutError||[],inputErrorClass:J.fieldControlError||[],messagesClass:J.fieldErrors||[],messageClass:J.fieldError||[]}):null;if(V&&W){const re=V;re.formieValidation=W,R.validation=W;const he={validator:W,addValidator:W.addValidator.bind(W),removeValidator:W.removeValidator.bind(W)};Ie(V,"formie:validator:ready",he),Ie(v,"formie:validator:ready",he)}V&&(Zm(V),ie.theme&&ie.theme!=="formie"&&V.setAttribute("data-formie-frontend-theme",ie.theme),(ue||ie.endpoint||v.dataset.formieEndpoint)&&bh(v,V,ie),ie.mode==="server-rendered"&&Af(V)&&(_f(V),Ai(V)),rr(V)),Object.keys(J).length&&Ie(v,"formie:theme:applied",{hasClasses:!0});const me=await Gm(U,{registry:e,matchContext:{root:v,form:V,mode:ie.mode,surface:U.surface},setupContext:{formId:P,root:v,form:V,target:v,scope:"form",state:R,on:(re,he)=>A.on(re,he),emit:(re,he)=>(Ie(v,re,he),A.emitSafe(re,he).then(ke=>{ke.failed.length>0&&Ce.warn("Lifecycle listeners failed.",{eventName:re,failed:ke.failed.length})}))}});Ce.log("Module setup complete.",{target:Ve(v),moduleInstances:me.length});const se={id:P,root:v,submit:async(re="submit")=>{if(Ce.log("Submit requested.",{id:P,target:Ve(v),action:re}),!V)return{ok:!1,code:"FORM_NOT_FOUND",message:"No form element found for mount target.",formErrors:["No form element found for mount target."]};const he=V.querySelector('input[name="submitAction"]');if(he&&(he.value=re),V.getAttribute("data-formie-loading")==="true")return{ok:!1,code:"SUBMIT_IN_PROGRESS",message:"Submission already in progress.",formErrors:[]};const ke=V.querySelector(`[data-formie-action="${re}"]`),ge=await Ua({id:P,target:v,form:V,bus:A,validator:W,validateOnSubmit:cn(V),action:re,submitter:ke,waitForSubmitDelay:ai,onRefreshTokensAfterSubmit:async()=>{await Ka(v,ie,V)},dispatchSubmitResult:_e=>{Ie(v,"formie:submit:result",_e)}});return Ce.log("Submit completed.",{id:P,action:re,ok:ge.ok,code:ge.code,message:ge.message}),ge},destroy:async()=>{await a(v)},on:(re,he)=>A.on(re,he)};V&&(of({target:v,form:V,validatorDetail:W?{validator:W,addValidator:W.addValidator.bind(W),removeValidator:W.removeValidator.bind(W)}:null,options:te,unbinds:L}),nf({target:v,form:V,instance:se,options:te,unbinds:L})),V&&(Ch(v,V,ie,A,W,L),W&&(L.push(fm(V,W,v)),L.push(ah(V))),await Ih(v,ie,V),V.dispatchEvent(new CustomEvent("formie:state:reset")),window.setTimeout(()=>{V.dispatchEvent(new CustomEvent("formie:state:reset"))},350)),i.forEach(re=>{const he=A.on(`formie:stage:${re}:before`,async _e=>{Ie(v,`formie:stage:${re}:before`,_e)}),ke=A.on(`formie:stage:${re}:before`,async _e=>{const qe={core:"prepare",field:"prepare",address:"prepare",captcha:"challenge",payment:"payment"},Je=_e;for(const Ze of me)if(Ze.beforeSubmit&&qe[Ze.kind??"core"]===re){const{form:ut,action:Me,formData:et,abort:He,isAborted:Ue,abortReason:Be}=Je;await Ze.beforeSubmit({form:ut,action:Me,formData:et,abort:He,isAborted:Ue,abortReason:Be})}}),ge=A.on(`formie:stage:${re}:after`,async _e=>{Ie(v,`formie:stage:${re}:after`,_e)});L.push(he,ke,ge)});const D=A.on("formie:submit:before",async re=>{Ie(v,"formie:submit:before",re)}),z=A.on("formie:submit:after",async re=>{var ge;if(Ie(v,"formie:submit:after",re),!V)return;const he=re;if(he.code==="PREFLIGHT_COMPLETE")return;const ke={form:V,action:he.action??"submit",formData:new FormData(V)};for(const _e of me)await((ge=_e.afterSubmit)==null?void 0:ge.call(_e,ke,he))}),Q=A.on("formie:submit:final:before",async re=>{Ie(v,"formie:submit:final:before",re)}),Se=A.on("formie:submit:final:after",async re=>{Ie(v,"formie:submit:final:after",re)});return L.push(D,z,Q,Se),t.set(v,{options:ie,bus:A,form:V,validator:W,modules:me,unbinds:L,instance:se}),Ie(v,"formie:mount:after",{id:P,mode:ie.mode}),V instanceof HTMLFormElement&&gf(V),Ce.log("Mount complete.",{id:P,target:Ve(v),mode:ie.mode}),se},s=(v,k)=>{var S;if(!k.autoVisible||_h(v)||typeof IntersectionObserver>"u")return o(v,k);if(t.has(v))return Promise.resolve(((S=t.get(v))==null?void 0:S.instance)||null);if(r.has(v))return Ce.log("Mount deferred (already waiting visibility).",{target:Ve(v)}),Promise.resolve(null);const C=new IntersectionObserver(A=>{A.some(P=>P.target===v&&P.isIntersecting)&&(C.disconnect(),r.delete(v),Ce.log("Visibility reached, proceeding mount.",{target:Ve(v)}),o(v,{...k,autoVisible:!1}))},{threshold:.01});return C.observe(v),r.set(v,()=>{C.disconnect()}),Ce.log("Mount deferred until visible.",{target:Ve(v)}),Promise.resolve(null)};return{mount:o,unmount:a,update:async(v,k)=>{var L,P,N;const C=t.get(v);if(!C)return o(v,{...fr(v),...k,mode:k.mode||"server-rendered"});C.options={...C.options,...k};const S=((L=k.payload)==null?void 0:L.theme)||((P=C.options.payload)==null?void 0:P.theme)||((N=No(v,C.form))==null?void 0:N.theme),A=go(v,S,C.form);return C.validator&&(C.validator.config.fieldContainerErrorClass=A.fieldLayoutError||[],C.validator.config.inputErrorClass=A.fieldControlError||[],C.validator.config.messagesClass=A.fieldErrors||[],C.validator.config.messageClass=A.fieldError||[]),Object.keys(A).length&&Ie(v,"formie:theme:applied",{hasClasses:!0,reason:"update"}),C.instance},getInstance:v=>{var k;return((k=t.get(v))==null?void 0:k.instance)||null},refreshForCache:async v=>{var ue;hh("refreshForCache","Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");let k=null;if(typeof v=="string"){const V=document.getElementById(v);V?k=V:k=document.querySelector(`[data-formie-form-id="${v}"]`)}else k=v;if(!k){Ce.warn("refreshForCache target not found.",{targetOrId:v});return}const C=t.get(k),S=oi(k),A=(C==null?void 0:C.options)||fr(k);if(!S){Ce.warn("refreshForCache found no form element for target.",{target:Ve(k)});return}const L=A.formHandle||k.dataset.formieHandle||S.dataset.formieHandle,P=Fi(A,k),N=S.querySelector('input[name="renderId"]'),ie=(N==null?void 0:N.value)||void 0;if(!L){Ce.warn("refreshForCache found no form handle for target.",{target:Ve(k)});return}const te=await Li(P,L,ie,A,(ue=S==null?void 0:S.querySelector('input[name="requestToken"]'))==null?void 0:ue.value);te&&(zi(S,te),Ie(k,"formie:refresh-tokens:after",te))},registerModule:(v,k)=>e.register(v,k),unregisterModule:v=>{e.unregister(v)},getRegisteredModules:()=>e.getAll(),scan:async v=>{const k=v||document,C=Array.from(k.querySelectorAll(dr));Ce.log("Scan started.",{scope:k===document?"document":k,targetCount:C.length});const A=(await Promise.all(C.map(L=>{const P=fr(L);return s(L,P)}))).filter(L=>!!L);return Ce.log("Scan finished.",{mountedCount:A.length,deferredCount:C.length-A.length}),A},observe:v=>{if(typeof MutationObserver>"u")return()=>{};const k=v||document;Ce.log("Observer started.",{scope:k===document?"document":k});const C=new MutationObserver(S=>{S.forEach(A=>{A.addedNodes.forEach(L=>{L instanceof Element&&(L.matches(dr)&&(Ce.log("Observer detected new root.",{target:Ve(L)}),s(L,fr(L))),L.querySelectorAll(dr).forEach(P=>{Ce.log("Observer detected new nested root.",{target:Ve(P)}),s(P,fr(P))}))}),A.removedNodes.forEach(L=>{L instanceof Element&&(t.has(L)&&(Ce.log("Observer detected removed root.",{target:Ve(L)}),a(L)),L.querySelectorAll(dr).forEach(P=>{t.has(P)&&(Ce.log("Observer detected removed nested root.",{target:Ve(P)}),a(P))}))})})});return C.observe(k,{childList:!0,subtree:!0}),()=>{C.disconnect(),Ce.log("Observer stopped."),r.forEach((A,L)=>{Ah(L,k)&&(A(),r.delete(L))});const S=[];k instanceof Element&&k.matches(dr)&&S.push(k),k.querySelectorAll(dr).forEach(A=>{S.push(A)}),S.forEach(A=>{t.has(A)&&a(A)})}}}}const Vi=2e3,eg=5e3,tg=5e3,rg=12e4;async function ji(t){await new Promise(e=>{window.setTimeout(e,Math.max(t,0))})}async function ng(t,{timeoutMs:e=5e3,intervalMs:r=30}={}){const n=Date.now();for(;Date.now()-n<e;){const i=t();if(i)return i;await ji(r)}throw new Error("Timed out waiting for async condition.")}function Ga(t,e){let r=null;return(...n)=>{r!==null&&window.clearTimeout(r),r=window.setTimeout(()=>{t(...n)},Math.max(e,0))}}function ig(t){const e=String(t||"asyncDefer").toLowerCase();return{async:e.includes("async"),defer:e.includes("defer")}}function Ja(t,e){const r=Array.from(t.querySelectorAll(`input[name="${e}"], textarea[name="${e}"]`));for(const n of r){const i=String(n.value||"").trim();if(i!=="")return i}return""}function si(t,e){return e.some(r=>Ja(t,r)!=="")}function Mh(t,e){e.forEach(r=>{Array.from(t.querySelectorAll(`input[name="${r}"], textarea[name="${r}"]`)).forEach(i=>{i.value=""})})}function Za(t,e,{value:r="",container:n}={}){let i=t.querySelector(`input[name="${e}"]`);if(!i){i=document.createElement("input"),i.type="hidden",i.name=e;const a=n||(t instanceof HTMLElement?t:null);a==null||a.appendChild(i)}return i.value=r,i}async function Qa(t,e,r){if(si(t,e))return!0;const n=Date.now()+Math.max(r,0);for(;Date.now()<n;)if(await ji(120),si(t,e))return!0;return!1}const Nh=new Set(["handle","placeholderSelector","errorMessage","sessionKey","value"]),Rh="[data-formie-captcha-error-container]",Oh=["formie:page:navigate","formie:page:navigate:after","formie:submit:result"],Ph=new Set(["formie:page:navigate","formie:page:navigate:after"]);function jr(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function xn(t,e){return t instanceof HTMLElement&&t.matches(e)?[t,...Array.from(t.querySelectorAll(e))]:Array.from(t.querySelectorAll(e))}function li(t){if(!(t instanceof HTMLElement)||!t.isConnected||t.hidden||t.closest("[hidden]")||t.closest("[data-formie-page-hidden]")||t.closest('[aria-hidden="true"]'))return!1;const e=window.getComputedStyle(t);return e.display!=="none"&&e.visibility!=="hidden"&&t.getClientRects().length>0}function Hn(t,e){const r=xn(t,e);return r.find(n=>li(n))||r[0]||null}function $h(t){t.innerHTML="";const e=document.createElement("div");return t.appendChild(e),e}function ci(t){var e;(e=t==null?void 0:t.querySelector(Rh))==null||e.remove()}function Fh(t,e,r){if(!t)return;ci(t);const n=document.createElement("div");n.setAttribute("data-formie-captcha-error-container",""),n.setAttribute("aria-live","polite"),n.setAttribute("aria-atomic","true"),Fe(n,r||t,"fieldErrors");const i=document.createElement("div");i.setAttribute("data-formie-captcha-error",""),i.setAttribute("role","alert"),Fe(i,r||t,"fieldError"),i.textContent=e,n.appendChild(i),t.appendChild(n)}function Dh(t){const e=t instanceof CustomEvent?t.detail:null;return!e||typeof e!="object"?null:e}function zh(t,e){if(!(t!=null&&t.captchas)||typeof t.captchas!="object")return null;const r=t.captchas[e];return!r||typeof r!="object"?null:r}function Vh(t,e,r,n){const i=new Set,a=()=>{const f=xn(t,e),g=new Set(f.filter(b=>li(b)));f.forEach(b=>{g.has(b)&&!i.has(b)&&(i.add(b),r(b))}),Array.from(i).forEach(b=>{g.has(b)||(i.delete(b),n(b))})},o=Ga(a,20),s=new MutationObserver(()=>{o()});s.observe(t,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["class","style","hidden","aria-hidden","data-formie-page-hidden"]});const u=[jr(window,"resize",()=>{o()}),...Oh.map(f=>jr(t,f,()=>{if(Ph.has(f)){a();return}o()}))];return a(),{cleanup:()=>{s.disconnect(),u.forEach(f=>{f()}),Array.from(i).forEach(f=>{n(f)}),i.clear()},reconcile:o,reconcileImmediate:a,getVisible:()=>xn(t,e).filter(f=>li(f))}}function jh(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function qh(t,e,{defaultPlaceholderSelector:r,defaultTokenFieldNames:n=[],defaultWaitForValueMs:i=Vi}){const a=e||{},o=Object.entries(a).reduce((y,[E,T])=>(Nh.has(E)||(y[E]=T),y),{}),s=n.map(String).filter(Boolean),u=Number(i),f=typeof a.placeholderSelector=="string"&&a.placeholderSelector.trim()!==""?a.placeholderSelector.trim():r,g=typeof a.errorMessage=="string"&&a.errorMessage.trim()!==""?a.errorMessage.trim():Ct("Captcha challenge must be completed."),b=typeof a.sessionKey=="string"&&a.sessionKey.trim()!==""?a.sessionKey.trim():null,p=typeof a.value=="string"?a.value:null;return{handle:jh(t,a),ui:{placeholderSelector:f,errorMessage:g},transport:{tokenFieldNames:s,waitForValueMs:Number.isFinite(u)?u:i,sessionKey:b,value:p},provider:o}}function Hh(t,e){const r=t.form||t.root,n=e.ui.placeholderSelector,i=e.handle;return{form:t.form,root:t.root,placeholder:{query:()=>xn(t.root,n),getPrimary:()=>Hn(t.root,n),observe:(a,o)=>Vh(t.root,n,a,o),createContainer:a=>$h(a),clear:a=>{a&&(ci(a),a.innerHTML="")}},errors:{getDefaultMessage:()=>e.ui.errorMessage,show:(a,o)=>{Fh(o||Hn(t.root,n),a||e.ui.errorMessage,t.form||t.root)},clear:a=>{ci(a||Hn(t.root,n))}},tokens:{names:e.transport.tokenFieldNames,has:(a=e.transport.tokenFieldNames,o=r)=>si(o,a),read:(a=e.transport.tokenFieldNames[0],o=r)=>a?Ja(o,a):"",write:(a,{names:o=e.transport.tokenFieldNames,root:s=r,container:u=t.form}={})=>{o.forEach(f=>{Za(s,f,{value:a,container:u})})},clear:(a=e.transport.tokenFieldNames,o=r)=>{Mh(o,a)},wait:(a=e.transport.waitForValueMs,o=e.transport.tokenFieldNames,s=r)=>Qa(s,o,a)},refresh:{providerHandle:i,onTokensRefreshed:a=>{const o=["formie:refresh-tokens:after","formie:refresh-tokens:refreshed"].map(s=>jr(t.root,s,u=>{const f=Dh(u),g=zh(f,i);g&&a(g)}));return()=>{o.forEach(s=>{s()})}}},events:{onRoot:(a,o)=>jr(t.root,a,o),onForm:(a,o)=>t.form?jr(t.form,a,o):()=>{}}}}const er=yt("captchas");function Xa({moduleId:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r=[],defaultWaitForValueMs:n=Vi,setup:i}){return{moduleId:t,version:2,surfaces:["server-rendered","client-rendered"],kind:"captcha",match:()=>!0,setup:async a=>{var b;const o=qh(t.split(":")[1],a.options||{},{defaultPlaceholderSelector:e,defaultTokenFieldNames:r,defaultWaitForValueMs:n});er.log("Setup module.",{moduleId:t,placeholderSelector:o.ui.placeholderSelector,tokenFieldNames:o.transport.tokenFieldNames});let s;if(a.surface==="client-rendered"&&!a.root.querySelector(o.ui.placeholderSelector)){const p=(b=/^\[(data-[a-z0-9-]+)\]$/.exec(o.ui.placeholderSelector))==null?void 0:b[1];if(!p)throw new Error("The CAPTCHA needs a supported placeholder in the client-rendered form.");s=document.createElement("div"),s.setAttribute(p,""),(a.form??a.root).append(s)}const u=Hh(a,o),f=await i({...a,options:o,services:u});if(!f){s==null||s.remove();return}const g=f.destroy;return{...f,destroy:async()=>{await g(),s==null||s.remove()}}}}}function Uh({moduleId:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r=[],defaultWaitForValueMs:n=Vi}){return Xa({moduleId:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r,defaultWaitForValueMs:n,setup:async({services:i,options:a,root:o})=>{const s=[];let u=i.placeholder.getPrimary(),f=a.transport.sessionKey,g=a.transport.value||"";const b=y=>{!y||!f||(y.innerHTML="",Za(y,f,{value:g,container:y}))},p=i.placeholder.observe(y=>{u=y,er.log("Passive placeholder visible.",{moduleId:t}),b(y)},y=>{u===y&&(u=i.placeholder.getPrimary()),y.innerHTML=""});return s.push(p.cleanup),b(u),s.push(i.refresh.onTokensRefreshed(y=>{f=typeof y.sessionKey=="string"&&y.sessionKey.trim()!==""?y.sessionKey.trim():f,g=typeof y.value=="string"?y.value:"";const E=i.placeholder.getPrimary()||u;u=E,b(E)})),{destroy:()=>{s.forEach(y=>{y()})},beforeSubmit:async y=>{if(y.action!=="submit")return;const E=f?[f]:a.transport.tokenFieldNames;if(E.length===0)return;if(!await Qa(o,E,a.transport.waitForValueMs)){const v=i.errors.getDefaultMessage();i.errors.show(v,u),er.warn("Passive captcha missing token.",{moduleId:t,tokenFieldNames:E}),y.abort(v)}}}}})}function Bh(t){return Xa({moduleId:t.moduleId,defaultPlaceholderSelector:t.defaultPlaceholderSelector,defaultTokenFieldNames:t.defaultTokenFieldNames,setup:async e=>{const r=[],n=new Map,i=new Map;let a=e.services.placeholder.getPrimary(),o=!1,s,u=null;const f=async()=>(u||(er.log("Loading captcha provider API.",{moduleId:t.moduleId}),u=t.load(e)),u),g=async E=>{const T=n.get(E);if(e.services.errors.clear(E),!T){E.innerHTML="";return}const v=await f();t.unmount&&await t.unmount({api:v,widget:T,placeholder:E,services:e.services,options:e.options,provider:e.options.provider}),n.delete(E),E.innerHTML="",e.services.tokens.clear(),er.log("Unmounted captcha placeholder widget.",{moduleId:t.moduleId}),a===E&&(a=e.services.placeholder.getPrimary())},b=async E=>{if(o||n.has(E))return;if(i.has(E))return i.get(E);const T=(async()=>{const v=await f();if(o||n.has(E))return;const k=e.services.placeholder.createContainer(E),C=await t.mount({api:v,placeholder:E,container:k,services:e.services,options:e.options,provider:e.options.provider});n.set(E,C),a=E,er.log("Mounted captcha placeholder widget.",{moduleId:t.moduleId})})().finally(()=>{i.delete(E)});i.set(E,T),await T},p=e.services.placeholder.observe(E=>{a=E,b(E).catch(T=>{s=T})},E=>{g(E)});r.push(p.cleanup);const y=async E=>{const v=p.getVisible();if(t.reset){const k=await f();for(const C of v){const S=n.get(C);if(!S){await b(C);continue}await t.reset({api:k,widget:S,placeholder:C,services:e.services,options:e.options,provider:e.options.provider,reason:E}),e.services.tokens.clear(),e.services.errors.clear(C)}p.reconcile();return}for(const k of Array.from(n.keys()))await g(k);for(const k of v)await b(k);p.reconcile()};r.push(e.services.events.onRoot("formie:submit:result",E=>{const T=E instanceof CustomEvent?E.detail:null;(T==null?void 0:T.stage)!=="validate"&&((T==null?void 0:T.ok)===!1&&(T==null?void 0:T.stage)==="challenge"||(T==null?void 0:T.ok)!==!0&&y("submit-result"))})),e.form&&r.push(e.services.events.onForm(Xn("reset"),()=>{a=e.services.placeholder.getPrimary()||a,window.setTimeout(()=>{y("reset-state")},0)})),p.reconcileImmediate();try{if(await Promise.all([...i.values()]),s)throw s}catch(E){throw o=!0,r.forEach(T=>T()),E}return{assertReady:()=>{if(s)throw s},destroy:async()=>{o=!0,r.forEach(E=>{E()});for(const E of Array.from(n.keys()))await g(E)},beforeSubmit:async E=>{if(E.action!=="submit")return;p.reconcileImmediate();const T=p.getVisible();if(T.length===0)return;let v=T.find(S=>S===a)||T[0];await b(v),v=a||v,e.services.errors.clear(v);const k=n.get(v);if(!k){const S=e.services.errors.getDefaultMessage();e.services.errors.show(S,v),er.warn("Captcha widget unavailable at challenge stage.",{moduleId:t.moduleId}),E.abort(S);return}const C=await f();await t.challenge({api:C,widget:k,placeholder:v,services:e.services,options:e.options,provider:e.options.provider,stageCtx:E})}}}})}const og=Bh,ag=Uh,Ro=2500,Yh={bpoint:["bpointToken"],stripe:["stripePaymentIntentId"],paypal:["paypalOrderId","paypalAuthId"],payway:["paywayTokenId"],opayo:["opayoTokenId"],eway:["ewayTokenData"],"go-cardless":["goCardlessRedirectId"],mollie:["molliePaymentId"],moneris:["monerisTokenId"],paddle:["paddleTransactionId"],square:["squarePaymentId"]};function Wh(t){return t.replace("{field:","").replace("{","").replace("}","").replace("]","").split("[").join("][")}function Kh(t){return`fields[${Wh(t)}]`}function Gh(t,e){const r=Kh(e),n=Array.from(t.querySelectorAll(`[name="${r}"]`)),i=Array.from(t.querySelectorAll(`[name="${r}[]"]`));return(i.length?i:n).filter(a=>a instanceof HTMLElement)}function Oo(t,e){var n,i,a;const r=Gh(t,e);for(const o of r){const s=o.closest("[data-formie-field-handle]"),u=(a=(i=(n=s==null?void 0:s.querySelector("[data-formie-field-label]"))==null?void 0:n.childNodes[0])==null?void 0:i.textContent)==null?void 0:a.trim();if(u)return u}return""}function Un(t){let e=t.replace(/[^\d.,-]/g,"");const r=e.includes(","),n=e.includes(".");if(r&&n)e.lastIndexOf(",")>e.lastIndexOf(".")?e=e.replace(/\./g,"").replace(",","."):e=e.replace(/,/g,"");else if(r&&!n){const i=e.split(",");i.length===2&&i[1].length===3&&/^\d+$/.test(i[0])&&/^\d+$/.test(i[1])?e=i[0]+i[1]:e=e.replace(",",".")}else e=e.replace(/,/g,"");return parseFloat(e)}function Jh(t){return t.replace(/^\{field:/,"").replace(/^\{/,"").replace(/\}$/,"").trim()}function kr(t){return Jh(t).replace(/\]/g,"").split("[").join(".").replace(/\.+/g,".").replace(/^\./,"").replace(/\.$/,"")}function ui(t){const r=kr(t).split(".").filter(Boolean);if(!r.length)return"";const[n,...i]=r;return`fields[${n}]${i.map(a=>`[${a}]`).join("")}`}function Zh(t){const r=String(t||"").trim().match(/^fields\[([^\]]+)\](.*)$/);if(!r)return"";const n=r[1]||"",i=r[2]||"",a=Array.from(i.matchAll(/\[([^\]]+)\]/g)).map(o=>o[1]||"").filter(Boolean);return[n,...a].join(".")}function Qh(t){const e=String(t||"").trim(),r=vi(e),n=e.startsWith("{"),i=r.isValid&&r.target==="field";return{raw:e,target:i?"field":"",key:i?kr(r.identifier):n?"":kr(e),selector:r.selector,defaultValue:r.default,transforms:r.transformerId?[{id:r.transformerId,params:r.transformerParams}]:[],isToken:n,isValid:n?i:e!==""}}function Xh(t){return t instanceof HTMLInputElement||t instanceof HTMLTextAreaElement||t instanceof HTMLSelectElement}function ep(t,e,r){const n=e.trim(),i=String(r.name||"").trim();if(!n||!i)return;const a=t.get(n)||{key:n,names:[],inputs:[]};a.names.includes(i)||a.names.push(i),a.inputs.includes(r)||a.inputs.push(r),t.set(n,a)}function tp(t){const e=new Map;return Array.from(t.querySelectorAll("[name]")).filter(n=>Xh(n)).forEach(n=>{const i=Zh(n.name);i&&ep(e,i,n)}),e}function rp(t){if(!t.length)return"";const e=t[0];if(e instanceof HTMLSelectElement&&e.multiple)return Array.from(e.selectedOptions).map(n=>n.value);if(t.some(n=>n instanceof HTMLInputElement&&(n.type==="checkbox"||n.type==="radio"))){const n=t.flatMap(i=>!(i instanceof HTMLInputElement)||!i.checked?[]:[i.value]);return n.length>1?n:n[0]||""}return e.value}function np(t,e){return t.get(kr(e))||null}function ip(t,e,r){const n=t.trim().startsWith("{")?t:`{field:${encodeURIComponent(e)}}`,i=vi(n),a=`field:${i.identifier}`,o=i.selector?`${a}:${i.selector}`:a,s=Jd(n,{definitions:{[a]:{id:a,selectors:i.selector?[i.selector]:[],availability:{server:!0,browser:!0}}},values:{[o]:r}});return{key:e,value:s.diagnostic?"":s.value,found:!s.diagnostic,diagnostic:s.diagnostic}}function pr(t,e){const r=Qh(t),n=r.key,i=r.selector?`${n}.${r.selector.replace(/:/g,".")}`:n,a=r.isValid?np(e,i):null;return a?ip(t,n,rp(a.inputs)):{key:n,value:"",found:!1,diagnostic:r.isValid?r.selector?"invalidSelector":"missingField":"invalidExpression"}}const es=new Set(["first","last","index","all","count","rows"]);function Po(t){return t.replace(/[.*+?^${}()|[\]\\]/g,"\\$&")}function op(t,e){const r=String(t||"").trim().toLowerCase();if(!r||e<=0)return[];if(r==="even"){const a=[];for(let o=1;o<=e;o++)o%2===0&&a.push(o-1);return a}if(r==="odd"){const a=[];for(let o=1;o<=e;o++)o%2===1&&a.push(o-1);return a}const n=r.match(/^every:(\d+)$/);if(n){const a=Math.max(1,Number.parseInt(n[1]||"1",10)),o=[];for(let s=1;s<=e;s+=a)o.push(s-1);return o}const i=[];return r.split(/\s*,\s*/).forEach(a=>{const o=a.trim();if(!o)return;const s=o.match(/^(\d+)\s*-\s*(\d+)$/);if(s){let f=Number.parseInt(s[1]||"0",10),g=Number.parseInt(s[2]||"0",10);f>g&&([f,g]=[g,f]);for(let b=Math.max(1,f);b<=Math.min(e,g);b++)b>=1&&b<=e&&i.push(b-1);return}const u=Number.parseInt(o,10);Number.isFinite(u)&&u>=1&&u<=e&&i.push(u-1)}),[...new Set(i)].sort((a,o)=>a-o)}function ts(t){const e=kr(t),r=e.split(".").filter(Boolean);return r.length<2?{fieldKey:e,columnKey:r[r.length-1]||""}:r.length>=3&&/^\d+$/.test(r[1]||"")?{fieldKey:r[0]||"",columnKey:r.slice(2).join(".")}:{fieldKey:r[0]||"",columnKey:r.slice(1).join(".")}}function rs(t,e,r){const n=new RegExp(`^${Po(t)}\\.(\\d+)\\.${Po(e)}$`);return[...r.keys()].filter(i=>n.test(i)).sort((i,a)=>{const o=Number.parseInt(i.split(".")[1]||"0",10),s=Number.parseInt(a.split(".")[1]||"0",10);return o-s})}function ap(t,e){return pr(t,e).value}function sg(t,e,r){const n=new Set,{fieldKey:i,columnKey:a}=ts(t),o=String(e.scope||"").trim().toLowerCase();if(!i||!a||!es.has(o)){const u=ui(t);return u&&(n.add(u),n.add(`${u}[]`)),n}return rs(i,a,r).forEach(u=>{var b;const f=r.get(u);if((b=f==null?void 0:f.names)!=null&&b.length){f.names.forEach(p=>{n.add(p)});return}const g=ui(u);g&&(n.add(g),n.add(`${g}[]`))}),n}function lg(t,e,r){const n=String(e.scope||"").trim().toLowerCase();if(!n||!es.has(n))return pr(t,r);const{fieldKey:i,columnKey:a}=ts(t);if(!i||!a)return pr(t,r);const o=rs(i,a,r),s=o.map(u=>ap(u,r));if(n==="count")return{key:`${i}.${a}`,value:String(o.length),found:!0};if(n==="first")return{key:o[0]||`${i}.0.${a}`,value:s[0]??"",found:o.length>0};if(n==="last")return{key:o[o.length-1]||`${i}.0.${a}`,value:s[s.length-1]??"",found:o.length>0};if(n==="index"){const u=Number.parseInt(String(e.index??"0"),10),f=`${i}.${u}.${a}`;return pr(f,r)}if(n==="all"){const u=s.flatMap(f=>Array.isArray(f)?f:f===""?[]:[f]);return{key:`${i}.${a}`,value:u,found:u.length>0}}if(n==="rows"){const u=op(String(e.rows||""),o.length);if(u.length===0)return{key:`${i}.${a}`,value:"",found:!1};if(u.length===1)return{key:o[u[0]]||`${i}.${u[0]}.${a}`,value:s[u[0]]??"",found:!0};const f=u.flatMap(g=>{const b=s[g];return Array.isArray(b)?b:b===""?[]:[b]});return{key:`${i}.${a}`,value:f,found:f.length>0}}return pr(t,r)}function Bn(t){const e=t;return!e.closest("[data-formie-conditionally-hidden]")&&!e.closest("[data-formie-row-hidden]")&&!e.closest("[data-formie-page-hidden]")&&!e.closest("[hidden]")}function ns(t,e){const r=e.replace(/"/g,'\\"');return t.querySelector(`input[name$="[${r}]"]`)||t.querySelector(`input[name$="${r}"]`)}function un(t,e){const r=e.find(n=>{const i=ns(t,n);return!i||String(i.value||"").trim()===""});return{ok:!r,missingSuffix:r}}async function is(t,e,r){const n=un(t,e);if(n.ok)return n;const i=Date.now()+Math.max(r,0);for(;Date.now()<i;){await ji(120);const a=un(t,e);if(a.ok)return a}return un(t,e)}const sp=new Set(["handle","requiredInputSuffixes","waitForValueMs","errorMessage"]),$o="[data-payment-success]",Fo="[data-payment-error]";function lp(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function cp(t,e,r){const n=e||{},i=Object.entries(n).reduce((u,[f,g])=>(sp.has(f)||(u[f]=g),u),{}),a=Array.isArray(n.requiredInputSuffixes)?n.requiredInputSuffixes.map(String).filter(Boolean):r.defaultRequiredInputSuffixes||[],o=Number(n.waitForValueMs??r.defaultWaitForValueMs??Ro),s=typeof n.errorMessage=="string"&&n.errorMessage.trim()!==""?n.errorMessage.trim():"Payment authorization is incomplete.";return{handle:lp(t,n),transport:{requiredInputSuffixes:a,waitForValueMs:Number.isFinite(o)?o:Ro,errorMessage:s},provider:i}}function Do(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function up(t,e){const r=t.target,n=t.form,i=t.root,a=n||i,o=e.transport.requiredInputSuffixes,s=()=>tp(n||i),u=S=>{const L=pr(S,s()).value;return Array.isArray(L)?L[0]||"":String(L||"")};return{root:i,form:n,field:r,updateInputs:(S,A)=>{const L=Array.isArray(S)?S:[S];for(const P of L){const N=ns(a,P)??r.querySelector(`input[name*="${P}"]`);N&&(N.value=A)}},addError:S=>{const A=r.querySelector("[data-formie-field-type] > div, [data-field-type] > div")||r,L=A.querySelector(Fo);L&&L.remove();const P=document.createElement("div");P.setAttribute("data-payment-error",""),P.textContent=S,Fe(P,n||i,"fieldError"),A.appendChild(P)},removeError:()=>{var S;(S=r.querySelector(Fo))==null||S.remove()},addSuccess:S=>{const A=r.querySelector("[data-formie-field-type] > div, [data-field-type] > div")||r,L=A.querySelector($o);L&&L.remove();const P=document.createElement("div");P.setAttribute("data-payment-success",""),P.textContent=S,Fe(P,n||i,"successMessage"),A.appendChild(P)},removeSuccess:()=>{var S;(S=r.querySelector($o))==null||S.remove()},hasToken:()=>un(a,o).ok,waitForToken:(S=e.transport.waitForValueMs)=>is(a,o,S).then(A=>A.ok),getFieldValue:(S,A="string")=>{const L=u(S);return A==="float"||A==="int"||A==="number"?Un(L):L},resolveAmount:S=>{const A=n||i,P=String(S.type||"").toLowerCase()==="dynamic"&&typeof S.variable=="string"&&S.variable.trim()!=="",N=S.value??(P?S.variable:S.fixed),ie=String(N??"").trim(),te=typeof N=="number"?N:Un(ie);if(Number.isFinite(te)&&te>0)return{ok:!0,value:te};if(ie!==""){const ue=u(ie),V=Un(ue);if(Number.isFinite(V)&&V>0)return{ok:!0,value:V};const K=Oo(A,ie);if(!ue)return{ok:!1,error:K?Ct('Provide a value for "{label}" to proceed.',{label:K}):Ct("Provide a payment amount to proceed.")}}return{ok:!1,error:Ct("Payment amount must be greater than 0.")}},resolveCurrency:S=>{const A=n||i,P=String(S.type||"").toLowerCase()==="dynamic"&&typeof S.variable=="string"&&S.variable.trim()!=="",N=S.value??(P?S.variable:S.fixed??S.defaultCurrency??""),ie=String(N??"").trim(),te=ie.toUpperCase();if(/^[A-Z]{3}$/.test(te)&&!P)return{ok:!0,value:te};if(ie!==""){const ue=String(u(ie)||"").trim(),V=ue.toUpperCase();if(/^[A-Z]{3}$/.test(V))return{ok:!0,value:V};const K=Oo(A,ie);if(!ue)return{ok:!1,error:K?Ct('Provide a value for "{label}" to proceed.',{label:K}):Ct("Provide a payment currency to proceed.")}}return{ok:!1,error:Ct("Payment currency must be a valid 3-letter code.")}},watchFieldValueChanges:(S,A,L=600)=>{const P=n||i,N=S.map(K=>String(K||"").trim()).filter(Boolean);if(N.length===0)return()=>{};const ie=s(),te=new Set;N.forEach(K=>{var U;const Y=kr(K),I=ie.get(Y);if((U=I==null?void 0:I.names)!=null&&U.length){I.names.forEach(J=>{te.add(J)});return}const R=ui(Y);R&&(te.add(R),te.add(`${R}[]`))});const ue=Ga(()=>{A()},L),V=K=>{const Y=K.target,I=(Y==null?void 0:Y.name)||"";!I||!te.has(I)||ue()};return P.addEventListener("input",V),P.addEventListener("change",V),()=>{P.removeEventListener("input",V),P.removeEventListener("change",V)}},triggerSubmit:()=>{n&&n.setAttribute("data-formie-internal-resubmit","true"),n&&typeof n.requestSubmit=="function"?n.requestSubmit():n&&n.submit()},releaseSubmitLoading:()=>{n&&(n.removeAttribute("data-formie-internal-resubmit"),yn(n))},getBillingData:S=>{const A={};if(!S||typeof S!="object")return{billing_details:A};if(S.billingName){const L=u(S.billingName);L&&(A.name=L)}if(S.billingEmail){const L=u(S.billingEmail);L&&(A.email=L)}if(S.billingAddress){const L=S.billingAddress,P={},N=u(`${L}.address1`),ie=u(`${L}.address2`),te=u(`${L}.address3`),ue=u(`${L}.city`),V=u(`${L}.zip`),K=u(`${L}.state`),Y=u(`${L}.country`);N&&(P.line1=N),ie&&(P.line2=ie),te&&(P.line3=te),ue&&(P.city=ue),V&&(P.postal_code=V),K&&(P.state=K),Y&&(P.country=Y),Object.keys(P).length&&(A.address=P)}return{billing_details:A}},events:{onForm:(S,A)=>n?Do(n,S,A):()=>{},onRoot:(S,A)=>Do(i,S,A)}}}const _t=yt("payments");function dp(t){const e=t.defaultRequiredInputSuffixes??Yh[t.moduleId.split(":")[1]]??[];return{moduleId:t.moduleId,version:2,surfaces:["server-rendered","client-rendered"],kind:"payment",match:r=>{var n,i;return!!(r.target.querySelector('[data-formie-field-type="payment"]')||r.target.closest('[data-formie-field-type="payment"]')||((i=(n=r.target).getAttribute)==null?void 0:i.call(n,"data-formie-field-type"))==="payment")},setup:async r=>{const n=r.target,i=n.__formiePaymentModuleRegistry||{};n.__formiePaymentModuleRegistry=i;const a=r.entryKey??t.moduleId,o=i[a];if(o!=null&&o.destroy){_t.warn("Found stale payment module instance; destroying previous.",{moduleId:t.moduleId});try{await o.destroy()}catch{}}const s=cp(t.moduleId.split(":")[1],r.options||{},{defaultRequiredInputSuffixes:e}),u=up(r,s),f={...r,options:s,services:u},g=[];let b=null,p=null,y=null,E=null;const T=async()=>(b||(_t.log("Loading payment provider API.",{moduleId:t.moduleId}),b=t.load(f)),b),v=async()=>{if(!t.mount||p||!Bn(r.target))return;const S=await T();try{p=await t.mount({api:S,field:r.target,services:u,options:s,provider:s.provider}),_t.log("Payment widget mounted.",{moduleId:t.moduleId,handle:s.handle})}catch(A){throw _t.warn("Payment widget mount failed.",{moduleId:t.moduleId,handle:s.handle}),A}};if(g.push(r.on("formie:submit:before",()=>{u.removeError(),u.removeSuccess()})),t.setup){const S=r.root||r.form||r.target;y=await t.setup({...f,root:S}),y.destroy&&g.push(y.destroy)}t.mount&&Bn(r.target)&&await v(),["formie:page:navigate:after","formie:submit:result"].forEach(S=>{const A=()=>{v()};r.root.addEventListener(S,A),g.push(()=>{r.root.removeEventListener(S,A)})}),g.push(r.on("formie:conditions:evaluated",()=>{v()}));const C=async()=>{var S;if(_t.log("Destroying payment module.",{moduleId:t.moduleId,handle:s.handle}),g.forEach(A=>A()),p&&t.unmount){const A=await T();await t.unmount({api:A,widget:p,field:r.target,services:u,options:s,provider:s.provider}),_t.log("Payment widget unmounted.",{moduleId:t.moduleId,handle:s.handle})}((S=i[a])==null?void 0:S.destroy)===C&&delete i[a],_t.log("Payment module destroy complete.",{moduleId:t.moduleId,handle:s.handle})};return i[a]={destroy:C},{destroy:C,beforeSubmit:async S=>{if(y!=null&&y.beforeSubmit){await y.beforeSubmit(S);return}if(S.action!=="submit"||!Bn(r.target))return;await v();const A=await T();if(t.onBeforePayment){E||(E=(async()=>t.onBeforePayment({api:A,widget:p,field:r.target,services:u,options:s,provider:s.provider,stageCtx:S}))().finally(()=>{E=null}));const N=await E;if(_t.log("onBeforePayment resolved.",{moduleId:t.moduleId,handle:s.handle,ok:N}),!N){S.abort(s.transport.errorMessage);return}return}if(s.transport.requiredInputSuffixes.length===0)return;const L=r.form||r.root,P=await is(L,s.transport.requiredInputSuffixes,s.transport.waitForValueMs);P.ok||(_t.warn("Required payment input(s) missing.",{moduleId:t.moduleId,handle:s.handle,missingSuffix:P.missingSuffix}),S.abort(s.transport.errorMessage))},afterSubmit:async(S,A)=>{if(!t.onAfterSubmit)return;const L=await t.onAfterSubmit({field:r.target,services:u,options:s,provider:s.provider,result:A});if(!(!(L!=null&&L.remount)||!t.mount)){if(p&&t.unmount){const P=await T();await t.unmount({api:P,widget:p,field:r.target,services:u,options:s,provider:s.provider})}p=null,await v()}}}}}}const cg=dp,fp="[data-formie-address-autocomplete-input]",zo="[data-formie-address-location]",Pt={autoComplete:"[data-formie-address-autocomplete-input]",address1:"[data-formie-address-line1-input]",address2:"[data-formie-address-line2-input]",address3:"[data-formie-address-line3-input]",city:"[data-formie-address-city-input]",state:"[data-formie-address-state-input]",zip:"[data-formie-address-zip-input]",country:"[data-formie-address-country-input]"},$t={autoComplete:"[data-formie-address-autocomplete-input]",address1:"[data-address1]",address2:"[data-address2]",address3:"[data-address3]",city:"[data-city]",state:"[data-state]",zip:"[data-zip]",country:"[data-country]"},mp={autoComplete:[Pt.autoComplete,$t.autoComplete],address1:[Pt.address1,$t.address1],address2:[Pt.address2,$t.address2],address3:[Pt.address3,$t.address3],city:[Pt.city,$t.city],state:[Pt.state,$t.state],zip:[Pt.zip,$t.zip],country:[Pt.country,$t.country]};function hp(t,e){for(const r of mp[e]){const n=t.querySelector(r);if(n instanceof HTMLInputElement||n instanceof HTMLSelectElement)return n}return null}const pp=new Set(["handle"]);function gp(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function bp(t,e){const r=e||{},n=Object.entries(r).reduce((i,[a,o])=>(pp.has(a)||(i[a]=o),i),{});return{handle:gp(t,r),provider:n}}function vp(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function yp(t){const e=t.target,r=t.form,n=t.root,i=fp;return{root:n,field:e,form:r,input:{getAutocomplete:()=>e.querySelector(i),setValue:(a,o,s)=>{const u=hp(e,a);if(!u)return;const f=o||s||"";u.value!==f&&(u.value=f,u.dispatchEvent(new Event("input",{bubbles:!0})),u.dispatchEvent(new Event("change",{bubbles:!0})))}},location:{getButton:()=>e.querySelector(zo),onUseLocation:a=>{const o=e.querySelector(zo);if(!o)return()=>{};const s=u=>{u.preventDefault(),navigator.geolocation&&navigator.geolocation.getCurrentPosition(a,()=>{},{enableHighAccuracy:!0})};return o.addEventListener("click",s),()=>{o.removeEventListener("click",s)}}},events:{onField:(a,o)=>vp(e,a,o)}}}const mr=yt("address");function Vo(t){const e=t;return!e.closest("[data-formie-page-hidden]")&&!e.closest("[hidden]")}function wp(t){return{moduleId:t.moduleId,version:2,surfaces:["server-rendered","client-rendered","cp-edit"],kind:"address",match:e=>!!e.target.querySelector("[data-formie-address-autocomplete-input]"),setup:async e=>{const r=bp(t.moduleId.split(":")[1],e.options||{}),n=yp(e);mr.log("Setup module.",{moduleId:t.moduleId});const i={...e,options:r,services:n},a=[];let o=null,s=null;if(!n.input.getAutocomplete())return console.warn(`[formie] Address module "${t.moduleId}" skipped: no autocomplete input found in target. Ensure the Address field has the Auto-Complete subfield enabled.`),mr.warn("Autocomplete input missing; skipping module.",{moduleId:t.moduleId}),{destroy:()=>{}};const f=async()=>(o||(mr.log("Loading provider API.",{moduleId:t.moduleId}),o=t.load(i)),o),g=async()=>{if(s||!Vo(e.target))return;const y=await f();s=await t.mount({api:y,field:e.target,services:n,options:r,provider:r.provider}),mr.log("Widget mounted.",{moduleId:t.moduleId})};Vo(e.target)&&await g(),["formie:page:navigate:after","formie:submit:result"].forEach(y=>{const E=()=>{g()};e.root.addEventListener(y,E),a.push(()=>{e.root.removeEventListener(y,E)})});const p=n.location.onUseLocation(y=>{t.onCurrentLocation&&(async()=>{var T;if(await g(),!s)return;const E=await f();await((T=t.onCurrentLocation)==null?void 0:T.call(t,y,{api:E,widget:s,field:e.target,services:n,options:r,provider:r.provider}))})()});return p&&a.push(p),{destroy:async()=>{if(mr.log("Destroying module.",{moduleId:t.moduleId}),a.forEach(y=>y()),s&&t.unmount){const y=await f();await t.unmount({api:y,widget:s,field:e.target,services:n,options:r,provider:r.provider}),mr.log("Widget unmounted.",{moduleId:t.moduleId})}}}}}}const ug=wp;function xp(t){const e=t.getElementById("formie-preview-config");if(!(e instanceof HTMLScriptElement)||!e.textContent)return{};try{return JSON.parse(e.textContent)}catch(r){return console.warn("[FormiePreview] Failed to parse preview config.",r),{}}}function Ep(t,e){if(!(e!=null&&e.length))return;const r={contractVersion:2,surface:"server-rendered",entries:e.map((i,a)=>({...i,key:`preview:${a}:${i.moduleId}`,config:i.config??{},required:i.required??!0}))},n=JSON.stringify(r);t.querySelectorAll("[data-formie], [data-formie-form]").forEach(i=>{i.setAttribute("data-formie-modules",n)})}function kp(t){var u,f,g;const e=t.body,r=(u=t.defaultView)==null?void 0:u.HTMLElement;if(!e)return((f=t.documentElement)==null?void 0:f.scrollHeight)||0;const n=e.getBoundingClientRect(),i=(g=t.defaultView)==null?void 0:g.getComputedStyle(e),a=parseFloat(i.paddingTop||"0")||0,o=parseFloat(i.paddingBottom||"0")||0,s=Array.from(e.children).reduce((b,p)=>{if(!r||!(p instanceof r)||p.tagName==="SCRIPT")return b;const y=p.getBoundingClientRect();return Math.max(b,y.bottom-n.top)},a);return Math.ceil(s+o)}function gr(t,e){var n;const r=kp(t.document);e==null||e(r),(n=t.parent)==null||n.postMessage({type:"formie-preview:height",height:r},"*")}function Sp(t,e){const r=t.document;if(typeof t.ResizeObserver<"u"){const n=new t.ResizeObserver(()=>{gr(t,e)});n.observe(r.documentElement),r.body&&n.observe(r.body)}["click","input","change"].forEach(n=>{r.addEventListener(n,()=>{t.requestAnimationFrame(()=>{gr(t,e)})},!0)})}async function _p(t,e){var i;const r=t.document,n=xp(r);Sp(t,e),t.addEventListener("load",()=>{gr(t,e)},{once:!0}),t.requestAnimationFrame(()=>{gr(t,e),t.requestAnimationFrame(()=>{gr(t,e)})}),(i=n.modules)!=null&&i.length&&(sf(!1),Ep(r,n.modules),await Lh().scan(r)),gr(t,e)}const Ap=Object.assign({"../../../browser/ui-reference/examples/address.preview.ts":()=>q(()=>import("./address.preview.D-ghwOAm.js"),[]),"../../../browser/ui-reference/examples/agree.preview.ts":()=>q(()=>import("./agree.preview.BuDgdg1_.js"),[]),"../../../browser/ui-reference/examples/buttons-loading.preview.ts":()=>q(()=>import("./buttons-loading.preview.BvDn73XT.js"),[]),"../../../browser/ui-reference/examples/buttons-positions.preview.ts":()=>q(()=>import("./buttons-positions.preview.B-G789jX.js"),[]),"../../../browser/ui-reference/examples/buttons-variants.preview.ts":()=>q(()=>import("./buttons-variants.preview.0jJSmcOh.js"),[]),"../../../browser/ui-reference/examples/buttons.preview.ts":()=>q(()=>import("./buttons.preview.MzXYysPp.js"),[]),"../../../browser/ui-reference/examples/calculations.preview.ts":()=>q(()=>import("./calculations.preview.DIvPq9FO.js"),[]),"../../../browser/ui-reference/examples/categories.preview.ts":()=>q(()=>import("./categories.preview.ixyBoeER.js"),__vite__mapDeps([56,57])),"../../../browser/ui-reference/examples/checkboxes.preview.ts":()=>q(()=>import("./checkboxes.preview.BI4i9Rg-.js"),[]),"../../../browser/ui-reference/examples/date.preview.ts":()=>q(()=>import("./date.preview.AGwKSCVy.js"),[]),"../../../browser/ui-reference/examples/entries.preview.ts":()=>q(()=>import("./entries.preview.vVoUh2wl.js"),__vite__mapDeps([58,57])),"../../../browser/ui-reference/examples/field-anatomy.preview.ts":()=>q(()=>import("./field-anatomy.preview.CDGHSvef.js"),[]),"../../../browser/ui-reference/examples/field-normal.preview.ts":()=>q(()=>import("./field-normal.preview.CiEbz5Fv.js"),[]),"../../../browser/ui-reference/examples/file-upload.preview.ts":()=>q(()=>import("./file-upload.preview.CTvngf20.js"),[]),"../../../browser/ui-reference/examples/hidden.preview.ts":()=>q(()=>import("./hidden.preview.MMyPAdXC.js"),[]),"../../../browser/ui-reference/examples/loading-button-variants.preview.ts":()=>q(()=>import("./loading-button-variants.preview.DsRnArXp.js"),[]),"../../../browser/ui-reference/examples/loading-buttons.preview.ts":()=>q(()=>import("./loading-buttons.preview.BUXpUDz6.js"),[]),"../../../browser/ui-reference/examples/loading-sizes-colors.preview.ts":()=>q(()=>import("./loading-sizes-colors.preview.IYbMzHOV.js"),[]),"../../../browser/ui-reference/examples/loading.preview.ts":()=>q(()=>import("./loading.preview.DlOgX5Nv.js"),[]),"../../../browser/ui-reference/examples/messages.preview.ts":()=>q(()=>import("./messages.preview.Bpxa33ze.js"),[]),"../../../browser/ui-reference/examples/multi-line-text-rich-text.preview.ts":()=>q(()=>import("./multi-line-text-rich-text.preview.CSXZQJoF.js"),[]),"../../../browser/ui-reference/examples/multi-line-text.preview.ts":()=>q(()=>import("./multi-line-text.preview.CRb5IKJ_.js"),[]),"../../../browser/ui-reference/examples/page-navigation-only.preview.ts":()=>q(()=>import("./page-navigation-only.preview.D9zHiF02.js"),[]),"../../../browser/ui-reference/examples/payment.preview.ts":()=>q(()=>import("./payment.preview.DtictnrE.js"),[]),"../../../browser/ui-reference/examples/phone.preview.ts":()=>q(()=>import("./phone.preview.CcZ4XbYB.js"),[]),"../../../browser/ui-reference/examples/progress.preview.ts":()=>q(()=>import("./progress.preview.kV7Ij1sV.js"),[]),"../../../browser/ui-reference/examples/radio.preview.ts":()=>q(()=>import("./radio.preview.DrkMq2KR.js"),[]),"../../../browser/ui-reference/examples/recipients.preview.ts":()=>q(()=>import("./recipients.preview.BWBx9rU1.js"),__vite__mapDeps([59,57])),"../../../browser/ui-reference/examples/repeater.preview.ts":()=>q(()=>import("./repeater.preview.D8tF_U4K.js"),[]),"../../../browser/ui-reference/examples/signature.preview.ts":()=>q(()=>import("./signature.preview.jxyPmfVO.js"),[]),"../../../browser/ui-reference/examples/single-line-text.preview.ts":()=>q(()=>import("./single-line-text.preview.BmmellSY.js"),[]),"../../../browser/ui-reference/examples/summary.preview.ts":()=>q(()=>import("./summary.preview.By_O1ubB.js"),[]),"../../../browser/ui-reference/examples/table.preview.ts":()=>q(()=>import("./table.preview.CyMZLWmn.js"),[]),"../../../browser/ui-reference/examples/tags.preview.ts":()=>q(()=>import("./tags.preview.CmHYrzId.js"),[]),"../../../browser/ui-reference/examples/upload-manager.preview.ts":()=>q(()=>import("./upload-manager.preview.DTc5MOwe.js"),[])});function Tp(t){const e=t.split(/[?#]/,1)[0]||"/";return e.endsWith("/")?e:`${e.slice(0,e.lastIndexOf("/")+1)}`}function Cp(t,e="/"){return e==="/"||!t.startsWith(e)?t:`/${t.slice(e.length)}`}function Ip(t,e,r="/"){return t.startsWith("@/")?`/${t.slice(2)}`:Cp(new URL(t,`https://docs.local${Tp(e)}`).pathname,r)}function Lp(t){return`../../../${t.replace(/^\//,"")}`}async function Mp(t,e,r="/"){const n=Ip(t,e,r),i=Lp(n),a=Ap[i];if(!a)return console.warn(`[FormiePreview] No preview source found for "${t}" resolved from "${e}".`),null;const o=await a();return o.default??o.preview??null}const Np=["srcdoc"],Rp=8,Op=gt({__name:"FormiePreview",props:{markup:{},minHeight:{default:120},src:{}},setup(t){const e=t,r=Uo(),{site:n}=Vt(),i=$e(null),a=$e(null),o=$e(e.minHeight);let s=0;pt(()=>[r.path,e.src,n.value.base],async()=>{if(!e.src){a.value=null;return}const v=++s,k=await Mp(e.src,r.path,n.value.base);v===s&&(a.value=k)},{immediate:!0});const u=fe(()=>{var v;return((v=a.value)==null?void 0:v.markup)??e.markup??""}),f=fe(()=>{var v;return((v=a.value)==null?void 0:v.minHeight)??e.minHeight}),g=fe(()=>{var k;const v=(k=a.value)==null?void 0:k.modules;return JSON.stringify({modules:v!=null&&v.length?v:void 0}).replaceAll("<","\\u003c")});pt(f,v=>{o.value=v},{immediate:!0}),pt(()=>[u.value,f.value],(v,k)=>{(!k||k[0]!==u.value||k[1]!==f.value)&&(o.value=f.value)});function b(v){!Number.isFinite(v)||v<=0||(o.value=Math.ceil(v+Rp))}function p(){var ie,te,ue;const v=(ie=i.value)==null?void 0:ie.contentDocument,k=v==null?void 0:v.body,C=(te=v==null?void 0:v.defaultView)==null?void 0:te.HTMLElement;if(!k)return f.value;const S=k.getBoundingClientRect(),A=(ue=v.defaultView)==null?void 0:ue.getComputedStyle(k),L=parseFloat((A==null?void 0:A.paddingTop)||"0")||0,P=parseFloat((A==null?void 0:A.paddingBottom)||"0")||0,N=Array.from(k.children).reduce((V,K)=>{if(!C||!(K instanceof C)||K.tagName==="SCRIPT")return V;const Y=K.getBoundingClientRect();return Math.max(V,Y.bottom-S.top)},L);return Math.ceil(N+P)}function y(v){var k,C;((k=v.data)==null?void 0:k.type)==="formie-preview:height"&&v.source===((C=i.value)==null?void 0:C.contentWindow)&&b(Number(v.data.height))}function E(){var k;const v=(k=i.value)==null?void 0:k.contentWindow;v&&(b(p()),_p(v,b))}zt(()=>{window.addEventListener("message",y)}),En(()=>{window.removeEventListener("message",y)});const T=fe(()=>`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    ${[rd,nd,id,od,ad,sd,ld,cd,ud,dd,fd,md,hd,pd,gd,bd,vd,yd,wd,xd,Ed,kd,Sd,_d,Ad,Td].join(`
`)}
    body { margin: 0; padding: 16px; background: #fff; }
  </style>
</head>
<body>
  <script id="formie-preview-config" type="application/json">${g.value}<\/script>
  ${u.value}
</body>
</html>`);return(v,k)=>(j(),H("iframe",{ref_key:"iframeRef",ref:i,class:"formie-preview-frame",style:Br({height:`${o.value}px`}),srcdoc:T.value,title:"Formie preview",loading:"lazy",onLoad:E},null,44,Np))}}),dg=td({enhanceApp({app:t}){t.component("FormiePreview",Op)}});export{bi as $,Xn as A,Ii as B,eg as C,Fp as D,Ti as E,Qp as F,Wp as G,Pt as H,yr as I,ji as J,Ga as K,qp as L,Xp as M,Ct as N,cg as O,Zp as P,Fe as Q,Bp as R,Er as S,Ud as V,Hp as X,Up as Z,Vp as _,Ea as a,Dp as b,og as c,ug as d,ig as e,hp as f,Gp as g,rg as h,ag as i,tg as j,yt as k,tp as l,sg as m,kr as n,ui as o,pr as p,Kp as q,lg as r,ki as s,dg as t,zp as u,jp as v,ng as w,Wl as x,Jp as y,Yp as z};
