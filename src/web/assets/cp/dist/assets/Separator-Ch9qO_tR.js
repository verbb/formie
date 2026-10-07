import{r as e}from"./rolldown-runtime-hePW80VL.js";import{T as t}from"./dndkit-Tbq_EQgB.js";import{M as n}from"./utils-BTUGQuuI.js";import{i as r,n as i,r as a}from"./has-slot-9zjTXvea-D2K9y9lH.js";import{f as o,l as s,p as c}from"./lit-C7H9X-yg.js";var l=e(t(),1),u=c`
    @layer pk-component {
        :host {
            display: block;
            flex-shrink: 0;
            margin: 0;
            padding: 0;
            border: 0;
            box-sizing: border-box;
            background: transparent;
        }

        .line {
            display: block;
            margin: 0;
            padding: 0;
            border: 0;
            box-sizing: border-box;
            background: var(--pk-color-slate-200);
        }

        :host([orientation='horizontal']) .line {
            width: 100%;
            height: 1px;
            margin-block: 0.25rem;
        }

        :host([orientation='vertical']) {
            display: inline-block;
            align-self: stretch;
            width: auto;
            height: 100%;
        }

        :host([orientation='vertical']) .line {
            width: 1px;
            height: 100%;
            margin-inline: 0.25rem;
        }

        /* CE :host { display } otherwise wins over the UA [hidden] rule. */
        :host([hidden]) {
            display: none !important;
        }
    }
`,d=class extends i{constructor(...e){super(...e),this.orientation=`horizontal`}static{this.styles=u}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`separator`),this.syncAriaOrientation()}updated(e){super.updated(e),e.has(`orientation`)&&this.syncAriaOrientation()}syncAriaOrientation(){this.setAttribute(`aria-orientation`,this.orientation)}render(){return o`<div class="line" part="base"></div>`}};a([s({reflect:!0})],d.prototype,`orientation`,void 0),d=a([r(`pk-separator`)],d);var f=n({tagName:`pk-separator`,elementClass:d,react:l.default});export{f as t};