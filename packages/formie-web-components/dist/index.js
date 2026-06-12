import { FRONTEND_CLIENT_EVENT_NAMES as e, compositePartDefinitions as t, createFrontendFormInstance as n, createGraphqlFrontendTransport as r, createRepeaterRowValue as i, createRestFrontendTransport as a, isCompositeField as o, isFileField as s, isKnownFrontendFieldType as c, isRepeatableField as l, loadFrontendEnvelope as u, loadGraphqlFrontendEnvelope as d, repeaterRowDefinitions as f } from "@verbb/formie-core";
import { FORMIE_HTML_EVENT_NAMES as p, createFormieClient as ee, createFormieClient as te } from "@verbb/formie-browser";
//#region ../../node_modules/@lit/reactive-element/css-tag.js
var m = globalThis, h = m.ShadowRoot && (m.ShadyCSS === void 0 || m.ShadyCSS.nativeShadow) && "adoptedStyleSheets" in Document.prototype && "replace" in CSSStyleSheet.prototype, g = Symbol(), ne = /* @__PURE__ */ new WeakMap(), re = class {
	constructor(e, t, n) {
		if (this._$cssResult$ = !0, n !== g) throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");
		this.cssText = e, this.t = t;
	}
	get styleSheet() {
		let e = this.o, t = this.t;
		if (h && e === void 0) {
			let n = t !== void 0 && t.length === 1;
			n && (e = ne.get(t)), e === void 0 && ((this.o = e = new CSSStyleSheet()).replaceSync(this.cssText), n && ne.set(t, e));
		}
		return e;
	}
	toString() {
		return this.cssText;
	}
}, ie = (e) => new re(typeof e == "string" ? e : e + "", void 0, g), ae = (e, ...t) => new re(e.length === 1 ? e[0] : t.reduce((t, n, r) => t + ((e) => {
	if (!0 === e._$cssResult$) return e.cssText;
	if (typeof e == "number") return e;
	throw Error("Value passed to 'css' function must be a 'css' function result: " + e + ". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.");
})(n) + e[r + 1], e[0]), e, g), oe = (e, t) => {
	if (h) e.adoptedStyleSheets = t.map((e) => e instanceof CSSStyleSheet ? e : e.styleSheet);
	else for (let n of t) {
		let t = document.createElement("style"), r = m.litNonce;
		r !== void 0 && t.setAttribute("nonce", r), t.textContent = n.cssText, e.appendChild(t);
	}
}, se = h ? (e) => e : (e) => e instanceof CSSStyleSheet ? ((e) => {
	let t = "";
	for (let n of e.cssRules) t += n.cssText;
	return ie(t);
})(e) : e, { is: ce, defineProperty: le, getOwnPropertyDescriptor: ue, getOwnPropertyNames: de, getOwnPropertySymbols: fe, getPrototypeOf: pe } = Object, _ = globalThis, me = _.trustedTypes, he = me ? me.emptyScript : "", ge = _.reactiveElementPolyfillSupport, v = (e, t) => e, y = {
	toAttribute(e, t) {
		switch (t) {
			case Boolean:
				e = e ? he : null;
				break;
			case Object:
			case Array: e = e == null ? e : JSON.stringify(e);
		}
		return e;
	},
	fromAttribute(e, t) {
		let n = e;
		switch (t) {
			case Boolean:
				n = e !== null;
				break;
			case Number:
				n = e === null ? null : Number(e);
				break;
			case Object:
			case Array: try {
				n = JSON.parse(e);
			} catch {
				n = null;
			}
		}
		return n;
	}
}, b = (e, t) => !ce(e, t), _e = {
	attribute: !0,
	type: String,
	converter: y,
	reflect: !1,
	useDefault: !1,
	hasChanged: b
};
Symbol.metadata ??= Symbol("metadata"), _.litPropertyMetadata ??= /* @__PURE__ */ new WeakMap();
var x = class extends HTMLElement {
	static addInitializer(e) {
		this._$Ei(), (this.l ??= []).push(e);
	}
	static get observedAttributes() {
		return this.finalize(), this._$Eh && [...this._$Eh.keys()];
	}
	static createProperty(e, t = _e) {
		if (t.state && (t.attribute = !1), this._$Ei(), this.prototype.hasOwnProperty(e) && ((t = Object.create(t)).wrapped = !0), this.elementProperties.set(e, t), !t.noAccessor) {
			let n = Symbol(), r = this.getPropertyDescriptor(e, n, t);
			r !== void 0 && le(this.prototype, e, r);
		}
	}
	static getPropertyDescriptor(e, t, n) {
		let { get: r, set: i } = ue(this.prototype, e) ?? {
			get() {
				return this[t];
			},
			set(e) {
				this[t] = e;
			}
		};
		return {
			get: r,
			set(t) {
				let a = r?.call(this);
				i?.call(this, t), this.requestUpdate(e, a, n);
			},
			configurable: !0,
			enumerable: !0
		};
	}
	static getPropertyOptions(e) {
		return this.elementProperties.get(e) ?? _e;
	}
	static _$Ei() {
		if (this.hasOwnProperty(v("elementProperties"))) return;
		let e = pe(this);
		e.finalize(), e.l !== void 0 && (this.l = [...e.l]), this.elementProperties = new Map(e.elementProperties);
	}
	static finalize() {
		if (this.hasOwnProperty(v("finalized"))) return;
		if (this.finalized = !0, this._$Ei(), this.hasOwnProperty(v("properties"))) {
			let e = this.properties, t = [...de(e), ...fe(e)];
			for (let n of t) this.createProperty(n, e[n]);
		}
		let e = this[Symbol.metadata];
		if (e !== null) {
			let t = litPropertyMetadata.get(e);
			if (t !== void 0) for (let [e, n] of t) this.elementProperties.set(e, n);
		}
		this._$Eh = /* @__PURE__ */ new Map();
		for (let [e, t] of this.elementProperties) {
			let n = this._$Eu(e, t);
			n !== void 0 && this._$Eh.set(n, e);
		}
		this.elementStyles = this.finalizeStyles(this.styles);
	}
	static finalizeStyles(e) {
		let t = [];
		if (Array.isArray(e)) {
			let n = new Set(e.flat(Infinity).reverse());
			for (let e of n) t.unshift(se(e));
		} else e !== void 0 && t.push(se(e));
		return t;
	}
	static _$Eu(e, t) {
		let n = t.attribute;
		return !1 === n ? void 0 : typeof n == "string" ? n : typeof e == "string" ? e.toLowerCase() : void 0;
	}
	constructor() {
		super(), this._$Ep = void 0, this.isUpdatePending = !1, this.hasUpdated = !1, this._$Em = null, this._$Ev();
	}
	_$Ev() {
		this._$ES = new Promise((e) => this.enableUpdating = e), this._$AL = /* @__PURE__ */ new Map(), this._$E_(), this.requestUpdate(), this.constructor.l?.forEach((e) => e(this));
	}
	addController(e) {
		(this._$EO ??= /* @__PURE__ */ new Set()).add(e), this.renderRoot !== void 0 && this.isConnected && e.hostConnected?.();
	}
	removeController(e) {
		this._$EO?.delete(e);
	}
	_$E_() {
		let e = /* @__PURE__ */ new Map(), t = this.constructor.elementProperties;
		for (let n of t.keys()) this.hasOwnProperty(n) && (e.set(n, this[n]), delete this[n]);
		e.size > 0 && (this._$Ep = e);
	}
	createRenderRoot() {
		let e = this.shadowRoot ?? this.attachShadow(this.constructor.shadowRootOptions);
		return oe(e, this.constructor.elementStyles), e;
	}
	connectedCallback() {
		this.renderRoot ??= this.createRenderRoot(), this.enableUpdating(!0), this._$EO?.forEach((e) => e.hostConnected?.());
	}
	enableUpdating(e) {}
	disconnectedCallback() {
		this._$EO?.forEach((e) => e.hostDisconnected?.());
	}
	attributeChangedCallback(e, t, n) {
		this._$AK(e, n);
	}
	_$ET(e, t) {
		let n = this.constructor.elementProperties.get(e), r = this.constructor._$Eu(e, n);
		if (r !== void 0 && !0 === n.reflect) {
			let i = (n.converter?.toAttribute === void 0 ? y : n.converter).toAttribute(t, n.type);
			this._$Em = e, i == null ? this.removeAttribute(r) : this.setAttribute(r, i), this._$Em = null;
		}
	}
	_$AK(e, t) {
		let n = this.constructor, r = n._$Eh.get(e);
		if (r !== void 0 && this._$Em !== r) {
			let e = n.getPropertyOptions(r), i = typeof e.converter == "function" ? { fromAttribute: e.converter } : e.converter?.fromAttribute === void 0 ? y : e.converter;
			this._$Em = r;
			let a = i.fromAttribute(t, e.type);
			this[r] = a ?? this._$Ej?.get(r) ?? a, this._$Em = null;
		}
	}
	requestUpdate(e, t, n, r = !1, i) {
		if (e !== void 0) {
			let a = this.constructor;
			if (!1 === r && (i = this[e]), n ??= a.getPropertyOptions(e), !((n.hasChanged ?? b)(i, t) || n.useDefault && n.reflect && i === this._$Ej?.get(e) && !this.hasAttribute(a._$Eu(e, n)))) return;
			this.C(e, t, n);
		}
		!1 === this.isUpdatePending && (this._$ES = this._$EP());
	}
	C(e, t, { useDefault: n, reflect: r, wrapped: i }, a) {
		n && !(this._$Ej ??= /* @__PURE__ */ new Map()).has(e) && (this._$Ej.set(e, a ?? t ?? this[e]), !0 !== i || a !== void 0) || (this._$AL.has(e) || (this.hasUpdated || n || (t = void 0), this._$AL.set(e, t)), !0 === r && this._$Em !== e && (this._$Eq ??= /* @__PURE__ */ new Set()).add(e));
	}
	async _$EP() {
		this.isUpdatePending = !0;
		try {
			await this._$ES;
		} catch (e) {
			Promise.reject(e);
		}
		let e = this.scheduleUpdate();
		return e != null && await e, !this.isUpdatePending;
	}
	scheduleUpdate() {
		return this.performUpdate();
	}
	performUpdate() {
		if (!this.isUpdatePending) return;
		if (!this.hasUpdated) {
			if (this.renderRoot ??= this.createRenderRoot(), this._$Ep) {
				for (let [e, t] of this._$Ep) this[e] = t;
				this._$Ep = void 0;
			}
			let e = this.constructor.elementProperties;
			if (e.size > 0) for (let [t, n] of e) {
				let { wrapped: e } = n, r = this[t];
				!0 !== e || this._$AL.has(t) || r === void 0 || this.C(t, void 0, n, r);
			}
		}
		let e = !1, t = this._$AL;
		try {
			e = this.shouldUpdate(t), e ? (this.willUpdate(t), this._$EO?.forEach((e) => e.hostUpdate?.()), this.update(t)) : this._$EM();
		} catch (t) {
			throw e = !1, this._$EM(), t;
		}
		e && this._$AE(t);
	}
	willUpdate(e) {}
	_$AE(e) {
		this._$EO?.forEach((e) => e.hostUpdated?.()), this.hasUpdated || (this.hasUpdated = !0, this.firstUpdated(e)), this.updated(e);
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
	shouldUpdate(e) {
		return !0;
	}
	update(e) {
		this._$Eq &&= this._$Eq.forEach((e) => this._$ET(e, this[e])), this._$EM();
	}
	updated(e) {}
	firstUpdated(e) {}
};
x.elementStyles = [], x.shadowRootOptions = { mode: "open" }, x[v("elementProperties")] = /* @__PURE__ */ new Map(), x[v("finalized")] = /* @__PURE__ */ new Map(), ge?.({ ReactiveElement: x }), (_.reactiveElementVersions ??= []).push("2.1.2");
//#endregion
//#region ../../node_modules/lit-html/lit-html.js
var ve = globalThis, ye = (e) => e, S = ve.trustedTypes, be = S ? S.createPolicy("lit-html", { createHTML: (e) => e }) : void 0, xe = "$lit$", C = `lit$${Math.random().toFixed(9).slice(2)}$`, Se = "?" + C, Ce = `<${Se}>`, w = document, T = () => w.createComment(""), E = (e) => e === null || typeof e != "object" && typeof e != "function", D = Array.isArray, we = (e) => D(e) || typeof e?.[Symbol.iterator] == "function", O = "[ 	\n\f\r]", k = /<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g, Te = /-->/g, Ee = />/g, A = RegExp(`>|${O}(?:([^\\s"'>=/]+)(${O}*=${O}*(?:[^ \t\n\f\r"'\`<>=]|("|')|))|$)`, "g"), De = /'/g, Oe = /"/g, ke = /^(?:script|style|textarea|title)$/i, j = ((e) => (t, ...n) => ({
	_$litType$: e,
	strings: t,
	values: n
}))(1), M = Symbol.for("lit-noChange"), N = Symbol.for("lit-nothing"), Ae = /* @__PURE__ */ new WeakMap(), P = w.createTreeWalker(w, 129);
function je(e, t) {
	if (!D(e) || !e.hasOwnProperty("raw")) throw Error("invalid template strings array");
	return be === void 0 ? t : be.createHTML(t);
}
var Me = (e, t) => {
	let n = e.length - 1, r = [], i, a = t === 2 ? "<svg>" : t === 3 ? "<math>" : "", o = k;
	for (let t = 0; t < n; t++) {
		let n = e[t], s, c, l = -1, u = 0;
		for (; u < n.length && (o.lastIndex = u, c = o.exec(n), c !== null);) u = o.lastIndex, o === k ? c[1] === "!--" ? o = Te : c[1] === void 0 ? c[2] === void 0 ? c[3] !== void 0 && (o = A) : (ke.test(c[2]) && (i = RegExp("</" + c[2], "g")), o = A) : o = Ee : o === A ? c[0] === ">" ? (o = i ?? k, l = -1) : c[1] === void 0 ? l = -2 : (l = o.lastIndex - c[2].length, s = c[1], o = c[3] === void 0 ? A : c[3] === "\"" ? Oe : De) : o === Oe || o === De ? o = A : o === Te || o === Ee ? o = k : (o = A, i = void 0);
		let d = o === A && e[t + 1].startsWith("/>") ? " " : "";
		a += o === k ? n + Ce : l >= 0 ? (r.push(s), n.slice(0, l) + xe + n.slice(l) + C + d) : n + C + (l === -2 ? t : d);
	}
	return [je(e, a + (e[n] || "<?>") + (t === 2 ? "</svg>" : t === 3 ? "</math>" : "")), r];
}, F = class e {
	constructor({ strings: t, _$litType$: n }, r) {
		let i;
		this.parts = [];
		let a = 0, o = 0, s = t.length - 1, c = this.parts, [l, u] = Me(t, n);
		if (this.el = e.createElement(l, r), P.currentNode = this.el.content, n === 2 || n === 3) {
			let e = this.el.content.firstChild;
			e.replaceWith(...e.childNodes);
		}
		for (; (i = P.nextNode()) !== null && c.length < s;) {
			if (i.nodeType === 1) {
				if (i.hasAttributes()) for (let e of i.getAttributeNames()) if (e.endsWith(xe)) {
					let t = u[o++], n = i.getAttribute(e).split(C), r = /([.?@])?(.*)/.exec(t);
					c.push({
						type: 1,
						index: a,
						name: r[2],
						strings: n,
						ctor: r[1] === "." ? Pe : r[1] === "?" ? Fe : r[1] === "@" ? Ie : R
					}), i.removeAttribute(e);
				} else e.startsWith(C) && (c.push({
					type: 6,
					index: a
				}), i.removeAttribute(e));
				if (ke.test(i.tagName)) {
					let e = i.textContent.split(C), t = e.length - 1;
					if (t > 0) {
						i.textContent = S ? S.emptyScript : "";
						for (let n = 0; n < t; n++) i.append(e[n], T()), P.nextNode(), c.push({
							type: 2,
							index: ++a
						});
						i.append(e[t], T());
					}
				}
			} else if (i.nodeType === 8) if (i.data === Se) c.push({
				type: 2,
				index: a
			});
			else {
				let e = -1;
				for (; (e = i.data.indexOf(C, e + 1)) !== -1;) c.push({
					type: 7,
					index: a
				}), e += C.length - 1;
			}
			a++;
		}
	}
	static createElement(e, t) {
		let n = w.createElement("template");
		return n.innerHTML = e, n;
	}
};
function I(e, t, n = e, r) {
	if (t === M) return t;
	let i = r === void 0 ? n._$Cl : n._$Co?.[r], a = E(t) ? void 0 : t._$litDirective$;
	return i?.constructor !== a && (i?._$AO?.(!1), a === void 0 ? i = void 0 : (i = new a(e), i._$AT(e, n, r)), r === void 0 ? n._$Cl = i : (n._$Co ??= [])[r] = i), i !== void 0 && (t = I(e, i._$AS(e, t.values), i, r)), t;
}
var Ne = class {
	constructor(e, t) {
		this._$AV = [], this._$AN = void 0, this._$AD = e, this._$AM = t;
	}
	get parentNode() {
		return this._$AM.parentNode;
	}
	get _$AU() {
		return this._$AM._$AU;
	}
	u(e) {
		let { el: { content: t }, parts: n } = this._$AD, r = (e?.creationScope ?? w).importNode(t, !0);
		P.currentNode = r;
		let i = P.nextNode(), a = 0, o = 0, s = n[0];
		for (; s !== void 0;) {
			if (a === s.index) {
				let t;
				s.type === 2 ? t = new L(i, i.nextSibling, this, e) : s.type === 1 ? t = new s.ctor(i, s.name, s.strings, this, e) : s.type === 6 && (t = new Le(i, this, e)), this._$AV.push(t), s = n[++o];
			}
			a !== s?.index && (i = P.nextNode(), a++);
		}
		return P.currentNode = w, r;
	}
	p(e) {
		let t = 0;
		for (let n of this._$AV) n !== void 0 && (n.strings === void 0 ? n._$AI(e[t]) : (n._$AI(e, n, t), t += n.strings.length - 2)), t++;
	}
}, L = class e {
	get _$AU() {
		return this._$AM?._$AU ?? this._$Cv;
	}
	constructor(e, t, n, r) {
		this.type = 2, this._$AH = N, this._$AN = void 0, this._$AA = e, this._$AB = t, this._$AM = n, this.options = r, this._$Cv = r?.isConnected ?? !0;
	}
	get parentNode() {
		let e = this._$AA.parentNode, t = this._$AM;
		return t !== void 0 && e?.nodeType === 11 && (e = t.parentNode), e;
	}
	get startNode() {
		return this._$AA;
	}
	get endNode() {
		return this._$AB;
	}
	_$AI(e, t = this) {
		e = I(this, e, t), E(e) ? e === N || e == null || e === "" ? (this._$AH !== N && this._$AR(), this._$AH = N) : e !== this._$AH && e !== M && this._(e) : e._$litType$ === void 0 ? e.nodeType === void 0 ? we(e) ? this.k(e) : this._(e) : this.T(e) : this.$(e);
	}
	O(e) {
		return this._$AA.parentNode.insertBefore(e, this._$AB);
	}
	T(e) {
		this._$AH !== e && (this._$AR(), this._$AH = this.O(e));
	}
	_(e) {
		this._$AH !== N && E(this._$AH) ? this._$AA.nextSibling.data = e : this.T(w.createTextNode(e)), this._$AH = e;
	}
	$(e) {
		let { values: t, _$litType$: n } = e, r = typeof n == "number" ? this._$AC(e) : (n.el === void 0 && (n.el = F.createElement(je(n.h, n.h[0]), this.options)), n);
		if (this._$AH?._$AD === r) this._$AH.p(t);
		else {
			let e = new Ne(r, this), n = e.u(this.options);
			e.p(t), this.T(n), this._$AH = e;
		}
	}
	_$AC(e) {
		let t = Ae.get(e.strings);
		return t === void 0 && Ae.set(e.strings, t = new F(e)), t;
	}
	k(t) {
		D(this._$AH) || (this._$AH = [], this._$AR());
		let n = this._$AH, r, i = 0;
		for (let a of t) i === n.length ? n.push(r = new e(this.O(T()), this.O(T()), this, this.options)) : r = n[i], r._$AI(a), i++;
		i < n.length && (this._$AR(r && r._$AB.nextSibling, i), n.length = i);
	}
	_$AR(e = this._$AA.nextSibling, t) {
		for (this._$AP?.(!1, !0, t); e !== this._$AB;) {
			let t = ye(e).nextSibling;
			ye(e).remove(), e = t;
		}
	}
	setConnected(e) {
		this._$AM === void 0 && (this._$Cv = e, this._$AP?.(e));
	}
}, R = class {
	get tagName() {
		return this.element.tagName;
	}
	get _$AU() {
		return this._$AM._$AU;
	}
	constructor(e, t, n, r, i) {
		this.type = 1, this._$AH = N, this._$AN = void 0, this.element = e, this.name = t, this._$AM = r, this.options = i, n.length > 2 || n[0] !== "" || n[1] !== "" ? (this._$AH = Array(n.length - 1).fill(/* @__PURE__ */ new String()), this.strings = n) : this._$AH = N;
	}
	_$AI(e, t = this, n, r) {
		let i = this.strings, a = !1;
		if (i === void 0) e = I(this, e, t, 0), a = !E(e) || e !== this._$AH && e !== M, a && (this._$AH = e);
		else {
			let r = e, o, s;
			for (e = i[0], o = 0; o < i.length - 1; o++) s = I(this, r[n + o], t, o), s === M && (s = this._$AH[o]), a ||= !E(s) || s !== this._$AH[o], s === N ? e = N : e !== N && (e += (s ?? "") + i[o + 1]), this._$AH[o] = s;
		}
		a && !r && this.j(e);
	}
	j(e) {
		e === N ? this.element.removeAttribute(this.name) : this.element.setAttribute(this.name, e ?? "");
	}
}, Pe = class extends R {
	constructor() {
		super(...arguments), this.type = 3;
	}
	j(e) {
		this.element[this.name] = e === N ? void 0 : e;
	}
}, Fe = class extends R {
	constructor() {
		super(...arguments), this.type = 4;
	}
	j(e) {
		this.element.toggleAttribute(this.name, !!e && e !== N);
	}
}, Ie = class extends R {
	constructor(e, t, n, r, i) {
		super(e, t, n, r, i), this.type = 5;
	}
	_$AI(e, t = this) {
		if ((e = I(this, e, t, 0) ?? N) === M) return;
		let n = this._$AH, r = e === N && n !== N || e.capture !== n.capture || e.once !== n.once || e.passive !== n.passive, i = e !== N && (n === N || r);
		r && this.element.removeEventListener(this.name, this, n), i && this.element.addEventListener(this.name, this, e), this._$AH = e;
	}
	handleEvent(e) {
		typeof this._$AH == "function" ? this._$AH.call(this.options?.host ?? this.element, e) : this._$AH.handleEvent(e);
	}
}, Le = class {
	constructor(e, t, n) {
		this.element = e, this.type = 6, this._$AN = void 0, this._$AM = t, this.options = n;
	}
	get _$AU() {
		return this._$AM._$AU;
	}
	_$AI(e) {
		I(this, e);
	}
}, Re = {
	M: xe,
	P: C,
	A: Se,
	C: 1,
	L: Me,
	R: Ne,
	D: we,
	V: I,
	I: L,
	H: R,
	N: Fe,
	U: Ie,
	B: Pe,
	F: Le
}, ze = ve.litHtmlPolyfillSupport;
ze?.(F, L), (ve.litHtmlVersions ??= []).push("3.3.2");
var Be = (e, t, n) => {
	let r = n?.renderBefore ?? t, i = r._$litPart$;
	if (i === void 0) {
		let e = n?.renderBefore ?? null;
		r._$litPart$ = i = new L(t.insertBefore(T(), e), e, void 0, n ?? {});
	}
	return i._$AI(e), i;
}, z = globalThis, B = class extends x {
	constructor() {
		super(...arguments), this.renderOptions = { host: this }, this._$Do = void 0;
	}
	createRenderRoot() {
		let e = super.createRenderRoot();
		return this.renderOptions.renderBefore ??= e.firstChild, e;
	}
	update(e) {
		let t = this.render();
		this.hasUpdated || (this.renderOptions.isConnected = this.isConnected), super.update(e), this._$Do = Be(t, this.renderRoot, this.renderOptions);
	}
	connectedCallback() {
		super.connectedCallback(), this._$Do?.setConnected(!0);
	}
	disconnectedCallback() {
		super.disconnectedCallback(), this._$Do?.setConnected(!1);
	}
	render() {
		return M;
	}
};
B._$litElement$ = !0, B.finalized = !0, z.litElementHydrateSupport?.({ LitElement: B });
var Ve = z.litElementPolyfillSupport;
Ve?.({ LitElement: B }), (z.litElementVersions ??= []).push("4.2.2");
//#endregion
//#region ../../node_modules/lit-html/static.js
var He = Symbol.for(""), Ue = (e) => {
	if (e?.r === He) return e?._$litStatic$;
}, We = (e) => ({
	_$litStatic$: e,
	r: He
}), Ge = /* @__PURE__ */ new Map(), V = ((e) => (t, ...n) => {
	let r = n.length, i, a, o = [], s = [], c, l = 0, u = !1;
	for (; l < r;) {
		for (c = t[l]; l < r && (a = n[l], i = Ue(a)) !== void 0;) c += i + t[++l], u = !0;
		l !== r && s.push(a), o.push(c), l++;
	}
	if (l === r && o.push(t[r]), u) {
		let e = o.join("$$lit$$");
		(t = Ge.get(e)) === void 0 && (o.raw = o, Ge.set(e, t = o)), n = s;
	}
	return e(t, ...n);
})(j), Ke = {
	attribute: !0,
	type: String,
	converter: y,
	reflect: !1,
	hasChanged: b
}, qe = (e = Ke, t, n) => {
	let { kind: r, metadata: i } = n, a = globalThis.litPropertyMetadata.get(i);
	if (a === void 0 && globalThis.litPropertyMetadata.set(i, a = /* @__PURE__ */ new Map()), r === "setter" && ((e = Object.create(e)).wrapped = !0), a.set(n.name, e), r === "accessor") {
		let { name: r } = n;
		return {
			set(n) {
				let i = t.get.call(this);
				t.set.call(this, n), this.requestUpdate(r, i, e, !0, n);
			},
			init(t) {
				return t !== void 0 && this.C(r, void 0, e, t), t;
			}
		};
	}
	if (r === "setter") {
		let { name: r } = n;
		return function(n) {
			let i = this[r];
			t.call(this, n), this.requestUpdate(r, i, e, !0, n);
		};
	}
	throw Error("Unsupported decorator location: " + r);
};
function H(e) {
	return (t, n) => typeof n == "object" ? qe(e, t, n) : ((e, t, n) => {
		let r = t.hasOwnProperty(n);
		return t.constructor.createProperty(n, e), r ? Object.getOwnPropertyDescriptor(t, n) : void 0;
	})(e, t, n);
}
//#endregion
//#region ../../node_modules/@lit/reactive-element/decorators/state.js
function U(e) {
	return H({
		...e,
		state: !0,
		attribute: !1
	});
}
//#endregion
//#region src/registry.ts
var Je = /^[a-z][a-z0-9]*(-[a-z0-9]+)+$/;
function W(e) {
	if (!Je.test(e)) throw Error(`[Formie WC] Invalid custom element tag "${e}". Use lowercase hyphenated names (e.g. my-text-field).`);
}
var G = class e {
	constructor() {
		this.fieldControls = {}, this.fieldTag = null, this.regions = {};
	}
	registerFieldControl(e, t) {
		return W(t), this.fieldControls[e] = t, this;
	}
	registerField(e) {
		return W(e), this.fieldTag = e, this;
	}
	registerRegion(e, t) {
		return W(t), this.regions[e] = t, this;
	}
	clone() {
		let t = new e();
		return t.fieldControls = { ...this.fieldControls }, t.fieldTag = this.fieldTag, t.regions = { ...this.regions }, t;
	}
}, Ye = new G();
function Xe() {
	return Ye;
}
function Ze() {
	return new G();
}
//#endregion
//#region ../../node_modules/lit-html/directive-helpers.js
var { I: Qe } = Re, $e = (e) => e.strings === void 0, et = {
	ATTRIBUTE: 1,
	CHILD: 2,
	PROPERTY: 3,
	BOOLEAN_ATTRIBUTE: 4,
	EVENT: 5,
	ELEMENT: 6
}, tt = (e) => (...t) => ({
	_$litDirective$: e,
	values: t
}), nt = class {
	constructor(e) {}
	get _$AU() {
		return this._$AM._$AU;
	}
	_$AT(e, t, n) {
		this._$Ct = e, this._$AM = t, this._$Ci = n;
	}
	_$AS(e, t) {
		return this.update(e, t);
	}
	update(e, t) {
		return this.render(...t);
	}
}, K = (e, t) => {
	let n = e._$AN;
	if (n === void 0) return !1;
	for (let e of n) e._$AO?.(t, !1), K(e, t);
	return !0;
}, q = (e) => {
	let t, n;
	do {
		if ((t = e._$AM) === void 0) break;
		n = t._$AN, n.delete(e), e = t;
	} while (n?.size === 0);
}, rt = (e) => {
	for (let t; t = e._$AM; e = t) {
		let n = t._$AN;
		if (n === void 0) t._$AN = n = /* @__PURE__ */ new Set();
		else if (n.has(e)) break;
		n.add(e), ot(t);
	}
};
function it(e) {
	this._$AN === void 0 ? this._$AM = e : (q(this), this._$AM = e, rt(this));
}
function at(e, t = !1, n = 0) {
	let r = this._$AH, i = this._$AN;
	if (i !== void 0 && i.size !== 0) if (t) if (Array.isArray(r)) for (let e = n; e < r.length; e++) K(r[e], !1), q(r[e]);
	else r != null && (K(r, !1), q(r));
	else K(this, e);
}
var ot = (e) => {
	e.type == et.CHILD && (e._$AP ??= at, e._$AQ ??= it);
}, st = class extends nt {
	constructor() {
		super(...arguments), this._$AN = void 0;
	}
	_$AT(e, t, n) {
		super._$AT(e, t, n), rt(this), this.isConnected = e._$AU;
	}
	_$AO(e, t = !0) {
		e !== this.isConnected && (this.isConnected = e, e ? this.reconnected?.() : this.disconnected?.()), t && (K(this, e), q(this));
	}
	setValue(e) {
		if ($e(this._$Ct)) this._$Ct._$AI(e, this);
		else {
			let t = [...this._$Ct._$AH];
			t[this._$Ci] = e, this._$Ct._$AI(t, this, 0);
		}
	}
	disconnected() {}
	reconnected() {}
}, ct = /* @__PURE__ */ new WeakMap(), lt = tt(class extends st {
	render(e) {
		return N;
	}
	update(e, [t]) {
		let n = t !== this.G;
		return n && this.G !== void 0 && this.rt(void 0), (n || this.lt !== this.ct) && (this.G = t, this.ht = e.options?.host, this.rt(this.ct = e.element)), N;
	}
	rt(e) {
		if (this.isConnected || (e = void 0), typeof this.G == "function") {
			let t = this.ht ?? globalThis, n = ct.get(t);
			n === void 0 && (n = /* @__PURE__ */ new WeakMap(), ct.set(t, n)), n.get(this.G) !== void 0 && this.G.call(this.ht, void 0), n.set(this.G, e), e !== void 0 && this.G.call(this.ht, e);
		} else this.G.value = e;
	}
	get lt() {
		return typeof this.G == "function" ? ct.get(this.ht ?? globalThis)?.get(this.G) : this.G?.value;
	}
	disconnected() {
		this.lt === this.ct && this.rt(void 0);
	}
	reconnected() {
		this.rt(this.ct);
	}
}), ut = class extends nt {
	constructor(e) {
		if (super(e), this.it = N, e.type !== et.CHILD) throw Error(this.constructor.directiveName + "() can only be used in child bindings");
	}
	render(e) {
		if (e === N || e == null) return this._t = void 0, this.it = e;
		if (e === M) return e;
		if (typeof e != "string") throw Error(this.constructor.directiveName + "() called with a non-string value");
		if (e === this.it) return this._t;
		this.it = e;
		let t = [e];
		return t.raw = t, this._t = {
			_$litType$: this.constructor.resultType,
			strings: t,
			values: []
		};
	}
};
ut.directiveName = "unsafeHTML", ut.resultType = 1;
var dt = tt(ut);
//#endregion
//#region src/field-utils.ts
function ft(e) {
	return !!e && typeof e == "object" && "id" in e && "handle" in e && "type" in e;
}
function pt(e) {
	if (c(e.type)) return e.type;
	let t = e.input && typeof e.input == "object" ? e.input : {}, n = typeof t.fieldKind == "string" ? t.fieldKind : null;
	return n === "text" ? "single-line-text" : n === "textarea" ? "multi-line-text" : n === "boolean" ? "agree" : n === "file" ? "file" : e.type;
}
//#endregion
//#region src/types.ts
var J = "formie-control-value-change";
//#endregion
//#region src/render-view.ts
function mt(e) {
	return W(e), We(e);
}
function ht(e, t, n, r, i, a, o, s) {
	if (!e || !(e instanceof HTMLElement)) return;
	let c = t.toLowerCase(), l = e.firstElementChild;
	(!l || l.tagName.toLowerCase() !== c) && (e.replaceChildren(), l = document.createElement(t), l.addEventListener(J, (e) => {
		s(e.detail);
	}), e.append(l)), l.field = n, l.value = r, l.errorKey = i, l.disabled = a, l.hidden = o;
}
function gt(e, t, n, r, i, a, o, s) {
	return j`<div
        class="starter-core-registry-host min-w-0"
        ${lt((e) => {
		ht(e, t, n, r, i, a, o, s);
	})}
    ></div>`;
}
function _t(e, t, n, r, i = "default") {
	if (i === "compositePart") return vt(t, n, r);
	let a = e.registry.fieldTag;
	if (!a) return yt(t, n, r);
	let o = mt(a);
	return V`<${o} .field=${t} .errors=${n}>${r}</${o}>`;
}
function vt(e, t, n) {
	return j`
        <div class="starter-component-subfield" data-formie-field-type=${e.type}>
            ${e.label ? j`<label class="starter-component-subfield-label">${e.label}</label>` : N}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${n}</div>
            ${t.length > 0 ? j`<ul class="grid gap-1 text-sm text-red-600">
                      ${t.map((e) => j`<li>${e}</li>`)}
                  </ul>` : N}
        </div>
    `;
}
function yt(e, t, n) {
	return j`
        <div class="starter-component-card" data-formie-field-type=${e.type}>
            ${e.label ? j`<label class="starter-component-label">${e.label}</label>` : N}
            ${e.instructions ? j`<div class="starter-component-help">${dt(e.instructions)}</div>` : N}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${n}</div>
            ${t.length > 0 ? j`<ul class="grid gap-1 text-sm text-red-600">
                      ${t.map((e) => j`<li>${e}</li>`)}
                  </ul>` : N}
        </div>
    `;
}
function bt(e, t, n, r) {
	let i = e.input;
	if (e.type === "multi-line-text") return j`
            <textarea
                class="starter-component-control"
                .value=${typeof t == "string" ? t : ""}
                ?disabled=${n}
                placeholder=${typeof i.placeholder == "string" ? i.placeholder : ""}
                @input=${(e) => {
		r(e.target.value);
	}}
            ></textarea>
        `;
	if (e.type === "dropdown") {
		let e = Array.isArray(i.options) ? i.options : [], a = i.multiple === !0;
		return j`
            <select
                class="starter-component-control"
                ?disabled=${n}
                multiple=${a}
                .value=${a ? void 0 : typeof t == "string" ? t : ""}
                @change=${(e) => {
			let t = e.target;
			r(a ? Array.from(t.selectedOptions).map((e) => e.value) : t.value);
		}}
            >
                ${e.map((e) => j`
                        <option
                            value=${String(e.value ?? "")}
                            ?disabled=${e.disabled === !0}
                        >
                            ${String(e.label ?? e.value ?? "")}
                        </option>
                    `)}
            </select>
        `;
	}
	return j`
        <input
            class="starter-component-control"
            type=${typeof i.inputType == "string" ? i.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text"}
            .value=${typeof t == "string" ? t : ""}
            ?disabled=${n}
            placeholder=${typeof i.placeholder == "string" ? i.placeholder : ""}
            @input=${(e) => {
		r(e.target.value);
	}}
        />
    `;
}
function xt(e, t) {
	let { field: n, value: r, errorKey: i, disabled: a, setValue: u } = t, d = n.input, f = pt(n);
	if (o(n)) return Ct(e, t);
	if (l(n)) return wt(e, t);
	if (s(n)) return St(n, r, a, u);
	if (f === "signature") return j`<formie-internal-signature
            .field=${n}
            .modules=${e.state.definition.modules}
            .value=${typeof r == "string" ? r : ""}
            ?disabled=${a}
            @formie-control-value-change=${(e) => {
		u(e.detail);
	}}
        ></formie-internal-signature>`;
	if (f === "multi-line-text" || f === "dropdown") return bt(n, r, a, u);
	if (f === "radio") return j`
            <div class="flex flex-col gap-2">
                ${(Array.isArray(d.options) ? d.options : []).map((e) => {
		let t = String(e.value ?? ""), i = a || e.disabled === !0;
		return j`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="radio"
                                name=${`${n.id}-radio`}
                                .checked=${r === t}
                                ?disabled=${i}
                                @change=${() => {
			u(t);
		}}
                            />
                            <span>${String(e.label ?? t)}</span>
                        </label>
                    `;
	})}
            </div>
        `;
	if (f === "checkboxes") {
		let e = Array.isArray(d.options) ? d.options : [], t = Array.isArray(r) ? r.map((e) => String(e)) : [];
		return j`
            <div class="flex flex-col gap-2">
                ${e.map((e) => {
			let n = String(e.value ?? ""), r = t.includes(n);
			return j`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="checkbox"
                                .checked=${r}
                                ?disabled=${a || e.disabled === !0}
                                @change=${() => {
				u(r ? t.filter((e) => e !== n) : [...t, n]);
			}}
                            />
                            <span>${String(e.label ?? n)}</span>
                        </label>
                    `;
		})}
            </div>
        `;
	}
	if (f === "agree") {
		let e = typeof d.descriptionHtml == "string" ? d.descriptionHtml : null;
		return j`
            <label class="flex items-start gap-2 text-sm text-slate-800">
                <input
                    type="checkbox"
                    .checked=${r === !0}
                    ?disabled=${a}
                    @change=${(e) => {
			u(e.target.checked);
		}}
                />
                <span>${e ? dt(e) : n.label ?? ""}</span>
            </label>
        `;
	}
	return c(f) ? bt(n, r, a, u) : j`<div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            Unknown field type:
            ${String(n.meta?.fieldType ?? n.type)}
        </div>`;
}
function St(e, t, n, r) {
	let i = e.input, a = Array.isArray(t) ? t : [], o = i.multiple === !0, s = a.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return j`
        <div class="grid gap-2">
            <input
                type="file"
                class="starter-component-control"
                ?disabled=${n}
                multiple=${o}
                @change=${(e) => {
		let t = e.target;
		r(Array.from(t.files || []));
	}}
            />
            ${s.length > 0 ? j`<ul class="grid gap-1 text-sm text-slate-600">
                      ${s.map((e) => j`<li>${e}</li>`)}
                  </ul>` : N}
        </div>
    `;
}
function Ct(e, n) {
	let { field: r, value: i, errorKey: a, disabled: o, setValue: s } = n, c = t(r), l = i && typeof i == "object" ? i : {};
	return c.length === 0 ? j`<div class="text-sm text-amber-800">Composite field has no parts.</div>` : j`
        <div class="starter-component-name-grid">
            ${c.filter((e) => e.meta?.hidden !== !0).map((t) => {
		let n = `${a}.${t.handle}`;
		return Tt(e, {
			field: t,
			value: l[t.handle],
			errors: e.state.errors.fields[n] || [],
			errorKey: n,
			disabled: o || t.meta?.disabled === !0,
			setValue(e) {
				s({
					...l,
					[t.handle]: e
				});
			}
		}, "compositePart");
	})}
        </div>
    `;
}
function wt(e, t) {
	let { field: n, value: r, errorKey: a, disabled: o, setValue: s } = t, c = f(n), l = Array.isArray(r) ? r : [], u = n.input, d = Number(u.minRows ?? 0) || 0, p = Number(u.maxRows ?? 0) || 0, ee = !o && (p <= 0 || l.length < p);
	return c.length === 0 ? j`<div class="text-sm text-amber-800">Repeater has no row layout.</div>` : j`
        <div class="grid gap-4" data-formie-repeater-container>
            ${l.map((t, n) => j`
                    <div class="rounded-xl border border-slate-200 p-4" data-formie-repeater-item>
                        ${c.map((r, i) => Et(e, r, t, `${a}.${n}`, o, (e, t) => {
		s(l.map((r, i) => i === n ? {
			...r,
			[e.handle]: t
		} : r));
	}))}
                        <button
                            type="button"
                            class="mt-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700"
                            ?disabled=${o || d > 0 && l.length <= d}
                            @click=${() => {
		s(l.filter((e, t) => t !== n));
	}}
                        >
                            Remove
                        </button>
                    </div>
                `)}
            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm"
                ?disabled=${!ee}
                @click=${() => {
		s([...l, i(n)]);
	}}
            >
                ${String(u.addLabel ?? "Add another row")}
            </button>
        </div>
    `;
}
function Tt(e, t, n = "default") {
	let { field: r, value: i, errors: a, errorKey: o, disabled: s, setValue: c } = t, l = e.state.fieldStates[r.id]?.hidden === !0;
	if (l) return j``;
	let u = pt(r), d = e.registry.fieldControls[r.type] || e.registry.fieldControls[u] || null, f = (t) => {
		c(t), e.host.requestUpdate();
	};
	return _t(e, r, a, d ? gt(e, d, r, i, o, s, l, f) : xt(e, {
		...t,
		setValue: f
	}), n);
}
function Et(e, t, n, r, i, a) {
	return j`
        <div class="starter-core-row grid gap-4">
            ${t.fields.map((t) => {
		let o = `${r}.${t.handle}`;
		return Tt(e, {
			field: t,
			value: n[t.handle],
			errors: e.state.errors.fields[o] || [],
			errorKey: o,
			disabled: i === !0 || e.state.fieldStates[t.id]?.disabled === !0,
			setValue(e) {
				a(t, e);
			}
		});
	})}
        </div>
    `;
}
function Dt(e, t) {
	return j`
        <div class="starter-core-row grid gap-4">
            ${t.fields.map((t) => Tt(e, {
		field: t,
		value: e.state.values[t.id],
		errors: e.state.errors.fields[t.id] || [],
		errorKey: t.id,
		disabled: e.state.fieldStates[t.id]?.disabled === !0,
		setValue(n) {
			e.instance.setValue(t.id, n);
		}
	}))}
        </div>
    `;
}
function Ot(e) {
	let t = e.state.definition.pages.find((t) => t.id === e.state.currentPageId);
	if (!t) return j``;
	let n = e.registry.regions.pageActions;
	if (n) {
		let r = mt(n);
		return V`<${r}
            .page=${t}
            .state=${e.state}
            .instance=${e.instance}
        ></${r}>`;
	}
	return j`
        <div class="formie-page-actions">
            ${t.actions.secondary.map((t) => j`
            <button
                type="button"
                @click=${() => {
		e.instance.submit(t.type);
	}}
            >
                ${t.label}
            </button>
        `)}
            <button type="submit">${t.actions.primary.label}</button>
        </div>
    `;
}
function kt(e) {
	let t = e.state.definition.pages.find((t) => t.id === e.state.currentPageId && e.state.pageStates[t.id]?.hidden !== !0) || e.state.definition.pages.find((t) => e.state.pageStates[t.id]?.hidden !== !0) || e.state.definition.pages[0];
	if (!t) return j``;
	let n = e.state.errors.form, r = e.state.lastSubmitResult?.messages.error, i = !!r && !n.includes(r), a = j`
        ${n.length > 0 ? j`<div class="starter-core-msg starter-core-msg-error mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                  <ul class="list-inside list-disc">
                      ${n.map((e) => j`<li>${e}</li>`)}
                  </ul>
              </div>` : N}
        ${e.state.lastSubmitResult?.messages.notice ? j`<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                  ${e.state.lastSubmitResult.messages.notice}
              </div>` : N}
        ${i ? j`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${r}</div>` : N}
        ${At(e, t)}
    `, o = e.state.session.tokens.render ?? "";
	return j`
        <form
            class=${e.formClass || "starter-component-form starter-core-preview text-slate-900"}
            data-formie-definition=${e.state.definition.handle}
            data-formie-render-id=${o}
            @submit=${(t) => {
		t.preventDefault(), e.instance.submit();
	}}
        >
            ${a}
        </form>
    `;
}
function At(e, t) {
	let n = e.registry.regions.page, r = j`
        <div class="starter-core-fields grid gap-4">
            ${t.rows.map((t) => Dt(e, t))}
        </div>
        ${Ot(e)}
    `;
	if (n) {
		let i = mt(n);
		return V`<${i} .page=${t} .state=${e.state}>${r}</${i}>`;
	}
	return j`
        <section data-page-id=${t.id} class="starter-core-page space-y-4">
            ${r}
        </section>
    `;
}
function jt(e = "Loading form…") {
	return j`<div class="mt-3 text-sm text-slate-500">${e}</div>`;
}
function Mt(e) {
	return j`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${e}</div>`;
}
//#endregion
//#region \0@oxc-project+runtime@0.133.0/helpers/esm/decorate.js
function Y(e, t, n, r) {
	var i = arguments.length, a = i < 3 ? t : r === null ? r = Object.getOwnPropertyDescriptor(t, n) : r, o;
	if (typeof Reflect == "object" && typeof Reflect.decorate == "function") a = Reflect.decorate(e, t, n, r);
	else for (var s = e.length - 1; s >= 0; s--) (o = e[s]) && (a = (i < 3 ? o(a) : i > 3 ? o(t, n, a) : o(t, n)) || a);
	return i > 3 && a && Object.defineProperty(t, n, a), a;
}
//#endregion
//#region src/formie-core-form.ts
function Nt(e) {
	return W(e), We(e);
}
var X = class extends B {
	constructor(...e) {
		super(...e), this.endpoint = "", this.formHandle = "", this.transport = "rest", this.fetchCredentials = "same-origin", this.formClass = "", this.loadingMessage = "Loading form…", this.loadError = null, this.booting = !1, this.snapshot = null, this.instance = null, this.unsubscribers = [], this.loadGeneration = 0;
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
	willUpdate(e) {
		super.willUpdate(e), this.hasUpdated && [
			"endpoint",
			"formHandle",
			"siteId",
			"transport",
			"fetchCredentials"
		].some((t) => e.has(t)) && this.bootstrap(!0);
	}
	getFormieInstance() {
		return this.instance;
	}
	async reload() {
		await this.bootstrap(!0);
	}
	get resolvedRegistry() {
		return this.registry ?? Xe();
	}
	teardown() {
		for (let e of this.unsubscribers) e();
		this.unsubscribers = [], this.instance?.destroy(), this.instance = null, this.snapshot = null;
	}
	async bootstrap(t) {
		let i = ++this.loadGeneration, o = this.formHandle.trim(), s = this.endpoint.trim();
		if (!o) {
			this.teardown(), this.booting = !1, this.loadError = "Set `form-handle` on <formie-core-form>.";
			return;
		}
		if (t) this.teardown();
		else if (this.instance) return;
		this.booting = !0, this.loadError = null;
		let c = this.fetchCredentials, l = this.siteId;
		try {
			let t = {
				endpoint: s,
				formHandle: o,
				...l === void 0 ? {} : { siteId: l },
				credentials: c
			}, f = this.transport === "graphql" ? await d(t) : await u(t);
			if (i !== this.loadGeneration) return;
			let p = n({
				envelope: f,
				transport: this.transport === "graphql" ? r(t) : a(t)
			});
			this.instance = p;
			for (let t of e) {
				let e = p.on(t, (e) => {
					this.dispatchEvent(new CustomEvent(t, {
						detail: e,
						bubbles: !0,
						composed: !0
					}));
				});
				this.unsubscribers.push(e);
			}
			this.unsubscribers.push(p.subscribe((e) => {
				this.snapshot = e, this.requestUpdate();
			})), this.booting = !1;
		} catch (e) {
			if (i !== this.loadGeneration) return;
			this.teardown(), this.booting = !1, this.loadError = e instanceof Error ? e.message : "Unable to load the form.";
		}
	}
	render() {
		let e = this.resolvedRegistry;
		if (this.loadError) {
			let t = e.regions.errorSummary;
			if (t) {
				let e = Nt(t);
				return V`<${e} .errors=${[this.loadError]} .kind=${"load"}></${e}>`;
			}
			return Mt(this.loadError);
		}
		if (this.booting || !this.snapshot || !this.instance) {
			let t = e.regions.loading;
			if (t) {
				let e = Nt(t);
				return V`<${e} .message=${this.loadingMessage}></${e}>`;
			}
			return jt(this.loadingMessage);
		}
		return kt({
			registry: e,
			state: this.snapshot,
			instance: this.instance,
			host: this,
			formClass: this.formClass || ""
		});
	}
};
Y([H({ type: String })], X.prototype, "endpoint", void 0), Y([H({
	type: String,
	attribute: "form-handle"
})], X.prototype, "formHandle", void 0), Y([H({
	type: Number,
	attribute: "site-id",
	converter: { fromAttribute(e) {
		if (e == null || e === "") return;
		let t = Number(e);
		return Number.isFinite(t) ? t : void 0;
	} }
})], X.prototype, "siteId", void 0), Y([H({
	attribute: "transport",
	converter: { fromAttribute(e) {
		return (e ?? "rest").toLowerCase() === "graphql" ? "graphql" : "rest";
	} }
})], X.prototype, "transport", void 0), Y([H({
	attribute: "fetch-credentials",
	converter: { fromAttribute(e) {
		return e === "omit" || e === "same-origin" || e === "include" ? e : "same-origin";
	} }
})], X.prototype, "fetchCredentials", void 0), Y([H({
	type: String,
	attribute: "form-class"
})], X.prototype, "formClass", void 0), Y([H({
	type: String,
	attribute: "loading-message"
})], X.prototype, "loadingMessage", void 0), Y([H({ attribute: !1 })], X.prototype, "registry", void 0), Y([U()], X.prototype, "loadError", void 0), Y([U()], X.prototype, "booting", void 0), Y([U()], X.prototype, "snapshot", void 0);
//#endregion
//#region src/form-element.ts
function Pt(e) {
	if (e == null || e === "") return !1;
	let t = e.toLowerCase();
	return t === "true" || t === "1";
}
function Z(e, t, n) {
	typeof n == "string" && n.length > 0 ? e.setAttribute(t, n) : e.removeAttribute(t);
}
function Q(e, t, n) {
	n === !0 ? e.setAttribute(t, "true") : e.removeAttribute(t);
}
function Ft(e, t) {
	if (t) return t.startsWith("http") ? t : `${e}${t}`;
}
var It = class extends HTMLElement {
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
	constructor() {
		super(), this.mountedInstance = null, this.optionState = {}, this.mountScheduled = null, this.eventUnsubs = [];
	}
	ensureInitialized() {
		this.mountRoot ||= (this.client = te(), document.createElement("div"));
	}
	connectedCallback() {
		this.ensureInitialized(), this.style.display = "block", this.contains(this.mountRoot) || this.append(this.mountRoot), this.scheduleMount();
	}
	disconnectedCallback() {
		this.unmount();
	}
	attributeChangedCallback(e, t, n) {
		t === n || !this.isConnected || e === "refresh-tokens" && this.optionState.refreshTokens !== void 0 || e === "static-cache" && this.optionState.staticCache !== void 0 || this.scheduleMount();
	}
	get baseUrl() {
		let e = this.getAttribute("base-url");
		return this.optionState.baseUrl ?? e ?? void 0;
	}
	set baseUrl(e) {
		this.optionState.baseUrl = e, Z(this, "base-url", e), this.scheduleMount();
	}
	get transport() {
		let e = this.getAttribute("transport");
		return this.optionState.transport ?? e ?? void 0;
	}
	set transport(e) {
		this.optionState.transport = e, Z(this, "transport", e), this.scheduleMount();
	}
	get theme() {
		let e = this.getAttribute("theme");
		return this.optionState.theme ?? e ?? void 0;
	}
	set theme(e) {
		this.optionState.theme = e, Z(this, "theme", e), this.scheduleMount();
	}
	get themeConfig() {
		return this.optionState.themeConfig;
	}
	set themeConfig(e) {
		this.optionState.themeConfig = e, this.scheduleMount();
	}
	get payload() {
		return this.optionState.payload;
	}
	set payload(e) {
		this.optionState.payload = e, this.scheduleMount();
	}
	get formHandle() {
		let e = this.getAttribute("form-handle");
		return this.optionState.formHandle ?? e ?? void 0;
	}
	set formHandle(e) {
		this.optionState.formHandle = e, Z(this, "form-handle", e), this.scheduleMount();
	}
	get endpoint() {
		let e = this.getAttribute("endpoint");
		return this.optionState.endpoint ?? e ?? void 0;
	}
	set endpoint(e) {
		this.optionState.endpoint = e, Z(this, "endpoint", e), this.scheduleMount();
	}
	get staticCache() {
		return this.optionState.staticCache ?? (this.hasAttribute("static-cache") ? Pt(this.getAttribute("static-cache")) : void 0);
	}
	set staticCache(e) {
		this.optionState.staticCache = e, Q(this, "static-cache", e), this.scheduleMount();
	}
	get refreshTokens() {
		return this.optionState.refreshTokens ?? (this.hasAttribute("refresh-tokens") ? Pt(this.getAttribute("refresh-tokens")) : void 0);
	}
	set refreshTokens(e) {
		this.optionState.refreshTokens = e, Q(this, "refresh-tokens", e), this.scheduleMount();
	}
	get locale() {
		let e = this.getAttribute("locale");
		return this.optionState.locale ?? e ?? void 0;
	}
	set locale(e) {
		this.optionState.locale = e, Z(this, "locale", e), this.scheduleMount();
	}
	get siteId() {
		return this.optionState.siteId ?? (this.getAttribute("site-id") ? Number(this.getAttribute("site-id")) : void 0);
	}
	set siteId(e) {
		this.optionState.siteId = e, Z(this, "site-id", typeof e == "number" ? String(e) : void 0), this.scheduleMount();
	}
	get autoVisible() {
		return this.optionState.autoVisible ?? (this.hasAttribute("auto-visible") ? Pt(this.getAttribute("auto-visible")) : void 0);
	}
	set autoVisible(e) {
		this.optionState.autoVisible = e, Q(this, "auto-visible", e), this.scheduleMount();
	}
	get mode() {
		let e = this.getAttribute("mode");
		return this.optionState.mode ?? e ?? "server-rendered";
	}
	set mode(e) {
		this.optionState.mode = e, Z(this, "mode", e), this.scheduleMount();
	}
	getInstance() {
		return this.ensureInitialized(), this.mountedInstance;
	}
	async submit(e = "submit") {
		return this.ensureInitialized(), this.mountedInstance ? this.mountedInstance.submit(e) : null;
	}
	buildOptions() {
		let e = this.baseUrl || "", t = this.transport, n = t === "graphql" ? "/api" : "/actions/formie/server/forms/render", r = Ft(e, this.endpoint || n), i = this.staticCache, a = this.refreshTokens;
		return {
			mode: this.mode,
			transport: t,
			theme: this.theme,
			themeConfig: this.themeConfig,
			formHandle: this.formHandle,
			endpoint: r,
			payload: this.payload,
			staticCache: i,
			refreshTokens: a,
			locale: this.locale,
			siteId: this.siteId,
			autoVisible: this.autoVisible ?? !1
		};
	}
	bindInstanceEvents(e) {
		this.eventUnsubs.forEach((e) => e()), this.eventUnsubs = p.map((t) => e.on(t, (e) => {
			this.dispatchEvent(new CustomEvent(t, {
				detail: e,
				bubbles: !0,
				composed: !0
			}));
		}));
	}
	async scheduleMount() {
		return this.ensureInitialized(), this.mountScheduled ||= Promise.resolve().then(async () => {
			this.mountScheduled = null, this.isConnected && await this.mount();
		}), this.mountScheduled;
	}
	async mount() {
		await this.unmount();
		let e = await this.client.mount(this.mountRoot, this.buildOptions());
		this.mountedInstance = e, this.bindInstanceEvents(e), this.dispatchEvent(new CustomEvent("formie-mounted", {
			detail: {
				id: e.id,
				instance: e
			},
			bubbles: !0,
			composed: !0
		}));
	}
	async unmount() {
		if (this.eventUnsubs.forEach((e) => e()), this.eventUnsubs = [], !this.mountRoot || !this.mountedInstance) return;
		let e = this.mountedInstance;
		await this.client.unmount(this.mountRoot), this.mountedInstance = null, this.dispatchEvent(new CustomEvent("formie-unmounted", {
			detail: { id: e.id },
			bubbles: !0,
			composed: !0
		}));
	}
}, $ = class extends B {
	constructor(...e) {
		super(...e), this.modules = [], this.value = "", this.disabled = !1, this.loadError = null, this.pad = null, this.strokeListener = () => {
			this.emitValue();
		}, this.onWinResize = () => {
			let e = this.shadowRoot?.querySelector("canvas");
			e instanceof HTMLCanvasElement && this.resizeCanvas(e);
		};
	}
	static {
		this.styles = ae`
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
	}
	async firstUpdated() {
		let e = this.shadowRoot?.querySelector("canvas");
		if (!(!e || !(e instanceof HTMLCanvasElement))) try {
			let { default: t } = await import("./signature_pad-D-mX46zR.js"), n = this.resolveDrawModuleConfig(), r = typeof n?.options == "object" && n.options && typeof n.options.backgroundColor == "string" ? String(n.options.backgroundColor) : "#ffffff", i = typeof n?.options == "object" && n.options && typeof n.options.penColor == "string" ? String(n.options.penColor) : "#000000", a = typeof n?.options == "object" && n.options && Number(n.options.penWeight ?? 2) || 2, o = new t(e, {
				backgroundColor: r,
				penColor: i,
				minWidth: a,
				maxWidth: a
			});
			this.pad = o, o.addEventListener?.("endStroke", this.strokeListener), this.resizeCanvas(e), window.addEventListener("resize", this.onWinResize), this.applySerializedValue();
		} catch (e) {
			this.loadError = e instanceof Error ? e.message : "Signature pad failed to load.";
		}
	}
	disconnectedCallback() {
		super.disconnectedCallback(), window.removeEventListener("resize", this.onWinResize), this.pad?.removeEventListener && this.pad.removeEventListener("endStroke", this.strokeListener), this.pad = null;
	}
	resolveDrawModuleConfig() {
		let e = new Set(this.field.moduleRefs || []), t = this.modules.find((t) => e.has(t.id) && t.capability === "draw-signature");
		return t && typeof t.config == "object" && t.config ? t.config : null;
	}
	resizeCanvas(e) {
		let t = Math.max(window.devicePixelRatio || 1, 1), n = Math.max(1, Math.floor(e.clientWidth || 480)), r = e.getContext("2d");
		e.width = n * t, e.height = 192 * t, r?.scale(t, t), e.style.width = `${n}px`, e.style.height = "192px", this.pad?.fromDataURL?.(this.value || "data:,");
	}
	updated(e) {
		if (e.has("value") && this.pad && this.applySerializedValue(), e.has("disabled") && this.pad) {
			let e = this.shadowRoot?.querySelector("canvas");
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
			} catch {}
		}
	}
	emitValue() {
		if (!this.pad || this.pad.isEmpty()) {
			this.dispatchEvent(new CustomEvent(J, {
				detail: "",
				bubbles: !0,
				composed: !0
			}));
			return;
		}
		this.dispatchEvent(new CustomEvent(J, {
			detail: this.pad.toDataURL(),
			bubbles: !0,
			composed: !0
		}));
	}
	render() {
		return this.loadError ? j`<div class="err">${this.loadError}</div>` : j`
            <div class="wrap">
                <canvas></canvas>
                <button type="button" ?disabled=${this.disabled} @click=${() => {
			this.pad?.clear(), this.dispatchEvent(new CustomEvent(J, {
				detail: "",
				bubbles: !0,
				composed: !0
			}));
		}}>Clear</button>
            </div>
        `;
	}
};
Y([H({ attribute: !1 })], $.prototype, "field", void 0), Y([H({ attribute: !1 })], $.prototype, "modules", void 0), Y([H({ type: String })], $.prototype, "value", void 0), Y([H({ type: Boolean })], $.prototype, "disabled", void 0), Y([U()], $.prototype, "loadError", void 0);
//#endregion
//#region src/index.ts
var Lt = !1;
function Rt() {
	Lt || (Lt = !0, customElements.get("formie-form") || customElements.define("formie-form", It), customElements.get("formie-internal-signature") || customElements.define("formie-internal-signature", $), customElements.get("formie-core-form") || customElements.define("formie-core-form", X));
}
//#endregion
export { J as FORMIE_CONTROL_VALUE_EVENT, X as FormieCoreForm, It as FormieFormElement, $ as FormieInternalSignature, G as FormieRegistry, W as assertValidCustomElementName, ee as createFormieClient, Ze as createFormieRegistry, Xe as getFormieRegistry, ft as isFieldDefinition, Rt as registerFormieWebComponents, Mt as renderErrorView, kt as renderFormView, jt as renderLoadingView, pt as resolveFieldRendererType };
