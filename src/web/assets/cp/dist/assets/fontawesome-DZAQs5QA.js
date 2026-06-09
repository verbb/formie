import{R as Da,r as Dt}from"./react-vendor-DtO2kpgD.js";function La(a,e){(e==null||e>a.length)&&(e=a.length);for(var t=0,n=Array(e);t<e;t++)n[t]=a[t];return n}function Wt(a){if(Array.isArray(a))return a}function Ut(a){if(Array.isArray(a))return La(a)}function Yt(a,e){if(!(a instanceof e))throw new TypeError("Cannot call a class as a function")}function Ht(a,e){for(var t=0;t<e.length;t++){var n=e[t];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(a,Ne(n.key),n)}}function Gt(a,e,t){return e&&Ht(a.prototype,e),Object.defineProperty(a,"prototype",{writable:!1}),a}function sa(a,e){var t=typeof Symbol<"u"&&a[Symbol.iterator]||a["@@iterator"];if(!t){if(Array.isArray(a)||(t=Wa(a))||e){t&&(a=t);var n=0,r=function(){};return{s:r,n:function(){return n>=a.length?{done:!0}:{done:!1,value:a[n++]}},e:function(l){throw l},f:r}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var i,s=!0,o=!1;return{s:function(){t=t.call(a)},n:function(){var l=t.next();return s=l.done,l},e:function(l){o=!0,i=l},f:function(){try{s||t.return==null||t.return()}finally{if(o)throw i}}}}function p(a,e,t){return(e=Ne(e))in a?Object.defineProperty(a,e,{value:t,enumerable:!0,configurable:!0,writable:!0}):a[e]=t,a}function Xt(a){if(typeof Symbol<"u"&&a[Symbol.iterator]!=null||a["@@iterator"]!=null)return Array.from(a)}function Bt(a,e){var t=a==null?null:typeof Symbol<"u"&&a[Symbol.iterator]||a["@@iterator"];if(t!=null){var n,r,i,s,o=[],l=!0,c=!1;try{if(i=(t=t.call(a)).next,e===0){if(Object(t)!==t)return;l=!1}else for(;!(l=(n=i.call(t)).done)&&(o.push(n.value),o.length!==e);l=!0);}catch(d){c=!0,r=d}finally{try{if(!l&&t.return!=null&&(s=t.return(),Object(s)!==s))return}finally{if(c)throw r}}return o}}function Vt(){throw new TypeError(`Invalid attempt to destructure non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function qt(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function Za(a,e){var t=Object.keys(a);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(a);e&&(n=n.filter(function(r){return Object.getOwnPropertyDescriptor(a,r).enumerable})),t.push.apply(t,n)}return t}function f(a){for(var e=1;e<arguments.length;e++){var t=arguments[e]!=null?arguments[e]:{};e%2?Za(Object(t),!0).forEach(function(n){p(a,n,t[n])}):Object.getOwnPropertyDescriptors?Object.defineProperties(a,Object.getOwnPropertyDescriptors(t)):Za(Object(t)).forEach(function(n){Object.defineProperty(a,n,Object.getOwnPropertyDescriptor(t,n))})}return a}function da(a,e){return Wt(a)||Bt(a,e)||Wa(a,e)||Vt()}function I(a){return Ut(a)||Xt(a)||Wa(a)||qt()}function Kt(a,e){if(typeof a!="object"||!a)return a;var t=a[Symbol.toPrimitive];if(t!==void 0){var n=t.call(a,e);if(typeof n!="object")return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(a)}function Ne(a){var e=Kt(a,"string");return typeof e=="symbol"?e:e+""}function fa(a){"@babel/helpers - typeof";return fa=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},fa(a)}function Wa(a,e){if(a){if(typeof a=="string")return La(a,e);var t={}.toString.call(a).slice(8,-1);return t==="Object"&&a.constructor&&(t=a.constructor.name),t==="Map"||t==="Set"?Array.from(a):t==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t)?La(a,e):void 0}}var ae=function(){},Ua={},Fe={},Oe=null,Te={mark:ae,measure:ae};try{typeof window<"u"&&(Ua=window),typeof document<"u"&&(Fe=document),typeof MutationObserver<"u"&&(Oe=MutationObserver),typeof performance<"u"&&(Te=performance)}catch{}var Jt=Ua.navigator||{},ee=Jt.userAgent,te=ee===void 0?"":ee,_=Ua,x=Fe,ne=Oe,ra=Te;_.document;var T=!!x.documentElement&&!!x.head&&typeof x.addEventListener=="function"&&typeof x.createElement=="function",_e=~te.indexOf("MSIE")||~te.indexOf("Trident/"),ba,Qt=/fa(k|kd|s|r|l|t|d|dr|dl|dt|b|slr|slpr|wsb|tl|ns|nds|es|gt|jr|jfr|jdr|usb|ufsb|udsb|cr|ss|sr|sl|st|sds|sdr|sdl|sdt)?[\-\ ]/,Zt=/Font ?Awesome ?([567 ]*)(Solid|Regular|Light|Thin|Duotone|Brands|Free|Pro|Sharp Duotone|Sharp|Kit|Notdog Duo|Notdog|Chisel|Etch|Graphite|Thumbprint|Jelly Fill|Jelly Duo|Jelly|Utility|Utility Fill|Utility Duo|Slab Press|Slab|Whiteboard)?.*/i,je={classic:{fa:"solid",fas:"solid","fa-solid":"solid",far:"regular","fa-regular":"regular",fal:"light","fa-light":"light",fat:"thin","fa-thin":"thin",fab:"brands","fa-brands":"brands"},duotone:{fa:"solid",fad:"solid","fa-solid":"solid","fa-duotone":"solid",fadr:"regular","fa-regular":"regular",fadl:"light","fa-light":"light",fadt:"thin","fa-thin":"thin"},sharp:{fa:"solid",fass:"solid","fa-solid":"solid",fasr:"regular","fa-regular":"regular",fasl:"light","fa-light":"light",fast:"thin","fa-thin":"thin"},"sharp-duotone":{fa:"solid",fasds:"solid","fa-solid":"solid",fasdr:"regular","fa-regular":"regular",fasdl:"light","fa-light":"light",fasdt:"thin","fa-thin":"thin"},slab:{"fa-regular":"regular",faslr:"regular"},"slab-press":{"fa-regular":"regular",faslpr:"regular"},thumbprint:{"fa-light":"light",fatl:"light"},whiteboard:{"fa-semibold":"semibold",fawsb:"semibold"},notdog:{"fa-solid":"solid",fans:"solid"},"notdog-duo":{"fa-solid":"solid",fands:"solid"},etch:{"fa-solid":"solid",faes:"solid"},graphite:{"fa-thin":"thin",fagt:"thin"},jelly:{"fa-regular":"regular",fajr:"regular"},"jelly-fill":{"fa-regular":"regular",fajfr:"regular"},"jelly-duo":{"fa-regular":"regular",fajdr:"regular"},chisel:{"fa-regular":"regular",facr:"regular"},utility:{"fa-semibold":"semibold",fausb:"semibold"},"utility-duo":{"fa-semibold":"semibold",faudsb:"semibold"},"utility-fill":{"fa-semibold":"semibold",faufsb:"semibold"}},an={GROUP:"duotone-group",PRIMARY:"primary",SECONDARY:"secondary"},Re=["fa-classic","fa-duotone","fa-sharp","fa-sharp-duotone","fa-thumbprint","fa-whiteboard","fa-notdog","fa-notdog-duo","fa-chisel","fa-etch","fa-graphite","fa-jelly","fa-jelly-fill","fa-jelly-duo","fa-slab","fa-slab-press","fa-utility","fa-utility-duo","fa-utility-fill"],L="classic",aa="duotone",$e="sharp",De="sharp-duotone",We="chisel",Ue="etch",Ye="graphite",He="jelly",Ge="jelly-duo",Xe="jelly-fill",Be="notdog",Ve="notdog-duo",qe="slab",Ke="slab-press",Je="thumbprint",Qe="utility",Ze="utility-duo",at="utility-fill",et="whiteboard",en="Classic",tn="Duotone",nn="Sharp",rn="Sharp Duotone",sn="Chisel",on="Etch",ln="Graphite",fn="Jelly",cn="Jelly Duo",un="Jelly Fill",dn="Notdog",mn="Notdog Duo",vn="Slab",hn="Slab Press",pn="Thumbprint",gn="Utility",bn="Utility Duo",yn="Utility Fill",xn="Whiteboard",tt=[L,aa,$e,De,We,Ue,Ye,He,Ge,Xe,Be,Ve,qe,Ke,Je,Qe,Ze,at,et];ba={},p(p(p(p(p(p(p(p(p(p(ba,L,en),aa,tn),$e,nn),De,rn),We,sn),Ue,on),Ye,ln),He,fn),Ge,cn),Xe,un),p(p(p(p(p(p(p(p(p(ba,Be,dn),Ve,mn),qe,vn),Ke,hn),Je,pn),Qe,gn),Ze,bn),at,yn),et,xn);var Sn={classic:{900:"fas",400:"far",normal:"far",300:"fal",100:"fat"},duotone:{900:"fad",400:"fadr",300:"fadl",100:"fadt"},sharp:{900:"fass",400:"fasr",300:"fasl",100:"fast"},"sharp-duotone":{900:"fasds",400:"fasdr",300:"fasdl",100:"fasdt"},slab:{400:"faslr"},"slab-press":{400:"faslpr"},whiteboard:{600:"fawsb"},thumbprint:{300:"fatl"},notdog:{900:"fans"},"notdog-duo":{900:"fands"},etch:{900:"faes"},graphite:{100:"fagt"},chisel:{400:"facr"},jelly:{400:"fajr"},"jelly-fill":{400:"fajfr"},"jelly-duo":{400:"fajdr"},utility:{600:"fausb"},"utility-duo":{600:"faudsb"},"utility-fill":{600:"faufsb"}},wn={"Font Awesome 7 Free":{900:"fas",400:"far"},"Font Awesome 7 Pro":{900:"fas",400:"far",normal:"far",300:"fal",100:"fat"},"Font Awesome 7 Brands":{400:"fab",normal:"fab"},"Font Awesome 7 Duotone":{900:"fad",400:"fadr",normal:"fadr",300:"fadl",100:"fadt"},"Font Awesome 7 Sharp":{900:"fass",400:"fasr",normal:"fasr",300:"fasl",100:"fast"},"Font Awesome 7 Sharp Duotone":{900:"fasds",400:"fasdr",normal:"fasdr",300:"fasdl",100:"fasdt"},"Font Awesome 7 Jelly":{400:"fajr",normal:"fajr"},"Font Awesome 7 Jelly Fill":{400:"fajfr",normal:"fajfr"},"Font Awesome 7 Jelly Duo":{400:"fajdr",normal:"fajdr"},"Font Awesome 7 Slab":{400:"faslr",normal:"faslr"},"Font Awesome 7 Slab Press":{400:"faslpr",normal:"faslpr"},"Font Awesome 7 Thumbprint":{300:"fatl",normal:"fatl"},"Font Awesome 7 Notdog":{900:"fans",normal:"fans"},"Font Awesome 7 Notdog Duo":{900:"fands",normal:"fands"},"Font Awesome 7 Etch":{900:"faes",normal:"faes"},"Font Awesome 7 Graphite":{100:"fagt",normal:"fagt"},"Font Awesome 7 Chisel":{400:"facr",normal:"facr"},"Font Awesome 7 Whiteboard":{600:"fawsb",normal:"fawsb"},"Font Awesome 7 Utility":{600:"fausb",normal:"fausb"},"Font Awesome 7 Utility Duo":{600:"faudsb",normal:"faudsb"},"Font Awesome 7 Utility Fill":{600:"faufsb",normal:"faufsb"}},An=new Map([["classic",{defaultShortPrefixId:"fas",defaultStyleId:"solid",styleIds:["solid","regular","light","thin","brands"],futureStyleIds:[],defaultFontWeight:900}],["duotone",{defaultShortPrefixId:"fad",defaultStyleId:"solid",styleIds:["solid","regular","light","thin"],futureStyleIds:[],defaultFontWeight:900}],["sharp",{defaultShortPrefixId:"fass",defaultStyleId:"solid",styleIds:["solid","regular","light","thin"],futureStyleIds:[],defaultFontWeight:900}],["sharp-duotone",{defaultShortPrefixId:"fasds",defaultStyleId:"solid",styleIds:["solid","regular","light","thin"],futureStyleIds:[],defaultFontWeight:900}],["chisel",{defaultShortPrefixId:"facr",defaultStyleId:"regular",styleIds:["regular"],futureStyleIds:[],defaultFontWeight:400}],["etch",{defaultShortPrefixId:"faes",defaultStyleId:"solid",styleIds:["solid"],futureStyleIds:[],defaultFontWeight:900}],["graphite",{defaultShortPrefixId:"fagt",defaultStyleId:"thin",styleIds:["thin"],futureStyleIds:[],defaultFontWeight:100}],["jelly",{defaultShortPrefixId:"fajr",defaultStyleId:"regular",styleIds:["regular"],futureStyleIds:[],defaultFontWeight:400}],["jelly-duo",{defaultShortPrefixId:"fajdr",defaultStyleId:"regular",styleIds:["regular"],futureStyleIds:[],defaultFontWeight:400}],["jelly-fill",{defaultShortPrefixId:"fajfr",defaultStyleId:"regular",styleIds:["regular"],futureStyleIds:[],defaultFontWeight:400}],["notdog",{defaultShortPrefixId:"fans",defaultStyleId:"solid",styleIds:["solid"],futureStyleIds:[],defaultFontWeight:900}],["notdog-duo",{defaultShortPrefixId:"fands",defaultStyleId:"solid",styleIds:["solid"],futureStyleIds:[],defaultFontWeight:900}],["slab",{defaultShortPrefixId:"faslr",defaultStyleId:"regular",styleIds:["regular"],futureStyleIds:[],defaultFontWeight:400}],["slab-press",{defaultShortPrefixId:"faslpr",defaultStyleId:"regular",styleIds:["regular"],futureStyleIds:[],defaultFontWeight:400}],["thumbprint",{defaultShortPrefixId:"fatl",defaultStyleId:"light",styleIds:["light"],futureStyleIds:[],defaultFontWeight:300}],["utility",{defaultShortPrefixId:"fausb",defaultStyleId:"semibold",styleIds:["semibold"],futureStyleIds:[],defaultFontWeight:600}],["utility-duo",{defaultShortPrefixId:"faudsb",defaultStyleId:"semibold",styleIds:["semibold"],futureStyleIds:[],defaultFontWeight:600}],["utility-fill",{defaultShortPrefixId:"faufsb",defaultStyleId:"semibold",styleIds:["semibold"],futureStyleIds:[],defaultFontWeight:600}],["whiteboard",{defaultShortPrefixId:"fawsb",defaultStyleId:"semibold",styleIds:["semibold"],futureStyleIds:[],defaultFontWeight:600}]]),kn={chisel:{regular:"facr"},classic:{brands:"fab",light:"fal",regular:"far",solid:"fas",thin:"fat"},duotone:{light:"fadl",regular:"fadr",solid:"fad",thin:"fadt"},etch:{solid:"faes"},graphite:{thin:"fagt"},jelly:{regular:"fajr"},"jelly-duo":{regular:"fajdr"},"jelly-fill":{regular:"fajfr"},notdog:{solid:"fans"},"notdog-duo":{solid:"fands"},sharp:{light:"fasl",regular:"fasr",solid:"fass",thin:"fast"},"sharp-duotone":{light:"fasdl",regular:"fasdr",solid:"fasds",thin:"fasdt"},slab:{regular:"faslr"},"slab-press":{regular:"faslpr"},thumbprint:{light:"fatl"},utility:{semibold:"fausb"},"utility-duo":{semibold:"faudsb"},"utility-fill":{semibold:"faufsb"},whiteboard:{semibold:"fawsb"}},nt=["fak","fa-kit","fakd","fa-kit-duotone"],re={kit:{fak:"kit","fa-kit":"kit"},"kit-duotone":{fakd:"kit-duotone","fa-kit-duotone":"kit-duotone"}},Ln=["kit"],zn="kit",Mn="kit-duotone",In="Kit",Cn="Kit Duotone";p(p({},zn,In),Mn,Cn);var Pn={kit:{"fa-kit":"fak"}},En={"Font Awesome Kit":{400:"fak",normal:"fak"},"Font Awesome Kit Duotone":{400:"fakd",normal:"fakd"}},Nn={kit:{fak:"fa-kit"}},ie={kit:{kit:"fak"},"kit-duotone":{"kit-duotone":"fakd"}},ya,ia={GROUP:"duotone-group",SWAP_OPACITY:"swap-opacity",PRIMARY:"primary",SECONDARY:"secondary"},Fn=["fa-classic","fa-duotone","fa-sharp","fa-sharp-duotone","fa-thumbprint","fa-whiteboard","fa-notdog","fa-notdog-duo","fa-chisel","fa-etch","fa-graphite","fa-jelly","fa-jelly-fill","fa-jelly-duo","fa-slab","fa-slab-press","fa-utility","fa-utility-duo","fa-utility-fill"],On="classic",Tn="duotone",_n="sharp",jn="sharp-duotone",Rn="chisel",$n="etch",Dn="graphite",Wn="jelly",Un="jelly-duo",Yn="jelly-fill",Hn="notdog",Gn="notdog-duo",Xn="slab",Bn="slab-press",Vn="thumbprint",qn="utility",Kn="utility-duo",Jn="utility-fill",Qn="whiteboard",Zn="Classic",ar="Duotone",er="Sharp",tr="Sharp Duotone",nr="Chisel",rr="Etch",ir="Graphite",sr="Jelly",or="Jelly Duo",lr="Jelly Fill",fr="Notdog",cr="Notdog Duo",ur="Slab",dr="Slab Press",mr="Thumbprint",vr="Utility",hr="Utility Duo",pr="Utility Fill",gr="Whiteboard";ya={},p(p(p(p(p(p(p(p(p(p(ya,On,Zn),Tn,ar),_n,er),jn,tr),Rn,nr),$n,rr),Dn,ir),Wn,sr),Un,or),Yn,lr),p(p(p(p(p(p(p(p(p(ya,Hn,fr),Gn,cr),Xn,ur),Bn,dr),Vn,mr),qn,vr),Kn,hr),Jn,pr),Qn,gr);var br="kit",yr="kit-duotone",xr="Kit",Sr="Kit Duotone";p(p({},br,xr),yr,Sr);var wr={classic:{"fa-brands":"fab","fa-duotone":"fad","fa-light":"fal","fa-regular":"far","fa-solid":"fas","fa-thin":"fat"},duotone:{"fa-regular":"fadr","fa-light":"fadl","fa-thin":"fadt"},sharp:{"fa-solid":"fass","fa-regular":"fasr","fa-light":"fasl","fa-thin":"fast"},"sharp-duotone":{"fa-solid":"fasds","fa-regular":"fasdr","fa-light":"fasdl","fa-thin":"fasdt"},slab:{"fa-regular":"faslr"},"slab-press":{"fa-regular":"faslpr"},whiteboard:{"fa-semibold":"fawsb"},thumbprint:{"fa-light":"fatl"},notdog:{"fa-solid":"fans"},"notdog-duo":{"fa-solid":"fands"},etch:{"fa-solid":"faes"},graphite:{"fa-thin":"fagt"},jelly:{"fa-regular":"fajr"},"jelly-fill":{"fa-regular":"fajfr"},"jelly-duo":{"fa-regular":"fajdr"},chisel:{"fa-regular":"facr"},utility:{"fa-semibold":"fausb"},"utility-duo":{"fa-semibold":"faudsb"},"utility-fill":{"fa-semibold":"faufsb"}},Ar={classic:["fas","far","fal","fat","fad"],duotone:["fadr","fadl","fadt"],sharp:["fass","fasr","fasl","fast"],"sharp-duotone":["fasds","fasdr","fasdl","fasdt"],slab:["faslr"],"slab-press":["faslpr"],whiteboard:["fawsb"],thumbprint:["fatl"],notdog:["fans"],"notdog-duo":["fands"],etch:["faes"],graphite:["fagt"],jelly:["fajr"],"jelly-fill":["fajfr"],"jelly-duo":["fajdr"],chisel:["facr"],utility:["fausb"],"utility-duo":["faudsb"],"utility-fill":["faufsb"]},za={classic:{fab:"fa-brands",fad:"fa-duotone",fal:"fa-light",far:"fa-regular",fas:"fa-solid",fat:"fa-thin"},duotone:{fadr:"fa-regular",fadl:"fa-light",fadt:"fa-thin"},sharp:{fass:"fa-solid",fasr:"fa-regular",fasl:"fa-light",fast:"fa-thin"},"sharp-duotone":{fasds:"fa-solid",fasdr:"fa-regular",fasdl:"fa-light",fasdt:"fa-thin"},slab:{faslr:"fa-regular"},"slab-press":{faslpr:"fa-regular"},whiteboard:{fawsb:"fa-semibold"},thumbprint:{fatl:"fa-light"},notdog:{fans:"fa-solid"},"notdog-duo":{fands:"fa-solid"},etch:{faes:"fa-solid"},graphite:{fagt:"fa-thin"},jelly:{fajr:"fa-regular"},"jelly-fill":{fajfr:"fa-regular"},"jelly-duo":{fajdr:"fa-regular"},chisel:{facr:"fa-regular"},utility:{fausb:"fa-semibold"},"utility-duo":{faudsb:"fa-semibold"},"utility-fill":{faufsb:"fa-semibold"}},kr=["fa-solid","fa-regular","fa-light","fa-thin","fa-duotone","fa-brands","fa-semibold"],rt=["fa","fas","far","fal","fat","fad","fadr","fadl","fadt","fab","fass","fasr","fasl","fast","fasds","fasdr","fasdl","fasdt","faslr","faslpr","fawsb","fatl","fans","fands","faes","fagt","fajr","fajfr","fajdr","facr","fausb","faudsb","faufsb"].concat(Fn,kr),Lr=["solid","regular","light","thin","duotone","brands","semibold"],it=[1,2,3,4,5,6,7,8,9,10],zr=it.concat([11,12,13,14,15,16,17,18,19,20]),Mr=["aw","fw","pull-left","pull-right"],Ir=[].concat(I(Object.keys(Ar)),Lr,Mr,["2xs","xs","sm","lg","xl","2xl","beat","border","fade","beat-fade","bounce","flip-both","flip-horizontal","flip-vertical","flip","inverse","layers","layers-bottom-left","layers-bottom-right","layers-counter","layers-text","layers-top-left","layers-top-right","li","pull-end","pull-start","pulse","rotate-180","rotate-270","rotate-90","rotate-by","shake","spin-pulse","spin-reverse","spin","stack-1x","stack-2x","stack","ul","width-auto","width-fixed",ia.GROUP,ia.SWAP_OPACITY,ia.PRIMARY,ia.SECONDARY]).concat(it.map(function(a){return"".concat(a,"x")})).concat(zr.map(function(a){return"w-".concat(a)})),Cr={"Font Awesome 5 Free":{900:"fas",400:"far"},"Font Awesome 5 Pro":{900:"fas",400:"far",normal:"far",300:"fal"},"Font Awesome 5 Brands":{400:"fab",normal:"fab"},"Font Awesome 5 Duotone":{900:"fad"}},F="___FONT_AWESOME___",Ma=16,st="fa",ot="svg-inline--fa",D="data-fa-i2svg",Ia="data-fa-pseudo-element",Pr="data-fa-pseudo-element-pending",Ya="data-prefix",Ha="data-icon",se="fontawesome-i2svg",Er="async",Nr=["HTML","HEAD","STYLE","SCRIPT"],lt=["::before","::after",":before",":after"],ft=(function(){try{return!0}catch{return!1}})();function ea(a){return new Proxy(a,{get:function(t,n){return n in t?t[n]:t[L]}})}var ct=f({},je);ct[L]=f(f(f(f({},{"fa-duotone":"duotone"}),je[L]),re.kit),re["kit-duotone"]);var Fr=ea(ct),Ca=f({},kn);Ca[L]=f(f(f(f({},{duotone:"fad"}),Ca[L]),ie.kit),ie["kit-duotone"]);var oe=ea(Ca),Pa=f({},za);Pa[L]=f(f({},Pa[L]),Nn.kit);var Ga=ea(Pa),Ea=f({},wr);Ea[L]=f(f({},Ea[L]),Pn.kit);ea(Ea);var Or=Qt,ut="fa-layers-text",Tr=Zt,_r=f({},Sn);ea(_r);var jr=["class","data-prefix","data-icon","data-fa-transform","data-fa-mask"],xa=an,Rr=[].concat(I(Ln),I(Ir)),K=_.FontAwesomeConfig||{};function $r(a){var e=x.querySelector("script["+a+"]");if(e)return e.getAttribute(a)}function Dr(a){return a===""?!0:a==="false"?!1:a==="true"?!0:a}if(x&&typeof x.querySelector=="function"){var Wr=[["data-family-prefix","familyPrefix"],["data-css-prefix","cssPrefix"],["data-family-default","familyDefault"],["data-style-default","styleDefault"],["data-replacement-class","replacementClass"],["data-auto-replace-svg","autoReplaceSvg"],["data-auto-add-css","autoAddCss"],["data-search-pseudo-elements","searchPseudoElements"],["data-search-pseudo-elements-warnings","searchPseudoElementsWarnings"],["data-search-pseudo-elements-full-scan","searchPseudoElementsFullScan"],["data-observe-mutations","observeMutations"],["data-mutate-approach","mutateApproach"],["data-keep-original-source","keepOriginalSource"],["data-measure-performance","measurePerformance"],["data-show-missing-icons","showMissingIcons"]];Wr.forEach(function(a){var e=da(a,2),t=e[0],n=e[1],r=Dr($r(t));r!=null&&(K[n]=r)})}var dt={styleDefault:"solid",familyDefault:L,cssPrefix:st,replacementClass:ot,autoReplaceSvg:!0,autoAddCss:!0,searchPseudoElements:!1,searchPseudoElementsWarnings:!0,searchPseudoElementsFullScan:!1,observeMutations:!0,mutateApproach:"async",keepOriginalSource:!0,measurePerformance:!1,showMissingIcons:!0};K.familyPrefix&&(K.cssPrefix=K.familyPrefix);var X=f(f({},dt),K);X.autoReplaceSvg||(X.observeMutations=!1);var m={};Object.keys(dt).forEach(function(a){Object.defineProperty(m,a,{enumerable:!0,set:function(t){X[a]=t,J.forEach(function(n){return n(m)})},get:function(){return X[a]}})});Object.defineProperty(m,"familyPrefix",{enumerable:!0,set:function(e){X.cssPrefix=e,J.forEach(function(t){return t(m)})},get:function(){return X.cssPrefix}});_.FontAwesomeConfig=m;var J=[];function Ur(a){return J.push(a),function(){J.splice(J.indexOf(a),1)}}var U=Ma,C={size:16,x:0,y:0,rotate:0,flipX:!1,flipY:!1};function Yr(a){if(!(!a||!T)){var e=x.createElement("style");e.setAttribute("type","text/css"),e.innerHTML=a;for(var t=x.head.childNodes,n=null,r=t.length-1;r>-1;r--){var i=t[r],s=(i.tagName||"").toUpperCase();["STYLE","LINK"].indexOf(s)>-1&&(n=i)}return x.head.insertBefore(e,n),a}}var Hr="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";function le(){for(var a=12,e="";a-- >0;)e+=Hr[Math.random()*62|0];return e}function V(a){for(var e=[],t=(a||[]).length>>>0;t--;)e[t]=a[t];return e}function Xa(a){return a.classList?V(a.classList):(a.getAttribute("class")||"").split(" ").filter(function(e){return e})}function mt(a){return"".concat(a).replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/'/g,"&#39;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function Gr(a){return Object.keys(a||{}).reduce(function(e,t){return e+"".concat(t,'="').concat(mt(a[t]),'" ')},"").trim()}function ma(a){return Object.keys(a||{}).reduce(function(e,t){return e+"".concat(t,": ").concat(a[t].trim(),";")},"")}function Ba(a){return a.size!==C.size||a.x!==C.x||a.y!==C.y||a.rotate!==C.rotate||a.flipX||a.flipY}function Xr(a){var e=a.transform,t=a.containerWidth,n=a.iconWidth,r={transform:"translate(".concat(t/2," 256)")},i="translate(".concat(e.x*32,", ").concat(e.y*32,") "),s="scale(".concat(e.size/16*(e.flipX?-1:1),", ").concat(e.size/16*(e.flipY?-1:1),") "),o="rotate(".concat(e.rotate," 0 0)"),l={transform:"".concat(i," ").concat(s," ").concat(o)},c={transform:"translate(".concat(n/2*-1," -256)")};return{outer:r,inner:l,path:c}}function Br(a){var e=a.transform,t=a.width,n=t===void 0?Ma:t,r=a.height,i=r===void 0?Ma:r,s="";return _e?s+="translate(".concat(e.x/U-n/2,"em, ").concat(e.y/U-i/2,"em) "):s+="translate(calc(-50% + ".concat(e.x/U,"em), calc(-50% + ").concat(e.y/U,"em)) "),s+="scale(".concat(e.size/U*(e.flipX?-1:1),", ").concat(e.size/U*(e.flipY?-1:1),") "),s+="rotate(".concat(e.rotate,"deg) "),s}var Vr=`:root, :host {
  --fa-font-solid: normal 900 1em/1 'Font Awesome 7 Free';
  --fa-font-regular: normal 400 1em/1 'Font Awesome 7 Free';
  --fa-font-light: normal 300 1em/1 'Font Awesome 7 Pro';
  --fa-font-thin: normal 100 1em/1 'Font Awesome 7 Pro';
  --fa-font-duotone: normal 900 1em/1 'Font Awesome 7 Duotone';
  --fa-font-duotone-regular: normal 400 1em/1 'Font Awesome 7 Duotone';
  --fa-font-duotone-light: normal 300 1em/1 'Font Awesome 7 Duotone';
  --fa-font-duotone-thin: normal 100 1em/1 'Font Awesome 7 Duotone';
  --fa-font-brands: normal 400 1em/1 'Font Awesome 7 Brands';
  --fa-font-sharp-solid: normal 900 1em/1 'Font Awesome 7 Sharp';
  --fa-font-sharp-regular: normal 400 1em/1 'Font Awesome 7 Sharp';
  --fa-font-sharp-light: normal 300 1em/1 'Font Awesome 7 Sharp';
  --fa-font-sharp-thin: normal 100 1em/1 'Font Awesome 7 Sharp';
  --fa-font-sharp-duotone-solid: normal 900 1em/1 'Font Awesome 7 Sharp Duotone';
  --fa-font-sharp-duotone-regular: normal 400 1em/1 'Font Awesome 7 Sharp Duotone';
  --fa-font-sharp-duotone-light: normal 300 1em/1 'Font Awesome 7 Sharp Duotone';
  --fa-font-sharp-duotone-thin: normal 100 1em/1 'Font Awesome 7 Sharp Duotone';
  --fa-font-slab-regular: normal 400 1em/1 'Font Awesome 7 Slab';
  --fa-font-slab-press-regular: normal 400 1em/1 'Font Awesome 7 Slab Press';
  --fa-font-whiteboard-semibold: normal 600 1em/1 'Font Awesome 7 Whiteboard';
  --fa-font-thumbprint-light: normal 300 1em/1 'Font Awesome 7 Thumbprint';
  --fa-font-notdog-solid: normal 900 1em/1 'Font Awesome 7 Notdog';
  --fa-font-notdog-duo-solid: normal 900 1em/1 'Font Awesome 7 Notdog Duo';
  --fa-font-etch-solid: normal 900 1em/1 'Font Awesome 7 Etch';
  --fa-font-graphite-thin: normal 100 1em/1 'Font Awesome 7 Graphite';
  --fa-font-jelly-regular: normal 400 1em/1 'Font Awesome 7 Jelly';
  --fa-font-jelly-fill-regular: normal 400 1em/1 'Font Awesome 7 Jelly Fill';
  --fa-font-jelly-duo-regular: normal 400 1em/1 'Font Awesome 7 Jelly Duo';
  --fa-font-chisel-regular: normal 400 1em/1 'Font Awesome 7 Chisel';
  --fa-font-utility-semibold: normal 600 1em/1 'Font Awesome 7 Utility';
  --fa-font-utility-duo-semibold: normal 600 1em/1 'Font Awesome 7 Utility Duo';
  --fa-font-utility-fill-semibold: normal 600 1em/1 'Font Awesome 7 Utility Fill';
}

.svg-inline--fa {
  box-sizing: content-box;
  display: var(--fa-display, inline-block);
  height: 1em;
  overflow: visible;
  vertical-align: -0.125em;
  width: var(--fa-width, 1.25em);
}
.svg-inline--fa.fa-2xs {
  vertical-align: 0.1em;
}
.svg-inline--fa.fa-xs {
  vertical-align: 0em;
}
.svg-inline--fa.fa-sm {
  vertical-align: -0.0714285714em;
}
.svg-inline--fa.fa-lg {
  vertical-align: -0.2em;
}
.svg-inline--fa.fa-xl {
  vertical-align: -0.25em;
}
.svg-inline--fa.fa-2xl {
  vertical-align: -0.3125em;
}
.svg-inline--fa.fa-pull-left,
.svg-inline--fa .fa-pull-start {
  float: inline-start;
  margin-inline-end: var(--fa-pull-margin, 0.3em);
}
.svg-inline--fa.fa-pull-right,
.svg-inline--fa .fa-pull-end {
  float: inline-end;
  margin-inline-start: var(--fa-pull-margin, 0.3em);
}
.svg-inline--fa.fa-li {
  width: var(--fa-li-width, 2em);
  inset-inline-start: calc(-1 * var(--fa-li-width, 2em));
  inset-block-start: 0.25em; /* syncing vertical alignment with Web Font rendering */
}

.fa-layers-counter, .fa-layers-text {
  display: inline-block;
  position: absolute;
  text-align: center;
}

.fa-layers {
  display: inline-block;
  height: 1em;
  position: relative;
  text-align: center;
  vertical-align: -0.125em;
  width: var(--fa-width, 1.25em);
}
.fa-layers .svg-inline--fa {
  inset: 0;
  margin: auto;
  position: absolute;
  transform-origin: center center;
}

.fa-layers-text {
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  transform-origin: center center;
}

.fa-layers-counter {
  background-color: var(--fa-counter-background-color, #ff253a);
  border-radius: var(--fa-counter-border-radius, 1em);
  box-sizing: border-box;
  color: var(--fa-inverse, #fff);
  line-height: var(--fa-counter-line-height, 1);
  max-width: var(--fa-counter-max-width, 5em);
  min-width: var(--fa-counter-min-width, 1.5em);
  overflow: hidden;
  padding: var(--fa-counter-padding, 0.25em 0.5em);
  right: var(--fa-right, 0);
  text-overflow: ellipsis;
  top: var(--fa-top, 0);
  transform: scale(var(--fa-counter-scale, 0.25));
  transform-origin: top right;
}

.fa-layers-bottom-right {
  bottom: var(--fa-bottom, 0);
  right: var(--fa-right, 0);
  top: auto;
  transform: scale(var(--fa-layers-scale, 0.25));
  transform-origin: bottom right;
}

.fa-layers-bottom-left {
  bottom: var(--fa-bottom, 0);
  left: var(--fa-left, 0);
  right: auto;
  top: auto;
  transform: scale(var(--fa-layers-scale, 0.25));
  transform-origin: bottom left;
}

.fa-layers-top-right {
  top: var(--fa-top, 0);
  right: var(--fa-right, 0);
  transform: scale(var(--fa-layers-scale, 0.25));
  transform-origin: top right;
}

.fa-layers-top-left {
  left: var(--fa-left, 0);
  right: auto;
  top: var(--fa-top, 0);
  transform: scale(var(--fa-layers-scale, 0.25));
  transform-origin: top left;
}

.fa-1x {
  font-size: 1em;
}

.fa-2x {
  font-size: 2em;
}

.fa-3x {
  font-size: 3em;
}

.fa-4x {
  font-size: 4em;
}

.fa-5x {
  font-size: 5em;
}

.fa-6x {
  font-size: 6em;
}

.fa-7x {
  font-size: 7em;
}

.fa-8x {
  font-size: 8em;
}

.fa-9x {
  font-size: 9em;
}

.fa-10x {
  font-size: 10em;
}

.fa-2xs {
  font-size: calc(10 / 16 * 1em); /* converts a 10px size into an em-based value that's relative to the scale's 16px base */
  line-height: calc(1 / 10 * 1em); /* sets the line-height of the icon back to that of it's parent */
  vertical-align: calc((6 / 10 - 0.375) * 1em); /* vertically centers the icon taking into account the surrounding text's descender */
}

.fa-xs {
  font-size: calc(12 / 16 * 1em); /* converts a 12px size into an em-based value that's relative to the scale's 16px base */
  line-height: calc(1 / 12 * 1em); /* sets the line-height of the icon back to that of it's parent */
  vertical-align: calc((6 / 12 - 0.375) * 1em); /* vertically centers the icon taking into account the surrounding text's descender */
}

.fa-sm {
  font-size: calc(14 / 16 * 1em); /* converts a 14px size into an em-based value that's relative to the scale's 16px base */
  line-height: calc(1 / 14 * 1em); /* sets the line-height of the icon back to that of it's parent */
  vertical-align: calc((6 / 14 - 0.375) * 1em); /* vertically centers the icon taking into account the surrounding text's descender */
}

.fa-lg {
  font-size: calc(20 / 16 * 1em); /* converts a 20px size into an em-based value that's relative to the scale's 16px base */
  line-height: calc(1 / 20 * 1em); /* sets the line-height of the icon back to that of it's parent */
  vertical-align: calc((6 / 20 - 0.375) * 1em); /* vertically centers the icon taking into account the surrounding text's descender */
}

.fa-xl {
  font-size: calc(24 / 16 * 1em); /* converts a 24px size into an em-based value that's relative to the scale's 16px base */
  line-height: calc(1 / 24 * 1em); /* sets the line-height of the icon back to that of it's parent */
  vertical-align: calc((6 / 24 - 0.375) * 1em); /* vertically centers the icon taking into account the surrounding text's descender */
}

.fa-2xl {
  font-size: calc(32 / 16 * 1em); /* converts a 32px size into an em-based value that's relative to the scale's 16px base */
  line-height: calc(1 / 32 * 1em); /* sets the line-height of the icon back to that of it's parent */
  vertical-align: calc((6 / 32 - 0.375) * 1em); /* vertically centers the icon taking into account the surrounding text's descender */
}

.fa-width-auto {
  --fa-width: auto;
}

.fa-fw,
.fa-width-fixed {
  --fa-width: 1.25em;
}

.fa-ul {
  list-style-type: none;
  margin-inline-start: var(--fa-li-margin, 2.5em);
  padding-inline-start: 0;
}
.fa-ul > li {
  position: relative;
}

.fa-li {
  inset-inline-start: calc(-1 * var(--fa-li-width, 2em));
  position: absolute;
  text-align: center;
  width: var(--fa-li-width, 2em);
  line-height: inherit;
}

/* Heads Up: Bordered Icons will not be supported in the future!
  - This feature will be deprecated in the next major release of Font Awesome (v8)!
  - You may continue to use it in this version *v7), but it will not be supported in Font Awesome v8.
*/
/* Notes:
* --@{v.$css-prefix}-border-width = 1/16 by default (to render as ~1px based on a 16px default font-size)
* --@{v.$css-prefix}-border-padding =
  ** 3/16 for vertical padding (to give ~2px of vertical whitespace around an icon considering it's vertical alignment)
  ** 4/16 for horizontal padding (to give ~4px of horizontal whitespace around an icon)
*/
.fa-border {
  border-color: var(--fa-border-color, #eee);
  border-radius: var(--fa-border-radius, 0.1em);
  border-style: var(--fa-border-style, solid);
  border-width: var(--fa-border-width, 0.0625em);
  box-sizing: var(--fa-border-box-sizing, content-box);
  padding: var(--fa-border-padding, 0.1875em 0.25em);
}

.fa-pull-left,
.fa-pull-start {
  float: inline-start;
  margin-inline-end: var(--fa-pull-margin, 0.3em);
}

.fa-pull-right,
.fa-pull-end {
  float: inline-end;
  margin-inline-start: var(--fa-pull-margin, 0.3em);
}

.fa-beat {
  animation-name: fa-beat;
  animation-delay: var(--fa-animation-delay, 0s);
  animation-direction: var(--fa-animation-direction, normal);
  animation-duration: var(--fa-animation-duration, 1s);
  animation-iteration-count: var(--fa-animation-iteration-count, infinite);
  animation-timing-function: var(--fa-animation-timing, ease-in-out);
}

.fa-bounce {
  animation-name: fa-bounce;
  animation-delay: var(--fa-animation-delay, 0s);
  animation-direction: var(--fa-animation-direction, normal);
  animation-duration: var(--fa-animation-duration, 1s);
  animation-iteration-count: var(--fa-animation-iteration-count, infinite);
  animation-timing-function: var(--fa-animation-timing, cubic-bezier(0.28, 0.84, 0.42, 1));
}

.fa-fade {
  animation-name: fa-fade;
  animation-delay: var(--fa-animation-delay, 0s);
  animation-direction: var(--fa-animation-direction, normal);
  animation-duration: var(--fa-animation-duration, 1s);
  animation-iteration-count: var(--fa-animation-iteration-count, infinite);
  animation-timing-function: var(--fa-animation-timing, cubic-bezier(0.4, 0, 0.6, 1));
}

.fa-beat-fade {
  animation-name: fa-beat-fade;
  animation-delay: var(--fa-animation-delay, 0s);
  animation-direction: var(--fa-animation-direction, normal);
  animation-duration: var(--fa-animation-duration, 1s);
  animation-iteration-count: var(--fa-animation-iteration-count, infinite);
  animation-timing-function: var(--fa-animation-timing, cubic-bezier(0.4, 0, 0.6, 1));
}

.fa-flip {
  animation-name: fa-flip;
  animation-delay: var(--fa-animation-delay, 0s);
  animation-direction: var(--fa-animation-direction, normal);
  animation-duration: var(--fa-animation-duration, 1s);
  animation-iteration-count: var(--fa-animation-iteration-count, infinite);
  animation-timing-function: var(--fa-animation-timing, ease-in-out);
}

.fa-shake {
  animation-name: fa-shake;
  animation-delay: var(--fa-animation-delay, 0s);
  animation-direction: var(--fa-animation-direction, normal);
  animation-duration: var(--fa-animation-duration, 1s);
  animation-iteration-count: var(--fa-animation-iteration-count, infinite);
  animation-timing-function: var(--fa-animation-timing, linear);
}

.fa-spin {
  animation-name: fa-spin;
  animation-delay: var(--fa-animation-delay, 0s);
  animation-direction: var(--fa-animation-direction, normal);
  animation-duration: var(--fa-animation-duration, 2s);
  animation-iteration-count: var(--fa-animation-iteration-count, infinite);
  animation-timing-function: var(--fa-animation-timing, linear);
}

.fa-spin-reverse {
  --fa-animation-direction: reverse;
}

.fa-pulse,
.fa-spin-pulse {
  animation-name: fa-spin;
  animation-direction: var(--fa-animation-direction, normal);
  animation-duration: var(--fa-animation-duration, 1s);
  animation-iteration-count: var(--fa-animation-iteration-count, infinite);
  animation-timing-function: var(--fa-animation-timing, steps(8));
}

@media (prefers-reduced-motion: reduce) {
  .fa-beat,
  .fa-bounce,
  .fa-fade,
  .fa-beat-fade,
  .fa-flip,
  .fa-pulse,
  .fa-shake,
  .fa-spin,
  .fa-spin-pulse {
    animation: none !important;
    transition: none !important;
  }
}
@keyframes fa-beat {
  0%, 90% {
    transform: scale(1);
  }
  45% {
    transform: scale(var(--fa-beat-scale, 1.25));
  }
}
@keyframes fa-bounce {
  0% {
    transform: scale(1, 1) translateY(0);
  }
  10% {
    transform: scale(var(--fa-bounce-start-scale-x, 1.1), var(--fa-bounce-start-scale-y, 0.9)) translateY(0);
  }
  30% {
    transform: scale(var(--fa-bounce-jump-scale-x, 0.9), var(--fa-bounce-jump-scale-y, 1.1)) translateY(var(--fa-bounce-height, -0.5em));
  }
  50% {
    transform: scale(var(--fa-bounce-land-scale-x, 1.05), var(--fa-bounce-land-scale-y, 0.95)) translateY(0);
  }
  57% {
    transform: scale(1, 1) translateY(var(--fa-bounce-rebound, -0.125em));
  }
  64% {
    transform: scale(1, 1) translateY(0);
  }
  100% {
    transform: scale(1, 1) translateY(0);
  }
}
@keyframes fa-fade {
  50% {
    opacity: var(--fa-fade-opacity, 0.4);
  }
}
@keyframes fa-beat-fade {
  0%, 100% {
    opacity: var(--fa-beat-fade-opacity, 0.4);
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(var(--fa-beat-fade-scale, 1.125));
  }
}
@keyframes fa-flip {
  50% {
    transform: rotate3d(var(--fa-flip-x, 0), var(--fa-flip-y, 1), var(--fa-flip-z, 0), var(--fa-flip-angle, -180deg));
  }
}
@keyframes fa-shake {
  0% {
    transform: rotate(-15deg);
  }
  4% {
    transform: rotate(15deg);
  }
  8%, 24% {
    transform: rotate(-18deg);
  }
  12%, 28% {
    transform: rotate(18deg);
  }
  16% {
    transform: rotate(-22deg);
  }
  20% {
    transform: rotate(22deg);
  }
  32% {
    transform: rotate(-12deg);
  }
  36% {
    transform: rotate(12deg);
  }
  40%, 100% {
    transform: rotate(0deg);
  }
}
@keyframes fa-spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
.fa-rotate-90 {
  transform: rotate(90deg);
}

.fa-rotate-180 {
  transform: rotate(180deg);
}

.fa-rotate-270 {
  transform: rotate(270deg);
}

.fa-flip-horizontal {
  transform: scale(-1, 1);
}

.fa-flip-vertical {
  transform: scale(1, -1);
}

.fa-flip-both,
.fa-flip-horizontal.fa-flip-vertical {
  transform: scale(-1, -1);
}

.fa-rotate-by {
  transform: rotate(var(--fa-rotate-angle, 0));
}

.svg-inline--fa .fa-primary {
  fill: var(--fa-primary-color, currentColor);
  opacity: var(--fa-primary-opacity, 1);
}

.svg-inline--fa .fa-secondary {
  fill: var(--fa-secondary-color, currentColor);
  opacity: var(--fa-secondary-opacity, 0.4);
}

.svg-inline--fa.fa-swap-opacity .fa-primary {
  opacity: var(--fa-secondary-opacity, 0.4);
}

.svg-inline--fa.fa-swap-opacity .fa-secondary {
  opacity: var(--fa-primary-opacity, 1);
}

.svg-inline--fa mask .fa-primary,
.svg-inline--fa mask .fa-secondary {
  fill: black;
}

.svg-inline--fa.fa-inverse {
  fill: var(--fa-inverse, #fff);
}

.fa-stack {
  display: inline-block;
  height: 2em;
  line-height: 2em;
  position: relative;
  vertical-align: middle;
  width: 2.5em;
}

.fa-inverse {
  color: var(--fa-inverse, #fff);
}

.svg-inline--fa.fa-stack-1x {
  --fa-width: 1.25em;
  height: 1em;
  width: var(--fa-width);
}
.svg-inline--fa.fa-stack-2x {
  --fa-width: 2.5em;
  height: 2em;
  width: var(--fa-width);
}

.fa-stack-1x,
.fa-stack-2x {
  inset: 0;
  margin: auto;
  position: absolute;
  z-index: var(--fa-stack-z-index, auto);
}`;function vt(){var a=st,e=ot,t=m.cssPrefix,n=m.replacementClass,r=Vr;if(t!==a||n!==e){var i=new RegExp("\\.".concat(a,"\\-"),"g"),s=new RegExp("\\--".concat(a,"\\-"),"g"),o=new RegExp("\\.".concat(e),"g");r=r.replace(i,".".concat(t,"-")).replace(s,"--".concat(t,"-")).replace(o,".".concat(n))}return r}var fe=!1;function Sa(){m.autoAddCss&&!fe&&(Yr(vt()),fe=!0)}var qr={mixout:function(){return{dom:{css:vt,insertCss:Sa}}},hooks:function(){return{beforeDOMElementCreation:function(){Sa()},beforeI2svg:function(){Sa()}}}},O=_||{};O[F]||(O[F]={});O[F].styles||(O[F].styles={});O[F].hooks||(O[F].hooks={});O[F].shims||(O[F].shims=[]);var M=O[F],ht=[],pt=function(){x.removeEventListener("DOMContentLoaded",pt),ca=1,ht.map(function(e){return e()})},ca=!1;T&&(ca=(x.documentElement.doScroll?/^loaded|^c/:/^loaded|^i|^c/).test(x.readyState),ca||x.addEventListener("DOMContentLoaded",pt));function Kr(a){T&&(ca?setTimeout(a,0):ht.push(a))}function ta(a){var e=a.tag,t=a.attributes,n=t===void 0?{}:t,r=a.children,i=r===void 0?[]:r;return typeof a=="string"?mt(a):"<".concat(e," ").concat(Gr(n),">").concat(i.map(ta).join(""),"</").concat(e,">")}function ce(a,e,t){if(a&&a[e]&&a[e][t])return{prefix:e,iconName:t,icon:a[e][t]}}var wa=function(e,t,n,r){var i=Object.keys(e),s=i.length,o=t,l,c,d;for(n===void 0?(l=1,d=e[i[0]]):(l=0,d=n);l<s;l++)c=i[l],d=o(d,e[c],c,e);return d};function gt(a){return I(a).length!==1?null:a.codePointAt(0).toString(16)}function ue(a){return Object.keys(a).reduce(function(e,t){var n=a[t],r=!!n.icon;return r?e[n.iconName]=n.icon:e[t]=n,e},{})}function Na(a,e){var t=arguments.length>2&&arguments[2]!==void 0?arguments[2]:{},n=t.skipHooks,r=n===void 0?!1:n,i=ue(e);typeof M.hooks.addPack=="function"&&!r?M.hooks.addPack(a,ue(e)):M.styles[a]=f(f({},M.styles[a]||{}),i),a==="fas"&&Na("fa",e)}var Z=M.styles,Jr=M.shims,bt=Object.keys(Ga),Qr=bt.reduce(function(a,e){return a[e]=Object.keys(Ga[e]),a},{}),Va=null,yt={},xt={},St={},wt={},At={};function Zr(a){return~Rr.indexOf(a)}function ai(a,e){var t=e.split("-"),n=t[0],r=t.slice(1).join("-");return n===a&&r!==""&&!Zr(r)?r:null}var kt=function(){var e=function(i){return wa(Z,function(s,o,l){return s[l]=wa(o,i,{}),s},{})};yt=e(function(r,i,s){if(i[3]&&(r[i[3]]=s),i[2]){var o=i[2].filter(function(l){return typeof l=="number"});o.forEach(function(l){r[l.toString(16)]=s})}return r}),xt=e(function(r,i,s){if(r[s]=s,i[2]){var o=i[2].filter(function(l){return typeof l=="string"});o.forEach(function(l){r[l]=s})}return r}),At=e(function(r,i,s){var o=i[2];return r[s]=s,o.forEach(function(l){r[l]=s}),r});var t="far"in Z||m.autoFetchSvg,n=wa(Jr,function(r,i){var s=i[0],o=i[1],l=i[2];return o==="far"&&!t&&(o="fas"),typeof s=="string"&&(r.names[s]={prefix:o,iconName:l}),typeof s=="number"&&(r.unicodes[s.toString(16)]={prefix:o,iconName:l}),r},{names:{},unicodes:{}});St=n.names,wt=n.unicodes,Va=va(m.styleDefault,{family:m.familyDefault})};Ur(function(a){Va=va(a.styleDefault,{family:m.familyDefault})});kt();function qa(a,e){return(yt[a]||{})[e]}function ei(a,e){return(xt[a]||{})[e]}function $(a,e){return(At[a]||{})[e]}function Lt(a){return St[a]||{prefix:null,iconName:null}}function ti(a){var e=wt[a],t=qa("fas",a);return e||(t?{prefix:"fas",iconName:t}:null)||{prefix:null,iconName:null}}function j(){return Va}var zt=function(){return{prefix:null,iconName:null,rest:[]}};function ni(a){var e=L,t=bt.reduce(function(n,r){return n[r]="".concat(m.cssPrefix,"-").concat(r),n},{});return tt.forEach(function(n){(a.includes(t[n])||a.some(function(r){return Qr[n].includes(r)}))&&(e=n)}),e}function va(a){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},t=e.family,n=t===void 0?L:t,r=Fr[n][a];if(n===aa&&!a)return"fad";var i=oe[n][a]||oe[n][r],s=a in M.styles?a:null,o=i||s||null;return o}function ri(a){var e=[],t=null;return a.forEach(function(n){var r=ai(m.cssPrefix,n);r?t=r:n&&e.push(n)}),{iconName:t,rest:e}}function de(a){return a.sort().filter(function(e,t,n){return n.indexOf(e)===t})}var me=rt.concat(nt);function ha(a){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},t=e.skipLookups,n=t===void 0?!1:t,r=null,i=de(a.filter(function(h){return me.includes(h)})),s=de(a.filter(function(h){return!me.includes(h)})),o=i.filter(function(h){return r=h,!Re.includes(h)}),l=da(o,1),c=l[0],d=c===void 0?null:c,u=ni(i),v=f(f({},ri(s)),{},{prefix:va(d,{family:u})});return f(f(f({},v),li({values:a,family:u,styles:Z,config:m,canonical:v,givenPrefix:r})),ii(n,r,v))}function ii(a,e,t){var n=t.prefix,r=t.iconName;if(a||!n||!r)return{prefix:n,iconName:r};var i=e==="fa"?Lt(r):{},s=$(n,r);return r=i.iconName||s||r,n=i.prefix||n,n==="far"&&!Z.far&&Z.fas&&!m.autoFetchSvg&&(n="fas"),{prefix:n,iconName:r}}var si=tt.filter(function(a){return a!==L||a!==aa}),oi=Object.keys(za).filter(function(a){return a!==L}).map(function(a){return Object.keys(za[a])}).flat();function li(a){var e=a.values,t=a.family,n=a.canonical,r=a.givenPrefix,i=r===void 0?"":r,s=a.styles,o=s===void 0?{}:s,l=a.config,c=l===void 0?{}:l,d=t===aa,u=e.includes("fa-duotone")||e.includes("fad"),v=c.familyDefault==="duotone",h=n.prefix==="fad"||n.prefix==="fa-duotone";if(!d&&(u||v||h)&&(n.prefix="fad"),(e.includes("fa-brands")||e.includes("fab"))&&(n.prefix="fab"),!n.prefix&&si.includes(t)){var y=Object.keys(o).find(function(S){return oi.includes(S)});if(y||c.autoFetchSvg){var b=An.get(t).defaultShortPrefixId;n.prefix=b,n.iconName=$(n.prefix,n.iconName)||n.iconName}}return(n.prefix==="fa"||i==="fa")&&(n.prefix=j()||"fas"),n}var fi=(function(){function a(){Yt(this,a),this.definitions={}}return Gt(a,[{key:"add",value:function(){for(var t=this,n=arguments.length,r=new Array(n),i=0;i<n;i++)r[i]=arguments[i];var s=r.reduce(this._pullDefinitions,{});Object.keys(s).forEach(function(o){t.definitions[o]=f(f({},t.definitions[o]||{}),s[o]),Na(o,s[o]);var l=Ga[L][o];l&&Na(l,s[o]),kt()})}},{key:"reset",value:function(){this.definitions={}}},{key:"_pullDefinitions",value:function(t,n){var r=n.prefix&&n.iconName&&n.icon?{0:n}:n;return Object.keys(r).map(function(i){var s=r[i],o=s.prefix,l=s.iconName,c=s.icon,d=c[2];t[o]||(t[o]={}),d.length>0&&d.forEach(function(u){typeof u=="string"&&(t[o][u]=c)}),t[o][l]=c}),t}}])})(),ve=[],H={},G={},ci=Object.keys(G);function ui(a,e){var t=e.mixoutsTo;return ve=a,H={},Object.keys(G).forEach(function(n){ci.indexOf(n)===-1&&delete G[n]}),ve.forEach(function(n){var r=n.mixout?n.mixout():{};if(Object.keys(r).forEach(function(s){typeof r[s]=="function"&&(t[s]=r[s]),fa(r[s])==="object"&&Object.keys(r[s]).forEach(function(o){t[s]||(t[s]={}),t[s][o]=r[s][o]})}),n.hooks){var i=n.hooks();Object.keys(i).forEach(function(s){H[s]||(H[s]=[]),H[s].push(i[s])})}n.provides&&n.provides(G)}),t}function Fa(a,e){for(var t=arguments.length,n=new Array(t>2?t-2:0),r=2;r<t;r++)n[r-2]=arguments[r];var i=H[a]||[];return i.forEach(function(s){e=s.apply(null,[e].concat(n))}),e}function W(a){for(var e=arguments.length,t=new Array(e>1?e-1:0),n=1;n<e;n++)t[n-1]=arguments[n];var r=H[a]||[];r.forEach(function(i){i.apply(null,t)})}function R(){var a=arguments[0],e=Array.prototype.slice.call(arguments,1);return G[a]?G[a].apply(null,e):void 0}function Oa(a){a.prefix==="fa"&&(a.prefix="fas");var e=a.iconName,t=a.prefix||j();if(e)return e=$(t,e)||e,ce(Mt.definitions,t,e)||ce(M.styles,t,e)}var Mt=new fi,di=function(){m.autoReplaceSvg=!1,m.observeMutations=!1,W("noAuto")},mi={i2svg:function(){var e=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};return T?(W("beforeI2svg",e),R("pseudoElements2svg",e),R("i2svg",e)):Promise.reject(new Error("Operation requires a DOM of some kind."))},watch:function(){var e=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{},t=e.autoReplaceSvgRoot;m.autoReplaceSvg===!1&&(m.autoReplaceSvg=!0),m.observeMutations=!0,Kr(function(){hi({autoReplaceSvgRoot:t}),W("watch",e)})}},vi={icon:function(e){if(e===null)return null;if(fa(e)==="object"&&e.prefix&&e.iconName)return{prefix:e.prefix,iconName:$(e.prefix,e.iconName)||e.iconName};if(Array.isArray(e)&&e.length===2){var t=e[1].indexOf("fa-")===0?e[1].slice(3):e[1],n=va(e[0]);return{prefix:n,iconName:$(n,t)||t}}if(typeof e=="string"&&(e.indexOf("".concat(m.cssPrefix,"-"))>-1||e.match(Or))){var r=ha(e.split(" "),{skipLookups:!0});return{prefix:r.prefix||j(),iconName:$(r.prefix,r.iconName)||r.iconName}}if(typeof e=="string"){var i=j();return{prefix:i,iconName:$(i,e)||e}}}},z={noAuto:di,config:m,dom:mi,parse:vi,library:Mt,findIconDefinition:Oa,toHtml:ta},hi=function(){var e=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{},t=e.autoReplaceSvgRoot,n=t===void 0?x:t;(Object.keys(M.styles).length>0||m.autoFetchSvg)&&T&&m.autoReplaceSvg&&z.dom.i2svg({node:n})};function pa(a,e){return Object.defineProperty(a,"abstract",{get:e}),Object.defineProperty(a,"html",{get:function(){return a.abstract.map(function(n){return ta(n)})}}),Object.defineProperty(a,"node",{get:function(){if(T){var n=x.createElement("div");return n.innerHTML=a.html,n.children}}}),a}function pi(a){var e=a.children,t=a.main,n=a.mask,r=a.attributes,i=a.styles,s=a.transform;if(Ba(s)&&t.found&&!n.found){var o=t.width,l=t.height,c={x:o/l/2,y:.5};r.style=ma(f(f({},i),{},{"transform-origin":"".concat(c.x+s.x/16,"em ").concat(c.y+s.y/16,"em")}))}return[{tag:"svg",attributes:r,children:e}]}function gi(a){var e=a.prefix,t=a.iconName,n=a.children,r=a.attributes,i=a.symbol,s=i===!0?"".concat(e,"-").concat(m.cssPrefix,"-").concat(t):i;return[{tag:"svg",attributes:{style:"display: none;"},children:[{tag:"symbol",attributes:f(f({},r),{},{id:s}),children:n}]}]}function bi(a){var e=["aria-label","aria-labelledby","title","role"];return e.some(function(t){return t in a})}function Ka(a){var e=a.icons,t=e.main,n=e.mask,r=a.prefix,i=a.iconName,s=a.transform,o=a.symbol,l=a.maskId,c=a.extra,d=a.watchable,u=d===void 0?!1:d,v=n.found?n:t,h=v.width,y=v.height,b=[m.replacementClass,i?"".concat(m.cssPrefix,"-").concat(i):""].filter(function(g){return c.classes.indexOf(g)===-1}).filter(function(g){return g!==""||!!g}).concat(c.classes).join(" "),S={children:[],attributes:f(f({},c.attributes),{},{"data-prefix":r,"data-icon":i,class:b,role:c.attributes.role||"img",viewBox:"0 0 ".concat(h," ").concat(y)})};!bi(c.attributes)&&!c.attributes["aria-hidden"]&&(S.attributes["aria-hidden"]="true"),u&&(S.attributes[D]="");var w=f(f({},S),{},{prefix:r,iconName:i,main:t,mask:n,maskId:l,transform:s,symbol:o,styles:f({},c.styles)}),A=n.found&&t.found?R("generateAbstractMask",w)||{children:[],attributes:{}}:R("generateAbstractIcon",w)||{children:[],attributes:{}},k=A.children,P=A.attributes;return w.children=k,w.attributes=P,o?gi(w):pi(w)}function he(a){var e=a.content,t=a.width,n=a.height,r=a.transform,i=a.extra,s=a.watchable,o=s===void 0?!1:s,l=f(f({},i.attributes),{},{class:i.classes.join(" ")});o&&(l[D]="");var c=f({},i.styles);Ba(r)&&(c.transform=Br({transform:r,width:t,height:n}),c["-webkit-transform"]=c.transform);var d=ma(c);d.length>0&&(l.style=d);var u=[];return u.push({tag:"span",attributes:l,children:[e]}),u}function yi(a){var e=a.content,t=a.extra,n=f(f({},t.attributes),{},{class:t.classes.join(" ")}),r=ma(t.styles);r.length>0&&(n.style=r);var i=[];return i.push({tag:"span",attributes:n,children:[e]}),i}var Aa=M.styles;function Ta(a){var e=a[0],t=a[1],n=a.slice(4),r=da(n,1),i=r[0],s=null;return Array.isArray(i)?s={tag:"g",attributes:{class:"".concat(m.cssPrefix,"-").concat(xa.GROUP)},children:[{tag:"path",attributes:{class:"".concat(m.cssPrefix,"-").concat(xa.SECONDARY),fill:"currentColor",d:i[0]}},{tag:"path",attributes:{class:"".concat(m.cssPrefix,"-").concat(xa.PRIMARY),fill:"currentColor",d:i[1]}}]}:s={tag:"path",attributes:{fill:"currentColor",d:i}},{found:!0,width:e,height:t,icon:s}}var xi={found:!1,width:512,height:512};function Si(a,e){!ft&&!m.showMissingIcons&&a&&console.error('Icon with name "'.concat(a,'" and prefix "').concat(e,'" is missing.'))}function _a(a,e){var t=e;return e==="fa"&&m.styleDefault!==null&&(e=j()),new Promise(function(n,r){if(t==="fa"){var i=Lt(a)||{};a=i.iconName||a,e=i.prefix||e}if(a&&e&&Aa[e]&&Aa[e][a]){var s=Aa[e][a];return n(Ta(s))}Si(a,e),n(f(f({},xi),{},{icon:m.showMissingIcons&&a?R("missingIconAbstract")||{}:{}}))})}var pe=function(){},ja=m.measurePerformance&&ra&&ra.mark&&ra.measure?ra:{mark:pe,measure:pe},q='FA "7.2.0"',wi=function(e){return ja.mark("".concat(q," ").concat(e," begins")),function(){return It(e)}},It=function(e){ja.mark("".concat(q," ").concat(e," ends")),ja.measure("".concat(q," ").concat(e),"".concat(q," ").concat(e," begins"),"".concat(q," ").concat(e," ends"))},Ja={begin:wi,end:It},oa=function(){};function ge(a){var e=a.getAttribute?a.getAttribute(D):null;return typeof e=="string"}function Ai(a){var e=a.getAttribute?a.getAttribute(Ya):null,t=a.getAttribute?a.getAttribute(Ha):null;return e&&t}function ki(a){return a&&a.classList&&a.classList.contains&&a.classList.contains(m.replacementClass)}function Li(){if(m.autoReplaceSvg===!0)return la.replace;var a=la[m.autoReplaceSvg];return a||la.replace}function zi(a){return x.createElementNS("http://www.w3.org/2000/svg",a)}function Mi(a){return x.createElement(a)}function Ct(a){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},t=e.ceFn,n=t===void 0?a.tag==="svg"?zi:Mi:t;if(typeof a=="string")return x.createTextNode(a);var r=n(a.tag);Object.keys(a.attributes||[]).forEach(function(s){r.setAttribute(s,a.attributes[s])});var i=a.children||[];return i.forEach(function(s){r.appendChild(Ct(s,{ceFn:n}))}),r}function Ii(a){var e=" ".concat(a.outerHTML," ");return e="".concat(e,"Font Awesome fontawesome.com "),e}var la={replace:function(e){var t=e[0];if(t.parentNode)if(e[1].forEach(function(r){t.parentNode.insertBefore(Ct(r),t)}),t.getAttribute(D)===null&&m.keepOriginalSource){var n=x.createComment(Ii(t));t.parentNode.replaceChild(n,t)}else t.remove()},nest:function(e){var t=e[0],n=e[1];if(~Xa(t).indexOf(m.replacementClass))return la.replace(e);var r=new RegExp("".concat(m.cssPrefix,"-.*"));if(delete n[0].attributes.id,n[0].attributes.class){var i=n[0].attributes.class.split(" ").reduce(function(o,l){return l===m.replacementClass||l.match(r)?o.toSvg.push(l):o.toNode.push(l),o},{toNode:[],toSvg:[]});n[0].attributes.class=i.toSvg.join(" "),i.toNode.length===0?t.removeAttribute("class"):t.setAttribute("class",i.toNode.join(" "))}var s=n.map(function(o){return ta(o)}).join(`
`);t.setAttribute(D,""),t.innerHTML=s}};function be(a){a()}function Pt(a,e){var t=typeof e=="function"?e:oa;if(a.length===0)t();else{var n=be;m.mutateApproach===Er&&(n=_.requestAnimationFrame||be),n(function(){var r=Li(),i=Ja.begin("mutate");a.map(r),i(),t()})}}var Qa=!1;function Et(){Qa=!0}function Ra(){Qa=!1}var ua=null;function ye(a){if(ne&&m.observeMutations){var e=a.treeCallback,t=e===void 0?oa:e,n=a.nodeCallback,r=n===void 0?oa:n,i=a.pseudoElementsCallback,s=i===void 0?oa:i,o=a.observeMutationsRoot,l=o===void 0?x:o;ua=new ne(function(c){if(!Qa){var d=j();V(c).forEach(function(u){if(u.type==="childList"&&u.addedNodes.length>0&&!ge(u.addedNodes[0])&&(m.searchPseudoElements&&s(u.target),t(u.target)),u.type==="attributes"&&u.target.parentNode&&m.searchPseudoElements&&s([u.target],!0),u.type==="attributes"&&ge(u.target)&&~jr.indexOf(u.attributeName))if(u.attributeName==="class"&&Ai(u.target)){var v=ha(Xa(u.target)),h=v.prefix,y=v.iconName;u.target.setAttribute(Ya,h||d),y&&u.target.setAttribute(Ha,y)}else ki(u.target)&&r(u.target)})}}),T&&ua.observe(l,{childList:!0,attributes:!0,characterData:!0,subtree:!0})}}function Ci(){ua&&ua.disconnect()}function Pi(a){var e=a.getAttribute("style"),t=[];return e&&(t=e.split(";").reduce(function(n,r){var i=r.split(":"),s=i[0],o=i.slice(1);return s&&o.length>0&&(n[s]=o.join(":").trim()),n},{})),t}function Ei(a){var e=a.getAttribute("data-prefix"),t=a.getAttribute("data-icon"),n=a.innerText!==void 0?a.innerText.trim():"",r=ha(Xa(a));return r.prefix||(r.prefix=j()),e&&t&&(r.prefix=e,r.iconName=t),r.iconName&&r.prefix||(r.prefix&&n.length>0&&(r.iconName=ei(r.prefix,a.innerText)||qa(r.prefix,gt(a.innerText))),!r.iconName&&m.autoFetchSvg&&a.firstChild&&a.firstChild.nodeType===Node.TEXT_NODE&&(r.iconName=a.firstChild.data)),r}function Ni(a){var e=V(a.attributes).reduce(function(t,n){return t.name!=="class"&&t.name!=="style"&&(t[n.name]=n.value),t},{});return e}function Fi(){return{iconName:null,prefix:null,transform:C,symbol:!1,mask:{iconName:null,prefix:null,rest:[]},maskId:null,extra:{classes:[],styles:{},attributes:{}}}}function xe(a){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{styleParser:!0},t=Ei(a),n=t.iconName,r=t.prefix,i=t.rest,s=Ni(a),o=Fa("parseNodeAttributes",{},a),l=e.styleParser?Pi(a):[];return f({iconName:n,prefix:r,transform:C,mask:{iconName:null,prefix:null,rest:[]},maskId:null,symbol:!1,extra:{classes:i,styles:l,attributes:s}},o)}var Oi=M.styles;function Nt(a){var e=m.autoReplaceSvg==="nest"?xe(a,{styleParser:!1}):xe(a);return~e.extra.classes.indexOf(ut)?R("generateLayersText",a,e):R("generateSvgReplacementMutation",a,e)}function Ti(){return[].concat(I(nt),I(rt))}function Se(a){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:null;if(!T)return Promise.resolve();var t=x.documentElement.classList,n=function(u){return t.add("".concat(se,"-").concat(u))},r=function(u){return t.remove("".concat(se,"-").concat(u))},i=m.autoFetchSvg?Ti():Re.concat(Object.keys(Oi));i.includes("fa")||i.push("fa");var s=[".".concat(ut,":not([").concat(D,"])")].concat(i.map(function(d){return".".concat(d,":not([").concat(D,"])")})).join(", ");if(s.length===0)return Promise.resolve();var o=[];try{o=V(a.querySelectorAll(s))}catch{}if(o.length>0)n("pending"),r("complete");else return Promise.resolve();var l=Ja.begin("onTree"),c=o.reduce(function(d,u){try{var v=Nt(u);v&&d.push(v)}catch(h){ft||h.name==="MissingIcon"&&console.error(h)}return d},[]);return new Promise(function(d,u){Promise.all(c).then(function(v){Pt(v,function(){n("active"),n("complete"),r("pending"),typeof e=="function"&&e(),l(),d()})}).catch(function(v){l(),u(v)})})}function _i(a){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:null;Nt(a).then(function(t){t&&Pt([t],e)})}function ji(a){return function(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},n=(e||{}).icon?e:Oa(e||{}),r=t.mask;return r&&(r=(r||{}).icon?r:Oa(r||{})),a(n,f(f({},t),{},{mask:r}))}}var Ri=function(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},n=t.transform,r=n===void 0?C:n,i=t.symbol,s=i===void 0?!1:i,o=t.mask,l=o===void 0?null:o,c=t.maskId,d=c===void 0?null:c,u=t.classes,v=u===void 0?[]:u,h=t.attributes,y=h===void 0?{}:h,b=t.styles,S=b===void 0?{}:b;if(e){var w=e.prefix,A=e.iconName,k=e.icon;return pa(f({type:"icon"},e),function(){return W("beforeDOMElementCreation",{iconDefinition:e,params:t}),Ka({icons:{main:Ta(k),mask:l?Ta(l.icon):{found:!1,width:null,height:null,icon:{}}},prefix:w,iconName:A,transform:f(f({},C),r),symbol:s,maskId:d,extra:{attributes:y,styles:S,classes:v}})})}},$i={mixout:function(){return{icon:ji(Ri)}},hooks:function(){return{mutationObserverCallbacks:function(t){return t.treeCallback=Se,t.nodeCallback=_i,t}}},provides:function(e){e.i2svg=function(t){var n=t.node,r=n===void 0?x:n,i=t.callback,s=i===void 0?function(){}:i;return Se(r,s)},e.generateSvgReplacementMutation=function(t,n){var r=n.iconName,i=n.prefix,s=n.transform,o=n.symbol,l=n.mask,c=n.maskId,d=n.extra;return new Promise(function(u,v){Promise.all([_a(r,i),l.iconName?_a(l.iconName,l.prefix):Promise.resolve({found:!1,width:512,height:512,icon:{}})]).then(function(h){var y=da(h,2),b=y[0],S=y[1];u([t,Ka({icons:{main:b,mask:S},prefix:i,iconName:r,transform:s,symbol:o,maskId:c,extra:d,watchable:!0})])}).catch(v)})},e.generateAbstractIcon=function(t){var n=t.children,r=t.attributes,i=t.main,s=t.transform,o=t.styles,l=ma(o);l.length>0&&(r.style=l);var c;return Ba(s)&&(c=R("generateAbstractTransformGrouping",{main:i,transform:s,containerWidth:i.width,iconWidth:i.width})),n.push(c||i.icon),{children:n,attributes:r}}}},Di={mixout:function(){return{layer:function(t){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},r=n.classes,i=r===void 0?[]:r;return pa({type:"layer"},function(){W("beforeDOMElementCreation",{assembler:t,params:n});var s=[];return t(function(o){Array.isArray(o)?o.map(function(l){s=s.concat(l.abstract)}):s=s.concat(o.abstract)}),[{tag:"span",attributes:{class:["".concat(m.cssPrefix,"-layers")].concat(I(i)).join(" ")},children:s}]})}}}},Wi={mixout:function(){return{counter:function(t){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{};n.title;var r=n.classes,i=r===void 0?[]:r,s=n.attributes,o=s===void 0?{}:s,l=n.styles,c=l===void 0?{}:l;return pa({type:"counter",content:t},function(){return W("beforeDOMElementCreation",{content:t,params:n}),yi({content:t.toString(),extra:{attributes:o,styles:c,classes:["".concat(m.cssPrefix,"-layers-counter")].concat(I(i))}})})}}}},Ui={mixout:function(){return{text:function(t){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},r=n.transform,i=r===void 0?C:r,s=n.classes,o=s===void 0?[]:s,l=n.attributes,c=l===void 0?{}:l,d=n.styles,u=d===void 0?{}:d;return pa({type:"text",content:t},function(){return W("beforeDOMElementCreation",{content:t,params:n}),he({content:t,transform:f(f({},C),i),extra:{attributes:c,styles:u,classes:["".concat(m.cssPrefix,"-layers-text")].concat(I(o))}})})}}},provides:function(e){e.generateLayersText=function(t,n){var r=n.transform,i=n.extra,s=null,o=null;if(_e){var l=parseInt(getComputedStyle(t).fontSize,10),c=t.getBoundingClientRect();s=c.width/l,o=c.height/l}return Promise.resolve([t,he({content:t.innerHTML,width:s,height:o,transform:r,extra:i,watchable:!0})])}}},Ft=new RegExp('"',"ug"),we=[1105920,1112319],Ae=f(f(f(f({},{FontAwesome:{normal:"fas",400:"fas"}}),wn),Cr),En),$a=Object.keys(Ae).reduce(function(a,e){return a[e.toLowerCase()]=Ae[e],a},{}),Yi=Object.keys($a).reduce(function(a,e){var t=$a[e];return a[e]=t[900]||I(Object.entries(t))[0][1],a},{});function Hi(a){var e=a.replace(Ft,"");return gt(I(e)[0]||"")}function Gi(a){var e=a.getPropertyValue("font-feature-settings").includes("ss01"),t=a.getPropertyValue("content"),n=t.replace(Ft,""),r=n.codePointAt(0),i=r>=we[0]&&r<=we[1],s=n.length===2?n[0]===n[1]:!1;return i||s||e}function Xi(a,e){var t=a.replace(/^['"]|['"]$/g,"").toLowerCase(),n=parseInt(e),r=isNaN(n)?"normal":n;return($a[t]||{})[r]||Yi[t]}function ke(a,e){var t="".concat(Pr).concat(e.replace(":","-"));return new Promise(function(n,r){if(a.getAttribute(t)!==null)return n();var i=V(a.children),s=i.filter(function(na){return na.getAttribute(Ia)===e})[0],o=_.getComputedStyle(a,e),l=o.getPropertyValue("font-family"),c=l.match(Tr),d=o.getPropertyValue("font-weight"),u=o.getPropertyValue("content");if(s&&!c)return a.removeChild(s),n();if(c&&u!=="none"&&u!==""){var v=o.getPropertyValue("content"),h=Xi(l,d),y=Hi(v),b=c[0].startsWith("FontAwesome"),S=Gi(o),w=qa(h,y),A=w;if(b){var k=ti(y);k.iconName&&k.prefix&&(w=k.iconName,h=k.prefix)}if(w&&!S&&(!s||s.getAttribute(Ya)!==h||s.getAttribute(Ha)!==A)){a.setAttribute(t,A),s&&a.removeChild(s);var P=Fi(),g=P.extra;g.attributes[Ia]=e,_a(w,h).then(function(na){var Rt=Ka(f(f({},P),{},{icons:{main:na,mask:zt()},prefix:h,iconName:A,extra:g,watchable:!0})),ga=x.createElementNS("http://www.w3.org/2000/svg","svg");e==="::before"?a.insertBefore(ga,a.firstChild):a.appendChild(ga),ga.outerHTML=Rt.map(function($t){return ta($t)}).join(`
`),a.removeAttribute(t),n()}).catch(r)}else n()}else n()})}function Bi(a){return Promise.all([ke(a,"::before"),ke(a,"::after")])}function Vi(a){return a.parentNode!==document.head&&!~Nr.indexOf(a.tagName.toUpperCase())&&!a.getAttribute(Ia)&&(!a.parentNode||a.parentNode.tagName!=="svg")}var qi=function(e){return!!e&&lt.some(function(t){return e.includes(t)})},Ki=function(e){if(!e)return[];var t=new Set,n=e.split(/,(?![^()]*\))/).map(function(l){return l.trim()});n=n.flatMap(function(l){return l.includes("(")?l:l.split(",").map(function(c){return c.trim()})});var r=sa(n),i;try{for(r.s();!(i=r.n()).done;){var s=i.value;if(qi(s)){var o=lt.reduce(function(l,c){return l.replace(c,"")},s);o!==""&&o!=="*"&&t.add(o)}}}catch(l){r.e(l)}finally{r.f()}return t};function Le(a){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;if(T){var t;if(e)t=a;else if(m.searchPseudoElementsFullScan)t=a.querySelectorAll("*");else{var n=new Set,r=sa(document.styleSheets),i;try{for(r.s();!(i=r.n()).done;){var s=i.value;try{var o=sa(s.cssRules),l;try{for(o.s();!(l=o.n()).done;){var c=l.value,d=Ki(c.selectorText),u=sa(d),v;try{for(u.s();!(v=u.n()).done;){var h=v.value;n.add(h)}}catch(b){u.e(b)}finally{u.f()}}}catch(b){o.e(b)}finally{o.f()}}catch(b){m.searchPseudoElementsWarnings&&console.warn("Font Awesome: cannot parse stylesheet: ".concat(s.href," (").concat(b.message,`)
If it declares any Font Awesome CSS pseudo-elements, they will not be rendered as SVG icons. Add crossorigin="anonymous" to the <link>, enable searchPseudoElementsFullScan for slower but more thorough DOM parsing, or suppress this warning by setting searchPseudoElementsWarnings to false.`))}}}catch(b){r.e(b)}finally{r.f()}if(!n.size)return;var y=Array.from(n).join(", ");try{t=a.querySelectorAll(y)}catch{}}return new Promise(function(b,S){var w=V(t).filter(Vi).map(Bi),A=Ja.begin("searchPseudoElements");Et(),Promise.all(w).then(function(){A(),Ra(),b()}).catch(function(){A(),Ra(),S()})})}}var Ji={hooks:function(){return{mutationObserverCallbacks:function(t){return t.pseudoElementsCallback=Le,t}}},provides:function(e){e.pseudoElements2svg=function(t){var n=t.node,r=n===void 0?x:n;m.searchPseudoElements&&Le(r)}}},ze=!1,Qi={mixout:function(){return{dom:{unwatch:function(){Et(),ze=!0}}}},hooks:function(){return{bootstrap:function(){ye(Fa("mutationObserverCallbacks",{}))},noAuto:function(){Ci()},watch:function(t){var n=t.observeMutationsRoot;ze?Ra():ye(Fa("mutationObserverCallbacks",{observeMutationsRoot:n}))}}}},Me=function(e){var t={size:16,x:0,y:0,flipX:!1,flipY:!1,rotate:0};return e.toLowerCase().split(" ").reduce(function(n,r){var i=r.toLowerCase().split("-"),s=i[0],o=i.slice(1).join("-");if(s&&o==="h")return n.flipX=!0,n;if(s&&o==="v")return n.flipY=!0,n;if(o=parseFloat(o),isNaN(o))return n;switch(s){case"grow":n.size=n.size+o;break;case"shrink":n.size=n.size-o;break;case"left":n.x=n.x-o;break;case"right":n.x=n.x+o;break;case"up":n.y=n.y-o;break;case"down":n.y=n.y+o;break;case"rotate":n.rotate=n.rotate+o;break}return n},t)},Zi={mixout:function(){return{parse:{transform:function(t){return Me(t)}}}},hooks:function(){return{parseNodeAttributes:function(t,n){var r=n.getAttribute("data-fa-transform");return r&&(t.transform=Me(r)),t}}},provides:function(e){e.generateAbstractTransformGrouping=function(t){var n=t.main,r=t.transform,i=t.containerWidth,s=t.iconWidth,o={transform:"translate(".concat(i/2," 256)")},l="translate(".concat(r.x*32,", ").concat(r.y*32,") "),c="scale(".concat(r.size/16*(r.flipX?-1:1),", ").concat(r.size/16*(r.flipY?-1:1),") "),d="rotate(".concat(r.rotate," 0 0)"),u={transform:"".concat(l," ").concat(c," ").concat(d)},v={transform:"translate(".concat(s/2*-1," -256)")},h={outer:o,inner:u,path:v};return{tag:"g",attributes:f({},h.outer),children:[{tag:"g",attributes:f({},h.inner),children:[{tag:n.icon.tag,children:n.icon.children,attributes:f(f({},n.icon.attributes),h.path)}]}]}}}},ka={x:0,y:0,width:"100%",height:"100%"};function Ie(a){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!0;return a.attributes&&(a.attributes.fill||e)&&(a.attributes.fill="black"),a}function as(a){return a.tag==="g"?a.children:[a]}var es={hooks:function(){return{parseNodeAttributes:function(t,n){var r=n.getAttribute("data-fa-mask"),i=r?ha(r.split(" ").map(function(s){return s.trim()})):zt();return i.prefix||(i.prefix=j()),t.mask=i,t.maskId=n.getAttribute("data-fa-mask-id"),t}}},provides:function(e){e.generateAbstractMask=function(t){var n=t.children,r=t.attributes,i=t.main,s=t.mask,o=t.maskId,l=t.transform,c=i.width,d=i.icon,u=s.width,v=s.icon,h=Xr({transform:l,containerWidth:u,iconWidth:c}),y={tag:"rect",attributes:f(f({},ka),{},{fill:"white"})},b=d.children?{children:d.children.map(Ie)}:{},S={tag:"g",attributes:f({},h.inner),children:[Ie(f({tag:d.tag,attributes:f(f({},d.attributes),h.path)},b))]},w={tag:"g",attributes:f({},h.outer),children:[S]},A="mask-".concat(o||le()),k="clip-".concat(o||le()),P={tag:"mask",attributes:f(f({},ka),{},{id:A,maskUnits:"userSpaceOnUse",maskContentUnits:"userSpaceOnUse"}),children:[y,w]},g={tag:"defs",children:[{tag:"clipPath",attributes:{id:k},children:as(v)},P]};return n.push(g,{tag:"rect",attributes:f({fill:"currentColor","clip-path":"url(#".concat(k,")"),mask:"url(#".concat(A,")")},ka)}),{children:n,attributes:r}}}},ts={provides:function(e){var t=!1;_.matchMedia&&(t=_.matchMedia("(prefers-reduced-motion: reduce)").matches),e.missingIconAbstract=function(){var n=[],r={fill:"currentColor"},i={attributeType:"XML",repeatCount:"indefinite",dur:"2s"};n.push({tag:"path",attributes:f(f({},r),{},{d:"M156.5,447.7l-12.6,29.5c-18.7-9.5-35.9-21.2-51.5-34.9l22.7-22.7C127.6,430.5,141.5,440,156.5,447.7z M40.6,272H8.5 c1.4,21.2,5.4,41.7,11.7,61.1L50,321.2C45.1,305.5,41.8,289,40.6,272z M40.6,240c1.4-18.8,5.2-37,11.1-54.1l-29.5-12.6 C14.7,194.3,10,216.7,8.5,240H40.6z M64.3,156.5c7.8-14.9,17.2-28.8,28.1-41.5L69.7,92.3c-13.7,15.6-25.5,32.8-34.9,51.5 L64.3,156.5z M397,419.6c-13.9,12-29.4,22.3-46.1,30.4l11.9,29.8c20.7-9.9,39.8-22.6,56.9-37.6L397,419.6z M115,92.4 c13.9-12,29.4-22.3,46.1-30.4l-11.9-29.8c-20.7,9.9-39.8,22.6-56.8,37.6L115,92.4z M447.7,355.5c-7.8,14.9-17.2,28.8-28.1,41.5 l22.7,22.7c13.7-15.6,25.5-32.9,34.9-51.5L447.7,355.5z M471.4,272c-1.4,18.8-5.2,37-11.1,54.1l29.5,12.6 c7.5-21.1,12.2-43.5,13.6-66.8H471.4z M321.2,462c-15.7,5-32.2,8.2-49.2,9.4v32.1c21.2-1.4,41.7-5.4,61.1-11.7L321.2,462z M240,471.4c-18.8-1.4-37-5.2-54.1-11.1l-12.6,29.5c21.1,7.5,43.5,12.2,66.8,13.6V471.4z M462,190.8c5,15.7,8.2,32.2,9.4,49.2h32.1 c-1.4-21.2-5.4-41.7-11.7-61.1L462,190.8z M92.4,397c-12-13.9-22.3-29.4-30.4-46.1l-29.8,11.9c9.9,20.7,22.6,39.8,37.6,56.9 L92.4,397z M272,40.6c18.8,1.4,36.9,5.2,54.1,11.1l12.6-29.5C317.7,14.7,295.3,10,272,8.5V40.6z M190.8,50 c15.7-5,32.2-8.2,49.2-9.4V8.5c-21.2,1.4-41.7,5.4-61.1,11.7L190.8,50z M442.3,92.3L419.6,115c12,13.9,22.3,29.4,30.5,46.1 l29.8-11.9C470,128.5,457.3,109.4,442.3,92.3z M397,92.4l22.7-22.7c-15.6-13.7-32.8-25.5-51.5-34.9l-12.6,29.5 C370.4,72.1,384.4,81.5,397,92.4z"})});var s=f(f({},i),{},{attributeName:"opacity"}),o={tag:"circle",attributes:f(f({},r),{},{cx:"256",cy:"364",r:"28"}),children:[]};return t||o.children.push({tag:"animate",attributes:f(f({},i),{},{attributeName:"r",values:"28;14;28;28;14;28;"})},{tag:"animate",attributes:f(f({},s),{},{values:"1;0;1;1;0;1;"})}),n.push(o),n.push({tag:"path",attributes:f(f({},r),{},{opacity:"1",d:"M263.7,312h-16c-6.6,0-12-5.4-12-12c0-71,77.4-63.9,77.4-107.8c0-20-17.8-40.2-57.4-40.2c-29.1,0-44.3,9.6-59.2,28.7 c-3.9,5-11.1,6-16.2,2.4l-13.1-9.2c-5.6-3.9-6.9-11.8-2.6-17.2c21.2-27.2,46.4-44.7,91.2-44.7c52.3,0,97.4,29.8,97.4,80.2 c0,67.6-77.4,63.5-77.4,107.8C275.7,306.6,270.3,312,263.7,312z"}),children:t?[]:[{tag:"animate",attributes:f(f({},s),{},{values:"1;0;0;0;0;1;"})}]}),t||n.push({tag:"path",attributes:f(f({},r),{},{opacity:"0",d:"M232.5,134.5l7,168c0.3,6.4,5.6,11.5,12,11.5h9c6.4,0,11.7-5.1,12-11.5l7-168c0.3-6.8-5.2-12.5-12-12.5h-23 C237.7,122,232.2,127.7,232.5,134.5z"}),children:[{tag:"animate",attributes:f(f({},s),{},{values:"0;0;1;1;0;0;"})}]}),{tag:"g",attributes:{class:"missing"},children:n}}}},ns={hooks:function(){return{parseNodeAttributes:function(t,n){var r=n.getAttribute("data-fa-symbol"),i=r===null?!1:r===""?!0:r;return t.symbol=i,t}}}},rs=[qr,$i,Di,Wi,Ui,Ji,Qi,Zi,es,ts,ns];ui(rs,{mixoutsTo:z});z.noAuto;var B=z.config;z.library;z.dom;var Ot=z.parse;z.findIconDefinition;z.toHtml;var is=z.icon;z.layer;z.text;z.counter;function ss(a){return a=a-0,a===a}function Tt(a){return ss(a)?a:(a=a.replace(/[_-]+(.)?/g,(e,t)=>t?t.toUpperCase():""),a.charAt(0).toLowerCase()+a.slice(1))}var os=(a,e)=>Da.createElement("stop",{key:`${e}-${a.offset}`,offset:a.offset,stopColor:a.color,...a.opacity!==void 0&&{stopOpacity:a.opacity}});function ls(a){return a.charAt(0).toUpperCase()+a.slice(1)}var Y=new Map,fs=1e3;function cs(a){if(Y.has(a))return Y.get(a);const e={};let t=0;const n=a.length;for(;t<n;){const r=a.indexOf(";",t),i=r===-1?n:r,s=a.slice(t,i).trim();if(s){const o=s.indexOf(":");if(o>0){const l=s.slice(0,o).trim(),c=s.slice(o+1).trim();if(l&&c){const d=Tt(l);e[d.startsWith("webkit")?ls(d):d]=c}}}t=i+1}if(Y.size===fs){const r=Y.keys().next().value;r&&Y.delete(r)}return Y.set(a,e),e}function _t(a,e,t={}){if(typeof e=="string")return e;const n=(e.children||[]).map(u=>{let v=u;return("fill"in t||t.gradientFill)&&u.tag==="path"&&"fill"in u.attributes&&(v={...u,attributes:{...u.attributes,fill:void 0}}),_t(a,v)}),r=e.attributes||{},i={};for(const[u,v]of Object.entries(r))switch(!0){case u==="class":{i.className=v;break}case u==="style":{i.style=cs(String(v));break}case u.startsWith("aria-"):case u.startsWith("data-"):{i[u.toLowerCase()]=v;break}default:i[Tt(u)]=v}const{style:s,role:o,"aria-label":l,gradientFill:c,...d}=t;if(s&&(i.style=i.style?{...i.style,...s}:s),o&&(i.role=o),l&&(i["aria-label"]=l,i["aria-hidden"]="false"),c){i.fill=`url(#${c.id})`;const{type:u,stops:v=[],...h}=c;n.unshift(a(u==="linear"?"linearGradient":"radialGradient",{...h,id:c.id},v.map(os)))}return a(e.tag,{...i,...d},...n)}var us=_t.bind(null,Da.createElement),Ce=(a,e)=>{const t=Dt.useId();return a||(e?t:void 0)},ds=class{constructor(a="react-fontawesome"){this.enabled=!1;let e=!1;try{e=typeof process<"u"&&!1}catch{}this.scope=a,this.enabled=e}log(...a){this.enabled&&console.log(`[${this.scope}]`,...a)}warn(...a){this.enabled&&console.warn(`[${this.scope}]`,...a)}error(...a){this.enabled&&console.error(`[${this.scope}]`,...a)}},ms="searchPseudoElementsFullScan"in B&&typeof B.searchPseudoElementsFullScan=="boolean"?"7.0.0":"6.0.0",vs=Number.parseInt(ms)>=7,hs=()=>vs,Q="fa",E={beat:"fa-beat",fade:"fa-fade",beatFade:"fa-beat-fade",bounce:"fa-bounce",shake:"fa-shake",spin:"fa-spin",spinPulse:"fa-spin-pulse",spinReverse:"fa-spin-reverse",pulse:"fa-pulse"},ps={left:"fa-pull-left",right:"fa-pull-right"},gs={90:"fa-rotate-90",180:"fa-rotate-180",270:"fa-rotate-270"},bs={"2xs":"fa-2xs",xs:"fa-xs",sm:"fa-sm",lg:"fa-lg",xl:"fa-xl","2xl":"fa-2xl","1x":"fa-1x","2x":"fa-2x","3x":"fa-3x","4x":"fa-4x","5x":"fa-5x","6x":"fa-6x","7x":"fa-7x","8x":"fa-8x","9x":"fa-9x","10x":"fa-10x"},N={border:"fa-border",fixedWidth:"fa-fw",flip:"fa-flip",flipHorizontal:"fa-flip-horizontal",flipVertical:"fa-flip-vertical",inverse:"fa-inverse",rotateBy:"fa-rotate-by",swapOpacity:"fa-swap-opacity",widthAuto:"fa-width-auto"};function ys(a){const e=B.cssPrefix||B.familyPrefix||Q;return e===Q?a:a.replace(new RegExp(String.raw`(?<=^|\s)${Q}-`,"g"),`${e}-`)}function xs(a){const{beat:e,fade:t,beatFade:n,bounce:r,shake:i,spin:s,spinPulse:o,spinReverse:l,pulse:c,fixedWidth:d,inverse:u,border:v,flip:h,size:y,rotation:b,pull:S,swapOpacity:w,rotateBy:A,widthAuto:k,className:P}=a,g=[];return P&&g.push(...P.split(" ")),e&&g.push(E.beat),t&&g.push(E.fade),n&&g.push(E.beatFade),r&&g.push(E.bounce),i&&g.push(E.shake),s&&g.push(E.spin),l&&g.push(E.spinReverse),o&&g.push(E.spinPulse),c&&g.push(E.pulse),d&&g.push(N.fixedWidth),u&&g.push(N.inverse),v&&g.push(N.border),h===!0&&g.push(N.flip),(h==="horizontal"||h==="both")&&g.push(N.flipHorizontal),(h==="vertical"||h==="both")&&g.push(N.flipVertical),y!=null&&g.push(bs[y]),b!=null&&b!==0&&g.push(gs[b]),S!=null&&g.push(ps[S]),w&&g.push(N.swapOpacity),hs()?(A&&g.push(N.rotateBy),k&&g.push(N.widthAuto),(B.cssPrefix||B.familyPrefix||Q)===Q?g:g.map(ys)):g}var Ss=a=>typeof a=="object"&&"icon"in a&&!!a.icon;function Pe(a){if(a)return Ss(a)?a:Ot.icon(a)}function ws(a){return Object.keys(a)}var Ee=new ds("FontAwesomeIcon"),jt={border:!1,className:"",mask:void 0,maskId:void 0,fixedWidth:!1,inverse:!1,flip:!1,icon:void 0,listItem:!1,pull:void 0,pulse:!1,rotation:void 0,rotateBy:!1,size:void 0,spin:!1,spinPulse:!1,spinReverse:!1,beat:!1,fade:!1,beatFade:!1,bounce:!1,shake:!1,symbol:!1,title:"",titleId:void 0,transform:void 0,swapOpacity:!1,widthAuto:!1},As=new Set(Object.keys(jt)),ks=Da.forwardRef((a,e)=>{const t={...jt,...a},{icon:n,mask:r,symbol:i,title:s,titleId:o,maskId:l,transform:c}=t,d=Ce(l,!!r),u=Ce(o,!!s),v=Pe(n);if(!v)return Ee.error("Icon lookup is undefined",n),null;const h=xs(t),y=typeof c=="string"?Ot.transform(c):c,b=Pe(r),S=is(v,{...h.length>0&&{classes:h},...y&&{transform:y},...b&&{mask:b},symbol:i,title:s,titleId:u,maskId:d});if(!S)return Ee.error("Could not find icon",v),null;const{abstract:w}=S,A={ref:e};for(const k of ws(t))As.has(k)||(A[k]=t[k]);return us(w[0],A)});ks.displayName="FontAwesomeIcon";var Fs={prefix:"fas",iconName:"align-justify",icon:[448,512,[],"f039","M448 64c0-17.7-14.3-32-32-32L32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32zm0 256c0-17.7-14.3-32-32-32L32 288c-17.7 0-32 14.3-32 32s14.3 32 32 32l384 0c17.7 0 32-14.3 32-32zM0 192c0 17.7 14.3 32 32 32l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L32 160c-17.7 0-32 14.3-32 32zM448 448c0-17.7-14.3-32-32-32L32 416c-17.7 0-32 14.3-32 32s14.3 32 32 32l384 0c17.7 0 32-14.3 32-32z"]},Os={prefix:"fas",iconName:"minus",icon:[448,512,[8211,8722,10134,"subtract"],"f068","M0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32z"]},Ts={prefix:"fas",iconName:"h5",icon:[576,512,[],"e412","M96 96c0-17.7-14.3-32-32-32S32 78.3 32 96l0 320c0 17.7 14.3 32 32 32s32-14.3 32-32l0-128 96 0 0 128c0 17.7 14.3 32 32 32s32-14.3 32-32l0-320c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 128-96 0 0-128zM352 64c-17.7 0-32 14.3-32 32l0 144c0 17.7 14.3 32 32 32l80 0c30.9 0 56 25.1 56 56s-25.1 56-56 56l-80 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l80 0c66.3 0 120-53.7 120-120S498.3 208 432 208l-48 0 0-80 120 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L352 64z"]},_s={prefix:"fas",iconName:"ellipsis",icon:[448,512,["ellipsis-h"],"f141","M0 256a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm168 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm224-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"]},Ls={prefix:"fas",iconName:"magnifying-glass",icon:[512,512,[128269,"search"],"f002","M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"]},js=Ls,Rs={prefix:"fas",iconName:"empty-set",icon:[512,512,[8709,216],"f656","M502.6 54.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L413 53.8C369.6 20.1 315.2 0 256 0 114.6 0 0 114.6 0 256 0 315.2 20.1 369.6 53.8 413L9.4 457.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L99 458.2c43.3 33.7 97.8 53.8 157 53.8 141.4 0 256-114.6 256-256 0-59.2-20.1-113.6-53.8-157l44.4-44.4zM367.2 99.5L99.5 367.2C77.1 335.9 64 297.5 64 256 64 150 150 64 256 64 297.5 64 335.9 77.1 367.2 99.5zm-222.5 313L412.5 144.8c22.4 31.4 35.5 69.8 35.5 111.2 0 106-86 192-192 192-41.5 0-79.9-13.1-111.2-35.5z"]},$s={prefix:"fas",iconName:"h4",icon:[512,512,[],"f86a","M64 96c0-17.7-14.3-32-32-32S0 78.3 0 96L0 416c0 17.7 14.3 32 32 32s32-14.3 32-32l0-128 96 0 0 128c0 17.7 14.3 32 32 32s32-14.3 32-32l0-320c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 128-96 0 0-128zm288 0c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 112c0 44.2 35.8 80 80 80l80 0 0 128c0 17.7 14.3 32 32 32s32-14.3 32-32l0-320c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 128-80 0c-8.8 0-16-7.2-16-16l0-112z"]},Ds={prefix:"fas",iconName:"h2",icon:[576,512,[],"f314","M96 96c0-17.7-14.3-32-32-32S32 78.3 32 96l0 320c0 17.7 14.3 32 32 32s32-14.3 32-32l0-128 96 0 0 128c0 17.7 14.3 32 32 32s32-14.3 32-32l0-320c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 128-96 0 0-128zM368 64c-17.7 0-32 14.3-32 32s14.3 32 32 32l81.1 0c21.5 0 38.9 17.4 38.9 38.9 0 13.9-7.5 26.8-19.6 33.8l-76.3 43.6C347.5 269.7 320 317.1 320 368.5l0 47.5c0 17.7 14.3 32 32 32l160 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-128 0 0-15.5c0-28.4 15.2-54.6 39.9-68.7l76.3-43.6C532.2 237.9 552 203.8 552 166.9 552 110.1 505.9 64 449.1 64L368 64z"]},Ws={prefix:"fas",iconName:"align-left",icon:[448,512,[],"f036","M288 64c0 17.7-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l224 0c17.7 0 32 14.3 32 32zm0 256c0 17.7-14.3 32-32 32L32 352c-17.7 0-32-14.3-32-32s14.3-32 32-32l224 0c17.7 0 32 14.3 32 32zM0 192c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 224c-17.7 0-32-14.3-32-32zM448 448c0 17.7-14.3 32-32 32L32 480c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32z"]},Us={prefix:"fas",iconName:"eye",icon:[576,512,[128065],"f06e","M288 32c-80.8 0-145.5 36.8-192.6 80.6-46.8 43.5-78.1 95.4-93 131.1-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64-11.5 0-22.3-3-31.7-8.4-1 10.9-.1 22.1 2.9 33.2 13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-12.2-45.7-55.5-74.8-101.1-70.8 5.3 9.3 8.4 20.1 8.4 31.7z"]},Ys={prefix:"fas",iconName:"trash",icon:[448,512,[],"f1f8","M136.7 5.9L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-8.7-26.1C306.9-7.2 294.7-16 280.9-16L167.1-16c-13.8 0-26 8.8-30.4 21.9zM416 144L32 144 53.1 467.1C54.7 492.4 75.7 512 101 512L347 512c25.3 0 46.3-19.6 47.9-44.9L416 144z"]},Hs={prefix:"fas",iconName:"chevron-up",icon:[448,512,[],"f077","M201.4 105.4c12.5-12.5 32.8-12.5 45.3 0l192 192c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L224 173.3 54.6 342.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l192-192z"]},Gs={prefix:"fas",iconName:"h6",icon:[512,512,[],"e413","M32 64c17.7 0 32 14.3 32 32l0 128 96 0 0-128c0-17.7 14.3-32 32-32s32 14.3 32 32l0 320c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-128-96 0 0 128c0 17.7-14.3 32-32 32S0 433.7 0 416L0 96C0 78.3 14.3 64 32 64zm352 64c-17.7 0-32 14.3-32 32l0 53.5c10-3.5 20.8-5.5 32-5.5l32 0c53 0 96 43 96 96l0 48c0 53-43 96-96 96l-32 0c-53 0-96-43-96-96l0-192c0-53 43-96 96-96l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0zM352 304l0 48c0 17.7 14.3 32 32 32l32 0c17.7 0 32-14.3 32-32l0-48c0-17.7-14.3-32-32-32l-32 0c-17.7 0-32 14.3-32 32z"]},Xs={prefix:"fas",iconName:"pen-to-square",icon:[512,512,["edit"],"f044","M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L368 46.1 465.9 144 490.3 119.6c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L432 177.9 334.1 80 172.4 241.7zM96 64C43 64 0 107 0 160L0 416c0 53 43 96 96 96l256 0c53 0 96-43 96-96l0-96c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7-14.3 32-32 32L96 448c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L96 64z"]},Bs={prefix:"fas",iconName:"clock",icon:[512,512,[128339,"clock-four"],"f017","M256 0a256 256 0 1 1 0 512 256 256 0 1 1 0-512zM232 120l0 136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2 280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24z"]},Vs={prefix:"fas",iconName:"h1",icon:[512,512,[],"f313","M448 96c0-11.1-5.7-21.4-15.2-27.2s-21.2-6.4-31.1-1.4l-64 32c-15.8 7.9-22.2 27.1-14.3 42.9s27.1 22.2 42.9 14.3l17.7-8.8 0 236.2-32 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l128 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-32 0 0-288zM64 96c0-17.7-14.3-32-32-32S0 78.3 0 96L0 416c0 17.7 14.3 32 32 32s32-14.3 32-32l0-128 128 0 0 128c0 17.7 14.3 32 32 32s32-14.3 32-32l0-320c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 128-128 0 0-128z"]},qs={prefix:"fas",iconName:"clone",icon:[512,512,[],"f24d","M288 448l-224 0 0-224 48 0 0-64-48 0c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l224 0c35.3 0 64-28.7 64-64l0-48-64 0 0 48zm-64-96l224 0c35.3 0 64-28.7 64-64l0-224c0-35.3-28.7-64-64-64L224 0c-35.3 0-64 28.7-64 64l0 224c0 35.3 28.7 64 64 64z"]},Ks={prefix:"fas",iconName:"chevron-right",icon:[320,512,[9002],"f054","M311.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L243.2 256 73.9 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"]},Js={prefix:"fas",iconName:"strikethrough",icon:[512,512,[],"f0cc","M96 157.5C96 88.2 152.2 32 221.5 32L368 32c17.7 0 32 14.3 32 32s-14.3 32-32 32L221.5 96c-34 0-61.5 27.5-61.5 61.5 0 31 23.1 57.2 53.9 61l44.1 5.5 222 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32s14.3-32 32-32l83.1 0C103 204.6 96 181.8 96 157.5zM349.2 336l65.5 0c.9 6.1 1.4 12.2 1.4 18.5 0 69.3-56.2 125.5-125.5 125.5L144 480c-17.7 0-32-14.3-32-32s14.3-32 32-32l146.5 0c34 0 61.5-27.5 61.5-61.5 0-6.4-1-12.7-2.8-18.5z"]},Qs={prefix:"fas",iconName:"align-right",icon:[448,512,[],"f038","M448 64c0 17.7-14.3 32-32 32L192 96c-17.7 0-32-14.3-32-32s14.3-32 32-32l224 0c17.7 0 32 14.3 32 32zm0 256c0 17.7-14.3 32-32 32l-224 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l224 0c17.7 0 32 14.3 32 32zM0 192c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 224c-17.7 0-32-14.3-32-32zM448 448c0 17.7-14.3 32-32 32L32 480c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32z"]},Zs={prefix:"fas",iconName:"superscript",icon:[576,512,[],"f12b","M544 32c0-11.1-5.7-21.4-15.2-27.2s-21.2-6.4-31.1-1.4l-32 16C449.9 27.3 443.5 46.5 451.4 62.3 457 73.5 468.3 80 480 80l0 80c-17.7 0-32 14.3-32 32s14.3 32 32 32l64 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l0-128zM96 64C78.3 64 64 78.3 64 96s14.3 32 32 32l15.3 0 89.6 128-89.6 128-15.3 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l32 0c10.4 0 20.2-5.1 26.2-13.6L240 311.8 325.8 434.4c6 8.6 15.8 13.6 26.2 13.6l32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-15.3 0-89.6-128 89.6-128 15.3 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-32 0c-10.4 0-20.2 5.1-26.2 13.6L240 200.2 154.2 77.6C148.2 69.1 138.4 64 128 64L96 64z"]},ao={prefix:"fas",iconName:"bold",icon:[384,512,[],"f032","M32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l32 0 0 320-32 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l224 0c70.7 0 128-57.3 128-128 0-46.5-24.8-87.3-62-109.7 18.7-22.3 30-51 30-82.3 0-70.7-57.3-128-128-128L32 32zM288 160c0 35.3-28.7 64-64 64l-96 0 0-128 96 0c35.3 0 64 28.7 64 64zM128 416l0-128 128 0c35.3 0 64 28.7 64 64s-28.7 64-64 64l-128 0z"]},eo={prefix:"fas",iconName:"pencil",icon:[512,512,[9999,61504,"pencil-alt"],"f303","M36.4 353.2c4.1-14.6 11.8-27.9 22.6-38.7l181.2-181.2 33.9-33.9c16.6 16.6 51.3 51.3 104 104l33.9 33.9-33.9 33.9-181.2 181.2c-10.7 10.7-24.1 18.5-38.7 22.6L30.4 510.6c-8.3 2.3-17.3 0-23.4-6.2S-1.4 489.3 .9 481L36.4 353.2zm55.6-3.7c-4.4 4.7-7.6 10.4-9.3 16.6l-24.1 86.9 86.9-24.1c6.4-1.8 12.2-5.1 17-9.7L91.9 349.5zm354-146.1c-16.6-16.6-51.3-51.3-104-104L308 65.5C334.5 39 349.4 24.1 352.9 20.6 366.4 7 384.8-.6 404-.6S441.6 7 455.1 20.6l35.7 35.7C504.4 69.9 512 88.3 512 107.4s-7.6 37.6-21.2 51.1c-3.5 3.5-18.4 18.4-44.9 44.9z"]},to={prefix:"fas",iconName:"align-center",icon:[448,512,[],"f037","M352 64c0-17.7-14.3-32-32-32L128 32c-17.7 0-32 14.3-32 32s14.3 32 32 32l192 0c17.7 0 32-14.3 32-32zm96 128c0-17.7-14.3-32-32-32L32 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l384 0c17.7 0 32-14.3 32-32zM0 448c0 17.7 14.3 32 32 32l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L32 416c-17.7 0-32 14.3-32 32zM352 320c0-17.7-14.3-32-32-32l-192 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l192 0c17.7 0 32-14.3 32-32z"]},no={prefix:"fas",iconName:"code",icon:[576,512,[],"f121","M360.8 1.2c-17-4.9-34.7 5-39.6 22l-128 448c-4.9 17 5 34.7 22 39.6s34.7-5 39.6-22l128-448c4.9-17-5-34.7-22-39.6zm64.6 136.1c-12.5 12.5-12.5 32.8 0 45.3l73.4 73.4-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l96-96c12.5-12.5 12.5-32.8 0-45.3l-96-96c-12.5-12.5-32.8-12.5-45.3 0zm-274.7 0c-12.5-12.5-32.8-12.5-45.3 0l-96 96c-12.5 12.5-12.5 32.8 0 45.3l96 96c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 150.6 182.6c12.5-12.5 12.5-32.8 0-45.3z"]},ro={prefix:"fas",iconName:"highlighter",icon:[576,512,[],"f591","M315 315L473.4 99.9 444.1 70.6 229 229 315 315zm-187 5l0 0 0-71.7c0-15.3 7.2-29.6 19.5-38.6L420.6 8.4C428 2.9 437 0 446.2 0 457.6 0 468.5 4.5 476.6 12.6l54.8 54.8c8.1 8.1 12.6 19 12.6 30.5 0 9.2-2.9 18.2-8.4 25.6L334.4 396.5c-9 12.3-23.4 19.5-38.6 19.5l-71.7 0-25.4 25.4c-12.5 12.5-32.8 12.5-45.3 0l-50.7-50.7c-12.5-12.5-12.5-32.8 0-45.3L128 320zM7 466.3l51.7-51.7 70.6 70.6-19.7 19.7c-4.5 4.5-10.6 7-17 7L24 512c-13.3 0-24-10.7-24-24l0-4.7c0-6.4 2.5-12.5 7-17z"]},io={prefix:"fas",iconName:"circle",icon:[512,512,[128308,128309,128992,128993,128994,128995,128996,9679,9898,9899,11044,61708,61915],"f111","M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"]},so={prefix:"fas",iconName:"table-list",icon:[448,512,["th-list"],"f00b","M0 96C0 60.7 28.7 32 64 32l320 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zm64 0l0 64 64 0 0-64-64 0zm320 0l-192 0 0 64 192 0 0-64zM64 224l0 64 64 0 0-64-64 0zm320 0l-192 0 0 64 192 0 0-64zM64 352l0 64 64 0 0-64-64 0zm320 0l-192 0 0 64 192 0 0-64z"]},oo={prefix:"fas",iconName:"text-slash",icon:[576,512,["remove-format"],"f87d","M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9L322.7 256.9 368.2 96 471 96 465 120.2c-4.3 17.1 6.1 34.5 23.3 38.8s34.5-6.1 38.8-23.3l11-44.1C545.6 61.3 522.7 32 491.5 32l-319 0c-19.8 0-37.3 12.1-44.5 30.1l-87-87zM180.4 114.5l4.6-18.5 116.7 0-30.8 109-90.5-90.5zM241 310.8L211.3 416 160 416c-17.7 0-32 14.3-32 32s14.3 32 32 32l160 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-42.2 0 15.1-53.3-51.9-51.9z"]},lo={prefix:"fas",iconName:"link",icon:[576,512,[128279,"chain"],"f0c1","M419.5 96c-16.6 0-32.7 4.5-46.8 12.7-15.8-16-34.2-29.4-54.5-39.5 28.2-24 64.1-37.2 101.3-37.2 86.4 0 156.5 70 156.5 156.5 0 41.5-16.5 81.3-45.8 110.6l-71.1 71.1c-29.3 29.3-69.1 45.8-110.6 45.8-86.4 0-156.5-70-156.5-156.5 0-1.5 0-3 .1-4.5 .5-17.7 15.2-31.6 32.9-31.1s31.6 15.2 31.1 32.9c0 .9 0 1.8 0 2.6 0 51.1 41.4 92.5 92.5 92.5 24.5 0 48-9.7 65.4-27.1l71.1-71.1c17.3-17.3 27.1-40.9 27.1-65.4 0-51.1-41.4-92.5-92.5-92.5zM275.2 173.3c-1.9-.8-3.8-1.9-5.5-3.1-12.6-6.5-27-10.2-42.1-10.2-24.5 0-48 9.7-65.4 27.1L91.1 258.2c-17.3 17.3-27.1 40.9-27.1 65.4 0 51.1 41.4 92.5 92.5 92.5 16.5 0 32.6-4.4 46.7-12.6 15.8 16 34.2 29.4 54.6 39.5-28.2 23.9-64 37.2-101.3 37.2-86.4 0-156.5-70-156.5-156.5 0-41.5 16.5-81.3 45.8-110.6l71.1-71.1c29.3-29.3 69.1-45.8 110.6-45.8 86.6 0 156.5 70.6 156.5 156.9 0 1.3 0 2.6 0 3.9-.4 17.7-15.1 31.6-32.8 31.2s-31.6-15.1-31.2-32.8c0-.8 0-1.5 0-2.3 0-33.7-18-63.3-44.8-79.6z"]},zs={prefix:"fas",iconName:"gear",icon:[512,512,[9881,"cog"],"f013","M195.1 9.5C198.1-5.3 211.2-16 226.4-16l59.8 0c15.2 0 28.3 10.7 31.3 25.5L332 79.5c14.1 6 27.3 13.7 39.3 22.8l67.8-22.5c14.4-4.8 30.2 1.2 37.8 14.4l29.9 51.8c7.6 13.2 4.9 29.8-6.5 39.9L447 233.3c.9 7.4 1.3 15 1.3 22.7s-.5 15.3-1.3 22.7l53.4 47.5c11.4 10.1 14 26.8 6.5 39.9l-29.9 51.8c-7.6 13.1-23.4 19.2-37.8 14.4l-67.8-22.5c-12.1 9.1-25.3 16.7-39.3 22.8l-14.4 69.9c-3.1 14.9-16.2 25.5-31.3 25.5l-59.8 0c-15.2 0-28.3-10.7-31.3-25.5l-14.4-69.9c-14.1-6-27.2-13.7-39.3-22.8L73.5 432.3c-14.4 4.8-30.2-1.2-37.8-14.4L5.8 366.1c-7.6-13.2-4.9-29.8 6.5-39.9l53.4-47.5c-.9-7.4-1.3-15-1.3-22.7s.5-15.3 1.3-22.7L12.3 185.8c-11.4-10.1-14-26.8-6.5-39.9L35.7 94.1c7.6-13.2 23.4-19.2 37.8-14.4l67.8 22.5c12.1-9.1 25.3-16.7 39.3-22.8L195.1 9.5zM256.3 336a80 80 0 1 0 -.6-160 80 80 0 1 0 .6 160z"]},fo=zs,co={prefix:"fas",iconName:"arrow-up",icon:[384,512,[8593],"f062","M214.6 9.4c-12.5-12.5-32.8-12.5-45.3 0l-160 160c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L160 109.3 160 480c0 17.7 14.3 32 32 32s32-14.3 32-32l0-370.7 105.4 105.4c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-160-160z"]},uo={prefix:"fas",iconName:"brackets-curly",icon:[576,512,[],"f7ea","M416 32l-32 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l32 0c17.7 0 32 14.3 32 32l0 37.5c0 25.5 10.1 49.9 28.1 67.9l22.6 22.6-22.6 22.6c-18 18-28.1 42.4-28.1 67.9l0 37.5c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l32 0c53 0 96-43 96-96l0-37.5c0-8.5 3.4-16.6 9.4-22.6l45.3-45.3c12.5-12.5 12.5-32.8 0-45.3l-45.3-45.3c-6-6-9.4-14.1-9.4-22.6l0-37.5c0-53-43-96-96-96zM160 32c-53 0-96 43-96 96l0 37.5c0 8.5-3.4 16.6-9.4 22.6L9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l45.3 45.3c6 6 9.4 14.1 9.4 22.6L64 384c0 53 43 96 96 96l32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-32 0c-17.7 0-32-14.3-32-32l0-37.5c0-25.5-10.1-49.9-28.1-67.9L77.3 256 99.9 233.4c18-18 28.1-42.4 28.1-67.9l0-37.5c0-17.7 14.3-32 32-32l32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-32 0z"]},mo={prefix:"fas",iconName:"calendar",icon:[448,512,[128197,128198],"f133","M128 0C110.3 0 96 14.3 96 32l0 32-32 0C28.7 64 0 92.7 0 128l0 48 448 0 0-48c0-35.3-28.7-64-64-64l-32 0 0-32c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 32-128 0 0-32c0-17.7-14.3-32-32-32zM0 224L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-192-448 0z"]},vo={prefix:"fas",iconName:"italic",icon:[384,512,[],"f033","M128 64c0-17.7 14.3-32 32-32l192 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-58.7 0-133.3 320 64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 480c-17.7 0-32-14.3-32-32s14.3-32 32-32l58.7 0 133.3-320-64 0c-17.7 0-32-14.3-32-32z"]},ho={prefix:"fas",iconName:"check",icon:[448,512,[10003,10004],"f00c","M434.8 70.1c14.3 10.4 17.5 30.4 7.1 44.7l-256 352c-5.5 7.6-14 12.3-23.4 13.1s-18.5-2.7-25.1-9.3l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l101.5 101.5 234-321.7c10.4-14.3 30.4-17.5 44.7-7.1z"]},po={prefix:"fas",iconName:"list-ol",icon:[512,512,["list-1-2","list-numeric"],"f0cb","M0 72C0 58.8 10.7 48 24 48l48 0c13.3 0 24 10.7 24 24l0 104 24 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-96 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l24 0 0-80-24 0C10.7 96 0 85.3 0 72zM30.4 301.2C41.8 292.6 55.7 288 70 288l4.9 0c33.7 0 61.1 27.4 61.1 61.1 0 19.6-9.4 37.9-25.2 49.4l-24 17.5 33.2 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-90.7 0C13.1 464 0 450.9 0 434.7 0 425.3 4.5 416.5 12.1 411l70.5-51.3c3.4-2.5 5.4-6.4 5.4-10.6 0-7.2-5.9-13.1-13.1-13.1L70 336c-3.9 0-7.7 1.3-10.8 3.6L38.4 355.2c-10.6 8-25.6 5.8-33.6-4.8S-1 324.8 9.6 316.8l20.8-15.6zM224 64l256 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-256 0c-17.7 0-32-14.3-32-32s14.3-32 32-32zm0 160l256 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-256 0c-17.7 0-32-14.3-32-32s14.3-32 32-32zm0 160l256 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-256 0c-17.7 0-32-14.3-32-32s14.3-32 32-32z"]},go={prefix:"fas",iconName:"arrow-right",icon:[512,512,[8594],"f061","M502.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l370.7 0-105.4 105.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"]},bo={prefix:"fas",iconName:"xmark",icon:[384,512,[128473,10005,10006,10060,215,"close","multiply","remove","times"],"f00d","M55.1 73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L147.2 256 9.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192.5 301.3 329.9 438.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.8 256 375.1 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192.5 210.7 55.1 73.4z"]},yo={prefix:"fas",iconName:"circle-check",icon:[512,512,[61533,"check-circle"],"f058","M256 512a256 256 0 1 1 0-512 256 256 0 1 1 0 512zM374 145.7c-10.7-7.8-25.7-5.4-33.5 5.3L221.1 315.2 169 263.1c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9l72 72c5 5 11.8 7.5 18.8 7s13.4-4.1 17.5-9.8L379.3 179.2c7.8-10.7 5.4-25.7-5.3-33.5z"]},xo={prefix:"fas",iconName:"quote-right",icon:[448,512,[8221,"quote-right-alt"],"f10e","M448 296c0 66.3-53.7 120-120 120l-8 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l8 0c30.9 0 56-25.1 56-56l0-8-64 0c-35.3 0-64-28.7-64-64l0-64c0-35.3 28.7-64 64-64l64 0c35.3 0 64 28.7 64 64l0 136zm-256 0c0 66.3-53.7 120-120 120l-8 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l8 0c30.9 0 56-25.1 56-56l0-8-64 0c-35.3 0-64-28.7-64-64l0-64c0-35.3 28.7-64 64-64l64 0c35.3 0 64 28.7 64 64l0 136z"]},So={prefix:"fas",iconName:"chevron-down",icon:[448,512,[],"f078","M201.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 338.7 54.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"]},wo={prefix:"fas",iconName:"chevron-left",icon:[320,512,[9001],"f053","M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"]},Ms={prefix:"fas",iconName:"triangle-exclamation",icon:[512,512,[9888,"exclamation-triangle","warning"],"f071","M256 0c14.7 0 28.2 8.1 35.2 21l216 400c6.7 12.4 6.4 27.4-.8 39.5S486.1 480 472 480L40 480c-14.1 0-27.2-7.4-34.4-19.5s-7.5-27.1-.8-39.5l216-400c7-12.9 20.5-21 35.2-21zm0 352a32 32 0 1 0 0 64 32 32 0 1 0 0-64zm0-192c-18.2 0-32.7 15.5-31.4 33.7l7.4 104c.9 12.5 11.4 22.3 23.9 22.3 12.6 0 23-9.7 23.9-22.3l7.4-104c1.3-18.2-13.1-33.7-31.4-33.7z"]},Ao=Ms,ko={prefix:"fas",iconName:"asterisk",icon:[448,512,[10033,61545],"2a","M224 0c17.7 0 32 14.3 32 32l0 168.6 144-83.1c15.3-8.8 34.9-3.6 43.7 11.7s3.6 34.9-11.7 43.7L288 256 432 339.1c15.3 8.8 20.6 28.4 11.7 43.7s-28.4 20.6-43.7 11.7L256 311.4 256 480c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-168.6-144 83.1c-15.3 8.8-34.9 3.6-43.7-11.7S.7 348 16 339.1L160 256 16 172.9C.7 164-4.5 144.5 4.3 129.1S32.7 108.6 48 117.4L192 200.6 192 32c0-17.7 14.3-32 32-32z"]},Lo={prefix:"fas",iconName:"subscript",icon:[576,512,[],"f12c","M96 64C78.3 64 64 78.3 64 96s14.3 32 32 32l15.3 0 89.6 128-89.6 128-15.3 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l32 0c10.4 0 20.2-5.1 26.2-13.6L240 311.8 325.8 434.4c6 8.6 15.8 13.6 26.2 13.6l32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-15.3 0-89.6-128 89.6-128 15.3 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-32 0c-10.4 0-20.2 5.1-26.2 13.6L240 200.2 154.2 77.6C148.2 69.1 138.4 64 128 64L96 64zM544 320c0-11.1-5.7-21.4-15.2-27.2s-21.2-6.4-31.1-1.4l-32 16c-15.8 7.9-22.2 27.1-14.3 42.9 5.6 11.2 16.9 17.7 28.6 17.7l0 80c-17.7 0-32 14.3-32 32s14.3 32 32 32l64 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l0-128z"]},zo={prefix:"fas",iconName:"arrow-left",icon:[512,512,[8592],"f060","M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 288 480 288c17.7 0 32-14.3 32-32s-14.3-32-32-32l-370.7 0 105.4-105.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"]},Mo={prefix:"fas",iconName:"underline",icon:[384,512,[],"f0cd","M0 32C0 14.3 14.3 0 32 0L96 0c17.7 0 32 14.3 32 32S113.7 64 96 64l0 160c0 53 43 96 96 96s96-43 96-96l0-160c-17.7 0-32-14.3-32-32S270.3 0 288 0l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l0 160c0 88.4-71.6 160-160 160S32 312.4 32 224L32 64C14.3 64 0 49.7 0 32zM0 480c0-17.7 14.3-32 32-32l320 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 512c-17.7 0-32-14.3-32-32z"]},Io={prefix:"fas",iconName:"plus",icon:[448,512,[10133,61543,"add"],"2b","M256 64c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 160-160 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l160 0 0 160c0 17.7 14.3 32 32 32s32-14.3 32-32l0-160 160 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-160 0 0-160z"]},Co={prefix:"fas",iconName:"link-slash",icon:[576,512,["chain-broken","chain-slash","unlink"],"f127","M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-122-122c4.2-3.4 8.3-7.1 12.1-10.9l71.1-71.1c29.3-29.3 45.8-69.1 45.8-110.6 0-86.4-70-156.5-156.5-156.5-37.3 0-73.1 13.3-101.3 37.2 20.3 10.1 38.7 23.5 54.5 39.5 14.1-8.3 30.2-12.7 46.8-12.7 51.1 0 92.5 41.4 92.5 92.5 0 24.5-9.7 48-27.1 65.4l-71.1 71.1c-3.9 3.9-8.1 7.4-12.6 10.5l-47.5-47.5c16.5-.9 29.7-14.4 30.2-31.1 0-1.3 0-2.6 0-3.9 0-86.3-69.9-156.9-156.5-156.9-19.2 0-37.9 3.5-55.5 10.2L41-24.9zM225.9 160c.6 0 1.1 0 1.7 0 15.1 0 29.5 3.7 42.1 10.2 1.8 1.2 3.6 2.3 5.5 3.1 26.8 16.3 44.8 45.9 44.8 79.6 0 .4 0 .8 0 1.2L225.9 160zM346.2 416L192 261.8c1.2 84.6 69.6 152.9 154.1 154.1zM139.7 209.5l-45.3-45.3-48.6 48.6c-29.3 29.3-45.8 69.1-45.8 110.6 0 86.4 70 156.5 156.5 156.5 37.2 0 73.1-13.3 101.3-37.2-20.3-10.1-38.8-23.5-54.6-39.5-14 8.2-30.1 12.6-46.7 12.6-51.1 0-92.5-41.4-92.5-92.5 0-24.5 9.7-48 27.1-65.4l48.6-48.6z"]},Po={prefix:"fas",iconName:"arrow-rotate-right",icon:[512,512,[8635,"arrow-right-rotate","arrow-rotate-forward","redo"],"f01e","M436.7 74.7L448 85.4 448 32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 128c0 17.7-14.3 32-32 32l-128 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l47.9 0-7.6-7.2c-.2-.2-.4-.4-.6-.6-75-75-196.5-75-271.5 0s-75 196.5 0 271.5 196.5 75 271.5 0c8.2-8.2 15.5-16.9 21.9-26.1 10.1-14.5 30.1-18 44.6-7.9s18 30.1 7.9 44.6c-8.5 12.2-18.2 23.8-29.1 34.7-100 100-262.1 100-362 0S-25 175 75 75c99.9-99.9 261.7-100 361.7-.3z"]},Eo={prefix:"fas",iconName:"eye-slash",icon:[576,512,[],"f070","M41-24.9c-9.4-9.4-24.6-9.4-33.9 0S-2.3-.3 7 9.1l528 528c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-96.4-96.4c2.7-2.4 5.4-4.8 8-7.2 46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1-47.1-43.7-111.8-80.6-192.6-80.6-56.8 0-105.6 18.2-146 44.2L41-24.9zM204.5 138.7c23.5-16.8 52.4-26.7 83.5-26.7 79.5 0 144 64.5 144 144 0 31.1-9.9 59.9-26.7 83.5l-34.7-34.7c12.7-21.4 17-47.7 10.1-73.7-13.7-51.2-66.4-81.6-117.6-67.9-8.6 2.3-16.7 5.7-24 10l-34.7-34.7zM325.3 395.1c-11.9 3.2-24.4 4.9-37.3 4.9-79.5 0-144-64.5-144-144 0-12.9 1.7-25.4 4.9-37.3L69.4 139.2c-32.6 36.8-55 75.8-66.9 104.5-3.3 7.9-3.3 16.7 0 24.6 14.9 35.7 46.2 87.7 93 131.1 47.1 43.7 111.8 80.6 192.6 80.6 37.3 0 71.2-7.9 101.5-20.6l-64.2-64.2z"]},No={prefix:"fas",iconName:"arrow-rotate-left",icon:[512,512,[8634,"arrow-left-rotate","arrow-rotate-back","arrow-rotate-backward","undo"],"f0e2","M256 64c-56.8 0-107.9 24.7-143.1 64l47.1 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 192c-17.7 0-32-14.3-32-32L0 32C0 14.3 14.3 0 32 0S64 14.3 64 32l0 54.7C110.9 33.6 179.5 0 256 0 397.4 0 512 114.6 512 256S397.4 512 256 512c-87 0-163.9-43.4-210.1-109.7-10.1-14.5-6.6-34.4 7.9-44.6s34.4-6.6 44.6 7.9c34.8 49.8 92.4 82.3 157.6 82.3 106 0 192-86 192-192S362 64 256 64z"]},Is={prefix:"fas",iconName:"arrow-up-right-from-square",icon:[512,512,["external-link"],"f08e","M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l82.7 0-201.4 201.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3 448 192c0 17.7 14.3 32 32 32s32-14.3 32-32l0-160c0-17.7-14.3-32-32-32L320 0zM80 96C35.8 96 0 131.8 0 176L0 432c0 44.2 35.8 80 80 80l256 0c44.2 0 80-35.8 80-80l0-80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 80c0 8.8-7.2 16-16 16L80 448c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l80 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L80 96z"]},Fo=Is,Oo={prefix:"fas",iconName:"h3",icon:[576,512,[],"f315","M96 96c0-17.7-14.3-32-32-32S32 78.3 32 96l0 320c0 17.7 14.3 32 32 32s32-14.3 32-32l0-128 96 0 0 128c0 17.7 14.3 32 32 32s32-14.3 32-32l0-320c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 128-96 0 0-128zM352 256c0 17.7 14.3 32 32 32l56 0c26.5 0 48 21.5 48 48s-21.5 48-48 48l-88 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l88 0c61.9 0 112-50.1 112-112 0-31.3-12.9-59.7-33.6-80 20.7-20.3 33.6-48.7 33.6-80 0-61.9-50.1-112-112-112l-88 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l88 0c26.5 0 48 21.5 48 48s-21.5 48-48 48l-56 0c-17.7 0-32 14.3-32 32z"]},To={prefix:"fas",iconName:"arrow-down",icon:[384,512,[8595],"f063","M169.4 502.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 402.7 224 32c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 370.7-105.4-105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z"]},_o={prefix:"fas",iconName:"grip-dots-vertical",icon:[320,512,[],"e411","M128 64A64 64 0 1 0 0 64 64 64 0 1 0 128 64zm0 192a64 64 0 1 0 -128 0 64 64 0 1 0 128 0zM0 448c0 35.3 28.7 64 64 64s64-28.7 64-64-28.7-64-64-64-64 28.7-64 64zM320 64a64 64 0 1 0 -128 0 64 64 0 1 0 128 0zM192 256a64 64 0 1 0 128 0 64 64 0 1 0 -128 0zM320 448c0-35.3-28.7-64-64-64s-64 28.7-64 64 28.7 64 64 64 64-28.7 64-64z"]},Cs={prefix:"fas",iconName:"arrows-rotate",icon:[512,512,[128472,"refresh","sync"],"f021","M65.9 228.5c13.3-93 93.4-164.5 190.1-164.5 53 0 101 21.5 135.8 56.2 .2 .2 .4 .4 .6 .6l7.6 7.2-47.9 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-128c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 53.4-11.3-10.7C390.5 28.6 326.5 0 256 0 127 0 20.3 95.4 2.6 219.5 .1 237 12.2 253.2 29.7 255.7s33.7-9.7 36.2-27.1zm443.5 64c2.5-17.5-9.7-33.7-27.1-36.2s-33.7 9.7-36.2 27.1c-13.3 93-93.4 164.5-190.1 164.5-53 0-101-21.5-135.8-56.2-.2-.2-.4-.4-.6-.6l-7.6-7.2 47.9 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L32 320c-8.5 0-16.7 3.4-22.7 9.5S-.1 343.7 0 352.3l1 127c.1 17.7 14.6 31.9 32.3 31.7S65.2 496.4 65 478.7l-.4-51.5 10.7 10.1c46.3 46.1 110.2 74.7 180.7 74.7 129 0 235.7-95.4 253.4-219.5z"]},jo=Cs,Ro={prefix:"fas",iconName:"list-ul",icon:[512,512,["list-dots"],"f0ca","M48 144a48 48 0 1 0 0-96 48 48 0 1 0 0 96zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 64zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zM48 464a48 48 0 1 0 0-96 48 48 0 1 0 0 96zM96 256a48 48 0 1 0 -96 0 48 48 0 1 0 96 0z"]},$o={prefix:"fas",iconName:"circle-info",icon:[512,512,["info-circle"],"f05a","M256 512a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM224 160a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm-8 64l48 0c13.3 0 24 10.7 24 24l0 88 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-80 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l24 0 0-64-24 0c-13.3 0-24-10.7-24-24s10.7-24 24-24z"]};var Do={prefix:"far",iconName:"table",icon:[448,512,[],"f0ce","M384 32c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64l-320 0-6.5-.3C25.2 476.4 0 449.1 0 416L0 96C0 60.7 28.7 32 64 32l320 0zM48 312l0 104c0 8.8 7.2 16 16 16l136 0 0-120-152 0zm200 0l0 120 136 0c8.8 0 16-7.2 16-16l0-104-152 0zM48 264l152 0 0-104-152 0 0 104zm200 0l152 0 0-104-152 0 0 104z"]},Ps={prefix:"far",iconName:"circle-plus",icon:[512,512,["plus-circle"],"f055","M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM232 344c0 13.3 10.7 24 24 24s24-10.7 24-24l0-64 64 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-64 0 0-64c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 64-64 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l64 0 0 64z"]},Wo=Ps,Uo={prefix:"far",iconName:"file-dashed-line",icon:[576,512,["page-break"],"f877","M272 48L160 48c-8.8 0-16 7.2-16 16l0 176-48 0 0-176c0-35.3 28.7-64 64-64L293.5 0c17 0 33.3 6.7 45.3 18.7L461.3 141.3c12 12 18.7 28.3 18.7 45.3l0 53.5-48 0 0-32-88 0c-39.8 0-72-32.2-72-72l0-88zM96 384l48 0 0 64c0 8.8 7.2 16 16 16l256 0c8.8 0 16-7.2 16-16l0-64 48 0 0 64c0 35.3-28.7 64-64 64l-256 0c-35.3 0-64-28.7-64-64l0-64zM412.1 160L320 67.9 320 136c0 13.3 10.7 24 24 24l68.1 0zM24 288l112 0c13.3 0 24 10.7 24 24s-10.7 24-24 24L24 336c-13.3 0-24-10.7-24-24s10.7-24 24-24zm208 0l112 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-112 0c-13.3 0-24-10.7-24-24s10.7-24 24-24zm208 0l112 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-112 0c-13.3 0-24-10.7-24-24s10.7-24 24-24z"]},Es={prefix:"far",iconName:"square-plus",icon:[448,512,[61846,"plus-square"],"f0fe","M64 80c-8.8 0-16 7.2-16 16l0 320c0 8.8 7.2 16 16 16l320 0c8.8 0 16-7.2 16-16l0-320c0-8.8-7.2-16-16-16L64 80zM0 96C0 60.7 28.7 32 64 32l320 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM200 344l0-64-64 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l64 0 0-64c0-13.3 10.7-24 24-24s24 10.7 24 24l0 64 64 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-64 0 0 64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"]},Yo=Es;export{Yo as $,oo as A,Fs as B,Qs as C,to as D,Ws as E,ks as F,Uo as G,Os as H,no as I,uo as J,ro as K,xo as L,po as M,Ro as N,Zs as O,Lo as P,Js as Q,Mo as R,vo as S,ao as T,ko as U,Fo as V,so as W,Us as X,Xs as Y,qs as Z,Ao as _,Ks as a,eo as a0,Co as a1,zo as a2,go as a3,Ys as a4,jo as a5,Eo as a6,fo as a7,_o as a8,Cs as a9,zs as aa,$o as ab,yo as ac,Rs as ad,So as b,bo as c,ho as d,js as e,wo as f,mo as g,Wo as h,Hs as i,Bs as j,_s as k,co as l,To as m,Io as n,io as o,lo as p,Ms as q,Gs as r,Ts as s,$s as t,Oo as u,Ds as v,Vs as w,Do as x,Po as y,No as z};
