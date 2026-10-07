import{l as e,t,u as n}from"./pk-state-panel-DPP7t-er-DDP2HWtM.js";import{f as r,i,r as a,w as o}from"./has-slot-9zjTXvea-D2K9y9lH.js";import{c as s,d as c,f as l,l as u,p as d,u as f}from"./lit-C7H9X-yg.js";import{a as p}from"./pk-alert-Cjxl6CY_-BFPmEjZU.js";import{t as m}from"./pk-status-CYcadu0Q-BFTalaYG.js";import{t as h}from"./pk-dialog-CFU850OH-D5U0sDIW.js";import"./alert-CO7icsXn.js";var g=()=>window,_=`#main-form`,v=({formSelector:e=_,host:t}={})=>{if(e){let t=document.querySelector(e);if(t)return t}return(t?.closest(`form`)??null)||(document.querySelector(_)??document.getElementById(`main`))},y=e=>{let t=g().Craft;return t?.escapeHtml?t.escapeHtml(e):e.replace(/&/g,`&amp;`).replace(/</g,`&lt;`).replace(/>/g,`&gt;`).replace(/"/g,`&quot;`)},b=(e,t)=>{let n=g().Craft;return n?.sendActionRequest?n.sendActionRequest(`POST`,e,{data:t}):Promise.reject(Error(`Craft.sendActionRequest is unavailable.`))},x=(e=_,t)=>{let n=v({formSelector:e,host:t});if(!n)return{};let r={};return n.querySelectorAll(`input, select, textarea`).forEach(e=>{let t=e.getAttribute(`name`);t&&(r[t]=e.value)}),r},S=(e,t={})=>{let n=t.includeTypeNamespace!==!1,r=e.type||t.type||``,i=t.idParam??`sourceId`,a={type:r};a[i]=e[i]??e.sourceId??e.id??t.sourceId;let o=typeof window<`u`?g().Craft?.csrfTokenName:void 0;if(o&&e[o]&&(a[o]=e[o]),n&&r){let t=`types[${r}]`;Object.keys(e).forEach(n=>{n.startsWith(t)&&(a[n]=e[n])})}return(t.extraKeys??[`name`,`handle`,`enabled`]).forEach(t=>{e[t]!==void 0&&(a[t]=e[t])}),a},C=(e,t)=>{if(e==null||e===``)return{heading:t.errorHeading,text:t.genericError,trace:``,traceAsString:``,traceAsArray:[]};if(typeof e==`string`)return{heading:t.errorHeading,text:e,trace:``,traceAsString:``,traceAsArray:[]};let r=e;typeof e==`object`&&e&&`data`in e&&!(`response`in e)&&(r={response:{data:e.data,statusText:t.errorHeading}});let i=n(r);return i.text?i:{heading:t.errorHeading,text:t.genericError,trace:``,traceAsString:``,traceAsArray:[]}},w=({formSelector:e=_,host:t,action:n,redirect:r,paramName:i,paramValue:a,confirm:o})=>{let s=v({formSelector:e,host:t}),{Craft:c,$:l}=g();if(!s||typeof c?.submitForm!=`function`||!l)return;let u={};i&&a&&(u[i]=a),c.submitForm(l(s),{action:n,redirect:r,params:u,confirm:o})},T=({formSelector:e=_,host:t,onDirty:n,onClean:r})=>{let i=v({formSelector:e,host:t});if(!i)return()=>{};let a=JSON.stringify(x(e,t)),o=()=>{JSON.stringify(x(e,t))===a?r?.():n()},s=i.querySelectorAll(`input, select, textarea`),c=i.querySelectorAll(`.lightswitch`);return s.forEach(e=>{e.addEventListener(`input`,o)}),c.forEach(e=>{e.addEventListener(`change`,o)}),()=>{s.forEach(e=>{e.removeEventListener(`input`,o)}),c.forEach(e=>{e.removeEventListener(`change`,o)})}},E=d`
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
`,D=e=>e===`connected`?`on`:e===`error`?`off`:`disabled`,O=class extends f{constructor(...e){super(...e),this.action=``,this.status=`disconnected`,this.formSelector=`#main-form`,this.sourceId=null,this.idParam=`sourceId`,this.type=``,this.labelConnected=`Connected`,this.labelNotConnected=`Not Connected`,this.labelConnecting=`Connecting…`,this.labelError=`Error`,this.labelConnect=`Connect`,this.labelRefresh=`Refresh`,this.labelSaveToConnect=`Save to connect.`,this.labelErrorHeading=`Connection error`,this.labelGenericError=`Unable to connect to the provider.`,this.labelShowDetails=`Show details`,this.labelHideDetails=`Hide details`,this.labelClose=`Close`,this.isDirty=!1,this.loading=!1,this.unwatchDirty=null,this.errorDialog=null}static{this.styles=E}createRenderRoot(){return this}connectedCallback(){super.connectedCallback(),this.unwatchDirty=T({formSelector:this.formSelector,host:this,onDirty:()=>{this.isDirty=!0}})}disconnectedCallback(){this.unwatchDirty?.(),this.unwatchDirty=null,this.errorDialog?.remove(),this.errorDialog=null,super.disconnectedCallback()}get labels(){return{connected:this.labelConnected,notConnected:this.labelNotConnected,connecting:this.labelConnecting,error:this.labelError,connect:this.labelConnect,refresh:this.labelRefresh,saveToConnect:this.labelSaveToConnect,errorHeading:this.labelErrorHeading,genericError:this.labelGenericError,showDetails:this.labelShowDetails,hideDetails:this.labelHideDetails}}get fieldHost(){return this.closest(`.pk-connect-field`)}get statusLabel(){switch(this.status){case`connected`:return this.labelConnected;case`error`:return this.labelError;case`connecting`:return this.labelConnecting;default:return this.labelNotConnected}}get statusLabelMuted(){return this.status!==`connected`&&this.status!==`error`}get actionLabel(){return this.status===`connected`?this.labelRefresh:this.labelConnect}setConnectStatus(e){this.status=e,this.dispatchEvent(new CustomEvent(`pk-status-change`,{detail:{status:e},bubbles:!0}))}setModalOpen(e){this.fieldHost?.classList.toggle(`pk-connect-field--modal-open`,e)}ensureErrorDialog(){if(this.errorDialog)return this.errorDialog;let e=document.createElement(`pk-dialog`);return e.className=`pk-connect-dialog`,e.setAttribute(`without-header`,``),e.innerHTML=[`<pk-button slot="trigger" type="button" variant="none" size="none" icon class="pk-connect-dialog__close" data-dialog="close" aria-label="${y(this.labelClose)}">`,`<pk-icon icon="xmark"></pk-icon>`,`</pk-button>`,`<div class="pk-connect-dialog__content">`,`<pk-state-panel class="pk-connect-dialog__state" variant="error" size="lg" announce="assertive">`,`<span class="pk-connect-dialog__message"></span>`,`</pk-state-panel>`,`</div>`].join(``),document.body.appendChild(e),e.addEventListener(`pk-open-change`,e=>{let t=!!e.detail?.open;this.setModalOpen(t)}),this.errorDialog=e,e}showErrorModal(e){let t=this.ensureErrorDialog(),n=t.querySelector(`pk-state-panel`),r=t.querySelector(`.pk-connect-dialog__message`);r&&(r.textContent=e.text||this.labelGenericError);let i=e.traceAsArray.length>0?e.traceAsArray.join(`
`):(e.traceAsString||e.trace||``).replace(/<br\s*\/?>/gi,`
`);if(t.querySelector(`[slot="details"]`)?.remove(),n&&(n.heading=e.heading||this.labelErrorHeading,n.detailsLabel=this.labelShowDetails,n.detailsOpen=!1,n.copyable=!!i,i)){let e=document.createElement(`pre`);e.slot=`details`,e.textContent=i,n.appendChild(e)}t.open=!0,this.setModalOpen(!0)}async handleConnectClick(e){if(e.preventDefault(),this.isDirty||this.loading||!this.action)return;this.loading=!0,this.setConnectStatus(`connecting`),this.setModalOpen(!1);let t=x(this.formSelector,this),n=S(t,{idParam:this.idParam,sourceId:this.sourceId,type:this.type||t.type});try{let e=await b(this.action,n);if(this.loading=!1,e?.data?.success){this.setConnectStatus(`connected`);return}(e?.data?.message||e?.data?.success===!1)&&(this.setConnectStatus(`error`),this.showErrorModal(C(e?.data?.message??null,this.labels)))}catch(e){this.loading=!1,this.setConnectStatus(`error`),this.showErrorModal(C(e,this.labels))}}render(){return this.isDirty?l`
                <div class="heading">
                    <span class="warning with-icon">${this.labelSaveToConnect}</span>
                </div>
            `:l`
            <div class="heading">
                <pk-status
                    status=${D(this.status)}
                    class="pk-connect__status-icon"
                ></pk-status><span class=${this.statusLabelMuted?`light`:c}>${this.statusLabel}</span>
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
        `}};a([u({reflect:!0})],O.prototype,`action`,void 0),a([u({reflect:!0})],O.prototype,`status`,void 0),a([u({attribute:`form-selector`})],O.prototype,`formSelector`,void 0),a([u({attribute:`source-id`})],O.prototype,`sourceId`,void 0),a([u({attribute:`id-param`})],O.prototype,`idParam`,void 0),a([u()],O.prototype,`type`,void 0),a([u({attribute:`label-connected`})],O.prototype,`labelConnected`,void 0),a([u({attribute:`label-not-connected`})],O.prototype,`labelNotConnected`,void 0),a([u({attribute:`label-connecting`})],O.prototype,`labelConnecting`,void 0),a([u({attribute:`label-error`})],O.prototype,`labelError`,void 0),a([u({attribute:`label-connect`})],O.prototype,`labelConnect`,void 0),a([u({attribute:`label-refresh`})],O.prototype,`labelRefresh`,void 0),a([u({attribute:`label-save-to-connect`})],O.prototype,`labelSaveToConnect`,void 0),a([u({attribute:`label-error-heading`})],O.prototype,`labelErrorHeading`,void 0),a([u({attribute:`label-generic-error`})],O.prototype,`labelGenericError`,void 0),a([u({attribute:`label-show-details`})],O.prototype,`labelShowDetails`,void 0),a([u({attribute:`label-hide-details`})],O.prototype,`labelHideDetails`,void 0),a([u({attribute:`label-close`})],O.prototype,`labelClose`,void 0),a([s()],O.prototype,`isDirty`,void 0),a([s()],O.prototype,`loading`,void 0),O=a([i(`pk-connect`)],O);var k=class extends f{constructor(...e){super(...e),this.connected=!1,this.connectAction=``,this.disconnectAction=``,this.paramName=``,this.paramValue=``,this.connectRedirect=``,this.disconnectRedirect=``,this.formSelector=`#main-form`,this.skipDirtyWatch=!1,this.labelConnected=`Connected`,this.labelNotConnected=`Not Connected`,this.labelConnect=`Connect`,this.labelDisconnect=`Disconnect`,this.labelSaveToConnect=`Save to connect.`,this.isDirty=!1,this.unwatchDirty=null}static{this.styles=E}createRenderRoot(){return this}connectedCallback(){super.connectedCallback(),!this.skipDirtyWatch&&(this.unwatchDirty=T({formSelector:this.formSelector,host:this,onDirty:()=>{this.isDirty=!0}}))}disconnectedCallback(){this.unwatchDirty?.(),this.unwatchDirty=null,super.disconnectedCallback()}submitOAuthAction(e,t){w({formSelector:this.formSelector,host:this,action:e,redirect:t,paramName:this.paramName,paramValue:this.paramValue})}handleConnectClick(e){e.preventDefault(),this.submitOAuthAction(this.connectAction,this.connectRedirect)}handleDisconnectClick(e){e.preventDefault(),this.submitOAuthAction(this.disconnectAction,this.disconnectRedirect)}render(){return this.isDirty?l`
                <div class="heading">
                    <span class="warning with-icon">${this.labelSaveToConnect}</span>
                </div>
            `:this.connected?l`
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
            `:l`
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
        `}};a([u({type:Boolean,reflect:!0})],k.prototype,`connected`,void 0),a([u({attribute:`connect-action`})],k.prototype,`connectAction`,void 0),a([u({attribute:`disconnect-action`})],k.prototype,`disconnectAction`,void 0),a([u({attribute:`param-name`})],k.prototype,`paramName`,void 0),a([u({attribute:`param-value`})],k.prototype,`paramValue`,void 0),a([u({attribute:`connect-redirect`})],k.prototype,`connectRedirect`,void 0),a([u({attribute:`disconnect-redirect`})],k.prototype,`disconnectRedirect`,void 0),a([u({attribute:`form-selector`})],k.prototype,`formSelector`,void 0),a([u({type:Boolean,attribute:`skip-dirty-watch`})],k.prototype,`skipDirtyWatch`,void 0),a([u({attribute:`label-connected`})],k.prototype,`labelConnected`,void 0),a([u({attribute:`label-not-connected`})],k.prototype,`labelNotConnected`,void 0),a([u({attribute:`label-connect`})],k.prototype,`labelConnect`,void 0),a([u({attribute:`label-disconnect`})],k.prototype,`labelDisconnect`,void 0),a([u({attribute:`label-save-to-connect`})],k.prototype,`labelSaveToConnect`,void 0),a([s()],k.prototype,`isDirty`,void 0),k=a([i(`pk-connect-oauth`)],k);var A=[p,O,k,h,e,t,m],j=[`pk-icon`,`pk-button`,`pk-connect`,`pk-connect-oauth`,`pk-dialog`,`pk-state-panel`,`pk-status`];async function M(){r({xmark:o});for(let e of A)if(typeof e!=`function`)throw Error(`Plugin Kit connect constructor missing from bundle`);await Promise.all(j.map(e=>customElements.whenDefined(e)))}M();