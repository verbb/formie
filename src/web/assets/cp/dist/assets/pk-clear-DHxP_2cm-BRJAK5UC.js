import{T as e}from"./utils-CI5K2xIr.js";import{i as t,n,r}from"./has-slot-DJv86HKx-D2K9y9lH.js";import{d as i,f as a,i as o,l as s,p as c,s as l}from"./lit-C7H9X-yg.js";function u(e){let t=e.split(`-`)[0];return t===`inline-start`?`left`:t===`inline-end`?`right`:t===`top`||t===`bottom`||t===`left`||t===`right`?t:`bottom`}function d(e,t,n,r,i){let a=u(e),o=t.x+t.width/2-n.x,s=t.y+t.height/2-n.y;return Math.abs(i?.y??0)>r&&(a===`top`||a===`bottom`)?`${o}px ${t.y+t.height/2-n.y}px`:{top:`${o}px calc(100% + ${r}px)`,bottom:`${o}px ${-r}px`,left:`calc(100% + ${r}px) ${s}px`,right:`${-r}px ${s}px`}[a]}function f(e,t){if(!t){e.removeAttribute(`data-side`);return}e.setAttribute(`data-side`,u(t))}function p(e,t,n=100,r){let i=()=>e.getAttribute(`data-current-placement`)??t;return!r?.requireEvent&&e.hasAttribute(`data-current-placement`)?Promise.resolve(i()):new Promise(t=>{let a=!1,o=()=>{a||(a=!0,t(i()))};e.addEventListener(`pk-reposition`,o,{once:!0}),r?.requireEvent||requestAnimationFrame(()=>{requestAnimationFrame(()=>{e.hasAttribute(`data-current-placement`)&&o()})}),window.setTimeout(o,n)})}var m=Math.min,h=Math.max,g=Math.round,_=Math.floor,v=e=>({x:e,y:e}),y={left:`right`,right:`left`,bottom:`top`,top:`bottom`};function b(e,t,n){return h(e,m(t,n))}function x(e,t){return typeof e==`function`?e(t):e}function S(e){return e.split(`-`)[0]}function C(e){return e.split(`-`)[1]}function w(e){return e===`x`?`y`:`x`}function T(e){return e===`y`?`height`:`width`}function E(e){let t=e[0];return t===`t`||t===`b`?`y`:`x`}function D(e){return w(E(e))}function O(e,t,n){n===void 0&&(n=!1);let r=C(e),i=D(e),a=T(i),o=i===`x`?r===(n?`end`:`start`)?`right`:`left`:r===`start`?`bottom`:`top`;return t.reference[a]>t.floating[a]&&(o=F(o)),[o,F(o)]}function k(e){let t=F(e);return[A(e),t,A(t)]}function A(e){return e.includes(`start`)?e.replace(`start`,`end`):e.replace(`end`,`start`)}var j=[`left`,`right`],M=[`right`,`left`],ee=[`top`,`bottom`],N=[`bottom`,`top`];function te(e,t,n){switch(e){case`top`:case`bottom`:return n?t?M:j:t?j:M;case`left`:case`right`:return t?ee:N;default:return[]}}function P(e,t,n,r){let i=C(e),a=te(S(e),n===`start`,r);return i&&(a=a.map(e=>e+`-`+i),t&&(a=a.concat(a.map(A)))),a}function F(e){let t=S(e);return y[t]+e.slice(t.length)}function I(e){return{top:0,right:0,bottom:0,left:0,...e}}function ne(e){return typeof e==`number`?{top:e,right:e,bottom:e,left:e}:I(e)}function L(e){let{x:t,y:n,width:r,height:i}=e;return{width:r,height:i,top:n,left:t,right:t+r,bottom:n+i,x:t,y:n}}function re(e,t,n){let{reference:r,floating:i}=e,a=E(t),o=D(t),s=T(o),c=S(t),l=a===`y`,u=r.x+r.width/2-i.width/2,d=r.y+r.height/2-i.height/2,f=r[s]/2-i[s]/2,p;switch(c){case`top`:p={x:u,y:r.y-i.height};break;case`bottom`:p={x:u,y:r.y+r.height};break;case`right`:p={x:r.x+r.width,y:d};break;case`left`:p={x:r.x-i.width,y:d};break;default:p={x:r.x,y:r.y}}switch(C(t)){case`start`:p[o]-=f*(n&&l?-1:1);break;case`end`:p[o]+=f*(n&&l?-1:1)}return p}async function ie(e,t){t===void 0&&(t={});let{x:n,y:r,platform:i,rects:a,elements:o,strategy:s}=e,{boundary:c=`clippingAncestors`,rootBoundary:l=`viewport`,elementContext:u=`floating`,altBoundary:d=!1,padding:f=0}=x(t,e),p=ne(f),m=o[d?u===`floating`?`reference`:`floating`:u],h=L(await i.getClippingRect({element:await(i.isElement==null?void 0:i.isElement(m))??!0?m:m.contextElement||await(i.getDocumentElement==null?void 0:i.getDocumentElement(o.floating)),boundary:c,rootBoundary:l,strategy:s})),g=u===`floating`?{x:n,y:r,width:a.floating.width,height:a.floating.height}:a.reference,_=await(i.getOffsetParent==null?void 0:i.getOffsetParent(o.floating)),v=await(i.isElement==null?void 0:i.isElement(_))&&await(i.getScale==null?void 0:i.getScale(_))||{x:1,y:1},y=L(i.convertOffsetParentRelativeRectToViewportRelativeRect?await i.convertOffsetParentRelativeRectToViewportRelativeRect({elements:o,rect:g,offsetParent:_,strategy:s}):g);return{top:(h.top-y.top+p.top)/v.y,bottom:(y.bottom-h.bottom+p.bottom)/v.y,left:(h.left-y.left+p.left)/v.x,right:(y.right-h.right+p.right)/v.x}}var ae=50,oe=async(e,t,n)=>{let{placement:r=`bottom`,strategy:i=`absolute`,middleware:a=[],platform:o}=n,s=o.detectOverflow?o:{...o,detectOverflow:ie},c=await(o.isRTL==null?void 0:o.isRTL(t)),l=await o.getElementRects({reference:e,floating:t,strategy:i}),{x:u,y:d}=re(l,r,c),f=r,p=0,m={};for(let n=0;n<a.length;n++){let h=a[n];if(!h)continue;let{name:g,fn:_}=h,{x:v,y,data:b,reset:x}=await _({x:u,y:d,initialPlacement:r,placement:f,strategy:i,middlewareData:m,rects:l,platform:s,elements:{reference:e,floating:t}});u=v??u,d=y??d,m[g]={...m[g],...b},x&&p<ae&&(p++,typeof x==`object`&&(x.placement&&(f=x.placement),x.rects&&(l=x.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:i}):x.rects),{x:u,y:d}=re(l,f,c)),n=-1)}return{x:u,y:d,placement:f,strategy:i,middlewareData:m}},se=e=>({name:`arrow`,options:e,async fn(t){let{x:n,y:r,placement:i,rects:a,platform:o,elements:s,middlewareData:c}=t,{element:l,padding:u=0}=x(e,t)||{};if(l==null)return{};let d=ne(u),f={x:n,y:r},p=D(i),h=T(p),g=await o.getDimensions(l),_=p===`y`,v=_?`top`:`left`,y=_?`bottom`:`right`,S=_?`clientHeight`:`clientWidth`,w=a.reference[h]+a.reference[p]-f[p]-a.floating[h],E=f[p]-a.reference[p],O=await(o.getOffsetParent==null?void 0:o.getOffsetParent(l)),k=O?O[S]:0;(!k||!await(o.isElement==null?void 0:o.isElement(O)))&&(k=s.floating[S]||a.floating[h]);let A=w/2-E/2,j=k/2-g[h]/2-1,M=m(d[v],j),ee=m(d[y],j),N=M,te=k-g[h]-ee,P=k/2-g[h]/2+A,F=b(N,P,te),I=!c.arrow&&C(i)!=null&&P!==F&&a.reference[h]/2-(P<N?M:ee)-g[h]/2<0,L=I?P<N?P-N:P-te:0;return{[p]:f[p]+L,data:{[p]:F,centerOffset:P-F-L,...I&&{alignmentOffset:L}},reset:I}}}),ce=function(e){return e===void 0&&(e={}),{name:`flip`,options:e,async fn(t){var n;let{placement:r,middlewareData:i,rects:a,initialPlacement:o,platform:s,elements:c}=t,{mainAxis:l=!0,crossAxis:u=!0,fallbackPlacements:d,fallbackStrategy:f=`bestFit`,fallbackAxisSideDirection:p=`none`,flipAlignment:m=!0,...h}=x(e,t);if((n=i.arrow)!=null&&n.alignmentOffset)return{};let g=S(r),_=E(o),v=S(o)===o,y=await(s.isRTL==null?void 0:s.isRTL(c.floating)),b=d||(v||!m?[F(o)]:k(o)),C=p!==`none`;!d&&C&&b.push(...P(o,m,p,y));let w=[o,...b],T=await s.detectOverflow(t,h),D=[],A=i.flip?.overflows||[];if(l&&D.push(T[g]),u){let e=O(r,a,y);D.push(T[e[0]],T[e[1]])}if(A=[...A,{placement:r,overflows:D}],!D.every(e=>e<=0)){let e=(i.flip?.index||0)+1,t=w[e];if(t&&(u!==`alignment`||_===E(t)||A.every(e=>E(e.placement)!==_||e.overflows[0]>0)))return{data:{index:e,overflows:A},reset:{placement:t}};let n=A.filter(e=>e.overflows[0]<=0).sort((e,t)=>e.overflows[1]-t.overflows[1])[0]?.placement;if(!n)switch(f){case`bestFit`:{let e=A.filter(e=>{if(C){let t=E(e.placement);return t===_||t===`y`}return!0}).map(e=>[e.placement,e.overflows.filter(e=>e>0).reduce((e,t)=>e+t,0)]).sort((e,t)=>e[1]-t[1])[0]?.[0];e&&(n=e);break}case`initialPlacement`:n=o}if(r!==n)return{reset:{placement:n}}}return{}}}},le=new Set([`left`,`top`]);async function ue(e,t){let{placement:n,platform:r,elements:i}=e,a=await(r.isRTL==null?void 0:r.isRTL(i.floating)),o=S(n),s=C(n),c=E(n)===`y`,l=le.has(o)?-1:1,u=a&&c?-1:1,d=x(t,e),{mainAxis:f,crossAxis:p,alignmentAxis:m}=typeof d==`number`?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return s&&typeof m==`number`&&(p=s===`end`?m*-1:m),c?{x:p*u,y:f*l}:{x:f*l,y:p*u}}var de=function(e){return e===void 0&&(e=0),{name:`offset`,options:e,async fn(t){var n;let{x:r,y:i,placement:a,middlewareData:o}=t,s=await ue(t,e);return a===o.offset?.placement&&(n=o.arrow)!=null&&n.alignmentOffset?{}:{x:r+s.x,y:i+s.y,data:{...s,placement:a}}}}},fe=function(e){return e===void 0&&(e={}),{name:`shift`,options:e,async fn(t){let{x:n,y:r,placement:i,platform:a}=t,{mainAxis:o=!0,crossAxis:s=!1,limiter:c={fn:e=>{let{x:t,y:n}=e;return{x:t,y:n}}},...l}=x(e,t),u={x:n,y:r},d=await a.detectOverflow(t,l),f=E(S(i)),p=w(f),m=u[p],h=u[f];if(o){let e=p===`y`?`top`:`left`,t=p===`y`?`bottom`:`right`,n=m+d[e],r=m-d[t];m=b(n,m,r)}if(s){let e=f===`y`?`top`:`left`,t=f===`y`?`bottom`:`right`,n=h+d[e],r=h-d[t];h=b(n,h,r)}let g=c.fn({...t,[p]:m,[f]:h});return{...g,data:{x:g.x-n,y:g.y-r,enabled:{[p]:o,[f]:s}}}}}},pe=function(e){return e===void 0&&(e={}),{name:`size`,options:e,async fn(t){var n,r;let{placement:i,rects:a,platform:o,elements:s}=t,{apply:c=()=>{},...l}=x(e,t),u=await o.detectOverflow(t,l),d=S(i),f=C(i),p=E(i)===`y`,{width:g,height:_}=a.floating,v,y;d===`top`||d===`bottom`?(v=d,y=f===(await(o.isRTL==null?void 0:o.isRTL(s.floating))?`start`:`end`)?`left`:`right`):(y=d,v=f===`end`?`top`:`bottom`);let b=_-u.top-u.bottom,w=g-u.left-u.right,T=m(_-u[v],b),D=m(g-u[y],w),O=!t.middlewareData.shift,k=T,A=D;if((n=t.middlewareData.shift)!=null&&n.enabled.x&&(A=w),(r=t.middlewareData.shift)!=null&&r.enabled.y&&(k=b),O&&!f){let e=h(u.left,0),t=h(u.right,0),n=h(u.top,0),r=h(u.bottom,0);p?A=g-2*(e!==0||t!==0?e+t:h(u.left,u.right)):k=_-2*(n!==0||r!==0?n+r:h(u.top,u.bottom))}await c({...t,availableWidth:A,availableHeight:k});let j=await o.getDimensions(s.floating);return g!==j.width||_!==j.height?{reset:{rects:!0}}:{}}}};function me(){return typeof window<`u`}function R(e){return he(e)?(e.nodeName||``).toLowerCase():`#document`}function z(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function B(e){return((he(e)?e.ownerDocument:e.document)||window.document)?.documentElement}function he(e){return me()?e instanceof Node||e instanceof z(e).Node:!1}function V(e){return me()?e instanceof Element||e instanceof z(e).Element:!1}function H(e){return me()?e instanceof HTMLElement||e instanceof z(e).HTMLElement:!1}function ge(e){return!me()||typeof ShadowRoot>`u`?!1:e instanceof ShadowRoot||e instanceof z(e).ShadowRoot}function U(e){let{overflow:t,overflowX:n,overflowY:r,display:i}=q(e);return/auto|scroll|overlay|hidden|clip/.test(t+r+n)&&i!==`inline`&&i!==`contents`}function _e(e){return/^(table|td|th)$/.test(R(e))}function ve(e){try{if(e.matches(`:popover-open`))return!0}catch{}try{return e.matches(`:modal`)}catch{return!1}}var ye=/transform|translate|scale|rotate|perspective|filter/,be=/paint|layout|strict|content/,W=e=>!!e&&e!==`none`,xe;function G(e){let t=V(e)?q(e):e;return W(t.transform)||W(t.translate)||W(t.scale)||W(t.rotate)||W(t.perspective)||!Ce()&&(W(t.backdropFilter)||W(t.filter))||ye.test(t.willChange||``)||be.test(t.contain||``)}function Se(e){let t=J(e);for(;H(t)&&!K(t);){if(G(t))return t;if(ve(t))return null;t=J(t)}return null}function Ce(){return xe??=typeof CSS<`u`&&CSS.supports&&CSS.supports(`-webkit-backdrop-filter`,`none`),xe}function K(e){return/^(html|body|#document)$/.test(R(e))}function q(e){return z(e).getComputedStyle(e)}function we(e){return V(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function J(e){if(R(e)===`html`)return e;let t=e.assignedSlot||e.parentNode||ge(e)&&e.host||B(e);return ge(t)?t.host:t}function Te(e){let t=J(e);return K(t)?e.ownerDocument?e.ownerDocument.body:e.body:H(t)&&U(t)?t:Te(t)}function Y(e,t,n){t===void 0&&(t=[]),n===void 0&&(n=!0);let r=Te(e),i=r===e.ownerDocument?.body,a=z(r);if(i){let e=Ee(a);return t.concat(a,a.visualViewport||[],U(r)?r:[],e&&n?Y(e):[])}return t.concat(r,Y(r,[],n))}function Ee(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function De(e){let t=q(e),n=parseFloat(t.width)||0,r=parseFloat(t.height)||0,i=H(e),a=i?e.offsetWidth:n,o=i?e.offsetHeight:r,s=g(n)!==a||g(r)!==o;return s&&(n=a,r=o),{width:n,height:r,$:s}}function Oe(e){return V(e)?e:e.contextElement}function X(e){let t=Oe(e);if(!H(t))return v(1);let n=t.getBoundingClientRect(),{width:r,height:i,$:a}=De(t),o=(a?g(n.width):n.width)/r,s=(a?g(n.height):n.height)/i;return(!o||!Number.isFinite(o))&&(o=1),(!s||!Number.isFinite(s))&&(s=1),{x:o,y:s}}var ke=v(0);function Ae(e){let t=z(e);return!Ce()||!t.visualViewport?ke:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function je(e,t,n){return t===void 0&&(t=!1),!n||t&&n!==z(e)?!1:t}function Z(e,t,n,r){t===void 0&&(t=!1),n===void 0&&(n=!1);let i=e.getBoundingClientRect(),a=Oe(e),o=v(1);t&&(r?V(r)&&(o=X(r)):o=X(e));let s=je(a,n,r)?Ae(a):v(0),c=(i.left+s.x)/o.x,l=(i.top+s.y)/o.y,u=i.width/o.x,d=i.height/o.y;if(a){let e=z(a),t=r&&V(r)?z(r):r,n=e,i=Ee(n);for(;i&&r&&t!==n;){let e=X(i),t=i.getBoundingClientRect(),r=q(i),a=t.left+(i.clientLeft+parseFloat(r.paddingLeft))*e.x,o=t.top+(i.clientTop+parseFloat(r.paddingTop))*e.y;c*=e.x,l*=e.y,u*=e.x,d*=e.y,c+=a,l+=o,n=z(i),i=Ee(n)}}return L({width:u,height:d,x:c,y:l})}function Me(e,t){let n=we(e).scrollLeft;return t?t.left+n:Z(B(e)).left+n}function Ne(e,t){let n=e.getBoundingClientRect();return{x:n.left+t.scrollLeft-Me(e,n),y:n.top+t.scrollTop}}function Pe(e){let{elements:t,rect:n,offsetParent:r,strategy:i}=e,a=i===`fixed`,o=B(r),s=t?ve(t.floating):!1;if(r===o||s&&a)return n;let c={scrollLeft:0,scrollTop:0},l=v(1),u=v(0),d=H(r);if((d||!d&&!a)&&((R(r)!==`body`||U(o))&&(c=we(r)),d)){let e=Z(r);l=X(r),u.x=e.x+r.clientLeft,u.y=e.y+r.clientTop}let f=o&&!d&&!a?Ne(o,c):v(0);return{width:n.width*l.x,height:n.height*l.y,x:n.x*l.x-c.scrollLeft*l.x+u.x+f.x,y:n.y*l.y-c.scrollTop*l.y+u.y+f.y}}function Fe(e){return Array.from(e.getClientRects())}function Ie(e){let t=B(e),n=we(e),r=e.ownerDocument.body,i=h(t.scrollWidth,t.clientWidth,r.scrollWidth,r.clientWidth),a=h(t.scrollHeight,t.clientHeight,r.scrollHeight,r.clientHeight),o=-n.scrollLeft+Me(e),s=-n.scrollTop;return q(r).direction===`rtl`&&(o+=h(t.clientWidth,r.clientWidth)-i),{width:i,height:a,x:o,y:s}}var Le=25;function Re(e,t){let n=z(e),r=B(e),i=n.visualViewport,a=r.clientWidth,o=r.clientHeight,s=0,c=0;if(i){a=i.width,o=i.height;let e=Ce();(!e||e&&t===`fixed`)&&(s=i.offsetLeft,c=i.offsetTop)}let l=Me(r);if(l<=0){let e=r.ownerDocument,t=e.body,n=getComputedStyle(t),i=e.compatMode===`CSS1Compat`&&parseFloat(n.marginLeft)+parseFloat(n.marginRight)||0,o=Math.abs(r.clientWidth-t.clientWidth-i);o<=Le&&(a-=o)}else l<=Le&&(a+=l);return{width:a,height:o,x:s,y:c}}function ze(e,t){let n=Z(e,!0,t===`fixed`),r=n.top+e.clientTop,i=n.left+e.clientLeft,a=H(e)?X(e):v(1);return{width:e.clientWidth*a.x,height:e.clientHeight*a.y,x:i*a.x,y:r*a.y}}function Be(e,t,n){let r;if(t===`viewport`)r=Re(e,n);else if(t===`document`)r=Ie(B(e));else if(V(t))r=ze(t,n);else{let n=Ae(e);r={x:t.x-n.x,y:t.y-n.y,width:t.width,height:t.height}}return L(r)}function Ve(e,t){let n=J(e);return n===t||!V(n)||K(n)?!1:q(n).position===`fixed`||Ve(n,t)}function He(e,t){let n=t.get(e);if(n)return n;let r=Y(e,[],!1).filter(e=>V(e)&&R(e)!==`body`),i=null,a=q(e).position===`fixed`,o=a?J(e):e;for(;V(o)&&!K(o);){let t=q(o),n=G(o);!n&&t.position===`fixed`&&(i=null),(a?!n&&!i:!n&&t.position===`static`&&i&&(i.position===`absolute`||i.position===`fixed`)||U(o)&&!n&&Ve(e,o))?r=r.filter(e=>e!==o):i=t,o=J(o)}return t.set(e,r),r}function Ue(e){let{element:t,boundary:n,rootBoundary:r,strategy:i}=e,a=[...n===`clippingAncestors`?ve(t)?[]:He(t,this._c):[].concat(n),r],o=Be(t,a[0],i),s=o.top,c=o.right,l=o.bottom,u=o.left;for(let e=1;e<a.length;e++){let n=Be(t,a[e],i);s=h(n.top,s),c=m(n.right,c),l=m(n.bottom,l),u=h(n.left,u)}return{width:c-u,height:l-s,x:u,y:s}}function We(e){let{width:t,height:n}=De(e);return{width:t,height:n}}function Ge(e,t,n){let r=H(t),i=B(t),a=n===`fixed`,o=Z(e,!0,a,t),s={scrollLeft:0,scrollTop:0},c=v(0);function l(){c.x=Me(i)}if(r||!r&&!a){if((R(t)!==`body`||U(i))&&(s=we(t)),r){let e=Z(t,!0,a,t);c.x=e.x+t.clientLeft,c.y=e.y+t.clientTop}else i&&l()}a&&!r&&i&&l();let u=i&&!r&&!a?Ne(i,s):v(0);return{x:o.left+s.scrollLeft-c.x-u.x,y:o.top+s.scrollTop-c.y-u.y,width:o.width,height:o.height}}function Ke(e){return q(e).position===`static`}function qe(e,t){if(!H(e)||q(e).position===`fixed`)return null;if(t)return t(e);let n=e.offsetParent;return B(e)===n&&(n=n.ownerDocument.body),n}function Je(e,t){let n=z(e);if(ve(e))return n;if(!H(e)){let t=J(e);for(;t&&!K(t);){if(V(t)&&!Ke(t))return t;t=J(t)}return n}let r=qe(e,t);for(;r&&_e(r)&&Ke(r);)r=qe(r,t);return r&&K(r)&&Ke(r)&&!G(r)?n:r||Se(e)||n}var Ye=async function(e){let t=this.getOffsetParent||Je,n=this.getDimensions,r=await n(e.floating);return{reference:Ge(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function Xe(e){return q(e).direction===`rtl`}var Q={convertOffsetParentRelativeRectToViewportRelativeRect:Pe,getDocumentElement:B,getClippingRect:Ue,getOffsetParent:Je,getElementRects:Ye,getClientRects:Fe,getDimensions:We,getScale:X,isElement:V,isRTL:Xe};function Ze(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function Qe(e,t){let n=null,r,i=B(e);function a(){var e;clearTimeout(r),(e=n)==null||e.disconnect(),n=null}function o(s,c){s===void 0&&(s=!1),c===void 0&&(c=1),a();let l=e.getBoundingClientRect(),{left:u,top:d,width:f,height:p}=l;if(s||t(),!f||!p)return;let g=_(d),v=_(i.clientWidth-(u+f)),y=_(i.clientHeight-(d+p)),b=_(u),x={rootMargin:-g+`px `+-v+`px `+-y+`px `+-b+`px`,threshold:h(0,m(1,c))||1},S=!0;function C(t){let n=t[0].intersectionRatio;if(n!==c){if(!S)return o();n?o(!1,n):r=setTimeout(()=>{o(!1,1e-7)},1e3)}n===1&&!Ze(l,e.getBoundingClientRect())&&o(),S=!1}try{n=new IntersectionObserver(C,{...x,root:i.ownerDocument})}catch{n=new IntersectionObserver(C,x)}n.observe(e)}return o(!0),a}function $e(e,t,n,r){r===void 0&&(r={});let{ancestorScroll:i=!0,ancestorResize:a=!0,elementResize:o=typeof ResizeObserver==`function`,layoutShift:s=typeof IntersectionObserver==`function`,animationFrame:c=!1}=r,l=Oe(e),u=i||a?[...l?Y(l):[],...t?Y(t):[]]:[];u.forEach(e=>{i&&e.addEventListener(`scroll`,n,{passive:!0}),a&&e.addEventListener(`resize`,n)});let d=l&&s?Qe(l,n):null,f=-1,p=null;o&&(p=new ResizeObserver(e=>{let[r]=e;r&&r.target===l&&p&&t&&(p.unobserve(t),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var e;(e=p)==null||e.observe(t)})),n()}),l&&!c&&p.observe(l),t&&p.observe(t));let m,h=c?Z(e):null;c&&g();function g(){let t=Z(e);h&&!Ze(h,t)&&n(),h=t,m=requestAnimationFrame(g)}return n(),()=>{var e;u.forEach(e=>{i&&e.removeEventListener(`scroll`,n),a&&e.removeEventListener(`resize`,n)}),d?.(),(e=p)==null||e.disconnect(),p=null,c&&cancelAnimationFrame(m)}}var et=de,tt=fe,nt=ce,rt=pe,it=se,at=(e,t,n)=>{let r=new Map,i={platform:Q,...n},a={...i.platform,_c:r};return oe(e,t,{...i,platform:a})};function ot(e){return ct(e)}function st(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function ct(e){for(let t=e;t;t=st(t))if(t instanceof Element&&getComputedStyle(t).display===`none`)return null;for(let t=st(e);t;t=st(t)){if(!(t instanceof Element))continue;let e=getComputedStyle(t);if(e.display!==`contents`&&(e.position!==`static`||G(e)||t.tagName===`BODY`))return t}return null}function lt(e,t){if(!t)return null;let n=e.getRootNode();if(n instanceof Document||n instanceof ShadowRoot){let e=n.getElementById(t);if(e)return e}return e.ownerDocument.getElementById(t)}var ut=class extends Event{constructor(){super(`pk-reposition`,{bubbles:!0,cancelable:!1,composed:!0})}},dt=c`
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
`;function ft(e){return typeof e==`object`&&!!e&&`getBoundingClientRect`in e}function pt(t){return t||(e?`absolute`:`fixed`)}function mt(t,n){if(e&&!ft(t)&&n===`scroll`)return Y(t).filter(e=>e instanceof Element)}var $=class extends n{constructor(...e){super(...e),this.anchor=``,this.active=!1,this.boundary=`viewport`,this.placement=`bottom-start`,this.distance=4,this.skidding=0,this.flip=!0,this.flipFallbackPlacements=``,this.flipFallbackStrategy=`best-fit`,this.flipPadding=8,this.shift=!0,this.shiftPadding=8,this.arrow=!1,this.arrowPlacement=`anchor`,this.arrowPadding=10,this.autoSizePadding=8,this.anchorTracking=!0,this.hoverBridge=!1,this.anchorElement=null,this.settlingInitialPosition=!1,this.settleGeneration=0}static{this.styles=dt}disconnectedCallback(){this.stop(),super.disconnectedCallback()}updated(e){super.updated(e),e.has(`active`)&&(this.active?(this.resolveAnchor(),this.start()):this.stop()),e.has(`anchor`)&&this.handleAnchorChange(),this.active&&!e.has(`active`)&&this.reposition()}reposition(){this.settlingInitialPosition||this.repositionAsync()}async repositionAsync(t=!0){let n=this.popupElement,r=this.arrow?this.arrowElement:null;if(!this.active||!this.anchorElement||!n)return!1;let i=mt(this.anchorElement,this.boundary),a=[et({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?a.push(rt({apply:({rects:e})=>{let t=this.sync===`width`||this.sync===`both`,r=this.sync===`height`||this.sync===`both`;n.style.width=t?`${e.reference.width}px`:``,n.style.height=r?`${e.reference.height}px`:``}})):(n.style.width=``,n.style.height=``),this.flip&&a.push(nt({boundary:i,fallbackPlacements:this.flipFallbackPlacements?this.flipFallbackPlacements.split(` `).map(e=>e.trim()).filter(Boolean):void 0,fallbackStrategy:this.flipFallbackStrategy===`best-fit`?`bestFit`:`initialPlacement`,padding:this.flipPadding})),this.shift&&a.push(tt({boundary:i,padding:this.shiftPadding})),this.autoSize?a.push(rt({boundary:i,padding:this.autoSizePadding,apply:({availableHeight:e,availableWidth:t})=>{let r=this.autoSize===`horizontal`||this.autoSize===`both`,i=this.autoSize===`vertical`||this.autoSize===`both`;r?n.style.setProperty(`--pk-popup-available-width`,`${Math.max(0,Math.floor(t))}px`):n.style.removeProperty(`--pk-popup-available-width`),i?n.style.setProperty(`--pk-popup-available-height`,`${Math.max(0,Math.floor(e))}px`):n.style.removeProperty(`--pk-popup-available-height`)}})):(n.style.removeProperty(`--pk-popup-available-width`),n.style.removeProperty(`--pk-popup-available-height`)),this.arrow&&r&&a.push(it({element:r,padding:this.arrowPadding}));let o=pt(this.positionMethod),s=o===`fixed`;n.classList.toggle(`popup-fixed`,s);let c=e?e=>Q.getOffsetParent(e,ot):Q.getOffsetParent,{x:l,y:u,middlewareData:f,placement:p}=await at(this.anchorElement,n,{placement:this.placement,middleware:a,strategy:o,platform:{...Q,getOffsetParent:c}});if(!this.active||!n.isConnected)return!1;let m={top:`bottom`,right:`left`,bottom:`top`,left:`right`}[p.split(`-`)[0]];if(this.setAttribute(`data-current-placement`,p),Object.assign(n.style,{left:`${l}px`,top:`${u}px`,...s?{position:`fixed`}:{position:``}}),this.anchorElement){let e=this.anchorElement.getBoundingClientRect(),t=n.getBoundingClientRect();n.style.setProperty(`--pk-anchor-width`,`${e.width}px`),n.style.setProperty(`--pk-anchor-height`,`${e.height}px`);let r=d(p,e,t,this.distance,f.shift);n.style.setProperty(`--pk-transform-origin`,r)}if(this.arrow&&r){let e=f.arrow?.x,t=f.arrow?.y,n=``,i=``,a=``,o=``;if(this.arrowPlacement===`start`){let r=typeof e==`number`?`${this.arrowPadding}px`:``;n=typeof t==`number`?`${this.arrowPadding}px`:``,o=r}else this.arrowPlacement===`end`?(i=typeof e==`number`?`${this.arrowPadding}px`:``,a=typeof t==`number`?`${this.arrowPadding}px`:``):this.arrowPlacement===`center`?(o=typeof e==`number`?`50%`:``,n=typeof t==`number`?`50%`:``):(o=typeof e==`number`?`${e}px`:``,n=typeof t==`number`?`${t}px`:``);Object.assign(r.style,{top:n,right:i,bottom:a,left:o,transform:``,[m]:`calc(-1 * var(--pk-popup-arrow-size, 6px) / 2)`})}return requestAnimationFrame(()=>this.updateHoverBridge()),t&&this.dispatchEvent(new ut),!0}frames(e){return new Promise(t=>{let n=e=>{if(e<=0){t();return}requestAnimationFrame(()=>n(e-1))};n(e)})}async settleInitialPosition(){let e=++this.settleGeneration,t=this.popupElement;if(!t){this.settlingInitialPosition=!1;return}await this.frames(2),this.active&&e===this.settleGeneration&&(await this.repositionAsync(!1),t.offsetHeight,await this.frames(1),this.active&&e===this.settleGeneration&&(await this.repositionAsync(!1),this.active&&e===this.settleGeneration&&(t.classList.add(`positioned`),this.settlingInitialPosition=!1,requestAnimationFrame(()=>this.updateHoverBridge()),this.dispatchEvent(new ut))))}resolveAnchor(){if(typeof this.anchor==`string`&&this.anchor){this.anchorElement=lt(this,this.anchor);return}if(this.anchor instanceof Element||ft(this.anchor)){this.anchorElement=this.anchor;return}let e=this.querySelector(`[slot="anchor"]`);e instanceof HTMLSlotElement&&(e=e.assignedElements({flatten:!0})[0]??null),this.anchorElement=e}async handleAnchorChange(){await this.stop(),this.resolveAnchor(),this.anchorElement&&this.active&&this.start()}usesPopoverTopLayer(){return e&&this.positionMethod!==`fixed`}stop(){return new Promise(e=>{let t=this.popupElement;this.settleGeneration+=1,this.settlingInitialPosition=!1,t?.classList.remove(`positioned`),this.usesPopoverTopLayer()&&t?.hidePopover?.(),this.cleanup?(this.cleanup(),this.cleanup=void 0,t?.style.removeProperty(`--pk-transform-origin`),requestAnimationFrame(()=>e())):e(),this.removeAttribute(`data-current-placement`)})}releasePositioning(){this.cleanup&&=(this.cleanup(),void 0)}async awaitHidden(){await this.stop()}start(){this.anchorElement&&this.active&&this.isConnected&&this.popupElement&&(this.popupElement.classList.remove(`positioned`),this.settlingInitialPosition=!0,this.usesPopoverTopLayer()&&this.popupElement.showPopover?.(),this.anchorTracking&&(this.cleanup=$e(this.anchorElement,this.popupElement,()=>{this.settlingInitialPosition||this.reposition()})),this.settleInitialPosition())}getContentElement(){let e=((this.shadowRoot?.querySelector(`slot:not([name])`))?.assignedElements({flatten:!0})??[]).find(e=>e instanceof HTMLElement);if(e)return e;for(let e of this.childNodes)if(e instanceof HTMLElement&&e.getAttribute(`slot`)!==`anchor`)return e;return null}updateHoverBridge(){let e=this.popupElement;if(!this.hoverBridge||!this.anchorElement||!e)return;let t=this.anchorElement.getBoundingClientRect(),n=e.getBoundingClientRect(),r=this.placement.includes(`top`)||this.placement.includes(`bottom`),i=0,a=0,o=0,s=0,c=0,l=0,u=0,d=0;r?t.top<n.top?(i=t.left,a=t.bottom,o=t.right,s=t.bottom,c=n.left,l=n.top,u=n.right,d=n.top):(i=n.left,a=n.bottom,o=n.right,s=n.bottom,c=t.left,l=t.top,u=t.right,d=t.top):t.left<n.left?(i=t.right,a=t.top,o=n.left,s=n.top,c=t.right,l=t.bottom,u=n.left,d=n.bottom):(i=n.right,a=n.top,o=t.left,s=t.top,c=n.right,l=n.bottom,u=t.left,d=t.bottom),this.style.setProperty(`--pk-hover-bridge-top-left-x`,`${i}px`),this.style.setProperty(`--pk-hover-bridge-top-left-y`,`${a}px`),this.style.setProperty(`--pk-hover-bridge-top-right-x`,`${o}px`),this.style.setProperty(`--pk-hover-bridge-top-right-y`,`${s}px`),this.style.setProperty(`--pk-hover-bridge-bottom-left-x`,`${c}px`),this.style.setProperty(`--pk-hover-bridge-bottom-left-y`,`${l}px`),this.style.setProperty(`--pk-hover-bridge-bottom-right-x`,`${u}px`),this.style.setProperty(`--pk-hover-bridge-bottom-right-y`,`${d}px`)}render(){let t=!e||this.positionMethod===`fixed`,n=this.usesPopoverTopLayer();return a`
            <slot name="anchor" @slotchange=${()=>{this.handleAnchorChange()}}></slot>
            ${this.hoverBridge?a`
                <div
                    part="hover-bridge"
                    class=${o({"hover-bridge":!0,"hover-bridge-visible":this.active})}
                    aria-hidden="true"
                ></div>
            `:i}
            <div
                popover=${n?`manual`:i}
                part="popup"
                class=${o({popup:!0,active:this.active,"popup-fixed":t})}
            >
                ${this.arrow?a`<div part="arrow" class="arrow"></div>`:i}
                <slot></slot>
            </div>
        `}};r([s()],$.prototype,`anchor`,void 0),r([s({type:Boolean,reflect:!0})],$.prototype,`active`,void 0),r([s({attribute:`position-method`})],$.prototype,`positionMethod`,void 0),r([s({reflect:!0})],$.prototype,`boundary`,void 0),r([s({reflect:!0})],$.prototype,`placement`,void 0),r([s({type:Number})],$.prototype,`distance`,void 0),r([s({type:Number})],$.prototype,`skidding`,void 0),r([s({type:Boolean})],$.prototype,`flip`,void 0),r([s({attribute:`flip-fallback-placements`})],$.prototype,`flipFallbackPlacements`,void 0),r([s({attribute:`flip-fallback-strategy`})],$.prototype,`flipFallbackStrategy`,void 0),r([s({attribute:`flip-padding`,type:Number})],$.prototype,`flipPadding`,void 0),r([s({type:Boolean})],$.prototype,`shift`,void 0),r([s({attribute:`shift-padding`,type:Number})],$.prototype,`shiftPadding`,void 0),r([s({type:Boolean})],$.prototype,`arrow`,void 0),r([s({attribute:`arrow-placement`})],$.prototype,`arrowPlacement`,void 0),r([s({attribute:`arrow-padding`,type:Number})],$.prototype,`arrowPadding`,void 0),r([s()],$.prototype,`sync`,void 0),r([s({attribute:`auto-size`})],$.prototype,`autoSize`,void 0),r([s({attribute:`auto-size-padding`,type:Number})],$.prototype,`autoSizePadding`,void 0),r([s({attribute:`anchor-tracking`,type:Boolean})],$.prototype,`anchorTracking`,void 0),r([s({attribute:`hover-bridge`,type:Boolean})],$.prototype,`hoverBridge`,void 0),r([l(`.popup`)],$.prototype,`popupElement`,void 0),r([l(`.arrow`)],$.prototype,`arrowElement`,void 0),$=r([t(`pk-popup`)],$);var ht=c`
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
`,gt=class extends Event{constructor(){super(`pk-clear`,{bubbles:!0,cancelable:!1,composed:!0})}};export{f as a,u as i,ht as n,p as o,lt as r,gt as t};