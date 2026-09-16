import{r as e}from"./rolldown-runtime-hePW80VL.js";import{T as t,w as n}from"./dndkit-Tbq_EQgB.js";import{F as r}from"./utils-DF6t9GV_.js";import{s as i}from"./pk-status-BehQARDv-UCn-zswF.js";import{p as a}from"./Field-F5nY6ns6.js";import{f as o,l as s,p as c}from"./lit-C7H9X-yg.js";import{It as l,Lt as u,Rt as d}from"./render-Dvc3MHQR-Byeexk_P.js";var f=n();function p({error:e=null,title:t=null,message:n=null,detailsLabel:r=null,actionLabel:o=null,onAction:s=null,showDetails:c=!0,containerClassName:l=`flex flex-1 items-center justify-center py-12`,contentClassName:u=`flex w-[90%] max-w-[560px] flex-col items-center text-center`}){let d=e?i(e):null,p=t||d?.heading||`Something went wrong`,m=d?.text||n||`An error has occurred.`,h=r||`Show error details`,g=d?.traceAsString||``;return(0,f.jsx)(a,{variant:`error`,title:p,message:m,containerClassName:l,contentClassName:u,primaryAction:o&&s?{label:o,onClick:s,variant:`primary`}:null,children:c&&g?(0,f.jsxs)(`details`,{className:`mb-4 w-full text-center text-xs text-rose-600`,children:[(0,f.jsx)(`summary`,{className:`cursor-pointer`,children:h}),(0,f.jsxs)(`div`,{className:`mt-2 whitespace-pre-wrap text-left`,children:[(0,f.jsxs)(`p`,{className:`mb-2`,children:[d?.heading,`: `,d?.text]}),(0,f.jsx)(`div`,{dangerouslySetInnerHTML:{__html:g}})]})]}):null})}var m=e(t(),1),h=class extends m.Component{state={hasError:!1,error:null};static getDerivedStateFromError(){return{hasError:!0}}componentDidCatch(e,t){this.setState({error:e}),console.error(this.props.consoleLabel||`React app crashed:`,e,t)}render(){if(!this.state.hasError)return this.props.children;let{title:e,message:t,detailsLabel:n,reloadLabel:r,containerClassName:i=`flex flex-1 items-center justify-center py-12`,contentClassName:a=`flex flex-col items-center justify-center text-center`}=this.props;return(0,f.jsx)(p,{error:this.state.error,title:e,message:t,detailsLabel:n,actionLabel:r,onAction:()=>{window.location.reload()},containerClassName:i,contentClassName:a})}},g=c`
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
`,_=class extends l{constructor(...e){super(...e),this.orientation=`horizontal`}static{this.styles=g}connectedCallback(){super.connectedCallback(),this.setAttribute(`role`,`separator`),this.syncAriaOrientation()}updated(e){super.updated(e),e.has(`orientation`)&&this.syncAriaOrientation()}syncAriaOrientation(){this.setAttribute(`aria-orientation`,this.orientation)}render(){return o`<div class="line" part="base"></div>`}};u([s({reflect:!0})],_.prototype,`orientation`,void 0),_=u([d(`pk-separator`)],_);var v=r({tagName:`pk-separator`,elementClass:_,react:m.default});export{h as n,p as r,v as t};