import{r as e}from"./rolldown-runtime-hePW80VL.js";import{T as t,w as n}from"./dndkit-Tbq_EQgB.js";import{A as r,C as i,E as a,M as o,O as s,S as c,T as l,b as u,k as d,x as f,y as p}from"./utils-D_lXaCpY.js";import{l as m}from"./pk-state-panel-BTo2xiug-40Dks_zl.js";import{i as h,n as g,r as _,t as v}from"./has-slot-DJv86HKx-D2K9y9lH.js";import{c as y,d as b,f as x,i as S,l as C,n as w,p as T,r as E,s as D}from"./lit-C7H9X-yg.js";import{c as O,o as ee,s as k}from"./pk-alert-BQAb4lJc-KYGPWJEN.js";var A=e(t(),1),te=o({tagName:`pk-icon`,elementClass:m,react:A.default});function j(e){let t=e.split(`-`)[0];return t===`inline-start`?`left`:t===`inline-end`?`right`:t===`top`||t===`bottom`||t===`left`||t===`right`?t:`bottom`}function ne(e,t,n,r,i){let a=j(e),o=t.x+t.width/2-n.x,s=t.y+t.height/2-n.y;return Math.abs(i?.y??0)>r&&(a===`top`||a===`bottom`)?`${o}px ${t.y+t.height/2-n.y}px`:{top:`${o}px calc(100% + ${r}px)`,bottom:`${o}px ${-r}px`,left:`calc(100% + ${r}px) ${s}px`,right:`${-r}px ${s}px`}[a]}function re(e,t){if(!t){e.removeAttribute(`data-side`);return}e.setAttribute(`data-side`,j(t))}function ie(e,t,n=100,r){let i=()=>e.getAttribute(`data-current-placement`)??t;return!r?.requireEvent&&e.hasAttribute(`data-current-placement`)?Promise.resolve(i()):new Promise(t=>{let a=!1,o=()=>{a||(a=!0,t(i()))};e.addEventListener(`pk-reposition`,o,{once:!0}),r?.requireEvent||requestAnimationFrame(()=>{requestAnimationFrame(()=>{e.hasAttribute(`data-current-placement`)&&o()})}),window.setTimeout(o,n)})}var M=Math.min,N=Math.max,ae=Math.round,oe=Math.floor,P=e=>({x:e,y:e}),se={left:`right`,right:`left`,bottom:`top`,top:`bottom`};function ce(e,t,n){return N(e,M(t,n))}function F(e,t){return typeof e==`function`?e(t):e}function I(e){return e.split(`-`)[0]}function L(e){return e.split(`-`)[1]}function le(e){return e===`x`?`y`:`x`}function ue(e){return e===`y`?`height`:`width`}function R(e){let t=e[0];return t===`t`||t===`b`?`y`:`x`}function de(e){return le(R(e))}function fe(e,t,n){n===void 0&&(n=!1);let r=L(e),i=de(e),a=ue(i),o=i===`x`?r===(n?`end`:`start`)?`right`:`left`:r===`start`?`bottom`:`top`;return t.reference[a]>t.floating[a]&&(o=xe(o)),[o,xe(o)]}function pe(e){let t=xe(e);return[me(e),t,me(t)]}function me(e){return e.includes(`start`)?e.replace(`start`,`end`):e.replace(`end`,`start`)}var he=[`left`,`right`],ge=[`right`,`left`],_e=[`top`,`bottom`],ve=[`bottom`,`top`];function ye(e,t,n){switch(e){case`top`:case`bottom`:return n?t?ge:he:t?he:ge;case`left`:case`right`:return t?_e:ve;default:return[]}}function be(e,t,n,r){let i=L(e),a=ye(I(e),n===`start`,r);return i&&(a=a.map(e=>e+`-`+i),t&&(a=a.concat(a.map(me)))),a}function xe(e){let t=I(e);return se[t]+e.slice(t.length)}function Se(e){return{top:0,right:0,bottom:0,left:0,...e}}function Ce(e){return typeof e==`number`?{top:e,right:e,bottom:e,left:e}:Se(e)}function we(e){let{x:t,y:n,width:r,height:i}=e;return{width:r,height:i,top:n,left:t,right:t+r,bottom:n+i,x:t,y:n}}function Te(e,t,n){let{reference:r,floating:i}=e,a=R(t),o=de(t),s=ue(o),c=I(t),l=a===`y`,u=r.x+r.width/2-i.width/2,d=r.y+r.height/2-i.height/2,f=r[s]/2-i[s]/2,p;switch(c){case`top`:p={x:u,y:r.y-i.height};break;case`bottom`:p={x:u,y:r.y+r.height};break;case`right`:p={x:r.x+r.width,y:d};break;case`left`:p={x:r.x-i.width,y:d};break;default:p={x:r.x,y:r.y}}switch(L(t)){case`start`:p[o]-=f*(n&&l?-1:1);break;case`end`:p[o]+=f*(n&&l?-1:1)}return p}async function Ee(e,t){t===void 0&&(t={});let{x:n,y:r,platform:i,rects:a,elements:o,strategy:s}=e,{boundary:c=`clippingAncestors`,rootBoundary:l=`viewport`,elementContext:u=`floating`,altBoundary:d=!1,padding:f=0}=F(t,e),p=Ce(f),m=o[d?u===`floating`?`reference`:`floating`:u],h=we(await i.getClippingRect({element:await(i.isElement==null?void 0:i.isElement(m))??!0?m:m.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(o.floating)),boundary:c,rootBoundary:l,strategy:s})),g=u===`floating`?{x:n,y:r,width:a.floating.width,height:a.floating.height}:a.reference,_=await(i.getOffsetParent==null?void 0:i.getOffsetParent(o.floating)),v=await(i.isElement==null?void 0:i.isElement(_))&&await(i.getScale==null?void 0:i.getScale(_))||{x:1,y:1},y=we(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:o,rect:g,offsetParent:_,strategy:s}):g);return{top:(h.top-y.top+p.top)/v.y,bottom:(y.bottom-h.bottom+p.bottom)/v.y,left:(h.left-y.left+p.left)/v.x,right:(y.right-h.right+p.right)/v.x}}var De=50,Oe=async(e,t,n)=>{let{placement:r=`bottom`,strategy:i=`absolute`,middleware:a=[],platform:o}=n,s=o.detectOverflow?o:{...o,detectOverflow:Ee},c=await(o.isRTL==null?void 0:o.isRTL(t)),l=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:u,y:d}=Te(l,r,c),f=r,p=0,m={};for(let n=0;n<a.length;n++){let h=a[n];if(!h)continue;let{name:g,fn:_}=h,{x:v,y,data:b,reset:x}=await _({x:u,y:d,initialPlacement:r,placement:f,strategy:i,middlewareData:m,rects:l,platform:s,elements:{reference:e,floating:t}});u=v??u,d=y??d,m[g]={...m[g],...b},x&&p<De&&(p++,typeof x==`object`&&(x.placement&&(f=x.placement),x.rects&&(l=x.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):x.rects),{x:u,y:d}=Te(l,f,c)),n=-1)}return{x:u,y:d,placement:f,strategy:i,middlewareData:m}},ke=e=>({name:`arrow`,options:e,async fn(t){let{x:n,y:r,placement:i,rects:a,platform:o,elements:s,middlewareData:c}=t,{element:l,padding:u=0}=F(e,t)||{};if(l==null)return{};let d=Ce(u),f={x:n,y:r},p=de(i),m=ue(p),h=await o.getDimensions(l),g=p===`y`,_=g?`top`:`left`,v=g?`bottom`:`right`,y=g?`clientHeight`:`clientWidth`,b=a.reference[m]+a.reference[p]-f[p]-a.floating[m],x=f[p]-a.reference[p],S=await(o.getOffsetParent==null?void 0:o.getOffsetParent(l)),C=S?S[y]:0;(!C||!await(o.isElement==null?void 0:o.isElement(S)))&&(C=s.floating[y]||a.floating[m]);let w=b/2-x/2,T=C/2-h[m]/2-1,E=M(d[_],T),D=M(d[v],T),O=E,ee=C-h[m]-D,k=C/2-h[m]/2+w,A=ce(O,k,ee),te=!c.arrow&&L(i)!=null&&k!==A&&a.reference[m]/2-(k<O?E:D)-h[m]/2<0,j=te?k<O?k-O:k-ee:0;return{[p]:f[p]+j,data:{[p]:A,centerOffset:k-A-j,...te&&{alignmentOffset:j}},reset:te}}}),Ae=function(e){return e===void 0&&(e={}),{name:`flip`,options:e,async fn(t){var n;let{placement:r,middlewareData:i,rects:a,initialPlacement:o,platform:s,elements:c}=t,{mainAxis:l=!0,crossAxis:u=!0,fallbackPlacements:d,fallbackStrategy:f=`bestFit`,fallbackAxisSideDirection:p=`none`,flipAlignment:m=!0,...h}=F(e,t);if((n=i.arrow)!=null&&n.alignmentOffset)return{};let g=I(r),_=R(o),v=I(o)===o,y=await(s.isRTL==null?void 0:s.isRTL(c.floating)),b=d||(v||!m?[xe(o)]:pe(o)),x=p!==`none`;!d&&x&&b.push(...be(o,m,p,y));let S=[o,...b],C=await s.detectOverflow(t,h),w=[],T=i.flip?.overflows||[];if(l&&w.push(C[g]),u){let e=fe(r,a,y);w.push(C[e[0]],C[e[1]])}if(T=[...T,{placement:r,overflows:w}],!w.every(e=>e<=0)){let e=(i.flip?.index||0)+1,t=S[e];if(t&&(u!==`alignment`||_===R(t)||T.every(e=>R(e.placement)!==_||e.overflows[0]>0)))return{data:{index:e,overflows:T},reset:{placement:t}};let n=T.filter(e=>e.overflows[0]<=0).sort((e,t)=>e.overflows[1]-t.overflows[1])[0]?.placement;if(!n)switch(f){case`bestFit`:{let e=T.filter(e=>{if(x){let t=R(e.placement);return t===_||t===`y`}return!0}).map(e=>[e.placement,e.overflows.filter(e=>e>0).reduce((e,t)=>e+t,0)]).sort((e,t)=>e[1]-t[1])[0]?.[0];e&&(n=e);break}case`initialPlacement`:n=o}if(r!==n)return{reset:{placement:n}}}return{}}}},je=new Set([`left`,`top`]);async function Me(e,t){let{placement:n,platform:r,elements:i}=e,a=await(r.isRTL==null?void 0:r.isRTL(i.floating)),o=I(n),s=L(n),c=R(n)===`y`,l=je.has(o)?-1:1,u=a&&c?-1:1,d=F(t,e),{mainAxis:f,crossAxis:p,alignmentAxis:m}=typeof d==`number`?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return s&&typeof m==`number`&&(p=s===`end`?m*-1:m),c?{x:p*u,y:f*l}:{x:f*l,y:p*u}}var Ne=function(e){return e===void 0&&(e=0),{name:`offset`,options:e,async fn(t){var n;let{x:r,y:i,placement:a,middlewareData:o}=t,s=await Me(t,e);return a===o.offset?.placement&&(n=o.arrow)!=null&&n.alignmentOffset?{}:{x:r+s.x,y:i+s.y,data:{...s,placement:a}}}}},Pe=function(e){return e===void 0&&(e={}),{name:`shift`,options:e,async fn(t){let{x:n,y:r,placement:i,platform:a}=t,{mainAxis:o=!0,crossAxis:s=!1,limiter:c={fn:e=>{let{x:t,y:n}=e;return{x:t,y:n}}},...l}=F(e,t),u={x:n,y:r},d=await a.detectOverflow(t,l),f=R(I(i)),p=le(f),m=u[p],h=u[f];if(o){let e=p===`y`?`top`:`left`,t=p===`y`?`bottom`:`right`,n=m+d[e],r=m-d[t];m=ce(n,m,r)}if(s){let e=f===`y`?`top`:`left`,t=f===`y`?`bottom`:`right`,n=h+d[e],r=h-d[t];h=ce(n,h,r)}let g=c.fn({...t,[p]:m,[f]:h});return{...g,data:{x:g.x-n,y:g.y-r,enabled:{[p]:o,[f]:s}}}}}},Fe=function(e){return e===void 0&&(e={}),{name:`size`,options:e,async fn(t){var n,r;let{placement:i,rects:a,platform:o,elements:s}=t,{apply:c=()=>{},...l}=F(e,t),u=await o.detectOverflow(t,l),d=I(i),f=L(i),p=R(i)===`y`,{width:m,height:h}=a.floating,g,_;d===`top`||d===`bottom`?(g=d,_=f===(await(o.isRTL==null?void 0:o.isRTL(s.floating))?`start`:`end`)?`left`:`right`):(_=d,g=f===`end`?`top`:`bottom`);let v=h-u.top-u.bottom,y=m-u.left-u.right,b=M(h-u[g],v),x=M(m-u[_],y),S=!t.middlewareData.shift,C=b,w=x;if((n=t.middlewareData.shift)!=null&&n.enabled.x&&(w=y),(r=t.middlewareData.shift)!=null&&r.enabled.y&&(C=v),S&&!f){let e=N(u.left,0),t=N(u.right,0),n=N(u.top,0),r=N(u.bottom,0);p?w=m-2*(e!==0||t!==0?e+t:N(u.left,u.right)):C=h-2*(n!==0||r!==0?n+r:N(u.top,u.bottom))}await c({...t,availableWidth:w,availableHeight:C});let T=await o.getDimensions(s.floating);return m!==T.width||h!==T.height?{reset:{rects:!0}}:{}}}};function Ie(){return typeof window<`u`}function z(e){return Le(e)?(e.nodeName||``).toLowerCase():`#document`}function B(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function V(e){return((Le(e)?e.ownerDocument:e.document)||window.document)?.documentElement}function Le(e){return Ie()?e instanceof Node||e instanceof B(e).Node:!1}function H(e){return Ie()?e instanceof Element||e instanceof B(e).Element:!1}function U(e){return Ie()?e instanceof HTMLElement||e instanceof B(e).HTMLElement:!1}function Re(e){return!Ie()||typeof ShadowRoot>`u`?!1:e instanceof ShadowRoot||e instanceof B(e).ShadowRoot}function W(e){let{overflow:t,overflowX:n,overflowY:r,display:i}=q(e);return/auto|scroll|overlay|hidden|clip/.test(t+r+n)&&i!==`inline`&&i!==`contents`}function ze(e){return/^(table|td|th)$/.test(z(e))}function Be(e){try{if(e.matches(`:popover-open`))return!0}catch{}try{return e.matches(`:modal`)}catch{return!1}}var Ve=/transform|translate|scale|rotate|perspective|filter/,He=/paint|layout|strict|content/,G=e=>!!e&&e!==`none`,Ue;function We(e){let t=H(e)?q(e):e;return G(t.transform)||G(t.translate)||G(t.scale)||G(t.rotate)||G(t.perspective)||!Ke()&&(G(t.backdropFilter)||G(t.filter))||Ve.test(t.willChange||``)||He.test(t.contain||``)}function Ge(e){let t=J(e);for(;U(t)&&!K(t);){if(We(t))return t;if(Be(t))return null;t=J(t)}return null}function Ke(){return Ue??=typeof CSS<`u`&&CSS.supports&&CSS.supports(`-webkit-backdrop-filter`,`none`),Ue}function K(e){return/^(html|body|#document)$/.test(z(e))}function q(e){return B(e).getComputedStyle(e)}function qe(e){return H(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function J(e){if(z(e)===`html`)return e;let t=e.assignedSlot||e.parentNode||Re(e)&&e.host||V(e);return Re(t)?t.host:t}function Je(e){let t=J(e);return K(t)?e.ownerDocument?e.ownerDocument.body:e.body:U(t)&&W(t)?t:Je(t)}function Y(e,t,n){t===void 0&&(t=[]),n===void 0&&(n=!0);let r=Je(e),i=r===e.ownerDocument?.body,a=B(r);if(i){let e=Ye(a);return t.concat(a,a.visualViewport||[],W(r)?r:[],e&&n?Y(e):[])}return t.concat(r,Y(r,[],n))}function Ye(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function Xe(e){let t=q(e),n=parseFloat(t.width)||0,r=parseFloat(t.height)||0,i=U(e),a=i?e.offsetWidth:n,o=i?e.offsetHeight:r,s=ae(n)!==a||ae(r)!==o;return s&&(n=a,r=o),{width:n,height:r,$:s}}function Ze(e){return H(e)?e:e.contextElement}function X(e){let t=Ze(e);if(!U(t))return P(1);let n=t.getBoundingClientRect(),{width:r,height:i,$:a}=Xe(t),o=(a?ae(n.width):n.width)/r,s=(a?ae(n.height):n.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!s||!Number.isFinite(s))&&(s=1),{x:o,y:s}}var Qe=P(0);function $e(e){let t=B(e);return!Ke()||!t.visualViewport?Qe:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function et(e,t,n){return t===void 0&&(t=!1),!n||t&&n!==B(e)?!1:t}function Z(e,t,n,r){t===void 0&&(t=!1),n===void 0&&(n=!1);let i=e.getBoundingClientRect(),a=Ze(e),o=P(1);t&&(r?H(r)&&(o=X(r)):o=X(e));let s=et(a,n,r)?$e(a):P(0),c=(i.left+s.x)/o.x,l=(i.top+s.y)/o.y,u=i.width/o.x,d=i.height/o.y;if(a){let e=B(a),t=r&&H(r)?B(r):r,n=e,i=Ye(n);for(;i&&r&&t!==n;){let e=X(i),t=i.getBoundingClientRect(),r=q(i),a=t.left+(i.clientLeft+parseFloat(r.paddingLeft))*e.x,o=t.top+(i.clientTop+parseFloat(r.paddingTop))*e.y;c*=e.x,l*=e.y,u*=e.x,d*=e.y,c+=a,l+=o,n=B(i),i=Ye(n)}}return we({width:u,height:d,x:c,y:l})}function tt(e,t){let n=qe(e).scrollLeft;return t?t.left+n:Z(V(e)).left+n}function nt(e,t){let n=e.getBoundingClientRect();return{x:n.left+t.scrollLeft-tt(e,n),y:n.top+t.scrollTop}}function rt(e){let{elements:t,rect:n,offsetParent:r,strategy:i}=e,a=i===`fixed`,o=V(r),s=t?Be(t.floating):!1;if(r===o||s&&a)return n;let c={scrollLeft:0,scrollTop:0},l=P(1),u=P(0),d=U(r);if((d||!d&&!a)&&((z(r)!==`body`||W(o))&&(c=qe(r)),d)){let e=Z(r);l=X(r),u.x=e.x+r.clientLeft,u.y=e.y+r.clientTop}let f=o&&!d&&!a?nt(o,c):P(0);return{width:n.width*l.x,height:n.height*l.y,x:n.x*l.x-c.scrollLeft*l.x+u.x+f.x,y:n.y*l.y-c.scrollTop*l.y+u.y+f.y}}function it(e){return Array.from(e.getClientRects())}function at(e){let t=V(e),n=qe(e),r=e.ownerDocument.body,i=N(t.scrollWidth,t.clientWidth,r.scrollWidth,r.clientWidth),a=N(t.scrollHeight,t.clientHeight,r.scrollHeight,r.clientHeight),o=-n.scrollLeft+tt(e),s=-n.scrollTop;return q(r).direction===`rtl`&&(o+=N(t.clientWidth,r.clientWidth)-i),{width:i,height:a,x:o,y:s}}var ot=25;function st(e,t){let n=B(e),r=V(e),i=n.visualViewport,a=r.clientWidth,o=r.clientHeight,s=0,c=0;if(i){a=i.width,o=i.height;let e=Ke();(!e||e&&t===`fixed`)&&(s=i.offsetLeft,c=i.offsetTop)}let l=tt(r);if(l<=0){let e=r.ownerDocument,t=e.body,n=getComputedStyle(t),i=e.compatMode===`CSS1Compat`&&parseFloat(n.marginLeft)+parseFloat(n.marginRight)||0,o=Math.abs(r.clientWidth-t.clientWidth-i);o<=ot&&(a-=o)}else l<=ot&&(a+=l);return{width:a,height:o,x:s,y:c}}function ct(e,t){let n=Z(e,!0,t===`fixed`),r=n.top+e.clientTop,i=n.left+e.clientLeft,a=U(e)?X(e):P(1);return{width:e.clientWidth*a.x,height:e.clientHeight*a.y,x:i*a.x,y:r*a.y}}function lt(e,t,n){let r;if(t===`viewport`)r=st(e,n);else if(t===`document`)r=at(V(e));else if(H(t))r=ct(t,n);else{let n=$e(e);r={x:t.x-n.x,y:t.y-n.y,width:t.width,height:t.height}}return we(r)}function ut(e,t){let n=J(e);return n===t||!H(n)||K(n)?!1:q(n).position===`fixed`||ut(n,t)}function dt(e,t){let n=t.get(e);if(n)return n;let r=Y(e,[],!1).filter(e=>H(e)&&z(e)!==`body`),i=null,a=q(e).position===`fixed`,o=a?J(e):e;for(;H(o)&&!K(o);){let t=q(o),n=We(o);!n&&t.position===`fixed`&&(i=null),(a?!n&&!i:!n&&t.position===`static`&&i&&(i.position===`absolute`||i.position===`fixed`)||W(o)&&!n&&ut(e,o))?r=r.filter(e=>e!==o):i=t,o=J(o)}return t.set(e,r),r}function ft(e){let{element:t,boundary:n,rootBoundary:r,strategy:i}=e,a=[...n===`clippingAncestors`?Be(t)?[]:dt(t,this._c):[].concat(n),r],o=lt(t,a[0],i),s=o.top,c=o.right,l=o.bottom,u=o.left;for(let e=1;e<a.length;e++){let n=lt(t,a[e],i);s=N(n.top,s),c=M(n.right,c),l=M(n.bottom,l),u=N(n.left,u)}return{width:c-u,height:l-s,x:u,y:s}}function pt(e){let{width:t,height:n}=Xe(e);return{width:t,height:n}}function mt(e,t,n){let r=U(t),i=V(t),a=n===`fixed`,o=Z(e,!0,a,t),s={scrollLeft:0,scrollTop:0},c=P(0);function l(){c.x=tt(i)}if(r||!r&&!a){if((z(t)!==`body`||W(i))&&(s=qe(t)),r){let e=Z(t,!0,a,t);c.x=e.x+t.clientLeft,c.y=e.y+t.clientTop}else i&&l()}a&&!r&&i&&l();let u=i&&!r&&!a?nt(i,s):P(0);return{x:o.left+s.scrollLeft-c.x-u.x,y:o.top+s.scrollTop-c.y-u.y,width:o.width,height:o.height}}function ht(e){return q(e).position===`static`}function gt(e,t){if(!U(e)||q(e).position===`fixed`)return null;if(t)return t(e);let n=e.offsetParent;return V(e)===n&&(n=n.ownerDocument.body),n}function _t(e,t){let n=B(e);if(Be(e))return n;if(!U(e)){let t=J(e);for(;t&&!K(t);){if(H(t)&&!ht(t))return t;t=J(t)}return n}let r=gt(e,t);for(;r&&ze(r)&&ht(r);)r=gt(r,t);return r&&K(r)&&ht(r)&&!We(r)?n:r||Ge(e)||n}var vt=async function(e){let t=this.getOffsetParent||_t,n=this.getDimensions,r=await n(e.floating);return{reference:mt(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function yt(e){return q(e).direction===`rtl`}var bt={convertOffsetParentRelativeRectToViewportRelativeRect:rt,getDocumentElement:V,getClippingRect:ft,getOffsetParent:_t,getElementRects:vt,getClientRects:it,getDimensions:pt,getScale:X,isElement:H,isRTL:yt};function xt(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function St(e,t){let n=null,r,i=V(e);function a(){var e;clearTimeout(r),(e=n)==null||e.disconnect(),n=null}function o(s,c){s===void 0&&(s=!1),c===void 0&&(c=1),a();let l=e.getBoundingClientRect(),{left:u,top:d,width:f,height:p}=l;if(s||t(),!f||!p)return;let m=oe(d),h=oe(i.clientWidth-(u+f)),g=oe(i.clientHeight-(d+p)),_=oe(u),v={rootMargin:-m+`px `+-h+`px `+-g+`px `+-_+`px`,threshold:N(0,M(1,c))||1},y=!0;function b(t){let n=t[0].intersectionRatio;if(n!==c){if(!y)return o();n?o(!1,n):r=setTimeout(()=>{o(!1,1e-7)},1e3)}n===1&&!xt(l,e.getBoundingClientRect())&&o(),y=!1}try{n=new IntersectionObserver(b,{...v,root:i.ownerDocument})}catch{n=new IntersectionObserver(b,v)}n.observe(e)}return o(!0),a}function Ct(e,t,n,r){r===void 0&&(r={});let{ancestorScroll:i=!0,ancestorResize:a=!0,elementResize:o=typeof ResizeObserver==`function`,layoutShift:s=typeof IntersectionObserver==`function`,animationFrame:c=!1}=r,l=Ze(e),u=i||a?[...l?Y(l):[],...t?Y(t):[]]:[];u.forEach(e=>{i&&e.addEventListener(`scroll`,n,{passive:!0}),a&&e.addEventListener(`resize`,n)});let d=l&&s?St(l,n):null,f=-1,p=null;o&&(p=new ResizeObserver(e=>{let[r]=e;r&&r.target===l&&p&&t&&(p.unobserve(t),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var e;(e=p)==null||e.observe(t)})),n()}),l&&!c&&p.observe(l),t&&p.observe(t));let m,h=c?Z(e):null;c&&g();function g(){let t=Z(e);h&&!xt(h,t)&&n(),h=t,m=requestAnimationFrame(g)}return n(),()=>{var e;u.forEach(e=>{i&&e.removeEventListener(`scroll`,n),a&&e.removeEventListener(`resize`,n)}),d?.(),(e=p)==null||e.disconnect(),p=null,c&&cancelAnimationFrame(m)}}var wt=Ne,Tt=Pe,Et=Ae,Dt=Fe,Ot=ke,kt=(e,t,n)=>{let r=new Map,i={platform:bt,...n},a={...i.platform,_c:r};return Oe(e,t,{...i,platform:a})};function At(e){return Mt(e)}function jt(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function Mt(e){for(let t=e;t;t=jt(t))if(t instanceof Element&&getComputedStyle(t).display===`none`)return null;for(let t=jt(e);t;t=jt(t)){if(!(t instanceof Element))continue;let e=getComputedStyle(t);if(e.display!==`contents`&&(e.position!==`static`||We(e)||t.tagName===`BODY`))return t}return null}function Nt(e,t){if(!t)return null;let n=e.getRootNode();if(n instanceof Document||n instanceof ShadowRoot){let e=n.getElementById(t);if(e)return e}return e.ownerDocument.getElementById(t)}var Pt=class extends Event{constructor(){super(`pk-reposition`,{bubbles:!0,cancelable:!1,composed:!0})}},Ft=T`
    @layer pk-component {
        :host {
            display: contents;
        }

        .popup {
            position: absolute;
            isolation: isolate;
            width: max-content;
            z-index: var(--pk-popup-z-index, 1000);
            /* Never transition coordinates — flip would animate the jump. */
            transition: none;

            /* Reset UA styles for [popover] — see  pk-popup. */
            inset: unset;
            padding: unset;
            margin: unset;
            height: unset;
            color: unset;
            background: unset;
            border: unset;
            overflow: unset;
        }

        .popup-fixed {
            position: fixed;
        }

        .popup:not(.active) {
            display: none;
        }

        /* Prefer visibility over opacity so enter animations are not fighting a
         * 0→1 fade. Matches base-ui isPositioned / hide-until-placed.
         */
        .popup.active:not(.positioned) {
            visibility: hidden;
            pointer-events: none;
        }

        .popup.show {
            animation: pk-popup-surface-in 100ms ease-out;
        }

        .popup.hide {
            animation: pk-popup-surface-out 100ms ease-in forwards;
        }

        @keyframes pk-popup-surface-in {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pk-popup-surface-out {
            from {
                opacity: 1;
                transform: scale(1);
            }

            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }

        .arrow {
            position: absolute;
            width: var(--pk-popup-arrow-size, 6px);
            height: var(--pk-popup-arrow-size, 6px);
            rotate: 45deg;
            background: var(--pk-popup-arrow-color, var(--pk-color-white));
            z-index: 1;
        }

        .hover-bridge {
            position: fixed;
            z-index: calc(var(--pk-popup-z-index, 1000) - 1);
            inset: 0;
            clip-path: polygon(
                var(--pk-hover-bridge-top-left-x, 0) var(--pk-hover-bridge-top-left-y, 0),
                var(--pk-hover-bridge-top-right-x, 0) var(--pk-hover-bridge-top-right-y, 0),
                var(--pk-hover-bridge-bottom-right-x, 0) var(--pk-hover-bridge-bottom-right-y, 0),
                var(--pk-hover-bridge-bottom-left-x, 0) var(--pk-hover-bridge-bottom-left-y, 0)
            );
            pointer-events: auto;
        }

        .hover-bridge:not(.hover-bridge-visible) {
            display: none;
        }
    }
`;function It(e){return typeof e==`object`&&!!e&&`getBoundingClientRect`in e}function Lt(e){return e||(l?`absolute`:`fixed`)}function Rt(e,t){if(l&&!It(e)&&t===`scroll`)return Y(e).filter(e=>e instanceof Element)}var Q=class extends g{constructor(...e){super(...e),this.anchor=``,this.active=!1,this.boundary=`viewport`,this.placement=`bottom-start`,this.distance=4,this.skidding=0,this.flip=!0,this.flipFallbackPlacements=``,this.flipFallbackStrategy=`best-fit`,this.flipPadding=8,this.shift=!0,this.shiftPadding=8,this.arrow=!1,this.arrowPlacement=`anchor`,this.arrowPadding=10,this.autoSizePadding=8,this.anchorTracking=!0,this.hoverBridge=!1,this.anchorElement=null,this.settlingInitialPosition=!1,this.settleGeneration=0}static{this.styles=Ft}disconnectedCallback(){this.stop(),super.disconnectedCallback()}updated(e){super.updated(e),e.has(`active`)&&(this.active?(this.resolveAnchor(),this.start()):this.stop()),e.has(`anchor`)&&this.handleAnchorChange(),this.active&&!e.has(`active`)&&this.reposition()}reposition(){this.settlingInitialPosition||this.repositionAsync()}async repositionAsync(e=!0){let t=this.popupElement,n=this.arrow?this.arrowElement:null;if(!this.active||!this.anchorElement||!t)return!1;let r=Rt(this.anchorElement,this.boundary),i=[wt({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?i.push(Dt({apply:({rects:e})=>{let n=this.sync===`width`||this.sync===`both`,r=this.sync===`height`||this.sync===`both`;t.style.width=n?`${e.reference.width}px`:``,t.style.height=r?`${e.reference.height}px`:``}})):(t.style.width=``,t.style.height=``),this.flip&&i.push(Et({boundary:r,fallbackPlacements:this.flipFallbackPlacements?this.flipFallbackPlacements.split(` `).map(e=>e.trim()).filter(Boolean):void 0,fallbackStrategy:this.flipFallbackStrategy===`best-fit`?`bestFit`:`initialPlacement`,padding:this.flipPadding})),this.shift&&i.push(Tt({boundary:r,padding:this.shiftPadding})),this.autoSize?i.push(Dt({boundary:r,padding:this.autoSizePadding,apply:({availableHeight:e,availableWidth:n})=>{let r=this.autoSize===`horizontal`||this.autoSize===`both`,i=this.autoSize===`vertical`||this.autoSize===`both`;r?t.style.setProperty(`--pk-popup-available-width`,`${Math.max(0,Math.floor(n))}px`):t.style.removeProperty(`--pk-popup-available-width`),i?t.style.setProperty(`--pk-popup-available-height`,`${Math.max(0,Math.floor(e))}px`):t.style.removeProperty(`--pk-popup-available-height`)}})):(t.style.removeProperty(`--pk-popup-available-width`),t.style.removeProperty(`--pk-popup-available-height`)),this.arrow&&n&&i.push(Ot({element:n,padding:this.arrowPadding}));let a=Lt(this.positionMethod),o=a===`fixed`;t.classList.toggle(`popup-fixed`,o);let s=l?e=>bt.getOffsetParent(e,At):bt.getOffsetParent,{x:c,y:u,middlewareData:d,placement:f}=await kt(this.anchorElement,t,{placement:this.placement,middleware:i,strategy:a,platform:{...bt,getOffsetParent:s}});if(!this.active||!t.isConnected)return!1;let p={top:`bottom`,right:`left`,bottom:`top`,left:`right`}[f.split(`-`)[0]];if(this.setAttribute(`data-current-placement`,f),Object.assign(t.style,{left:`${c}px`,top:`${u}px`,...o?{position:`fixed`}:{position:``}}),this.anchorElement){let e=this.anchorElement.getBoundingClientRect(),n=t.getBoundingClientRect();t.style.setProperty(`--pk-anchor-width`,`${e.width}px`),t.style.setProperty(`--pk-anchor-height`,`${e.height}px`);let r=ne(f,e,n,this.distance,d.shift);t.style.setProperty(`--pk-transform-origin`,r)}if(this.arrow&&n){let e=d.arrow?.x,t=d.arrow?.y,r=``,i=``,a=``,o=``;if(this.arrowPlacement===`start`){let n=typeof e==`number`?`${this.arrowPadding}px`:``;r=typeof t==`number`?`${this.arrowPadding}px`:``,o=n}else this.arrowPlacement===`end`?(i=typeof e==`number`?`${this.arrowPadding}px`:``,a=typeof t==`number`?`${this.arrowPadding}px`:``):this.arrowPlacement===`center`?(o=typeof e==`number`?`50%`:``,r=typeof t==`number`?`50%`:``):(o=typeof e==`number`?`${e}px`:``,r=typeof t==`number`?`${t}px`:``);Object.assign(n.style,{top:r,right:i,bottom:a,left:o,transform:``,[p]:`calc(-1 * var(--pk-popup-arrow-size, 6px) / 2)`})}return requestAnimationFrame(()=>this.updateHoverBridge()),e&&this.dispatchEvent(new Pt),!0}frames(e){return new Promise(t=>{let n=e=>{if(e<=0){t();return}requestAnimationFrame(()=>n(e-1))};n(e)})}async settleInitialPosition(){let e=++this.settleGeneration,t=this.popupElement;if(!t){this.settlingInitialPosition=!1;return}await this.frames(2),this.active&&e===this.settleGeneration&&(await this.repositionAsync(!1),t.offsetHeight,await this.frames(1),this.active&&e===this.settleGeneration&&(await this.repositionAsync(!1),this.active&&e===this.settleGeneration&&(t.classList.add(`positioned`),this.settlingInitialPosition=!1,requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new Pt))))}resolveAnchor(){if(typeof this.anchor==`string`&&this.anchor){this.anchorElement=Nt(this,this.anchor);return}if(this.anchor instanceof Element||It(this.anchor)){this.anchorElement=this.anchor;return}let e=this.querySelector(`[slot="anchor"]`);e instanceof HTMLSlotElement&&(e=e.assignedElements({flatten:!0})[0]??null),this.anchorElement=e}async handleAnchorChange(){await this.stop(),this.resolveAnchor(),this.anchorElement&&this.active&&this.start()}usesPopoverTopLayer(){return l&&this.positionMethod!==`fixed`}stop(){return new Promise(e=>{let t=this.popupElement;this.settleGeneration+=1,this.settlingInitialPosition=!1,t?.classList.remove(`positioned`),this.usesPopoverTopLayer()&&t?.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,t?.style.removeProperty(`--pk-transform-origin`),requestAnimationFrame(()=>e())):e(),this.removeAttribute(`data-current-placement`)})}releasePositioning(){this.cleanup&&=(this.cleanup(),void 0)}async awaitHidden(){await this.stop()}start(){this.anchorElement&&this.active&&this.isConnected&&this.popupElement&&(this.popupElement.classList.remove(`positioned`),this.settlingInitialPosition=!0,this.usesPopoverTopLayer()&&this.popupElement.showPopover?.(),this.anchorTracking&&(this.cleanup=Ct(this.anchorElement,this.popupElement,()=>{this.settlingInitialPosition||this.reposition()})),this.settleInitialPosition())}getContentElement(){let e=((this.shadowRoot?.querySelector(`slot:not([name])`))?.assignedElements({flatten:!0})??[]).find(e=>e instanceof HTMLElement);if(e)return e;for(let e of this.childNodes)if(e instanceof HTMLElement&&e.getAttribute(`slot`)!==`anchor`)return e;return null}updateHoverBridge(){let e=this.popupElement;if(!this.hoverBridge||!this.anchorElement||!e)return;let t=this.anchorElement.getBoundingClientRect(),n=e.getBoundingClientRect(),r=this.placement.includes(`top`)||this.placement.includes(`bottom`),i=0,a=0,o=0,s=0,c=0,l=0,u=0,d=0;r?t.top<n.top?(i=t.left,a=t.bottom,o=t.right,s=t.bottom,c=n.left,l=n.top,u=n.right,d=n.top):(i=n.left,a=n.bottom,o=n.right,s=n.bottom,c=t.left,l=t.top,u=t.right,d=t.top):t.left<n.left?(i=t.right,a=t.top,o=n.left,s=n.top,c=t.right,l=t.bottom,u=n.left,d=n.bottom):(i=n.right,a=n.top,o=t.left,s=t.top,c=n.right,l=n.bottom,u=t.left,d=t.bottom),this.style.setProperty(`--pk-hover-bridge-top-left-x`,`${i}px`),this.style.setProperty(`--pk-hover-bridge-top-left-y`,`${a}px`),this.style.setProperty(`--pk-hover-bridge-top-right-x`,`${o}px`),this.style.setProperty(`--pk-hover-bridge-top-right-y`,`${s}px`),this.style.setProperty(`--pk-hover-bridge-bottom-left-x`,`${c}px`),this.style.setProperty(`--pk-hover-bridge-bottom-left-y`,`${l}px`),this.style.setProperty(`--pk-hover-bridge-bottom-right-x`,`${u}px`),this.style.setProperty(`--pk-hover-bridge-bottom-right-y`,`${d}px`)}render(){let e=!l||this.positionMethod===`fixed`,t=this.usesPopoverTopLayer();return x`
            <slot name="anchor" @slotchange=${()=>{this.handleAnchorChange()}}></slot>
            ${this.hoverBridge?x`
                <div
                    part="hover-bridge"
                    class=${S({"hover-bridge":!0,"hover-bridge-visible":this.active})}
                    aria-hidden="true"
                ></div>
            `:b}
            <div
                popover=${t?`manual`:b}
                part="popup"
                class=${S({popup:!0,active:this.active,"popup-fixed":e})}
            >
                ${this.arrow?x`<div part="arrow" class="arrow"></div>`:b}
                <slot></slot>
            </div>
        `}};_([C()],Q.prototype,`anchor`,void 0),_([C({type:Boolean,reflect:!0})],Q.prototype,`active`,void 0),_([C({attribute:`position-method`})],Q.prototype,`positionMethod`,void 0),_([C({reflect:!0})],Q.prototype,`boundary`,void 0),_([C({reflect:!0})],Q.prototype,`placement`,void 0),_([C({type:Number})],Q.prototype,`distance`,void 0),_([C({type:Number})],Q.prototype,`skidding`,void 0),_([C({type:Boolean})],Q.prototype,`flip`,void 0),_([C({attribute:`flip-fallback-placements`})],Q.prototype,`flipFallbackPlacements`,void 0),_([C({attribute:`flip-fallback-strategy`})],Q.prototype,`flipFallbackStrategy`,void 0),_([C({attribute:`flip-padding`,type:Number})],Q.prototype,`flipPadding`,void 0),_([C({type:Boolean})],Q.prototype,`shift`,void 0),_([C({attribute:`shift-padding`,type:Number})],Q.prototype,`shiftPadding`,void 0),_([C({type:Boolean})],Q.prototype,`arrow`,void 0),_([C({attribute:`arrow-placement`})],Q.prototype,`arrowPlacement`,void 0),_([C({attribute:`arrow-padding`,type:Number})],Q.prototype,`arrowPadding`,void 0),_([C()],Q.prototype,`sync`,void 0),_([C({attribute:`auto-size`})],Q.prototype,`autoSize`,void 0),_([C({attribute:`auto-size-padding`,type:Number})],Q.prototype,`autoSizePadding`,void 0),_([C({attribute:`anchor-tracking`,type:Boolean})],Q.prototype,`anchorTracking`,void 0),_([C({attribute:`hover-bridge`,type:Boolean})],Q.prototype,`hoverBridge`,void 0),_([D(`.popup`)],Q.prototype,`popupElement`,void 0),_([D(`.arrow`)],Q.prototype,`arrowElement`,void 0),Q=_([h(`pk-popup`)],Q);var zt=T`
    @layer pk-component {
        .pk-popup-content {
            transform-origin: var(--pk-transform-origin, top);
        }

        .pk-popup-content[data-open] {
            animation: pk-popup-content-in 100ms ease-out;
        }

        .pk-popup-content[data-open][data-side='bottom'] {
            animation-name: pk-popup-content-in-bottom;
        }

        .pk-popup-content[data-open][data-side='top'] {
            animation-name: pk-popup-content-in-top;
        }

        .pk-popup-content[data-open][data-side='left'] {
            animation-name: pk-popup-content-in-left;
        }

        .pk-popup-content[data-open][data-side='right'] {
            animation-name: pk-popup-content-in-right;
        }

        /* Exit: fade + zoom only — matches tw-animate animate-out / tooltip motion. */
        .pk-popup-content.closing {
            animation: pk-popup-content-out 100ms ease-in forwards;
        }
    }

    @keyframes pk-popup-content-in {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes pk-popup-content-out {
        from {
            opacity: 1;
            transform: scale(1);
        }

        to {
            opacity: 0;
            transform: scale(0.95);
        }
    }

    @keyframes pk-popup-content-in-bottom {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(-0.5rem);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    @keyframes pk-popup-content-in-top {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(0.5rem);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    @keyframes pk-popup-content-in-left {
        from {
            opacity: 0;
            transform: scale(0.95) translateX(0.5rem);
        }

        to {
            opacity: 1;
            transform: scale(1) translateX(0);
        }
    }

    @keyframes pk-popup-content-in-right {
        from {
            opacity: 0;
            transform: scale(0.95) translateX(-0.5rem);
        }

        to {
            opacity: 1;
            transform: scale(1) translateX(0);
        }
    }
`,Bt=n(),Vt=class extends Event{constructor(){super(`pk-clear`,{bubbles:!0,cancelable:!1,composed:!0})}},Ht=new Set([`button`,`submit`,`reset`,`checkbox`,`radio`,`file`,`image`,`hidden`]),Ut=`pk-implicit-submit`,Wt=(e,t)=>{if(e.key!==`Enter`||e.defaultPrevented||e.isComposing||e.altKey||e.ctrlKey||e.metaKey||e.shiftKey)return!1;let n=(t||`text`).toLowerCase();return!Ht.has(n)},Gt=e=>{let t=e.closest?.(`pk-dialog`);if(t){let e=t.querySelector(`form`);if(e)return e}let n=e.form;return n&&n.id===`main`?e.closest?.(`form`)===n?null:e.closest(`form`):n},Kt=(e,t,n)=>{if(e.disabled||e.readonly||!Wt(t,n))return!1;let r=Gt(e);return!r||r.id===`main`?!1:(t.preventDefault(),t.stopPropagation(),r.dispatchEvent(new CustomEvent(Ut,{bubbles:!1,cancelable:!0})),!0)},qt=T`
    @layer pk-component {
        :host {
            display: block;
            width: 100%;
            font-family: var(--pk-font-family);
            font-size: var(--pk-font-size-base);
            line-height: var(--pk-line-height);
            /*
             * Control chrome tokens — inherit into light-DOM in-control actions
             * (e.g. pk-copy-button[slot=end]) the same way combobox/image-browser
             * size their trailing clear/expand hit targets.
             */
            --pk-input-padding-block: 6px;
            --pk-input-padding-inline: 8px;
            --pk-input-control-gap: 6px;
            --pk-input-decoration-size: 0.75rem;
        }

        :host([data-pk-group-orientation]) {
            display: flex;
            flex-direction: column;
            width: auto;
            flex: 0 1 auto;
            align-self: stretch;
        }

        :host([data-pk-group-orientation]) .form-control {
            gap: 0;
            height: 100%;
        }

        :host([data-pk-group-orientation]) .form-control__input {
            min-height: var(--pk-btn-height-default);
            height: 100%;
        }

        :host([data-pk-group-orientation]) .form-control__start,
        :host([data-pk-group-orientation]) .form-control__end {
            display: none;
        }

        :host([data-pk-group-orientation]) .form-control__input {
            width: 100%;
        }

        :host([data-pk-group-orientation]) .input {
            width: 100%;
        }

        :host([data-pk-group-orientation]) .input {
            min-height: var(--pk-btn-height-default);
            height: 100%;
        }

        :host([data-pk-group-orientation='vertical']) {
            width: 100%;
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider])) {
            margin-inline-start: var(--pk-bg-horizontal-indent-outlined, 0);
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider])) {
            margin-block-start: var(--pk-bg-vertical-indent-outlined, 0);
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-divider][data-pk-group-join]) {
            margin-inline-start: var(--pk-bg-horizontal-indent-outlined, 0);
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-divider][data-pk-group-join]) {
            margin-block-start: var(--pk-bg-vertical-indent-outlined, 0);
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-divider]) .form-control__input {
            border-left-width: 1px;
            border-left-style: solid;
            border-left-color: var(--pk-btn-group-divider-color-outline, var(--pk-input-border-color));
            box-shadow: none;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-divider]) .form-control__input {
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: var(--pk-btn-group-divider-color-outline, var(--pk-input-border-color));
            box-shadow: none;
        }

        :host([data-pk-group-divider]) .form-control__input:focus-within,
        :host([data-pk-group-divider][data-state='focus-visible']) .form-control__input {
            box-shadow: var(--pk-input-focus-shadow);
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-divider]) .form-control__input:focus-within,
        :host([data-pk-group-orientation='vertical'][data-pk-group-divider][data-state='focus-visible']) .form-control__input {
            box-shadow: var(--pk-input-focus-shadow);
        }

        /* Chrome lives on the flex shell (part=base) so slot=start/end adornments sit
         * inside the border — same visual contract as pk-input-group / v1 InputGroup.
         * Height is content-sized (v1): padding-block + --pk-input-control-line-height + border.
         * Trailing actions (clear, pk-copy-button[slot=end]) stay in flex flow so long
         * values never paint under the button — mirror combobox / image-browser.
         */
        .form-control__input {
            align-items: center;
            gap: var(--pk-input-control-gap);
            padding-inline: var(--pk-input-padding-inline);
            border: var(--pk-input-border);
            border-radius: var(--pk-input-border-radius, var(--pk-radius-sm));
            background: var(--pk-input-bg);
            background-clip: padding-box;
            box-sizing: border-box;
            transition: border-color 0.12s ease, box-shadow 0.12s ease;
        }

        .form-control__start,
        .form-control__end {
            margin: 0;
            color: var(--pk-color-gray-400);
            line-height: 0;
            align-self: stretch;
            align-items: center;
        }

        /* Decorative glyphs only — interactive in-control actions opt out below. */
        .form-control__start ::slotted(*),
        .form-control__end ::slotted(*) {
            display: block;
            max-width: 1.25rem;
            max-height: 1.25rem;
        }

        /* Copy (and similar) inside the field: flex-reserved space, not absolute overlay. */
        .form-control__end:has(::slotted(pk-copy-button)) {
            align-items: stretch;
        }

        .form-control__end ::slotted(pk-copy-button) {
            display: inline-flex;
            align-self: stretch;
            align-items: stretch;
            max-width: none;
            max-height: none;
            /*
             * Pull into trailing padding like combobox expand/clear, but leave a
             * small inset so the glyph is not tight against the field border.
             */
            margin-inline-end: calc(-1 * var(--pk-input-padding-inline) + 4px);
            margin-block: calc(-1 * var(--pk-input-padding-block));
        }

        .input {
            display: block;
            width: 100%;
            margin: 0;
            /* v1 Input default: py-1.5 + text-sm (14px / 1.25rem lh) → 34px with border. */
            padding-block: var(--pk-input-padding-block);
            padding-inline: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            /* Craft CP body / field value text. */
            color: var(--pk-color-gray-700);
            font: inherit;
            line-height: var(--pk-input-control-line-height, 1.25rem);
            appearance: none;
            box-sizing: border-box;
            outline: none;
        }

        .form-control__input .input {
            flex: 1 1 auto;
            min-width: 0;
        }

        .input::placeholder {
            color: var(--pk-input-placeholder-color, var(--pk-color-gray-400));
        }

        /*
         * Craft text:focus-visible only sets box-shadow (--focus-ring); resting border stays.
         * Do not also set border-color — --pk-input-focus-shadow already includes 0 0 0 1px,
         * so border-color + that ring reads as a double focus treatment.
         */
        :host(:not([invalid]):not(:state(user-invalid))) .form-control__input:focus-within,
        :host([data-state='focus-visible']:not([invalid]):not(:state(user-invalid))) .form-control__input {
            box-shadow: var(--pk-input-focus-shadow);
        }

        .form-control__input:has(.input:disabled) {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .input:disabled {
            cursor: not-allowed;
        }

        :host([invalid]) .form-control__input,
        :host(:state(user-invalid)) .form-control__input {
            border-color: var(--pk-color-rose-600);
        }

        /* Invalid + focus: rose ring (same token as select/combobox), not sky over rose border. */
        :host([invalid]) .form-control__input:focus-within,
        :host([invalid][data-state='focus-visible']) .form-control__input,
        :host(:state(user-invalid)) .form-control__input:focus-within {
            box-shadow: var(--pk-input-invalid-focus-shadow);
        }

        :host([size='xs']) {
            --pk-input-padding-block: 4px;
            --pk-input-padding-inline: 6px;
            --pk-input-control-gap: 4px;
            --pk-input-decoration-size: 0.625rem;
        }

        :host([size='xs']) .input {
            font-size: 11px;
        }

        :host([size='sm']) {
            --pk-input-padding-block: 4px;
            --pk-input-padding-inline: 8px;
            --pk-input-control-gap: 4px;
            --pk-input-decoration-size: 0.6875rem;
        }

        :host([size='sm']) .input {
            font-size: 12px;
        }

        :host([size='lg']) {
            --pk-input-padding-block: 8px;
            --pk-input-padding-inline: 12px;
            --pk-input-control-gap: 8px;
            --pk-input-decoration-size: 0.875rem;
        }

        :host([size='lg']) .input {
            font-size: var(--pk-font-size-base);
        }

        :host([size='xl']) {
            --pk-input-padding-block: 10px;
            --pk-input-padding-inline: 16px;
            --pk-input-control-gap: 8px;
            --pk-input-decoration-size: 1rem;
        }

        :host([size='xl']) .input {
            font-size: 16px;
        }

        /*
         * Mono face + 0.9× optical size + line-height 1.5. The taller line-height
         * offsets the smaller face so padding + content height stays aligned with
         * stock inputs (1.25rem ≈ 1.5 × 12.6px). Scale the size's face, not
         * the parent em, so xs/sm/xl mono stay proportional.
         */
        :host([mono]) .input {
            font-family: var(--pk-input-mono-font-family);
            font-size: calc(var(--pk-font-size-base) * 0.9);
            line-height: var(--pk-input-mono-line-height, 1.5);
        }

        :host([mono][size='xs']) .input {
            font-size: calc(11px * 0.9);
        }

        :host([mono][size='sm']) .input {
            font-size: calc(12px * 0.9);
        }

        :host([mono][size='lg']) .input {
            font-size: calc(var(--pk-font-size-base) * 0.9);
        }

        :host([mono][size='xl']) .input {
            font-size: calc(16px * 0.9);
        }

        /* Editable-table cells (v1): flush into the row — no chrome border/radius.
         * Prefer reflected fit-cell (Lit property); data-editable-table-input is a legacy alias.
         * Fill host → form-control → input so the control spans the full td.
         */
        :host([fit-cell]),
        :host([data-editable-table-input]) {
            display: block;
            height: 100%;
            min-height: 100%;
            box-sizing: border-box;
        }

        :host([fit-cell]) .form-control,
        :host([data-editable-table-input]) .form-control {
            height: 100%;
            min-height: 100%;
            gap: 0;
        }

        :host([fit-cell]) .form-control__input,
        :host([data-editable-table-input]) .form-control__input {
            height: 100%;
            min-height: 100%;
            flex: 1 1 auto;
            padding-inline: 0;
            border: none;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        :host([fit-cell]) .input,
        :host([data-editable-table-input]) .input {
            height: 100%;
            min-height: 100%;
        }

        :host([fit-cell]:not([invalid]):not(:state(user-invalid))) .form-control__input:focus-within,
        :host([fit-cell][data-state='focus-visible']:not([invalid]):not(:state(user-invalid))) .form-control__input,
        :host([data-editable-table-input]:not([invalid]):not(:state(user-invalid))) .form-control__input:focus-within,
        :host([data-editable-table-input][data-state='focus-visible']:not([invalid]):not(:state(user-invalid))) .form-control__input {
            border: none;
            box-shadow: inset 0 0 0 1px var(--pk-color-gray-200);
        }

        :host([fit-cell][invalid]) .form-control__input,
        :host([fit-cell]:state(user-invalid)) .form-control__input,
        :host([data-editable-table-input][invalid]) .form-control__input,
        :host([data-editable-table-input]:state(user-invalid)) .form-control__input {
            border: none;
            box-shadow: inset 0 0 0 1px var(--pk-color-rose-600);
        }

        :host([fit-cell][invalid]) .form-control__input:focus-within,
        :host([fit-cell][invalid][data-state='focus-visible']) .form-control__input,
        :host([fit-cell]:state(user-invalid)) .form-control__input:focus-within,
        :host([data-editable-table-input][invalid]) .form-control__input:focus-within,
        :host([data-editable-table-input][invalid][data-state='focus-visible']) .form-control__input,
        :host([data-editable-table-input]:state(user-invalid)) .form-control__input:focus-within {
            border: none;
            box-shadow: inset 0 0 0 1px var(--pk-color-rose-600);
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-join]:not([data-pk-group-divider])) .form-control__input {
            border-left-width: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-join]:not([data-pk-group-divider])) .form-control__input {
            border-top-width: 0;
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-divider]) .form-control__input {
            border-left-width: 1px;
            border-left-style: solid;
            border-left-color: var(--pk-btn-group-divider-color-outline, var(--pk-input-border-color));
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-divider]) .form-control__input {
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: var(--pk-btn-group-divider-color-outline, var(--pk-input-border-color));
        }

        :host([data-pk-group-orientation='horizontal'][data-pk-group-internal-trail]) .form-control__input {
            border-right-width: 0;
        }

        :host([data-pk-group-orientation='vertical'][data-pk-group-internal-trail]) .form-control__input {
            border-bottom-width: 0;
        }

        /*
         * Clear is a flex trailing action (not absolute). Reserves width in the
         * control so values cannot scroll under the glyph — same contract as
         * combobox clear/expand and image-browser clear.
         */
        .clear-button {
            display: inline-flex;
            flex-shrink: 0;
            align-self: stretch;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            width: calc(var(--pk-input-decoration-size) + var(--pk-input-padding-inline));
            margin-block: calc(-1 * var(--pk-input-padding-block));
            /* Match pk-copy-button[slot=end]: pull into padding but leave a 4px glyph inset. */
            margin-inline-end: calc(-1 * var(--pk-input-padding-inline) + 4px);
            font-size: var(--pk-input-decoration-size);
            line-height: 1;
        }
    }
