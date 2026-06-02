const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/chunks/address-finder.BsV6S_7d.js","assets/chunks/scripts.pjRO1ntO.js","assets/chunks/framework.BKStFnx-.js","assets/chunks/google-address.BnBb3Kl1.js","assets/chunks/loqate.Bw5quUlY.js","assets/chunks/place-kit.BEpu7dLT.js","assets/chunks/styles.DqkRI_my.js","assets/chunks/captcha-eu.gIDjzctq.js","assets/chunks/friendly-captcha-v1.C9cwjy9h.js","assets/chunks/friendly-captcha-v2.Cg0Lk-Kx.js","assets/chunks/hcaptcha.Ur0NisHP.js","assets/chunks/recaptcha-enterprise.iUURyeXe.js","assets/chunks/recaptcha-shared.B-Ym7kNf.js","assets/chunks/recaptcha-v2-checkbox.z5ocNAm_.js","assets/chunks/recaptcha-v2-invisible.guRJWufw.js","assets/chunks/recaptcha-v3.DLS6BsKc.js","assets/chunks/snaptcha.tJT99HSA.js","assets/chunks/turnstile.DsZlCtkL.js","assets/chunks/calculations.CsCnnHVp.js","assets/chunks/index.BmqIol9_.js","assets/chunks/shared.CkSjEpgG.js","assets/chunks/checkbox-radio.wQR6jl23.js","assets/chunks/conditions.BMATD62A.js","assets/chunks/date-picker.DGAmdIaX.js","assets/chunks/file-upload.Bhpgej_B.js","assets/chunks/hidden.DI846PWm.js","assets/chunks/phone-country.BYawHTaa.js","assets/chunks/repeater.BZRxNbQU.js","assets/chunks/rich-text.D-voHtqM.js","assets/chunks/signature.B5jMOttl.js","assets/chunks/summary.CU6HVw1B.js","assets/chunks/table.DHUenowH.js","assets/chunks/text-limit.PLvIlYHF.js","assets/chunks/bpoint.yE9ryjUa.js","assets/chunks/eway.DqT8p9QE.js","assets/chunks/go-cardless.s1BYHWop.js","assets/chunks/mollie.B6ly0jc5.js","assets/chunks/moneris.Ct1bgkwu.js","assets/chunks/opayo.zjsCQuZX.js","assets/chunks/paddle.y1hZc5MD.js","assets/chunks/paypal.CG4CbbfX.js","assets/chunks/payway.oct1FwdC.js","assets/chunks/square.CHbEYH5f.js","assets/chunks/stripe.Dy66WA4g.js","assets/chunks/categories.preview.ixyBoeER.js","assets/chunks/elementDisplayPreview.BQWAWWZ5.js","assets/chunks/entries.preview.vVoUh2wl.js","assets/chunks/recipients.preview.BWBx9rU1.js"])))=>i.map(i=>d[i]);
var hi=Object.defineProperty;var pi=(t,e,r)=>e in t?hi(t,e,{enumerable:!0,configurable:!0,writable:!0,value:r}):t[e]=r;var dr=(t,e,r)=>pi(t,typeof e!="symbol"?e+"":e,r);import{u as We,w as he,a as rr,o as He,b as ro,c as V,r as ae,d as Ce,e as L,f as R,n as ce,F as ge,g as be,h as Y,i as Br,j as S,t as re,k as ke,l as At,m as ze,p as no,q as Ge,s as J,v as nr,x as oo,y as Me,z as gt,_ as O,A as gi,B as vi,C as bi,D as io,E as yi,G as wi,H as ye,I as Re,J as ao,T as xi,K as Tr,L as Ei,M as ki,N as mn,O as _i,P as Si,Q as Ai,R as Ti,S as so,U as Ci,V as hn}from"./framework.BKStFnx-.js";const Ii=/#.*$/,Li=/[?#].*$/,Mi=/(?:(^|\/)index)?\.(?:md|html)$/;function pn(t){return decodeURI(t).replace(Li,"").replace(Mi,"$1")}function gn(t){return/^\//.test(t)?t:`/${t}`}function zt(t,e){return t.map(r=>{const n={...r},i=n.base||e;return i&&n.link&&(n.link=`${i}${n.link}`),n.items&&(n.items=zt(n.items,i)),n})}function or(t,e,r=!1){if(e===void 0)return!1;const n=pn(`/${t}`);if(r)return new RegExp(e).test(n);if(pn(e)!==n)return!1;const i=e.match(Ii);return i?typeof window<"u"&&window.location.hash===i[0]:!0}function wt(t,e){var r;return e?or(t,e.link)?!0:((r=e.items)==null?void 0:r.some(n=>wt(t,n)))??!1:!1}function Ri(t,e){if(Array.isArray(t))return zt(t);if(!t)return[];const r=gn(e),n=Object.keys(t).sort((a,o)=>o.split("/").length-a.split("/").length).find(a=>r.startsWith(gn(a))),i=n?t[n]:[];return Array.isArray(i)?zt(i):zt(i.items,i.base)}function Fi(t){const e=[];let r=0;for(const n of t){if(n.items){e.push({text:n.text,icon:n.icon,items:n.items}),r=e.length-1;continue}e[r]||(e.push({items:[]}),r=e.length-1),e[r].items.push(n)}return e}function Ur(){const{frontmatter:t,page:e,theme:r}=We(),n=ae(!1),i=V(()=>Ri(r.value.sidebar,e.value.relativePath)),a=V(()=>Fi(i.value)),o=V(()=>t.value.sidebar!==!1&&t.value.layout!=="home"&&i.value.length>0);he(o,d=>{d||(n.value=!1)}),rr(d=>{if(typeof document>"u")return;const h=document.body.style.overflow;n.value&&typeof window<"u"&&window.innerWidth<1024&&(document.body.style.overflow="hidden"),d(()=>{document.body.style.overflow=h})});function s(){n.value=!0}function l(){n.value=!1}function c(){n.value=!n.value}return{isOpen:n,sidebar:i,sidebarGroups:a,hasSidebar:o,open:s,close:l,toggle:c}}function Oi(t,e){let r=null;rr(()=>{r=t.value?document.activeElement:null});const n=i=>{i.key==="Escape"&&t.value&&(e(),r instanceof HTMLElement&&r.focus())};He(()=>{window.addEventListener("keyup",n)}),ro(()=>{window.removeEventListener("keyup",n)})}const Pi=["d","fill"],ir=Ce({__name:"DocsIcon",props:{name:{default:""},class:{default:"size-4"}},setup(t){const e=t,r={"play-circle":{paths:[{d:"M8 14.25A6.25 6.25 0 1 0 8 1.75a6.25 6.25 0 0 0 0 12.5"},{d:"M6.25 5.75 10.25 8l-4 2.25V5.75",fill:"currentColor"}]},"app-window":{paths:[{d:"M2.75 4.25A1.5 1.5 0 0 1 4.25 2.75h7.5a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5 1.5h-7.5a1.5 1.5 0 0 1-1.5-1.5v-7.5Z"},{d:"M2.75 5.5h10.5"},{d:"M5 4.125h.01M7 4.125h.01M9 4.125h.01"}]},blocks:{paths:[{d:"M2.75 3.25h4.5v4.5h-4.5z"},{d:"M8.75 3.25h4.5v4.5h-4.5z"},{d:"M5.75 8.75h4.5v4.5h-4.5z"}]},"clipboard-list":{paths:[{d:"M5.25 3.25h5.5a1.5 1.5 0 0 1 1.5 1.5v7a1.5 1.5 0 0 1-1.5 1.5h-5.5a1.5 1.5 0 0 1-1.5-1.5v-7a1.5 1.5 0 0 1 1.5-1.5Z"},{d:"M6.25 2.75h3.5v1.5h-3.5z"},{d:"M6 6.5h3.75M6 8.5h3.75M6 10.5h3.75"},{d:"M5 6.5h.01M5 8.5h.01M5 10.5h.01"}]},"layout-template":{paths:[{d:"M2.75 3.25h10.5v9.5H2.75z"},{d:"M6.25 3.25v9.5"},{d:"M6.25 6.75h7"}]},"rows-3":{paths:[{d:"M3 4.5h1.5M6 4.5h7"},{d:"M3 8h1.5M6 8h7"},{d:"M3 11.5h1.5M6 11.5h7"}]},"square-terminal":{paths:[{d:"M3.25 3.25h9.5v9.5h-9.5z"},{d:"M5.25 6.25 7 8l-1.75 1.75"},{d:"M8.75 9.75h2.25"}]},"flask-conical":{paths:[{d:"M6 2.75h4"},{d:"M7 2.75v2.5l-3 5.25a1.5 1.5 0 0 0 1.3 2.25h5.4A1.5 1.5 0 0 0 12 10.5L9 5.25v-2.5"},{d:"M5.5 9h5"}]}},n=V(()=>r[e.name]??null);return(i,a)=>n.value?(L(),R("svg",{key:0,viewBox:"0 0 16 16",fill:"none",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round",class:ce(e.class),"aria-hidden":"true"},[(L(!0),R(ge,null,be(n.value.paths,o=>(L(),R("path",{key:o.d,d:o.d,fill:o.fill??"none"},null,8,Pi))),128))],2)):Y("",!0)}}),Ni={class:"relative"},Di={class:"min-w-0 flex-1 break-words"},zi=["href"],$i={class:"flex min-w-0 flex-1 items-start gap-x-2.5"},Vi={class:"flex min-w-0 flex-1 flex-wrap items-center gap-1.5 [word-break:break-word]"},Hi={class:"min-w-0 max-w-full break-words"},qi=Ce({__name:"DocsMobileMenuNode",props:{item:{},depth:{default:0}},emits:["navigate"],setup(t,{emit:e}){const r=t,n=e,{page:i}=We(),a=Br(),o=V(()=>{var u;return!!((u=r.item.items)!=null&&u.length)}),s=V(()=>or(i.value.relativePath,r.item.link)),l=V(()=>{var u;return((u=r.item.items)==null?void 0:u.some(g=>wt(i.value.relativePath,g)))??!1}),c=ae(o.value?!r.item.collapsed||l.value:!1);he(l,u=>{u&&(c.value=!0)});function d(u){return u?ze(u):"#"}async function h(u,g){g&&(u.preventDefault(),await a.go(d(g)),n("navigate"))}function f(){o.value&&(c.value=!c.value)}return(u,g)=>{const x=no("DocsMobileMenuNode",!0);return L(),R("li",Ni,[o.value?(L(),R("button",{key:0,type:"button",class:ce(["group flex w-full cursor-pointer items-center py-0.5 pr-2 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",l.value?"text-docs-primary":"text-slate-700"]),onClick:f},[S("span",Di,re(t.item.text),1),(L(),R("svg",{viewBox:"0 0 640 640",class:ce(["size-3 shrink-0 transition-transform",c.value?"rotate-90":"rotate-0"]),"aria-hidden":"true"},[...g[2]||(g[2]=[S("path",{d:"M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"},null,-1)])],2))],2)):(L(),R("a",{key:1,href:d(t.item.link),class:ce(["group flex w-full cursor-pointer items-center py-0.5 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",s.value?"text-docs-primary":"text-slate-700"]),onClick:g[0]||(g[0]=m=>h(m,t.item.link))},[S("div",$i,[t.item.icon?(L(),ke(ir,{key:0,name:t.item.icon,class:"mt-1 size-4 shrink-0 text-slate-500 group-hover:text-slate-700"},null,8,["name"])):Y("",!0),S("div",Vi,[S("span",Hi,re(t.item.text),1)])])],10,zi)),o.value&&c.value?(L(),R("ul",{key:2,style:At({marginLeft:t.depth===0?"1rem":"1.25rem"})},[(L(!0),R(ge,null,be(t.item.items,m=>(L(),ke(x,{key:m.link??`${m.text}-${m.icon??""}`,item:m,depth:t.depth+1,onNavigate:g[1]||(g[1]=p=>n("navigate"))},null,8,["item","depth"]))),128))],4)):Y("",!0)])}}}),ji={class:"min-h-full bg-white"},Bi={class:"border-b border-slate-200/80 px-4 pb-4 pt-5"},Ui={class:"flex min-w-0 items-center gap-3"},Wi=["src"],Ki={key:1,class:"min-w-0 truncate text-base font-semibold tracking-[-0.01em] text-slate-900"},Ji={class:"px-4 pb-6 pt-6"},Gi={"aria-label":"Sidebar navigation",class:"text-sm"},Yi={key:0,class:"mb-3 flex items-center gap-2.5 text-sm font-medium text-slate-900"},Zi={class:"space-y-px"},Qi=Ce({__name:"DocsMobileMenu",props:{logoSrc:{},siteTitle:{}},emits:["navigate"],setup(t){const{sidebarGroups:e}=Ur(),r=V(()=>e.value.filter(n=>{var i;return(i=n.items)==null?void 0:i.length}));return(n,i)=>(L(),R("div",ji,[S("div",Bi,[S("div",Ui,[t.logoSrc?(L(),R("img",{key:0,src:t.logoSrc,alt:"",class:"block h-7 w-auto max-w-[156px] shrink-0 object-contain"},null,8,Wi)):(L(),R("div",Ki,re(t.siteTitle),1))])]),S("div",Ji,[S("nav",Gi,[(L(!0),R(ge,null,be(r.value,a=>{var o,s;return L(),R("section",{key:a.text??((s=(o=a.items)==null?void 0:o[0])==null?void 0:s.link),class:"mt-6 first:mt-0"},[a.text?(L(),R("h2",Yi,[a.icon?(L(),ke(ir,{key:0,name:a.icon,class:"size-4 text-slate-600"},null,8,["name"])):Y("",!0),Ge(" "+re(a.text),1)])):Y("",!0),S("ul",Zi,[(L(!0),R(ge,null,be(a.items,l=>(L(),ke(qi,{key:l.link??`${l.text}-${l.icon??""}`,item:l,onNavigate:i[0]||(i[0]=c=>n.$emit("navigate"))},null,8,["item"]))),128))])])}),128))])])]))}}),Xi={class:"flex min-h-[calc(100dvh-14rem)] w-full flex-col items-center justify-center px-6 py-16 text-center sm:px-8 sm:py-24 lg:min-h-[calc(100dvh-10rem)]"},ea={class:"text-6xl font-semibold tracking-[-0.04em] text-slate-900 sm:text-7xl"},ta={class:"mt-3 text-2xl font-semibold tracking-[-0.03em] text-slate-900 sm:text-3xl"},ra={class:"mt-5 max-w-sm text-sm leading-6 text-slate-600"},na=["href","aria-label"],oa=Ce({__name:"DocsNotFound",setup(t){const{theme:e}=We(),r=V(()=>{var s;return((s=e.value.notFound)==null?void 0:s.code)??"404"}),n=V(()=>{var s;return((s=e.value.notFound)==null?void 0:s.title)??"Page not found"}),i=V(()=>{var s;return((s=e.value.notFound)==null?void 0:s.quote)??"The page you requested does not exist or may have moved."}),a=V(()=>{var s;return((s=e.value.notFound)==null?void 0:s.linkLabel)??"Go to home"}),o=V(()=>{var s;return((s=e.value.notFound)==null?void 0:s.linkText)??"Take me home"});return(s,l)=>(L(),R("section",Xi,[S("p",ea,re(r.value),1),S("h1",ta,re(n.value),1),l[0]||(l[0]=S("div",{class:"mt-6 h-px w-16 bg-slate-200"},null,-1)),S("p",ra,re(i.value),1),S("a",{href:J(ze)("/"),"aria-label":a.value,class:"mt-7 inline-flex items-center rounded-xl border border-docs-primary-border bg-docs-primary-soft px-4 py-2 text-sm font-medium text-docs-primary transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft-hover"},re(o.value),9,na)]))}}),ia={id:"table-of-contents-content",class:"toc"},aa=["data-depth","data-active","data-active-deepest"],sa=["href","onClick"],la=Ce({__name:"DocsOutlineItem",props:{items:{}},setup(t){const e=t;function r(n,i){const a=i.replace(/^#/,""),o=document.getElementById(a);o&&(n.preventDefault(),o.scrollIntoView({block:"start",behavior:"smooth"}),window.location.hash=i)}return(n,i)=>(L(),R("ul",ia,[(L(!0),R(ge,null,be(e.items,a=>(L(),R("li",{key:a.link,class:ce(["toc-item relative",a.depth>0?a.active?"border-l pl-4 border-docs-primary hover:border-docs-primary":"border-l pl-4 border-slate-950/5 hover:border-slate-950/20":""]),"data-depth":a.depth,"data-active":a.active||void 0,"data-active-deepest":a.activeDeepest||void 0},[S("a",{href:a.link,style:At(a.depth>0?"padding-left:1rem":void 0),class:ce(["break-words py-1",[a.depth>0?"group flex items-start whitespace-pre-wrap":"block border-l pl-4 font-medium",a.active?a.depth>0?"text-docs-primary":"text-docs-primary border-docs-primary hover:border-docs-primary":a.depth>0?"text-gray-500 hover:text-gray-900":"border-slate-950/5 hover:border-slate-950/20 hover:text-gray-900"]]),onClick:o=>r(o,a.link)},re(a.title),15,sa)],10,aa))),128))]))}}),ca={key:0,id:"table-of-contents","aria-label":"On this page",class:"space-y-2"},ua={type:"button",class:"flex cursor-pointer items-center space-x-2 text-sm font-medium text-slate-700 transition-colors hover:text-slate-900"},da=Ce({__name:"DocsOutline",setup(t){const{frontmatter:e,theme:r}=We(),n=V(()=>{const p=r.value.outline;return typeof p=="object"&&!Array.isArray(p)&&(p==null?void 0:p.label)||r.value.outlineTitle||"On this page"}),i=ae(null),a=ae([]),o=V(()=>{var y;const p=g(a.value,i.value),v=new Set(p.map(_=>_.link)),b=((y=p.at(-1))==null?void 0:y.link)??null;return u(a.value).map(_=>({..._,depth:Math.max(_.level-2,0),active:v.has(_.link),activeDeepest:_.link===b}))});function s(){return document.getElementById("docs-scroll-container")??document.getElementById("content-container")}function l(p){const v=Number.parseFloat(getComputedStyle(document.documentElement).getPropertyValue("--scroll-mt"));return Number.isFinite(v)?v:Math.min(Math.max(p.clientHeight*.18,56),120)}function c(p){if(p===!1)return null;const v=(typeof p=="object"&&!Array.isArray(p)&&p&&"level"in p?p.level:p)??2;return v==="deep"?[2,6]:Array.isArray(v)?[v[0],v[1]]:[v,v]}function d(){const p=c(e.value.outline??r.value.outline);return p||null}function h(p){const v=/\b(?:VPBadge|header-anchor|footnote-ref|ignore-header)\b/;let b="";for(const y of p.childNodes)if(y.nodeType===Node.ELEMENT_NODE){const _=y;if(v.test(_.className))continue;b+=_.textContent??""}else y.nodeType===Node.TEXT_NODE&&(b+=y.textContent??"");return b.trim()}function f(){const p=d();if(!p){a.value=[];return}const[v,b]=p,y=Array.from(document.querySelectorAll(".vp-doc :where(h1,h2,h3,h4,h5,h6)")).filter(F=>F instanceof HTMLElement&&!!F.id).map(F=>{const j=Number(F.tagName.slice(1));return{title:h(F),slug:F.id,link:`#${F.id}`,level:j,children:[]}}).filter(F=>F.title&&F.level>=v&&F.level<=b),_=[],C=[];for(const F of y){for(;C.length&&C[C.length-1].level>=F.level;)C.pop();C.length?C[C.length-1].children.push(F):_.push(F),C.push(F)}a.value=_}function u(p){return p.flatMap(v=>[v,...u(v.children??[])])}function g(p,v){var b;if(!v)return[];for(const y of p){if(y.link===v)return[y];if((b=y.children)!=null&&b.length){const _=g(y.children,v);if(_.length)return[y,..._]}}return[]}function x(){var M,B,N;const p=u(a.value),v=s();if(!p.length||!v){i.value=null;return}const b=v.scrollTop,y=v.clientHeight,_=v.scrollHeight,C=l(v),F=Math.abs(b+y-_)<1;if(b<1){const w=window.location.hash,A=p.some(P=>P.link===w)?w:null;i.value=A??((M=p[0])==null?void 0:M.link)??null;return}if(F){i.value=((B=p[p.length-1])==null?void 0:B.link)??null;return}const j=window.location.hash,H=p.some(w=>w.link===j)?j:null;let ee=null;for(const w of p){const A=document.getElementById(w.slug);if(!A)continue;const P=v.getBoundingClientRect().top;if(b+A.getBoundingClientRect().top-P>b+C)break;ee=w.link}i.value=ee??H??((N=p[0])==null?void 0:N.link)??null}const m=()=>{x()};return He(()=>{const p=s();requestAnimationFrame(()=>{f(),x()}),p==null||p.addEventListener("scroll",m,{passive:!0}),window.addEventListener("hashchange",m,{passive:!0})}),nr(()=>{const p=s();p==null||p.removeEventListener("scroll",m),window.removeEventListener("hashchange",m)}),oo(async()=>{await Me(),f(),x()}),(p,v)=>a.value.length?(L(),R("nav",ca,[S("button",ua,[v[0]||(v[0]=S("svg",{viewBox:"0 0 16 16",fill:"none",stroke:"currentColor","stroke-width":"2",class:"h-3 w-3","aria-hidden":"true"},[S("path",{d:"M2.5 3.5h11M2.5 8h7M2.5 12.5h11","stroke-linecap":"round"})],-1)),S("span",null,re(n.value),1)]),gt(la,{items:o.value},null,8,["items"])])):Y("",!0)}}),fa={root:()=>O(()=>import("./@localSearchIndexroot.pOTiiJMh.js"),[])};function Wr(t){return gi()?(vi(t),!0):!1}const lo=typeof window<"u"&&typeof document<"u";typeof WorkerGlobalScope<"u"&&globalThis instanceof WorkerGlobalScope;const ma=t=>t!=null,ha=Object.prototype.toString,pa=t=>ha.call(t)==="[object Object]",jt=()=>{},vn=ga();function ga(){var t,e;return lo&&((t=window==null?void 0:window.navigator)==null?void 0:t.userAgent)&&(/iP(?:ad|hone|od)/.test(window.navigator.userAgent)||((e=window==null?void 0:window.navigator)==null?void 0:e.maxTouchPoints)>2&&/iPad|Macintosh/.test(window==null?void 0:window.navigator.userAgent))}function va(t,e){function r(...n){return new Promise((i,a)=>{Promise.resolve(t(()=>e.apply(this,n),{fn:e,thisArg:this,args:n})).then(i).catch(a)})}return r}const co=t=>t();function ba(t,e={}){let r,n,i=jt;const a=l=>{clearTimeout(l),i(),i=jt};let o;return l=>{const c=ye(t),d=ye(e.maxWait);return r&&a(r),c<=0||d!==void 0&&d<=0?(n&&(a(n),n=null),Promise.resolve(l())):new Promise((h,f)=>{i=e.rejectOnCancel?f:h,o=l,d&&!n&&(n=setTimeout(()=>{r&&a(r),n=null,h(o())},d)),r=setTimeout(()=>{n&&a(n),n=null,h(l())},c)})}}function ya(t=co,e={}){const{initialState:r="active"}=e,n=uo(r==="active");function i(){n.value=!1}function a(){n.value=!0}const o=(...s)=>{n.value&&t(...s)};return{isActive:io(n),pause:i,resume:a,eventFilter:o}}function wa(t){return wi()}function $t(t){return Array.isArray(t)?t:[t]}function uo(...t){if(t.length!==1)return bi(...t);const e=t[0];return typeof e=="function"?io(yi(()=>({get:e,set:jt}))):ae(e)}function fo(t,e,r={}){const{eventFilter:n=co,...i}=r;return he(t,va(n,e),i)}function xa(t,e,r={}){const{eventFilter:n,initialState:i="active",...a}=r,{eventFilter:o,pause:s,resume:l,isActive:c}=ya(n,{initialState:i});return{stop:fo(t,e,{...a,eventFilter:o}),pause:s,resume:l,isActive:c}}function Ea(t,e=!0,r){wa()?He(t,r):e?t():Me(t)}function ka(t,e,r={}){const{debounce:n=0,maxWait:i=void 0,...a}=r;return fo(t,e,{...a,eventFilter:ba(n,{maxWait:i})})}function _a(t,e,r){return he(t,e,{...r,immediate:!0})}function bn(t,e,r){let n;ao(r)?n={evaluating:r}:n={};const{lazy:i=!1,evaluating:a=void 0,shallow:o=!0,onError:s=jt}=n,l=Re(!i),c=o?Re(e):ae(e);let d=0;return rr(async h=>{if(!l.value)return;d++;const f=d;let u=!1;a&&Promise.resolve().then(()=>{a.value=!0});try{const g=await t(x=>{h(()=>{a&&(a.value=!1),u||x()})});f===d&&(c.value=g)}catch(g){s(g)}finally{a&&f===d&&(a.value=!1),u=!0}}),i?V(()=>(l.value=!0,c.value)):c}const ut=lo?window:void 0;function mo(t){var e;const r=ye(t);return(e=r==null?void 0:r.$el)!=null?e:r}function xt(...t){const e=[],r=()=>{e.forEach(s=>s()),e.length=0},n=(s,l,c,d)=>(s.addEventListener(l,c,d),()=>s.removeEventListener(l,c,d)),i=V(()=>{const s=$t(ye(t[0])).filter(l=>l!=null);return s.every(l=>typeof l!="string")?s:void 0}),a=_a(()=>{var s,l;return[(l=(s=i.value)==null?void 0:s.map(c=>mo(c)))!=null?l:[ut].filter(c=>c!=null),$t(ye(i.value?t[1]:t[0])),$t(J(i.value?t[2]:t[1])),ye(i.value?t[3]:t[2])]},([s,l,c,d])=>{if(r(),!(s!=null&&s.length)||!(l!=null&&l.length)||!(c!=null&&c.length))return;const h=pa(d)?{...d}:d;e.push(...s.flatMap(f=>l.flatMap(u=>c.map(g=>n(f,u,g,h)))))},{flush:"post"}),o=()=>{a(),r()};return Wr(r),o}function Sa(t){return typeof t=="function"?t:typeof t=="string"?e=>e.key===t:Array.isArray(t)?e=>t.includes(e.key):()=>!0}function Tt(...t){let e,r,n={};t.length===3?(e=t[0],r=t[1],n=t[2]):t.length===2?typeof t[1]=="object"?(e=!0,r=t[0],n=t[1]):(e=t[0],r=t[1]):(e=!0,r=t[0]);const{target:i=ut,eventName:a="keydown",passive:o=!1,dedupe:s=!1}=n,l=Sa(e);return xt(i,a,d=>{d.repeat&&ye(s)||l(d)&&r(d)},o)}const Ct=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{},It="__vueuse_ssr_handlers__",Aa=Ta();function Ta(){return It in Ct||(Ct[It]=Ct[It]||{}),Ct[It]}function Ca(t,e){return Aa[t]||e}function Ia(t){return t==null?"any":t instanceof Set?"set":t instanceof Map?"map":t instanceof Date?"date":typeof t=="boolean"?"boolean":typeof t=="string"?"string":typeof t=="object"?"object":Number.isNaN(t)?"any":"number"}const La={boolean:{read:t=>t==="true",write:t=>String(t)},object:{read:t=>JSON.parse(t),write:t=>JSON.stringify(t)},number:{read:t=>Number.parseFloat(t),write:t=>String(t)},any:{read:t=>t,write:t=>String(t)},string:{read:t=>t,write:t=>String(t)},map:{read:t=>new Map(JSON.parse(t)),write:t=>JSON.stringify(Array.from(t.entries()))},set:{read:t=>new Set(JSON.parse(t)),write:t=>JSON.stringify(Array.from(t))},date:{read:t=>new Date(t),write:t=>t.toISOString()}},yn="vueuse-storage";function ho(t,e,r,n={}){var i;const{flush:a="pre",deep:o=!0,listenToStorageChanges:s=!0,writeDefaults:l=!0,mergeDefaults:c=!1,shallow:d,window:h=ut,eventFilter:f,onError:u=M=>{console.error(M)},initOnMounted:g}=n,x=(d?Re:ae)(typeof e=="function"?e():e),m=V(()=>ye(t));if(!r)try{r=Ca("getDefaultStorage",()=>{var M;return(M=ut)==null?void 0:M.localStorage})()}catch(M){u(M)}if(!r)return x;const p=ye(e),v=Ia(p),b=(i=n.serializer)!=null?i:La[v],{pause:y,resume:_}=xa(x,()=>F(x.value),{flush:a,deep:o,eventFilter:f});he(m,()=>H(),{flush:a}),h&&s&&Ea(()=>{r instanceof Storage?xt(h,"storage",H,{passive:!0}):xt(h,yn,ee),g&&H()}),g||H();function C(M,B){if(h){const N={key:m.value,oldValue:M,newValue:B,storageArea:r};h.dispatchEvent(r instanceof Storage?new StorageEvent("storage",N):new CustomEvent(yn,{detail:N}))}}function F(M){try{const B=r.getItem(m.value);if(M==null)C(B,null),r.removeItem(m.value);else{const N=b.write(M);B!==N&&(r.setItem(m.value,N),C(B,N))}}catch(B){u(B)}}function j(M){const B=M?M.newValue:r.getItem(m.value);if(B==null)return l&&p!=null&&r.setItem(m.value,b.write(p)),p;if(!M&&c){const N=b.read(B);return typeof c=="function"?c(N,p):v==="object"&&!Array.isArray(N)?{...p,...N}:N}else return typeof B!="string"?B:b.read(B)}function H(M){if(!(M&&M.storageArea!==r)){if(M&&M.key==null){x.value=p;return}if(!(M&&M.key!==m.value)){y();try{(M==null?void 0:M.newValue)!==b.write(x.value)&&(x.value=j(M))}catch(B){u(B)}finally{M?Me(_):_()}}}}function ee(M){H(M.detail)}return x}function fr(t){return typeof Window<"u"&&t instanceof Window?t.document.documentElement:typeof Document<"u"&&t instanceof Document?t.documentElement:t}function Ma(t,e,r={}){const{window:n=ut}=r;return ho(t,e,n==null?void 0:n.localStorage,r)}function po(t){const e=window.getComputedStyle(t);if(e.overflowX==="scroll"||e.overflowY==="scroll"||e.overflowX==="auto"&&t.clientWidth<t.scrollWidth||e.overflowY==="auto"&&t.clientHeight<t.scrollHeight)return!0;{const r=t.parentNode;return!r||r.tagName==="BODY"?!1:po(r)}}function Ra(t){const e=t||window.event,r=e.target;return po(r)?!1:e.touches.length>1?!0:(e.preventDefault&&e.preventDefault(),!1)}const mr=new WeakMap;function Fa(t,e=!1){const r=Re(e);let n=null,i="";he(uo(t),s=>{const l=fr(ye(s));if(l){const c=l;if(mr.get(c)||mr.set(c,c.style.overflow),c.style.overflow!=="hidden"&&(i=c.style.overflow),c.style.overflow==="hidden")return r.value=!0;if(r.value)return c.style.overflow="hidden"}},{immediate:!0});const a=()=>{const s=fr(ye(t));!s||r.value||(vn&&(n=xt(s,"touchmove",l=>{Ra(l)},{passive:!1})),s.style.overflow="hidden",r.value=!0)},o=()=>{const s=fr(ye(t));!s||!r.value||(vn&&(n==null||n()),s.style.overflow=i,mr.delete(s),r.value=!1)};return Wr(o),V({get(){return r.value},set(s){s?a():o()}})}function Oa(t,e,r={}){const{window:n=ut}=r;return ho(t,e,n==null?void 0:n.sessionStorage,r)}/*!
* tabbable 6.4.0
* @license MIT, https://github.com/focus-trap/tabbable/blob/master/LICENSE
*/var go=["input:not([inert]):not([inert] *)","select:not([inert]):not([inert] *)","textarea:not([inert]):not([inert] *)","a[href]:not([inert]):not([inert] *)","button:not([inert]):not([inert] *)","[tabindex]:not(slot):not([inert]):not([inert] *)","audio[controls]:not([inert]):not([inert] *)","video[controls]:not([inert]):not([inert] *)",'[contenteditable]:not([contenteditable="false"]):not([inert]):not([inert] *)',"details>summary:first-of-type:not([inert]):not([inert] *)","details:not([inert]):not([inert] *)"],Bt=go.join(","),vo=typeof Element>"u",Qe=vo?function(){}:Element.prototype.matches||Element.prototype.msMatchesSelector||Element.prototype.webkitMatchesSelector,Ut=!vo&&Element.prototype.getRootNode?function(t){var e;return t==null||(e=t.getRootNode)===null||e===void 0?void 0:e.call(t)}:function(t){return t==null?void 0:t.ownerDocument},Wt=function(e,r){var n;r===void 0&&(r=!0);var i=e==null||(n=e.getAttribute)===null||n===void 0?void 0:n.call(e,"inert"),a=i===""||i==="true",o=a||r&&e&&(typeof e.closest=="function"?e.closest("[inert]"):Wt(e.parentNode));return o},Pa=function(e){var r,n=e==null||(r=e.getAttribute)===null||r===void 0?void 0:r.call(e,"contenteditable");return n===""||n==="true"},bo=function(e,r,n){if(Wt(e))return[];var i=Array.prototype.slice.apply(e.querySelectorAll(Bt));return r&&Qe.call(e,Bt)&&i.unshift(e),i=i.filter(n),i},Kt=function(e,r,n){for(var i=[],a=Array.from(e);a.length;){var o=a.shift();if(!Wt(o,!1))if(o.tagName==="SLOT"){var s=o.assignedElements(),l=s.length?s:o.children,c=Kt(l,!0,n);n.flatten?i.push.apply(i,c):i.push({scopeParent:o,candidates:c})}else{var d=Qe.call(o,Bt);d&&n.filter(o)&&(r||!e.includes(o))&&i.push(o);var h=o.shadowRoot||typeof n.getShadowRoot=="function"&&n.getShadowRoot(o),f=!Wt(h,!1)&&(!n.shadowRootFilter||n.shadowRootFilter(o));if(h&&f){var u=Kt(h===!0?o.children:h.children,!0,n);n.flatten?i.push.apply(i,u):i.push({scopeParent:o,candidates:u})}else a.unshift.apply(a,o.children)}}return i},yo=function(e){return!isNaN(parseInt(e.getAttribute("tabindex"),10))},Ye=function(e){if(!e)throw new Error("No node provided");return e.tabIndex<0&&(/^(AUDIO|VIDEO|DETAILS)$/.test(e.tagName)||Pa(e))&&!yo(e)?0:e.tabIndex},Na=function(e,r){var n=Ye(e);return n<0&&r&&!yo(e)?0:n},Da=function(e,r){return e.tabIndex===r.tabIndex?e.documentOrder-r.documentOrder:e.tabIndex-r.tabIndex},wo=function(e){return e.tagName==="INPUT"},za=function(e){return wo(e)&&e.type==="hidden"},$a=function(e){var r=e.tagName==="DETAILS"&&Array.prototype.slice.apply(e.children).some(function(n){return n.tagName==="SUMMARY"});return r},Va=function(e,r){for(var n=0;n<e.length;n++)if(e[n].checked&&e[n].form===r)return e[n]},Ha=function(e){if(!e.name)return!0;var r=e.form||Ut(e),n=function(s){return r.querySelectorAll('input[type="radio"][name="'+s+'"]')},i;if(typeof window<"u"&&typeof window.CSS<"u"&&typeof window.CSS.escape=="function")i=n(window.CSS.escape(e.name));else try{i=n(e.name)}catch(o){return console.error("Looks like you have a radio button with a name attribute containing invalid CSS selector characters and need the CSS.escape polyfill: %s",o.message),!1}var a=Va(i,e.form);return!a||a===e},qa=function(e){return wo(e)&&e.type==="radio"},ja=function(e){return qa(e)&&!Ha(e)},Ba=function(e){var r,n=e&&Ut(e),i=(r=n)===null||r===void 0?void 0:r.host,a=!1;if(n&&n!==e){var o,s,l;for(a=!!((o=i)!==null&&o!==void 0&&(s=o.ownerDocument)!==null&&s!==void 0&&s.contains(i)||e!=null&&(l=e.ownerDocument)!==null&&l!==void 0&&l.contains(e));!a&&i;){var c,d,h;n=Ut(i),i=(c=n)===null||c===void 0?void 0:c.host,a=!!((d=i)!==null&&d!==void 0&&(h=d.ownerDocument)!==null&&h!==void 0&&h.contains(i))}}return a},wn=function(e){var r=e.getBoundingClientRect(),n=r.width,i=r.height;return n===0&&i===0},Ua=function(e,r){var n=r.displayCheck,i=r.getShadowRoot;if(n==="full-native"&&"checkVisibility"in e){var a=e.checkVisibility({checkOpacity:!1,opacityProperty:!1,contentVisibilityAuto:!0,visibilityProperty:!0,checkVisibilityCSS:!0});return!a}if(getComputedStyle(e).visibility==="hidden")return!0;var o=Qe.call(e,"details>summary:first-of-type"),s=o?e.parentElement:e;if(Qe.call(s,"details:not([open]) *"))return!0;if(!n||n==="full"||n==="full-native"||n==="legacy-full"){if(typeof i=="function"){for(var l=e;e;){var c=e.parentElement,d=Ut(e);if(c&&!c.shadowRoot&&i(c)===!0)return wn(e);e.assignedSlot?e=e.assignedSlot:!c&&d!==e.ownerDocument?e=d.host:e=c}e=l}if(Ba(e))return!e.getClientRects().length;if(n!=="legacy-full")return!0}else if(n==="non-zero-area")return wn(e);return!1},Wa=function(e){if(/^(INPUT|BUTTON|SELECT|TEXTAREA)$/.test(e.tagName))for(var r=e.parentElement;r;){if(r.tagName==="FIELDSET"&&r.disabled){for(var n=0;n<r.children.length;n++){var i=r.children.item(n);if(i.tagName==="LEGEND")return Qe.call(r,"fieldset[disabled] *")?!0:!i.contains(e)}return!0}r=r.parentElement}return!1},Jt=function(e,r){return!(r.disabled||za(r)||Ua(r,e)||$a(r)||Wa(r))},Cr=function(e,r){return!(ja(r)||Ye(r)<0||!Jt(e,r))},Ka=function(e){var r=parseInt(e.getAttribute("tabindex"),10);return!!(isNaN(r)||r>=0)},xo=function(e){var r=[],n=[];return e.forEach(function(i,a){var o=!!i.scopeParent,s=o?i.scopeParent:i,l=Na(s,o),c=o?xo(i.candidates):s;l===0?o?r.push.apply(r,c):r.push(s):n.push({documentOrder:a,tabIndex:l,item:i,isScope:o,content:c})}),n.sort(Da).reduce(function(i,a){return a.isScope?i.push.apply(i,a.content):i.push(a.content),i},[]).concat(r)},Ja=function(e,r){r=r||{};var n;return r.getShadowRoot?n=Kt([e],r.includeContainer,{filter:Cr.bind(null,r),flatten:!1,getShadowRoot:r.getShadowRoot,shadowRootFilter:Ka}):n=bo(e,r.includeContainer,Cr.bind(null,r)),xo(n)},Ga=function(e,r){r=r||{};var n;return r.getShadowRoot?n=Kt([e],r.includeContainer,{filter:Jt.bind(null,r),flatten:!0,getShadowRoot:r.getShadowRoot}):n=bo(e,r.includeContainer,Jt.bind(null,r)),n},tt=function(e,r){if(r=r||{},!e)throw new Error("No node provided");return Qe.call(e,Bt)===!1?!1:Cr(r,e)},Ya=go.concat("iframe:not([inert]):not([inert] *)").join(","),hr=function(e,r){if(r=r||{},!e)throw new Error("No node provided");return Qe.call(e,Ya)===!1?!1:Jt(r,e)};/*!
* focus-trap 7.8.0
* @license MIT, https://github.com/focus-trap/focus-trap/blob/master/LICENSE
*/function Ir(t,e){(e==null||e>t.length)&&(e=t.length);for(var r=0,n=Array(e);r<e;r++)n[r]=t[r];return n}function Za(t){if(Array.isArray(t))return Ir(t)}function xn(t,e){var r=typeof Symbol<"u"&&t[Symbol.iterator]||t["@@iterator"];if(!r){if(Array.isArray(t)||(r=Eo(t))||e){r&&(t=r);var n=0,i=function(){};return{s:i,n:function(){return n>=t.length?{done:!0}:{done:!1,value:t[n++]}},e:function(l){throw l},f:i}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var a,o=!0,s=!1;return{s:function(){r=r.call(t)},n:function(){var l=r.next();return o=l.done,l},e:function(l){s=!0,a=l},f:function(){try{o||r.return==null||r.return()}finally{if(s)throw a}}}}function Qa(t,e,r){return(e=ns(e))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}function Xa(t){if(typeof Symbol<"u"&&t[Symbol.iterator]!=null||t["@@iterator"]!=null)return Array.from(t)}function es(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function En(t,e){var r=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);e&&(n=n.filter(function(i){return Object.getOwnPropertyDescriptor(t,i).enumerable})),r.push.apply(r,n)}return r}function kn(t){for(var e=1;e<arguments.length;e++){var r=arguments[e]!=null?arguments[e]:{};e%2?En(Object(r),!0).forEach(function(n){Qa(t,n,r[n])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(r)):En(Object(r)).forEach(function(n){Object.defineProperty(t,n,Object.getOwnPropertyDescriptor(r,n))})}return t}function ts(t){return Za(t)||Xa(t)||Eo(t)||es()}function rs(t,e){if(typeof t!="object"||!t)return t;var r=t[Symbol.toPrimitive];if(r!==void 0){var n=r.call(t,e);if(typeof n!="object")return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}function ns(t){var e=rs(t,"string");return typeof e=="symbol"?e:e+""}function Eo(t,e){if(t){if(typeof t=="string")return Ir(t,e);var r={}.toString.call(t).slice(8,-1);return r==="Object"&&t.constructor&&(r=t.constructor.name),r==="Map"||r==="Set"?Array.from(t):r==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?Ir(t,e):void 0}}var Ve={getActiveTrap:function(e){return(e==null?void 0:e.length)>0?e[e.length-1]:null},activateTrap:function(e,r){var n=Ve.getActiveTrap(e);r!==n&&Ve.pauseTrap(e);var i=e.indexOf(r);i===-1||e.splice(i,1),e.push(r)},deactivateTrap:function(e,r){var n=e.indexOf(r);n!==-1&&e.splice(n,1),Ve.unpauseTrap(e)},pauseTrap:function(e){var r=Ve.getActiveTrap(e);r==null||r._setPausedState(!0)},unpauseTrap:function(e){var r=Ve.getActiveTrap(e);r&&!r._isManuallyPaused()&&r._setPausedState(!1)}},os=function(e){return e.tagName&&e.tagName.toLowerCase()==="input"&&typeof e.select=="function"},is=function(e){return(e==null?void 0:e.key)==="Escape"||(e==null?void 0:e.key)==="Esc"||(e==null?void 0:e.keyCode)===27},bt=function(e){return(e==null?void 0:e.key)==="Tab"||(e==null?void 0:e.keyCode)===9},as=function(e){return bt(e)&&!e.shiftKey},ss=function(e){return bt(e)&&e.shiftKey},_n=function(e){return setTimeout(e,0)},ht=function(e){for(var r=arguments.length,n=new Array(r>1?r-1:0),i=1;i<r;i++)n[i-1]=arguments[i];return typeof e=="function"?e.apply(void 0,n):e},Lt=function(e){return e.target.shadowRoot&&typeof e.composedPath=="function"?e.composedPath()[0]:e.target},ls=[],cs=function(e,r){var n=(r==null?void 0:r.document)||document,i=(r==null?void 0:r.trapStack)||ls,a=kn({returnFocusOnDeactivate:!0,escapeDeactivates:!0,delayInitialFocus:!0,isolateSubtrees:!1,isKeyForward:as,isKeyBackward:ss},r),o={containers:[],containerGroups:[],tabbableGroups:[],adjacentElements:new Set,alreadySilent:new Set,nodeFocusedBeforeActivation:null,mostRecentlyFocusedNode:null,active:!1,paused:!1,manuallyPaused:!1,delayInitialFocusTimer:void 0,recentNavEvent:void 0},s,l=function(w,A,P){return w&&w[A]!==void 0?w[A]:a[P||A]},c=function(w,A){var P=typeof(A==null?void 0:A.composedPath)=="function"?A.composedPath():void 0;return o.containerGroups.findIndex(function(W){var $=W.container,Z=W.tabbableNodes;return $.contains(w)||(P==null?void 0:P.includes($))||Z.find(function(U){return U===w})})},d=function(w){var A=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},P=A.hasFallback,W=P===void 0?!1:P,$=A.params,Z=$===void 0?[]:$,U=a[w];if(typeof U=="function"&&(U=U.apply(void 0,ts(Z))),U===!0&&(U=void 0),!U){if(U===void 0||U===!1)return U;throw new Error("`".concat(w,"` was specified but was not a node, or did not return a node"))}var I=U;if(typeof U=="string"){try{I=n.querySelector(U)}catch(T){throw new Error("`".concat(w,'` appears to be an invalid selector; error="').concat(T.message,'"'))}if(!I&&!W)throw new Error("`".concat(w,"` as selector refers to no known node"))}return I},h=function(){var w=d("initialFocus",{hasFallback:!0});if(w===!1)return!1;if(w===void 0||w&&!hr(w,a.tabbableOptions))if(c(n.activeElement)>=0)w=n.activeElement;else{var A=o.tabbableGroups[0],P=A&&A.firstTabbableNode;w=P||d("fallbackFocus")}else w===null&&(w=d("fallbackFocus"));if(!w)throw new Error("Your focus-trap needs to have at least one focusable element");return w},f=function(){if(o.containerGroups=o.containers.map(function(w){var A=Ja(w,a.tabbableOptions),P=Ga(w,a.tabbableOptions),W=A.length>0?A[0]:void 0,$=A.length>0?A[A.length-1]:void 0,Z=P.find(function(T){return tt(T)}),U=P.slice().reverse().find(function(T){return tt(T)}),I=!!A.find(function(T){return Ye(T)>0});return{container:w,tabbableNodes:A,focusableNodes:P,posTabIndexesFound:I,firstTabbableNode:W,lastTabbableNode:$,firstDomTabbableNode:Z,lastDomTabbableNode:U,nextTabbableNode:function(G){var te=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!0,z=A.indexOf(G);return z<0?te?P.slice(P.indexOf(G)+1).find(function(Q){return tt(Q)}):P.slice(0,P.indexOf(G)).reverse().find(function(Q){return tt(Q)}):A[z+(te?1:-1)]}}}),o.tabbableGroups=o.containerGroups.filter(function(w){return w.tabbableNodes.length>0}),o.tabbableGroups.length<=0&&!d("fallbackFocus"))throw new Error("Your focus-trap must have at least one container with at least one tabbable node in it at all times");if(o.containerGroups.find(function(w){return w.posTabIndexesFound})&&o.containerGroups.length>1)throw new Error("At least one node with a positive tabindex was found in one of your focus-trap's multiple containers. Positive tabindexes are only supported in single-container focus-traps.")},u=function(w){var A=w.activeElement;if(A)return A.shadowRoot&&A.shadowRoot.activeElement!==null?u(A.shadowRoot):A},g=function(w){if(w!==!1&&w!==u(document)){if(!w||!w.focus){g(h());return}w.focus({preventScroll:!!a.preventScroll}),o.mostRecentlyFocusedNode=w,os(w)&&w.select()}},x=function(w){var A=d("setReturnFocus",{params:[w]});return A||(A===!1?!1:w)},m=function(w){var A=w.target,P=w.event,W=w.isBackward,$=W===void 0?!1:W;A=A||Lt(P),f();var Z=null;if(o.tabbableGroups.length>0){var U=c(A,P),I=U>=0?o.containerGroups[U]:void 0;if(U<0)$?Z=o.tabbableGroups[o.tabbableGroups.length-1].lastTabbableNode:Z=o.tabbableGroups[0].firstTabbableNode;else if($){var T=o.tabbableGroups.findIndex(function(X){var se=X.firstTabbableNode;return A===se});if(T<0&&(I.container===A||hr(A,a.tabbableOptions)&&!tt(A,a.tabbableOptions)&&!I.nextTabbableNode(A,!1))&&(T=U),T>=0){var G=T===0?o.tabbableGroups.length-1:T-1,te=o.tabbableGroups[G];Z=Ye(A)>=0?te.lastTabbableNode:te.lastDomTabbableNode}else bt(P)||(Z=I.nextTabbableNode(A,!1))}else{var z=o.tabbableGroups.findIndex(function(X){var se=X.lastTabbableNode;return A===se});if(z<0&&(I.container===A||hr(A,a.tabbableOptions)&&!tt(A,a.tabbableOptions)&&!I.nextTabbableNode(A))&&(z=U),z>=0){var Q=z===o.tabbableGroups.length-1?0:z+1,ne=o.tabbableGroups[Q];Z=Ye(A)>=0?ne.firstTabbableNode:ne.firstDomTabbableNode}else bt(P)||(Z=I.nextTabbableNode(A))}}else Z=d("fallbackFocus");return Z},p=function(w){var A=Lt(w);if(!(c(A,w)>=0)){if(ht(a.clickOutsideDeactivates,w)){s.deactivate({returnFocus:a.returnFocusOnDeactivate});return}ht(a.allowOutsideClick,w)||w.preventDefault()}},v=function(w){var A=Lt(w),P=c(A,w)>=0;if(P||A instanceof Document)P&&(o.mostRecentlyFocusedNode=A);else{w.stopImmediatePropagation();var W,$=!0;if(o.mostRecentlyFocusedNode)if(Ye(o.mostRecentlyFocusedNode)>0){var Z=c(o.mostRecentlyFocusedNode),U=o.containerGroups[Z].tabbableNodes;if(U.length>0){var I=U.findIndex(function(T){return T===o.mostRecentlyFocusedNode});I>=0&&(a.isKeyForward(o.recentNavEvent)?I+1<U.length&&(W=U[I+1],$=!1):I-1>=0&&(W=U[I-1],$=!1))}}else o.containerGroups.some(function(T){return T.tabbableNodes.some(function(G){return Ye(G)>0})})||($=!1);else $=!1;$&&(W=m({target:o.mostRecentlyFocusedNode,isBackward:a.isKeyBackward(o.recentNavEvent)})),g(W||o.mostRecentlyFocusedNode||h())}o.recentNavEvent=void 0},b=function(w){var A=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;o.recentNavEvent=w;var P=m({event:w,isBackward:A});P&&(bt(w)&&w.preventDefault(),g(P))},y=function(w){(a.isKeyForward(w)||a.isKeyBackward(w))&&b(w,a.isKeyBackward(w))},_=function(w){is(w)&&ht(a.escapeDeactivates,w)!==!1&&(w.preventDefault(),s.deactivate())},C=function(w){var A=Lt(w);c(A,w)>=0||ht(a.clickOutsideDeactivates,w)||ht(a.allowOutsideClick,w)||(w.preventDefault(),w.stopImmediatePropagation())},F=function(){if(o.active)return Ve.activateTrap(i,s),o.delayInitialFocusTimer=a.delayInitialFocus?_n(function(){g(h())}):g(h()),n.addEventListener("focusin",v,!0),n.addEventListener("mousedown",p,{capture:!0,passive:!1}),n.addEventListener("touchstart",p,{capture:!0,passive:!1}),n.addEventListener("click",C,{capture:!0,passive:!1}),n.addEventListener("keydown",y,{capture:!0,passive:!1}),n.addEventListener("keydown",_),s},j=function(w){o.active&&!o.paused&&s._setSubtreeIsolation(!1),o.adjacentElements.clear(),o.alreadySilent.clear();var A=new Set,P=new Set,W=xn(w),$;try{for(W.s();!($=W.n()).done;){var Z=$.value;A.add(Z);for(var U=typeof ShadowRoot<"u"&&Z.getRootNode()instanceof ShadowRoot,I=Z;I;){A.add(I);var T=I.parentElement,G=[];T?G=T.children:!T&&U&&(G=I.getRootNode().children,T=I.getRootNode().host,U=typeof ShadowRoot<"u"&&T.getRootNode()instanceof ShadowRoot);var te=xn(G),z;try{for(te.s();!(z=te.n()).done;){var Q=z.value;P.add(Q)}}catch(ne){te.e(ne)}finally{te.f()}I=T}}}catch(ne){W.e(ne)}finally{W.f()}A.forEach(function(ne){P.delete(ne)}),o.adjacentElements=P},H=function(){if(o.active)return n.removeEventListener("focusin",v,!0),n.removeEventListener("mousedown",p,!0),n.removeEventListener("touchstart",p,!0),n.removeEventListener("click",C,!0),n.removeEventListener("keydown",y,!0),n.removeEventListener("keydown",_),s},ee=function(w){var A=w.some(function(P){var W=Array.from(P.removedNodes);return W.some(function($){return $===o.mostRecentlyFocusedNode})});A&&g(h())},M=typeof window<"u"&&"MutationObserver"in window?new MutationObserver(ee):void 0,B=function(){M&&(M.disconnect(),o.active&&!o.paused&&o.containers.map(function(w){M.observe(w,{subtree:!0,childList:!0})}))};return s={get active(){return o.active},get paused(){return o.paused},activate:function(w){if(o.active)return this;var A=l(w,"onActivate"),P=l(w,"onPostActivate"),W=l(w,"checkCanFocusTrap"),$=Ve.getActiveTrap(i),Z=!1;if($&&!$.paused){var U;(U=$._setSubtreeIsolation)===null||U===void 0||U.call($,!1),Z=!0}try{W||f(),o.active=!0,o.paused=!1,o.nodeFocusedBeforeActivation=u(n),A==null||A();var I=function(){W&&f(),F(),B(),a.isolateSubtrees&&s._setSubtreeIsolation(!0),P==null||P()};if(W)return W(o.containers.concat()).then(I,I),this;I()}catch(G){if($===Ve.getActiveTrap(i)&&Z){var T;(T=$._setSubtreeIsolation)===null||T===void 0||T.call($,!0)}throw G}return this},deactivate:function(w){if(!o.active)return this;var A=kn({onDeactivate:a.onDeactivate,onPostDeactivate:a.onPostDeactivate,checkCanReturnFocus:a.checkCanReturnFocus},w);clearTimeout(o.delayInitialFocusTimer),o.delayInitialFocusTimer=void 0,o.paused||s._setSubtreeIsolation(!1),o.alreadySilent.clear(),H(),o.active=!1,o.paused=!1,B(),Ve.deactivateTrap(i,s);var P=l(A,"onDeactivate"),W=l(A,"onPostDeactivate"),$=l(A,"checkCanReturnFocus"),Z=l(A,"returnFocus","returnFocusOnDeactivate");P==null||P();var U=function(){_n(function(){Z&&g(x(o.nodeFocusedBeforeActivation)),W==null||W()})};return Z&&$?($(x(o.nodeFocusedBeforeActivation)).then(U,U),this):(U(),this)},pause:function(w){return o.active?(o.manuallyPaused=!0,this._setPausedState(!0,w)):this},unpause:function(w){return o.active?(o.manuallyPaused=!1,i[i.length-1]!==this?this:this._setPausedState(!1,w)):this},updateContainerElements:function(w){var A=[].concat(w).filter(Boolean);return o.containers=A.map(function(P){return typeof P=="string"?n.querySelector(P):P}),a.isolateSubtrees&&j(o.containers),o.active&&(f(),a.isolateSubtrees&&!o.paused&&s._setSubtreeIsolation(!0)),B(),this}},Object.defineProperties(s,{_isManuallyPaused:{value:function(){return o.manuallyPaused}},_setPausedState:{value:function(w,A){if(o.paused===w)return this;if(o.paused=w,w){var P=l(A,"onPause"),W=l(A,"onPostPause");P==null||P(),H(),B(),s._setSubtreeIsolation(!1),W==null||W()}else{var $=l(A,"onUnpause"),Z=l(A,"onPostUnpause");$==null||$(),s._setSubtreeIsolation(!0),f(),F(),B(),Z==null||Z()}return this}},_setSubtreeIsolation:{value:function(w){a.isolateSubtrees&&o.adjacentElements.forEach(function(A){var P;if(w)switch(a.isolateSubtrees){case"aria-hidden":(A.ariaHidden==="true"||((P=A.getAttribute("aria-hidden"))===null||P===void 0?void 0:P.toLowerCase())==="true")&&o.alreadySilent.add(A),A.setAttribute("aria-hidden","true");break;default:(A.inert||A.hasAttribute("inert"))&&o.alreadySilent.add(A),A.setAttribute("inert",!0);break}else if(!o.alreadySilent.has(A))switch(a.isolateSubtrees){case"aria-hidden":A.removeAttribute("aria-hidden");break;default:A.removeAttribute("inert");break}})}}}),s.updateContainerElements(e),s};function us(t,e={}){let r;const{immediate:n,...i}=e,a=Re(!1),o=Re(!1),s=f=>r&&r.activate(f),l=f=>r&&r.deactivate(f),c=()=>{r&&(r.pause(),o.value=!0)},d=()=>{r&&(r.unpause(),o.value=!1)},h=V(()=>{const f=ye(t);return $t(f).map(u=>{const g=ye(u);return typeof g=="string"?g:mo(g)}).filter(ma)});return he(h,f=>{f.length&&(r=cs(f,{...i,onActivate(){a.value=!0,e.onActivate&&e.onActivate()},onDeactivate(){a.value=!1,e.onDeactivate&&e.onDeactivate()}}),n&&s())},{flush:"post"}),Wr(()=>l()),{hasFocus:a,isPaused:o,activate:s,deactivate:l,pause:c,unpause:d}}var ds=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function fs(t){return t&&t.__esModule&&Object.prototype.hasOwnProperty.call(t,"default")?t.default:t}var ko={exports:{}};/*!***************************************************
* mark.js v8.11.1
* https://markjs.io/
* Copyright (c) 2014–2018, Julian Kühnel
* Released under the MIT license https://git.io/vwTVl
*****************************************************/(function(t,e){(function(r,n){t.exports=n()})(ds,function(){class r{constructor(o,s=!0,l=[],c=5e3){this.ctx=o,this.iframes=s,this.exclude=l,this.iframesTimeout=c}static matches(o,s){const l=typeof s=="string"?[s]:s,c=o.matches||o.matchesSelector||o.msMatchesSelector||o.mozMatchesSelector||o.oMatchesSelector||o.webkitMatchesSelector;if(c){let d=!1;return l.every(h=>c.call(o,h)?(d=!0,!1):!0),d}else return!1}getContexts(){let o,s=[];return typeof this.ctx>"u"||!this.ctx?o=[]:NodeList.prototype.isPrototypeOf(this.ctx)?o=Array.prototype.slice.call(this.ctx):Array.isArray(this.ctx)?o=this.ctx:typeof this.ctx=="string"?o=Array.prototype.slice.call(document.querySelectorAll(this.ctx)):o=[this.ctx],o.forEach(l=>{const c=s.filter(d=>d.contains(l)).length>0;s.indexOf(l)===-1&&!c&&s.push(l)}),s}getIframeContents(o,s,l=()=>{}){let c;try{const d=o.contentWindow;if(c=d.document,!d||!c)throw new Error("iframe inaccessible")}catch{l()}c&&s(c)}isIframeBlank(o){const s="about:blank",l=o.getAttribute("src").trim();return o.contentWindow.location.href===s&&l!==s&&l}observeIframeLoad(o,s,l){let c=!1,d=null;const h=()=>{if(!c){c=!0,clearTimeout(d);try{this.isIframeBlank(o)||(o.removeEventListener("load",h),this.getIframeContents(o,s,l))}catch{l()}}};o.addEventListener("load",h),d=setTimeout(h,this.iframesTimeout)}onIframeReady(o,s,l){try{o.contentWindow.document.readyState==="complete"?this.isIframeBlank(o)?this.observeIframeLoad(o,s,l):this.getIframeContents(o,s,l):this.observeIframeLoad(o,s,l)}catch{l()}}waitForIframes(o,s){let l=0;this.forEachIframe(o,()=>!0,c=>{l++,this.waitForIframes(c.querySelector("html"),()=>{--l||s()})},c=>{c||s()})}forEachIframe(o,s,l,c=()=>{}){let d=o.querySelectorAll("iframe"),h=d.length,f=0;d=Array.prototype.slice.call(d);const u=()=>{--h<=0&&c(f)};h||u(),d.forEach(g=>{r.matches(g,this.exclude)?u():this.onIframeReady(g,x=>{s(g)&&(f++,l(x)),u()},u)})}createIterator(o,s,l){return document.createNodeIterator(o,s,l,!1)}createInstanceOnIframe(o){return new r(o.querySelector("html"),this.iframes)}compareNodeIframe(o,s,l){const c=o.compareDocumentPosition(l),d=Node.DOCUMENT_POSITION_PRECEDING;if(c&d)if(s!==null){const h=s.compareDocumentPosition(l),f=Node.DOCUMENT_POSITION_FOLLOWING;if(h&f)return!0}else return!0;return!1}getIteratorNode(o){const s=o.previousNode();let l;return s===null?l=o.nextNode():l=o.nextNode()&&o.nextNode(),{prevNode:s,node:l}}checkIframeFilter(o,s,l,c){let d=!1,h=!1;return c.forEach((f,u)=>{f.val===l&&(d=u,h=f.handled)}),this.compareNodeIframe(o,s,l)?(d===!1&&!h?c.push({val:l,handled:!0}):d!==!1&&!h&&(c[d].handled=!0),!0):(d===!1&&c.push({val:l,handled:!1}),!1)}handleOpenIframes(o,s,l,c){o.forEach(d=>{d.handled||this.getIframeContents(d.val,h=>{this.createInstanceOnIframe(h).forEachNode(s,l,c)})})}iterateThroughNodes(o,s,l,c,d){const h=this.createIterator(s,o,c);let f=[],u=[],g,x,m=()=>({prevNode:x,node:g}=this.getIteratorNode(h),g);for(;m();)this.iframes&&this.forEachIframe(s,p=>this.checkIframeFilter(g,x,p,f),p=>{this.createInstanceOnIframe(p).forEachNode(o,v=>u.push(v),c)}),u.push(g);u.forEach(p=>{l(p)}),this.iframes&&this.handleOpenIframes(f,o,l,c),d()}forEachNode(o,s,l,c=()=>{}){const d=this.getContexts();let h=d.length;h||c(),d.forEach(f=>{const u=()=>{this.iterateThroughNodes(o,f,s,l,()=>{--h<=0&&c()})};this.iframes?this.waitForIframes(f,u):u()})}}class n{constructor(o){this.ctx=o,this.ie=!1;const s=window.navigator.userAgent;(s.indexOf("MSIE")>-1||s.indexOf("Trident")>-1)&&(this.ie=!0)}set opt(o){this._opt=Object.assign({},{element:"",className:"",exclude:[],iframes:!1,iframesTimeout:5e3,separateWordSearch:!0,diacritics:!0,synonyms:{},accuracy:"partially",acrossElements:!1,caseSensitive:!1,ignoreJoiners:!1,ignoreGroups:0,ignorePunctuation:[],wildcards:"disabled",each:()=>{},noMatch:()=>{},filter:()=>!0,done:()=>{},debug:!1,log:window.console},o)}get opt(){return this._opt}get iterator(){return new r(this.ctx,this.opt.iframes,this.opt.exclude,this.opt.iframesTimeout)}log(o,s="debug"){const l=this.opt.log;this.opt.debug&&typeof l=="object"&&typeof l[s]=="function"&&l[s](`mark.js: ${o}`)}escapeStr(o){return o.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")}createRegExp(o){return this.opt.wildcards!=="disabled"&&(o=this.setupWildcardsRegExp(o)),o=this.escapeStr(o),Object.keys(this.opt.synonyms).length&&(o=this.createSynonymsRegExp(o)),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.setupIgnoreJoinersRegExp(o)),this.opt.diacritics&&(o=this.createDiacriticsRegExp(o)),o=this.createMergedBlanksRegExp(o),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.createJoinersRegExp(o)),this.opt.wildcards!=="disabled"&&(o=this.createWildcardsRegExp(o)),o=this.createAccuracyRegExp(o),o}createSynonymsRegExp(o){const s=this.opt.synonyms,l=this.opt.caseSensitive?"":"i",c=this.opt.ignoreJoiners||this.opt.ignorePunctuation.length?"\0":"";for(let d in s)if(s.hasOwnProperty(d)){const h=s[d],f=this.opt.wildcards!=="disabled"?this.setupWildcardsRegExp(d):this.escapeStr(d),u=this.opt.wildcards!=="disabled"?this.setupWildcardsRegExp(h):this.escapeStr(h);f!==""&&u!==""&&(o=o.replace(new RegExp(`(${this.escapeStr(f)}|${this.escapeStr(u)})`,`gm${l}`),c+`(${this.processSynomyms(f)}|${this.processSynomyms(u)})`+c))}return o}processSynomyms(o){return(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(o=this.setupIgnoreJoinersRegExp(o)),o}setupWildcardsRegExp(o){return o=o.replace(/(?:\\)*\?/g,s=>s.charAt(0)==="\\"?"?":""),o.replace(/(?:\\)*\*/g,s=>s.charAt(0)==="\\"?"*":"")}createWildcardsRegExp(o){let s=this.opt.wildcards==="withSpaces";return o.replace(/\u0001/g,s?"[\\S\\s]?":"\\S?").replace(/\u0002/g,s?"[\\S\\s]*?":"\\S*")}setupIgnoreJoinersRegExp(o){return o.replace(/[^(|)\\]/g,(s,l,c)=>{let d=c.charAt(l+1);return/[(|)\\]/.test(d)||d===""?s:s+"\0"})}createJoinersRegExp(o){let s=[];const l=this.opt.ignorePunctuation;return Array.isArray(l)&&l.length&&s.push(this.escapeStr(l.join(""))),this.opt.ignoreJoiners&&s.push("\\u00ad\\u200b\\u200c\\u200d"),s.length?o.split(/\u0000+/).join(`[${s.join("")}]*`):o}createDiacriticsRegExp(o){const s=this.opt.caseSensitive?"":"i",l=this.opt.caseSensitive?["aàáảãạăằắẳẵặâầấẩẫậäåāą","AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćč","CÇĆČ","dđď","DĐĎ","eèéẻẽẹêềếểễệëěēę","EÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïī","IÌÍỈĨỊÎÏĪ","lł","LŁ","nñňń","NÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøō","OÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rř","RŘ","sšśșş","SŠŚȘŞ","tťțţ","TŤȚŢ","uùúủũụưừứửữựûüůū","UÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿ","YÝỲỶỸỴŸ","zžżź","ZŽŻŹ"]:["aàáảãạăằắẳẵặâầấẩẫậäåāąAÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćčCÇĆČ","dđďDĐĎ","eèéẻẽẹêềếểễệëěēęEÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïīIÌÍỈĨỊÎÏĪ","lłLŁ","nñňńNÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøōOÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rřRŘ","sšśșşSŠŚȘŞ","tťțţTŤȚŢ","uùúủũụưừứửữựûüůūUÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿYÝỲỶỸỴŸ","zžżźZŽŻŹ"];let c=[];return o.split("").forEach(d=>{l.every(h=>{if(h.indexOf(d)!==-1){if(c.indexOf(h)>-1)return!1;o=o.replace(new RegExp(`[${h}]`,`gm${s}`),`[${h}]`),c.push(h)}return!0})}),o}createMergedBlanksRegExp(o){return o.replace(/[\s]+/gmi,"[\\s]+")}createAccuracyRegExp(o){const s="!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~¡¿";let l=this.opt.accuracy,c=typeof l=="string"?l:l.value,d=typeof l=="string"?[]:l.limiters,h="";switch(d.forEach(f=>{h+=`|${this.escapeStr(f)}`}),c){case"partially":default:return`()(${o})`;case"complementary":return h="\\s"+(h||this.escapeStr(s)),`()([^${h}]*${o}[^${h}]*)`;case"exactly":return`(^|\\s${h})(${o})(?=$|\\s${h})`}}getSeparatedKeywords(o){let s=[];return o.forEach(l=>{this.opt.separateWordSearch?l.split(" ").forEach(c=>{c.trim()&&s.indexOf(c)===-1&&s.push(c)}):l.trim()&&s.indexOf(l)===-1&&s.push(l)}),{keywords:s.sort((l,c)=>c.length-l.length),length:s.length}}isNumeric(o){return Number(parseFloat(o))==o}checkRanges(o){if(!Array.isArray(o)||Object.prototype.toString.call(o[0])!=="[object Object]")return this.log("markRanges() will only accept an array of objects"),this.opt.noMatch(o),[];const s=[];let l=0;return o.sort((c,d)=>c.start-d.start).forEach(c=>{let{start:d,end:h,valid:f}=this.callNoMatchOnInvalidRanges(c,l);f&&(c.start=d,c.length=h-d,s.push(c),l=h)}),s}callNoMatchOnInvalidRanges(o,s){let l,c,d=!1;return o&&typeof o.start<"u"?(l=parseInt(o.start,10),c=l+parseInt(o.length,10),this.isNumeric(o.start)&&this.isNumeric(o.length)&&c-s>0&&c-l>0?d=!0:(this.log(`Ignoring invalid or overlapping range: ${JSON.stringify(o)}`),this.opt.noMatch(o))):(this.log(`Ignoring invalid range: ${JSON.stringify(o)}`),this.opt.noMatch(o)),{start:l,end:c,valid:d}}checkWhitespaceRanges(o,s,l){let c,d=!0,h=l.length,f=s-h,u=parseInt(o.start,10)-f;return u=u>h?h:u,c=u+parseInt(o.length,10),c>h&&(c=h,this.log(`End range automatically set to the max value of ${h}`)),u<0||c-u<0||u>h||c>h?(d=!1,this.log(`Invalid range: ${JSON.stringify(o)}`),this.opt.noMatch(o)):l.substring(u,c).replace(/\s+/g,"")===""&&(d=!1,this.log("Skipping whitespace only range: "+JSON.stringify(o)),this.opt.noMatch(o)),{start:u,end:c,valid:d}}getTextNodes(o){let s="",l=[];this.iterator.forEachNode(NodeFilter.SHOW_TEXT,c=>{l.push({start:s.length,end:(s+=c.textContent).length,node:c})},c=>this.matchesExclude(c.parentNode)?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT,()=>{o({value:s,nodes:l})})}matchesExclude(o){return r.matches(o,this.opt.exclude.concat(["script","style","title","head","html"]))}wrapRangeInTextNode(o,s,l){const c=this.opt.element?this.opt.element:"mark",d=o.splitText(s),h=d.splitText(l-s);let f=document.createElement(c);return f.setAttribute("data-markjs","true"),this.opt.className&&f.setAttribute("class",this.opt.className),f.textContent=d.textContent,d.parentNode.replaceChild(f,d),h}wrapRangeInMappedTextNode(o,s,l,c,d){o.nodes.every((h,f)=>{const u=o.nodes[f+1];if(typeof u>"u"||u.start>s){if(!c(h.node))return!1;const g=s-h.start,x=(l>h.end?h.end:l)-h.start,m=o.value.substr(0,h.start),p=o.value.substr(x+h.start);if(h.node=this.wrapRangeInTextNode(h.node,g,x),o.value=m+p,o.nodes.forEach((v,b)=>{b>=f&&(o.nodes[b].start>0&&b!==f&&(o.nodes[b].start-=x),o.nodes[b].end-=x)}),l-=x,d(h.node.previousSibling,h.start),l>h.end)s=h.end;else return!1}return!0})}wrapMatches(o,s,l,c,d){const h=s===0?0:s+1;this.getTextNodes(f=>{f.nodes.forEach(u=>{u=u.node;let g;for(;(g=o.exec(u.textContent))!==null&&g[h]!=="";){if(!l(g[h],u))continue;let x=g.index;if(h!==0)for(let m=1;m<h;m++)x+=g[m].length;u=this.wrapRangeInTextNode(u,x,x+g[h].length),c(u.previousSibling),o.lastIndex=0}}),d()})}wrapMatchesAcrossElements(o,s,l,c,d){const h=s===0?0:s+1;this.getTextNodes(f=>{let u;for(;(u=o.exec(f.value))!==null&&u[h]!=="";){let g=u.index;if(h!==0)for(let m=1;m<h;m++)g+=u[m].length;const x=g+u[h].length;this.wrapRangeInMappedTextNode(f,g,x,m=>l(u[h],m),(m,p)=>{o.lastIndex=p,c(m)})}d()})}wrapRangeFromIndex(o,s,l,c){this.getTextNodes(d=>{const h=d.value.length;o.forEach((f,u)=>{let{start:g,end:x,valid:m}=this.checkWhitespaceRanges(f,h,d.value);m&&this.wrapRangeInMappedTextNode(d,g,x,p=>s(p,f,d.value.substring(g,x),u),p=>{l(p,f)})}),c()})}unwrapMatches(o){const s=o.parentNode;let l=document.createDocumentFragment();for(;o.firstChild;)l.appendChild(o.removeChild(o.firstChild));s.replaceChild(l,o),this.ie?this.normalizeTextNode(s):s.normalize()}normalizeTextNode(o){if(o){if(o.nodeType===3)for(;o.nextSibling&&o.nextSibling.nodeType===3;)o.nodeValue+=o.nextSibling.nodeValue,o.parentNode.removeChild(o.nextSibling);else this.normalizeTextNode(o.firstChild);this.normalizeTextNode(o.nextSibling)}}markRegExp(o,s){this.opt=s,this.log(`Searching with expression "${o}"`);let l=0,c="wrapMatches";const d=h=>{l++,this.opt.each(h)};this.opt.acrossElements&&(c="wrapMatchesAcrossElements"),this[c](o,this.opt.ignoreGroups,(h,f)=>this.opt.filter(f,h,l),d,()=>{l===0&&this.opt.noMatch(o),this.opt.done(l)})}mark(o,s){this.opt=s;let l=0,c="wrapMatches";const{keywords:d,length:h}=this.getSeparatedKeywords(typeof o=="string"?[o]:o),f=this.opt.caseSensitive?"":"i",u=g=>{let x=new RegExp(this.createRegExp(g),`gm${f}`),m=0;this.log(`Searching with expression "${x}"`),this[c](x,1,(p,v)=>this.opt.filter(v,g,l,m),p=>{m++,l++,this.opt.each(p)},()=>{m===0&&this.opt.noMatch(g),d[h-1]===g?this.opt.done(l):u(d[d.indexOf(g)+1])})};this.opt.acrossElements&&(c="wrapMatchesAcrossElements"),h===0?this.opt.done(l):u(d[0])}markRanges(o,s){this.opt=s;let l=0,c=this.checkRanges(o);c&&c.length?(this.log("Starting to mark with the following ranges: "+JSON.stringify(c)),this.wrapRangeFromIndex(c,(d,h,f,u)=>this.opt.filter(d,h,f,u),(d,h)=>{l++,this.opt.each(d,h)},()=>{this.opt.done(l)})):this.opt.done(l)}unmark(o){this.opt=o;let s=this.opt.element?this.opt.element:"*";s+="[data-markjs]",this.opt.className&&(s+=`.${this.opt.className}`),this.log(`Removal selector "${s}"`),this.iterator.forEachNode(NodeFilter.SHOW_ELEMENT,l=>{this.unwrapMatches(l)},l=>{const c=r.matches(l,s),d=this.matchesExclude(l);return!c||d?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT},this.opt.done)}}function i(a){const o=new n(a);return this.mark=(s,l)=>(o.mark(s,l),this),this.markRegExp=(s,l)=>(o.markRegExp(s,l),this),this.markRanges=(s,l)=>(o.markRanges(s,l),this),this.unmark=s=>(o.unmark(s),this),this}return i})})(ko);var ms=ko.exports;const hs=fs(ms),ps="ENTRIES",_o="KEYS",So="VALUES",me="";class pr{constructor(e,r){const n=e._tree,i=Array.from(n.keys());this.set=e,this._type=r,this._path=i.length>0?[{node:n,keys:i}]:[]}next(){const e=this.dive();return this.backtrack(),e}dive(){if(this._path.length===0)return{done:!0,value:void 0};const{node:e,keys:r}=rt(this._path);if(rt(r)===me)return{done:!1,value:this.result()};const n=e.get(rt(r));return this._path.push({node:n,keys:Array.from(n.keys())}),this.dive()}backtrack(){if(this._path.length===0)return;const e=rt(this._path).keys;e.pop(),!(e.length>0)&&(this._path.pop(),this.backtrack())}key(){return this.set._prefix+this._path.map(({keys:e})=>rt(e)).filter(e=>e!==me).join("")}value(){return rt(this._path).node.get(me)}result(){switch(this._type){case So:return this.value();case _o:return this.key();default:return[this.key(),this.value()]}}[Symbol.iterator](){return this}}const rt=t=>t[t.length-1],gs=(t,e,r)=>{const n=new Map;if(e===void 0)return n;const i=e.length+1,a=i+r,o=new Uint8Array(a*i).fill(r+1);for(let s=0;s<i;++s)o[s]=s;for(let s=1;s<a;++s)o[s*i]=s;return Ao(t,e,r,n,o,1,i,""),n},Ao=(t,e,r,n,i,a,o,s)=>{const l=a*o;e:for(const c of t.keys())if(c===me){const d=i[l-1];d<=r&&n.set(s,[t.get(c),d])}else{let d=a;for(let h=0;h<c.length;++h,++d){const f=c[h],u=o*d,g=u-o;let x=i[u];const m=Math.max(0,d-r-1),p=Math.min(o-1,d+r);for(let v=m;v<p;++v){const b=f!==e[v],y=i[g+v]+ +b,_=i[g+v+1]+1,C=i[u+v]+1,F=i[u+v+1]=Math.min(y,_,C);F<x&&(x=F)}if(x>r)continue e}Ao(t.get(c),e,r,n,i,d,o,s+c)}};class Ue{constructor(e=new Map,r=""){this._size=void 0,this._tree=e,this._prefix=r}atPrefix(e){if(!e.startsWith(this._prefix))throw new Error("Mismatched prefix");const[r,n]=Gt(this._tree,e.slice(this._prefix.length));if(r===void 0){const[i,a]=Kr(n);for(const o of i.keys())if(o!==me&&o.startsWith(a)){const s=new Map;return s.set(o.slice(a.length),i.get(o)),new Ue(s,e)}}return new Ue(r,e)}clear(){this._size=void 0,this._tree.clear()}delete(e){return this._size=void 0,vs(this._tree,e)}entries(){return new pr(this,ps)}forEach(e){for(const[r,n]of this)e(r,n,this)}fuzzyGet(e,r){return gs(this._tree,e,r)}get(e){const r=Lr(this._tree,e);return r!==void 0?r.get(me):void 0}has(e){const r=Lr(this._tree,e);return r!==void 0&&r.has(me)}keys(){return new pr(this,_o)}set(e,r){if(typeof e!="string")throw new Error("key must be a string");return this._size=void 0,gr(this._tree,e).set(me,r),this}get size(){if(this._size)return this._size;this._size=0;const e=this.entries();for(;!e.next().done;)this._size+=1;return this._size}update(e,r){if(typeof e!="string")throw new Error("key must be a string");this._size=void 0;const n=gr(this._tree,e);return n.set(me,r(n.get(me))),this}fetch(e,r){if(typeof e!="string")throw new Error("key must be a string");this._size=void 0;const n=gr(this._tree,e);let i=n.get(me);return i===void 0&&n.set(me,i=r()),i}values(){return new pr(this,So)}[Symbol.iterator](){return this.entries()}static from(e){const r=new Ue;for(const[n,i]of e)r.set(n,i);return r}static fromObject(e){return Ue.from(Object.entries(e))}}const Gt=(t,e,r=[])=>{if(e.length===0||t==null)return[t,r];for(const n of t.keys())if(n!==me&&e.startsWith(n))return r.push([t,n]),Gt(t.get(n),e.slice(n.length),r);return r.push([t,e]),Gt(void 0,"",r)},Lr=(t,e)=>{if(e.length===0||t==null)return t;for(const r of t.keys())if(r!==me&&e.startsWith(r))return Lr(t.get(r),e.slice(r.length))},gr=(t,e)=>{const r=e.length;e:for(let n=0;t&&n<r;){for(const a of t.keys())if(a!==me&&e[n]===a[0]){const o=Math.min(r-n,a.length);let s=1;for(;s<o&&e[n+s]===a[s];)++s;const l=t.get(a);if(s===a.length)t=l;else{const c=new Map;c.set(a.slice(s),l),t.set(e.slice(n,n+s),c),t.delete(a),t=c}n+=s;continue e}const i=new Map;return t.set(e.slice(n),i),i}return t},vs=(t,e)=>{const[r,n]=Gt(t,e);if(r!==void 0){if(r.delete(me),r.size===0)To(n);else if(r.size===1){const[i,a]=r.entries().next().value;Co(n,i,a)}}},To=t=>{if(t.length===0)return;const[e,r]=Kr(t);if(e.delete(r),e.size===0)To(t.slice(0,-1));else if(e.size===1){const[n,i]=e.entries().next().value;n!==me&&Co(t.slice(0,-1),n,i)}},Co=(t,e,r)=>{if(t.length===0)return;const[n,i]=Kr(t);n.set(i+e,r),n.delete(i)},Kr=t=>t[t.length-1],Jr="or",Io="and",bs="and_not";class st{constructor(e){if((e==null?void 0:e.fields)==null)throw new Error('MiniSearch: option "fields" must be provided');const r=e.autoVacuum==null||e.autoVacuum===!0?yr:e.autoVacuum;this._options={...br,...e,autoVacuum:r,searchOptions:{...Sn,...e.searchOptions||{}},autoSuggestOptions:{...ks,...e.autoSuggestOptions||{}}},this._index=new Ue,this._documentCount=0,this._documentIds=new Map,this._idToShortId=new Map,this._fieldIds={},this._fieldLength=new Map,this._avgFieldLength=[],this._nextId=0,this._storedFields=new Map,this._dirtCount=0,this._currentVacuum=null,this._enqueuedVacuum=null,this._enqueuedVacuumConditions=Rr,this.addFields(this._options.fields)}add(e){const{extractField:r,stringifyField:n,tokenize:i,processTerm:a,fields:o,idField:s}=this._options,l=r(e,s);if(l==null)throw new Error(`MiniSearch: document does not have ID field "${s}"`);if(this._idToShortId.has(l))throw new Error(`MiniSearch: duplicate ID ${l}`);const c=this.addDocumentId(l);this.saveStoredFields(c,e);for(const d of o){const h=r(e,d);if(h==null)continue;const f=i(n(h,d),d),u=this._fieldIds[d],g=new Set(f).size;this.addFieldLength(c,u,this._documentCount-1,g);for(const x of f){const m=a(x,d);if(Array.isArray(m))for(const p of m)this.addTerm(u,c,p);else m&&this.addTerm(u,c,m)}}}addAll(e){for(const r of e)this.add(r)}addAllAsync(e,r={}){const{chunkSize:n=10}=r,i={chunk:[],promise:Promise.resolve()},{chunk:a,promise:o}=e.reduce(({chunk:s,promise:l},c,d)=>(s.push(c),(d+1)%n===0?{chunk:[],promise:l.then(()=>new Promise(h=>setTimeout(h,0))).then(()=>this.addAll(s))}:{chunk:s,promise:l}),i);return o.then(()=>this.addAll(a))}remove(e){const{tokenize:r,processTerm:n,extractField:i,stringifyField:a,fields:o,idField:s}=this._options,l=i(e,s);if(l==null)throw new Error(`MiniSearch: document does not have ID field "${s}"`);const c=this._idToShortId.get(l);if(c==null)throw new Error(`MiniSearch: cannot remove document with ID ${l}: it is not in the index`);for(const d of o){const h=i(e,d);if(h==null)continue;const f=r(a(h,d),d),u=this._fieldIds[d],g=new Set(f).size;this.removeFieldLength(c,u,this._documentCount,g);for(const x of f){const m=n(x,d);if(Array.isArray(m))for(const p of m)this.removeTerm(u,c,p);else m&&this.removeTerm(u,c,m)}}this._storedFields.delete(c),this._documentIds.delete(c),this._idToShortId.delete(l),this._fieldLength.delete(c),this._documentCount-=1}removeAll(e){if(e)for(const r of e)this.remove(r);else{if(arguments.length>0)throw new Error("Expected documents to be present. Omit the argument to remove all documents.");this._index=new Ue,this._documentCount=0,this._documentIds=new Map,this._idToShortId=new Map,this._fieldLength=new Map,this._avgFieldLength=[],this._storedFields=new Map,this._nextId=0}}discard(e){const r=this._idToShortId.get(e);if(r==null)throw new Error(`MiniSearch: cannot discard document with ID ${e}: it is not in the index`);this._idToShortId.delete(e),this._documentIds.delete(r),this._storedFields.delete(r),(this._fieldLength.get(r)||[]).forEach((n,i)=>{this.removeFieldLength(r,i,this._documentCount,n)}),this._fieldLength.delete(r),this._documentCount-=1,this._dirtCount+=1,this.maybeAutoVacuum()}maybeAutoVacuum(){if(this._options.autoVacuum===!1)return;const{minDirtFactor:e,minDirtCount:r,batchSize:n,batchWait:i}=this._options.autoVacuum;this.conditionalVacuum({batchSize:n,batchWait:i},{minDirtCount:r,minDirtFactor:e})}discardAll(e){const r=this._options.autoVacuum;try{this._options.autoVacuum=!1;for(const n of e)this.discard(n)}finally{this._options.autoVacuum=r}this.maybeAutoVacuum()}replace(e){const{idField:r,extractField:n}=this._options,i=n(e,r);this.discard(i),this.add(e)}vacuum(e={}){return this.conditionalVacuum(e)}conditionalVacuum(e,r){return this._currentVacuum?(this._enqueuedVacuumConditions=this._enqueuedVacuumConditions&&r,this._enqueuedVacuum!=null?this._enqueuedVacuum:(this._enqueuedVacuum=this._currentVacuum.then(()=>{const n=this._enqueuedVacuumConditions;return this._enqueuedVacuumConditions=Rr,this.performVacuuming(e,n)}),this._enqueuedVacuum)):this.vacuumConditionsMet(r)===!1?Promise.resolve():(this._currentVacuum=this.performVacuuming(e),this._currentVacuum)}async performVacuuming(e,r){const n=this._dirtCount;if(this.vacuumConditionsMet(r)){const i=e.batchSize||Mr.batchSize,a=e.batchWait||Mr.batchWait;let o=1;for(const[s,l]of this._index){for(const[c,d]of l)for(const[h]of d)this._documentIds.has(h)||(d.size<=1?l.delete(c):d.delete(h));this._index.get(s).size===0&&this._index.delete(s),o%i===0&&await new Promise(c=>setTimeout(c,a)),o+=1}this._dirtCount-=n}await null,this._currentVacuum=this._enqueuedVacuum,this._enqueuedVacuum=null}vacuumConditionsMet(e){if(e==null)return!0;let{minDirtCount:r,minDirtFactor:n}=e;return r=r||yr.minDirtCount,n=n||yr.minDirtFactor,this.dirtCount>=r&&this.dirtFactor>=n}get isVacuuming(){return this._currentVacuum!=null}get dirtCount(){return this._dirtCount}get dirtFactor(){return this._dirtCount/(1+this._documentCount+this._dirtCount)}has(e){return this._idToShortId.has(e)}getStoredFields(e){const r=this._idToShortId.get(e);if(r!=null)return this._storedFields.get(r)}search(e,r={}){const{searchOptions:n}=this._options,i={...n,...r},a=this.executeQuery(e,r),o=[];for(const[s,{score:l,terms:c,match:d}]of a){const h=c.length||1,f={id:this._documentIds.get(s),score:l*h,terms:Object.keys(d),queryTerms:c,match:d};Object.assign(f,this._storedFields.get(s)),(i.filter==null||i.filter(f))&&o.push(f)}return e===st.wildcard&&i.boostDocument==null||o.sort(Tn),o}autoSuggest(e,r={}){r={...this._options.autoSuggestOptions,...r};const n=new Map;for(const{score:a,terms:o}of this.search(e,r)){const s=o.join(" "),l=n.get(s);l!=null?(l.score+=a,l.count+=1):n.set(s,{score:a,terms:o,count:1})}const i=[];for(const[a,{score:o,terms:s,count:l}]of n)i.push({suggestion:a,terms:s,score:o/l});return i.sort(Tn),i}get documentCount(){return this._documentCount}get termCount(){return this._index.size}static loadJSON(e,r){if(r==null)throw new Error("MiniSearch: loadJSON should be given the same options used when serializing the index");return this.loadJS(JSON.parse(e),r)}static async loadJSONAsync(e,r){if(r==null)throw new Error("MiniSearch: loadJSON should be given the same options used when serializing the index");return this.loadJSAsync(JSON.parse(e),r)}static getDefault(e){if(br.hasOwnProperty(e))return vr(br,e);throw new Error(`MiniSearch: unknown option "${e}"`)}static loadJS(e,r){const{index:n,documentIds:i,fieldLength:a,storedFields:o,serializationVersion:s}=e,l=this.instantiateMiniSearch(e,r);l._documentIds=Mt(i),l._fieldLength=Mt(a),l._storedFields=Mt(o);for(const[c,d]of l._documentIds)l._idToShortId.set(d,c);for(const[c,d]of n){const h=new Map;for(const f of Object.keys(d)){let u=d[f];s===1&&(u=u.ds),h.set(parseInt(f,10),Mt(u))}l._index.set(c,h)}return l}static async loadJSAsync(e,r){const{index:n,documentIds:i,fieldLength:a,storedFields:o,serializationVersion:s}=e,l=this.instantiateMiniSearch(e,r);l._documentIds=await Rt(i),l._fieldLength=await Rt(a),l._storedFields=await Rt(o);for(const[d,h]of l._documentIds)l._idToShortId.set(h,d);let c=0;for(const[d,h]of n){const f=new Map;for(const u of Object.keys(h)){let g=h[u];s===1&&(g=g.ds),f.set(parseInt(u,10),await Rt(g))}++c%1e3===0&&await Lo(0),l._index.set(d,f)}return l}static instantiateMiniSearch(e,r){const{documentCount:n,nextId:i,fieldIds:a,averageFieldLength:o,dirtCount:s,serializationVersion:l}=e;if(l!==1&&l!==2)throw new Error("MiniSearch: cannot deserialize an index created with an incompatible version");const c=new st(r);return c._documentCount=n,c._nextId=i,c._idToShortId=new Map,c._fieldIds=a,c._avgFieldLength=o,c._dirtCount=s||0,c._index=new Ue,c}executeQuery(e,r={}){if(e===st.wildcard)return this.executeWildcardQuery(r);if(typeof e!="string"){const f={...r,...e,queries:void 0},u=e.queries.map(g=>this.executeQuery(g,f));return this.combineResults(u,f.combineWith)}const{tokenize:n,processTerm:i,searchOptions:a}=this._options,o={tokenize:n,processTerm:i,...a,...r},{tokenize:s,processTerm:l}=o,h=s(e).flatMap(f=>l(f)).filter(f=>!!f).map(Es(o)).map(f=>this.executeQuerySpec(f,o));return this.combineResults(h,o.combineWith)}executeQuerySpec(e,r){const n={...this._options.searchOptions,...r},i=(n.fields||this._options.fields).reduce((x,m)=>({...x,[m]:vr(n.boost,m)||1}),{}),{boostDocument:a,weights:o,maxFuzzy:s,bm25:l}=n,{fuzzy:c,prefix:d}={...Sn.weights,...o},h=this._index.get(e.term),f=this.termResults(e.term,e.term,1,e.termBoost,h,i,a,l);let u,g;if(e.prefix&&(u=this._index.atPrefix(e.term)),e.fuzzy){const x=e.fuzzy===!0?.2:e.fuzzy,m=x<1?Math.min(s,Math.round(e.term.length*x)):x;m&&(g=this._index.fuzzyGet(e.term,m))}if(u)for(const[x,m]of u){const p=x.length-e.term.length;if(!p)continue;g==null||g.delete(x);const v=d*x.length/(x.length+.3*p);this.termResults(e.term,x,v,e.termBoost,m,i,a,l,f)}if(g)for(const x of g.keys()){const[m,p]=g.get(x);if(!p)continue;const v=c*x.length/(x.length+p);this.termResults(e.term,x,v,e.termBoost,m,i,a,l,f)}return f}executeWildcardQuery(e){const r=new Map,n={...this._options.searchOptions,...e};for(const[i,a]of this._documentIds){const o=n.boostDocument?n.boostDocument(a,"",this._storedFields.get(i)):1;r.set(i,{score:o,terms:[],match:{}})}return r}combineResults(e,r=Jr){if(e.length===0)return new Map;const n=r.toLowerCase(),i=ys[n];if(!i)throw new Error(`Invalid combination operator: ${r}`);return e.reduce(i)||new Map}toJSON(){const e=[];for(const[r,n]of this._index){const i={};for(const[a,o]of n)i[a]=Object.fromEntries(o);e.push([r,i])}return{documentCount:this._documentCount,nextId:this._nextId,documentIds:Object.fromEntries(this._documentIds),fieldIds:this._fieldIds,fieldLength:Object.fromEntries(this._fieldLength),averageFieldLength:this._avgFieldLength,storedFields:Object.fromEntries(this._storedFields),dirtCount:this._dirtCount,index:e,serializationVersion:2}}termResults(e,r,n,i,a,o,s,l,c=new Map){if(a==null)return c;for(const d of Object.keys(o)){const h=o[d],f=this._fieldIds[d],u=a.get(f);if(u==null)continue;let g=u.size;const x=this._avgFieldLength[f];for(const m of u.keys()){if(!this._documentIds.has(m)){this.removeTerm(f,m,r),g-=1;continue}const p=s?s(this._documentIds.get(m),r,this._storedFields.get(m)):1;if(!p)continue;const v=u.get(m),b=this._fieldLength.get(m)[f],y=xs(v,g,this._documentCount,b,x,l),_=n*i*h*p*y,C=c.get(m);if(C){C.score+=_,_s(C.terms,e);const F=vr(C.match,r);F?F.push(d):C.match[r]=[d]}else c.set(m,{score:_,terms:[e],match:{[r]:[d]}})}}return c}addTerm(e,r,n){const i=this._index.fetch(n,Cn);let a=i.get(e);if(a==null)a=new Map,a.set(r,1),i.set(e,a);else{const o=a.get(r);a.set(r,(o||0)+1)}}removeTerm(e,r,n){if(!this._index.has(n)){this.warnDocumentChanged(r,e,n);return}const i=this._index.fetch(n,Cn),a=i.get(e);a==null||a.get(r)==null?this.warnDocumentChanged(r,e,n):a.get(r)<=1?a.size<=1?i.delete(e):a.delete(r):a.set(r,a.get(r)-1),this._index.get(n).size===0&&this._index.delete(n)}warnDocumentChanged(e,r,n){for(const i of Object.keys(this._fieldIds))if(this._fieldIds[i]===r){this._options.logger("warn",`MiniSearch: document with ID ${this._documentIds.get(e)} has changed before removal: term "${n}" was not present in field "${i}". Removing a document after it has changed can corrupt the index!`,"version_conflict");return}}addDocumentId(e){const r=this._nextId;return this._idToShortId.set(e,r),this._documentIds.set(r,e),this._documentCount+=1,this._nextId+=1,r}addFields(e){for(let r=0;r<e.length;r++)this._fieldIds[e[r]]=r}addFieldLength(e,r,n,i){let a=this._fieldLength.get(e);a==null&&this._fieldLength.set(e,a=[]),a[r]=i;const s=(this._avgFieldLength[r]||0)*n+i;this._avgFieldLength[r]=s/(n+1)}removeFieldLength(e,r,n,i){if(n===1){this._avgFieldLength[r]=0;return}const a=this._avgFieldLength[r]*n-i;this._avgFieldLength[r]=a/(n-1)}saveStoredFields(e,r){const{storeFields:n,extractField:i}=this._options;if(n==null||n.length===0)return;let a=this._storedFields.get(e);a==null&&this._storedFields.set(e,a={});for(const o of n){const s=i(r,o);s!==void 0&&(a[o]=s)}}}st.wildcard=Symbol("*");const vr=(t,e)=>Object.prototype.hasOwnProperty.call(t,e)?t[e]:void 0,ys={[Jr]:(t,e)=>{for(const r of e.keys()){const n=t.get(r);if(n==null)t.set(r,e.get(r));else{const{score:i,terms:a,match:o}=e.get(r);n.score=n.score+i,n.match=Object.assign(n.match,o),An(n.terms,a)}}return t},[Io]:(t,e)=>{const r=new Map;for(const n of e.keys()){const i=t.get(n);if(i==null)continue;const{score:a,terms:o,match:s}=e.get(n);An(i.terms,o),r.set(n,{score:i.score+a,terms:i.terms,match:Object.assign(i.match,s)})}return r},[bs]:(t,e)=>{for(const r of e.keys())t.delete(r);return t}},ws={k:1.2,b:.7,d:.5},xs=(t,e,r,n,i,a)=>{const{k:o,b:s,d:l}=a;return Math.log(1+(r-e+.5)/(e+.5))*(l+t*(o+1)/(t+o*(1-s+s*n/i)))},Es=t=>(e,r,n)=>{const i=typeof t.fuzzy=="function"?t.fuzzy(e,r,n):t.fuzzy||!1,a=typeof t.prefix=="function"?t.prefix(e,r,n):t.prefix===!0,o=typeof t.boostTerm=="function"?t.boostTerm(e,r,n):1;return{term:e,fuzzy:i,prefix:a,termBoost:o}},br={idField:"id",extractField:(t,e)=>t[e],stringifyField:(t,e)=>t.toString(),tokenize:t=>t.split(Ss),processTerm:t=>t.toLowerCase(),fields:void 0,searchOptions:void 0,storeFields:[],logger:(t,e)=>{typeof(console==null?void 0:console[t])=="function"&&console[t](e)},autoVacuum:!0},Sn={combineWith:Jr,prefix:!1,fuzzy:!1,maxFuzzy:6,boost:{},weights:{fuzzy:.45,prefix:.375},bm25:ws},ks={combineWith:Io,prefix:(t,e,r)=>e===r.length-1},Mr={batchSize:1e3,batchWait:10},Rr={minDirtFactor:.1,minDirtCount:20},yr={...Mr,...Rr},_s=(t,e)=>{t.includes(e)||t.push(e)},An=(t,e)=>{for(const r of e)t.includes(r)||t.push(r)},Tn=({score:t},{score:e})=>e-t,Cn=()=>new Map,Mt=t=>{const e=new Map;for(const r of Object.keys(t))e.set(parseInt(r,10),t[r]);return e},Rt=async t=>{const e=new Map;let r=0;for(const n of Object.keys(t))e.set(parseInt(n,10),t[n]),++r%1e3===0&&await Lo(0);return e},Lo=t=>new Promise(e=>setTimeout(e,t)),Ss=/[\n\r\p{Z}\p{P}]+/u,As=typeof document<"u",Ts=/[\u0000-\u001F"#$&*+,:;<=>?[\]^`{|}\u007F]/g,Cs=/^[a-z]:/i;function In(t){const e=Cs.exec(t),r=e?e[0]:"";return r+t.slice(r.length).replace(Ts,"_").replace(/(^|\/)_+(?=[^/]*$)/,"$1")}function Is(t){return t.replace(/[|\\{}()[\]^$+*?.]/g,"\\$&").replace(/-/g,"\\x2d")}function Ls(t){let e=t.replace(/\.html$/,"");if(e=decodeURIComponent(e),e=e.replace(/\/$/,"/index"),As){const r="/formie/";e=In(e.slice(r.length).replace(/\//g,"_")||"index")+".md";let n=__VP_HASH_MAP__[e.toLowerCase()];if(n||(e=e.endsWith("_index.md")?e.slice(0,-9)+".md":e.slice(0,-3)+"_index.md",n=__VP_HASH_MAP__[e.toLowerCase()]),!n)return null;e=`${r}assets/${e}.${n}.js`}else e=`./${In(e.slice(1).replace(/\//g,"_"))}.md.js`;return e}const Mo=We;class Ms{constructor(e=10){dr(this,"max");dr(this,"cache");this.max=e,this.cache=new Map}get(e){let r=this.cache.get(e);return r!==void 0&&(this.cache.delete(e),this.cache.set(e,r)),r}set(e,r){this.cache.has(e)?this.cache.delete(e):this.cache.size===this.max&&this.cache.delete(this.first()),this.cache.set(e,r)}first(){return this.cache.keys().next().value}clear(){this.cache.clear()}}function Rs(t){const{localeIndex:e,theme:r}=Mo();function n(i){var g,x,m;const a=i.split("."),o=(g=r.value.search)==null?void 0:g.options,s=o&&typeof o=="object",l=s&&((m=(x=o.locales)==null?void 0:x[e.value])==null?void 0:m.translations)||null,c=s&&o.translations||null;let d=l,h=c,f=t;const u=a.pop();for(const p of a){let v=null;const b=f==null?void 0:f[p];b&&(v=f=b);const y=h==null?void 0:h[p];y&&(v=h=y);const _=d==null?void 0:d[p];_&&(v=d=_),b||(f=v),y||(h=v),_||(d=v)}return(d==null?void 0:d[u])??(h==null?void 0:h[u])??(f==null?void 0:f[u])??""}return n}const Fs=["aria-owns"],Os={class:"shell"},Ps=["title"],Ns={class:"search-actions before"},Ds=["title"],zs=["aria-activedescendant","aria-controls","placeholder"],$s={class:"search-actions"},Vs=["title"],Hs=["disabled","title"],qs=["id","role","aria-labelledby"],js=["id","aria-selected"],Bs=["href","aria-label","onMouseenter","onFocusin","data-index"],Us={class:"titles"},Ws=["innerHTML"],Ks={class:"title main"},Js=["innerHTML"],Gs={key:0,class:"excerpt-wrapper"},Ys={key:0,class:"excerpt",inert:""},Zs=["innerHTML"],Qs={key:0,class:"no-results"},Xs={class:"search-keyboard-shortcuts"},el=["aria-label"],tl=["aria-label"],rl=["aria-label"],nl=["aria-label"],ol=Ce({__name:"VPLocalSearchBox",emits:["close"],setup(t,{emit:e}){var Z,U;const r=e,n=Re(),i=Re(),a=Re(fa),o=Mo(),{activate:s}=us(n,{immediate:!0,allowOutsideClick:!0,clickOutsideDeactivates:!0,escapeDeactivates:!0}),{localeIndex:l,theme:c}=o,d=bn(async()=>{var I,T,G,te,z,Q,ne,X,se;return mn(st.loadJSON((G=await((T=(I=a.value)[l.value])==null?void 0:T.call(I)))==null?void 0:G.default,{fields:["title","titles","text"],storeFields:["title","titles"],searchOptions:{fuzzy:.2,prefix:!0,boost:{title:4,text:2,titles:1},...((te=c.value.search)==null?void 0:te.provider)==="local"&&((Q=(z=c.value.search.options)==null?void 0:z.miniSearch)==null?void 0:Q.searchOptions)},...((ne=c.value.search)==null?void 0:ne.provider)==="local"&&((se=(X=c.value.search.options)==null?void 0:X.miniSearch)==null?void 0:se.options)}))}),f=V(()=>{var I,T;return((I=c.value.search)==null?void 0:I.provider)==="local"&&((T=c.value.search.options)==null?void 0:T.disableQueryPersistence)===!0}).value?ae(""):Oa("vitepress:local-search-filter",""),u=Ma("vitepress:local-search-detailed-list",((Z=c.value.search)==null?void 0:Z.provider)==="local"&&((U=c.value.search.options)==null?void 0:U.detailedView)===!0),g=V(()=>{var I,T,G;return((I=c.value.search)==null?void 0:I.provider)==="local"&&(((T=c.value.search.options)==null?void 0:T.disableDetailedView)===!0||((G=c.value.search.options)==null?void 0:G.detailedView)===!1)}),x=V(()=>{var T,G,te,z,Q,ne,X;const I=((T=c.value.search)==null?void 0:T.options)??c.value.algolia;return((Q=(z=(te=(G=I==null?void 0:I.locales)==null?void 0:G[l.value])==null?void 0:te.translations)==null?void 0:z.button)==null?void 0:Q.buttonText)||((X=(ne=I==null?void 0:I.translations)==null?void 0:ne.button)==null?void 0:X.buttonText)||"Search"});rr(()=>{g.value&&(u.value=!1)});const m=Re([]),p=ae(!1);he(f,()=>{p.value=!1});const v=bn(async()=>{if(i.value)return mn(new hs(i.value))},null),b=new Ms(16);ka(()=>[d.value,f.value,u.value],async([I,T,G],te,z)=>{var ue,we,Ie,qe;(te==null?void 0:te[0])!==I&&b.clear();let Q=!1;if(z(()=>{Q=!0}),!I)return;m.value=I.search(T).slice(0,16),p.value=!0;const ne=G?await Promise.all(m.value.map(de=>y(de.id))):[];if(Q)return;for(const{id:de,mod:_e}of ne){const Oe=de.slice(0,de.indexOf("#"));let Se=b.get(Oe);if(Se)continue;Se=new Map,b.set(Oe,Se);const Ae=_e.default??_e;if(Ae!=null&&Ae.render||Ae!=null&&Ae.setup){const Pe=_i(Ae);Pe.config.warnHandler=()=>{},Pe.provide(Si,o),Object.defineProperties(Pe.config.globalProperties,{$frontmatter:{get(){return o.frontmatter.value}},$params:{get(){return o.page.value.params}}});const dt=document.createElement("div");Pe.mount(dt),dt.querySelectorAll("h1, h2, h3, h4, h5, h6").forEach(Ke=>{var mt;const je=(mt=Ke.querySelector("a"))==null?void 0:mt.getAttribute("href"),ft=(je==null?void 0:je.startsWith("#"))&&je.slice(1);if(!ft)return;let et="";for(;(Ke=Ke.nextElementSibling)&&!/^h[1-6]$/i.test(Ke.tagName);)et+=Ke.outerHTML;Se.set(ft,et)}),Pe.unmount()}if(Q)return}const X=new Set;if(m.value=m.value.map(de=>{const[_e,Oe]=de.id.split("#"),Se=b.get(_e),Ae=(Se==null?void 0:Se.get(Oe))??"";for(const Pe in de.match)X.add(Pe);return{...de,text:Ae}}),await Me(),Q)return;await new Promise(de=>{var _e;(_e=v.value)==null||_e.unmark({done:()=>{var Oe;(Oe=v.value)==null||Oe.markRegExp(W(X),{done:de})}})});const se=((ue=n.value)==null?void 0:ue.querySelectorAll(".result .excerpt"))??[];for(const de of se)(we=de.querySelector('mark[data-markjs="true"]'))==null||we.scrollIntoView({block:"center"});(qe=(Ie=i.value)==null?void 0:Ie.firstElementChild)==null||qe.scrollIntoView({block:"start"})},{debounce:200,immediate:!0});async function y(I){const T=Ls(I.slice(0,I.indexOf("#")));try{if(!T)throw new Error(`Cannot find file for id: ${I}`);return{id:I,mod:await import(T)}}catch(G){return console.error(G),{id:I,mod:{}}}}const _=ae(),C=V(()=>{var I;return((I=f.value)==null?void 0:I.length)<=0});function F(I=!0){var T,G;(T=_.value)==null||T.focus(),I&&((G=_.value)==null||G.select())}He(()=>{F()});function j(I){I.pointerType==="mouse"&&F()}const H=ae(-1),ee=ae(!0);he(m,I=>{H.value=I.length?0:-1,M()});function M(){Me(()=>{const I=document.querySelector(".result.selected");I==null||I.scrollIntoView({block:"nearest"})})}Tt("ArrowUp",I=>{I.preventDefault(),H.value--,H.value<0&&(H.value=m.value.length-1),ee.value=!0,M()}),Tt("ArrowDown",I=>{I.preventDefault(),H.value++,H.value>=m.value.length&&(H.value=0),ee.value=!0,M()});const B=Br();Tt("Enter",I=>{if(I.isComposing||I.target instanceof HTMLButtonElement&&I.target.type!=="submit")return;const T=m.value[H.value];if(I.target instanceof HTMLInputElement&&!T){I.preventDefault();return}T&&(B.go(T.id),r("close"))}),Tt("Escape",()=>{r("close")});const w=Rs({modal:{displayDetails:"Display detailed list",resetButtonTitle:"Reset search",backButtonTitle:"Close search",noResultsText:"No results for",footer:{selectText:"to select",selectKeyAriaLabel:"enter",navigateText:"to navigate",navigateUpKeyAriaLabel:"up arrow",navigateDownKeyAriaLabel:"down arrow",closeText:"to close",closeKeyAriaLabel:"escape"}}});He(()=>{window.history.pushState(null,"",null)}),xt("popstate",I=>{I.preventDefault(),r("close")});const A=Fa(Ai?document.body:null);He(()=>{Me(()=>{A.value=!0,Me().then(()=>s())})}),nr(()=>{A.value=!1});function P(){f.value="",Me().then(()=>F(!1))}function W(I){return new RegExp([...I].sort((T,G)=>G.length-T.length).map(T=>`(${Is(T)})`).join("|"),"gi")}function $(I){var te;if(!ee.value)return;const T=(te=I.target)==null?void 0:te.closest(".result"),G=Number.parseInt(T==null?void 0:T.dataset.index);G>=0&&G!==H.value&&(H.value=G),ee.value=!1}return(I,T)=>{var G,te,z,Q,ne;return L(),ke(xi,{to:"body"},[S("div",{ref_key:"el",ref:n,role:"button","aria-owns":(G=m.value)!=null&&G.length?"localsearch-list":void 0,"aria-expanded":"true","aria-haspopup":"listbox","aria-labelledby":"localsearch-label",class:"VPLocalSearchBox"},[S("div",{class:"backdrop",onClick:T[0]||(T[0]=X=>I.$emit("close"))}),S("div",Os,[S("form",{class:"search-bar",onPointerup:T[4]||(T[4]=X=>j(X)),onSubmit:T[5]||(T[5]=Tr(()=>{},["prevent"]))},[S("label",{title:x.value,id:"localsearch-label",for:"localsearch-input"},[...T[7]||(T[7]=[S("span",{"aria-hidden":"true",class:"vpi-search search-icon local-search-icon"},null,-1)])],8,Ps),S("div",Ns,[S("button",{class:"back-button",title:J(w)("modal.backButtonTitle"),onClick:T[1]||(T[1]=X=>I.$emit("close"))},[...T[8]||(T[8]=[S("span",{class:"vpi-arrow-left local-search-icon"},null,-1)])],8,Ds)]),Ei(S("input",{ref_key:"searchInput",ref:_,"onUpdate:modelValue":T[2]||(T[2]=X=>ao(f)?f.value=X:null),"aria-activedescendant":H.value>-1?"localsearch-item-"+H.value:void 0,"aria-autocomplete":"both","aria-controls":(te=m.value)!=null&&te.length?"localsearch-list":void 0,"aria-labelledby":"localsearch-label",autocapitalize:"off",autocomplete:"off",autocorrect:"off",class:"search-input",id:"localsearch-input",enterkeyhint:"go",maxlength:"64",placeholder:x.value,spellcheck:"false",type:"search"},null,8,zs),[[ki,J(f)]]),S("div",$s,[g.value?Y("",!0):(L(),R("button",{key:0,class:ce(["toggle-layout-button",{"detailed-list":J(u)}]),type:"button",title:J(w)("modal.displayDetails"),onClick:T[3]||(T[3]=X=>H.value>-1&&(u.value=!J(u)))},[...T[9]||(T[9]=[S("span",{class:"vpi-layout-list local-search-icon"},null,-1)])],10,Vs)),S("button",{class:"clear-button",type:"reset",disabled:C.value,title:J(w)("modal.resetButtonTitle"),onClick:P},[...T[10]||(T[10]=[S("span",{class:"vpi-delete local-search-icon"},null,-1)])],8,Hs)])],32),S("ul",{ref_key:"resultsEl",ref:i,id:(z=m.value)!=null&&z.length?"localsearch-list":void 0,role:(Q=m.value)!=null&&Q.length?"listbox":void 0,"aria-labelledby":(ne=m.value)!=null&&ne.length?"localsearch-label":void 0,class:"results",onMousemove:$},[(L(!0),R(ge,null,be(m.value,(X,se)=>(L(),R("li",{key:X.id,id:"localsearch-item-"+se,"aria-selected":H.value===se?"true":"false",role:"option"},[S("a",{href:X.id,class:ce(["result",{selected:H.value===se}]),"aria-label":[...X.titles,X.title].join(" > "),onMouseenter:ue=>!ee.value&&(H.value=se),onFocusin:ue=>H.value=se,onClick:T[6]||(T[6]=ue=>I.$emit("close")),"data-index":se},[S("div",null,[S("div",Us,[T[12]||(T[12]=S("span",{class:"title-icon"},"#",-1)),(L(!0),R(ge,null,be(X.titles,(ue,we)=>(L(),R("span",{key:we,class:"title"},[S("span",{class:"text",innerHTML:ue},null,8,Ws),T[11]||(T[11]=S("span",{class:"vpi-chevron-right local-search-icon"},null,-1))]))),128)),S("span",Ks,[S("span",{class:"text",innerHTML:X.title},null,8,Js)])]),J(u)?(L(),R("div",Gs,[X.text?(L(),R("div",Ys,[S("div",{class:"vp-doc",innerHTML:X.text},null,8,Zs)])):Y("",!0),T[13]||(T[13]=S("div",{class:"excerpt-gradient-bottom"},null,-1)),T[14]||(T[14]=S("div",{class:"excerpt-gradient-top"},null,-1))])):Y("",!0)])],42,Bs)],8,js))),128)),J(f)&&!m.value.length&&p.value?(L(),R("li",Qs,[Ge(re(J(w)("modal.noResultsText"))+' "',1),S("strong",null,re(J(f)),1),T[15]||(T[15]=Ge('" ',-1))])):Y("",!0)],40,qs),S("div",Xs,[S("span",null,[S("kbd",{"aria-label":J(w)("modal.footer.navigateUpKeyAriaLabel")},[...T[16]||(T[16]=[S("span",{class:"vpi-arrow-up navigate-icon"},null,-1)])],8,el),S("kbd",{"aria-label":J(w)("modal.footer.navigateDownKeyAriaLabel")},[...T[17]||(T[17]=[S("span",{class:"vpi-arrow-down navigate-icon"},null,-1)])],8,tl),Ge(" "+re(J(w)("modal.footer.navigateText")),1)]),S("span",null,[S("kbd",{"aria-label":J(w)("modal.footer.selectKeyAriaLabel")},[...T[18]||(T[18]=[S("span",{class:"vpi-corner-down-left navigate-icon"},null,-1)])],8,rl),Ge(" "+re(J(w)("modal.footer.selectText")),1)]),S("span",null,[S("kbd",{"aria-label":J(w)("modal.footer.closeKeyAriaLabel")},"esc",8,nl),Ge(" "+re(J(w)("modal.footer.closeText")),1)])])])],8,Fs)])}}}),il=Ti(ol,[["__scopeId","data-v-140b7a94"]]),lt=Re(null);function al(t){lt.value=t}function sl(t){lt.value===t&&(lt.value=null)}function ll(){var t;(t=lt.value)==null||t.call(lt)}const cl=Ce({__name:"DocsSearchProvider",setup(t){const e=ae(!1);function r(){e.value=!0}function n(){e.value=!1}function i(o){const s=o.target,l=s.tagName;return s.isContentEditable||l==="INPUT"||l==="SELECT"||l==="TEXTAREA"}function a(o){(o.key.toLowerCase()==="k"&&(o.metaKey||o.ctrlKey)||!i(o)&&o.key==="/")&&(o.preventDefault(),r())}return He(()=>{al(r),window.addEventListener("keydown",a)}),ro(()=>{sl(r),window.removeEventListener("keydown",a)}),(o,s)=>e.value?(L(),ke(il,{key:0,onClose:n})):Y("",!0)}}),ul={class:"relative"},dl={class:"min-w-0 flex-1 break-words"},fl=["href"],ml={class:"flex min-w-0 flex-1 items-start gap-x-2.5"},hl={class:"flex min-w-0 flex-1 flex-wrap items-center gap-1.5 [word-break:break-word]"},pl={class:"min-w-0 max-w-full break-words"},gl=Ce({__name:"DocsSidebarNode",props:{item:{},depth:{default:0}},emits:["navigate"],setup(t,{emit:e}){const r=t,n=e,{page:i}=We(),a=Br(),o=V(()=>{var u;return!!((u=r.item.items)!=null&&u.length)}),s=V(()=>or(i.value.relativePath,r.item.link)),l=V(()=>{var u;return((u=r.item.items)==null?void 0:u.some(g=>wt(i.value.relativePath,g)))??!1}),c=ae(o.value?!r.item.collapsed||l.value:!1);he(l,u=>{u&&(c.value=!0)});function d(u){return u?ze(u):"#"}async function h(u,g){g&&(u.preventDefault(),await a.go(d(g)),n("navigate"))}function f(){o.value&&(c.value=!c.value)}return(u,g)=>{const x=no("DocsSidebarNode",!0);return L(),R("li",ul,[o.value?(L(),R("button",{key:0,type:"button",class:ce(["group flex w-full cursor-pointer items-center py-0.5 pr-2 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",l.value?"text-docs-primary":"text-slate-700"]),onClick:f},[S("span",dl,re(t.item.text),1),(L(),R("svg",{viewBox:"0 0 640 640",class:ce(["size-3 shrink-0",c.value?"rotate-90":"rotate-0"]),"aria-hidden":"true"},[...g[2]||(g[2]=[S("path",{d:"M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"},null,-1)])],2))],2)):(L(),R("a",{key:1,href:d(t.item.link),class:ce(["group flex w-full cursor-pointer items-center py-0.5 text-left text-sm leading-6 outline-offset-[-1px] transition hover:text-docs-primary",s.value?"text-docs-primary":"text-slate-700"]),onClick:g[0]||(g[0]=m=>h(m,t.item.link))},[S("div",ml,[t.item.icon?(L(),ke(ir,{key:0,name:t.item.icon,class:"mt-1 size-4 shrink-0 text-slate-500 group-hover:text-slate-700"},null,8,["name"])):Y("",!0),S("div",hl,[S("span",pl,re(t.item.text),1)])])],10,fl)),o.value&&c.value?(L(),R("ul",{key:2,style:At({marginLeft:t.depth===0?"1rem":"1.25rem"})},[(L(!0),R(ge,null,be(t.item.items,m=>(L(),ke(x,{key:m.link??`${m.text}-${m.icon??""}`,item:m,depth:t.depth+1,onNavigate:g[1]||(g[1]=p=>n("navigate"))},null,8,["item","depth"]))),128))],4)):Y("",!0)])}}}),vl={"aria-label":"Sidebar navigation",class:"text-sm"},bl={key:0,class:"mb-3 flex items-center gap-2.5 text-sm font-medium text-slate-900 lg:mb-2"},yl={class:"space-y-px"},wl=Ce({__name:"DocsSidebar",emits:["navigate"],setup(t){const{sidebarGroups:e}=Ur(),r=V(()=>e.value.filter(n=>{var i;return(i=n.items)==null?void 0:i.length}));return(n,i)=>(L(),R("nav",vl,[(L(!0),R(ge,null,be(r.value,a=>{var o,s;return L(),R("section",{key:a.text??((s=(o=a.items)==null?void 0:o[0])==null?void 0:s.link),class:"mt-6 first:mt-0 lg:mt-6 lg:first:mt-0"},[a.text?(L(),R("h2",bl,[a.icon?(L(),ke(ir,{key:0,name:a.icon,class:"size-4 text-slate-600"},null,8,["name"])):Y("",!0),Ge(" "+re(a.text),1)])):Y("",!0),S("ul",yl,[(L(!0),R(ge,null,be(a.items,l=>(L(),ke(gl,{key:l.link??`${l.text}-${l.icon??""}`,item:l,onNavigate:i[0]||(i[0]=c=>n.$emit("navigate"))},null,8,["item"]))),128))])])}),128))]))}}),xl={key:0,class:"min-h-screen bg-slate-50 text-slate-900 lg:h-screen lg:overflow-hidden"},El={class:"max-lg:contents lg:flex-1 lg:min-w-0 lg:overflow-x-clip"},kl={id:"navbar",class:"peer fixed top-0 z-30 w-full"},_l={class:"relative z-10 mx-auto max-w-[96rem] px-4"},Sl={class:"relative"},Al={class:"lg:hidden"},Tl={class:"flex h-14 items-center justify-between gap-3"},Cl=["href"],Il=["src"],Ll={key:1,class:"min-w-0 truncate text-[15px] font-semibold tracking-[-0.01em] text-slate-900"},Ml={class:"flex items-center gap-1.5"},Rl=["href","aria-label"],Fl={key:0,viewBox:"0 0 24 24",fill:"currentColor",class:"h-[18px] w-[18px]","aria-hidden":"true"},Ol=["aria-expanded"],Pl={class:"min-w-0 truncate"},Nl=["aria-label"],Dl=["href","aria-selected"],zl=["aria-expanded"],$l={class:"ml-4 flex min-w-0 items-center space-x-3 overflow-hidden text-sm leading-6 whitespace-nowrap"},Vl={key:0,class:"flex shrink-0 items-center space-x-3 text-slate-500"},Hl={class:"min-w-0 flex-1 truncate font-semibold text-slate-900"},ql={class:"relative hidden h-14 min-w-0 flex-1 items-center gap-x-4 lg:flex lg:border-none"},jl={class:"flex min-w-0 flex-1 items-center gap-x-4"},Bl=["href"],Ul=["src"],Wl={key:1,class:"min-w-0 truncate text-[15px] font-semibold tracking-[-0.01em] text-slate-900"},Kl=["aria-expanded"],Jl={class:"truncate"},Gl=["aria-label"],Yl=["href","aria-selected"],Zl={class:"flex items-center gap-4"},Ql={class:"flex items-center gap-2"},Xl=["href","aria-label"],ec={key:0,viewBox:"0 0 24 24",fill:"currentColor",class:"h-[18px] w-[18px]","aria-hidden":"true"},tc={class:"scroll-mt-[var(--scroll-mt)] fixed top-[7rem] w-full pb-2 pt-0 lg:top-[3.5rem]"},rc={key:0,id:"sidebar-content",class:"hidden min-h-0 lg:flex lg:flex-col"},nc={class:"flex h-full min-h-0 flex-col gap-4 text-sm"},oc={class:"relative z-20 hidden items-center gap-2.5 mr-4 mt-2 mb-2 lg:flex"},ic={class:"min-w-0 h-full min-h-0"},ac={class:"mx-auto w-full max-w-[88rem] xl:grid xl:grid-cols-[minmax(0,52rem)_16.5rem] xl:justify-center xl:gap-x-12"},sc={id:"content-area",class:"w-full min-w-0 overflow-x-visible"},lc={key:0,class:"eyebrow mb-2.5 h-5 text-sm font-semibold text-docs-primary"},cc={key:1,class:"mt-12 border-t border-slate-200 pt-6"},uc={key:0,class:"flex flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between"},dc=["href"],fc={key:1},mc={key:1,class:"mt-6 grid gap-3 sm:grid-cols-2"},hc=["href"],pc={class:"mt-1 text-sm font-medium text-slate-900 group-hover:text-docs-primary-strong"},gc={key:1,class:"hidden sm:block"},vc=["href"],bc={class:"mt-1 text-sm font-medium text-slate-900 group-hover:text-docs-primary-strong"},yc={key:0,id:"content-side-layout",class:"hidden xl:block"},wc={class:"sticky top-0 pt-1"},xc={id:"table-of-contents-shell",class:"max-h-[calc(100dvh-7rem)] w-[16.5rem] overflow-y-auto space-y-2 pb-4 text-sm leading-6 text-slate-600"},Ec=Ce({__name:"Layout",setup(t){const e=so(),{frontmatter:r,page:n,site:i,theme:a}=We(),{close:o,hasSidebar:s,isOpen:l,toggle:c,sidebarGroups:d}=Ur(),h=ae(null),f=ae(null),u=ae(!1),g=ae(null),x=ae(null),m=ae(!1),p=ae(!1);Oi(l,o),he(()=>e.path,o),he(()=>e.path,async(E,k)=>{await ft(E,k)});const v=V(()=>a.value.logo?typeof a.value.logo=="string"?ze(a.value.logo):ze(a.value.logo.src):null),b=V(()=>r.value.layout===!1||r.value.layout==="home"||r.value.aside===!1?!1:(r.value.outline??a.value.outline)!==!1),y=V(()=>typeof a.value.siteTitle=="string"&&a.value.siteTitle.trim()?a.value.siteTitle:i.value.title),_=V(()=>{var k;return((k=a.value.docsTheme)==null?void 0:k.homeLink)||i.value.base||"/"}),C=V(()=>a.value.socialLinks??[]),F=V(()=>d.value.find(E=>{var k;return(k=E.items)==null?void 0:k.some(D=>wt(n.value.relativePath,D))})??null),j=V(()=>r.value.title??n.value.title??y.value),H=V(()=>{const E=[se.value,j.value].filter(k=>!!(k!=null&&k.trim()));return E.filter((k,D)=>k!==E[D-1])}),ee=V(()=>H.value.length>1?H.value[0]:null),M=V(()=>H.value.at(-1)??y.value),B=V(()=>{var k;const E=a.value;return!!((k=E.search)!=null&&k.provider||E.algolia)}),N=V(()=>a.value.docsTheme??{}),w=V(()=>a.value.editLink),A=V(()=>a.value.lastUpdatedText??"Last updated");function P(E){var D;const k=[];for(const q of E)q.link&&k.push(q),(D=q.items)!=null&&D.length&&k.push(...P(q.items));return k}function W(E,k){var D;for(const q of E){if(q.link&&wt(k,q))return[q];if(!((D=q.items)!=null&&D.length))continue;const K=W(q.items,k);if(K)return[q,...K]}return null}function $(E){var D;const k=[];for(const q of E)q.text&&q.link&&k.push({text:q.text,link:q.link,activeMatch:q.activeMatch}),(D=q.items)!=null&&D.length&&k.push(...$(q.items));return k}function Z(E){const k=decodeURI(E).split(/[?#]/,1)[0]||"/",D=i.value.base&&i.value.base!=="/"?i.value.base.replace(/\/+$/,""):"",q=D&&k.startsWith(`${D}/`)?k.slice(D.length):k;if(q==="/")return"/";const ve=q.replace(/\/index(?:\.html)?$/,"/").replace(/\.html$/,"");return ve==="/"?"/":ve.replace(/\/+$/,"")}const U=V(()=>Z(e.path)),I=V(()=>{const E=Array.isArray(a.value.nav)?a.value.nav:[];return $(E)});function T(E){if(E.activeMatch)return new RegExp(E.activeMatch).test(U.value);const k=Z(E.link);return k==="/"?U.value==="/":U.value===k||U.value.startsWith(`${k}/`)}const G=V(()=>I.value.find(E=>T(E))??null),te=V(()=>{var E,k;return((E=G.value)==null?void 0:E.text)??((k=I.value[0])==null?void 0:k.text)??"Documentation"});function z(){m.value=!1,p.value=!1}function Q(){m.value=!m.value,p.value=!1}function ne(){p.value=!p.value,m.value=!1}function X(E){const k=E.target;m.value&&g.value&&!g.value.contains(k)&&(m.value=!1),p.value&&x.value&&!x.value.contains(k)&&(p.value=!1)}he(()=>e.path,z);const se=V(()=>{var D,q;const E=F.value;if(!((D=E==null?void 0:E.items)!=null&&D.length))return(E==null?void 0:E.text)??null;const k=W(E.items,n.value.relativePath);return!(k!=null&&k.length)||k.length===1?E.text??null:((q=k.at(-2))==null?void 0:q.text)??E.text??null}),ue=V(()=>{var E,k;return(k=(E=F.value)==null?void 0:E.items)!=null&&k.length?P(F.value.items):[]}),we=V(()=>ue.value.findIndex(E=>or(n.value.relativePath,E.link))),Ie=V(()=>{const E=we.value;return E>0?ue.value[E-1]:null}),qe=V(()=>{const E=we.value;return E>=0&&E<ue.value.length-1?ue.value[E+1]:null}),de=V(()=>{var D,q;if(r.value.editLink===!1)return null;const E=(D=w.value)==null?void 0:D.pattern,k=n.value.filePath;return!E||!k?null:{text:((q=w.value)==null?void 0:q.text)??"Edit this page",href:E.replace(":path",k)}}),_e=V(()=>{if(r.value.lastUpdated===!1)return null;const E=n.value.lastUpdated;return E?new Intl.DateTimeFormat(i.value.lang||void 0,{dateStyle:"medium",timeStyle:"short"}).format(E):null}),Oe=V(()=>{var k,D,q,K,ve,xe;const E=((k=N.value.primary)==null?void 0:k.trim())||"#0f766e";return{"--docs-primary":E,"--docs-primary-strong":((D=N.value.primaryStrong)==null?void 0:D.trim())||`color-mix(in oklab, ${E} 82%, black)`,"--docs-primary-soft":((q=N.value.primarySoft)==null?void 0:q.trim())||`color-mix(in oklab, ${E} 12%, white)`,"--docs-primary-soft-hover":((K=N.value.primarySoftHover)==null?void 0:K.trim())||`color-mix(in oklab, ${E} 16%, white)`,"--docs-primary-border":((ve=N.value.primaryBorder)==null?void 0:ve.trim())||`color-mix(in oklab, ${E} 18%, white)`,"--docs-primary-border-strong":((xe=N.value.primaryBorderStrong)==null?void 0:xe.trim())||`color-mix(in oklab, ${E} 28%, white)`}});function Se(){const E=f.value;if(!E){u.value=!1;return}u.value=E.scrollTop>4}function Ae(E){return E.split("#")[0]??E}function Pe(E){const k=E.indexOf("#");return k>=0?decodeURIComponent(E.slice(k+1)):""}function dt(E){const k=Pe(E);return k||(typeof window<"u"?decodeURIComponent(window.location.hash.replace(/^#/,"")):"")}function cr(){return h.value??document.getElementById("docs-scroll-container")??document.getElementById("content-container")}function Ke(){var E;(E=cr())==null||E.scrollTo({top:0,left:0,behavior:"auto"}),window.scrollTo({top:0,left:0,behavior:"auto"})}function je(E){if(!E)return!1;const k=document.getElementById(E),D=cr();if(!(k instanceof HTMLElement))return!1;if(!(D instanceof HTMLElement))return k.scrollIntoView({block:"start"}),!0;const q=D.scrollTop+k.getBoundingClientRect().top-D.getBoundingClientRect().top-24;return D.scrollTo({top:Math.max(0,q),left:0,behavior:"auto"}),!0}async function ft(E,k){await Me(),Se(),Ae(E)!==Ae(k??"")&&Ke();const D=dt(E);D&&(await Me(),je(D))}function et(E,k,D){const q=(D==null?void 0:D.size)??18,K=(D==null?void 0:D.strokeWidth)??1.5,ve=(D==null?void 0:D.viewBox)??`0 0 ${q} ${q}`,xe=document.createElementNS("http://www.w3.org/2000/svg","svg");xe.setAttribute("width",String(q)),xe.setAttribute("height",String(q)),xe.setAttribute("viewBox",ve),xe.setAttribute("fill","none"),xe.setAttribute("aria-hidden","true"),xe.setAttribute("class",k);for(const ur of E.split("||")){const Te=document.createElementNS("http://www.w3.org/2000/svg","path");Te.setAttribute("d",ur),D!=null&&D.fill?Te.setAttribute("fill","currentColor"):(Te.setAttribute("stroke","currentColor"),Te.setAttribute("stroke-width",String(K)),Te.setAttribute("stroke-linecap","round"),Te.setAttribute("stroke-linejoin","round")),xe.append(Te)}return xe}async function mt(E){var D;try{if((D=navigator.clipboard)!=null&&D.writeText)return await navigator.clipboard.writeText(E),!0}catch{}const k=document.createElement("textarea");k.value=E,k.setAttribute("readonly",""),k.style.position="fixed",k.style.opacity="0",k.style.pointerEvents="none",document.body.append(k),k.select(),k.setSelectionRange(0,E.length);try{return document.execCommand("copy")}finally{k.remove()}}function di(E){const k=Array.from(E.querySelectorAll("pre code .line"));if(k.length)return k.map(q=>q.textContent??"").join(`
`).replace(/\n$/,"");const D=E.querySelector("pre code");return((D==null?void 0:D.textContent)??"").replace(/\n$/,"")}function fi(E){const k=new URL(window.location.href);return k.hash=E,k}function mi(){const E=window.getSelection();return!!(E&&E.type==="Range"&&E.toString().trim())}function un(){document.querySelectorAll(".vp-doc h2[id], .vp-doc h3[id], .vp-doc h4[id], .vp-doc h5[id], .vp-doc h6[id]").forEach(E=>{if(E.dataset.docsHeadingCopyBound==="true")return;const k=E.querySelector(".header-anchor");if(!k)return;E.dataset.docsHeadingCopyBound="true",E.classList.add("docs-copyable-heading");const D=document.createElement("span");for(D.className="anchor-heading__content";E.childNodes.length>0;){const K=E.firstChild;if(K===k)break;D.appendChild(K)}const q=document.createElement("div");q.className="anchor-heading__icon-wrap",q.tabIndex=-1,q.appendChild(k),E.prepend(q),E.appendChild(D),k.replaceChildren(et("M0 256C0 167.6 71.6 96 160 96h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H160C98.1 144 48 194.1 48 256s50.1 112 112 112h72c13.3 0 24 10.7 24 24s-10.7 24-24 24H160C71.6 416 0 344.4 0 256zm576 0c0 88.4-71.6 160-160 160H344c-13.3 0-24-10.7-24-24s10.7-24 24-24h72c61.9 0 112-50.1 112-112s-50.1-112-112-112H344c-13.3 0-24-10.7-24-24s10.7-24 24-24h72c88.4 0 160 71.6 160 160zM184 232H392c13.3 0 24 10.7 24 24s-10.7 24-24 24H184c-13.3 0-24-10.7-24-24s10.7-24 24-24z","docs-heading-anchor__icon",{fill:!0,size:12,viewBox:"0 0 576 512"})),E.addEventListener("click",K=>{if(mi()||K.target instanceof HTMLElement&&K.target.closest("a:not(.header-anchor)"))return;const ve=K.target instanceof HTMLElement?K.target:null,xe=ve==null?void 0:ve.closest(".anchor-heading__content"),ur=ve==null?void 0:ve.closest(".header-anchor");if(!xe&&!ur)return;K.preventDefault();const Te=fi(E.id);window.history.replaceState(null,"",`${Te.pathname}${Te.search}${Te.hash}`),je(E.id),mt(Te.toString())})})}function dn(){document.querySelectorAll('.vp-doc [class*="language-"] > button.copy').forEach(E=>{if(E.querySelector(".docs-copy-button__icon")){if(E.dataset.docsCopyBound==="true")return}else{const k=et("M14.25 5.25H7.25C6.14543 5.25 5.25 6.14543 5.25 7.25V14.25C5.25 15.3546 6.14543 16.25 7.25 16.25H14.25C15.3546 16.25 16.25 15.3546 16.25 14.25V7.25C16.25 6.14543 15.3546 5.25 14.25 5.25Z||M2.80103 11.998L1.77203 5.07397C1.61003 3.98097 2.36403 2.96397 3.45603 2.80197L10.38 1.77297C11.313 1.63397 12.19 2.16297 12.528 3.00097","docs-copy-button__icon"),D=et("M2.75 9.25L6.75 13.25L15.25 4.75","docs-copy-button__icon docs-copy-button__icon--copied",{size:18,strokeWidth:2,viewBox:"0 0 18 18"}),q=document.createElement("span");q.className="sr-only",q.textContent=E.title||"Copy code",E.replaceChildren(k,D,q)}E.dataset.docsCopyBound="true",E.type="button",E.addEventListener("click",async k=>{k.preventDefault(),k.stopPropagation();const D=E.closest('[class*="language-"]');if(!(D instanceof HTMLElement))return;const q=di(D);!q||!await mt(q)||(E.classList.add("copied"),window.setTimeout(()=>{E.classList.remove("copied")},1500))})})}function fn(){ll()}return He(async()=>{var E;document.addEventListener("pointerdown",X),await ft(e.path),dn(),un(),(E=f.value)==null||E.addEventListener("scroll",Se,{passive:!0})}),oo(async()=>{await Me(),dn(),un(),je(dt(e.path))}),nr(()=>{var E;document.removeEventListener("pointerdown",X),(E=f.value)==null||E.removeEventListener("scroll",Se)}),(E,k)=>{var D,q;return J(r).layout!==!1?(L(),R("div",xl,[B.value?(L(),ke(cl,{key:0})):Y("",!0),S("div",{class:"max-lg:contents lg:flex lg:w-full","data-docs-theme":"almond",style:At(Oe.value)},[S("div",El,[S("header",kl,[S("div",_l,[S("div",Sl,[S("div",{class:ce(["transition-opacity duration-200",J(l)?"max-lg:pointer-events-none max-lg:opacity-0":""])},[S("div",Al,[S("div",Tl,[S("a",{href:_.value,class:"flex min-w-0 items-center gap-3 select-none"},[v.value?(L(),R("img",{key:0,src:v.value,alt:"",class:"relative block h-6 w-auto max-w-[156px] shrink-0 object-contain"},null,8,Il)):Y("",!0),v.value?Y("",!0):(L(),R("div",Ll,re(y.value),1))],8,Cl),S("div",Ml,[B.value?(L(),R("button",{key:0,type:"button",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":"Open search",onClick:fn},[...k[3]||(k[3]=[S("svg",{viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round",class:"h-[18px] w-[18px]","aria-hidden":"true"},[S("circle",{cx:"11",cy:"11",r:"8"}),S("path",{d:"m21 21-4.3-4.3"})],-1)])])):Y("",!0),(L(!0),R(ge,null,be(C.value,K=>(L(),R("a",{key:`mobile-${K.link}`,href:K.link,target:"_blank",rel:"noreferrer",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":K.icon},[K.icon==="github"?(L(),R("svg",Fl,[...k[4]||(k[4]=[S("path",{d:"M12 .5a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58l-.02-2.04c-3.34.73-4.04-1.42-4.04-1.42-.54-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.9 0-1.3.46-2.36 1.23-3.19-.12-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.22a11.5 11.5 0 0 1 6 0c2.3-1.54 3.3-1.22 3.3-1.22.65 1.65.24 2.87.12 3.17.76.83 1.22 1.89 1.22 3.19 0 4.58-2.81 5.59-5.49 5.89.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.22.7.83.58A12 12 0 0 0 12 .5Z"},null,-1)])])):Y("",!0)],8,Rl))),128))])]),I.value.length?(L(),R("div",{key:0,ref_key:"topNavRootMobile",ref:x,class:"relative border-t border-slate-100 px-1 py-2 lg:hidden"},[S("button",{type:"button",class:"flex w-full items-center justify-between gap-2 rounded-lg border border-slate-200/80 bg-white/80 px-3 py-2 text-left text-sm font-medium text-slate-800","aria-expanded":p.value,"aria-haspopup":"listbox","aria-label":"Documentation section",onClick:Tr(ne,["stop"])},[S("span",Pl,re(te.value),1),(L(),R("svg",{class:ce(["h-4 w-4 shrink-0 text-slate-500 transition-transform",p.value?"rotate-180":""]),viewBox:"0 0 20 20",fill:"none","aria-hidden":"true"},[...k[5]||(k[5]=[S("path",{d:"M5 7.5L10 12.5L15 7.5",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round","stroke-linejoin":"round"},null,-1)])],2))],8,Ol),p.value?(L(),R("div",{key:0,class:"absolute left-1 right-1 top-full z-[100] mt-1 max-h-[min(60vh,24rem)] overflow-y-auto rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg",role:"listbox","aria-label":`${te.value} options`},[(L(!0),R(ge,null,be(I.value,K=>(L(),R("a",{key:K.link,href:J(ze)(K.link),role:"option",class:ce(["block truncate px-3 py-2 text-sm transition",T(K)?"bg-docs-primary-soft font-medium text-docs-primary-strong":"text-slate-700 hover:bg-slate-50"]),"aria-selected":T(K),onClick:z},re(K.text),11,Dl))),128))],8,Nl)):Y("",!0)],512)):Y("",!0),J(s)?(L(),R("button",{key:1,type:"button",class:"flex h-14 w-full items-center px-1 text-left cursor-pointer focus:outline-0","aria-label":"Open navigation menu","aria-expanded":J(l),onClick:k[0]||(k[0]=(...K)=>J(c)&&J(c)(...K))},[k[7]||(k[7]=S("div",{class:"text-slate-500 transition hover:text-slate-600"},[S("span",{class:"sr-only"},"Navigation"),S("svg",{class:"h-4",fill:"currentColor",viewBox:"0 0 448 512","aria-hidden":"true"},[S("path",{d:"M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"})])],-1)),S("div",$l,[ee.value?(L(),R("div",Vl,[S("span",null,re(ee.value),1),k[6]||(k[6]=S("svg",{width:"3",height:"24",viewBox:"0 -9 3 24",class:"h-5 overflow-visible text-slate-400","aria-hidden":"true"},[S("path",{d:"M0 0L3 3L0 6",fill:"none",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round"})],-1))])):Y("",!0),S("div",Hl,re(M.value),1)])],8,zl)):Y("",!0)]),S("div",ql,[S("div",jl,[S("a",{href:_.value,class:"flex min-w-0 items-center gap-3 select-none"},[v.value?(L(),R("img",{key:0,src:v.value,alt:"",class:"relative block h-6 w-auto max-w-[156px] shrink-0 object-contain"},null,8,Ul)):Y("",!0),v.value?Y("",!0):(L(),R("div",Wl,re(y.value),1))],8,Bl),I.value.length?(L(),R("div",{key:0,ref_key:"topNavRootDesktop",ref:g,class:"relative hidden min-w-0 shrink lg:block"},[S("button",{type:"button",class:"inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200/80 bg-white/70 px-3 py-1.5 text-sm font-medium text-slate-800 backdrop-blur transition hover:bg-slate-100/90","aria-expanded":m.value,"aria-haspopup":"listbox","aria-label":"Documentation section",onClick:Tr(Q,["stop"])},[S("span",Jl,re(te.value),1),(L(),R("svg",{class:ce(["h-4 w-4 shrink-0 text-slate-500 transition-transform",m.value?"rotate-180":""]),viewBox:"0 0 20 20",fill:"none","aria-hidden":"true"},[...k[8]||(k[8]=[S("path",{d:"M5 7.5L10 12.5L15 7.5",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round","stroke-linejoin":"round"},null,-1)])],2))],8,Kl),m.value?(L(),R("div",{key:0,class:"absolute left-0 top-full z-[100] mt-1 min-w-[12rem] max-w-[min(100vw-2rem,22rem)] rounded-xl border border-slate-200/80 bg-white py-1 shadow-lg",role:"listbox","aria-label":`${te.value} options`},[(L(!0),R(ge,null,be(I.value,K=>(L(),R("a",{key:K.link,href:J(ze)(K.link),role:"option",class:ce(["block truncate px-3 py-2 text-sm transition",T(K)?"bg-docs-primary-soft font-medium text-docs-primary-strong":"text-slate-700 hover:bg-slate-50"]),"aria-selected":T(K),onClick:z},re(K.text),11,Yl))),128))],8,Gl)):Y("",!0)],512)):Y("",!0)]),S("div",Zl,[S("div",Ql,[(L(!0),R(ge,null,be(C.value,K=>(L(),R("a",{key:K.link,href:K.link,target:"_blank",rel:"noreferrer",class:"inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-white/80 hover:text-slate-900","aria-label":K.icon},[K.icon==="github"?(L(),R("svg",ec,[...k[9]||(k[9]=[S("path",{d:"M12 .5a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58l-.02-2.04c-3.34.73-4.04-1.42-4.04-1.42-.54-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.9 0-1.3.46-2.36 1.23-3.19-.12-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.22a11.5 11.5 0 0 1 6 0c2.3-1.54 3.3-1.22 3.3-1.22.65 1.65.24 2.87.12 3.17.76.83 1.22 1.89 1.22 3.19 0 4.58-2.81 5.59-5.49 5.89.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.22.7.83.58A12 12 0 0 0 12 .5Z"},null,-1)])])):Y("",!0)],8,Xl))),128))])])])],2)])])]),S("div",tc,[J(s)?(L(),R("div",{key:0,class:ce(["fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-md transition-opacity duration-300 lg:hidden",J(l)?"pointer-events-auto opacity-100":"pointer-events-none opacity-0"]),onClick:k[1]||(k[1]=(...K)=>J(o)&&J(o)(...K))},null,2)):Y("",!0),J(s)?(L(),R("button",{key:1,type:"button",class:ce(["fixed right-4 top-5 z-[60] inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/70 bg-white/95 text-slate-500 shadow-[0_8px_24px_rgba(15,23,42,0.16)] backdrop-blur transition duration-300 lg:hidden",J(l)?"pointer-events-auto opacity-100 scale-100":"pointer-events-none opacity-0 scale-95"]),"aria-label":"Close navigation menu",onClick:k[2]||(k[2]=(...K)=>J(o)&&J(o)(...K))},[...k[10]||(k[10]=[S("svg",{viewBox:"0 0 20 20",fill:"none",class:"h-5 w-5","aria-hidden":"true"},[S("path",{d:"M5 5L15 15M15 5L5 15",stroke:"currentColor","stroke-width":"1.75","stroke-linecap":"round"})],-1)])],2)):Y("",!0),J(s)?(L(),R("aside",{key:2,class:ce(["fixed inset-y-0 left-0 z-50 w-[min(22rem,calc(100vw-2.5rem))] max-w-full overflow-y-auto overscroll-contain border-r border-slate-200/80 bg-white shadow-[0_28px_90px_rgba(15,23,42,0.22)] transition-transform duration-300 lg:hidden",J(l)?"translate-x-0":"-translate-x-[105%]"])},[gt(Qi,{"logo-src":v.value,"site-title":y.value,onNavigate:J(o)},null,8,["logo-src","site-title","onNavigate"])],2)):Y("",!0),S("div",{class:ce(["mx-auto grid h-[calc(100dvh-8rem)] min-h-0 w-full max-w-[96rem] rounded-2xl px-2 lg:h-[calc(100dvh-4rem)] lg:grid-cols-[16.5rem_minmax(0,1fr)] lg:gap-x-2 lg:px-4",J(s)?"":"lg:grid-cols-[minmax(0,1fr)]"])},[J(s)?(L(),R("div",rc,[S("div",nc,[S("div",oc,[B.value?(L(),R("button",{key:0,type:"button",class:"group/search flex h-9 w-full items-center justify-between gap-2 rounded-lg bg-white pl-3.5 pr-3 text-left text-sm leading-6 text-gray-500 ring-1 ring-gray-400/30 transition-[color,box-shadow] hover:text-gray-800 hover:ring-gray-600/30","aria-label":"Open search",onClick:fn},[...k[11]||(k[11]=[Ci('<div class="flex min-w-0 items-center gap-2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 min-w-4 flex-none text-gray-700" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg><div class="min-w-0 truncate">Search...</div></div><span class="flex-none text-xs">⌘K</span>',2)])])):Y("",!0)]),S("div",{id:"navigation-items",ref_key:"navigationItems",ref:f,class:ce(["stable-scrollbar-gutter pb-4 min-h-0 flex-1 overflow-y-auto",u.value?"[mask-image:linear-gradient(transparent,black_32px)] [-webkit-mask-image:linear-gradient(transparent,black_32px)]":""])},[gt(wl)],2)])])):Y("",!0),S("div",ic,[S("div",{id:"docs-scroll-container",ref_key:"docsScrollContainer",ref:h,class:"stable-scrollbar-gutter h-full overflow-y-auto rounded-xl border border-gray-400/30 bg-white px-8 pt-8 pb-10 lg:px-10 lg:pt-10"},[S("div",ac,[S("main",sc,[J(n).isNotFound?(L(),ke(oa,{key:0})):(L(),R(ge,{key:1},[se.value?(L(),R("div",lc,re(se.value),1)):Y("",!0),gt(J(hn),{class:"vp-doc mdx-content relative prose prose-gray [contain:inline-size] isolate"}),de.value||_e.value||Ie.value||qe.value?(L(),R("div",cc,[de.value||_e.value?(L(),R("div",uc,[de.value?(L(),R("a",{key:0,href:de.value.href,target:"_blank",rel:"noreferrer",class:"font-medium text-docs-primary transition hover:text-docs-primary-strong"},re(de.value.text),9,dc)):Y("",!0),_e.value?(L(),R("div",fc,re(A.value)+": "+re(_e.value),1)):Y("",!0)])):Y("",!0),Ie.value||qe.value?(L(),R("div",mc,[(D=Ie.value)!=null&&D.link?(L(),R("a",{key:0,href:J(ze)(Ie.value.link),class:"group rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft/50"},[k[12]||(k[12]=S("div",{class:"text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"},"Previous",-1)),S("div",pc,re(Ie.value.text),1)],8,hc)):(L(),R("div",gc)),(q=qe.value)!=null&&q.link?(L(),R("a",{key:2,href:J(ze)(qe.value.link),class:"group rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-docs-primary-border-strong hover:bg-docs-primary-soft/50 sm:text-right"},[k[13]||(k[13]=S("div",{class:"text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"},"Next",-1)),S("div",bc,re(qe.value.text),1)],8,vc)):Y("",!0)])):Y("",!0)])):Y("",!0)],64))]),b.value?(L(),R("aside",yc,[S("div",wc,[S("div",xc,[gt(da)])])])):Y("",!0)])],512)])],2)])])],4)])):(L(),ke(J(hn),{key:1}))}}});function kc(t={}){return{Layout:Ec,async enhanceApp(e){var r;await((r=t.enhanceApp)==null?void 0:r.call(t,e))}}}const _c=`@layer formie-base, formie-theme-base, formie-theme;

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
}`,Sc=`@layer formie-theme-base {
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
`,Ac=`@layer formie-theme-base {
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
}`,Tc=`@layer formie-theme {
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
        --formie-color-text-muted: var(--formie-neutral-500);
        --formie-color-heading: var(--formie-neutral-900);
        --formie-color-border: var(--formie-neutral-300);
        --formie-color-border-soft: var(--formie-neutral-200);
        --formie-color-primary: var(--formie-primary-400);
        --formie-color-primary-hover: var(--formie-primary-500);
        --formie-color-primary-border: var(--formie-primary-500);
        --formie-color-primary-soft: var(--formie-primary-100);
        --formie-color-focus-ring: var(--formie-primary-300);
        --formie-color-danger: var(--formie-danger-500);
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
        --formie-button-icon-border: var(--formie-border-width) solid var(--formie-neutral-300);
        --formie-button-icon-border-hover: var(--formie-border-width) solid var(--formie-neutral-400);
        --formie-button-icon-color: var(--formie-neutral-950);
        --formie-button-opacity-disabled: 0.7;
        --formie-button-shadow-focus: 0 0 0 3px var(--formie-color-border-soft);

        /* Icons */
        --formie-icon-mask-plus: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='%23000' d='M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z'/%3E%3C/svg%3E");
        --formie-icon-mask-arrow-left: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23000' d='M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z'/%3E%3C/svg%3E");
        --formie-icon-mask-arrow-right: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23000' d='M278.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z'/%3E%3C/svg%3E");
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
        --formie-signature-border: 1px solid var(--formie-color-border);
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
}`,Cc=`@layer formie-theme {
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

}`,Ic=`@layer formie-theme {
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
`,Lc=`@layer formie-theme {
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

}`,Mc=`@layer formie-theme {
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
}`,Rc=`@layer formie-theme {
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
`,Fc=`@layer formie-theme {
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
}`,Oc=`@layer formie-theme {
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
}`,Pc=`@layer formie-theme {
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
`,Nc=`@layer formie-theme {

    .formie-input,
    .formie-textarea {
        border: var(--formie-border-width) solid var(--formie-color-border);
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
}`,Dc=`@layer formie-theme {
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
`,zc=`@layer formie-theme {
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
        border: var(--formie-border-width) solid var(--formie-color-border);
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
        border-color: color-mix(in srgb, var(--formie-color-border) 70%, var(--formie-color-heading) 30%);
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
        border: var(--formie-border-width) solid var(--formie-color-border);
        border-radius: var(--formie-radius-sm);
    }

    .formie-file-summary-container {
        margin: 0;
        padding-left: var(--formie-list-indent);
    }
}`,$c=`@layer formie-theme {

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
        border: var(--formie-border-width) solid var(--formie-color-border);
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
}`,Vc=`@layer formie-theme {
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
}`,Hc=`@layer formie-theme {
    .formie-repeater-container {
        display: grid;
        gap: var(--formie-space-4);
    }

    .formie-repeater-item-wrapper {
        position: relative;
        display: grid;
        gap: var(--formie-space-4);
        padding: var(--formie-space-4);
        border: var(--formie-border-width) solid var(--formie-color-border);
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
}`,qc=`@layer formie-theme {
    .formie-rich-text {
        border: var(--formie-border-width) solid var(--formie-color-border);
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
}`,jc=`@layer formie-theme {
    .formie-select {
        border: var(--formie-border-width) solid var(--formie-color-border);
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
}`,Bc=`@layer formie-theme {
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
        height: var(--formie-signature-height);
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
}`,Uc=`@layer formie-theme {
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
}`,Wc=`@layer formie-theme {

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
        color: var(--formie-table-th-color, var(--formie-gray-500));
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

}`,Kc=`@layer formie-theme {
    .formie-limit-number {
        font-weight: var(--formie-font-weight-semibold);
        color: var(--formie-color-text);
    }

    .formie-limit-number-error {
        color: var(--formie-color-danger);
    }
}`,Jc=`@layer formie-theme {
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
`,Gc=`.preview-gallery-page {
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
`,Yc=[{legacyEvent:"onFormieLoaded",canonicalEvent:"formie:mount:after",disposition:"approximate",target:"document"},{legacyEvent:"onFormieInit",canonicalEvent:"formie:mount:after",disposition:"approximate",target:"document"},{legacyEvent:"onFormieReady",canonicalEvent:"formie:mount:after",disposition:"safe"},{legacyEvent:"onAfterFormieSubmit",canonicalEvent:"formie:submit:result",disposition:"safe"},{legacyEvent:"onFormieSubmitError",canonicalEvent:"formie:submit:result",disposition:"safe"},{legacyEvent:"onFormiePageToggle",canonicalEvent:"formie:page:navigate:after",disposition:"safe"},{legacyEvent:"onBeforeFormieSubmit",canonicalEvent:"formie:submit:before",disposition:"approximate"},{legacyEvent:"onFormieValidate",canonicalEvent:"formie:stage:validate:before",disposition:"approximate"},{legacyEvent:"onAfterFormieValidate",canonicalEvent:"formie:stage:validate:after",disposition:"approximate"},{legacyEvent:"onFormieSubmit",canonicalEvent:"formie:submit:after",disposition:"approximate"}];function Zc(t){if(!t)return{enabled:!1,legacyDomEvents:!1,legacyValidatorEvents:!1};if(t===!0)return{enabled:!0,legacyDomEvents:!0,legacyValidatorEvents:!0};const e=t.legacyDomEvents??!0,r=t.legacyValidatorEvents??!0;return{enabled:e||r,legacyDomEvents:e,legacyValidatorEvents:r}}function Fr(t){return t}function ym(t,e){return`formie:field:${t}:${e}`}function Ft(t){return`formie:validator:${t}`}function wm(t,e){return`formie:address:${t}:${e}`}function xm(t){return`formie:file-upload:${t}`}function Em(t,e){return`formie:payment:${t}:${e}`}function Or(t){return`formie:state:${t}`}function Qc(t,e){return`formie:module:${t}:${e}`}function Xc(t){return`formie:module:${t}`}function eu(t,e,r){t.dispatchEvent(new CustomEvent(e,{bubbles:!0,detail:r}))}function tu(t,e){if(t.canonicalEvent!=="formie:submit:result")return!0;const r=e;return t.legacyEvent==="onAfterFormieSubmit"?!!(r!=null&&r.ok):t.legacyEvent==="onFormieSubmitError"?(r==null?void 0:r.ok)===!1:!0}function ru(t,e){const r=e&&typeof e=="object"?e:{},n=typeof r.pageId=="string"?r.pageId:"",i=Array.from(t.querySelectorAll("[data-formie-page-id]")),a=i.findIndex(o=>o.getAttribute("data-formie-page-id")===n);return{data:{nextPageId:n,nextPageIndex:a,totalPages:i.length}}}function nu(t,e,r,n,i){const a=globalThis.Formie||i;return t.legacyEvent==="onFormieLoaded"?{formie:a}:t.legacyEvent==="onFormieInit"?{formie:a,form:i,$form:n,formId:i.id}:t.legacyEvent==="onFormieReady"?{...e&&typeof e=="object"?e:{},form:n,target:r,instance:i}:t.legacyEvent==="onFormiePageToggle"?ru(n,e):e}function ou({target:t,form:e,instance:r,options:n,unbinds:i}){n.legacyDomEvents&&Yc.forEach(a=>{const o=s=>{if(!(s instanceof CustomEvent)||!tu(a,s.detail))return;const l=a.target==="document"?document:e;eu(l,a.legacyEvent,nu(a,s.detail,t,e,r))};t.addEventListener(Fr(a.canonicalEvent),o),i.push(()=>{t.removeEventListener(Fr(a.canonicalEvent),o)})})}function Ot(t,e,r){t.dispatchEvent(new CustomEvent(e,{bubbles:!0,detail:r}))}function wr(t,e){return!!t&&typeof t=="object"&&t.validator===e}function iu({target:t,form:e,validatorDetail:r,options:n,unbinds:i}){if(!n.legacyValidatorEvents||!r)return;const{validator:a,addValidator:o,removeValidator:s}=r,l={...r,form:e,target:t};Ot(document,"formieValidatorInitialized",l);const c=f=>{!(f instanceof CustomEvent)||!wr(f.detail,a)||Ot(document,"formieValidatorDestroyed",{...l,...f.detail})},d=f=>{!(f instanceof CustomEvent)||!wr(f.detail,a)||!(f.target instanceof Element)||e.contains(f.target)&&Ot(f.target,"formieValidatorShowError",{...f.detail,addValidator:o,removeValidator:s,form:e,target:t})},h=f=>{!(f instanceof CustomEvent)||!wr(f.detail,a)||!(f.target instanceof Element)||e.contains(f.target)&&Ot(f.target,"formieValidatorClearError",{...f.detail,addValidator:o,removeValidator:s,form:e,target:t})};document.addEventListener("formie:validator:destroy",c),document.addEventListener("formie:validator:show-error",d),document.addEventListener("formie:validator:clear-error",h),i.push(()=>{document.removeEventListener("formie:validator:destroy",c),document.removeEventListener("formie:validator:show-error",d),document.removeEventListener("formie:validator:clear-error",h)})}function ie(t,e,r){t.dispatchEvent(new CustomEvent(Fr(e),{bubbles:!0,detail:r}))}function Ro(){return globalThis}function Fo(){return Ro().__FORMIE_DEBUG__===!0}function au(t){Ro().__FORMIE_DEBUG__=t}function su(t,e,r){if(Fo()){if(typeof r>"u"){console.log(`[formie:${t}] ${e}`);return}console.log(`[formie:${t}] ${e}`,r)}}function lu(t,e,r){if(Fo()){if(typeof r>"u"){console.warn(`[formie:${t}] ${e}`);return}console.warn(`[formie:${t}] ${e}`,r)}}function Fe(t,e){const r=e?`${t}:${e}`:t;return{log:(n,i)=>{su(r,n,i)},warn:(n,i)=>{lu(r,n,i)}}}const Vt=Fe("general","page-client-event"),cu="data-formie-client-event";function uu(t){var e;return typeof window<"u"&&((e=window.CSS)!=null&&e.escape)?window.CSS.escape(t):t.replace(/\\/g,"\\\\").replace(/"/g,'\\"')}function du(t){var o,s,l;const e=t.querySelector('input[name="pageId"]'),r=(o=e==null?void 0:e.value)==null?void 0:o.trim();if(r)return r;const n=t.querySelector("[data-formie-page]:not([data-formie-page-hidden])"),i=(s=n==null?void 0:n.getAttribute("data-formie-page-id"))==null?void 0:s.trim();if(i)return i;const a=t.querySelector("[data-formie-page]");return((l=a==null?void 0:a.getAttribute("data-formie-page-id"))==null?void 0:l.trim())||null}function fu(t){if(!(t!=null&&t.trim()))return null;try{const e=JSON.parse(t);return e&&typeof e=="object"?e:null}catch{return Vt.warn("Invalid data-formie-client-event JSON.",{rawPreview:t.slice(0,80)}),null}}function mu(t){const e={};return t.forEach(r=>{const n=typeof r.label=="string"?r.label.trim():"";n&&(e[n]=typeof r.value=="string"?r.value:"")}),e}function Oo(t,e){if(e!=="submit")return;const r=du(t);if(!r){Vt.log("No submitted page id; skipping client event.");return}const n=t.querySelector(`[data-formie-page][data-formie-page-id="${uu(r)}"]`);if(!n){Vt.log("No page section for id; skipping client event.",{pageId:r});return}const i=n.getAttribute(cu);if(i===null)return;const a=fu(i);if(!a||!Array.isArray(a.fields))return;const o=mu(a.fields),s=window;s.dataLayer=s.dataLayer||[],s.dataLayer.push(o),t.dispatchEvent(new CustomEvent("formie:client-event",{bubbles:!0,detail:{payload:o}})),Vt.log("Dispatched page client event.",{pageId:r,keys:Object.keys(o)})}const Yt=new WeakMap,hu="[data-formie-form], [data-formie], form";function pu(t){return t?(Array.isArray(t)?t:[t]).flatMap(r=>String(r).split(/\s+/)).map(r=>r.trim()).filter(Boolean):[]}function Gr(t){return Array.from(new Set(t))}function gu(t){if(!t)return{};const e=Yt.get(t);if(e)return e;const r=t.closest(hu);return r?Yt.get(r)||{}:{}}function vu(t){const e={};return Object.entries(t||{}).forEach(([r,n])=>{const i=Gr(pu(n));i.length&&(e[r]=i)}),e}function Ln(t,e,r){const n=vu(e),i=r||(t instanceof HTMLFormElement?t:t.querySelector("form"));return Yt.set(t,n),i&&Yt.set(i,n),n}function Yr(t,e){return gu(t)[e]||[]}function pe(t,e,...r){const n=Gr(r.flatMap(i=>Yr(e,i)));n.length&&t.classList.add(...n)}function Et(t,e,...r){const n=Gr(r.flatMap(i=>Yr(e,i)));n.length&&t.classList.remove(...n)}function kt(t,e,r,n){Yr(e,r).forEach(i=>{t.classList.toggle(i,n)})}function bu(t,e){if(kt(t,t,"tabError",e),e){t.setAttribute("data-formie-tab-error","true");return}t.removeAttribute("data-formie-tab-error")}function Xe(t){const e=new Set;t.querySelectorAll("[data-formie-page]").forEach(r=>{const n=r,i=n.getAttribute("data-formie-page-id");i&&n.querySelector("[data-formie-field-has-error]")&&e.add(i)}),t.querySelectorAll("[data-formie-tab]").forEach(r=>{const n=r,i=n.getAttribute("data-formie-page-id");bu(n,!!i&&e.has(i))})}class yu{constructor(){this.listeners=new Map}on(e,r){var n;return this.listeners.has(e)||this.listeners.set(e,new Set),(n=this.listeners.get(e))==null||n.add(r),()=>{var i;(i=this.listeners.get(e))==null||i.delete(r)}}async emit(e,r){const n=this.listeners.get(e);if(!(!n||n.size===0))for(const i of n)await i(r)}async emitSafe(e,r){const n=this.listeners.get(e),i={eventName:e,total:(n==null?void 0:n.size)||0,succeeded:0,failed:[]};if(!n||n.size===0)return i;let a=0;for(const o of n){try{await o(r),i.succeeded+=1}catch(s){i.failed.push({index:a,error:s})}a+=1}return i}async emitParallelSafe(e,r){const n=this.listeners.get(e),i={eventName:e,total:(n==null?void 0:n.size)||0,succeeded:0,failed:[]};return!n||n.size===0||(await Promise.allSettled(Array.from(n).map(async o=>o(r)))).forEach((o,s)=>{if(o.status==="fulfilled"){i.succeeded+=1;return}i.failed.push({index:s,error:o.reason})}),i}clear(){this.listeners.clear()}}async function Po(t,e={}){const r={Accept:"application/json",...e.headers||{}};return delete r["X-Requested-With"],delete r["x-requested-with"],fetch(String(t),{method:e.method||"GET",body:e.body??null,signal:e.signal,cache:"no-store",headers:r,credentials:"same-origin"})}async function ar(t,e={}){const r=await Po(t,e);if(!r.ok)throw new Error(`Request failed (${r.status}) for ${String(t)}`);return r.json()}async function km(t,e={}){const r=await Po(t,e);if(!r.ok)throw new Error(`Request failed (${r.status}) for ${String(t)}`);return r.text()}const Ee=Fe("general","transport");function wu(t){const e={};return["theme","themeConfig","locale","siteId"].forEach(r=>{t[r]!==void 0&&(e[r]=t[r])}),e}function No(t,e="",r={}){if(Array.isArray(t)){const n=t.map(i=>typeof i=="string"?i:String(i??"")).filter(i=>i.trim()!=="");return e&&n.length&&(r[e]=(r[e]||[]).concat(n)),r}return t&&typeof t=="object"&&Object.entries(t).forEach(([n,i])=>{const a=e?`${e}.${n}`:n;No(i,a,r)}),r}function xu(t,e){const r=t.success===!0,n=t.keepSubmitLoading===!0,i=t.errors,a=No(i||{}),o=a.form||[],s={};Object.entries(a).forEach(([h,f])=>{if(h==="form")return;const u=h.split(".")[0];s[u]=(s[u]||[]).concat(f)});const l=!r&&o.length===0&&Object.keys(s).length>0?[e||"Submission failed."]:o,c=!r&&n&&l.length===0&&Object.keys(s).length===0;return{ok:r,action:t.submitAction==="back"||t.submitAction==="save"||t.submitAction==="submit"?t.submitAction:void 0,message:t.submitActionMessage||(r?"Submission completed.":c?"":l[0]||"Submission failed."),code:r?void 0:String(t.code||"SUBMIT_ERROR"),keepSubmitLoading:n,fieldErrors:Object.keys(s).length?s:void 0,formErrors:l.length?l:void 0,nextPage:t.nextPageId?{id:String(t.nextPageId)}:null,redirect:t.redirectUrl?{url:String(t.redirectUrl),target:t.submitActionTab==="new-tab"?"new-tab":"same-tab"}:null,submitData:Array.isArray(t.submitData)?t.submitData:void 0,meta:t}}async function Eu(t,e,r={}){const n=JSON.stringify({handle:e,renderOptions:r});Ee.log("requestRender start.",{endpoint:t,handle:e});const i=await ar(t,{method:"POST",body:n,headers:{"Content-Type":"application/json"}});return Ee.log("requestRender complete.",{hasHtml:!!i.html}),i}async function ku(t,e,r={}){var s;const i=JSON.stringify({query:`
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`,variables:{handle:e,input:wu(r)}});Ee.log("requestGraphqlRender start.",{endpoint:t,handle:e});const a=await ar(t,{method:"POST",body:i,headers:{"Content-Type":"application/json"}});if(Array.isArray(a.errors)&&a.errors.length>0)throw new Error(a.errors.map(l=>l.message||"Unknown GraphQL error").join("; "));if(!((s=a.data)!=null&&s.formieHtmlForm))throw new Error(`Form not found for handle "${e}".`);const o=a.data.formieHtmlForm;return Ee.log("requestGraphqlRender complete.",{hasHtml:!!o.html}),o}async function Zr(t,e,r){const n=new URL(t,window.location.origin);n.searchParams.set("handle",e),r&&n.searchParams.set("renderId",r),Ee.log("requestRefreshTokens start.",{endpoint:n.toString(),handle:e,hasRenderId:!!r});const i=await ar(n.toString());return Ee.log("requestRefreshTokens complete.",{hasRefreshTokens:!!i.refreshTokens}),i.refreshTokens||i}async function _u(t,e,r){var o;const n=new URL(t,window.location.origin),i=new FormData;if(r&&i.append("pageId",r),e){["handle","renderId","draftContextToken","draftContext","continuationToken"].forEach(d=>{var u;const h=e.querySelector(`input[name="${d}"]`),f=(u=h==null?void 0:h.value)==null?void 0:u.trim();f&&i.append(d,f)});const l=e.querySelector('input[name="CRAFT_CSRF_TOKEN"]'),c=(o=l==null?void 0:l.value)==null?void 0:o.trim();c&&i.append("CRAFT_CSRF_TOKEN",c)}Ee.log("requestSetPage start.",{requestUrl:n.toString(),pageId:r||null});const a=await ar(n.toString(),{method:"POST",body:i});return Ee.log("requestSetPage complete.",a),a}function Su(t,e){var s;const r=new URL(t,window.location.origin),n=new FormData;["handle","renderId","draftContextToken","draftContext"].forEach(l=>{var h;const c=e.querySelector(`input[name="${l}"]`),d=(h=c==null?void 0:c.value)==null?void 0:h.trim();d&&n.append(l,d)});const a=e.querySelector('input[name="CRAFT_CSRF_TOKEN"]'),o=(s=a==null?void 0:a.value)==null?void 0:s.trim();o&&n.append("CRAFT_CSRF_TOKEN",o),Ee.log("clearSubmissionOnUnload start.",{requestUrl:r.toString()});try{if(typeof navigator.sendBeacon=="function"&&navigator.sendBeacon(r.toString(),n))return}catch{}fetch(r.toString(),{method:"POST",body:n,credentials:"include",keepalive:!0,headers:{Accept:"application/json"}})}async function Au(t,e){var c,d;const r=(t.getAttribute("method")||"POST").toUpperCase(),n=t.getAttribute("action")||window.location.href,i=((c=t.dataset.formieErrorMessage)==null?void 0:c.trim())||"Submission failed.";Ee.log("submitForm start.",{method:r,action:n,submitAction:e.get("submitAction")});const a=await fetch(n,{method:r,body:e,credentials:"include",headers:{Accept:"application/json"}}),o=a.headers.get("content-type")||"";if(!o.includes("application/json"))return a.ok?(Ee.log("submitForm non-JSON success response.",{status:a.status,contentType:o}),{ok:!0,message:"Submission completed."}):(Ee.warn("submitForm non-JSON HTTP error.",{status:a.status,contentType:o}),{ok:!1,code:"HTTP_ERROR",message:`Request failed (${a.status}).`,formErrors:[`Request failed (${a.status}).`]});const s=await a.json(),l=xu(s,i);return Ee.log("submitForm JSON response normalized.",{ok:l.ok,code:l.code,hasRedirect:!!((d=l.redirect)!=null&&d.url),hasSubmitData:Array.isArray(l.submitData)&&l.submitData.length>0}),l}const Tu=["prepare","normalize","validate","screen","authorize","dispatch","finalize"],Cu=["prepare","normalize","validate","screen","authorize"],le=Fe("general","pipeline");function xr(t,e){return{ok:!1,stage:t,code:"ABORTED",message:e||"Submission aborted.",formErrors:[e||"Submission aborted."]}}function Qr(t){return Array.from(t.querySelectorAll("[data-formie-page]"))}function Iu(t){const e=Qr(t);if(!e.length)return{scope:t,final:!0};const r=e.find(n=>!n.hasAttribute("data-formie-page-hidden"))||e[e.length-1];return{scope:r,final:r===e[e.length-1]}}function Do(t){return t instanceof HTMLInputElement||t instanceof HTMLSelectElement||t instanceof HTMLTextAreaElement}function zo(t){return!(!t.name||t.disabled||t instanceof HTMLInputElement&&(t.type==="submit"||t.type==="button"||t.type==="reset"||t.type==="image"||(t.type==="checkbox"||t.type==="radio")&&!t.checked||t.type==="file"&&(!t.files||t.files.length===0)))}function $o(t,e){if(e instanceof HTMLInputElement){if(e.type==="file"){Array.from(e.files||[]).forEach(r=>{t.append(e.name,r)});return}t.append(e.name,e.value);return}if(e instanceof HTMLSelectElement&&e.multiple){Array.from(e.selectedOptions).forEach(r=>{t.append(e.name,r.value)});return}t.append(e.name,e.value)}function Lu(t,e){e.querySelectorAll("input, select, textarea").forEach(r=>{const n=Do(r)?r:null;!n||n.closest("[data-formie-page]")||zo(n)&&$o(t,n)})}function Mu(t,e){const r=new Set;return e.querySelectorAll("input, select, textarea").forEach(n=>{const i=Do(n)?n:null;!i||!i.name||i.disabled||i instanceof HTMLInputElement&&(i.type==="submit"||i.type==="button"||i.type==="reset"||i.type==="image")||(i.name.startsWith("fields[")&&r.add(i.name),zo(i)&&$o(t,i))}),r}function Ru(t,e){e.forEach(r=>{t.has(r)||t.append(r,"")})}function Mn(t,e){const r=Qr(t),n=r.find(o=>!o.hasAttribute("data-formie-page-hidden"))||null;if(!r.length||!n){const o=new FormData(t);return o.set("submitAction",e),o}const i=new FormData;Lu(i,t);const a=Mu(i,n);return Ru(i,a),i.set("submitAction",e),i}function Fu(t,e){if(e!=="submit")return!1;const r=Qr(t);return r.length?(r.find(i=>!i.hasAttribute("data-formie-page-hidden"))||r[r.length-1])===r[r.length-1]:!0}async function Vo(t,e,r,n={}){le.log("Starting submit pipeline.",{action:e,preflightOnly:n.preflightOnly===!0});let i=!1,a,o=null;const s=Fu(t,e),l={form:t,action:e,formData:Mn(t,e),abort:f=>{i=!0,a=f,le.warn("Pipeline aborted.",{reason:f})},isAborted:()=>i,abortReason:()=>a},c={prepare:async f=>{const u=f.form.querySelector('input[name="submitAction"]');return u&&(u.value=f.action),f.formData.set("submitAction",f.action),null},normalize:async()=>null,validate:async f=>{var u;if(f.action!=="submit"||n.validateOnSubmit===!1)return null;if(n.validator){const{scope:g,final:x}=Iu(f.form),m=n.validator.submit(x?f.form:g,{final:x});return m.length>0?((u=m[0])==null||u.input.focus(),{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:n.validator.config.errorMessage||"Validation failed.",fieldErrors:n.validator.getFieldErrors(m),formErrors:[n.validator.config.errorMessage||"Validation failed."]}):null}if(!f.form.checkValidity()){const g=f.form.querySelector(":invalid");return g==null||g.focus(),{ok:!1,stage:"validate",code:"VALIDATION_FAILED",message:"Validation failed.",formErrors:["Validation failed."]}}return null},screen:async()=>null,authorize:async()=>null,dispatch:async f=>{f.formData=Mn(f.form,f.action);const u=await Au(f.form,f.formData);return o=u,u},finalize:async f=>{var u;return o&&o.ok&&(u=o.redirect)!=null&&u.url&&(o.redirect.target==="new-tab"?window.open(o.redirect.url,"_blank"):window.location.href=o.redirect.url),null}};{const f=await r.emitSafe("formie:submit:before",l);f.failed.length>0&&le.warn("Submit before listeners failed.",{eventName:f.eventName,failed:f.failed.length})}if(s){const f=await r.emitSafe("formie:submit:final:before",l);f.failed.length>0&&le.warn("Final submit before listeners failed.",{eventName:f.eventName,failed:f.failed.length})}const d=n.preflightOnly?Cu:Tu;for(const f of d){if(le.log("Stage start.",{stage:f,action:e}),i)return le.warn("Stage skipped due to abort.",{stage:f,reason:a}),xr(f,a);{const g=await r.emitSafe(`formie:stage:${f}:before`,{...l,stage:f});g.failed.length>0&&le.warn("Stage before listeners failed.",{stage:f,failed:g.failed.length})}if(i){const g=xr(f,a);{const x=await r.emitSafe("formie:submit:after",g);x.failed.length>0&&le.warn("Submit after listeners failed (abort before stage).",{stage:f,failed:x.failed.length})}if(s){const x=await r.emitSafe("formie:submit:final:after",g);x.failed.length>0&&le.warn("Final submit after listeners failed (abort before stage).",{stage:f,failed:x.failed.length})}return le.warn("Aborted after stage before-hooks.",{stage:f,reason:a}),g}const u=await c[f](l);le.log("Stage runner complete.",{stage:f,hasResult:!!u,ok:u?u.ok:void 0,code:u==null?void 0:u.code});{const g=await r.emitSafe(`formie:stage:${f}:after`,{...l,stage:f,result:u});g.failed.length>0&&le.warn("Stage after listeners failed.",{stage:f,failed:g.failed.length})}if(i){const g=xr(f,a);{const x=await r.emitSafe("formie:submit:after",g);x.failed.length>0&&le.warn("Submit after listeners failed (abort after stage).",{stage:f,failed:x.failed.length})}if(s){const x=await r.emitSafe("formie:submit:final:after",g);x.failed.length>0&&le.warn("Final submit after listeners failed (abort after stage).",{stage:f,failed:x.failed.length})}return le.warn("Aborted after stage after-hooks.",{stage:f,reason:a}),g}if(u&&!u.ok){{const g=await r.emitSafe("formie:submit:after",u);g.failed.length>0&&le.warn("Submit after listeners failed (failed stage).",{stage:f,failed:g.failed.length})}if(s){const g=await r.emitSafe("formie:submit:final:after",u);g.failed.length>0&&le.warn("Final submit after listeners failed (failed stage).",{stage:f,failed:g.failed.length})}return le.warn("Pipeline short-circuited by failed stage.",{stage:f,code:u.code,message:u.message}),u}}const h=o||{ok:!0,stage:n.preflightOnly?"authorize":"finalize",message:n.preflightOnly?"Submission preflight completed.":"Submission completed."};{const f=await r.emitSafe("formie:submit:after",h);f.failed.length>0&&le.warn("Submit after listeners failed (success).",{failed:f.failed.length})}if(s){const f=await r.emitSafe("formie:submit:final:after",h);f.failed.length>0&&le.warn("Final submit after listeners failed (success).",{failed:f.failed.length})}return le.log("Pipeline completed.",{ok:h.ok,stage:h.stage,code:h.code}),h}const Ou={rule:({input:t,getRule:e})=>!e("email")||!t.value||t.value.length<1?!0:/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t.value),message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-pattern-email-message")??t.getAttribute("data-pattern-email-message")??r("{attribute} is not a valid email address.",{attribute:e})};function Pu(t){var e,r,n;return((n=(r=(e=t==null?void 0:t.querySelector("[data-formie-field-label]"))==null?void 0:e.childNodes[0])==null?void 0:r.textContent)==null?void 0:n.trim())||""}function Rn(t){const e=t.getRule("match");if(!e||e===!0||typeof e!="object"||!t.field)return null;const r=typeof e.fieldHandle=="string"?e.fieldHandle.trim():"";if(!r)return null;const n=t.form.querySelector(`[data-formie-field-handle="${r}"]`);return n?n.querySelector(t.config.fieldsSelector):null}const Nu={rule:t=>{const e=Rn(t);return e?e.value===t.input.value:!0},message:t=>{const e=Rn(t),r=e==null?void 0:e.closest("[data-formie-field-handle]"),n=Pu(r);return t.t("{name} must match {value}.",{name:t.label,value:n})}},Du={rule:({input:t,getRule:e})=>{const r=e("number");if(!r||!t.value||t.value.trim()==="")return!0;const n=parseFloat(t.value);if(Number.isNaN(n))return!1;if(r!==!0&&typeof r=="object"){const i=typeof r.min=="number"?r.min:null,a=typeof r.max=="number"?r.max:null;if(i!==null&&n<i||a!==null&&n>a)return!1}return!0},message:({input:t,label:e,getRule:r,t:n})=>{const i=r("number"),a=i!==!0&&i&&typeof i=="object"&&typeof i.min=="number"?i.min:null,o=i!==!0&&i&&typeof i=="object"&&typeof i.max=="number"?i.max:null;return a!==null&&o!==null?n("{attribute} must be between {min} and {max}.",{attribute:e,min:a,max:o}):a!==null?n("{attribute} must be no less than {min}.",{attribute:e,min:a}):o!==null?n("{attribute} must be no greater than {max}.",{attribute:e,max:o}):t.getAttribute("data-formie-pattern-number-message")??t.getAttribute("data-pattern-number-message")??n("{attribute} is not a valid number.",{attribute:e})}},zu={rule:({input:t,getRule:e})=>{var r;if(!e("required")||t.type==="hidden")return!0;if(t.type==="checkbox"||t.type==="radio"){const n=((r=t.form)==null?void 0:r.querySelectorAll(`[name="${t.name}"]:not([type="hidden"]):not([disabled])`))||[];return n.length?Array.from(n).some(i=>i instanceof HTMLInputElement&&i.checked):t instanceof HTMLInputElement?t.checked:!0}return t.value.trim()!==""},message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-required-message")??t.getAttribute("data-required-message")??r("{attribute} cannot be blank.",{attribute:e})},$u={rule:({input:t,getRule:e})=>{if(!e("url")||!t.value||t.value.length<1)return!0;try{return new URL(t.value),!0}catch{return!1}},message:({input:t,label:e,t:r})=>t.getAttribute("data-formie-pattern-url-message")??t.getAttribute("data-pattern-url-message")??r("{attribute} is not a valid URL.",{attribute:e})},Vu={required:zu,email:Ou,url:$u,number:Du,match:Nu};function Ho(){return window.FormieTranslations||{}}function Hu(){var r;if(typeof document>"u")return;const t=Array.from(document.querySelectorAll('script[type="application/json"][data-formie-translations]:not([data-formie-translations-loaded="true"])'));if(t.length===0)return;let e=null;for(const n of t){n.dataset.formieTranslationsLoaded="true";const i=(r=n.textContent)==null?void 0:r.trim();if(i)try{const a=JSON.parse(i);if(!a||Array.isArray(a)||typeof a!="object")continue;e={...e??Ho(),...a}}catch{continue}}e&&(window.FormieTranslations=e)}function qu(){return Hu(),Ho()}function $e(t,e={}){let r=qu()[t]||t;return r=r.replace(/{([a-zA-Z0-9]+)}/g,(n,i)=>Object.prototype.hasOwnProperty.call(e,i)?String(e[i]):n),r}const ju={email:/^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,url:/^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,number:/^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,color:/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,date:/(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,time:/^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,month:/^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/},Je=Fe("general","validator");function pt(t){return!!t&&(t instanceof HTMLInputElement||t instanceof HTMLSelectElement||t instanceof HTMLTextAreaElement)}function Bu(t,e){const r=(t.getAttribute("aria-describedby")||"").trim();if(!r)return;const n=r.split(/\s+/).filter(i=>i!==e);if(n.length){t.setAttribute("aria-describedby",n.join(" "));return}t.removeAttribute("aria-describedby")}function Uu(t,e){const r=(t.getAttribute("aria-describedby")||"").trim(),n=r?r.split(/\s+/):[];n.includes(e)||n.push(e),t.setAttribute("aria-describedby",n.join(" ").trim())}function Wu(t,e){t.setAttribute("aria-errormessage",e)}function Ku(t,e){t.getAttribute("aria-errormessage")===e&&t.removeAttribute("aria-errormessage")}class Ju{constructor(e,r={}){this.errors=[],this.validators={},this.boundListeners=!1,this.activated=new WeakSet,this.submitted=!1,this.initialValues=new WeakMap,this.form=e,this.onBlur=this.blurHandler.bind(this),this.onChange=this.changeHandler.bind(this),this.onInput=this.inputHandler.bind(this),this.config={live:!1,errorMessage:"",fieldContainerErrorClass:[],inputErrorClass:[],messagesClass:[],messageClass:[],fieldsSelector:'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',patterns:ju,...r},Object.entries(Vu).forEach(([n,i])=>{this.addValidator(n,i.rule,i.message)}),this.init()}init(){Je.log("Initializing validator.",{formId:this.form.id||null,live:this.config.live}),this.form.setAttribute("novalidate","true"),this.inputs().forEach(e=>{this.initialValues.set(e,this.getInputValue(e))}),this.config.live&&this.addEventListeners(),this.emitEvent(document,Ft("ready"),{validator:this})}inputs(e=null){if(pt(e))return[e];const r=e||this.form;return Array.from(r.querySelectorAll(this.config.fieldsSelector)).filter(n=>pt(n))}getInputValue(e){var r;return e instanceof HTMLInputElement&&(e.type==="checkbox"||e.type==="radio")?e.checked:e instanceof HTMLInputElement&&e.type==="file"?(r=e.files)!=null&&r.length?Array.from(e.files).map(n=>n.name).join("|"):"":e.value??""}isDirty(e){return this.initialValues.has(e)?this.getInputValue(e)!==this.initialValues.get(e):(this.initialValues.set(e,this.getInputValue(e)),!1)}shouldShowError(e){return this.submitted||this.activated.has(e)}validate(e=null,r={}){this.errors=[];const n=new Set;return this.inputs(e).forEach(i=>{let a=!1;if(!this.isVisible(i,r))return;const o=i.closest("[data-formie-field-handle]"),s=i instanceof HTMLInputElement&&(i.type==="checkbox"||i.type==="radio")?`${(o==null?void 0:o.getAttribute("data-formie-field-handle"))||""}:${i.name}`:null;if(s){if(n.has(s))return;n.add(s)}this.shouldShowError(i)&&this.removeError(i);const l=this.getValidatorCallbackOptions(i);Object.entries(this.validators).forEach(([c,d])=>{var f;if(!d.validate(l)){const u=this.getErrorMessage(i,c,d,l);this.shouldShowError(i)&&!a&&this.showError(i,c,u),this.errors.push({input:i,field:l.field,validator:c,message:u,handle:((f=l.field)==null?void 0:f.getAttribute("data-formie-field-handle"))||null,result:!1}),a=!0}}),!a&&this.shouldShowError(i)&&this.removeError(i)}),Je.log("Validation pass complete.",{errorCount:this.errors.length,includeHiddenPages:r.includeHiddenPages===!0}),this.errors}removeAllErrors(){this.inputs().forEach(e=>{this.removeError(e)})}removeError(e){var a;const r=e.closest("[data-formie-field-handle]");if(!r){e.removeAttribute("aria-invalid");return}const n=r.querySelector("[data-formie-field-errors]"),i=(n==null?void 0:n.id)||"";r.querySelectorAll("[data-formie-field-error]").forEach(o=>{o.remove()}),n&&(n.innerHTML=""),r.querySelectorAll("input, select, textarea").forEach(o=>{const s=o;s.removeAttribute("aria-invalid"),this.config.inputErrorClass.length&&s.classList.remove(...this.config.inputErrorClass),s.removeAttribute("data-formie-input-has-error"),i&&Bu(s,i),r.querySelectorAll("[data-formie-field-error]").forEach(l=>{const c=l.id;c&&Ku(s,c)})});for(let o=r;o;o=(a=o.parentElement)==null?void 0:a.closest("[data-formie-field-handle]"))this.config.fieldContainerErrorClass.length&&o.classList.remove(...this.config.fieldContainerErrorClass),o.removeAttribute("data-formie-field-has-error");this.emitEvent(e,Ft("clear-error"),{validator:this}),Xe(this.form)}showError(e,r,n){var c;const i=e.closest("[data-formie-field-handle]");if(!i)return;let a=i.querySelector("[data-formie-field-errors]");a||(a=document.createElement("div"),a.setAttribute("data-formie-field-errors","true"),this.config.messagesClass.length&&a.classList.add(...this.config.messagesClass),i.appendChild(a)),this.config.messagesClass.length&&a.classList.add(...this.config.messagesClass),a.innerHTML="";const o=i.getAttribute("data-formie-field-handle")||"field",s=`${o}-error`;a.id=a.id||`${o}-errors`,a.setAttribute("aria-live","polite"),a.setAttribute("aria-atomic","true");const l=document.createElement("div");l.setAttribute("data-formie-field-error","true"),l.setAttribute(`data-formie-field-error-${r}`,"true"),l.setAttribute("id",s),l.setAttribute("role","alert"),this.config.messageClass.length&&l.classList.add(...this.config.messageClass),l.textContent=n,a.appendChild(l),i.setAttribute("data-formie-field-has-error","true"),i.querySelectorAll("input, select, textarea").forEach(d=>{const h=d;h.setAttribute("aria-invalid","true"),this.config.inputErrorClass.length&&h.classList.add(...this.config.inputErrorClass),h.setAttribute("data-formie-input-has-error","true"),Uu(h,a.id),Wu(h,s)});for(let d=i;d;d=(c=d.parentElement)==null?void 0:c.closest("[data-formie-field-handle]"))this.config.fieldContainerErrorClass.length&&d.classList.add(...this.config.fieldContainerErrorClass),d.setAttribute("data-formie-field-has-error","true");this.emitEvent(e,Ft("show-error"),{validator:this,validatorName:r,errorMessage:n}),Xe(this.form)}getValidatorCallbackOptions(e){var a,o,s;const r=e.closest("[data-formie-field-handle]"),n=((s=(o=(a=r==null?void 0:r.querySelector("[data-formie-field-label]"))==null?void 0:a.childNodes[0])==null?void 0:o.textContent)==null?void 0:s.trim())??"",i=this.parseValidationRules(r==null?void 0:r.getAttribute("data-formie-validation"));return{t:$e,input:e,label:n,field:r,form:this.form,config:this.config,rules:i,getRule:l=>this.getRule(r,l)}}getErrorMessage(e,r,n,i){return(typeof n.errorMessage=="function"?n.errorMessage(i):n.errorMessage)??$e("{attribute} is invalid.",{attribute:i.label})}getErrors(){return this.errors}getFieldErrors(e=this.errors){const r={};return e.forEach(n=>{var i;!n.handle||(i=r[n.handle])!=null&&i.length||(r[n.handle]=[n.message])}),r}getRule(e,r){if(!e)return!1;const n=this.parseValidationRules(e.getAttribute("data-formie-validation"));return Object.prototype.hasOwnProperty.call(n,r)?n[r]:!1}parseValidationRules(e){const r={};if(!e)return r;let n=null;try{n=JSON.parse(e)}catch{return Je.warn("Invalid validation rules payload.",{formId:this.form.id||null}),r}return Array.isArray(n)&&n.forEach(i=>{if(!i||typeof i!="object"||Array.isArray(i))return;const a=i,o=typeof a.type=="string"?a.type.trim():"";o&&(r[o]=a)}),r}destroy(){Je.log("Destroying validator.",{formId:this.form.id||null}),this.removeEventListeners(),this.form.removeAttribute("novalidate"),this.emitEvent(document,Ft("destroy"),{validator:this})}isVisible(e,r={}){return e.closest("[data-formie-conditionally-hidden]")?!1:e.closest("[data-formie-page-hidden]")?!!r.includeHiddenPages:!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length)}blurHandler(e){var r;!(e.target instanceof HTMLElement)||!pt(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form))||e instanceof CustomEvent||e.target instanceof HTMLInputElement&&e.target.type==="file"||e.target instanceof HTMLInputElement&&(e.target.type==="checkbox"||e.target.type==="radio")||(this.isDirty(e.target)&&this.activated.add(e.target),this.shouldShowError(e.target)&&this.validate(e.target))}changeHandler(e){var r;if(!(!(e.target instanceof HTMLElement)||!pt(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form)))&&!(e instanceof CustomEvent)){if(e.target instanceof HTMLSelectElement){this.activated.add(e.target),this.validate(e.target);return}e.target instanceof HTMLInputElement&&(e.target.type!=="file"&&e.target.type!=="checkbox"&&e.target.type!=="radio"||(this.activated.add(e.target),this.validate(e.target)))}}inputHandler(e){var r;!(e.target instanceof HTMLElement)||!pt(e.target)||!((r=e.target.form)!=null&&r.isSameNode(this.form))||e instanceof CustomEvent||e.target instanceof HTMLInputElement&&(e.target.type==="checkbox"||e.target.type==="radio")||this.shouldShowError(e.target)&&this.validate(e.target)}submit(e=null,{final:r=!1}={}){return this.submitted=!0,Je.log("Submit validation requested.",{final:r}),this.boundListeners||this.addEventListeners(),this.removeAllErrors(),this.validate(e,{includeHiddenPages:r})}resetLiveState(){this.submitted=!1,this.activated=new WeakSet,this.errors=[],this.removeAllErrors()}addEventListeners(){this.boundListeners||(this.form.addEventListener("blur",this.onBlur,!0),this.form.addEventListener("change",this.onChange,!1),this.form.addEventListener("input",this.onInput,!1),this.boundListeners=!0,Je.log("Event listeners attached."))}removeEventListeners(){this.form.removeEventListener("blur",this.onBlur,!0),this.form.removeEventListener("change",this.onChange,!1),this.form.removeEventListener("input",this.onInput,!1),this.boundListeners=!1,Je.log("Event listeners removed.")}emitEvent(e,r,n={}){e.dispatchEvent(new CustomEvent(r,{bubbles:!0,detail:n}))}addValidator(e,r,n){this.validators[e]={validate:r,errorMessage:n}}removeValidator(e){delete this.validators[e]}}const Gu="STALE_SUBMISSION_STATE",Fn=new WeakMap,Zt=new WeakMap,Be=Fe("general","submit-result");function Pr(t,e,r){let n=t.querySelector(`input[name="${e}"]`);n||(n=document.createElement("input"),n.type="hidden",n.name=e,t.appendChild(n)),n.value=r}function On(t,e){t.setAttribute("data-formie-internal-navigation",e)}function vt(t,e){const r=t.querySelector(`input[name="${e}"]`);r==null||r.remove()}function Yu(t,e){try{const r=new URL(t,window.location.href);return r.searchParams.delete(e),r.toString()}catch{return t}}function Zu(t){try{return new URL(t,window.location.href).origin===window.location.origin}catch{return!1}}function qo(t){return Array.from(t.querySelectorAll("[data-formie-page]"))}function Qu(t){return Array.from(t.querySelectorAll("[data-formie-tab]"))}function Xu(t,e,r){return e<0||r<1?0:(t.dataset.formieProgressCalculation==="page-position"?"page-position":"completion")==="page-position"?Math.round((e+1)/r*100):Math.round(e/r*100)}function ed(t){return t<=0?"start":t>=100?"end":"middle"}function Pn(t){return(t.dataset.formieSubmitAction||"").trim()}function Nn(t){const e=t.dataset.formieSubmitActionFormHide;if(e===void 0)return!1;const r=e.trim().toLowerCase();return r==="true"||r==="1"||r===""}function Xr(t,e){const r=["[data-formie-form-header]","[data-formie-form-navigation]","[data-formie-form-body]","[data-formie-form-footer]"];t.toggleAttribute("data-formie-form-hidden",e),r.forEach(n=>{t.querySelectorAll(n).forEach(i=>{const a=i;e?a.hidden=!0:a.hidden=!1})})}function De(t){const e=Fn.get(t);typeof e=="number"&&(window.clearTimeout(e),Fn.delete(t))}function td(t,e){Zt.has(t)||Zt.set(t,t.innerHTML),t.textContent=e}function Nr(t){const e=Zt.get(t);e!==void 0&&(t.innerHTML=e,Zt.delete(t))}function rd(t,e){const r=t.querySelector("[data-formie-progress-bar]"),n=t.querySelector("[data-formie-progress-value]");r&&(r.style.width=`${e}%`,r.setAttribute("aria-valuenow",`${e}`),r.setAttribute("data-formie-progress-state",ed(e)),n&&(n.textContent=`${e}%`,n.setAttribute("data-formie-progress-value",`${e}`)))}function nd(t,e){var n;if(!e)return;const r=(t.dataset.formieLoadingIndicator||"").trim();if(r){if(e.setAttribute("data-formie-loading-indicator",r),r==="spinner"){kt(e,t,"loading",!0),Nr(e),e.removeAttribute("data-formie-loading-text");return}if(r==="text"){const i=(t.dataset.formieLoadingIndicatorText||"").trim(),a=((n=e.textContent)==null?void 0:n.trim())||"",o=i||a;e.setAttribute("data-formie-loading-text",o),td(e,o);return}Nr(e),e.removeAttribute("data-formie-loading-text")}}function jo(t){return Array.from(t.querySelectorAll("[data-formie-action]"))}function Bo(t,e){if(t.getAttribute("data-formie-loading")==="true")return;t.setAttribute("data-formie-loading","true"),jo(t).forEach(n=>{"disabled"in n&&(n.disabled?n.setAttribute("data-formie-was-disabled","true"):n.removeAttribute("data-formie-was-disabled"),n.disabled=!0)}),e&&(e.setAttribute("data-formie-loading","true"),nd(t,e))}function Qt(t){t.removeAttribute("data-formie-loading"),jo(t).forEach(r=>{if("disabled"in r){const n=r,i=n.getAttribute("data-formie-was-disabled")==="true";n.disabled=i}Nr(r),r.removeAttribute("data-formie-was-disabled"),r.removeAttribute("data-formie-loading"),kt(r,t,"loading",!1),r.removeAttribute("data-formie-loading-indicator"),r.removeAttribute("data-formie-loading-text")})}function en(t,e){const r=qo(t),n=Qu(t),i=r.findIndex(a=>a.getAttribute("data-formie-page-id")===e);if(r.forEach(a=>{a.getAttribute("data-formie-page-id")===e?(a.removeAttribute("data-formie-page-hidden"),Et(a,t,"pageHidden")):(a.setAttribute("data-formie-page-hidden","true"),pe(a,t,"pageHidden"))}),n.forEach((a,o)=>{const s=a.getAttribute("data-formie-page-id")===e,l=i>-1&&o<i;kt(a,t,"tabCurrent",s),kt(a,t,"tabComplete",l),s?a.setAttribute("aria-current","page"):a.removeAttribute("aria-current"),l?a.setAttribute("data-formie-tab-complete","true"):a.removeAttribute("data-formie-tab-complete")}),i>-1&&r.length>0){const a=Xu(t,i,r.length);rd(t,a)}Pr(t,"pageId",e),Xe(t)}function od(t,e){var i,a,o,s;const r=(i=e.meta)==null?void 0:i.submissionUid;typeof r=="string"&&r.trim()!==""&&Pr(t,"submissionUid",r);const n=(s=(o=(a=e.meta)==null?void 0:a.session)==null?void 0:o.continuation)==null?void 0:s.continuationToken;typeof n=="string"&&n.trim()!==""?Pr(t,"continuationToken",n):vt(t,"continuationToken")}function id(t){const e=t.getAttribute("action");e&&t.setAttribute("action",Yu(e,"resumeToken"));try{const r=new URL(window.location.href);if(!r.searchParams.has("resumeToken"))return;r.searchParams.delete("resumeToken"),window.history.replaceState({},document.title,`${r.pathname}${r.search}${r.hash}`)}catch{}}function ad(t,e){var a;const r=(a=e.meta)==null?void 0:a.resumeUrl;if(typeof r!="string"||r.trim()==="")return;const n=r.trim();if(!Zu(n))return;t.getAttribute("action")&&t.setAttribute("action",n);try{const o=new URL(n,window.location.href);window.history.replaceState({},document.title,`${o.pathname}${o.search}${o.hash}`)}catch{}}function Pt(t,e={}){var a;const n=t.formieValidation,i=(a=qo(t)[0])==null?void 0:a.getAttribute("data-formie-page-id");if(De(t),t.reset(),e.preserveHiddenState||Xr(t,!1),vt(t,"submissionId"),vt(t,"submissionUid"),vt(t,"continuationToken"),vt(t,"pageId"),id(t),n==null||n.resetLiveState(),i){en(t,i),t.dispatchEvent(new CustomEvent(Or("reset"),{bubbles:!0}));return}Xe(t),t.dispatchEvent(new CustomEvent(Or("reset"),{bubbles:!0}))}function sd(t){var e;return t.code===Gu||((e=t.meta)==null?void 0:e.resetState)===!0}function ld(t,e){const r=e.submitData,n=new Set;let i=!1;if(Array.isArray(r)&&r.length>0){const d=r.filter(h=>typeof h=="object"&&h!==null&&"event"in h&&typeof h.event=="string");for(const h of d){const f=h.event;n.add(f),Be.log("Dispatching submitData event.",{eventName:f}),f.startsWith("formie:payment:")&&(i=!0),t.dispatchEvent(new CustomEvent(f,{bubbles:!0,detail:{data:h.data}}))}}const a=e.meta||{},o=(a.paymentAction&&typeof a.paymentAction=="object"?a.paymentAction:null)||(a.paymentDecision&&typeof a.paymentDecision=="object"?a.paymentDecision.action:null),s=o?String(o.event||""):"",l=o?o.payload:void 0,c=s;return c&&!n.has(c)&&(c.startsWith("formie:payment:")&&(i=!0),t.dispatchEvent(new CustomEvent(c,{bubbles:!0,detail:{data:l}})),Be.log("Dispatching fallback payment action event.",{eventName:c})),{hasPaymentFollowUpEvent:i}}function cd(t,e,r){var i,a,o,s,l;if(Be.log("Applying submit result state.",{ok:e.ok,action:r,code:e.code,hasRedirect:!!((i=e.redirect)!=null&&i.url),hasSubmitData:Array.isArray(e.submitData)&&e.submitData.length>0}),sd(e)){Pt(t),Be.log("Resetting state due to stale/reset marker.");return}const n=ld(t,e);if(!e.ok&&((a=e.redirect)!=null&&a.url)&&!n.hasPaymentFollowUpEvent){Be.log("Applying redirect fallback for failed result.",{url:e.redirect.url,target:e.redirect.target}),De(t),e.redirect.target==="new-tab"?window.open(e.redirect.url,"_blank"):(On(t,"redirect"),window.location.href=e.redirect.url);return}if(od(t,e),!e.ok){Be.log("Non-redirect failure; keeping current form state."),De(t);return}if(Oo(t,r),(o=e.nextPage)!=null&&o.id){De(t);const d=t.formieValidation;d==null||d.resetLiveState(),en(t,e.nextPage.id),Be.log("Advanced to next page.",{nextPageId:e.nextPage.id});return}if(r==="save"){De(t),ad(t,e),Be.log("Applied save/resume token state.");return}if(r==="submit"&&!((s=e.redirect)!=null&&s.url)){const c=Pn(t),d=c==="message"&&Nn(t);if(c==="reload"){De(t),On(t,"reload"),window.location.reload();return}if(c==="reset"){Pt(t);return}De(t),Pt(t,{preserveHiddenState:d});return}if(r==="submit"&&((l=e.redirect)!=null&&l.url)&&e.redirect.target==="new-tab"){const d=Pn(t)==="message"&&Nn(t);De(t),Pt(t,{preserveHiddenState:d});return}De(t)}const Xt=new WeakMap;function Uo(t){return(t.dataset.formieSubmitAction||"").trim()}function ud(t){return(t.dataset.formieErrorMessagePosition||"top-form").trim()||"top-form"}function Wo(t){return(t.dataset.formieSubmitActionMessagePosition||"").trim()}function dd(t){const e=(t.dataset.formieSubmitActionMessageTimeout||"").trim();if(!e)return null;const r=Number.parseFloat(e);return!Number.isFinite(r)||r<0?null:Math.round(r*1e3)}function tn(t){const e=t.dataset.formieSubmitActionFormHide;if(e===void 0)return!1;const r=e.trim().toLowerCase();return r==="true"||r==="1"||r===""}function fd(t){const e=Xt.get(t);typeof e=="number"&&(window.clearTimeout(e),Xt.delete(t))}function Ko(t){return t.querySelector("[data-formie-form-messages-top]")||t}function Jo(t){return t.querySelector("[data-formie-form-messages-bottom]")||t}function md(t,e){return e==="bottom-form"?Jo(t):Ko(t)}function hd(t,e){return e==="top-form"?Ko(t):e==="bottom-form"&&!tn(t)?Jo(t):t}function pd(t){const e=ud(t),r=md(t,e);let n=r.querySelector("[data-formie-error-container], [data-formie-errors]");return n||(n=document.createElement("div"),n.setAttribute("data-formie-errors","true"),pe(n,t,"errors")),n.setAttribute("data-formie-error-container","true"),e==="bottom-form"?r.append(n):r.prepend(n),n}function gd(t,e){let r=e.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");return r||(r=document.createElement("div"),r.setAttribute("data-formie-error-message-container","true"),e.appendChild(r)),r.setAttribute("data-formie-message","true"),r.setAttribute("data-formie-message-error","true"),pe(r,t,"message","messageError"),r.setAttribute("role","alert"),r.setAttribute("aria-live","polite"),r.setAttribute("aria-atomic","true"),r}function vd(t,e){let r=t.querySelector("[data-formie-success-container]");const n=hd(t,e);return r||(r=document.createElement("div"),r.setAttribute("data-formie-success-container","true"),pe(r,t,"successes")),e==="bottom-form"?n.append(r):n.prepend(r),r}function bd(t){let e=t.querySelector("[data-formie-field-errors]");return e||(e=document.createElement("div"),e.setAttribute("data-formie-field-errors","true"),pe(e,t,"fieldErrors"),t.appendChild(e)),e}function yd(t,e){const r=(t.getAttribute("aria-describedby")||"").trim();if(!r)return;const n=r.split(/\s+/).filter(i=>i!==e).join(" ").trim();if(n){t.setAttribute("aria-describedby",n);return}t.removeAttribute("aria-describedby")}function wd(t,e){t.setAttribute("aria-errormessage",e)}function xd(t,e){t.getAttribute("aria-errormessage")===e&&t.removeAttribute("aria-errormessage")}function Go(t){t.querySelectorAll("[data-formie-field-handle]").forEach(e=>{const r=e,n=r.querySelector("[data-formie-field-errors]"),i=(n==null?void 0:n.id)||"",a=Array.from(r.querySelectorAll("[data-formie-field-error]")).map(o=>o.id).filter(Boolean);Et(r,t,"fieldLayoutError"),r.removeAttribute("data-formie-field-has-error"),r.querySelectorAll("[data-formie-field-error]").forEach(o=>{o.remove()}),n&&!n.querySelector("[data-formie-field-error]")&&(n.innerHTML=""),r.querySelectorAll("input, select, textarea").forEach(o=>{const s=o;s.removeAttribute("aria-invalid"),Et(s,t,"fieldControlError"),s.removeAttribute("data-formie-input-has-error"),i&&yd(s,i),a.forEach(l=>{xd(s,l)})})}),Xe(t)}function Yo(t){t.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach(e=>{const r=e;r.querySelectorAll("[data-formie-error]").forEach(n=>{n.remove()}),Et(r,t,"message","messageError"),r.removeAttribute("data-formie-message"),r.removeAttribute("data-formie-message-error"),r.removeAttribute("role"),r.removeAttribute("aria-live"),r.removeAttribute("aria-atomic"),r.querySelector("[data-formie-error]")||(r.innerHTML="")})}function rn(t){fd(t),t.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach(e=>{e.remove()}),t.querySelectorAll("[data-formie-success-container]").forEach(e=>{const r=e;r.querySelectorAll("[data-formie-success]").forEach(n=>{n.remove()}),Et(r,t,"message","messageSuccess"),r.removeAttribute("data-formie-message"),r.removeAttribute("data-formie-message-success"),r.removeAttribute("role"),r.removeAttribute("aria-live"),r.removeAttribute("aria-atomic"),r.querySelector("[data-formie-success]")||(r.innerHTML="")}),Uo(t)==="message"&&tn(t)||Xr(t,!1)}function Zo(t){t.querySelectorAll('[aria-invalid="true"]').forEach(e=>{e.removeAttribute("aria-invalid")})}function Dn(t,e){const r=(t.getAttribute("aria-describedby")||"").trim(),n=r?r.split(/\s+/):[];n.includes(e)||n.push(e),t.setAttribute("aria-describedby",n.join(" ").trim())}function Ed(t,e){Object.entries(e).forEach(([r,n])=>{var l;const i=t.querySelector(`[data-formie-field-handle="${r}"]`);if(!i)return;const a=bd(i),o=a.id&&a.id.trim()?a.id:`${r}-errors`;a.id=o,a.setAttribute("aria-live","polite"),a.setAttribute("aria-atomic","true"),pe(i,t,"fieldLayoutError"),i.setAttribute("data-formie-field-has-error","true"),n.forEach((c,d)=>{const h=document.createElement("div");h.setAttribute("data-formie-field-error","true"),h.setAttribute("role","alert"),h.id=`${o}-${d+1}`,pe(h,t,"fieldError"),h.textContent=c,a.appendChild(h)});const s=(l=a.querySelector("[data-formie-field-error]"))==null?void 0:l.id;i.querySelectorAll("input, select, textarea").forEach(c=>{const d=c;d.setAttribute("aria-invalid","true"),pe(d,t,"fieldControlError"),d.setAttribute("data-formie-input-has-error","true"),Dn(d,o),s&&wd(d,s);const h=i.querySelector("[data-formie-instructions]");h!=null&&h.id&&Dn(d,h.id)})}),Xe(t)}function zn(t,e){const r=pd(t),n=gd(t,r);pe(r,t,"errors"),e.forEach(i=>{const a=document.createElement("div");a.setAttribute("data-formie-error","true"),a.setAttribute("role","alert"),pe(a,t,"error"),a.innerHTML=i,n.appendChild(a)})}function kd(t,e){return!e.message||e.nextPage||e.redirect?!1:e.action==="save"?!0:Uo(t)==="message"&&Wo(t)!==""}function _d(t,e){const r=Wo(t);if(!r)return;const n=vd(t,r);pe(n,t,"message","messageSuccess"),n.setAttribute("data-formie-message","true"),n.setAttribute("data-formie-message-success","true"),n.setAttribute("role","status"),n.setAttribute("aria-live","polite"),n.setAttribute("aria-atomic","true");const i=document.createElement("div");i.setAttribute("data-formie-success","true"),pe(i,t,"success"),i.innerHTML=e,n.appendChild(i),tn(t)&&Xr(t,!0);const a=dd(t);if(a!==null){const o=window.setTimeout(()=>{Xt.delete(t),rn(t)},a);Xt.set(t,o)}}function er(t,e){var r;if(Go(t),Yo(t),rn(t),Zo(t),e.ok){kd(t,e)&&_d(t,e.message||"");return}if(e.fieldErrors&&Ed(t,e.fieldErrors),(r=e.formErrors)!=null&&r.length){zn(t,e.formErrors);return}!e.fieldErrors&&e.message&&zn(t,[e.message])}const Sd=Fe("general","submit-flow");function Ad(t){return!(!t.ok&&t.stage==="validate")}function Qo(t){var e;return t?!!(t.keepSubmitLoading===!0||t.ok&&((e=t.redirect)!=null&&e.url)&&t.redirect.target!=="new-tab"):!1}function Xo(t){Go(t),Yo(t),rn(t),Zo(t)}async function ei(t){const{id:e,target:r,form:n,bus:i,validator:a,validateOnSubmit:o,action:s,submitter:l,waitForSubmitDelay:c,onRefreshTokensAfterSubmit:d,dispatchSubmitResult:h}=t;Xo(n),Bo(n,l||null);let f={ok:!1,code:"SUBMIT_ERROR",message:"Submission failed.",formErrors:["Submission failed."]};try{await c(n),f=await Vo(n,s,i,{validator:a,validateOnSubmit:o}),er(n,f),cd(n,f,s),Ad(f)&&await d(f),h(f)}catch(u){f={ok:!1,code:"SUBMIT_ERROR",message:u instanceof Error?u.message:"Submission failed.",formErrors:[u instanceof Error?u.message:"Submission failed."]},er(n,f),h(f),Sd.warn("Submit failed with exception.",{id:e,action:s,target:r,error:u instanceof Error?u.message:u})}finally{Qo(f)||Qt(n)}return f}class Td{constructor(){this.modules=new Map}register(e,r={}){const n=this.modules.get(e.id);return n===e?!0:n&&!r.replace?(console.warn(`[formie] Module "${e.id}" is already registered. Pass { replace: true } to override the existing definition.`),!1):(this.modules.set(e.id,e),!0)}unregister(e){this.modules.delete(e)}get(e){return this.modules.get(e)||null}getAll(){return Array.from(this.modules.values())}}const Cd={"address-finder":()=>O(()=>import("./address-finder.BsV6S_7d.js"),__vite__mapDeps([0,1,2])).then(t=>t.addressFinderModule),"google-address":()=>O(()=>import("./google-address.BnBb3Kl1.js"),__vite__mapDeps([3,1,2])).then(t=>t.googleAddressModule),loqate:()=>O(()=>import("./loqate.Bw5quUlY.js"),__vite__mapDeps([4,1,2])).then(t=>t.loqateModule),"place-kit":()=>O(()=>import("./place-kit.BEpu7dLT.js"),__vite__mapDeps([5,2,6])).then(t=>t.placeKitModule)},Id={"captcha-eu":()=>O(()=>import("./captcha-eu.gIDjzctq.js"),__vite__mapDeps([7,1,2])).then(t=>t.captchaEuModule),"friendly-captcha-v1":()=>O(()=>import("./friendly-captcha-v1.C9cwjy9h.js"),__vite__mapDeps([8,2])).then(t=>t.friendlyCaptchaV1Module),"friendly-captcha-v2":()=>O(()=>import("./friendly-captcha-v2.Cg0Lk-Kx.js"),__vite__mapDeps([9,2])).then(t=>t.friendlyCaptchaV2Module),hcaptcha:()=>O(()=>import("./hcaptcha.Ur0NisHP.js"),__vite__mapDeps([10,1,2])).then(t=>t.hcaptchaModule),"recaptcha-enterprise":()=>O(()=>import("./recaptcha-enterprise.iUURyeXe.js"),__vite__mapDeps([11,12,1,2])).then(t=>t.recaptchaEnterpriseModule),"recaptcha-v2-checkbox":()=>O(()=>import("./recaptcha-v2-checkbox.z5ocNAm_.js"),__vite__mapDeps([13,12,1,2])).then(t=>t.recaptchaV2CheckboxModule),"recaptcha-v2-invisible":()=>O(()=>import("./recaptcha-v2-invisible.guRJWufw.js"),__vite__mapDeps([14,12,1,2])).then(t=>t.recaptchaV2InvisibleModule),"recaptcha-v3":()=>O(()=>import("./recaptcha-v3.DLS6BsKc.js"),__vite__mapDeps([15,12,1,2])).then(t=>t.recaptchaV3Module),snaptcha:()=>O(()=>import("./snaptcha.tJT99HSA.js"),__vite__mapDeps([16,2])).then(t=>t.snaptchaModule),turnstile:()=>O(()=>import("./turnstile.DsZlCtkL.js"),__vite__mapDeps([17,1,2])).then(t=>t.turnstileModule)},Ld={calculations:()=>O(()=>import("./calculations.CsCnnHVp.js"),__vite__mapDeps([18,19,20,2])).then(t=>t.calculationsModule),"checkbox-radio":()=>O(()=>import("./checkbox-radio.wQR6jl23.js"),__vite__mapDeps([21,20,2])).then(t=>t.checkboxRadioModule),conditions:()=>O(()=>import("./conditions.BMATD62A.js"),__vite__mapDeps([22,19,20,2])).then(t=>t.conditionsModule),"date-picker":()=>O(()=>import("./date-picker.DGAmdIaX.js"),__vite__mapDeps([23,20,6,2])).then(t=>t.datePickerModule),"file-upload":()=>O(()=>import("./file-upload.Bhpgej_B.js"),__vite__mapDeps([24,20,6,2])).then(t=>t.fileUploadModule),hidden:()=>O(()=>import("./hidden.DI846PWm.js"),__vite__mapDeps([25,20,2])).then(t=>t.hiddenModule),"phone-country":()=>O(()=>import("./phone-country.BYawHTaa.js"),__vite__mapDeps([26,2,20,6])).then(t=>t.phoneCountryModule),repeater:()=>O(()=>import("./repeater.BZRxNbQU.js"),__vite__mapDeps([27,20,6,2])).then(t=>t.repeaterModule),"rich-text":()=>O(()=>import("./rich-text.D-voHtqM.js"),__vite__mapDeps([28,20,6,2])).then(t=>t.richTextModule),signature:()=>O(()=>import("./signature.B5jMOttl.js"),__vite__mapDeps([29,20,6,2])).then(t=>t.signatureModule),summary:()=>O(()=>import("./summary.CU6HVw1B.js"),__vite__mapDeps([30,20,6,2])).then(t=>t.summaryModule),table:()=>O(()=>import("./table.DHUenowH.js"),__vite__mapDeps([31,20,6,2])).then(t=>t.tableModule),"text-limit":()=>O(()=>import("./text-limit.PLvIlYHF.js"),__vite__mapDeps([32,19,20,6,2])).then(t=>t.textLimitModule)},Md={bpoint:()=>O(()=>import("./bpoint.yE9ryjUa.js"),__vite__mapDeps([33,2])).then(t=>t.bpointModule),eway:()=>O(()=>import("./eway.DqT8p9QE.js"),__vite__mapDeps([34,1,2])).then(t=>t.ewayModule),"go-cardless":()=>O(()=>import("./go-cardless.s1BYHWop.js"),__vite__mapDeps([35,2])).then(t=>t.goCardlessModule),mollie:()=>O(()=>import("./mollie.B6ly0jc5.js"),__vite__mapDeps([36,2])).then(t=>t.mollieModule),moneris:()=>O(()=>import("./moneris.Ct1bgkwu.js"),__vite__mapDeps([37,2])).then(t=>t.monerisModule),opayo:()=>O(()=>import("./opayo.zjsCQuZX.js"),__vite__mapDeps([38,1,2])).then(t=>t.opayoModule),paddle:()=>O(()=>import("./paddle.y1hZc5MD.js"),__vite__mapDeps([39,1,2])).then(t=>t.paddleModule),paypal:()=>O(()=>import("./paypal.CG4CbbfX.js"),__vite__mapDeps([40,6,1,2])).then(t=>t.paypalModule),payway:()=>O(()=>import("./payway.oct1FwdC.js"),__vite__mapDeps([41,6,1,2])).then(t=>t.paywayModule),square:()=>O(()=>import("./square.CHbEYH5f.js"),__vite__mapDeps([42,1,2])).then(t=>t.squareModule),stripe:()=>O(()=>import("./stripe.Dy66WA4g.js"),__vite__mapDeps([43,6,1,2])).then(t=>t.stripeModule)},Rd={...Ld,...Cd,...Id,...Md},Er=new Map,Le=Fe("general","loader"),Fd=new Function("src","return import(src);");async function Nt(t,e,r,n){await t(Xc(r),n),await t(Qc(e,r),n)}function ti(t){return!!t&&typeof t=="object"&&typeof t.id=="string"&&typeof t.setup=="function"&&typeof t.match=="function"}async function Od(t,e){const r=Rd[t];return r?(Er.has(t)||Er.set(t,(async()=>{try{const n=await r();return ti(n)?(e.registry.register(n),n):null}catch(n){return console.error("[formie] Failed to load builtin module:",t,n),Le.warn("Failed loading builtin module.",{moduleId:t,error:n}),null}})()),Er.get(t)||null):null}async function Pd(t){try{const e=await Fd(t),r=(e==null?void 0:e.default)||(e==null?void 0:e.formieModule)||null;return ti(r)?r:null}catch(e){return console.error("[formie] Failed to load module from src:",t,e),Le.warn("Failed loading module from src.",{src:t,error:e}),null}}async function Nd(t,e){const r=e.registry.get(t.id);if(r)return r;const n=await Od(t.id,e);if(n)return n;if(t.src){const i=await Pd(t.src);if(i)return e.registry.register(i),i}return null}function kr(t){var e;return typeof((e=window.CSS)==null?void 0:e.escape)=="function"?window.CSS.escape(t):t.replace(/["\\]/g,"\\$&")}function Dt(t,e){return t.matches(e)?[t,...Array.from(t.querySelectorAll(e))]:Array.from(t.querySelectorAll(e))}function Dd(t,e){const r=e.setupContext.root,n=e.setupContext.form,i=t.targetType,a=t.targetId;return i==="selector"?Dt(r,a).map(o=>({scope:i,element:o})):i==="field"?Dt(r,`[data-formie-field-handle="${kr(a)}"]`).map(o=>({scope:i,element:o})):i==="page"?Dt(r,`[data-formie-page-id="${kr(a)}"]`).map(o=>({scope:i,element:o})):i==="button"?Dt(r,`[data-formie-action="${kr(a)}"]`).map(o=>({scope:i,element:o})):[{scope:"form",element:n||r}]}function zd(t,e){return(t.targets&&t.targets.length>0?t.targets:[{targetType:"form",targetId:"form"}]).flatMap(n=>Dd(n,e))}async function $d(t,e){var n,i;const r=[];Le.log("Loading module manifest.",{manifestCount:t.length});for(const a of t){const o=await Nd(a,e);if(!o){Le.warn("Skipping manifest item (definition not resolved).",{moduleId:a.id,src:a.src});continue}const s=zd(a,e);Le.log("Resolved module targets.",{moduleId:o.id,targets:a.targets||[],targetCount:s.length}),s.length===0&&o.kind==="address"&&console.warn(`[formie] Address module "${a.id}" skipped: no target element found for fieldHandle="${((i=(n=a.targets)==null?void 0:n.find(l=>l.targetType==="field"))==null?void 0:i.targetId)??"?"}". Check that the Address field exists in the rendered form.`);for(const l of s){const c={...e.matchContext,target:l.element,scope:l.scope,manifestItem:a};if(!o.match(c)){o.kind==="address"&&console.warn(`[formie] Address module "${o.id}" skipped: target element does not contain [data-formie-address-autocomplete-input]. Enable the Auto-Complete subfield.`),Le.log("Module target did not match predicate.",{moduleId:o.id,scope:l.scope});continue}const d=a.config||e.setupContext.options,h=o.id,f={moduleId:o.id,moduleKind:o.kind,target:l.element,scope:l.scope,options:d,manifestItem:a};await Nt(e.setupContext.emit,h,"before-setup",f);let u=null;try{const g=await o.setup({...e.setupContext,target:l.element,scope:l.scope,options:d});g&&(u=g)}catch(g){console.error(`[formie] Module "${o.id}" setup failed:`,g),Le.warn("Module setup failed.",{moduleId:o.id,scope:l.scope,error:g})}await Nt(e.setupContext.emit,h,"after-setup",{...f,instanceCreated:!!u}),u&&(Le.log("Module instance created.",{moduleId:o.id,scope:l.scope}),r.push({...u,destroy:async()=>{Le.log("Destroying module instance.",{moduleId:o.id,scope:l.scope}),await Nt(e.setupContext.emit,h,"before-destroy",f),await u.destroy(),await Nt(e.setupContext.emit,h,"after-destroy",f),Le.log("Module instance destroyed.",{moduleId:o.id,scope:l.scope})}}))}}return Le.log("Module manifest processing complete.",{instanceCount:r.length}),r}const Vd=new Set(["CRAFT_CSRF_TOKEN","action","redirect","requestToken","renderId","submitAction","pageId","draftContextToken","draftContext","continuationToken"]);function Dr(t,e){if(t==null)return String(t);if(typeof t=="string")return JSON.stringify(t);if(typeof t=="number"||typeof t=="boolean")return String(t);if(typeof t=="function")return"[function]";if(typeof File<"u"&&t instanceof File)return`[file:${t.name}:${t.size}:${t.type}]`;if(typeof Blob<"u"&&t instanceof Blob)return`[blob:${t.size}:${t.type}]`;if(Array.isArray(t))return`[${t.map(r=>Dr(r,e)).join(",")}]`;if(typeof t=="object"){if(e.has(t))return"[circular]";e.add(t);const r=Object.entries(t).sort(([n],[i])=>n.localeCompare(i)).map(([n,i])=>`${JSON.stringify(n)}:${Dr(i,e)}`);return e.delete(t),`{${r.join(",")}}`}return JSON.stringify(String(t))}function Hd(t){return Dr(t,new WeakSet)}function qd(t){if(!t)return!1;const e=t.endsWith("[]")?t.slice(0,-2):t;return!Vd.has(e)}function $n(t){const e=Array.from(new FormData(t).entries()).filter(([r])=>qd(String(r||"")));return Hd(e)}function jd(t,e={}){let r=null,n=!1,i=!1,a=null,o=null,s=null;const l=()=>{a!==null&&(window.cancelAnimationFrame(a),a=null),o!==null&&(window.clearTimeout(o),o=null),s!==null&&(window.clearTimeout(s),s=null)},c=()=>n?(i=$n(t)!==r,i):!1,d=()=>{r=$n(t),n=!0,i=!1},h=()=>{l(),n=!1,a=window.requestAnimationFrame(()=>{a=null,s=window.setTimeout(()=>{s=null,d()},0)})},f=()=>{o!==null&&window.clearTimeout(o),o=window.setTimeout(()=>{o=null,c()},120)},u=g=>{e.shouldWarn&&!e.shouldWarn()||c()&&(g.preventDefault(),g.returnValue="")};return t.addEventListener("input",f),t.addEventListener("change",f),window.addEventListener("beforeunload",u),h(),{captureBaseline:d,scheduleBaselineCapture:h,refreshDirtyState:c,destroy:()=>{l(),t.removeEventListener("input",f),t.removeEventListener("change",f),window.removeEventListener("beforeunload",u)}}}const nt='[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])',Bd=300,Ud="/actions/formie/server/forms/render",Vn="/api",Wd="/actions/formie/server/forms/refresh-tokens",Kd="/actions/formie/server/submissions/submit",Jd="/actions/formie/server/submissions/set-page",Gd="/actions/formie/server/submissions/clear-submission",Yd="/actions/formie/file-upload/hydrate",oe=Fe("general","client"),Hn=new Set;function _t(t,e){if(t==null||t==="")return e;const r=t.toLowerCase();return!(r==="false"||r==="0"||r==="off")}function zr(t){return t.formieRefreshTokens!=null&&t.formieRefreshTokens!==""?_t(t.formieRefreshTokens,!1):t.formieStaticCache!=null&&t.formieStaticCache!==""?_t(t.formieStaticCache,!1):!1}function ot(t){const e=t instanceof HTMLElement?t.dataset:{};return{mode:"server-rendered",transport:e.formieTransport||"rest",formHandle:e.formieHandle,endpoint:e.formieEndpoint,staticCache:zr(e),autoVisible:_t(e.formieAutoVisible,!0),compatibility:_t(e.formieCompatibility,!1)}}function sr(t){return t||"server-rendered"}function lr(t){return t||"rest"}function Ht(t){return t instanceof HTMLFormElement?t:t.querySelector("form")}function Zd(t,e){Hn.has(t)||(Hn.add(t),oe.warn(e))}function ri(t,e){if(!t)return t;try{return new URL(t).toString()}catch{}if(!e)return t;try{return new URL(t,e).toString()}catch{return t}}function ct(t,e){const r=(t||"").trim();return r?r.includes("/actions/")?r:ri(e,r):e}function Qd(t,e){return ct(t.endpoint||e.dataset.formieEndpoint,Ud)}function Xd(t,e){const r=(t.endpoint||e.dataset.formieEndpoint||"").trim();return r?r.includes("/graphql")||r.endsWith("/api")||r.includes("/actions/graphql/")?r:ri(Vn,r):Vn}function nn(t,e){return ct(e.dataset.formieRefreshTokensEndpoint||t.endpoint||e.dataset.formieEndpoint,Wd)}function qn(t,e){if(!t)return e;try{const r=new URL(t,window.location.origin),n=new URL(e,window.location.origin);return r.searchParams.forEach((i,a)=>{n.searchParams.has(a)||n.searchParams.set(a,i)}),n.toString()}catch{return e}}function ef(t,e,r){const n=r.endpoint||t.dataset.formieEndpoint,i=ct(n,Kd),a=e.getAttribute("action");e.setAttribute("action",qn(a,i)),e.querySelectorAll("[data-formie-tab-link]").forEach(o=>{const s=o.getAttribute("href"),l=ct(n,Jd);o.setAttribute("href",qn(s,l))}),e.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach(o=>{o.setAttribute("data-formie-file-upload-hydrate-endpoint",ct(n,Yd))})}function on(t,e){if(t==="graphql"&&e!=="server-rendered")throw new Error(`Formie ${e} mode does not support GraphQL transport yet.`)}function an(t){if(t==null)return!1;const e=t.trim().toLowerCase();return e==="true"||e==="1"||e===""}function tf(t){return _t(t.dataset.formieAutomaticSubmissionState,!0)}function rf(t,e,r){return ct(r.dataset.formieClearSubmissionEndpoint||t.endpoint||e.dataset.formieEndpoint,Gd)}function nf(t){return an(t.dataset.formieUnloadWarning)}function jn(t,e){t.setAttribute("data-formie-internal-navigation",e)}function _r(t){t.removeAttribute("data-formie-internal-navigation")}function Bn(t){return t.getAttribute("data-formie-internal-navigation")!==null}function Un(t,e){if(!t)return!1;try{return new URL(t,window.location.origin).searchParams.has(e)}catch{return!1}}function of(t){return Un(window.location.href,"resumeToken")||Un(t.getAttribute("action"),"resumeToken")}function af(t){return t instanceof MouseEvent?t.button===0&&!t.metaKey&&!t.ctrlKey&&!t.shiftKey&&!t.altKey:!0}function sf(t,e=0){if(!t)return e;const r=Number.parseInt(t,10);return Number.isFinite(r)?r:e}function lf(t){return Math.max(0,sf(t.dataset.formieSubmitDelay,Bd))}function $r(t){return an(t.dataset.formieValidationOnSubmit)}async function Vr(t){const e=lf(t);e<1||await new Promise(r=>{window.setTimeout(r,e)})}function Wn(t,e){var n;const r=(n=t==null?void 0:t.getAttribute(e))==null?void 0:n.trim();if(!r)return null;try{return JSON.parse(r)}catch(i){return console.error(`[formie] Failed to parse ${e}.`,i),null}}function Kn(t,e){const r=e||(t instanceof HTMLFormElement?t:null);if(!r)return null;const n=Wn(r,"data-formie-modules"),i=Wn(r,"data-formie-theme");return!n&&!i?null:{modules:n||void 0,theme:i||void 0}}function cf(t){if(!(t instanceof HTMLElement))return!0;if(!t.isConnected||t.hidden||t.closest("[hidden]"))return!1;const e=window.getComputedStyle(t);return e.display==="none"||e.visibility==="hidden"?!1:t.getClientRects().length>0}function uf(t,e){return e===document?!0:e instanceof Element?e===t||e.contains(t):!0}function fe(t){var a;const e=t,r=e.id?`#${e.id}`:"",n=(a=e.dataset)!=null&&a.formieHandle?`[handle="${e.dataset.formieHandle}"]`:"";return`${e.tagName?e.tagName.toLowerCase():"element"}${r}${n}`}function sn(t,e){var r,n;if(e){if((r=e.csrf)!=null&&r.param&&((n=e.csrf)!=null&&n.token)){const i=t.querySelector(`input[name="${e.csrf.param}"]`);i&&(i.value=e.csrf.token)}if(e.requestToken){const i=t.querySelector('input[name="requestToken"]');i&&(i.value=e.requestToken)}if(e.renderId){const i=t.querySelector('input[name="renderId"]');i&&(i.value=e.renderId)}e.captchas&&typeof e.captchas=="object"&&Object.values(e.captchas).forEach(i=>{if(!i||typeof i!="object")return;const a=i;if(!a.sessionKey)return;const o=t.querySelector(`input[name="${a.sessionKey}"]`);o&&typeof a.value=="string"&&(o.value=a.value)})}}async function df(t,e){const r=sr(e.mode),n=lr(e.transport);if(r!=="server-rendered")return null;if(e.payload)return e.payload.html&&(t.innerHTML=e.payload.html),e.payload;on(n,r);const i=!!Ht(t),a=e.formHandle||t.dataset.formieHandle;if(i||!a)return null;const o={mode:r,endpoint:e.endpoint,locale:e.locale,siteId:e.siteId,theme:e.theme,themeConfig:e.themeConfig},s=n==="graphql"?Xd(e,t):Qd(e,t),l=n==="graphql"?await ku(s,a,o):await Eu(s,a,{...o,endpoint:s});return l!=null&&l.html&&(t.innerHTML=l.html),l}async function ni(t,e,r){if(e.refreshTokens===!1)return;on(lr(e.transport),sr(e.mode));const n=e.formHandle||t.dataset.formieHandle;if(!n)return;const i=nn(e,t),a=r.querySelector('input[name="renderId"]'),o=(a==null?void 0:a.value)||void 0,s=await Zr(i,n,o);sn(r,s),ie(t,"formie:refresh-tokens:refreshed",s)}function ff(t,e,r,n,i,a){const o=String(e.dataset.formieSubmitMethod||"").trim().toLowerCase(),s=rf(r,t,e);let l=!1;const c=e.querySelectorAll("[data-formie-action]"),d=u=>{if(u){e.setAttribute("data-formie-pending-action",u);return}e.removeAttribute("data-formie-pending-action")};if(nf(e)){const u=jd(e,{shouldWarn:()=>!Bn(e)}),g=m=>{if(!(m instanceof CustomEvent))return;const p=m.detail;p!=null&&p.ok&&p.action==="save"&&u.scheduleBaselineCapture()},x=()=>{u.scheduleBaselineCapture()};t.addEventListener("formie:submit:result",g),e.addEventListener("formie:state:reset",x),a.push(()=>{t.removeEventListener("formie:submit:result",g),e.removeEventListener("formie:state:reset",x),u.destroy()})}if(c.forEach(u=>{const g=x=>{const m=x.currentTarget.getAttribute("data-formie-action"),p=e.querySelector('input[name="submitAction"]');d(m),m&&p&&(p.value=m)};u.addEventListener("click",g),a.push(()=>{u.removeEventListener("click",g)})}),e.querySelectorAll("[data-formie-tab-link]").forEach(u=>{const g=async x=>{if(o!=="ajax"){af(x)&&jn(e,"set-page");return}x.preventDefault();const m=x.currentTarget,p=m==null?void 0:m.getAttribute("data-formie-page-id"),v=m==null?void 0:m.getAttribute("href");if(!(!p||!v)){en(e,p),ie(t,"formie:page:navigate",{pageId:p,href:v});try{const b=await _u(v,e,p);ie(t,"formie:page:navigate:after",{pageId:p,href:v,response:b})}catch(b){console.error("[formie] Failed to persist page navigation state.",b),ie(t,"formie:page:navigate:error",{pageId:p,href:v,error:b})}}};u.addEventListener("click",g),a.push(()=>{u.removeEventListener("click",g)})}),!tf(e)){let u=!1;const g=()=>{u||Bn(e)||of(e)||(u=!0,Su(s,e))};window.addEventListener("pagehide",g),window.addEventListener("beforeunload",g),a.push(()=>{window.removeEventListener("pagehide",g),window.removeEventListener("beforeunload",g)})}const f=async u=>{if(l)return;const g=o==="ajax";if(u.preventDefault(),e.getAttribute("data-formie-loading")==="true"){if(!(e.getAttribute("data-formie-internal-resubmit")==="true"))return;e.removeAttribute("data-formie-internal-resubmit")}else e.removeAttribute("data-formie-internal-resubmit");const m=u.submitter,p=m==null?void 0:m.getAttribute("data-formie-action"),v=e.getAttribute("data-formie-pending-action"),b=e.querySelector('input[name="submitAction"]'),y=p||v||(b==null?void 0:b.value)||"submit";let _=null,C=!1;try{if(g)_=await ei({target:t,form:e,bus:n,validator:i,validateOnSubmit:$r(e),action:y,submitter:m,waitForSubmitDelay:Vr,onRefreshTokensAfterSubmit:async()=>{await ni(t,r,e)},dispatchSubmitResult:F=>{ie(t,"formie:submit:result",F)}});else{if(Xo(e),Bo(e,m),await Vr(e),_=await Vo(e,y,n,{validator:i,validateOnSubmit:$r(e),preflightOnly:!0}),_.ok){Oo(e,y),l=!0,jn(e,"submit"),d(null);let F=!1;const j=()=>{F=!0,l=!1,_r(e),Qt(e)};if(typeof e.requestSubmit=="function"){e.addEventListener("invalid",j,!0);try{e.requestSubmit()}finally{e.removeEventListener("invalid",j,!0)}}else e.submit();if(F)return;C=!0;return}er(e,_),ie(t,"formie:submit:result",_),_r(e)}}catch(F){l=!1,_={ok:!1,code:"SUBMIT_ERROR",message:F instanceof Error?F.message:"Submission failed.",formErrors:[F instanceof Error?F.message:"Submission failed."]},er(e,_),ie(t,"formie:submit:result",_),_r(e)}finally{d(null),!g&&!C&&!Qo(_)&&Qt(e)}};e.addEventListener("submit",f),a.push(()=>{e.removeEventListener("submit",f)})}async function mf(t,e,r){if(e.refreshTokens===!1||!e.staticCache)return;on(lr(e.transport),sr(e.mode));const n=e.formHandle||t.dataset.formieHandle,i=nn(e,t),a=r==null?void 0:r.querySelector('input[name="renderId"]'),o=(a==null?void 0:a.value)||void 0;if(!n)return;const s=await Zr(i,n,o);!s||!r||(sn(r,s),ie(t,"formie:refresh-tokens:after",s))}function hf(){const t=new Map,e=new Td,r=new Map,n=new Map,i=["prepare","normalize","validate","screen","authorize","dispatch","finalize"],a=async m=>{const p=n.get(m);if(p){await p;return}const v=(async()=>{var _;oe.log("Unmount requested.",{target:fe(m)});const b=r.get(m);b&&(b(),r.delete(m));const y=t.get(m);if(!y){oe.log("Unmount skipped (no mounted state).",{target:fe(m)});return}ie(m,"formie:unmount:before",{id:y.instance.id}),y.unbinds.forEach(C=>{C()}),y.unbinds=[],(_=y.validator)==null||_.destroy(),y.validator=null;for(const C of y.modules)await C.destroy();y.modules=[],y.bus.clear(),t.delete(m),ie(m,"formie:unmount:after",{id:y.instance.id}),oe.log("Unmount complete.",{id:y.instance.id,target:fe(m)})})().finally(()=>{n.delete(m)});n.set(m,v),await v},o=async(m,p)=>{oe.log("Mount requested.",{target:fe(m),mode:p.mode,autoVisible:p.autoVisible});const v=r.get(m);v&&(v(),r.delete(m));const b=t.get(m);if(b)return oe.log("Mount skipped (already mounted).",{id:b.instance.id,target:fe(m)}),b.instance;const y=new yu,_=[],C=(m==null?void 0:m.id)||`formie-${t.size+1}`,F=ot(m),j={...F,...p,mode:sr(p.mode??F.mode),transport:lr(p.transport??F.transport)},H=Zc(j.compatibility);if(j.mode!=="server-rendered"&&!Ht(m))throw new Error(`Formie ${j.mode} mode is not implemented yet in the browser client.`);const ee=await df(m,j),M=Ht(m);j.staticCache=p.staticCache??zr(M?M.dataset:m.dataset);const B=Kn(m,M),N=ee||B?{...ee||{},...B||{}}:null,w=N==null?void 0:N.theme,A={},P=((N==null?void 0:N.modules)||[]).filter(z=>!!(z!=null&&z.id)&&!!(z!=null&&z.type));oe.log("Resolved mount payload.",{target:fe(m),hasRenderPayload:!!ee,hasEmbeddedPayload:!!B,moduleCount:P.length});const W=Ln(m,w,M),$=M?new Ju(M,{live:an(M.dataset.formieValidationOnFocus),errorMessage:M.dataset.formieErrorMessage||"",fieldContainerErrorClass:W.fieldLayoutError||[],inputErrorClass:W.fieldControlError||[],messagesClass:W.fieldErrors||[],messageClass:W.fieldError||[]}):null;if(M&&$){const z=M;z.formieValidation=$,A.validation=$;const Q={validator:$,addValidator:$.addValidator.bind($),removeValidator:$.removeValidator.bind($)};ie(M,"formie:validator:ready",Q),ie(m,"formie:validator:ready",Q)}M&&((ee||j.endpoint||m.dataset.formieEndpoint)&&ef(m,M,j),Xe(M)),Object.keys(W).length&&ie(m,"formie:theme:applied",{hasClasses:!0});const Z=await $d(P,{registry:e,matchContext:{root:m,form:M,mode:j.mode},setupContext:{formId:C,root:m,form:M,target:m,scope:"form",state:A,on:(z,Q)=>y.on(z,Q),emit:(z,Q)=>(ie(m,z,Q),y.emitSafe(z,Q).then(ne=>{ne.failed.length>0&&oe.warn("Lifecycle listeners failed.",{eventName:z,failed:ne.failed.length})}))}});oe.log("Module setup complete.",{target:fe(m),moduleInstances:Z.length});const U={id:C,root:m,submit:async(z="submit")=>{if(oe.log("Submit requested.",{id:C,target:fe(m),action:z}),!M)return{ok:!1,code:"FORM_NOT_FOUND",message:"No form element found for mount target.",formErrors:["No form element found for mount target."]};const Q=M.querySelector('input[name="submitAction"]');if(Q&&(Q.value=z),M.getAttribute("data-formie-loading")==="true")return{ok:!1,code:"SUBMIT_IN_PROGRESS",message:"Submission already in progress.",formErrors:[]};const ne=M.querySelector(`[data-formie-action="${z}"]`),X=await ei({id:C,target:m,form:M,bus:y,validator:$,validateOnSubmit:$r(M),action:z,submitter:ne,waitForSubmitDelay:Vr,onRefreshTokensAfterSubmit:async()=>{await ni(m,j,M)},dispatchSubmitResult:se=>{ie(m,"formie:submit:result",se)}});return oe.log("Submit completed.",{id:C,action:z,ok:X.ok,code:X.code,message:X.message}),X},destroy:async()=>{await a(m)},on:(z,Q)=>y.on(z,Q)};M&&(iu({target:m,form:M,validatorDetail:$?{validator:$,addValidator:$.addValidator.bind($),removeValidator:$.removeValidator.bind($)}:null,options:H,unbinds:_}),ou({target:m,form:M,instance:U,options:H,unbinds:_})),M&&(ff(m,M,j,y,$,_),await mf(m,j,M)),i.forEach(z=>{const Q=y.on(`formie:stage:${z}:before`,async ue=>{ie(m,`formie:stage:${z}:before`,ue)}),ne=y.on(`formie:stage:${z}:before`,async ue=>{for(const we of Z)we.onBeforeStage&&await we.onBeforeStage(ue)}),X=y.on(`formie:stage:${z}:after`,async ue=>{ie(m,`formie:stage:${z}:after`,ue)}),se=y.on(`formie:stage:${z}:after`,async ue=>{const we=ue;for(const Ie of Z)Ie.onAfterStage&&await Ie.onAfterStage(we,we.result)});_.push(Q,ne,X,se)});const I=y.on("formie:submit:before",async z=>{ie(m,"formie:submit:before",z)}),T=y.on("formie:submit:after",async z=>{ie(m,"formie:submit:after",z)}),G=y.on("formie:submit:final:before",async z=>{ie(m,"formie:submit:final:before",z)}),te=y.on("formie:submit:final:after",async z=>{ie(m,"formie:submit:final:after",z)});return _.push(I,T,G,te),t.set(m,{options:j,bus:y,form:M,validator:$,modules:Z,unbinds:_,instance:U}),ie(m,"formie:mount:after",{id:C,mode:j.mode}),oe.log("Mount complete.",{id:C,target:fe(m),mode:j.mode}),U},s=(m,p)=>{var b;if(!p.autoVisible||cf(m)||typeof IntersectionObserver>"u")return o(m,p);if(t.has(m))return Promise.resolve(((b=t.get(m))==null?void 0:b.instance)||null);if(r.has(m))return oe.log("Mount deferred (already waiting visibility).",{target:fe(m)}),Promise.resolve(null);const v=new IntersectionObserver(y=>{y.some(C=>C.target===m&&C.isIntersecting)&&(v.disconnect(),r.delete(m),oe.log("Visibility reached, proceeding mount.",{target:fe(m)}),o(m,{...p,autoVisible:!1}))},{threshold:.01});return v.observe(m),r.set(m,()=>{v.disconnect()}),oe.log("Mount deferred until visible.",{target:fe(m)}),Promise.resolve(null)};return{mount:o,unmount:a,update:async(m,p)=>{var _,C,F;const v=t.get(m);if(!v)return o(m,{...ot(m),...p,mode:p.mode||"server-rendered"});v.options={...v.options,...p};const b=((_=p.payload)==null?void 0:_.theme)||((C=v.options.payload)==null?void 0:C.theme)||((F=Kn(m,v.form))==null?void 0:F.theme),y=Ln(m,b,v.form);return v.validator&&(v.validator.config.fieldContainerErrorClass=y.fieldLayoutError||[],v.validator.config.inputErrorClass=y.fieldControlError||[],v.validator.config.messagesClass=y.fieldErrors||[],v.validator.config.messageClass=y.fieldError||[]),Object.keys(y).length&&ie(m,"formie:theme:applied",{hasClasses:!0,reason:"update"}),v.instance},getInstance:m=>{var p;return((p=t.get(m))==null?void 0:p.instance)||null},refreshForCache:async m=>{Zd("refreshForCache","Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");let p=null;if(typeof m=="string"){const ee=document.getElementById(m);ee?p=ee:p=document.querySelector(`[data-formie-form-id="${m}"]`)}else p=m;if(!p){oe.warn("refreshForCache target not found.",{targetOrId:m});return}const v=t.get(p),b=Ht(p),y=(v==null?void 0:v.options)||ot(p);if(!b){oe.warn("refreshForCache found no form element for target.",{target:fe(p)});return}const _=y.formHandle||p.dataset.formieHandle||b.dataset.formieHandle,C=nn(y,p),F=b.querySelector('input[name="renderId"]'),j=(F==null?void 0:F.value)||void 0;if(!_){oe.warn("refreshForCache found no form handle for target.",{target:fe(p)});return}const H=await Zr(C,_,j);H&&(sn(b,H),ie(p,"formie:refresh-tokens:after",H))},registerModule:(m,p)=>e.register(m,p),unregisterModule:m=>{e.unregister(m)},getRegisteredModules:()=>e.getAll(),scan:async m=>{const p=m||document,v=Array.from(p.querySelectorAll(nt));oe.log("Scan started.",{scope:p===document?"document":p,targetCount:v.length});const y=(await Promise.all(v.map(_=>{const C=ot(_);return s(_,C)}))).filter(_=>!!_);return oe.log("Scan finished.",{mountedCount:y.length,deferredCount:v.length-y.length}),y},observe:m=>{if(typeof MutationObserver>"u")return()=>{};const p=m||document;oe.log("Observer started.",{scope:p===document?"document":p});const v=new MutationObserver(b=>{b.forEach(y=>{y.addedNodes.forEach(_=>{_ instanceof Element&&(_.matches(nt)&&(oe.log("Observer detected new root.",{target:fe(_)}),s(_,ot(_))),_.querySelectorAll(nt).forEach(C=>{oe.log("Observer detected new nested root.",{target:fe(C)}),s(C,ot(C))}))}),y.removedNodes.forEach(_=>{_ instanceof Element&&(t.has(_)&&(oe.log("Observer detected removed root.",{target:fe(_)}),a(_)),_.querySelectorAll(nt).forEach(C=>{t.has(C)&&(oe.log("Observer detected removed nested root.",{target:fe(C)}),a(C))}))})})});return v.observe(p,{childList:!0,subtree:!0}),()=>{v.disconnect(),oe.log("Observer stopped."),r.forEach((y,_)=>{uf(_,p)&&(y(),r.delete(_))});const b=[];p instanceof Element&&p.matches(nt)&&b.push(p),p.querySelectorAll(nt).forEach(y=>{b.push(y)}),b.forEach(y=>{t.has(y)&&a(y)})}}}}const ln=2e3,_m=5e3,Sm=5e3,Am=12e4;async function cn(t){await new Promise(e=>{window.setTimeout(e,Math.max(t,0))})}async function Tm(t,{timeoutMs:e=5e3,intervalMs:r=30}={}){const n=Date.now();for(;Date.now()-n<e;){const i=t();if(i)return i;await cn(r)}throw new Error("Timed out waiting for async condition.")}function oi(t,e){let r=null;return(...n)=>{r!==null&&window.clearTimeout(r),r=window.setTimeout(()=>{t(...n)},Math.max(e,0))}}function Cm(t){const e=String(t||"asyncDefer").toLowerCase();return{async:e.includes("async"),defer:e.includes("defer")}}function ii(t,e){const r=Array.from(t.querySelectorAll(`input[name="${e}"], textarea[name="${e}"]`));for(const n of r){const i=String(n.value||"").trim();if(i!=="")return i}return""}function Hr(t,e){return e.some(r=>ii(t,r)!=="")}function pf(t,e){e.forEach(r=>{Array.from(t.querySelectorAll(`input[name="${r}"], textarea[name="${r}"]`)).forEach(i=>{i.value=""})})}function ai(t,e,{value:r="",container:n}={}){let i=t.querySelector(`input[name="${e}"]`);if(!i){i=document.createElement("input"),i.type="hidden",i.name=e;const a=n||(t instanceof HTMLElement?t:null);a==null||a.appendChild(i)}return i.value=r,i}async function si(t,e,r){if(Hr(t,e))return!0;const n=Date.now()+Math.max(r,0);for(;Date.now()<n;)if(await cn(120),Hr(t,e))return!0;return!1}const gf=new Set(["handle","placeholderSelector","errorMessage","sessionKey","value"]),vf="[data-formie-captcha-error-container]",bf=["formie:page:navigate","formie:page:navigate:after","formie:submit:result"];function yt(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function tr(t,e){return t instanceof HTMLElement&&t.matches(e)?[t,...Array.from(t.querySelectorAll(e))]:Array.from(t.querySelectorAll(e))}function qr(t){if(!(t instanceof HTMLElement)||!t.isConnected||t.hidden||t.closest("[hidden]")||t.closest("[data-formie-page-hidden]")||t.closest('[aria-hidden="true"]'))return!1;const e=window.getComputedStyle(t);return e.display!=="none"&&e.visibility!=="hidden"}function Sr(t,e){const r=tr(t,e);return r.find(n=>qr(n))||r[0]||null}function yf(t){t.innerHTML="";const e=document.createElement("div");return t.appendChild(e),e}function jr(t){var e;(e=t==null?void 0:t.querySelector(vf))==null||e.remove()}function wf(t,e,r){if(!t)return;jr(t);const n=document.createElement("div");n.setAttribute("data-formie-captcha-error-container",""),n.setAttribute("aria-live","polite"),n.setAttribute("aria-atomic","true"),pe(n,r||t,"fieldErrors");const i=document.createElement("div");i.setAttribute("data-formie-captcha-error",""),i.setAttribute("role","alert"),pe(i,r||t,"fieldError"),i.textContent=e,n.appendChild(i),t.appendChild(n)}function xf(t){const e=t instanceof CustomEvent?t.detail:null;return!e||typeof e!="object"?null:e}function Ef(t,e){if(!(t!=null&&t.captchas)||typeof t.captchas!="object")return null;const r=t.captchas[e];return!r||typeof r!="object"?null:r}function kf(t,e,r,n){const i=new Set,a=()=>{const c=tr(t,e),d=new Set(c.filter(h=>qr(h)));c.forEach(h=>{d.has(h)&&!i.has(h)&&(i.add(h),r(h))}),Array.from(i).forEach(h=>{d.has(h)||(i.delete(h),n(h))})},o=oi(a,20),s=new MutationObserver(()=>{o()});s.observe(t,{childList:!0,subtree:!0,attributes:!0,attributeFilter:["class","style","hidden","aria-hidden","data-formie-page-hidden"]});const l=[yt(window,"resize",()=>{o()}),...bf.map(c=>yt(t,c,()=>{o()}))];return a(),{cleanup:()=>{s.disconnect(),l.forEach(c=>{c()}),Array.from(i).forEach(c=>{n(c)}),i.clear()},reconcile:o,getVisible:()=>tr(t,e).filter(c=>qr(c))}}function _f(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function Sf(t,e,{defaultPlaceholderSelector:r,defaultTokenFieldNames:n=[],defaultWaitForValueMs:i=ln}){const a=e||{},o=Object.entries(a).reduce((u,[g,x])=>(gf.has(g)||(u[g]=x),u),{}),s=n.map(String).filter(Boolean),l=Number(i),c=typeof a.placeholderSelector=="string"&&a.placeholderSelector.trim()!==""?a.placeholderSelector.trim():r,d=typeof a.errorMessage=="string"&&a.errorMessage.trim()!==""?a.errorMessage.trim():$e("Captcha challenge must be completed."),h=typeof a.sessionKey=="string"&&a.sessionKey.trim()!==""?a.sessionKey.trim():null,f=typeof a.value=="string"?a.value:null;return{handle:_f(t,a),ui:{placeholderSelector:c,errorMessage:d},transport:{tokenFieldNames:s,waitForValueMs:Number.isFinite(l)?l:i,sessionKey:h,value:f},provider:o}}function Af(t,e){const r=t.form||t.root,n=e.ui.placeholderSelector,i=e.handle;return{form:t.form,root:t.root,placeholder:{query:()=>tr(t.root,n),getPrimary:()=>Sr(t.root,n),observe:(a,o)=>kf(t.root,n,a,o),createContainer:a=>yf(a),clear:a=>{a&&(jr(a),a.innerHTML="")}},errors:{getDefaultMessage:()=>e.ui.errorMessage,show:(a,o)=>{wf(o||Sr(t.root,n),a||e.ui.errorMessage,t.form||t.root)},clear:a=>{jr(a||Sr(t.root,n))}},tokens:{names:e.transport.tokenFieldNames,has:(a=e.transport.tokenFieldNames,o=r)=>Hr(o,a),read:(a=e.transport.tokenFieldNames[0],o=r)=>a?ii(o,a):"",write:(a,{names:o=e.transport.tokenFieldNames,root:s=r,container:l=t.form}={})=>{o.forEach(c=>{ai(s,c,{value:a,container:l})})},clear:(a=e.transport.tokenFieldNames,o=r)=>{pf(o,a)},wait:(a=e.transport.waitForValueMs,o=e.transport.tokenFieldNames,s=r)=>si(s,o,a)},refresh:{providerHandle:i,onTokensRefreshed:a=>{const o=["formie:refresh-tokens:after","formie:refresh-tokens:refreshed"].map(s=>yt(t.root,s,l=>{const c=xf(l),d=Ef(c,i);d&&a(d)}));return()=>{o.forEach(s=>{s()})}}},events:{onRoot:(a,o)=>yt(t.root,a,o),onForm:(a,o)=>t.form?yt(t.form,a,o):()=>{}}}}const Ze=Fe("captchas");function li({id:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r=[],defaultWaitForValueMs:n=ln,setup:i}){return{id:t,kind:"captcha",match:()=>!0,setup:async a=>{const o=Sf(t,a.options||{},{defaultPlaceholderSelector:e,defaultTokenFieldNames:r,defaultWaitForValueMs:n});Ze.log("Setup module.",{moduleId:t,placeholderSelector:o.ui.placeholderSelector,tokenFieldNames:o.transport.tokenFieldNames});const s=Af(a,o);return i({...a,options:o,services:s})}}}function Tf({id:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r=[],defaultWaitForValueMs:n=ln}){return li({id:t,defaultPlaceholderSelector:e,defaultTokenFieldNames:r,defaultWaitForValueMs:n,setup:async({services:i,options:a,root:o})=>{const s=[];let l=i.placeholder.getPrimary(),c=a.transport.sessionKey,d=a.transport.value||"";const h=u=>{!u||!c||(u.innerHTML="",ai(u,c,{value:d,container:u}))},f=i.placeholder.observe(u=>{l=u,Ze.log("Passive placeholder visible.",{moduleId:t}),h(u)},u=>{l===u&&(l=i.placeholder.getPrimary()),u.innerHTML=""});return s.push(f.cleanup),h(l),s.push(i.refresh.onTokensRefreshed(u=>{c=typeof u.sessionKey=="string"&&u.sessionKey.trim()!==""?u.sessionKey.trim():c,d=typeof u.value=="string"?u.value:"";const g=i.placeholder.getPrimary()||l;l=g,h(g)})),{destroy:()=>{s.forEach(u=>{u()})},onBeforeStage:async u=>{if(u.stage!=="screen"||u.action!=="submit")return;const g=c?[c]:a.transport.tokenFieldNames;if(g.length===0)return;if(!await si(o,g,a.transport.waitForValueMs)){const m=i.errors.getDefaultMessage();i.errors.show(m,l),Ze.warn("Passive captcha missing token.",{moduleId:t,tokenFieldNames:g}),u.abort(m)}}}}})}function Cf(t){return li({id:t.id,defaultPlaceholderSelector:t.defaultPlaceholderSelector,defaultTokenFieldNames:t.defaultTokenFieldNames,setup:async e=>{const r=[],n=new Map,i=new Map;let a=e.services.placeholder.getPrimary(),o=!1,s=null;const l=async()=>(s||(Ze.log("Loading captcha provider API.",{moduleId:t.id}),s=t.load(e)),s),c=async u=>{const g=n.get(u);if(e.services.errors.clear(u),!g){u.innerHTML="";return}const x=await l();t.unmount&&await t.unmount({api:x,widget:g,placeholder:u,services:e.services,options:e.options,provider:e.options.provider}),n.delete(u),u.innerHTML="",e.services.tokens.clear(),Ze.log("Unmounted captcha placeholder widget.",{moduleId:t.id}),a===u&&(a=e.services.placeholder.getPrimary())},d=async u=>{if(o||n.has(u)||i.has(u))return;const g=(async()=>{const x=await l();if(o||n.has(u))return;const m=e.services.placeholder.createContainer(u),p=await t.mount({api:x,placeholder:u,container:m,services:e.services,options:e.options,provider:e.options.provider});n.set(u,p),a=u,Ze.log("Mounted captcha placeholder widget.",{moduleId:t.id})})().finally(()=>{i.delete(u)});i.set(u,g),await g},h=e.services.placeholder.observe(u=>{a=u,d(u)},u=>{c(u)});r.push(h.cleanup);const f=async u=>{const x=h.getVisible();if(t.reset){const m=await l();for(const p of x){const v=n.get(p);if(!v){await d(p);continue}await t.reset({api:m,widget:v,placeholder:p,services:e.services,options:e.options,provider:e.options.provider,reason:u}),e.services.tokens.clear(),e.services.errors.clear(p)}h.reconcile();return}for(const m of Array.from(n.keys()))await c(m);for(const m of x)await d(m);h.reconcile()};return r.push(e.services.events.onRoot("formie:submit:result",u=>{const g=u instanceof CustomEvent?u.detail:null;(g==null?void 0:g.stage)!=="validate"&&((g==null?void 0:g.ok)===!1&&(g==null?void 0:g.stage)==="screen"||(g==null?void 0:g.ok)!==!0&&f("submit-result"))})),e.form&&r.push(e.services.events.onForm(Or("reset"),()=>{a=e.services.placeholder.getPrimary()||a,window.setTimeout(()=>{f("reset-state")},0)})),{destroy:async()=>{o=!0,r.forEach(u=>{u()});for(const u of Array.from(n.keys()))await c(u)},onBeforeStage:async u=>{if(u.stage!=="screen"||u.action!=="submit")return;const g=h.getVisible();if(g.length===0)return;let x=g.find(v=>v===a)||g[0];await d(x),x=a||x,e.services.errors.clear(x);const m=n.get(x);if(!m){const v=e.services.errors.getDefaultMessage();e.services.errors.show(v,x),Ze.warn("Captcha widget unavailable at screen stage.",{moduleId:t.id}),u.abort(v);return}const p=await l();await t.screen({api:p,widget:m,placeholder:x,services:e.services,options:e.options,provider:e.options.provider,stageCtx:u})}}}})}const Im=Cf,Lm=Tf,Jn=2500,If={bpoint:["bpointToken"],stripe:["stripePaymentIntentId"],paypal:["paypalOrderId","paypalAuthId"],payway:["paywayTokenId"],opayo:["opayoTokenId"],eway:["ewayTokenData"],"go-cardless":["goCardlessRedirectId"],mollie:["molliePaymentId"],moneris:["monerisTokenId"],paddle:["paddleTransactionId"],square:["squarePaymentId"]};function Lf(t){return t.replace("{field:","").replace("{","").replace("}","").replace("]","").split("[").join("][")}function Mf(t){return`fields[${Lf(t)}]`}function Rf(t,e){const r=Mf(e),n=Array.from(t.querySelectorAll(`[name="${r}"]`)),i=Array.from(t.querySelectorAll(`[name="${r}[]"]`));return(i.length?i:n).filter(a=>a instanceof HTMLElement)}function Gn(t,e){var n,i,a;const r=Rf(t,e);for(const o of r){const s=o.closest("[data-formie-field-handle]"),l=(a=(i=(n=s==null?void 0:s.querySelector("[data-formie-field-label]"))==null?void 0:n.childNodes[0])==null?void 0:i.textContent)==null?void 0:a.trim();if(l)return l}return""}function Ar(t){let e=t.replace(/[^\d.,-]/g,"");const r=e.includes(","),n=e.includes(".");return r&&n?e=e.replace(/\./g,"").replace(/,/,"."):r&&!n?e=e.replace(/,/,"."):e=e.replace(/,/g,""),parseFloat(e)}function Ff(t){return t.replace(/^\{field:/,"").replace(/^\{/,"").replace(/\}$/,"").trim()}function St(t){return Ff(t).replace(/\]/g,"").split("[").join(".").replace(/\.+/g,".").replace(/^\./,"").replace(/\.$/,"")}function Of(t){const r=St(t).split(".").filter(Boolean);if(!r.length)return"";const[n,...i]=r;return`fields[${n}]${i.map(a=>`[${a}]`).join("")}`}function Pf(t){const r=String(t||"").trim().match(/^fields\[([^\]]+)\](.*)$/);if(!r)return"";const n=r[1]||"",i=r[2]||"",a=Array.from(i.matchAll(/\[([^\]]+)\]/g)).map(o=>o[1]||"").filter(Boolean);return[n,...a].join(".")}function Nf(t){const e=t.split(";").map(o=>o.trim()).filter(Boolean);if(!e.length)return{source:"",transforms:[]};const[r,...n]=e,i=[];let a=null;return n.forEach(o=>{if(o.startsWith("transform=")){a&&i.push(a),a={id:decodeURIComponent(o.slice(10)||"").trim(),params:{}};return}if(!a||!o.includes("="))return;const[s,l]=o.split("=",2),c=(s||"").trim();!c||c==="transform"||(a.params[c]=decodeURIComponent(l||"").trim())}),a&&i.push(a),{source:r||"",transforms:i}}function Df(t){const e=String(t||"").trim();if(!e)return{raw:e,target:"",key:"",selector:"",defaultValue:"",transforms:[],isToken:!1,isValid:!1};const r=e.match(/^\{([a-zA-Z]+)(?::(.*))?\}$/);if(!r)return{raw:e,target:"",key:St(e),selector:"",defaultValue:"",transforms:[],isToken:!1,isValid:!0};const n=(r[1]||"").trim().toLowerCase(),i=(r[2]||"").trim(),[a,o=""]=i.split("|",2),{source:s,transforms:l}=Nf(a||"");if(n!=="field")return{raw:e,target:"",key:"",selector:"",defaultValue:o.trim(),transforms:l,isToken:!0,isValid:!1};const c=s.indexOf(":"),d=c===-1?s:s.slice(0,c),h=c===-1?"":s.slice(c+1),f=St(d);return{raw:e,target:"field",key:f,selector:h.trim(),defaultValue:o.trim(),transforms:l,isToken:!0,isValid:f!==""}}function zf(t){return t instanceof HTMLInputElement||t instanceof HTMLTextAreaElement||t instanceof HTMLSelectElement}function $f(t,e,r){const n=e.trim(),i=String(r.name||"").trim();if(!n||!i)return;const a=t.get(n)||{key:n,names:[],inputs:[]};a.names.includes(i)||a.names.push(i),a.inputs.includes(r)||a.inputs.push(r),t.set(n,a)}function Vf(t){const e=new Map;return Array.from(t.querySelectorAll("[name]")).filter(n=>zf(n)).forEach(n=>{const i=Pf(n.name);i&&$f(e,i,n)}),e}function Hf(t){if(!t.length)return"";const e=t[0];if(e instanceof HTMLSelectElement&&e.multiple)return Array.from(e.selectedOptions).map(n=>n.value);if(t.some(n=>n instanceof HTMLInputElement&&(n.type==="checkbox"||n.type==="radio"))){const n=t.flatMap(i=>!(i instanceof HTMLInputElement)||!i.checked?[]:[i.value]);return n.length>1?n:n[0]||""}return e.value}function qf(t,e){return t.get(St(e))||null}function jf(t,e){const r=Df(t),n=r.key,i=qf(e,n);if(!i)return{key:n,value:r.defaultValue,found:!1};const a=Hf(i.inputs);return{key:n,value:a===""&&r.defaultValue!==""?r.defaultValue:a,found:!0}}function ci(t,e){const r=e.replace(/"/g,'\\"');return t.querySelector(`input[name$="[${r}]"]`)||t.querySelector(`input[name$="${r}"]`)}function qt(t,e){const r=e.find(n=>{const i=ci(t,n);return!i||String(i.value||"").trim()===""});return{ok:!r,missingSuffix:r}}async function ui(t,e,r){const n=qt(t,e);if(n.ok)return n;const i=Date.now()+Math.max(r,0);for(;Date.now()<i;){await cn(120);const a=qt(t,e);if(a.ok)return a}return qt(t,e)}const Bf=new Set(["handle","requiredInputSuffixes","waitForValueMs","errorMessage"]),Yn="[data-payment-success]",Zn="[data-payment-error]";function Uf(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function Wf(t,e,r){const n=e||{},i=Object.entries(n).reduce((l,[c,d])=>(Bf.has(c)||(l[c]=d),l),{}),a=Array.isArray(n.requiredInputSuffixes)?n.requiredInputSuffixes.map(String).filter(Boolean):r.defaultRequiredInputSuffixes||[],o=Number(n.waitForValueMs??r.defaultWaitForValueMs??Jn),s=typeof n.errorMessage=="string"&&n.errorMessage.trim()!==""?n.errorMessage.trim():"Payment authorization is incomplete.";return{handle:Uf(t,n),transport:{requiredInputSuffixes:a,waitForValueMs:Number.isFinite(o)?o:Jn,errorMessage:s},provider:i}}function Qn(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function Kf(t,e){const r=t.target,n=t.form,i=t.root,a=n||i,o=e.transport.requiredInputSuffixes,s=()=>Vf(n||i),l=b=>{const _=jf(b,s()).value;return Array.isArray(_)?_[0]||"":String(_||"")};return{root:i,form:n,field:r,updateInputs:(b,y)=>{const _=Array.isArray(b)?b:[b];for(const C of _){const F=ci(a,C)??r.querySelector(`input[name*="${C}"]`);F&&(F.value=y)}},addError:b=>{const y=r.querySelector("[data-formie-field-type] > div, [data-field-type] > div")||r,_=y.querySelector(Zn);_&&_.remove();const C=document.createElement("div");C.setAttribute("data-payment-error",""),C.textContent=b,pe(C,n||i,"fieldError"),y.appendChild(C)},removeError:()=>{var b;(b=r.querySelector(Zn))==null||b.remove()},addSuccess:b=>{const y=r.querySelector("[data-formie-field-type] > div, [data-field-type] > div")||r,_=y.querySelector(Yn);_&&_.remove();const C=document.createElement("div");C.setAttribute("data-payment-success",""),C.textContent=b,pe(C,n||i,"successMessage"),y.appendChild(C)},removeSuccess:()=>{var b;(b=r.querySelector(Yn))==null||b.remove()},hasToken:()=>qt(a,o).ok,waitForToken:(b=e.transport.waitForValueMs)=>ui(a,o,b).then(y=>y.ok),getFieldValue:(b,y="string")=>{const _=l(b);return y==="float"||y==="int"||y==="number"?Ar(_):_},resolveAmount:b=>{const y=n||i,C=String(b.type||"").toLowerCase()==="dynamic"&&typeof b.variable=="string"&&b.variable.trim()!=="",F=b.value??(C?b.variable:b.fixed),j=String(F??"").trim(),H=typeof F=="number"?F:Ar(j);if(Number.isFinite(H)&&H>0)return{ok:!0,value:H};if(j!==""){const ee=l(j),M=Ar(ee);if(Number.isFinite(M)&&M>0)return{ok:!0,value:M};const B=Gn(y,j);if(!ee)return{ok:!1,error:B?$e('Provide a value for "{label}" to proceed.',{label:B}):$e("Provide a payment amount to proceed.")}}return{ok:!1,error:$e("Payment amount must be greater than 0.")}},resolveCurrency:b=>{const y=n||i,C=String(b.type||"").toLowerCase()==="dynamic"&&typeof b.variable=="string"&&b.variable.trim()!=="",F=b.value??(C?b.variable:b.fixed??b.defaultCurrency??""),j=String(F??"").trim(),H=j.toUpperCase();if(/^[A-Z]{3}$/.test(H)&&!C)return{ok:!0,value:H};if(j!==""){const ee=String(l(j)||"").trim(),M=ee.toUpperCase();if(/^[A-Z]{3}$/.test(M))return{ok:!0,value:M};const B=Gn(y,j);if(!ee)return{ok:!1,error:B?$e('Provide a value for "{label}" to proceed.',{label:B}):$e("Provide a payment currency to proceed.")}}return{ok:!1,error:$e("Payment currency must be a valid 3-letter code.")}},watchFieldValueChanges:(b,y,_=600)=>{const C=n||i,F=b.map(B=>String(B||"").trim()).filter(Boolean);if(F.length===0)return()=>{};const j=s(),H=new Set;F.forEach(B=>{var P;const N=St(B),w=j.get(N);if((P=w==null?void 0:w.names)!=null&&P.length){w.names.forEach(W=>{H.add(W)});return}const A=Of(N);A&&(H.add(A),H.add(`${A}[]`))});const ee=oi(()=>{y()},_),M=B=>{const N=B.target,w=(N==null?void 0:N.name)||"";!w||!H.has(w)||ee()};return C.addEventListener("input",M),C.addEventListener("change",M),()=>{C.removeEventListener("input",M),C.removeEventListener("change",M)}},triggerSubmit:()=>{n&&n.setAttribute("data-formie-internal-resubmit","true"),n&&typeof n.requestSubmit=="function"?n.requestSubmit():n&&n.submit()},releaseSubmitLoading:()=>{n&&(n.removeAttribute("data-formie-internal-resubmit"),Qt(n))},getBillingData:b=>{const y={};if(!b||typeof b!="object")return{billing_details:y};if(b.billingName){const _=l(b.billingName);_&&(y.name=_)}if(b.billingEmail){const _=l(b.billingEmail);_&&(y.email=_)}if(b.billingAddress){const _=b.billingAddress,C={},F=l(`${_}.address1`),j=l(`${_}.address2`),H=l(`${_}.address3`),ee=l(`${_}.city`),M=l(`${_}.zip`),B=l(`${_}.state`),N=l(`${_}.country`);F&&(C.line1=F),j&&(C.line2=j),H&&(C.line3=H),ee&&(C.city=ee),M&&(C.postal_code=M),B&&(C.state=B),N&&(C.country=N),Object.keys(C).length&&(y.address=C)}return{billing_details:y}},events:{onForm:(b,y)=>n?Qn(n,b,y):()=>{},onRoot:(b,y)=>Qn(i,b,y)}}}const Ne=Fe("payments");function Xn(t){const e=t;return!e.closest("[data-formie-page-hidden]")&&!e.closest("[hidden]")}function Jf(t){const e=t.defaultRequiredInputSuffixes??If[t.id]??[];return{id:t.id,kind:"payment",match:r=>{var n,i;return!!(r.target.querySelector('[data-formie-field-type="payment"]')||r.target.closest('[data-formie-field-type="payment"]')||((i=(n=r.target).getAttribute)==null?void 0:i.call(n,"data-formie-field-type"))==="payment")},setup:async r=>{const n=r.target,i=n.__formiePaymentModuleRegistry||{};n.__formiePaymentModuleRegistry=i;const a=i[t.id];if(a!=null&&a.destroy){Ne.warn("Found stale payment module instance; destroying previous.",{moduleId:t.id});try{await a.destroy()}catch{}}const o=Wf(t.id,r.options||{},{defaultRequiredInputSuffixes:e}),s=Kf(r,o),l={...r,options:o,services:s},c=[];let d=null,h=null,f=null,u=null;const g=async()=>(d||(Ne.log("Loading payment provider API.",{moduleId:t.id}),d=t.load(l)),d),x=async()=>{if(!t.mount||h||!Xn(r.target))return;const v=await g();try{h=await t.mount({api:v,field:r.target,services:s,options:o,provider:o.provider}),Ne.log("Payment widget mounted.",{moduleId:t.id,handle:o.handle})}catch{Ne.warn("Payment widget mount failed.",{moduleId:t.id,handle:o.handle})}};if(c.push(r.on("formie:submit:before",()=>{s.removeError(),s.removeSuccess()})),t.setup){const v=r.root||r.form||r.target;f=await t.setup({...l,root:v}),f.destroy&&c.push(f.destroy)}t.mount&&Xn(r.target)&&await x(),["formie:page:navigate:after","formie:submit:result"].forEach(v=>{const b=()=>{x()};r.root.addEventListener(v,b),c.push(()=>{r.root.removeEventListener(v,b)})});const p=async()=>{var v;if(Ne.log("Destroying payment module.",{moduleId:t.id,handle:o.handle}),c.forEach(b=>b()),h&&t.unmount){const b=await g();await t.unmount({api:b,widget:h,field:r.target,services:s,options:o,provider:o.provider}),Ne.log("Payment widget unmounted.",{moduleId:t.id,handle:o.handle})}((v=i[t.id])==null?void 0:v.destroy)===p&&delete i[t.id],Ne.log("Payment module destroy complete.",{moduleId:t.id,handle:o.handle})};return i[t.id]={destroy:p},{destroy:p,onBeforeStage:async v=>{if(f!=null&&f.onBeforeStage){await f.onBeforeStage(v);return}if(v.stage!=="authorize"||v.action!=="submit")return;const y=r.target.closest("[data-formie-page]");if(y!=null&&y.hasAttribute("data-formie-page-hidden"))return;await x();const _=await g();if(t.onBeforeAuthorize){u||(u=(async()=>t.onBeforeAuthorize({api:_,widget:h,field:r.target,services:s,options:o,provider:o.provider,stageCtx:v}))().finally(()=>{u=null}));const j=await u;if(Ne.log("onBeforeAuthorize resolved.",{moduleId:t.id,handle:o.handle,ok:j}),!j){v.abort(o.transport.errorMessage);return}return}if(o.transport.requiredInputSuffixes.length===0)return;const C=r.form||r.root,F=await ui(C,o.transport.requiredInputSuffixes,o.transport.waitForValueMs);F.ok||(Ne.warn("Required payment input(s) missing.",{moduleId:t.id,handle:o.handle,missingSuffix:F.missingSuffix}),v.abort(o.transport.errorMessage))},onAfterStage:async(v,b)=>{v.stage!=="dispatch"||!t.onAfterSubmit||await t.onAfterSubmit({field:r.target,services:s,options:o,provider:o.provider,result:b})}}}}}const Mm=Jf,Gf="[data-formie-address-autocomplete-input]",eo="[data-formie-address-location]",Yf={autoComplete:"[data-formie-address-autocomplete-input]",address1:"[data-formie-address-line1-input]",address2:"[data-formie-address-line2-input]",address3:"[data-formie-address-line3-input]",city:"[data-formie-address-city-input]",state:"[data-formie-address-state-input]",zip:"[data-formie-address-zip-input]",country:"[data-formie-address-country-input]"},Zf=new Set(["handle"]);function Qf(t,e){return(typeof e.handle=="string"&&e.handle.trim()!==""?e.handle.trim():"")||t}function Xf(t,e){const r=e||{},n=Object.entries(r).reduce((i,[a,o])=>(Zf.has(a)||(i[a]=o),i),{});return{handle:Qf(t,r),provider:n}}function em(t,e,r){return t.addEventListener(e,r),()=>{t.removeEventListener(e,r)}}function tm(t){const e=t.target,r=t.form,n=t.root,i=Gf;return{root:n,field:e,form:r,input:{getAutocomplete:()=>e.querySelector(i),setValue:(a,o,s)=>{const l=Yf[a],c=e.querySelector(l);c&&(c.value=o||s||"")}},location:{getButton:()=>e.querySelector(eo),onUseLocation:a=>{const o=e.querySelector(eo);if(!o)return()=>{};const s=l=>{l.preventDefault(),navigator.geolocation&&navigator.geolocation.getCurrentPosition(a,()=>{},{enableHighAccuracy:!0})};return o.addEventListener("click",s),()=>{o.removeEventListener("click",s)}}},events:{onField:(a,o)=>em(e,a,o)}}}const it=Fe("address");function to(t){const e=t;return!e.closest("[data-formie-page-hidden]")&&!e.closest("[hidden]")}function rm(t){return{id:t.id,kind:"address",match:e=>!!e.target.querySelector("[data-formie-address-autocomplete-input]"),setup:async e=>{const r=Xf(t.id,e.options||{}),n=tm(e);it.log("Setup module.",{moduleId:t.id});const i={...e,options:r,services:n},a=[];let o=null,s=null;if(!n.input.getAutocomplete())return console.warn(`[formie] Address module "${t.id}" skipped: no autocomplete input found in target. Ensure the Address field has the Auto-Complete subfield enabled.`),it.warn("Autocomplete input missing; skipping module.",{moduleId:t.id}),{destroy:()=>{}};const c=async()=>(o||(it.log("Loading provider API.",{moduleId:t.id}),o=t.load(i)),o),d=async()=>{if(s||!to(e.target))return;const u=await c();s=await t.mount({api:u,field:e.target,services:n,options:r,provider:r.provider}),it.log("Widget mounted.",{moduleId:t.id})};to(e.target)&&await d(),["formie:page:navigate:after","formie:submit:result"].forEach(u=>{const g=()=>{d()};e.root.addEventListener(u,g),a.push(()=>{e.root.removeEventListener(u,g)})});const f=n.location.onUseLocation(u=>{t.onCurrentLocation&&(async()=>{var x;if(await d(),!s)return;const g=await c();await((x=t.onCurrentLocation)==null?void 0:x.call(t,u,{api:g,widget:s,field:e.target,services:n,options:r,provider:r.provider}))})()});return f&&a.push(f),{destroy:async()=>{if(it.log("Destroying module.",{moduleId:t.id}),a.forEach(u=>u()),s&&t.unmount){const u=await c();await t.unmount({api:u,widget:s,field:e.target,services:n,options:r,provider:r.provider}),it.log("Widget unmounted.",{moduleId:t.id})}}}}}}const Rm=rm;function nm(t){const e=t.getElementById("formie-preview-config");if(!(e instanceof HTMLScriptElement)||!e.textContent)return{};try{return JSON.parse(e.textContent)}catch(r){return console.warn("[FormiePreview] Failed to parse preview config.",r),{}}}function om(t,e){if(!(e!=null&&e.length))return;const r=JSON.stringify(e);t.querySelectorAll("[data-formie], [data-formie-form]").forEach(n=>{n.setAttribute("data-formie-modules",r)})}function im(t){var l,c,d;const e=t.body,r=(l=t.defaultView)==null?void 0:l.HTMLElement;if(!e)return((c=t.documentElement)==null?void 0:c.scrollHeight)||0;const n=e.getBoundingClientRect(),i=(d=t.defaultView)==null?void 0:d.getComputedStyle(e),a=parseFloat(i.paddingTop||"0")||0,o=parseFloat(i.paddingBottom||"0")||0,s=Array.from(e.children).reduce((h,f)=>{if(!r||!(f instanceof r)||f.tagName==="SCRIPT")return h;const u=f.getBoundingClientRect();return Math.max(h,u.bottom-n.top)},a);return Math.ceil(s+o)}function at(t,e){var n;const r=im(t.document);e==null||e(r),(n=t.parent)==null||n.postMessage({type:"formie-preview:height",height:r},"*")}function am(t,e){const r=t.document;if(typeof t.ResizeObserver<"u"){const n=new t.ResizeObserver(()=>{at(t,e)});n.observe(r.documentElement),r.body&&n.observe(r.body)}["click","input","change"].forEach(n=>{r.addEventListener(n,()=>{t.requestAnimationFrame(()=>{at(t,e)})},!0)})}async function sm(t,e){var i;const r=t.document,n=nm(r);am(t,e),t.addEventListener("load",()=>{at(t,e)},{once:!0}),t.requestAnimationFrame(()=>{at(t,e),t.requestAnimationFrame(()=>{at(t,e)})}),(i=n.modules)!=null&&i.length&&(au(!1),om(r,n.modules),await hf().scan(r)),at(t,e)}const lm=Object.assign({"../../../browser/ui-reference/examples/address.preview.ts":()=>O(()=>import("./address.preview.D-ghwOAm.js"),[]),"../../../browser/ui-reference/examples/agree.preview.ts":()=>O(()=>import("./agree.preview.BuDgdg1_.js"),[]),"../../../browser/ui-reference/examples/buttons-loading.preview.ts":()=>O(()=>import("./buttons-loading.preview.BvDn73XT.js"),[]),"../../../browser/ui-reference/examples/buttons-positions.preview.ts":()=>O(()=>import("./buttons-positions.preview.B-G789jX.js"),[]),"../../../browser/ui-reference/examples/buttons-variants.preview.ts":()=>O(()=>import("./buttons-variants.preview.0jJSmcOh.js"),[]),"../../../browser/ui-reference/examples/buttons.preview.ts":()=>O(()=>import("./buttons.preview.MzXYysPp.js"),[]),"../../../browser/ui-reference/examples/calculations.preview.ts":()=>O(()=>import("./calculations.preview.CtChBkrf.js"),[]),"../../../browser/ui-reference/examples/categories.preview.ts":()=>O(()=>import("./categories.preview.ixyBoeER.js"),__vite__mapDeps([44,45])),"../../../browser/ui-reference/examples/checkboxes.preview.ts":()=>O(()=>import("./checkboxes.preview.BI4i9Rg-.js"),[]),"../../../browser/ui-reference/examples/date.preview.ts":()=>O(()=>import("./date.preview.CSKAFHj6.js"),[]),"../../../browser/ui-reference/examples/entries.preview.ts":()=>O(()=>import("./entries.preview.vVoUh2wl.js"),__vite__mapDeps([46,45])),"../../../browser/ui-reference/examples/field-anatomy.preview.ts":()=>O(()=>import("./field-anatomy.preview.CDGHSvef.js"),[]),"../../../browser/ui-reference/examples/field-normal.preview.ts":()=>O(()=>import("./field-normal.preview.CiEbz5Fv.js"),[]),"../../../browser/ui-reference/examples/file-upload.preview.ts":()=>O(()=>import("./file-upload.preview.CTvngf20.js"),[]),"../../../browser/ui-reference/examples/hidden.preview.ts":()=>O(()=>import("./hidden.preview.MMyPAdXC.js"),[]),"../../../browser/ui-reference/examples/loading-button-variants.preview.ts":()=>O(()=>import("./loading-button-variants.preview.DsRnArXp.js"),[]),"../../../browser/ui-reference/examples/loading-buttons.preview.ts":()=>O(()=>import("./loading-buttons.preview.BUXpUDz6.js"),[]),"../../../browser/ui-reference/examples/loading-sizes-colors.preview.ts":()=>O(()=>import("./loading-sizes-colors.preview.IYbMzHOV.js"),[]),"../../../browser/ui-reference/examples/loading.preview.ts":()=>O(()=>import("./loading.preview.DlOgX5Nv.js"),[]),"../../../browser/ui-reference/examples/messages.preview.ts":()=>O(()=>import("./messages.preview.Bpxa33ze.js"),[]),"../../../browser/ui-reference/examples/multi-line-text-rich-text.preview.ts":()=>O(()=>import("./multi-line-text-rich-text.preview.pKViw2NJ.js"),[]),"../../../browser/ui-reference/examples/multi-line-text.preview.ts":()=>O(()=>import("./multi-line-text.preview.CRb5IKJ_.js"),[]),"../../../browser/ui-reference/examples/page-navigation-only.preview.ts":()=>O(()=>import("./page-navigation-only.preview.D9zHiF02.js"),[]),"../../../browser/ui-reference/examples/payment.preview.ts":()=>O(()=>import("./payment.preview.DtictnrE.js"),[]),"../../../browser/ui-reference/examples/phone.preview.ts":()=>O(()=>import("./phone.preview.D-k2drYO.js"),[]),"../../../browser/ui-reference/examples/progress.preview.ts":()=>O(()=>import("./progress.preview.kV7Ij1sV.js"),[]),"../../../browser/ui-reference/examples/radio.preview.ts":()=>O(()=>import("./radio.preview.DrkMq2KR.js"),[]),"../../../browser/ui-reference/examples/recipients.preview.ts":()=>O(()=>import("./recipients.preview.BWBx9rU1.js"),__vite__mapDeps([47,45])),"../../../browser/ui-reference/examples/repeater.preview.ts":()=>O(()=>import("./repeater.preview.BzsOZh0V.js"),[]),"../../../browser/ui-reference/examples/signature.preview.ts":()=>O(()=>import("./signature.preview.B_FNvQIT.js"),[]),"../../../browser/ui-reference/examples/single-line-text.preview.ts":()=>O(()=>import("./single-line-text.preview.BmmellSY.js"),[]),"../../../browser/ui-reference/examples/summary.preview.ts":()=>O(()=>import("./summary.preview.By_O1ubB.js"),[]),"../../../browser/ui-reference/examples/table.preview.ts":()=>O(()=>import("./table.preview.BFrHaTOl.js"),[]),"../../../browser/ui-reference/examples/tags.preview.ts":()=>O(()=>import("./tags.preview.CmHYrzId.js"),[])});function cm(t){const e=t.split(/[?#]/,1)[0]||"/";return e.endsWith("/")?e:`${e.slice(0,e.lastIndexOf("/")+1)}`}function um(t,e="/"){return e==="/"||!t.startsWith(e)?t:`/${t.slice(e.length)}`}function dm(t,e,r="/"){return t.startsWith("@/")?`/${t.slice(2)}`:um(new URL(t,`https://docs.local${cm(e)}`).pathname,r)}function fm(t){return`../../../${t.replace(/^\//,"")}`}async function mm(t,e,r="/"){const n=dm(t,e,r),i=fm(n),a=lm[i];if(!a)return console.warn(`[FormiePreview] No preview source found for "${t}" resolved from "${e}".`),null;const o=await a();return o.default??o.preview??null}const hm=["srcdoc"],pm=8,gm=Ce({__name:"FormiePreview",props:{markup:{},minHeight:{default:120},src:{}},setup(t){const e=t,r=so(),{site:n}=We(),i=ae(null),a=ae(null),o=ae(e.minHeight);let s=0;he(()=>[r.path,e.src,n.value.base],async()=>{if(!e.src){a.value=null;return}const m=++s,p=await mm(e.src,r.path,n.value.base);m===s&&(a.value=p)},{immediate:!0});const l=V(()=>{var m;return((m=a.value)==null?void 0:m.markup)??e.markup??""}),c=V(()=>{var m;return((m=a.value)==null?void 0:m.minHeight)??e.minHeight}),d=V(()=>{var p;const m=(p=a.value)==null?void 0:p.modules;return JSON.stringify({modules:m!=null&&m.length?m:void 0}).replaceAll("<","\\u003c")});he(c,m=>{o.value=m},{immediate:!0}),he(()=>[l.value,c.value],(m,p)=>{(!p||p[0]!==l.value||p[1]!==c.value)&&(o.value=c.value)});function h(m){!Number.isFinite(m)||m<=0||(o.value=Math.ceil(m+pm))}function f(){var j,H,ee;const m=(j=i.value)==null?void 0:j.contentDocument,p=m==null?void 0:m.body,v=(H=m==null?void 0:m.defaultView)==null?void 0:H.HTMLElement;if(!p)return c.value;const b=p.getBoundingClientRect(),y=(ee=m.defaultView)==null?void 0:ee.getComputedStyle(p),_=parseFloat((y==null?void 0:y.paddingTop)||"0")||0,C=parseFloat((y==null?void 0:y.paddingBottom)||"0")||0,F=Array.from(p.children).reduce((M,B)=>{if(!v||!(B instanceof v)||B.tagName==="SCRIPT")return M;const N=B.getBoundingClientRect();return Math.max(M,N.bottom-b.top)},_);return Math.ceil(F+C)}function u(m){var p,v;((p=m.data)==null?void 0:p.type)==="formie-preview:height"&&m.source===((v=i.value)==null?void 0:v.contentWindow)&&h(Number(m.data.height))}function g(){var p;const m=(p=i.value)==null?void 0:p.contentWindow;m&&(h(f()),sm(m,h))}He(()=>{window.addEventListener("message",u)}),nr(()=>{window.removeEventListener("message",u)});const x=V(()=>`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    ${[_c,Sc,Ac,Tc,Cc,Ic,Lc,Mc,Rc,Fc,Oc,Pc,Nc,Dc,zc,$c,Vc,Hc,qc,jc,Bc,Uc,Wc,Kc,Jc,Gc].join(`
`)}
    body { margin: 0; padding: 16px; background: #fff; }
  </style>
</head>
<body>
  <script id="formie-preview-config" type="application/json">${d.value}<\/script>
  ${l.value}
</body>
</html>`);return(m,p)=>(L(),R("iframe",{ref_key:"iframeRef",ref:i,class:"formie-preview-frame",style:At({height:`${o.value}px`}),srcdoc:x.value,title:"Formie preview",loading:"lazy",onLoad:g},null,44,hm))}}),Fm=kc({enhanceApp({app:t}){t.component("FormiePreview",gm)}});export{Yf as A,pe as B,_m as C,Et as D,Im as a,Cm as b,Am as c,Rm as d,Lm as e,Sm as f,wm as g,Fe as h,Vf as i,Of as j,ym as k,fs as l,ds as m,St as n,xm as o,Or as p,ar as q,jf as r,cn as s,Fm as t,kt as u,oi as v,Tm as w,km as x,Mm as y,Em as z};
