import { isKnownFrontendFieldType as xt, isCompositeField as Wt, isRepeatableField as qt, isFileField as Gt, compositePartDefinitions as Bt, repeaterRowDefinitions as Kt, createRepeaterRowValue as Zt, loadGraphqlFrontendEnvelope as Jt, loadFrontendEnvelope as Qt, createGraphqlFrontendTransport as Xt, createRestFrontendTransport as Yt, createFrontendFormInstance as te, FRONTEND_CLIENT_EVENT_NAMES as ee } from "@verbb/formie-core";
import { createFormieClient as se, FORMIE_HTML_EVENT_NAMES as ie } from "@verbb/formie-browser";
import { createFormieClient as gs } from "@verbb/formie-browser";
const D = globalThis, st = D.ShadowRoot && (D.ShadyCSS === void 0 || D.ShadyCSS.nativeShadow) && "adoptedStyleSheets" in Document.prototype && "replace" in CSSStyleSheet.prototype, it = /* @__PURE__ */ Symbol(), ut = /* @__PURE__ */ new WeakMap();
let Rt = class {
  constructor(t, e, i) {
    if (this._$cssResult$ = !0, i !== it) throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");
    this.cssText = t, this.t = e;
  }
  get styleSheet() {
    let t = this.o;
    const e = this.t;
    if (st && t === void 0) {
      const i = e !== void 0 && e.length === 1;
      i && (t = ut.get(e)), t === void 0 && ((this.o = t = new CSSStyleSheet()).replaceSync(this.cssText), i && ut.set(e, t));
    }
    return t;
  }
  toString() {
    return this.cssText;
  }
};
const re = (s) => new Rt(typeof s == "string" ? s : s + "", void 0, it), ne = (s, ...t) => {
  const e = s.length === 1 ? s[0] : t.reduce((i, r, n) => i + ((o) => {
    if (o._$cssResult$ === !0) return o.cssText;
    if (typeof o == "number") return o;
    throw Error("Value passed to 'css' function must be a 'css' function result: " + o + ". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.");
  })(r) + s[n + 1], s[0]);
  return new Rt(e, s, it);
}, oe = (s, t) => {
  if (st) s.adoptedStyleSheets = t.map((e) => e instanceof CSSStyleSheet ? e : e.styleSheet);
  else for (const e of t) {
    const i = document.createElement("style"), r = D.litNonce;
    r !== void 0 && i.setAttribute("nonce", r), i.textContent = e.cssText, s.appendChild(i);
  }
}, pt = st ? (s) => s : (s) => s instanceof CSSStyleSheet ? ((t) => {
  let e = "";
  for (const i of t.cssRules) e += i.cssText;
  return re(e);
})(s) : s;
const { is: ae, defineProperty: le, getOwnPropertyDescriptor: de, getOwnPropertyNames: he, getOwnPropertySymbols: ce, getPrototypeOf: ue } = Object, B = globalThis, ft = B.trustedTypes, pe = ft ? ft.emptyScript : "", fe = B.reactiveElementPolyfillSupport, U = (s, t) => s, W = { toAttribute(s, t) {
  switch (t) {
    case Boolean:
      s = s ? pe : null;
      break;
    case Object:
    case Array:
      s = s == null ? s : JSON.stringify(s);
  }
  return s;
}, fromAttribute(s, t) {
  let e = s;
  switch (t) {
    case Boolean:
      e = s !== null;
      break;
    case Number:
      e = s === null ? null : Number(s);
      break;
    case Object:
    case Array:
      try {
        e = JSON.parse(s);
      } catch {
        e = null;
      }
  }
  return e;
} }, rt = (s, t) => !ae(s, t), mt = { attribute: !0, type: String, converter: W, reflect: !1, useDefault: !1, hasChanged: rt };
Symbol.metadata ??= /* @__PURE__ */ Symbol("metadata"), B.litPropertyMetadata ??= /* @__PURE__ */ new WeakMap();
let R = class extends HTMLElement {
  static addInitializer(t) {
    this._$Ei(), (this.l ??= []).push(t);
  }
  static get observedAttributes() {
    return this.finalize(), this._$Eh && [...this._$Eh.keys()];
  }
  static createProperty(t, e = mt) {
    if (e.state && (e.attribute = !1), this._$Ei(), this.prototype.hasOwnProperty(t) && ((e = Object.create(e)).wrapped = !0), this.elementProperties.set(t, e), !e.noAccessor) {
      const i = /* @__PURE__ */ Symbol(), r = this.getPropertyDescriptor(t, i, e);
      r !== void 0 && le(this.prototype, t, r);
    }
  }
  static getPropertyDescriptor(t, e, i) {
    const { get: r, set: n } = de(this.prototype, t) ?? { get() {
      return this[e];
    }, set(o) {
      this[e] = o;
    } };
    return { get: r, set(o) {
      const l = r?.call(this);
      n?.call(this, o), this.requestUpdate(t, l, i);
    }, configurable: !0, enumerable: !0 };
  }
  static getPropertyOptions(t) {
    return this.elementProperties.get(t) ?? mt;
  }
  static _$Ei() {
    if (this.hasOwnProperty(U("elementProperties"))) return;
    const t = ue(this);
    t.finalize(), t.l !== void 0 && (this.l = [...t.l]), this.elementProperties = new Map(t.elementProperties);
  }
  static finalize() {
    if (this.hasOwnProperty(U("finalized"))) return;
    if (this.finalized = !0, this._$Ei(), this.hasOwnProperty(U("properties"))) {
      const e = this.properties, i = [...he(e), ...ce(e)];
      for (const r of i) this.createProperty(r, e[r]);
    }
    const t = this[Symbol.metadata];
    if (t !== null) {
      const e = litPropertyMetadata.get(t);
      if (e !== void 0) for (const [i, r] of e) this.elementProperties.set(i, r);
    }
    this._$Eh = /* @__PURE__ */ new Map();
    for (const [e, i] of this.elementProperties) {
      const r = this._$Eu(e, i);
      r !== void 0 && this._$Eh.set(r, e);
    }
    this.elementStyles = this.finalizeStyles(this.styles);
  }
  static finalizeStyles(t) {
    const e = [];
    if (Array.isArray(t)) {
      const i = new Set(t.flat(1 / 0).reverse());
      for (const r of i) e.unshift(pt(r));
    } else t !== void 0 && e.push(pt(t));
    return e;
  }
  static _$Eu(t, e) {
    const i = e.attribute;
    return i === !1 ? void 0 : typeof i == "string" ? i : typeof t == "string" ? t.toLowerCase() : void 0;
  }
  constructor() {
    super(), this._$Ep = void 0, this.isUpdatePending = !1, this.hasUpdated = !1, this._$Em = null, this._$Ev();
  }
  _$Ev() {
    this._$ES = new Promise((t) => this.enableUpdating = t), this._$AL = /* @__PURE__ */ new Map(), this._$E_(), this.requestUpdate(), this.constructor.l?.forEach((t) => t(this));
  }
  addController(t) {
    (this._$EO ??= /* @__PURE__ */ new Set()).add(t), this.renderRoot !== void 0 && this.isConnected && t.hostConnected?.();
  }
  removeController(t) {
    this._$EO?.delete(t);
  }
  _$E_() {
    const t = /* @__PURE__ */ new Map(), e = this.constructor.elementProperties;
    for (const i of e.keys()) this.hasOwnProperty(i) && (t.set(i, this[i]), delete this[i]);
    t.size > 0 && (this._$Ep = t);
  }
  createRenderRoot() {
    const t = this.shadowRoot ?? this.attachShadow(this.constructor.shadowRootOptions);
    return oe(t, this.constructor.elementStyles), t;
  }
  connectedCallback() {
    this.renderRoot ??= this.createRenderRoot(), this.enableUpdating(!0), this._$EO?.forEach((t) => t.hostConnected?.());
  }
  enableUpdating(t) {
  }
  disconnectedCallback() {
    this._$EO?.forEach((t) => t.hostDisconnected?.());
  }
  attributeChangedCallback(t, e, i) {
    this._$AK(t, i);
  }
  _$ET(t, e) {
    const i = this.constructor.elementProperties.get(t), r = this.constructor._$Eu(t, i);
    if (r !== void 0 && i.reflect === !0) {
      const n = (i.converter?.toAttribute !== void 0 ? i.converter : W).toAttribute(e, i.type);
      this._$Em = t, n == null ? this.removeAttribute(r) : this.setAttribute(r, n), this._$Em = null;
    }
  }
  _$AK(t, e) {
    const i = this.constructor, r = i._$Eh.get(t);
    if (r !== void 0 && this._$Em !== r) {
      const n = i.getPropertyOptions(r), o = typeof n.converter == "function" ? { fromAttribute: n.converter } : n.converter?.fromAttribute !== void 0 ? n.converter : W;
      this._$Em = r;
      const l = o.fromAttribute(e, n.type);
      this[r] = l ?? this._$Ej?.get(r) ?? l, this._$Em = null;
    }
  }
  requestUpdate(t, e, i, r = !1, n) {
    if (t !== void 0) {
      const o = this.constructor;
      if (r === !1 && (n = this[t]), i ??= o.getPropertyOptions(t), !((i.hasChanged ?? rt)(n, e) || i.useDefault && i.reflect && n === this._$Ej?.get(t) && !this.hasAttribute(o._$Eu(t, i)))) return;
      this.C(t, e, i);
    }
    this.isUpdatePending === !1 && (this._$ES = this._$EP());
  }
  C(t, e, { useDefault: i, reflect: r, wrapped: n }, o) {
    i && !(this._$Ej ??= /* @__PURE__ */ new Map()).has(t) && (this._$Ej.set(t, o ?? e ?? this[t]), n !== !0 || o !== void 0) || (this._$AL.has(t) || (this.hasUpdated || i || (e = void 0), this._$AL.set(t, e)), r === !0 && this._$Em !== t && (this._$Eq ??= /* @__PURE__ */ new Set()).add(t));
  }
  async _$EP() {
    this.isUpdatePending = !0;
    try {
      await this._$ES;
    } catch (e) {
      Promise.reject(e);
    }
    const t = this.scheduleUpdate();
    return t != null && await t, !this.isUpdatePending;
  }
  scheduleUpdate() {
    return this.performUpdate();
  }
  performUpdate() {
    if (!this.isUpdatePending) return;
    if (!this.hasUpdated) {
      if (this.renderRoot ??= this.createRenderRoot(), this._$Ep) {
        for (const [r, n] of this._$Ep) this[r] = n;
        this._$Ep = void 0;
      }
      const i = this.constructor.elementProperties;
      if (i.size > 0) for (const [r, n] of i) {
        const { wrapped: o } = n, l = this[r];
        o !== !0 || this._$AL.has(r) || l === void 0 || this.C(r, void 0, n, l);
      }
    }
    let t = !1;
    const e = this._$AL;
    try {
      t = this.shouldUpdate(e), t ? (this.willUpdate(e), this._$EO?.forEach((i) => i.hostUpdate?.()), this.update(e)) : this._$EM();
    } catch (i) {
      throw t = !1, this._$EM(), i;
    }
    t && this._$AE(e);
  }
  willUpdate(t) {
  }
  _$AE(t) {
    this._$EO?.forEach((e) => e.hostUpdated?.()), this.hasUpdated || (this.hasUpdated = !0, this.firstUpdated(t)), this.updated(t);
  }
  _$EM() {
    this._$AL = /* @__PURE__ */ new Map(), this.isUpdatePending = !1;
  }
  get updateComplete() {
    return this.getUpdateComplete();
  }
  getUpdateComplete() {
    return this._$ES;
  }
  shouldUpdate(t) {
    return !0;
  }
  update(t) {
    this._$Eq &&= this._$Eq.forEach((e) => this._$ET(e, this[e])), this._$EM();
  }
  updated(t) {
  }
  firstUpdated(t) {
  }
};
R.elementStyles = [], R.shadowRootOptions = { mode: "open" }, R[U("elementProperties")] = /* @__PURE__ */ new Map(), R[U("finalized")] = /* @__PURE__ */ new Map(), fe?.({ ReactiveElement: R }), (B.reactiveElementVersions ??= []).push("2.1.2");
const nt = globalThis, $t = (s) => s, q = nt.trustedTypes, gt = q ? q.createPolicy("lit-html", { createHTML: (s) => s }) : void 0, Mt = "$lit$", _ = `lit$${Math.random().toFixed(9).slice(2)}$`, Tt = "?" + _, me = `<${Tt}>`, C = document, H = () => C.createComment(""), I = (s) => s === null || typeof s != "object" && typeof s != "function", ot = Array.isArray, $e = (s) => ot(s) || typeof s?.[Symbol.iterator] == "function", Q = `[ 	
\f\r]`, k = /<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g, bt = /-->/g, vt = />/g, E = RegExp(`>|${Q}(?:([^\\s"'>=/]+)(${Q}*=${Q}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`, "g"), yt = /'/g, _t = /"/g, kt = /^(?:script|style|textarea|title)$/i, ge = (s) => (t, ...e) => ({ _$litType$: s, strings: t, values: e }), c = ge(1), w = /* @__PURE__ */ Symbol.for("lit-noChange"), p = /* @__PURE__ */ Symbol.for("lit-nothing"), At = /* @__PURE__ */ new WeakMap(), S = C.createTreeWalker(C, 129);
function Ut(s, t) {
  if (!ot(s) || !s.hasOwnProperty("raw")) throw Error("invalid template strings array");
  return gt !== void 0 ? gt.createHTML(t) : t;
}
const be = (s, t) => {
  const e = s.length - 1, i = [];
  let r, n = t === 2 ? "<svg>" : t === 3 ? "<math>" : "", o = k;
  for (let l = 0; l < e; l++) {
    const a = s[l];
    let d, u, h = -1, f = 0;
    for (; f < a.length && (o.lastIndex = f, u = o.exec(a), u !== null); ) f = o.lastIndex, o === k ? u[1] === "!--" ? o = bt : u[1] !== void 0 ? o = vt : u[2] !== void 0 ? (kt.test(u[2]) && (r = RegExp("</" + u[2], "g")), o = E) : u[3] !== void 0 && (o = E) : o === E ? u[0] === ">" ? (o = r ?? k, h = -1) : u[1] === void 0 ? h = -2 : (h = o.lastIndex - u[2].length, d = u[1], o = u[3] === void 0 ? E : u[3] === '"' ? _t : yt) : o === _t || o === yt ? o = E : o === bt || o === vt ? o = k : (o = E, r = void 0);
    const m = o === E && s[l + 1].startsWith("/>") ? " " : "";
    n += o === k ? a + me : h >= 0 ? (i.push(d), a.slice(0, h) + Mt + a.slice(h) + _ + m) : a + _ + (h === -2 ? l : m);
  }
  return [Ut(s, n + (s[e] || "<?>") + (t === 2 ? "</svg>" : t === 3 ? "</math>" : "")), i];
};
class O {
  constructor({ strings: t, _$litType$: e }, i) {
    let r;
    this.parts = [];
    let n = 0, o = 0;
    const l = t.length - 1, a = this.parts, [d, u] = be(t, e);
    if (this.el = O.createElement(d, i), S.currentNode = this.el.content, e === 2 || e === 3) {
      const h = this.el.content.firstChild;
      h.replaceWith(...h.childNodes);
    }
    for (; (r = S.nextNode()) !== null && a.length < l; ) {
      if (r.nodeType === 1) {
        if (r.hasAttributes()) for (const h of r.getAttributeNames()) if (h.endsWith(Mt)) {
          const f = u[o++], m = r.getAttribute(h).split(_), g = /([.?@])?(.*)/.exec(f);
          a.push({ type: 1, index: n, name: g[2], strings: m, ctor: g[1] === "." ? ye : g[1] === "?" ? _e : g[1] === "@" ? Ae : K }), r.removeAttribute(h);
        } else h.startsWith(_) && (a.push({ type: 6, index: n }), r.removeAttribute(h));
        if (kt.test(r.tagName)) {
          const h = r.textContent.split(_), f = h.length - 1;
          if (f > 0) {
            r.textContent = q ? q.emptyScript : "";
            for (let m = 0; m < f; m++) r.append(h[m], H()), S.nextNode(), a.push({ type: 2, index: ++n });
            r.append(h[f], H());
          }
        }
      } else if (r.nodeType === 8) if (r.data === Tt) a.push({ type: 2, index: n });
      else {
        let h = -1;
        for (; (h = r.data.indexOf(_, h + 1)) !== -1; ) a.push({ type: 7, index: n }), h += _.length - 1;
      }
      n++;
    }
  }
  static createElement(t, e) {
    const i = C.createElement("template");
    return i.innerHTML = t, i;
  }
}
function T(s, t, e = s, i) {
  if (t === w) return t;
  let r = i !== void 0 ? e._$Co?.[i] : e._$Cl;
  const n = I(t) ? void 0 : t._$litDirective$;
  return r?.constructor !== n && (r?._$AO?.(!1), n === void 0 ? r = void 0 : (r = new n(s), r._$AT(s, e, i)), i !== void 0 ? (e._$Co ??= [])[i] = r : e._$Cl = r), r !== void 0 && (t = T(s, r._$AS(s, t.values), r, i)), t;
}
class ve {
  constructor(t, e) {
    this._$AV = [], this._$AN = void 0, this._$AD = t, this._$AM = e;
  }
  get parentNode() {
    return this._$AM.parentNode;
  }
  get _$AU() {
    return this._$AM._$AU;
  }
  u(t) {
    const { el: { content: e }, parts: i } = this._$AD, r = (t?.creationScope ?? C).importNode(e, !0);
    S.currentNode = r;
    let n = S.nextNode(), o = 0, l = 0, a = i[0];
    for (; a !== void 0; ) {
      if (o === a.index) {
        let d;
        a.type === 2 ? d = new V(n, n.nextSibling, this, t) : a.type === 1 ? d = new a.ctor(n, a.name, a.strings, this, t) : a.type === 6 && (d = new Ee(n, this, t)), this._$AV.push(d), a = i[++l];
      }
      o !== a?.index && (n = S.nextNode(), o++);
    }
    return S.currentNode = C, r;
  }
  p(t) {
    let e = 0;
    for (const i of this._$AV) i !== void 0 && (i.strings !== void 0 ? (i._$AI(t, i, e), e += i.strings.length - 2) : i._$AI(t[e])), e++;
  }
}
class V {
  get _$AU() {
    return this._$AM?._$AU ?? this._$Cv;
  }
  constructor(t, e, i, r) {
    this.type = 2, this._$AH = p, this._$AN = void 0, this._$AA = t, this._$AB = e, this._$AM = i, this.options = r, this._$Cv = r?.isConnected ?? !0;
  }
  get parentNode() {
    let t = this._$AA.parentNode;
    const e = this._$AM;
    return e !== void 0 && t?.nodeType === 11 && (t = e.parentNode), t;
  }
  get startNode() {
    return this._$AA;
  }
  get endNode() {
    return this._$AB;
  }
  _$AI(t, e = this) {
    t = T(this, t, e), I(t) ? t === p || t == null || t === "" ? (this._$AH !== p && this._$AR(), this._$AH = p) : t !== this._$AH && t !== w && this._(t) : t._$litType$ !== void 0 ? this.$(t) : t.nodeType !== void 0 ? this.T(t) : $e(t) ? this.k(t) : this._(t);
  }
  O(t) {
    return this._$AA.parentNode.insertBefore(t, this._$AB);
  }
  T(t) {
    this._$AH !== t && (this._$AR(), this._$AH = this.O(t));
  }
  _(t) {
    this._$AH !== p && I(this._$AH) ? this._$AA.nextSibling.data = t : this.T(C.createTextNode(t)), this._$AH = t;
  }
  $(t) {
    const { values: e, _$litType$: i } = t, r = typeof i == "number" ? this._$AC(t) : (i.el === void 0 && (i.el = O.createElement(Ut(i.h, i.h[0]), this.options)), i);
    if (this._$AH?._$AD === r) this._$AH.p(e);
    else {
      const n = new ve(r, this), o = n.u(this.options);
      n.p(e), this.T(o), this._$AH = n;
    }
  }
  _$AC(t) {
    let e = At.get(t.strings);
    return e === void 0 && At.set(t.strings, e = new O(t)), e;
  }
  k(t) {
    ot(this._$AH) || (this._$AH = [], this._$AR());
    const e = this._$AH;
    let i, r = 0;
    for (const n of t) r === e.length ? e.push(i = new V(this.O(H()), this.O(H()), this, this.options)) : i = e[r], i._$AI(n), r++;
    r < e.length && (this._$AR(i && i._$AB.nextSibling, r), e.length = r);
  }
  _$AR(t = this._$AA.nextSibling, e) {
    for (this._$AP?.(!1, !0, e); t !== this._$AB; ) {
      const i = $t(t).nextSibling;
      $t(t).remove(), t = i;
    }
  }
  setConnected(t) {
    this._$AM === void 0 && (this._$Cv = t, this._$AP?.(t));
  }
}
class K {
  get tagName() {
    return this.element.tagName;
  }
  get _$AU() {
    return this._$AM._$AU;
  }
  constructor(t, e, i, r, n) {
    this.type = 1, this._$AH = p, this._$AN = void 0, this.element = t, this.name = e, this._$AM = r, this.options = n, i.length > 2 || i[0] !== "" || i[1] !== "" ? (this._$AH = Array(i.length - 1).fill(new String()), this.strings = i) : this._$AH = p;
  }
  _$AI(t, e = this, i, r) {
    const n = this.strings;
    let o = !1;
    if (n === void 0) t = T(this, t, e, 0), o = !I(t) || t !== this._$AH && t !== w, o && (this._$AH = t);
    else {
      const l = t;
      let a, d;
      for (t = n[0], a = 0; a < n.length - 1; a++) d = T(this, l[i + a], e, a), d === w && (d = this._$AH[a]), o ||= !I(d) || d !== this._$AH[a], d === p ? t = p : t !== p && (t += (d ?? "") + n[a + 1]), this._$AH[a] = d;
    }
    o && !r && this.j(t);
  }
  j(t) {
    t === p ? this.element.removeAttribute(this.name) : this.element.setAttribute(this.name, t ?? "");
  }
}
class ye extends K {
  constructor() {
    super(...arguments), this.type = 3;
  }
  j(t) {
    this.element[this.name] = t === p ? void 0 : t;
  }
}
class _e extends K {
  constructor() {
    super(...arguments), this.type = 4;
  }
  j(t) {
    this.element.toggleAttribute(this.name, !!t && t !== p);
  }
}
class Ae extends K {
  constructor(t, e, i, r, n) {
    super(t, e, i, r, n), this.type = 5;
  }
  _$AI(t, e = this) {
    if ((t = T(this, t, e, 0) ?? p) === w) return;
    const i = this._$AH, r = t === p && i !== p || t.capture !== i.capture || t.once !== i.once || t.passive !== i.passive, n = t !== p && (i === p || r);
    r && this.element.removeEventListener(this.name, this, i), n && this.element.addEventListener(this.name, this, t), this._$AH = t;
  }
  handleEvent(t) {
    typeof this._$AH == "function" ? this._$AH.call(this.options?.host ?? this.element, t) : this._$AH.handleEvent(t);
  }
}
class Ee {
  constructor(t, e, i) {
    this.element = t, this.type = 6, this._$AN = void 0, this._$AM = e, this.options = i;
  }
  get _$AU() {
    return this._$AM._$AU;
  }
  _$AI(t) {
    T(this, t);
  }
}
const Se = nt.litHtmlPolyfillSupport;
Se?.(O, V), (nt.litHtmlVersions ??= []).push("3.3.2");
const Ce = (s, t, e) => {
  const i = e?.renderBefore ?? t;
  let r = i._$litPart$;
  if (r === void 0) {
    const n = e?.renderBefore ?? null;
    i._$litPart$ = r = new V(t.insertBefore(H(), n), n, void 0, e ?? {});
  }
  return r._$AI(s), r;
};
const at = globalThis;
let M = class extends R {
  constructor() {
    super(...arguments), this.renderOptions = { host: this }, this._$Do = void 0;
  }
  createRenderRoot() {
    const t = super.createRenderRoot();
    return this.renderOptions.renderBefore ??= t.firstChild, t;
  }
  update(t) {
    const e = this.render();
    this.hasUpdated || (this.renderOptions.isConnected = this.isConnected), super.update(t), this._$Do = Ce(e, this.renderRoot, this.renderOptions);
  }
  connectedCallback() {
    super.connectedCallback(), this._$Do?.setConnected(!0);
  }
  disconnectedCallback() {
    super.disconnectedCallback(), this._$Do?.setConnected(!1);
  }
  render() {
    return w;
  }
};
M._$litElement$ = !0, M.finalized = !0, at.litElementHydrateSupport?.({ LitElement: M });
const we = at.litElementPolyfillSupport;
we?.({ LitElement: M });
(at.litElementVersions ??= []).push("4.2.2");
const Pt = /* @__PURE__ */ Symbol.for(""), xe = (s) => {
  if (s?.r === Pt) return s?._$litStatic$;
}, Nt = (s) => ({ _$litStatic$: s, r: Pt }), Et = /* @__PURE__ */ new Map(), Re = (s) => (t, ...e) => {
  const i = e.length;
  let r, n;
  const o = [], l = [];
  let a, d = 0, u = !1;
  for (; d < i; ) {
    for (a = t[d]; d < i && (n = e[d], (r = xe(n)) !== void 0); ) a += r + t[++d], u = !0;
    d !== i && l.push(n), o.push(a), d++;
  }
  if (d === i && o.push(t[i]), u) {
    const h = o.join("$$lit$$");
    (t = Et.get(h)) === void 0 && (o.raw = o, Et.set(h, t = o)), e = l;
  }
  return s(t, ...e);
}, L = Re(c);
const Me = { attribute: !0, type: String, converter: W, reflect: !1, hasChanged: rt }, Te = (s = Me, t, e) => {
  const { kind: i, metadata: r } = e;
  let n = globalThis.litPropertyMetadata.get(r);
  if (n === void 0 && globalThis.litPropertyMetadata.set(r, n = /* @__PURE__ */ new Map()), i === "setter" && ((s = Object.create(s)).wrapped = !0), n.set(e.name, s), i === "accessor") {
    const { name: o } = e;
    return { set(l) {
      const a = t.get.call(this);
      t.set.call(this, l), this.requestUpdate(o, a, s, !0, l);
    }, init(l) {
      return l !== void 0 && this.C(o, void 0, s, l), l;
    } };
  }
  if (i === "setter") {
    const { name: o } = e;
    return function(l) {
      const a = this[o];
      t.call(this, l), this.requestUpdate(o, a, s, !0, l);
    };
  }
  throw Error("Unsupported decorator location: " + i);
};
function $(s) {
  return (t, e) => typeof e == "object" ? Te(s, t, e) : ((i, r, n) => {
    const o = r.hasOwnProperty(n);
    return r.constructor.createProperty(n, i), o ? Object.getOwnPropertyDescriptor(r, n) : void 0;
  })(s, t, e);
}
function Z(s) {
  return $({ ...s, state: !0, attribute: !1 });
}
const ke = /^[a-z][a-z0-9]*(-[a-z0-9]+)+$/;
function P(s) {
  if (!ke.test(s))
    throw new Error(
      `[Formie WC] Invalid custom element tag "${s}". Use lowercase hyphenated names (e.g. my-text-field).`
    );
}
class J {
  constructor() {
    this.fieldControls = {}, this.fieldTag = null, this.regions = {};
  }
  registerFieldControl(t, e) {
    return P(e), this.fieldControls[t] = e, this;
  }
  /** Register a custom element used as the **field** host for every question. */
  registerField(t) {
    return P(t), this.fieldTag = t, this;
  }
  registerRegion(t, e) {
    return P(e), this.regions[t] = e, this;
  }
  clone() {
    const t = new J();
    return t.fieldControls = { ...this.fieldControls }, t.fieldTag = this.fieldTag, t.regions = { ...this.regions }, t;
  }
}
const Ue = new J();
function Pe() {
  return Ue;
}
function us() {
  return new J();
}
const Ne = (s) => s.strings === void 0;
const Ht = { CHILD: 2 }, It = (s) => (...t) => ({ _$litDirective$: s, values: t });
class Ot {
  constructor(t) {
  }
  get _$AU() {
    return this._$AM._$AU;
  }
  _$AT(t, e, i) {
    this._$Ct = t, this._$AM = e, this._$Ci = i;
  }
  _$AS(t, e) {
    return this.update(t, e);
  }
  update(t, e) {
    return this.render(...e);
  }
}
const N = (s, t) => {
  const e = s._$AN;
  if (e === void 0) return !1;
  for (const i of e) i._$AO?.(t, !1), N(i, t);
  return !0;
}, G = (s) => {
  let t, e;
  do {
    if ((t = s._$AM) === void 0) break;
    e = t._$AN, e.delete(s), s = t;
  } while (e?.size === 0);
}, Lt = (s) => {
  for (let t; t = s._$AM; s = t) {
    let e = t._$AN;
    if (e === void 0) t._$AN = e = /* @__PURE__ */ new Set();
    else if (e.has(s)) break;
    e.add(s), Oe(t);
  }
};
function He(s) {
  this._$AN !== void 0 ? (G(this), this._$AM = s, Lt(this)) : this._$AM = s;
}
function Ie(s, t = !1, e = 0) {
  const i = this._$AH, r = this._$AN;
  if (r !== void 0 && r.size !== 0) if (t) if (Array.isArray(i)) for (let n = e; n < i.length; n++) N(i[n], !1), G(i[n]);
  else i != null && (N(i, !1), G(i));
  else N(this, s);
}
const Oe = (s) => {
  s.type == Ht.CHILD && (s._$AP ??= Ie, s._$AQ ??= He);
};
class Le extends Ot {
  constructor() {
    super(...arguments), this._$AN = void 0;
  }
  _$AT(t, e, i) {
    super._$AT(t, e, i), Lt(this), this.isConnected = t._$AU;
  }
  _$AO(t, e = !0) {
    t !== this.isConnected && (this.isConnected = t, t ? this.reconnected?.() : this.disconnected?.()), e && (N(this, t), G(this));
  }
  setValue(t) {
    if (Ne(this._$Ct)) this._$Ct._$AI(t, this);
    else {
      const e = [...this._$Ct._$AH];
      e[this._$Ci] = t, this._$Ct._$AI(e, this, 0);
    }
  }
  disconnected() {
  }
  reconnected() {
  }
}
const X = /* @__PURE__ */ new WeakMap(), Ve = It(class extends Le {
  render(s) {
    return p;
  }
  update(s, [t]) {
    const e = t !== this.G;
    return e && this.G !== void 0 && this.rt(void 0), (e || this.lt !== this.ct) && (this.G = t, this.ht = s.options?.host, this.rt(this.ct = s.element)), p;
  }
  rt(s) {
    if (this.isConnected || (s = void 0), typeof this.G == "function") {
      const t = this.ht ?? globalThis;
      let e = X.get(t);
      e === void 0 && (e = /* @__PURE__ */ new WeakMap(), X.set(t, e)), e.get(this.G) !== void 0 && this.G.call(this.ht, void 0), e.set(this.G, s), s !== void 0 && this.G.call(this.ht, s);
    } else this.G.value = s;
  }
  get lt() {
    return typeof this.G == "function" ? X.get(this.ht ?? globalThis)?.get(this.G) : this.G?.value;
  }
  disconnected() {
    this.lt === this.ct && this.rt(void 0);
  }
  reconnected() {
    this.rt(this.ct);
  }
});
class et extends Ot {
  constructor(t) {
    if (super(t), this.it = p, t.type !== Ht.CHILD) throw Error(this.constructor.directiveName + "() can only be used in child bindings");
  }
  render(t) {
    if (t === p || t == null) return this._t = void 0, this.it = t;
    if (t === w) return t;
    if (typeof t != "string") throw Error(this.constructor.directiveName + "() called with a non-string value");
    if (t === this.it) return this._t;
    this.it = t;
    const e = [t];
    return e.raw = e, this._t = { _$litType$: this.constructor.resultType, strings: e, values: [] };
  }
}
et.directiveName = "unsafeHTML", et.resultType = 1;
const ze = It(et);
function ps(s) {
  return !!s && typeof s == "object" && "id" in s && "handle" in s && "type" in s;
}
function Vt(s) {
  if (xt(s.type))
    return s.type;
  const t = s.input && typeof s.input == "object" ? s.input : {}, e = typeof t.fieldKind == "string" ? t.fieldKind : null;
  return e === "text" ? "single-line-text" : e === "textarea" ? "multi-line-text" : e === "boolean" ? "agree" : e === "file" ? "file" : s.type;
}
const j = "formie-control-value-change";
function lt(s) {
  return P(s), Nt(s);
}
function Fe(s, t, e, i, r, n, o, l) {
  if (!s || !(s instanceof HTMLElement))
    return;
  const a = t.toLowerCase();
  let d = s.firstElementChild;
  (!d || d.tagName.toLowerCase() !== a) && (s.replaceChildren(), d = document.createElement(t), d.addEventListener(j, (u) => {
    l(u.detail);
  }), s.append(d)), d.field = e, d.value = i, d.errorKey = r, d.disabled = n, d.hidden = o;
}
function De(s, t, e, i, r, n, o, l) {
  return c`<div
        class="starter-core-registry-host min-w-0"
        ${Ve((a) => {
    Fe(a, t, e, i, r, n, o, l);
  })}
    ></div>`;
}
function je(s, t, e, i, r = "default") {
  if (r === "compositePart")
    return We(t, e, i);
  const n = s.registry.fieldTag;
  if (!n)
    return qe(t, e, i);
  const o = lt(n);
  return L`<${o} .field=${t} .errors=${e}>${i}</${o}>`;
}
function We(s, t, e) {
  return c`
        <div class="starter-component-subfield" data-formie-field-type=${s.type}>
            ${s.label ? c`<label class="starter-component-subfield-label">${s.label}</label>` : p}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${e}</div>
            ${t.length > 0 ? c`<ul class="grid gap-1 text-sm text-red-600">
                      ${t.map((i) => c`<li>${i}</li>`)}
                  </ul>` : p}
        </div>
    `;
}
function qe(s, t, e) {
  return c`
        <div class="starter-component-card" data-formie-field-type=${s.type}>
            ${s.label ? c`<label class="starter-component-label">${s.label}</label>` : p}
            ${s.instructions ? c`<p class="starter-component-help">${s.instructions}</p>` : p}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${e}</div>
            ${t.length > 0 ? c`<ul class="grid gap-1 text-sm text-red-600">
                      ${t.map((i) => c`<li>${i}</li>`)}
                  </ul>` : p}
        </div>
    `;
}
function St(s, t, e, i) {
  const r = s.input;
  if (s.type === "multi-line-text")
    return c`
            <textarea
                class="starter-component-control"
                .value=${typeof t == "string" ? t : ""}
                ?disabled=${e}
                placeholder=${typeof r.placeholder == "string" ? r.placeholder : ""}
                @input=${(o) => {
      i(o.target.value);
    }}
            ></textarea>
        `;
  if (s.type === "dropdown") {
    const o = Array.isArray(r.options) ? r.options : [], l = r.multiple === !0;
    return c`
            <select
                class="starter-component-control"
                ?disabled=${e}
                multiple=${l}
                .value=${l ? void 0 : typeof t == "string" ? t : ""}
                @change=${(a) => {
      const d = a.target;
      i(l ? Array.from(d.selectedOptions).map((u) => u.value) : d.value);
    }}
            >
                ${o.map(
      (a) => c`
                        <option
                            value=${String(a.value ?? "")}
                            ?disabled=${a.disabled === !0}
                        >
                            ${String(a.label ?? a.value ?? "")}
                        </option>
                    `
    )}
            </select>
        `;
  }
  const n = typeof r.inputType == "string" ? r.inputType : s.type === "email" ? "email" : s.type === "phone" ? "tel" : s.type === "number" ? "number" : "text";
  return c`
        <input
            class="starter-component-control"
            type=${n}
            .value=${typeof t == "string" ? t : ""}
            ?disabled=${e}
            placeholder=${typeof r.placeholder == "string" ? r.placeholder : ""}
            @input=${(o) => {
    i(o.target.value);
  }}
        />
    `;
}
function Ge(s, t) {
  const { field: e, value: i, errorKey: r, disabled: n, setValue: o } = t, l = e.input, a = Vt(e);
  if (Wt(e))
    return Ke(s, t);
  if (qt(e))
    return Ze(s, t);
  if (Gt(e))
    return Be(e, i, n, o);
  if (a === "signature")
    return c`<formie-internal-signature
            .field=${e}
            .modules=${s.state.definition.modules}
            .value=${typeof i == "string" ? i : ""}
            ?disabled=${n}
            @formie-control-value-change=${(d) => {
      o(d.detail);
    }}
        ></formie-internal-signature>`;
  if (a === "multi-line-text" || a === "dropdown")
    return St(e, i, n, o);
  if (a === "radio") {
    const d = Array.isArray(l.options) ? l.options : [];
    return c`
            <div class="flex flex-col gap-2">
                ${d.map((u) => {
      const h = String(u.value ?? ""), f = n || u.disabled === !0;
      return c`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="radio"
                                name=${`${e.id}-radio`}
                                .checked=${i === h}
                                ?disabled=${f}
                                @change=${() => {
        o(h);
      }}
                            />
                            <span>${String(u.label ?? h)}</span>
                        </label>
                    `;
    })}
            </div>
        `;
  }
  if (a === "checkboxes") {
    const d = Array.isArray(l.options) ? l.options : [], u = Array.isArray(i) ? i.map((h) => String(h)) : [];
    return c`
            <div class="flex flex-col gap-2">
                ${d.map((h) => {
      const f = String(h.value ?? ""), m = u.includes(f), g = n || h.disabled === !0;
      return c`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="checkbox"
                                .checked=${m}
                                ?disabled=${g}
                                @change=${() => {
        const x = m ? u.filter((F) => F !== f) : [...u, f];
        o(x);
      }}
                            />
                            <span>${String(h.label ?? f)}</span>
                        </label>
                    `;
    })}
            </div>
        `;
  }
  if (a === "agree") {
    const d = typeof l.descriptionHtml == "string" ? l.descriptionHtml : null;
    return c`
            <label class="flex items-start gap-2 text-sm text-slate-800">
                <input
                    type="checkbox"
                    .checked=${i === !0}
                    ?disabled=${n}
                    @change=${(u) => {
      o(u.target.checked);
    }}
                />
                <span>${d ? ze(d) : e.label ?? ""}</span>
            </label>
        `;
  }
  return xt(a) ? St(e, i, n, o) : c`<div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            Unknown field type:
            ${String(e.meta?.fieldType ?? e.type)}
        </div>`;
}
function Be(s, t, e, i) {
  const r = s.input, n = Array.isArray(t) ? t : [], o = r.multiple === !0, l = n.map((a, d) => a && typeof a == "object" && "name" in a && typeof a.name == "string" ? a.name : a && typeof a == "object" && "filename" in a && typeof a.filename == "string" ? a.filename : a && typeof a == "object" && "assetId" in a && typeof a.assetId == "number" ? `Asset #${a.assetId}` : `File ${d + 1}`);
  return c`
        <div class="grid gap-2">
            <input
                type="file"
                class="starter-component-control"
                ?disabled=${e}
                multiple=${o}
                @change=${(a) => {
    const d = a.target;
    i(Array.from(d.files || []));
  }}
            />
            ${l.length > 0 ? c`<ul class="grid gap-1 text-sm text-slate-600">
                      ${l.map((a) => c`<li>${a}</li>`)}
                  </ul>` : p}
        </div>
    `;
}
function Ke(s, t) {
  const { field: e, value: i, errorKey: r, disabled: n, setValue: o } = t, l = Bt(e), a = i && typeof i == "object" ? i : {};
  return l.length === 0 ? c`<div class="text-sm text-amber-800">Composite field has no parts.</div>` : c`
        <div class="starter-component-name-grid">
            ${l.filter((d) => d.meta?.hidden !== !0).map((d) => {
    const u = `${r}.${d.handle}`;
    return dt(
      s,
      {
        field: d,
        value: a[d.handle],
        errors: s.state.errors.fields[u] || [],
        errorKey: u,
        disabled: n || d.meta?.disabled === !0,
        setValue(h) {
          o({
            ...a,
            [d.handle]: h
          });
        }
      },
      "compositePart"
    );
  })}
        </div>
    `;
}
function Ze(s, t) {
  const { field: e, value: i, errorKey: r, disabled: n, setValue: o } = t, l = Kt(e), a = Array.isArray(i) ? i : [], d = e.input, u = Number(d.minRows ?? 0) || 0, h = Number(d.maxRows ?? 0) || 0, f = !n && (h <= 0 || a.length < h);
  return l.length === 0 ? c`<div class="text-sm text-amber-800">Repeater has no row layout.</div>` : c`
        <div class="grid gap-4" data-formie-repeater-container>
            ${a.map((m, g) => c`
                    <div class="rounded-xl border border-slate-200 p-4" data-formie-repeater-item>
                        ${l.map((x, F) => Je(s, x, m, `${r}.${g}`, n, (zt, Ft) => {
    const Dt = a.map((ct, jt) => jt !== g ? ct : { ...ct, [zt.handle]: Ft });
    o(Dt);
  }))}
                        <button
                            type="button"
                            class="mt-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700"
                            ?disabled=${n || u > 0 && a.length <= u}
                            @click=${() => {
    o(a.filter((x, F) => F !== g));
  }}
                        >
                            Remove
                        </button>
                    </div>
                `)}
            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm"
                ?disabled=${!f}
                @click=${() => {
    o([...a, Zt(e)]);
  }}
            >
                ${String(d.addLabel ?? "Add another row")}
            </button>
        </div>
    `;
}
function dt(s, t, e = "default") {
  const { field: i, value: r, errors: n, errorKey: o, disabled: l, setValue: a } = t, u = s.state.fieldStates[i.id]?.hidden === !0;
  if (u)
    return c``;
  const h = Vt(i), f = s.registry.fieldControls[i.type] || s.registry.fieldControls[h] || null, m = (x) => {
    a(x), s.host.requestUpdate();
  }, g = f ? De(s, f, i, r, o, l, u, m) : Ge(s, { ...t, setValue: m });
  return je(s, i, n, g, e);
}
function Je(s, t, e, i, r, n) {
  return c`
        <div class="starter-core-row grid gap-4">
            ${t.fields.map((o) => {
    const l = `${i}.${o.handle}`;
    return dt(s, {
      field: o,
      value: e[o.handle],
      errors: s.state.errors.fields[l] || [],
      errorKey: l,
      disabled: r === !0 || s.state.fieldStates[o.id]?.disabled === !0,
      setValue(a) {
        n(o, a);
      }
    });
  })}
        </div>
    `;
}
function Qe(s, t) {
  return c`
        <div class="starter-core-row grid gap-4">
            ${t.fields.map((e) => dt(s, {
    field: e,
    value: s.state.values[e.id],
    errors: s.state.errors.fields[e.id] || [],
    errorKey: e.id,
    disabled: s.state.fieldStates[e.id]?.disabled === !0,
    setValue(i) {
      s.instance.setValue(e.id, i);
    }
  }))}
        </div>
    `;
}
function Xe(s) {
  const t = s.state.definition.pages.find((r) => r.id === s.state.currentPageId);
  if (!t)
    return c``;
  const e = s.registry.regions.pageActions;
  if (e) {
    const r = lt(e);
    return L`<${r}
            .page=${t}
            .state=${s.state}
            .instance=${s.instance}
        ></${r}>`;
  }
  const i = t.actions.secondary.map(
    (r) => c`
            <button
                type="button"
                @click=${() => {
      s.instance.submit(r.type);
    }}
            >
                ${r.label}
            </button>
        `
  );
  return c`
        <div class="formie-page-actions">
            ${i}
            <button type="submit">${t.actions.primary.label}</button>
        </div>
    `;
}
function Ye(s) {
  const t = s.state.definition.pages.find((l) => l.id === s.state.currentPageId && s.state.pageStates[l.id]?.hidden !== !0) || s.state.definition.pages.find((l) => s.state.pageStates[l.id]?.hidden !== !0) || s.state.definition.pages[0];
  if (!t)
    return c``;
  const e = s.state.errors.form, i = s.state.lastSubmitResult?.messages.error, r = !!i && !e.includes(i), n = c`
        ${e.length > 0 ? c`<div class="starter-core-msg starter-core-msg-error mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                  <ul class="list-inside list-disc">
                      ${e.map((l) => c`<li>${l}</li>`)}
                  </ul>
              </div>` : p}
        ${s.state.lastSubmitResult?.messages.notice ? c`<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                  ${s.state.lastSubmitResult.messages.notice}
              </div>` : p}
        ${r ? c`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${i}</div>` : p}
        ${ts(s, t)}
    `, o = s.state.session.tokens.render ?? "";
  return c`
        <form
            class=${s.formClass || "starter-component-form starter-core-preview text-slate-900"}
            data-formie-definition=${s.state.definition.handle}
            data-formie-render-id=${o}
            @submit=${(l) => {
    l.preventDefault(), s.instance.submit();
  }}
        >
            ${n}
        </form>
    `;
}
function ts(s, t) {
  const e = s.registry.regions.page, i = c`
        <div class="starter-core-fields grid gap-4">
            ${t.rows.map((r) => Qe(s, r))}
        </div>
        ${Xe(s)}
    `;
  if (e) {
    const r = lt(e);
    return L`<${r} .page=${t} .state=${s.state}>${i}</${r}>`;
  }
  return c`
        <section data-page-id=${t.id} class="starter-core-page space-y-4">
            ${i}
        </section>
    `;
}
function es(s = "Loading form…") {
  return c`<div class="mt-3 text-sm text-slate-500">${s}</div>`;
}
function ss(s) {
  return c`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${s}</div>`;
}
var is = Object.defineProperty, v = (s, t, e, i) => {
  for (var r = void 0, n = s.length - 1, o; n >= 0; n--)
    (o = s[n]) && (r = o(t, e, r) || r);
  return r && is(t, e, r), r;
};
function Ct(s) {
  return P(s), Nt(s);
}
class b extends M {
  constructor() {
    super(...arguments), this.endpoint = "", this.formHandle = "", this.transport = "rest", this.fetchCredentials = "same-origin", this.formClass = "", this.loadingMessage = "Loading form…", this.loadError = null, this.booting = !1, this.snapshot = null, this.instance = null, this.unsubscribers = [], this.loadGeneration = 0;
  }
  createRenderRoot() {
    return this;
  }
  disconnectedCallback() {
    super.disconnectedCallback(), this.teardown();
  }
  connectedCallback() {
    super.connectedCallback(), this.bootstrap(!1);
  }
  willUpdate(t) {
    if (super.willUpdate(t), !this.hasUpdated)
      return;
    ["endpoint", "formHandle", "siteId", "transport", "fetchCredentials"].some((i) => t.has(i)) && this.bootstrap(!0);
  }
  /** Form instance after a successful load; `null` while loading or after teardown. */
  getFormieInstance() {
    return this.instance;
  }
  /** Reload envelope + form instance from the server. */
  async reload() {
    await this.bootstrap(!0);
  }
  get resolvedRegistry() {
    return this.registry ?? Pe();
  }
  teardown() {
    for (const t of this.unsubscribers)
      t();
    this.unsubscribers = [], this.instance?.destroy(), this.instance = null, this.snapshot = null;
  }
  async bootstrap(t) {
    const e = ++this.loadGeneration, i = this.formHandle.trim(), r = this.endpoint.trim();
    if (!i) {
      this.teardown(), this.booting = !1, this.loadError = "Set `form-handle` on <formie-core-form>.";
      return;
    }
    if (t)
      this.teardown();
    else if (this.instance)
      return;
    this.booting = !0, this.loadError = null;
    const n = this.fetchCredentials, o = this.siteId;
    try {
      const l = {
        endpoint: r,
        formHandle: i,
        ...o !== void 0 ? { siteId: o } : {},
        credentials: n
      }, a = this.transport === "graphql" ? await Jt(l) : await Qt(l);
      if (e !== this.loadGeneration)
        return;
      const d = this.transport === "graphql" ? Xt(l) : Yt(l), u = te({ envelope: a, transport: d });
      this.instance = u;
      for (const h of ee) {
        const f = u.on(h, (m) => {
          this.dispatchEvent(
            new CustomEvent(h, {
              detail: m,
              bubbles: !0,
              composed: !0
            })
          );
        });
        this.unsubscribers.push(f);
      }
      this.unsubscribers.push(
        u.subscribe((h) => {
          this.snapshot = h, this.requestUpdate();
        })
      ), this.booting = !1;
    } catch (l) {
      if (e !== this.loadGeneration)
        return;
      this.teardown(), this.booting = !1, this.loadError = l instanceof Error ? l.message : "Unable to load the form.";
    }
  }
  render() {
    const t = this.resolvedRegistry;
    if (this.loadError) {
      const e = t.regions.errorSummary;
      if (e) {
        const i = Ct(e);
        return L`<${i} .errors=${[this.loadError]} .kind=${"load"}></${i}>`;
      }
      return ss(this.loadError);
    }
    if (this.booting || !this.snapshot || !this.instance) {
      const e = t.regions.loading;
      if (e) {
        const i = Ct(e);
        return L`<${i} .message=${this.loadingMessage}></${i}>`;
      }
      return es(this.loadingMessage);
    }
    return Ye({
      registry: t,
      state: this.snapshot,
      instance: this.instance,
      host: this,
      formClass: this.formClass || ""
    });
  }
}
v([
  $({ type: String })
], b.prototype, "endpoint");
v([
  $({ type: String, attribute: "form-handle" })
], b.prototype, "formHandle");
v([
  $({
    type: Number,
    attribute: "site-id",
    converter: {
      fromAttribute(s) {
        if (s == null || s === "")
          return;
        const t = Number(s);
        return Number.isFinite(t) ? t : void 0;
      }
    }
  })
], b.prototype, "siteId");
v([
  $({
    attribute: "transport",
    converter: {
      fromAttribute(s) {
        return (s ?? "rest").toLowerCase() === "graphql" ? "graphql" : "rest";
      }
    }
  })
], b.prototype, "transport");
v([
  $({
    attribute: "fetch-credentials",
    converter: {
      fromAttribute(s) {
        return s === "omit" || s === "same-origin" || s === "include" ? s : "same-origin";
      }
    }
  })
], b.prototype, "fetchCredentials");
v([
  $({ type: String, attribute: "form-class" })
], b.prototype, "formClass");
v([
  $({ type: String, attribute: "loading-message" })
], b.prototype, "loadingMessage");
v([
  $({ attribute: !1 })
], b.prototype, "registry");
v([
  Z()
], b.prototype, "loadError");
v([
  Z()
], b.prototype, "booting");
v([
  Z()
], b.prototype, "snapshot");
function Y(s) {
  if (s == null || s === "")
    return !1;
  const t = s.toLowerCase();
  return t === "true" || t === "1";
}
function y(s, t, e) {
  typeof e == "string" && e.length > 0 ? s.setAttribute(t, e) : s.removeAttribute(t);
}
function tt(s, t, e) {
  e === !0 ? s.setAttribute(t, "true") : s.removeAttribute(t);
}
function rs(s, t) {
  return t.startsWith("http") ? t : `${s}${t}`;
}
class ns extends HTMLElement {
  constructor() {
    super(), this.mountedInstance = null, this.optionState = {}, this.mountScheduled = null, this.eventUnsubs = [];
  }
  static get observedAttributes() {
    return [
      "mode",
      "transport",
      "theme",
      "form-handle",
      "endpoint",
      "refresh-tokens",
      "static-cache",
      "locale",
      "site-id",
      "auto-visible",
      "base-url"
    ];
  }
  /** Lazily create client + mount container (not in the constructor). */
  ensureInitialized() {
    this.mountRoot || (this.client = se(), this.mountRoot = document.createElement("div"));
  }
  connectedCallback() {
    this.ensureInitialized(), this.style.display = "block", this.contains(this.mountRoot) || this.append(this.mountRoot), this.scheduleMount();
  }
  disconnectedCallback() {
    this.unmount();
  }
  attributeChangedCallback(t, e, i) {
    e === i || !this.isConnected || t === "refresh-tokens" && this.optionState.refreshTokens !== void 0 || t === "static-cache" && this.optionState.staticCache !== void 0 || this.scheduleMount();
  }
  get baseUrl() {
    const t = this.getAttribute("base-url");
    return this.optionState.baseUrl ?? t ?? void 0;
  }
  set baseUrl(t) {
    this.optionState.baseUrl = t, y(this, "base-url", t), this.scheduleMount();
  }
  get transport() {
    const t = this.getAttribute("transport");
    return this.optionState.transport ?? t ?? void 0;
  }
  set transport(t) {
    this.optionState.transport = t, y(this, "transport", t), this.scheduleMount();
  }
  get theme() {
    const t = this.getAttribute("theme");
    return this.optionState.theme ?? t ?? void 0;
  }
  set theme(t) {
    this.optionState.theme = t, y(this, "theme", t), this.scheduleMount();
  }
  get themeConfig() {
    return this.optionState.themeConfig;
  }
  set themeConfig(t) {
    this.optionState.themeConfig = t, this.scheduleMount();
  }
  get payload() {
    return this.optionState.payload;
  }
  set payload(t) {
    this.optionState.payload = t, this.scheduleMount();
  }
  get formHandle() {
    const t = this.getAttribute("form-handle");
    return this.optionState.formHandle ?? t ?? void 0;
  }
  set formHandle(t) {
    this.optionState.formHandle = t, y(this, "form-handle", t), this.scheduleMount();
  }
  get endpoint() {
    const t = this.getAttribute("endpoint");
    return this.optionState.endpoint ?? t ?? void 0;
  }
  set endpoint(t) {
    this.optionState.endpoint = t, y(this, "endpoint", t), this.scheduleMount();
  }
  get staticCache() {
    return this.optionState.staticCache ?? (this.hasAttribute("static-cache") ? Y(this.getAttribute("static-cache")) : void 0);
  }
  set staticCache(t) {
    this.optionState.staticCache = t, tt(this, "static-cache", t), this.scheduleMount();
  }
  get refreshTokens() {
    return this.optionState.refreshTokens ?? (this.hasAttribute("refresh-tokens") ? Y(this.getAttribute("refresh-tokens")) : void 0);
  }
  set refreshTokens(t) {
    this.optionState.refreshTokens = t, tt(this, "refresh-tokens", t), this.scheduleMount();
  }
  get locale() {
    const t = this.getAttribute("locale");
    return this.optionState.locale ?? t ?? void 0;
  }
  set locale(t) {
    this.optionState.locale = t, y(this, "locale", t), this.scheduleMount();
  }
  get siteId() {
    return this.optionState.siteId ?? (this.getAttribute("site-id") ? Number(this.getAttribute("site-id")) : void 0);
  }
  set siteId(t) {
    this.optionState.siteId = t, y(this, "site-id", typeof t == "number" ? String(t) : void 0), this.scheduleMount();
  }
  get autoVisible() {
    return this.optionState.autoVisible ?? (this.hasAttribute("auto-visible") ? Y(this.getAttribute("auto-visible")) : void 0);
  }
  set autoVisible(t) {
    this.optionState.autoVisible = t, tt(this, "auto-visible", t), this.scheduleMount();
  }
  get mode() {
    const t = this.getAttribute("mode");
    return this.optionState.mode ?? t ?? "server-rendered";
  }
  set mode(t) {
    this.optionState.mode = t, y(this, "mode", t), this.scheduleMount();
  }
  getInstance() {
    return this.ensureInitialized(), this.mountedInstance;
  }
  async submit(t = "submit") {
    return this.ensureInitialized(), this.mountedInstance ? this.mountedInstance.submit(t) : null;
  }
  buildOptions() {
    const t = this.baseUrl || "", e = this.transport, i = e === "graphql" ? "/api" : "/actions/formie/server/forms/render", r = rs(t, this.endpoint || i), n = this.staticCache, o = this.refreshTokens;
    return {
      mode: this.mode,
      transport: e,
      theme: this.theme,
      themeConfig: this.themeConfig,
      formHandle: this.formHandle,
      endpoint: r,
      payload: this.payload,
      staticCache: n,
      refreshTokens: o,
      locale: this.locale,
      siteId: this.siteId,
      autoVisible: this.autoVisible ?? !1
    };
  }
  bindInstanceEvents(t) {
    this.eventUnsubs.forEach((e) => e()), this.eventUnsubs = ie.map((e) => t.on(e, (i) => {
      this.dispatchEvent(new CustomEvent(e, {
        detail: i,
        bubbles: !0,
        composed: !0
      }));
    }));
  }
  async scheduleMount() {
    return this.ensureInitialized(), this.mountScheduled ? this.mountScheduled : (this.mountScheduled = Promise.resolve().then(async () => {
      this.mountScheduled = null, this.isConnected && await this.mount();
    }), this.mountScheduled);
  }
  async mount() {
    await this.unmount();
    const t = await this.client.mount(this.mountRoot, this.buildOptions());
    this.mountedInstance = t, this.bindInstanceEvents(t), this.dispatchEvent(new CustomEvent("formie-mounted", {
      detail: {
        id: t.id,
        instance: t
      },
      bubbles: !0,
      composed: !0
    }));
  }
  async unmount() {
    if (this.eventUnsubs.forEach((e) => e()), this.eventUnsubs = [], !this.mountRoot || !this.mountedInstance)
      return;
    const t = this.mountedInstance;
    await this.client.unmount(this.mountRoot), this.mountedInstance = null, this.dispatchEvent(new CustomEvent("formie-unmounted", {
      detail: {
        id: t.id
      },
      bubbles: !0,
      composed: !0
    }));
  }
}
var os = Object.defineProperty, z = (s, t, e, i) => {
  for (var r = void 0, n = s.length - 1, o; n >= 0; n--)
    (o = s[n]) && (r = o(t, e, r) || r);
  return r && os(t, e, r), r;
};
const ht = class ht extends M {
  constructor() {
    super(...arguments), this.modules = [], this.value = "", this.disabled = !1, this.loadError = null, this.pad = null, this.strokeListener = () => {
      this.emitValue();
    }, this.onWinResize = () => {
      const t = this.shadowRoot?.querySelector("canvas");
      t instanceof HTMLCanvasElement && this.resizeCanvas(t);
    };
  }
  async firstUpdated() {
    const t = this.shadowRoot?.querySelector("canvas");
    if (!(!t || !(t instanceof HTMLCanvasElement)))
      try {
        const { default: e } = await import("./signature_pad-CKGlHEaq.js"), i = this.resolveDrawModuleConfig(), r = typeof i?.options == "object" && i.options && typeof i.options.backgroundColor == "string" ? String(i.options.backgroundColor) : "#ffffff", n = typeof i?.options == "object" && i.options && typeof i.options.penColor == "string" ? String(i.options.penColor) : "#000000", o = typeof i?.options == "object" && i.options && Number(i.options.penWeight ?? 2) || 2, l = new e(t, {
          backgroundColor: r,
          penColor: n,
          minWidth: o,
          maxWidth: o
        });
        this.pad = l, l.addEventListener?.("endStroke", this.strokeListener), this.resizeCanvas(t), window.addEventListener("resize", this.onWinResize), this.applySerializedValue();
      } catch (e) {
        this.loadError = e instanceof Error ? e.message : "Signature pad failed to load.";
      }
  }
  disconnectedCallback() {
    super.disconnectedCallback(), window.removeEventListener("resize", this.onWinResize), this.pad?.removeEventListener && this.pad.removeEventListener("endStroke", this.strokeListener), this.pad = null;
  }
  resolveDrawModuleConfig() {
    const t = new Set(this.field.moduleRefs || []), e = this.modules.find((i) => t.has(i.id) && i.capability === "draw-signature");
    return e && typeof e.config == "object" && e.config ? e.config : null;
  }
  resizeCanvas(t) {
    const e = Math.max(window.devicePixelRatio || 1, 1), i = Math.max(1, Math.floor(t.clientWidth || 480)), r = 192, n = t.getContext("2d");
    t.width = i * e, t.height = r * e, n?.scale(e, e), t.style.width = `${i}px`, t.style.height = `${r}px`, this.pad?.fromDataURL?.(this.value || "data:,");
  }
  updated(t) {
    if (t.has("value") && this.pad && this.applySerializedValue(), t.has("disabled") && this.pad) {
      const e = this.shadowRoot?.querySelector("canvas");
      e instanceof HTMLElement && (e.style.pointerEvents = this.disabled ? "none" : "");
    }
  }
  applySerializedValue() {
    if (this.pad) {
      if (!this.value) {
        this.pad.isEmpty() || this.pad.clear();
        return;
      }
      try {
        this.pad.fromDataURL(this.value);
      } catch {
      }
    }
  }
  emitValue() {
    if (!this.pad || this.pad.isEmpty()) {
      this.dispatchEvent(
        new CustomEvent(j, {
          detail: "",
          bubbles: !0,
          composed: !0
        })
      );
      return;
    }
    this.dispatchEvent(
      new CustomEvent(j, {
        detail: this.pad.toDataURL(),
        bubbles: !0,
        composed: !0
      })
    );
  }
  render() {
    return this.loadError ? c`<div class="err">${this.loadError}</div>` : c`
            <div class="wrap">
                <canvas></canvas>
                <button type="button" ?disabled=${this.disabled} @click=${() => {
      this.pad?.clear(), this.dispatchEvent(
        new CustomEvent(j, {
          detail: "",
          bubbles: !0,
          composed: !0
        })
      );
    }}>Clear</button>
            </div>
        `;
  }
};
ht.styles = ne`
        :host {
            display: block;
        }
        .wrap {
            display: grid;
            gap: 0.5rem;
        }
        canvas {
            width: 100%;
            height: 12rem;
            border-radius: 0.75rem;
            border: 1px dashed #fda4af;
            background: linear-gradient(180deg, #fff1f2 0%, #fff 100%);
        }
        button {
            justify-self: start;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 0.45rem 0.85rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .err {
            font-size: 0.875rem;
            color: #b91c1c;
        }
    `;
let A = ht;
z([
  $({ attribute: !1 })
], A.prototype, "field");
z([
  $({ attribute: !1 })
], A.prototype, "modules");
z([
  $({ type: String })
], A.prototype, "value");
z([
  $({ type: Boolean })
], A.prototype, "disabled");
z([
  Z()
], A.prototype, "loadError");
let wt = !1;
function fs() {
  wt || (wt = !0, customElements.get("formie-form") || customElements.define("formie-form", ns), customElements.get("formie-internal-signature") || customElements.define("formie-internal-signature", A), customElements.get("formie-core-form") || customElements.define("formie-core-form", b));
}
export {
  j as FORMIE_CONTROL_VALUE_EVENT,
  b as FormieCoreForm,
  ns as FormieFormElement,
  A as FormieInternalSignature,
  J as FormieRegistry,
  P as assertValidCustomElementName,
  gs as createFormieClient,
  us as createFormieRegistry,
  Pe as getFormieRegistry,
  ps as isFieldDefinition,
  fs as registerFormieWebComponents,
  ss as renderErrorView,
  Ye as renderFormView,
  es as renderLoadingView,
  Vt as resolveFieldRendererType
};