`,$=class extends i{constructor(...e){super(...e),this.assumeInteractionOn=[`blur`,`input`],this.hasSlotController=new v(this,`instructions`,`hint`,`label`,`start`,`end`),this.inputId=s(`pk-input`),this.type=`text`,this._value=null,this.defaultValue=null,this.size=`default`,this.label=``,this.instructions=``,this.withClear=!1,this.placeholder=``,this.readonly=!1,this.invalid=!1,this.fitCell=!1,this.mono=!1,this.autofocus=!1,this.withLabel=!1,this.withInstructions=!1}static{this.styles=[d,O(),k(`.input`,`var(--pk-input-border-radius, var(--pk-radius-sm))`),ee(`.input`),qt]}static get validators(){return[...super.validators,c(),f()]}get value(){return this.valueHasChanged?this._value??``:this._value??this.defaultValue??``}set value(e){let t=e??``;this._value!==t&&(this.valueHasChanged=!0,this._value=t)}connectedCallback(){this.instructions=u(this,this.instructions),this.hasAttribute(`with-hint`)&&(this.withInstructions=!0),super.connectedCallback()}syncFormValue(){this.setValue(this.value||``)}resetToDefaultValue(){this.valueHasChanged=!1,this._value=null}restoreFormState(e){typeof e==`string`&&(this.value=e)}formResetCallback(){this.valueHasChanged=!1,this._value=null,this.input&&(this.input.value=this.defaultValue??``),super.formResetCallback()}updated(e){(e.has(`value`)||e.has(`defaultValue`))&&this.setState(`blank`,!this.value),super.updated(e)}syncStandaloneAria(){if(!this.input)return;let e=!!this.label||this.hasSlotController.test(`label`,this.withLabel),t=p((e,t)=>this.hasSlotController.test(e,t),this.instructions,this.withInstructions);a({control:this.input,labelId:`${this.inputId}-label`,instructionsId:`${this.inputId}-instructions`,hasLabel:e,hasInstructions:t,required:this.required,invalid:this.invalid||!this.internals.validity.valid})}hasLabelContent(){return!!this.label||this.hasSlotController.test(`label`,this.withLabel)}hasInstructionsContent(){return p((e,t)=>this.hasSlotController.test(e,t),this.instructions,this.withInstructions)}focus(e){this.input?.focus(e)}blur(){this.input?.blur()}select(){this.input?.select()}handleInput(){this.value=this.input.value,this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0}))}handleChange(e){this.value=this.input.value,e.stopPropagation(),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0}))}handleKeyDown(e){Kt(this,e,this.type)}handleClearClick(e){e.preventDefault(),this.value!==``&&(this.value=``,this.dispatchEvent(new Vt),this.dispatchEvent(new Event(`input`,{bubbles:!0,composed:!0})),this.dispatchEvent(new Event(`change`,{bubbles:!0,composed:!0})),this.input.focus())}render(){let e=this.hasLabelContent(),t=this.hasInstructionsContent(),n=this.withClear&&!this.disabled&&!this.readonly&&this.value.length>0,r=this.hasSlotController.test(`start`),i=this.hasSlotController.test(`end`);return x`
            <div part="form-control" class="form-control">
                ${e||t?x`
                        <div part="header" class="form-control__header">
                            ${e?x`
                                    <label
                                        part="label"
                                        class="form-control__label"
                                        id=${`${this.inputId}-label`}
                                        for=${`${this.inputId}-control`}
                                    >
                                        <slot name="label">${this.label}</slot>
                                    </label>
                                `:b}

                            ${t?x`
                                    <p
                                        part="instructions"
                                        class="form-control__instructions"
                                        id=${`${this.inputId}-instructions`}
                                    >
                                        <slot name="instructions">${this.instructions}</slot>
                                        <slot name="hint"></slot>
                                    </p>
                                `:b}
                        </div>
                    `:b}

                <div part="base" class="form-control__input">
                    ${r?x`
                            <span part="start" class="form-control__start">
                                <slot name="start"></slot>
                            </span>
                        `:x`<slot name="start" hidden></slot>`}

                    <input
                        part="input"
                        class="input"
                        id=${e?`${this.inputId}-control`:b}
                        type=${this.type}
                        .value=${w(this.value)}
                        placeholder=${this.placeholder||b}
                        pattern=${E(this.pattern)}
                        minlength=${E(this.minlength)}
                        maxlength=${E(this.maxlength)}
                        min=${E(this.min)}
                        max=${E(this.max)}
                        step=${E(this.step)}
                        autocomplete=${E(this.autocomplete)}
                        ?disabled=${this.disabled}
                        ?readonly=${this.readonly}
                        ?required=${this.required}
                        ?autofocus=${this.autofocus}
                        @input=${this.handleInput}
                        @change=${this.handleChange}
                        @keydown=${this.handleKeyDown}
                        @focus=${()=>this.dispatchEvent(new Event(`focus`,{bubbles:!0,composed:!0}))}
                        @blur=${()=>this.dispatchEvent(new Event(`blur`,{bubbles:!0,composed:!0}))}
                    />

                    ${n?x`
                            <button
                                part="clear-button"
                                class="icon-button clear-button"
                                type="button"
                                tabindex="-1"
                                aria-label="Clear"
                                @click=${this.handleClearClick}
                            >
                                <slot name="clear-icon">×</slot>
                            </button>
                        `:b}

                    ${i?x`
                            <span part="end" class="form-control__end">
                                <slot name="end"></slot>
                            </span>
                        `:x`<slot name="end" hidden></slot>`}
                </div>
            </div>
        `}};_([D(`input`)],$.prototype,`input`,void 0),_([C({reflect:!0})],$.prototype,`type`,void 0),_([y()],$.prototype,`value`,null),_([C({attribute:`value`,reflect:!0})],$.prototype,`defaultValue`,void 0),_([C({reflect:!0})],$.prototype,`size`,void 0),_([C()],$.prototype,`label`,void 0),_([C()],$.prototype,`instructions`,void 0),_([C({attribute:`with-clear`,type:Boolean})],$.prototype,`withClear`,void 0),_([C()],$.prototype,`placeholder`,void 0),_([C({type:Boolean,reflect:!0})],$.prototype,`readonly`,void 0),_([C({type:Boolean,reflect:!0})],$.prototype,`invalid`,void 0),_([C({type:Boolean,reflect:!0,attribute:`fit-cell`})],$.prototype,`fitCell`,void 0),_([C({type:Boolean,reflect:!0})],$.prototype,`mono`,void 0),_([C()],$.prototype,`pattern`,void 0),_([C({type:Number})],$.prototype,`minlength`,void 0),_([C({type:Number})],$.prototype,`maxlength`,void 0),_([C()],$.prototype,`min`,void 0),_([C()],$.prototype,`max`,void 0),_([C()],$.prototype,`step`,void 0),_([C()],$.prototype,`autocomplete`,void 0),_([C({type:Boolean,reflect:!0})],$.prototype,`autofocus`,void 0),_([C({attribute:`with-label`,type:Boolean})],$.prototype,`withLabel`,void 0),_([C({attribute:`with-instructions`,type:Boolean})],$.prototype,`withInstructions`,void 0),$=_([h(`pk-input`)],$);var Jt=o({tagName:`pk-input`,elementClass:$,react:A.default,events:{onInput:`input`,onChange:`input`,onPkClear:`pk-clear`,onFocus:`focus`,onBlur:`blur`}}),Yt=(0,A.forwardRef)(function(e,t){let{disabled:n,readonly:i,invalid:a,fitCell:o,autofocus:s,mono:c,...l}=e;return(0,Bt.jsx)(Jt,{ref:t,...l,...r([`disabled`,`readonly`,`invalid`,`fitCell`,`autofocus`,`mono`],{disabled:n,readonly:i,invalid:a,fitCell:o,autofocus:s,mono:c})})});Yt.displayName=`Input`;export{j as a,te as c,Nt as i,Vt as n,re as o,zt as r,ie as s,Yt as t};