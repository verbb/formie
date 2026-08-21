const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/chunks/address-finder.B5bUt3aO.js","assets/chunks/scripts.DBFHJ7iR.js","assets/chunks/framework.BKStFnx-.js","assets/chunks/google-address.BVZhSUJf.js","assets/chunks/loqate.BC1LGCXi.js","assets/chunks/place-kit.BGnTLDnK.js","assets/chunks/styles.DqkRI_my.js","assets/chunks/captcha-eu.8szyj-vT.js","assets/chunks/friendly-captcha-v1.B8Q5l8Mf.js","assets/chunks/friendly-captcha-v2.CMtPPTWp.js","assets/chunks/hcaptcha.y0u1q83c.js","assets/chunks/recaptcha-enterprise.CWmOH9mm.js","assets/chunks/recaptcha-shared.PXsXAnwi.js","assets/chunks/recaptcha-v2-checkbox.CADKbpp9.js","assets/chunks/recaptcha-v2-invisible.Dwmpr01D.js","assets/chunks/recaptcha-v3.C8805hME.js","assets/chunks/snaptcha.qFBRWVcH.js","assets/chunks/turnstile.Bf76WNob.js","assets/chunks/calculations.DE37xsO1.js","assets/chunks/index.DP7gAAjG.js","assets/chunks/shared.CCacJPOf.js","assets/chunks/checkbox-radio.W7XOFqlh.js","assets/chunks/combobox.D7BjMnri.js","assets/chunks/conditions.DHVREdxr.js","assets/chunks/custom-google-maps.C-npGY3u.js","assets/chunks/custom-link.D0uCHdjJ.js","assets/chunks/custom-maps.7Fs0vxP-.js","assets/chunks/date-picker.3LyJObcl.js","assets/chunks/file-upload.T_isckxz.js","assets/chunks/upload-manager.DjnXShtD.js","assets/chunks/hidden.Dl7Lj9Jo.js","assets/chunks/phone-country.BGNPlKnJ.js","assets/chunks/country-from-ip.Dtgh513v.js","assets/chunks/password-validation.Dd8aYvt5.js","assets/chunks/address-country.DAgvLoqf.js","assets/chunks/address-state.CV3rc95O.js","assets/chunks/repeater.DRxUbTIc.js","assets/chunks/rich-text.BG3CipRf.js","assets/chunks/signature.CXBgTLR1.js","assets/chunks/summary.82Fnn6Kq.js","assets/chunks/survey-likert.DnUP7Hdi.js","assets/chunks/survey-presentations.B4wzIxW0.js","assets/chunks/survey-rank.B80GTiom.js","assets/chunks/survey-rating.Dv5X0l_o.js","assets/chunks/table.AFBmKb7s.js","assets/chunks/text-limit.Db1oqz8C.js","assets/chunks/bpoint.B_1mg6xW.js","assets/chunks/eway.B_P7Uw9b.js","assets/chunks/go-cardless.BxVXKsjg.js","assets/chunks/mollie.iBpWWbCB.js","assets/chunks/moneris.CsPYGhkr.js","assets/chunks/opayo.C5AkDI4M.js","assets/chunks/paddle._7-DXh4E.js","assets/chunks/paypal.BvP53fz2.js","assets/chunks/payway.D6Ltb7K8.js","assets/chunks/square.Br3l0p14.js","assets/chunks/stripe.CxAo1CFD.js","assets/chunks/categories.preview.ixyBoeER.js","assets/chunks/elementDisplayPreview.BQWAWWZ5.js","assets/chunks/entries.preview.vVoUh2wl.js","assets/chunks/recipients.preview.BWBx9rU1.js"])))=>i.map(i=>d[i]);
var Ui=Object.defineProperty;var Wi=(e,t,r)=>t in e?Ui(e,t,{enumerable:!0,configurable:!0,writable:!0,value:r}):e[t]=r;var br=(e,t,r)=>Wi(e,typeof t!="symbol"?t+"":t,r);import{u as Ge,w as pe,a as lr,o as qe,b as yo,c as q,r as ae,d as Ce,e as M,f as O,n as le,F as ge,g as be,h as Z,i as Xr,j as S,t as re,k as ke,l as Rt,m as ze,p as wo,q as Ze,s as J,v as cr,x as Eo,y as Me,z as kt,_ as R,A as Ki,B as Ji,C as Gi,D as xo,E as Yi,G as Qi,H as ye,I as Re,J as ko,T as Zi,K as Nr,L as Xi,M as ea,N as Tn,O as ta,P as ra,Q as na,R as oa,S as _o,U as ia,V as Cn}from"./framework.BKStFnx-.js";const aa=/#.*$/,sa=/[?#].*$/,la=/(?:(^|\/)index)?\.(?:md|html)$/;function In(e){return decodeURI(e).replace(sa,"").replace(la,"$1")}function Ln(e){return/^\//.test(e)?e:`/${e}`}function Ut(e,t){return e.map(r=>{const n={...r},i=n.base||t;return i&&n.link&&(n.link=`${i}${n.link}`),n.items&&(n.items=Ut(n.items,i)),n})}function ur(e,t,r=!1){if(t===void 0)return!1;const n=In(`/${e}`);if(r)return new RegExp(t).test(n);if(In(t)!==n)return!1;const i=t.match(aa);return i?typeof window<"u"&&window.location.hash===i[0]:!0}function Ct(e,t){var r;return t?ur(e,t.link)?!0:((r=t.items)==null?void 0:r.some(n=>Ct(e,n)))??!1:!1}function ca(e,t){if(Array.isArray(e))return Ut(e);if(!e)return[];const r=Ln(t),n=Object.keys(e).sort((a,o)=>o.split("/").length-a.split("/").length).find(a=>r.startsWith(Ln(a))),i=n?e[n]:[];return Array.isArray(i)?Ut(i):Ut(i.items,i.base)}function ua(e){const t=[];let r=0;for(const n of e){if(n.items){t.push({text:n.text,icon:n.icon,items:n.items}),r=t.length-1;continue}t[r]||(t.push({items:[]}),r=t.length-1),t[r].items.push(n)}return t}function en(){const{frontmatter:e,page:t,theme:r}=Ge(),n=ae(!1),i=q(()=>ca(r.value.sidebar,t.value.relativePath)),a=q(()=>ua(i.value)),o=q(()=>e.value.sidebar!==!1&&e.value.layout!=="home"&&i.value.length>0);pe(o,f=>{f||(n.value=!1)}),lr(f=>{if(typeof document>"u")return;const m=document.body.style.overflow;n.value&&typeof window<"u"&&window.innerWidth<1024&&(document.body.style.overflow="hidden"),f(()=>{document.body.style.overflow=m})});function s(){n.value=!0}function l(){n.value=!1}function c(){n.value=!n.value}return{isOpen:n,sidebar:i,sidebarGroups:a,hasSidebar:o,open:s,close:l,toggle:c}}function da(e,t){let r=null;lr(()=>{r=e.value?document.activeElement:null});const n=i=>{i.key==="Escape"&&e.value&&(t(),r instanceof HTMLElement&&r.focus())};qe(()=>{window.addEventListener("keyup",n)}),yo(()=>{window.removeEventListener("keyup",n)})}const fa=["d","fill"],dr=Ce({__name:"DocsIcon",props:{name:{default:""},class:{default:"size-4"}},setup(e){const t=e,r={"play-circle":{paths:[{d:"M8 14.25A6.25 6.25 0 1 0 8 1.75a6.25 6.25 0 0 0 0 12.5"},{d:"M6.25 5.75 10.25 8l-4 2.25V5.75",fill:"currentColor"}]},"app-window":{paths:[{d:"M2.75 4.25A1.5 1.5 0 0 1 4.25 2.75h7.5a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5 1.5h-7.5a1.5 1.5 0 0 1-1.5-1.5v-7.5Z"},{d:"M2.75 5.5h10.5"},{d:"M5 4.125h.01M7 4.125h.01M9 4.125h.01"}]},blocks:{paths:[{d:"M2.75 3.25h4.5v4.5h-4.5z"},{d:"M8.75 3.25h4.5v4.5h-4.5z"},{d:"M5.75 8.75h4.5v4.5h-4.5z"}]},"clipboard-list":{paths:[{d:"M5.25 3.25h5.5a1.5 1.5 0 0 1 1.5 1.5v7a1.5 1.5 0 0 1-1.5 1.5h-5.5a1.5 1.5 0 0 1-1.5-1.5v-7a1.5 1.5 0 0 1 1.5-1.5Z"},{d:"M6.25 2.75h3.5v1.5h-3.5z"},{d:"M6 6.5h3.75M6 8.5h3.75M6 10.5h3.75"},{d:"M5 6.5h.01M5 8.5h.01M5 10.5h.01"}]},"layout-template":{paths:[{d:"M2.75 3.25h10.5v9.5H2.75z"},{d:"M6.25 3.25v9.5"},{d:"M6.25 6.75h7"}]},"rows-3":{paths:[{d:"M3 4.5h1.5M6 4.5h7"},{d:"M3 8h1.5M6 8h7"},{d:"M3 11.5h1.5M6 11.5h7"}]},"square-terminal":{paths:[{d:"M3.25 3.25h9.5v9.5h-9.5z"},{d:"M5.25 6.25 7 8l-1.75 1.75"},{d:"M8.75 9.75h2.25"}]},"flask-conical":{paths:[{d:"M6 2.75h4"},{d:"M7 2.75v2.5l-3 5.25a1.5 1.5 0 0 0 1.3 2.25h5.4A1.5 1.5 0 0 0 12 10.5L9 5.25v-2.5"},{d:"M5.5 9h5"}]}},n=q(()=>r[t.name]??null);return(i,a)=>n.value?(M(),O("svg",{key:0,viewBox:"0 0 16 16",fill:"none",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round",class:le(t.class),"aria-hidden":"true"},[(M(!0),O(ge,null,be(n.value.paths,o=>(M(),O("path",{key:o.d,d:o.d,fill:o.fill??"none"},null,8,fa))),128))],2)):Z("",!0)}}),ma={class:"relative"},ha={class:"min-w-0 flex-1 break-words"},pa=["href"],ga={class:"flex min-w-0 flex-1 items-start gap-x-2.5"},va={class:"flex min-w-0 flex-1 flex-wrap items-center gap-1.5 [word-break:break-word]"},ba={class:"min-w-0 max-w-full break-words"},ya=Ce({__name:"DocsMobileMenuNode",props:{item:{},depth:{default:0}},emits:["navigate"],setup(e,{emit:t}){const r=e,n=t,{page:i}=Ge(),a=Xr(),o=q(()=>{var u;return!!((u=r.item.items)!=null&&u.length)}),s=q(()=>ur(i.value.relativePath,r.item.link)),l=q(()=>{var u;return((u=r.item.items)==null?void 0:u.some(g=>Ct(i.value.relativePath,g)))??!1}),c=ae(o.value?!r.item.collapsed||l.value:!1);pe(l,u=>{u&&(c.value=!0)});function f(u){return u?ze(u):"#"}async function m(u,g){g&&(u.preventDefault(),await a.go(f(g)),n("navigate"))}function d(){o.value&&(c.value=!c.value)}return(u,g)=>{const E=wo("DocsMobileMenuNode",!0);return M(),O("li",ma,[o.value?(M(),O("button",{key:0,type:"button",class:le(["group flex w-full cursor-pointer items-center py-0.5 pr-2 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",l.value?"text-docs-primary":"text-slate-700"]),onClick:d},[S("span",ha,re(e.item.text),1),(M(),O("svg",{viewBox:"0 0 640 640",class:le(["size-3 shrink-0 transition-transform",c.value?"rotate-90":"rotate-0"]),"aria-hidden":"true"},[...g[2]||(g[2]=[S("path",{d:"M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"},null,-1)])],2))],2)):(M(),O("a",{key:1,href:f(e.item.link),class:le(["group flex w-full cursor-pointer items-center py-0.5 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",s.value?"text-docs-primary":"text-slate-700"]),onClick:g[0]||(g[0]=h=>m(h,e.item.link))},[S("div",ga,[e.item.icon?(M(),ke(dr,{key:0,name:e.item.icon,class:"mt-1 size-4 shrink-0 text-slate-500 group-hover:text-slate-700"},null,8,["name"])):Z("",!0),S("div",va,[S("span",ba,re(e.item.text),1)])])],10,pa)),o.value&&c.value?(M(),O("ul",{key:2,style:Rt({marginLeft:e.depth===0?"1rem":"1.25rem"})},[(M(!0),O(ge,null,be(e.item.items,h=>(M(),ke(E,{key:h.link??`${h.text}-${h.icon??""}`,item:h,depth:e.depth+1,onNavigate:g[1]||(g[1]=p=>n("navigate"))},null,8,["item","depth"]))),128))],4)):Z("",!0)])}}}),wa={class:"min-h-full bg-white"},Ea={class:"border-b border-slate-200/80 px-4 pb-4 pt-5"},xa={class:"flex min-w-0 items-center gap-3"},ka=["src"],_a={key:1,class:"min-w-0 truncate text-base font-semibold tracking-[-0.01em] text-slate-900"},Sa={class:"px-4 pb-6 pt-6"},Aa={"aria-label":"Sidebar navigation",class:"text-sm"},Ta={key:0,class:"mb-3 flex items-center gap-2.5 text-sm font-medium text-slate-900"},Ca={class:"space-y-px"},Ia=Ce({__name:"DocsMobileMenu",props:{logoSrc:{},siteTitle:{}},emits:["navigate"],setup(e){const{sidebarGroups:t}=en(),r=q(()=>t.value.filter(n=>{var i;return(i=n.items)==null?void 0:i.length}));return(n,i)=>(M(),O("div",wa,[S("div",Ea,[S("div",xa,[e.logoSrc?(M(),O("img",{key:0,src:e.logoSrc,alt:"",class:"block h-7 w-auto max-w-[156px] shrink-0 object-contain"},null,8,ka)):(M(),O("div",_a,re(e.siteTitle),1))])]),S("div",Sa,[S("nav",Aa,[(M(!0),O(ge,null,be(r.value,a=>{var o,s;return M(),O("section",{key:a.text??((s=(o=a.items)==null?void 0:o[0])==null?void 0:s.link),class:"mt-6 first:mt-0"},[a.text?(M(),O("h2",Ta,[a.icon?(M(),ke(dr,{key:0,name:a.icon,class:"size-4 text-slate-600"},null,8,["name"])):Z("",!0),Ze(" "+re(a.text),1)])):Z("",!0),S("ul",Ca,[(M(!0),O(ge,null,be(a.items,l=>(M(),ke(ya,{key:l.link??`${l.text}-${l.icon??""}`,item:l,onNavigate:i[0]||(i[0]=c=>n.$emit("navigate"))},null,8,["item"]))),128))])])}),128))])])]))}}),La={class:"flex min-h-[calc(100dvh-14rem)] w-full flex-col items-center justify-center px-6 py-16 text-center sm:px-8 sm:py-24 lg:min-h-[calc(100dvh-10rem)]"},Ma={class:"text-6xl font-semibold tracking-[-0.04em] text-slate-900 sm:text-7xl"},Ra={class:"mt-3 text-2xl font-semibold tracking-[-0.03em] text-slate-900 sm:text-3xl"},Fa={class:"mt-5 max-w-sm text-sm leading-6 text-slate-600"},Oa=["href","aria-label"],Pa=Ce({__name:"DocsNotFound",setup(e){const{theme:t}=Ge(),r=q(()=>{var s;return((s=t.value.notFound)==null?void 0:s.code)??"404"}),n=q(()=>{var s;return((s=t.value.notFound)==null?void 0:s.title)??"Page not found"}),i=q(()=>{var s;return((s=t.value.notFound)==null?void 0:s.quote)??"The page you requested does not exist or may have moved."}),a=q(()=>{var s;return((s=t.value.notFound)==null?void 0:s.linkLabel)??"Go to home"}),o=q(()=>{var s;return((s=t.value.notFound)==null?void 0:s.linkText)??"Take me home"});return(s,l)=>(M(),O("section",La,[S("p",Ma,re(r.value),1),S("h1",Ra,re(n.value),1),l[0]||(l[0]=S("div",{class:"mt-6 h-px w-16 bg-slate-200"},null,-1)),S("p",Fa,re(i.value),1),S("a",{href:J(ze)("/"),"aria-label":a.value,class:"mt-7 inline-flex items-center rounded-xl border border-docs-primary-border bg-docs-primary-soft px-4 py-2 text-sm font-medium text-docs-primary transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft-hover"},re(o.value),9,Oa)]))}}),Na={id:"table-of-contents-content",class:"toc"},Da=["data-depth","data-active","data-active-deepest"],$a=["href","onClick"],za=Ce({__name:"DocsOutlineItem",props:{items:{}},setup(e){const t=e;function r(n,i){const a=i.replace(/^#/,""),o=document.getElementById(a);o&&(n.preventDefault(),o.scrollIntoView({block:"start",behavior:"smooth"}),window.location.hash=i)}return(n,i)=>(M(),O("ul",Na,[(M(!0),O(ge,null,be(t.items,a=>(M(),O("li",{key:a.link,class:le(["toc-item relative",a.depth>0?a.active?"border-l pl-4 border-docs-primary hover:border-docs-primary":"border-l pl-4 border-slate-950/5 hover:border-slate-950/20":""]),"data-depth":a.depth,"data-active":a.active||void 0,"data-active-deepest":a.activeDeepest||void 0},[S("a",{href:a.link,style:Rt(a.depth>0?"padding-left:1rem":void 0),class:le(["break-words py-1",[a.depth>0?"group flex items-start whitespace-pre-wrap":"block border-l pl-4 font-medium",a.active?a.depth>0?"text-docs-primary":"text-docs-primary border-docs-primary hover:border-docs-primary":a.depth>0?"text-gray-500 hover:text-gray-900":"border-slate-950/5 hover:border-slate-950/20 hover:text-gray-900"]]),onClick:o=>r(o,a.link)},re(a.title),15,$a)],10,Da))),128))]))}}),Va={key:0,id:"table-of-contents","aria-label":"On this page",class:"space-y-2"},Ha={type:"button",class:"flex cursor-pointer items-center space-x-2 text-sm font-medium text-slate-700 transition-colors hover:text-slate-900"},qa=Ce({__name:"DocsOutline",setup(e){const{frontmatter:t,theme:r}=Ge(),n=q(()=>{const p=r.value.outline;return typeof p=="object"&&!Array.isArray(p)&&(p==null?void 0:p.label)||r.value.outlineTitle||"On this page"}),i=ae(null),a=ae([]),o=q(()=>{var b;const p=g(a.value,i.value),v=new Set(p.map(k=>k.link)),y=((b=p.at(-1))==null?void 0:b.link)??null;return u(a.value).map(k=>({...k,depth:Math.max(k.level-2,0),active:v.has(k.link),activeDeepest:k.link===y}))});function s(){return document.getElementById("docs-scroll-container")??document.getElementById("content-container")}function l(p){const v=Number.parseFloat(getComputedStyle(document.documentElement).getPropertyValue("--scroll-mt"));return Number.isFinite(v)?v:Math.min(Math.max(p.clientHeight*.18,56),120)}function c(p){if(p===!1)return null;const v=(typeof p=="object"&&!Array.isArray(p)&&p&&"level"in p?p.level:p)??2;return v==="deep"?[2,6]:Array.isArray(v)?[v[0],v[1]]:[v,v]}function f(){const p=c(t.value.outline??r.value.outline);return p||null}function m(p){const v=/\b(?:VPBadge|header-anchor|footnote-ref|ignore-header)\b/;let y="";for(const b of p.childNodes)if(b.nodeType===Node.ELEMENT_NODE){const k=b;if(v.test(k.className))continue;y+=k.textContent??""}else b.nodeType===Node.TEXT_NODE&&(y+=b.textContent??"");return y.trim()}function d(){const p=f();if(!p){a.value=[];return}const[v,y]=p,b=Array.from(document.querySelectorAll(".vp-doc :where(h1,h2,h3,h4,h5,h6)")).filter(F=>F instanceof HTMLElement&&!!F.id).map(F=>{const V=Number(F.tagName.slice(1));return{title:m(F),slug:F.id,link:`#${F.id}`,level:V,children:[]}}).filter(F=>F.title&&F.level>=v&&F.level<=y),k=[],I=[];for(const F of b){for(;I.length&&I[I.length-1].level>=F.level;)I.pop();I.length?I[I.length-1].children.push(F):k.push(F),I.push(F)}a.value=k}function u(p){return p.flatMap(v=>[v,...u(v.children??[])])}function g(p,v){var y;if(!v)return[];for(const b of p){if(b.link===v)return[b];if((y=b.children)!=null&&y.length){const k=g(b.children,v);if(k.length)return[b,...k]}}return[]}function E(){var C,j,N;const p=u(a.value),v=s();if(!p.length||!v){i.value=null;return}const y=v.scrollTop,b=v.clientHeight,k=v.scrollHeight,I=l(v),F=Math.abs(y+b-k)<1;if(y<1){const w=window.location.hash,A=p.some(P=>P.link===w)?w:null;i.value=A??((C=p[0])==null?void 0:C.link)??null;return}if(F){i.value=((j=p[p.length-1])==null?void 0:j.link)??null;return}const V=window.location.hash,H=p.some(w=>w.link===V)?V:null;let Q=null;for(const w of p){const A=document.getElementById(w.slug);if(!A)continue;const P=v.getBoundingClientRect().top;if(y+A.getBoundingClientRect().top-P>y+I)break;Q=w.link}i.value=Q??H??((N=p[0])==null?void 0:N.link)??null}const h=()=>{E()};return qe(()=>{const p=s();requestAnimationFrame(()=>{d(),E()}),p==null||p.addEventListener("scroll",h,{passive:!0}),window.addEventListener("hashchange",h,{passive:!0})}),cr(()=>{const p=s();p==null||p.removeEventListener("scroll",h),window.removeEventListener("hashchange",h)}),Eo(async()=>{await Me(),d(),E()}),(p,v)=>a.value.length?(M(),O("nav",Va,[S("button",Ha,[v[0]||(v[0]=S("svg",{viewBox:"0 0 16 16",fill:"none",stroke:"currentColor","stroke-width":"2",class:"h-3 w-3","aria-hidden":"true"},[S("path",{d:"M2.5 3.5h11M2.5 8h7M2.5 12.5h11","stroke-linecap":"round"})],-1)),S("span",null,re(n.value),1)]),kt(za,{items:o.value},null,8,["items"])])):Z("",!0)}}),Ba={root:()=>R(()=>import("./@localSearchIndexroot.DxQRO9fg.js"),[])};function tn(e){return Ki()?(Ji(e),!0):!1}const So=typeof window<"u"&&typeof document<"u";typeof WorkerGlobalScope<"u"&&globalThis instanceof WorkerGlobalScope;const ja=e=>e!=null,Ua=Object.prototype.toString,Wa=e=>Ua.call(e)==="[object Object]",Yt=()=>{},Mn=Ka();function Ka(){var e,t;return So&&((e=window==null?void 0:window.navigator)==null?void 0:e.userAgent)&&(/iP(?:ad|hone|od)/.test(window.navigator.userAgent)||((t=window==null?void 0:window.navigator)==null?void 0:t.maxTouchPoints)>2&&/iPad|Macintosh/.test(window==null?void 0:window.navigator.userAgent))}function Ja(e,t){function r(...n){return new Promise((i,a)=>{Promise.resolve(e(()=>t.apply(this,n),{fn:t,thisArg:this,args:n})).then(i).catch(a)})}return r}const Ao=e=>e();function Ga(e,t={}){let r,n,i=Yt;const a=l=>{clearTimeout(l),i(),i=Yt};let o;return l=>{const c=ye(e),f=ye(t.maxWait);return r&&a(r),c<=0||f!==void 0&&f<=0?(n&&(a(n),n=null),Promise.resolve(l())):new Promise((m,d)=>{i=t.rejectOnCancel?d:m,o=l,f&&!n&&(n=setTimeout(()=>{r&&a(r),n=null,m(o())},f)),r=setTimeout(()=>{n&&a(n),n=null,m(l())},c)})}}function Ya(e=Ao,t={}){const{initialState:r="active"}=t,n=To(r==="active");function i(){n.value=!1}function a(){n.value=!0}const o=(...s)=>{n.value&&e(...s)};return{isActive:xo(n),pause:i,resume:a,eventFilter:o}}function Qa(e){return Qi()}function Wt(e){return Array.isArray(e)?e:[e]}function To(...e){if(e.length!==1)return Gi(...e);const t=e[0];return typeof t=="function"?xo(Yi(()=>({get:t,set:Yt}))):ae(t)}function Co(e,t,r={}){const{eventFilter:n=Ao,...i}=r;return pe(e,Ja(n,t),i)}function Za(e,t,r={}){const{eventFilter:n,initialState:i="active",...a}=r,{eventFilter:o,pause:s,resume:l,isActive:c}=Ya(n,{initialState:i});return{stop:Co(e,t,{...a,eventFilter:o}),pause:s,resume:l,isActive:c}}function Xa(e,t=!0,r){Qa()?qe(e,r):t?e():Me(e)}function es(e,t,r={}){const{debounce:n=0,maxWait:i=void 0,...a}=r;return Co(e,t,{...a,eventFilter:Ga(n,{maxWait:i})})}function ts(e,t,r){return pe(e,t,{...r,immediate:!0})}function Rn(e,t,r){let n;ko(r)?n={evaluating:r}:n={};const{lazy:i=!1,evaluating:a=void 0,shallow:o=!0,onError:s=Yt}=n,l=Re(!i),c=o?Re(t):ae(t);let f=0;return lr(async m=>{if(!l.value)return;f++;const d=f;let u=!1;a&&Promise.resolve().then(()=>{a.value=!0});try{const g=await e(E=>{m(()=>{a&&(a.value=!1),u||E()})});d===f&&(c.value=g)}catch(g){s(g)}finally{a&&d===f&&(a.value=!1),u=!0}}),i?q(()=>(l.value=!0,c.value)):c}const pt=So?window:void 0;function Io(e){var t;const r=ye(e);return(t=r==null?void 0:r.$el)!=null?t:r}function It(...e){const t=[],r=()=>{t.forEach(s=>s()),t.length=0},n=(s,l,c,f)=>(s.addEventListener(l,c,f),()=>s.removeEventListener(l,c,f)),i=q(()=>{const s=Wt(ye(e[0])).filter(l=>l!=null);return s.every(l=>typeof l!="string")?s:void 0}),a=ts(()=>{var s,l;return[(l=(s=i.value)==null?void 0:s.map(c=>Io(c)))!=null?l:[pt].filter(c=>c!=null),Wt(ye(i.value?e[1]:e[0])),Wt(J(i.value?e[2]:e[1])),ye(i.value?e[3]:e[2])]},([s,l,c,f])=>{if(r(),!(s!=null&&s.length)||!(l!=null&&l.length)||!(c!=null&&c.length))return;const m=Wa(f)?{...f}:f;t.push(...s.flatMap(d=>l.flatMap(u=>c.map(g=>n(d,u,g,m)))))},{flush:"post"}),o=()=>{a(),r()};return tn(r),o}function rs(e){return typeof e=="function"?e:typeof e=="string"?t=>t.key===e:Array.isArray(e)?t=>e.includes(t.key):()=>!0}function Ft(...e){let t,r,n={};e.length===3?(t=e[0],r=e[1],n=e[2]):e.length===2?typeof e[1]=="object"?(t=!0,r=e[0],n=e[1]):(t=e[0],r=e[1]):(t=!0,r=e[0]);const{target:i=pt,eventName:a="keydown",passive:o=!1,dedupe:s=!1}=n,l=rs(t);return It(i,a,f=>{f.repeat&&ye(s)||l(f)&&r(f)},o)}const Ot=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{},Pt="__vueuse_ssr_handlers__",ns=os();function os(){return Pt in Ot||(Ot[Pt]=Ot[Pt]||{}),Ot[Pt]}function is(e,t){return ns[e]||t}function as(e){return e==null?"any":e instanceof Set?"set":e instanceof Map?"map":e instanceof Date?"date":typeof e=="boolean"?"boolean":typeof e=="string"?"string":typeof e=="object"?"object":Number.isNaN(e)?"any":"number"}const ss={boolean:{read:e=>e==="true",write:e=>String(e)},object:{read:e=>JSON.parse(e),write:e=>JSON.stringify(e)},number:{read:e=>Number.parseFloat(e),write:e=>String(e)},any:{read:e=>e,write:e=>String(e)},string:{read:e=>e,write:e=>String(e)},map:{read:e=>new Map(JSON.parse(e)),write:e=>JSON.stringify(Array.from(e.entries()))},set:{read:e=>new Set(JSON.parse(e)),write:e=>JSON.stringify(Array.from(e))},date:{read:e=>new Date(e),write:e=>e.toISOString()}},Fn="vueuse-storage";function Lo(e,t,r,n={}){var i;const{flush:a="pre",deep:o=!0,listenToStorageChanges:s=!0,writeDefaults:l=!0,mergeDefaults:c=!1,shallow:f,window:m=pt,eventFilter:d,onError:u=C=>{console.error(C)},initOnMounted:g}=n,E=(f?Re:ae)(typeof t=="function"?t():t),h=q(()=>ye(e));if(!r)try{r=is("getDefaultStorage",()=>{var C;return(C=pt)==null?void 0:C.localStorage})()}catch(C){u(C)}if(!r)return E;const p=ye(t),v=as(p),y=(i=n.serializer)!=null?i:ss[v],{pause:b,resume:k}=Za(E,()=>F(E.value),{flush:a,deep:o,eventFilter:d});pe(h,()=>H(),{flush:a}),m&&s&&Xa(()=>{r instanceof Storage?It(m,"storage",H,{passive:!0}):It(m,Fn,Q),g&&H()}),g||H();function I(C,j){if(m){const N={key:h.value,oldValue:C,newValue:j,storageArea:r};m.dispatchEvent(r instanceof Storage?new StorageEvent("storage",N):new CustomEvent(Fn,{detail:N}))}}function F(C){try{const j=r.getItem(h.value);if(C==null)I(j,null),r.removeItem(h.value);else{const N=y.write(C);j!==N&&(r.setItem(h.value,N),I(j,N))}}catch(j){u(j)}}function V(C){const j=C?C.newValue:r.getItem(h.value);if(j==null)return l&&p!=null&&r.setItem(h.value,y.write(p)),p;if(!C&&c){const N=y.read(j);return typeof c=="function"?c(N,p):v==="object"&&!Array.isArray(N)?{...p,...N}:N}else return typeof j!="string"?j:y.read(j)}function H(C){if(!(C&&C.storageArea!==r)){if(C&&C.key==null){E.value=p;return}if(!(C&&C.key!==h.value)){b();try{(C==null?void 0:C.newValue)!==y.write(E.value)&&(E.value=V(C))}catch(j){u(j)}finally{C?Me(k):k()}}}}function Q(C){H(C.detail)}return E}function yr(e){return typeof Window<"u"&&e instanceof Window?e.document.documentElement:typeof Document<"u"&&e instanceof Document?e.documentElement:e}function ls(e,t,r={}){const{window:n=pt}=r;return Lo(e,t,n==null?void 0:n.localStorage,r)}function Mo(e){const t=window.getComputedStyle(e);if(t.overflowX==="scroll"||t.overflowY==="scroll"||t.overflowX==="auto"&&e.clientWidth<e.scrollWidth||t.overflowY==="auto"&&e.clientHeight<e.scrollHeight)return!0;{const r=e.parentNode;return!r||r.tagName==="BODY"?!1:Mo(r)}}function cs(e){const t=e||window.event,r=t.target;return Mo(r)?!1:t.touches.length>1?!0:(t.preventDefault&&t.preventDefault(),!1)}const wr=new WeakMap;function us(e,t=!1){const r=Re(t);let n=null,i="";pe(To(e),s=>{const l=yr(ye(s));if(l){const c=l;if(wr.get(c)||wr.set(c,c.style.overflow),c.style.overflow!=="hidden"&&(i=c.style.overflow),c.style.overflow==="hidden")return r.value=!0;if(r.value)return c.style.overflow="hidden"}},{immediate:!0});const a=()=>{const s=yr(ye(e));!s||r.value||(Mn&&(n=It(s,"touchmove",l=>{cs(l)},{passive:!1})),s.style.overflow="hidden",r.value=!0)},o=()=>{const s=yr(ye(e));!s||!r.value||(Mn&&(n==null||n()),s.style.overflow=i,wr.delete(s),r.value=!1)};return tn(o),q({get(){return r.value},set(s){s?a():o()}})}function ds(e,t,r={}){const{window:n=pt}=r;return Lo(e,t,n==null?void 0:n.sessionStorage,r)}/*!
* tabbable 6.4.0
* @license MIT, https://github.com/focus-trap/tabbable/blob/master/LICENSE
*/var Ro=["input:not([inert]):not([inert] *)","select:not([inert]):not([inert] *)","textarea:not([inert]):not([inert] *)","a[href]:not([inert]):not([inert] *)","button:not([inert]):not([inert] *)","[tabindex]:not(slot):not([inert]):not([inert] *)","audio[controls]:not([inert]):not([inert] *)","video[controls]:not([inert]):not([inert] *)",'[contenteditable]:not([contenteditable="false"]):not([inert]):not([inert] *)',"details>summary:first-of-type:not([inert]):not([inert] *)","details:not([inert]):not([inert] *)"],Qt=Ro.join(","),Fo=typeof Element>"u",tt=Fo?function(){}:Element.prototype.matches||Element.prototype.msMatchesSelector||Element.prototype.webkitMatchesSelector,Zt=!Fo&&Element.prototype.getRootNode?function(e){var t;return e==null||(t=e.getRootNode)===null||t===void 0?void 0:t.call(e)}:function(e){return e==null?void 0:e.ownerDocument},Xt=function(t,r){var n;r===void 0&&(r=!0);var i=t==null||(n=t.getAttribute)===null||n===void 0?void 0:n.call(t,"inert"),a=i===""||i==="true",o=a||r&&t&&(typeof t.closest=="function"?t.closest("[inert]"):Xt(t.parentNode));return o},fs=function(t){var r,n=t==null||(r=t.getAttribute)===null||r===void 0?void 0:r.call(t,"contenteditable");return n===""||n==="true"},Oo=function(t,r,n){if(Xt(t))return[];var i=Array.prototype.slice.apply(t.querySelectorAll(Qt));return r&&tt.call(t,Qt)&&i.unshift(t),i=i.filter(n),i},er=function(t,r,n){for(var i=[],a=Array.from(t);a.length;){var o=a.shift();if(!Xt(o,!1))if(o.tagName==="SLOT"){var s=o.assignedElements(),l=s.length?s:o.children,c=er(l,!0,n);n.flatten?i.push.apply(i,c):i.push({scopeParent:o,candidates:c})}else{var f=tt.call(o,Qt);f&&n.filter(o)&&(r||!t.includes(o))&&i.push(o);var m=o.shadowRoot||typeof n.getShadowRoot=="function"&&n.getShadowRoot(o),d=!Xt(m,!1)&&(!n.shadowRootFilter||n.shadowRootFilter(o));if(m&&d){var u=er(m===!0?o.children:m.children,!0,n);n.flatten?i.push.apply(i,u):i.push({scopeParent:o,candidates:u})}else a.unshift.apply(a,o.children)}}return i},Po=function(t){return!isNaN(parseInt(t.getAttribute("tabindex"),10))},Xe=function(t){if(!t)throw new Error("No node provided");return t.tabIndex<0&&(/^(AUDIO|VIDEO|DETAILS)$/.test(t.tagName)||fs(t))&&!Po(t)?0:t.tabIndex},ms=function(t,r){var n=Xe(t);return n<0&&r&&!Po(t)?0:n},hs=function(t,r){return t.tabIndex===r.tabIndex?t.documentOrder-r.documentOrder:t.tabIndex-r.tabIndex},No=function(t){return t.tagName==="INPUT"},ps=function(t){return No(t)&&t.type==="hidden"},gs=function(t){var r=t.tagName==="DETAILS"&&Array.prototype.slice.apply(t.children).some(function(n){return n.tagName==="SUMMARY"});return r},vs=function(t,r){for(var n=0;n<t.length;n++)if(t[n].checked&&t[n].form===r)return t[n]},bs=function(t){if(!t.name)return!0;var r=t.form||Zt(t),n=function(s){return r.querySelectorAll('input[type="radio"][name="'+s+'"]')},i;if(typeof window<"u"&&typeof window.CSS<"u"&&typeof window.CSS.escape=="function")i=n(window.CSS.escape(t.name));else try{i=n(t.name)}catch(o){return console.error("Looks like you have a radio button with a name attribute containing invalid CSS selector characters and need the CSS.escape polyfill: %s",o.message),!1}var a=vs(i,t.form);return!a||a===t},ys=function(t){return No(t)&&t.type==="radio"},ws=function(t){return ys(t)&&!bs(t)},Es=function(t){var r,n=t&&Zt(t),i=(r=n)===null||r===void 0?void 0:r.host,a=!1;if(n&&n!==t){var o,s,l;for(a=!!((o=i)!==null&&o!==void 0&&(s=o.ownerDocument)!==null&&s!==void 0&&s.contains(i)||t!=null&&(l=t.ownerDocument)!==null&&l!==void 0&&l.contains(t));!a&&i;){var c,f,m;n=Zt(i),i=(c=n)===null||c===void 0?void 0:c.host,a=!!((f=i)!==null&&f!==void 0&&(m=f.ownerDocument)!==null&&m!==void 0&&m.contains(i))}}return a},On=function(t){var r=t.getBoundingClientRect(),n=r.width,i=r.height;return n===0&&i===0},xs=function(t,r){var n=r.displayCheck,i=r.getShadowRoot;if(n==="full-native"&&"checkVisibility"in t){var a=t.checkVisibility({checkOpacity:!1,opacityProperty:!1,contentVisibilityAuto:!0,visibilityProperty:!0,checkVisibilityCSS:!0});return!a}if(getComputedStyle(t).visibility==="hidden")return!0;var o=tt.call(t,"details>summary:first-of-type"),s=o?t.parentElement:t;if(tt.call(s,"details:not([open]) *"))return!0;if(!n||n==="full"||n==="full-native"||n==="legacy-full"){if(typeof i=="function"){for(var l=t;t;){var c=t.parentElement,f=Zt(t);if(c&&!c.shadowRoot&&i(c)===!0)return On(t);t.assignedSlot?t=t.assignedSlot:!c&&f!==t.ownerDocument?t=f.host:t=c}t=l}if(Es(t))return!t.getClientRects().length;if(n!=="legacy-full")return!0}else if(n==="non-zero-area")return On(t);return!1},ks=function(t){if(/^(INPUT|BUTTON|SELECT|TEXTAREA)$/.test(t.tagName))for(var r=t.parentElement;r;){if(r.tagName==="FIELDSET"&&r.disabled){for(var n=0;n<r.children.length;n++){var i=r.children.item(n);if(i.tagName==="LEGEND")return tt.call(r,"fieldset[disabled] *")?!0:!i.contains(t)}return!0}r=r.parentElement}return!1},tr=function(t,r){return!(r.disabled||ps(r)||xs(r,t)||gs(r)||ks(r))},Dr=function(t,r){return!(ws(r)||Xe(r)<0||!tr(t,r))},_s=function(t){var r=parseInt(t.getAttribute("tabindex"),10);return!!(isNaN(r)||r>=0)},Do=function(t){var r=[],n=[];return t.forEach(function(i,a){var o=!!i.scopeParent,s=o?i.scopeParent:i,l=ms(s,o),c=o?Do(i.candidates):s;l===0?o?r.push.apply(r,c):r.push(s):n.push({documentOrder:a,tabIndex:l,item:i,isScope:o,content:c})}),n.sort(hs).reduce(function(i,a){return a.isScope?i.push.apply(i,a.content):i.push(a.content),i},[]).concat(r)},Ss=function(t,r){r=r||{};var n;return r.getShadowRoot?n=er([t],r.includeContainer,{filter:Dr.bind(null,r),flatten:!1,getShadowRoot:r.getShadowRoot,shadowRootFilter:_s}):n=Oo(t,r.includeContainer,Dr.bind(null,r)),Do(n)},As=function(t,r){r=r||{};var n;return r.getShadowRoot?n=er([t],r.includeContainer,{filter:tr.bind(null,r),flatten:!0,getShadowRoot:r.getShadowRoot}):n=Oo(t,r.includeContainer,tr.bind(null,r)),n},ot=function(t,r){if(r=r||{},!t)throw new Error("No node provided");return tt.call(t,Qt)===!1?!1:Dr(r,t)},Ts=Ro.concat("iframe:not([inert]):not([inert] *)").join(","),Er=function(t,r){if(r=r||{},!t)throw new Error("No node provided");return tt.call(t,Ts)===!1?!1:tr(r,t)};/*!
* focus-trap 7.8.0
* @license MIT, https://github.com/focus-trap/focus-trap/blob/master/LICENSE
*/function $r(e,t){(t==null||t>e.length)&&(t=e.length);for(var r=0,n=Array(t);r<t;r++)n[r]=e[r];return n}function Cs(e){if(Array.isArray(e))return $r(e)}function Pn(e,t){var r=typeof Symbol<"u"&&e[Symbol.iterator]||e["@@iterator"];if(!r){if(Array.isArray(e)||(r=$o(e))||t){r&&(e=r);var n=0,i=function(){};return{s:i,n:function(){return n>=e.length?{done:!0}:{done:!1,value:e[n++]}},e:function(l){throw l},f:i}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var a,o=!0,s=!1;return{s:function(){r=r.call(e)},n:function(){var l=r.next();return o=l.done,l},e:function(l){s=!0,a=l},f:function(){try{o||r.return==null||r.return()}finally{if(s)throw a}}}}function Is(e,t,r){return(t=Os(t))in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function Ls(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function Ms(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function Nn(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter(function(i){return Object.getOwnPropertyDescriptor(e,i).enumerable})),r.push.apply(r,n)}return r}function Dn(e){for(var t=1;t<arguments.length;t++){var r=arguments[t]!=null?arguments[t]:{};t%2?Nn(Object(r),!0).forEach(function(n){Is(e,n,r[n])}):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):Nn(Object(r)).forEach(function(n){Object.defineProperty(e,n,Object.getOwnPropertyDescriptor(r,n))})}return e}function Rs(e){return Cs(e)||Ls(e)||$o(e)||Ms()}function Fs(e,t){if(typeof e!="object"||!e)return e;var r=e[Symbol.toPrimitive];if(r!==void 0){var n=r.call(e,t);if(typeof n!="object")return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}function Os(e){var t=Fs(e,"string");return typeof t=="symbol"?t:t+""}function $o(e,t){if(e){if(typeof e=="string")return $r(e,t);var r={}.toString.call(e).slice(8,-1);return r==="Object"&&e.constructor&&(r=e.constructor.name),r==="Map"||r==="Set"?Array.from(e):r==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?$r(e,t):void 0}}var He={getActiveTrap:function(t){return(t==null?void 0:t.length)>0?t[t.length-1]:null},activateTrap:function(t,r){var n=He.getActiveTrap(t);r!==n&&He.pauseTrap(t);var i=t.indexOf(r);i===-1||t.splice(i,1),t.push(r)},deactivateTrap:function(t,r){var n=t.indexOf(r);n!==-1&&t.splice(n,1),He.unpauseTrap(t)},pauseTrap:function(t){var r=He.getActiveTrap(t);r==null||r._setPausedState(!0)},unpauseTrap:function(t){var r=He.getActiveTrap(t);r&&!r._isManuallyPaused()&&r._setPausedState(!1)}},Ps=function(t){return t.tagName&&t.tagName.toLowerCase()==="input"&&typeof t.select=="function"},Ns=function(t){return(t==null?void 0:t.key)==="Escape"||(t==null?void 0:t.key)==="Esc"||(t==null?void 0:t.keyCode)===27},St=function(t){return(t==null?void 0:t.key)==="Tab"||(t==null?void 0:t.keyCode)===9},Ds=function(t){return St(t)&&!t.shiftKey},$s=function(t){return St(t)&&t.shiftKey},$n=function(t){return setTimeout(t,0)},Et=function(t){for(var r=arguments.length,n=new Array(r>1?r-1:0),i=1;i<r;i++)n[i-1]=arguments[i];return typeof t=="function"?t.apply(void 0,n):t},Nt=function(t){return t.target.shadowRoot&&typeof t.composedPath=="function"?t.composedPath()[0]:t.target},zs=[],Vs=function(t,r){var n=(r==null?void 0:r.document)||document,i=(r==null?void 0:r.trapStack)||zs,a=Dn({returnFocusOnDeactivate:!0,escapeDeactivates:!0,delayInitialFocus:!0,isolateSubtrees:!1,isKeyForward:Ds,isKeyBackward:$s},r),o={containers:[],containerGroups:[],tabbableGroups:[],adjacentElements:new Set,alreadySilent:new Set,nodeFocusedBeforeActivation:null,mostRecentlyFocusedNode:null,active:!1,paused:!1,manuallyPaused:!1,delayInitialFocusTimer:void 0,recentNavEvent:void 0},s,l=function(w,A,P){return w&&w[A]!==void 0?w[A]:a[P||A]},c=function(w,A){var P=typeof(A==null?void 0:A.composedPath)=="function"?A.composedPath():void 0;return o.containerGroups.findIndex(function(W){var z=W.container,Y=W.tabbableNodes;return z.contains(w)||(P==null?void 0:P.includes(z))||Y.find(function(U){return U===w})})},f=function(w){var A=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},P=A.hasFallback,W=P===void 0?!1:P,z=A.params,Y=z===void 0?[]:z,U=a[w];if(typeof U=="function"&&(U=U.apply(void 0,Rs(Y))),U===!0&&(U=void 0),!U){if(U===void 0||U===!1)return U;throw new Error("`".concat(w,"` was specified but was not a node, or did not return a node"))}var L=U;if(typeof U=="string"){try{L=n.querySelector(U)}catch(T){throw new Error("`".concat(w,'` appears to be an invalid selector; error="').concat(T.message,'"'))}if(!L&&!W)throw new Error("`".concat(w,"` as selector refers to no known node"))}return L},m=function(){var w=f("initialFocus",{hasFallback:!0});if(w===!1)return!1;if(w===void 0||w&&!Er(w,a.tabbableOptions))if(c(n.activeElement)>=0)w=n.activeElement;else{var A=o.tabbableGroups[0],P=A&&A.firstTabbableNode;w=P||f("fallbackFocus")}else w===null&&(w=f("fallbackFocus"));if(!w)throw new Error("Your focus-trap needs to have at least one focusable element");return w},d=function(){if(o.containerGroups=o.containers.map(function(w){var A=Ss(w,a.tabbableOptions),P=As(w,a.tabbableOptions),W=A.length>0?A[0]:void 0,z=A.length>0?A[A.length-1]:void 0,Y=P.find(function(T){return ot(T)}),U=P.slice().reverse().find(function(T){return ot(T)}),L=!!A.find(function(T){return Xe(T)>0});return{container:w,tabbableNodes:A,focusableNodes:P,posTabIndexesFound:L,firstTabbableNode:W,lastTabbableNode:z,firstDomTabbableNode:Y,lastDomTabbableNode:U,nextTabbableNode:function(G){var te=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!0,$=A.indexOf(G);return $<0?te?P.slice(P.indexOf(G)+1).find(function(X){return ot(X)}):P.slice(0,P.indexOf(G)).reverse().find(function(X){return ot(X)}):A[$+(te?1:-1)]}}}),o.tabbableGroups=o.containerGroups.filter(function(w){return w.tabbableNodes.length>0}),o.tabbableGroups.length<=0&&!f("fallbackFocus"))throw new Error("Your focus-trap must have at least one container with at least one tabbable node in it at all times");if(o.containerGroups.find(function(w){return w.posTabIndexesFound})&&o.containerGroups.length>1)throw new Error("At least one node with a positive tabindex was found in one of your focus-trap's multiple containers. Positive tabindexes are only supported in single-container focus-traps.")},u=function(w){var A=w.activeElement;if(A)return A.shadowRoot&&A.shadowRoot.activeElement!==null?u(A.shadowRoot):A},g=function(w){if(w!==!1&&w!==u(document)){if(!w||!w.focus){g(m());return}w.focus({preventScroll:!!a.preventScroll}),o.mostRecentlyFocusedNode=w,Ps(w)&&w.select()}},E=function(w){var A=f("setReturnFocus",{params:[w]});return A||(A===!1?!1:w)},h=function(w){var A=w.target,P=w.event,W=w.isBackward,z=W===void 0?!1:W;A=A||Nt(P),d();var Y=null;if(o.tabbableGroups.length>0){var U=c(A,P),L=U>=0?o.containerGroups[U]:void 0;if(U<0)z?Y=o.tabbableGroups[o.tabbableGroups.length-1].lastTabbableNode:Y=o.tabbableGroups[0].firstTabbableNode;else if(z){var T=o.tabbableGroups.findIndex(function(ee){var se=ee.firstTabbableNode;return A===se});if(T<0&&(L.container===A||Er(A,a.tabbableOptions)&&!ot(A,a.tabbableOptions)&&!L.nextTabbableNode(A,!1))&&(T=U),T>=0){var G=T===0?o.tabbableGroups.length-1:T-1,te=o.tabbableGroups[G];Y=Xe(A)>=0?te.lastTabbableNode:te.lastDomTabbableNode}else St(P)||(Y=L.nextTabbableNode(A,!1))}else{var $=o.tabbableGroups.findIndex(function(ee){var se=ee.lastTabbableNode;return A===se});if($<0&&(L.container===A||Er(A,a.tabbableOptions)&&!ot(A,a.tabbableOptions)&&!L.nextTabbableNode(A))&&($=U),$>=0){var X=$===o.tabbableGroups.length-1?0:$+1,ne=o.tabbableGroups[X];Y=Xe(A)>=0?ne.firstTabbableNode:ne.firstDomTabbableNode}else St(P)||(Y=L.nextTabbableNode(A))}}else Y=f("fallbackFocus");return Y},p=function(w){var A=Nt(w);if(!(c(A,w)>=0)){if(Et(a.clickOutsideDeactivates,w)){s.deactivate({returnFocus:a.returnFocusOnDeactivate});return}Et(a.allowOutsideClick,w)||w.preventDefault()}},v=function(w){var A=Nt(w),P=c(A,w)>=0;if(P||A instanceof Document)P&&(o.mostRecentlyFocusedNode=A);else{w.stopImmediatePropagation();var W,z=!0;if(o.mostRecentlyFocusedNode)if(Xe(o.mostRecentlyFocusedNode)>0){var Y=c(o.mostRecentlyFocusedNode),U=o.containerGroups[Y].tabbableNodes;if(U.length>0){var L=U.findIndex(function(T){return T===o.mostRecentlyFocusedNode});L>=0&&(a.isKeyForward(o.recentNavEvent)?L+1<U.length&&(W=U[L+1],z=!1):L-1>=0&&(W=U[L-1],z=!1))}}else o.containerGroups.some(function(T){return T.tabbableNodes.some(function(G){return Xe(G)>0})})||(z=!1);else z=!1;z&&(W=h({target:o.mostRecentlyFocusedNode,isBackward:a.isKeyBackward(o.recentNavEvent)})),g(W||o.mostRecentlyFocusedNode||m())}o.recentNavEvent=void 0},y=function(w){var A=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;o.recentNavEvent=w;var P=h({event:w,isBackward:A});P&&(St(w)&&w.preventDefault(),g(P))},b=function(w){(a.isKeyForward(w)||a.isKeyBackward(w))&&y(w,a.isKeyBackward(w))},k=function(w){Ns(w)&&Et(a.escapeDeactivates,w)!==!1&&(w.preventDefault(),s.deactivate())},I=function(w){var A=Nt(w);c(A,w)>=0||Et(a.clickOutsideDeactivates,w)||Et(a.allowOutsideClick,w)||(w.preventDefault(),w.stopImmediatePropagation())},F=function(){if(o.active)return He.activateTrap(i,s),o.delayInitialFocusTimer=a.delayInitialFocus?$n(function(){g(m())}):g(m()),n.addEventListener("focusin",v,!0),n.addEventListener("mousedown",p,{capture:!0,passive:!1}),n.addEventListener("touchstart",p,{capture:!0,passive:!1}),n.addEventListener("click",I,{capture:!0,passive:!1}),n.addEventListener("keydown",b,{capture:!0,passive:!1}),n.addEventListener("keydown",k),s},V=function(w){o.active&&!o.paused&&s._setSubtreeIsolation(!1),o.adjacentElements.clear(),o.alreadySilent.clear();var A=new Set,P=new Set,W=Pn(w),z;try{for(W.s();!(z=W.n()).done;){var Y=z.value;A.add(Y);for(var U=typeof ShadowRoot<"u"&&Y.getRootNode()instanceof ShadowRoot,L=Y;L;){A.add(L);var T=L.parentElement,G=[];T?G=T.children:!T&&U&&(G=L.getRootNode().children,T=L.getRootNode().host,U=typeof ShadowRoot<"u"&&T.getRootNode()instanceof ShadowRoot);var te=Pn(G),$;try{for(te.s();!($=te.n()).done;){var X=$.value;P.add(X)}}catch(ne){te.e(ne)}finally{te.f()}L=T}}}catch(ne){W.e(ne)}finally{W.f()}A.forEach(function(ne){P.delete(ne)}),o.adjacentElements=P},H=function(){if(o.active)return n.removeEventListener("focusin",v,!0),n.removeEventListener("mousedown",p,!0),n.removeEventListener("touchstart",p,!0),n.removeEventListener("click",I,!0),n.removeEventListener("keydown",b,!0),n.removeEventListener("keydown",k),s},Q=function(w){var A=w.some(function(P){var W=Array.from(P.removedNodes);return W.some(function(z){return z===o.mostRecentlyFocusedNode})});A&&g(m())},C=typeof window<"u"&&"MutationObserver"in window?new MutationObserver(Q):void 0,j=function(){C&&(C.disconnect(),o.active&&!o.paused&&o.containers.map(function(w){C.observe(w,{subtree:!0,childList:!0})}))};return s={get active(){return o.active},get paused(){return o.paused},activate:function(w){if(o.active)return this;var A=l(w,"onActivate"),P=l(w,"onPostActivate"),W=l(w,"checkCanFocusTrap"),z=He.getActiveTrap(i),Y=!1;if(z&&!z.paused){var U;(U=z._setSubtreeIsolation)===null||U===void 0||U.call(z,!1),Y=!0}try{W||d(),o.active=!0,o.paused=!1,o.nodeFocusedBeforeActivation=u(n),A==null||A();var L=function(){W&&d(),F(),j(),a.isolateSubtrees&&s._setSubtreeIsolation(!0),P==null||P()};if(W)return W(o.containers.concat()).then(L,L),this;L()}catch(G){if(z===He.getActiveTrap(i)&&Y){var T;(T=z._setSubtreeIsolation)===null||T===void 0||T.call(z,!0)}throw G}return this},deactivate:function(w){if(!o.active)return this;var A=Dn({onDeactivate:a.onDeactivate,onPostDeactivate:a.onPostDeactivate,checkCanReturnFocus:a.checkCanReturnFocus},w);clearTimeout(o.delayInitialFocusTimer),o.delayInitialFocusTimer=void 0,o.paused||s._setSubtreeIsolation(!1),o.alreadySilent.clear(),H(),o.active=!1,o.paused=!1,j(),He.deactivateTrap(i,s);var P=l(A,"onDeactivate"),W=l(A,"onPostDeactivate"),z=l(A,"checkCanReturnFocus"),Y=l(A,"returnFocus","returnFocusOnDeactivate");P==null||P();var U=function(){$n(function(){Y&&g(E(o.nodeFocusedBeforeActivation)),W==null||W()})};return Y&&z?(z(E(o.nodeFocusedBeforeActivation)).then(U,U),this):(U(),this)},pause:function(w){return o.active?(o.manuallyPaused=!0,this._setPausedState(!0,w)):this},unpause:function(w){return o.active?(o.manuallyPaused=!1,i[i.length-1]!==this?this:this._setPausedState(!1,w)):this},updateContainerElements:function(w){var A=[].concat(w).filter(Boolean);return o.containers=A.map(function(P){return typeof P=="string"?n.querySelector(P):P}),a.isolateSubtrees&&V(o.containers),o.active&&(d(),a.isolateSubtrees&&!o.paused&&s._setSubtreeIsolation(!0)),j(),this}},Object.defineProperties(s,{_isManuallyPaused:{value:function(){return o.manuallyPaused}},_setPausedState:{value:function(w,A){if(o.paused===w)return this;if(o.paused=w,w){var P=l(A,"onPause"),W=l(A,"onPostPause");P==null||P(),H(),j(),s._setSubtreeIsolation(!1),W==null||W()}else{var z=l(A,"onUnpause"),Y=l(A,"onPostUnpause");z==null||z(),s._setSubtreeIsolation(!0),d(),F(),j(),Y==null||Y()}return this}},_setSubtreeIsolation:{value:function(w){a.isolateSubtrees&&o.adjacentElements.forEach(function(A){var P;if(w)switch(a.isolateSubtrees){case"aria-hidden":(A.ariaHidden==="true"||((P=A.getAttribute("aria-hidden"))===null||P===void 0?void 0:P.toLowerCase())==="true")&&o.alreadySilent.add(A),A.setAttribute("aria-hidden","true");break;default:(A.inert||A.hasAttribute("inert"))&&o.alreadySilent.add(A),A.setAttribute("inert",!0);break}else if(!o.alreadySilent.has(A))switch(a.isolateSubtrees){case"aria-hidden":A.removeAttribute("aria-hidden");break;default:A.removeAttribute("inert");break}})}}}),s.updateContainerElements(t),s};function Hs(e,t={}){let r;const{immediate:n,...i}=t,a=Re(!1),o=Re(!1),s=d=>r&&r.activate(d),l=d=>r&&r.deactivate(d),c=()=>{r&&(r.pause(),o.value=!0)},f=()=>{r&&(r.unpause(),o.value=!1)},m=q(()=>{const d=ye(e);return Wt(d).map(u=>{const g=ye(u);return typeof g=="string"?g:Io(g)}).filter(ja)});return pe(m,d=>{d.length&&(r=Vs(d,{...i,onActivate(){a.value=!0,t.onActivate&&t.onActivate()},onDeactivate(){a.value=!1,t.onDeactivate&&t.onDeactivate()}}),n&&s())},{flush:"post"}),tn(()=>l()),{hasFocus:a,isPaused:o,activate:s,deactivate:l,pause:c,unpause:f}}var qs=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function Bs(e){return e&&e.__esModule&&Object.prototype.hasOwnProperty.call(e,"default")?e.default:e}var zo={exports:{}};/*!***************************************************
* mark.js v8.11.1
* https://markjs.io/
* Copyright (c) 2014–2018, Julian Kühnel
* Released under the MIT license https://git.io/vwTVl
*****************************************************/(function(e,t){(function(r,n){e.exports=n()})(qs,function(){class r{constructor(o,s=!0,l=[],c=5e3){this.ctx=o,this.iframes=s,this.exclude=l,this.iframesTimeout=c}static matches(o,s){const l=typeof s=="string"?[s]:s,c=o.matches||o.matchesSelector||o.msMatchesSelector||o.mozMatchesSelector||o.oMatchesSelector||o.webkitMatchesSelector;if(c){let f=!1;return l.every(m=>c.call(o,m)?(f=!0,!1):!0),f}else return!1}getContexts(){let o,s=[];return typeof this.ctx>"u"||!this.ctx?o=[]:NodeList.prototype.isPrototypeOf(this.ctx)?o=Array.prototype.slice.call(this.ctx):Array.isArray(this.ctx)?o=this.ctx:typeof this.ctx=="string"?o=Array.prototype.slice.call(document.querySelectorAll(this.ctx)):o=[this.ctx],o.forEach(l=>{const c=s.filter(f=>f.contains(l)).length>0;s.indexOf(l)===-1&&!c&&s.push(l)}),s}getIframeContents(o,s,l=()=>{}){let c;try{const f=o.contentWindow;if(c=f.document,!f||!c)throw new Error("iframe inaccessible")}catch{l()}c&&s(c)}isIframeBlank(o){const s="about:blank",l=o.getAttribute("src").trim();return o.contentWindow.location.href===s&&l!==s&&l}observeIframeLoad(o,s,l){let c=!1,f=null;const m=()=>{if(!c){c=!0,clearTimeout(f);try{this.isIframeBlank(o)||(o.removeEventListener("load",m),this.getIframeContents(o,s,l))}catch{l()}}};o.addEventListener("load",m),f=setTimeout(m,this.iframesTimeout)}onIframeReady(o,s,l){try{o.contentWindow.document.readyState==="complete"?this.isIframeBlank(o)?this.observeIframeLoad(o,s,l):this.getIframeContents(o,s,l):this.observeIframeLoad(o,s,l)}catch{l()}}waitForIframes(o,s){let l=0;this.forEachIframe(o,()=>!0,c=>{l++,this.waitForIframes(c.querySelector("html"),()=>{--l||s()})},c=>{c||s()})}forEachIframe(o,s,l,c=()=>{}){let f=o.querySelectorAll("iframe"),m=f.length,d=0;f=Array.prototype.slice.call(f);const u=()=>{--m<=0&&c(d)};m||u(),f.forEach(g=>{r.matches(g,this.exclude)?u():this.onIframeReady(g,E=>{s(g)&&(d++,l(E)),u()},u)})}createIterator(o,s,l){return document.createNodeIterator(o,s,l,!1)}createInstanceOnIframe(o){return new r(o.querySelector("html"),this.iframes)}compareNodeIframe(o,s,l){const c=o.compareDocumentPosition(l),f=Node.DOCUMENT_POSITION_PRECEDING;if(c&f)if(s!==null){const m=s.compareDocumentPosition(l),d=Node.DOCUMENT_POSITION_FOLLOWING;if(m&d)return!0}else return!0;return!1}getIteratorNode(o){const s=o.previousNode();let l;return s===null?l=o.nextNode():l=o.nextNode()&&o.nextNode(),{prevNode:s,node:l}}checkIframeFilter(o,s,l,c){let f=!1,m=!1;return c.forEach((d,u)=>{d.val===l&&(f=u,m=d.handled)}),this.compareNodeIframe(o,s,l)?(f===!1&&!m?c.push({val:l,handled:!0}):f!==!1&&!m&&(c[f].handled=!0),!0):(f===!1&&c.push({val:l,handled:!1}),!1)}handleOpenIframes(o,s,l,c){o.forEach(f=>{f.handled||this.getIframeContents(f.val,m=>{this.createInstanceOnIframe(m).forEachNode(s,l,c)})})}iterateThroughNodes(o,s,l,c,f){const m=this.createIterator(s,o,c);let d=[],u=[],g,E,h=()=>({prevNode:E,node:g}=this.getIteratorNode(m),g);for(;h();)this.iframes&&this.forEachIframe(s,p=>this.checkIframeFilter(g,E,p,d),p=>{this.createInstanceOnIframe(p).forEachNode(o,v=>u.push(v),c)}),u.push(g);u.forEach(p=>{l(p)}),this.iframes&&this.handleOpenIframes(d,o,l,c),f()}forEachNode(o,s,l,c=()=>{}){const f=this.getContexts();let m=f.length;m||c(),f.forEach(d=>{const u=()=>{this.iterateThroughNodes(o,d,s,l,()=>{--m<=0&&c()})};this.iframes?this.waitForIframes(d,u):u()})}}class n{constructor(o){this.ctx=o,this.ie=!1;const s=window.navigator.userAgent;(s.indexOf("MSIE")>-1||s.indexOf("Trident")>-1)&&(this.ie=!0)}set opt(o){this._opt=Object.assign({},{element:"",className:"",exclude:[],iframes:!1,iframesTimeout:5e3,separateWordSearch:!0,diacritics:!0,synonyms:{},accuracy:"partially",acrossElements:!1,caseSensitive:!1,ignoreJoiners:!1,ignoreGroups:0,ignorePunctuation:[],wildcards:"disabled",each:()=>{},noMatch:()=>{},filter:()=>!0,done:()=>{},debug:!1,log:window.console},o)}get opt(){return this._opt}get iterator(){return new r(this.ctx,this.opt.iframes,this.opt.exclude,this.opt.iframesTimeout)}log(o,s="debug"){const l=this.opt.log;this.opt.debug&&typeof l=="object"&&typeof l[s]=="function"&&l[s](`mark.js: ${o}`)}escapeStr(o){return o.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")}createRegExp(o){return this.opt.wildcards!=="disabled"&&(o=this.setupWildcardsRegExp(o)),o=this.escapeStr(o),Object.keys(this.opt.synonyms).length&&(o=this.createSynonymsRegExp(o)),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.setupIgnoreJoinersRegExp(o)),this.opt.diacritics&&(o=this.createDiacriticsRegExp(o)),o=this.createMergedBlanksRegExp(o),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.createJoinersRegExp(o)),this.opt.wildcards!=="disabled"&&(o=this.createWildcardsRegExp(o)),o=this.createAccuracyRegExp(o),o}createSynonymsRegExp(o){const s=this.opt.synonyms,l=this.opt.caseSensitive?"":"i",c=this.opt.ignoreJoiners||this.opt.ignorePunctuation.length?"\0":"";for(let f in s)if(s.hasOwnProperty(f)){const m=s[f],d=this.opt.wildcards!=="disabled"?this.setupWildcardsRegExp(f):this.escapeStr(f),u=this.opt.wildcards!=="disabled"?this.setupWildcardsRegExp(m):this.escapeStr(m);d!==""&&u!==""&&(o=o.replace(new RegExp(`(${this.escapeStr(d)}|${this.escapeStr(u)})`,`gm${l}`),c+`(${this.processSynomyms(d)}|${this.processSynomyms(u)})`+c))}return o}processSynomyms(o){return(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.setupIgnoreJoinersRegExp(o)),o}setupWildcardsRegExp(o){return o=o.replace(/(?:\\)*\?/g,s=>s.charAt(0)==="\\"?"?":""),o.replace(/(?:\\)*\*/g,s=>s.charAt(0)==="\\"?"*":"")}createWildcardsRegExp(o){let s=this.opt.wildcards==="withSpaces";return o.replace(/\u0001/g,s?"[\\S\\s]?":"\\S?").replace(/\u0002/g,s?"[\\S\\s]*?":"\\S*")}setupIgnoreJoinersRegExp(o){return o.replace(/[^(|)\\]/g,(s,l,c)=>{let f=c.charAt(l+1);return/[(|)\\]/.test(f)||f===""?s:s+"\0"})}createJoinersRegExp(o){let s=[];const l=this.opt.ignorePunctuation;return Array.isArray(l)&&l.length&&s.push(this.escapeStr(l.join(""))),this.opt.ignoreJoiners&&s.push("\\u00ad\\u200b\\u200c\\u200d"),s.length?o.split(/\u0000+/).join(`[${s.join("")}]*`):o}createDiacriticsRegExp(o){const s=this.opt.caseSensitive?"":"i",l=this.opt.caseSensitive?["aàáảãạăằắẳẵặâầấẩẫậäåāą","AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćč","CÇĆČ","dđď","DĐĎ","eèéẻẽẹêềếểễệëěēę","EÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïī","IÌÍỈĨỊÎÏĪ","lł","LŁ","nñňń","NÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøō","OÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rř","RŘ","sšśșş","SŠŚȘŞ","tťțţ","TŤȚŢ","uùúủũụưừứửữựûüůū","UÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿ","YÝỲỶỸỴŸ","zžżź","ZŽŻŹ"]:["aàáảãạăằắẳẵặâầấẩẫậäåāąAÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćčCÇĆČ","dđďDĐĎ","eèéẻẽẹêềếểễệëěēęEÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïīIÌÍỈĨỊÎÏĪ","lłLŁ","nñňńNÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøōOÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rřRŘ","sšśșşSŠŚȘŞ","tťțţTŤȚŢ","uùúủũụưừứửữựûüůūUÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿYÝỲỶỸỴŸ","zžżźZŽŻŹ"];let c=[];return o.split("").forEach(f=>{l.every(m=>{if(m.indexOf(f)!==-1){if(c.indexOf(m)>-1)return!1;o=o.replace(new RegExp(`[${m}]`,`gm${s}`),`[${m}]`),c.push(m)}return!0})}),o}createMergedBlanksRegExp(o){return o.replace(/[\s]+/gmi,"[\\s]+")}createAccuracyRegExp(o){const s="!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~¡¿";let l=this.opt.accuracy,c=typeof l=="string"?l:l.value,f=typeof l=="string"?[]:l.limiters,m="";switch(f.forEach(d=>{m+=`|${this.escapeStr(d)}`}),c){case"partially":default:return`()(${o})`;case"complementary":return m="\\s"+(m||this.escapeStr(s)),`()([^${m}]*${o}[^${m}]*)`;case"exactly":return`(^|\\s${m})(${o})(?=$|\\s${m})`}}getSeparatedKeywords(o){let s=[];return o.forEach(l=>{this.opt.separateWordSearch?l.split(" ").forEach(c=>{c.trim()&&s.indexOf(c)===-1&&s.push(c)}):l.trim()&&s.indexOf(l)===-1&&s.push(l)}),{keywords:s.sort((l,c)=>c.length-l.length),length:s.length}}isNumeric(o){return Number(parseFloat(o))==o}checkRanges(o){if(!Array.isArray(o)||Object.prototype.toString.call(o[0])!=="[object Object]")return this.log("markRanges() will only accept an array of objects"),this.opt.noMatch(o),[];const s=[];let l=0;return o.sort((c,f)=>c.start-f.start).forEach(c=>{let{start:f,end:m,valid:d}=this.callNoMatchOnInvalidRanges(c,l);d&&(c.start=f,c.length=m-f,s.push(c),l=m)}),s}callNoMatchOnInvalidRanges(o,s){let l,c,f=!1;return o&&typeof o.start<"u"?(l=parseInt(o.start,10),c=l+parseInt(o.length,10),this.isNumeric(o.start)&&this.isNumeric(o.length)&&c-s>0&&c-l>0?f=!0:(this.log(`Ignoring invalid or overlapping range: ${JSON.stringify(o)}`),this.opt.noMatch(o))):(this.log(`Ignoring invalid range: ${JSON.stringify(o)}`),this.opt.noMatch(o)),{start:l,end:c,valid:f}}checkWhitespaceRanges(o,s,l){let c,f=!0,m=l.length,d=s-m,u=parseInt(o.start,10)-d;return u=u>m?m:u,c=u+parseInt(o.length,10),c>m&&(c=m,this.log(`End range automatically set to the max value of ${m}`)),u<0||c-u<0||u>m||c>m?(f=!1,this.log(`Invalid range: ${JSON.stringify(o)}`),this.opt.noMatch(o)):l.substring(u,c).replace(/\s+/g,"")===""&&(f=!1,this.log("Skipping whitespace only range: "+JSON.stringify(o)),this.opt.noMatch(o)),{start:u,end:c,valid:f}}getTextNodes(o){let s="",l=[];this.iterator.forEachNode(NodeFilter.SHOW_TEXT,c=>{l.push({start:s.length,end:(s+=c.textContent).length,node:c})},c=>this.matchesExclude(c.parentNode)?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT,()=>{o({value:s,nodes:l})})}matchesExclude(o){return r.matches(o,this.opt.exclude.concat(["script","style","title","head","html"]))}wrapRangeInTextNode(o,s,l){const c=this.opt.element?this.opt.element:"mark",f=o.splitText(s),m=f.splitText(l-s);let d=document.createElement(c);return d.setAttribute("data-markjs","true"),this.opt.className&&d.setAttribute("class",this.opt.className),d.textContent=f.textContent,f.parentNode.replaceChild(d,f),m}wrapRangeInMappedTextNode(o,s,l,c,f){o.nodes.every((m,d)=>{const u=o.nodes[d+1];if(typeof u>"u"||u.start>s){if(!c(m.node))return!1;const g=s-m.start,E=(l>m.end?m.end:l)-m.start,h=o.value.substr(0,m.start),p=o.value.substr(E+m.start);if(m.node=this.wrapRangeInTextNode(m.node,g,E),o.value=h+p,o.nodes.forEach((v,y)=>{y>=d&&(o.nodes[y].start>0&&y!==d&&(o.nodes[y].start-=E),o.nodes[y].end-=E)}),l-=E,f(m.node.previousSibling,m.start),l>m.end)s=m.end;else return!1}return!0})}wrapMatches(o,s,l,c,f){const m=s===0?0:s+1;this.getTextNodes(d=>{d.nodes.forEach(u=>{u=u.node;let g;for(;(g=o.exec(u.textContent))!==null&&g[m]!=="";){if(!l(g[m],u))continue;let E=g.index;if(m!==0)for(let h=1;h<m;h++)E+=g[h].length;u=this.wrapRangeInTextNode(u,E,E+g[m].length),c(u.previousSibling),o.lastIndex=0}}),f()})}wrapMatchesAcrossElements(o,s,l,c,f){const m=s===0?0:s+1;this.getTextNodes(d=>{let u;for(;(u=o.exec(d.value))!==null&&u[m]!=="";){let g=u.index;if(m!==0)for(let h=1;h<m;h++)g+=u[h].length;const E=g+u[m].length;this.wrapRangeInMappedTextNode(d,g,E,h=>l(u[m],h),(h,p)=>{o.lastIndex=p,c(h)})}f()})}wrapRangeFromIndex(o,s,l,c){this.getTextNodes(f=>{const m=f.value.length;o.forEach((d,u)=>{let{start:g,end:E,valid:h}=this.checkWhitespaceRanges(d,m,f.value);h&&this.wrapRangeInMappedTextNode(f,g,E,p=>s(p,d,f.value.substring(g,E),u),p=>{l(p,d)})}),c()})}unwrapMatches(o){const s=o.parentNode;let l=document.createDocumentFragment();for(;o.firstChild;)l.appendChild(o.removeChild(o.firstChild));s.replaceChild(l,o),this.ie?this.normalizeTextNode(s):s.normalize()}normalizeTextNode(o){if(o){if(o.nodeType===3)for(;o.nextSibling&&o.nextSibling.nodeType===3;)o.nodeValue+=o.nextSibling.nodeValue,o.parentNode.removeChild(o.nextSibling);else this.normalizeTextNode(o.firstChild);this.normalizeTextNode(o.nextSibling)}}markRegExp(o,s){this.opt=s,this.log(`Searching with expression "${o}"`);let l=0,c="wrapMatches";const f=m=>{l++,this.opt.each(m)};this.opt.acrossElements&&(c="wrapMatchesAcrossElements"),this[c](o,this.opt.ignoreGroups,(m,d)=>this.opt.filter(d,m,l),f,()=>{l===0&&this.opt.noMatch(o),this.opt.done(l)})}mark(o,s){this.opt=s;let l=0,c="wrapMatches";const{keywords:f,length:m}=this.getSeparatedKeywords(typeof o=="string"?[o]:o),d=this.opt.caseSensitive?"":"i",u=g=>{let E=new RegExp(this.createRegExp(g),`gm${d}`),h=0;this.log(`Searching with expression "${E}"`),this[c](E,1,(p,v)=>this.opt.filter(v,g,l,h),p=>{h++,l++,this.opt.each(p)},()=>{h===0&&this.opt.noMatch(g),f[m-1]===g?this.opt.done(l):u(f[f.indexOf(g)+1])})};this.opt.acrossElements&&(c="wrapMatchesAcrossElements"),m===0?this.opt.done(l):u(f[0])}markRanges(o,s){this.opt=s;let l=0,c=this.checkRanges(o);c&&c.length?(this.log("Starting to mark with the following ranges: "+JSON.stringify(c)),this.wrapRangeFromIndex(c,(f,m,d,u)=>this.opt.filter(f,m,d,u),(f,m)=>{l++,this.opt.each(f,m)},()=>{this.opt.done(l)})):this.opt.done(l)}unmark(o){this.opt=o;let s=this.opt.element?this.opt.element:"*";s+="[data-markjs]",this.opt.className&&(s+=`.${this.opt.className}`),this.log(`Removal selector "${s}"`),this.iterator.forEachNode(NodeFilter.SHOW_ELEMENT,l=>{this.unwrapMatches(l)},l=>{const c=r.matches(l,s),f=this.matchesExclude(l);return!c||f?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT},this.opt.done)}}function i(a){const o=new n(a);return this.mark=(s,l)=>(o.mark(s,l),this),this.markRegExp=(s,l)=>(o.markRegExp(s,l),this),this.markRanges=(s,l)=>(o.markRanges(s,l),this),this.unmark=s=>(o.unmark(s),this),this}return i})})(zo);var js=zo.exports;const Us=Bs(js),Ws="ENTRIES",Vo="KEYS",Ho="VALUES",he="";class xr{constructor(t,r){const n=t._tree,i=Array.from(n.keys());this.set=t,this._type=r,this._path=i.length>0?[{node:n,keys:i}]:[]}next(){const t=this.dive();return this.backtrack(),t}dive(){if(this._path.length===0)return{done:!0,value:void 0};const{node:t,keys:r}=it(this._path);if(it(r)===he)return{done:!1,value:this.result()};const n=t.get(it(r));return this._path.push({node:n,keys:Array.from(n.keys())}),this.dive()}backtrack(){if(this._path.length===0)return;const t=it(this._path).keys;t.pop(),!(t.length>0)&&(this._path.pop(),this.backtrack())}key(){return this.set._prefix+this._path.map(({keys:t})=>it(t)).filter(t=>t!==he).join("")}value(){return it(this._path).node.get(he)}result(){switch(this._type){case Ho:return this.value();case Vo:return this.key();default:return[this.key(),this.value()]}}[Symbol.iterator](){return this}}const it=e=>e[e.length-1],Ks=(e,t,r)=>{const n=new Map;if(t===void 0)return n;const i=t.length+1,a=i+r,o=new Uint8Array(a*i).fill(r+1);for(let s=0;s<i;++s)o[s]=s;for(let s=1;s<a;++s)o[s*i]=s;return qo(e,t,r,n,o,1,i,""),n},qo=(e,t,r,n,i,a,o,s)=>{const l=a*o;e:for(const c of e.keys())if(c===he){const f=i[l-1];f<=r&&n.set(s,[e.get(c),f])}else{let f=a;for(let m=0;m<c.length;++m,++f){const d=c[m],u=o*f,g=u-o;let E=i[u];const h=Math.max(0,f-r-1),p=Math.min(o-1,f+r);for(let v=h;v<p;++v){const y=d!==t[v],b=i[g+v]+ +y,k=i[g+v+1]+1,I=i[u+v]+1,F=i[u+v+1]=Math.min(b,k,I);F<E&&(E=F)}if(E>r)continue e}qo(e.get(c),t,r,n,i,f,o,s+c)}};class Je{constructor(t=new Map,r=""){this._size=void 0,this._tree=t,this._prefix=r}atPrefix(t){if(!t.startsWith(this._prefix))throw new Error("Mismatched prefix");const[r,n]=rr(this._tree,t.slice(this._prefix.length));if(r===void 0){const[i,a]=rn(n);for(const o of i.keys())if(o!==he&&o.startsWith(a)){const s=new Map;return s.set(o.slice(a.length),i.get(o)),new Je(s,t)}}return new Je(r,t)}clear(){this._size=void 0,this._tree.clear()}delete(t){return this._size=void 0,Js(this._tree,t)}entries(){return new xr(this,Ws)}forEach(t){for(const[r,n]of this)t(r,n,this)}fuzzyGet(t,r){return Ks(this._tree,t,r)}get(t){const r=zr(this._tree,t);return r!==void 0?r.get(he):void 0}has(t){const r=zr(this._tree,t);return r!==void 0&&r.has(he)}keys(){return new xr(this,Vo)}set(t,r){if(typeof t!="string")throw new Error("key must be a string");return this._size=void 0,kr(this._tree,t).set(he,r),this}get size(){if(this._size)return this._size;this._size=0;const t=this.entries();for(;!t.next().done;)this._size+=1;return this._size}update(t,r){if(typeof t!="string")throw new Error("key must be a string");this._size=void 0;const n=kr(this._tree,t);return n.set(he,r(n.get(he))),this}fetch(t,r){if(typeof t!="string")throw new Error("key must be a string");this._size=void 0;const n=kr(this._tree,t);let i=n.get(he);return i===void 0&&n.set(he,i=r()),i}values(){return new xr(this,Ho)}[Symbol.iterator](){return this.entries()}static from(t){const r=new Je;for(const[n,i]of t)r.set(n,i);return r}static fromObject(t){return Je.from(Object.entries(t))}}const rr=(e,t,r=[])=>{if(t.length===0||e==null)return[e,r];for(const n of e.keys())if(n!==he&&t.startsWith(n))return r.push([e,n]),rr(e.get(n),t.slice(n.length),r);return r.push([e,t]),rr(void 0,"",r)},zr=(e,t)=>{if(t.length===0||e==null)return e;for(const r of e.keys())if(r!==he&&t.startsWith(r))return zr(e.get(r),t.slice(r.length))},kr=(e,t)=>{const r=t.length;e:for(let n=0;e&&n<r;){for(const a of e.keys())if(a!==he&&t[n]===a[0]){const o=Math.min(r-n,a.length);let s=1;for(;s<o&&t[n+s]===a[s];)++s;const l=e.get(a);if(s===a.length)e=l;else{const c=new Map;c.set(a.slice(s),l),e.set(t.slice(n,n+s),c),e.delete(a),e=c}n+=s;continue e}const i=new Map;return e.set(t.slice(n),i),i}return e},Js=(e,t)=>{const[r,n]=rr(e,t);if(r!==void 0){if(r.delete(he),r.size===0)Bo(n);else if(r.size===1){const[i,a]=r.entries().next().value;jo(n,i,a)}}},Bo=e=>{if(e.length===0)return;const[t,r]=rn(e);if(t.delete(r),t.size===0)Bo(e.slice(0,-1));else if(t.size===1){const[n,i]=t.entries().next().value;n!==he&&jo(e.slice(0,-1),n,i)}},jo=(e,t,r)=>{if(e.length===0)return;const[n,i]=rn(e);n.set(i+t,r),n.delete(i)},rn=e=>e[e.length-1],nn="or",Uo="and",Gs="and_not";class dt{constructor(t){if((t==null?void 0:t.fields)==null)throw new Error('MiniSearch: option "fields" must be provided');const r=t.autoVacuum==null||t.autoVacuum===!0?Ar:t.autoVacuum;this._options={...Sr,...t,autoVacuum:r,searchOptions:{...zn,...t.searchOptions||{}},autoSuggestOptions:{...el,...t.autoSuggestOptions||{}}},this._index=new Je,this._documentCount=0,this._documentIds=new Map,this._idToShortId=new Map,this._fieldIds={},this._fieldLength=new Map,this._avgFieldLength=[],this._nextId=0,this._storedFields=new Map,this._dirtCount=0,this._currentVacuum=null,this._enqueuedVacuum=null,this._enqueuedVacuumConditions=Hr,this.addFields(this._options.fields)}add(t){const{extractField:r,stringifyField:n,tokenize:i,processTerm:a,fields:o,idField:s}=this._options,l=r(t,s);if(l==null)throw new Error(`MiniSearch: document does not have ID field "${s}"`);if(this._idToShortId.has(l))throw new Error(`MiniSearch: duplicate ID ${l}`);const c=this.addDocumentId(l);this.saveStoredFields(c,t);for(const f of o){const m=r(t,f);if(m==null)continue;const d=i(n(m,f),f),u=this._fieldIds[f],g=new Set(d).size;this.addFieldLength(c,u,this._documentCount-1,g);for(const E of d){const h=a(E,f);if(Array.isArray(h))for(const p of h)this.addTerm(u,c,p);else h&&this.addTerm(u,c,h)}}}addAll(t){for(const r of t)this.add(r)}addAllAsync(t,r={}){const{chunkSize:n=10}=r,i={chunk:[],promise:Promise.resolve()},{chunk:a,promise:o}=t.reduce(({chunk:s,promise:l},c,f)=>(s.push(c),(f+1)%n===0?{chunk:[],promise:l.then(()=>new Promise(m=>setTimeout(m,0))).then(()=>this.addAll(s))}:{chunk:s,promise:l}),i);return o.then(()=>this.addAll(a))}remove(t){const{tokenize:r,processTerm:n,extractField:i,stringifyField:a,fields:o,idField:s}=this._options,l=i(t,s);if(l==null)throw new Error(`MiniSearch: document does not have ID field "${s}"`);const c=this._idToShortId.get(l);if(c==null)throw new Error(`MiniSearch: cannot remove document with ID ${l}: it is not in the index`);for(const f of o){const m=i(t,f);if(m==null)continue;const d=r(a(m,f),f),u=this._fieldIds[f],g=new Set(d).size;this.removeFieldLength(c,u,this._documentCount,g);for(const E of d){const h=n(E,f);if(Array.isArray(h))for(const p of h)this.removeTerm(u,c,p);else h&&this.removeTerm(u,c,h)}}this._storedFields.delete(c),this._documentIds.delete(c),this._idToShortId.delete(l),this._fieldLength.delete(c),this._documentCount-=1}removeAll(t){if(t)for(const r of t)this.remove(r);else{if(arguments.length>0)throw new Error("Expected documents to be present. Omit the argument to remove all documents.");this._index=new Je,this._documentCount=0,this._documentIds=new Map,this._idToShortId=new Map,this._fieldLength=new Map,this._avgFieldLength=[],this._storedFields=new Map,this._nextId=0}}discard(t){const r=this._idToShortId.get(t);if(r==null)throw new Error(`MiniSearch: cannot discard document with ID ${t}: it is not in the index`);this._idToShortId.delete(t),this._documentIds.delete(r),this._storedFields.delete(r),(this._fieldLength.get(r)||[]).forEach((n,i)=>{this.removeFieldLength(r,i,this._documentCount,n)}),this._fieldLength.delete(r),this._documentCount-=1,this._dirtCount+=1,this.maybeAutoVacuum()}maybeAutoVacuum(){if(this._options.autoVacuum===!1)return;const{minDirtFactor:t,minDirtCount:r,batchSize:n,batchWait:i}=this._options.autoVacuum;this.conditionalVacuum({batchSize:n,batchWait:i},{minDirtCount:r,minDirtFactor:t})}discardAll(t){const r=this._options.autoVacuum;try{this._options.autoVacuum=!1;for(const n of t)this.discard(n)}finally{this._options.autoVacuum=r}this.maybeAutoVacuum()}replace(t){const{idField:r,extractField:n}=this._options,i=n(t,r);this.discard(i),this.add(t)}vacuum(t={}){return this.conditionalVacuum(t)}conditionalVacuum(t,r){return this._currentVacuum?(this._enqueuedVacuumConditions=this._enqueuedVacuumConditions&&r,this._enqueuedVacuum!=null?this._enqueuedVacuum:(this._enqueuedVacuum=this._currentVacuum.then(()=>{const n=this._enqueuedVacuumConditions;return this._enqueuedVacuumConditions=Hr,this.performVacuuming(t,n)}),this._enqueuedVacuum)):this.vacuumConditionsMet(r)===!1?Promise.resolve():(this._currentVacuum=this.performVacuuming(t),this._currentVacuum)}async performVacuuming(t,r){const n=this._dirtCount;if(this.vacuumConditionsMet(r)){const i=t.batchSize||Vr.batchSize,a=t.batchWait||Vr.batchWait;let o=1;for(const[s,l]of this._index){for(const[c,f]of l)for(const[m]of f)this._documentIds.has(m)||(f.size<=1?l.delete(c):f.delete(m));this._index.get(s).size===0&&this._index.delete(s),o%i===0&&await new Promise(c=>setTimeout(c,a)),o+=1}this._dirtCount-=n}await null,this._currentVacuum=this._enqueuedVacuum,this._enqueuedVacuum=null}vacuumConditionsMet(t){if(t==null)return!0;let{minDirtCount:r,minDirtFactor:n}=t;return r=r||Ar.minDirtCount,n=n||Ar.minDirtFactor,this.dirtCount>=r&&this.dirtFactor>=n}get isVacuuming(){return this._currentVacuum!=null}get dirtCount(){return this._dirtCount}get dirtFactor(){return this._dirtCount/(1+this._documentCount+this._dirtCount)}has(t){return this._idToShortId.has(t)}getStoredFields(t){const r=this._idToShortId.get(t);if(r!=null)return this._storedFields.get(r)}search(t,r={}){const{searchOptions:n}=this._options,i={...n,...r},a=this.executeQuery(t,r),o=[];for(const[s,{score:l,terms:c,match:f}]of a){const m=c.length||1,d={id:this._documentIds.get(s),score:l*m,terms:Object.keys(f),queryTerms:c,match:f};Object.assign(d,this._storedFields.get(s)),(i.filter==null||i.filter(d))&&o.push(d)}return t===dt.wildcard&&i.boostDocument==null||o.sort(Hn),o}autoSuggest(t,r={}){r={...this._options.autoSuggestOptions,...r};const n=new Map;for(const{score:a,terms:o}of this.search(t,r)){const s=o.join(" "),l=n.get(s);l!=null?(l.score+=a,l.count+=1):n.set(s,{score:a,terms:o,count:1})}const i=[];for(const[a,{score:o,terms:s,count:l}]of n)i.push({suggestion:a,terms:s,score:o/l});return i.sort(Hn),i}get documentCount(){return this._documentCount}get termCount(){return this._index.size}static loadJSON(t,r){if(r==null)throw new Error("MiniSearch: loadJSON should be given the same options used when serializing the index");return this.loadJS(JSON.parse(t),r)}static async loadJSONAsync(t,r){if(r==null)throw new Error("MiniSearch: loadJSON should be given the same options used when serializing the index");return this.loadJSAsync(JSON.parse(t),r)}static getDefault(t){if(Sr.hasOwnProperty(t))return _r(Sr,t);throw new Error(`MiniSearch: unknown option "${t}"`)}static loadJS(t,r){const{index:n,documentIds:i,fieldLength:a,storedFields:o,serializationVersion:s}=t,l=this.instantiateMiniSearch(t,r);l._documentIds=Dt(i),l._fieldLength=Dt(a),l._storedFields=Dt(o);for(const[c,f]of l._documentIds)l._idToShortId.set(f,c);for(const[c,f]of n){const m=new Map;for(const d of Object.keys(f)){let u=f[d];s===1&&(u=u.ds),m.set(parseInt(d,10),Dt(u))}l._index.set(c,m)}return l}static async loadJSAsync(t,r){const{index:n,documentIds:i,fieldLength:a,storedFields:o,serializationVersion:s}=t,l=this.instantiateMiniSearch(t,r);l._documentIds=await $t(i),l._fieldLength=await $t(a),l._storedFields=await $t(o);for(const[f,m]of l._documentIds)l._idToShortId.set(m,f);let c=0;for(const[f,m]of n){const d=new Map;for(const u of Object.keys(m)){let g=m[u];s===1&&(g=g.ds),d.set(parseInt(u,10),await $t(g))}++c%1e3===0&&await Wo(0),l._index.set(f,d)}return l}static instantiateMiniSearch(t,r){const{documentCount:n,nextId:i,fieldIds:a,averageFieldLength:o,dirtCount:s,serializationVersion:l}=t;if(l!==1&&l!==2)throw new Error("MiniSearch: cannot deserialize an index created with an incompatible version");const c=new dt(r);return c._documentCount=n,c._nextId=i,c._idToShortId=new Map,c._fieldIds=a,c._avgFieldLength=o,c._dirtCount=s||0,c._index=new Je,c}executeQuery(t,r={}){if(t===dt.wildcard)return this.executeWildcardQuery(r);if(typeof t!="string"){const d={...r,...t,queries:void 0},u=t.queries.map(g=>this.executeQuery(g,d));return this.combineResults(u,d.combineWith)}const{tokenize:n,processTerm:i,searchOptions:a}=this._options,o={tokenize:n,processTerm:i,...a,...r},{tokenize:s,processTerm:l}=o,m=s(t).flatMap(d=>l(d)).filter(d=>!!d).map(Xs(o)).map(d=>this.executeQuerySpec(d,o));return this.combineResults(m,o.combineWith)}executeQuerySpec(t,r){const n={...this._options.searchOptions,...r},i=(n.fields||this._options.fields).reduce((E,h)=>({...E,[h]:_r(n.boost,h)||1}),{}),{boostDocument:a,weights:o,maxFuzzy:s,bm25:l}=n,{fuzzy:c,prefix:f}={...zn.weights,...o},m=this._index.get(t.term),d=this.termResults(t.term,t.term,1,t.termBoost,m,i,a,l);let u,g;if(t.prefix&&(u=this._index.atPrefix(t.term)),t.fuzzy){const E=t.fuzzy===!0?.2:t.fuzzy,h=E<1?Math.min(s,Math.round(t.term.length*E)):E;h&&(g=this._index.fuzzyGet(t.term,h))}if(u)for(const[E,h]of u){const p=E.length-t.term.length;if(!p)continue;g==null||g.delete(E);const v=f*E.length/(E.length+.3*p);this.termResults(t.term,E,v,t.termBoost,h,i,a,l,d)}if(g)for(const E of g.keys()){const[h,p]=g.get(E);if(!p)continue;const v=c*E.length/(E.length+p);this.termResults(t.term,E,v,t.termBoost,h,i,a,l,d)}return d}executeWildcardQuery(t){const r=new Map,n={...this._options.searchOptions,...t};for(const[i,a]of this._documentIds){const o=n.boostDocument?n.boostDocument(a,"",this._storedFields.get(i)):1;r.set(i,{score:o,terms:[],match:{}})}return r}combineResults(t,r=nn){if(t.length===0)return new Map;const n=r.toLowerCase(),i=Ys[n];if(!i)throw new Error(`Invalid combination operator: ${r}`);return t.reduce(i)||new Map}toJSON(){const t=[];for(const[r,n]of this._index){const i={};for(const[a,o]of n)i[a]=Object.fromEntries(o);t.push([r,i])}return{documentCount:this._documentCount,nextId:this._nextId,documentIds:Object.fromEntries(this._documentIds),fieldIds:this._fieldIds,fieldLength:Object.fromEntries(this._fieldLength),averageFieldLength:this._avgFieldLength,storedFields:Object.fromEntries(this._storedFields),dirtCount:this._dirtCount,index:t,serializationVersion:2}}termResults(t,r,n,i,a,o,s,l,c=new Map){if(a==null)return c;for(const f of Object.keys(o)){const m=o[f],d=this._fieldIds[f],u=a.get(d);if(u==null)continue;let g=u.size;const E=this._avgFieldLength[d];for(const h of u.keys()){if(!this._documentIds.has(h)){this.removeTerm(d,h,r),g-=1;continue}const p=s?s(this._documentIds.get(h),r,this._storedFields.get(h)):1;if(!p)continue;const v=u.get(h),y=this._fieldLength.get(h)[d],b=Zs(v,g,this._documentCount,y,E,l),k=n*i*m*p*b,I=c.get(h);if(I){I.score+=k,tl(I.terms,t);const F=_r(I.match,r);F?F.push(f):I.match[r]=[f]}else c.set(h,{score:k,terms:[t],match:{[r]:[f]}})}}return c}addTerm(t,r,n){const i=this._index.fetch(n,qn);let a=i.get(t);if(a==null)a=new Map,a.set(r,1),i.set(t,a);else{const o=a.get(r);a.set(r,(o||0)+1)}}removeTerm(t,r,n){if(!this._index.has(n)){this.warnDocumentChanged(r,t,n);return}const i=this._index.fetch(n,qn),a=i.get(t);a==null||a.get(r)==null?this.warnDocumentChanged(r,t,n):a.get(r)<=1?a.size<=1?i.delete(t):a.delete(r):a.set(r,a.get(r)-1),this._index.get(n).size===0&&this._index.delete(n)}warnDocumentChanged(t,r,n){for(const i of Object.keys(this._fieldIds))if(this._fieldIds[i]===r){this._options.logger("warn",`MiniSearch: document with ID ${this._documentIds.get(t)} has changed before removal: term "${n}" was not present in field "${i}". Removing a document after it has changed can corrupt the index!`,"version_conflict");return}}addDocumentId(t){const r=this._nextId;return this._idToShortId.set(t,r),this._documentIds.set(r,t),this._documentCount+=1,this._nextId+=1,r}addFields(t){for(let r=0;r<t.length;r++)this._fieldIds[t[r]]=r}addFieldLength(t,r,n,i){let a=this._fieldLength.get(t);a==null&&this._fieldLength.set(t,a=[]),a[r]=i;const s=(this._avgFieldLength[r]||0)*n+i;this._avgFieldLength[r]=s/(n+1)}removeFieldLength(t,r,n,i){if(n===1){this._avgFieldLength[r]=0;return}const a=this._avgFieldLength[r]*n-i;this._avgFieldLength[r]=a/(n-1)}saveStoredFields(t,r){const{storeFields:n,extractField:i}=this._options;if(n==null||n.length===0)return;let a=this._storedFields.get(t);a==null&&this._storedFields.set(t,a={});for(const o of n){const s=i(r,o);s!==void 0&&(a[o]=s)}}}dt.wildcard=Symbol("*");const _r=(e,t)=>Object.prototype.hasOwnProperty.call(e,t)?e[t]:void 0,Ys={[nn]:(e,t)=>{for(const r of t.keys()){const n=e.get(r);if(n==null)e.set(r,t.get(r));else{const{score:i,terms:a,match:o}=t.get(r);n.score=n.score+i,n.match=Object.assign(n.match,o),Vn(n.terms,a)}}return e},[Uo]:(e,t)=>{const r=new Map;for(const n of t.keys()){const i=e.get(n);if(i==null)continue;const{score:a,terms:o,match:s}=t.get(n);Vn(i.terms,o),r.set(n,{score:i.score+a,terms:i.terms,match:Object.assign(i.match,s)})}return r},[Gs]:(e,t)=>{for(const r of t.keys())e.delete(r);return e}},Qs={k:1.2,b:.7,d:.5},Zs=(e,t,r,n,i,a)=>{const{k:o,b:s,d:l}=a;return Math.log(1+(r-t+.5)/(t+.5))*(l+e*(o+1)/(e+o*(1-s+s*n/i)))},Xs=e=>(t,r,n)=>{const i=typeof e.fuzzy=="function"?e.fuzzy(t,r,n):e.fuzzy||!1,a=typeof e.prefix=="function"?e.prefix(t,r,n):e.prefix===!0,o=typeof e.boostTerm=="function"?e.boostTerm(t,r,n):1;return{term:t,fuzzy:i,prefix:a,termBoost:o}},Sr={idField:"id",extractField:(e,t)=>e[t],stringifyField:(e,t)=>e.toString(),tokenize:e=>e.split(rl),processTerm:e=>e.toLowerCase(),fields:void 0,searchOptions:void 0,storeFields:[],logger:(e,t)=>{typeof(console==null?void 0:console[e])=="function"&&console[e](t)},autoVacuum:!0},zn={combineWith:nn,prefix:!1,fuzzy:!1,maxFuzzy:6,boost:{},weights:{fuzzy:.45,prefix:.375},bm25:Qs},el={combineWith:Uo,prefix:(e,t,r)=>t===r.length-1},Vr={batchSize:1e3,batchWait:10},Hr={minDirtFactor:.1,minDirtCount:20},Ar={...Vr,...Hr},tl=(e,t)=>{e.includes(t)||e.push(t)},Vn=(e,t)=>{for(const r of t)e.includes(r)||e.push(r)},Hn=({score:e},{score:t})=>t-e,qn=()=>new Map,Dt=e=>{const t=new Map;for(const r of Object.keys(e))t.set(parseInt(r,10),e[r]);return t},$t=async e=>{const t=new Map;let r=0;for(const n of Object.keys(e))t.set(parseInt(n,10),e[n]),++r%1e3===0&&await Wo(0);return t},Wo=e=>new Promise(t=>setTimeout(t,e)),rl=/[\n\r\p{Z}\p{P}]+/u,nl=typeof document<"u",ol=/[\u0000-\u001F"#$&*+,:;<=>?[\]^`{|}\u007F]/g,il=/^[a-z]:/i;function Bn(e){const t=il.exec(e),r=t?t[0]:"";return r+e.slice(r.length).replace(ol,"_").replace(/(^|\/)_+(?=[^/]*$)/,"$1")}function al(e){return e.replace(/[|\\{}()[\]^$+*?.]/g,"\\$&").replace(/-/g,"\\x2d")}function sl(e){let t=e.replace(/\.html$/,"");if(t=decodeURIComponent(t),t=t.replace(/\/$/,"/index"),nl){const r="/formie/";t=Bn(t.slice(r.length).replace(/\//g,"_")||"index")+".md";let n=__VP_HASH_MAP__[t.toLowerCase()];if(n||(t=t.endsWith("_index.md")?t.slice(0,-9)+".md":t.slice(0,-3)+"_index.md",n=__VP_HASH_MAP__[t.toLowerCase()]),!n)return null;t=`${r}assets/${t}.${n}.js`}else t=`./${Bn(t.slice(1).replace(/\//g,"_"))}.md.js`;return t}const Ko=Ge;class ll{constructor(t=10){br(this,"max");br(this,"cache");this.max=t,this.cache=new Map}get(t){let r=this.cache.get(t);return r!==void 0&&(this.cache.delete(t),this.cache.set(t,r)),r}set(t,r){this.cache.has(t)?this.cache.delete(t):this.cache.size===this.max&&this.cache.delete(this.first()),this.cache.set(t,r)}first(){return this.cache.keys().next().value}clear(){this.cache.clear()}}function cl(e){const{localeIndex:t,theme:r}=Ko();function n(i){var g,E,h;const a=i.split("."),o=(g=r.value.search)==null?void 0:g.options,s=o&&typeof o=="object",l=s&&((h=(E=o.locales)==null?void 0:E[t.value])==null?void 0:h.translations)||null,c=s&&o.translations||null;let f=l,m=c,d=e;const u=a.pop();for(const p of a){let v=null;const y=d==null?void 0:d[p];y&&(v=d=y);const b=m==null?void 0:m[p];b&&(v=m=b);const k=f==null?void 0:f[p];k&&(v=f=k),y||(d=v),b||(m=v),k||(f=v)}return(f==null?void 0:f[u])??(m==null?void 0:m[u])??(d==null?void 0:d[u])??""}return n}const ul=["aria-owns"],dl={class:"shell"},fl=["title"],ml={class:"search-actions before"},hl=["title"],pl=["aria-activedescendant","aria-controls","placeholder"],gl={class:"search-actions"},vl=["title"],bl=["disabled","title"],yl=["id","role","aria-labelledby"],wl=["id","aria-selected"],El=["href","aria-label","onMouseenter","onFocusin","data-index"],xl={class:"titles"},kl=["innerHTML"],_l={class:"title main"},Sl=["innerHTML"],Al={key:0,class:"excerpt-wrapper"},Tl={key:0,class:"excerpt",inert:""},Cl=["innerHTML"],Il={key:0,class:"no-results"},Ll={class:"search-keyboard-shortcuts"},Ml=["aria-label"],Rl=["aria-label"],Fl=["aria-label"],Ol=["aria-label"],Pl=Ce({__name:"VPLocalSearchBox",emits:["close"],setup(e,{emit:t}){var Y,U;const r=t,n=Re(),i=Re(),a=Re(Ba),o=Ko(),{activate:s}=Hs(n,{immediate:!0,allowOutsideClick:!0,clickOutsideDeactivates:!0,escapeDeactivates:!0}),{localeIndex:l,theme:c}=o,f=Rn(async()=>{var L,T,G,te,$,X,ne,ee,se;return Tn(dt.loadJSON((G=await((T=(L=a.value)[l.value])==null?void 0:T.call(L)))==null?void 0:G.default,{fields:["title","titles","text"],storeFields:["title","titles"],searchOptions:{fuzzy:.2,prefix:!0,boost:{title:4,text:2,titles:1},...((te=c.value.search)==null?void 0:te.provider)==="local"&&((X=($=c.value.search.options)==null?void 0:$.miniSearch)==null?void 0:X.searchOptions)},...((ne=c.value.search)==null?void 0:ne.provider)==="local"&&((se=(ee=c.value.search.options)==null?void 0:ee.miniSearch)==null?void 0:se.options)}))}),d=q(()=>{var L,T;return((L=c.value.search)==null?void 0:L.provider)==="local"&&((T=c.value.search.options)==null?void 0:T.disableQueryPersistence)===!0}).value?ae(""):ds("vitepress:local-search-filter",""),u=ls("vitepress:local-search-detailed-list",((Y=c.value.search)==null?void 0:Y.provider)==="local"&&((U=c.value.search.options)==null?void 0:U.detailedView)===!0),g=q(()=>{var L,T,G;return((L=c.value.search)==null?void 0:L.provider)==="local"&&(((T=c.value.search.options)==null?void 0:T.disableDetailedView)===!0||((G=c.value.search.options)==null?void 0:G.detailedView)===!1)}),E=q(()=>{var T,G,te,$,X,ne,ee;const L=((T=c.value.search)==null?void 0:T.options)??c.value.algolia;return((X=($=(te=(G=L==null?void 0:L.locales)==null?void 0:G[l.value])==null?void 0:te.translations)==null?void 0:$.button)==null?void 0:X.buttonText)||((ee=(ne=L==null?void 0:L.translations)==null?void 0:ne.button)==null?void 0:ee.buttonText)||"Search"});lr(()=>{g.value&&(u.value=!1)});const h=Re([]),p=ae(!1);pe(d,()=>{p.value=!1});const v=Rn(async()=>{if(i.value)return Tn(new Us(i.value))},null),y=new ll(16);es(()=>[f.value,d.value,u.value],async([L,T,G],te,$)=>{var de,we,Ie,Be;(te==null?void 0:te[0])!==L&&y.clear();let X=!1;if($(()=>{X=!0}),!L)return;h.value=L.search(T).slice(0,16),p.value=!0;const ne=G?await Promise.all(h.value.map(fe=>b(fe.id))):[];if(X)return;for(const{id:fe,mod:_e}of ne){const Pe=fe.slice(0,fe.indexOf("#"));let Se=y.get(Pe);if(Se)continue;Se=new Map,y.set(Pe,Se);const Ae=_e.default??_e;if(Ae!=null&&Ae.render||Ae!=null&&Ae.setup){const Ne=ta(Ae);Ne.config.warnHandler=()=>{},Ne.provide(ra,o),Object.defineProperties(Ne.config.globalProperties,{$frontmatter:{get(){return o.frontmatter.value}},$params:{get(){return o.page.value.params}}});const bt=document.createElement("div");Ne.mount(bt),bt.querySelectorAll("h1, h2, h3, h4, h5, h6").forEach(Ye=>{var wt;const je=(wt=Ye.querySelector("a"))==null?void 0:wt.getAttribute("href"),yt=(je==null?void 0:je.startsWith("#"))&&je.slice(1);if(!yt)return;let nt="";for(;(Ye=Ye.nextElementSibling)&&!/^h[1-6]$/i.test(Ye.tagName);)nt+=Ye.outerHTML;Se.set(yt,nt)}),Ne.unmount()}if(X)return}const ee=new Set;if(h.value=h.value.map(fe=>{const[_e,Pe]=fe.id.split("#"),Se=y.get(_e),Ae=(Se==null?void 0:Se.get(Pe))??"";for(const Ne in fe.match)ee.add(Ne);return{...fe,text:Ae}}),await Me(),X)return;await new Promise(fe=>{var _e;(_e=v.value)==null||_e.unmark({done:()=>{var Pe;(Pe=v.value)==null||Pe.markRegExp(W(ee),{done:fe})}})});const se=((de=n.value)==null?void 0:de.querySelectorAll(".result .excerpt"))??[];for(const fe of se)(we=fe.querySelector('mark[data-markjs="true"]'))==null||we.scrollIntoView({block:"center"});(Be=(Ie=i.value)==null?void 0:Ie.firstElementChild)==null||Be.scrollIntoView({block:"start"})},{debounce:200,immediate:!0});async function b(L){const T=sl(L.slice(0,L.indexOf("#")));try{if(!T)throw new Error(`Cannot find file for id: ${L}`);return{id:L,mod:await import(T)}}catch(G){return console.error(G),{id:L,mod:{}}}}const k=ae(),I=q(()=>{var L;return((L=d.value)==null?void 0:L.length)<=0});function F(L=!0){var T,G;(T=k.value)==null||T.focus(),L&&((G=k.value)==null||G.select())}qe(()=>{F()});function V(L){L.pointerType==="mouse"&&F()}const H=ae(-1),Q=ae(!0);pe(h,L=>{H.value=L.length?0:-1,C()});function C(){Me(()=>{const L=document.querySelector(".result.selected");L==null||L.scrollIntoView({block:"nearest"})})}Ft("ArrowUp",L=>{L.preventDefault(),H.value--,H.value<0&&(H.value=h.value.length-1),Q.value=!0,C()}),Ft("ArrowDown",L=>{L.preventDefault(),H.value++,H.value>=h.value.length&&(H.value=0),Q.value=!0,C()});const j=Xr();Ft("Enter",L=>{if(L.isComposing||L.target instanceof HTMLButtonElement&&L.target.type!=="submit")return;const T=h.value[H.value];if(L.target instanceof HTMLInputElement&&!T){L.preventDefault();return}T&&(j.go(T.id),r("close"))}),Ft("Escape",()=>{r("close")});const w=cl({modal:{displayDetails:"Display detailed list",resetButtonTitle:"Reset search",backButtonTitle:"Close search",noResultsText:"No results for",footer:{selectText:"to select",selectKeyAriaLabel:"enter",navigateText:"to navigate",navigateUpKeyAriaLabel:"up arrow",navigateDownKeyAriaLabel:"down arrow",closeText:"to close",closeKeyAriaLabel:"escape"}}});qe(()=>{window.history.pushState(null,"",null)}),It("popstate",L=>{L.preventDefault(),r("close")});const A=us(na?document.body:null);qe(()=>{Me(()=>{A.value=!0,Me().then(()=>s())})}),cr(()=>{A.value=!1});function P(){d.value="",Me().then(()=>F(!1))}function W(L){return new RegExp([...L].sort((T,G)=>G.length-T.length).map(T=>`(${al(T)})`).join("|"),"gi")}function z(L){var te;if(!Q.value)return;const T=(te=L.target)==null?void 0:te.closest(".result"),G=Number.parseInt(T==null?void 0:T.dataset.index);G>=0&&G!==H.value&&(H.value=G),Q.value=!1}return(L,T)=>{var G,te,$,X,ne;return M(),ke(Zi,{to:"body"},[S("div",{ref_key:"el",ref:n,role:"button","aria-owns":(G=h.value)!=null&&G.length?"localsearch-list":void 0,"aria-expanded":"true","aria-haspopup":"listbox","aria-labelledby":"localsearch-label",class:"VPLocalSearchBox"},[S("div",{class:"backdrop",onClick:T[0]||(T[0]=ee=>L.$emit("close"))}),S("div",dl,[S("form",{class:"search-bar",onPointerup:T[4]||(T[4]=ee=>V(ee)),onSubmit:T[5]||(T[5]=Nr(()=>{},["prevent"]))},[S("label",{title:E.value,id:"localsearch-label",for:"localsearch-input"},[...T[7]||(T[7]=[S("span",{"aria-hidden":"true",class:"vpi-search search-icon local-search-icon"},null,-1)])],8,fl),S("div",ml,[S("button",{class:"back-button",title:J(w)("modal.backButtonTitle"),onClick:T[1]||(T[1]=ee=>L.$emit("close"))},[...T[8]||(T[8]=[S("span",{class:"vpi-arrow-left local-search-icon"},null,-1)])],8,hl)]),Xi(S("input",{ref_key:"searchInput",ref:k,"onUpdate:modelValue":T[2]||(T[2]=ee=>ko(d)?d.value=ee:null),"aria-activedescendant":H.value>-1?"localsearch-item-"+H.value:void 0,"aria-autocomplete":"both","aria-controls":(te=h.value)!=null&&te.length?"localsearch-list":void 0,"aria-labelledby":"localsearch-label",autocapitalize:"off",autocomplete:"off",autocorrect:"off",class:"search-input",id:"localsearch-input",enterkeyhint:"go",maxlength:"64",placeholder:E.value,spellcheck:"false",type:"search"},null,8,pl),[[ea,J(d)]]),S("div",gl,[g.value?Z("",!0):(M(),O("button",{key:0,class:le(["toggle-layout-button",{"detailed-list":J(u)}]),type:"button",title:J(w)("modal.displayDetails"),onClick:T[3]||(T[3]=ee=>H.value>-1&&(u.value=!J(u)))},[...T[9]||(T[9]=[S("span",{class:"vpi-layout-list local-search-icon"},null,-1)])],10,vl)),S("button",{class:"clear-button",type:"reset",disabled:I.value,title:J(w)("modal.resetButtonTitle"),onClick:P},[...T[10]||(T[10]=[S("span",{class:"vpi-delete local-search-icon"},null,-1)])],8,bl)])],32),S("ul",{ref_key:"resultsEl",ref:i,id:($=h.value)!=null&&$.length?"localsearch-list":void 0,role:(X=h.value)!=null&&X.length?"listbox":void 0,"aria-labelledby":(ne=h.value)!=null&&ne.length?"localsearch-label":void 0,class:"results",onMousemove:z},[(M(!0),O(ge,null,be(h.value,(ee,se)=>(M(),O("li",{key:ee.id,id:"localsearch-item-"+se,"aria-selected":H.value===se?"true":"false",role:"option"},[S("a",{href:ee.id,class:le(["result",{selected:H.value===se}]),"aria-label":[...ee.titles,ee.title].join(" > "),onMouseenter:de=>!Q.value&&(H.value=se),onFocusin:de=>H.value=se,onClick:T[6]||(T[6]=de=>L.$emit("close")),"data-index":se},[S("div",null,[S("div",xl,[T[12]||(T[12]=S("span",{class:"title-icon"},"#",-1)),(M(!0),O(ge,null,be(ee.titles,(de,we)=>(M(),O("span",{key:we,class:"title"},[S("span",{class:"text",innerHTML:de},null,8,kl),T[11]||(T[11]=S("span",{class:"vpi-chevron-right local-search-icon"},null,-1))]))),128)),S("span",_l,[S("span",{class:"text",innerHTML:ee.title},null,8,Sl)])]),J(u)?(M(),O("div",Al,[ee.text?(M(),O("div",Tl,[S("div",{class:"vp-doc",innerHTML:ee.text},null,8,Cl)])):Z("",!0),T[13]||(T[13]=S("div",{class:"excerpt-gradient-bottom"},null,-1)),T[14]||(T[14]=S("div",{class:"excerpt-gradient-top"},null,-1))])):Z("",!0)])],42,El)],8,wl))),128)),J(d)&&!h.value.length&&p.value?(M(),O("li",Il,[Ze(re(J(w)("modal.noResultsText"))+' "',1),S("strong",null,re(J(d)),1),T[15]||(T[15]=Ze('" ',-1))])):Z("",!0)],40,yl),S("div",Ll,[S("span",null,[S("kbd",{"aria-label":J(w)("modal.footer.navigateUpKeyAriaLabel")},[...T[16]||(T[16]=[S("span",{class:"vpi-arrow-up navigate-icon"},null,-1)])],8,Ml),S("kbd",{"aria-label":J(w)("modal.footer.navigateDownKeyAriaLabel")},[...T[17]||(T[17]=[S("span",{class:"vpi-arrow-down navigate-icon"},null,-1)])],8,Rl),Ze(" "+re(J(w)("modal.footer.navigateText")),1)]),S("span",null,[S("kbd",{"aria-label":J(w)("modal.footer.selectKeyAriaLabel")},[...T[18]||(T[18]=[S("span",{class:"vpi-corner-down-left navigate-icon"},null,-1)])],8,Fl),Ze(" "+re(J(w)("modal.footer.selectText")),1)]),S("span",null,[S("kbd",{"aria-label":J(w)("modal.footer.closeKeyAriaLabel")},"esc",8,Ol),Ze(" "+re(J(w)("modal.footer.closeText")),1)])])])],8,ul)])}}}),Nl=oa(Pl,[["__scopeId","data-v-140b7a94"]]),ft=Re(null);function Dl(e){ft.value=e}function $l(e){ft.value===e&&(ft.value=null)}function zl(){var e;(e=ft.value)==null||e.call(ft)}const Vl=Ce({__name:"DocsSearchProvider",setup(e){const t=ae(!1);function r(){t.value=!0}function n(){t.value=!1}function i(o){const s=o.target,l=s.tagName;return s.isContentEditable||l==="INPUT"||l==="SELECT"||l==="TEXTAREA"}function a(o){(o.key.toLowerCase()==="k"&&(o.metaKey||o.ctrlKey)||!i(o)&&o.key==="/")&&(o.preventDefault(),r())}return qe(()=>{Dl(r),window.addEventListener("keydown",a)}),yo(()=>{$l(r),window.removeEventListener("keydown",a)}),(o,s)=>t.value?(M(),ke(Nl,{key:0,onClose:n})):Z("",!0)}}),Hl={class:"relative"},ql={class:"min-w-0 flex-1 break-words"},Bl=["href"],jl={class:"flex min-w-0 flex-1 items-start gap-x-2.5"},Ul={class:"flex min-w-0 flex-1 flex-wrap items-center gap-1.5 [word-break:break-word]"},Wl={class:"min-w-0 max-w-full break-words"},Kl=Ce({__name:"DocsSidebarNode",props:{item:{},depth:{default:0}},emits:["navigate"],setup(e,{emit:t}){const r=e,n=t,{page:i}=Ge(),a=Xr(),o=q(()=>{var u;return!!((u=r.item.items)!=null&&u.length)}),s=q(()=>ur(i.value.relativePath,r.item.link)),l=q(()=>{var u;return((u=r.item.items)==null?void 0:u.some(g=>Ct(i.value.relativePath,g)))??!1}),c=ae(o.value?!r.item.collapsed||l.value:!1);pe(l,u=>{u&&(c.value=!0)});function f(u){return u?ze(u):"#"}async function m(u,g){g&&(u.preventDefault(),await a.go(f(g)),n("navigate"))}function d(){o.value&&(c.value=!c.value)}return(u,g)=>{const E=wo("DocsSidebarNode",!0);return M(),O("li",Hl,[o.value?(M(),O("button",{key:0,type:"button",class:le(["group flex w-full cursor-pointer items-center py-0.5 pr-2 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",l.value?"text-docs-primary":"text-slate-700"]),onClick:d},[S("span",ql,re(e.item.text),1),(M(),O("svg",{viewBox:"0 0 640 640",class:le(["size-3 shrink-0",c.value?"rotate-90":"rotate-0"]),"aria-hidden":"true"},[...g[2]||(g[2]=[S("path",{d:"M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"},null,-1)])],2))],2)):(M(),O("a",{key:1,href:f(e.item.link),class:le(["group flex w-full cursor-pointer items-center py-0.5 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",s.value?"text-docs-primary":"text-slate-700"]),onClick:g[0]||(g[0]=h=>m(h,e.item.link))},[S("div",jl,[e.item.icon?(M(),ke(dr,{key:0,name:e.item.icon,class:"mt-1 size-4 shrink-0 text-slate-500 group-hover:text-slate-700"},null,8,["name"])):Z("",!0),S("div",Ul,[S("span",Wl,re(e.item.text),1)])])],10,Bl)),o.value&&c.value?(M(),O("ul",{key:2,style:Rt({marginLeft:e.depth===0?"1rem":"1.25rem"})},[(M(!0),O(ge,null,be(e.item.items,h=>(M(),ke(E,{key:h.link??`${h.text}-${h.icon??""}`,item:h,depth:e.depth+1,onNavigate:g[1]||(g[1]=p=>n("navigate"))},null,8,["item","depth"]))),128))],4)):Z("",!0)])}}}),Jl={"aria-label":"Sidebar navigation",class:"text-sm"},Gl={key:0,class:"mb-3 flex items-center gap-2.5 text-sm font-medium text-slate-900 lg:mb-2"},Yl={class:"space-y-px"},Ql=Ce({__name:"DocsSidebar",emits:["navigate"],setup(e){const{sidebarGroups:t}=en(),r=q(()=>t.value.filter(n=>{var i;return(i=n.items)==null?void 0:i.length}));return(n,i)=>(M(),O("nav",Jl,[(M(!0),O(ge,null,be(r.value,a=>{var o,s;return M(),O("section",{key:a.text??((s=(o=a.items)==null?void 0:o[0])==null?void 0:s.link),class:"mt-6 first:mt-0 lg:mt-6 lg:first:mt-0"},[a.text?(M(),O("h2",Gl,[a.icon?(M(),ke(dr,{key:0,name:a.icon,class:"size-4 text-slate-600"},null,8,["name"])):Z("",!0),Ze(" "+re(a.text),1)])):Z("",!0),S("ul",Yl,[(M(!0),O(ge,null,be(a.items,l=>(M(),ke(Kl,{key:l.link??`${l.text}-${l.icon??""}`,item:l,onNavigate:i[0]||(i[0]=c=>n.$emit("navigate"))},null,8,["item"]))),128))])])}),128))]))}}),Zl={key:0,class:"min-h-screen bg-slate-50 text-slate-900 lg:h-screen lg:overflow-hidden"},Xl={class:"max-lg:contents lg:flex-1 lg:min-w-0 lg:overflow-x-clip"},ec={class:"relative z-10 mx-auto max-w-[96rem] px-4 max-lg:px-3"},tc={class:"relative"},rc={class:"lg:hidden"},nc={class:"flex h-14 items-center justify-between gap-3"},oc={class:"flex min-w-0 flex-1 items-center gap-2.5"},ic=["href"],ac=["src"],sc={key:1,class:"min-w-0 truncate text-[15px] font-semibold tracking-[-0.01em] text-slate-900"},lc=["aria-expanded"],cc={class:"min-w-0 truncate"},uc=["aria-label"],dc=["href","aria-selected"],fc={class:"-mr-2 flex shrink-0 items-center gap-1.5"},mc=["href","aria-label"],hc={key:0,viewBox:"0 0 24 24",fill:"currentColor",class:"h-[18px] w-[18px]","aria-hidden":"true"},pc=["aria-expanded"],gc={class:"ml-4 flex min-w-0 items-center space-x-3 overflow-hidden text-sm leading-6 whitespace-nowrap"},vc={key:0,class:"flex shrink-0 items-center space-x-3 text-slate-500"},bc={class:"min-w-0 flex-1 truncate font-semibold text-slate-900"},yc={class:"relative hidden h-14 min-w-0 flex-1 items-center gap-x-4 lg:flex lg:border-none"},wc={class:"flex min-w-0 flex-1 items-center gap-x-4"},Ec=["href"],xc=["src"],kc={key:1,class:"min-w-0 truncate text-[15px] font-semibold tracking-[-0.01em] text-slate-900"},_c=["aria-expanded"],Sc={class:"truncate"},Ac=["aria-label"],Tc=["href","aria-selected"],Cc={class:"-mr-2 flex items-center gap-4"},Ic={class:"flex items-center gap-2"},Lc=["href","aria-label"],Mc={key:0,viewBox:"0 0 24 24",fill:"currentColor",class:"h-[18px] w-[18px]","aria-hidden":"true"},Rc={class:"scroll-mt-[var(--scroll-mt)] fixed top-[7rem] w-full pb-2 pt-0 lg:top-[3.5rem]"},Fc=["aria-hidden"],Oc={key:0,id:"sidebar-content",class:"hidden min-h-0 lg:flex lg:flex-col"},Pc={class:"flex h-full min-h-0 flex-col gap-4 text-sm"},Nc={class:"relative z-20 hidden items-center gap-2.5 mr-4 mt-2 mb-2 lg:flex"},Dc={class:"min-w-0 h-full min-h-0"},$c={class:"mx-auto w-full max-w-[88rem] xl:grid xl:grid-cols-[minmax(0,52rem)_16.5rem] xl:justify-center xl:gap-x-12"},zc={id:"content-area",class:"w-full min-w-0 overflow-x-visible"},Vc={key:0,class:"eyebrow mb-2.5 h-5 text-sm font-semibold text-docs-primary"},Hc={key:1,class:"mt-12 border-t border-slate-200 pt-6"},qc={key:0,class:"flex flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between"},Bc=["href"],jc={key:1},Uc={key:1,class:"mt-6 grid gap-3 sm:grid-cols-2"},Wc=["href"],Kc={class:"mt-1 text-sm font-medium text-slate-900 group-hover:text-docs-primary-strong"},Jc={key:1,class:"hidden sm:block"},Gc=["href"],Yc={class:"mt-1 text-sm font-medium text-slate-900 group-hover:text-docs-primary-strong"},Qc={key:0,id:"content-side-layout",class:"hidden xl:block"},Zc={class:"sticky top-0 pt-1"},Xc={id:"table-of-contents-shell",class:"max-h-[calc(100dvh-7rem)] w-[16.5rem] overflow-y-auto space-y-2 pb-4 text-sm leading-6 text-slate-600"},eu=Ce({__name:"Layout",setup(e){const t=_o(),{frontmatter:r,page:n,site:i,theme:a}=Ge(),{close:o,hasSidebar:s,isOpen:l,toggle:c,sidebarGroups:f}=en(),m=ae(null),d=ae(null),u=ae(!1),g=ae(null),E=ae(null),h=ae(!1),p=ae(!1);da(l,o),pe(()=>t.path,o),pe(()=>t.path,async(x,_)=>{await yt(x,_)});const v=q(()=>a.value.logo?typeof a.value.logo=="string"?ze(a.value.logo):ze(a.value.logo.src):null),y=q(()=>r.value.layout===!1||r.value.layout==="home"||r.value.aside===!1?!1:(r.value.outline??a.value.outline)!==!1),b=q(()=>typeof a.value.siteTitle=="string"&&a.value.siteTitle.trim()?a.value.siteTitle:i.value.title),k=q(()=>{var _;return((_=a.value.docsTheme)==null?void 0:_.homeLink)||i.value.base||"/"}),I=q(()=>a.value.socialLinks??[]),F=q(()=>f.value.find(x=>{var _;return(_=x.items)==null?void 0:_.some(D=>Ct(n.value.relativePath,D))})??null),V=q(()=>r.value.title??n.value.title??b.value),H=q(()=>{const x=[se.value,V.value].filter(_=>!!(_!=null&&_.trim()));return x.filter((_,D)=>_!==x[D-1])}),Q=q(()=>H.value.length>1?H.value[0]:null),C=q(()=>H.value.at(-1)??b.value),j=q(()=>{var _;const x=a.value;return!!((_=x.search)!=null&&_.provider||x.algolia)}),N=q(()=>a.value.docsTheme??{}),w=q(()=>a.value.editLink),A=q(()=>a.value.lastUpdatedText??"Last updated");function P(x){var D;const _=[];for(const B of x)B.link&&_.push(B),(D=B.items)!=null&&D.length&&_.push(...P(B.items));return _}function W(x,_){var D;for(const B of x){if(B.link&&Ct(_,B))return[B];if(!((D=B.items)!=null&&D.length))continue;const K=W(B.items,_);if(K)return[B,...K]}return null}function z(x){var D;const _=[];for(const B of x)B.text&&B.link&&_.push({text:B.text,link:B.link,activeMatch:B.activeMatch}),(D=B.items)!=null&&D.length&&_.push(...z(B.items));return _}function Y(x){const _=decodeURI(x).split(/[?#]/,1)[0]||"/",D=i.value.base&&i.value.base!=="/"?i.value.base.replace(/\/+$/,""):"",B=D&&_.startsWith(`${D}/`)?_.slice(D.length):_;if(B==="/")return"/";const ve=B.replace(/\/index(?:\.html)?$/,"/").replace(/\.html$/,"");return ve==="/"?"/":ve.replace(/\/+$/,"")}const U=q(()=>Y(t.path)),L=q(()=>{const x=Array.isArray(a.value.nav)?a.value.nav:[];return z(x)});function T(x){if(x.activeMatch)return new RegExp(x.activeMatch).test(U.value);const _=Y(x.link);return _==="/"?U.value==="/":U.value===_||U.value.startsWith(`${_}/`)}const G=q(()=>L.value.find(x=>T(x))??null),te=q(()=>{var x,_;return((x=G.value)==null?void 0:x.text)??((_=L.value[0])==null?void 0:_.text)??"Documentation"});function $(){h.value=!1,p.value=!1}function X(){h.value=!h.value,p.value=!1}function ne(){p.value=!p.value,h.value=!1}function ee(x){const _=x.target;h.value&&g.value&&!g.value.contains(_)&&(h.value=!1),p.value&&E.value&&!E.value.contains(_)&&(p.value=!1)}pe(()=>t.path,$);const se=q(()=>{var D,B;const x=F.value;if(!((D=x==null?void 0:x.items)!=null&&D.length))return(x==null?void 0:x.text)??null;const _=W(x.items,n.value.relativePath);return!(_!=null&&_.length)||_.length===1?x.text??null:((B=_.at(-2))==null?void 0:B.text)??x.text??null}),de=q(()=>{var x,_;return(_=(x=F.value)==null?void 0:x.items)!=null&&_.length?P(F.value.items):[]}),we=q(()=>de.value.findIndex(x=>ur(n.value.relativePath,x.link))),Ie=q(()=>{const x=we.value;return x>0?de.value[x-1]:null}),Be=q(()=>{const x=we.value;return x>=0&&x<de.value.length-1?de.value[x+1]:null}),fe=q(()=>{var D,B;if(r.value.editLink===!1)return null;const x=(D=w.value)==null?void 0:D.pattern,_=n.value.filePath;return!x||!_?null:{text:((B=w.value)==null?void 0:B.text)??"Edit this page",href:x.replace(":path",_)}}),_e=q(()=>{if(r.value.lastUpdated===!1)return null;const x=n.value.lastUpdated;return x?new Intl.DateTimeFormat(i.value.lang||void 0,{dateStyle:"medium",timeStyle:"short"}).format(x):null}),Pe=q(()=>{var _,D,B,K,ve,Ee;const x=((_=N.value.primary)==null?void 0:_.trim())||"#0f766e";return{"--docs-primary":x,"--docs-primary-strong":((D=N.value.primaryStrong)==null?void 0:D.trim())||`color-mix(in oklab, ${x} 82%, black)`,"--docs-primary-soft":((B=N.value.primarySoft)==null?void 0:B.trim())||`color-mix(in oklab, ${x} 12%, white)`,"--docs-primary-soft-hover":((K=N.value.primarySoftHover)==null?void 0:K.trim())||`color-mix(in oklab, ${x} 16%, white)`,"--docs-primary-border":((ve=N.value.primaryBorder)==null?void 0:ve.trim())||`color-mix(in oklab, ${x} 18%, white)`,"--docs-primary-border-strong":((Ee=N.value.primaryBorderStrong)==null?void 0:Ee.trim())||`color-mix(in oklab, ${x} 28%, white)`}});function Se(){const x=d.value;if(!x){u.value=!1;return}u.value=x.scrollTop>4}function Ae(x){return x.split("#")[0]??x}function Ne(x){const _=x.indexOf("#");return _>=0?decodeURIComponent(x.slice(_+1)):""}function bt(x){const _=Ne(x);return _||(typeof window<"u"?decodeURIComponent(window.location.hash.replace(/^#/,"")):"")}function gr(){return m.value??document.getElementById("docs-scroll-container")??document.getElementById("content-container")}function Ye(){var x;(x=gr())==null||x.scrollTo({top:0,left:0,behavior:"auto"}),window.scrollTo({top:0,left:0,behavior:"auto"})}function je(x){if(!x)return!1;const _=document.getElementById(x),D=gr();if(!(_ instanceof HTMLElement))return!1;if(!(D instanceof HTMLElement))return _.scrollIntoView({block:"start"}),!0;const B=D.scrollTop+_.getBoundingClientRect().top-D.getBoundingClientRect().top-24;return D.scrollTo({top:Math.max(0,B),left:0,behavior:"auto"}),!0}async function yt(x,_){await Me(),Se(),Ae(x)!==Ae(_??"")&&Ye();const D=bt(x);D&&(await Me(),je(D))}function nt(x,_,D){const B=(D==null?void 0:D.size)??18,K=(D==null?void 0:D.strokeWidth)??1.5,ve=(D==null?void 0:D.viewBox)??`0 0 ${B} ${B}`,Ee=document.createElementNS("http://www.w3.org/2000/svg","svg");Ee.setAttribute("width",String(B)),Ee.setAttribute("height",String(B)),Ee.setAttribute("viewBox",ve),Ee.setAttribute("fill","none"),Ee.setAttribute("aria-hidden","true"),Ee.setAttribute("class",_);for(const vr of x.split("||")){const Te=document.createElementNS("http://www.w3.org/2000/svg","path");Te.setAttribute("d",vr),D!=null&&D.fill?Te.setAttribute("fill","currentColor"):(Te.setAttribute("stroke","currentColor"),Te.setAttribute("stroke-width",String(K)),Te.setAttribute("stroke-linecap","round"),Te.setAttribute("stroke-linejoin","round")),Ee.append(Te)}return Ee}async function wt(x){var D;try{if((D=navigator.clipboard)!=null&&D.writeText)return await navigator.clipboard.writeText(x),!0}catch{}const _=document.createElement("textarea");_.value=x,_.setAttribute("readonly",""),_.style.position="fixed",_.style.opacity="0",_.style.pointerEvents="none",document.body.append(_),_.select(),_.setSelectionRange(0,x.length);try{return document.execCommand("copy")}finally{_.remove()}}function qi(x){const _=Array.from(x.querySelectorAll("pre code .line"));if(_.length)return _.map(B=>B.textContent??"").join(`
`).replace(/\n$/,"");const D=x.querySelector("pre code");return((D==null?void 0:D.textContent)??"").replace(/\n$/,"")}function Bi(x){const _=new URL(window.location.href);return _.hash=x,_}function ji(){const x=window.getSelection();return!!(x&&x.type==="Range"&&x.toString().trim())}function _n(){document.querySelectorAll(".vp-doc h2[id], .vp-doc h3[id], .vp-doc h4[id], .vp-doc h5[id], .vp-doc h6[id]").forEach(x=>{if(x.dataset.docsHeadingCopyBound==="true")return;const _=x.querySelector(".header-anchor");if(!_)return;x.dataset.docsHeadingCopyBound="true",x.classList.add("docs-copyable-heading");const D=document.createElement("span");for(D.className="anchor-heading__content";x.childNodes.length>0;){const K=x.firstChild;if(K===_)break;D.appendChild(K)}const B=document.createElement("div");B.className="anchor-heading__icon-wrap",B.tabIndex=-1,B.appendChild(_),x.prepend(B),x.appendChild(D),_.replaceChildren(nt("M0 256C0 167.6 71.6 96 160 96h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H160C98.1 144 48 194.1 48 256s50.1 112 112 112h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H160C71.6 416 0 344.4 0 256zm576 0c0 88.4-71.6 160-160 160H344c-13.3 0-24-10.7-24-24s10.7-24 24-24h72c61.9 0 112-50.1 112-112s-50.1-112-112-112H344c-13.3 0-24-10.7-24-24s10.7-24 24-24h72c88.4 0 160 71.6 160 160zM184 232H392c13.3 0 24 10.7 24 24s-10.7 24-24 24H184c-13.3 0-24-10.7-24-24s10.7-24 24-24z","docs-heading-anchor__icon",{fill:!0,size:12,viewBox:"0 0 576 512"})),x.addEventListener("click",K=>{if(ji()||K.target instanceof HTMLElement&&K.target.closest("a:not(.header-anchor)"))return;const ve=K.target instanceof HTMLElement?K.target:null,Ee=ve==null?void 0:ve.closest(".anchor-heading__content"),vr=ve==null?void 0:ve.closest(".header-anchor");if(!Ee&&!vr)return;K.preventDefault();const Te=Bi(x.id);window.history.replaceState(null,"",`${Te.pathname}${Te.search}${Te.hash}`),je(x.id),wt(Te.toString())})})}function Sn(){document.querySelectorAll('.vp-doc [class*="language-"] > button.copy').forEach(x=>{if(x.querySelector(".docs-copy-button__icon")){if(x.dataset.docsCopyBound==="true")return}else{const _=nt("M14.25 5.25H7.25C6.14543 5.25 5.25 6.14543 5.25 7.25V14.25C5.25 15.3546 6.14543 16.25 7.25 16.25H14.25C15.3546 16.25 16.25 15.3546 16.25 14.25V7.25C16.25 6.14543 15.3546 5.25 14.25 5.25Z||M2.80103 11.998L1.77203 5.07397C1.61003 3.98097 2.36403 2.96397 3.45603 2.80197L10.38 1.77297C11.313 1.63397 12.19 2.16297 12.528 3.00097","docs-copy-button__icon"),D=nt("M2.75 9.25L6.75 13.25L15.25 4.75","docs-copy-button__icon docs-copy-button__icon--copied",{size:18,strokeWidth:2,viewBox:"0 0 18 18"}),B=document.createElement("span");B.className="sr-only",B.textContent=x.title||"Copy code",x.replaceChildren(_,D,B)}x.dataset.docsCopyBound="true",x.type="button",x.addEventListener("click",async _=>{_.preventDefault(),_.stopPropagation();const D=x.closest('[class*="language-"]');if(!(D instanceof HTMLElement))return;const B=qi(D);!B||!await wt(B)||(x.classList.add("copied"),window.setTimeout(()=>{x.classList.remove("copied")},1500))})})}function An(){zl()}return qe(async()=>{var x;document.addEventListener("pointerdown",ee),await yt(t.path),Sn(),_n(),(x=d.value)==null||x.addEventListener("scroll",Se,{passive:!0})}),Eo(async()=>{await Me(),Sn(),_n(),je(bt(t.path))}),cr(()=>{var x;document.removeEventListener("pointerdown",ee),(x=d.value)==null||x.removeEventListener("scroll",Se)}),(x,_)=>{var D,B;return J(r).layout!==!1?(M(),O("div",Zl,[j.value?(M(),ke(Vl,{key:0})):Z("",!0),S("div",{class:"max-lg:contents lg:flex lg:w-full","data-docs-theme":"almond",style:Rt(Pe.value)},[S("div",Xl,[S("header",{id:"navbar",class:le(["peer fixed top-0 z-30 w-full",J(l)?"max-lg:pointer-events-none":""])},[S("div",ec,[S("div",tc,[S("div",{class:le(["transition-opacity duration-200",J(l)?"max-lg:pointer-events-none max-lg:opacity-0":""])},[S("div",rc,[S("div",nc,[S("div",oc,[S("a",{href:k.value,class:"flex min-w-0 shrink items-center gap-3 select-none"},[v.value?(M(),O("img",{key:0,src:v.value,alt:"",class:"relative block h-6 w-auto max-w-[156px] shrink-0 object-contain"},null,8,ac)):Z("",!0),v.value?Z("",!0):(M(),O("div",sc,re(b.value),1))],8,ic),L.value.length?(M(),O("div",{key:0,ref_key:"topNavRootMobile",ref:E,class:"relative min-w-0 shrink"},[S("button",{type:"button",class:"inline-flex max-w-full items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white/80 py-1.5 pl-2.5 pr-2 text-sm font-medium text-slate-800","aria-expanded":p.value,"aria-haspopup":"listbox","aria-label":"Documentation section",onClick:Nr(ne,["stop"])},[S("span",cc,re(te.value),1),(M(),O("svg",{class:le(["h-4 w-4 shrink-0 text-slate-500 transition-transform",p.value?"rotate-180":""]),viewBox:"0 0 20 20",fill:"none","aria-hidden":"true"},[..._[3]||(_[3]=[S("path",{d:"M5 7.5L10 12.5L15 7.5",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round","stroke-linejoin":"round"},null,-1)])],2))],8,lc),p.value?(M(),O("div",{key:0,class:"absolute left-0 top-full z-[100] mt-1 min-w-[12rem] max-w-[min(100vw-2rem,22rem)] rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg",role:"listbox","aria-label":`${te.value} options`},[(M(!0),O(ge,null,be(L.value,K=>(M(),O("a",{key:K.link,href:J(ze)(K.link),role:"option",class:le(["block truncate px-3 py-2 text-sm transition",T(K)?"bg-docs-primary-soft font-medium text-docs-primary-strong":"text-slate-700 hover:bg-slate-50"]),"aria-selected":T(K),onClick:$},re(K.text),11,dc))),128))],8,uc)):Z("",!0)],512)):Z("",!0)]),S("div",fc,[j.value?(M(),O("button",{key:0,type:"button",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":"Open search",onClick:An},[..._[4]||(_[4]=[S("svg",{viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round",class:"h-[18px] w-[18px]","aria-hidden":"true"},[S("circle",{cx:"11",cy:"11",r:"8"}),S("path",{d:"m21 21-4.3-4.3"})],-1)])])):Z("",!0),(M(!0),O(ge,null,be(I.value,K=>(M(),O("a",{key:`mobile-${K.link}`,href:K.link,target:"_blank",rel:"noreferrer",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":K.icon},[K.icon==="github"?(M(),O("svg",hc,[..._[5]||(_[5]=[S("path",{d:"M12 .5a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58l-.02-2.04c-3.34.73-4.04-1.42-4.04-1.42-.54-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.9 0-1.3.46-2.36 1.23-3.19-.12-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.22a11.5 11.5 0 0 1 6 0c2.3-1.54 3.3-1.22 3.3-1.22.65 1.65.24 2.87.12 3.17.76.83 1.22 1.89 1.22 3.19 0 4.58-2.81 5.59-5.49 5.89.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.22.7.83.58A12 12 0 0 0 12 .5Z"},null,-1)])])):Z("",!0)],8,mc))),128))])]),J(s)?(M(),O("button",{key:0,type:"button",class:"flex h-14 w-full items-center px-1 text-left cursor-pointer focus:outline-0","aria-label":"Open navigation menu","aria-expanded":J(l),onClick:_[0]||(_[0]=(...K)=>J(c)&&J(c)(...K))},[_[7]||(_[7]=S("div",{class:"text-slate-500 transition hover:text-slate-600"},[S("span",{class:"sr-only"},"Navigation"),S("svg",{class:"h-4",fill:"currentColor",viewBox:"0 0 448 512","aria-hidden":"true"},[S("path",{d:"M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"})])],-1)),S("div",gc,[Q.value?(M(),O("div",vc,[S("span",null,re(Q.value),1),_[6]||(_[6]=S("svg",{width:"3",height:"24",viewBox:"0 -9 3 24",class:"h-5 overflow-visible text-slate-400","aria-hidden":"true"},[S("path",{d:"M0 0L3 3L0 6",fill:"none",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round"})],-1))])):Z("",!0),S("div",bc,re(C.value),1)])],8,pc)):Z("",!0)]),S("div",yc,[S("div",wc,[S("a",{href:k.value,class:"flex min-w-0 items-center gap-3 select-none"},[v.value?(M(),O("img",{key:0,src:v.value,alt:"",class:"relative block h-6 w-auto max-w-[156px] shrink-0 object-contain"},null,8,xc)):Z("",!0),v.value?Z("",!0):(M(),O("div",kc,re(b.value),1))],8,Ec),L.value.length?(M(),O("div",{key:0,ref_key:"topNavRootDesktop",ref:g,class:"relative hidden min-w-0 shrink lg:block"},[S("button",{type:"button",class:"inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200/80 bg-white/70 px-3 py-1.5 text-sm font-medium text-slate-800 backdrop-blur transition hover:bg-slate-100/90","aria-expanded":h.value,"aria-haspopup":"listbox","aria-label":"Documentation section",onClick:Nr(X,["stop"])},[S("span",Sc,re(te.value),1),(M(),O("svg",{class:le(["h-4 w-4 shrink-0 text-slate-500 transition-transform",h.value?"rotate-180":""]),viewBox:"0 0 20 20",fill:"none","aria-hidden":"true"},[..._[8]||(_[8]=[S("path",{d:"M5 7.5L10 12.5L15 7.5",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round","stroke-linejoin":"round"},null,-1)])],2))],8,_c),h.value?(M(),O("div",{key:0,class:"absolute left-0 top-full z-[100] mt-1 min-w-[12rem] max-w-[min(100vw-2rem,22rem)] rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg",role:"listbox","aria-label":`${te.value} options`},[(M(!0),O(ge,null,be(L.value,K=>(M(),O("a",{key:K.link,href:J(ze)(K.link),role:"option",class:le(["block truncate px-3 py-2 text-sm transition",T(K)?"bg-docs-primary-soft font-medium text-docs-primary-strong":"text-slate-700 hover:bg-slate-50"]),"aria-selected":T(K),onClick:$},re(K.text),11,Tc))),128))],8,Ac)):Z("",!0)],512)):Z("",!0)]),S("div",Cc,[S("div",Ic,[(M(!0),O(ge,null,be(I.value,K=>(M(),O("a",{key:K.link,href:K.link,target:"_blank",rel:"noreferrer",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":K.icon},[K.icon==="github"?(M(),O("svg",Mc,[..._[9]||(_[9]=[S("path",{d:"M12 .5a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58l-.02-2.04c-3.34.73-4.04-1.42-4.04-1.42-.54-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.9 0-1.3.46-2.36 1.23-3.19-.12-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.22a11.5 11.5 0 0 1 6 0c2.3-1.54 3.3-1.22 3.3-1.22.65 1.65.24 2.87.12 3.17.76.83 1.22 1.89 1.22 3.19 0 4.58-2.81 5.59-5.49 5.89.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.22.7.83.58A12 12 0 0 0 12 .5Z"},null,-1)])])):Z("",!0)],8,Lc))),128))])])])],2)])])],2),S("div",Rc,[J(s)?(M(),O("div",{key:0,class:le(["fixed inset-0 z-40 lg:hidden",J(l)?"":"pointer-events-none"]),"aria-hidden":J(l)?void 0:"true"},[S("div",{class:le(["absolute inset-0 bg-slate-950/40 backdrop-blur-md transition-opacity duration-300",J(l)?"opacity-100":"opacity-0"]),onClick:_[1]||(_[1]=(...K)=>J(o)&&J(o)(...K))},null,2),S("button",{type:"button",class:le(["absolute right-4 top-5 inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/70 bg-white/95 text-slate-500 shadow-[0_8px_24px_rgba(15,23,42,0.16)] transition duration-300",J(l)?"opacity-100 scale-100":"pointer-events-none opacity-0 scale-95"]),"aria-label":"Close navigation menu",onClick:_[2]||(_[2]=(...K)=>J(o)&&J(o)(...K))},[..._[10]||(_[10]=[S("svg",{viewBox:"0 0 20 20",fill:"none",class:"h-5 w-5","aria-hidden":"true"},[S("path",{d:"M5 5L15 15M15 5L5 15",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round"})],-1)])],2),S("aside",{class:le(["absolute inset-y-0 left-0 w-[min(22rem,calc(100vw-2.5rem))] max-w-full overflow-y-auto overscroll-contain border-r border-slate-200/80 bg-white shadow-[0_28px_90px_rgba(15,23,42,0.22)] transition-transform duration-300",J(l)?"translate-x-0":"pointer-events-none -translate-x-[105%]"])},[kt(Ia,{"logo-src":v.value,"site-title":b.value,onNavigate:J(o)},null,8,["logo-src","site-title","onNavigate"])],2)],10,Fc)):Z("",!0),S("div",{class:le(["mx-auto grid h-[calc(100dvh-7rem)] min-h-0 w-full max-w-[96rem] rounded-2xl px-2 lg:h-[calc(100dvh-4rem)] lg:grid-cols-[16.5rem_minmax(0,1fr)] lg:gap-x-2 lg:px-4",J(s)?"":"lg:grid-cols-[minmax(0,1fr)]"])},[J(s)?(M(),O("div",Oc,[S("div",Pc,[S("div",Nc,[j.value?(M(),O("button",{key:0,type:"button",class:"group/search flex h-9 w-full items-center justify-between gap-2 rounded-lg bg-white pl-3.5 pr-3 text-left text-sm leading-6 text-gray-500 ring-1 ring-gray-400/30 transition-[color,box-shadow] hover:text-gray-800 hover:ring-gray-600/30","aria-label":"Open search",onClick:An},[..._[11]||(_[11]=[ia('<div class="flex min-w-0 items-center gap-2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 min-w-4 flex-none text-gray-700" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg><div class="min-w-0 truncate">Search...</div></div><span class="flex-none text-xs">⌘K</span>',2)])])):Z("",!0)]),S("div",{id:"navigation-items",ref_key:"navigationItems",ref:d,class:le(["stable-scrollbar-gutter pb-4 min-h-0 flex-1 overflow-y-auto",u.value?"[mask-image:linear-gradient(transparent,black_32px)] [-webkit-mask-image:linear-gradient(transparent,black_32px)]":""])},[kt(Ql)],2)])])):Z("",!0),S("div",Dc,[S("div",{id:"docs-scroll-container",ref_key:"docsScrollContainer",ref:m,class:"stable-scrollbar-gutter h-full overflow-y-auto rounded-xl border border-gray-400/30 bg-white px-4 pb-10 pt-6 lg:px-10 lg:pt-10"},[S("div",$c,[S("main",zc,[J(n).isNotFound?(M(),ke(Pa,{key:0})):(M(),O(ge,{key:1},[se.value?(M(),O("div",Vc,re(se.value),1)):Z("",!0),kt(J(Cn),{class:"vp-doc mdx-content relative prose prose-gray [contain:inline-size] isolate"}),fe.value||_e.value||Ie.value||Be.value?(M(),O("div",Hc,[fe.value||_e.value?(M(),O("div",qc,[fe.value?(M(),O("a",{key:0,href:fe.value.href,target:"_blank",rel:"noreferrer",class:"font-medium text-docs-primary transition hover:text-docs-primary-strong"},re(fe.value.text),9,Bc)):Z("",!0),_e.value?(M(),O("div",jc,re(A.value)+": "+re(_e.value),1)):Z("",!0)])):Z("",!0),Ie.value||Be.value?(M(),O("div",Uc,[(D=Ie.value)!=null&&D.link?(M(),O("a",{key:0,href:J(ze)(Ie.value.link),class:"group rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft/50"},[_[12]||(_[12]=S("div",{class:"text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"},"Previous",-1)),S("div",Kc,re(Ie.value.text),1)],8,Wc)):(M(),O("div",Jc)),(B=Be.value)!=null&&B.link?(M(),O("a",{key:2,href:J(ze)(Be.value.link),class:"group rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft/50 sm:text-right"},[_[13]||(_[13]=S("div",{class:"text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"},"Next",-1)),S("div",Yc,re(Be.value.text),1)],8,Gc)):Z("",!0)])):Z("",!0)])):Z("",!0)],64))]),y.value?(M(),O("aside",Qc,[S("div",Zc,[S("div",Xc,[kt(qa)])])])):Z("",!0)])],512)])],2)])])],4)])):(M(),ke(J(Cn),{key:1}))}}});function tu(e={}){return{Layout:eu,async enhanceApp(t){var r;await((r=e.enhanceApp)==null?void 0:r.call(e,t))}}}const ru=`@layer formie-base, formie-theme-base, formie-theme;

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
}`,nu=`@layer formie-theme-base {
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
`,ou=`@layer formie-theme-base {
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
}`,iu=`@layer formie-theme {
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
}`,au=`@layer formie-theme {
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

}`,su=`@layer formie-theme {
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
`,lu=`@layer formie-theme {
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

}`,cu=`@layer formie-theme {
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
}`,uu=`@layer formie-theme {
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
`,du=`@layer formie-theme {
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
}`,fu=`@layer formie-theme {
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
}`,mu=`@layer formie-theme {
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
`,hu=`@layer formie-theme {

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
}`,pu=`@layer formie-theme {
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
`,gu=`@layer formie-theme {
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
}`,vu=`@layer formie-theme {

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
}`,bu=`@layer formie-theme {
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
}`,yu=`@layer formie-theme {
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
}`,wu=`@layer formie-theme {
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
}`,Eu=`@layer formie-theme {
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
}`,xu=`@layer formie-theme {
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
}`,ku=`@layer formie-theme {
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
}`,_u=`@layer formie-theme {

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

}`,Su=`@layer formie-theme {
    .formie-limit-number {
        font-weight: var(--formie-font-weight-semibold);
        color: var(--formie-color-text);
    }

    .formie-limit-number-error {
        color: var(--formie-color-danger);
    }
}`,Au=`@layer formie-theme {
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
`,Tu=`.preview-gallery-page {
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
`,Cu=[{legacyEvent:"onFormieLoaded",canonicalEvent:"formie:mount:after",disposition:"approximate",target:"document"},{legacyEvent:"onFormieInit",canonicalEvent:"formie:mount:after",disposition:"approximate",target:"document"},{legacyEvent:"onFormieReady",canonicalEvent:"formie:mount:after",disposition:"safe"},{legacyEvent:"onAfterFormieSubmit",canonicalEvent:"formie:submit:result",disposition:"safe"},{legacyEvent:"onFormieSubmitError",canonicalEvent:"formie:submit:result",disposition:"safe"},{legacyEvent:"onFormiePageToggle",canonicalEvent:"formie:page:navigate:after",disposition:"safe"},{legacyEvent:"onBeforeFormieSubmit",canonicalEvent:"formie:submit:before",disposition:"approximate"},{legacyEvent:"onFormieValidate",canonicalEvent:"formie:stage:validate:before",disposition:"approximate"},{legacyEvent:"onAfterFormieValidate",canonicalEvent:"formie:stage:validate:after",disposition:"approximate"},{legacyEvent:"onFormieSubmit",canonicalEvent:"formie:submit:after",disposition:"approximate"}];function Iu(e){if(!e)return{enabled:!1,legacyDomEvents:!1,legacyValidatorEvents:!1};if(e===!0)return{enabled:!0,legacyDomEvents:!0,legacyValidatorEvents:!0};const t=e.legacyDomEvents??!0,r=e.legacyValidatorEvents??!0;return{enabled:t||r,legacyDomEvents:t,legacyValidatorEvents:r}}function qr(e){return e}function Ih(e,t){return`formie:field:${e}:${t}`}function zt(e){return`formie:validator:${e}`}function Lh(e,t){return`formie:address:${e}:${t}`}function Mh(e){return`formie:file-upload:${e}`}function Rh(e,t){return`formie:payment:${e}:${t}`}function Br(e){return`formie:state:${e}`}function Lu(e,t){return`formie:module:${e}:${t}`}function Mu(e){return`formie:module:${e}`}function Ru(e,t,r){e.dispatchEvent(new CustomEvent(t,{bubbles:!0,detail:r}))}function Fu(e,t){if(e.canonicalEvent!=="formie:submit:result")return!0;const r=t;return e.legacyEvent==="onAfterFormieSubmit"?!!(r!=null&&r.ok):e.legacyEvent==="onFormieSubmitError"?(r==null?void 0:r.ok)===!1:!0}function Ou(e,t){const r=t&&typeof t=="object"?t:{},n=typeof r.pageId=="string"?r.pageId:"",i=Array.from(e.querySelectorAll("[data-formie-page-id]")),a=i.findIndex(o=>o.getAttribute("data-formie-page-id")===n);return{data:{nextPageId:n,nextPageIndex:a,totalPages:i.length}}}function Pu(e,t,r,n,i){const a=globalThis.Formie||i;return e.legacyEvent==="onFormieLoaded"?{formie:a}:e.legacyEvent==="onFormieInit"?{formie:a,form:i,$form:n,formId:i.id}:e.legacyEvent==="onFormieReady"?{...t&&typeof t=="object"?t:{},form:n,target:r,instance:i}:e.legacyEvent==="onFormiePageToggle"?Ou(n,t):t}function Nu({target:e,form:t,instance:r,options:n,unbinds:i}){n.legacyDomEvents&&Cu.forEach(a=>{const o=s=>{if(!(s instanceof CustomEvent)||!Fu(a,s.detail))return;const l=a.target==="document"?document:t;Ru(l,a.legacyEvent,Pu(a,s.detail,e,t,r))};e.addEventListener(qr(a.canonicalEvent),o),i.push(()=>{e.removeEventListener(qr(a.canonicalEvent),o)})})}function Vt(e,t,r){e.dispatchEvent(new CustomEvent(t,{bubbles:!0,detail:r}))}function Tr(e,t){return!!e&&typeof e=="object"&&e.validator===t}function Du({target:e,form:t,validatorDetail:r,options:n,unbinds:i}){if(!n.legacyValidatorEvents||!r)return;const{validator:a,addValidator:o,removeValidator:s}=r,l={...r,form:t,target:e};Vt(document,"formieValidatorInitialized",l);const c=d=>{!(d instanceof CustomEvent)||!Tr(d.detail,a)||Vt(document,"formieValidatorDestroyed",{...l,...d.detail})},f=d=>{!(d instanceof CustomEvent)||!Tr(d.detail,a)||!(d.target instanceof Element)||t.contains(d.target)&&Vt(d.target,"formieValidatorShowError",{...d.detail,addValidator:o,removeValidator:s,form:t,target:e})},m=d=>{!(d instanceof CustomEvent)||!Tr(d.detail,a)||!(d.target instanceof Element)||t.contains(d.target)&&Vt(d.target,"formieValidatorClearError",{...d.detail,addValidator:o,removeValidator:s,form:t,target:e})};document.addEventListener("formie:validator:destroy",c),document.addEventListener("formie:validator:show-error",f),document.addEventListener("formie:validator:clear-error",m),i.push(()=>{document.removeEventListener("formie:validator:destroy",c),document.removeEventListener("formie:validator:show-error",f),document.removeEventListener("formie:validator:clear-error",m)})}function ie(e,t,r){e.dispatchEvent(new CustomEvent(qr(t),{bubbles:!0,detail:r}))}function on(e){const t=(e.dataset.formieErrorAriaLive||"polite").trim().toLowerCase();return t==="assertive"||t==="off"?t:"polite"}function $u(e,t){return e==="off"?null:t?e:"polite"}function Jo(e){return e==="off"?null:e}function an(e,t){if(t){e.setAttribute("aria-live",t),e.setAttribute("aria-atomic","true");return}e.removeAttribute("aria-live"),e.removeAttribute("aria-atomic")}function Go(){return globalThis}function Yo(){return Go().__FORMIE_DEBUG__===!0}function zu(e){Go().__FORMIE_DEBUG__=e}function Vu(e,t,r){if(Yo()){if(typeof r>"u"){console.log(`[formie:${e}] ${t}`);return}console.log(`[formie:${e}] ${t}`,r)}}function Hu(e,t,r){if(Yo()){if(typeof r>"u"){console.warn(`[formie:${e}] ${t}`);return}console.warn(`[formie:${e}] ${t}`,r)}}function Fe(e,t){const r=t?`${e}:${t}`:e;return{log:(n,i)=>{Vu(r,n,i)},warn:(n,i)=>{Hu(r,n,i)}}}const Lt=Fe("general","page-client-event"),qu="data-formie-client-event",jn="data-formie-pending-client-events";function Bu(e){var t;return typeof window<"u"&&((t=window.CSS)!=null&&t.escape)?window.CSS.escape(e):e.replace(/\\/g,"\\\\").replace(/"/g,'\\"')}function ju(e){var o,s,l;const t=e.querySelector('input[name="pageId"]'),r=(o=t==null?void 0:t.value)==null?void 0:o.trim();if(r)return r;const n=e.querySelector("[data-formie-page]:not([data-formie-page-hidden])"),i=(s=n==null?void 0:n.getAttribute("data-formie-page-id"))==null?void 0:s.trim();if(i)return i;const a=e.querySelector("[data-formie-page]");return((l=a==null?void 0:a.getAttribute("data-formie-page-id"))==null?void 0:l.trim())||null}function Uu(e){if(!(e!=null&&e.trim()))return null;try{const t=JSON.parse(e);return t&&typeof t=="object"?t:null}catch{return Lt.warn("Invalid data-formie-client-event JSON.",{rawPreview:e.slice(0,80)}),null}}function Wu(e){const t={};return e.forEach(r=>{const n=typeof r.label=="string"?r.label.trim():"";n&&(t[n]=typeof r.value=="string"?r.value:"")}),t}function Ku(e){return Array.isArray(e)?e.map(t=>{if(!t||typeof t!="object")return null;const r=t,n=typeof r.event=="string"?r.event.trim():"",i=r.payload&&typeof r.payload=="object"?r.payload:null;return!n||!i?null:{event:n,payload:i}}).filter(t=>t!==null):[]}function sn(e,t){if(!t.length)return;const r=window;r.dataLayer=r.dataLayer||[],t.forEach(n=>{r.dataLayer.push(n.payload),e.dispatchEvent(new CustomEvent("formie:client-event",{bubbles:!0,detail:{event:n.event,payload:n.payload}}))}),Lt.log("Dispatched resolved client events.",{count:t.length,events:t.map(n=>n.event)})}function Ju(e){const t=e.getAttribute(jn);if(t!=null&&t.trim())try{const r=JSON.parse(t),n=Ku(r);n.length&&sn(e,n)}catch{Lt.warn("Invalid pending client events JSON on form element.")}finally{e.removeAttribute(jn)}}function Qo(e,t){if(t!=="submit")return;const r=ju(e);if(!r){Lt.log("No submitted page id; skipping client event.");return}const n=e.querySelector(`[data-formie-page][data-formie-page-id="${Bu(r)}"]`);if(!n){Lt.log("No page section for id; skipping client event.",{pageId:r});return}const i=n.getAttribute(qu);if(i===null)return;const a=Uu(i);if(!a||!Array.isArray(a.fields))return;const o=Wu(a.fields);sn(e,[{event:typeof o.event=="string"&&o.event!==""?o.event:"formPageSubmission",payload:o}])}const nr=new WeakMap,Gu="[data-formie-form], [data-formie], form";function Yu(e){return e?(Array.isArray(e)?e:[e]).flatMap(r=>String(r).split(/\s+/)).map(r=>r.trim()).filter(Boolean):[]}function ln(e){return Array.from(new Set(e))}function Qu(e){if(!e)return{};const t=nr.get(e);if(t)return t;const r=e.closest(Gu);return r?nr.get(r)||{}:{}}function Zu(e){const t={};return Object.entries(e||{}).forEach(([r,n])=>{const i=ln(Yu(n));i.length&&(t[r]=i)}),t}function Un(e,t,r){const n=Zu(t),i=r||(e instanceof HTMLFormElement?e:e.querySelector("form"));return nr.set(e,n),i&&nr.set(i,n),n}function cn(e,t){return Qu(e)[t]||[]}function ue(e,t,...r){const n=ln(r.flatMap(i=>cn(t,i)));n.length&&e.classList.add(...n)}function gt(e,t,...r){const n=ln(r.flatMap(i=>cn(t,i)));n.length&&e.classList.remove(...n)}function mt(e,t,r,n){cn(t,r).forEach(i=>{e.classList.toggle(i,n)})}function Xu(e,t){if(mt(e,e,"tabError",t),t){e.setAttribute("data-formie-tab-error","true");return}e.removeAttribute("data-formie-tab-error")}function rt(e){const t=new Set;e.querySelectorAll("[data-formie-page]").forEach(r=>{const n=r,i=n.getAttribute("data-formie-page-id");i&&n.querySelector("[data-formie-field-has-error]")&&t.add(i)}),e.querySelectorAll("[data-formie-tab]").forEach(r=>{const n=r,i=n.getAttribute("data-formie-page-id");Xu(n,!!i&&t.has(i))})}const ed="data-formie-validation-skip";function Oe(e){return!!e&&e.hasAttribute(ed)}function td(e,t){const r=(e.getAttribute("aria-describedby")||"").trim(),n=r?r.split(/\s+/):[];n.includes(t)||n.push(t),e.setAttribute("aria-describedby",n.join(" ").trim())}function Zo(e){return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find(r=>r.getAttribute("data-formie-field-has-error")==="true"?!0:r.querySelector("[data-formie-field-error]")!==null)||null}function rd(e){const t=Array.from(e.querySelectorAll('[aria-invalid="true"]')).find(r=>!Oe(r));return t||(Array.from(e.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')).find(r=>!Oe(r))??null)}function Xo(e){return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]")}function nd(e){e.querySelectorAll("[data-formie-field-handle]").forEach(t=>{const r=t;if(!(r.getAttribute("data-formie-field-has-error")==="true"||r.querySelector("[data-formie-field-error]")!==null))return;r.setAttribute("data-formie-field-has-error","true"),ue(r,e,"fieldLayoutError");const i=r.querySelector("[data-formie-field-errors]"),a=(i==null?void 0:i.id)||"",o=r.querySelector("[data-formie-field-error]"),s=(o==null?void 0:o.id)||"";r.querySelectorAll("input, select, textarea").forEach(l=>{const c=l;Oe(c)||(c.setAttribute("aria-invalid","true"),ue(c,e,"fieldControlError"),c.setAttribute("data-formie-input-has-error","true"),a&&td(c,a),s&&c.setAttribute("aria-errormessage",s))})})}function od(e){return!!Zo(e)||!!Xo(e)}function ei(e){const t=Zo(e);if(t){const n=rd(t);if(n){if(n.scrollIntoView({behavior:"smooth",block:"center"}),typeof n.focus=="function")try{n.focus({preventScroll:!0})}catch{n.focus()}return!0}return t.scrollIntoView({behavior:"smooth",block:"center"}),!0}const r=Xo(e);return r?(r.scrollIntoView({behavior:"smooth",block:"center"}),!0):!1}class id{constructor(){this.listeners=new Map}on(t,r){var n;return this.listeners.has(t)||this.listeners.set(t,new Set),(n=this.listeners.get(t))==null||n.add(r),()=>{var i;(i=this.listeners.get(t))==null||i.delete(r)}}async emit(t,r){const n=this.listeners.get(t);if(!(!n||n.size===0))for(const i of n)await i(r)}async emitSafe(t,r){const n=this.listeners.get(t),i={eventName:t,total:(n==null?void 0:n.size)||0,succeeded:0,failed:[]};if(!n||n.size===0)return i;let a=0;for(const o of n){try{await o(r),i.succeeded+=1}catch(s){i.failed.push({index:a,error:s})}a+=1}return i}async emitParallelSafe(t,r){const n=this.listeners.get(t),i={eventName:t,total:(n==null?void 0:n.size)||0,succeeded:0,failed:[]};return!n||n.size===0||(await Promise.allSettled(Array.from(n).map(async o=>o(r)))).forEach((o,s)=>{if(o.status==="fulfilled"){i.succeeded+=1;return}i.failed.push({index:s,error:o.reason})}),i}clear(){this.listeners.clear()}}const ti="CRAFT_CSRF_TOKEN",ri="data-formie-csrf-param",ad="data-formie-csrf";function ni(){const e=globalThis.Craft,t=e==null?void 0:e.csrfTokenName;return typeof t=="string"&&t.trim()?t.trim():null}function sd(e){return typeof CSS<"u"&&typeof CSS.escape=="function"?CSS.escape(e):e.replace(/\\/g,"\\\\").replace(/"/g,'\\"')}function Cr(e,t){const r=e.querySelector(`input[name="${sd(t)}"]`);return r instanceof HTMLInputElement?r:null}function ld(e){var n;if(!e)return null;const t=e.querySelector(`input[${ad}]`);if(t instanceof HTMLInputElement&&t.name.trim())return t;if(e instanceof Element){const i=(n=e.getAttribute(ri))==null?void 0:n.trim();if(i){const a=Cr(e,i);if(a)return a}}const r=ni();if(r){const i=Cr(e,r);if(i)return i}return Cr(e,ti)}function un(e){var i,a;const t=ld(e),r=((i=t==null?void 0:t.name)==null?void 0:i.trim())||"",n=((a=t==null?void 0:t.value)==null?void 0:a.trim())||"";return!r||!n?null:{name:r,value:n}}function oi(e,t){const r=un(t);r&&e.append(r.name,r.value)}function Fh(e,t){const r=un(t);r&&(e[r.name]=r.value)}function cd(e,t){var a;const r=e.endsWith("[]")?e.slice(0,-2):e;if(!r)return!1;if(r===ti)return!0;const n=ni();if(n&&r===n)return!0;if(t instanceof Element){const o=(a=t.getAttribute(ri))==null?void 0:a.trim();if(o&&r===o)return!0}const i=un(t);return!!i&&r===i.name}async function ii(e,t={}){const r={Accept:"application/json",...t.headers||{}};return delete r["X-Requested-With"],delete r["x-requested-with"],fetch(String(e),{method:t.method||"GET",body:t.body??null,signal:t.signal,cache:"no-store",headers:r,credentials:"same-origin"})}async function fr(e,t={}){const r=await ii(e,t);if(!r.ok)throw new Error(`Request failed (${r.status}) for ${String(e)}`);return r.json()}async function Oh(e,t={}){const r=await ii(e,t);if(!r.ok)throw new Error(`Request failed (${r.status}) for ${String(e)}`);return r.text()}const xe=Fe("general","transport");function ud(e){const t={};return["theme","themeConfig","locale","siteId"].forEach(r=>{e[r]!==void 0&&(t[r]=e[r])}),t}function ai(e,t="",r={}){if(Array.isArray(e)){const n=e.map(i=>typeof i=="string"?i:String(i??"")).filter(i=>i.trim()!=="");return t&&n.length&&(r[t]=(r[t]||[]).concat(n)),r}return e&&typeof e=="object"&&Object.entries(e).forEach(([n,i])=>{const a=t?`${t}.${n}`:n;ai(i,a,r)}),r}function dd(e,t){const r=e.success===!0,n=e.keepSubmitLoading===!0,i=e.errors,a=ai(i||{}),o=a.form||[],s={};Object.entries(a).forEach(([m,d])=>{if(m==="form")return;const u=m.split(".")[0];s[u]=(s[u]||[]).concat(d)});const l=!r&&o.length===0&&Object.keys(s).length>0?[t||"Submission failed."]:o,c=!r&&n&&l.length===0&&Object.keys(s).length===0;return{ok:r,action:e.submitAction==="back"||e.submitAction==="save"||e.submitAction==="submit"?e.submitAction:void 0,message:e.submitActionMessage||(r?"Submission completed.":c?"":l[0]||"Submission failed."),code:r?void 0:String(e.code||"SUBMIT_ERROR"),keepSubmitLoading:n,fieldErrors:Object.keys(s).length?s:void 0,formErrors:l.length?l:void 0,nextPage:e.nextPageId?{id:String(e.nextPageId)}:null,redirect:e.redirectUrl?{url:String(e.redirectUrl),target:e.submitActionTab==="new-tab"?"new-tab":"same-tab"}:null,submitData:Array.isArray(e.submitData)?e.submitData:void 0,clientEvents:Array.isArray(e.clientEvents)?e.clientEvents:void 0,meta:e}}async function fd(e,t,r={}){const n=JSON.stringify({handle:t,renderOptions:r});xe.log("requestRender start.",{endpoint:e,handle:t});const i=await fr(e,{method:"POST",body:n,headers:{"Content-Type":"application/json"}});return xe.log("requestRender complete.",{hasHtml:!!i.html}),i}async function md(e,t,r={}){var s;const i=JSON.stringify({query:`
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`,variables:{handle:t,input:ud(r)}});xe.log("requestGraphqlRender start.",{endpoint:e,handle:t});const a=await fr(e,{method:"POST",body:i,headers:{"Content-Type":"application/json"}});if(Array.isArray(a.errors)&&a.errors.length>0)throw new Error(a.errors.map(l=>l.message||"Unknown GraphQL error").join("; "));if(!((s=a.data)!=null&&s.formieHtmlForm))throw new Error(`Form not found for handle "${t}".`);const o=a.data.formieHtmlForm;return xe.log("requestGraphqlRender complete.",{hasHtml:!!o.html}),o}async function dn(e,t,r){const n=new URL(e,window.location.origin);n.searchParams.set("handle",t),r&&n.searchParams.set("renderId",r),xe.log("requestRefreshTokens start.",{endpoint:n.toString(),handle:t,hasRenderId:!!r});const i=await fr(n.toString());return xe.log("requestRefreshTokens complete.",{hasRefreshTokens:!!i.refreshTokens}),i.refreshTokens||i}async function hd(e,t,r){const n=new URL(e,window.location.origin),i=new FormData;r&&i.append("pageId",r),t&&(["handle","renderId","draftContextToken","draftContext","continuationToken"].forEach(s=>{var f;const l=t.querySelector(`input[name="${s}"]`),c=(f=l==null?void 0:l.value)==null?void 0:f.trim();c&&i.append(s,c)}),oi(i,t)),xe.log("requestSetPage start.",{requestUrl:n.toString(),pageId:r||null});const a=await fr(n.toString(),{method:"POST",body:i});return xe.log("requestSetPage complete.",a),a}function pd(e,t){const r=new URL(e,window.location.origin),n=new FormData;["handle","renderId","draftContextToken","draftContext"].forEach(a=>{var l;const o=t.querySelector(`input[name="${a}"]`),s=(l=o==null?void 0:o.value)==null?void 0:l.trim();s&&n.append(a,s)}),oi(n,t),xe.log("clearSubmissionOnUnload start.",{requestUrl:r.toString()});try{if(typeof navigator.sendBeacon=="function"&&navigator.sendBeacon(r.toString(),n))return}catch{}fetch(r.toString(),{method:"POST",body:n,credentials:"include",keepalive:!0,headers:{Accept:"application/json"}})}async function gd(e,t){var c,f;const r=(e.getAttribute("method")||"POST").toUpperCase(),n=e.getAttribute("action")||window.location.href,i=((c=e.dataset.formieErrorMessage)==null?void 0:c.trim())||"Submission failed.";xe.log("submitForm start.",{method:r,action:n,submitAction:t.get("submitAction")});const a=await fetch(n,{method:r,body:t,credentials:"include",headers:{Accept:"application/json"}}),o=a.headers.get("content-type")||"";if(!o.includes("application/json"))return a.ok?(xe.log("submitForm non-JSON success response.",{status:a.status,contentType:o}),{ok:!0,message:"Submission completed."}):(xe.warn("submitForm non-JSON HTTP error.",{status:a.status,contentType:o}),{ok:!1,code:"HTTP_ERROR",message:`Request failed (${a.status}).`,formErrors:[`Request failed (${a.status}).`]});const s=await a.json(),l=dd(s,i);return xe.log("submitForm JSON response normalized.",{ok:l.ok,code:l.code,hasRedirect:!!((f=l.redirect)!=null&&f.url),hasSubmitData:Array.isArray(l.submitData)&&l.submitData.length>0}),l}function fn(e){return Array.from(e.querySelectorAll("[data-formie-page]"))}function mr(e){const t=fn(e);if(!t.length)return{scope:e,final:!0};const r=t.find(n=>!n.hasAttribute("data-formie-page-hidden"))||t[t.length-1];return{scope:r,final:r===t[t.length-1]}}const vd=["prepare","normalize","validate","screen","authorize","dispatch","finalize"],bd=["prepare","normalize","validate","screen","authorize"],ce=Fe("general","pipeline");function Ir(e,t){return{ok:!1,stage:e,code:"ABORTED",message:t||"Submission aborted.",formErrors:[t||"Submission aborted."]}}function si(e){return e instanceof HTMLInputElement||e instanceof HTMLSelectElement||e instanceof HTMLTextAreaElement}function li(e){return!(!e.name||e.disabled||e instanceof HTMLInputElement&&(e.type==="submit"||e.type==="button"||e.type==="reset"||e.type==="image"||(e.type==="checkbox"||e.type==="radio")&&!e.checked||e.type==="file"&&(!e.files||e.files.length===0)))}function ci(e,t){if(t instanceof HTMLInputElement){if(t.type==="file"){Array.from(t.files||[]).forEach(r=>{e.append(t.name,r)});return}e.append(t.name,t.value);return}if(t instanceof HTMLSelectElement&&t.multiple){Array.from(t.selectedOptions).forEach(r=>{e.append(t.name,r.value)});return}e.append(t.name,t.value)}function yd(e,t){t.querySelectorAll("input, select, textarea").forEach(r=>{const n=si(r)?r:null;!n||n.closest("[data-formie-page]")||li(n)&&ci(e,n)})}function wd(e,t){const r=new Set;return t.querySelectorAll("input, select, textarea").forEach(n=>{const i=si(n)?n:null;!i||!i.name||i.disabled||i instanceof HTMLInputElement&&(i.type==="submit"||i.type==="button"||i.type==="reset"||i.type==="image")||(i.name.startsWith("fields[")&&r.add(i.name),li(i)&&ci(e,i))}),r}function Ed(e,t){t.forEach(r=>{e.has(r)||e.append(r,"")})}function Wn(e,t){const r=fn(e),n=r.find(o=>!o.hasAttribute("data-formie-page-hidden"))||null;if(!r.length||!n){const o=new FormData(e);return o.set("submitAction",t),o}const i=new FormData;yd(i,e);const a=wd(i,n);return Ed(i,a),i.set("submitAction",t),i}function xd(e,t){if(t!=="submit")return!1;const r=fn(e);return r.length?(r.find(i=>!i.hasAttribute("data-formie-page-hidden"))||r[r.length-1])===r[r.length-1]:!0}async function ui(e,t,r,n={}){ce.log("Starting submit pipeline.",{action:t,preflightOnly:n.preflightOnly===!0});let i=!1,a,o=null;const s=xd(e,t),l={form:e,action:t,formData:Wn(e,t),abort:d=>{i=!0,a=d,ce.warn("Pipeline aborted.",{reason:d})},isAborted:()=>i,abortReason:()=>a},c={prepare:async d=>{const u=d.form.querySelector('input[name="submitAction"]');return u&&(u.value=d.action),d.formData.set("submitAction",d.action),null},normalize:async()=>null,validate:async d=>{var u;if(d.action!=="submit"||n.validateOnSubmit===!1)return null;if(n.validator){const{scope:g,final:E}=mr(d.form),h=n.validator.submit(E?d.form:g,{final:E});if(h.length>0){const p=(u=h[0])==null?void 0:u.input;if(p){p.scrollIntoView({behavior:"smooth",block:"center"});try{p.focus({preventScroll:!0})}catch{p.focus()}}return{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:n.validator.config.errorMessage||"Validation failed.",fieldErrors:n.validator.getFieldErrors(h),formErrors:[n.validator.config.errorMessage||"Validation failed."]}}return null}if(!d.form.checkValidity()){const g=d.form.querySelector(":invalid");return g==null||g.focus(),{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:"Validation failed.",formErrors:["Validation failed."]}}return null},screen:async()=>null,authorize:async()=>null,dispatch:async d=>{d.formData=Wn(d.form,d.action);const u=await gd(d.form,d.formData);return o=u,u},finalize:async d=>{var u;return o&&o.ok&&(u=o.redirect)!=null&&u.url&&(o.redirect.target==="new-tab"?window.open(o.redirect.url,"_blank"):window.location.href=o.redirect.url),null}};{const d=await r.emitSafe("formie:submit:before",l);d.failed.length>0&&ce.warn("Submit before listeners failed.",{eventName:d.eventName,failed:d.failed.length})}if(s){const d=await r.emitSafe("formie:submit:final:before",l);d.failed.length>0&&ce.warn("Final submit before listeners failed.",{eventName:d.eventName,failed:d.failed.length})}const f=n.preflightOnly?bd:vd;for(const d of f){if(ce.log("Stage start.",{stage:d,action:t}),i)return ce.warn("Stage skipped due to abort.",{stage:d,reason:a}),Ir(d,a);{const g=await r.emitSafe(`formie:stage:${d}:before`,{...l,stage:d});g.failed.length>0&&ce.warn("Stage before listeners failed.",{stage:d,failed:g.failed.length})}if(i){const g=Ir(d,a);{const E=await r.emitSafe("formie:submit:after",g);E.failed.length>0&&ce.warn("Submit after listeners failed (abort before stage).",{stage:d,failed:E.failed.length})}if(s){const E=await r.emitSafe("formie:submit:final:after",g);E.failed.length>0&&ce.warn("Final submit after listeners failed (abort before stage).",{stage:d,failed:E.failed.length})}return ce.warn("Aborted after stage before-hooks.",{stage:d,reason:a}),g}const u=await c[d](l);ce.log("Stage runner complete.",{stage:d,hasResult:!!u,ok:u?u.ok:void 0,code:u==null?void 0:u.code});{const g=await r.emitSafe(`formie:stage:${d}:after`,{...l,stage:d,result:u});g.failed.length>0&&ce.warn("Stage after listeners failed.",{stage:d,failed:g.failed.length})}if(i){const g=Ir(d,a);{const E=await r.emitSafe("formie:submit:after",g);E.failed.length>0&&ce.warn("Submit after listeners failed (abort after stage).",{stage:d,failed:E.failed.length})}if(s){const E=await r.emitSafe("formie:submit:final:after",g);E.failed.length>0&&ce.warn("Final submit after listeners failed (abort after stage).",{stage:d,failed:E.failed.length})}return ce.warn("Aborted after stage after-hooks.",{stage:d,reason:a}),g}if(u&&!u.ok){{const g=await r.emitSafe("formie:submit:after",u);g.failed.length>0&&ce.warn("Submit after listeners failed (failed stage).",{stage:d,failed:g.failed.length})}if(s){const g=await r.emitSafe("formie:submit:final:after",u);g.failed.length>0&&ce.warn("Final submit after listeners failed (failed stage).",{stage:d,failed:g.failed.length})}return ce.warn("Pipeline short-circuited by failed stage.",{stage:d,code:u.code,message:u.message}),u}}const m=o||{ok:!0,stage:n.preflightOnly?"authorize":"finalize",message:n.preflightOnly?"Submission preflight completed.":"Submission completed."};{const d=await r.emitSafe("formie:submit:after",m);d.failed.length>0&&ce.warn("Submit after listeners failed (success).",{failed:d.failed.length})}if(s){const d=await r.emitSafe("formie:submit:final:after",m);d.failed.length>0&&ce.warn("Final submit after listeners failed (success).",{failed:d.failed.length})}return ce.log("Pipeline completed.",{ok:m.ok,stage:m.stage,code:m.code}),m}function kd(e){var n;const t=e.querySelector("[data-formie-field-layout]");return((n=t==null?void 0:t.getAttribute("data-formie-error-position"))==null?void 0:n.trim())==="above"?"above":"below"}function di(e,t){const r=e.querySelector("[data-formie-field-errors]");if(r)return r;const n=e.querySelector("[data-formie-field-content]"),i=e.querySelector("[data-formie-field-control]"),a=kd(e),o=document.createElement("div");return o.setAttribute("data-formie-field-errors","true"),t==null||t(o),n&&i?a==="above"?n.insertBefore(o,i):n.appendChild(o):e.appendChild(o),o}const _d={rule:({input:e,getRule:t})=>!t("email")||!e.value||e.value.length<1?!0:/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.value),message:({input:e,label:t,t:r})=>e.getAttribute("data-formie-validation-email-message")??e.getAttribute("data-formie-pattern-email-message")??e.getAttribute("data-pattern-email-message")??r("{label} is not a valid email address.",{label:t})};function Sd(e){var t,r,n;return((n=(r=(t=e==null?void 0:e.querySelector("[data-formie-field-label]"))==null?void 0:t.childNodes[0])==null?void 0:r.textContent)==null?void 0:n.trim())||""}function Kn(e){const t=e.getRule("match");if(!t||t===!0||typeof t!="object"||!e.field)return null;const r=typeof t.fieldHandle=="string"?t.fieldHandle.trim():"";if(!r)return null;const n=e.form.querySelector(`[data-formie-field-handle="${r}"]`);return n?Array.from(n.querySelectorAll(e.config.fieldsSelector)).find(i=>(i instanceof HTMLInputElement||i instanceof HTMLSelectElement||i instanceof HTMLTextAreaElement)&&!Oe(i))??null:null}const Ad={rule:e=>{const t=Kn(e);return t?t.value===e.input.value:!0},message:e=>{const t=Kn(e),r=t==null?void 0:t.closest("[data-formie-field-handle]"),n=Sd(r);return e.input.getAttribute("data-formie-validation-match-message")??e.t("{label} must match {value}.",{label:e.label,value:n})}},Td={rule:({input:e,getRule:t})=>{const r=t("number");if(!r||!e.value||e.value.trim()==="")return!0;const n=parseFloat(e.value);if(Number.isNaN(n))return!1;if(r!==!0&&typeof r=="object"){const i=typeof r.min=="number"?r.min:null,a=typeof r.max=="number"?r.max:null;if(i!==null&&n<i||a!==null&&n>a)return!1}return!0},message:({input:e,label:t,getRule:r,t:n})=>{const i=r("number"),a=i!==!0&&i&&typeof i=="object"&&typeof i.min=="number"?i.min:null,o=i!==!0&&i&&typeof i=="object"&&typeof i.max=="number"?i.max:null;return a!==null&&o!==null?e.getAttribute("data-formie-validation-number-min-message")??n("{label} must be no less than {min}.",{label:t,min:a}):a!==null?e.getAttribute("data-formie-validation-number-min-message")??n("{label} must be no less than {min}.",{label:t,min:a}):o!==null?e.getAttribute("data-formie-validation-number-max-message")??n("{label} must be no greater than {max}.",{label:t,max:o}):e.getAttribute("data-formie-validation-number-message")??e.getAttribute("data-formie-pattern-number-message")??e.getAttribute("data-pattern-number-message")??n("{label} is not a valid number.",{label:t})}},Cd={rule:({input:e,getRule:t})=>{var r;if(!t("required")||e.type==="hidden")return!0;if(e.type==="checkbox"||e.type==="radio"){const n=((r=e.form)==null?void 0:r.querySelectorAll(`[name="${e.name}"]:not([type="hidden"]):not([disabled])`))||[];return n.length?Array.from(n).some(i=>i instanceof HTMLInputElement&&i.checked):e instanceof HTMLInputElement?e.checked:!0}return e.value.trim()!==""},message:({input:e,label:t,t:r})=>e.getAttribute("data-formie-required-message")??e.getAttribute("data-required-message")??r("{label} cannot be blank.",{label:t})},Id={rule:({input:e,getRule:t})=>{if(!t("url")||!e.value||e.value.length<1)return!0;try{return new URL(e.value),!0}catch{return!1}},message:({input:e,label:t,t:r})=>e.getAttribute("data-formie-pattern-url-message")??e.getAttribute("data-pattern-url-message")??r("{label} is not a valid URL.",{label:t})},Ld={required:Cd,email:_d,url:Id,number:Td,match:Ad};function fi(){return window.FormieTranslations||{}}function Md(){var r;if(typeof document>"u")return;const e=Array.from(document.querySelectorAll('script[type="application/json"][data-formie-translations]:not([data-formie-translations-loaded="true"])'));if(e.length===0)return;let t=null;for(const n of e){n.dataset.formieTranslationsLoaded="true";const i=(r=n.textContent)==null?void 0:r.trim();if(i)try{const a=JSON.parse(i);if(!a||Array.isArray(a)||typeof a!="object")continue;t={...t??fi(),...a}}catch{continue}}t&&(window.FormieTranslations=t)}function Rd(){return Md(),fi()}function Fd(e){const t={};let r=0;for(;r<e.length;){for(;r<e.length&&/\s/.test(e[r]);)r++;if(r>=e.length)break;const n=e.slice(r).match(/^(\w+|=\d+)\{/);if(!n)break;const i=n[1];r+=n[0].length;let a=1;const o=r;for(;r<e.length&&a>0;)e[r]==="{"?a++:e[r]==="}"&&a--,a>0&&r++;t[i]=e.slice(o,r),r++}return t}function Od(e,t){const r=`=${e}`;if(Object.prototype.hasOwnProperty.call(t,r))return t[r];if(typeof Intl<"u"&&typeof Intl.PluralRules=="function"){const i=new Intl.PluralRules().select(e);if(Object.prototype.hasOwnProperty.call(t,i))return t[i]}if(e===1&&Object.prototype.hasOwnProperty.call(t,"one"))return t.one;if(Object.prototype.hasOwnProperty.call(t,"other"))return t.other;const n=Object.keys(t)[0];return n?t[n]:""}function Pd(e,t){const r=e.slice(t).match(/^\{(\w+),\s*plural,\s*/);if(!r)return null;const n=r[1],i=t+r[0].length;let a=i;for(;a<e.length;){for(;a<e.length&&/\s/.test(e[a]);)a++;if(a>=e.length||e[a]==="}")break;const o=e.slice(a).match(/^(\w+|=\d+)\{/);if(!o)return null;a+=o[0].length;let s=1;for(;a<e.length&&s>0;)e[a]==="{"?s++:e[a]==="}"&&s--,s>0&&a++;a++}return a>=e.length||e[a]!=="}"?null:{param:n,body:e.slice(i,a),endIndex:a}}function Nd(e,t){let r="",n=0;for(;n<e.length;){if(e[n]!=="{"){r+=e[n],n++;continue}const i=Pd(e,n);if(!i){r+=e[n],n++;continue}const a=t[i.param],o=typeof a=="number"?a:Number.parseInt(String(a??""),10)||0,s=Fd(i.body);let l=Od(o,s);l=l.replace(/#/g,String(o)),r+=l,n=i.endIndex+1}return r}function Dd(e,t){return e.replace(/\{(\w+),\s*number\}/g,(r,n)=>{if(!Object.prototype.hasOwnProperty.call(t,n))return r;const i=t[n];return typeof i=="number"?i.toLocaleString():String(i)})}function $d(e,t){return e.replace(/\{(\w+)\}/g,(r,n)=>Object.prototype.hasOwnProperty.call(t,n)?String(t[n]):r)}function Ve(e,t={}){let r=Rd()[e]||e;return r=Nd(r,t),r=Dd(r,t),r=$d(r,t),r}const zd={email:/^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,url:/^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,number:/^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,color:/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,date:/(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,time:/^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,month:/^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/},Qe=Fe("general","validator");function xt(e){return!!e&&(e instanceof HTMLInputElement||e instanceof HTMLSelectElement||e instanceof HTMLTextAreaElement)}function Vd(e,t){const r=(e.getAttribute("aria-describedby")||"").trim();if(!r)return;const n=r.split(/\s+/).filter(i=>i!==t);if(n.length){e.setAttribute("aria-describedby",n.join(" "));return}e.removeAttribute("aria-describedby")}function Hd(e,t){const r=(e.getAttribute("aria-describedby")||"").trim(),n=r?r.split(/\s+/):[];n.includes(t)||n.push(t),e.setAttribute("aria-describedby",n.join(" ").trim())}function qd(e,t){e.setAttribute("aria-errormessage",t)}function Bd(e,t){e.getAttribute("aria-errormessage")===t&&e.removeAttribute("aria-errormessage")}class jd{constructor(t,r={}){this.errors=[],this.validators={},this.boundListeners=!1,this.activated=new WeakSet,this.submitted=!1,this.initialValues=new WeakMap,this.form=t,this.onBlur=this.blurHandler.bind(this),this.onChange=this.changeHandler.bind(this),this.onInput=this.inputHandler.bind(this),this.config={live:!1,errorAriaLive:"polite",errorMessage:"",fieldContainerErrorClass:[],inputErrorClass:[],messagesClass:[],messageClass:[],fieldsSelector:'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',patterns:zd,...r},Object.entries(Ld).forEach(([n,i])=>{this.addValidator(n,i.rule,i.message)}),this.init()}init(){Qe.log("Initializing validator.",{formId:this.form.id||null,live:this.config.live}),this.form.setAttribute("novalidate","true"),this.inputs().forEach(t=>{this.initialValues.set(t,this.getInputValue(t))}),this.config.live&&this.addEventListeners(),this.emitEvent(document,zt("ready"),{validator:this})}inputs(t=null){if(xt(t))return Oe(t)?[]:[t];const r=t||this.form;return Array.from(r.querySelectorAll(this.config.fieldsSelector)).filter(n=>xt(n)&&!Oe(n))}getInputValue(t){var r;return t instanceof HTMLInputElement&&(t.type==="checkbox"||t.type==="radio")?t.checked:t instanceof HTMLInputElement&&t.type==="file"?(r=t.files)!=null&&r.length?Array.from(t.files).map(n=>n.name).join("|"):"":t.value??""}isDirty(t){return this.initialValues.has(t)?this.getInputValue(t)!==this.initialValues.get(t):(this.initialValues.set(t,this.getInputValue(t)),!1)}shouldShowError(t){return this.submitted||this.activated.has(t)}isValid(t=null,r={}){return this.validate(t,r).length===0}validate(t=null,r={}){this.errors=[];const n=new Set;return this.inputs(t).forEach(i=>{let a=!1;if(!this.isVisible(i,r))return;const o=i.closest("[data-formie-field-handle]"),s=i instanceof HTMLInputElement&&(i.type==="checkbox"||i.type==="radio")?`${(o==null?void 0:o.getAttribute("data-formie-field-handle"))||""}:${i.name}`:null;if(s){if(n.has(s))return;n.add(s)}this.shouldShowError(i)&&this.removeError(i);const l=this.getValidatorCallbackOptions(i);Object.entries(this.validators).forEach(([c,f])=>{var d;if(!f.validate(l)){const u=this.getErrorMessage(i,c,f,l);this.shouldShowError(i)&&!a&&this.showError(i,c,u),this.errors.push({input:i,field:l.field,validator:c,message:u,handle:((d=l.field)==null?void 0:d.getAttribute("data-formie-field-handle"))||null,result:!1}),a=!0}}),!a&&this.shouldShowError(i)&&this.removeError(i)}),Qe.log("Validation pass complete.",{errorCount:this.errors.length,includeHiddenPages:r.includeHiddenPages===!0}),this.errors}removeAllErrors(){this.inputs().forEach(t=>{this.removeError(t)})}removeError(t){var a;const r=t.closest("[data-formie-field-handle]");if(!r){t.removeAttribute("aria-invalid");return}const n=r.querySelector("[data-formie-field-errors]"),i=(n==null?void 0:n.id)||"";r.querySelectorAll("[data-formie-field-error]").forEach(o=>{o.remove()}),n&&(n.innerHTML=""),r.querySelectorAll("input, select, textarea").forEach(o=>{const s=o;s.removeAttribute("aria-invalid"),this.config.inputErrorClass.length&&s.classList.remove(...this.config.inputErrorClass),s.removeAttribute("data-formie-input-has-error"),i&&Vd(s,i),r.querySelectorAll("[data-formie-field-error]").forEach(l=>{const c=l.id;c&&Bd(s,c)})});for(let o=r;o;o=(a=o.parentElement)==null?void 0:a.closest("[data-formie-field-handle]"))this.config.fieldContainerErrorClass.length&&o.classList.remove(...this.config.fieldContainerErrorClass),o.removeAttribute("data-formie-field-has-error");this.emitEvent(t,zt("clear-error"),{validator:this}),rt(this.form)}showError(t,r,n){var c;const i=t.closest("[data-formie-field-handle]");if(!i)return;let a=i.querySelector("[data-formie-field-errors]");a||(a=di(i,f=>{this.config.messagesClass.length&&f.classList.add(...this.config.messagesClass)})),this.config.messagesClass.length&&a.classList.add(...this.config.messagesClass),a.innerHTML="";const o=i.getAttribute("data-formie-field-handle")||"field",s=`${o}-error`;a.id=a.id||`${o}-errors`,an(a,$u(this.config.errorAriaLive,this.submitted));const l=document.createElement("div");l.setAttribute("data-formie-field-error","true"),l.setAttribute(`data-formie-field-error-${r}`,"true"),l.setAttribute("id",s),l.setAttribute("role","alert"),this.config.messageClass.length&&l.classList.add(...this.config.messageClass),l.textContent=n,a.appendChild(l),i.setAttribute("data-formie-field-has-error","true"),i.querySelectorAll("input, select, textarea").forEach(f=>{const m=f;Oe(m)||(m.setAttribute("aria-invalid","true"),this.config.inputErrorClass.length&&m.classList.add(...this.config.inputErrorClass),m.setAttribute("data-formie-input-has-error","true"),Hd(m,a.id),qd(m,s))});for(let f=i;f;f=(c=f.parentElement)==null?void 0:c.closest("[data-formie-field-handle]"))this.config.fieldContainerErrorClass.length&&f.classList.add(...this.config.fieldContainerErrorClass),f.setAttribute("data-formie-field-has-error","true");this.emitEvent(t,zt("show-error"),{validator:this,validatorName:r,errorMessage:n}),rt(this.form)}getValidatorCallbackOptions(t){var a,o,s;const r=t.closest("[data-formie-field-handle]"),n=((s=(o=(a=r==null?void 0:r.querySelector("[data-formie-field-label]"))==null?void 0:a.childNodes[0])==null?void 0:o.textContent)==null?void 0:s.trim())??"",i=this.parseValidationRules(r==null?void 0:r.getAttribute("data-formie-validation"));return{t:Ve,input:t,label:n,field:r,form:this.form,config:this.config,rules:i,getRule:l=>this.getRule(r,l)}}getErrorMessage(t,r,n,i){return(typeof n.errorMessage=="function"?n.errorMessage(i):n.errorMessage)??Ve("{label} is invalid.",{label:i.label})}getErrors(){return this.errors}getFieldErrors(t=this.errors){const r={};return t.forEach(n=>{var i;!n.handle||(i=r[n.handle])!=null&&i.length||(r[n.handle]=[n.message])}),r}getRule(t,r){if(!t)return!1;const n=this.parseValidationRules(t.getAttribute("data-formie-validation"));return Object.prototype.hasOwnProperty.call(n,r)?n[r]:!1}parseValidationRules(t){const r={};if(!t)return r;let n=null;try{n=JSON.parse(t)}catch{return Qe.warn("Invalid validation rules payload.",{formId:this.form.id||null}),r}return Array.isArray(n)&&n.forEach(i=>{if(!i||typeof i!="object"||Array.isArray(i))return;const a=i,o=typeof a.type=="string"?a.type.trim():"";o&&(r[o]=a)}),r}destroy(){Qe.log("Destroying validator.",{formId:this.form.id||null}),this.removeEventListeners(),this.form.removeAttribute("novalidate"),this.emitEvent(document,zt("destroy"),{validator:this})}isVisible(t,r={}){return t.disabled||t.hasAttribute("data-formie-conditions-disabled")||t.closest("[data-formie-conditions-disabled]")||t.closest("[data-formie-conditionally-hidden]")?!1:t.closest("[data-formie-page-hidden]")?!!r.includeHiddenPages:!!(t.offsetWidth||t.offsetHeight||t.getClientRects().length)}blurHandler(t){var r;!(t.target instanceof HTMLElement)||!xt(t.target)||Oe(t.target)||!((r=t.target.form)!=null&&r.isSameNode(this.form))||t instanceof CustomEvent||t.target instanceof HTMLInputElement&&t.target.type==="file"||t.target instanceof HTMLInputElement&&(t.target.type==="checkbox"||t.target.type==="radio")||(this.isDirty(t.target)&&this.activated.add(t.target),this.shouldShowError(t.target)&&this.validate(t.target))}changeHandler(t){var r;if(!(!(t.target instanceof HTMLElement)||!xt(t.target)||Oe(t.target)||!((r=t.target.form)!=null&&r.isSameNode(this.form)))&&!(t instanceof CustomEvent)){if(t.target instanceof HTMLSelectElement){this.activated.add(t.target),this.validate(t.target);return}t.target instanceof HTMLInputElement&&(t.target.type!=="file"&&t.target.type!=="checkbox"&&t.target.type!=="radio"||(this.activated.add(t.target),this.validate(t.target)))}}inputHandler(t){var r;!(t.target instanceof HTMLElement)||!xt(t.target)||Oe(t.target)||!((r=t.target.form)!=null&&r.isSameNode(this.form))||t instanceof CustomEvent||t.target instanceof HTMLInputElement&&(t.target.type==="checkbox"||t.target.type==="radio")||this.shouldShowError(t.target)&&this.validate(t.target)}submit(t=null,{final:r=!1}={}){return this.submitted=!0,Qe.log("Submit validation requested.",{final:r}),this.boundListeners||this.addEventListeners(),this.removeAllErrors(),this.validate(t,{includeHiddenPages:r})}resetLiveState(){this.submitted=!1,this.activated=new WeakSet,this.errors=[],this.removeAllErrors()}addEventListeners(){this.boundListeners||(this.form.addEventListener("blur",this.onBlur,!0),this.form.addEventListener("change",this.onChange,!1),this.form.addEventListener("input",this.onInput,!1),this.boundListeners=!0,Qe.log("Event listeners attached."))}removeEventListeners(){this.form.removeEventListener("blur",this.onBlur,!0),this.form.removeEventListener("change",this.onChange,!1),this.form.removeEventListener("input",this.onInput,!1),this.boundListeners=!1,Qe.log("Event listeners removed.")}emitEvent(t,r,n={}){t.dispatchEvent(new CustomEvent(r,{bubbles:!0,detail:n}))}addValidator(t,r,n){this.validators[t]={validate:r,errorMessage:n}}removeValidator(t){delete this.validators[t]}}const Ht="data-formie-submit-validation-disabled",Lr="data-formie-preserve-disabled",Ud="data-formie-submit-ready";function mi(e){return e.dataset.formieDisableSubmitUntilValid==="true"}function Wd(e){return Array.from(e.querySelectorAll('button[data-formie-action="submit"]')).filter(t=>t instanceof HTMLButtonElement)}function Kd(e){return!e.hasAttribute("data-formie-conditionally-hidden")&&!e.closest("[data-formie-conditionally-hidden]")}function mn(e,t){if(!mi(e)||e.getAttribute("data-formie-loading")==="true")return;const{scope:r,final:n}=mr(e),i=t.isValid(r,{includeHiddenPages:n});e.setAttribute(Ud,i?"true":"false"),Wd(e).forEach(a=>{if(Kd(a)){if(i){if(!a.hasAttribute(Ht))return;a.hasAttribute(Lr)?(a.disabled=!0,a.removeAttribute(Lr)):a.disabled=!1,a.removeAttribute(Ht);return}a.hasAttribute(Ht)||(a.disabled&&a.setAttribute(Lr,"true"),a.setAttribute(Ht,"true")),a.disabled=!0}})}function Jd(e,t,r){if(!mi(e))return()=>{};let n=!1;const i=()=>{n||(n=!0,queueMicrotask(()=>{n=!1,mn(e,t)}))};i();const a=()=>{i()};e.addEventListener("input",a,!0),e.addEventListener("change",a,!0);const o=()=>{window.setTimeout(()=>{i()},0)};e.addEventListener("reset",o);const s=()=>{i()};r.addEventListener("formie:conditions:evaluated",s);const l=new MutationObserver(c=>{c.some(m=>{if(m.type==="attributes"){const d=m.attributeName||"";return d==="data-formie-page-hidden"||d==="data-formie-conditionally-hidden"||d==="data-formie-loading"||d==="disabled"}return m.type==="childList"})&&i()});return l.observe(e,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["data-formie-page-hidden","data-formie-conditionally-hidden","data-formie-loading","disabled"]}),()=>{e.removeEventListener("input",a,!0),e.removeEventListener("change",a,!0),e.removeEventListener("reset",o),r.removeEventListener("formie:conditions:evaluated",s),l.disconnect()}}const Gd="STALE_SUBMISSION_STATE",Jn=new WeakMap,or=new WeakMap,Ke=Fe("general","submit-result");function jr(e,t,r){let n=e.querySelector(`input[name="${t}"]`);n||(n=document.createElement("input"),n.type="hidden",n.name=t,e.appendChild(n)),n.value=r}function Gn(e,t){e.setAttribute("data-formie-internal-navigation",t)}function _t(e,t){const r=e.querySelector(`input[name="${t}"]`);r==null||r.remove()}function Yd(e,t){try{const r=new URL(e,window.location.href);return r.searchParams.delete(t),r.toString()}catch{return e}}function Qd(e){try{return new URL(e,window.location.href).origin===window.location.origin}catch{return!1}}function hi(e){return Array.from(e.querySelectorAll("[data-formie-page]"))}function Zd(e){return Array.from(e.querySelectorAll("[data-formie-tab]"))}function Xd(e,t,r){return t<0||r<1?0:(e.dataset.formieProgressCalculation==="page-position"?"page-position":"completion")==="page-position"?Math.round((t+1)/r*100):Math.round(t/r*100)}function ef(e){return e<=0?"start":e>=100?"end":"middle"}function tf(e){return(e.dataset.formieSubmitAction||"").trim()}function Yn(e,t){var n;const r=(n=t.meta)==null?void 0:n.effectiveSubmitAction;return typeof r=="string"&&r.trim()!==""?r.trim():tf(e)}function Qn(e){const t=e.dataset.formieSubmitActionFormHide;if(t===void 0)return!1;const r=t.trim().toLowerCase();return r==="true"||r==="1"||r===""}function hn(e,t){const r=["[data-formie-form-header]","[data-formie-form-navigation]","[data-formie-form-body]","[data-formie-form-footer]"];e.toggleAttribute("data-formie-form-hidden",t),r.forEach(n=>{e.querySelectorAll(n).forEach(i=>{const a=i;t?a.hidden=!0:a.hidden=!1})})}function $e(e){const t=Jn.get(e);typeof t=="number"&&(window.clearTimeout(t),Jn.delete(e))}function rf(e,t){or.has(e)||or.set(e,e.innerHTML),e.textContent=t}function Ur(e){const t=or.get(e);t!==void 0&&(e.innerHTML=t,or.delete(e))}function nf(e,t){const r=e.querySelector("[data-formie-progress-bar]"),n=e.querySelector("[data-formie-progress-value]");r&&(r.style.width=`${t}%`,r.setAttribute("aria-valuenow",`${t}`),r.setAttribute("data-formie-progress-state",ef(t)),n&&(n.textContent=`${t}%`,n.setAttribute("data-formie-progress-value",`${t}`)))}function of(e,t){var n;if(!t)return;const r=(e.dataset.formieLoadingIndicator||"").trim();if(r){if(t.setAttribute("data-formie-loading-indicator",r),r==="spinner"){mt(t,e,"loading",!0),Ur(t),t.removeAttribute("data-formie-loading-text");return}if(r==="text"){const i=(e.dataset.formieLoadingIndicatorText||"").trim(),a=((n=t.textContent)==null?void 0:n.trim())||"",o=i||a;t.setAttribute("data-formie-loading-text",o),rf(t,o);return}Ur(t),t.removeAttribute("data-formie-loading-text")}}function pi(e){return Array.from(e.querySelectorAll("[data-formie-action]"))}function gi(e,t){if(e.getAttribute("data-formie-loading")==="true")return;e.setAttribute("data-formie-loading","true"),pi(e).forEach(n=>{"disabled"in n&&(n.disabled?n.setAttribute("data-formie-was-disabled","true"):n.removeAttribute("data-formie-was-disabled"),n.disabled=!0)}),t&&(t.setAttribute("data-formie-loading","true"),of(e,t))}function ir(e){if(e.removeAttribute("data-formie-loading"),pi(e).forEach(r=>{if("disabled"in r){const n=r,i=n.getAttribute("data-formie-was-disabled")==="true";n.disabled=i}Ur(r),r.removeAttribute("data-formie-was-disabled"),r.removeAttribute("data-formie-loading"),mt(r,e,"loading",!1),r.removeAttribute("data-formie-loading-indicator"),r.removeAttribute("data-formie-loading-text")}),e.dataset.formieDisableSubmitUntilValid==="true"){const r=e;r.formieValidation&&mn(e,r.formieValidation)}}function pn(e,t){const r=hi(e),n=Zd(e),i=r.findIndex(a=>a.getAttribute("data-formie-page-id")===t);if(r.forEach(a=>{a.getAttribute("data-formie-page-id")===t?(a.removeAttribute("data-formie-page-hidden"),gt(a,e,"pageHidden")):(a.setAttribute("data-formie-page-hidden","true"),ue(a,e,"pageHidden"))}),n.forEach((a,o)=>{const s=a.getAttribute("data-formie-page-id")===t,l=i>-1&&o<i;mt(a,e,"tabCurrent",s),mt(a,e,"tabComplete",l);const c=a.querySelector("[data-formie-tab-link]");c&&(mt(c,e,"tabLinkCurrent",s),s?gt(c,e,"tabLinkInactive"):ue(c,e,"tabLinkInactive")),s?a.setAttribute("aria-current","page"):a.removeAttribute("aria-current"),l?a.setAttribute("data-formie-tab-complete","true"):a.removeAttribute("data-formie-tab-complete")}),i>-1&&r.length>0){const a=Xd(e,i,r.length);nf(e,a)}if(jr(e,"pageId",t),rt(e),e.dataset.formieDisableSubmitUntilValid==="true"){const a=e;a.formieValidation&&mn(e,a.formieValidation)}}function af(e,t){var i,a,o,s;const r=(i=t.meta)==null?void 0:i.submissionUid;typeof r=="string"&&r.trim()!==""&&jr(e,"submissionUid",r);const n=(s=(o=(a=t.meta)==null?void 0:a.session)==null?void 0:o.continuation)==null?void 0:s.continuationToken;typeof n=="string"&&n.trim()!==""?jr(e,"continuationToken",n):_t(e,"continuationToken")}function sf(e){const t=e.getAttribute("action");t&&e.setAttribute("action",Yd(t,"resumeToken"));try{const r=new URL(window.location.href);if(!r.searchParams.has("resumeToken"))return;r.searchParams.delete("resumeToken"),window.history.replaceState({},document.title,`${r.pathname}${r.search}${r.hash}`)}catch{}}function lf(e,t){var a;const r=(a=t.meta)==null?void 0:a.resumeUrl;if(typeof r!="string"||r.trim()==="")return;const n=r.trim();if(!Qd(n))return;e.getAttribute("action")&&e.setAttribute("action",n);try{const o=new URL(n,window.location.href);window.history.replaceState({},document.title,`${o.pathname}${o.search}${o.hash}`)}catch{}}function qt(e,t={}){var a;const n=e.formieValidation,i=(a=hi(e)[0])==null?void 0:a.getAttribute("data-formie-page-id");if($e(e),e.reset(),t.preserveHiddenState||hn(e,!1),_t(e,"submissionId"),_t(e,"submissionUid"),_t(e,"continuationToken"),_t(e,"pageId"),sf(e),n==null||n.resetLiveState(),i){pn(e,i),e.dispatchEvent(new CustomEvent(Br("reset"),{bubbles:!0}));return}rt(e),e.dispatchEvent(new CustomEvent(Br("reset"),{bubbles:!0}))}function cf(e){var t;return e.code===Gd||((t=e.meta)==null?void 0:t.resetState)===!0}function uf(e,t){const r=t.submitData,n=new Set;let i=!1;if(Array.isArray(r)&&r.length>0){const f=r.filter(m=>typeof m=="object"&&m!==null&&"event"in m&&typeof m.event=="string");for(const m of f){const d=m.event;n.add(d),Ke.log("Dispatching submitData event.",{eventName:d}),d.startsWith("formie:payment:")&&(i=!0),e.dispatchEvent(new CustomEvent(d,{bubbles:!0,detail:{data:m.data}}))}}const a=t.meta||{},o=(a.paymentAction&&typeof a.paymentAction=="object"?a.paymentAction:null)||(a.paymentDecision&&typeof a.paymentDecision=="object"?a.paymentDecision.action:null),s=o?String(o.event||""):"",l=o?o.payload:void 0,c=s;return c&&!n.has(c)&&(c.startsWith("formie:payment:")&&(i=!0),e.dispatchEvent(new CustomEvent(c,{bubbles:!0,detail:{data:l}})),Ke.log("Dispatching fallback payment action event.",{eventName:c})),{hasPaymentFollowUpEvent:i}}function df(e,t,r){var i,a,o,s,l;if(Ke.log("Applying submit result state.",{ok:t.ok,action:r,code:t.code,hasRedirect:!!((i=t.redirect)!=null&&i.url),hasSubmitData:Array.isArray(t.submitData)&&t.submitData.length>0}),cf(t)){qt(e),Ke.log("Resetting state due to stale/reset marker.");return}const n=uf(e,t);if(!t.ok&&((a=t.redirect)!=null&&a.url)&&!n.hasPaymentFollowUpEvent){Ke.log("Applying redirect fallback for failed result.",{url:t.redirect.url,target:t.redirect.target}),$e(e),t.redirect.target==="new-tab"?window.open(t.redirect.url,"_blank"):(Gn(e,"redirect"),window.location.href=t.redirect.url);return}if(af(e,t),!t.ok){Ke.log("Non-redirect failure; keeping current form state."),$e(e);return}if(Array.isArray(t.clientEvents)&&t.clientEvents.length>0?sn(e,t.clientEvents):Qo(e,r),(o=t.nextPage)!=null&&o.id){$e(e);const f=e.formieValidation;f==null||f.resetLiveState(),pn(e,t.nextPage.id),ie(e,"formie:page:navigate:after",{pageId:t.nextPage.id}),Ke.log("Advanced to next page.",{nextPageId:t.nextPage.id});return}if(r==="save"){$e(e),lf(e,t),Ke.log("Applied save/resume token state.");return}if(r==="submit"&&!((s=t.redirect)!=null&&s.url)){const c=Yn(e,t),f=c==="message"&&Qn(e);if(c==="reload"){$e(e),Gn(e,"reload"),window.location.reload();return}if(c==="reset"){qt(e);return}$e(e),qt(e,{preserveHiddenState:f});return}if(r==="submit"&&((l=t.redirect)!=null&&l.url)&&t.redirect.target==="new-tab"){const f=Yn(e,t)==="message"&&Qn(e);$e(e),qt(e,{preserveHiddenState:f});return}$e(e)}const ar=new WeakMap;function vi(e){return(e.dataset.formieSubmitAction||"").trim()}function ff(e){return(e.dataset.formieErrorMessagePosition||"top-form").trim()||"top-form"}function bi(e){return(e.dataset.formieSubmitActionMessagePosition||"").trim()}function mf(e){const t=(e.dataset.formieSubmitActionMessageTimeout||"").trim();if(!t)return null;const r=Number.parseFloat(t);return!Number.isFinite(r)||r<0?null:Math.round(r*1e3)}function gn(e){const t=e.dataset.formieSubmitActionFormHide;if(t===void 0)return!1;const r=t.trim().toLowerCase();return r==="true"||r==="1"||r===""}function hf(e){const t=ar.get(e);typeof t=="number"&&(window.clearTimeout(t),ar.delete(e))}function yi(e){return e.querySelector("[data-formie-form-messages-top]")||e}function wi(e){return e.querySelector("[data-formie-form-messages-bottom]")||e}function pf(e,t){return t==="bottom-form"?wi(e):yi(e)}function gf(e,t){return t==="top-form"?yi(e):t==="bottom-form"&&!gn(e)?wi(e):e}function Ei(e){const t=ff(e),r=pf(e,t);let n=r.querySelector("[data-formie-error-container], [data-formie-errors]");return n||(n=document.createElement("div"),n.setAttribute("data-formie-errors","true"),ue(n,e,"errors")),n.setAttribute("data-formie-error-container","true"),t==="bottom-form"?r.append(n):r.prepend(n),n}function xi(e,t){let r=t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");return r||(r=document.createElement("div"),r.setAttribute("data-formie-error-message-container","true"),t.appendChild(r)),r.setAttribute("data-formie-message","true"),r.setAttribute("data-formie-message-error","true"),ue(r,e,"message","messageError"),r.setAttribute("role","alert"),an(r,Jo(on(e))),r}function vf(e,t){let r=e.querySelector("[data-formie-success-container]");const n=gf(e,t);return r||(r=document.createElement("div"),r.setAttribute("data-formie-success-container","true"),ue(r,e,"successes")),t==="bottom-form"?n.append(r):n.prepend(r),r}function bf(e){return di(e,t=>{ue(t,e,"fieldErrors")})}function yf(e,t){const r=(e.getAttribute("aria-describedby")||"").trim();if(!r)return;const n=r.split(/\s+/).filter(i=>i!==t).join(" ").trim();if(n){e.setAttribute("aria-describedby",n);return}e.removeAttribute("aria-describedby")}function wf(e,t){e.setAttribute("aria-errormessage",t)}function Ef(e,t){e.getAttribute("aria-errormessage")===t&&e.removeAttribute("aria-errormessage")}function ki(e){e.querySelectorAll("[data-formie-field-handle]").forEach(t=>{const r=t,n=r.querySelector("[data-formie-field-errors]"),i=(n==null?void 0:n.id)||"",a=Array.from(r.querySelectorAll("[data-formie-field-error]")).map(o=>o.id).filter(Boolean);gt(r,e,"fieldLayoutError"),r.removeAttribute("data-formie-field-has-error"),r.querySelectorAll("[data-formie-field-error]").forEach(o=>{o.remove()}),n&&!n.querySelector("[data-formie-field-error]")&&(n.innerHTML=""),r.querySelectorAll("input, select, textarea").forEach(o=>{const s=o;s.removeAttribute("aria-invalid"),gt(s,e,"fieldControlError"),s.removeAttribute("data-formie-input-has-error"),i&&yf(s,i),a.forEach(l=>{Ef(s,l)})})}),rt(e)}function _i(e){e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach(t=>{const r=t;r.querySelectorAll("[data-formie-error]").forEach(n=>{n.remove()}),gt(r,e,"message","messageError"),r.removeAttribute("data-formie-message"),r.removeAttribute("data-formie-message-error"),r.removeAttribute("role"),r.removeAttribute("aria-live"),r.removeAttribute("aria-atomic"),r.querySelector("[data-formie-error]")||(r.innerHTML="")})}function vn(e){hf(e),e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach(t=>{t.remove()}),e.querySelectorAll("[data-formie-success-container]").forEach(t=>{const r=t;r.querySelectorAll("[data-formie-success]").forEach(n=>{n.remove()}),gt(r,e,"message","messageSuccess"),r.removeAttribute("data-formie-message"),r.removeAttribute("data-formie-message-success"),r.removeAttribute("role"),r.removeAttribute("aria-live"),r.removeAttribute("aria-atomic"),r.querySelector("[data-formie-success]")||(r.innerHTML="")}),vi(e)==="message"&&gn(e)||hn(e,!1)}function Si(e){e.querySelectorAll('[aria-invalid="true"]').forEach(t=>{t.removeAttribute("aria-invalid")})}function Zn(e,t){const r=(e.getAttribute("aria-describedby")||"").trim(),n=r?r.split(/\s+/):[];n.includes(t)||n.push(t),e.setAttribute("aria-describedby",n.join(" ").trim())}function xf(e,t){const r=Jo(on(e));Object.entries(t).forEach(([n,i])=>{var c;const a=e.querySelector(`[data-formie-field-handle="${n}"]`);if(!a)return;const o=bf(a),s=o.id&&o.id.trim()?o.id:`${n}-errors`;o.id=s,an(o,r),ue(a,e,"fieldLayoutError"),a.setAttribute("data-formie-field-has-error","true"),i.forEach((f,m)=>{const d=document.createElement("div");d.setAttribute("data-formie-field-error","true"),d.setAttribute("role","alert"),d.id=`${s}-${m+1}`,ue(d,e,"fieldError"),d.textContent=f,o.appendChild(d)});const l=(c=o.querySelector("[data-formie-field-error]"))==null?void 0:c.id;a.querySelectorAll("input, select, textarea").forEach(f=>{const m=f;m.setAttribute("aria-invalid","true"),ue(m,e,"fieldControlError"),m.setAttribute("data-formie-input-has-error","true"),Zn(m,s),l&&wf(m,l);const d=a.querySelector("[data-formie-instructions]");d!=null&&d.id&&Zn(m,d.id)})}),rt(e)}function Xn(e,t){const r=Ei(e),n=xi(e,r);ue(r,e,"errors"),t.forEach(i=>{const a=document.createElement("div");a.setAttribute("data-formie-error","true"),a.setAttribute("role","alert"),ue(a,e,"error"),a.innerHTML=i,n.appendChild(a)})}function kf(e){if(e.ok||e.keepSubmitLoading!==!0)return!1;const t=e.meta||{},r=String(t.paymentStatus||"");return r==="actionRequired"||r==="pending"}function _f(e,t){const r=Ei(e),n=xi(e,r);ue(r,e,"errors");const i=document.createElement("div");i.setAttribute("data-formie-notice","true"),i.setAttribute("role","status"),ue(i,e,"message"),i.textContent=t,n.appendChild(i)}function Sf(e,t){return!t.message||t.nextPage||t.redirect?!1:t.action==="save"?!0:vi(e)==="message"&&bi(e)!==""}function Af(e,t){const r=bi(e);if(!r)return;const n=vf(e,r);ue(n,e,"message","messageSuccess"),n.setAttribute("data-formie-message","true"),n.setAttribute("data-formie-message-success","true"),n.setAttribute("role","status"),n.setAttribute("aria-live","polite"),n.setAttribute("aria-atomic","true");const i=document.createElement("div");i.setAttribute("data-formie-success","true"),ue(i,e,"success"),i.innerHTML=t,n.appendChild(i),gn(e)&&hn(e,!0);const a=mf(e);if(a!==null){const o=window.setTimeout(()=>{ar.delete(e),vn(e)},a);ar.set(e,o)}}function At(e,t){var r;if(ki(e),_i(e),vn(e),Si(e),t.ok){Sf(e,t)&&Af(e,t.message||"");return}if(!t.ok){if(kf(t)){const n=t.meta||{},i=String(n.paymentMessage||"").trim();i&&_f(e,i);return}t.fieldErrors&&xf(e,t.fieldErrors),(r=t.formErrors)!=null&&r.length?Xn(e,t.formErrors):!t.fieldErrors&&t.message&&Xn(e,[t.message]),ei(e)}}const Tf=Fe("general","submit-flow");function Cf(e){return!(!e.ok&&e.stage==="validate")}function Ai(e){var t;return e?!!(e.keepSubmitLoading===!0||e.ok&&((t=e.redirect)!=null&&t.url)&&e.redirect.target!=="new-tab"):!1}function Ti(e){ki(e),_i(e),vn(e),Si(e)}async function Ci(e){const{id:t,target:r,form:n,bus:i,validator:a,validateOnSubmit:o,action:s,submitter:l,waitForSubmitDelay:c,onRefreshTokensAfterSubmit:f,dispatchSubmitResult:m}=e;Ti(n),gi(n,l||null);let d={ok:!1,code:"SUBMIT_ERROR",message:"Submission failed.",formErrors:["Submission failed."]};try{await c(n),d=await ui(n,s,i,{validator:a,validateOnSubmit:o}),At(n,d),m(d),df(n,d,s),Cf(d)&&await f(d)}catch(u){d={ok:!1,code:"SUBMIT_ERROR",message:u instanceof Error?u.message:"Submission failed.",formErrors:[u instanceof Error?u.message:"Submission failed."]},At(n,d),m(d),Tf.warn("Submit failed with exception.",{id:t,action:s,target:r,error:u instanceof Error?u.message:u})}finally{Ai(d)||ir(n)}return d}class If{constructor(){this.modules=new Map}register(t,r={}){const n=this.modules.get(t.id);return n===t?!0:n&&!r.replace?(console.warn(`[formie] Module "${t.id}" is already registered. Pass { replace: true } to override the existing definition.`),!1):(this.modules.set(t.id,t),!0)}unregister(t){this.modules.delete(t)}get(t){return this.modules.get(t)||null}getAll(){return Array.from(this.modules.values())}}const Lf={"address-finder":()=>R(()=>import("./address-finder.B5bUt3aO.js"),__vite__mapDeps([0,1,2])).then(e=>e.addressFinderModule),"google-address":()=>R(()=>import("./google-address.BVZhSUJf.js"),__vite__mapDeps([3,1,2])).then(e=>e.googleAddressModule),loqate:()=>R(()=>import("./loqate.BC1LGCXi.js"),__vite__mapDeps([4,1,2])).then(e=>e.loqateModule),"place-kit":()=>R(()=>import("./place-kit.BGnTLDnK.js"),__vite__mapDeps([5,2,6])).then(e=>e.placeKitModule)},Mf={"captcha-eu":()=>R(()=>import("./captcha-eu.8szyj-vT.js"),__vite__mapDeps([7,1,2])).then(e=>e.captchaEuModule),"friendly-captcha-v1":()=>R(()=>import("./friendly-captcha-v1.B8Q5l8Mf.js"),__vite__mapDeps([8,2])).then(e=>e.friendlyCaptchaV1Module),"friendly-captcha-v2":()=>R(()=>import("./friendly-captcha-v2.CMtPPTWp.js"),__vite__mapDeps([9,2])).then(e=>e.friendlyCaptchaV2Module),hcaptcha:()=>R(()=>import("./hcaptcha.y0u1q83c.js"),__vite__mapDeps([10,1,2])).then(e=>e.hcaptchaModule),"recaptcha-enterprise":()=>R(()=>import("./recaptcha-enterprise.CWmOH9mm.js"),__vite__mapDeps([11,12,1,2])).then(e=>e.recaptchaEnterpriseModule),"recaptcha-v2-checkbox":()=>R(()=>import("./recaptcha-v2-checkbox.CADKbpp9.js"),__vite__mapDeps([13,12,1,2])).then(e=>e.recaptchaV2CheckboxModule),"recaptcha-v2-invisible":()=>R(()=>import("./recaptcha-v2-invisible.Dwmpr01D.js"),__vite__mapDeps([14,12,1,2])).then(e=>e.recaptchaV2InvisibleModule),"recaptcha-v3":()=>R(()=>import("./recaptcha-v3.C8805hME.js"),__vite__mapDeps([15,12,1,2])).then(e=>e.recaptchaV3Module),snaptcha:()=>R(()=>import("./snaptcha.qFBRWVcH.js"),__vite__mapDeps([16,2])).then(e=>e.snaptchaModule),turnstile:()=>R(()=>import("./turnstile.Bf76WNob.js"),__vite__mapDeps([17,1,2])).then(e=>e.turnstileModule)},Rf={calculations:()=>R(()=>import("./calculations.DE37xsO1.js"),__vite__mapDeps([18,19,20,2])).then(e=>e.calculationsModule),"checkbox-radio":()=>R(()=>import("./checkbox-radio.W7XOFqlh.js"),__vite__mapDeps([21,20,2])).then(e=>e.checkboxRadioModule),combobox:()=>R(()=>import("./combobox.D7BjMnri.js"),__vite__mapDeps([22,20,6,2])).then(e=>e.comboboxModule),conditions:()=>R(()=>import("./conditions.DHVREdxr.js"),__vite__mapDeps([23,19,20,2])).then(e=>e.conditionsModule),"custom-google-maps":()=>R(()=>import("./custom-google-maps.C-npGY3u.js"),__vite__mapDeps([24,20,2])).then(e=>e.customGoogleMapsModule),"custom-link":()=>R(()=>import("./custom-link.D0uCHdjJ.js"),__vite__mapDeps([25,20,2])).then(e=>e.customLinkModule),"custom-maps":()=>R(()=>import("./custom-maps.7Fs0vxP-.js"),__vite__mapDeps([26,2,20,6])).then(e=>e.customMapsModule),"date-picker":()=>R(()=>import("./date-picker.3LyJObcl.js"),__vite__mapDeps([27,20,6,2])).then(e=>e.datePickerModule),"file-upload":()=>R(()=>import("./file-upload.T_isckxz.js"),__vite__mapDeps([28,20,6,2])).then(e=>e.fileUploadModule),"upload-manager":()=>R(()=>import("./upload-manager.DjnXShtD.js"),__vite__mapDeps([29,20,6,2])).then(e=>e.uploadManagerModule),hidden:()=>R(()=>import("./hidden.Dl7Lj9Jo.js"),__vite__mapDeps([30,20,2])).then(e=>e.hiddenModule),"phone-country":()=>R(()=>import("./phone-country.BGNPlKnJ.js"),__vite__mapDeps([31,2,20,6,32])).then(e=>e.phoneCountryModule),"password-validation":()=>R(()=>import("./password-validation.Dd8aYvt5.js"),__vite__mapDeps([33,19,20,2])).then(e=>e.passwordValidationModule),"address-country":()=>R(()=>import("./address-country.DAgvLoqf.js"),__vite__mapDeps([34,20,32,2])).then(e=>e.addressCountryModule),"address-state":()=>R(()=>import("./address-state.CV3rc95O.js"),__vite__mapDeps([35,22,20,6,2])).then(e=>e.addressStateModule),repeater:()=>R(()=>import("./repeater.DRxUbTIc.js"),__vite__mapDeps([36,20,6,2])).then(e=>e.repeaterModule),"rich-text":()=>R(()=>import("./rich-text.BG3CipRf.js"),__vite__mapDeps([37,20,6,2])).then(e=>e.richTextModule),signature:()=>R(()=>import("./signature.CXBgTLR1.js"),__vite__mapDeps([38,20,6,2])).then(e=>e.signatureModule),summary:()=>R(()=>import("./summary.82Fnn6Kq.js"),__vite__mapDeps([39,20,6,2])).then(e=>e.summaryModule),"survey-likert":()=>R(()=>import("./survey-likert.DnUP7Hdi.js"),__vite__mapDeps([40,41,6,2])).then(e=>e.surveyLikertModule),"survey-rank":()=>R(()=>import("./survey-rank.B80GTiom.js"),__vite__mapDeps([42,41,20,6,2])).then(e=>e.surveyRankModule),"survey-rating":()=>R(()=>import("./survey-rating.Dv5X0l_o.js"),__vite__mapDeps([43,41,20,6,2])).then(e=>e.surveyRatingModule),table:()=>R(()=>import("./table.AFBmKb7s.js"),__vite__mapDeps([44,20,6,2])).then(e=>e.tableModule),"text-limit":()=>R(()=>import("./text-limit.Db1oqz8C.js"),__vite__mapDeps([45,19,20,6,2])).then(e=>e.textLimitModule)},Ff={bpoint:()=>R(()=>import("./bpoint.B_1mg6xW.js"),__vite__mapDeps([46,2])).then(e=>e.bpointModule),eway:()=>R(()=>import("./eway.B_P7Uw9b.js"),__vite__mapDeps([47,1,2])).then(e=>e.ewayModule),"go-cardless":()=>R(()=>import("./go-cardless.BxVXKsjg.js"),__vite__mapDeps([48,2])).then(e=>e.goCardlessModule),mollie:()=>R(()=>import("./mollie.iBpWWbCB.js"),__vite__mapDeps([49,2])).then(e=>e.mollieModule),moneris:()=>R(()=>import("./moneris.CsPYGhkr.js"),__vite__mapDeps([50,2])).then(e=>e.monerisModule),opayo:()=>R(()=>import("./opayo.C5AkDI4M.js"),__vite__mapDeps([51,6,1,2])).then(e=>e.opayoModule),paddle:()=>R(()=>import("./paddle._7-DXh4E.js"),__vite__mapDeps([52,1,2])).then(e=>e.paddleModule),paypal:()=>R(()=>import("./paypal.BvP53fz2.js"),__vite__mapDeps([53,6,1,2])).then(e=>e.paypalModule),payway:()=>R(()=>import("./payway.D6Ltb7K8.js"),__vite__mapDeps([54,6,1,2])).then(e=>e.paywayModule),square:()=>R(()=>import("./square.Br3l0p14.js"),__vite__mapDeps([55,1,2])).then(e=>e.squareModule),stripe:()=>R(()=>import("./stripe.CxAo1CFD.js"),__vite__mapDeps([56,6,1,2])).then(e=>e.stripeModule)},Of={...Rf,...Lf,...Mf,...Ff},Mr=new Map,Le=Fe("general","loader"),Pf=new Function("src","return import(src);");async function Bt(e,t,r,n){await e(Mu(r),n),await e(Lu(t,r),n)}function Ii(e){return!!e&&typeof e=="object"&&typeof e.id=="string"&&typeof e.setup=="function"&&typeof e.match=="function"}async function Nf(e,t){const r=Of[e];return r?(Mr.has(e)||Mr.set(e,(async()=>{try{const n=await r();return Ii(n)?(t.registry.register(n),n):null}catch(n){return console.error("[formie] Failed to load builtin module:",e,n),Le.warn("Failed loading builtin module.",{moduleId:e,error:n}),null}})()),Mr.get(e)||null):null}async function Df(e){try{const t=await Pf(e),r=(t==null?void 0:t.default)||(t==null?void 0:t.formieModule)||null;return Ii(r)?r:null}catch(t){return console.error("[formie] Failed to load module from src:",e,t),Le.warn("Failed loading module from src.",{src:e,error:t}),null}}async function $f(e,t){const r=t.registry.get(e.id);if(r)return r;const n=await Nf(e.id,t);if(n)return n;if(e.src){const i=await Df(e.src);if(i)return t.registry.register(i),i}return null}function Rr(e){var t;return typeof((t=window.CSS)==null?void 0:t.escape)=="function"?window.CSS.escape(e):e.replace(/["\\]/g,"\\$&")}function jt(e,t){return e.matches(t)?[e,...Array.from(e.querySelectorAll(t))]:Array.from(e.querySelectorAll(t))}function zf(e,t){const r=t.setupContext.root,n=t.setupContext.form,i=e.targetType,a=e.targetId;return i==="selector"?jt(r,a).map(o=>({scope:i,element:o})):i==="field"?jt(r,`[data-formie-field-handle="${Rr(a)}"]`).map(o=>({scope:i,element:o})):i==="page"?jt(r,`[data-formie-page-id="${Rr(a)}"]`).map(o=>({scope:i,element:o})):i==="button"?jt(r,`[data-formie-action="${Rr(a)}"]`).map(o=>({scope:i,element:o})):[{scope:"form",element:n||r}]}function Vf(e,t){return(e.targets&&e.targets.length>0?e.targets:[{targetType:"form",targetId:"form"}]).flatMap(n=>zf(n,t))}async function Hf(e,t){var n,i;const r=[];Le.log("Loading module manifest.",{manifestCount:e.length});for(const a of e){const o=await $f(a,t);if(!o){Le.warn("Skipping manifest item (definition not resolved).",{moduleId:a.id,src:a.src});continue}const s=Vf(a,t);Le.log("Resolved module targets.",{moduleId:o.id,targets:a.targets||[],targetCount:s.length}),s.length===0&&o.kind==="address"&&console.warn(`[formie] Address module "${a.id}" skipped: no target element found for fieldHandle="${((i=(n=a.targets)==null?void 0:n.find(l=>l.targetType==="field"))==null?void 0:i.targetId)??"?"}". Check that the Address field exists in the rendered form.`);for(const l of s){const c={...t.matchContext,target:l.element,scope:l.scope,manifestItem:a};if(!o.match(c)){o.kind==="address"&&console.warn(`[formie] Address module "${o.id}" skipped: target element does not contain [data-formie-address-autocomplete-input]. Enable the Auto-Complete subfield.`),Le.log("Module target did not match predicate.",{moduleId:o.id,scope:l.scope});continue}const f=a.config||t.setupContext.options,m=o.id,d={moduleId:o.id,moduleKind:o.kind,target:l.element,scope:l.scope,options:f,manifestItem:a};await Bt(t.setupContext.emit,m,"before-setup",d);let u=null;try{const g=await o.setup({...t.setupContext,target:l.element,scope:l.scope,options:f});g&&(u=g)}catch(g){console.error(`[formie] Module "${o.id}" setup failed:`,g),Le.warn("Module setup failed.",{moduleId:o.id,scope:l.scope,error:g})}await Bt(t.setupContext.emit,m,"after-setup",{...d,instanceCreated:!!u}),u&&(Le.log("Module instance created.",{moduleId:o.id,scope:l.scope}),r.push({...u,destroy:async()=>{Le.log("Destroying module instance.",{moduleId:o.id,scope:l.scope}),await Bt(t.setupContext.emit,m,"before-destroy",d),await u.destroy(),await Bt(t.setupContext.emit,m,"after-destroy",d),Le.log("Module instance destroyed.",{moduleId:o.id,scope:l.scope})}}))}}return Le.log("Module manifest processing complete.",{instanceCount:r.length}),r}const qf="formie:formStartedAt:";function Bf(e){var o;const t=e.querySelector('input[name="formStartedAt"]');if(!t)return;const r=e.querySelector('input[name="renderId"]'),n=((o=r==null?void 0:r.value)==null?void 0:o.trim())??"",i=n?`${qf}${n}`:null;let a=i?sessionStorage.getItem(i):null;a||(a=String(Date.now()),i&&sessionStorage.setItem(i,a)),t.value=a}const jf=new Set(["action","redirect","requestToken","renderId","formStartedAt","submitAction","pageId","draftContextToken","draftContext","continuationToken"]);function Wr(e,t){if(e==null)return String(e);if(typeof e=="string")return JSON.stringify(e);if(typeof e=="number"||typeof e=="boolean")return String(e);if(typeof e=="function")return"[function]";if(typeof File<"u"&&e instanceof File)return`[file:${e.name}:${e.size}:${e.type}]`;if(typeof Blob<"u"&&e instanceof Blob)return`[blob:${e.size}:${e.type}]`;if(Array.isArray(e))return`[${e.map(r=>Wr(r,t)).join(",")}]`;if(typeof e=="object"){if(t.has(e))return"[circular]";t.add(e);const r=Object.entries(e).sort(([n],[i])=>n.localeCompare(i)).map(([n,i])=>`${JSON.stringify(n)}:${Wr(i,t)}`);return t.delete(e),`{${r.join(",")}}`}return JSON.stringify(String(e))}function Uf(e){return Wr(e,new WeakSet)}function Wf(e,t){if(!e)return!1;const r=e.endsWith("[]")?e.slice(0,-2):e;return cd(r,t)?!1:!jf.has(r)}function eo(e){const t=Array.from(new FormData(e).entries()).filter(([r])=>Wf(String(r||""),e));return Uf(t)}function Kf(e,t={}){let r=null,n=!1,i=!1,a=null,o=null,s=null;const l=()=>{a!==null&&(window.cancelAnimationFrame(a),a=null),o!==null&&(window.clearTimeout(o),o=null),s!==null&&(window.clearTimeout(s),s=null)},c=()=>n?(i=eo(e)!==r,i):!1,f=()=>{r=eo(e),n=!0,i=!1},m=()=>{l(),n=!1,a=window.requestAnimationFrame(()=>{a=null,s=window.setTimeout(()=>{s=null,f()},0)})},d=()=>{o!==null&&window.clearTimeout(o),o=window.setTimeout(()=>{o=null,c()},120)},u=g=>{t.shouldWarn&&!t.shouldWarn()||c()&&(g.preventDefault(),g.returnValue="")};return e.addEventListener("input",d),e.addEventListener("change",d),window.addEventListener("beforeunload",u),m(),{captureBaseline:f,scheduleBaselineCapture:m,refreshDirtyState:c,destroy:()=>{l(),e.removeEventListener("input",d),e.removeEventListener("change",d),window.removeEventListener("beforeunload",u)}}}function Jf(e){return e.hasAttribute("data-formie-conditionally-hidden")||!!e.closest("[data-formie-conditionally-hidden]")||e.hasAttribute("data-formie-page-hidden")||!!e.closest("[data-formie-page-hidden]")}function Gf(e,t){const r=e.querySelectorAll(`[data-formie-action="${t}"]`);return Array.from(r).some(n=>!Jf(n))}function Yf(e){const{final:t}=mr(e);return"submit"}function Qf(e){const t=Yf(e);return!Gf(e,t)}function Zf(e){const t=r=>{if(r.key!=="Enter"||r.defaultPrevented)return;const n=r.target;(n instanceof HTMLInputElement||n instanceof HTMLSelectElement)&&(n instanceof HTMLInputElement&&(n.type==="button"||n.type==="submit"||n.type==="reset"||n.type==="file")||Qf(e)&&r.preventDefault())};return e.addEventListener("keydown",t,!0),()=>{e.removeEventListener("keydown",t,!0)}}const at='[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])',Xf=300,em="/actions/formie/server/forms/render",to="/api",tm="/actions/formie/server/forms/refresh-tokens",rm="/actions/formie/server/submissions/submit",nm="/actions/formie/server/submissions/set-page",om="/actions/formie/server/submissions/clear-submission",im="/actions/formie/file-upload/hydrate",oe=Fe("general","client"),ro=new Set;function Mt(e,t){if(e==null||e==="")return t;const r=e.toLowerCase();return!(r==="false"||r==="0"||r==="off")}function Kr(e){return e.formieRefreshTokens!=null&&e.formieRefreshTokens!==""?Mt(e.formieRefreshTokens,!1):e.formieStaticCache!=null&&e.formieStaticCache!==""?Mt(e.formieStaticCache,!1):!1}function st(e){const t=e instanceof HTMLElement?e.dataset:{};return{mode:"server-rendered",transport:t.formieTransport||"rest",formHandle:t.formieHandle,endpoint:t.formieEndpoint,staticCache:Kr(t),autoVisible:Mt(t.formieAutoVisible,!0),compatibility:Mt(t.formieCompatibility,!1)}}function hr(e){return e||"server-rendered"}function pr(e){return e||"rest"}function Kt(e){return e instanceof HTMLFormElement?e:e.querySelector("form")}function am(e,t){ro.has(e)||(ro.add(e),oe.warn(t))}function Li(e,t){if(!e)return e;try{return new URL(e).toString()}catch{}if(!t)return e;try{return new URL(e,t).toString()}catch{return e}}function ht(e,t){const r=(e||"").trim();return r?r.includes("/actions/")?r:Li(t,r):t}function sm(e,t){return ht(e.endpoint||t.dataset.formieEndpoint,em)}function lm(e,t){const r=(e.endpoint||t.dataset.formieEndpoint||"").trim();return r?r.includes("/graphql")||r.endsWith("/api")||r.includes("/actions/graphql/")?r:Li(to,r):to}function bn(e,t){return ht(t.dataset.formieRefreshTokensEndpoint||e.endpoint||t.dataset.formieEndpoint,tm)}function no(e,t){if(!e)return t;try{const r=new URL(e,window.location.origin),n=new URL(t,window.location.origin);return r.searchParams.forEach((i,a)=>{n.searchParams.has(a)||n.searchParams.set(a,i)}),n.toString()}catch{return t}}function cm(e,t,r){const n=r.endpoint||e.dataset.formieEndpoint,i=ht(n,rm),a=t.getAttribute("action");t.setAttribute("action",no(a,i)),t.querySelectorAll("[data-formie-tab-link]").forEach(o=>{const s=o.getAttribute("href"),l=ht(n,nm);o.setAttribute("href",no(s,l))}),t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach(o=>{o.setAttribute("data-formie-file-upload-hydrate-endpoint",ht(n,im))})}function yn(e,t){if(e==="graphql"&&t!=="server-rendered")throw new Error(`Formie ${t} mode does not support GraphQL transport yet.`)}function wn(e){if(e==null)return!1;const t=e.trim().toLowerCase();return t==="true"||t==="1"||t===""}function um(e){return Mt(e.dataset.formieAutomaticSubmissionState,!0)}function dm(e,t,r){return ht(r.dataset.formieClearSubmissionEndpoint||e.endpoint||t.dataset.formieEndpoint,om)}function fm(e){return wn(e.dataset.formieUnloadWarning)}function oo(e,t){e.setAttribute("data-formie-internal-navigation",t)}function Fr(e){e.removeAttribute("data-formie-internal-navigation")}function io(e){return e.getAttribute("data-formie-internal-navigation")!==null}function ao(e,t){if(!e)return!1;try{return new URL(e,window.location.origin).searchParams.has(t)}catch{return!1}}function mm(e){return ao(window.location.href,"resumeToken")||ao(e.getAttribute("action"),"resumeToken")}function hm(e){return e instanceof MouseEvent?e.button===0&&!e.metaKey&&!e.ctrlKey&&!e.shiftKey&&!e.altKey:!0}function pm(e,t=0){if(!e)return t;const r=Number.parseInt(e,10);return Number.isFinite(r)?r:t}function gm(e){return Math.max(0,pm(e.dataset.formieSubmitDelay,Xf))}function Jt(e){return wn(e.dataset.formieValidationOnSubmit)}async function Jr(e){const t=gm(e);t<1||await new Promise(r=>{window.setTimeout(r,t)})}function so(e,t){var n;const r=(n=e==null?void 0:e.getAttribute(t))==null?void 0:n.trim();if(!r)return null;try{return JSON.parse(r)}catch(i){return console.error(`[formie] Failed to parse ${t}.`,i),null}}function lo(e,t){const r=t||(e instanceof HTMLFormElement?e:null);if(!r)return null;const n=so(r,"data-formie-modules"),i=so(r,"data-formie-theme");return!n&&!i?null:{modules:n||void 0,theme:i||void 0}}function vm(e){if(!(e instanceof HTMLElement))return!0;if(!e.isConnected||e.hidden||e.closest("[hidden]"))return!1;const t=window.getComputedStyle(e);return t.display==="none"||t.visibility==="hidden"?!1:e.getClientRects().length>0}function bm(e,t){return t===document?!0:t instanceof Element?t===e||t.contains(e):!0}function me(e){var a;const t=e,r=t.id?`#${t.id}`:"",n=(a=t.dataset)!=null&&a.formieHandle?`[handle="${t.dataset.formieHandle}"]`:"";return`${t.tagName?t.tagName.toLowerCase():"element"}${r}${n}`}function En(e,t){var r,n;if(t){if((r=t.csrf)!=null&&r.param&&((n=t.csrf)!=null&&n.token)){let i=e.querySelector(`input[name="${t.csrf.param}"]`);i?i.value=t.csrf.token:(i=document.createElement("input"),i.type="hidden",i.name=t.csrf.param,i.value=t.csrf.token,i.setAttribute("autocomplete","off"),i.setAttribute("data-formie-csrf",""),e.prepend(i))}if(t.requestToken){const i=e.querySelector('input[name="requestToken"]');i&&(i.value=t.requestToken)}if(t.renderId){const i=e.querySelector('input[name="renderId"]');i&&(i.value=t.renderId)}t.captchas&&typeof t.captchas=="object"&&Object.values(t.captchas).forEach(i=>{if(!i||typeof i!="object")return;const a=i;if(!a.sessionKey)return;const o=e.querySelector(`input[name="${a.sessionKey}"]`);o&&typeof a.value=="string"&&(o.value=a.value)})}}async function ym(e,t){const r=hr(t.mode),n=pr(t.transport);if(r!=="server-rendered")return null;if(t.payload)return t.payload.html&&(e.innerHTML=t.payload.html),t.payload;yn(n,r);const i=!!Kt(e),a=t.formHandle||e.dataset.formieHandle;if(i||!a)return null;const o={mode:r,endpoint:t.endpoint,locale:t.locale,siteId:t.siteId,theme:t.theme,themeConfig:t.themeConfig},s=n==="graphql"?lm(t,e):sm(t,e),l=n==="graphql"?await md(s,a,o):await fd(s,a,{...o,endpoint:s});return l!=null&&l.html&&(e.innerHTML=l.html),l}async function Mi(e,t,r){if(t.refreshTokens===!1)return;yn(pr(t.transport),hr(t.mode));const n=t.formHandle||e.dataset.formieHandle;if(!n)return;const i=bn(t,e),a=r.querySelector('input[name="renderId"]'),o=(a==null?void 0:a.value)||void 0,s=await dn(i,n,o);En(r,s),ie(e,"formie:refresh-tokens:refreshed",s)}function wm(e,t,r,n,i,a){const o=String(t.dataset.formieSubmitMethod||"").trim().toLowerCase(),s=dm(r,e,t);let l=!1;const c=t.querySelectorAll("[data-formie-action]"),f=u=>{if(u){t.setAttribute("data-formie-pending-action",u);return}t.removeAttribute("data-formie-pending-action")};if(fm(t)){const u=Kf(t,{shouldWarn:()=>!io(t)}),g=h=>{if(!(h instanceof CustomEvent))return;const p=h.detail;p!=null&&p.ok&&p.action==="save"&&u.scheduleBaselineCapture()},E=()=>{u.scheduleBaselineCapture()};e.addEventListener("formie:submit:result",g),t.addEventListener("formie:state:reset",E),a.push(()=>{e.removeEventListener("formie:submit:result",g),t.removeEventListener("formie:state:reset",E),u.destroy()})}if(c.forEach(u=>{const g=E=>{const h=E.currentTarget.getAttribute("data-formie-action"),p=t.querySelector('input[name="submitAction"]');f(h),h&&p&&(p.value=h)};u.addEventListener("click",g),a.push(()=>{u.removeEventListener("click",g)})}),t.querySelectorAll("[data-formie-tab-link]").forEach(u=>{const g=async E=>{if(o!=="ajax"){hm(E)&&oo(t,"set-page");return}E.preventDefault();const h=E.currentTarget,p=h==null?void 0:h.getAttribute("data-formie-page-id"),v=h==null?void 0:h.getAttribute("href");if(!(!p||!v)){pn(t,p),ie(e,"formie:page:navigate",{pageId:p,href:v});try{const y=await hd(v,t,p);ie(e,"formie:page:navigate:after",{pageId:p,href:v,response:y})}catch(y){console.error("[formie] Failed to persist page navigation state.",y),ie(e,"formie:page:navigate:error",{pageId:p,href:v,error:y})}}};u.addEventListener("click",g),a.push(()=>{u.removeEventListener("click",g)})}),!um(t)){let u=!1;const g=()=>{u||io(t)||mm(t)||(u=!0,pd(s,t))};window.addEventListener("pagehide",g),window.addEventListener("beforeunload",g),a.push(()=>{window.removeEventListener("pagehide",g),window.removeEventListener("beforeunload",g)})}const d=async u=>{if(l)return;const g=o==="ajax";if(u.preventDefault(),t.getAttribute("data-formie-loading")==="true"){if(!(t.getAttribute("data-formie-internal-resubmit")==="true"))return;t.removeAttribute("data-formie-internal-resubmit")}else t.removeAttribute("data-formie-internal-resubmit");const h=u.submitter,p=h==null?void 0:h.getAttribute("data-formie-action"),v=t.getAttribute("data-formie-pending-action"),y=t.querySelector('input[name="submitAction"]'),b=p||v||(y==null?void 0:y.value)||"submit";let k=null,I=!1;try{if(g)k=await Ci({target:e,form:t,bus:n,validator:i,validateOnSubmit:Jt(t),action:b,submitter:h,waitForSubmitDelay:Jr,onRefreshTokensAfterSubmit:async()=>{await Mi(e,r,t)},dispatchSubmitResult:F=>{ie(e,"formie:submit:result",F)}});else{if(Ti(t),gi(t,h),await Jr(t),k=await ui(t,b,n,{validator:i,validateOnSubmit:Jt(t),preflightOnly:!0}),k.ok){Qo(t,b),l=!0,oo(t,"submit"),f(null);let F=!1;const V=()=>{if(F=!0,l=!1,Fr(t),ir(t),i&&Jt(t)){const{scope:H,final:Q}=mr(t),C=i.submit(Q?t:H,{final:Q});C.length>0&&At(t,{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:i.config.errorMessage||"Validation failed.",fieldErrors:i.getFieldErrors(C),formErrors:[i.config.errorMessage||"Validation failed."]})}};if(typeof t.requestSubmit=="function"){t.addEventListener("invalid",V,!0);try{t.requestSubmit()}finally{t.removeEventListener("invalid",V,!0)}}else t.submit();if(F)return;I=!0;return}At(t,k),ie(e,"formie:submit:result",k),Fr(t)}}catch(F){l=!1,k={ok:!1,code:"SUBMIT_ERROR",message:F instanceof Error?F.message:"Submission failed.",formErrors:[F instanceof Error?F.message:"Submission failed."]},At(t,k),ie(e,"formie:submit:result",k),Fr(t)}finally{f(null),!g&&!I&&!Ai(k)&&ir(t)}};t.addEventListener("submit",d),a.push(()=>{t.removeEventListener("submit",d)})}async function Em(e,t,r){if(t.refreshTokens===!1||!t.staticCache)return;yn(pr(t.transport),hr(t.mode));const n=t.formHandle||e.dataset.formieHandle,i=bn(t,e),a=r==null?void 0:r.querySelector('input[name="renderId"]'),o=(a==null?void 0:a.value)||void 0;if(!n)return;const s=await dn(i,n,o);!s||!r||(En(r,s),ie(e,"formie:refresh-tokens:after",s))}function xm(){const e=new Map,t=new If,r=new Map,n=new Map,i=["prepare","normalize","validate","screen","authorize","dispatch","finalize"],a=async h=>{const p=n.get(h);if(p){await p;return}const v=(async()=>{var k;oe.log("Unmount requested.",{target:me(h)});const y=r.get(h);y&&(y(),r.delete(h));const b=e.get(h);if(!b){oe.log("Unmount skipped (no mounted state).",{target:me(h)});return}ie(h,"formie:unmount:before",{id:b.instance.id}),b.unbinds.forEach(I=>{I()}),b.unbinds=[],(k=b.validator)==null||k.destroy(),b.validator=null;for(const I of b.modules)await I.destroy();b.modules=[],b.bus.clear(),e.delete(h),ie(h,"formie:unmount:after",{id:b.instance.id}),oe.log("Unmount complete.",{id:b.instance.id,target:me(h)})})().finally(()=>{n.delete(h)});n.set(h,v),await v},o=async(h,p)=>{oe.log("Mount requested.",{target:me(h),mode:p.mode,autoVisible:p.autoVisible});const v=r.get(h);v&&(v(),r.delete(h));const y=e.get(h);if(y)return oe.log("Mount skipped (already mounted).",{id:y.instance.id,target:me(h)}),y.instance;const b=new id,k=[],I=(h==null?void 0:h.id)||`formie-${e.size+1}`,F=st(h),V={...F,...p,mode:hr(p.mode??F.mode),transport:pr(p.transport??F.transport)},H=Iu(V.compatibility);if(V.mode!=="server-rendered"&&!Kt(h))throw new Error(`Formie ${V.mode} mode is not implemented yet in the browser client.`);const Q=await ym(h,V),C=Kt(h);V.staticCache=p.staticCache??Kr(C?C.dataset:h.dataset);const j=lo(h,C),N=Q||j?{...Q||{},...j||{}}:null,w=N==null?void 0:N.theme,A={},P=((N==null?void 0:N.modules)||[]).filter($=>!!($!=null&&$.id)&&!!($!=null&&$.type));oe.log("Resolved mount payload.",{target:me(h),hasRenderPayload:!!Q,hasEmbeddedPayload:!!j,moduleCount:P.length});const W=Un(h,w,C),z=C?new jd(C,{live:wn(C.dataset.formieValidationOnFocus),errorAriaLive:on(C),errorMessage:C.dataset.formieErrorMessage||"",fieldContainerErrorClass:W.fieldLayoutError||[],inputErrorClass:W.fieldControlError||[],messagesClass:W.fieldErrors||[],messageClass:W.fieldError||[]}):null;if(C&&z){const $=C;$.formieValidation=z,A.validation=z;const X={validator:z,addValidator:z.addValidator.bind(z),removeValidator:z.removeValidator.bind(z)};ie(C,"formie:validator:ready",X),ie(h,"formie:validator:ready",X)}C&&(Bf(C),V.themeConfig&&typeof V.themeConfig=="object"&&C.setAttribute("data-formie-theme-config",JSON.stringify(V.themeConfig)),V.theme&&V.theme!=="formie"&&C.setAttribute("data-formie-frontend-theme",V.theme),(Q||V.endpoint||h.dataset.formieEndpoint)&&cm(h,C,V),V.mode==="server-rendered"&&od(C)&&(nd(C),ei(C)),rt(C)),Object.keys(W).length&&ie(h,"formie:theme:applied",{hasClasses:!0});const Y=await Hf(P,{registry:t,matchContext:{root:h,form:C,mode:V.mode},setupContext:{formId:I,root:h,form:C,target:h,scope:"form",state:A,on:($,X)=>b.on($,X),emit:($,X)=>(ie(h,$,X),b.emitSafe($,X).then(ne=>{ne.failed.length>0&&oe.warn("Lifecycle listeners failed.",{eventName:$,failed:ne.failed.length})}))}});oe.log("Module setup complete.",{target:me(h),moduleInstances:Y.length});const U={id:I,root:h,submit:async($="submit")=>{if(oe.log("Submit requested.",{id:I,target:me(h),action:$}),!C)return{ok:!1,code:"FORM_NOT_FOUND",message:"No form element found for mount target.",formErrors:["No form element found for mount target."]};const X=C.querySelector('input[name="submitAction"]');if(X&&(X.value=$),C.getAttribute("data-formie-loading")==="true")return{ok:!1,code:"SUBMIT_IN_PROGRESS",message:"Submission already in progress.",formErrors:[]};const ne=C.querySelector(`[data-formie-action="${$}"]`),ee=await Ci({id:I,target:h,form:C,bus:b,validator:z,validateOnSubmit:Jt(C),action:$,submitter:ne,waitForSubmitDelay:Jr,onRefreshTokensAfterSubmit:async()=>{await Mi(h,V,C)},dispatchSubmitResult:se=>{ie(h,"formie:submit:result",se)}});return oe.log("Submit completed.",{id:I,action:$,ok:ee.ok,code:ee.code,message:ee.message}),ee},destroy:async()=>{await a(h)},on:($,X)=>b.on($,X)};C&&(Du({target:h,form:C,validatorDetail:z?{validator:z,addValidator:z.addValidator.bind(z),removeValidator:z.removeValidator.bind(z)}:null,options:H,unbinds:k}),Nu({target:h,form:C,instance:U,options:H,unbinds:k})),C&&(wm(h,C,V,b,z,k),z&&(k.push(Jd(C,z,h)),k.push(Zf(C))),await Em(h,V,C),C.dispatchEvent(new CustomEvent("formie:state:reset")),window.setTimeout(()=>{C.dispatchEvent(new CustomEvent("formie:state:reset"))},350)),i.forEach($=>{const X=b.on(`formie:stage:${$}:before`,async de=>{ie(h,`formie:stage:${$}:before`,de)}),ne=b.on(`formie:stage:${$}:before`,async de=>{for(const we of Y)we.onBeforeStage&&await we.onBeforeStage(de)}),ee=b.on(`formie:stage:${$}:after`,async de=>{ie(h,`formie:stage:${$}:after`,de)}),se=b.on(`formie:stage:${$}:after`,async de=>{const we=de;for(const Ie of Y)Ie.onAfterStage&&await Ie.onAfterStage(we,we.result)});k.push(X,ne,ee,se)});const L=b.on("formie:submit:before",async $=>{ie(h,"formie:submit:before",$)}),T=b.on("formie:submit:after",async $=>{ie(h,"formie:submit:after",$)}),G=b.on("formie:submit:final:before",async $=>{ie(h,"formie:submit:final:before",$)}),te=b.on("formie:submit:final:after",async $=>{ie(h,"formie:submit:final:after",$)});return k.push(L,T,G,te),e.set(h,{options:V,bus:b,form:C,validator:z,modules:Y,unbinds:k,instance:U}),ie(h,"formie:mount:after",{id:I,mode:V.mode}),C instanceof HTMLFormElement&&Ju(C),oe.log("Mount complete.",{id:I,target:me(h),mode:V.mode}),U},s=(h,p)=>{var y;if(!p.autoVisible||vm(h)||typeof IntersectionObserver>"u")return o(h,p);if(e.has(h))return Promise.resolve(((y=e.get(h))==null?void 0:y.instance)||null);if(r.has(h))return oe.log("Mount deferred (already waiting visibility).",{target:me(h)}),Promise.resolve(null);const v=new IntersectionObserver(b=>{b.some(I=>I.target===h&&I.isIntersecting)&&(v.disconnect(),r.delete(h),oe.log("Visibility reached, proceeding mount.",{target:me(h)}),o(h,{...p,autoVisible:!1}))},{threshold:.01});return v.observe(h),r.set(h,()=>{v.disconnect()}),oe.log("Mount deferred until visible.",{target:me(h)}),Promise.resolve(null)};return{mount:o,unmount:a,update:async(h,p)=>{var k,I,F;const v=e.get(h);if(!v)return o(h,{...st(h),...p,mode:p.mode||"server-rendered"});v.options={...v.options,...p};const y=((k=p.payload)==null?void 0:k.theme)||((I=v.options.payload)==null?void 0:I.theme)||((F=lo(h,v.form))==null?void 0:F.theme),b=Un(h,y,v.form);return v.validator&&(v.validator.config.fieldContainerErrorClass=b.fieldLayoutError||[],v.validator.config.inputErrorClass=b.fieldControlError||[],v.validator.config.messagesClass=b.fieldErrors||[],v.validator.config.messageClass=b.fieldError||[]),Object.keys(b).length&&ie(h,"formie:theme:applied",{hasClasses:!0,reason:"update"}),v.instance},getInstance:h=>{var p;return((p=e.get(h))==null?void 0:p.instance)||null},refreshForCache:async h=>{am("refreshForCache","Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");let p=null;if(typeof h=="string"){const Q=document.getElementById(h);Q?p=Q:p=document.querySelector(`[data-formie-form-id="${h}"]`)}else p=h;if(!p){oe.warn("refreshForCache target not found.",{targetOrId:h});return}const v=e.get(p),y=Kt(p),b=(v==null?void 0:v.options)||st(p);if(!y){oe.warn("refreshForCache found no form element for target.",{target:me(p)});return}const k=b.formHandle||p.dataset.formieHandle||y.dataset.formieHandle,I=bn(b,p),F=y.querySelector('input[name="renderId"]'),V=(F==null?void 0:F.value)||void 0;if(!k){oe.warn("refreshForCache found no form handle for target.",{target:me(p)});return}const H=await dn(I,k,V);H&&(En(y,H),ie(p,"formie:refresh-tokens:after",H))},registerModule:(h,p)=>t.register(h,p),unregisterModule:h=>{t.unregister(h)},getRegisteredModules:()=>t.getAll(),scan:async h=>{const p=h||document,v=Array.from(p.querySelectorAll(at));oe.log("Scan started.",{scope:p===document?"document":p,targetCount:v.length});const b=(await Promise.all(v.map(k=>{const I=st(k);return s(k,I)}))).filter(k=>!!k);return oe.log("Scan finished.",{mountedCount:b.length,deferredCount:v.length-b.length}),b},observe:h=>{if(typeof MutationObserver>"u")return()=>{};const p=h||document;oe.log("Observer started.",{scope:p===document?"document":p});const v=new MutationObserver(y=>{y.forEach(b=>{b.addedNodes.forEach(k=>{k instanceof Element&&(k.matches(at)&&(oe.log("Observer detected new root.",{target:me(k)}),s(k,st(k))),k.querySelectorAll(at).forEach(I=>{oe.log("Observer detected new nested root.",{target:me(I)}),s(I,st(I))}))}),b.removedNodes.forEach(k=>{k instanceof Element&&(e.has(k)&&(oe.log("Observer detected removed root.",{target:me(k)}),a(k)),k.querySelectorAll(at).forEach(I=>{e.has(I)&&(oe.log("Observer detected removed nested root.",{target:me(I)}),a(I))}))})})});return v.observe(p,{childList:!0,subtree:!0}),()=>{v.disconnect(),oe.log("Observer stopped."),r.forEach((b,k)=>{bm(k,p)&&(b(),r.delete(k))});const y=[];p instanceof Element&&p.matches(at)&&y.push(p),p.querySelectorAll(at).forEach(b=>{y.push(b)}),y.forEach(b=>{e.has(b)&&a(b)})}}}}const xn=2e3,Ph=5e3,Nh=5e3,Dh=12e4;async function kn(e){await new Promise(t=>{window.setTimeout(t,Math.max(e,0))})}async function $h(e,{timeoutMs:t=5e3,intervalMs:r=30}={}){const n=Date.now();for(;Date.now()-n<t;){const i=e();if(i)return i;await kn(r)}throw new Error("Timed out waiting for async condition.")}function Ri(e,t){let r=null;return(...n)=>{r!==null&&window.clearTimeout(r),r=window.setTimeout(()=>{e(...n)},Math.max(t,0))}}function zh(e){const t=String(e||"asyncDefer").toLowerCase();return{async:t.includes("async"),defer:t.includes("defer")}}function Fi(e,t){const r=Array.from(e.querySelectorAll(`input[name="${t}"], textarea[name="${t}"]`));for(const n of r){const i=String(n.value||"").trim();if(i!=="")return i}return""}function Gr(e,t){return t.some(r=>Fi(e,r)!=="")}function km(e,t){t.forEach(r=>{Array.from(e.querySelectorAll(`input[name="${r}"], textarea[name="${r}"]`)).forEach(i=>{i.value=""})})}function Oi(e,t,{value:r="",container:n}={}){let i=e.querySelector(`input[name="${t}"]`);if(!i){i=document.createElement("input"),i.type="hidden",i.name=t;const a=n||(e instanceof HTMLElement?e:null);a==null||a.appendChild(i)}return i.value=r,i}async function Pi(e,t,r){if(Gr(e,t))return!0;const n=Date.now()+Math.max(r,0);for(;Date.now()<n;)if(await kn(120),Gr(e,t))return!0;return!1}const _m=new Set(["handle","placeholderSelector","errorMessage","sessionKey","value"]),Sm="[data-formie-captcha-error-container]",Am=["formie:page:navigate","formie:page:navigate:after","formie:submit:result"],Tm=new Set(["formie:page:navigate","formie:page:navigate:after"]);function Tt(e,t,r){return e.addEventListener(t,r),()=>{e.removeEventListener(t,r)}}function sr(e,t){return e instanceof HTMLElement&&e.matches(t)?[e,...Array.from(e.querySelectorAll(t))]:Array.from(e.querySelectorAll(t))}function Yr(e){if(!(e instanceof HTMLElement)||!e.isConnected||e.hidden||e.closest("[hidden]")||e.closest("[data-formie-page-hidden]")||e.closest('[aria-hidden="true"]'))return!1;const t=window.getComputedStyle(e);return t.display!=="none"&&t.visibility!=="hidden"&&e.getClientRects().length>0}function Or(e,t){const r=sr(e,t);return r.find(n=>Yr(n))||r[0]||null}function Cm(e){e.innerHTML="";const t=document.createElement("div");return e.appendChild(t),t}function Qr(e){var t;(t=e==null?void 0:e.querySelector(Sm))==null||t.remove()}function Im(e,t,r){if(!e)return;Qr(e);const n=document.createElement("div");n.setAttribute("data-formie-captcha-error-container",""),n.setAttribute("aria-live","polite"),n.setAttribute("aria-atomic","true"),ue(n,r||e,"fieldErrors");const i=document.createElement("div");i.setAttribute("data-formie-captcha-error",""),i.setAttribute("role","alert"),ue(i,r||e,"fieldError"),i.textContent=t,n.appendChild(i),e.appendChild(n)}function Lm(e){const t=e instanceof CustomEvent?e.detail:null;return!t||typeof t!="object"?null:t}function Mm(e,t){if(!(e!=null&&e.captchas)||typeof e.captchas!="object")return null;const r=e.captchas[t];return!r||typeof r!="object"?null:r}function Rm(e,t,r,n){const i=new Set,a=()=>{const c=sr(e,t),f=new Set(c.filter(m=>Yr(m)));c.forEach(m=>{f.has(m)&&!i.has(m)&&(i.add(m),r(m))}),Array.from(i).forEach(m=>{f.has(m)||(i.delete(m),n(m))})},o=Ri(a,20),s=new MutationObserver(()=>{o()});s.observe(e,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["class","style","hidden","aria-hidden","data-formie-page-hidden"]});const l=[Tt(window,"resize",()=>{o()}),...Am.map(c=>Tt(e,c,()=>{if(Tm.has(c)){a();return}o()}))];return a(),{cleanup:()=>{s.disconnect(),l.forEach(c=>{c()}),Array.from(i).forEach(c=>{n(c)}),i.clear()},reconcile:o,reconcileImmediate:a,getVisible:()=>sr(e,t).filter(c=>Yr(c))}}function Fm(e,t){return(typeof t.handle=="string"&&t.handle.trim()!==""?t.handle.trim():"")||e}function Om(e,t,{defaultPlaceholderSelector:r,defaultTokenFieldNames:n=[],defaultWaitForValueMs:i=xn}){const a=t||{},o=Object.entries(a).reduce((u,[g,E])=>(_m.has(g)||(u[g]=E),u),{}),s=n.map(String).filter(Boolean),l=Number(i),c=typeof a.placeholderSelector=="string"&&a.placeholderSelector.trim()!==""?a.placeholderSelector.trim():r,f=typeof a.errorMessage=="string"&&a.errorMessage.trim()!==""?a.errorMessage.trim():Ve("Captcha challenge must be completed."),m=typeof a.sessionKey=="string"&&a.sessionKey.trim()!==""?a.sessionKey.trim():null,d=typeof a.value=="string"?a.value:null;return{handle:Fm(e,a),ui:{placeholderSelector:c,errorMessage:f},transport:{tokenFieldNames:s,waitForValueMs:Number.isFinite(l)?l:i,sessionKey:m,value:d},provider:o}}function Pm(e,t){const r=e.form||e.root,n=t.ui.placeholderSelector,i=t.handle;return{form:e.form,root:e.root,placeholder:{query:()=>sr(e.root,n),getPrimary:()=>Or(e.root,n),observe:(a,o)=>Rm(e.root,n,a,o),createContainer:a=>Cm(a),clear:a=>{a&&(Qr(a),a.innerHTML="")}},errors:{getDefaultMessage:()=>t.ui.errorMessage,show:(a,o)=>{Im(o||Or(e.root,n),a||t.ui.errorMessage,e.form||e.root)},clear:a=>{Qr(a||Or(e.root,n))}},tokens:{names:t.transport.tokenFieldNames,has:(a=t.transport.tokenFieldNames,o=r)=>Gr(o,a),read:(a=t.transport.tokenFieldNames[0],o=r)=>a?Fi(o,a):"",write:(a,{names:o=t.transport.tokenFieldNames,root:s=r,container:l=e.form}={})=>{o.forEach(c=>{Oi(s,c,{value:a,container:l})})},clear:(a=t.transport.tokenFieldNames,o=r)=>{km(o,a)},wait:(a=t.transport.waitForValueMs,o=t.transport.tokenFieldNames,s=r)=>Pi(s,o,a)},refresh:{providerHandle:i,onTokensRefreshed:a=>{const o=["formie:refresh-tokens:after","formie:refresh-tokens:refreshed"].map(s=>Tt(e.root,s,l=>{const c=Lm(l),f=Mm(c,i);f&&a(f)}));return()=>{o.forEach(s=>{s()})}}},events:{onRoot:(a,o)=>Tt(e.root,a,o),onForm:(a,o)=>e.form?Tt(e.form,a,o):()=>{}}}}const et=Fe("captchas");function Ni({id:e,defaultPlaceholderSelector:t,defaultTokenFieldNames:r=[],defaultWaitForValueMs:n=xn,setup:i}){return{id:e,kind:"captcha",match:()=>!0,setup:async a=>{const o=Om(e,a.options||{},{defaultPlaceholderSelector:t,defaultTokenFieldNames:r,defaultWaitForValueMs:n});et.log("Setup module.",{moduleId:e,placeholderSelector:o.ui.placeholderSelector,tokenFieldNames:o.transport.tokenFieldNames});const s=Pm(a,o);return i({...a,options:o,services:s})}}}function Nm({id:e,defaultPlaceholderSelector:t,defaultTokenFieldNames:r=[],defaultWaitForValueMs:n=xn}){return Ni({id:e,defaultPlaceholderSelector:t,defaultTokenFieldNames:r,defaultWaitForValueMs:n,setup:async({services:i,options:a,root:o})=>{const s=[];let l=i.placeholder.getPrimary(),c=a.transport.sessionKey,f=a.transport.value||"";const m=u=>{!u||!c||(u.innerHTML="",Oi(u,c,{value:f,container:u}))},d=i.placeholder.observe(u=>{l=u,et.log("Passive placeholder visible.",{moduleId:e}),m(u)},u=>{l===u&&(l=i.placeholder.getPrimary()),u.innerHTML=""});return s.push(d.cleanup),m(l),s.push(i.refresh.onTokensRefreshed(u=>{c=typeof u.sessionKey=="string"&&u.sessionKey.trim()!==""?u.sessionKey.trim():c,f=typeof u.value=="string"?u.value:"";const g=i.placeholder.getPrimary()||l;l=g,m(g)})),{destroy:()=>{s.forEach(u=>{u()})},onBeforeStage:async u=>{if(u.stage!=="screen"||u.action!=="submit")return;const g=c?[c]:a.transport.tokenFieldNames;if(g.length===0)return;if(!await Pi(o,g,a.transport.waitForValueMs)){const h=i.errors.getDefaultMessage();i.errors.show(h,l),et.warn("Passive captcha missing token.",{moduleId:e,tokenFieldNames:g}),u.abort(h)}}}}})}function Dm(e){return Ni({id:e.id,defaultPlaceholderSelector:e.defaultPlaceholderSelector,defaultTokenFieldNames:e.defaultTokenFieldNames,setup:async t=>{const r=[],n=new Map,i=new Map;let a=t.services.placeholder.getPrimary(),o=!1,s=null;const l=async()=>(s||(et.log("Loading captcha provider API.",{moduleId:e.id}),s=e.load(t)),s),c=async u=>{const g=n.get(u);if(t.services.errors.clear(u),!g){u.innerHTML="";return}const E=await l();e.unmount&&await e.unmount({api:E,widget:g,placeholder:u,services:t.services,options:t.options,provider:t.options.provider}),n.delete(u),u.innerHTML="",t.services.tokens.clear(),et.log("Unmounted captcha placeholder widget.",{moduleId:e.id}),a===u&&(a=t.services.placeholder.getPrimary())},f=async u=>{if(o||n.has(u)||i.has(u))return;const g=(async()=>{const E=await l();if(o||n.has(u))return;const h=t.services.placeholder.createContainer(u),p=await e.mount({api:E,placeholder:u,container:h,services:t.services,options:t.options,provider:t.options.provider});n.set(u,p),a=u,et.log("Mounted captcha placeholder widget.",{moduleId:e.id})})().finally(()=>{i.delete(u)});i.set(u,g),await g},m=t.services.placeholder.observe(u=>{a=u,f(u)},u=>{c(u)});r.push(m.cleanup);const d=async u=>{const E=m.getVisible();if(e.reset){const h=await l();for(const p of E){const v=n.get(p);if(!v){await f(p);continue}await e.reset({api:h,widget:v,placeholder:p,services:t.services,options:t.options,provider:t.options.provider,reason:u}),t.services.tokens.clear(),t.services.errors.clear(p)}m.reconcile();return}for(const h of Array.from(n.keys()))await c(h);for(const h of E)await f(h);m.reconcile()};return r.push(t.services.events.onRoot("formie:submit:result",u=>{const g=u instanceof CustomEvent?u.detail:null;(g==null?void 0:g.stage)!=="validate"&&((g==null?void 0:g.ok)===!1&&(g==null?void 0:g.stage)==="screen"||(g==null?void 0:g.ok)!==!0&&d("submit-result"))})),t.form&&r.push(t.services.events.onForm(Br("reset"),()=>{a=t.services.placeholder.getPrimary()||a,window.setTimeout(()=>{d("reset-state")},0)})),{destroy:async()=>{o=!0,r.forEach(u=>{u()});for(const u of Array.from(n.keys()))await c(u)},onBeforeStage:async u=>{if(u.stage!=="screen"||u.action!=="submit")return;m.reconcileImmediate();const g=m.getVisible();if(g.length===0)return;let E=g.find(v=>v===a)||g[0];await f(E),E=a||E,t.services.errors.clear(E);const h=n.get(E);if(!h){const v=t.services.errors.getDefaultMessage();t.services.errors.show(v,E),et.warn("Captcha widget unavailable at screen stage.",{moduleId:e.id}),u.abort(v);return}const p=await l();await e.screen({api:p,widget:h,placeholder:E,services:t.services,options:t.options,provider:t.options.provider,stageCtx:u})}}}})}const Vh=Dm,Hh=Nm,co=2500,$m={bpoint:["bpointToken"],stripe:["stripePaymentIntentId"],paypal:["paypalOrderId","paypalAuthId"],payway:["paywayTokenId"],opayo:["opayoTokenId"],eway:["ewayTokenData"],"go-cardless":["goCardlessRedirectId"],mollie:["molliePaymentId"],moneris:["monerisTokenId"],paddle:["paddleTransactionId"],square:["squarePaymentId"]};function zm(e){return e.replace("{field:","").replace("{","").replace("}","").replace("]","").split("[").join("][")}function Vm(e){return`fields[${zm(e)}]`}function Hm(e,t){const r=Vm(t),n=Array.from(e.querySelectorAll(`[name="${r}"]`)),i=Array.from(e.querySelectorAll(`[name="${r}[]"]`));return(i.length?i:n).filter(a=>a instanceof HTMLElement)}function uo(e,t){var n,i,a;const r=Hm(e,t);for(const o of r){const s=o.closest("[data-formie-field-handle]"),l=(a=(i=(n=s==null?void 0:s.querySelector("[data-formie-field-label]"))==null?void 0:n.childNodes[0])==null?void 0:i.textContent)==null?void 0:a.trim();if(l)return l}return""}function Pr(e){let t=e.replace(/[^\d.,-]/g,"");const r=t.includes(","),n=t.includes(".");if(r&&n)t.lastIndexOf(",")>t.lastIndexOf(".")?t=t.replace(/\./g,"").replace(",","."):t=t.replace(/,/g,"");else if(r&&!n){const i=t.split(",");i.length===2&&i[1].length===3&&/^\d+$/.test(i[0])&&/^\d+$/.test(i[1])?t=i[0]+i[1]:t=t.replace(",",".")}else t=t.replace(/,/g,"");return parseFloat(t)}function qm(e){return e.replace(/^\{field:/,"").replace(/^\{/,"").replace(/\}$/,"").trim()}function vt(e){return qm(e).replace(/\]/g,"").split("[").join(".").replace(/\.+/g,".").replace(/^\./,"").replace(/\.$/,"")}function Zr(e){const r=vt(e).split(".").filter(Boolean);if(!r.length)return"";const[n,...i]=r;return`fields[${n}]${i.map(a=>`[${a}]`).join("")}`}function Bm(e){const r=String(e||"").trim().match(/^fields\[([^\]]+)\](.*)$/);if(!r)return"";const n=r[1]||"",i=r[2]||"",a=Array.from(i.matchAll(/\[([^\]]+)\]/g)).map(o=>o[1]||"").filter(Boolean);return[n,...a].join(".")}function jm(e){const t=e.split(";").map(o=>o.trim()).filter(Boolean);if(!t.length)return{source:"",transforms:[]};const[r,...n]=t,i=[];let a=null;return n.forEach(o=>{if(o.startsWith("transform=")){a&&i.push(a),a={id:decodeURIComponent(o.slice(10)||"").trim(),params:{}};return}if(!a||!o.includes("="))return;const[s,l]=o.split("=",2),c=(s||"").trim();!c||c==="transform"||(a.params[c]=decodeURIComponent(l||"").trim())}),a&&i.push(a),{source:r||"",transforms:i}}function Um(e){const t=String(e||"").trim();if(!t)return{raw:t,target:"",key:"",selector:"",defaultValue:"",transforms:[],isToken:!1,isValid:!1};const r=t.match(/^\{([a-zA-Z]+)(?::(.*))?\}$/);if(!r)return{raw:t,target:"",key:vt(t),selector:"",defaultValue:"",transforms:[],isToken:!1,isValid:!0};const n=(r[1]||"").trim().toLowerCase(),i=(r[2]||"").trim(),[a,o=""]=i.split("|",2),{source:s,transforms:l}=jm(a||"");if(n!=="field")return{raw:t,target:"",key:"",selector:"",defaultValue:o.trim(),transforms:l,isToken:!0,isValid:!1};const c=s.indexOf(":"),f=c===-1?s:s.slice(0,c),m=c===-1?"":s.slice(c+1),d=vt(f);return{raw:t,target:"field",key:d,selector:m.trim(),defaultValue:o.trim(),transforms:l,isToken:!0,isValid:d!==""}}function Wm(e){return e instanceof HTMLInputElement||e instanceof HTMLTextAreaElement||e instanceof HTMLSelectElement}function Km(e,t,r){const n=t.trim(),i=String(r.name||"").trim();if(!n||!i)return;const a=e.get(n)||{key:n,names:[],inputs:[]};a.names.includes(i)||a.names.push(i),a.inputs.includes(r)||a.inputs.push(r),e.set(n,a)}function Jm(e){const t=new Map;return Array.from(e.querySelectorAll("[name]")).filter(n=>Wm(n)).forEach(n=>{const i=Bm(n.name);i&&Km(t,i,n)}),t}function Gm(e){if(!e.length)return"";const t=e[0];if(t instanceof HTMLSelectElement&&t.multiple)return Array.from(t.selectedOptions).map(n=>n.value);if(e.some(n=>n instanceof HTMLInputElement&&(n.type==="checkbox"||n.type==="radio"))){const n=e.flatMap(i=>!(i instanceof HTMLInputElement)||!i.checked?[]:[i.value]);return n.length>1?n:n[0]||""}return t.value}function Ym(e,t){return e.get(vt(t))||null}function ct(e,t){const r=Um(e),n=r.key,i=Ym(t,n);if(!i)return{key:n,value:r.defaultValue,found:!1};const a=Gm(i.inputs);return{key:n,value:a===""&&r.defaultValue!==""?r.defaultValue:a,found:!0}}const Di=new Set(["first","last","index","all","count","rows"]);function fo(e){return e.replace(/[.*+?^${}()|[\]\\]/g,"\\$&")}function Qm(e,t){const r=String(e||"").trim().toLowerCase();if(!r||t<=0)return[];if(r==="even"){const a=[];for(let o=1;o<=t;o++)o%2===0&&a.push(o-1);return a}if(r==="odd"){const a=[];for(let o=1;o<=t;o++)o%2===1&&a.push(o-1);return a}const n=r.match(/^every:(\d+)$/);if(n){const a=Math.max(1,Number.parseInt(n[1]||"1",10)),o=[];for(let s=1;s<=t;s+=a)o.push(s-1);return o}const i=[];return r.split(/\s*,\s*/).forEach(a=>{const o=a.trim();if(!o)return;const s=o.match(/^(\d+)\s*-\s*(\d+)$/);if(s){let c=Number.parseInt(s[1]||"0",10),f=Number.parseInt(s[2]||"0",10);c>f&&([c,f]=[f,c]);for(let m=c;m<=f;m++)m>=1&&m<=t&&i.push(m-1);return}const l=Number.parseInt(o,10);Number.isFinite(l)&&l>=1&&l<=t&&i.push(l-1)}),[...new Set(i)].sort((a,o)=>a-o)}function $i(e){const t=vt(e),r=t.split(".").filter(Boolean);return r.length<2?{fieldKey:t,columnKey:r[r.length-1]||""}:r.length>=3&&/^\d+$/.test(r[1]||"")?{fieldKey:r[0]||"",columnKey:r.slice(2).join(".")}:{fieldKey:r[0]||"",columnKey:r.slice(1).join(".")}}function zi(e,t,r){const n=new RegExp(`^${fo(e)}\\.(\\d+)\\.${fo(t)}$`);return[...r.keys()].filter(i=>n.test(i)).sort((i,a)=>{const o=Number.parseInt(i.split(".")[1]||"0",10),s=Number.parseInt(a.split(".")[1]||"0",10);return o-s})}function Zm(e,t){return ct(e,t).value}function qh(e,t,r){const n=new Set,{fieldKey:i,columnKey:a}=$i(e),o=String(t.scope||"").trim().toLowerCase();if(!i||!a||!Di.has(o)){const l=Zr(e);return l&&(n.add(l),n.add(`${l}[]`)),n}return zi(i,a,r).forEach(l=>{var m;const c=r.get(l);if((m=c==null?void 0:c.names)!=null&&m.length){c.names.forEach(d=>{n.add(d)});return}const f=Zr(l);f&&(n.add(f),n.add(`${f}[]`))}),n}function Bh(e,t,r){const n=String(t.scope||"").trim().toLowerCase();if(!n||!Di.has(n))return ct(e,r);const{fieldKey:i,columnKey:a}=$i(e);if(!i||!a)return ct(e,r);const o=zi(i,a,r),s=o.map(l=>Zm(l,r));if(n==="count")return{key:`${i}.${a}`,value:String(o.length),found:!0};if(n==="first")return{key:o[0]||`${i}.0.${a}`,value:s[0]??"",found:o.length>0};if(n==="last")return{key:o[o.length-1]||`${i}.0.${a}`,value:s[s.length-1]??"",found:o.length>0};if(n==="index"){const l=Number.parseInt(String(t.index??"0"),10),c=`${i}.${l}.${a}`;return ct(c,r)}if(n==="all"){const l=s.flatMap(c=>Array.isArray(c)?c:c===""?[]:[c]);return{key:`${i}.${a}`,value:l,found:l.length>0}}if(n==="rows"){const l=Qm(String(t.rows||""),o.length);if(l.length===0)return{key:`${i}.${a}`,value:"",found:!1};if(l.length===1)return{key:o[l[0]]||`${i}.${l[0]}.${a}`,value:s[l[0]]??"",found:!0};const c=l.flatMap(f=>{const m=s[f];return Array.isArray(m)?m:m===""?[]:[m]});return{key:`${i}.${a}`,value:c,found:c.length>0}}return ct(e,r)}function Vi(e,t){const r=t.replace(/"/g,'\\"');return e.querySelector(`input[name$="[${r}]"]`)||e.querySelector(`input[name$="${r}"]`)}function Gt(e,t){const r=t.find(n=>{const i=Vi(e,n);return!i||String(i.value||"").trim()===""});return{ok:!r,missingSuffix:r}}async function Hi(e,t,r){const n=Gt(e,t);if(n.ok)return n;const i=Date.now()+Math.max(r,0);for(;Date.now()<i;){await kn(120);const a=Gt(e,t);if(a.ok)return a}return Gt(e,t)}const Xm=new Set(["handle","requiredInputSuffixes","waitForValueMs","errorMessage"]),mo="[data-payment-success]",ho="[data-payment-error]";function eh(e,t){return(typeof t.handle=="string"&&t.handle.trim()!==""?t.handle.trim():"")||e}function th(e,t,r){const n=t||{},i=Object.entries(n).reduce((l,[c,f])=>(Xm.has(c)||(l[c]=f),l),{}),a=Array.isArray(n.requiredInputSuffixes)?n.requiredInputSuffixes.map(String).filter(Boolean):r.defaultRequiredInputSuffixes||[],o=Number(n.waitForValueMs??r.defaultWaitForValueMs??co),s=typeof n.errorMessage=="string"&&n.errorMessage.trim()!==""?n.errorMessage.trim():"Payment authorization is incomplete.";return{handle:eh(e,n),transport:{requiredInputSuffixes:a,waitForValueMs:Number.isFinite(o)?o:co,errorMessage:s},provider:i}}function po(e,t,r){return e.addEventListener(t,r),()=>{e.removeEventListener(t,r)}}function rh(e,t){const r=e.target,n=e.form,i=e.root,a=n||i,o=t.transport.requiredInputSuffixes,s=()=>Jm(n||i),l=y=>{const k=ct(y,s()).value;return Array.isArray(k)?k[0]||"":String(k||"")};return{root:i,form:n,field:r,updateInputs:(y,b)=>{const k=Array.isArray(y)?y:[y];for(const I of k){const F=Vi(a,I)??r.querySelector(`input[name*="${I}"]`);F&&(F.value=b)}},addError:y=>{const b=r.querySelector("[data-formie-field-type] > div, [data-field-type] > div")||r,k=b.querySelector(ho);k&&k.remove();const I=document.createElement("div");I.setAttribute("data-payment-error",""),I.textContent=y,ue(I,n||i,"fieldError"),b.appendChild(I)},removeError:()=>{var y;(y=r.querySelector(ho))==null||y.remove()},addSuccess:y=>{const b=r.querySelector("[data-formie-field-type] > div, [data-field-type] > div")||r,k=b.querySelector(mo);k&&k.remove();const I=document.createElement("div");I.setAttribute("data-payment-success",""),I.textContent=y,ue(I,n||i,"successMessage"),b.appendChild(I)},removeSuccess:()=>{var y;(y=r.querySelector(mo))==null||y.remove()},hasToken:()=>Gt(a,o).ok,waitForToken:(y=t.transport.waitForValueMs)=>Hi(a,o,y).then(b=>b.ok),getFieldValue:(y,b="string")=>{const k=l(y);return b==="float"||b==="int"||b==="number"?Pr(k):k},resolveAmount:y=>{const b=n||i,I=String(y.type||"").toLowerCase()==="dynamic"&&typeof y.variable=="string"&&y.variable.trim()!=="",F=y.value??(I?y.variable:y.fixed),V=String(F??"").trim(),H=typeof F=="number"?F:Pr(V);if(Number.isFinite(H)&&H>0)return{ok:!0,value:H};if(V!==""){const Q=l(V),C=Pr(Q);if(Number.isFinite(C)&&C>0)return{ok:!0,value:C};const j=uo(b,V);if(!Q)return{ok:!1,error:j?Ve('Provide a value for "{label}" to proceed.',{label:j}):Ve("Provide a payment amount to proceed.")}}return{ok:!1,error:Ve("Payment amount must be greater than 0.")}},resolveCurrency:y=>{const b=n||i,I=String(y.type||"").toLowerCase()==="dynamic"&&typeof y.variable=="string"&&y.variable.trim()!=="",F=y.value??(I?y.variable:y.fixed??y.defaultCurrency??""),V=String(F??"").trim(),H=V.toUpperCase();if(/^[A-Z]{3}$/.test(H)&&!I)return{ok:!0,value:H};if(V!==""){const Q=String(l(V)||"").trim(),C=Q.toUpperCase();if(/^[A-Z]{3}$/.test(C))return{ok:!0,value:C};const j=uo(b,V);if(!Q)return{ok:!1,error:j?Ve('Provide a value for "{label}" to proceed.',{label:j}):Ve("Provide a payment currency to proceed.")}}return{ok:!1,error:Ve("Payment currency must be a valid 3-letter code.")}},watchFieldValueChanges:(y,b,k=600)=>{const I=n||i,F=y.map(j=>String(j||"").trim()).filter(Boolean);if(F.length===0)return()=>{};const V=s(),H=new Set;F.forEach(j=>{var P;const N=vt(j),w=V.get(N);if((P=w==null?void 0:w.names)!=null&&P.length){w.names.forEach(W=>{H.add(W)});return}const A=Zr(N);A&&(H.add(A),H.add(`${A}[]`))});const Q=Ri(()=>{b()},k),C=j=>{const N=j.target,w=(N==null?void 0:N.name)||"";!w||!H.has(w)||Q()};return I.addEventListener("input",C),I.addEventListener("change",C),()=>{I.removeEventListener("input",C),I.removeEventListener("change",C)}},triggerSubmit:()=>{n&&n.setAttribute("data-formie-internal-resubmit","true"),n&&typeof n.requestSubmit=="function"?n.requestSubmit():n&&n.submit()},releaseSubmitLoading:()=>{n&&(n.removeAttribute("data-formie-internal-resubmit"),ir(n))},getBillingData:y=>{const b={};if(!y||typeof y!="object")return{billing_details:b};if(y.billingName){const k=l(y.billingName);k&&(b.name=k)}if(y.billingEmail){const k=l(y.billingEmail);k&&(b.email=k)}if(y.billingAddress){const k=y.billingAddress,I={},F=l(`${k}.address1`),V=l(`${k}.address2`),H=l(`${k}.address3`),Q=l(`${k}.city`),C=l(`${k}.zip`),j=l(`${k}.state`),N=l(`${k}.country`);F&&(I.line1=F),V&&(I.line2=V),H&&(I.line3=H),Q&&(I.city=Q),C&&(I.postal_code=C),j&&(I.state=j),N&&(I.country=N),Object.keys(I).length&&(b.address=I)}return{billing_details:b}},events:{onForm:(y,b)=>n?po(n,y,b):()=>{},onRoot:(y,b)=>po(i,y,b)}}}const De=Fe("payments");function go(e){const t=e;return!t.closest("[data-formie-page-hidden]")&&!t.closest("[hidden]")}function nh(e){const t=e.defaultRequiredInputSuffixes??$m[e.id]??[];return{id:e.id,kind:"payment",match:r=>{var n,i;return!!(r.target.querySelector('[data-formie-field-type="payment"]')||r.target.closest('[data-formie-field-type="payment"]')||((i=(n=r.target).getAttribute)==null?void 0:i.call(n,"data-formie-field-type"))==="payment")},setup:async r=>{const n=r.target,i=n.__formiePaymentModuleRegistry||{};n.__formiePaymentModuleRegistry=i;const a=i[e.id];if(a!=null&&a.destroy){De.warn("Found stale payment module instance; destroying previous.",{moduleId:e.id});try{await a.destroy()}catch{}}const o=th(e.id,r.options||{},{defaultRequiredInputSuffixes:t}),s=rh(r,o),l={...r,options:o,services:s},c=[];let f=null,m=null,d=null,u=null;const g=async()=>(f||(De.log("Loading payment provider API.",{moduleId:e.id}),f=e.load(l)),f),E=async()=>{if(!e.mount||m||!go(r.target))return;const v=await g();try{m=await e.mount({api:v,field:r.target,services:s,options:o,provider:o.provider}),De.log("Payment widget mounted.",{moduleId:e.id,handle:o.handle})}catch{De.warn("Payment widget mount failed.",{moduleId:e.id,handle:o.handle})}};if(c.push(r.on("formie:submit:before",()=>{s.removeError(),s.removeSuccess()})),e.setup){const v=r.root||r.form||r.target;d=await e.setup({...l,root:v}),d.destroy&&c.push(d.destroy)}e.mount&&go(r.target)&&await E(),["formie:page:navigate:after","formie:submit:result"].forEach(v=>{const y=()=>{E()};r.root.addEventListener(v,y),c.push(()=>{r.root.removeEventListener(v,y)})});const p=async()=>{var v;if(De.log("Destroying payment module.",{moduleId:e.id,handle:o.handle}),c.forEach(y=>y()),m&&e.unmount){const y=await g();await e.unmount({api:y,widget:m,field:r.target,services:s,options:o,provider:o.provider}),De.log("Payment widget unmounted.",{moduleId:e.id,handle:o.handle})}((v=i[e.id])==null?void 0:v.destroy)===p&&delete i[e.id],De.log("Payment module destroy complete.",{moduleId:e.id,handle:o.handle})};return i[e.id]={destroy:p},{destroy:p,onBeforeStage:async v=>{if(d!=null&&d.onBeforeStage){await d.onBeforeStage(v);return}if(v.stage!=="authorize"||v.action!=="submit")return;const b=r.target.closest("[data-formie-page]");if(b!=null&&b.hasAttribute("data-formie-page-hidden"))return;await E();const k=await g();if(e.onBeforeAuthorize){u||(u=(async()=>e.onBeforeAuthorize({api:k,widget:m,field:r.target,services:s,options:o,provider:o.provider,stageCtx:v}))().finally(()=>{u=null}));const V=await u;if(De.log("onBeforeAuthorize resolved.",{moduleId:e.id,handle:o.handle,ok:V}),!V){v.abort(o.transport.errorMessage);return}return}if(o.transport.requiredInputSuffixes.length===0)return;const I=r.form||r.root,F=await Hi(I,o.transport.requiredInputSuffixes,o.transport.waitForValueMs);F.ok||(De.warn("Required payment input(s) missing.",{moduleId:e.id,handle:o.handle,missingSuffix:F.missingSuffix}),v.abort(o.transport.errorMessage))},onAfterStage:async(v,y)=>{if(v.stage!=="dispatch"||!e.onAfterSubmit)return;const b=await e.onAfterSubmit({field:r.target,services:s,options:o,provider:o.provider,result:y});if(!(!(b!=null&&b.remount)||!e.mount)){if(m&&e.unmount){const k=await g();await e.unmount({api:k,widget:m,field:r.target,services:s,options:o,provider:o.provider})}m=null,await E()}}}}}}const jh=nh,oh="[data-formie-address-autocomplete-input]",vo="[data-formie-address-location]",Ue={autoComplete:"[data-formie-address-autocomplete-input]",address1:"[data-formie-address-line1-input]",address2:"[data-formie-address-line2-input]",address3:"[data-formie-address-line3-input]",city:"[data-formie-address-city-input]",state:"[data-formie-address-state-input]",zip:"[data-formie-address-zip-input]",country:"[data-formie-address-country-input]"},We={autoComplete:"[data-formie-address-autocomplete-input]",address1:"[data-address1]",address2:"[data-address2]",address3:"[data-address3]",city:"[data-city]",state:"[data-state]",zip:"[data-zip]",country:"[data-country]"},ih={autoComplete:[Ue.autoComplete,We.autoComplete],address1:[Ue.address1,We.address1],address2:[Ue.address2,We.address2],address3:[Ue.address3,We.address3],city:[Ue.city,We.city],state:[Ue.state,We.state],zip:[Ue.zip,We.zip],country:[Ue.country,We.country]};function ah(e,t){for(const r of ih[t]){const n=e.querySelector(r);if(n instanceof HTMLInputElement||n instanceof HTMLSelectElement)return n}return null}const sh=new Set(["handle"]);function lh(e,t){return(typeof t.handle=="string"&&t.handle.trim()!==""?t.handle.trim():"")||e}function ch(e,t){const r=t||{},n=Object.entries(r).reduce((i,[a,o])=>(sh.has(a)||(i[a]=o),i),{});return{handle:lh(e,r),provider:n}}function uh(e,t,r){return e.addEventListener(t,r),()=>{e.removeEventListener(t,r)}}function dh(e){const t=e.target,r=e.form,n=e.root,i=oh;return{root:n,field:t,form:r,input:{getAutocomplete:()=>t.querySelector(i),setValue:(a,o,s)=>{const l=ah(t,a);if(!l)return;const c=o||s||"";l.value!==c&&(l.value=c,l.dispatchEvent(new Event("input",{bubbles:!0})),l.dispatchEvent(new Event("change",{bubbles:!0})))}},location:{getButton:()=>t.querySelector(vo),onUseLocation:a=>{const o=t.querySelector(vo);if(!o)return()=>{};const s=l=>{l.preventDefault(),navigator.geolocation&&navigator.geolocation.getCurrentPosition(a,()=>{},{enableHighAccuracy:!0})};return o.addEventListener("click",s),()=>{o.removeEventListener("click",s)}}},events:{onField:(a,o)=>uh(t,a,o)}}}const lt=Fe("address");function bo(e){const t=e;return!t.closest("[data-formie-page-hidden]")&&!t.closest("[hidden]")}function fh(e){return{id:e.id,kind:"address",match:t=>!!t.target.querySelector("[data-formie-address-autocomplete-input]"),setup:async t=>{const r=ch(e.id,t.options||{}),n=dh(t);lt.log("Setup module.",{moduleId:e.id});const i={...t,options:r,services:n},a=[];let o=null,s=null;if(!n.input.getAutocomplete())return console.warn(`[formie] Address module "${e.id}" skipped: no autocomplete input found in target. Ensure the Address field has the Auto-Complete subfield enabled.`),lt.warn("Autocomplete input missing; skipping module.",{moduleId:e.id}),{destroy:()=>{}};const c=async()=>(o||(lt.log("Loading provider API.",{moduleId:e.id}),o=e.load(i)),o),f=async()=>{if(s||!bo(t.target))return;const u=await c();s=await e.mount({api:u,field:t.target,services:n,options:r,provider:r.provider}),lt.log("Widget mounted.",{moduleId:e.id})};bo(t.target)&&await f(),["formie:page:navigate:after","formie:submit:result"].forEach(u=>{const g=()=>{f()};t.root.addEventListener(u,g),a.push(()=>{t.root.removeEventListener(u,g)})});const d=n.location.onUseLocation(u=>{e.onCurrentLocation&&(async()=>{var E;if(await f(),!s)return;const g=await c();await((E=e.onCurrentLocation)==null?void 0:E.call(e,u,{api:g,widget:s,field:t.target,services:n,options:r,provider:r.provider}))})()});return d&&a.push(d),{destroy:async()=>{if(lt.log("Destroying module.",{moduleId:e.id}),a.forEach(u=>u()),s&&e.unmount){const u=await c();await e.unmount({api:u,widget:s,field:t.target,services:n,options:r,provider:r.provider}),lt.log("Widget unmounted.",{moduleId:e.id})}}}}}}const Uh=fh;function mh(e){const t=e.getElementById("formie-preview-config");if(!(t instanceof HTMLScriptElement)||!t.textContent)return{};try{return JSON.parse(t.textContent)}catch(r){return console.warn("[FormiePreview] Failed to parse preview config.",r),{}}}function hh(e,t){if(!(t!=null&&t.length))return;const r=JSON.stringify(t);e.querySelectorAll("[data-formie], [data-formie-form]").forEach(n=>{n.setAttribute("data-formie-modules",r)})}function ph(e){var l,c,f;const t=e.body,r=(l=e.defaultView)==null?void 0:l.HTMLElement;if(!t)return((c=e.documentElement)==null?void 0:c.scrollHeight)||0;const n=t.getBoundingClientRect(),i=(f=e.defaultView)==null?void 0:f.getComputedStyle(t),a=parseFloat(i.paddingTop||"0")||0,o=parseFloat(i.paddingBottom||"0")||0,s=Array.from(t.children).reduce((m,d)=>{if(!r||!(d instanceof r)||d.tagName==="SCRIPT")return m;const u=d.getBoundingClientRect();return Math.max(m,u.bottom-n.top)},a);return Math.ceil(s+o)}function ut(e,t){var n;const r=ph(e.document);t==null||t(r),(n=e.parent)==null||n.postMessage({type:"formie-preview:height",height:r},"*")}function gh(e,t){const r=e.document;if(typeof e.ResizeObserver<"u"){const n=new e.ResizeObserver(()=>{ut(e,t)});n.observe(r.documentElement),r.body&&n.observe(r.body)}["click","input","change"].forEach(n=>{r.addEventListener(n,()=>{e.requestAnimationFrame(()=>{ut(e,t)})},!0)})}async function vh(e,t){var i;const r=e.document,n=mh(r);gh(e,t),e.addEventListener("load",()=>{ut(e,t)},{once:!0}),e.requestAnimationFrame(()=>{ut(e,t),e.requestAnimationFrame(()=>{ut(e,t)})}),(i=n.modules)!=null&&i.length&&(zu(!1),hh(r,n.modules),await xm().scan(r)),ut(e,t)}const bh=Object.assign({"../../../browser/ui-reference/examples/address.preview.ts":()=>R(()=>import("./address.preview.D-ghwOAm.js"),[]),"../../../browser/ui-reference/examples/agree.preview.ts":()=>R(()=>import("./agree.preview.BuDgdg1_.js"),[]),"../../../browser/ui-reference/examples/buttons-loading.preview.ts":()=>R(()=>import("./buttons-loading.preview.BvDn73XT.js"),[]),"../../../browser/ui-reference/examples/buttons-positions.preview.ts":()=>R(()=>import("./buttons-positions.preview.B-G789jX.js"),[]),"../../../browser/ui-reference/examples/buttons-variants.preview.ts":()=>R(()=>import("./buttons-variants.preview.0jJSmcOh.js"),[]),"../../../browser/ui-reference/examples/buttons.preview.ts":()=>R(()=>import("./buttons.preview.MzXYysPp.js"),[]),"../../../browser/ui-reference/examples/calculations.preview.ts":()=>R(()=>import("./calculations.preview.CtChBkrf.js"),[]),"../../../browser/ui-reference/examples/categories.preview.ts":()=>R(()=>import("./categories.preview.ixyBoeER.js"),__vite__mapDeps([57,58])),"../../../browser/ui-reference/examples/checkboxes.preview.ts":()=>R(()=>import("./checkboxes.preview.BI4i9Rg-.js"),[]),"../../../browser/ui-reference/examples/date.preview.ts":()=>R(()=>import("./date.preview.CSKAFHj6.js"),[]),"../../../browser/ui-reference/examples/entries.preview.ts":()=>R(()=>import("./entries.preview.vVoUh2wl.js"),__vite__mapDeps([59,58])),"../../../browser/ui-reference/examples/field-anatomy.preview.ts":()=>R(()=>import("./field-anatomy.preview.CDGHSvef.js"),[]),"../../../browser/ui-reference/examples/field-normal.preview.ts":()=>R(()=>import("./field-normal.preview.CiEbz5Fv.js"),[]),"../../../browser/ui-reference/examples/file-upload.preview.ts":()=>R(()=>import("./file-upload.preview.CTvngf20.js"),[]),"../../../browser/ui-reference/examples/hidden.preview.ts":()=>R(()=>import("./hidden.preview.MMyPAdXC.js"),[]),"../../../browser/ui-reference/examples/loading-button-variants.preview.ts":()=>R(()=>import("./loading-button-variants.preview.DsRnArXp.js"),[]),"../../../browser/ui-reference/examples/loading-buttons.preview.ts":()=>R(()=>import("./loading-buttons.preview.BUXpUDz6.js"),[]),"../../../browser/ui-reference/examples/loading-sizes-colors.preview.ts":()=>R(()=>import("./loading-sizes-colors.preview.IYbMzHOV.js"),[]),"../../../browser/ui-reference/examples/loading.preview.ts":()=>R(()=>import("./loading.preview.DlOgX5Nv.js"),[]),"../../../browser/ui-reference/examples/messages.preview.ts":()=>R(()=>import("./messages.preview.Bpxa33ze.js"),[]),"../../../browser/ui-reference/examples/multi-line-text-rich-text.preview.ts":()=>R(()=>import("./multi-line-text-rich-text.preview.pKViw2NJ.js"),[]),"../../../browser/ui-reference/examples/multi-line-text.preview.ts":()=>R(()=>import("./multi-line-text.preview.CRb5IKJ_.js"),[]),"../../../browser/ui-reference/examples/page-navigation-only.preview.ts":()=>R(()=>import("./page-navigation-only.preview.D9zHiF02.js"),[]),"../../../browser/ui-reference/examples/payment.preview.ts":()=>R(()=>import("./payment.preview.DtictnrE.js"),[]),"../../../browser/ui-reference/examples/phone.preview.ts":()=>R(()=>import("./phone.preview.D-k2drYO.js"),[]),"../../../browser/ui-reference/examples/progress.preview.ts":()=>R(()=>import("./progress.preview.kV7Ij1sV.js"),[]),"../../../browser/ui-reference/examples/radio.preview.ts":()=>R(()=>import("./radio.preview.DrkMq2KR.js"),[]),"../../../browser/ui-reference/examples/recipients.preview.ts":()=>R(()=>import("./recipients.preview.BWBx9rU1.js"),__vite__mapDeps([60,58])),"../../../browser/ui-reference/examples/repeater.preview.ts":()=>R(()=>import("./repeater.preview.BzsOZh0V.js"),[]),"../../../browser/ui-reference/examples/signature.preview.ts":()=>R(()=>import("./signature.preview.CWYxzxWD.js"),[]),"../../../browser/ui-reference/examples/single-line-text.preview.ts":()=>R(()=>import("./single-line-text.preview.BmmellSY.js"),[]),"../../../browser/ui-reference/examples/summary.preview.ts":()=>R(()=>import("./summary.preview.By_O1ubB.js"),[]),"../../../browser/ui-reference/examples/table.preview.ts":()=>R(()=>import("./table.preview.BFrHaTOl.js"),[]),"../../../browser/ui-reference/examples/tags.preview.ts":()=>R(()=>import("./tags.preview.CmHYrzId.js"),[]),"../../../browser/ui-reference/examples/upload-manager.preview.ts":()=>R(()=>import("./upload-manager.preview.DTc5MOwe.js"),[])});function yh(e){const t=e.split(/[?#]/,1)[0]||"/";return t.endsWith("/")?t:`${t.slice(0,t.lastIndexOf("/")+1)}`}function wh(e,t="/"){return t==="/"||!e.startsWith(t)?e:`/${e.slice(t.length)}`}function Eh(e,t,r="/"){return e.startsWith("@/")?`/${e.slice(2)}`:wh(new URL(e,`https://docs.local${yh(t)}`).pathname,r)}function xh(e){return`../../../${e.replace(/^\//,"")}`}async function kh(e,t,r="/"){const n=Eh(e,t,r),i=xh(n),a=bh[i];if(!a)return console.warn(`[FormiePreview] No preview source found for "${e}" resolved from "${t}".`),null;const o=await a();return o.default??o.preview??null}const _h=["srcdoc"],Sh=8,Ah=Ce({__name:"FormiePreview",props:{markup:{},minHeight:{default:120},src:{}},setup(e){const t=e,r=_o(),{site:n}=Ge(),i=ae(null),a=ae(null),o=ae(t.minHeight);let s=0;pe(()=>[r.path,t.src,n.value.base],async()=>{if(!t.src){a.value=null;return}const h=++s,p=await kh(t.src,r.path,n.value.base);h===s&&(a.value=p)},{immediate:!0});const l=q(()=>{var h;return((h=a.value)==null?void 0:h.markup)??t.markup??""}),c=q(()=>{var h;return((h=a.value)==null?void 0:h.minHeight)??t.minHeight}),f=q(()=>{var p;const h=(p=a.value)==null?void 0:p.modules;return JSON.stringify({modules:h!=null&&h.length?h:void 0}).replaceAll("<","\\u003c")});pe(c,h=>{o.value=h},{immediate:!0}),pe(()=>[l.value,c.value],(h,p)=>{(!p||p[0]!==l.value||p[1]!==c.value)&&(o.value=c.value)});function m(h){!Number.isFinite(h)||h<=0||(o.value=Math.ceil(h+Sh))}function d(){var V,H,Q;const h=(V=i.value)==null?void 0:V.contentDocument,p=h==null?void 0:h.body,v=(H=h==null?void 0:h.defaultView)==null?void 0:H.HTMLElement;if(!p)return c.value;const y=p.getBoundingClientRect(),b=(Q=h.defaultView)==null?void 0:Q.getComputedStyle(p),k=parseFloat((b==null?void 0:b.paddingTop)||"0")||0,I=parseFloat((b==null?void 0:b.paddingBottom)||"0")||0,F=Array.from(p.children).reduce((C,j)=>{if(!v||!(j instanceof v)||j.tagName==="SCRIPT")return C;const N=j.getBoundingClientRect();return Math.max(C,N.bottom-y.top)},k);return Math.ceil(F+I)}function u(h){var p,v;((p=h.data)==null?void 0:p.type)==="formie-preview:height"&&h.source===((v=i.value)==null?void 0:v.contentWindow)&&m(Number(h.data.height))}function g(){var p;const h=(p=i.value)==null?void 0:p.contentWindow;h&&(m(d()),vh(h,m))}qe(()=>{window.addEventListener("message",u)}),cr(()=>{window.removeEventListener("message",u)});const E=q(()=>`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    ${[ru,nu,ou,iu,au,su,lu,cu,uu,du,fu,mu,hu,pu,gu,vu,bu,yu,wu,Eu,xu,ku,_u,Su,Au,Tu].join(`
`)}
    body { margin: 0; padding: 16px; background: #fff; }
  </style>
</head>
<body>
  <script id="formie-preview-config" type="application/json">${f.value}<\/script>
  ${l.value}
</body>
</html>`);return(h,p)=>(M(),O("iframe",{ref_key:"iframeRef",ref:i,class:"formie-preview-frame",style:Rt({height:`${o.value}px`}),srcdoc:E.value,title:"Formie preview",loading:"lazy",onLoad:g},null,44,_h))}}),Wh=tu({enhanceApp({app:e}){e.component("FormiePreview",Ah)}});export{Ue as A,kn as B,Ph as C,mt as D,Ri as E,Oh as F,Ve as G,jh as H,Rh as I,ue as J,gt as K,Vh as a,zh as b,Dh as c,Uh as d,Hh as e,ah as f,Lh as g,Nh as h,Fe as i,Jm as j,qh as k,Zr as l,ct as m,vt as n,Ih as o,Bs as p,qs as q,Bh as r,Mh as s,Wh as t,Br as u,fr as v,$h as w,un as x,Fh as y,oi as z};
