import{s as e,t}from"./pk-status-BehQARDv-UCn-zswF.js";import{a as n,f as r}from"./overlay-lifecycle-D0pkTQyI-BDCiftP5.js";import{c as i,d as a,f as o,l as s,p as c,u as l}from"./lit-C7H9X-yg.js";import{Ft as u,Lt as d,M as f,Nt as p,Rt as m,l as h}from"./render-Dvc3MHQR-Byeexk_P.js";import{t as g}from"./pk-dialog-VMQqLW1f-BSujVpCq.js";var _=()=>window,v=`#main-form`,y=({formSelector:e=v,host:t}={})=>{if(e){let t=document.querySelector(e);if(t)return t}return(t?.closest(`form`)??null)||(document.querySelector(v)??document.getElementById(`main`))},b=e=>{let t=_().Craft;return t?.escapeHtml?t.escapeHtml(e):e.replace(/&/g,`&amp;`).replace(/</g,`&lt;`).replace(/>/g,`&gt;`).replace(/"/g,`&quot;`)},x=(e,t)=>{let n=_().Craft;return n?.sendActionRequest?n.sendActionRequest(`POST`,e,{data:t}):Promise.reject(Error(`Craft.sendActionRequest is unavailable.`))},S=(e=v,t)=>{let n=y({formSelector:e,host:t});if(!n)return{};let r={};return n.querySelectorAll(`input, select, textarea`).forEach(e=>{let t=e.getAttribute(`name`);t&&(r[t]=e.value)}),r},C=(e,t={})=>{let n=t.includeTypeNamespace!==!1,r=e.type||t.type||``,i=t.idParam??`sourceId`,a={type:r};a[i]=e[i]??e.sourceId??e.id??t.sourceId;let o=typeof window<`u`?_().Craft?.csrfTokenName:void 0;if(o&&e[o]&&(a[o]=e[o]),n&&r){let t=`types[${r}]`;Object.keys(e).forEach(n=>{n.startsWith(t)&&(a[n]=e[n])})}return(t.extraKeys??[`name`,`handle`,`enabled`]).forEach(t=>{e[t]!==void 0&&(a[t]=e[t])}),a},w=(t,n)=>{if(t==null||t===``)return{heading:n.errorHeading,text:n.genericError,trace:``,traceAsString:``,traceAsArray:[]};if(typeof t==`string`)return{heading:n.errorHeading,text:t,trace:``,traceAsString:``,traceAsArray:[]};let r=t;typeof t==`object`&&t&&`data`in t&&!(`response`in t)&&(r={response:{data:t.data,statusText:n.errorHeading}});let i=e(r);return i.text?i:{heading:n.errorHeading,text:n.genericError,trace:``,traceAsString:``,traceAsArray:[]}},T=({formSelector:e=v,host:t,action:n,redirect:r,paramName:i,paramValue:a,confirm:o})=>{let s=y({formSelector:e,host:t}),{Craft:c,$:l}=_();if(!s||typeof c?.submitForm!=`function`||!l)return;let u={};i&&a&&(u[i]=a),c.submitForm(l(s),{action:n,redirect:r,params:u,confirm:o})},E=({formSelector:e=v,host:t,onDirty:n,onClean:r})=>{let i=y({formSelector:e,host:t});if(!i)return()=>{};let a=JSON.stringify(S(e,t)),o=()=>{JSON.stringify(S(e,t))===a?r?.():n()},s=i.querySelectorAll(`input, select, textarea`),c=i.querySelectorAll(`.lightswitch`);return s.forEach(e=>{e.addEventListener(`input`,o)}),c.forEach(e=>{e.addEventListener(`change`,o)}),()=>{s.forEach(e=>{e.removeEventListener(`input`,o)}),c.forEach(e=>{e.removeEventListener(`change`,o)})}},D=c`
    @layer pk-component {
        :host {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            justify-content: space-between;
            box-sizing: border-box;
            width: 100%;
            flex: 1 1 100%;
            min-width: 0;
            min-height: 2.75rem;
        }

        :host .heading {
            display: flex;
            align-items: center;
            gap: var(--pk-connect-status-gap, 15px);
            margin: 0;
            flex: 1 1 auto;
            min-width: 0;
            line-height: 1.125rem;
            padding-block: 0.75rem;
            padding-inline: var(--pk-connect-heading-padding-inline, var(--m, 1rem) var(--s, 0.75rem));
            color: var(--pk-connect-heading-color, var(--gray-600, #515f6c));
        }

        :host .heading:only-child {
            flex: 1 1 100%;
        }

        :host .input {
            display: flex;
            align-items: center;
            flex: 0 0 auto;
            padding-block: var(--pk-connect-input-padding-block, var(--s, 0.75rem));
            padding-inline: var(--pk-connect-input-padding-inline, 10px var(--m, 1rem));
        }

        :host .heading .light {
            color: var(--pk-connect-muted-color, var(--gray-500, #606d7b));
        }

        :host .heading pk-status.pk-connect__status-icon {
            display: block;
            flex-shrink: 0;
            width: 0.75rem;
            height: 0.75rem;
            --pk-status-ring: var(--gray-500);
        }

        :host .heading .warning.with-icon::before {
            margin-inline-end: 7px;
        }
    }
`,O=e=>e===`connected`?`on`:e===`error`?`off`:`disabled`,k=class extends l{constructor(...e){super(...e),this.action=``,this.status=`disconnected`,this.formSelector=`#main-form`,this.sourceId=null,this.idParam=`sourceId`,this.type=``,this.labelConnected=`Connected`,this.labelNotConnected=`Not Connected`,this.labelConnecting=`Connecting…`,this.labelError=`Error`,this.labelConnect=`Connect`,this.labelRefresh=`Refresh`,this.labelSaveToConnect=`Save to connect.`,this.labelErrorHeading=`Connection error`,this.labelGenericError=`Unable to connect to the provider.`,this.labelShowDetails=`Show details`,this.labelHideDetails=`Hide details`,this.labelClose=`Close`,this.isDirty=!1,this.loading=!1,this.showDetails=!1,this.unwatchDirty=null,this.errorDialog=null}static{this.styles=D}createRenderRoot(){return this}connectedCallback(){super.connectedCallback(),this.unwatchDirty=E({formSelector:this.formSelector,host:this,onDirty:()=>{this.isDirty=!0}})}disconnectedCallback(){this.unwatchDirty?.(),this.unwatchDirty=null,this.errorDialog?.remove(),this.errorDialog=null,super.disconnectedCallback()}get labels(){return{connected:this.labelConnected,notConnected:this.labelNotConnected,connecting:this.labelConnecting,error:this.labelError,connect:this.labelConnect,refresh:this.labelRefresh,saveToConnect:this.labelSaveToConnect,errorHeading:this.labelErrorHeading,genericError:this.labelGenericError,showDetails:this.labelShowDetails,hideDetails:this.labelHideDetails}}get fieldHost(){return this.closest(`.pk-connect-field`)}get statusLabel(){switch(this.status){case`connected`:return this.labelConnected;case`error`:return this.labelError;case`connecting`:return this.labelConnecting;default:return this.labelNotConnected}}get statusLabelMuted(){return this.status!==`connected`&&this.status!==`error`}get actionLabel(){return this.status===`connected`?this.labelRefresh:this.labelConnect}setConnectStatus(e){this.status=e,this.dispatchEvent(new CustomEvent(`pk-status-change`,{detail:{status:e},bubbles:!0}))}setModalOpen(e){this.fieldHost?.classList.toggle(`pk-connect-field--modal-open`,e),e||(this.showDetails=!1)}ensureErrorDialog(){if(this.errorDialog)return this.errorDialog;let e=document.createElement(`pk-dialog`);e.className=`pk-connect-dialog`,e.setAttribute(`without-header`,``),e.innerHTML=[`<pk-button slot="trigger" type="button" variant="none" size="none" icon class="pk-connect-dialog__close" data-dialog="close" aria-label="${b(this.labelClose)}">`,`<pk-icon icon="xmark"></pk-icon>`,`</pk-button>`,`<div class="pk-connection-error">`,`<div class="pk-connection-error__stack">`,`<div class="pk-connection-error__icon"><pk-icon icon="triangle-exclamation"></pk-icon></div>`,`<h3 class="pk-connection-error__heading"></h3>`,`<p class="pk-connection-error__message"></p>`,`<div class="pk-connection-error__details hidden">`,`<button type="button" class="pk-connection-error__details-toggle">`,`<pk-icon icon="chevron-right"></pk-icon>`,`<span class="pk-connection-error__details-label">${b(this.labelShowDetails)}</span>`,`</button>`,`<div class="pk-connection-error__trace hidden"></div>`,`</div>`,`</div>`,`</div>`].join(``),document.body.appendChild(e);let t=e.querySelector(`.pk-connection-error__details-toggle`),n=e.querySelector(`.pk-connection-error__trace`),r=e.querySelector(`.pk-connection-error__details-label`);e.querySelector(`.pk-connection-error__details`);let i=e.querySelector(`pk-icon`);return t?.addEventListener(`click`,()=>{this.showDetails=!this.showDetails,n?.classList.toggle(`hidden`,!this.showDetails),i?.classList.toggle(`is-open`,this.showDetails),r&&(r.textContent=this.showDetails?this.labelHideDetails:this.labelShowDetails)}),e.addEventListener(`pk-open-change`,e=>{let t=!!e.detail?.open;this.setModalOpen(t),t||(n?.classList.add(`hidden`),i?.classList.remove(`is-open`),r&&(r.textContent=this.labelShowDetails))}),this.errorDialog=e,e}showErrorModal(e){let t=this.ensureErrorDialog(),n=t.querySelector(`.pk-connection-error__heading`),r=t.querySelector(`.pk-connection-error__message`),i=t.querySelector(`.pk-connection-error__details`),a=t.querySelector(`.pk-connection-error__trace`);n&&(n.textContent=e.heading||this.labelErrorHeading),r&&(r.textContent=e.text||this.labelGenericError);let o=e.traceAsString||e.trace||``;o&&i&&a?(i.classList.remove(`hidden`),a.innerHTML=o,a.classList.add(`hidden`)):i?.classList.add(`hidden`),this.showDetails=!1,t.open=!0,this.setModalOpen(!0)}async handleConnectClick(e){if(e.preventDefault(),this.isDirty||this.loading||!this.action)return;this.loading=!0,this.setConnectStatus(`connecting`),this.setModalOpen(!1);let t=S(this.formSelector,this),n=C(t,{idParam:this.idParam,sourceId:this.sourceId,type:this.type||t.type});try{let e=await x(this.action,n);if(this.loading=!1,e?.data?.success){this.setConnectStatus(`connected`);return}(e?.data?.message||e?.data?.success===!1)&&(this.setConnectStatus(`error`),this.showErrorModal(w(e?.data?.message??null,this.labels)))}catch(e){this.loading=!1,this.setConnectStatus(`error`),this.showErrorModal(w(e,this.labels))}}render(){return this.isDirty?o`
                <div class="heading">
                    <span class="warning with-icon">${this.labelSaveToConnect}</span>
                </div>
            `:o`
            <div class="heading">
                <pk-status
                    status=${O(this.status)}
                    class="pk-connect__status-icon"
                ></pk-status><span class=${this.statusLabelMuted?`light`:a}>${this.statusLabel}</span>
            </div>

            <div class="input ltr">
                <pk-button
                    type="button"
                    size="xs"
                    variant="default"
                    class="pk-connect__action"
                    spinner-size="xs"
                    ?loading=${this.loading}
                    ?disabled=${this.loading||this.isDirty}
                    @click=${this.handleConnectClick}
                >
                    ${this.actionLabel}
                </pk-button>
            </div>
        `}};d([s({reflect:!0})],k.prototype,`action`,void 0),d([s({reflect:!0})],k.prototype,`status`,void 0),d([s({attribute:`form-selector`})],k.prototype,`formSelector`,void 0),d([s({attribute:`source-id`})],k.prototype,`sourceId`,void 0),d([s({attribute:`id-param`})],k.prototype,`idParam`,void 0),d([s()],k.prototype,`type`,void 0),d([s({attribute:`label-connected`})],k.prototype,`labelConnected`,void 0),d([s({attribute:`label-not-connected`})],k.prototype,`labelNotConnected`,void 0),d([s({attribute:`label-connecting`})],k.prototype,`labelConnecting`,void 0),d([s({attribute:`label-error`})],k.prototype,`labelError`,void 0),d([s({attribute:`label-connect`})],k.prototype,`labelConnect`,void 0),d([s({attribute:`label-refresh`})],k.prototype,`labelRefresh`,void 0),d([s({attribute:`label-save-to-connect`})],k.prototype,`labelSaveToConnect`,void 0),d([s({attribute:`label-error-heading`})],k.prototype,`labelErrorHeading`,void 0),d([s({attribute:`label-generic-error`})],k.prototype,`labelGenericError`,void 0),d([s({attribute:`label-show-details`})],k.prototype,`labelShowDetails`,void 0),d([s({attribute:`label-hide-details`})],k.prototype,`labelHideDetails`,void 0),d([s({attribute:`label-close`})],k.prototype,`labelClose`,void 0),d([i()],k.prototype,`isDirty`,void 0),d([i()],k.prototype,`loading`,void 0),d([i()],k.prototype,`showDetails`,void 0),k=d([m(`pk-connect`)],k);var A=class extends l{constructor(...e){super(...e),this.connected=!1,this.connectAction=``,this.disconnectAction=``,this.paramName=``,this.paramValue=``,this.connectRedirect=``,this.disconnectRedirect=``,this.formSelector=`#main-form`,this.skipDirtyWatch=!1,this.labelConnected=`Connected`,this.labelNotConnected=`Not Connected`,this.labelConnect=`Connect`,this.labelDisconnect=`Disconnect`,this.labelSaveToConnect=`Save to connect.`,this.isDirty=!1,this.unwatchDirty=null}static{this.styles=D}createRenderRoot(){return this}connectedCallback(){super.connectedCallback(),!this.skipDirtyWatch&&(this.unwatchDirty=E({formSelector:this.formSelector,host:this,onDirty:()=>{this.isDirty=!0}}))}disconnectedCallback(){this.unwatchDirty?.(),this.unwatchDirty=null,super.disconnectedCallback()}submitOAuthAction(e,t){T({formSelector:this.formSelector,host:this,action:e,redirect:t,paramName:this.paramName,paramValue:this.paramValue})}handleConnectClick(e){e.preventDefault(),this.submitOAuthAction(this.connectAction,this.connectRedirect)}handleDisconnectClick(e){e.preventDefault(),this.submitOAuthAction(this.disconnectAction,this.disconnectRedirect)}render(){return this.isDirty?o`
                <div class="heading">
                    <span class="warning with-icon">${this.labelSaveToConnect}</span>
                </div>
            `:this.connected?o`
                <div class="heading">
                    <pk-status status="on" class="pk-connect__status-icon"></pk-status>${this.labelConnected}
                </div>

                <div class="input ltr">
                    <pk-button
                        type="button"
                        size="xs"
                        variant="default"
                        class="pk-connect__action"
                        @click=${this.handleDisconnectClick}
                    >
                        ${this.labelDisconnect}
                    </pk-button>
                </div>
            `:o`
            <div class="heading">
                <pk-status status="disabled" class="pk-connect__status-icon"></pk-status><span class="light">${this.labelNotConnected}</span>
            </div>

            <div class="input ltr">
                <pk-button
                    type="button"
                    size="xs"
                    variant="default"
                    class="pk-connect__action"
                    @click=${this.handleConnectClick}
                >
                    ${this.labelConnect}
                </pk-button>
            </div>
        `}};d([s({type:Boolean,reflect:!0})],A.prototype,`connected`,void 0),d([s({attribute:`connect-action`})],A.prototype,`connectAction`,void 0),d([s({attribute:`disconnect-action`})],A.prototype,`disconnectAction`,void 0),d([s({attribute:`param-name`})],A.prototype,`paramName`,void 0),d([s({attribute:`param-value`})],A.prototype,`paramValue`,void 0),d([s({attribute:`connect-redirect`})],A.prototype,`connectRedirect`,void 0),d([s({attribute:`disconnect-redirect`})],A.prototype,`disconnectRedirect`,void 0),d([s({attribute:`form-selector`})],A.prototype,`formSelector`,void 0),d([s({type:Boolean,attribute:`skip-dirty-watch`})],A.prototype,`skipDirtyWatch`,void 0),d([s({attribute:`label-connected`})],A.prototype,`labelConnected`,void 0),d([s({attribute:`label-not-connected`})],A.prototype,`labelNotConnected`,void 0),d([s({attribute:`label-connect`})],A.prototype,`labelConnect`,void 0),d([s({attribute:`label-disconnect`})],A.prototype,`labelDisconnect`,void 0),d([s({attribute:`label-save-to-connect`})],A.prototype,`labelSaveToConnect`,void 0),d([i()],A.prototype,`isDirty`,void 0),A=d([m(`pk-connect-oauth`)],A);var j=[n,k,A,g,r,t],M=[`pk-icon`,`pk-button`,`pk-connect`,`pk-connect-oauth`,`pk-dialog`,`pk-status`];async function N(){h({chevronRight:f,triangleExclamation:p,xmark:u});for(let e of j)if(typeof e!=`function`)throw Error(`Plugin Kit connect constructor missing from bundle`);await Promise.all(M.map(e=>customElements.whenDefined(e)))}N();