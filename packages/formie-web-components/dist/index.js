import { FORMIE_HTML_EVENT_NAMES as e, createFormieClient as t, createFormieClient as n, mountClientRenderedModules as r } from "@verbb/formie-browser";
import { CLIENT_FORM_EVENT_NAMES as i, clientActionAllowed as a, compositePartDefinitions as o, createClientFormInstance as s, createGraphqlClientTransport as c, createRepeaterRowValue as l, createRestClientTransport as u, getClientErrorAriaLive as d, getClientFieldErrorId as f, isCompositeField as p, isFileField as m, isKnownClientFieldType as h, isRepeatableField as ee, loadClientFormBootstrap as te, loadGraphqlClientFormBootstrap as ne, repeaterRowDefinitions as re } from "@verbb/formie-core";
//#region ../../node_modules/@lit/reactive-element/css-tag.js
var g = globalThis, ie = g.ShadowRoot && (g.ShadyCSS === void 0 || g.ShadyCSS.nativeShadow) && "adoptedStyleSheets" in Document.prototype && "replace" in CSSStyleSheet.prototype, ae = Symbol(), oe = /* @__PURE__ */ new WeakMap(), se = class {
	constructor(e, t, n) {
		if (this._$cssResult$ = !0, n !== ae) throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");
		this.cssText = e, this.t = t;
	}
	get styleSheet() {
		let e = this.o, t = this.t;
		if (ie && e === void 0) {
			let n = t !== void 0 && t.length === 1;
			n && (e = oe.get(t)), e === void 0 && ((this.o = e = new CSSStyleSheet()).replaceSync(this.cssText), n && oe.set(t, e));
		}
		return e;
	}
	toString() {
		return this.cssText;
	}
}, ce = (e) => new se(typeof e == "string" ? e : e + "", void 0, ae), le = (e, ...t) => new se(e.length === 1 ? e[0] : t.reduce((t, n, r) => t + ((e) => {
	if (!0 === e._$cssResult$) return e.cssText;
	if (typeof e == "number") return e;
	throw Error("Value passed to 'css' function must be a 'css' function result: " + e + ". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.");
})(n) + e[r + 1], e[0]), e, ae), ue = (e, t) => {
	if (ie) e.adoptedStyleSheets = t.map((e) => e instanceof CSSStyleSheet ? e : e.styleSheet);
	else for (let n of t) {
		let t = document.createElement("style"), r = g.litNonce;
		r !== void 0 && t.setAttribute("nonce", r), t.textContent = n.cssText, e.appendChild(t);
	}
}, de = ie ? (e) => e : (e) => e instanceof CSSStyleSheet ? ((e) => {
	let t = "";
	for (let n of e.cssRules) t += n.cssText;
	return ce(t);
})(e) : e, { is: fe, defineProperty: pe, getOwnPropertyDescriptor: me, getOwnPropertyNames: he, getOwnPropertySymbols: ge, getPrototypeOf: _e } = Object, _ = globalThis, ve = _.trustedTypes, ye = ve ? ve.emptyScript : "", be = _.reactiveElementPolyfillSupport, v = (e, t) => e, y = {
	toAttribute(e, t) {
		switch (t) {
			case Boolean:
				e = e ? ye : null;
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
}, b = (e, t) => !fe(e, t), xe = {
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
	static createProperty(e, t = xe) {
		if (t.state && (t.attribute = !1), this._$Ei(), this.prototype.hasOwnProperty(e) && ((t = Object.create(t)).wrapped = !0), this.elementProperties.set(e, t), !t.noAccessor) {
			let n = Symbol(), r = this.getPropertyDescriptor(e, n, t);
			r !== void 0 && pe(this.prototype, e, r);
		}
	}
	static getPropertyDescriptor(e, t, n) {
		let { get: r, set: i } = me(this.prototype, e) ?? {
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
		return this.elementProperties.get(e) ?? xe;
	}
	static _$Ei() {
		if (this.hasOwnProperty(v("elementProperties"))) return;
		let e = _e(this);
		e.finalize(), e.l !== void 0 && (this.l = [...e.l]), this.elementProperties = new Map(e.elementProperties);
	}
	static finalize() {
		if (this.hasOwnProperty(v("finalized"))) return;
		if (this.finalized = !0, this._$Ei(), this.hasOwnProperty(v("properties"))) {
			let e = this.properties, t = [...he(e), ...ge(e)];
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
			let n = new Set(e.flat(1 / 0).reverse());
			for (let e of n) t.unshift(de(e));
		} else e !== void 0 && t.push(de(e));
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
		return ue(e, this.constructor.elementStyles), e;
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
x.elementStyles = [], x.shadowRootOptions = { mode: "open" }, x[v("elementProperties")] = /* @__PURE__ */ new Map(), x[v("finalized")] = /* @__PURE__ */ new Map(), be?.({ ReactiveElement: x }), (_.reactiveElementVersions ??= []).push("2.1.2");
//#endregion
//#region ../../node_modules/lit-html/lit-html.js
var Se = globalThis, Ce = (e) => e, S = Se.trustedTypes, we = S ? S.createPolicy("lit-html", { createHTML: (e) => e }) : void 0, C = "$lit$", w = `lit$${Math.random().toFixed(9).slice(2)}$`, T = "?" + w, Te = `<${T}>`, E = document, D = () => E.createComment(""), O = (e) => e === null || typeof e != "object" && typeof e != "function", Ee = Array.isArray, De = (e) => Ee(e) || typeof e?.[Symbol.iterator] == "function", k = "[ 	\n\f\r]", A = /<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g, Oe = /-->/g, ke = />/g, j = RegExp(`>|${k}(?:([^\\s"'>=/]+)(${k}*=${k}*(?:[^ \t\n\f\r"'\`<>=]|("|')|))|$)`, "g"), Ae = /'/g, je = /"/g, Me = /^(?:script|style|textarea|title)$/i, M = ((e) => (t, ...n) => ({
	_$litType$: e,
	strings: t,
	values: n
}))(1), N = Symbol.for("lit-noChange"), P = Symbol.for("lit-nothing"), Ne = /* @__PURE__ */ new WeakMap(), F = E.createTreeWalker(E, 129);
function Pe(e, t) {
	if (!Ee(e) || !e.hasOwnProperty("raw")) throw Error("invalid template strings array");
	return we === void 0 ? t : we.createHTML(t);
}
var Fe = (e, t) => {
	let n = e.length - 1, r = [], i, a = t === 2 ? "<svg>" : t === 3 ? "<math>" : "", o = A;
	for (let t = 0; t < n; t++) {
		let n = e[t], s, c, l = -1, u = 0;
		for (; u < n.length && (o.lastIndex = u, c = o.exec(n), c !== null);) u = o.lastIndex, o === A ? c[1] === "!--" ? o = Oe : c[1] === void 0 ? c[2] === void 0 ? c[3] !== void 0 && (o = j) : (Me.test(c[2]) && (i = RegExp("</" + c[2], "g")), o = j) : o = ke : o === j ? c[0] === ">" ? (o = i ?? A, l = -1) : c[1] === void 0 ? l = -2 : (l = o.lastIndex - c[2].length, s = c[1], o = c[3] === void 0 ? j : c[3] === "\"" ? je : Ae) : o === je || o === Ae ? o = j : o === Oe || o === ke ? o = A : (o = j, i = void 0);
		let d = o === j && e[t + 1].startsWith("/>") ? " " : "";
		a += o === A ? n + Te : l >= 0 ? (r.push(s), n.slice(0, l) + C + n.slice(l) + w + d) : n + w + (l === -2 ? t : d);
	}
	return [Pe(e, a + (e[n] || "<?>") + (t === 2 ? "</svg>" : t === 3 ? "</math>" : "")), r];
}, I = class e {
	constructor({ strings: t, _$litType$: n }, r) {
		let i;
		this.parts = [];
		let a = 0, o = 0, s = t.length - 1, c = this.parts, [l, u] = Fe(t, n);
		if (this.el = e.createElement(l, r), F.currentNode = this.el.content, n === 2 || n === 3) {
			let e = this.el.content.firstChild;
			e.replaceWith(...e.childNodes);
		}
		for (; (i = F.nextNode()) !== null && c.length < s;) {
			if (i.nodeType === 1) {
				if (i.hasAttributes()) for (let e of i.getAttributeNames()) if (e.endsWith(C)) {
					let t = u[o++], n = i.getAttribute(e).split(w), r = /([.?@])?(.*)/.exec(t);
					c.push({
						type: 1,
						index: a,
						name: r[2],
						strings: n,
						ctor: r[1] === "." ? Le : r[1] === "?" ? Re : r[1] === "@" ? ze : z
					}), i.removeAttribute(e);
				} else e.startsWith(w) && (c.push({
					type: 6,
					index: a
				}), i.removeAttribute(e));
				if (Me.test(i.tagName)) {
					let e = i.textContent.split(w), t = e.length - 1;
					if (t > 0) {
						i.textContent = S ? S.emptyScript : "";
						for (let n = 0; n < t; n++) i.append(e[n], D()), F.nextNode(), c.push({
							type: 2,
							index: ++a
						});
						i.append(e[t], D());
					}
				}
			} else if (i.nodeType === 8) {
				if (i.data === T) c.push({
					type: 2,
					index: a
				});
				else {
					let e = -1;
					for (; (e = i.data.indexOf(w, e + 1)) !== -1;) c.push({
						type: 7,
						index: a
					}), e += w.length - 1;
				}
			}
			a++;
		}
	}
	static createElement(e, t) {
		let n = E.createElement("template");
		return n.innerHTML = e, n;
	}
};
function L(e, t, n = e, r) {
	if (t === N) return t;
	let i = r === void 0 ? n._$Cl : n._$Co?.[r], a = O(t) ? void 0 : t._$litDirective$;
	return i?.constructor !== a && (i?._$AO?.(!1), a === void 0 ? i = void 0 : (i = new a(e), i._$AT(e, n, r)), r === void 0 ? n._$Cl = i : (n._$Co ??= [])[r] = i), i !== void 0 && (t = L(e, i._$AS(e, t.values), i, r)), t;
}
var Ie = class {
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
		let { el: { content: t }, parts: n } = this._$AD, r = (e?.creationScope ?? E).importNode(t, !0);
		F.currentNode = r;
		let i = F.nextNode(), a = 0, o = 0, s = n[0];
		for (; s !== void 0;) {
			if (a === s.index) {
				let t;
				s.type === 2 ? t = new R(i, i.nextSibling, this, e) : s.type === 1 ? t = new s.ctor(i, s.name, s.strings, this, e) : s.type === 6 && (t = new Be(i, this, e)), this._$AV.push(t), s = n[++o];
			}
			a !== s?.index && (i = F.nextNode(), a++);
		}
		return F.currentNode = E, r;
	}
	p(e) {
		let t = 0;
		for (let n of this._$AV) n !== void 0 && (n.strings === void 0 ? n._$AI(e[t]) : (n._$AI(e, n, t), t += n.strings.length - 2)), t++;
	}
}, R = class e {
	get _$AU() {
		return this._$AM?._$AU ?? this._$Cv;
	}
	constructor(e, t, n, r) {
		this.type = 2, this._$AH = P, this._$AN = void 0, this._$AA = e, this._$AB = t, this._$AM = n, this.options = r, this._$Cv = r?.isConnected ?? !0;
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
		e = L(this, e, t), O(e) ? e === P || e == null || e === "" ? (this._$AH !== P && this._$AR(), this._$AH = P) : e !== this._$AH && e !== N && this._(e) : e._$litType$ === void 0 ? e.nodeType === void 0 ? De(e) ? this.k(e) : this._(e) : this.T(e) : this.$(e);
	}
	O(e) {
		return this._$AA.parentNode.insertBefore(e, this._$AB);
	}
	T(e) {
		this._$AH !== e && (this._$AR(), this._$AH = this.O(e));
	}
	_(e) {
		this._$AH !== P && O(this._$AH) ? this._$AA.nextSibling.data = e : this.T(E.createTextNode(e)), this._$AH = e;
	}
	$(e) {
		let { values: t, _$litType$: n } = e, r = typeof n == "number" ? this._$AC(e) : (n.el === void 0 && (n.el = I.createElement(Pe(n.h, n.h[0]), this.options)), n);
		if (this._$AH?._$AD === r) this._$AH.p(t);
		else {
			let e = new Ie(r, this), n = e.u(this.options);
			e.p(t), this.T(n), this._$AH = e;
		}
	}
	_$AC(e) {
		let t = Ne.get(e.strings);
		return t === void 0 && Ne.set(e.strings, t = new I(e)), t;
	}
	k(t) {
		Ee(this._$AH) || (this._$AH = [], this._$AR());
		let n = this._$AH, r, i = 0;
		for (let a of t) i === n.length ? n.push(r = new e(this.O(D()), this.O(D()), this, this.options)) : r = n[i], r._$AI(a), i++;
		i < n.length && (this._$AR(r && r._$AB.nextSibling, i), n.length = i);
	}
	_$AR(e = this._$AA.nextSibling, t) {
		for (this._$AP?.(!1, !0, t); e !== this._$AB;) {
			let t = Ce(e).nextSibling;
			Ce(e).remove(), e = t;
		}
	}
	setConnected(e) {
		this._$AM === void 0 && (this._$Cv = e, this._$AP?.(e));
	}
}, z = class {
	get tagName() {
		return this.element.tagName;
	}
	get _$AU() {
		return this._$AM._$AU;
	}
	constructor(e, t, n, r, i) {
		this.type = 1, this._$AH = P, this._$AN = void 0, this.element = e, this.name = t, this._$AM = r, this.options = i, n.length > 2 || n[0] !== "" || n[1] !== "" ? (this._$AH = Array(n.length - 1).fill(/* @__PURE__ */ new String()), this.strings = n) : this._$AH = P;
	}
	_$AI(e, t = this, n, r) {
		let i = this.strings, a = !1;
		if (i === void 0) e = L(this, e, t, 0), a = !O(e) || e !== this._$AH && e !== N, a && (this._$AH = e);
		else {
			let r = e, o, s;
			for (e = i[0], o = 0; o < i.length - 1; o++) s = L(this, r[n + o], t, o), s === N && (s = this._$AH[o]), a ||= !O(s) || s !== this._$AH[o], s === P ? e = P : e !== P && (e += (s ?? "") + i[o + 1]), this._$AH[o] = s;
		}
		a && !r && this.j(e);
	}
	j(e) {
		e === P ? this.element.removeAttribute(this.name) : this.element.setAttribute(this.name, e ?? "");
	}
}, Le = class extends z {
	constructor() {
		super(...arguments), this.type = 3;
	}
	j(e) {
		this.element[this.name] = e === P ? void 0 : e;
	}
}, Re = class extends z {
	constructor() {
		super(...arguments), this.type = 4;
	}
	j(e) {
		this.element.toggleAttribute(this.name, !!e && e !== P);
	}
}, ze = class extends z {
	constructor(e, t, n, r, i) {
		super(e, t, n, r, i), this.type = 5;
	}
	_$AI(e, t = this) {
		if ((e = L(this, e, t, 0) ?? P) === N) return;
		let n = this._$AH, r = e === P && n !== P || e.capture !== n.capture || e.once !== n.once || e.passive !== n.passive, i = e !== P && (n === P || r);
		r && this.element.removeEventListener(this.name, this, n), i && this.element.addEventListener(this.name, this, e), this._$AH = e;
	}
	handleEvent(e) {
		typeof this._$AH == "function" ? this._$AH.call(this.options?.host ?? this.element, e) : this._$AH.handleEvent(e);
	}
}, Be = class {
	constructor(e, t, n) {
		this.element = e, this.type = 6, this._$AN = void 0, this._$AM = t, this.options = n;
	}
	get _$AU() {
		return this._$AM._$AU;
	}
	_$AI(e) {
		L(this, e);
	}
}, Ve = {
	M: C,
	P: w,
	A: T,
	C: 1,
	L: Fe,
	R: Ie,
	D: De,
	V: L,
	I: R,
	H: z,
	N: Re,
	U: ze,
	B: Le,
	F: Be
}, He = Se.litHtmlPolyfillSupport;
He?.(I, R), (Se.litHtmlVersions ??= []).push("3.3.3");
var Ue = (e, t, n) => {
	let r = n?.renderBefore ?? t, i = r._$litPart$;
	if (i === void 0) {
		let e = n?.renderBefore ?? null;
		r._$litPart$ = i = new R(t.insertBefore(D(), e), e, void 0, n ?? {});
	}
	return i._$AI(e), i;
}, We = globalThis, B = class extends x {
	constructor() {
		super(...arguments), this.renderOptions = { host: this }, this._$Do = void 0;
	}
	createRenderRoot() {
		let e = super.createRenderRoot();
		return this.renderOptions.renderBefore ??= e.firstChild, e;
	}
	update(e) {
		let t = this.render();
		this.hasUpdated || (this.renderOptions.isConnected = this.isConnected), super.update(e), this._$Do = Ue(t, this.renderRoot, this.renderOptions);
	}
	connectedCallback() {
		super.connectedCallback(), this._$Do?.setConnected(!0);
	}
	disconnectedCallback() {
		super.disconnectedCallback(), this._$Do?.setConnected(!1);
	}
	render() {
		return N;
	}
};
B._$litElement$ = !0, B.finalized = !0, We.litElementHydrateSupport?.({ LitElement: B });
var Ge = We.litElementPolyfillSupport;
Ge?.({ LitElement: B }), (We.litElementVersions ??= []).push("4.2.2");
//#endregion
//#region ../../node_modules/lit-html/static.js
var Ke = Symbol.for(""), qe = (e) => {
	if (e?.r === Ke) return e?._$litStatic$;
}, Je = (e) => ({
	_$litStatic$: e,
	r: Ke
}), Ye = /* @__PURE__ */ new Map(), V = ((e) => (t, ...n) => {
	let r = n.length, i, a, o = [], s = [], c, l = 0, u = !1;
	for (; l < r;) {
		for (c = t[l]; l < r && (a = n[l], (i = qe(a)) !== void 0);) c += i + t[++l], u = !0;
		l !== r && s.push(a), o.push(c), l++;
	}
	if (l === r && o.push(t[r]), u) {
		let e = o.join("$$lit$$");
		(t = Ye.get(e)) === void 0 && (o.raw = o, Ye.set(e, t = o)), n = s;
	}
	return e(t, ...n);
})(M), Xe = {
	attribute: !0,
	type: String,
	converter: y,
	reflect: !1,
	hasChanged: b
}, Ze = (e = Xe, t, n) => {
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
	return (t, n) => typeof n == "object" ? Ze(e, t, n) : ((e, t, n) => {
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
var Qe = /^[a-z][a-z0-9]*(-[a-z0-9]+)+$/;
function W(e) {
	if (!Qe.test(e)) throw Error(`[Formie WC] Invalid custom element tag "${e}". Use lowercase hyphenated names (e.g. my-text-field).`);
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
}, $e = new G();
function et() {
	return $e;
}
function tt() {
	return new G();
}
//#endregion
//#region ../../node_modules/lit-html/directive-helpers.js
var { I: nt } = Ve, rt = (e) => e.strings === void 0, it = {
	ATTRIBUTE: 1,
	CHILD: 2,
	PROPERTY: 3,
	BOOLEAN_ATTRIBUTE: 4,
	EVENT: 5,
	ELEMENT: 6
}, at = (e) => (...t) => ({
	_$litDirective$: e,
	values: t
}), ot = class {
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
}, st = (e) => {
	for (let t; t = e._$AM; e = t) {
		let n = t._$AN;
		if (n === void 0) t._$AN = n = /* @__PURE__ */ new Set();
		else if (n.has(e)) break;
		n.add(e), ut(t);
	}
};
function ct(e) {
	this._$AN === void 0 ? this._$AM = e : (q(this), this._$AM = e, st(this));
}
function lt(e, t = !1, n = 0) {
	let r = this._$AH, i = this._$AN;
	if (i !== void 0 && i.size !== 0) {
		if (t) {
			if (Array.isArray(r)) for (let e = n; e < r.length; e++) K(r[e], !1), q(r[e]);
			else r != null && (K(r, !1), q(r));
		} else K(this, e);
	}
}
var ut = (e) => {
	e.type == it.CHILD && (e._$AP ??= lt, e._$AQ ??= ct);
}, dt = class extends ot {
	constructor() {
		super(...arguments), this._$AN = void 0;
	}
	_$AT(e, t, n) {
		super._$AT(e, t, n), st(this), this.isConnected = e._$AU;
	}
	_$AO(e, t = !0) {
		e !== this.isConnected && (this.isConnected = e, e ? this.reconnected?.() : this.disconnected?.()), t && (K(this, e), q(this));
	}
	setValue(e) {
		if (rt(this._$Ct)) this._$Ct._$AI(e, this);
		else {
			let t = [...this._$Ct._$AH];
			t[this._$Ci] = e, this._$Ct._$AI(t, this, 0);
		}
	}
	disconnected() {}
	reconnected() {}
}, ft = /* @__PURE__ */ new WeakMap(), pt = at(class extends dt {
	render(e) {
		return P;
	}
	update(e, [t]) {
		let n = t !== this.G;
		return n && this.rt(void 0), (n || this.lt !== this.ct) && (this.G = t, this.ht = e.options?.host, this.rt(this.ct = e.element)), P;
	}
	rt(e) {
		if (this.G !== void 0) {
			if (this.isConnected || (e = void 0), typeof this.G == "function") {
				let t = this.ht ?? globalThis, n = ft.get(t);
				n === void 0 && (n = /* @__PURE__ */ new WeakMap(), ft.set(t, n)), n.get(this.G) !== void 0 && this.G.call(this.ht, void 0), n.set(this.G, e), e !== void 0 && this.G.call(this.ht, e);
			} else this.G.value = e;
		}
	}
	get lt() {
		return typeof this.G == "function" ? ft.get(this.ht ?? globalThis)?.get(this.G) : this.G?.value;
	}
	disconnected() {
		this.lt === this.ct && this.rt(void 0);
	}
	reconnected() {
		this.rt(this.ct);
	}
}), J = class extends ot {
	constructor(e) {
		if (super(e), this.it = P, e.type !== it.CHILD) throw Error(this.constructor.directiveName + "() can only be used in child bindings");
	}
	render(e) {
		if (e === P || e == null) return this._t = void 0, this.it = e;
		if (e === N) return e;
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
J.directiveName = "unsafeHTML", J.resultType = 1;
var mt = at(J);
//#endregion
//#region src/field-utils.ts
function ht(e) {
	return !!e && typeof e == "object" && "id" in e && "handle" in e && "type" in e;
}
function gt(e) {
	if (h(e.type)) return e.type;
	let t = e.input && typeof e.input == "object" ? e.input : {}, n = typeof t.fieldKind == "string" ? t.fieldKind : null;
	return n === "text" ? "single-line-text" : n === "textarea" ? "multi-line-text" : n === "boolean" ? "agree" : n === "file" ? "file" : e.type;
}
//#endregion
//#region src/types.ts
var Y = "formie-control-value-change";
//#endregion
//#region src/render-view.ts
function _t(e) {
	return W(e), Je(e);
}
function vt(e, t, n, r, i, a, o, s, c, l, u) {
	if (!e || !(e instanceof HTMLElement)) return;
	let d = t.toLowerCase(), f = e.firstElementChild;
	(!f || f.tagName.toLowerCase() !== d) && (e.replaceChildren(), f = document.createElement(t), f.addEventListener(Y, (e) => {
		u(e.detail);
	}), e.append(f)), f.field = n, f.value = r, f.errorKey = i, f.errors = a, f.errorId = o, f.errorAriaLive = s, f.disabled = c, f.hidden = l;
}
function yt(e, t, n, r, i, a, o, s, c, l, u) {
	return M`<div
        class="starter-core-registry-host min-w-0"
        ${pt((e) => {
		vt(e, t, n, r, i, a, o, s, c, l, u);
	})}
    ></div>`;
}
function bt(e, t, n, r, i, a, o = "default") {
	if (o === "compositePart") return xt(t, n, r, i, a);
	let s = e.registry.fieldTag;
	if (!s) return St(t, n, r, i, a);
	let c = _t(s);
	return V`<${c} .field=${t} .errors=${n} .errorId=${r} .errorAriaLive=${i}>${a}</${c}>`;
}
function xt(e, t, n, r, i) {
	return M`
        <div class="starter-component-subfield" data-formie-field-type=${e.type} data-formie-field-uid=${e.uid} data-formie-field-handle=${e.handle}>
            ${e.label ? M`<label class="starter-component-subfield-label">${e.label}</label>` : P}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${i}</div>
            <ul id=${n} data-formie-field-errors aria-live=${r === "off" ? P : r} aria-atomic=${r === "off" ? P : "true"} style=${t.length === 0 ? "position: absolute" : P} class="grid gap-1 text-sm text-red-600">
                ${t.map((e) => M`<li>${e}</li>`)}
            </ul>
        </div>
    `;
}
function St(e, t, n, r, i) {
	return M`
        <div class="starter-component-card" data-formie-field-type=${e.type} data-formie-field-uid=${e.uid} data-formie-field-handle=${e.handle}>
            ${e.label ? M`<label class="starter-component-label">${e.label}</label>` : P}
            ${e.instructions ? M`<div class="starter-component-help">${mt(e.instructions)}</div>` : P}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${i}</div>
            <ul id=${n} data-formie-field-errors aria-live=${r === "off" ? P : r} aria-atomic=${r === "off" ? P : "true"} style=${t.length === 0 ? "position: absolute" : P} class="grid gap-1 text-sm text-red-600">
                ${t.map((e) => M`<li>${e}</li>`)}
            </ul>
        </div>
    `;
}
function Ct(e, t, n, r, i = [], a = "") {
	let o = e.input;
	if (e.type === "multi-line-text") return M`
            <textarea
            aria-label=${e.label || e.handle}
            aria-invalid=${i.length > 0 ? "true" : P}
            aria-errormessage=${i.length > 0 ? a : P}
            aria-describedby=${i.length > 0 ? a : P}
                class="starter-component-control"
                .value=${typeof t == "string" ? t : ""}
                ?disabled=${n}
                placeholder=${typeof o.placeholder == "string" ? o.placeholder : ""}
                @input=${(e) => {
		r(e.target.value);
	}}
            ></textarea>
        `;
	if (e.type === "dropdown") {
		let s = Array.isArray(o.options) ? o.options : [], c = o.multiple === !0;
		return M`
            <select
            aria-label=${e.label || e.handle}
            aria-invalid=${i.length > 0 ? "true" : P}
            aria-errormessage=${i.length > 0 ? a : P}
            aria-describedby=${i.length > 0 ? a : P}
                class="starter-component-control"
                ?disabled=${n}
                multiple=${c}
                .value=${c ? void 0 : typeof t == "string" ? t : ""}
                @change=${(e) => {
			let t = e.target;
			r(c ? Array.from(t.selectedOptions).map((e) => e.value) : t.value);
		}}
            >
                ${s.map((e) => M`
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
	let s = typeof o.inputType == "string" ? o.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
	return M`
        <input
            aria-label=${e.label || e.handle}
            aria-invalid=${i.length > 0 ? "true" : P}
            aria-errormessage=${i.length > 0 ? a : P}
            aria-describedby=${i.length > 0 ? a : P}
            class="starter-component-control"
            type=${s}
            .value=${typeof t == "string" ? t : ""}
            ?disabled=${n}
            placeholder=${typeof o.placeholder == "string" ? o.placeholder : ""}
            @input=${(e) => {
		r(e.target.value);
	}}
        />
    `;
}
function wt(e, t) {
	let { field: n, value: r, errorKey: i, disabled: a, setValue: o } = t, s = n.input, c = gt(n);
	if (p(n)) return Et(e, t);
	if (ee(n)) return Dt(e, t);
	if (m(n)) return Tt(n, r, a, o, t.errors, t.errorId);
	if (c === "signature") return M`<formie-internal-signature
            aria-invalid=${t.errors.length > 0 ? "true" : P}
            aria-errormessage=${t.errors.length > 0 ? t.errorId : P}
            aria-describedby=${t.errors.length > 0 ? t.errorId : P}
            .field=${n}
            .modules=${e.state.definition.modules}
            .value=${typeof r == "string" ? r : ""}
            ?disabled=${a}
            @formie-control-value-change=${(e) => {
		o(e.detail);
	}}
        ></formie-internal-signature>`;
	if (c === "multi-line-text" || c === "dropdown") return Ct(n, r, a, o, t.errors, t.errorId);
	if (c === "radio") return M`
            <div class="flex flex-col gap-2">
                ${(Array.isArray(s.options) ? s.options : []).map((e) => {
		let i = String(e.value ?? ""), s = a || e.disabled === !0;
		return M`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="radio"
                                aria-invalid=${t.errors.length > 0 ? "true" : P}
                                aria-errormessage=${t.errors.length > 0 ? t.errorId : P}
                                aria-describedby=${t.errors.length > 0 ? t.errorId : P}
                                name=${`${n.id}-radio`}
                                .checked=${r === i}
                                ?disabled=${s}
                                @change=${() => {
			o(i);
		}}
                            />
                            <span>${String(e.label ?? i)}</span>
                        </label>
                    `;
	})}
            </div>
        `;
	if (c === "checkboxes") {
		let e = Array.isArray(s.options) ? s.options : [], n = Array.isArray(r) ? r.map((e) => String(e)) : [];
		return M`
            <div class="flex flex-col gap-2">
                ${e.map((e) => {
			let r = String(e.value ?? ""), i = n.includes(r), s = a || e.disabled === !0;
			return M`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="checkbox"
                                aria-invalid=${t.errors.length > 0 ? "true" : P}
                                aria-errormessage=${t.errors.length > 0 ? t.errorId : P}
                                aria-describedby=${t.errors.length > 0 ? t.errorId : P}
                                .checked=${i}
                                ?disabled=${s}
                                @change=${() => {
				let e = i ? n.filter((e) => e !== r) : [...n, r];
				o(e);
			}}
                            />
                            <span>${String(e.label ?? r)}</span>
                        </label>
                    `;
		})}
            </div>
        `;
	}
	if (c === "agree") {
		let e = typeof s.descriptionHtml == "string" ? s.descriptionHtml : null;
		return M`
            <label class="flex items-start gap-2 text-sm text-slate-800">
                <input
                    type="checkbox"
                    aria-invalid=${t.errors.length > 0 ? "true" : P}
                    aria-errormessage=${t.errors.length > 0 ? t.errorId : P}
                    aria-describedby=${t.errors.length > 0 ? t.errorId : P}
                    .checked=${r === !0}
                    ?disabled=${a}
                    @change=${(e) => {
			o(e.target.checked);
		}}
                />
                <span>${e ? mt(e) : n.label ?? ""}</span>
            </label>
        `;
	}
	return h(c) ? Ct(n, r, a, o, t.errors, t.errorId) : M`<div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            Unknown field type:
            ${String(n.meta?.fieldType ?? n.type)}
        </div>`;
}
function Tt(e, t, n, r, i = [], a = "") {
	let o = e.input, s = Array.isArray(t) ? t : [], c = o.multiple === !0, l = s.map((e, t) => e && typeof e == "object" && "name" in e && typeof e.name == "string" ? e.name : e && typeof e == "object" && "filename" in e && typeof e.filename == "string" ? e.filename : e && typeof e == "object" && "assetId" in e && typeof e.assetId == "number" ? `Asset #${e.assetId}` : `File ${t + 1}`);
	return M`
        <div class="grid gap-2">
            <input
                type="file"
                aria-invalid=${i.length > 0 ? "true" : P}
                aria-errormessage=${i.length > 0 ? a : P}
                aria-describedby=${i.length > 0 ? a : P}
                class="starter-component-control"
                ?disabled=${n}
                multiple=${c}
                @change=${(e) => {
		let t = e.target;
		r(Array.from(t.files || []));
	}}
            />
            ${l.length > 0 ? M`<ul class="grid gap-1 text-sm text-slate-600">
                      ${l.map((e) => M`<li>${e}</li>`)}
                  </ul>` : P}
        </div>
    `;
}
function Et(e, t) {
	let { field: n, value: r, errorKey: i, disabled: a, setValue: s } = t, c = o(n), l = r && typeof r == "object" ? r : {};
	return c.length === 0 ? M`<div class="text-sm text-amber-800">Composite field has no parts.</div>` : M`
        <div class="starter-component-name-grid">
            ${c.filter((e) => e.meta?.hidden !== !0).map((t) => {
		let n = `${i}.${t.handle}`;
		return Ot(e, {
			field: t,
			value: l[t.handle],
			errors: e.state.errors.fields[n] || [],
			errorKey: n,
			disabled: a || t.meta?.disabled === !0,
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
function Dt(e, t) {
	let { field: n, value: r, errorKey: i, disabled: a, setValue: o } = t, s = re(n), c = Array.isArray(r) ? r : [], u = n.input, d = Number(u.minRows ?? 0) || 0, f = Number(u.maxRows ?? 0) || 0, p = !a && (f <= 0 || c.length < f);
	return s.length === 0 ? M`<div class="text-sm text-amber-800">Repeater has no row layout.</div>` : M`
        <div class="grid gap-4" data-formie-repeater-container>
            ${c.map((t, n) => M`
                    <div class="rounded-xl border border-slate-200 p-4" data-formie-repeater-item>
                        ${s.map((r, s) => kt(e, r, t, `${i}.${n}`, a, (e, t) => {
		let r = c.map((r, i) => i === n ? {
			...r,
			[e.handle]: t
		} : r);
		o(r);
	}))}
                        <button
                            type="button"
                            class="mt-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700"
                            ?disabled=${a || d > 0 && c.length <= d}
                            @click=${() => {
		o(c.filter((e, t) => t !== n));
	}}
                        >
                            Remove
                        </button>
                    </div>
                `)}
            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm"
                ?disabled=${!p}
                @click=${() => {
		o([...c, l(n)]);
	}}
            >
                ${String(u.addLabel ?? "Add another row")}
            </button>
        </div>
    `;
}
function Ot(e, t, n = "default") {
	let { field: r, value: i, errors: a, errorKey: o, disabled: s, setValue: c } = t, l = e.state.fieldStates[o]?.hidden === !0;
	if (l) return M``;
	let u = gt(r), p = f(e.state.session, o), m = d(e.state.definition), h = e.registry.fieldControls[r.type] || e.registry.fieldControls[u] || null, ee = (t) => {
		c(t), e.host.requestUpdate();
	};
	return bt(e, r, a, p, m, h ? yt(e, h, r, i, o, a, p, m, s, l, ee) : wt(e, {
		...t,
		errorId: p,
		errorAriaLive: m,
		setValue: ee
	}), n);
}
function kt(e, t, n, r, i, a) {
	return M`
        <div class="starter-core-row grid gap-4">
            ${t.fields.map((t) => {
		let o = `${r}.${t.handle}`;
		return Ot(e, {
			field: t,
			value: n[t.handle],
			errors: e.state.errors.fields[o] || [],
			errorKey: o,
			disabled: i === !0 || e.state.fieldStates[o]?.disabled === !0,
			setValue(e) {
				a(t, e);
			}
		});
	})}
        </div>
    `;
}
function At(e, t) {
	return M`
        <div class="starter-core-row grid gap-4">
            ${t.fields.map((t) => Ot(e, {
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
function jt(e) {
	let t = e.state.definition.pages.find((t) => t.id === e.state.currentPageId);
	if (!t) return M``;
	let n = e.registry.regions.pageActions;
	if (n) {
		let r = _t(n);
		return V`<${r}
            .page=${t}
            .state=${e.state}
            .instance=${e.instance}
        ></${r}>`;
	}
	return M`
        <div class="formie-page-actions">
            ${t.actions.secondary.map((t) => M`
            <button
                type="button"
                @click=${() => {
		e.instance.submit(t.type);
	}}
            >
                ${t.label}
            </button>
        `)}
            <button type="submit" ?disabled=${!a(e.state)}>${t.actions.primary.label}</button>
        </div>
    `;
}
function Mt(e) {
	let t = e.state.definition.pages.find((t) => t.id === e.state.currentPageId && e.state.pageStates[t.id]?.hidden !== !0) || e.state.definition.pages.find((t) => e.state.pageStates[t.id]?.hidden !== !0) || e.state.definition.pages[0];
	if (!t) return M``;
	let n = e.state.errors.form, r = e.state.lastSubmitResult?.messages.error, i = !!r && !n.includes(r), a = M`
        ${n.length > 0 ? M`<div class="starter-core-msg starter-core-msg-error mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                  <ul class="list-inside list-disc">
                      ${n.map((e) => M`<li>${e}</li>`)}
                  </ul>
              </div>` : P}
        ${e.state.lastSubmitResult?.messages.notice ? M`<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                  ${e.state.lastSubmitResult.messages.notice}
              </div>` : P}
        ${i ? M`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${r}</div>` : P}
        ${e.state.lastSubmitResult?.completion?.behavior === "message" && e.state.lastSubmitResult.completion.hideForm ? P : Nt(e, t)}
    `, o = e.state.session.tokens.render ?? "";
	return M`
        <form
            class=${e.formClass || "starter-component-form starter-core-preview text-slate-900"}
            data-formie-definition=${e.state.definition.handle}
            data-formie-render-id=${o}
            @submit=${async (t) => {
		t.preventDefault();
		let n = t.currentTarget;
		await e.instance.submit(), requestAnimationFrame(() => n.querySelector("[aria-invalid=\"true\"]")?.focus());
	}}
        >
            ${a}
        </form>
    `;
}
function Nt(e, t) {
	let n = e.registry.regions.page, r = M`
        <div class="starter-core-fields grid gap-4">
            ${t.rows.map((t) => At(e, t))}
        </div>
        ${jt(e)}
    `;
	if (n) {
		let i = _t(n);
		return V`<${i} .page=${t} .state=${e.state}>${r}</${i}>`;
	}
	return M`
        <section data-page-id=${t.id} class="starter-core-page space-y-4">
            ${r}
        </section>
    `;
}
function Pt(e = "Loading form…") {
	return M`<div class="mt-3 text-sm text-slate-500">${e}</div>`;
}
function Ft(e) {
	return M`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${e}</div>`;
}
//#endregion
//#region \0@oxc-project+runtime@0.149.0/helpers/esm/decorate.js
function X(e, t, n, r) {
	var i = arguments.length, a = i < 3 ? t : r === null ? r = Object.getOwnPropertyDescriptor(t, n) : r, o;
	if (typeof Reflect == "object" && typeof Reflect.decorate == "function") a = Reflect.decorate(e, t, n, r);
	else for (var s = e.length - 1; s >= 0; s--) (o = e[s]) && (a = (i < 3 ? o(a) : i > 3 ? o(t, n, a) : o(t, n)) || a);
	return i > 3 && a && Object.defineProperty(t, n, a), a;
}
//#endregion
//#region src/formie-client-form.ts
function It(e) {
	return W(e), Je(e);
}
var Z = class extends B {
	constructor(...e) {
		super(...e), this.endpoint = "", this.profile = "same-origin-browser", this.formHandle = "", this.transport = "rest", this.fetchCredentials = "same-origin", this.query = {}, this.formClass = "", this.loadingMessage = "Loading form…", this.loadError = null, this.booting = !1, this.snapshot = null, this.instance = null, this.unsubscribers = [], this.loadGeneration = 0;
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
			"profile",
			"fetchCredentials",
			"query"
		].some((t) => e.has(t)) && this.bootstrap(!0);
	}
	getFormieInstance() {
		return this.instance;
	}
	async reload() {
		await this.bootstrap(!0);
	}
	get resolvedRegistry() {
		return this.registry ?? et();
	}
	teardown() {
		this.moduleHost?.destroy(), this.moduleHost = void 0;
		for (let e of this.unsubscribers) e();
		this.unsubscribers = [], this.instance?.destroy(), this.instance = null, this.snapshot = null;
	}
	async bootstrap(e) {
		let t = ++this.loadGeneration, n = this.formHandle.trim(), a = this.endpoint.trim();
		if (!n) {
			this.teardown(), this.booting = !1, this.loadError = "Set `form-handle` on <formie-client-form>.";
			return;
		}
		if (e) this.teardown();
		else if (this.instance) return;
		this.booting = !0, this.loadError = null;
		let o = this.fetchCredentials, l = this.siteId;
		try {
			let e = {
				endpoint: a,
				profile: this.profile,
				formHandle: n,
				...l === void 0 ? {} : { siteId: l },
				credentials: o,
				query: this.query
			}, d = this.transport === "graphql" ? await ne(e) : await te(e);
			if (t !== this.loadGeneration) return;
			let f = this.transport === "graphql" ? c(e) : u(e), p = s({
				envelope: d,
				transport: f
			});
			this.instance = p;
			for (let e of i) {
				let t = p.on(e, (t) => {
					this.dispatchEvent(new CustomEvent(e, {
						detail: t,
						bubbles: !0,
						composed: !0
					}));
				});
				this.unsubscribers.push(t);
			}
			this.unsubscribers.push(p.subscribe((e) => {
				this.snapshot = e, this.requestUpdate();
			})), this.booting = !1, p.setBrowserModuleGuard(() => {
				throw Error("Form features are still loading.");
			}), await this.updateComplete;
			let m = await r(this, p);
			t === this.loadGeneration ? this.moduleHost = m : await m.destroy();
		} catch (e) {
			if (t !== this.loadGeneration) return;
			this.teardown(), this.booting = !1, this.loadError = e instanceof Error ? e.message : "Unable to load the form.";
		}
	}
	render() {
		let e = this.resolvedRegistry;
		if (this.loadError) {
			let t = e.regions.errorSummary;
			if (t) {
				let e = It(t);
				return V`<${e} .errors=${[this.loadError]} .kind=${"load"}></${e}>`;
			}
			return Ft(this.loadError);
		}
		if (this.booting || !this.snapshot || !this.instance) {
			let t = e.regions.loading;
			if (t) {
				let e = It(t);
				return V`<${e} .message=${this.loadingMessage}></${e}>`;
			}
			return Pt(this.loadingMessage);
		}
		return Mt({
			registry: e,
			state: this.snapshot,
			instance: this.instance,
			host: this,
			formClass: this.formClass || ""
		});
	}
};
X([H({ type: String })], Z.prototype, "endpoint", void 0), X([H({
	type: String,
	attribute: "request-profile"
})], Z.prototype, "profile", void 0), X([H({
	type: String,
	attribute: "form-handle"
})], Z.prototype, "formHandle", void 0), X([H({
	type: Number,
	attribute: "site-id",
	converter: { fromAttribute(e) {
		if (e == null || e === "") return;
		let t = Number(e);
		return Number.isFinite(t) ? t : void 0;
	} }
})], Z.prototype, "siteId", void 0), X([H({
	attribute: "transport",
	converter: { fromAttribute(e) {
		return (e ?? "rest").toLowerCase() === "graphql" ? "graphql" : "rest";
	} }
})], Z.prototype, "transport", void 0), X([H({
	attribute: "fetch-credentials",
	converter: { fromAttribute(e) {
		return e === "omit" || e === "same-origin" || e === "include" ? e : "same-origin";
	} }
})], Z.prototype, "fetchCredentials", void 0), X([H({ attribute: !1 })], Z.prototype, "query", void 0), X([H({
	type: String,
	attribute: "form-class"
})], Z.prototype, "formClass", void 0), X([H({
	type: String,
	attribute: "loading-message"
})], Z.prototype, "loadingMessage", void 0), X([H({ attribute: !1 })], Z.prototype, "registry", void 0), X([U()], Z.prototype, "loadError", void 0), X([U()], Z.prototype, "booting", void 0), X([U()], Z.prototype, "snapshot", void 0);
//#endregion
//#region src/form-element.ts
function Lt(e) {
	if (e == null || e === "") return !1;
	let t = e.toLowerCase();
	return t === "true" || t === "1";
}
function Q(e, t, n) {
	typeof n == "string" && n.length > 0 ? e.setAttribute(t, n) : e.removeAttribute(t);
}
function Rt(e, t, n) {
	n === !0 ? e.setAttribute(t, "true") : e.removeAttribute(t);
}
function zt(e, t) {
	if (t) return t.startsWith("http") ? t : `${e}${t}`;
}
var Bt = class extends HTMLElement {
	static get observedAttributes() {
		return [
			"mode",
			"transport",
			"request-profile",
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
		this.mountRoot ||= (this.client = n(), document.createElement("div"));
	}
	connectedCallback() {
		this.ensureInitialized(), this.style.display = "block", this.contains(this.mountRoot) || this.append(this.mountRoot), this.scheduleMount();
	}
	disconnectedCallback() {
		this.unmount();
	}
	attributeChangedCallback(e, t, n) {
		t !== n && this.isConnected && (e !== "refresh-tokens" || this.optionState.refreshTokens === void 0) && (e !== "static-cache" || this.optionState.staticCache === void 0) && this.scheduleMount();
	}
	get baseUrl() {
		let e = this.getAttribute("base-url");
		return this.optionState.baseUrl ?? e ?? void 0;
	}
	set baseUrl(e) {
		this.optionState.baseUrl = e, Q(this, "base-url", e), this.scheduleMount();
	}
	get transport() {
		let e = this.getAttribute("transport");
		return this.optionState.transport ?? e ?? void 0;
	}
	set transport(e) {
		this.optionState.transport = e, Q(this, "transport", e), this.scheduleMount();
	}
	get theme() {
		let e = this.getAttribute("theme");
		return this.optionState.theme ?? e ?? void 0;
	}
	set theme(e) {
		this.optionState.theme = e, Q(this, "theme", e), this.scheduleMount();
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
		this.optionState.formHandle = e, Q(this, "form-handle", e), this.scheduleMount();
	}
	get endpoint() {
		let e = this.getAttribute("endpoint");
		return this.optionState.endpoint ?? e ?? void 0;
	}
	set endpoint(e) {
		this.optionState.endpoint = e, Q(this, "endpoint", e), this.scheduleMount();
	}
	get staticCache() {
		return this.optionState.staticCache ?? (this.hasAttribute("static-cache") ? Lt(this.getAttribute("static-cache")) : void 0);
	}
	set staticCache(e) {
		this.optionState.staticCache = e, Rt(this, "static-cache", e), this.scheduleMount();
	}
	get refreshTokens() {
		return this.optionState.refreshTokens ?? (this.hasAttribute("refresh-tokens") ? Lt(this.getAttribute("refresh-tokens")) : void 0);
	}
	set refreshTokens(e) {
		this.optionState.refreshTokens = e, Rt(this, "refresh-tokens", e), this.scheduleMount();
	}
	get locale() {
		let e = this.getAttribute("locale");
		return this.optionState.locale ?? e ?? void 0;
	}
	set locale(e) {
		this.optionState.locale = e, Q(this, "locale", e), this.scheduleMount();
	}
	get siteId() {
		return this.optionState.siteId ?? (this.getAttribute("site-id") ? Number(this.getAttribute("site-id")) : void 0);
	}
	set siteId(e) {
		this.optionState.siteId = e, Q(this, "site-id", typeof e == "number" ? String(e) : void 0), this.scheduleMount();
	}
	get autoVisible() {
		return this.optionState.autoVisible ?? (this.hasAttribute("auto-visible") ? Lt(this.getAttribute("auto-visible")) : void 0);
	}
	set autoVisible(e) {
		this.optionState.autoVisible = e, Rt(this, "auto-visible", e), this.scheduleMount();
	}
	get mode() {
		let e = this.getAttribute("mode");
		return this.optionState.mode ?? e ?? "server-rendered";
	}
	set mode(e) {
		this.optionState.mode = e, Q(this, "mode", e), this.scheduleMount();
	}
	getInstance() {
		return this.ensureInitialized(), this.mountedInstance;
	}
	async submit(e = "submit") {
		return this.ensureInitialized(), this.mountedInstance ? this.mountedInstance.submit(e) : null;
	}
	buildOptions() {
		let e = this.baseUrl || "", t = this.transport, n = t === "graphql" ? "/api" : "/actions/formie/server/forms/render", r = zt(e, this.endpoint || n), i = this.staticCache, a = this.refreshTokens;
		return {
			profile: this.optionState.profile ?? this.getAttribute("request-profile") ?? "same-origin-browser",
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
	bindInstanceEvents(t) {
		this.eventUnsubs.forEach((e) => e()), this.eventUnsubs = e.map((e) => t.on(e, (t) => {
			this.dispatchEvent(new CustomEvent(e, {
				detail: t,
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
		super(...e), this.modules = {
			contractVersion: 2,
			surface: "client-rendered",
			entries: []
		}, this.value = "", this.disabled = !1, this.loadError = null, this.pad = null, this.strokeListener = () => {
			this.emitValue();
		}, this.onWinResize = () => {
			let e = this.shadowRoot?.querySelector("canvas");
			e instanceof HTMLCanvasElement && this.resizeCanvas(e);
		};
	}
	static {
		this.styles = le`
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
		if (e && e instanceof HTMLCanvasElement) try {
			let { default: t } = await import("./signature_pad-dbpVgfTh.js"), n = this.resolveDrawModuleConfig(), r = typeof n?.backgroundColor == "string" ? String(n.backgroundColor) : "#ffffff", i = typeof n?.penColor == "string" ? String(n.penColor) : "#000000", a = Number(n?.penWeight ?? 2) || 2, o = new t(e, {
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
		let e = new Set(this.field.moduleRefs || []), t = this.modules.entries.find((t) => e.has(t.key) && t.moduleId === "formie:signature");
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
			this.dispatchEvent(new CustomEvent(Y, {
				detail: "",
				bubbles: !0,
				composed: !0
			}));
			return;
		}
		this.dispatchEvent(new CustomEvent(Y, {
			detail: this.pad.toDataURL(),
			bubbles: !0,
			composed: !0
		}));
	}
	render() {
		return this.loadError ? M`<div class="err">${this.loadError}</div>` : M`
            <div class="wrap">
                <canvas></canvas>
                <button type="button" ?disabled=${this.disabled} @click=${() => {
			this.pad?.clear(), this.dispatchEvent(new CustomEvent(Y, {
				detail: "",
				bubbles: !0,
				composed: !0
			}));
		}}>Clear</button>
            </div>
        `;
	}
};
X([H({ attribute: !1 })], $.prototype, "field", void 0), X([H({ attribute: !1 })], $.prototype, "modules", void 0), X([H({ type: String })], $.prototype, "value", void 0), X([H({ type: Boolean })], $.prototype, "disabled", void 0), X([U()], $.prototype, "loadError", void 0);
//#endregion
//#region src/index.ts
var Vt = !1;
function Ht() {
	Vt || (Vt = !0, customElements.get("formie-form") || customElements.define("formie-form", Bt), customElements.get("formie-internal-signature") || customElements.define("formie-internal-signature", $), customElements.get("formie-client-form") || customElements.define("formie-client-form", Z));
}
//#endregion
export { Y as FORMIE_CONTROL_VALUE_EVENT, Z as FormieClientFormElement, Bt as FormieFormElement, $ as FormieInternalSignature, G as FormieRegistry, W as assertValidCustomElementName, t as createFormieClient, tt as createFormieRegistry, et as getFormieRegistry, ht as isFieldDefinition, Ht as registerFormieWebComponents, Ft as renderErrorView, Mt as renderFormView, Pt as renderLoadingView, gt as resolveFieldRendererType };
