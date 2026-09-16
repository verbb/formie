const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/chunks/address-finder.C1ltImMz.js","assets/chunks/scripts.DPvKGCHT.js","assets/chunks/framework.BBqb3frr.js","assets/chunks/google-address.CnehpJaP.js","assets/chunks/loqate.C6PQnSmq.js","assets/chunks/place-kit.BdaTZhSk.js","assets/chunks/styles.DqkRI_my.js","assets/chunks/captcha-eu.BZa4FKni.js","assets/chunks/friendly-captcha-v1.OavOb8JG.js","assets/chunks/friendly-captcha-v2.DiiUNSfB.js","assets/chunks/hcaptcha.DfkeCH6Z.js","assets/chunks/recaptcha-enterprise.CnBnUGHx.js","assets/chunks/recaptcha-shared.DlojcV0H.js","assets/chunks/recaptcha-v2-checkbox.JY0ePzKR.js","assets/chunks/recaptcha-v2-invisible.CKmKtBxa.js","assets/chunks/recaptcha-v3.D7OLAOBr.js","assets/chunks/snaptcha.DhFvFcQI.js","assets/chunks/turnstile.D1up_sRS.js","assets/chunks/calculations.Cdw7B92g.js","assets/chunks/index.DMwtCJXd.js","assets/chunks/shared.gyCsmJcM.js","assets/chunks/checkbox-radio.BlBiEoWm.js","assets/chunks/combobox.DFW53lRa.js","assets/chunks/conditions.DGqMljfH.js","assets/chunks/custom-google-maps.3VZuGKsq.js","assets/chunks/custom-link.CpwPdM8C.js","assets/chunks/custom-maps.B44Ve3DH.js","assets/chunks/date-picker.ZS2svB9w.js","assets/chunks/file-upload._8B-yicd.js","assets/chunks/upload-manager.DKIeJvxn.js","assets/chunks/hidden.Dhv-9mVa.js","assets/chunks/phone-country.BWhzZqXR.js","assets/chunks/country-from-ip.Dtgh513v.js","assets/chunks/password-validation.ByiGlq7v.js","assets/chunks/address-country.CEnK1PNj.js","assets/chunks/address-state.DEFZxp3h.js","assets/chunks/repeater.CMFzMUxl.js","assets/chunks/rich-text.BJQ9-KE8.js","assets/chunks/signature.Et7Wuj7v.js","assets/chunks/summary.Ddnf195w.js","assets/chunks/survey-likert.BCNZYGJh.js","assets/chunks/survey-presentations.3wSESoEp.js","assets/chunks/survey-rank.DcWUFdcz.js","assets/chunks/survey-rating.BQRYtYy7.js","assets/chunks/table.DBo2CcXR.js","assets/chunks/text-limit.Bxf4Q1ZN.js","assets/chunks/bpoint.2SllHOTA.js","assets/chunks/eway.tYfjvgM-.js","assets/chunks/go-cardless.7dVJZpH4.js","assets/chunks/mollie.CiX7sg7G.js","assets/chunks/moneris.C0UnTOVn.js","assets/chunks/opayo.UAIiyRmh.js","assets/chunks/paddle.DHSkygHS.js","assets/chunks/paypal.DrW-4Jzr.js","assets/chunks/payway.Du-OByOx.js","assets/chunks/square.Dh4knbJx.js","assets/chunks/stripe.N7KAxuoK.js","assets/chunks/categories.preview.ixyBoeER.js","assets/chunks/elementDisplayPreview.BQWAWWZ5.js","assets/chunks/entries.preview.vVoUh2wl.js","assets/chunks/recipients.preview.BWBx9rU1.js"])))=>i.map(i=>d[i]);
var Ti=Object.defineProperty;var Ci=(t,e,r)=>e in t?Ti(t,e,{enumerable:!0,configurable:!0,writable:!0,value:r}):t[e]=r;var ur=(t,e,r)=>Ci(t,typeof e!="symbol"?e+"":e,r);import{u as We,w as xe,a as jr,o as Ke,b as uo,c as B,r as ce,d as ke,e as L,f as R,n as le,F as ge,g as ve,h as Y,i as Ur,j as S,t as re,k as we,l as Ct,m as De,p as fo,q as Ye,s as G,v as rr,x as mo,y as $e,z as wt,_ as M,A as Ii,B as En,C as Li,D as Mi,E as Ri,G as Ze,H as xn,I as Fi,J as Oi,K as Pi,L as Lt,M as Ni,N as Di,T as zi,O as Tr,P as $i,Q as Vi,R as Hi,S as qi,U as kn,V as Bi,W as ji,X as Ui,Y as Ki,Z as Wi,$ as ho,a0 as Gi,a1 as _n}from"./framework.BBqb3frr.js";const Ji=/#.*$/,Yi=/[?#].*$/,Qi=/(?:(^|\/)index)?\.(?:md|html)$/;function Sn(t){return decodeURI(t).replace(Yi,"").replace(Qi,"$1")}function An(t){return/^\//.test(t)?t:`/${t}`}function Vt(t,e){return t.map(r=>{const n={...r},i=n.base||e;return i&&n.link&&(n.link=`${i}${n.link}`),n.items&&(n.items=Vt(n.items,i)),n})}function nr(t,e,r=!1){if(e===void 0)return!1;const n=Sn(`/${t}`);if(r)return new RegExp(e).test(n);if(Sn(e)!==n)return!1;const i=e.match(Ji);return i?typeof window<"u"&&window.location.hash===i[0]:!0}function St(t,e){var r;return e?nr(t,e.link)?!0:((r=e.items)==null?void 0:r.some(n=>St(t,n)))??!1:!1}function Zi(t,e){if(Array.isArray(t))return Vt(t);if(!t)return[];const r=An(e),n=Object.keys(t).sort((a,o)=>o.split("/").length-a.split("/").length).find(a=>r.startsWith(An(a))),i=n?t[n]:[];return Array.isArray(i)?Vt(i):Vt(i.items,i.base)}function Xi(t){const e=[];let r=0;for(const n of t){if(n.items){e.push({text:n.text,icon:n.icon,items:n.items}),r=e.length-1;continue}e[r]||(e.push({items:[]}),r=e.length-1),e[r].items.push(n)}return e}function Kr(){const{frontmatter:t,page:e,theme:r}=We(),n=ce(!1),i=B(()=>Zi(r.value.sidebar,e.value.relativePath)),a=B(()=>Xi(i.value)),o=B(()=>t.value.sidebar!==!1&&t.value.layout!=="home"&&i.value.length>0);xe(o,h=>{h||(n.value=!1)}),jr(h=>{if(typeof document>"u")return;const f=document.body.style.overflow;n.value&&typeof window<"u"&&window.innerWidth<1024&&(document.body.style.overflow="hidden"),h(()=>{document.body.style.overflow=f})});function s(){n.value=!0}function l(){n.value=!1}function c(){n.value=!n.value}return{isOpen:n,sidebar:i,sidebarGroups:a,hasSidebar:o,open:s,close:l,toggle:c}}function ea(t,e){let r=null;jr(()=>{r=t.value?document.activeElement:null});const n=i=>{i.key==="Escape"&&t.value&&(e(),r instanceof HTMLElement&&r.focus())};Ke(()=>{window.addEventListener("keyup",n)}),uo(()=>{window.removeEventListener("keyup",n)})}const ta=["d","fill"],or=ke({__name:"DocsIcon",props:{name:{default:""},class:{default:"size-4"}},setup(t){const e=t,r={"play-circle":{paths:[{d:"M8 14.25A6.25 6.25 0 1 0 8 1.75a6.25 6.25 0 0 0 0 12.5"},{d:"M6.25 5.75 10.25 8l-4 2.25V5.75",fill:"currentColor"}]},"app-window":{paths:[{d:"M2.75 4.25A1.5 1.5 0 0 1 4.25 2.75h7.5a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5 1.5h-7.5a1.5 1.5 0 0 1-1.5-1.5v-7.5Z"},{d:"M2.75 5.5h10.5"},{d:"M5 4.125h.01M7 4.125h.01M9 4.125h.01"}]},blocks:{paths:[{d:"M2.75 3.25h4.5v4.5h-4.5z"},{d:"M8.75 3.25h4.5v4.5h-4.5z"},{d:"M5.75 8.75h4.5v4.5h-4.5z"}]},"clipboard-list":{paths:[{d:"M5.25 3.25h5.5a1.5 1.5 0 0 1 1.5 1.5v7a1.5 1.5 0 0 1-1.5 1.5h-5.5a1.5 1.5 0 0 1-1.5-1.5v-7a1.5 1.5 0 0 1 1.5-1.5Z"},{d:"M6.25 2.75h3.5v1.5h-3.5z"},{d:"M6 6.5h3.75M6 8.5h3.75M6 10.5h3.75"},{d:"M5 6.5h.01M5 8.5h.01M5 10.5h.01"}]},"layout-template":{paths:[{d:"M2.75 3.25h10.5v9.5H2.75z"},{d:"M6.25 3.25v9.5"},{d:"M6.25 6.75h7"}]},"rows-3":{paths:[{d:"M3 4.5h1.5M6 4.5h7"},{d:"M3 8h1.5M6 8h7"},{d:"M3 11.5h1.5M6 11.5h7"}]},"square-terminal":{paths:[{d:"M3.25 3.25h9.5v9.5h-9.5z"},{d:"M5.25 6.25 7 8l-1.75 1.75"},{d:"M8.75 9.75h2.25"}]},"flask-conical":{paths:[{d:"M6 2.75h4"},{d:"M7 2.75v2.5l-3 5.25a1.5 1.5 0 0 0 1.3 2.25h5.4A1.5 1.5 0 0 0 12 10.5L9 5.25v-2.5"},{d:"M5.5 9h5"}]}},n=B(()=>r[e.name]??null);return(i,a)=>n.value?(L(),R("svg",{key:0,viewBox:"0 0 16 16",fill:"none",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round",class:le(e.class),"aria-hidden":"true"},[(L(!0),R(ge,null,ve(n.value.paths,o=>(L(),R("path",{key:o.d,d:o.d,fill:o.fill??"none"},null,8,ta))),128))],2)):Y("",!0)}}),ra={class:"relative"},na={class:"min-w-0 flex-1 break-words"},oa=["href"],ia={class:"flex min-w-0 flex-1 items-start gap-x-2.5"},aa={class:"flex min-w-0 flex-1 flex-wrap items-center gap-1.5 [word-break:break-word]"},sa={class:"min-w-0 max-w-full break-words"},la=ke({__name:"DocsMobileMenuNode",props:{item:{},depth:{default:0}},emits:["navigate"],setup(t,{emit:e}){const r=t,n=e,{page:i}=We(),a=Ur(),o=B(()=>{var u;return!!((u=r.item.items)!=null&&u.length)}),s=B(()=>nr(i.value.relativePath,r.item.link)),l=B(()=>{var u;return((u=r.item.items)==null?void 0:u.some(g=>St(i.value.relativePath,g)))??!1}),c=ce(o.value?!r.item.collapsed||l.value:!1);xe(l,u=>{u&&(c.value=!0)});function h(u){return u?De(u):"#"}async function f(u,g){g&&(u.preventDefault(),await a.go(h(g)),n("navigate"))}function d(){o.value&&(c.value=!c.value)}return(u,g)=>{const x=fo("DocsMobileMenuNode",!0);return L(),R("li",ra,[o.value?(L(),R("button",{key:0,type:"button",class:le(["group flex w-full cursor-pointer items-center py-0.5 pr-2 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",l.value?"text-docs-primary":"text-slate-700"]),onClick:d},[S("span",na,re(t.item.text),1),(L(),R("svg",{viewBox:"0 0 640 640",class:le(["size-3 shrink-0 transition-transform",c.value?"rotate-90":"rotate-0"]),"aria-hidden":"true"},[...g[2]||(g[2]=[S("path",{d:"M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"},null,-1)])],2))],2)):(L(),R("a",{key:1,href:h(t.item.link),class:le(["group flex w-full cursor-pointer items-center py-0.5 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",s.value?"text-docs-primary":"text-slate-700"]),onClick:g[0]||(g[0]=m=>f(m,t.item.link))},[S("div",ia,[t.item.icon?(L(),we(or,{key:0,name:t.item.icon,class:"mt-1 size-4 shrink-0 text-slate-500 group-hover:text-slate-700"},null,8,["name"])):Y("",!0),S("div",aa,[S("span",sa,re(t.item.text),1)])])],10,oa)),o.value&&c.value?(L(),R("ul",{key:2,style:Ct({marginLeft:t.depth===0?"1rem":"1.25rem"})},[(L(!0),R(ge,null,ve(t.item.items,m=>(L(),we(x,{key:m.link??`${m.text}-${m.icon??""}`,item:m,depth:t.depth+1,onNavigate:g[1]||(g[1]=p=>n("navigate"))},null,8,["item","depth"]))),128))],4)):Y("",!0)])}}}),ca={class:"min-h-full bg-white"},ua={class:"border-b border-slate-200/80 px-4 pb-4 pt-5"},da={class:"flex min-w-0 items-center gap-3"},fa=["src"],ma={key:1,class:"min-w-0 truncate text-base font-semibold tracking-[-0.01em] text-slate-900"},ha={class:"px-4 pb-6 pt-6"},pa={"aria-label":"Sidebar navigation",class:"text-sm"},ga={key:0,class:"mb-3 flex items-center gap-2.5 text-sm font-medium text-slate-900"},va={class:"space-y-px"},ba=ke({__name:"DocsMobileMenu",props:{logoSrc:{},siteTitle:{}},emits:["navigate"],setup(t){const{sidebarGroups:e}=Kr(),r=B(()=>e.value.filter(n=>{var i;return(i=n.items)==null?void 0:i.length}));return(n,i)=>(L(),R("div",ca,[S("div",ua,[S("div",da,[t.logoSrc?(L(),R("img",{key:0,src:t.logoSrc,alt:"",class:"block h-7 w-auto max-w-[156px] shrink-0 object-contain"},null,8,fa)):(L(),R("div",ma,re(t.siteTitle),1))])]),S("div",ha,[S("nav",pa,[(L(!0),R(ge,null,ve(r.value,a=>{var o,s;return L(),R("section",{key:a.text??((s=(o=a.items)==null?void 0:o[0])==null?void 0:s.link),class:"mt-6 first:mt-0"},[a.text?(L(),R("h2",ga,[a.icon?(L(),we(or,{key:0,name:a.icon,class:"size-4 text-slate-600"},null,8,["name"])):Y("",!0),Ye(" "+re(a.text),1)])):Y("",!0),S("ul",va,[(L(!0),R(ge,null,ve(a.items,l=>(L(),we(la,{key:l.link??`${l.text}-${l.icon??""}`,item:l,onNavigate:i[0]||(i[0]=c=>n.$emit("navigate"))},null,8,["item"]))),128))])])}),128))])])]))}}),ya={class:"flex min-h-[calc(100dvh-14rem)] w-full flex-col items-center justify-center px-6 py-16 text-center sm:px-8 sm:py-24 lg:min-h-[calc(100dvh-10rem)]"},wa={class:"text-6xl font-semibold tracking-[-0.04em] text-slate-900 sm:text-7xl"},Ea={class:"mt-3 text-2xl font-semibold tracking-[-0.03em] text-slate-900 sm:text-3xl"},xa={class:"mt-5 max-w-sm text-sm leading-6 text-slate-600"},ka=["href","aria-label"],_a=ke({__name:"DocsNotFound",setup(t){const{theme:e}=We(),r=B(()=>{var s;return((s=e.value.notFound)==null?void 0:s.code)??"404"}),n=B(()=>{var s;return((s=e.value.notFound)==null?void 0:s.title)??"Page not found"}),i=B(()=>{var s;return((s=e.value.notFound)==null?void 0:s.quote)??"The page you requested does not exist or may have moved."}),a=B(()=>{var s;return((s=e.value.notFound)==null?void 0:s.linkLabel)??"Go to home"}),o=B(()=>{var s;return((s=e.value.notFound)==null?void 0:s.linkText)??"Take me home"});return(s,l)=>(L(),R("section",ya,[S("p",wa,re(r.value),1),S("h1",Ea,re(n.value),1),l[0]||(l[0]=S("div",{class:"mt-6 h-px w-16 bg-slate-200"},null,-1)),S("p",xa,re(i.value),1),S("a",{href:G(De)("/"),"aria-label":a.value,class:"mt-7 inline-flex items-center rounded-xl border border-docs-primary-border bg-docs-primary-soft px-4 py-2 text-sm font-medium text-docs-primary transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft-hover"},re(o.value),9,ka)]))}}),Sa={id:"table-of-contents-content",class:"toc"},Aa=["data-depth","data-active","data-active-deepest"],Ta=["href","onClick"],Ca=ke({__name:"DocsOutlineItem",props:{items:{}},setup(t){const e=t;function r(n,i){const a=i.replace(/^#/,""),o=document.getElementById(a);o&&(n.preventDefault(),o.scrollIntoView({block:"start",behavior:"smooth"}),window.location.hash=i)}return(n,i)=>(L(),R("ul",Sa,[(L(!0),R(ge,null,ve(e.items,a=>(L(),R("li",{key:a.link,class:le(["toc-item relative",a.depth>0?a.active?"border-l pl-4 border-docs-primary hover:border-docs-primary":"border-l pl-4 border-slate-950/5 hover:border-slate-950/20":""]),"data-depth":a.depth,"data-active":a.active||void 0,"data-active-deepest":a.activeDeepest||void 0},[S("a",{href:a.link,style:Ct(a.depth>0?"padding-left:1rem":void 0),class:le(["break-words py-1",[a.depth>0?"group flex items-start whitespace-pre-wrap":"block border-l pl-4 font-medium",a.active?a.depth>0?"text-docs-primary":"text-docs-primary border-docs-primary hover:border-docs-primary":a.depth>0?"text-gray-500 hover:text-gray-900":"border-slate-950/5 hover:border-slate-950/20 hover:text-gray-900"]]),onClick:o=>r(o,a.link)},re(a.title),15,Ta)],10,Aa))),128))]))}}),Ia={key:0,id:"table-of-contents","aria-label":"On this page",class:"space-y-2"},La={type:"button",class:"flex cursor-pointer items-center space-x-2 text-sm font-medium text-slate-700 transition-colors hover:text-slate-900"},Ma=ke({__name:"DocsOutline",setup(t){const{frontmatter:e,theme:r}=We(),n=B(()=>{const p=r.value.outline;return typeof p=="object"&&!Array.isArray(p)&&(p==null?void 0:p.label)||r.value.outlineTitle||"On this page"}),i=ce(null),a=ce([]),o=B(()=>{var b;const p=g(a.value,i.value),v=new Set(p.map(k=>k.link)),w=((b=p.at(-1))==null?void 0:b.link)??null;return u(a.value).map(k=>({...k,depth:Math.max(k.level-2,0),active:v.has(k.link),activeDeepest:k.link===w}))});function s(){return document.getElementById("docs-scroll-container")??document.getElementById("content-container")}function l(p){const v=Number.parseFloat(getComputedStyle(document.documentElement).getPropertyValue("--scroll-mt"));return Number.isFinite(v)?v:Math.min(Math.max(p.clientHeight*.18,56),120)}function c(p){if(p===!1)return null;const v=(typeof p=="object"&&!Array.isArray(p)&&p&&"level"in p?p.level:p)??2;return v==="deep"?[2,6]:Array.isArray(v)?[v[0],v[1]]:[v,v]}function h(){const p=c(e.value.outline??r.value.outline);return p||null}function f(p){const v=/\b(?:VPBadge|header-anchor|footnote-ref|ignore-header)\b/;let w="";for(const b of p.childNodes)if(b.nodeType===Node.ELEMENT_NODE){const k=b;if(v.test(k.className))continue;w+=k.textContent??""}else b.nodeType===Node.TEXT_NODE&&(w+=b.textContent??"");return w.trim()}function d(){const p=h();if(!p){a.value=[];return}const[v,w]=p,b=Array.from(document.querySelectorAll(".vp-doc :where(h1,h2,h3,h4,h5,h6)")).filter(O=>O instanceof HTMLElement&&!!O.id).map(O=>{const $=Number(O.tagName.slice(1));return{title:f(O),slug:O.id,link:`#${O.id}`,level:$,children:[]}}).filter(O=>O.title&&O.level>=v&&O.level<=w),k=[],C=[];for(const O of b){for(;C.length&&C[C.length-1].level>=O.level;)C.pop();C.length?C[C.length-1].children.push(O):k.push(O),C.push(O)}a.value=k}function u(p){return p.flatMap(v=>[v,...u(v.children??[])])}function g(p,v){var w;if(!v)return[];for(const b of p){if(b.link===v)return[b];if((w=b.children)!=null&&w.length){const k=g(b.children,v);if(k.length)return[b,...k]}}return[]}function x(){var F,Q,H;const p=u(a.value),v=s();if(!p.length||!v){i.value=null;return}const w=v.scrollTop,b=v.clientHeight,k=v.scrollHeight,C=l(v),O=Math.abs(w+b-k)<1;if(w<1){const y=window.location.hash,A=p.some(P=>P.link===y)?y:null;i.value=A??((F=p[0])==null?void 0:F.link)??null;return}if(O){i.value=((Q=p[p.length-1])==null?void 0:Q.link)??null;return}const $=window.location.hash,j=p.some(y=>y.link===$)?$:null;let ee=null;for(const y of p){const A=document.getElementById(y.slug);if(!A)continue;const P=v.getBoundingClientRect().top;if(w+A.getBoundingClientRect().top-P>w+C)break;ee=y.link}i.value=ee??j??((H=p[0])==null?void 0:H.link)??null}const m=()=>{x()};return Ke(()=>{const p=s();requestAnimationFrame(()=>{d(),x()}),p==null||p.addEventListener("scroll",m,{passive:!0}),window.addEventListener("hashchange",m,{passive:!0})}),rr(()=>{const p=s();p==null||p.removeEventListener("scroll",m),window.removeEventListener("hashchange",m)}),mo(async()=>{await $e(),d(),x()}),(p,v)=>a.value.length?(L(),R("nav",Ia,[S("button",La,[v[0]||(v[0]=S("svg",{viewBox:"0 0 16 16",fill:"none",stroke:"currentColor","stroke-width":"2",class:"h-3 w-3","aria-hidden":"true"},[S("path",{d:"M2.5 3.5h11M2.5 8h7M2.5 12.5h11","stroke-linecap":"round"})],-1)),S("span",null,re(n.value),1)]),wt(Ca,{items:o.value},null,8,["items"])])):Y("",!0)}}),Ra={root:()=>M(()=>import("./@localSearchIndexroot.DwvBtAkS.js"),[])};/*!
* tabbable 6.5.0
* @license MIT, https://github.com/focus-trap/tabbable/blob/master/LICENSE
*/var po=["input:not([inert]):not([inert] *)","select:not([inert]):not([inert] *)","textarea:not([inert]):not([inert] *)","a[href]:not([inert]):not([inert] *)","area[href]:not([inert]):not([inert] *)","button:not([inert]):not([inert] *)","[tabindex]:not(slot):not([inert]):not([inert] *)","audio[controls]:not([inert]):not([inert] *)","video[controls]:not([inert]):not([inert] *)",'[contenteditable]:not([contenteditable="false"]):not([inert]):not([inert] *)',"details>summary:first-of-type:not([inert]):not([inert] *)","details:not([inert]):not([inert] *)"],Ut=po.join(","),go=typeof Element>"u",et=go?function(){}:Element.prototype.matches||Element.prototype.msMatchesSelector||Element.prototype.webkitMatchesSelector,Kt=!go&&Element.prototype.getRootNode?function(t){var e;return t==null||(e=t.getRootNode)===null||e===void 0?void 0:e.call(t)}:function(t){return t==null?void 0:t.ownerDocument},Wt=function(e,r){var n;r===void 0&&(r=!0);var i=e==null||(n=e.getAttribute)===null||n===void 0?void 0:n.call(e,"inert"),a=i===""||i==="true",o=a||r&&e&&(typeof e.closest=="function"?e.closest("[inert]"):Wt(e.parentNode));return o},Fa=function(e){var r,n=e==null||(r=e.getAttribute)===null||r===void 0?void 0:r.call(e,"contenteditable");return n===""||n==="true"},vo=function(e,r,n){if(Wt(e))return[];var i=Array.prototype.slice.apply(e.querySelectorAll(Ut));return r&&et.call(e,Ut)&&i.unshift(e),i=i.filter(n),i},Gt=function(e,r,n){for(var i=[],a=Array.from(e);a.length;){var o=a.shift();if(!Wt(o,!1))if(o.tagName==="SLOT"){var s=o.assignedElements(),l=s.length?s:o.children,c=Gt(l,!0,n);n.flatten?i.push.apply(i,c):i.push({scopeParent:o,candidates:c})}else{var h=et.call(o,Ut);h&&n.filter(o)&&(r||!e.includes(o))&&i.push(o);var f=o.shadowRoot||typeof n.getShadowRoot=="function"&&n.getShadowRoot(o),d=!Wt(f,!1)&&(!n.shadowRootFilter||n.shadowRootFilter(o));if(f&&d){var u=Gt(f===!0?o.children:f.children,!0,n);n.flatten?i.push.apply(i,u):i.push({scopeParent:o,candidates:u})}else a.unshift.apply(a,o.children)}}return i},bo=function(e){return!isNaN(parseInt(e.getAttribute("tabindex"),10))},Qe=function(e){if(!e)throw new Error("No node provided");return e.tabIndex<0&&(/^(AUDIO|VIDEO|DETAILS)$/.test(e.tagName)||Fa(e))&&!bo(e)?0:e.tabIndex},Oa=function(e,r){var n=Qe(e);return n<0&&r&&!bo(e)?0:n},Pa=function(e,r){return e.tabIndex===r.tabIndex?e.documentOrder-r.documentOrder:e.tabIndex-r.tabIndex},yo=function(e){return e.tagName==="INPUT"},Na=function(e){return yo(e)&&e.type==="hidden"},Da=function(e){var r=e.tagName==="DETAILS"&&Array.prototype.slice.apply(e.children).some(function(n){return n.tagName==="SUMMARY"});return r},za=function(e,r){for(var n=0;n<e.length;n++)if(e[n].checked&&e[n].form===r)return e[n]},$a=function(e){if(!e.name)return!0;var r=e.form||Kt(e),n=function(s){return r.querySelectorAll('input[type="radio"][name="'+s+'"]')},i;if(typeof window<"u"&&typeof window.CSS<"u"&&typeof window.CSS.escape=="function")i=n(window.CSS.escape(e.name));else try{i=n(e.name)}catch(o){return console.error("Looks like you have a radio button with a name attribute containing invalid CSS selector characters and need the CSS.escape polyfill: %s",o.message),!1}var a=za(i,e.form);return!a||a===e},Va=function(e){return yo(e)&&e.type==="radio"},Ha=function(e){return Va(e)&&!$a(e)},qa=function(e){var r,n=e&&Kt(e),i=(r=n)===null||r===void 0?void 0:r.host,a=!1;if(n&&n!==e){var o,s,l;for(a=!!((o=i)!==null&&o!==void 0&&(s=o.ownerDocument)!==null&&s!==void 0&&s.contains(i)||e!=null&&(l=e.ownerDocument)!==null&&l!==void 0&&l.contains(e));!a&&i;){var c,h,f;n=Kt(i),i=(c=n)===null||c===void 0?void 0:c.host,a=!!((h=i)!==null&&h!==void 0&&(f=h.ownerDocument)!==null&&f!==void 0&&f.contains(i))}}return a},Tn=function(e){var r=e.getBoundingClientRect(),n=r.width,i=r.height;return n===0&&i===0},Ba=function(e,r){var n=r.displayCheck,i=r.getShadowRoot;if(n==="full-native"&&"checkVisibility"in e){var a=e.checkVisibility({checkOpacity:!1,opacityProperty:!1,contentVisibilityAuto:!0,visibilityProperty:!0,checkVisibilityCSS:!0});return!a}var o=getComputedStyle(e),s=o.visibility;if(s==="hidden"||s==="collapse")return!0;var l=et.call(e,"details>summary:first-of-type"),c=l?e.parentElement:e;if(et.call(c,"details:not([open]) *"))return!0;if(!n||n==="full"||n==="full-native"||n==="legacy-full"){if(typeof i=="function"){for(var h=e;e;){var f=e.parentElement,d=Kt(e);if(f&&!f.shadowRoot&&i(f)===!0)return Tn(e);e.assignedSlot?e=e.assignedSlot:!f&&d!==e.ownerDocument?e=d.host:e=f}e=h}if(qa(e))return!e.getClientRects().length;if(n!=="legacy-full")return!0}else if(n==="non-zero-area")return Tn(e);return!1},ja=function(e){if(/^(INPUT|BUTTON|SELECT|TEXTAREA)$/.test(e.tagName))for(var r=e.parentElement;r;){if(r.tagName==="FIELDSET"&&r.disabled){for(var n=0;n<r.children.length;n++){var i=r.children.item(n);if(i.tagName==="LEGEND")return et.call(r,"fieldset[disabled] *")?!0:!i.contains(e)}return!0}r=r.parentElement}return!1},Jt=function(e,r){return!(r.disabled||Na(r)||Ba(r,e)||Da(r)||ja(r))},Cr=function(e,r){return!(Ha(r)||Qe(r)<0||!Jt(e,r))},Ua=function(e){var r=parseInt(e.getAttribute("tabindex"),10);return!!(isNaN(r)||r>=0)},wo=function(e){var r=[],n=[];return e.forEach(function(i,a){var o=!!i.scopeParent,s=o?i.scopeParent:i,l=Oa(s,o),c=o?wo(i.candidates):s;l===0?o?r.push.apply(r,c):r.push(s):n.push({documentOrder:a,tabIndex:l,item:i,isScope:o,content:c})}),n.sort(Pa).reduce(function(i,a){return a.isScope?i.push.apply(i,a.content):i.push(a.content),i},[]).concat(r)},Ka=function(e,r){r=r||{};var n;return r.getShadowRoot?n=Gt([e],r.includeContainer,{filter:Cr.bind(null,r),flatten:!1,getShadowRoot:r.getShadowRoot,shadowRootFilter:Ua}):n=vo(e,r.includeContainer,Cr.bind(null,r)),wo(n)},Wa=function(e,r){r=r||{};var n;return r.getShadowRoot?n=Gt([e],r.includeContainer,{filter:Jt.bind(null,r),flatten:!0,getShadowRoot:r.getShadowRoot}):n=vo(e,r.includeContainer,Jt.bind(null,r)),n},nt=function(e,r){if(r=r||{},!e)throw new Error("No node provided");return et.call(e,Ut)===!1?!1:Cr(r,e)},Ga=po.concat("iframe:not([inert]):not([inert] *)").join(","),dr=function(e,r){if(r=r||{},!e)throw new Error("No node provided");return et.call(e,Ga)===!1?!1:Jt(r,e)};/*!
* focus-trap 7.8.0
* @license MIT, https://github.com/focus-trap/focus-trap/blob/master/LICENSE
*/function Ir(t,e){(e==null||e>t.length)&&(e=t.length);for(var r=0,n=Array(e);r<e;r++)n[r]=t[r];return n}function Ja(t){if(Array.isArray(t))return Ir(t)}function Cn(t,e){var r=typeof Symbol<"u"&&t[Symbol.iterator]||t["@@iterator"];if(!r){if(Array.isArray(t)||(r=Eo(t))||e){r&&(t=r);var n=0,i=function(){};return{s:i,n:function(){return n>=t.length?{done:!0}:{done:!1,value:t[n++]}},e:function(l){throw l},f:i}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var a,o=!0,s=!1;return{s:function(){r=r.call(t)},n:function(){var l=r.next();return o=l.done,l},e:function(l){s=!0,a=l},f:function(){try{o||r.return==null||r.return()}finally{if(s)throw a}}}}function Ya(t,e,r){return(e=ts(e))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}function Qa(t){if(typeof Symbol<"u"&&t[Symbol.iterator]!=null||t["@@iterator"]!=null)return Array.from(t)}function Za(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function In(t,e){var r=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);e&&(n=n.filter(function(i){return Object.getOwnPropertyDescriptor(t,i).enumerable})),r.push.apply(r,n)}return r}function Ln(t){for(var e=1;e<arguments.length;e++){var r=arguments[e]!=null?arguments[e]:{};e%2?In(Object(r),!0).forEach(function(n){Ya(t,n,r[n])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(r)):In(Object(r)).forEach(function(n){Object.defineProperty(t,n,Object.getOwnPropertyDescriptor(r,n))})}return t}function Xa(t){return Ja(t)||Qa(t)||Eo(t)||Za()}function es(t,e){if(typeof t!="object"||!t)return t;var r=t[Symbol.toPrimitive];if(r!==void 0){var n=r.call(t,e);if(typeof n!="object")return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}function ts(t){var e=es(t,"string");return typeof e=="symbol"?e:e+""}function Eo(t,e){if(t){if(typeof t=="string")return Ir(t,e);var r={}.toString.call(t).slice(8,-1);return r==="Object"&&t.constructor&&(r=t.constructor.name),r==="Map"||r==="Set"?Array.from(t):r==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?Ir(t,e):void 0}}var Ve={getActiveTrap:function(e){return(e==null?void 0:e.length)>0?e[e.length-1]:null},activateTrap:function(e,r){var n=Ve.getActiveTrap(e);r!==n&&Ve.pauseTrap(e);var i=e.indexOf(r);i===-1||e.splice(i,1),e.push(r)},deactivateTrap:function(e,r){var n=e.indexOf(r);n!==-1&&e.splice(n,1),Ve.unpauseTrap(e)},pauseTrap:function(e){var r=Ve.getActiveTrap(e);r==null||r._setPausedState(!0)},unpauseTrap:function(e){var r=Ve.getActiveTrap(e);r&&!r._isManuallyPaused()&&r._setPausedState(!1)}},rs=function(e){return e.tagName&&e.tagName.toLowerCase()==="input"&&typeof e.select=="function"},ns=function(e){return(e==null?void 0:e.key)==="Escape"||(e==null?void 0:e.key)==="Esc"||(e==null?void 0:e.keyCode)===27},xt=function(e){return(e==null?void 0:e.key)==="Tab"||(e==null?void 0:e.keyCode)===9},os=function(e){return xt(e)&&!e.shiftKey},is=function(e){return xt(e)&&e.shiftKey},Mn=function(e){return setTimeout(e,0)},bt=function(e){for(var r=arguments.length,n=new Array(r>1?r-1:0),i=1;i<r;i++)n[i-1]=arguments[i];return typeof e=="function"?e.apply(void 0,n):e},Mt=function(e){return e.target.shadowRoot&&typeof e.composedPath=="function"?e.composedPath()[0]:e.target},as=[],ss=function(e,r){var n=(r==null?void 0:r.document)||document,i=(r==null?void 0:r.trapStack)||as,a=Ln({returnFocusOnDeactivate:!0,escapeDeactivates:!0,delayInitialFocus:!0,isolateSubtrees:!1,isKeyForward:os,isKeyBackward:is},r),o={containers:[],containerGroups:[],tabbableGroups:[],adjacentElements:new Set,alreadySilent:new Set,nodeFocusedBeforeActivation:null,mostRecentlyFocusedNode:null,active:!1,paused:!1,manuallyPaused:!1,delayInitialFocusTimer:void 0,recentNavEvent:void 0},s,l=function(y,A,P){return y&&y[A]!==void 0?y[A]:a[P||A]},c=function(y,A){var P=typeof(A==null?void 0:A.composedPath)=="function"?A.composedPath():void 0;return o.containerGroups.findIndex(function(U){var z=U.container,J=U.tabbableNodes;return z.contains(y)||(P==null?void 0:P.includes(z))||J.find(function(q){return q===y})})},h=function(y){var A=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},P=A.hasFallback,U=P===void 0?!1:P,z=A.params,J=z===void 0?[]:z,q=a[y];if(typeof q=="function"&&(q=q.apply(void 0,Xa(J))),q===!0&&(q=void 0),!q){if(q===void 0||q===!1)return q;throw new Error("`".concat(y,"` was specified but was not a node, or did not return a node"))}var I=q;if(typeof q=="string"){try{I=n.querySelector(q)}catch(T){throw new Error("`".concat(y,'` appears to be an invalid selector; error="').concat(T.message,'"'))}if(!I&&!U)throw new Error("`".concat(y,"` as selector refers to no known node"))}return I},f=function(){var y=h("initialFocus",{hasFallback:!0});if(y===!1)return!1;if(y===void 0||y&&!dr(y,a.tabbableOptions))if(c(n.activeElement)>=0)y=n.activeElement;else{var A=o.tabbableGroups[0],P=A&&A.firstTabbableNode;y=P||h("fallbackFocus")}else y===null&&(y=h("fallbackFocus"));if(!y)throw new Error("Your focus-trap needs to have at least one focusable element");return y},d=function(){if(o.containerGroups=o.containers.map(function(y){var A=Ka(y,a.tabbableOptions),P=Wa(y,a.tabbableOptions),U=A.length>0?A[0]:void 0,z=A.length>0?A[A.length-1]:void 0,J=P.find(function(T){return nt(T)}),q=P.slice().reverse().find(function(T){return nt(T)}),I=!!A.find(function(T){return Qe(T)>0});return{container:y,tabbableNodes:A,focusableNodes:P,posTabIndexesFound:I,firstTabbableNode:U,lastTabbableNode:z,firstDomTabbableNode:J,lastDomTabbableNode:q,nextTabbableNode:function(K){var te=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!0,N=A.indexOf(K);return N<0?te?P.slice(P.indexOf(K)+1).find(function(X){return nt(X)}):P.slice(0,P.indexOf(K)).reverse().find(function(X){return nt(X)}):A[N+(te?1:-1)]}}}),o.tabbableGroups=o.containerGroups.filter(function(y){return y.tabbableNodes.length>0}),o.tabbableGroups.length<=0&&!h("fallbackFocus"))throw new Error("Your focus-trap must have at least one container with at least one tabbable node in it at all times");if(o.containerGroups.find(function(y){return y.posTabIndexesFound})&&o.containerGroups.length>1)throw new Error("At least one node with a positive tabindex was found in one of your focus-trap's multiple containers. Positive tabindexes are only supported in single-container focus-traps.")},u=function(y){var A=y.activeElement;if(A)return A.shadowRoot&&A.shadowRoot.activeElement!==null?u(A.shadowRoot):A},g=function(y){if(y!==!1&&y!==u(document)){if(!y||!y.focus){g(f());return}y.focus({preventScroll:!!a.preventScroll}),o.mostRecentlyFocusedNode=y,rs(y)&&y.select()}},x=function(y){var A=h("setReturnFocus",{params:[y]});return A||(A===!1?!1:y)},m=function(y){var A=y.target,P=y.event,U=y.isBackward,z=U===void 0?!1:U;A=A||Mt(P),d();var J=null;if(o.tabbableGroups.length>0){var q=c(A,P),I=q>=0?o.containerGroups[q]:void 0;if(q<0)z?J=o.tabbableGroups[o.tabbableGroups.length-1].lastTabbableNode:J=o.tabbableGroups[0].firstTabbableNode;else if(z){var T=o.tabbableGroups.findIndex(function(Z){var ae=Z.firstTabbableNode;return A===ae});if(T<0&&(I.container===A||dr(A,a.tabbableOptions)&&!nt(A,a.tabbableOptions)&&!I.nextTabbableNode(A,!1))&&(T=q),T>=0){var K=T===0?o.tabbableGroups.length-1:T-1,te=o.tabbableGroups[K];J=Qe(A)>=0?te.lastTabbableNode:te.lastDomTabbableNode}else xt(P)||(J=I.nextTabbableNode(A,!1))}else{var N=o.tabbableGroups.findIndex(function(Z){var ae=Z.lastTabbableNode;return A===ae});if(N<0&&(I.container===A||dr(A,a.tabbableOptions)&&!nt(A,a.tabbableOptions)&&!I.nextTabbableNode(A))&&(N=q),N>=0){var X=N===o.tabbableGroups.length-1?0:N+1,ne=o.tabbableGroups[X];J=Qe(A)>=0?ne.firstTabbableNode:ne.firstDomTabbableNode}else xt(P)||(J=I.nextTabbableNode(A))}}else J=h("fallbackFocus");return J},p=function(y){var A=Mt(y);if(!(c(A,y)>=0)){if(bt(a.clickOutsideDeactivates,y)){s.deactivate({returnFocus:a.returnFocusOnDeactivate});return}bt(a.allowOutsideClick,y)||y.preventDefault()}},v=function(y){var A=Mt(y),P=c(A,y)>=0;if(P||A instanceof Document)P&&(o.mostRecentlyFocusedNode=A);else{y.stopImmediatePropagation();var U,z=!0;if(o.mostRecentlyFocusedNode)if(Qe(o.mostRecentlyFocusedNode)>0){var J=c(o.mostRecentlyFocusedNode),q=o.containerGroups[J].tabbableNodes;if(q.length>0){var I=q.findIndex(function(T){return T===o.mostRecentlyFocusedNode});I>=0&&(a.isKeyForward(o.recentNavEvent)?I+1<q.length&&(U=q[I+1],z=!1):I-1>=0&&(U=q[I-1],z=!1))}}else o.containerGroups.some(function(T){return T.tabbableNodes.some(function(K){return Qe(K)>0})})||(z=!1);else z=!1;z&&(U=m({target:o.mostRecentlyFocusedNode,isBackward:a.isKeyBackward(o.recentNavEvent)})),g(U||o.mostRecentlyFocusedNode||f())}o.recentNavEvent=void 0},w=function(y){var A=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;o.recentNavEvent=y;var P=m({event:y,isBackward:A});P&&(xt(y)&&y.preventDefault(),g(P))},b=function(y){(a.isKeyForward(y)||a.isKeyBackward(y))&&w(y,a.isKeyBackward(y))},k=function(y){ns(y)&&bt(a.escapeDeactivates,y)!==!1&&(y.preventDefault(),s.deactivate())},C=function(y){var A=Mt(y);c(A,y)>=0||bt(a.clickOutsideDeactivates,y)||bt(a.allowOutsideClick,y)||(y.preventDefault(),y.stopImmediatePropagation())},O=function(){if(o.active)return Ve.activateTrap(i,s),o.delayInitialFocusTimer=a.delayInitialFocus?Mn(function(){g(f())}):g(f()),n.addEventListener("focusin",v,!0),n.addEventListener("mousedown",p,{capture:!0,passive:!1}),n.addEventListener("touchstart",p,{capture:!0,passive:!1}),n.addEventListener("click",C,{capture:!0,passive:!1}),n.addEventListener("keydown",b,{capture:!0,passive:!1}),n.addEventListener("keydown",k),s},$=function(y){o.active&&!o.paused&&s._setSubtreeIsolation(!1),o.adjacentElements.clear(),o.alreadySilent.clear();var A=new Set,P=new Set,U=Cn(y),z;try{for(U.s();!(z=U.n()).done;){var J=z.value;A.add(J);for(var q=typeof ShadowRoot<"u"&&J.getRootNode()instanceof ShadowRoot,I=J;I;){A.add(I);var T=I.parentElement,K=[];T?K=T.children:!T&&q&&(K=I.getRootNode().children,T=I.getRootNode().host,q=typeof ShadowRoot<"u"&&T.getRootNode()instanceof ShadowRoot);var te=Cn(K),N;try{for(te.s();!(N=te.n()).done;){var X=N.value;P.add(X)}}catch(ne){te.e(ne)}finally{te.f()}I=T}}}catch(ne){U.e(ne)}finally{U.f()}A.forEach(function(ne){P.delete(ne)}),o.adjacentElements=P},j=function(){if(o.active)return n.removeEventListener("focusin",v,!0),n.removeEventListener("mousedown",p,!0),n.removeEventListener("touchstart",p,!0),n.removeEventListener("click",C,!0),n.removeEventListener("keydown",b,!0),n.removeEventListener("keydown",k),s},ee=function(y){var A=y.some(function(P){var U=Array.from(P.removedNodes);return U.some(function(z){return z===o.mostRecentlyFocusedNode})});A&&g(f())},F=typeof window<"u"&&"MutationObserver"in window?new MutationObserver(ee):void 0,Q=function(){F&&(F.disconnect(),o.active&&!o.paused&&o.containers.map(function(y){F.observe(y,{subtree:!0,childList:!0})}))};return s={get active(){return o.active},get paused(){return o.paused},activate:function(y){if(o.active)return this;var A=l(y,"onActivate"),P=l(y,"onPostActivate"),U=l(y,"checkCanFocusTrap"),z=Ve.getActiveTrap(i),J=!1;if(z&&!z.paused){var q;(q=z._setSubtreeIsolation)===null||q===void 0||q.call(z,!1),J=!0}try{U||d(),o.active=!0,o.paused=!1,o.nodeFocusedBeforeActivation=u(n),A==null||A();var I=function(){U&&d(),O(),Q(),a.isolateSubtrees&&s._setSubtreeIsolation(!0),P==null||P()};if(U)return U(o.containers.concat()).then(I,I),this;I()}catch(K){if(z===Ve.getActiveTrap(i)&&J){var T;(T=z._setSubtreeIsolation)===null||T===void 0||T.call(z,!0)}throw K}return this},deactivate:function(y){if(!o.active)return this;var A=Ln({onDeactivate:a.onDeactivate,onPostDeactivate:a.onPostDeactivate,checkCanReturnFocus:a.checkCanReturnFocus},y);clearTimeout(o.delayInitialFocusTimer),o.delayInitialFocusTimer=void 0,o.paused||s._setSubtreeIsolation(!1),o.alreadySilent.clear(),j(),o.active=!1,o.paused=!1,Q(),Ve.deactivateTrap(i,s);var P=l(A,"onDeactivate"),U=l(A,"onPostDeactivate"),z=l(A,"checkCanReturnFocus"),J=l(A,"returnFocus","returnFocusOnDeactivate");P==null||P();var q=function(){Mn(function(){J&&g(x(o.nodeFocusedBeforeActivation)),U==null||U()})};return J&&z?(z(x(o.nodeFocusedBeforeActivation)).then(q,q),this):(q(),this)},pause:function(y){return o.active?(o.manuallyPaused=!0,this._setPausedState(!0,y)):this},unpause:function(y){return o.active?(o.manuallyPaused=!1,i[i.length-1]!==this?this:this._setPausedState(!1,y)):this},updateContainerElements:function(y){var A=[].concat(y).filter(Boolean);return o.containers=A.map(function(P){return typeof P=="string"?n.querySelector(P):P}),a.isolateSubtrees&&$(o.containers),o.active&&(d(),a.isolateSubtrees&&!o.paused&&s._setSubtreeIsolation(!0)),Q(),this}},Object.defineProperties(s,{_isManuallyPaused:{value:function(){return o.manuallyPaused}},_setPausedState:{value:function(y,A){if(o.paused===y)return this;if(o.paused=y,y){var P=l(A,"onPause"),U=l(A,"onPostPause");P==null||P(),j(),Q(),s._setSubtreeIsolation(!1),U==null||U()}else{var z=l(A,"onUnpause"),J=l(A,"onPostUnpause");z==null||z(),s._setSubtreeIsolation(!0),d(),O(),Q(),J==null||J()}return this}},_setSubtreeIsolation:{value:function(y){a.isolateSubtrees&&o.adjacentElements.forEach(function(A){var P;if(y)switch(a.isolateSubtrees){case"aria-hidden":(A.ariaHidden==="true"||((P=A.getAttribute("aria-hidden"))===null||P===void 0?void 0:P.toLowerCase())==="true")&&o.alreadySilent.add(A),A.setAttribute("aria-hidden","true");break;default:(A.inert||A.hasAttribute("inert"))&&o.alreadySilent.add(A),A.setAttribute("inert",!0);break}else if(!o.alreadySilent.has(A))switch(a.isolateSubtrees){case"aria-hidden":A.removeAttribute("aria-hidden");break;default:A.removeAttribute("inert");break}})}}}),s.updateContainerElements(e),s};function ls(t,e={}){let r;const{immediate:n,...i}=e,a=Ze(!1),o=Ze(!1),s=d=>r&&r.activate(d),l=d=>r&&r.deactivate(d),c=()=>{r&&(r.pause(),o.value=!0)},h=()=>{r&&(r.unpause(),o.value=!1)},f=B(()=>{const d=En(t);return Li(d).map(u=>{const g=En(u);return typeof g=="string"?g:Mi(g)}).filter(Ri)});return xe(f,d=>{d.length&&(r=ss(d,{...i,onActivate(){a.value=!0,e.onActivate&&e.onActivate()},onDeactivate(){a.value=!1,e.onDeactivate&&e.onDeactivate()}}),n&&s())},{flush:"post"}),Ii(()=>l()),{hasFocus:a,isPaused:o,activate:s,deactivate:l,pause:c,unpause:h}}var zm=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function cs(t){return t&&t.__esModule&&Object.prototype.hasOwnProperty.call(t,"default")?t.default:t}var Ht={exports:{}};/*!***************************************************
* mark.js v8.11.1
* https://markjs.io/
* Copyright (c) 2014–2018, Julian Kühnel
* Released under the MIT license https://git.io/vwTVl
*****************************************************/var us=Ht.exports,Rn;function ds(){return Rn||(Rn=1,(function(t,e){(function(r,n){t.exports=n()})(us,(function(){class r{constructor(o,s=!0,l=[],c=5e3){this.ctx=o,this.iframes=s,this.exclude=l,this.iframesTimeout=c}static matches(o,s){const l=typeof s=="string"?[s]:s,c=o.matches||o.matchesSelector||o.msMatchesSelector||o.mozMatchesSelector||o.oMatchesSelector||o.webkitMatchesSelector;if(c){let h=!1;return l.every(f=>c.call(o,f)?(h=!0,!1):!0),h}else return!1}getContexts(){let o,s=[];return typeof this.ctx>"u"||!this.ctx?o=[]:NodeList.prototype.isPrototypeOf(this.ctx)?o=Array.prototype.slice.call(this.ctx):Array.isArray(this.ctx)?o=this.ctx:typeof this.ctx=="string"?o=Array.prototype.slice.call(document.querySelectorAll(this.ctx)):o=[this.ctx],o.forEach(l=>{const c=s.filter(h=>h.contains(l)).length>0;s.indexOf(l)===-1&&!c&&s.push(l)}),s}getIframeContents(o,s,l=()=>{}){let c;try{const h=o.contentWindow;if(c=h.document,!h||!c)throw new Error("iframe inaccessible")}catch{l()}c&&s(c)}isIframeBlank(o){const s="about:blank",l=o.getAttribute("src").trim();return o.contentWindow.location.href===s&&l!==s&&l}observeIframeLoad(o,s,l){let c=!1,h=null;const f=()=>{if(!c){c=!0,clearTimeout(h);try{this.isIframeBlank(o)||(o.removeEventListener("load",f),this.getIframeContents(o,s,l))}catch{l()}}};o.addEventListener("load",f),h=setTimeout(f,this.iframesTimeout)}onIframeReady(o,s,l){try{o.contentWindow.document.readyState==="complete"?this.isIframeBlank(o)?this.observeIframeLoad(o,s,l):this.getIframeContents(o,s,l):this.observeIframeLoad(o,s,l)}catch{l()}}waitForIframes(o,s){let l=0;this.forEachIframe(o,()=>!0,c=>{l++,this.waitForIframes(c.querySelector("html"),()=>{--l||s()})},c=>{c||s()})}forEachIframe(o,s,l,c=()=>{}){let h=o.querySelectorAll("iframe"),f=h.length,d=0;h=Array.prototype.slice.call(h);const u=()=>{--f<=0&&c(d)};f||u(),h.forEach(g=>{r.matches(g,this.exclude)?u():this.onIframeReady(g,x=>{s(g)&&(d++,l(x)),u()},u)})}createIterator(o,s,l){return document.createNodeIterator(o,s,l,!1)}createInstanceOnIframe(o){return new r(o.querySelector("html"),this.iframes)}compareNodeIframe(o,s,l){const c=o.compareDocumentPosition(l),h=Node.DOCUMENT_POSITION_PRECEDING;if(c&h)if(s!==null){const f=s.compareDocumentPosition(l),d=Node.DOCUMENT_POSITION_FOLLOWING;if(f&d)return!0}else return!0;return!1}getIteratorNode(o){const s=o.previousNode();let l;return s===null?l=o.nextNode():l=o.nextNode()&&o.nextNode(),{prevNode:s,node:l}}checkIframeFilter(o,s,l,c){let h=!1,f=!1;return c.forEach((d,u)=>{d.val===l&&(h=u,f=d.handled)}),this.compareNodeIframe(o,s,l)?(h===!1&&!f?c.push({val:l,handled:!0}):h!==!1&&!f&&(c[h].handled=!0),!0):(h===!1&&c.push({val:l,handled:!1}),!1)}handleOpenIframes(o,s,l,c){o.forEach(h=>{h.handled||this.getIframeContents(h.val,f=>{this.createInstanceOnIframe(f).forEachNode(s,l,c)})})}iterateThroughNodes(o,s,l,c,h){const f=this.createIterator(s,o,c);let d=[],u=[],g,x,m=()=>({prevNode:x,node:g}=this.getIteratorNode(f),g);for(;m();)this.iframes&&this.forEachIframe(s,p=>this.checkIframeFilter(g,x,p,d),p=>{this.createInstanceOnIframe(p).forEachNode(o,v=>u.push(v),c)}),u.push(g);u.forEach(p=>{l(p)}),this.iframes&&this.handleOpenIframes(d,o,l,c),h()}forEachNode(o,s,l,c=()=>{}){const h=this.getContexts();let f=h.length;f||c(),h.forEach(d=>{const u=()=>{this.iterateThroughNodes(o,d,s,l,()=>{--f<=0&&c()})};this.iframes?this.waitForIframes(d,u):u()})}}class n{constructor(o){this.ctx=o,this.ie=!1;const s=window.navigator.userAgent;(s.indexOf("MSIE")>-1||s.indexOf("Trident")>-1)&&(this.ie=!0)}set opt(o){this._opt=Object.assign({},{element:"",className:"",exclude:[],iframes:!1,iframesTimeout:5e3,separateWordSearch:!0,diacritics:!0,synonyms:{},accuracy:"partially",acrossElements:!1,caseSensitive:!1,ignoreJoiners:!1,ignoreGroups:0,ignorePunctuation:[],wildcards:"disabled",each:()=>{},noMatch:()=>{},filter:()=>!0,done:()=>{},debug:!1,log:window.console},o)}get opt(){return this._opt}get iterator(){return new r(this.ctx,this.opt.iframes,this.opt.exclude,this.opt.iframesTimeout)}log(o,s="debug"){const l=this.opt.log;this.opt.debug&&typeof l=="object"&&typeof l[s]=="function"&&l[s](`mark.js: ${o}`)}escapeStr(o){return o.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")}createRegExp(o){return this.opt.wildcards!=="disabled"&&(o=this.setupWildcardsRegExp(o)),o=this.escapeStr(o),Object.keys(this.opt.synonyms).length&&(o=this.createSynonymsRegExp(o)),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.setupIgnoreJoinersRegExp(o)),this.opt.diacritics&&(o=this.createDiacriticsRegExp(o)),o=this.createMergedBlanksRegExp(o),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.createJoinersRegExp(o)),this.opt.wildcards!=="disabled"&&(o=this.createWildcardsRegExp(o)),o=this.createAccuracyRegExp(o),o}createSynonymsRegExp(o){const s=this.opt.synonyms,l=this.opt.caseSensitive?"":"i",c=this.opt.ignoreJoiners||this.opt.ignorePunctuation.length?"\0":"";for(let h in s)if(s.hasOwnProperty(h)){const f=s[h],d=this.opt.wildcards!=="disabled"?this.setupWildcardsRegExp(h):this.escapeStr(h),u=this.opt.wildcards!=="disabled"?this.setupWildcardsRegExp(f):this.escapeStr(f);d!==""&&u!==""&&(o=o.replace(new RegExp(`(${this.escapeStr(d)}|${this.escapeStr(u)})`,`gm${l}`),c+`(${this.processSynomyms(d)}|${this.processSynomyms(u)})`+c))}return o}processSynomyms(o){return(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.setupIgnoreJoinersRegExp(o)),o}setupWildcardsRegExp(o){return o=o.replace(/(?:\\)*\?/g,s=>s.charAt(0)==="\\"?"?":""),o.replace(/(?:\\)*\*/g,s=>s.charAt(0)==="\\"?"*":"")}createWildcardsRegExp(o){let s=this.opt.wildcards==="withSpaces";return o.replace(/\u0001/g,s?"[\\S\\s]?":"\\S?").replace(/\u0002/g,s?"[\\S\\s]*?":"\\S*")}setupIgnoreJoinersRegExp(o){return o.replace(/[^(|)\\]/g,(s,l,c)=>{let h=c.charAt(l+1);return/[(|)\\]/.test(h)||h===""?s:s+"\0"})}createJoinersRegExp(o){let s=[];const l=this.opt.ignorePunctuation;return Array.isArray(l)&&l.length&&s.push(this.escapeStr(l.join(""))),this.opt.ignoreJoiners&&s.push("\\u00ad\\u200b\\u200c\\u200d"),s.length?o.split(/\u0000+/).join(`[${s.join("")}]*`):o}createDiacriticsRegExp(o){const s=this.opt.caseSensitive?"":"i",l=this.opt.caseSensitive?["aàáảãạăằắẳẵặâầấẩẫậäåāą","AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćč","CÇĆČ","dđď","DĐĎ","eèéẻẽẹêềếểễệëěēę","EÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïī","IÌÍỈĨỊÎÏĪ","lł","LŁ","nñňń","NÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøō","OÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rř","RŘ","sšśșş","SŠŚȘŞ","tťțţ","TŤȚŢ","uùúủũụưừứửữựûüůū","UÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿ","YÝỲỶỸỴŸ","zžżź","ZŽŻŹ"]:["aàáảãạăằắẳẵặâầấẩẫậäåāąAÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćčCÇĆČ","dđďDĐĎ","eèéẻẽẹêềếểễệëěēęEÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïīIÌÍỈĨỊÎÏĪ","lłLŁ","nñňńNÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøōOÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rřRŘ","sšśșşSŠŚȘŞ","tťțţTŤȚŢ","uùúủũụưừứửữựûüůūUÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿYÝỲỶỸỴŸ","zžżźZŽŻŹ"];let c=[];return o.split("").forEach(h=>{l.every(f=>{if(f.indexOf(h)!==-1){if(c.indexOf(f)>-1)return!1;o=o.replace(new RegExp(`[${f}]`,`gm${s}`),`[${f}]`),c.push(f)}return!0})}),o}createMergedBlanksRegExp(o){return o.replace(/[\s]+/gmi,"[\\s]+")}createAccuracyRegExp(o){const s="!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~¡¿";let l=this.opt.accuracy,c=typeof l=="string"?l:l.value,h=typeof l=="string"?[]:l.limiters,f="";switch(h.forEach(d=>{f+=`|${this.escapeStr(d)}`}),c){case"partially":default:return`()(${o})`;case"complementary":return f="\\s"+(f||this.escapeStr(s)),`()([^${f}]*${o}[^${f}]*)`;case"exactly":return`(^|\\s${f})(${o})(?=$|\\s${f})`}}getSeparatedKeywords(o){let s=[];return o.forEach(l=>{this.opt.separateWordSearch?l.split(" ").forEach(c=>{c.trim()&&s.indexOf(c)===-1&&s.push(c)}):l.trim()&&s.indexOf(l)===-1&&s.push(l)}),{keywords:s.sort((l,c)=>c.length-l.length),length:s.length}}isNumeric(o){return Number(parseFloat(o))==o}checkRanges(o){if(!Array.isArray(o)||Object.prototype.toString.call(o[0])!=="[object Object]")return this.log("markRanges() will only accept an array of objects"),this.opt.noMatch(o),[];const s=[];let l=0;return o.sort((c,h)=>c.start-h.start).forEach(c=>{let{start:h,end:f,valid:d}=this.callNoMatchOnInvalidRanges(c,l);d&&(c.start=h,c.length=f-h,s.push(c),l=f)}),s}callNoMatchOnInvalidRanges(o,s){let l,c,h=!1;return o&&typeof o.start<"u"?(l=parseInt(o.start,10),c=l+parseInt(o.length,10),this.isNumeric(o.start)&&this.isNumeric(o.length)&&c-s>0&&c-l>0?h=!0:(this.log(`Ignoring invalid or overlapping range: ${JSON.stringify(o)}`),this.opt.noMatch(o))):(this.log(`Ignoring invalid range: ${JSON.stringify(o)}`),this.opt.noMatch(o)),{start:l,end:c,valid:h}}checkWhitespaceRanges(o,s,l){let c,h=!0,f=l.length,d=s-f,u=parseInt(o.start,10)-d;return u=u>f?f:u,c=u+parseInt(o.length,10),c>f&&(c=f,this.log(`End range automatically set to the max value of ${f}`)),u<0||c-u<0||u>f||c>f?(h=!1,this.log(`Invalid range: ${JSON.stringify(o)}`),this.opt.noMatch(o)):l.substring(u,c).replace(/\s+/g,"")===""&&(h=!1,this.log("Skipping whitespace only range: "+JSON.stringify(o)),this.opt.noMatch(o)),{start:u,end:c,valid:h}}getTextNodes(o){let s="",l=[];this.iterator.forEachNode(NodeFilter.SHOW_TEXT,c=>{l.push({start:s.length,end:(s+=c.textContent).length,node:c})},c=>this.matchesExclude(c.parentNode)?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT,()=>{o({value:s,nodes:l})})}matchesExclude(o){return r.matches(o,this.opt.exclude.concat(["script","style","title","head","html"]))}wrapRangeInTextNode(o,s,l){const c=this.opt.element?this.opt.element:"mark",h=o.splitText(s),f=h.splitText(l-s);let d=document.createElement(c);return d.setAttribute("data-markjs","true"),this.opt.className&&d.setAttribute("class",this.opt.className),d.textContent=h.textContent,h.parentNode.replaceChild(d,h),f}wrapRangeInMappedTextNode(o,s,l,c,h){o.nodes.every((f,d)=>{const u=o.nodes[d+1];if(typeof u>"u"||u.start>s){if(!c(f.node))return!1;const g=s-f.start,x=(l>f.end?f.end:l)-f.start,m=o.value.substr(0,f.start),p=o.value.substr(x+f.start);if(f.node=this.wrapRangeInTextNode(f.node,g,x),o.value=m+p,o.nodes.forEach((v,w)=>{w>=d&&(o.nodes[w].start>0&&w!==d&&(o.nodes[w].start-=x),o.nodes[w].end-=x)}),l-=x,h(f.node.previousSibling,f.start),l>f.end)s=f.end;else return!1}return!0})}wrapMatches(o,s,l,c,h){const f=s===0?0:s+1;this.getTextNodes(d=>{d.nodes.forEach(u=>{u=u.node;let g;for(;(g=o.exec(u.textContent))!==null&&g[f]!=="";){if(!l(g[f],u))continue;let x=g.index;if(f!==0)for(let m=1;m<f;m++)x+=g[m].length;u=this.wrapRangeInTextNode(u,x,x+g[f].length),c(u.previousSibling),o.lastIndex=0}}),h()})}wrapMatchesAcrossElements(o,s,l,c,h){const f=s===0?0:s+1;this.getTextNodes(d=>{let u;for(;(u=o.exec(d.value))!==null&&u[f]!=="";){let g=u.index;if(f!==0)for(let m=1;m<f;m++)g+=u[m].length;const x=g+u[f].length;this.wrapRangeInMappedTextNode(d,g,x,m=>l(u[f],m),(m,p)=>{o.lastIndex=p,c(m)})}h()})}wrapRangeFromIndex(o,s,l,c){this.getTextNodes(h=>{const f=h.value.length;o.forEach((d,u)=>{let{start:g,end:x,valid:m}=this.checkWhitespaceRanges(d,f,h.value);m&&this.wrapRangeInMappedTextNode(h,g,x,p=>s(p,d,h.value.substring(g,x),u),p=>{l(p,d)})}),c()})}unwrapMatches(o){const s=o.parentNode;let l=document.createDocumentFragment();for(;o.firstChild;)l.appendChild(o.removeChild(o.firstChild));s.replaceChild(l,o),this.ie?this.normalizeTextNode(s):s.normalize()}normalizeTextNode(o){if(o){if(o.nodeType===3)for(;o.nextSibling&&o.nextSibling.nodeType===3;)o.nodeValue+=o.nextSibling.nodeValue,o.parentNode.removeChild(o.nextSibling);else this.normalizeTextNode(o.firstChild);this.normalizeTextNode(o.nextSibling)}}markRegExp(o,s){this.opt=s,this.log(`Searching with expression "${o}"`);let l=0,c="wrapMatches";const h=f=>{l++,this.opt.each(f)};this.opt.acrossElements&&(c="wrapMatchesAcrossElements"),this[c](o,this.opt.ignoreGroups,(f,d)=>this.opt.filter(d,f,l),h,()=>{l===0&&this.opt.noMatch(o),this.opt.done(l)})}mark(o,s){this.opt=s;let l=0,c="wrapMatches";const{keywords:h,length:f}=this.getSeparatedKeywords(typeof o=="string"?[o]:o),d=this.opt.caseSensitive?"":"i",u=g=>{let x=new RegExp(this.createRegExp(g),`gm${d}`),m=0;this.log(`Searching with expression "${x}"`),this[c](x,1,(p,v)=>this.opt.filter(v,g,l,m),p=>{m++,l++,this.opt.each(p)},()=>{m===0&&this.opt.noMatch(g),h[f-1]===g?this.opt.done(l):u(h[h.indexOf(g)+1])})};this.opt.acrossElements&&(c="wrapMatchesAcrossElements"),f===0?this.opt.done(l):u(h[0])}markRanges(o,s){this.opt=s;let l=0,c=this.checkRanges(o);c&&c.length?(this.log("Starting to mark with the following ranges: "+JSON.stringify(c)),this.wrapRangeFromIndex(c,(h,f,d,u)=>this.opt.filter(h,f,d,u),(h,f)=>{l++,this.opt.each(h,f)},()=>{this.opt.done(l)})):this.opt.done(l)}unmark(o){this.opt=o;let s=this.opt.element?this.opt.element:"*";s+="[data-markjs]",this.opt.className&&(s+=`.${this.opt.className}`),this.log(`Removal selector "${s}"`),this.iterator.forEachNode(NodeFilter.SHOW_ELEMENT,l=>{this.unwrapMatches(l)},l=>{const c=r.matches(l,s),h=this.matchesExclude(l);return!c||h?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT},this.opt.done)}}function i(a){const o=new n(a);return this.mark=(s,l)=>(o.mark(s,l),this),this.markRegExp=(s,l)=>(o.markRegExp(s,l),this),this.markRanges=(s,l)=>(o.markRanges(s,l),this),this.unmark=s=>(o.unmark(s),this),this}return i}))})(Ht)),Ht.exports}var fs=ds();const ms=cs(fs),hs="ENTRIES",xo="KEYS",ko="VALUES",he="";class fr{constructor(e,r){const n=e._tree,i=Array.from(n.keys());this.set=e,this._type=r,this._path=i.length>0?[{node:n,keys:i}]:[]}next(){const e=this.dive();return this.backtrack(),e}dive(){if(this._path.length===0)return{done:!0,value:void 0};const{node:e,keys:r}=ot(this._path);if(ot(r)===he)return{done:!1,value:this.result()};const n=e.get(ot(r));return this._path.push({node:n,keys:Array.from(n.keys())}),this.dive()}backtrack(){if(this._path.length===0)return;const e=ot(this._path).keys;e.pop(),!(e.length>0)&&(this._path.pop(),this.backtrack())}key(){return this.set._prefix+this._path.map(({keys:e})=>ot(e)).filter(e=>e!==he).join("")}value(){return ot(this._path).node.get(he)}result(){switch(this._type){case ko:return this.value();case xo:return this.key();default:return[this.key(),this.value()]}}[Symbol.iterator](){return this}}const ot=t=>t[t.length-1],ps=(t,e,r)=>{const n=new Map;if(e===void 0)return n;const i=e.length+1,a=i+r,o=new Uint8Array(a*i).fill(r+1);for(let s=0;s<i;++s)o[s]=s;for(let s=1;s<a;++s)o[s*i]=s;return _o(t,e,r,n,o,1,i,""),n},_o=(t,e,r,n,i,a,o,s)=>{const l=a*o;e:for(const c of t.keys())if(c===he){const h=i[l-1];h<=r&&n.set(s,[t.get(c),h])}else{let h=a;for(let f=0;f<c.length;++f,++h){const d=c[f],u=o*h,g=u-o;let x=i[u];const m=Math.max(0,h-r-1),p=Math.min(o-1,h+r);for(let v=m;v<p;++v){const w=d!==e[v],b=i[g+v]+ +w,k=i[g+v+1]+1,C=i[u+v]+1,O=i[u+v+1]=Math.min(b,k,C);O<x&&(x=O)}if(x>r)continue e}_o(t.get(c),e,r,n,i,h,o,s+c)}};class Ue{constructor(e=new Map,r=""){this._size=void 0,this._tree=e,this._prefix=r}atPrefix(e){if(!e.startsWith(this._prefix))throw new Error("Mismatched prefix");const[r,n]=Yt(this._tree,e.slice(this._prefix.length));if(r===void 0){const[i,a]=Wr(n);for(const o of i.keys())if(o!==he&&o.startsWith(a)){const s=new Map;return s.set(o.slice(a.length),i.get(o)),new Ue(s,e)}}return new Ue(r,e)}clear(){this._size=void 0,this._tree.clear()}delete(e){return this._size=void 0,gs(this._tree,e)}entries(){return new fr(this,hs)}forEach(e){for(const[r,n]of this)e(r,n,this)}fuzzyGet(e,r){return ps(this._tree,e,r)}get(e){const r=Lr(this._tree,e);return r!==void 0?r.get(he):void 0}has(e){const r=Lr(this._tree,e);return r!==void 0&&r.has(he)}keys(){return new fr(this,xo)}set(e,r){if(typeof e!="string")throw new Error("key must be a string");return this._size=void 0,mr(this._tree,e).set(he,r),this}get size(){if(this._size)return this._size;this._size=0;const e=this.entries();for(;!e.next().done;)this._size+=1;return this._size}update(e,r){if(typeof e!="string")throw new Error("key must be a string");this._size=void 0;const n=mr(this._tree,e);return n.set(he,r(n.get(he))),this}fetch(e,r){if(typeof e!="string")throw new Error("key must be a string");this._size=void 0;const n=mr(this._tree,e);let i=n.get(he);return i===void 0&&n.set(he,i=r()),i}values(){return new fr(this,ko)}[Symbol.iterator](){return this.entries()}static from(e){const r=new Ue;for(const[n,i]of e)r.set(n,i);return r}static fromObject(e){return Ue.from(Object.entries(e))}}const Yt=(t,e,r=[])=>{if(e.length===0||t==null)return[t,r];for(const n of t.keys())if(n!==he&&e.startsWith(n))return r.push([t,n]),Yt(t.get(n),e.slice(n.length),r);return r.push([t,e]),Yt(void 0,"",r)},Lr=(t,e)=>{if(e.length===0||t==null)return t;for(const r of t.keys())if(r!==he&&e.startsWith(r))return Lr(t.get(r),e.slice(r.length))},mr=(t,e)=>{const r=e.length;e:for(let n=0;t&&n<r;){for(const a of t.keys())if(a!==he&&e[n]===a[0]){const o=Math.min(r-n,a.length);let s=1;for(;s<o&&e[n+s]===a[s];)++s;const l=t.get(a);if(s===a.length)t=l;else{const c=new Map;c.set(a.slice(s),l),t.set(e.slice(n,n+s),c),t.delete(a),t=c}n+=s;continue e}const i=new Map;return t.set(e.slice(n),i),i}return t},gs=(t,e)=>{const[r,n]=Yt(t,e);if(r!==void 0){if(r.delete(he),r.size===0)So(n);else if(r.size===1){const[i,a]=r.entries().next().value;Ao(n,i,a)}}},So=t=>{if(t.length===0)return;const[e,r]=Wr(t);if(e.delete(r),e.size===0)So(t.slice(0,-1));else if(e.size===1){const[n,i]=e.entries().next().value;n!==he&&Ao(t.slice(0,-1),n,i)}},Ao=(t,e,r)=>{if(t.length===0)return;const[n,i]=Wr(t);n.set(i+e,r),n.delete(i)},Wr=t=>t[t.length-1],Gr="or",To="and",vs="and_not";class ut{constructor(e){if((e==null?void 0:e.fields)==null)throw new Error('MiniSearch: option "fields" must be provided');const r=e.autoVacuum==null||e.autoVacuum===!0?gr:e.autoVacuum;this._options={...pr,...e,autoVacuum:r,searchOptions:{...Fn,...e.searchOptions||{}},autoSuggestOptions:{...xs,...e.autoSuggestOptions||{}}},this._index=new Ue,this._documentCount=0,this._documentIds=new Map,this._idToShortId=new Map,this._fieldIds={},this._fieldLength=new Map,this._avgFieldLength=[],this._nextId=0,this._storedFields=new Map,this._dirtCount=0,this._currentVacuum=null,this._enqueuedVacuum=null,this._enqueuedVacuumConditions=Rr,this.addFields(this._options.fields)}add(e){const{extractField:r,stringifyField:n,tokenize:i,processTerm:a,fields:o,idField:s}=this._options,l=r(e,s);if(l==null)throw new Error(`MiniSearch: document does not have ID field "${s}"`);if(this._idToShortId.has(l))throw new Error(`MiniSearch: duplicate ID ${l}`);const c=this.addDocumentId(l);this.saveStoredFields(c,e);for(const h of o){const f=r(e,h);if(f==null)continue;const d=i(n(f,h),h),u=this._fieldIds[h],g=new Set(d).size;this.addFieldLength(c,u,this._documentCount-1,g);for(const x of d){const m=a(x,h);if(Array.isArray(m))for(const p of m)this.addTerm(u,c,p);else m&&this.addTerm(u,c,m)}}}addAll(e){for(const r of e)this.add(r)}addAllAsync(e,r={}){const{chunkSize:n=10}=r,i={chunk:[],promise:Promise.resolve()},{chunk:a,promise:o}=e.reduce(({chunk:s,promise:l},c,h)=>(s.push(c),(h+1)%n===0?{chunk:[],promise:l.then(()=>new Promise(f=>setTimeout(f,0))).then(()=>this.addAll(s))}:{chunk:s,promise:l}),i);return o.then(()=>this.addAll(a))}remove(e){const{tokenize:r,processTerm:n,extractField:i,stringifyField:a,fields:o,idField:s}=this._options,l=i(e,s);if(l==null)throw new Error(`MiniSearch: document does not have ID field "${s}"`);const c=this._idToShortId.get(l);if(c==null)throw new Error(`MiniSearch: cannot remove document with ID ${l}: it is not in the index`);for(const h of o){const f=i(e,h);if(f==null)continue;const d=r(a(f,h),h),u=this._fieldIds[h],g=new Set(d).size;this.removeFieldLength(c,u,this._documentCount,g);for(const x of d){const m=n(x,h);if(Array.isArray(m))for(const p of m)this.removeTerm(u,c,p);else m&&this.removeTerm(u,c,m)}}this._storedFields.delete(c),this._documentIds.delete(c),this._idToShortId.delete(l),this._fieldLength.delete(c),this._documentCount-=1}removeAll(e){if(e)for(const r of e)this.remove(r);else{if(arguments.length>0)throw new Error("Expected documents to be present. Omit the argument to remove all documents.");this._index=new Ue,this._documentCount=0,this._documentIds=new Map,this._idToShortId=new Map,this._fieldLength=new Map,this._avgFieldLength=[],this._storedFields=new Map,this._nextId=0}}discard(e){const r=this._idToShortId.get(e);if(r==null)throw new Error(`MiniSearch: cannot discard document with ID ${e}: it is not in the index`);this._idToShortId.delete(e),this._documentIds.delete(r),this._storedFields.delete(r),(this._fieldLength.get(r)||[]).forEach((n,i)=>{this.removeFieldLength(r,i,this._documentCount,n)}),this._fieldLength.delete(r),this._documentCount-=1,this._dirtCount+=1,this.maybeAutoVacuum()}maybeAutoVacuum(){if(this._options.autoVacuum===!1)return;const{minDirtFactor:e,minDirtCount:r,batchSize:n,batchWait:i}=this._options.autoVacuum;this.conditionalVacuum({batchSize:n,batchWait:i},{minDirtCount:r,minDirtFactor:e})}discardAll(e){const r=this._options.autoVacuum;try{this._options.autoVacuum=!1;for(const n of e)this.discard(n)}finally{this._options.autoVacuum=r}this.maybeAutoVacuum()}replace(e){const{idField:r,extractField:n}=this._options,i=n(e,r);this.discard(i),this.add(e)}vacuum(e={}){return this.conditionalVacuum(e)}conditionalVacuum(e,r){return this._currentVacuum?(this._enqueuedVacuumConditions=this._enqueuedVacuumConditions&&r,this._enqueuedVacuum!=null?this._enqueuedVacuum:(this._enqueuedVacuum=this._currentVacuum.then(()=>{const n=this._enqueuedVacuumConditions;return this._enqueuedVacuumConditions=Rr,this.performVacuuming(e,n)}),this._enqueuedVacuum)):this.vacuumConditionsMet(r)===!1?Promise.resolve():(this._currentVacuum=this.performVacuuming(e),this._currentVacuum)}async performVacuuming(e,r){const n=this._dirtCount;if(this.vacuumConditionsMet(r)){const i=e.batchSize||Mr.batchSize,a=e.batchWait||Mr.batchWait;let o=1;for(const[s,l]of this._index){for(const[c,h]of l)for(const[f]of h)this._documentIds.has(f)||(h.size<=1?l.delete(c):h.delete(f));this._index.get(s).size===0&&this._index.delete(s),o%i===0&&await new Promise(c=>setTimeout(c,a)),o+=1}this._dirtCount-=n}await null,this._currentVacuum=this._enqueuedVacuum,this._enqueuedVacuum=null}vacuumConditionsMet(e){if(e==null)return!0;let{minDirtCount:r,minDirtFactor:n}=e;return r=r||gr.minDirtCount,n=n||gr.minDirtFactor,this.dirtCount>=r&&this.dirtFactor>=n}get isVacuuming(){return this._currentVacuum!=null}get dirtCount(){return this._dirtCount}get dirtFactor(){return this._dirtCount/(1+this._documentCount+this._dirtCount)}has(e){return this._idToShortId.has(e)}getStoredFields(e){const r=this._idToShortId.get(e);if(r!=null)return this._storedFields.get(r)}search(e,r={}){const{searchOptions:n}=this._options,i={...n,...r},a=this.executeQuery(e,r),o=[];for(const[s,{score:l,terms:c,match:h}]of a){const f=c.length||1,d={id:this._documentIds.get(s),score:l*f,terms:Object.keys(h),queryTerms:c,match:h};Object.assign(d,this._storedFields.get(s)),(i.filter==null||i.filter(d))&&o.push(d)}return e===ut.wildcard&&i.boostDocument==null||o.sort(Pn),o}autoSuggest(e,r={}){r={...this._options.autoSuggestOptions,...r};const n=new Map;for(const{score:a,terms:o}of this.search(e,r)){const s=o.join(" "),l=n.get(s);l!=null?(l.score+=a,l.count+=1):n.set(s,{score:a,terms:o,count:1})}const i=[];for(const[a,{score:o,terms:s,count:l}]of n)i.push({suggestion:a,terms:s,score:o/l});return i.sort(Pn),i}get documentCount(){return this._documentCount}get termCount(){return this._index.size}static loadJSON(e,r){if(r==null)throw new Error("MiniSearch: loadJSON should be given the same options used when serializing the index");return this.loadJS(JSON.parse(e),r)}static async loadJSONAsync(e,r){if(r==null)throw new Error("MiniSearch: loadJSON should be given the same options used when serializing the index");return this.loadJSAsync(JSON.parse(e),r)}static getDefault(e){if(pr.hasOwnProperty(e))return hr(pr,e);throw new Error(`MiniSearch: unknown option "${e}"`)}static loadJS(e,r){const{index:n,documentIds:i,fieldLength:a,storedFields:o,serializationVersion:s}=e,l=this.instantiateMiniSearch(e,r);l._documentIds=Rt(i),l._fieldLength=Rt(a),l._storedFields=Rt(o);for(const[c,h]of l._documentIds)l._idToShortId.set(h,c);for(const[c,h]of n){const f=new Map;for(const d of Object.keys(h)){let u=h[d];s===1&&(u=u.ds),f.set(parseInt(d,10),Rt(u))}l._index.set(c,f)}return l}static async loadJSAsync(e,r){const{index:n,documentIds:i,fieldLength:a,storedFields:o,serializationVersion:s}=e,l=this.instantiateMiniSearch(e,r);l._documentIds=await Ft(i),l._fieldLength=await Ft(a),l._storedFields=await Ft(o);for(const[h,f]of l._documentIds)l._idToShortId.set(f,h);let c=0;for(const[h,f]of n){const d=new Map;for(const u of Object.keys(f)){let g=f[u];s===1&&(g=g.ds),d.set(parseInt(u,10),await Ft(g))}++c%1e3===0&&await Co(0),l._index.set(h,d)}return l}static instantiateMiniSearch(e,r){const{documentCount:n,nextId:i,fieldIds:a,averageFieldLength:o,dirtCount:s,serializationVersion:l}=e;if(l!==1&&l!==2)throw new Error("MiniSearch: cannot deserialize an index created with an incompatible version");const c=new ut(r);return c._documentCount=n,c._nextId=i,c._idToShortId=new Map,c._fieldIds=a,c._avgFieldLength=o,c._dirtCount=s||0,c._index=new Ue,c}executeQuery(e,r={}){if(e===ut.wildcard)return this.executeWildcardQuery(r);if(typeof e!="string"){const d={...r,...e,queries:void 0},u=e.queries.map(g=>this.executeQuery(g,d));return this.combineResults(u,d.combineWith)}const{tokenize:n,processTerm:i,searchOptions:a}=this._options,o={tokenize:n,processTerm:i,...a,...r},{tokenize:s,processTerm:l}=o,f=s(e).flatMap(d=>l(d)).filter(d=>!!d).map(Es(o)).map(d=>this.executeQuerySpec(d,o));return this.combineResults(f,o.combineWith)}executeQuerySpec(e,r){const n={...this._options.searchOptions,...r},i=(n.fields||this._options.fields).reduce((x,m)=>({...x,[m]:hr(n.boost,m)||1}),{}),{boostDocument:a,weights:o,maxFuzzy:s,bm25:l}=n,{fuzzy:c,prefix:h}={...Fn.weights,...o},f=this._index.get(e.term),d=this.termResults(e.term,e.term,1,e.termBoost,f,i,a,l);let u,g;if(e.prefix&&(u=this._index.atPrefix(e.term)),e.fuzzy){const x=e.fuzzy===!0?.2:e.fuzzy,m=x<1?Math.min(s,Math.round(e.term.length*x)):x;m&&(g=this._index.fuzzyGet(e.term,m))}if(u)for(const[x,m]of u){const p=x.length-e.term.length;if(!p)continue;g==null||g.delete(x);const v=h*x.length/(x.length+.3*p);this.termResults(e.term,x,v,e.termBoost,m,i,a,l,d)}if(g)for(const x of g.keys()){const[m,p]=g.get(x);if(!p)continue;const v=c*x.length/(x.length+p);this.termResults(e.term,x,v,e.termBoost,m,i,a,l,d)}return d}executeWildcardQuery(e){const r=new Map,n={...this._options.searchOptions,...e};for(const[i,a]of this._documentIds){const o=n.boostDocument?n.boostDocument(a,"",this._storedFields.get(i)):1;r.set(i,{score:o,terms:[],match:{}})}return r}combineResults(e,r=Gr){if(e.length===0)return new Map;const n=r.toLowerCase(),i=bs[n];if(!i)throw new Error(`Invalid combination operator: ${r}`);return e.reduce(i)||new Map}toJSON(){const e=[];for(const[r,n]of this._index){const i={};for(const[a,o]of n)i[a]=Object.fromEntries(o);e.push([r,i])}return{documentCount:this._documentCount,nextId:this._nextId,documentIds:Object.fromEntries(this._documentIds),fieldIds:this._fieldIds,fieldLength:Object.fromEntries(this._fieldLength),averageFieldLength:this._avgFieldLength,storedFields:Object.fromEntries(this._storedFields),dirtCount:this._dirtCount,index:e,serializationVersion:2}}termResults(e,r,n,i,a,o,s,l,c=new Map){if(a==null)return c;for(const h of Object.keys(o)){const f=o[h],d=this._fieldIds[h],u=a.get(d);if(u==null)continue;let g=u.size;const x=this._avgFieldLength[d];for(const m of u.keys()){if(!this._documentIds.has(m)){this.removeTerm(d,m,r),g-=1;continue}const p=s?s(this._documentIds.get(m),r,this._storedFields.get(m)):1;if(!p)continue;const v=u.get(m),w=this._fieldLength.get(m)[d],b=ws(v,g,this._documentCount,w,x,l),k=n*i*f*p*b,C=c.get(m);if(C){C.score+=k,ks(C.terms,e);const O=hr(C.match,r);O?O.push(h):C.match[r]=[h]}else c.set(m,{score:k,terms:[e],match:{[r]:[h]}})}}return c}addTerm(e,r,n){const i=this._index.fetch(n,Nn);let a=i.get(e);if(a==null)a=new Map,a.set(r,1),i.set(e,a);else{const o=a.get(r);a.set(r,(o||0)+1)}}removeTerm(e,r,n){if(!this._index.has(n)){this.warnDocumentChanged(r,e,n);return}const i=this._index.fetch(n,Nn),a=i.get(e);a==null||a.get(r)==null?this.warnDocumentChanged(r,e,n):a.get(r)<=1?a.size<=1?i.delete(e):a.delete(r):a.set(r,a.get(r)-1),this._index.get(n).size===0&&this._index.delete(n)}warnDocumentChanged(e,r,n){for(const i of Object.keys(this._fieldIds))if(this._fieldIds[i]===r){this._options.logger("warn",`MiniSearch: document with ID ${this._documentIds.get(e)} has changed before removal: term "${n}" was not present in field "${i}". Removing a document after it has changed can corrupt the index!`,"version_conflict");return}}addDocumentId(e){const r=this._nextId;return this._idToShortId.set(e,r),this._documentIds.set(r,e),this._documentCount+=1,this._nextId+=1,r}addFields(e){for(let r=0;r<e.length;r++)this._fieldIds[e[r]]=r}addFieldLength(e,r,n,i){let a=this._fieldLength.get(e);a==null&&this._fieldLength.set(e,a=[]),a[r]=i;const s=(this._avgFieldLength[r]||0)*n+i;this._avgFieldLength[r]=s/(n+1)}removeFieldLength(e,r,n,i){if(n===1){this._avgFieldLength[r]=0;return}const a=this._avgFieldLength[r]*n-i;this._avgFieldLength[r]=a/(n-1)}saveStoredFields(e,r){const{storeFields:n,extractField:i}=this._options;if(n==null||n.length===0)return;let a=this._storedFields.get(e);a==null&&this._storedFields.set(e,a={});for(const o of n){const s=i(r,o);s!==void 0&&(a[o]=s)}}}ut.wildcard=Symbol("*");const hr=(t,e)=>Object.prototype.hasOwnProperty.call(t,e)?t[e]:void 0,bs={[Gr]:(t,e)=>{for(const r of e.keys()){const n=t.get(r);if(n==null)t.set(r,e.get(r));else{const{score:i,terms:a,match:o}=e.get(r);n.score=n.score+i,n.match=Object.assign(n.match,o),On(n.terms,a)}}return t},[To]:(t,e)=>{const r=new Map;for(const n of e.keys()){const i=t.get(n);if(i==null)continue;const{score:a,terms:o,match:s}=e.get(n);On(i.terms,o),r.set(n,{score:i.score+a,terms:i.terms,match:Object.assign(i.match,s)})}return r},[vs]:(t,e)=>{for(const r of e.keys())t.delete(r);return t}},ys={k:1.2,b:.7,d:.5},ws=(t,e,r,n,i,a)=>{const{k:o,b:s,d:l}=a;return Math.log(1+(r-e+.5)/(e+.5))*(l+t*(o+1)/(t+o*(1-s+s*n/i)))},Es=t=>(e,r,n)=>{const i=typeof t.fuzzy=="function"?t.fuzzy(e,r,n):t.fuzzy||!1,a=typeof t.prefix=="function"?t.prefix(e,r,n):t.prefix===!0,o=typeof t.boostTerm=="function"?t.boostTerm(e,r,n):1;return{term:e,fuzzy:i,prefix:a,termBoost:o}},pr={idField:"id",extractField:(t,e)=>t[e],stringifyField:(t,e)=>t.toString(),tokenize:t=>t.split(_s),processTerm:t=>t.toLowerCase(),fields:void 0,searchOptions:void 0,storeFields:[],logger:(t,e)=>{typeof(console==null?void 0:console[t])=="function"&&console[t](e)},autoVacuum:!0},Fn={combineWith:Gr,prefix:!1,fuzzy:!1,maxFuzzy:6,boost:{},weights:{fuzzy:.45,prefix:.375},bm25:ys},xs={combineWith:To,prefix:(t,e,r)=>e===r.length-1},Mr={batchSize:1e3,batchWait:10},Rr={minDirtFactor:.1,minDirtCount:20},gr={...Mr,...Rr},ks=(t,e)=>{t.includes(e)||t.push(e)},On=(t,e)=>{for(const r of e)t.includes(r)||t.push(r)},Pn=({score:t},{score:e})=>e-t,Nn=()=>new Map,Rt=t=>{const e=new Map;for(const r of Object.keys(t))e.set(parseInt(r,10),t[r]);return e},Ft=async t=>{const e=new Map;let r=0;for(const n of Object.keys(t))e.set(parseInt(n,10),t[n]),++r%1e3===0&&await Co(0);return e},Co=t=>new Promise(e=>setTimeout(e,t)),_s=/[\n\r\p{Z}\p{P}]+/u,Io=We;class Ss{constructor(e=10){ur(this,"max");ur(this,"cache");this.max=e,this.cache=new Map}get(e){let r=this.cache.get(e);return r!==void 0&&(this.cache.delete(e),this.cache.set(e,r)),r}set(e,r){this.cache.has(e)?this.cache.delete(e):this.cache.size===this.max&&this.cache.delete(this.first()),this.cache.set(e,r)}first(){return this.cache.keys().next().value}clear(){this.cache.clear()}}function As(t){const{localeIndex:e,theme:r}=Io();function n(i){var g,x,m;const a=i.split("."),o=(g=r.value.search)==null?void 0:g.options,s=o&&typeof o=="object",l=s&&((m=(x=o.locales)==null?void 0:x[e.value])==null?void 0:m.translations)||null,c=s&&o.translations||null;let h=l,f=c,d=t;const u=a.pop();for(const p of a){let v=null;const w=d==null?void 0:d[p];w&&(v=d=w);const b=f==null?void 0:f[p];b&&(v=f=b);const k=h==null?void 0:h[p];k&&(v=h=k),w||(d=v),b||(f=v),k||(h=v)}return(h==null?void 0:h[u])??(f==null?void 0:f[u])??(d==null?void 0:d[u])??""}return n}const Ts=["aria-owns"],Cs={class:"shell"},Is=["title"],Ls={class:"search-actions before"},Ms=["title"],Rs=["aria-activedescendant","aria-controls","placeholder"],Fs={class:"search-actions"},Os=["title"],Ps=["disabled","title"],Ns=["id","role","aria-labelledby"],Ds=["id","aria-selected"],zs=["href","aria-label","onMouseenter","onFocusin","data-index"],$s={class:"titles"},Vs=["innerHTML"],Hs={class:"title main"},qs=["innerHTML"],Bs={key:0,class:"excerpt-wrapper"},js={key:0,class:"excerpt",inert:""},Us=["innerHTML"],Ks={key:0,class:"no-results"},Ws={class:"search-keyboard-shortcuts"},Gs=["aria-label"],Js=["aria-label"],Ys=["aria-label"],Qs=["aria-label"],Zs=ke({__name:"VPLocalSearchBox",emits:["close"],setup(t,{emit:e}){var J,q;const r=e,n=Ze(),i=Ze(),a=Ze(Ra),o=Io(),{activate:s}=ls(n,{immediate:!0,allowOutsideClick:!0,clickOutsideDeactivates:!0,escapeDeactivates:!0}),{localeIndex:l,theme:c}=o,h=xn(async()=>{var I,T,K,te,N,X,ne,Z,ae;return kn(ut.loadJSON((K=await((T=(I=a.value)[l.value])==null?void 0:T.call(I)))==null?void 0:K.default,{fields:["title","titles","text"],storeFields:["title","titles"],searchOptions:{fuzzy:.2,prefix:!0,boost:{title:4,text:2,titles:1},...((te=c.value.search)==null?void 0:te.provider)==="local"&&((X=(N=c.value.search.options)==null?void 0:N.miniSearch)==null?void 0:X.searchOptions)},...((ne=c.value.search)==null?void 0:ne.provider)==="local"&&((ae=(Z=c.value.search.options)==null?void 0:Z.miniSearch)==null?void 0:ae.options)}))}),d=B(()=>{var I,T;return((I=c.value.search)==null?void 0:I.provider)==="local"&&((T=c.value.search.options)==null?void 0:T.disableQueryPersistence)===!0}).value?ce(""):Fi("vitepress:local-search-filter",""),u=Oi("vitepress:local-search-detailed-list",((J=c.value.search)==null?void 0:J.provider)==="local"&&((q=c.value.search.options)==null?void 0:q.detailedView)===!0),g=B(()=>{var I,T,K;return((I=c.value.search)==null?void 0:I.provider)==="local"&&(((T=c.value.search.options)==null?void 0:T.disableDetailedView)===!0||((K=c.value.search.options)==null?void 0:K.detailedView)===!1)}),x=B(()=>{var T,K,te,N,X,ne,Z;const I=((T=c.value.search)==null?void 0:T.options)??c.value.algolia;return((X=(N=(te=(K=I==null?void 0:I.locales)==null?void 0:K[l.value])==null?void 0:te.translations)==null?void 0:N.button)==null?void 0:X.buttonText)||((Z=(ne=I==null?void 0:I.translations)==null?void 0:ne.button)==null?void 0:Z.buttonText)||"Search"});jr(()=>{g.value&&(u.value=!1)});const m=Ze([]),p=ce(!1);xe(d,()=>{p.value=!1});const v=xn(async()=>{if(i.value)return kn(new ms(i.value))},null),w=new Ss(16);Pi(()=>[h.value,d.value,u.value],async([I,T,K],te,N)=>{var de,pe,_e,He;(te==null?void 0:te[0])!==I&&w.clear();let X=!1;if(N(()=>{X=!0}),!I)return;m.value=I.search(T).slice(0,16),p.value=!0;const ne=K?await Promise.all(m.value.map(me=>b(me.id))):[];if(X)return;for(const{id:me,mod:Fe}of ne){const Se=me.slice(0,me.indexOf("#"));let Ae=w.get(Se);if(Ae)continue;Ae=new Map,w.set(Se,Ae);const Te=Fe.default??Fe;if(Te!=null&&Te.render||Te!=null&&Te.setup){const Me=Bi(Te);Me.config.warnHandler=()=>{},Me.provide(ji,o),Object.defineProperties(Me.config.globalProperties,{$frontmatter:{get(){return o.frontmatter.value}},$params:{get(){return o.page.value.params}}});const gt=document.createElement("div");Me.mount(gt),gt.querySelectorAll("h1, h2, h3, h4, h5, h6").forEach(Oe=>{var It;const Ge=(It=Oe.querySelector("a"))==null?void 0:It.getAttribute("href"),rt=(Ge==null?void 0:Ge.startsWith("#"))&&Ge.slice(1);if(!rt)return;let vt="";for(;(Oe=Oe.nextElementSibling)&&!/^h[1-6]$/i.test(Oe.tagName);)vt+=Oe.outerHTML;Ae.set(rt,vt)}),Me.unmount()}if(X)return}const Z=new Set;if(m.value=m.value.map(me=>{const[Fe,Se]=me.id.split("#"),Ae=w.get(Fe),Te=(Ae==null?void 0:Ae.get(Se))??"";for(const Me in me.match)Z.add(Me);return{...me,text:Te}}),await $e(),X)return;await new Promise(me=>{var Fe;(Fe=v.value)==null||Fe.unmark({done:()=>{var Se;(Se=v.value)==null||Se.markRegExp(U(Z),{done:me})}})});const ae=((de=n.value)==null?void 0:de.querySelectorAll(".result .excerpt"))??[];for(const me of ae)(pe=me.querySelector('mark[data-markjs="true"]'))==null||pe.scrollIntoView({block:"center"});(He=(_e=i.value)==null?void 0:_e.firstElementChild)==null||He.scrollIntoView({block:"start"})},{debounce:200,immediate:!0});async function b(I){const T=Ui(I.slice(0,I.indexOf("#")));try{if(!T)throw new Error(`Cannot find file for id: ${I}`);return{id:I,mod:await import(T)}}catch(K){return console.error(K),{id:I,mod:{}}}}const k=ce(),C=B(()=>{var I;return((I=d.value)==null?void 0:I.length)<=0});function O(I=!0){var T,K;(T=k.value)==null||T.focus(),I&&((K=k.value)==null||K.select())}Ke(()=>{O()});function $(I){I.pointerType==="mouse"&&O()}const j=ce(-1),ee=ce(!0);xe(m,I=>{j.value=I.length?0:-1,F()});function F(){$e(()=>{const I=document.querySelector(".result.selected");I==null||I.scrollIntoView({block:"nearest"})})}Lt("ArrowUp",I=>{I.preventDefault(),j.value--,j.value<0&&(j.value=m.value.length-1),ee.value=!0,F()}),Lt("ArrowDown",I=>{I.preventDefault(),j.value++,j.value>=m.value.length&&(j.value=0),ee.value=!0,F()});const Q=Ur();Lt("Enter",I=>{if(I.isComposing||I.target instanceof HTMLButtonElement&&I.target.type!=="submit")return;const T=m.value[j.value];if(I.target instanceof HTMLInputElement&&!T){I.preventDefault();return}T&&(Q.go(T.id),r("close"))}),Lt("Escape",()=>{r("close")});const y=As({modal:{displayDetails:"Display detailed list",resetButtonTitle:"Reset search",backButtonTitle:"Close search",noResultsText:"No results for",footer:{selectText:"to select",selectKeyAriaLabel:"enter",navigateText:"to navigate",navigateUpKeyAriaLabel:"up arrow",navigateDownKeyAriaLabel:"down arrow",closeText:"to close",closeKeyAriaLabel:"escape"}}});Ke(()=>{window.history.pushState(null,"",null)}),Ni("popstate",I=>{I.preventDefault(),r("close")});const A=Di(qi?document.body:null);Ke(()=>{$e(()=>{A.value=!0,$e().then(()=>s())})}),rr(()=>{A.value=!1});function P(){d.value="",$e().then(()=>O(!1))}function U(I){return new RegExp([...I].sort((T,K)=>K.length-T.length).map(T=>`(${Ki(T)})`).join("|"),"gi")}function z(I){var te;if(!ee.value)return;const T=(te=I.target)==null?void 0:te.closest(".result"),K=Number.parseInt(T==null?void 0:T.dataset.index);K>=0&&K!==j.value&&(j.value=K),ee.value=!1}return(I,T)=>{var K,te,N,X,ne;return L(),we(zi,{to:"body"},[S("div",{ref_key:"el",ref:n,role:"button","aria-owns":(K=m.value)!=null&&K.length?"localsearch-list":void 0,"aria-expanded":"true","aria-haspopup":"listbox","aria-labelledby":"localsearch-label",class:"VPLocalSearchBox"},[S("div",{class:"backdrop",onClick:T[0]||(T[0]=Z=>I.$emit("close"))}),S("div",Cs,[S("form",{class:"search-bar",onPointerup:T[4]||(T[4]=Z=>$(Z)),onSubmit:T[5]||(T[5]=Tr(()=>{},["prevent"]))},[S("label",{title:x.value,id:"localsearch-label",for:"localsearch-input"},[...T[7]||(T[7]=[S("span",{"aria-hidden":"true",class:"vpi-search search-icon local-search-icon"},null,-1)])],8,Is),S("div",Ls,[S("button",{class:"back-button",title:G(y)("modal.backButtonTitle"),onClick:T[1]||(T[1]=Z=>I.$emit("close"))},[...T[8]||(T[8]=[S("span",{class:"vpi-arrow-left local-search-icon"},null,-1)])],8,Ms)]),$i(S("input",{ref_key:"searchInput",ref:k,"onUpdate:modelValue":T[2]||(T[2]=Z=>Hi(d)?d.value=Z:null),"aria-activedescendant":j.value>-1?"localsearch-item-"+j.value:void 0,"aria-autocomplete":"both","aria-controls":(te=m.value)!=null&&te.length?"localsearch-list":void 0,"aria-labelledby":"localsearch-label",autocapitalize:"off",autocomplete:"off",autocorrect:"off",class:"search-input",id:"localsearch-input",enterkeyhint:"go",maxlength:"64",placeholder:x.value,spellcheck:"false",type:"search"},null,8,Rs),[[Vi,G(d)]]),S("div",Fs,[g.value?Y("",!0):(L(),R("button",{key:0,class:le(["toggle-layout-button",{"detailed-list":G(u)}]),type:"button",title:G(y)("modal.displayDetails"),onClick:T[3]||(T[3]=Z=>j.value>-1&&(u.value=!G(u)))},[...T[9]||(T[9]=[S("span",{class:"vpi-layout-list local-search-icon"},null,-1)])],10,Os)),S("button",{class:"clear-button",type:"reset",disabled:C.value,title:G(y)("modal.resetButtonTitle"),onClick:P},[...T[10]||(T[10]=[S("span",{class:"vpi-delete local-search-icon"},null,-1)])],8,Ps)])],32),S("ul",{ref_key:"resultsEl",ref:i,id:(N=m.value)!=null&&N.length?"localsearch-list":void 0,role:(X=m.value)!=null&&X.length?"listbox":void 0,"aria-labelledby":(ne=m.value)!=null&&ne.length?"localsearch-label":void 0,class:"results",onMousemove:z},[(L(!0),R(ge,null,ve(m.value,(Z,ae)=>(L(),R("li",{key:Z.id,id:"localsearch-item-"+ae,"aria-selected":j.value===ae?"true":"false",role:"option"},[S("a",{href:Z.id,class:le(["result",{selected:j.value===ae}]),"aria-label":[...Z.titles,Z.title].join(" > "),onMouseenter:de=>!ee.value&&(j.value=ae),onFocusin:de=>j.value=ae,onClick:T[6]||(T[6]=de=>I.$emit("close")),"data-index":ae},[S("div",null,[S("div",$s,[T[12]||(T[12]=S("span",{class:"title-icon"},"#",-1)),(L(!0),R(ge,null,ve(Z.titles,(de,pe)=>(L(),R("span",{key:pe,class:"title"},[S("span",{class:"text",innerHTML:de},null,8,Vs),T[11]||(T[11]=S("span",{class:"vpi-chevron-right local-search-icon"},null,-1))]))),128)),S("span",Hs,[S("span",{class:"text",innerHTML:Z.title},null,8,qs)])]),G(u)?(L(),R("div",Bs,[Z.text?(L(),R("div",js,[S("div",{class:"vp-doc",innerHTML:Z.text},null,8,Us)])):Y("",!0),T[13]||(T[13]=S("div",{class:"excerpt-gradient-bottom"},null,-1)),T[14]||(T[14]=S("div",{class:"excerpt-gradient-top"},null,-1))])):Y("",!0)])],42,zs)],8,Ds))),128)),G(d)&&!m.value.length&&p.value?(L(),R("li",Ks,[Ye(re(G(y)("modal.noResultsText"))+' "',1),S("strong",null,re(G(d)),1),T[15]||(T[15]=Ye('" ',-1))])):Y("",!0)],40,Ns),S("div",Ws,[S("span",null,[S("kbd",{"aria-label":G(y)("modal.footer.navigateUpKeyAriaLabel")},[...T[16]||(T[16]=[S("span",{class:"vpi-arrow-up navigate-icon"},null,-1)])],8,Gs),S("kbd",{"aria-label":G(y)("modal.footer.navigateDownKeyAriaLabel")},[...T[17]||(T[17]=[S("span",{class:"vpi-arrow-down navigate-icon"},null,-1)])],8,Js),Ye(" "+re(G(y)("modal.footer.navigateText")),1)]),S("span",null,[S("kbd",{"aria-label":G(y)("modal.footer.selectKeyAriaLabel")},[...T[18]||(T[18]=[S("span",{class:"vpi-corner-down-left navigate-icon"},null,-1)])],8,Ys),Ye(" "+re(G(y)("modal.footer.selectText")),1)]),S("span",null,[S("kbd",{"aria-label":G(y)("modal.footer.closeKeyAriaLabel")},"esc",8,Qs),Ye(" "+re(G(y)("modal.footer.closeText")),1)])])])],8,Ts)])}}}),Xs=Wi(Zs,[["__scopeId","data-v-42e65fb9"]]),dt=Ze(null);function el(t){dt.value=t}function tl(t){dt.value===t&&(dt.value=null)}function rl(){var t;(t=dt.value)==null||t.call(dt)}const nl=ke({__name:"DocsSearchProvider",setup(t){const e=ce(!1);function r(){e.value=!0}function n(){e.value=!1}function i(o){const s=o.target,l=s.tagName;return s.isContentEditable||l==="INPUT"||l==="SELECT"||l==="TEXTAREA"}function a(o){(o.key.toLowerCase()==="k"&&(o.metaKey||o.ctrlKey)||!i(o)&&o.key==="/")&&(o.preventDefault(),r())}return Ke(()=>{el(r),window.addEventListener("keydown",a)}),uo(()=>{tl(r),window.removeEventListener("keydown",a)}),(o,s)=>e.value?(L(),we(Xs,{key:0,onClose:n})):Y("",!0)}}),ol={class:"relative"},il={class:"min-w-0 flex-1 break-words"},al=["href"],sl={class:"flex min-w-0 flex-1 items-start gap-x-2.5"},ll={class:"flex min-w-0 flex-1 flex-wrap items-center gap-1.5 [word-break:break-word]"},cl={class:"min-w-0 max-w-full break-words"},ul=ke({__name:"DocsSidebarNode",props:{item:{},depth:{default:0}},emits:["navigate"],setup(t,{emit:e}){const r=t,n=e,{page:i}=We(),a=Ur(),o=B(()=>{var u;return!!((u=r.item.items)!=null&&u.length)}),s=B(()=>nr(i.value.relativePath,r.item.link)),l=B(()=>{var u;return((u=r.item.items)==null?void 0:u.some(g=>St(i.value.relativePath,g)))??!1}),c=ce(o.value?!r.item.collapsed||l.value:!1);xe(l,u=>{u&&(c.value=!0)});function h(u){return u?De(u):"#"}async function f(u,g){g&&(u.preventDefault(),await a.go(h(g)),n("navigate"))}function d(){o.value&&(c.value=!c.value)}return(u,g)=>{const x=fo("DocsSidebarNode",!0);return L(),R("li",ol,[o.value?(L(),R("button",{key:0,type:"button",class:le(["group flex w-full cursor-pointer items-center py-0.5 pr-2 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",l.value?"text-docs-primary":"text-slate-700"]),onClick:d},[S("span",il,re(t.item.text),1),(L(),R("svg",{viewBox:"0 0 640 640",class:le(["size-3 shrink-0",c.value?"rotate-90":"rotate-0"]),"aria-hidden":"true"},[...g[2]||(g[2]=[S("path",{d:"M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"},null,-1)])],2))],2)):(L(),R("a",{key:1,href:h(t.item.link),class:le(["group flex w-full cursor-pointer items-center py-0.5 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",s.value?"text-docs-primary":"text-slate-700"]),onClick:g[0]||(g[0]=m=>f(m,t.item.link))},[S("div",sl,[t.item.icon?(L(),we(or,{key:0,name:t.item.icon,class:"mt-1 size-4 shrink-0 text-slate-500 group-hover:text-slate-700"},null,8,["name"])):Y("",!0),S("div",ll,[S("span",cl,re(t.item.text),1)])])],10,al)),o.value&&c.value?(L(),R("ul",{key:2,style:Ct({marginLeft:t.depth===0?"1rem":"1.25rem"})},[(L(!0),R(ge,null,ve(t.item.items,m=>(L(),we(x,{key:m.link??`${m.text}-${m.icon??""}`,item:m,depth:t.depth+1,onNavigate:g[1]||(g[1]=p=>n("navigate"))},null,8,["item","depth"]))),128))],4)):Y("",!0)])}}}),dl={"aria-label":"Sidebar navigation",class:"text-sm"},fl={key:0,class:"mb-3 flex items-center gap-2.5 text-sm font-medium text-slate-900 lg:mb-2"},ml={class:"space-y-px"},hl=ke({__name:"DocsSidebar",emits:["navigate"],setup(t){const{sidebarGroups:e}=Kr(),r=B(()=>e.value.filter(n=>{var i;return(i=n.items)==null?void 0:i.length}));return(n,i)=>(L(),R("nav",dl,[(L(!0),R(ge,null,ve(r.value,a=>{var o,s;return L(),R("section",{key:a.text??((s=(o=a.items)==null?void 0:o[0])==null?void 0:s.link),class:"mt-6 first:mt-0 lg:mt-6 lg:first:mt-0"},[a.text?(L(),R("h2",fl,[a.icon?(L(),we(or,{key:0,name:a.icon,class:"size-4 text-slate-600"},null,8,["name"])):Y("",!0),Ye(" "+re(a.text),1)])):Y("",!0),S("ul",ml,[(L(!0),R(ge,null,ve(a.items,l=>(L(),we(ul,{key:l.link??`${l.text}-${l.icon??""}`,item:l,onNavigate:i[0]||(i[0]=c=>n.$emit("navigate"))},null,8,["item"]))),128))])])}),128))]))}}),pl={key:0,class:"min-h-screen bg-slate-50 text-slate-900 lg:h-screen lg:overflow-hidden"},gl={class:"max-lg:contents lg:flex-1 lg:min-w-0 lg:overflow-x-clip"},vl={id:"navbar",class:"peer fixed top-0 z-30 w-full"},bl={class:"relative z-10 mx-auto max-w-[96rem] px-4"},yl={class:"relative"},wl={class:"lg:hidden"},El={class:"flex h-14 items-center justify-between gap-3"},xl={href:"/",class:"flex min-w-0 items-center gap-3 select-none"},kl=["src"],_l={key:1,class:"min-w-0 truncate text-[15px] font-semibold tracking-[-0.01em] text-slate-900"},Sl={class:"flex items-center gap-1.5"},Al=["href","aria-label"],Tl={key:0,viewBox:"0 0 24 24",fill:"currentColor",class:"h-[18px] w-[18px]","aria-hidden":"true"},Cl=["aria-expanded"],Il={class:"min-w-0 truncate"},Ll=["aria-label"],Ml=["href","aria-selected"],Rl=["aria-expanded"],Fl={class:"ml-4 flex min-w-0 items-center space-x-3 overflow-hidden text-sm leading-6 whitespace-nowrap"},Ol={key:0,class:"flex shrink-0 items-center space-x-3 text-slate-500"},Pl={class:"min-w-0 flex-1 truncate font-semibold text-slate-900"},Nl={class:"relative hidden h-14 min-w-0 flex-1 items-center gap-x-4 lg:flex lg:border-none"},Dl={class:"flex min-w-0 flex-1 items-center gap-x-4"},zl={href:"/",class:"flex min-w-0 items-center gap-3 select-none"},$l=["src"],Vl={key:1,class:"min-w-0 truncate text-[15px] font-semibold tracking-[-0.01em] text-slate-900"},Hl=["aria-expanded"],ql={class:"truncate"},Bl=["aria-label"],jl=["href","aria-selected"],Ul={class:"flex items-center gap-4"},Kl={class:"flex items-center gap-2"},Wl=["href","aria-label"],Gl={key:0,viewBox:"0 0 24 24",fill:"currentColor",class:"h-[18px] w-[18px]","aria-hidden":"true"},Jl={class:"scroll-mt-[var(--scroll-mt)] fixed top-[7rem] w-full pb-2 pt-0 lg:top-[3.5rem]"},Yl={key:0,id:"sidebar-content",class:"hidden min-h-0 lg:flex lg:flex-col"},Ql={class:"flex h-full min-h-0 flex-col gap-4 text-sm"},Zl={class:"relative z-20 hidden items-center gap-2.5 mr-4 mt-2 mb-2 lg:flex"},Xl={class:"min-w-0 h-full min-h-0"},ec={class:"mx-auto w-full max-w-[88rem] xl:grid xl:grid-cols-[minmax(0,52rem)_16.5rem] xl:justify-center xl:gap-x-12"},tc={id:"content-area",class:"w-full min-w-0 overflow-x-visible"},rc={key:0,class:"eyebrow mb-2.5 h-5 text-sm font-semibold text-docs-primary"},nc={key:1,class:"mt-12 border-t border-slate-200 pt-6"},oc={key:0,class:"flex flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between"},ic=["href"],ac={key:1},sc={key:1,class:"mt-6 grid gap-3 sm:grid-cols-2"},lc=["href"],cc={class:"mt-1 text-sm font-medium text-slate-900 group-hover:text-docs-primary-strong"},uc={key:1,class:"hidden sm:block"},dc=["href"],fc={class:"mt-1 text-sm font-medium text-slate-900 group-hover:text-docs-primary-strong"},mc={key:0,id:"content-side-layout",class:"hidden xl:block"},hc={class:"sticky top-0 pt-1"},pc={id:"table-of-contents-shell",class:"max-h-[calc(100dvh-7rem)] w-[16.5rem] overflow-y-auto space-y-2 pb-4 text-sm leading-6 text-slate-600"},gc=ke({__name:"Layout",setup(t){const e=ho(),{frontmatter:r,page:n,site:i,theme:a}=We(),{close:o,hasSidebar:s,isOpen:l,toggle:c,sidebarGroups:h}=Kr(),f=ce(null),d=ce(null),u=ce(!1),g=ce(null),x=ce(null),m=ce(!1),p=ce(!1);ea(l,o),xe(()=>e.path,o),xe(()=>e.path,async(E,_)=>{await Ge(E,_)});const v=B(()=>a.value.logo?typeof a.value.logo=="string"?De(a.value.logo):De(a.value.logo.src):null),w=B(()=>r.value.layout===!1||r.value.layout==="home"||r.value.aside===!1?!1:(r.value.outline??a.value.outline)!==!1),b=B(()=>typeof a.value.siteTitle=="string"&&a.value.siteTitle.trim()?a.value.siteTitle:i.value.title),k=B(()=>a.value.socialLinks??[]),C=B(()=>h.value.find(E=>{var _;return(_=E.items)==null?void 0:_.some(D=>St(n.value.relativePath,D))})??null),O=B(()=>r.value.title??n.value.title??b.value),$=B(()=>{const E=[Z.value,O.value].filter(_=>!!(_!=null&&_.trim()));return E.filter((_,D)=>_!==E[D-1])}),j=B(()=>$.value.length>1?$.value[0]:null),ee=B(()=>$.value.at(-1)??b.value),F=B(()=>{var _;const E=a.value;return!!((_=E.search)!=null&&_.provider||E.algolia)}),Q=B(()=>a.value.docsTheme??{}),H=B(()=>a.value.editLink),y=B(()=>a.value.lastUpdatedText??"Last updated");function A(E){var D;const _=[];for(const V of E)V.link&&_.push(V),(D=V.items)!=null&&D.length&&_.push(...A(V.items));return _}function P(E,_){var D;for(const V of E){if(V.link&&St(_,V))return[V];if(!((D=V.items)!=null&&D.length))continue;const W=P(V.items,_);if(W)return[V,...W]}return null}function U(E){var D;const _=[];for(const V of E)V.text&&V.link&&_.push({text:V.text,link:V.link,activeMatch:V.activeMatch}),(D=V.items)!=null&&D.length&&_.push(...U(V.items));return _}function z(E){const _=decodeURI(E).split(/[?#]/,1)[0]||"/";if(_==="/")return"/";const V=_.replace(/\/index(?:\.html)?$/,"/").replace(/\.html$/,"");return V==="/"?"/":V.replace(/\/+$/,"")}const J=B(()=>z(e.path)),q=B(()=>{const E=Array.isArray(a.value.nav)?a.value.nav:[];return U(E)});function I(E){if(E.activeMatch)return new RegExp(E.activeMatch).test(J.value);const _=z(E.link);return _==="/"?J.value==="/":J.value===_||J.value.startsWith(`${_}/`)}const T=B(()=>q.value.find(E=>I(E))??null),K=B(()=>{var E,_;return((E=T.value)==null?void 0:E.text)??((_=q.value[0])==null?void 0:_.text)??"Documentation"});function te(){m.value=!1,p.value=!1}function N(){m.value=!m.value,p.value=!1}function X(){p.value=!p.value,m.value=!1}function ne(E){const _=E.target;m.value&&g.value&&!g.value.contains(_)&&(m.value=!1),p.value&&x.value&&!x.value.contains(_)&&(p.value=!1)}xe(()=>e.path,te);const Z=B(()=>{var D,V;const E=C.value;if(!((D=E==null?void 0:E.items)!=null&&D.length))return(E==null?void 0:E.text)??null;const _=P(E.items,n.value.relativePath);return!(_!=null&&_.length)||_.length===1?E.text??null:((V=_.at(-2))==null?void 0:V.text)??E.text??null}),ae=B(()=>{var E,_;return(_=(E=C.value)==null?void 0:E.items)!=null&&_.length?A(C.value.items):[]}),de=B(()=>ae.value.findIndex(E=>nr(n.value.relativePath,E.link))),pe=B(()=>{const E=de.value;return E>0?ae.value[E-1]:null}),_e=B(()=>{const E=de.value;return E>=0&&E<ae.value.length-1?ae.value[E+1]:null}),He=B(()=>{var D,V;if(r.value.editLink===!1)return null;const E=(D=H.value)==null?void 0:D.pattern,_=n.value.filePath;return!E||!_?null:{text:((V=H.value)==null?void 0:V.text)??"Edit this page",href:E.replace(":path",_)}}),me=B(()=>{if(r.value.lastUpdated===!1)return null;const E=n.value.lastUpdated;return E?new Intl.DateTimeFormat(i.value.lang||void 0,{dateStyle:"medium",timeStyle:"short"}).format(E):null}),Fe=B(()=>{var _,D,V,W,Ce,be;const E=((_=Q.value.primary)==null?void 0:_.trim())||"#0f766e";return{"--docs-primary":E,"--docs-primary-strong":((D=Q.value.primaryStrong)==null?void 0:D.trim())||`color-mix(in oklab, ${E} 82%, black)`,"--docs-primary-soft":((V=Q.value.primarySoft)==null?void 0:V.trim())||`color-mix(in oklab, ${E} 12%, white)`,"--docs-primary-soft-hover":((W=Q.value.primarySoftHover)==null?void 0:W.trim())||`color-mix(in oklab, ${E} 16%, white)`,"--docs-primary-border":((Ce=Q.value.primaryBorder)==null?void 0:Ce.trim())||`color-mix(in oklab, ${E} 18%, white)`,"--docs-primary-border-strong":((be=Q.value.primaryBorderStrong)==null?void 0:be.trim())||`color-mix(in oklab, ${E} 28%, white)`}});function Se(){const E=d.value;if(!E){u.value=!1;return}u.value=E.scrollTop>4}function Ae(E){return E.split("#")[0]??E}function Te(E){const _=E.indexOf("#");return _>=0?decodeURIComponent(E.slice(_+1)):""}function Me(E){const _=Te(E);return _||(typeof window<"u"?decodeURIComponent(window.location.hash.replace(/^#/,"")):"")}function gt(){return f.value??document.getElementById("docs-scroll-container")??document.getElementById("content-container")}function vn(){var E;(E=gt())==null||E.scrollTo({top:0,left:0,behavior:"auto"}),window.scrollTo({top:0,left:0,behavior:"auto"})}function Oe(E){if(!E)return!1;const _=document.getElementById(E),D=gt();if(!(_ instanceof HTMLElement))return!1;if(!(D instanceof HTMLElement))return _.scrollIntoView({block:"start"}),!0;const V=D.scrollTop+_.getBoundingClientRect().top-D.getBoundingClientRect().top-24;return D.scrollTo({top:Math.max(0,V),left:0,behavior:"auto"}),!0}async function Ge(E,_){await $e(),Se(),Ae(E)!==Ae(_??"")&&vn();const D=Me(E);D&&(await $e(),Oe(D))}function rt(E,_,D){const V=(D==null?void 0:D.size)??18,W=(D==null?void 0:D.strokeWidth)??1.5,Ce=(D==null?void 0:D.viewBox)??`0 0 ${V} ${V}`,be=document.createElementNS("http://www.w3.org/2000/svg","svg");be.setAttribute("width",String(V)),be.setAttribute("height",String(V)),be.setAttribute("viewBox",Ce),be.setAttribute("fill","none"),be.setAttribute("aria-hidden","true"),be.setAttribute("class",_);for(const cr of E.split("||")){const Ee=document.createElementNS("http://www.w3.org/2000/svg","path");Ee.setAttribute("d",cr),D!=null&&D.fill?Ee.setAttribute("fill","currentColor"):(Ee.setAttribute("stroke","currentColor"),Ee.setAttribute("stroke-width",String(W)),Ee.setAttribute("stroke-linecap","round"),Ee.setAttribute("stroke-linejoin","round")),be.append(Ee)}return be}async function vt(E){var D;try{if((D=navigator.clipboard)!=null&&D.writeText)return await navigator.clipboard.writeText(E),!0}catch{}const _=document.createElement("textarea");_.value=E,_.setAttribute("readonly",""),_.style.position="fixed",_.style.opacity="0",_.style.pointerEvents="none",document.body.append(_),_.select(),_.setSelectionRange(0,E.length);try{return document.execCommand("copy")}finally{_.remove()}}function It(E){const _=Array.from(E.querySelectorAll("pre code .line"));if(_.length)return _.map(V=>V.textContent??"").join(`
`).replace(/\n$/,"");const D=E.querySelector("pre code");return((D==null?void 0:D.textContent)??"").replace(/\n$/,"")}function Si(E){const _=new URL(window.location.href);return _.hash=E,_}function Ai(){const E=window.getSelection();return!!(E&&E.type==="Range"&&E.toString().trim())}function bn(){document.querySelectorAll(".vp-doc h2[id], .vp-doc h3[id], .vp-doc h4[id], .vp-doc h5[id], .vp-doc h6[id]").forEach(E=>{if(E.dataset.docsHeadingCopyBound==="true")return;const _=E.querySelector(".header-anchor");if(!_)return;E.dataset.docsHeadingCopyBound="true",E.classList.add("docs-copyable-heading");const D=document.createElement("span");for(D.className="anchor-heading__content";E.childNodes.length>0;){const W=E.firstChild;if(W===_)break;D.appendChild(W)}const V=document.createElement("div");V.className="anchor-heading__icon-wrap",V.tabIndex=-1,V.appendChild(_),E.prepend(V),E.appendChild(D),_.replaceChildren(rt("M0 256C0 167.6 71.6 96 160 96h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H160C98.1 144 48 194.1 48 256s50.1 112 112 112h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H160C71.6 416 0 344.4 0 256zm576 0c0 88.4-71.6 160-160 160H344c-13.3 0-24-10.7-24-24s10.7-24 24-24h72c61.9 0 112-50.1 112-112s-50.1-112-112-112H344c-13.3 0-24-10.7-24-24s10.7-24 24-24h72c88.4 0 160 71.6 160 160zM184 232H392c13.3 0 24 10.7 24 24s-10.7 24-24 24H184c-13.3 0-24-10.7-24-24s10.7-24 24-24z","docs-heading-anchor__icon",{fill:!0,size:12,viewBox:"0 0 576 512"})),E.addEventListener("click",W=>{if(Ai()||W.target instanceof HTMLElement&&W.target.closest("a:not(.header-anchor)"))return;const Ce=W.target instanceof HTMLElement?W.target:null,be=Ce==null?void 0:Ce.closest(".anchor-heading__content"),cr=Ce==null?void 0:Ce.closest(".header-anchor");if(!be&&!cr)return;W.preventDefault();const Ee=Si(E.id);window.history.replaceState(null,"",`${Ee.pathname}${Ee.search}${Ee.hash}`),Oe(E.id),vt(Ee.toString())})})}function yn(){document.querySelectorAll('.vp-doc [class*="language-"] > button.copy').forEach(E=>{if(E.querySelector(".docs-copy-button__icon")){if(E.dataset.docsCopyBound==="true")return}else{const _=rt("M14.25 5.25H7.25C6.14543 5.25 5.25 6.14543 5.25 7.25V14.25C5.25 15.3546 6.14543 16.25 7.25 16.25H14.25C15.3546 16.25 16.25 15.3546 16.25 14.25V7.25C16.25 6.14543 15.3546 5.25 14.25 5.25Z||M2.80103 11.998L1.77203 5.07397C1.61003 3.98097 2.36403 2.96397 3.45603 2.80197L10.38 1.77297C11.313 1.63397 12.19 2.16297 12.528 3.00097","docs-copy-button__icon"),D=rt("M2.75 9.25L6.75 13.25L15.25 4.75","docs-copy-button__icon docs-copy-button__icon--copied",{size:18,strokeWidth:2,viewBox:"0 0 18 18"}),V=document.createElement("span");V.className="sr-only",V.textContent=E.title||"Copy code",E.replaceChildren(_,D,V)}E.dataset.docsCopyBound="true",E.type="button",E.addEventListener("click",async _=>{_.preventDefault(),_.stopPropagation();const D=E.closest('[class*="language-"]');if(!(D instanceof HTMLElement))return;const V=It(D);!V||!await vt(V)||(E.classList.add("copied"),window.setTimeout(()=>{E.classList.remove("copied")},1500))})})}function wn(){rl()}return Ke(async()=>{var E;document.addEventListener("pointerdown",ne),await Ge(e.path),yn(),bn(),(E=d.value)==null||E.addEventListener("scroll",Se,{passive:!0})}),mo(async()=>{await $e(),yn(),bn(),Oe(Me(e.path))}),rr(()=>{var E;document.removeEventListener("pointerdown",ne),(E=d.value)==null||E.removeEventListener("scroll",Se)}),(E,_)=>{var D,V;return G(r).layout!==!1?(L(),R("div",pl,[F.value?(L(),we(nl,{key:0})):Y("",!0),S("div",{class:"max-lg:contents lg:flex lg:w-full","data-docs-theme":"almond",style:Ct(Fe.value)},[S("div",gl,[S("header",vl,[S("div",bl,[S("div",yl,[S("div",{class:le(["transition-opacity duration-200",G(l)?"max-lg:pointer-events-none max-lg:opacity-0":""])},[S("div",wl,[S("div",El,[S("a",xl,[v.value?(L(),R("img",{key:0,src:v.value,alt:"",class:"relative block h-6 w-auto max-w-[156px] shrink-0 object-contain"},null,8,kl)):Y("",!0),v.value?Y("",!0):(L(),R("div",_l,re(b.value),1))]),S("div",Sl,[F.value?(L(),R("button",{key:0,type:"button",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":"Open search",onClick:wn},[..._[3]||(_[3]=[S("svg",{viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round",class:"h-[18px] w-[18px]","aria-hidden":"true"},[S("circle",{cx:"11",cy:"11",r:"8"}),S("path",{d:"m21 21-4.3-4.3"})],-1)])])):Y("",!0),(L(!0),R(ge,null,ve(k.value,W=>(L(),R("a",{key:`mobile-${W.link}`,href:W.link,target:"_blank",rel:"noreferrer",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":W.icon},[W.icon==="github"?(L(),R("svg",Tl,[..._[4]||(_[4]=[S("path",{d:"M12 .5a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58l-.02-2.04c-3.34.73-4.04-1.42-4.04-1.42-.54-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.9 0-1.3.46-2.36 1.23-3.19-.12-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.22a11.5 11.5 0 0 1 6 0c2.3-1.54 3.3-1.22 3.3-1.22.65 1.65.24 2.87.12 3.17.76.83 1.22 1.89 1.22 3.19 0 4.58-2.81 5.59-5.49 5.89.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.22.7.83.58A12 12 0 0 0 12 .5Z"},null,-1)])])):Y("",!0)],8,Al))),128))])]),q.value.length?(L(),R("div",{key:0,ref_key:"topNavRootMobile",ref:x,class:"relative border-t border-slate-100 px-1 py-2 lg:hidden"},[S("button",{type:"button",class:"flex w-full items-center justify-between gap-2 rounded-lg border border-slate-200/80 bg-white/80 px-3 py-2 text-left text-sm font-medium text-slate-800","aria-expanded":p.value,"aria-haspopup":"listbox","aria-label":"Documentation section",onClick:Tr(X,["stop"])},[S("span",Il,re(K.value),1),(L(),R("svg",{class:le(["h-4 w-4 shrink-0 text-slate-500 transition-transform",p.value?"rotate-180":""]),viewBox:"0 0 20 20",fill:"none","aria-hidden":"true"},[..._[5]||(_[5]=[S("path",{d:"M5 7.5L10 12.5L15 7.5",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round","stroke-linejoin":"round"},null,-1)])],2))],8,Cl),p.value?(L(),R("div",{key:0,class:"absolute left-1 right-1 top-full z-[100] mt-1 max-h-[min(60vh,24rem)] overflow-y-auto rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg",role:"listbox","aria-label":`${K.value} options`},[(L(!0),R(ge,null,ve(q.value,W=>(L(),R("a",{key:W.link,href:G(De)(W.link),role:"option",class:le(["block truncate px-3 py-2 text-sm transition",I(W)?"bg-docs-primary-soft font-medium text-docs-primary-strong":"text-slate-700 hover:bg-slate-50"]),"aria-selected":I(W),onClick:te},re(W.text),11,Ml))),128))],8,Ll)):Y("",!0)],512)):Y("",!0),G(s)?(L(),R("button",{key:1,type:"button",class:"flex h-14 w-full items-center px-1 text-left cursor-pointer focus:outline-0","aria-label":"Open navigation menu","aria-expanded":G(l),onClick:_[0]||(_[0]=(...W)=>G(c)&&G(c)(...W))},[_[7]||(_[7]=S("div",{class:"text-slate-500 transition hover:text-slate-600"},[S("span",{class:"sr-only"},"Navigation"),S("svg",{class:"h-4",fill:"currentColor",viewBox:"0 0 448 512","aria-hidden":"true"},[S("path",{d:"M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"})])],-1)),S("div",Fl,[j.value?(L(),R("div",Ol,[S("span",null,re(j.value),1),_[6]||(_[6]=S("svg",{width:"3",height:"24",viewBox:"0 -9 3 24",class:"h-5 overflow-visible text-slate-400","aria-hidden":"true"},[S("path",{d:"M0 0L3 3L0 6",fill:"none",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round"})],-1))])):Y("",!0),S("div",Pl,re(ee.value),1)])],8,Rl)):Y("",!0)]),S("div",Nl,[S("div",Dl,[S("a",zl,[v.value?(L(),R("img",{key:0,src:v.value,alt:"",class:"relative block h-6 w-auto max-w-[156px] shrink-0 object-contain"},null,8,$l)):Y("",!0),v.value?Y("",!0):(L(),R("div",Vl,re(b.value),1))]),q.value.length?(L(),R("div",{key:0,ref_key:"topNavRootDesktop",ref:g,class:"relative hidden min-w-0 shrink lg:block"},[S("button",{type:"button",class:"inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200/80 bg-white/70 px-3 py-1.5 text-sm font-medium text-slate-800 backdrop-blur transition hover:bg-slate-100/90","aria-expanded":m.value,"aria-haspopup":"listbox","aria-label":"Documentation section",onClick:Tr(N,["stop"])},[S("span",ql,re(K.value),1),(L(),R("svg",{class:le(["h-4 w-4 shrink-0 text-slate-500 transition-transform",m.value?"rotate-180":""]),viewBox:"0 0 20 20",fill:"none","aria-hidden":"true"},[..._[8]||(_[8]=[S("path",{d:"M5 7.5L10 12.5L15 7.5",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round","stroke-linejoin":"round"},null,-1)])],2))],8,Hl),m.value?(L(),R("div",{key:0,class:"absolute left-0 top-full z-[100] mt-1 min-w-[12rem] max-w-[min(100vw-2rem,22rem)] rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg",role:"listbox","aria-label":`${K.value} options`},[(L(!0),R(ge,null,ve(q.value,W=>(L(),R("a",{key:W.link,href:G(De)(W.link),role:"option",class:le(["block truncate px-3 py-2 text-sm transition",I(W)?"bg-docs-primary-soft font-medium text-docs-primary-strong":"text-slate-700 hover:bg-slate-50"]),"aria-selected":I(W),onClick:te},re(W.text),11,jl))),128))],8,Bl)):Y("",!0)],512)):Y("",!0)]),S("div",Ul,[S("div",Kl,[(L(!0),R(ge,null,ve(k.value,W=>(L(),R("a",{key:W.link,href:W.link,target:"_blank",rel:"noreferrer",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":W.icon},[W.icon==="github"?(L(),R("svg",Gl,[..._[9]||(_[9]=[S("path",{d:"M12 .5a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58l-.02-2.04c-3.34.73-4.04-1.42-4.04-1.42-.54-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.9 0-1.3.46-2.36 1.23-3.19-.12-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.22a11.5 11.5 0 0 1 6 0c2.3-1.54 3.3-1.22 3.3-1.22.65 1.65.24 2.87.12 3.17.76.83 1.22 1.89 1.22 3.19 0 4.58-2.81 5.59-5.49 5.89.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.22.7.83.58A12 12 0 0 0 12 .5Z"},null,-1)])])):Y("",!0)],8,Wl))),128))])])])],2)])])]),S("div",Jl,[G(s)?(L(),R("div",{key:0,class:le(["fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-md transition-opacity duration-300 lg:hidden",G(l)?"pointer-events-auto opacity-100":"pointer-events-none opacity-0"]),onClick:_[1]||(_[1]=(...W)=>G(o)&&G(o)(...W))},null,2)):Y("",!0),G(s)?(L(),R("button",{key:1,type:"button",class:le(["fixed right-4 top-5 z-[60] inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/70 bg-white/95 text-slate-500 shadow-[0_8px_24px_rgba(15,23,42,0.16)] backdrop-blur transition duration-300 lg:hidden",G(l)?"pointer-events-auto opacity-100 scale-100":"pointer-events-none opacity-0 scale-95"]),"aria-label":"Close navigation menu",onClick:_[2]||(_[2]=(...W)=>G(o)&&G(o)(...W))},[..._[10]||(_[10]=[S("svg",{viewBox:"0 0 20 20",fill:"none",class:"h-5 w-5","aria-hidden":"true"},[S("path",{d:"M5 5L15 15M15 5L5 15",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round"})],-1)])],2)):Y("",!0),G(s)?(L(),R("aside",{key:2,class:le(["fixed inset-y-0 left-0 z-50 w-[min(22rem,calc(100vw-2.5rem))] max-w-full overflow-y-auto overscroll-contain border-r border-slate-200/80 bg-white shadow-[0_28px_90px_rgba(15,23,42,0.22)] transition-transform duration-300 lg:hidden",G(l)?"translate-x-0":"-translate-x-[105%]"])},[wt(ba,{"logo-src":v.value,"site-title":b.value,onNavigate:G(o)},null,8,["logo-src","site-title","onNavigate"])],2)):Y("",!0),S("div",{class:le(["mx-auto grid h-[calc(100dvh-8rem)] min-h-0 w-full max-w-[96rem] rounded-2xl px-2 lg:h-[calc(100dvh-4rem)] lg:grid-cols-[16.5rem_minmax(0,1fr)] lg:gap-x-2 lg:px-4",G(s)?"":"lg:grid-cols-[minmax(0,1fr)]"])},[G(s)?(L(),R("div",Yl,[S("div",Ql,[S("div",Zl,[F.value?(L(),R("button",{key:0,type:"button",class:"group/search flex h-9 w-full items-center justify-between gap-2 rounded-lg bg-white pl-3.5 pr-3 text-left text-sm leading-6 text-gray-500 ring-1 ring-gray-400/30 transition-[color,box-shadow] hover:text-gray-800 hover:ring-gray-600/30","aria-label":"Open search",onClick:wn},[..._[11]||(_[11]=[Gi('<div class="flex min-w-0 items-center gap-2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 min-w-4 flex-none text-gray-700" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg><div class="min-w-0 truncate">Search...</div></div><span class="flex-none text-xs">⌘K</span>',2)])])):Y("",!0)]),S("div",{id:"navigation-items",ref_key:"navigationItems",ref:d,class:le(["stable-scrollbar-gutter pb-4 min-h-0 flex-1 overflow-y-auto",u.value?"[mask-image:linear-gradient(transparent,black_32px)] [-webkit-mask-image:linear-gradient(transparent,black_32px)]":""])},[wt(hl)],2)])])):Y("",!0),S("div",Xl,[S("div",{id:"docs-scroll-container",ref_key:"docsScrollContainer",ref:f,class:"stable-scrollbar-gutter h-full overflow-y-auto rounded-xl border border-gray-400/30 bg-white px-8 pt-8 pb-10 lg:px-10 lg:pt-10"},[S("div",ec,[S("main",tc,[G(n).isNotFound?(L(),we(_a,{key:0})):(L(),R(ge,{key:1},[Z.value?(L(),R("div",rc,re(Z.value),1)):Y("",!0),wt(G(_n),{class:"vp-doc mdx-content relative prose prose-gray [contain:inline-size] isolate"}),He.value||me.value||pe.value||_e.value?(L(),R("div",nc,[He.value||me.value?(L(),R("div",oc,[He.value?(L(),R("a",{key:0,href:He.value.href,target:"_blank",rel:"noreferrer",class:"font-medium text-docs-primary transition hover:text-docs-primary-strong"},re(He.value.text),9,ic)):Y("",!0),me.value?(L(),R("div",ac,re(y.value)+": "+re(me.value),1)):Y("",!0)])):Y("",!0),pe.value||_e.value?(L(),R("div",sc,[(D=pe.value)!=null&&D.link?(L(),R("a",{key:0,href:G(De)(pe.value.link),class:"group rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft/50"},[_[12]||(_[12]=S("div",{class:"text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"},"Previous",-1)),S("div",cc,re(pe.value.text),1)],8,lc)):(L(),R("div",uc)),(V=_e.value)!=null&&V.link?(L(),R("a",{key:2,href:G(De)(_e.value.link),class:"group rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft/50 sm:text-right"},[_[13]||(_[13]=S("div",{class:"text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"},"Next",-1)),S("div",fc,re(_e.value.text),1)],8,dc)):Y("",!0)])):Y("",!0)])):Y("",!0)],64))]),w.value?(L(),R("aside",mc,[S("div",hc,[S("div",pc,[wt(Ma)])])])):Y("",!0)])],512)])],2)])])],4)])):(L(),we(G(_n),{key:1}))}}});function vc(t={}){return{Layout:gc,async enhanceApp(e){var r;await((r=t.enhanceApp)==null?void 0:r.call(t,e))}}}const bc=`@layer formie-base, formie-theme-base, formie-theme;

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
}`,yc=`@layer formie-theme-base {
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
`,wc=`@layer formie-theme-base {
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
}`,Ec=`@layer formie-theme {
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
}`,xc=`@layer formie-theme {
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

}`,kc=`@layer formie-theme {
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
`,_c=`@layer formie-theme {
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

}`,Sc=`@layer formie-theme {
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
}`,Ac=`@layer formie-theme {
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
`,Tc=`@layer formie-theme {
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
`,Cc=`@layer formie-theme {
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
}`,Ic=`@layer formie-theme {
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
`,Lc=`@layer formie-theme {

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
}`,Mc=`@layer formie-theme {
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
`,Rc=`@layer formie-theme {
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
}`,Fc=`@layer formie-theme {

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
}`,Oc=`@layer formie-theme {
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
}`,Pc=`@layer formie-theme {
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
}`,Nc=`@layer formie-theme {
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
}`,Dc=`@layer formie-theme {
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
}`,zc=`@layer formie-theme {
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
}`,$c=`@layer formie-theme {
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
}`,Vc=`@layer formie-theme {

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

}`,Hc=`@layer formie-theme {
    .formie-limit-number {
        font-weight: var(--formie-font-weight-semibold);
        color: var(--formie-color-text);
    }

    .formie-limit-number-error {
        color: var(--formie-color-danger);
    }
}`,qc=`@layer formie-theme {
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
`,Bc=`.preview-gallery-page {
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
`,jc=[{legacyEvent:"onFormieLoaded",canonicalEvent:"formie:mount:after",disposition:"approximate",target:"document"},{legacyEvent:"onFormieInit",canonicalEvent:"formie:mount:after",disposition:"approximate",target:"document"},{legacyEvent:"onFormieReady",canonicalEvent:"formie:mount:after",disposition:"safe"},{legacyEvent:"onAfterFormieSubmit",canonicalEvent:"formie:submit:result",disposition:"safe"},{legacyEvent:"onFormieSubmitError",canonicalEvent:"formie:submit:result",disposition:"safe"},{legacyEvent:"onFormiePageToggle",canonicalEvent:"formie:page:navigate:after",disposition:"safe"},{legacyEvent:"onBeforeFormieSubmit",canonicalEvent:"formie:submit:before",disposition:"approximate"},{legacyEvent:"onFormieValidate",canonicalEvent:"formie:stage:validate:before",disposition:"approximate"},{legacyEvent:"onAfterFormieValidate",canonicalEvent:"formie:stage:validate:after",disposition:"approximate"},{legacyEvent:"onFormieSubmit",canonicalEvent:"formie:submit:after",disposition:"approximate"}];function Uc(t){if(!t)return{enabled:!1,legacyDomEvents:!1,legacyValidatorEvents:!1};if(t===!0)return{enabled:!0,legacyDomEvents:!0,legacyValidatorEvents:!0};const e=t.legacyDomEvents??!0,r=t.legacyValidatorEvents??!0;return{enabled:e||r,legacyDomEvents:e,legacyValidatorEvents:r}}function Fr(t){return t}function $m(t,e){return`formie:field:${t}:${e}`}function Ot(t){return`formie:validator:${t}`}function Vm(t,e){return`formie:address:${t}:${e}`}function Hm(t){return`formie:file-upload:${t}`}function qm(t,e){return`formie:payment:${t}:${e}`}function Or(t){return`formie:state:${t}`}function Kc(t,e){return`formie:module:${t}:${e}`}function Wc(t){return`formie:module:${t}`}function Gc(t,e,r){t.dispatchEvent(new CustomEvent(e,{bubbles:!0,detail:r}))}function Jc(t,e){if(t.canonicalEvent!=="formie:submit:result")return!0;const r=e;return t.legacyEvent==="onAfterFormieSubmit"?!!(r!=null&&r.ok):t.legacyEvent==="onFormieSubmitError"?(r==null?void 0:r.ok)===!1:!0}function Yc(t,e){const r=e&&typeof e=="object"?e:{},n=typeof r.pageId=="string"?r.pageId:"",i=Array.from(t.querySelectorAll("[data-formie-page-id]")),a=i.findIndex(o=>o.getAttribute("data-formie-page-id")===n);return{data:{nextPageId:n,nextPageIndex:a,totalPages:i.length}}}function Qc(t,e,r,n,i){const a=globalThis.Formie||i;return t.legacyEvent==="onFormieLoaded"?{formie:a}:t.legacyEvent==="onFormieInit"?{formie:a,form:i,$form:n,formId:i.id}:t.legacyEvent==="onFormieReady"?{...e&&typeof e=="object"?e:{},form:n,target:r,instance:i}:t.legacyEvent==="onFormiePageToggle"?Yc(n,e):e}function Zc({target:t,form:e,instance:r,options:n,unbinds:i}){n.legacyDomEvents&&jc.forEach(a=>{const o=s=>{if(!(s instanceof CustomEvent)||!Jc(a,s.detail))return;const l=a.target==="document"?document:e;Gc(l,a.legacyEvent,Qc(a,s.detail,t,e,r))};t.addEventListener(Fr(a.canonicalEvent),o),i.push(()=>{t.removeEventListener(Fr(a.canonicalEvent),o)})})}function Pt(t,e,r){t.dispatchEvent(new CustomEvent(e,{bubbles:!0,detail:r}))}function vr(t,e){return!!t&&typeof t=="object"&&t.validator===e}function Xc({target:t,form:e,validatorDetail:r,options:n,unbinds:i}){if(!n.legacyValidatorEvents||!r)return;const{validator:a,addValidator:o,removeValidator:s}=r,l={...r,form:e,target:t};Pt(document,"formieValidatorInitialized",l);const c=d=>{!(d instanceof CustomEvent)||!vr(d.detail,a)||Pt(document,"formieValidatorDestroyed",{...l,...d.detail})},h=d=>{!(d instanceof CustomEvent)||!vr(d.detail,a)||!(d.target instanceof Element)||e.contains(d.target)&&Pt(d.target,"formieValidatorShowError",{...d.detail,addValidator:o,removeValidator:s,form:e,target:t})},f=d=>{!(d instanceof CustomEvent)||!vr(d.detail,a)||!(d.target instanceof Element)||e.contains(d.target)&&Pt(d.target,"formieValidatorClearError",{...d.detail,addValidator:o,removeValidator:s,form:e,target:t})};document.addEventListener("formie:validator:destroy",c),document.addEventListener("formie:validator:show-error",h),document.addEventListener("formie:validator:clear-error",f),i.push(()=>{document.removeEventListener("formie:validator:destroy",c),document.removeEventListener("formie:validator:show-error",h),document.removeEventListener("formie:validator:clear-error",f)})}function ie(t,e,r){t.dispatchEvent(new CustomEvent(Fr(e),{bubbles:!0,detail:r}))}function Jr(t){const e=(t.dataset.formieErrorAriaLive||"polite").trim().toLowerCase();return e==="assertive"||e==="off"?e:"polite"}function eu(t,e){return t==="off"?null:e?t:"polite"}function Lo(t){return t==="off"?null:t}function Yr(t,e){if(e){t.setAttribute("aria-live",e),t.setAttribute("aria-atomic","true");return}t.removeAttribute("aria-live"),t.removeAttribute("aria-atomic")}function Mo(){return globalThis}function Ro(){return Mo().__FORMIE_DEBUG__===!0}function tu(t){Mo().__FORMIE_DEBUG__=t}function ru(t,e,r){if(Ro()){if(typeof r>"u"){console.log(`[formie:${t}] ${e}`);return}console.log(`[formie:${t}] ${e}`,r)}}function nu(t,e,r){if(Ro()){if(typeof r>"u"){console.warn(`[formie:${t}] ${e}`);return}console.warn(`[formie:${t}] ${e}`,r)}}function Le(t,e){const r=e?`${t}:${e}`:t;return{log:(n,i)=>{ru(r,n,i)},warn:(n,i)=>{nu(r,n,i)}}}const At=Le("general","page-client-event"),ou="data-formie-client-event",Dn="data-formie-pending-client-events";function iu(t){var e;return typeof window<"u"&&((e=window.CSS)!=null&&e.escape)?window.CSS.escape(t):t.replace(/\\/g,"\\\\").replace(/"/g,'\\"')}function au(t){var o,s,l;const e=t.querySelector('input[name="pageId"]'),r=(o=e==null?void 0:e.value)==null?void 0:o.trim();if(r)return r;const n=t.querySelector("[data-formie-page]:not([data-formie-page-hidden])"),i=(s=n==null?void 0:n.getAttribute("data-formie-page-id"))==null?void 0:s.trim();if(i)return i;const a=t.querySelector("[data-formie-page]");return((l=a==null?void 0:a.getAttribute("data-formie-page-id"))==null?void 0:l.trim())||null}function su(t){if(!(t!=null&&t.trim()))return null;try{const e=JSON.parse(t);return e&&typeof e=="object"?e:null}catch{return At.warn("Invalid data-formie-client-event JSON.",{rawPreview:t.slice(0,80)}),null}}function lu(t){const e={};return t.forEach(r=>{const n=typeof r.label=="string"?r.label.trim():"";n&&(e[n]=typeof r.value=="string"?r.value:"")}),e}function cu(t){return Array.isArray(t)?t.map(e=>{if(!e||typeof e!="object")return null;const r=e,n=typeof r.event=="string"?r.event.trim():"",i=r.payload&&typeof r.payload=="object"?r.payload:null;return!n||!i?null:{event:n,payload:i}}).filter(e=>e!==null):[]}function Qr(t,e){if(!e.length)return;const r=window;r.dataLayer=r.dataLayer||[],e.forEach(n=>{r.dataLayer.push(n.payload),t.dispatchEvent(new CustomEvent("formie:client-event",{bubbles:!0,detail:{event:n.event,payload:n.payload}}))}),At.log("Dispatched resolved client events.",{count:e.length,events:e.map(n=>n.event)})}function uu(t){const e=t.getAttribute(Dn);if(e!=null&&e.trim())try{const r=JSON.parse(e),n=cu(r);n.length&&Qr(t,n)}catch{At.warn("Invalid pending client events JSON on form element.")}finally{t.removeAttribute(Dn)}}function Fo(t,e){if(e!=="submit")return;const r=au(t);if(!r){At.log("No submitted page id; skipping client event.");return}const n=t.querySelector(`[data-formie-page][data-formie-page-id="${iu(r)}"]`);if(!n){At.log("No page section for id; skipping client event.",{pageId:r});return}const i=n.getAttribute(ou);if(i===null)return;const a=su(i);if(!a||!Array.isArray(a.fields))return;const o=lu(a.fields);Qr(t,[{event:typeof o.event=="string"&&o.event!==""?o.event:"formPageSubmission",payload:o}])}const Qt=new WeakMap,du="[data-formie-form], [data-formie], form";function fu(t){return t?(Array.isArray(t)?t:[t]).flatMap(r=>String(r).split(/\s+/)).map(r=>r.trim()).filter(Boolean):[]}function Zr(t){return Array.from(new Set(t))}function mu(t){if(!t)return{};const e=Qt.get(t);if(e)return e;const r=t.closest(du);return r?Qt.get(r)||{}:{}}function hu(t){const e={};return Object.entries(t||{}).forEach(([r,n])=>{const i=Zr(fu(n));i.length&&(e[r]=i)}),e}function zn(t,e,r){const n=hu(e),i=r||(t instanceof HTMLFormElement?t:t.querySelector("form"));return Qt.set(t,n),i&&Qt.set(i,n),n}function Xr(t,e){return mu(t)[e]||[]}function ue(t,e,...r){const n=Zr(r.flatMap(i=>Xr(e,i)));n.length&&t.classList.add(...n)}function ht(t,e,...r){const n=Zr(r.flatMap(i=>Xr(e,i)));n.length&&t.classList.remove(...n)}function ft(t,e,r,n){Xr(e,r).forEach(i=>{t.classList.toggle(i,n)})}function pu(t,e){if(ft(t,t,"tabError",e),e){t.setAttribute("data-formie-tab-error","true");return}t.removeAttribute("data-formie-tab-error")}function tt(t){const e=new Set;t.querySelectorAll("[data-formie-page]").forEach(r=>{const n=r,i=n.getAttribute("data-formie-page-id");i&&n.querySelector("[data-formie-field-has-error]")&&e.add(i)}),t.querySelectorAll("[data-formie-tab]").forEach(r=>{const n=r,i=n.getAttribute("data-formie-page-id");pu(n,!!i&&e.has(i))})}function en(t,e){const r=(t.getAttribute("aria-describedby")||"").trim(),n=r?r.split(/\s+/):[];n.includes(e)||n.push(e),t.setAttribute("aria-describedby",n.join(" ").trim())}function gu(t,e=document){const r=(t.getAttribute("aria-describedby")||"").trim();if(!r)return;const n=r.split(/\s+/).filter(i=>!!i&&!!e.getElementById(i));if(n.length){t.setAttribute("aria-describedby",n.join(" "));return}t.removeAttribute("aria-describedby")}function tn(t,e){t.setAttribute("aria-errormessage",e),en(t,e)}function Oo(t,e=[]){e.forEach(r=>{t.getAttribute("aria-errormessage")===r&&t.removeAttribute("aria-errormessage")}),!e.length&&t.hasAttribute("aria-errormessage")&&t.removeAttribute("aria-errormessage"),gu(t)}const vu="data-formie-validation-skip";function Re(t){return!!t&&t.hasAttribute(vu)}function Po(t){return Array.from(t.querySelectorAll("[data-formie-field-handle]")).find(r=>r.getAttribute("data-formie-field-has-error")==="true"?!0:r.querySelector("[data-formie-field-error]")!==null)||null}function bu(t){const e=Array.from(t.querySelectorAll('[aria-invalid="true"]')).find(r=>!Re(r));return e||(Array.from(t.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')).find(r=>!Re(r))??null)}function No(t){return t.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]")}function yu(t){t.querySelectorAll("[data-formie-field-handle]").forEach(e=>{const r=e;if(!(r.getAttribute("data-formie-field-has-error")==="true"||r.querySelector("[data-formie-field-error]")!==null))return;r.setAttribute("data-formie-field-has-error","true"),ue(r,t,"fieldLayoutError");const i=r.querySelector("[data-formie-field-error]"),a=(i==null?void 0:i.id)||"";r.querySelectorAll("input, select, textarea").forEach(o=>{const s=o;if(Re(s))return;s.setAttribute("aria-invalid","true"),ue(s,t,"fieldControlError"),s.setAttribute("data-formie-input-has-error","true"),a&&tn(s,a);const l=r.querySelector("[data-formie-instructions]");l!=null&&l.id&&en(s,l.id)})})}function wu(t){return!!Po(t)||!!No(t)}function Do(t){const e=Po(t);if(e){const n=bu(e);if(n){if(n.scrollIntoView({behavior:"smooth",block:"center"}),typeof n.focus=="function")try{n.focus({preventScroll:!0})}catch{n.focus()}return!0}return e.scrollIntoView({behavior:"smooth",block:"center"}),!0}const r=No(t);return r?(r.scrollIntoView({behavior:"smooth",block:"center"}),!0):!1}class Eu{constructor(){this.listeners=new Map}on(e,r){var n;return this.listeners.has(e)||this.listeners.set(e,new Set),(n=this.listeners.get(e))==null||n.add(r),()=>{var i;(i=this.listeners.get(e))==null||i.delete(r)}}async emit(e,r){const n=this.listeners.get(e);if(!(!n||n.size===0))for(const i of n)await i(r)}async emitSafe(e,r){const n=this.listeners.get(e),i={eventName:e,total:(n==null?void 0:n.size)||0,succeeded:0,failed:[]};if(!n||n.size===0)return i;let a=0;for(const o of n){try{await o(r),i.succeeded+=1}catch(s){i.failed.push({index:a,error:s})}a+=1}return i}async emitParallelSafe(e,r){const n=this.listeners.get(e),i={eventName:e,total:(n==null?void 0:n.size)||0,succeeded:0,failed:[]};return!n||n.size===0||(await Promise.allSettled(Array.from(n).map(async o=>o(r)))).forEach((o,s)=>{if(o.status==="fulfilled"){i.succeeded+=1;return}i.failed.push({index:s,error:o.reason})}),i}clear(){this.listeners.clear()}}const zo="CRAFT_CSRF_TOKEN",$o="data-formie-csrf-param",xu="data-formie-csrf";function Vo(){const t=globalThis.Craft,e=t==null?void 0:t.csrfTokenName;return typeof e=="string"&&e.trim()?e.trim():null}function ku(t){return typeof CSS<"u"&&typeof CSS.escape=="function"?CSS.escape(t):t.replace(/\\/g,"\\\\").replace(/"/g,'\\"')}function br(t,e){const r=t.querySelector(`input[name="${ku(e)}"]`);return r instanceof HTMLInputElement?r:null}function _u(t){var n;if(!t)return null;const e=t.querySelector(`input[${xu}]`);if(e instanceof HTMLInputElement&&e.name.trim())return e;if(t instanceof Element){const i=(n=t.getAttribute($o))==null?void 0:n.trim();if(i){const a=br(t,i);if(a)return a}}const r=Vo();if(r){const i=br(t,r);if(i)return i}return br(t,zo)}function rn(t){var i,a;const e=_u(t),r=((i=e==null?void 0:e.name)==null?void 0:i.trim())||"",n=((a=e==null?void 0:e.value)==null?void 0:a.trim())||"";return!r||!n?null:{name:r,value:n}}function Ho(t,e){const r=rn(e);r&&t.append(r.name,r.value)}function Bm(t,e){const r=rn(e);r&&(t[r.name]=r.value)}function Su(t,e){var a;const r=t.endsWith("[]")?t.slice(0,-2):t;if(!r)return!1;if(r===zo)return!0;const n=Vo();if(n&&r===n)return!0;if(e instanceof Element){const o=(a=e.getAttribute($o))==null?void 0:a.trim();if(o&&r===o)return!0}const i=rn(e);return!!i&&r===i.name}async function qo(t,e={}){const r={Accept:"application/json",...e.headers||{}};return delete r["X-Requested-With"],delete r["x-requested-with"],fetch(String(t),{method:e.method||"GET",body:e.body??null,signal:e.signal,cache:"no-store",headers:r,credentials:"same-origin"})}async function ir(t,e={}){const r=await qo(t,e);if(!r.ok)throw new Error(`Request failed (${r.status}) for ${String(t)}`);return r.json()}async function jm(t,e={}){const r=await qo(t,e);if(!r.ok)throw new Error(`Request failed (${r.status}) for ${String(t)}`);return r.text()}const ye=Le("general","transport");function Au(t){const e={};return["theme","themeConfig","locale","siteId"].forEach(r=>{t[r]!==void 0&&(e[r]=t[r])}),e}function Bo(t,e="",r={}){if(Array.isArray(t)){const n=t.map(i=>typeof i=="string"?i:String(i??"")).filter(i=>i.trim()!=="");return e&&n.length&&(r[e]=(r[e]||[]).concat(n)),r}return t&&typeof t=="object"&&Object.entries(t).forEach(([n,i])=>{const a=e?`${e}.${n}`:n;Bo(i,a,r)}),r}function Tu(t,e){const r=t.success===!0,n=t.keepSubmitLoading===!0,i=t.errors,a=Bo(i||{}),o=a.form||[],s={};Object.entries(a).forEach(([f,d])=>{if(f==="form")return;const u=f.split(".")[0];s[u]=(s[u]||[]).concat(d)});const l=!r&&o.length===0&&Object.keys(s).length>0?[e||"Submission failed."]:o,c=!r&&n&&l.length===0&&Object.keys(s).length===0;return{ok:r,action:t.submitAction==="back"||t.submitAction==="save"||t.submitAction==="submit"?t.submitAction:void 0,message:t.submitActionMessage||(r?"Submission completed.":c?"":l[0]||"Submission failed."),code:r?void 0:String(t.code||"SUBMIT_ERROR"),keepSubmitLoading:n,fieldErrors:Object.keys(s).length?s:void 0,formErrors:l.length?l:void 0,nextPage:t.nextPageId?{id:String(t.nextPageId)}:null,redirect:t.redirectUrl?{url:String(t.redirectUrl),target:t.submitActionTab==="new-tab"?"new-tab":"same-tab"}:null,submitData:Array.isArray(t.submitData)?t.submitData:void 0,clientEvents:Array.isArray(t.clientEvents)?t.clientEvents:void 0,meta:t}}async function Cu(t,e,r={}){const n=JSON.stringify({handle:e,renderOptions:r});ye.log("requestRender start.",{endpoint:t,handle:e});const i=await ir(t,{method:"POST",body:n,headers:{"Content-Type":"application/json"}});return ye.log("requestRender complete.",{hasHtml:!!i.html}),i}async function Iu(t,e,r={}){var s;const i=JSON.stringify({query:`
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`,variables:{handle:e,input:Au(r)}});ye.log("requestGraphqlRender start.",{endpoint:t,handle:e});const a=await ir(t,{method:"POST",body:i,headers:{"Content-Type":"application/json"}});if(Array.isArray(a.errors)&&a.errors.length>0)throw new Error(a.errors.map(l=>l.message||"Unknown GraphQL error").join("; "));if(!((s=a.data)!=null&&s.formieHtmlForm))throw new Error(`Form not found for handle "${e}".`);const o=a.data.formieHtmlForm;return ye.log("requestGraphqlRender complete.",{hasHtml:!!o.html}),o}async function nn(t,e,r){const n=new URL(t,window.location.origin);n.searchParams.set("handle",e),r&&n.searchParams.set("renderId",r),ye.log("requestRefreshTokens start.",{endpoint:n.toString(),handle:e,hasRenderId:!!r});const i=await ir(n.toString());return ye.log("requestRefreshTokens complete.",{hasRefreshTokens:!!i.refreshTokens}),i.refreshTokens||i}async function Lu(t,e,r){const n=new URL(t,window.location.origin),i=new FormData;r&&i.append("pageId",r),e&&(["handle","renderId","draftContextToken","draftContext","continuationToken"].forEach(s=>{var h;const l=e.querySelector(`input[name="${s}"]`),c=(h=l==null?void 0:l.value)==null?void 0:h.trim();c&&i.append(s,c)}),Ho(i,e)),ye.log("requestSetPage start.",{requestUrl:n.toString(),pageId:r||null});const a=await ir(n.toString(),{method:"POST",body:i});return ye.log("requestSetPage complete.",a),a}function Mu(t,e){const r=new URL(t,window.location.origin),n=new FormData;["handle","renderId","draftContextToken","draftContext"].forEach(a=>{var l;const o=e.querySelector(`input[name="${a}"]`),s=(l=o==null?void 0:o.value)==null?void 0:l.trim();s&&n.append(a,s)}),Ho(n,e),ye.log("clearSubmissionOnUnload start.",{requestUrl:r.toString()});try{if(typeof navigator.sendBeacon=="function"&&navigator.sendBeacon(r.toString(),n))return}catch{}fetch(r.toString(),{method:"POST",body:n,credentials:"include",keepalive:!0,headers:{Accept:"application/json"}})}async function Ru(t,e){var c,h;const r=(t.getAttribute("method")||"POST").toUpperCase(),n=t.getAttribute("action")||window.location.href,i=((c=t.dataset.formieErrorMessage)==null?void 0:c.trim())||"Submission failed.";ye.log("submitForm start.",{method:r,action:n,submitAction:e.get("submitAction")});const a=await fetch(n,{method:r,body:e,credentials:"include",headers:{Accept:"application/json"}}),o=a.headers.get("content-type")||"";if(!o.includes("application/json"))return a.ok?(ye.log("submitForm non-JSON success response.",{status:a.status,contentType:o}),{ok:!0,message:"Submission completed."}):(ye.warn("submitForm non-JSON HTTP error.",{status:a.status,contentType:o}),{ok:!1,code:"HTTP_ERROR",message:`Request failed (${a.status}).`,formErrors:[`Request failed (${a.status}).`]});const s=await a.json(),l=Tu(s,i);return ye.log("submitForm JSON response normalized.",{ok:l.ok,code:l.code,hasRedirect:!!((h=l.redirect)!=null&&h.url),hasSubmitData:Array.isArray(l.submitData)&&l.submitData.length>0}),l}function on(t){return Array.from(t.querySelectorAll("[data-formie-page]"))}function ar(t){const e=on(t);if(!e.length)return{scope:t,final:!0};const r=e.find(n=>!n.hasAttribute("data-formie-page-hidden"))||e[e.length-1];return{scope:r,final:r===e[e.length-1]}}const Fu=["prepare","normalize","validate","screen","authorize","dispatch","finalize"],Ou=["prepare","normalize","validate","screen","authorize"],se=Le("general","pipeline");function yr(t,e){return{ok:!1,stage:t,code:"ABORTED",message:e||"Submission aborted.",formErrors:[e||"Submission aborted."]}}function jo(t){return t instanceof HTMLInputElement||t instanceof HTMLSelectElement||t instanceof HTMLTextAreaElement}function Uo(t){return!(!t.name||t.disabled||t instanceof HTMLInputElement&&(t.type==="submit"||t.type==="button"||t.type==="reset"||t.type==="image"||(t.type==="checkbox"||t.type==="radio")&&!t.checked||t.type==="file"&&(!t.files||t.files.length===0)))}function Ko(t,e){if(e instanceof HTMLInputElement){if(e.type==="file"){Array.from(e.files||[]).forEach(r=>{t.append(e.name,r)});return}t.append(e.name,e.value);return}if(e instanceof HTMLSelectElement&&e.multiple){Array.from(e.selectedOptions).forEach(r=>{t.append(e.name,r.value)});return}t.append(e.name,e.value)}function Pu(t,e){e.querySelectorAll("input, select, textarea").forEach(r=>{const n=jo(r)?r:null;!n||n.closest("[data-formie-page]")||Uo(n)&&Ko(t,n)})}function Nu(t,e){const r=new Set;return e.querySelectorAll("input, select, textarea").forEach(n=>{const i=jo(n)?n:null;!i||!i.name||i.disabled||i instanceof HTMLInputElement&&(i.type==="submit"||i.type==="button"||i.type==="reset"||i.type==="image")||(i.name.startsWith("fields[")&&r.add(i.name),Uo(i)&&Ko(t,i))}),r}function Du(t,e){e.forEach(r=>{t.has(r)||t.append(r,"")})}function $n(t,e){const r=on(t),n=r.find(o=>!o.hasAttribute("data-formie-page-hidden"))||null;if(!r.length||!n){const o=new FormData(t);return o.set("submitAction",e),o}const i=new FormData;Pu(i,t);const a=Nu(i,n);return Du(i,a),i.set("submitAction",e),i}function zu(t,e){if(e!=="submit")return!1;const r=on(t);return r.length?(r.find(i=>!i.hasAttribute("data-formie-page-hidden"))||r[r.length-1])===r[r.length-1]:!0}async function Wo(t,e,r,n={}){se.log("Starting submit pipeline.",{action:e,preflightOnly:n.preflightOnly===!0});let i=!1,a,o=null;const s=zu(t,e),l={form:t,action:e,formData:$n(t,e),abort:d=>{i=!0,a=d,se.warn("Pipeline aborted.",{reason:d})},isAborted:()=>i,abortReason:()=>a},c={prepare:async d=>{const u=d.form.querySelector('input[name="submitAction"]');return u&&(u.value=d.action),d.formData.set("submitAction",d.action),null},normalize:async()=>null,validate:async d=>{var u;if(d.action!=="submit"||n.validateOnSubmit===!1)return null;if(n.validator){const{scope:g,final:x}=ar(d.form),m=n.validator.submit(x?d.form:g,{final:x});if(m.length>0){const p=(u=m[0])==null?void 0:u.input;if(p){p.scrollIntoView({behavior:"smooth",block:"center"});try{p.focus({preventScroll:!0})}catch{p.focus()}}return{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:n.validator.config.errorMessage||"Validation failed.",fieldErrors:n.validator.getFieldErrors(m),formErrors:[n.validator.config.errorMessage||"Validation failed."]}}return null}if(!d.form.checkValidity()){const g=d.form.querySelector(":invalid");return g==null||g.focus(),{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:"Validation failed.",formErrors:["Validation failed."]}}return null},screen:async()=>null,authorize:async()=>null,dispatch:async d=>{d.formData=$n(d.form,d.action);const u=await Ru(d.form,d.formData);return o=u,u},finalize:async d=>{var u;return o&&o.ok&&(u=o.redirect)!=null&&u.url&&(o.redirect.target==="new-tab"?window.open(o.redirect.url,"_blank"):window.location.href=o.redirect.url),null}};{const d=await r.emitSafe("formie:submit:before",l);d.failed.length>0&&se.warn("Submit before listeners failed.",{eventName:d.eventName,failed:d.failed.length})}if(s){const d=await r.emitSafe("formie:submit:final:before",l);d.failed.length>0&&se.warn("Final submit before listeners failed.",{eventName:d.eventName,failed:d.failed.length})}const h=n.preflightOnly?Ou:Fu;for(const d of h){if(se.log("Stage start.",{stage:d,action:e}),i)return se.warn("Stage skipped due to abort.",{stage:d,reason:a}),yr(d,a);{const g=await r.emitSafe(`formie:stage:${d}:before`,{...l,stage:d});g.failed.length>0&&se.warn("Stage before listeners failed.",{stage:d,failed:g.failed.length})}if(i){const g=yr(d,a);{const x=await r.emitSafe("formie:submit:after",g);x.failed.length>0&&se.warn("Submit after listeners failed (abort before stage).",{stage:d,failed:x.failed.length})}if(s){const x=await r.emitSafe("formie:submit:final:after",g);x.failed.length>0&&se.warn("Final submit after listeners failed (abort before stage).",{stage:d,failed:x.failed.length})}return se.warn("Aborted after stage before-hooks.",{stage:d,reason:a}),g}const u=await c[d](l);se.log("Stage runner complete.",{stage:d,hasResult:!!u,ok:u?u.ok:void 0,code:u==null?void 0:u.code});{const g=await r.emitSafe(`formie:stage:${d}:after`,{...l,stage:d,result:u});g.failed.length>0&&se.warn("Stage after listeners failed.",{stage:d,failed:g.failed.length})}if(i){const g=yr(d,a);{const x=await r.emitSafe("formie:submit:after",g);x.failed.length>0&&se.warn("Submit after listeners failed (abort after stage).",{stage:d,failed:x.failed.length})}if(s){const x=await r.emitSafe("formie:submit:final:after",g);x.failed.length>0&&se.warn("Final submit after listeners failed (abort after stage).",{stage:d,failed:x.failed.length})}return se.warn("Aborted after stage after-hooks.",{stage:d,reason:a}),g}if(u&&!u.ok){{const g=await r.emitSafe("formie:submit:after",u);g.failed.length>0&&se.warn("Submit after listeners failed (failed stage).",{stage:d,failed:g.failed.length})}if(s){const g=await r.emitSafe("formie:submit:final:after",u);g.failed.length>0&&se.warn("Final submit after listeners failed (failed stage).",{stage:d,failed:g.failed.length})}return se.warn("Pipeline short-circuited by failed stage.",{stage:d,code:u.code,message:u.message}),u}}const f=o||{ok:!0,stage:n.preflightOnly?"authorize":"finalize",message:n.preflightOnly?"Submission preflight completed.":"Submission completed."};{const d=await r.emitSafe("formie:submit:after",f);d.failed.length>0&&se.warn("Submit after listeners failed (success).",{failed:d.failed.length})}if(s){const d=await r.emitSafe("formie:submit:final:after",f);d.failed.length>0&&se.warn("Final submit after listeners failed (success).",{failed:d.failed.length})}return se.log("Pipeline completed.",{ok:f.ok,stage:f.stage,code:f.code}),f}function $u(t){var n;const e=t.querySelector("[data-formie-field-layout]");return((n=e==null?void 0:e.getAttribute("data-formie-error-position"))==null?void 0:n.trim())==="above"?"above":"below"}function Go(t,e){const r=t.querySelector("[data-formie-field-errors]");if(r)return r;const n=t.querySelector("[data-formie-field-content]"),i=t.querySelector("[data-formie-field-control]"),a=$u(t),o=document.createElement("div");return o.setAttribute("data-formie-field-errors","true"),e==null||e(o),n&&i?a==="above"?n.insertBefore(o,i):n.appendChild(o):t.appendChild(o),o}const Vu={rule:({input:t,getRule:e})=>!e("email")||!t.value||t.value.length<1?!0:/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t.value),message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-validation-email-message")??t.getAttribute("data-formie-pattern-email-message")??t.getAttribute("data-pattern-email-message")??r("{label} is not a valid email address.",{label:e})};function Hu(t){var e,r,n;return((n=(r=(e=t==null?void 0:t.querySelector("[data-formie-field-label]"))==null?void 0:e.childNodes[0])==null?void 0:r.textContent)==null?void 0:n.trim())||""}function Vn(t){const e=t.getRule("match");if(!e||e===!0||typeof e!="object"||!t.field)return null;const r=typeof e.fieldHandle=="string"?e.fieldHandle.trim():"";if(!r)return null;const n=t.form.querySelector(`[data-formie-field-handle="${r}"]`);return n?Array.from(n.querySelectorAll(t.config.fieldsSelector)).find(i=>(i instanceof HTMLInputElement||i instanceof HTMLSelectElement||i instanceof HTMLTextAreaElement)&&!Re(i))??null:null}const qu={rule:t=>{const e=Vn(t);return e?e.value===t.input.value:!0},message:t=>{const e=Vn(t),r=e==null?void 0:e.closest("[data-formie-field-handle]"),n=Hu(r);return t.input.getAttribute("data-formie-validation-match-message")??t.t("{label} must match {value}.",{label:t.label,value:n})}},Bu={rule:({input:t,getRule:e})=>{const r=e("number");if(!r||!t.value||t.value.trim()==="")return!0;const n=parseFloat(t.value);if(Number.isNaN(n))return!1;if(r!==!0&&typeof r=="object"){const i=typeof r.min=="number"?r.min:null,a=typeof r.max=="number"?r.max:null;if(i!==null&&n<i||a!==null&&n>a)return!1}return!0},message:({input:t,label:e,getRule:r,t:n})=>{const i=r("number"),a=i!==!0&&i&&typeof i=="object"&&typeof i.min=="number"?i.min:null,o=i!==!0&&i&&typeof i=="object"&&typeof i.max=="number"?i.max:null;return a!==null&&o!==null?t.getAttribute("data-formie-validation-number-min-message")??n("{label} must be no less than {min}.",{label:e,min:a}):a!==null?t.getAttribute("data-formie-validation-number-min-message")??n("{label} must be no less than {min}.",{label:e,min:a}):o!==null?t.getAttribute("data-formie-validation-number-max-message")??n("{label} must be no greater than {max}.",{label:e,max:o}):t.getAttribute("data-formie-validation-number-message")??t.getAttribute("data-formie-pattern-number-message")??t.getAttribute("data-pattern-number-message")??n("{label} is not a valid number.",{label:e})}},ju={rule:({input:t,getRule:e})=>{var r;if(!e("required")||t.type==="hidden")return!0;if(t.type==="checkbox"||t.type==="radio"){const n=((r=t.form)==null?void 0:r.querySelectorAll(`[name="${t.name}"]:not([type="hidden"]):not([disabled])`))||[];return n.length?Array.from(n).some(i=>i instanceof HTMLInputElement&&i.checked):t instanceof HTMLInputElement?t.checked:!0}return t.value.trim()!==""},message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-required-message")??t.getAttribute("data-required-message")??r("{label} cannot be blank.",{label:e})},Uu={rule:({input:t,getRule:e})=>{if(!e("url")||!t.value||t.value.length<1)return!0;try{return new URL(t.value),!0}catch{return!1}},message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-pattern-url-message")??t.getAttribute("data-pattern-url-message")??r("{label} is not a valid URL.",{label:e})},Ku={required:ju,email:Vu,url:Uu,number:Bu,match:qu};function Jo(){return window.FormieTranslations||{}}function Wu(){var r;if(typeof document>"u")return;const t=Array.from(document.querySelectorAll('script[type="application/json"][data-formie-translations]:not([data-formie-translations-loaded="true"])'));if(t.length===0)return;let e=null;for(const n of t){n.dataset.formieTranslationsLoaded="true";const i=(r=n.textContent)==null?void 0:r.trim();if(i)try{const a=JSON.parse(i);if(!a||Array.isArray(a)||typeof a!="object")continue;e={...e??Jo(),...a}}catch{continue}}e&&(window.FormieTranslations=e)}function Gu(){return Wu(),Jo()}function Ju(t){const e={};let r=0;for(;r<t.length;){for(;r<t.length&&/\s/.test(t[r]);)r++;if(r>=t.length)break;const n=t.slice(r).match(/^(\w+|=\d+)\{/);if(!n)break;const i=n[1];r+=n[0].length;let a=1;const o=r;for(;r<t.length&&a>0;)t[r]==="{"?a++:t[r]==="}"&&a--,a>0&&r++;e[i]=t.slice(o,r),r++}return e}function Yu(t,e){const r=`=${t}`;if(Object.prototype.hasOwnProperty.call(e,r))return e[r];if(typeof Intl<"u"&&typeof Intl.PluralRules=="function"){const i=new Intl.PluralRules().select(t);if(Object.prototype.hasOwnProperty.call(e,i))return e[i]}if(t===1&&Object.prototype.hasOwnProperty.call(e,"one"))return e.one;if(Object.prototype.hasOwnProperty.call(e,"other"))return e.other;const n=Object.keys(e)[0];return n?e[n]:""}function Qu(t,e){const r=t.slice(e).match(/^\{(\w+),\s*plural,\s*/);if(!r)return null;const n=r[1],i=e+r[0].length;let a=i;for(;a<t.length;){for(;a<t.length&&/\s/.test(t[a]);)a++;if(a>=t.length||t[a]==="}")break;const o=t.slice(a).match(/^(\w+|=\d+)\{/);if(!o)return null;a+=o[0].length;let s=1;for(;a<t.length&&s>0;)t[a]==="{"?s++:t[a]==="}"&&s--,s>0&&a++;a++}return a>=t.length||t[a]!=="}"?null:{param:n,body:t.slice(i,a),endIndex:a}}function Zu(t,e){let r="",n=0;for(;n<t.length;){if(t[n]!=="{"){r+=t[n],n++;continue}const i=Qu(t,n);if(!i){r+=t[n],n++;continue}const a=e[i.param],o=typeof a=="number"?a:Number.parseInt(String(a??""),10)||0,s=Ju(i.body);let l=Yu(o,s);l=l.replace(/#/g,String(o)),r+=l,n=i.endIndex+1}return r}function Xu(t,e){return t.replace(/\{(\w+),\s*number\}/g,(r,n)=>{if(!Object.prototype.hasOwnProperty.call(e,n))return r;const i=e[n];return typeof i=="number"?i.toLocaleString():String(i)})}function ed(t,e){return t.replace(/\{(\w+)\}/g,(r,n)=>Object.prototype.hasOwnProperty.call(e,n)?String(e[n]):r)}function ze(t,e={}){let r=Gu()[t]||t;return r=Zu(r,e),r=Xu(r,e),r=ed(r,e),r}const td={email:/^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,url:/^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,number:/^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,color:/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,date:/(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,time:/^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,month:/^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/},Je=Le("general","validator");function yt(t){return!!t&&(t instanceof HTMLInputElement||t instanceof HTMLSelectElement||t instanceof HTMLTextAreaElement)}function Hn(t){return!!(t.offsetWidth||t.offsetHeight||t.getClientRects().length)}class rd{constructor(e,r={}){this.errors=[],this.validators={},this.boundListeners=!1,this.activated=new WeakSet,this.submitted=!1,this.initialValues=new WeakMap,this.form=e,this.onBlur=this.blurHandler.bind(this),this.onChange=this.changeHandler.bind(this),this.onInput=this.inputHandler.bind(this),this.config={live:!1,errorAriaLive:"polite",errorMessage:"",fieldContainerErrorClass:[],inputErrorClass:[],messagesClass:[],messageClass:[],fieldsSelector:'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',patterns:td,...r},Object.entries(Ku).forEach(([n,i])=>{this.addValidator(n,i.rule,i.message)}),this.init()}init(){Je.log("Initializing validator.",{formId:this.form.id||null,live:this.config.live}),this.form.setAttribute("novalidate","true"),this.inputs().forEach(e=>{this.initialValues.set(e,this.getInputValue(e))}),this.config.live&&this.addEventListeners(),this.emitEvent(document,Ot("ready"),{validator:this})}inputs(e=null){if(yt(e))return Re(e)?[]:[e];const r=e||this.form;return Array.from(r.querySelectorAll(this.config.fieldsSelector)).filter(n=>yt(n)&&!Re(n))}getInputValue(e){var r;return e instanceof HTMLInputElement&&(e.type==="checkbox"||e.type==="radio")?e.checked:e instanceof HTMLInputElement&&e.type==="file"?(r=e.files)!=null&&r.length?Array.from(e.files).map(n=>n.name).join("|"):"":e.value??""}isDirty(e){return this.initialValues.has(e)?this.getInputValue(e)!==this.initialValues.get(e):(this.initialValues.set(e,this.getInputValue(e)),!1)}shouldShowError(e){return this.submitted||this.activated.has(e)}isValid(e=null,r={}){return this.validate(e,r).length===0}validate(e=null,r={}){this.errors=[];const n=new Set;return this.inputs(e).forEach(i=>{let a=!1;if(!this.isVisible(i,r))return;const o=i.closest("[data-formie-field-handle]"),s=i instanceof HTMLInputElement&&(i.type==="checkbox"||i.type==="radio")?`${(o==null?void 0:o.getAttribute("data-formie-field-handle"))||""}:${i.name}`:null;if(s){if(n.has(s))return;n.add(s)}this.shouldShowError(i)&&this.removeError(i);const l=this.getValidatorCallbackOptions(i);Object.entries(this.validators).forEach(([c,h])=>{var d;if(!h.validate(l)){const u=this.getErrorMessage(i,c,h,l);this.shouldShowError(i)&&!a&&this.showError(i,c,u),this.errors.push({input:i,field:l.field,validator:c,message:u,handle:((d=l.field)==null?void 0:d.getAttribute("data-formie-field-handle"))||null,result:!1}),a=!0}}),!a&&this.shouldShowError(i)&&this.removeError(i)}),Je.log("Validation pass complete.",{errorCount:this.errors.length,includeHiddenPages:r.includeHiddenPages===!0}),this.errors}removeAllErrors(){this.inputs().forEach(e=>{this.removeError(e)})}removeError(e){var a;const r=e.closest("[data-formie-field-handle]");if(!r){e.removeAttribute("aria-invalid");return}const n=r.querySelector("[data-formie-field-errors]"),i=Array.from(r.querySelectorAll("[data-formie-field-error]")).map(o=>o.id).filter(Boolean);r.querySelectorAll("[data-formie-field-error]").forEach(o=>{o.remove()}),n&&(n.innerHTML=""),r.querySelectorAll("input, select, textarea").forEach(o=>{const s=o;s.removeAttribute("aria-invalid"),this.config.inputErrorClass.length&&s.classList.remove(...this.config.inputErrorClass),s.removeAttribute("data-formie-input-has-error"),Oo(s,i)});for(let o=r;o;o=(a=o.parentElement)==null?void 0:a.closest("[data-formie-field-handle]"))this.config.fieldContainerErrorClass.length&&o.classList.remove(...this.config.fieldContainerErrorClass),o.removeAttribute("data-formie-field-has-error");this.emitEvent(e,Ot("clear-error"),{validator:this}),tt(this.form)}showError(e,r,n){var c;const i=e.closest("[data-formie-field-handle]");if(!i)return;let a=i.querySelector("[data-formie-field-errors]");a||(a=Go(i,h=>{this.config.messagesClass.length&&h.classList.add(...this.config.messagesClass)})),this.config.messagesClass.length&&a.classList.add(...this.config.messagesClass),a.innerHTML="";const o=i.getAttribute("data-formie-field-handle")||"field",s=`${o}-error`;a.id=a.id||`${o}-errors`,Yr(a,eu(this.config.errorAriaLive,this.submitted));const l=document.createElement("div");l.setAttribute("data-formie-field-error","true"),l.setAttribute(`data-formie-field-error-${r}`,"true"),l.setAttribute("id",s),this.config.messageClass.length&&l.classList.add(...this.config.messageClass),l.textContent=n,a.appendChild(l),i.setAttribute("data-formie-field-has-error","true"),i.querySelectorAll("input, select, textarea").forEach(h=>{const f=h;Re(f)||(f.setAttribute("aria-invalid","true"),this.config.inputErrorClass.length&&f.classList.add(...this.config.inputErrorClass),f.setAttribute("data-formie-input-has-error","true"),tn(f,s))});for(let h=i;h;h=(c=h.parentElement)==null?void 0:c.closest("[data-formie-field-handle]"))this.config.fieldContainerErrorClass.length&&h.classList.add(...this.config.fieldContainerErrorClass),h.setAttribute("data-formie-field-has-error","true");this.emitEvent(e,Ot("show-error"),{validator:this,validatorName:r,errorMessage:n}),tt(this.form)}getValidatorCallbackOptions(e){var a,o,s;const r=e.closest("[data-formie-field-handle]"),n=((s=(o=(a=r==null?void 0:r.querySelector("[data-formie-field-label]"))==null?void 0:a.childNodes[0])==null?void 0:o.textContent)==null?void 0:s.trim())??"",i=this.parseValidationRules(r==null?void 0:r.getAttribute("data-formie-validation"));return{t:ze,input:e,label:n,field:r,form:this.form,config:this.config,rules:i,getRule:l=>this.getRule(r,l)}}getErrorMessage(e,r,n,i){return(typeof n.errorMessage=="function"?n.errorMessage(i):n.errorMessage)??ze("{label} is invalid.",{label:i.label})}getErrors(){return this.errors}getFieldErrors(e=this.errors){const r={};return e.forEach(n=>{var i;!n.handle||(i=r[n.handle])!=null&&i.length||(r[n.handle]=[n.message])}),r}getRule(e,r){if(!e)return!1;const n=this.parseValidationRules(e.getAttribute("data-formie-validation"));return Object.prototype.hasOwnProperty.call(n,r)?n[r]:!1}parseValidationRules(e){const r={};if(!e)return r;let n=null;try{n=JSON.parse(e)}catch{return Je.warn("Invalid validation rules payload.",{formId:this.form.id||null}),r}return Array.isArray(n)&&n.forEach(i=>{if(!i||typeof i!="object"||Array.isArray(i))return;const a=i,o=typeof a.type=="string"?a.type.trim():"";o&&(r[o]=a)}),r}destroy(){Je.log("Destroying validator.",{formId:this.form.id||null}),this.removeEventListeners(),this.form.removeAttribute("novalidate"),this.emitEvent(document,Ot("destroy"),{validator:this})}isVisible(e,r={}){if(e.disabled||e.hasAttribute("data-formie-conditions-disabled")||e.closest("[data-formie-conditions-disabled]")||e.closest("[data-formie-conditionally-hidden]"))return!1;if(e.closest("[data-formie-page-hidden]"))return!!r.includeHiddenPages;const n=e.closest("[data-formie-field-handle]"),i=n==null?void 0:n.querySelector("[data-formie-rich-text]");return i instanceof HTMLElement?Hn(i):Hn(e)}blurHandler(e){var r;!(e.target instanceof HTMLElement)||!yt(e.target)||Re(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form))||e instanceof CustomEvent||e.target instanceof HTMLInputElement&&e.target.type==="file"||e.target instanceof HTMLInputElement&&(e.target.type==="checkbox"||e.target.type==="radio")||(this.isDirty(e.target)&&this.activated.add(e.target),this.shouldShowError(e.target)&&this.validate(e.target))}changeHandler(e){var r;if(!(!(e.target instanceof HTMLElement)||!yt(e.target)||Re(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form)))&&!(e instanceof CustomEvent)){if(e.target instanceof HTMLSelectElement){this.activated.add(e.target),this.validate(e.target);return}e.target instanceof HTMLInputElement&&(e.target.type!=="file"&&e.target.type!=="checkbox"&&e.target.type!=="radio"||(this.activated.add(e.target),this.validate(e.target)))}}inputHandler(e){var r;!(e.target instanceof HTMLElement)||!yt(e.target)||Re(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form))||e instanceof CustomEvent||e.target instanceof HTMLInputElement&&(e.target.type==="checkbox"||e.target.type==="radio")||this.shouldShowError(e.target)&&this.validate(e.target)}submit(e=null,{final:r=!1}={}){return this.submitted=!0,Je.log("Submit validation requested.",{final:r}),this.boundListeners||this.addEventListeners(),this.removeAllErrors(),this.validate(e,{includeHiddenPages:r})}resetLiveState(){this.submitted=!1,this.activated=new WeakSet,this.errors=[],this.removeAllErrors()}addEventListeners(){this.boundListeners||(this.form.addEventListener("blur",this.onBlur,!0),this.form.addEventListener("change",this.onChange,!1),this.form.addEventListener("input",this.onInput,!1),this.boundListeners=!0,Je.log("Event listeners attached."))}removeEventListeners(){this.form.removeEventListener("blur",this.onBlur,!0),this.form.removeEventListener("change",this.onChange,!1),this.form.removeEventListener("input",this.onInput,!1),this.boundListeners=!1,Je.log("Event listeners removed.")}emitEvent(e,r,n={}){e.dispatchEvent(new CustomEvent(r,{bubbles:!0,detail:n}))}addValidator(e,r,n){this.validators[e]={validate:r,errorMessage:n}}removeValidator(e){delete this.validators[e]}}const Nt="data-formie-submit-validation-disabled",wr="data-formie-preserve-disabled",nd="data-formie-submit-ready";function Yo(t){return t.dataset.formieDisableSubmitUntilValid==="true"}function od(t){return Array.from(t.querySelectorAll('button[data-formie-action="submit"]')).filter(e=>e instanceof HTMLButtonElement)}function id(t){return!t.hasAttribute("data-formie-conditionally-hidden")&&!t.closest("[data-formie-conditionally-hidden]")}function an(t,e){if(!Yo(t)||t.getAttribute("data-formie-loading")==="true")return;const{scope:r,final:n}=ar(t),i=e.isValid(r,{includeHiddenPages:n});t.setAttribute(nd,i?"true":"false"),od(t).forEach(a=>{if(id(a)){if(i){if(!a.hasAttribute(Nt))return;a.hasAttribute(wr)?(a.disabled=!0,a.removeAttribute(wr)):a.disabled=!1,a.removeAttribute(Nt);return}a.hasAttribute(Nt)||(a.disabled&&a.setAttribute(wr,"true"),a.setAttribute(Nt,"true")),a.disabled=!0}})}function ad(t,e,r){if(!Yo(t))return()=>{};let n=!1;const i=()=>{n||(n=!0,queueMicrotask(()=>{n=!1,an(t,e)}))};i();const a=()=>{i()};t.addEventListener("input",a,!0),t.addEventListener("change",a,!0);const o=()=>{window.setTimeout(()=>{i()},0)};t.addEventListener("reset",o);const s=()=>{i()};r.addEventListener("formie:conditions:evaluated",s);const l=new MutationObserver(c=>{c.some(f=>{if(f.type==="attributes"){const d=f.attributeName||"";return d==="data-formie-page-hidden"||d==="data-formie-conditionally-hidden"||d==="data-formie-loading"||d==="disabled"}return f.type==="childList"})&&i()});return l.observe(t,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["data-formie-page-hidden","data-formie-conditionally-hidden","data-formie-loading","disabled"]}),()=>{t.removeEventListener("input",a,!0),t.removeEventListener("change",a,!0),t.removeEventListener("reset",o),r.removeEventListener("formie:conditions:evaluated",s),l.disconnect()}}const sd="STALE_SUBMISSION_STATE",qn=new WeakMap,Zt=new WeakMap,je=Le("general","submit-result");function Pr(t,e,r){let n=t.querySelector(`input[name="${e}"]`);n||(n=document.createElement("input"),n.type="hidden",n.name=e,t.appendChild(n)),n.value=r}function Bn(t,e){t.setAttribute("data-formie-internal-navigation",e)}function Et(t,e){const r=t.querySelector(`input[name="${e}"]`);r==null||r.remove()}function ld(t,e){try{const r=new URL(t,window.location.href);return r.searchParams.delete(e),r.toString()}catch{return t}}function cd(t){try{return new URL(t,window.location.href).origin===window.location.origin}catch{return!1}}function Qo(t){return Array.from(t.querySelectorAll("[data-formie-page]"))}function ud(t){return Array.from(t.querySelectorAll("[data-formie-tab]"))}function dd(t,e,r){return e<0||r<1?0:(t.dataset.formieProgressCalculation==="page-position"?"page-position":"completion")==="page-position"?Math.round((e+1)/r*100):Math.round(e/r*100)}function fd(t){return t<=0?"start":t>=100?"end":"middle"}function md(t){return(t.dataset.formieSubmitAction||"").trim()}function jn(t,e){var n;const r=(n=e.meta)==null?void 0:n.effectiveSubmitAction;return typeof r=="string"&&r.trim()!==""?r.trim():md(t)}function Un(t){const e=t.dataset.formieSubmitActionFormHide;if(e===void 0)return!1;const r=e.trim().toLowerCase();return r==="true"||r==="1"||r===""}function sn(t,e){const r=["[data-formie-form-header]","[data-formie-form-navigation]","[data-formie-form-body]","[data-formie-form-footer]"];t.toggleAttribute("data-formie-form-hidden",e),r.forEach(n=>{t.querySelectorAll(n).forEach(i=>{const a=i;e?a.hidden=!0:a.hidden=!1})})}function Ne(t){const e=qn.get(t);typeof e=="number"&&(window.clearTimeout(e),qn.delete(t))}function hd(t,e){Zt.has(t)||Zt.set(t,t.innerHTML),t.textContent=e}function Nr(t){const e=Zt.get(t);e!==void 0&&(t.innerHTML=e,Zt.delete(t))}function pd(t,e){const r=t.querySelector("[data-formie-progress-bar]"),n=t.querySelector("[data-formie-progress-value]");r&&(r.style.width=`${e}%`,r.setAttribute("aria-valuenow",`${e}`),r.setAttribute("data-formie-progress-state",fd(e)),n&&(n.textContent=`${e}%`,n.setAttribute("data-formie-progress-value",`${e}`)))}function gd(t,e){var n;if(!e)return;const r=(t.dataset.formieLoadingIndicator||"").trim();if(r){if(e.setAttribute("data-formie-loading-indicator",r),r==="spinner"){ft(e,t,"loading",!0),Nr(e),e.removeAttribute("data-formie-loading-text");return}if(r==="text"){const i=(t.dataset.formieLoadingIndicatorText||"").trim(),a=((n=e.textContent)==null?void 0:n.trim())||"",o=i||a;e.setAttribute("data-formie-loading-text",o),hd(e,o);return}Nr(e),e.removeAttribute("data-formie-loading-text")}}function Zo(t){return Array.from(t.querySelectorAll("[data-formie-action]"))}function Xo(t,e){if(t.getAttribute("data-formie-loading")==="true")return;t.setAttribute("data-formie-loading","true"),Zo(t).forEach(n=>{"disabled"in n&&(n.disabled?n.setAttribute("data-formie-was-disabled","true"):n.removeAttribute("data-formie-was-disabled"),n.disabled=!0)}),e&&(e.setAttribute("data-formie-loading","true"),gd(t,e))}function Xt(t){if(t.removeAttribute("data-formie-loading"),Zo(t).forEach(r=>{if("disabled"in r){const n=r,i=n.getAttribute("data-formie-was-disabled")==="true";n.disabled=i}Nr(r),r.removeAttribute("data-formie-was-disabled"),r.removeAttribute("data-formie-loading"),ft(r,t,"loading",!1),r.removeAttribute("data-formie-loading-indicator"),r.removeAttribute("data-formie-loading-text")}),t.dataset.formieDisableSubmitUntilValid==="true"){const r=t;r.formieValidation&&an(t,r.formieValidation)}}function ln(t,e){const r=Qo(t),n=ud(t),i=r.findIndex(a=>a.getAttribute("data-formie-page-id")===e);if(r.forEach(a=>{a.getAttribute("data-formie-page-id")===e?(a.removeAttribute("data-formie-page-hidden"),ht(a,t,"pageHidden")):(a.setAttribute("data-formie-page-hidden","true"),ue(a,t,"pageHidden"))}),n.forEach((a,o)=>{const s=a.getAttribute("data-formie-page-id")===e,l=i>-1&&o<i;ft(a,t,"tabCurrent",s),ft(a,t,"tabComplete",l);const c=a.querySelector("[data-formie-tab-link]");c&&(ft(c,t,"tabLinkCurrent",s),s?ht(c,t,"tabLinkInactive"):ue(c,t,"tabLinkInactive")),s?a.setAttribute("aria-current","page"):a.removeAttribute("aria-current"),l?a.setAttribute("data-formie-tab-complete","true"):a.removeAttribute("data-formie-tab-complete")}),i>-1&&r.length>0){const a=dd(t,i,r.length);pd(t,a)}if(Pr(t,"pageId",e),tt(t),t.dataset.formieDisableSubmitUntilValid==="true"){const a=t;a.formieValidation&&an(t,a.formieValidation)}}function vd(t,e){var i,a,o,s;const r=(i=e.meta)==null?void 0:i.submissionUid;typeof r=="string"&&r.trim()!==""&&Pr(t,"submissionUid",r);const n=(s=(o=(a=e.meta)==null?void 0:a.session)==null?void 0:o.continuation)==null?void 0:s.continuationToken;typeof n=="string"&&n.trim()!==""?Pr(t,"continuationToken",n):Et(t,"continuationToken")}function bd(t){const e=t.getAttribute("action");e&&t.setAttribute("action",ld(e,"resumeToken"));try{const r=new URL(window.location.href);if(!r.searchParams.has("resumeToken"))return;r.searchParams.delete("resumeToken"),window.history.replaceState({},document.title,`${r.pathname}${r.search}${r.hash}`)}catch{}}function yd(t,e){var a;const r=(a=e.meta)==null?void 0:a.resumeUrl;if(typeof r!="string"||r.trim()==="")return;const n=r.trim();if(!cd(n))return;t.getAttribute("action")&&t.setAttribute("action",n);try{const o=new URL(n,window.location.href);window.history.replaceState({},document.title,`${o.pathname}${o.search}${o.hash}`)}catch{}}function Dt(t,e={}){var a;const n=t.formieValidation,i=(a=Qo(t)[0])==null?void 0:a.getAttribute("data-formie-page-id");if(Ne(t),t.reset(),e.preserveHiddenState||sn(t,!1),Et(t,"submissionId"),Et(t,"submissionUid"),Et(t,"continuationToken"),Et(t,"pageId"),bd(t),n==null||n.resetLiveState(),i){ln(t,i),t.dispatchEvent(new CustomEvent(Or("reset"),{bubbles:!0}));return}tt(t),t.dispatchEvent(new CustomEvent(Or("reset"),{bubbles:!0}))}function wd(t){var e;return t.code===sd||((e=t.meta)==null?void 0:e.resetState)===!0}function Ed(t,e){const r=e.submitData,n=new Set;let i=!1;if(Array.isArray(r)&&r.length>0){const h=r.filter(f=>typeof f=="object"&&f!==null&&"event"in f&&typeof f.event=="string");for(const f of h){const d=f.event;n.add(d),je.log("Dispatching submitData event.",{eventName:d}),d.startsWith("formie:payment:")&&(i=!0),t.dispatchEvent(new CustomEvent(d,{bubbles:!0,detail:{data:f.data}}))}}const a=e.meta||{},o=(a.paymentAction&&typeof a.paymentAction=="object"?a.paymentAction:null)||(a.paymentDecision&&typeof a.paymentDecision=="object"?a.paymentDecision.action:null),s=o?String(o.event||""):"",l=o?o.payload:void 0,c=s;return c&&!n.has(c)&&(c.startsWith("formie:payment:")&&(i=!0),t.dispatchEvent(new CustomEvent(c,{bubbles:!0,detail:{data:l}})),je.log("Dispatching fallback payment action event.",{eventName:c})),{hasPaymentFollowUpEvent:i}}function xd(t,e,r){var i,a,o,s,l;if(je.log("Applying submit result state.",{ok:e.ok,action:r,code:e.code,hasRedirect:!!((i=e.redirect)!=null&&i.url),hasSubmitData:Array.isArray(e.submitData)&&e.submitData.length>0}),wd(e)){Dt(t),je.log("Resetting state due to stale/reset marker.");return}const n=Ed(t,e);if(!e.ok&&((a=e.redirect)!=null&&a.url)&&!n.hasPaymentFollowUpEvent){je.log("Applying redirect fallback for failed result.",{url:e.redirect.url,target:e.redirect.target}),Ne(t),e.redirect.target==="new-tab"?window.open(e.redirect.url,"_blank"):(Bn(t,"redirect"),window.location.href=e.redirect.url);return}if(vd(t,e),!e.ok){je.log("Non-redirect failure; keeping current form state."),Ne(t);return}if(Array.isArray(e.clientEvents)&&e.clientEvents.length>0?Qr(t,e.clientEvents):Fo(t,r),(o=e.nextPage)!=null&&o.id){Ne(t);const h=t.formieValidation;h==null||h.resetLiveState(),ln(t,e.nextPage.id),ie(t,"formie:page:navigate:after",{pageId:e.nextPage.id}),je.log("Advanced to next page.",{nextPageId:e.nextPage.id});return}if(r==="save"){Ne(t),yd(t,e),je.log("Applied save/resume token state.");return}if(r==="submit"&&!((s=e.redirect)!=null&&s.url)){const c=jn(t,e),h=c==="message"&&Un(t);if(c==="reload"){Ne(t),Bn(t,"reload"),window.location.reload();return}if(c==="reset"){Dt(t);return}Ne(t),Dt(t,{preserveHiddenState:h});return}if(r==="submit"&&((l=e.redirect)!=null&&l.url)&&e.redirect.target==="new-tab"){const h=jn(t,e)==="message"&&Un(t);Ne(t),Dt(t,{preserveHiddenState:h});return}Ne(t)}const er=new WeakMap;function ei(t){return(t.dataset.formieSubmitAction||"").trim()}function kd(t){return(t.dataset.formieErrorMessagePosition||"top-form").trim()||"top-form"}function ti(t){return(t.dataset.formieSubmitActionMessagePosition||"").trim()}function _d(t){const e=(t.dataset.formieSubmitActionMessageTimeout||"").trim();if(!e)return null;const r=Number.parseFloat(e);return!Number.isFinite(r)||r<0?null:Math.round(r*1e3)}function cn(t){const e=t.dataset.formieSubmitActionFormHide;if(e===void 0)return!1;const r=e.trim().toLowerCase();return r==="true"||r==="1"||r===""}function Sd(t){const e=er.get(t);typeof e=="number"&&(window.clearTimeout(e),er.delete(t))}function ri(t){return t.querySelector("[data-formie-form-messages-top]")||t}function ni(t){return t.querySelector("[data-formie-form-messages-bottom]")||t}function Ad(t,e){return e==="bottom-form"?ni(t):ri(t)}function Td(t,e){return e==="top-form"?ri(t):e==="bottom-form"&&!cn(t)?ni(t):t}function oi(t){const e=kd(t),r=Ad(t,e);let n=r.querySelector("[data-formie-error-container], [data-formie-errors]");return n||(n=document.createElement("div"),n.setAttribute("data-formie-errors","true"),ue(n,t,"errors")),n.setAttribute("data-formie-error-container","true"),e==="bottom-form"?r.append(n):r.prepend(n),n}function ii(t,e){let r=e.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");return r||(r=document.createElement("div"),r.setAttribute("data-formie-error-message-container","true"),e.appendChild(r)),r.setAttribute("data-formie-message","true"),r.setAttribute("data-formie-message-error","true"),ue(r,t,"message","messageError"),r.setAttribute("role","alert"),Yr(r,Lo(Jr(t))),r}function Cd(t,e){let r=t.querySelector("[data-formie-success-container]");const n=Td(t,e);return r||(r=document.createElement("div"),r.setAttribute("data-formie-success-container","true"),ue(r,t,"successes")),e==="bottom-form"?n.append(r):n.prepend(r),r}function Id(t){return Go(t,e=>{ue(e,t,"fieldErrors")})}function ai(t){t.querySelectorAll("[data-formie-field-handle]").forEach(e=>{const r=e,n=r.querySelector("[data-formie-field-errors]"),i=Array.from(r.querySelectorAll("[data-formie-field-error]")).map(a=>a.id).filter(Boolean);ht(r,t,"fieldLayoutError"),r.removeAttribute("data-formie-field-has-error"),r.querySelectorAll("[data-formie-field-error]").forEach(a=>{a.remove()}),n&&!n.querySelector("[data-formie-field-error]")&&(n.innerHTML=""),r.querySelectorAll("input, select, textarea").forEach(a=>{const o=a;o.removeAttribute("aria-invalid"),ht(o,t,"fieldControlError"),o.removeAttribute("data-formie-input-has-error"),Oo(o,i)})}),tt(t)}function si(t){t.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach(e=>{const r=e;r.querySelectorAll("[data-formie-error]").forEach(n=>{n.remove()}),ht(r,t,"message","messageError"),r.removeAttribute("data-formie-message"),r.removeAttribute("data-formie-message-error"),r.removeAttribute("role"),r.removeAttribute("aria-live"),r.removeAttribute("aria-atomic"),r.querySelector("[data-formie-error]")||(r.innerHTML="")})}function un(t){Sd(t),t.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach(e=>{e.remove()}),t.querySelectorAll("[data-formie-success-container]").forEach(e=>{const r=e;r.querySelectorAll("[data-formie-success]").forEach(n=>{n.remove()}),ht(r,t,"message","messageSuccess"),r.removeAttribute("data-formie-message"),r.removeAttribute("data-formie-message-success"),r.removeAttribute("role"),r.removeAttribute("aria-live"),r.removeAttribute("aria-atomic"),r.querySelector("[data-formie-success]")||(r.innerHTML="")}),ei(t)==="message"&&cn(t)||sn(t,!1)}function li(t){t.querySelectorAll('[aria-invalid="true"]').forEach(e=>{e.removeAttribute("aria-invalid")})}function Ld(t,e){const r=Lo(Jr(t));Object.entries(e).forEach(([n,i])=>{var c;const a=t.querySelector(`[data-formie-field-handle="${n}"]`);if(!a)return;const o=Id(a),s=o.id&&o.id.trim()?o.id:`${n}-errors`;o.id=s,Yr(o,r),ue(a,t,"fieldLayoutError"),a.setAttribute("data-formie-field-has-error","true"),i.forEach((h,f)=>{const d=document.createElement("div");d.setAttribute("data-formie-field-error","true"),d.id=`${s}-${f+1}`,ue(d,t,"fieldError"),d.textContent=h,o.appendChild(d)});const l=(c=o.querySelector("[data-formie-field-error]"))==null?void 0:c.id;a.querySelectorAll("input, select, textarea").forEach(h=>{const f=h;f.setAttribute("aria-invalid","true"),ue(f,t,"fieldControlError"),f.setAttribute("data-formie-input-has-error","true"),l&&tn(f,l);const d=a.querySelector("[data-formie-instructions]");d!=null&&d.id&&en(f,d.id)})}),tt(t)}function Kn(t,e){const r=oi(t),n=ii(t,r);ue(r,t,"errors"),e.forEach(i=>{const a=document.createElement("div");a.setAttribute("data-formie-error","true"),a.setAttribute("role","alert"),ue(a,t,"error"),a.innerHTML=i,n.appendChild(a)})}function Md(t){if(t.ok||t.keepSubmitLoading!==!0)return!1;const e=t.meta||{},r=String(e.paymentStatus||"");return r==="actionRequired"||r==="pending"}function Rd(t,e){const r=oi(t),n=ii(t,r);ue(r,t,"errors");const i=document.createElement("div");i.setAttribute("data-formie-notice","true"),i.setAttribute("role","status"),ue(i,t,"message"),i.textContent=e,n.appendChild(i)}function Fd(t,e){return!e.message||e.nextPage||e.redirect?!1:e.action==="save"?!0:ei(t)==="message"&&ti(t)!==""}function Od(t,e){const r=ti(t);if(!r)return;const n=Cd(t,r);ue(n,t,"message","messageSuccess"),n.setAttribute("data-formie-message","true"),n.setAttribute("data-formie-message-success","true"),n.setAttribute("role","status"),n.setAttribute("aria-live","polite"),n.setAttribute("aria-atomic","true");const i=document.createElement("div");i.setAttribute("data-formie-success","true"),ue(i,t,"success"),i.innerHTML=e,n.appendChild(i),cn(t)&&sn(t,!0);const a=_d(t);if(a!==null){const o=window.setTimeout(()=>{er.delete(t),un(t)},a);er.set(t,o)}}function kt(t,e){var r;if(ai(t),si(t),un(t),li(t),e.ok){Fd(t,e)&&Od(t,e.message||"");return}if(!e.ok){if(Md(e)){const n=e.meta||{},i=String(n.paymentMessage||"").trim();i&&Rd(t,i);return}e.fieldErrors&&Ld(t,e.fieldErrors),(r=e.formErrors)!=null&&r.length?Kn(t,e.formErrors):!e.fieldErrors&&e.message&&Kn(t,[e.message]),Do(t)}}const Pd=Le("general","submit-flow");function Nd(t){return!(!t.ok&&t.stage==="validate")}function ci(t){var e;return t?!!(t.keepSubmitLoading===!0||t.ok&&((e=t.redirect)!=null&&e.url)&&t.redirect.target!=="new-tab"):!1}function ui(t){ai(t),si(t),un(t),li(t)}async function di(t){const{id:e,target:r,form:n,bus:i,validator:a,validateOnSubmit:o,action:s,submitter:l,waitForSubmitDelay:c,onRefreshTokensAfterSubmit:h,dispatchSubmitResult:f}=t;ui(n),Xo(n,l||null);let d={ok:!1,code:"SUBMIT_ERROR",message:"Submission failed.",formErrors:["Submission failed."]};try{await c(n),d=await Wo(n,s,i,{validator:a,validateOnSubmit:o}),kt(n,d),f(d),xd(n,d,s),Nd(d)&&await h(d)}catch(u){d={ok:!1,code:"SUBMIT_ERROR",message:u instanceof Error?u.message:"Submission failed.",formErrors:[u instanceof Error?u.message:"Submission failed."]},kt(n,d),f(d),Pd.warn("Submit failed with exception.",{id:e,action:s,target:r,error:u instanceof Error?u.message:u})}finally{ci(d)||Xt(n)}return d}class Dd{constructor(){this.modules=new Map}register(e,r={}){const n=this.modules.get(e.id);return n===e?!0:n&&!r.replace?(console.warn(`[formie] Module "${e.id}" is already registered. Pass { replace: true } to override the existing definition.`),!1):(this.modules.set(e.id,e),!0)}unregister(e){this.modules.delete(e)}get(e){return this.modules.get(e)||null}getAll(){return Array.from(this.modules.values())}}const zd={"address-finder":()=>M(()=>import("./address-finder.C1ltImMz.js"),__vite__mapDeps([0,1,2])).then(t=>t.addressFinderModule),"google-address":()=>M(()=>import("./google-address.CnehpJaP.js"),__vite__mapDeps([3,1,2])).then(t=>t.googleAddressModule),loqate:()=>M(()=>import("./loqate.C6PQnSmq.js"),__vite__mapDeps([4,1,2])).then(t=>t.loqateModule),"place-kit":()=>M(()=>import("./place-kit.BdaTZhSk.js"),__vite__mapDeps([5,2,6])).then(t=>t.placeKitModule)},$d={"captcha-eu":()=>M(()=>import("./captcha-eu.BZa4FKni.js"),__vite__mapDeps([7,1,2])).then(t=>t.captchaEuModule),"friendly-captcha-v1":()=>M(()=>import("./friendly-captcha-v1.OavOb8JG.js"),__vite__mapDeps([8,2])).then(t=>t.friendlyCaptchaV1Module),"friendly-captcha-v2":()=>M(()=>import("./friendly-captcha-v2.DiiUNSfB.js"),__vite__mapDeps([9,2])).then(t=>t.friendlyCaptchaV2Module),hcaptcha:()=>M(()=>import("./hcaptcha.DfkeCH6Z.js"),__vite__mapDeps([10,1,2])).then(t=>t.hcaptchaModule),"recaptcha-enterprise":()=>M(()=>import("./recaptcha-enterprise.CnBnUGHx.js"),__vite__mapDeps([11,12,1,2])).then(t=>t.recaptchaEnterpriseModule),"recaptcha-v2-checkbox":()=>M(()=>import("./recaptcha-v2-checkbox.JY0ePzKR.js"),__vite__mapDeps([13,12,1,2])).then(t=>t.recaptchaV2CheckboxModule),"recaptcha-v2-invisible":()=>M(()=>import("./recaptcha-v2-invisible.CKmKtBxa.js"),__vite__mapDeps([14,12,1,2])).then(t=>t.recaptchaV2InvisibleModule),"recaptcha-v3":()=>M(()=>import("./recaptcha-v3.D7OLAOBr.js"),__vite__mapDeps([15,12,1,2])).then(t=>t.recaptchaV3Module),snaptcha:()=>M(()=>import("./snaptcha.DhFvFcQI.js"),__vite__mapDeps([16,2])).then(t=>t.snaptchaModule),turnstile:()=>M(()=>import("./turnstile.D1up_sRS.js"),__vite__mapDeps([17,1,2])).then(t=>t.turnstileModule)},Vd={calculations:()=>M(()=>import("./calculations.Cdw7B92g.js"),__vite__mapDeps([18,19,20,2])).then(t=>t.calculationsModule),"checkbox-radio":()=>M(()=>import("./checkbox-radio.BlBiEoWm.js"),__vite__mapDeps([21,20,2])).then(t=>t.checkboxRadioModule),combobox:()=>M(()=>import("./combobox.DFW53lRa.js"),__vite__mapDeps([22,20,6,2])).then(t=>t.comboboxModule),conditions:()=>M(()=>import("./conditions.DGqMljfH.js"),__vite__mapDeps([23,19,20,2])).then(t=>t.conditionsModule),"custom-google-maps":()=>M(()=>import("./custom-google-maps.3VZuGKsq.js"),__vite__mapDeps([24,20,2])).then(t=>t.customGoogleMapsModule),"custom-link":()=>M(()=>import("./custom-link.CpwPdM8C.js"),__vite__mapDeps([25,20,2])).then(t=>t.customLinkModule),"custom-maps":()=>M(()=>import("./custom-maps.B44Ve3DH.js"),__vite__mapDeps([26,2,20,6])).then(t=>t.customMapsModule),"date-picker":()=>M(()=>import("./date-picker.ZS2svB9w.js"),__vite__mapDeps([27,20,6,2])).then(t=>t.datePickerModule),"file-upload":()=>M(()=>import("./file-upload._8B-yicd.js"),__vite__mapDeps([28,20,6,2])).then(t=>t.fileUploadModule),"upload-manager":()=>M(()=>import("./upload-manager.DKIeJvxn.js"),__vite__mapDeps([29,20,6,2])).then(t=>t.uploadManagerModule),hidden:()=>M(()=>import("./hidden.Dhv-9mVa.js"),__vite__mapDeps([30,20,2])).then(t=>t.hiddenModule),"phone-country":()=>M(()=>import("./phone-country.BWhzZqXR.js"),__vite__mapDeps([31,2,20,6,32])).then(t=>t.phoneCountryModule),"password-validation":()=>M(()=>import("./password-validation.ByiGlq7v.js"),__vite__mapDeps([33,19,20,2])).then(t=>t.passwordValidationModule),"address-country":()=>M(()=>import("./address-country.CEnK1PNj.js"),__vite__mapDeps([34,20,32,2])).then(t=>t.addressCountryModule),"address-state":()=>M(()=>import("./address-state.DEFZxp3h.js"),__vite__mapDeps([35,22,20,6,2])).then(t=>t.addressStateModule),repeater:()=>M(()=>import("./repeater.CMFzMUxl.js"),__vite__mapDeps([36,20,6,2])).then(t=>t.repeaterModule),"rich-text":()=>M(()=>import("./rich-text.BJQ9-KE8.js"),__vite__mapDeps([37,20,6,2])).then(t=>t.richTextModule),signature:()=>M(()=>import("./signature.Et7Wuj7v.js"),__vite__mapDeps([38,20,6,2])).then(t=>t.signatureModule),summary:()=>M(()=>import("./summary.Ddnf195w.js"),__vite__mapDeps([39,20,6,2])).then(t=>t.summaryModule),"survey-likert":()=>M(()=>import("./survey-likert.BCNZYGJh.js"),__vite__mapDeps([40,41,6,2])).then(t=>t.surveyLikertModule),"survey-rank":()=>M(()=>import("./survey-rank.DcWUFdcz.js"),__vite__mapDeps([42,41,20,6,2])).then(t=>t.surveyRankModule),"survey-rating":()=>M(()=>import("./survey-rating.BQRYtYy7.js"),__vite__mapDeps([43,41,20,6,2])).then(t=>t.surveyRatingModule),table:()=>M(()=>import("./table.DBo2CcXR.js"),__vite__mapDeps([44,20,6,2])).then(t=>t.tableModule),"text-limit":()=>M(()=>import("./text-limit.Bxf4Q1ZN.js"),__vite__mapDeps([45,19,20,6,2])).then(t=>t.textLimitModule)},Hd={bpoint:()=>M(()=>import("./bpoint.2SllHOTA.js"),__vite__mapDeps([46,2])).then(t=>t.bpointModule),eway:()=>M(()=>import("./eway.tYfjvgM-.js"),__vite__mapDeps([47,1,2])).then(t=>t.ewayModule),"go-cardless":()=>M(()=>import("./go-cardless.7dVJZpH4.js"),__vite__mapDeps([48,2])).then(t=>t.goCardlessModule),mollie:()=>M(()=>import("./mollie.CiX7sg7G.js"),__vite__mapDeps([49,2])).then(t=>t.mollieModule),moneris:()=>M(()=>import("./moneris.C0UnTOVn.js"),__vite__mapDeps([50,2])).then(t=>t.monerisModule),opayo:()=>M(()=>import("./opayo.UAIiyRmh.js"),__vite__mapDeps([51,6,1,2])).then(t=>t.opayoModule),paddle:()=>M(()=>import("./paddle.DHSkygHS.js"),__vite__mapDeps([52,1,2])).then(t=>t.paddleModule),paypal:()=>M(()=>import("./paypal.DrW-4Jzr.js"),__vite__mapDeps([53,6,1,2])).then(t=>t.paypalModule),payway:()=>M(()=>import("./payway.Du-OByOx.js"),__vite__mapDeps([54,6,1,2])).then(t=>t.paywayModule),square:()=>M(()=>import("./square.Dh4knbJx.js"),__vite__mapDeps([55,1,2])).then(t=>t.squareModule),stripe:()=>M(()=>import("./stripe.N7KAxuoK.js"),__vite__mapDeps([56,6,1,2])).then(t=>t.stripeModule)},qd={...Vd,...zd,...$d,...Hd},Er=new Map,Ie=Le("general","loader"),Bd=new Function("src","return import(src);");async function zt(t,e,r,n){await t(Wc(r),n),await t(Kc(e,r),n)}function fi(t){return!!t&&typeof t=="object"&&typeof t.id=="string"&&typeof t.setup=="function"&&typeof t.match=="function"}async function jd(t,e){const r=qd[t];return r?(Er.has(t)||Er.set(t,(async()=>{try{const n=await r();return fi(n)?(e.registry.register(n),n):null}catch(n){return console.error("[formie] Failed to load builtin module:",t,n),Ie.warn("Failed loading builtin module.",{moduleId:t,error:n}),null}})()),Er.get(t)||null):null}async function Ud(t){try{const e=await Bd(t),r=(e==null?void 0:e.default)||(e==null?void 0:e.formieModule)||null;return fi(r)?r:null}catch(e){return console.error("[formie] Failed to load module from src:",t,e),Ie.warn("Failed loading module from src.",{src:t,error:e}),null}}async function Kd(t,e){const r=e.registry.get(t.id);if(r)return r;const n=await jd(t.id,e);if(n)return n;if(t.src){const i=await Ud(t.src);if(i)return e.registry.register(i),i}return null}function xr(t){var e;return typeof((e=window.CSS)==null?void 0:e.escape)=="function"?window.CSS.escape(t):t.replace(/["\\]/g,"\\$&")}function $t(t,e){return t.matches(e)?[t,...Array.from(t.querySelectorAll(e))]:Array.from(t.querySelectorAll(e))}function Wd(t,e){const r=e.setupContext.root,n=e.setupContext.form,i=t.targetType,a=t.targetId;return i==="selector"?$t(r,a).map(o=>({scope:i,element:o})):i==="field"?$t(r,`[data-formie-field-handle="${xr(a)}"]`).map(o=>({scope:i,element:o})):i==="page"?$t(r,`[data-formie-page-id="${xr(a)}"]`).map(o=>({scope:i,element:o})):i==="button"?$t(r,`[data-formie-action="${xr(a)}"]`).map(o=>({scope:i,element:o})):[{scope:"form",element:n||r}]}function Gd(t,e){return(t.targets&&t.targets.length>0?t.targets:[{targetType:"form",targetId:"form"}]).flatMap(n=>Wd(n,e))}async function Jd(t,e){var n,i;const r=[];Ie.log("Loading module manifest.",{manifestCount:t.length});for(const a of t){const o=await Kd(a,e);if(!o){Ie.warn("Skipping manifest item (definition not resolved).",{moduleId:a.id,src:a.src});continue}const s=Gd(a,e);Ie.log("Resolved module targets.",{moduleId:o.id,targets:a.targets||[],targetCount:s.length}),s.length===0&&o.kind==="address"&&console.warn(`[formie] Address module "${a.id}" skipped: no target element found for fieldHandle="${((i=(n=a.targets)==null?void 0:n.find(l=>l.targetType==="field"))==null?void 0:i.targetId)??"?"}". Check that the Address field exists in the rendered form.`);for(const l of s){const c={...e.matchContext,target:l.element,scope:l.scope,manifestItem:a};if(!o.match(c)){o.kind==="address"&&console.warn(`[formie] Address module "${o.id}" skipped: target element does not contain [data-formie-address-autocomplete-input]. Enable the Auto-Complete subfield.`),Ie.log("Module target did not match predicate.",{moduleId:o.id,scope:l.scope});continue}const h=a.config||e.setupContext.options,f=o.id,d={moduleId:o.id,moduleKind:o.kind,target:l.element,scope:l.scope,options:h,manifestItem:a};await zt(e.setupContext.emit,f,"before-setup",d);let u=null;try{const g=await o.setup({...e.setupContext,target:l.element,scope:l.scope,options:h});g&&(u=g)}catch(g){console.error(`[formie] Module "${o.id}" setup failed:`,g),Ie.warn("Module setup failed.",{moduleId:o.id,scope:l.scope,error:g})}await zt(e.setupContext.emit,f,"after-setup",{...d,instanceCreated:!!u}),u&&(Ie.log("Module instance created.",{moduleId:o.id,scope:l.scope}),r.push({...u,destroy:async()=>{Ie.log("Destroying module instance.",{moduleId:o.id,scope:l.scope}),await zt(e.setupContext.emit,f,"before-destroy",d),await u.destroy(),await zt(e.setupContext.emit,f,"after-destroy",d),Ie.log("Module instance destroyed.",{moduleId:o.id,scope:l.scope})}}))}}return Ie.log("Module manifest processing complete.",{instanceCount:r.length}),r}const Yd="formie:formStartedAt:";function Qd(t){var o;const e=t.querySelector('input[name="formStartedAt"]');if(!e)return;const r=t.querySelector('input[name="renderId"]'),n=((o=r==null?void 0:r.value)==null?void 0:o.trim())??"",i=n?`${Yd}${n}`:null;let a=i?sessionStorage.getItem(i):null;a||(a=String(Date.now()),i&&sessionStorage.setItem(i,a)),e.value=a}const Zd=new Set(["action","redirect","requestToken","renderId","formStartedAt","submitAction","pageId","draftContextToken","draftContext","continuationToken"]);function Dr(t,e){if(t==null)return String(t);if(typeof t=="string")return JSON.stringify(t);if(typeof t=="number"||typeof t=="boolean")return String(t);if(typeof t=="function")return"[function]";if(typeof File<"u"&&t instanceof File)return`[file:${t.name}:${t.size}:${t.type}]`;if(typeof Blob<"u"&&t instanceof Blob)return`[blob:${t.size}:${t.type}]`;if(Array.isArray(t))return`[${t.map(r=>Dr(r,e)).join(",")}]`;if(typeof t=="object"){if(e.has(t))return"[circular]";e.add(t);const r=Object.entries(t).sort(([n],[i])=>n.localeCompare(i)).map(([n,i])=>`${JSON.stringify(n)}:${Dr(i,e)}`);return e.delete(t),`{${r.join(",")}}`}return JSON.stringify(String(t))}function Xd(t){return Dr(t,new WeakSet)}function ef(t,e){if(!t)return!1;const r=t.endsWith("[]")?t.slice(0,-2):t;return Su(r,e)?!1:!Zd.has(r)}function Wn(t){const e=Array.from(new FormData(t).entries()).filter(([r])=>ef(String(r||""),t));return Xd(e)}function tf(t,e={}){let r=null,n=!1,i=!1,a=null,o=null,s=null;const l=()=>{a!==null&&(window.cancelAnimationFrame(a),a=null),o!==null&&(window.clearTimeout(o),o=null),s!==null&&(window.clearTimeout(s),s=null)},c=()=>n?(i=Wn(t)!==r,i):!1,h=()=>{r=Wn(t),n=!0,i=!1},f=()=>{l(),n=!1,a=window.requestAnimationFrame(()=>{a=null,s=window.setTimeout(()=>{s=null,h()},0)})},d=()=>{o!==null&&window.clearTimeout(o),o=window.setTimeout(()=>{o=null,c()},120)},u=g=>{e.shouldWarn&&!e.shouldWarn()||c()&&(g.preventDefault(),g.returnValue="")};return t.addEventListener("input",d),t.addEventListener("change",d),window.addEventListener("beforeunload",u),f(),{captureBaseline:h,scheduleBaselineCapture:f,refreshDirtyState:c,destroy:()=>{l(),t.removeEventListener("input",d),t.removeEventListener("change",d),window.removeEventListener("beforeunload",u)}}}function rf(t){return t.hasAttribute("data-formie-conditionally-hidden")||!!t.closest("[data-formie-conditionally-hidden]")||t.hasAttribute("data-formie-page-hidden")||!!t.closest("[data-formie-page-hidden]")}function nf(t,e){const r=t.querySelectorAll(`[data-formie-action="${e}"]`);return Array.from(r).some(n=>!rf(n))}function of(t){const{final:e}=ar(t);return"submit"}function af(t){const e=of(t);return!nf(t,e)}function sf(t){const e=r=>{if(r.key!=="Enter"||r.defaultPrevented)return;const n=r.target;(n instanceof HTMLInputElement||n instanceof HTMLSelectElement)&&(n instanceof HTMLInputElement&&(n.type==="button"||n.type==="submit"||n.type==="reset"||n.type==="file")||af(t)&&r.preventDefault())};return t.addEventListener("keydown",e,!0),()=>{t.removeEventListener("keydown",e,!0)}}const it='[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])',lf=300,cf="/actions/formie/server/forms/render",Gn="/api",uf="/actions/formie/server/forms/refresh-tokens",df="/actions/formie/server/submissions/submit",ff="/actions/formie/server/submissions/set-page",mf="/actions/formie/server/submissions/clear-submission",hf="/actions/formie/file-upload/hydrate",oe=Le("general","client"),Jn=new Set;function Tt(t,e){if(t==null||t==="")return e;const r=t.toLowerCase();return!(r==="false"||r==="0"||r==="off")}function zr(t){return t.formieRefreshTokens!=null?Tt(t.formieRefreshTokens,!0):t.formieStaticCache!=null?Tt(t.formieStaticCache,!0):!1}function at(t){const e=t instanceof HTMLElement?t.dataset:{};return{mode:"server-rendered",transport:e.formieTransport||"rest",formHandle:e.formieHandle,endpoint:e.formieEndpoint,staticCache:zr(e),autoVisible:Tt(e.formieAutoVisible,!0),compatibility:Tt(e.formieCompatibility,!1)}}function sr(t){return t||"server-rendered"}function lr(t){return t||"rest"}function qt(t){return t instanceof HTMLFormElement?t:t.querySelector("form")}function pf(t,e){Jn.has(t)||(Jn.add(t),oe.warn(e))}function mi(t,e){if(!t)return t;try{return new URL(t).toString()}catch{}if(!e)return t;try{return new URL(t,e).toString()}catch{return t}}function mt(t,e){const r=(t||"").trim();return r?r.includes("/actions/")?r:mi(e,r):e}function gf(t,e){return mt(t.endpoint||e.dataset.formieEndpoint,cf)}function vf(t,e){const r=(t.endpoint||e.dataset.formieEndpoint||"").trim();return r?r.includes("/graphql")||r.endsWith("/api")||r.includes("/actions/graphql/")?r:mi(Gn,r):Gn}function dn(t,e){return mt(e.dataset.formieRefreshTokensEndpoint||t.endpoint||e.dataset.formieEndpoint,uf)}function Yn(t,e){if(!t)return e;try{const r=new URL(t,window.location.origin),n=new URL(e,window.location.origin);return r.searchParams.forEach((i,a)=>{n.searchParams.has(a)||n.searchParams.set(a,i)}),n.toString()}catch{return e}}function bf(t,e,r){const n=r.endpoint||t.dataset.formieEndpoint,i=mt(n,df),a=e.getAttribute("action");e.setAttribute("action",Yn(a,i)),e.querySelectorAll("[data-formie-tab-link]").forEach(o=>{const s=o.getAttribute("href"),l=mt(n,ff);o.setAttribute("href",Yn(s,l))}),e.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach(o=>{o.setAttribute("data-formie-file-upload-hydrate-endpoint",mt(n,hf))})}function fn(t,e){if(t==="graphql"&&e!=="server-rendered")throw new Error(`Formie ${e} mode does not support GraphQL transport yet.`)}function mn(t){if(t==null)return!1;const e=t.trim().toLowerCase();return e==="true"||e==="1"||e===""}function yf(t){return Tt(t.dataset.formieAutomaticSubmissionState,!0)}function wf(t,e,r){return mt(r.dataset.formieClearSubmissionEndpoint||t.endpoint||e.dataset.formieEndpoint,mf)}function Ef(t){return mn(t.dataset.formieUnloadWarning)}function Qn(t,e){t.setAttribute("data-formie-internal-navigation",e)}function kr(t){t.removeAttribute("data-formie-internal-navigation")}function Zn(t){return t.getAttribute("data-formie-internal-navigation")!==null}function Xn(t,e){if(!t)return!1;try{return new URL(t,window.location.origin).searchParams.has(e)}catch{return!1}}function xf(t){return Xn(window.location.href,"resumeToken")||Xn(t.getAttribute("action"),"resumeToken")}function kf(t){return t instanceof MouseEvent?t.button===0&&!t.metaKey&&!t.ctrlKey&&!t.shiftKey&&!t.altKey:!0}function _f(t,e=0){if(!t)return e;const r=Number.parseInt(t,10);return Number.isFinite(r)?r:e}function Sf(t){return Math.max(0,_f(t.dataset.formieSubmitDelay,lf))}function Bt(t){return mn(t.dataset.formieValidationOnSubmit)}async function $r(t){const e=Sf(t);e<1||await new Promise(r=>{window.setTimeout(r,e)})}function eo(t,e){var n;const r=(n=t==null?void 0:t.getAttribute(e))==null?void 0:n.trim();if(!r)return null;try{return JSON.parse(r)}catch(i){return console.error(`[formie] Failed to parse ${e}.`,i),null}}function to(t,e){const r=e||(t instanceof HTMLFormElement?t:null);if(!r)return null;const n=eo(r,"data-formie-modules"),i=eo(r,"data-formie-theme");return!n&&!i?null:{modules:n||void 0,theme:i||void 0}}function Af(t){if(!(t instanceof HTMLElement))return!0;if(!t.isConnected||t.hidden||t.closest("[hidden]"))return!1;const e=window.getComputedStyle(t);return e.display==="none"||e.visibility==="hidden"?!1:t.getClientRects().length>0}function Tf(t,e){return e===document?!0:e instanceof Element?e===t||e.contains(t):!0}function fe(t){var a;const e=t,r=e.id?`#${e.id}`:"",n=(a=e.dataset)!=null&&a.formieHandle?`[handle="${e.dataset.formieHandle}"]`:"";return`${e.tagName?e.tagName.toLowerCase():"element"}${r}${n}`}function hn(t,e){var r,n;if(e){if((r=e.csrf)!=null&&r.param&&((n=e.csrf)!=null&&n.token)){let i=t.querySelector(`input[name="${e.csrf.param}"]`);i?i.value=e.csrf.token:(i=document.createElement("input"),i.type="hidden",i.name=e.csrf.param,i.value=e.csrf.token,i.setAttribute("autocomplete","off"),i.setAttribute("data-formie-csrf",""),t.prepend(i))}if(e.requestToken){const i=t.querySelector('input[name="requestToken"]');i&&(i.value=e.requestToken)}if(e.renderId){const i=t.querySelector('input[name="renderId"]');i&&(i.value=e.renderId)}e.captchas&&typeof e.captchas=="object"&&Object.values(e.captchas).forEach(i=>{if(!i||typeof i!="object")return;const a=i;if(!a.sessionKey)return;const o=t.querySelector(`input[name="${a.sessionKey}"]`);o&&typeof a.value=="string"&&(o.value=a.value)})}}async function Cf(t,e){const r=sr(e.mode),n=lr(e.transport);if(r!=="server-rendered")return null;if(e.payload)return e.payload.html&&(t.innerHTML=e.payload.html),e.payload;fn(n,r);const i=!!qt(t),a=e.formHandle||t.dataset.formieHandle;if(i||!a)return null;const o={mode:r,endpoint:e.endpoint,locale:e.locale,siteId:e.siteId,theme:e.theme,themeConfig:e.themeConfig},s=n==="graphql"?vf(e,t):gf(e,t),l=n==="graphql"?await Iu(s,a,o):await Cu(s,a,{...o,endpoint:s});return l!=null&&l.html&&(t.innerHTML=l.html),l}async function hi(t,e,r){if(e.refreshTokens===!1)return;fn(lr(e.transport),sr(e.mode));const n=e.formHandle||t.dataset.formieHandle;if(!n)return;const i=dn(e,t),a=r.querySelector('input[name="renderId"]'),o=(a==null?void 0:a.value)||void 0,s=await nn(i,n,o);hn(r,s),ie(t,"formie:refresh-tokens:refreshed",s)}function If(t,e,r,n,i,a){const o=String(e.dataset.formieSubmitMethod||"").trim().toLowerCase(),s=wf(r,t,e);let l=!1;const c=e.querySelectorAll("[data-formie-action]"),h=u=>{if(u){e.setAttribute("data-formie-pending-action",u);return}e.removeAttribute("data-formie-pending-action")};if(Ef(e)){const u=tf(e,{shouldWarn:()=>!Zn(e)}),g=m=>{if(!(m instanceof CustomEvent))return;const p=m.detail;p!=null&&p.ok&&p.action==="save"&&u.scheduleBaselineCapture()},x=()=>{u.scheduleBaselineCapture()};t.addEventListener("formie:submit:result",g),e.addEventListener("formie:state:reset",x),a.push(()=>{t.removeEventListener("formie:submit:result",g),e.removeEventListener("formie:state:reset",x),u.destroy()})}if(c.forEach(u=>{const g=x=>{const m=x.currentTarget.getAttribute("data-formie-action"),p=e.querySelector('input[name="submitAction"]');h(m),m&&p&&(p.value=m)};u.addEventListener("click",g),a.push(()=>{u.removeEventListener("click",g)})}),e.querySelectorAll("[data-formie-tab-link]").forEach(u=>{const g=async x=>{if(o!=="ajax"){kf(x)&&Qn(e,"set-page");return}x.preventDefault();const m=x.currentTarget,p=m==null?void 0:m.getAttribute("data-formie-page-id"),v=m==null?void 0:m.getAttribute("href");if(!(!p||!v)){ln(e,p),ie(t,"formie:page:navigate",{pageId:p,href:v});try{const w=await Lu(v,e,p);ie(t,"formie:page:navigate:after",{pageId:p,href:v,response:w})}catch(w){console.error("[formie] Failed to persist page navigation state.",w),ie(t,"formie:page:navigate:error",{pageId:p,href:v,error:w})}}};u.addEventListener("click",g),a.push(()=>{u.removeEventListener("click",g)})}),!yf(e)){let u=!1;const g=()=>{u||Zn(e)||xf(e)||(u=!0,Mu(s,e))};window.addEventListener("pagehide",g),window.addEventListener("beforeunload",g),a.push(()=>{window.removeEventListener("pagehide",g),window.removeEventListener("beforeunload",g)})}const d=async u=>{if(l)return;const g=o==="ajax";if(u.preventDefault(),e.getAttribute("data-formie-loading")==="true"){if(!(e.getAttribute("data-formie-internal-resubmit")==="true"))return;e.removeAttribute("data-formie-internal-resubmit")}else e.removeAttribute("data-formie-internal-resubmit");const m=u.submitter,p=m==null?void 0:m.getAttribute("data-formie-action"),v=e.getAttribute("data-formie-pending-action"),w=e.querySelector('input[name="submitAction"]'),b=p||v||(w==null?void 0:w.value)||"submit";let k=null,C=!1;try{if(g)k=await di({target:t,form:e,bus:n,validator:i,validateOnSubmit:Bt(e),action:b,submitter:m,waitForSubmitDelay:$r,onRefreshTokensAfterSubmit:async()=>{await hi(t,r,e)},dispatchSubmitResult:O=>{ie(t,"formie:submit:result",O)}});else{if(ui(e),Xo(e,m),await $r(e),k=await Wo(e,b,n,{validator:i,validateOnSubmit:Bt(e),preflightOnly:!0}),k.ok){Fo(e,b),l=!0,Qn(e,"submit"),h(null);let O=!1;const $=()=>{if(O=!0,l=!1,kr(e),Xt(e),i&&Bt(e)){const{scope:j,final:ee}=ar(e),F=i.submit(ee?e:j,{final:ee});F.length>0&&kt(e,{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:i.config.errorMessage||"Validation failed.",fieldErrors:i.getFieldErrors(F),formErrors:[i.config.errorMessage||"Validation failed."]})}};if(typeof e.requestSubmit=="function"){e.addEventListener("invalid",$,!0);try{e.requestSubmit()}finally{e.removeEventListener("invalid",$,!0)}}else e.submit();if(O)return;C=!0;return}kt(e,k),ie(t,"formie:submit:result",k),kr(e)}}catch(O){l=!1,k={ok:!1,code:"SUBMIT_ERROR",message:O instanceof Error?O.message:"Submission failed.",formErrors:[O instanceof Error?O.message:"Submission failed."]},kt(e,k),ie(t,"formie:submit:result",k),kr(e)}finally{h(null),!g&&!C&&!ci(k)&&Xt(e)}};e.addEventListener("submit",d),a.push(()=>{e.removeEventListener("submit",d)})}async function Lf(t,e,r){if(e.refreshTokens===!1||!e.staticCache)return;fn(lr(e.transport),sr(e.mode));const n=e.formHandle||t.dataset.formieHandle,i=dn(e,t),a=r==null?void 0:r.querySelector('input[name="renderId"]'),o=(a==null?void 0:a.value)||void 0;if(!n)return;const s=await nn(i,n,o);!s||!r||(hn(r,s),ie(t,"formie:refresh-tokens:after",s))}function Mf(){const t=new Map,e=new Dd,r=new Map,n=new Map,i=["prepare","normalize","validate","screen","authorize","dispatch","finalize"],a=async m=>{const p=n.get(m);if(p){await p;return}const v=(async()=>{var k;oe.log("Unmount requested.",{target:fe(m)});const w=r.get(m);w&&(w(),r.delete(m));const b=t.get(m);if(!b){oe.log("Unmount skipped (no mounted state).",{target:fe(m)});return}ie(m,"formie:unmount:before",{id:b.instance.id}),b.unbinds.forEach(C=>{C()}),b.unbinds=[],(k=b.validator)==null||k.destroy(),b.validator=null;for(const C of b.modules)await C.destroy();b.modules=[],b.bus.clear(),t.delete(m),ie(m,"formie:unmount:after",{id:b.instance.id}),oe.log("Unmount complete.",{id:b.instance.id,target:fe(m)})})().finally(()=>{n.delete(m)});n.set(m,v),await v},o=async(m,p)=>{oe.log("Mount requested.",{target:fe(m),mode:p.mode,autoVisible:p.autoVisible});const v=r.get(m);v&&(v(),r.delete(m));const w=t.get(m);if(w)return oe.log("Mount skipped (already mounted).",{id:w.instance.id,target:fe(m)}),w.instance;const b=new Eu,k=[],C=(m==null?void 0:m.id)||`formie-${t.size+1}`,O=at(m),$={...O,...p,mode:sr(p.mode??O.mode),transport:lr(p.transport??O.transport)},j=Uc($.compatibility);if($.mode!=="server-rendered"&&!qt(m))throw new Error(`Formie ${$.mode} mode is not implemented yet in the browser client.`);const ee=await Cf(m,$),F=qt(m);$.staticCache=p.staticCache??zr(F?F.dataset:m.dataset);const Q=to(m,F),H=ee||Q?{...ee||{},...Q||{}}:null,y=H==null?void 0:H.theme,A={},P=((H==null?void 0:H.modules)||[]).filter(N=>!!(N!=null&&N.id)&&!!(N!=null&&N.type));oe.log("Resolved mount payload.",{target:fe(m),hasRenderPayload:!!ee,hasEmbeddedPayload:!!Q,moduleCount:P.length});const U=zn(m,y,F),z=F?new rd(F,{live:mn(F.dataset.formieValidationOnFocus),errorAriaLive:Jr(F),errorMessage:F.dataset.formieErrorMessage||"",fieldContainerErrorClass:U.fieldLayoutError||[],inputErrorClass:U.fieldControlError||[],messagesClass:U.fieldErrors||[],messageClass:U.fieldError||[]}):null;if(F&&z){const N=F;N.formieValidation=z,A.validation=z;const X={validator:z,addValidator:z.addValidator.bind(z),removeValidator:z.removeValidator.bind(z)};ie(F,"formie:validator:ready",X),ie(m,"formie:validator:ready",X)}F&&(Qd(F),$.themeConfig&&typeof $.themeConfig=="object"&&F.setAttribute("data-formie-theme-config",JSON.stringify($.themeConfig)),$.theme&&$.theme!=="formie"&&F.setAttribute("data-formie-frontend-theme",$.theme),(ee||$.endpoint||m.dataset.formieEndpoint)&&bf(m,F,$),$.mode==="server-rendered"&&wu(F)&&(yu(F),Do(F)),tt(F)),Object.keys(U).length&&ie(m,"formie:theme:applied",{hasClasses:!0});const J=await Jd(P,{registry:e,matchContext:{root:m,form:F,mode:$.mode},setupContext:{formId:C,root:m,form:F,target:m,scope:"form",state:A,on:(N,X)=>b.on(N,X),emit:(N,X)=>(ie(m,N,X),b.emitSafe(N,X).then(ne=>{ne.failed.length>0&&oe.warn("Lifecycle listeners failed.",{eventName:N,failed:ne.failed.length})}))}});oe.log("Module setup complete.",{target:fe(m),moduleInstances:J.length});const q={id:C,root:m,submit:async(N="submit")=>{if(oe.log("Submit requested.",{id:C,target:fe(m),action:N}),!F)return{ok:!1,code:"FORM_NOT_FOUND",message:"No form element found for mount target.",formErrors:["No form element found for mount target."]};const X=F.querySelector('input[name="submitAction"]');if(X&&(X.value=N),F.getAttribute("data-formie-loading")==="true")return{ok:!1,code:"SUBMIT_IN_PROGRESS",message:"Submission already in progress.",formErrors:[]};const ne=F.querySelector(`[data-formie-action="${N}"]`),Z=await di({id:C,target:m,form:F,bus:b,validator:z,validateOnSubmit:Bt(F),action:N,submitter:ne,waitForSubmitDelay:$r,onRefreshTokensAfterSubmit:async()=>{await hi(m,$,F)},dispatchSubmitResult:ae=>{ie(m,"formie:submit:result",ae)}});return oe.log("Submit completed.",{id:C,action:N,ok:Z.ok,code:Z.code,message:Z.message}),Z},destroy:async()=>{await a(m)},on:(N,X)=>b.on(N,X)};F&&(Xc({target:m,form:F,validatorDetail:z?{validator:z,addValidator:z.addValidator.bind(z),removeValidator:z.removeValidator.bind(z)}:null,options:j,unbinds:k}),Zc({target:m,form:F,instance:q,options:j,unbinds:k})),F&&(If(m,F,$,b,z,k),z&&(k.push(ad(F,z,m)),k.push(sf(F))),await Lf(m,$,F),F.dispatchEvent(new CustomEvent("formie:state:reset")),window.setTimeout(()=>{F.dispatchEvent(new CustomEvent("formie:state:reset"))},350)),i.forEach(N=>{const X=b.on(`formie:stage:${N}:before`,async de=>{ie(m,`formie:stage:${N}:before`,de)}),ne=b.on(`formie:stage:${N}:before`,async de=>{for(const pe of J)pe.onBeforeStage&&await pe.onBeforeStage(de)}),Z=b.on(`formie:stage:${N}:after`,async de=>{ie(m,`formie:stage:${N}:after`,de)}),ae=b.on(`formie:stage:${N}:after`,async de=>{const pe=de;for(const _e of J)_e.onAfterStage&&await _e.onAfterStage(pe,pe.result)});k.push(X,ne,Z,ae)});const I=b.on("formie:submit:before",async N=>{ie(m,"formie:submit:before",N)}),T=b.on("formie:submit:after",async N=>{ie(m,"formie:submit:after",N)}),K=b.on("formie:submit:final:before",async N=>{ie(m,"formie:submit:final:before",N)}),te=b.on("formie:submit:final:after",async N=>{ie(m,"formie:submit:final:after",N)});return k.push(I,T,K,te),t.set(m,{options:$,bus:b,form:F,validator:z,modules:J,unbinds:k,instance:q}),ie(m,"formie:mount:after",{id:C,mode:$.mode}),F instanceof HTMLFormElement&&uu(F),oe.log("Mount complete.",{id:C,target:fe(m),mode:$.mode}),q},s=(m,p)=>{var w;if(!p.autoVisible||Af(m)||typeof IntersectionObserver>"u")return o(m,p);if(t.has(m))return Promise.resolve(((w=t.get(m))==null?void 0:w.instance)||null);if(r.has(m))return oe.log("Mount deferred (already waiting visibility).",{target:fe(m)}),Promise.resolve(null);const v=new IntersectionObserver(b=>{b.some(C=>C.target===m&&C.isIntersecting)&&(v.disconnect(),r.delete(m),oe.log("Visibility reached, proceeding mount.",{target:fe(m)}),o(m,{...p,autoVisible:!1}))},{threshold:.01});return v.observe(m),r.set(m,()=>{v.disconnect()}),oe.log("Mount deferred until visible.",{target:fe(m)}),Promise.resolve(null)};return{mount:o,unmount:a,update:async(m,p)=>{var k,C,O;const v=t.get(m);if(!v)return o(m,{...at(m),...p,mode:p.mode||"server-rendered"});v.options={...v.options,...p};const w=((k=p.payload)==null?void 0:k.theme)||((C=v.options.payload)==null?void 0:C.theme)||((O=to(m,v.form))==null?void 0:O.theme),b=zn(m,w,v.form);return v.validator&&(v.validator.config.fieldContainerErrorClass=b.fieldLayoutError||[],v.validator.config.inputErrorClass=b.fieldControlError||[],v.validator.config.messagesClass=b.fieldErrors||[],v.validator.config.messageClass=b.fieldError||[]),Object.keys(b).length&&ie(m,"formie:theme:applied",{hasClasses:!0,reason:"update"}),v.instance},getInstance:m=>{var p;return((p=t.get(m))==null?void 0:p.instance)||null},refreshForCache:async m=>{pf("refreshForCache","Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");let p=null;if(typeof m=="string"){const ee=document.getElementById(m);ee?p=ee:p=document.querySelector(`[data-formie-form-id="${m}"]`)}else p=m;if(!p){oe.warn("refreshForCache target not found.",{targetOrId:m});return}const v=t.get(p),w=qt(p),b=(v==null?void 0:v.options)||at(p);if(!w){oe.warn("refreshForCache found no form element for target.",{target:fe(p)});return}const k=b.formHandle||p.dataset.formieHandle||w.dataset.formieHandle,C=dn(b,p),O=w.querySelector('input[name="renderId"]'),$=(O==null?void 0:O.value)||void 0;if(!k){oe.warn("refreshForCache found no form handle for target.",{target:fe(p)});return}const j=await nn(C,k,$);j&&(hn(w,j),ie(p,"formie:refresh-tokens:after",j))},registerModule:(m,p)=>e.register(m,p),unregisterModule:m=>{e.unregister(m)},getRegisteredModules:()=>e.getAll(),scan:async m=>{const p=m||document,v=Array.from(p.querySelectorAll(it));oe.log("Scan started.",{scope:p===document?"document":p,targetCount:v.length});const b=(await Promise.all(v.map(k=>{const C=at(k);return s(k,C)}))).filter(k=>!!k);return oe.log("Scan finished.",{mountedCount:b.length,deferredCount:v.length-b.length}),b},observe:m=>{if(typeof MutationObserver>"u")return()=>{};const p=m||document;oe.log("Observer started.",{scope:p===document?"document":p});const v=new MutationObserver(w=>{w.forEach(b=>{b.addedNodes.forEach(k=>{k instanceof Element&&(k.matches(it)&&(oe.log("Observer detected new root.",{target:fe(k)}),s(k,at(k))),k.querySelectorAll(it).forEach(C=>{oe.log("Observer detected new nested root.",{target:fe(C)}),s(C,at(C))}))}),b.removedNodes.forEach(k=>{k instanceof Element&&(t.has(k)&&(oe.log("Observer detected removed root.",{target:fe(k)}),a(k)),k.querySelectorAll(it).forEach(C=>{t.has(C)&&(oe.log("Observer detected removed nested root.",{target:fe(C)}),a(C))}))})})});return v.observe(p,{childList:!0,subtree:!0}),()=>{v.disconnect(),oe.log("Observer stopped."),r.forEach((b,k)=>{Tf(k,p)&&(b(),r.delete(k))});const w=[];p instanceof Element&&p.matches(it)&&w.push(p),p.querySelectorAll(it).forEach(b=>{w.push(b)}),w.forEach(b=>{t.has(b)&&a(b)})}}}}const pn=2e3,Um=5e3,Km=5e3,Wm=12e4;async function gn(t){await new Promise(e=>{window.setTimeout(e,Math.max(t,0))})}async function Gm(t,{timeoutMs:e=5e3,intervalMs:r=30}={}){const n=Date.now();for(;Date.now()-n<e;){const i=t();if(i)return i;await gn(r)}throw new Error("Timed out waiting for async condition.")}function pi(t,e){let r=null;return(...n)=>{r!==null&&window.clearTimeout(r),r=window.setTimeout(()=>{t(...n)},Math.max(e,0))}}function Jm(t){const e=String(t||"asyncDefer").toLowerCase();return{async:e.includes("async"),defer:e.includes("defer")}}function gi(t,e){const r=Array.from(t.querySelectorAll(`input[name="${e}"], textarea[name="${e}"]`));for(const n of r){const i=String(n.value||"").trim();if(i!=="")return i}return""}function Vr(t,e){return e.some(r=>gi(t,r)!=="")}function Rf(t,e){e.forEach(r=>{Array.from(t.querySelectorAll(`input[name="${r}"], textarea[name="${r}"]`)).forEach(i=>{i.value=""})})}function vi(t,e,{value:r="",container:n}={}){let i=t.querySelector(`input[name="${e}"]`);if(!i){i=document.createElement("input"),i.type="hidden",i.name=e;const a=n||(t instanceof HTMLElement?t:null);a==null||a.appendChild(i)}return i.value=r,i}async function bi(t,e,r){if(Vr(t,e))return!0;const n=Date.now()+Math.max(r,0);for(;Date.now()<n;)if(await gn(120),Vr(t,e))return!0;return!1}const Ff=new Set(["handle","placeholderSelector","errorMessage","sessionKey","value"]),Of="[data-formie-captcha-error-container]",Pf=["formie:page:navigate","formie:page:navigate:after","formie:submit:result"],Nf=new Set(["formie:page:navigate","formie:page:navigate:after"]);function _t(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function tr(t,e){return t instanceof HTMLElement&&t.matches(e)?[t,...Array.from(t.querySelectorAll(e))]:Array.from(t.querySelectorAll(e))}function Hr(t){if(!(t instanceof HTMLElement)||!t.isConnected||t.hidden||t.closest("[hidden]")||t.closest("[data-formie-page-hidden]")||t.closest('[aria-hidden="true"]'))return!1;const e=window.getComputedStyle(t);return e.display!=="none"&&e.visibility!=="hidden"&&t.getClientRects().length>0}function _r(t,e){const r=tr(t,e);return r.find(n=>Hr(n))||r[0]||null}function Df(t){t.innerHTML="";const e=document.createElement("div");return t.appendChild(e),e}function qr(t){var e;(e=t==null?void 0:t.querySelector(Of))==null||e.remove()}function zf(t,e,r){if(!t)return;qr(t);const n=document.createElement("div");n.setAttribute("data-formie-captcha-error-container",""),n.setAttribute("aria-live","polite"),n.setAttribute("aria-atomic","true"),ue(n,r||t,"fieldErrors");const i=document.createElement("div");i.setAttribute("data-formie-captcha-error",""),i.setAttribute("role","alert"),ue(i,r||t,"fieldError"),i.textContent=e,n.appendChild(i),t.appendChild(n)}function $f(t){const e=t instanceof CustomEvent?t.detail:null;return!e||typeof e!="object"?null:e}function Vf(t,e){if(!(t!=null&&t.captchas)||typeof t.captchas!="object")return null;const r=t.captchas[e];return!r||typeof r!="object"?null:r}function Hf(t,e,r,n){const i=new Set,a=()=>{const c=tr(t,e),h=new Set(c.filter(f=>Hr(f)));c.forEach(f=>{h.has(f)&&!i.has(f)&&(i.add(f),r(f))}),Array.from(i).forEach(f=>{h.has(f)||(i.delete(f),n(f))})},o=pi(a,20),s=new MutationObserver(()=>{o()});s.observe(t,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["class","style","hidden","aria-hidden","data-formie-page-hidden"]});const l=[_t(window,"resize",()=>{o()}),...Pf.map(c=>_t(t,c,()=>{if(Nf.has(c)){a();return}o()}))];return a(),{cleanup:()=>{s.disconnect(),l.forEach(c=>{c()}),Array.from(i).forEach(c=>{n(c)}),i.clear()},reconcile:o,reconcileImmediate:a,getVisible:()=>tr(t,e).filter(c=>Hr(c))}}function qf(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function Bf(t,e,{defaultPlaceholderSelector:r,defaultTokenFieldNames:n=[],defaultWaitForValueMs:i=pn}){const a=e||{},o=Object.entries(a).reduce((u,[g,x])=>(Ff.has(g)||(u[g]=x),u),{}),s=n.map(String).filter(Boolean),l=Number(i),c=typeof a.placeholderSelector=="string"&&a.placeholderSelector.trim()!==""?a.placeholderSelector.trim():r,h=typeof a.errorMessage=="string"&&a.errorMessage.trim()!==""?a.errorMessage.trim():ze("Captcha challenge must be completed."),f=typeof a.sessionKey=="string"&&a.sessionKey.trim()!==""?a.sessionKey.trim():null,d=typeof a.value=="string"?a.value:null;return{handle:qf(t,a),ui:{placeholderSelector:c,errorMessage:h},transport:{tokenFieldNames:s,waitForValueMs:Number.isFinite(l)?l:i,sessionKey:f,value:d},provider:o}}function jf(t,e){const r=t.form||t.root,n=e.ui.placeholderSelector,i=e.handle;return{form:t.form,root:t.root,placeholder:{query:()=>tr(t.root,n),getPrimary:()=>_r(t.root,n),observe:(a,o)=>Hf(t.root,n,a,o),createContainer:a=>Df(a),clear:a=>{a&&(qr(a),a.innerHTML="")}},errors:{getDefaultMessage:()=>e.ui.errorMessage,show:(a,o)=>{zf(o||_r(t.root,n),a||e.ui.errorMessage,t.form||t.root)},clear:a=>{qr(a||_r(t.root,n))}},tokens:{names:e.transport.tokenFieldNames,has:(a=e.transport.tokenFieldNames,o=r)=>Vr(o,a),read:(a=e.transport.tokenFieldNames[0],o=r)=>a?gi(o,a):"",write:(a,{names:o=e.transport.tokenFieldNames,root:s=r,container:l=t.form}={})=>{o.forEach(c=>{vi(s,c,{value:a,container:l})})},clear:(a=e.transport.tokenFieldNames,o=r)=>{Rf(o,a)},wait:(a=e.transport.waitForValueMs,o=e.transport.tokenFieldNames,s=r)=>bi(s,o,a)},refresh:{providerHandle:i,onTokensRefreshed:a=>{const o=["formie:refresh-tokens:after","formie:refresh-tokens:refreshed"].map(s=>_t(t.root,s,l=>{const c=$f(l),h=Vf(c,i);h&&a(h)}));return()=>{o.forEach(s=>{s()})}}},events:{onRoot:(a,o)=>_t(t.root,a,o),onForm:(a,o)=>t.form?_t(t.form,a,o):()=>{}}}}const Xe=Le("captchas");function yi({id:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r=[],defaultWaitForValueMs:n=pn,setup:i}){return{id:t,kind:"captcha",match:()=>!0,setup:async a=>{const o=Bf(t,a.options||{},{defaultPlaceholderSelector:e,defaultTokenFieldNames:r,defaultWaitForValueMs:n});Xe.log("Setup module.",{moduleId:t,placeholderSelector:o.ui.placeholderSelector,tokenFieldNames:o.transport.tokenFieldNames});const s=jf(a,o);return i({...a,options:o,services:s})}}}function Uf({id:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r=[],defaultWaitForValueMs:n=pn}){return yi({id:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r,defaultWaitForValueMs:n,setup:async({services:i,options:a,root:o})=>{const s=[];let l=i.placeholder.getPrimary(),c=a.transport.sessionKey,h=a.transport.value||"";const f=u=>{!u||!c||(u.innerHTML="",vi(u,c,{value:h,container:u}))},d=i.placeholder.observe(u=>{l=u,Xe.log("Passive placeholder visible.",{moduleId:t}),f(u)},u=>{l===u&&(l=i.placeholder.getPrimary()),u.innerHTML=""});return s.push(d.cleanup),f(l),s.push(i.refresh.onTokensRefreshed(u=>{c=typeof u.sessionKey=="string"&&u.sessionKey.trim()!==""?u.sessionKey.trim():c,h=typeof u.value=="string"?u.value:"";const g=i.placeholder.getPrimary()||l;l=g,f(g)})),{destroy:()=>{s.forEach(u=>{u()})},onBeforeStage:async u=>{if(u.stage!=="screen"||u.action!=="submit")return;const g=c?[c]:a.transport.tokenFieldNames;if(g.length===0)return;if(!await bi(o,g,a.transport.waitForValueMs)){const m=i.errors.getDefaultMessage();i.errors.show(m,l),Xe.warn("Passive captcha missing token.",{moduleId:t,tokenFieldNames:g}),u.abort(m)}}}}})}function Kf(t){return yi({id:t.id,defaultPlaceholderSelector:t.defaultPlaceholderSelector,defaultTokenFieldNames:t.defaultTokenFieldNames,setup:async e=>{const r=[],n=new Map,i=new Map;let a=e.services.placeholder.getPrimary(),o=!1,s=null;const l=async()=>(s||(Xe.log("Loading captcha provider API.",{moduleId:t.id}),s=t.load(e)),s),c=async u=>{const g=n.get(u);if(e.services.errors.clear(u),!g){u.innerHTML="";return}const x=await l();t.unmount&&await t.unmount({api:x,widget:g,placeholder:u,services:e.services,options:e.options,provider:e.options.provider}),n.delete(u),u.innerHTML="",e.services.tokens.clear(),Xe.log("Unmounted captcha placeholder widget.",{moduleId:t.id}),a===u&&(a=e.services.placeholder.getPrimary())},h=async u=>{if(o||n.has(u)||i.has(u))return;const g=(async()=>{const x=await l();if(o||n.has(u))return;const m=e.services.placeholder.createContainer(u),p=await t.mount({api:x,placeholder:u,container:m,services:e.services,options:e.options,provider:e.options.provider});n.set(u,p),a=u,Xe.log("Mounted captcha placeholder widget.",{moduleId:t.id})})().finally(()=>{i.delete(u)});i.set(u,g),await g},f=e.services.placeholder.observe(u=>{a=u,h(u)},u=>{c(u)});r.push(f.cleanup);const d=async u=>{const x=f.getVisible();if(t.reset){const m=await l();for(const p of x){const v=n.get(p);if(!v){await h(p);continue}await t.reset({api:m,widget:v,placeholder:p,services:e.services,options:e.options,provider:e.options.provider,reason:u}),e.services.tokens.clear(),e.services.errors.clear(p)}f.reconcile();return}for(const m of Array.from(n.keys()))await c(m);for(const m of x)await h(m);f.reconcile()};return r.push(e.services.events.onRoot("formie:submit:result",u=>{const g=u instanceof CustomEvent?u.detail:null;(g==null?void 0:g.stage)!=="validate"&&((g==null?void 0:g.ok)===!1&&(g==null?void 0:g.stage)==="screen"||(g==null?void 0:g.ok)!==!0&&d("submit-result"))})),e.form&&r.push(e.services.events.onForm(Or("reset"),()=>{a=e.services.placeholder.getPrimary()||a,window.setTimeout(()=>{d("reset-state")},0)})),{destroy:async()=>{o=!0,r.forEach(u=>{u()});for(const u of Array.from(n.keys()))await c(u)},onBeforeStage:async u=>{if(u.stage!=="screen"||u.action!=="submit")return;f.reconcileImmediate();const g=f.getVisible();if(g.length===0)return;let x=g.find(v=>v===a)||g[0];await h(x),x=a||x,e.services.errors.clear(x);const m=n.get(x);if(!m){const v=e.services.errors.getDefaultMessage();e.services.errors.show(v,x),Xe.warn("Captcha widget unavailable at screen stage.",{moduleId:t.id}),u.abort(v);return}const p=await l();await t.screen({api:p,widget:m,placeholder:x,services:e.services,options:e.options,provider:e.options.provider,stageCtx:u})}}}})}const Ym=Kf,Qm=Uf,ro=2500,Wf={bpoint:["bpointToken"],stripe:["stripePaymentIntentId"],paypal:["paypalOrderId","paypalAuthId"],payway:["paywayTokenId"],opayo:["opayoTokenId"],eway:["ewayTokenData"],"go-cardless":["goCardlessRedirectId"],mollie:["molliePaymentId"],moneris:["monerisTokenId"],paddle:["paddleTransactionId"],square:["squarePaymentId"]};function Gf(t){return t.replace("{field:","").replace("{","").replace("}","").replace("]","").split("[").join("][")}function Jf(t){return`fields[${Gf(t)}]`}function Yf(t,e){const r=Jf(e),n=Array.from(t.querySelectorAll(`[name="${r}"]`)),i=Array.from(t.querySelectorAll(`[name="${r}[]"]`));return(i.length?i:n).filter(a=>a instanceof HTMLElement)}function no(t,e){var n,i,a;const r=Yf(t,e);for(const o of r){const s=o.closest("[data-formie-field-handle]"),l=(a=(i=(n=s==null?void 0:s.querySelector("[data-formie-field-label]"))==null?void 0:n.childNodes[0])==null?void 0:i.textContent)==null?void 0:a.trim();if(l)return l}return""}function Sr(t){let e=t.replace(/[^\d.,-]/g,"");const r=e.includes(","),n=e.includes(".");if(r&&n)e.lastIndexOf(",")>e.lastIndexOf(".")?e=e.replace(/\./g,"").replace(",","."):e=e.replace(/,/g,"");else if(r&&!n){const i=e.split(",");i.length===2&&i[1].length===3&&/^\d+$/.test(i[0])&&/^\d+$/.test(i[1])?e=i[0]+i[1]:e=e.replace(",",".")}else e=e.replace(/,/g,"");return parseFloat(e)}function Qf(t){return t.replace(/^\{field:/,"").replace(/^\{/,"").replace(/\}$/,"").trim()}function pt(t){return Qf(t).replace(/\]/g,"").split("[").join(".").replace(/\.+/g,".").replace(/^\./,"").replace(/\.$/,"")}function Br(t){const r=pt(t).split(".").filter(Boolean);if(!r.length)return"";const[n,...i]=r;return`fields[${n}]${i.map(a=>`[${a}]`).join("")}`}function Zf(t){const r=String(t||"").trim().match(/^fields\[([^\]]+)\](.*)$/);if(!r)return"";const n=r[1]||"",i=r[2]||"",a=Array.from(i.matchAll(/\[([^\]]+)\]/g)).map(o=>o[1]||"").filter(Boolean);return[n,...a].join(".")}function Xf(t){const e=t.split(";").map(o=>o.trim()).filter(Boolean);if(!e.length)return{source:"",transforms:[]};const[r,...n]=e,i=[];let a=null;return n.forEach(o=>{if(o.startsWith("transform=")){a&&i.push(a),a={id:decodeURIComponent(o.slice(10)||"").trim(),params:{}};return}if(!a||!o.includes("="))return;const[s,l]=o.split("=",2),c=(s||"").trim();!c||c==="transform"||(a.params[c]=decodeURIComponent(l||"").trim())}),a&&i.push(a),{source:r||"",transforms:i}}function em(t){const e=String(t||"").trim();if(!e)return{raw:e,target:"",key:"",selector:"",defaultValue:"",transforms:[],isToken:!1,isValid:!1};const r=e.match(/^\{([a-zA-Z]+)(?::(.*))?\}$/);if(!r)return{raw:e,target:"",key:pt(e),selector:"",defaultValue:"",transforms:[],isToken:!1,isValid:!0};const n=(r[1]||"").trim().toLowerCase(),i=(r[2]||"").trim(),[a,o=""]=i.split("|",2),{source:s,transforms:l}=Xf(a||"");if(n!=="field")return{raw:e,target:"",key:"",selector:"",defaultValue:o.trim(),transforms:l,isToken:!0,isValid:!1};const c=s.indexOf(":"),h=c===-1?s:s.slice(0,c),f=c===-1?"":s.slice(c+1),d=pt(h);return{raw:e,target:"field",key:d,selector:f.trim(),defaultValue:o.trim(),transforms:l,isToken:!0,isValid:d!==""}}function tm(t){return t instanceof HTMLInputElement||t instanceof HTMLTextAreaElement||t instanceof HTMLSelectElement}function rm(t,e,r){const n=e.trim(),i=String(r.name||"").trim();if(!n||!i)return;const a=t.get(n)||{key:n,names:[],inputs:[]};a.names.includes(i)||a.names.push(i),a.inputs.includes(r)||a.inputs.push(r),t.set(n,a)}function nm(t){const e=new Map;return Array.from(t.querySelectorAll("[name]")).filter(n=>tm(n)).forEach(n=>{const i=Zf(n.name);i&&rm(e,i,n)}),e}function om(t){if(!t.length)return"";const e=t[0];if(e instanceof HTMLSelectElement&&e.multiple)return Array.from(e.selectedOptions).map(n=>n.value);if(t.some(n=>n instanceof HTMLInputElement&&(n.type==="checkbox"||n.type==="radio"))){const n=t.flatMap(i=>!(i instanceof HTMLInputElement)||!i.checked?[]:[i.value]);return n.length>1?n:n[0]||""}return e.value}function im(t,e){return t.get(pt(e))||null}function lt(t,e){const r=em(t),n=r.key,i=im(e,n);if(!i)return{key:n,value:r.defaultValue,found:!1};const a=om(i.inputs);return{key:n,value:a===""&&r.defaultValue!==""?r.defaultValue:a,found:!0}}const wi=new Set(["first","last","index","all","count","rows"]);function oo(t){return t.replace(/[.*+?^${}()|[\]\\]/g,"\\$&")}function am(t,e){const r=String(t||"").trim().toLowerCase();if(!r||e<=0)return[];if(r==="even"){const a=[];for(let o=1;o<=e;o++)o%2===0&&a.push(o-1);return a}if(r==="odd"){const a=[];for(let o=1;o<=e;o++)o%2===1&&a.push(o-1);return a}const n=r.match(/^every:(\d+)$/);if(n){const a=Math.max(1,Number.parseInt(n[1]||"1",10)),o=[];for(let s=1;s<=e;s+=a)o.push(s-1);return o}const i=[];return r.split(/\s*,\s*/).forEach(a=>{const o=a.trim();if(!o)return;const s=o.match(/^(\d+)\s*-\s*(\d+)$/);if(s){let c=Number.parseInt(s[1]||"0",10),h=Number.parseInt(s[2]||"0",10);c>h&&([c,h]=[h,c]);for(let f=c;f<=h;f++)f>=1&&f<=e&&i.push(f-1);return}const l=Number.parseInt(o,10);Number.isFinite(l)&&l>=1&&l<=e&&i.push(l-1)}),[...new Set(i)].sort((a,o)=>a-o)}function Ei(t){const e=pt(t),r=e.split(".").filter(Boolean);return r.length<2?{fieldKey:e,columnKey:r[r.length-1]||""}:r.length>=3&&/^\d+$/.test(r[1]||"")?{fieldKey:r[0]||"",columnKey:r.slice(2).join(".")}:{fieldKey:r[0]||"",columnKey:r.slice(1).join(".")}}function xi(t,e,r){const n=new RegExp(`^${oo(t)}\\.(\\d+)\\.${oo(e)}$`);return[...r.keys()].filter(i=>n.test(i)).sort((i,a)=>{const o=Number.parseInt(i.split(".")[1]||"0",10),s=Number.parseInt(a.split(".")[1]||"0",10);return o-s})}function sm(t,e){return lt(t,e).value}function Zm(t,e,r){const n=new Set,{fieldKey:i,columnKey:a}=Ei(t),o=String(e.scope||"").trim().toLowerCase();if(!i||!a||!wi.has(o)){const l=Br(t);return l&&(n.add(l),n.add(`${l}[]`)),n}return xi(i,a,r).forEach(l=>{var f;const c=r.get(l);if((f=c==null?void 0:c.names)!=null&&f.length){c.names.forEach(d=>{n.add(d)});return}const h=Br(l);h&&(n.add(h),n.add(`${h}[]`))}),n}function Xm(t,e,r){const n=String(e.scope||"").trim().toLowerCase();if(!n||!wi.has(n))return lt(t,r);const{fieldKey:i,columnKey:a}=Ei(t);if(!i||!a)return lt(t,r);const o=xi(i,a,r),s=o.map(l=>sm(l,r));if(n==="count")return{key:`${i}.${a}`,value:String(o.length),found:!0};if(n==="first")return{key:o[0]||`${i}.0.${a}`,value:s[0]??"",found:o.length>0};if(n==="last")return{key:o[o.length-1]||`${i}.0.${a}`,value:s[s.length-1]??"",found:o.length>0};if(n==="index"){const l=Number.parseInt(String(e.index??"0"),10),c=`${i}.${l}.${a}`;return lt(c,r)}if(n==="all"){const l=s.flatMap(c=>Array.isArray(c)?c:c===""?[]:[c]);return{key:`${i}.${a}`,value:l,found:l.length>0}}if(n==="rows"){const l=am(String(e.rows||""),o.length);if(l.length===0)return{key:`${i}.${a}`,value:"",found:!1};if(l.length===1)return{key:o[l[0]]||`${i}.${l[0]}.${a}`,value:s[l[0]]??"",found:!0};const c=l.flatMap(h=>{const f=s[h];return Array.isArray(f)?f:f===""?[]:[f]});return{key:`${i}.${a}`,value:c,found:c.length>0}}return lt(t,r)}function Ar(t){const e=t;return!e.closest("[data-formie-conditionally-hidden]")&&!e.closest("[data-formie-row-hidden]")&&!e.closest("[data-formie-page-hidden]")&&!e.closest("[hidden]")}function ki(t,e){const r=e.replace(/"/g,'\\"');return t.querySelector(`input[name$="[${r}]"]`)||t.querySelector(`input[name$="${r}"]`)}function jt(t,e){const r=e.find(n=>{const i=ki(t,n);return!i||String(i.value||"").trim()===""});return{ok:!r,missingSuffix:r}}async function _i(t,e,r){const n=jt(t,e);if(n.ok)return n;const i=Date.now()+Math.max(r,0);for(;Date.now()<i;){await gn(120);const a=jt(t,e);if(a.ok)return a}return jt(t,e)}const lm=new Set(["handle","requiredInputSuffixes","waitForValueMs","errorMessage"]),io="[data-payment-success]",ao="[data-payment-error]";function cm(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function um(t,e,r){const n=e||{},i=Object.entries(n).reduce((l,[c,h])=>(lm.has(c)||(l[c]=h),l),{}),a=Array.isArray(n.requiredInputSuffixes)?n.requiredInputSuffixes.map(String).filter(Boolean):r.defaultRequiredInputSuffixes||[],o=Number(n.waitForValueMs??r.defaultWaitForValueMs??ro),s=typeof n.errorMessage=="string"&&n.errorMessage.trim()!==""?n.errorMessage.trim():"Payment authorization is incomplete.";return{handle:cm(t,n),transport:{requiredInputSuffixes:a,waitForValueMs:Number.isFinite(o)?o:ro,errorMessage:s},provider:i}}function so(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function dm(t,e){const r=t.target,n=t.form,i=t.root,a=n||i,o=e.transport.requiredInputSuffixes,s=()=>nm(n||i),l=w=>{const k=lt(w,s()).value;return Array.isArray(k)?k[0]||"":String(k||"")};return{root:i,form:n,field:r,updateInputs:(w,b)=>{const k=Array.isArray(w)?w:[w];for(const C of k){const O=ki(a,C)??r.querySelector(`input[name*="${C}"]`);O&&(O.value=b)}},addError:w=>{const b=r.querySelector("[data-formie-field-type] > div, [data-field-type] > div")||r,k=b.querySelector(ao);k&&k.remove();const C=document.createElement("div");C.setAttribute("data-payment-error",""),C.textContent=w,ue(C,n||i,"fieldError"),b.appendChild(C)},removeError:()=>{var w;(w=r.querySelector(ao))==null||w.remove()},addSuccess:w=>{const b=r.querySelector("[data-formie-field-type] > div, [data-field-type] > div")||r,k=b.querySelector(io);k&&k.remove();const C=document.createElement("div");C.setAttribute("data-payment-success",""),C.textContent=w,ue(C,n||i,"successMessage"),b.appendChild(C)},removeSuccess:()=>{var w;(w=r.querySelector(io))==null||w.remove()},hasToken:()=>jt(a,o).ok,waitForToken:(w=e.transport.waitForValueMs)=>_i(a,o,w).then(b=>b.ok),getFieldValue:(w,b="string")=>{const k=l(w);return b==="float"||b==="int"||b==="number"?Sr(k):k},resolveAmount:w=>{const b=n||i,C=String(w.type||"").toLowerCase()==="dynamic"&&typeof w.variable=="string"&&w.variable.trim()!=="",O=w.value??(C?w.variable:w.fixed),$=String(O??"").trim(),j=typeof O=="number"?O:Sr($);if(Number.isFinite(j)&&j>0)return{ok:!0,value:j};if($!==""){const ee=l($),F=Sr(ee);if(Number.isFinite(F)&&F>0)return{ok:!0,value:F};const Q=no(b,$);if(!ee)return{ok:!1,error:Q?ze('Provide a value for "{label}" to proceed.',{label:Q}):ze("Provide a payment amount to proceed.")}}return{ok:!1,error:ze("Payment amount must be greater than 0.")}},resolveCurrency:w=>{const b=n||i,C=String(w.type||"").toLowerCase()==="dynamic"&&typeof w.variable=="string"&&w.variable.trim()!=="",O=w.value??(C?w.variable:w.fixed??w.defaultCurrency??""),$=String(O??"").trim(),j=$.toUpperCase();if(/^[A-Z]{3}$/.test(j)&&!C)return{ok:!0,value:j};if($!==""){const ee=String(l($)||"").trim(),F=ee.toUpperCase();if(/^[A-Z]{3}$/.test(F))return{ok:!0,value:F};const Q=no(b,$);if(!ee)return{ok:!1,error:Q?ze('Provide a value for "{label}" to proceed.',{label:Q}):ze("Provide a payment currency to proceed.")}}return{ok:!1,error:ze("Payment currency must be a valid 3-letter code.")}},watchFieldValueChanges:(w,b,k=600)=>{const C=n||i,O=w.map(Q=>String(Q||"").trim()).filter(Boolean);if(O.length===0)return()=>{};const $=s(),j=new Set;O.forEach(Q=>{var P;const H=pt(Q),y=$.get(H);if((P=y==null?void 0:y.names)!=null&&P.length){y.names.forEach(U=>{j.add(U)});return}const A=Br(H);A&&(j.add(A),j.add(`${A}[]`))});const ee=pi(()=>{b()},k),F=Q=>{const H=Q.target,y=(H==null?void 0:H.name)||"";!y||!j.has(y)||ee()};return C.addEventListener("input",F),C.addEventListener("change",F),()=>{C.removeEventListener("input",F),C.removeEventListener("change",F)}},triggerSubmit:()=>{n&&n.setAttribute("data-formie-internal-resubmit","true"),n&&typeof n.requestSubmit=="function"?n.requestSubmit():n&&n.submit()},releaseSubmitLoading:()=>{n&&(n.removeAttribute("data-formie-internal-resubmit"),Xt(n))},getBillingData:w=>{const b={};if(!w||typeof w!="object")return{billing_details:b};if(w.billingName){const k=l(w.billingName);k&&(b.name=k)}if(w.billingEmail){const k=l(w.billingEmail);k&&(b.email=k)}if(w.billingAddress){const k=w.billingAddress,C={},O=l(`${k}.address1`),$=l(`${k}.address2`),j=l(`${k}.address3`),ee=l(`${k}.city`),F=l(`${k}.zip`),Q=l(`${k}.state`),H=l(`${k}.country`);O&&(C.line1=O),$&&(C.line2=$),j&&(C.line3=j),ee&&(C.city=ee),F&&(C.postal_code=F),Q&&(C.state=Q),H&&(C.country=H),Object.keys(C).length&&(b.address=C)}return{billing_details:b}},events:{onForm:(w,b)=>n?so(n,w,b):()=>{},onRoot:(w,b)=>so(i,w,b)}}}const Pe=Le("payments");function fm(t){const e=t.defaultRequiredInputSuffixes??Wf[t.id]??[];return{id:t.id,kind:"payment",match:r=>{var n,i;return!!(r.target.querySelector('[data-formie-field-type="payment"]')||r.target.closest('[data-formie-field-type="payment"]')||((i=(n=r.target).getAttribute)==null?void 0:i.call(n,"data-formie-field-type"))==="payment")},setup:async r=>{const n=r.target,i=n.__formiePaymentModuleRegistry||{};n.__formiePaymentModuleRegistry=i;const a=i[t.id];if(a!=null&&a.destroy){Pe.warn("Found stale payment module instance; destroying previous.",{moduleId:t.id});try{await a.destroy()}catch{}}const o=um(t.id,r.options||{},{defaultRequiredInputSuffixes:e}),s=dm(r,o),l={...r,options:o,services:s},c=[];let h=null,f=null,d=null,u=null;const g=async()=>(h||(Pe.log("Loading payment provider API.",{moduleId:t.id}),h=t.load(l)),h),x=async()=>{if(!t.mount||f||!Ar(r.target))return;const v=await g();try{f=await t.mount({api:v,field:r.target,services:s,options:o,provider:o.provider}),Pe.log("Payment widget mounted.",{moduleId:t.id,handle:o.handle})}catch{Pe.warn("Payment widget mount failed.",{moduleId:t.id,handle:o.handle})}};if(c.push(r.on("formie:submit:before",()=>{s.removeError(),s.removeSuccess()})),t.setup){const v=r.root||r.form||r.target;d=await t.setup({...l,root:v}),d.destroy&&c.push(d.destroy)}t.mount&&Ar(r.target)&&await x(),["formie:page:navigate:after","formie:submit:result"].forEach(v=>{const w=()=>{x()};r.root.addEventListener(v,w),c.push(()=>{r.root.removeEventListener(v,w)})}),c.push(r.on("formie:conditions:evaluated",()=>{x()}));const p=async()=>{var v;if(Pe.log("Destroying payment module.",{moduleId:t.id,handle:o.handle}),c.forEach(w=>w()),f&&t.unmount){const w=await g();await t.unmount({api:w,widget:f,field:r.target,services:s,options:o,provider:o.provider}),Pe.log("Payment widget unmounted.",{moduleId:t.id,handle:o.handle})}((v=i[t.id])==null?void 0:v.destroy)===p&&delete i[t.id],Pe.log("Payment module destroy complete.",{moduleId:t.id,handle:o.handle})};return i[t.id]={destroy:p},{destroy:p,onBeforeStage:async v=>{if(d!=null&&d.onBeforeStage){await d.onBeforeStage(v);return}if(v.stage!=="authorize"||v.action!=="submit"||!Ar(r.target))return;await x();const w=await g();if(t.onBeforeAuthorize){u||(u=(async()=>t.onBeforeAuthorize({api:w,widget:f,field:r.target,services:s,options:o,provider:o.provider,stageCtx:v}))().finally(()=>{u=null}));const C=await u;if(Pe.log("onBeforeAuthorize resolved.",{moduleId:t.id,handle:o.handle,ok:C}),!C){v.abort(o.transport.errorMessage);return}return}if(o.transport.requiredInputSuffixes.length===0)return;const b=r.form||r.root,k=await _i(b,o.transport.requiredInputSuffixes,o.transport.waitForValueMs);k.ok||(Pe.warn("Required payment input(s) missing.",{moduleId:t.id,handle:o.handle,missingSuffix:k.missingSuffix}),v.abort(o.transport.errorMessage))},onAfterStage:async(v,w)=>{if(v.stage!=="dispatch"||!t.onAfterSubmit)return;const b=await t.onAfterSubmit({field:r.target,services:s,options:o,provider:o.provider,result:w});if(!(!(b!=null&&b.remount)||!t.mount)){if(f&&t.unmount){const k=await g();await t.unmount({api:k,widget:f,field:r.target,services:s,options:o,provider:o.provider})}f=null,await x()}}}}}}const eh=fm,mm="[data-formie-address-autocomplete-input]",lo="[data-formie-address-location]",qe={autoComplete:"[data-formie-address-autocomplete-input]",address1:"[data-formie-address-line1-input]",address2:"[data-formie-address-line2-input]",address3:"[data-formie-address-line3-input]",city:"[data-formie-address-city-input]",state:"[data-formie-address-state-input]",zip:"[data-formie-address-zip-input]",country:"[data-formie-address-country-input]"},Be={autoComplete:"[data-formie-address-autocomplete-input]",address1:"[data-address1]",address2:"[data-address2]",address3:"[data-address3]",city:"[data-city]",state:"[data-state]",zip:"[data-zip]",country:"[data-country]"},hm={autoComplete:[qe.autoComplete,Be.autoComplete],address1:[qe.address1,Be.address1],address2:[qe.address2,Be.address2],address3:[qe.address3,Be.address3],city:[qe.city,Be.city],state:[qe.state,Be.state],zip:[qe.zip,Be.zip],country:[qe.country,Be.country]};function pm(t,e){for(const r of hm[e]){const n=t.querySelector(r);if(n instanceof HTMLInputElement||n instanceof HTMLSelectElement)return n}return null}const gm=new Set(["handle"]);function vm(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function bm(t,e){const r=e||{},n=Object.entries(r).reduce((i,[a,o])=>(gm.has(a)||(i[a]=o),i),{});return{handle:vm(t,r),provider:n}}function ym(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function wm(t){const e=t.target,r=t.form,n=t.root,i=mm;return{root:n,field:e,form:r,input:{getAutocomplete:()=>e.querySelector(i),setValue:(a,o,s)=>{const l=pm(e,a);if(!l)return;const c=o||s||"";l.value!==c&&(l.value=c,l.dispatchEvent(new Event("input",{bubbles:!0})),l.dispatchEvent(new Event("change",{bubbles:!0})))}},location:{getButton:()=>e.querySelector(lo),onUseLocation:a=>{const o=e.querySelector(lo);if(!o)return()=>{};const s=l=>{l.preventDefault(),navigator.geolocation&&navigator.geolocation.getCurrentPosition(a,()=>{},{enableHighAccuracy:!0})};return o.addEventListener("click",s),()=>{o.removeEventListener("click",s)}}},events:{onField:(a,o)=>ym(e,a,o)}}}const st=Le("address");function co(t){const e=t;return!e.closest("[data-formie-page-hidden]")&&!e.closest("[hidden]")}function Em(t){return{id:t.id,kind:"address",match:e=>!!e.target.querySelector("[data-formie-address-autocomplete-input]"),setup:async e=>{const r=bm(t.id,e.options||{}),n=wm(e);st.log("Setup module.",{moduleId:t.id});const i={...e,options:r,services:n},a=[];let o=null,s=null;if(!n.input.getAutocomplete())return console.warn(`[formie] Address module "${t.id}" skipped: no autocomplete input found in target. Ensure the Address field has the Auto-Complete subfield enabled.`),st.warn("Autocomplete input missing; skipping module.",{moduleId:t.id}),{destroy:()=>{}};const c=async()=>(o||(st.log("Loading provider API.",{moduleId:t.id}),o=t.load(i)),o),h=async()=>{if(s||!co(e.target))return;const u=await c();s=await t.mount({api:u,field:e.target,services:n,options:r,provider:r.provider}),st.log("Widget mounted.",{moduleId:t.id})};co(e.target)&&await h(),["formie:page:navigate:after","formie:submit:result"].forEach(u=>{const g=()=>{h()};e.root.addEventListener(u,g),a.push(()=>{e.root.removeEventListener(u,g)})});const d=n.location.onUseLocation(u=>{t.onCurrentLocation&&(async()=>{var x;if(await h(),!s)return;const g=await c();await((x=t.onCurrentLocation)==null?void 0:x.call(t,u,{api:g,widget:s,field:e.target,services:n,options:r,provider:r.provider}))})()});return d&&a.push(d),{destroy:async()=>{if(st.log("Destroying module.",{moduleId:t.id}),a.forEach(u=>u()),s&&t.unmount){const u=await c();await t.unmount({api:u,widget:s,field:e.target,services:n,options:r,provider:r.provider}),st.log("Widget unmounted.",{moduleId:t.id})}}}}}}const th=Em;function xm(t){const e=t.getElementById("formie-preview-config");if(!(e instanceof HTMLScriptElement)||!e.textContent)return{};try{return JSON.parse(e.textContent)}catch(r){return console.warn("[FormiePreview] Failed to parse preview config.",r),{}}}function km(t,e){if(!(e!=null&&e.length))return;const r=JSON.stringify(e);t.querySelectorAll("[data-formie], [data-formie-form]").forEach(n=>{n.setAttribute("data-formie-modules",r)})}function _m(t){var l,c,h;const e=t.body,r=(l=t.defaultView)==null?void 0:l.HTMLElement;if(!e)return((c=t.documentElement)==null?void 0:c.scrollHeight)||0;const n=e.getBoundingClientRect(),i=(h=t.defaultView)==null?void 0:h.getComputedStyle(e),a=parseFloat(i.paddingTop||"0")||0,o=parseFloat(i.paddingBottom||"0")||0,s=Array.from(e.children).reduce((f,d)=>{if(!r||!(d instanceof r)||d.tagName==="SCRIPT")return f;const u=d.getBoundingClientRect();return Math.max(f,u.bottom-n.top)},a);return Math.ceil(s+o)}function ct(t,e){var n;const r=_m(t.document);e==null||e(r),(n=t.parent)==null||n.postMessage({type:"formie-preview:height",height:r},"*")}function Sm(t,e){const r=t.document;if(typeof t.ResizeObserver<"u"){const n=new t.ResizeObserver(()=>{ct(t,e)});n.observe(r.documentElement),r.body&&n.observe(r.body)}["click","input","change"].forEach(n=>{r.addEventListener(n,()=>{t.requestAnimationFrame(()=>{ct(t,e)})},!0)})}async function Am(t,e){var i;const r=t.document,n=xm(r);Sm(t,e),t.addEventListener("load",()=>{ct(t,e)},{once:!0}),t.requestAnimationFrame(()=>{ct(t,e),t.requestAnimationFrame(()=>{ct(t,e)})}),(i=n.modules)!=null&&i.length&&(tu(!1),km(r,n.modules),await Mf().scan(r)),ct(t,e)}const Tm=Object.assign({"../../../browser/ui-reference/examples/address.preview.ts":()=>M(()=>import("./address.preview.D-ghwOAm.js"),[]),"../../../browser/ui-reference/examples/agree.preview.ts":()=>M(()=>import("./agree.preview.BuDgdg1_.js"),[]),"../../../browser/ui-reference/examples/buttons-loading.preview.ts":()=>M(()=>import("./buttons-loading.preview.BvDn73XT.js"),[]),"../../../browser/ui-reference/examples/buttons-positions.preview.ts":()=>M(()=>import("./buttons-positions.preview.B-G789jX.js"),[]),"../../../browser/ui-reference/examples/buttons-variants.preview.ts":()=>M(()=>import("./buttons-variants.preview.0jJSmcOh.js"),[]),"../../../browser/ui-reference/examples/buttons.preview.ts":()=>M(()=>import("./buttons.preview.MzXYysPp.js"),[]),"../../../browser/ui-reference/examples/calculations.preview.ts":()=>M(()=>import("./calculations.preview.CtChBkrf.js"),[]),"../../../browser/ui-reference/examples/categories.preview.ts":()=>M(()=>import("./categories.preview.ixyBoeER.js"),__vite__mapDeps([57,58])),"../../../browser/ui-reference/examples/checkboxes.preview.ts":()=>M(()=>import("./checkboxes.preview.BI4i9Rg-.js"),[]),"../../../browser/ui-reference/examples/date.preview.ts":()=>M(()=>import("./date.preview.CSKAFHj6.js"),[]),"../../../browser/ui-reference/examples/entries.preview.ts":()=>M(()=>import("./entries.preview.vVoUh2wl.js"),__vite__mapDeps([59,58])),"../../../browser/ui-reference/examples/field-anatomy.preview.ts":()=>M(()=>import("./field-anatomy.preview.CDGHSvef.js"),[]),"../../../browser/ui-reference/examples/field-normal.preview.ts":()=>M(()=>import("./field-normal.preview.CiEbz5Fv.js"),[]),"../../../browser/ui-reference/examples/file-upload.preview.ts":()=>M(()=>import("./file-upload.preview.CTvngf20.js"),[]),"../../../browser/ui-reference/examples/hidden.preview.ts":()=>M(()=>import("./hidden.preview.MMyPAdXC.js"),[]),"../../../browser/ui-reference/examples/loading-button-variants.preview.ts":()=>M(()=>import("./loading-button-variants.preview.DsRnArXp.js"),[]),"../../../browser/ui-reference/examples/loading-buttons.preview.ts":()=>M(()=>import("./loading-buttons.preview.BUXpUDz6.js"),[]),"../../../browser/ui-reference/examples/loading-sizes-colors.preview.ts":()=>M(()=>import("./loading-sizes-colors.preview.IYbMzHOV.js"),[]),"../../../browser/ui-reference/examples/loading.preview.ts":()=>M(()=>import("./loading.preview.DlOgX5Nv.js"),[]),"../../../browser/ui-reference/examples/messages.preview.ts":()=>M(()=>import("./messages.preview.Bpxa33ze.js"),[]),"../../../browser/ui-reference/examples/multi-line-text-rich-text.preview.ts":()=>M(()=>import("./multi-line-text-rich-text.preview.pKViw2NJ.js"),[]),"../../../browser/ui-reference/examples/multi-line-text.preview.ts":()=>M(()=>import("./multi-line-text.preview.CRb5IKJ_.js"),[]),"../../../browser/ui-reference/examples/page-navigation-only.preview.ts":()=>M(()=>import("./page-navigation-only.preview.D9zHiF02.js"),[]),"../../../browser/ui-reference/examples/payment.preview.ts":()=>M(()=>import("./payment.preview.DtictnrE.js"),[]),"../../../browser/ui-reference/examples/phone.preview.ts":()=>M(()=>import("./phone.preview.D-k2drYO.js"),[]),"../../../browser/ui-reference/examples/progress.preview.ts":()=>M(()=>import("./progress.preview.kV7Ij1sV.js"),[]),"../../../browser/ui-reference/examples/radio.preview.ts":()=>M(()=>import("./radio.preview.DrkMq2KR.js"),[]),"../../../browser/ui-reference/examples/recipients.preview.ts":()=>M(()=>import("./recipients.preview.BWBx9rU1.js"),__vite__mapDeps([60,58])),"../../../browser/ui-reference/examples/repeater.preview.ts":()=>M(()=>import("./repeater.preview.BzsOZh0V.js"),[]),"../../../browser/ui-reference/examples/signature.preview.ts":()=>M(()=>import("./signature.preview.CWYxzxWD.js"),[]),"../../../browser/ui-reference/examples/single-line-text.preview.ts":()=>M(()=>import("./single-line-text.preview.BmmellSY.js"),[]),"../../../browser/ui-reference/examples/summary.preview.ts":()=>M(()=>import("./summary.preview.By_O1ubB.js"),[]),"../../../browser/ui-reference/examples/table.preview.ts":()=>M(()=>import("./table.preview.BFrHaTOl.js"),[]),"../../../browser/ui-reference/examples/tags.preview.ts":()=>M(()=>import("./tags.preview.CmHYrzId.js"),[]),"../../../browser/ui-reference/examples/upload-manager.preview.ts":()=>M(()=>import("./upload-manager.preview.DTc5MOwe.js"),[])});function Cm(t){const e=t.split(/[?#]/,1)[0]||"/";return e.endsWith("/")?e:`${e.slice(0,e.lastIndexOf("/")+1)}`}function Im(t,e="/"){return e==="/"||!t.startsWith(e)?t:`/${t.slice(e.length)}`}function Lm(t,e,r="/"){return t.startsWith("@/")?`/${t.slice(2)}`:Im(new URL(t,`https://docs.local${Cm(e)}`).pathname,r)}function Mm(t){return`../../../${t.replace(/^\//,"")}`}async function Rm(t,e,r="/"){const n=Lm(t,e,r),i=Mm(n),a=Tm[i];if(!a)return console.warn(`[FormiePreview] No preview source found for "${t}" resolved from "${e}".`),null;const o=await a();return o.default??o.preview??null}const Fm=["srcdoc"],Om=8,Pm=ke({__name:"FormiePreview",props:{markup:{},minHeight:{default:120},src:{}},setup(t){const e=t,r=ho(),{site:n}=We(),i=ce(null),a=ce(null),o=ce(e.minHeight);let s=0;xe(()=>[r.path,e.src,n.value.base],async()=>{if(!e.src){a.value=null;return}const m=++s,p=await Rm(e.src,r.path,n.value.base);m===s&&(a.value=p)},{immediate:!0});const l=B(()=>{var m;return((m=a.value)==null?void 0:m.markup)??e.markup??""}),c=B(()=>{var m;return((m=a.value)==null?void 0:m.minHeight)??e.minHeight}),h=B(()=>{var p;const m=(p=a.value)==null?void 0:p.modules;return JSON.stringify({modules:m!=null&&m.length?m:void 0}).replaceAll("<","\\u003c")});xe(c,m=>{o.value=m},{immediate:!0}),xe(()=>[l.value,c.value],(m,p)=>{(!p||p[0]!==l.value||p[1]!==c.value)&&(o.value=c.value)});function f(m){!Number.isFinite(m)||m<=0||(o.value=Math.ceil(m+Om))}function d(){var $,j,ee;const m=($=i.value)==null?void 0:$.contentDocument,p=m==null?void 0:m.body,v=(j=m==null?void 0:m.defaultView)==null?void 0:j.HTMLElement;if(!p)return c.value;const w=p.getBoundingClientRect(),b=(ee=m.defaultView)==null?void 0:ee.getComputedStyle(p),k=parseFloat((b==null?void 0:b.paddingTop)||"0")||0,C=parseFloat((b==null?void 0:b.paddingBottom)||"0")||0,O=Array.from(p.children).reduce((F,Q)=>{if(!v||!(Q instanceof v)||Q.tagName==="SCRIPT")return F;const H=Q.getBoundingClientRect();return Math.max(F,H.bottom-w.top)},k);return Math.ceil(O+C)}function u(m){var p,v;((p=m.data)==null?void 0:p.type)==="formie-preview:height"&&m.source===((v=i.value)==null?void 0:v.contentWindow)&&f(Number(m.data.height))}function g(){var p;const m=(p=i.value)==null?void 0:p.contentWindow;m&&(f(d()),Am(m,f))}Ke(()=>{window.addEventListener("message",u)}),rr(()=>{window.removeEventListener("message",u)});const x=B(()=>`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    ${[bc,yc,wc,Ec,xc,kc,_c,Sc,Ac,Tc,Cc,Ic,Lc,Mc,Rc,Fc,Oc,Pc,Nc,Dc,zc,$c,Vc,Hc,qc,Bc].join(`
`)}
    body { margin: 0; padding: 16px; background: #fff; }
  </style>
</head>
<body>
  <script id="formie-preview-config" type="application/json">${h.value}<\/script>
  ${l.value}
</body>
</html>`);return(m,p)=>(L(),R("iframe",{ref_key:"iframeRef",ref:i,class:"formie-preview-frame",style:Ct({height:`${o.value}px`}),srcdoc:x.value,title:"Formie preview",loading:"lazy",onLoad:g},null,44,Fm))}}),rh=vc({enhanceApp({app:t}){t.component("FormiePreview",Pm)}});export{qe as A,gn as B,Um as C,ft as D,pi as E,jm as F,ze as G,eh as H,qm as I,ue as J,ht as K,Ym as a,Jm as b,Wm as c,th as d,Qm as e,pm as f,Vm as g,Km as h,Le as i,nm as j,Zm as k,Br as l,lt as m,pt as n,$m as o,cs as p,Hm as q,Xm as r,Or as s,rh as t,ir as u,zm as v,Gm as w,rn as x,Bm as y,Ho as z};
