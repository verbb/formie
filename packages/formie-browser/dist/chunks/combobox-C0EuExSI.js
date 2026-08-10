import { t as e } from "./debug-BV0DvdHx.js";
import { t } from "./styles-BfoIZwJp.js";
import { r as n, t as r } from "./shared-Bx9s0i0P.js";
//#region ../../node_modules/tom-select/dist/esm/contrib/microevent.js
function i(e, t) {
	e.split(/\s+/).forEach((e) => {
		t(e);
	});
}
var a = class {
	constructor() {
		this._events = {};
	}
	on(e, t) {
		i(e, (e) => {
			let n = this._events[e] || [];
			n.push(t), this._events[e] = n;
		});
	}
	off(e, t) {
		var n = arguments.length;
		if (n === 0) {
			this._events = {};
			return;
		}
		i(e, (e) => {
			if (n === 1) {
				delete this._events[e];
				return;
			}
			let r = this._events[e];
			r !== void 0 && (r.splice(r.indexOf(t), 1), this._events[e] = r);
		});
	}
	trigger(e, ...t) {
		var n = this;
		i(e, (e) => {
			let r = n._events[e];
			r !== void 0 && r.forEach((e) => {
				e.apply(n, t);
			});
		});
	}
};
//#endregion
//#region ../../node_modules/tom-select/dist/esm/contrib/microplugin.js
function o(e) {
	return e.plugins = {}, class extends e {
		constructor() {
			super(...arguments), this.plugins = {
				names: [],
				settings: {},
				requested: {},
				loaded: {}
			};
		}
		static define(t, n) {
			e.plugins[t] = {
				name: t,
				fn: n
			};
		}
		initializePlugins(e) {
			var t, n;
			let r = this, i = [];
			if (Array.isArray(e)) e.forEach((e) => {
				typeof e == "string" ? i.push(e) : (r.plugins.settings[e.name] = e.options, i.push(e.name));
			});
			else if (e) for (t in e) e.hasOwnProperty(t) && (r.plugins.settings[t] = e[t], i.push(t));
			for (; n = i.shift();) r.require(n);
		}
		loadPlugin(t) {
			var n = this, r = n.plugins, i = e.plugins[t];
			if (!e.plugins.hasOwnProperty(t)) throw Error("Unable to find \"" + t + "\" plugin");
			r.requested[t] = !0, r.loaded[t] = i.fn.apply(n, [n.plugins.settings[t] || {}]), r.names.push(t);
		}
		require(e) {
			var t = this, n = t.plugins;
			if (!t.plugins.loaded.hasOwnProperty(e)) {
				if (n.requested[e]) throw Error("Plugin has circular dependency (\"" + e + "\")");
				t.loadPlugin(e);
			}
			return n.loaded[e];
		}
	};
}
//#endregion
//#region ../../node_modules/@orchidjs/unicode-variants/dist/esm/regex.js
var s = (e) => (e = e.filter(Boolean), e.length < 2 ? e[0] || "" : f(e) == 1 ? "[" + e.join("") + "]" : "(?:" + e.join("|") + ")"), c = (e) => {
	if (!u(e)) return e.join("");
	let t = "", n = 0, r = () => {
		n > 1 && (t += "{" + n + "}");
	};
	return e.forEach((i, a) => {
		if (i === e[a - 1]) {
			n++;
			return;
		}
		r(), t += i, n = 1;
	}), r(), t;
}, l = (e) => s(Array.from(e)), u = (e) => new Set(e).size !== e.length, d = (e) => (e + "").replace(/([\$\(\)\*\+\.\?\[\]\^\{\|\}\\])/gu, "\\$1"), f = (e) => e.reduce((e, t) => Math.max(e, p(t)), 0), p = (e) => Array.from(e).length, m = (e) => {
	if (e.length === 1) return [[e]];
	let t = [];
	return m(e.substring(1)).forEach(function(n) {
		let r = n.slice(0);
		r[0] = e.charAt(0) + r[0], t.push(r), r = n.slice(0), r.unshift(e.charAt(0)), t.push(r);
	}), t;
}, h = [[0, 65535]], g, _, v = 3, y = {}, b = {
	"/": "⁄∕",
	0: "߀",
	a: "ⱥɐɑ",
	aa: "ꜳ",
	ae: "æǽǣ",
	ao: "ꜵ",
	au: "ꜷ",
	av: "ꜹꜻ",
	ay: "ꜽ",
	b: "ƀɓƃ",
	c: "ꜿƈȼↄ",
	d: "đɗɖᴅƌꮷԁɦ",
	e: "ɛǝᴇɇ",
	f: "ꝼƒ",
	g: "ǥɠꞡᵹꝿɢ",
	h: "ħⱨⱶɥ",
	i: "ɨı",
	j: "ɉȷ",
	k: "ƙⱪꝁꝃꝅꞣ",
	l: "łƚɫⱡꝉꝇꞁɭ",
	m: "ɱɯϻ",
	n: "ꞥƞɲꞑᴎлԉ",
	o: "øǿɔɵꝋꝍᴑ",
	oe: "œ",
	oi: "ƣ",
	oo: "ꝏ",
	ou: "ȣ",
	p: "ƥᵽꝑꝓꝕρ",
	q: "ꝗꝙɋ",
	r: "ɍɽꝛꞧꞃ",
	s: "ßȿꞩꞅʂ",
	t: "ŧƭʈⱦꞇ",
	th: "þ",
	tz: "ꜩ",
	u: "ʉ",
	v: "ʋꝟʌ",
	vy: "ꝡ",
	w: "ⱳ",
	y: "ƴɏỿ",
	z: "ƶȥɀⱬꝣ",
	hv: "ƕ"
};
for (let e in b) {
	let t = b[e] || "";
	for (let n = 0; n < t.length; n++) {
		let r = t.substring(n, n + 1);
		y[r] = e;
	}
}
var x = RegExp(Object.keys(y).join("|") + "|[̀-ͯ·ʾʼ]", "gu"), S = (e) => {
	g === void 0 && (g = ie(e || h));
}, ee = (e, t = "NFKD") => e.normalize(t), C = (e) => Array.from(e).reduce((e, t) => e + te(t), ""), te = (e) => (e = ee(e).toLowerCase().replace(x, (e) => y[e] || ""), ee(e, "NFC"));
function* ne(e) {
	for (let [t, n] of e) for (let e = t; e <= n; e++) {
		let t = String.fromCharCode(e), n = C(t);
		n != t.toLowerCase() && (n.length > v || n.length != 0 && (yield {
			folded: n,
			composed: t,
			code_point: e
		}));
	}
}
var re = (e) => {
	let t = {}, n = (e, n) => {
		let r = t[e] || /* @__PURE__ */ new Set(), i = RegExp("^" + l(r) + "$", "iu");
		n.match(i) || (r.add(d(n)), t[e] = r);
	};
	for (let t of ne(e)) n(t.folded, t.folded), n(t.folded, t.composed);
	return t;
}, ie = (e) => {
	let t = re(e), n = {}, r = [];
	for (let e in t) {
		let i = t[e];
		i && (n[e] = l(i)), e.length > 1 && r.push(d(e));
	}
	r.sort((e, t) => t.length - e.length);
	let i = s(r);
	return _ = RegExp("^" + i, "u"), n;
}, ae = (e, t = 1) => {
	let n = 0;
	return e = e.map((e) => (g[e] && (n += e.length), g[e] || e)), n >= t ? c(e) : "";
}, oe = (e, t = 1) => (t = Math.max(t, e.length - 1), s(m(e).map((e) => ae(e, t)))), se = (e, t = !0) => {
	let n = +(e.length > 1);
	return s(e.map((e) => {
		let r = [], i = t ? e.length() : e.length() - 1;
		for (let t = 0; t < i; t++) r.push(oe(e.substrs[t] || "", n));
		return c(r);
	}));
}, ce = (e, t) => {
	for (let n of t) {
		if (n.start != e.start || n.end != e.end || n.substrs.join("") !== e.substrs.join("")) continue;
		let t = e.parts;
		if (!(n.parts.filter((e) => {
			for (let n of t) {
				if (n.start === e.start && n.substr === e.substr) return !1;
				if (!(e.length == 1 || n.length == 1) && (e.start < n.start && e.end > n.start || n.start < e.start && n.end > e.start)) return !0;
			}
			return !1;
		}).length > 0)) return !0;
	}
	return !1;
}, w = class e {
	parts;
	substrs;
	start;
	end;
	constructor() {
		this.parts = [], this.substrs = [], this.start = 0, this.end = 0;
	}
	add(e) {
		e && (this.parts.push(e), this.substrs.push(e.substr), this.start = Math.min(e.start, this.start), this.end = Math.max(e.end, this.end));
	}
	last() {
		return this.parts[this.parts.length - 1];
	}
	length() {
		return this.parts.length;
	}
	clone(t, n) {
		let r = new e(), i = JSON.parse(JSON.stringify(this.parts)), a = i.pop();
		for (let e of i) r.add(e);
		let o = n.substr.substring(0, t - a.start), s = o.length;
		return r.add({
			start: a.start,
			end: a.start + s,
			length: s,
			substr: o
		}), r;
	}
}, le = (e) => {
	S(), e = C(e);
	let t = "", n = [new w()];
	for (let r = 0; r < e.length; r++) {
		let i = e.substring(r).match(_), a = e.substring(r, r + 1), o = i ? i[0] : null, s = [], c = /* @__PURE__ */ new Set();
		for (let e of n) {
			let t = e.last();
			if (!t || t.length == 1 || t.end <= r) if (o) {
				let t = o.length;
				e.add({
					start: r,
					end: r + t,
					length: t,
					substr: o
				}), c.add("1");
			} else e.add({
				start: r,
				end: r + 1,
				length: 1,
				substr: a
			}), c.add("2");
			else if (o) {
				let n = e.clone(r, t), i = o.length;
				n.add({
					start: r,
					end: r + i,
					length: i,
					substr: o
				}), s.push(n);
			} else c.add("3");
		}
		if (s.length > 0) {
			s = s.sort((e, t) => e.length() - t.length());
			for (let e of s) ce(e, n) || n.push(e);
			continue;
		}
		if (r > 0 && c.size == 1 && !c.has("3")) {
			t += se(n, !1);
			let e = new w(), r = n[0];
			r && e.add(r.last()), n = [e];
		}
	}
	return t += se(n, !0), t;
}, ue = (e, t) => {
	if (e) return e[t];
}, de = (e, t) => {
	if (e) {
		for (var n, r = t.split("."); (n = r.shift()) && (e = e[n]););
		return e;
	}
}, T = (e, t, n) => {
	var r, i;
	return !e || (e += "", t.regex == null) || (i = e.search(t.regex), i === -1) ? 0 : (r = t.string.length / e.length, i === 0 && (r += .5), r * n);
}, E = (e, t) => {
	var n = e[t];
	if (typeof n == "function") return n;
	n && !Array.isArray(n) && (e[t] = [n]);
}, D = (e, t) => {
	if (Array.isArray(e)) e.forEach(t);
	else for (var n in e) e.hasOwnProperty(n) && t(e[n], n);
}, fe = (e, t) => typeof e == "number" && typeof t == "number" ? e > t ? 1 : e < t ? -1 : 0 : (e = C(e + "").toLowerCase(), t = C(t + "").toLowerCase(), e > t ? 1 : t > e ? -1 : 0), pe = class {
	items;
	settings;
	constructor(e, t) {
		this.items = e, this.settings = t || { diacritics: !0 };
	}
	tokenize(e, t, n) {
		if (!e || !e.length) return [];
		let r = [], i = e.split(/\s+/);
		var a;
		return n && (a = RegExp("^(" + Object.keys(n).map(d).join("|") + "):(.*)$")), i.forEach((e) => {
			let n, i = null, o = null;
			a && (n = e.match(a)) && (i = n[1], e = n[2]), e.length > 0 && (o = this.settings.diacritics ? le(e) || null : d(e), o && t && (o = "\\b" + o)), r.push({
				string: e,
				regex: o ? new RegExp(o, "iu") : null,
				field: i
			});
		}), r;
	}
	getScoreFunction(e, t) {
		var n = this.prepareSearch(e, t);
		return this._getScoreFunction(n);
	}
	_getScoreFunction(e) {
		let t = e.tokens, n = t.length;
		if (!n) return function() {
			return 0;
		};
		let r = e.options.fields, i = e.weights, a = r.length, o = e.getAttrFn;
		if (!a) return function() {
			return 1;
		};
		let s = (function() {
			return a === 1 ? function(e, t) {
				let n = r[0].field;
				return T(o(t, n), e, i[n] || 1);
			} : function(e, t) {
				var n = 0;
				if (e.field) {
					let r = o(t, e.field);
					!e.regex && r ? n += 1 / a : n += T(r, e, 1);
				} else D(i, (r, i) => {
					n += T(o(t, i), e, r);
				});
				return n / a;
			};
		})();
		return n === 1 ? function(e) {
			return s(t[0], e);
		} : e.options.conjunction === "and" ? function(e) {
			var r, i = 0;
			for (let n of t) {
				if (r = s(n, e), r <= 0) return 0;
				i += r;
			}
			return i / n;
		} : function(e) {
			var r = 0;
			return D(t, (t) => {
				r += s(t, e);
			}), r / n;
		};
	}
	getSortFunction(e, t) {
		var n = this.prepareSearch(e, t);
		return this._getSortFunction(n);
	}
	_getSortFunction(e) {
		var t, n = [];
		let r = this, i = e.options, a = !e.query && i.sort_empty ? i.sort_empty : i.sort;
		if (typeof a == "function") return a.bind(this);
		let o = function(t, n) {
			return t === "$score" ? n.score : e.getAttrFn(r.items[n.id], t);
		};
		if (a) for (let t of a) (e.query || t.field !== "$score") && n.push(t);
		if (e.query) {
			t = !0;
			for (let e of n) if (e.field === "$score") {
				t = !1;
				break;
			}
			t && n.unshift({
				field: "$score",
				direction: "desc"
			});
		} else n = n.filter((e) => e.field !== "$score");
		return n.length ? function(e, t) {
			var r, i;
			for (let a of n) if (i = a.field, r = (a.direction === "desc" ? -1 : 1) * fe(o(i, e), o(i, t)), r) return r;
			return 0;
		} : null;
	}
	prepareSearch(e, t) {
		let n = {};
		var r = Object.assign({}, t);
		if (E(r, "sort"), E(r, "sort_empty"), r.fields) {
			E(r, "fields");
			let e = [];
			r.fields.forEach((t) => {
				typeof t == "string" && (t = {
					field: t,
					weight: 1
				}), e.push(t), n[t.field] = "weight" in t ? t.weight : 1;
			}), r.fields = e;
		}
		return {
			options: r,
			query: e.toLowerCase().trim(),
			tokens: this.tokenize(e, r.respect_word_boundaries, n),
			total: 0,
			items: [],
			weights: n,
			getAttrFn: r.nesting ? de : ue
		};
	}
	search(e, t) {
		var n = this, r, i = this.prepareSearch(e, t);
		t = i.options, e = i.query;
		let a = t.score || n._getScoreFunction(i);
		e.length ? D(n.items, (e, n) => {
			r = a(e), (t.filter === !1 || r > 0) && i.items.push({
				score: r,
				id: n
			});
		}) : D(n.items, (e, t) => {
			i.items.push({
				score: 1,
				id: t
			});
		});
		let o = n._getSortFunction(i);
		return o && i.items.sort(o), i.total = i.items.length, typeof t.limit == "number" && (i.items = i.items.slice(0, t.limit)), i;
	}
}, O = (e) => e == null ? null : k(e), k = (e) => typeof e == "boolean" ? e ? "1" : "0" : e + "", A = (e) => (e + "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"), me = (e, t) => t > 0 ? window.setTimeout(e, t) : (e.call(null), null), he = (e, t) => {
	var n;
	return function(r, i) {
		var a = this;
		n && (a.loading = Math.max(a.loading - 1, 0), clearTimeout(n)), n = setTimeout(function() {
			n = null, a.loadedSearches[r] = !0, e.call(a, r, i);
		}, t);
	};
}, ge = (e, t, n) => {
	var r, i = e.trigger, a = {};
	e.trigger = function() {
		var n = arguments[0];
		if (t.indexOf(n) !== -1) a[n] = arguments;
		else return i.apply(e, arguments);
	}, n.apply(e, []), e.trigger = i;
	for (r of t) r in a && i.apply(e, a[r]);
}, _e = (e) => ({
	start: e.selectionStart || 0,
	length: (e.selectionEnd || 0) - (e.selectionStart || 0)
}), j = (e, t = !1) => {
	e && (e.preventDefault(), t && e.stopPropagation());
}, M = (e, t, n, r) => {
	e.addEventListener(t, n, r);
}, N = (e, t) => !t || !t[e] ? !1 : +!!t.altKey + +!!t.ctrlKey + +!!t.shiftKey + +!!t.metaKey == 1, P = (e, t) => e.getAttribute("id") || (e.setAttribute("id", t), t), F = (e) => e.replace(/[\\"']/g, "\\$&"), I = (e, t) => {
	t && e.append(t);
}, L = (e, t) => {
	if (Array.isArray(e)) e.forEach(t);
	else for (var n in e) e.hasOwnProperty(n) && t(e[n], n);
}, R = (e) => {
	if (e.jquery) return e[0];
	if (e instanceof HTMLElement) return e;
	if (z(e)) {
		var t = document.createElement("template");
		return t.innerHTML = e.trim(), t.content.firstChild;
	}
	return document.querySelector(e);
}, z = (e) => typeof e == "string" && e.indexOf("<") > -1, ve = (e) => e.replace(/['"\\]/g, "\\$&"), B = (e, t) => {
	var n = document.createEvent("HTMLEvents");
	n.initEvent(t, !0, !1), e.dispatchEvent(n);
}, V = (e, t) => {
	Object.assign(e.style, t);
}, H = (e, ...t) => {
	var n = ye(t);
	e = be(e), e.map((e) => {
		n.map((t) => {
			e.classList.add(t);
		});
	});
}, U = (e, ...t) => {
	var n = ye(t);
	e = be(e), e.map((e) => {
		n.map((t) => {
			e.classList.remove(t);
		});
	});
}, ye = (e) => {
	var t = [];
	return L(e, (e) => {
		typeof e == "string" && (e = e.trim().split(/[\t\n\f\r\s]/)), Array.isArray(e) && (t = t.concat(e));
	}), t.filter(Boolean);
}, be = (e) => (Array.isArray(e) || (e = [e]), e), W = (e, t, n) => {
	if (!(n && !n.contains(e))) for (; e && e.matches;) {
		if (e.matches(t)) return e;
		e = e.parentNode;
	}
}, xe = (e, t = 0) => t > 0 ? e[e.length - 1] : e[0], Se = (e) => Object.keys(e).length === 0, Ce = (e, t) => {
	if (!e) return -1;
	t ||= e.nodeName;
	for (var n = 0; e = e.previousElementSibling;) e.matches(t) && n++;
	return n;
}, G = (e, t) => {
	L(t, (t, n) => {
		t == null ? e.removeAttribute(n) : e.setAttribute(n, "" + t);
	});
}, K = (e, t) => {
	e.parentNode && e.parentNode.replaceChild(t, e);
}, we = (e, t) => {
	if (t === null) return;
	if (typeof t == "string") {
		if (!t.length) return;
		t = new RegExp(t, "i");
	}
	let n = (e) => {
		var n = e.data.match(t);
		if (n && e.data.length > 0) {
			var r = document.createElement("span");
			r.className = "highlight";
			var i = e.splitText(n.index);
			i.splitText(n[0].length);
			var a = i.cloneNode(!0);
			return r.appendChild(a), K(i, r), 1;
		}
		return 0;
	}, r = (e) => {
		e.nodeType === 1 && e.childNodes && !/(script|style)/i.test(e.tagName) && (e.className !== "highlight" || e.tagName !== "SPAN") && Array.from(e.childNodes).forEach((e) => {
			i(e);
		});
	}, i = (e) => e.nodeType === 3 ? n(e) : (r(e), 0);
	i(e);
}, Te = (e) => {
	var t = e.querySelectorAll("span.highlight");
	Array.prototype.forEach.call(t, function(e) {
		var t = e.parentNode;
		t.replaceChild(e.firstChild, e), t.normalize();
	});
}, q = !(typeof navigator > "u") && /Mac/.test(navigator.userAgent) ? "metaKey" : "ctrlKey", Ee = {
	options: [],
	optgroups: [],
	plugins: [],
	delimiter: ",",
	splitOn: null,
	persist: !0,
	diacritics: !0,
	create: null,
	createOnBlur: !1,
	createFilter: null,
	clearAfterSelect: !1,
	highlight: !0,
	openOnFocus: !0,
	shouldOpen: null,
	maxOptions: 50,
	maxItems: null,
	hideSelected: null,
	duplicates: !1,
	addPrecedence: !1,
	selectOnTab: !1,
	preload: null,
	allowEmptyOption: !1,
	refreshThrottle: 300,
	loadThrottle: 300,
	loadingClass: "loading",
	dataAttr: null,
	optgroupField: "optgroup",
	valueField: "value",
	labelField: "text",
	disabledField: "disabled",
	optgroupLabelField: "label",
	optgroupValueField: "value",
	lockOptgroupOrder: !1,
	sortField: "$order",
	searchField: ["text"],
	searchConjunction: "and",
	mode: null,
	wrapperClass: "ts-wrapper",
	controlClass: "ts-control",
	dropdownClass: "ts-dropdown",
	dropdownContentClass: "ts-dropdown-content",
	itemClass: "item",
	optionClass: "option",
	dropdownParent: null,
	controlInput: "<input type=\"text\" autocomplete=\"off\" size=\"1\" />",
	copyClassesToDropdown: !1,
	placeholder: null,
	hidePlaceholder: null,
	shouldLoad: function(e) {
		return e.length > 0;
	},
	render: {}
};
//#endregion
//#region ../../node_modules/tom-select/dist/esm/getSettings.js
function De(e, t) {
	var n = Object.assign({}, Ee, t), r = n.dataAttr, i = n.labelField, a = n.valueField, o = n.disabledField, s = n.optgroupField, c = n.optgroupLabelField, l = n.optgroupValueField, u = e.tagName.toLowerCase(), d = e.getAttribute("placeholder") || e.getAttribute("data-placeholder");
	if (!d && !n.allowEmptyOption) {
		let t = e.querySelector("option[value=\"\"]");
		t && (d = t.textContent);
	}
	var f = {
		placeholder: d,
		options: [],
		optgroups: [],
		items: [],
		maxItems: null
	};
	return u === "select" ? (() => {
		var t, u = f.options, d = {}, p = 1;
		let m = 0;
		var h = (e) => {
			var t = Object.assign({}, e.dataset), n = r && t[r];
			return typeof n == "string" && n.length && (t = Object.assign(t, JSON.parse(n))), t;
		}, g = (e, t) => {
			var r = O(e.value);
			if (r != null && !(!r && !n.allowEmptyOption)) {
				if (d.hasOwnProperty(r)) {
					if (t) {
						var c = d[r][s];
						c ? Array.isArray(c) ? c.push(t) : d[r][s] = [c, t] : d[r][s] = t;
					}
				} else {
					var l = h(e);
					l[i] = l[i] || e.textContent, l[a] = l[a] || r, l[o] = l[o] || e.disabled, l[s] = l[s] || t, l.$option = e, l.$order = l.$order || ++m, d[r] = l, u.push(l);
				}
				e.selected && f.items.push(r);
			}
		}, _ = (e) => {
			var t, n = h(e);
			n[c] = n[c] || e.getAttribute("label") || "", n[l] = n[l] || p++, n[o] = n[o] || e.disabled, n.$order = n.$order || ++m, f.optgroups.push(n), t = n[l], L(e.children, (e) => {
				g(e, t);
			});
		};
		f.maxItems = e.hasAttribute("multiple") ? null : 1, L(e.children, (e) => {
			t = e.tagName.toLowerCase(), t === "optgroup" ? _(e) : t === "option" && g(e);
		});
	})() : (() => {
		let t = e.getAttribute(r);
		if (t) f.options = JSON.parse(t), L(f.options, (e) => {
			f.items.push(e[a]);
		});
		else {
			var o = (e?.value)?.trim() ?? "";
			if (!n.allowEmptyOption && !o.length) return;
			let t = o.split(n.delimiter);
			L(t, (e) => {
				let t = {};
				t[i] = e, t[a] = e, f.options.push(t);
			}), f.items = t;
		}
	})(), Object.assign({}, Ee, f, t);
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/tom-select.js
var J = 0, Y = class extends o(a) {
	constructor(e, t) {
		super(), this.order = 0, this.isOpen = !1, this.isDisabled = !1, this.isReadOnly = !1, this.isInvalid = !1, this.isValid = !0, this.isLocked = !1, this.isFocused = !1, this.isInputHidden = !1, this.isSetup = !1, this.isDropdownContentStale = !0, this.ignoreFocus = !1, this.ignoreHover = !1, this.hasOptions = !1, this.lastValue = "", this.caretPos = 0, this.loading = 0, this.loadedSearches = {}, this.activeOption = null, this.activeItems = [], this.optgroups = {}, this.options = {}, this.userOptions = {}, this.items = [], this.refreshTimeout = null, J++;
		var n, r = R(e);
		if (r.tomselect) throw Error("Tom Select already initialized on this element");
		r.tomselect = this, n = (window.getComputedStyle && window.getComputedStyle(r, null)).getPropertyValue("direction");
		let i = De(r, t);
		this.settings = i, this.input = r, this.tabIndex = r.tabIndex || 0, this.is_select_tag = r.tagName.toLowerCase() === "select", this.rtl = /rtl/i.test(n), this.inputId = P(r, "tomselect-" + J), this.isRequired = r.required, this.sifter = new pe(this.options, { diacritics: i.diacritics }), i.mode = i.mode || (i.maxItems === 1 ? "single" : "multi"), typeof i.hideSelected != "boolean" && (i.hideSelected = i.mode === "multi"), typeof i.hidePlaceholder != "boolean" && (i.hidePlaceholder = i.mode !== "multi");
		var a = i.createFilter;
		typeof a != "function" && (typeof a == "string" && (a = new RegExp(a)), a instanceof RegExp ? i.createFilter = (e) => a.test(e) : i.createFilter = (e) => this.settings.duplicates || !this.options[e]), this.initializePlugins(i.plugins), this.setupCallbacks(), this.setupTemplates();
		let o = R("<div>"), s = R("<div>"), c = this._render("dropdown"), l = R("<div role=\"listbox\" tabindex=\"-1\">"), u = this.input.getAttribute("class") || "", d = i.mode;
		var f;
		H(o, i.wrapperClass, u, d), H(s, i.controlClass), I(o, s), H(c, i.dropdownClass, d), i.copyClassesToDropdown && H(c, u), H(l, i.dropdownContentClass), I(c, l), R(i.dropdownParent || o).appendChild(c), z(i.controlInput) ? (f = R(i.controlInput), L([
			"autocorrect",
			"autocapitalize",
			"autocomplete",
			"spellcheck",
			"aria-label"
		], (e) => {
			r.getAttribute(e) && G(f, { [e]: r.getAttribute(e) });
		}), f.tabIndex = -1, s.appendChild(f), this.focus_node = f) : i.controlInput ? (f = R(i.controlInput), this.focus_node = f) : (f = R("<input/>"), this.focus_node = s), this.wrapper = o, this.dropdown = c, this.dropdown_content = l, this.control = s, this.control_input = f, this.setup();
	}
	setup() {
		let e = this, t = e.settings, n = e.control_input, r = e.dropdown, i = e.dropdown_content, a = e.wrapper, o = e.control, s = e.input, c = e.focus_node, l = { passive: !0 }, u = e.inputId + "-ts-dropdown";
		G(i, { id: u }), G(c, {
			role: "combobox",
			"aria-haspopup": "listbox",
			"aria-expanded": "false",
			"aria-controls": u
		});
		let f = P(c, e.inputId + "-ts-control"), p = "label[for='" + ve(e.inputId) + "']", m = document.querySelector(p), h = e.focus.bind(e);
		if (m) {
			M(m, "click", h), G(m, { for: f });
			let t = P(m, e.inputId + "-ts-label");
			G(c, { "aria-labelledby": t }), G(i, { "aria-labelledby": t });
		}
		if (a.style.width = s.style.width, a.style.minWidth = s.style.minWidth, a.style.maxWidth = s.style.maxWidth, e.plugins.names.length) {
			let t = "plugin-" + e.plugins.names.join(" plugin-");
			H([a, r], t);
		}
		(t.maxItems === null || t.maxItems > 1) && e.is_select_tag && G(s, { multiple: "multiple" }), t.placeholder && G(n, { placeholder: t.placeholder }), !t.splitOn && t.delimiter && (t.splitOn = RegExp("\\s*" + d(t.delimiter) + "+\\s*")), t.load && t.loadThrottle && (t.load = he(t.load, t.loadThrottle)), M(r, "mousemove", () => {
			e.ignoreHover = !1;
		}), M(r, "mouseenter", (t) => {
			var n = W(t.target, "[data-selectable]", r);
			n && e.onOptionHover(t, n);
		}, { capture: !0 }), M(r, "click", (t) => {
			let n = W(t.target, "[data-selectable]");
			n && (e.onOptionSelect(t, n), j(t, !0));
		}), M(o, "click", (t) => {
			var r = W(t.target, "[data-ts-item]", o);
			if (r && e.onItemSelect(t, r)) {
				j(t, !0);
				return;
			}
			n.value == "" && (e.onClick(), j(t, !0));
		}), M(c, "keydown", (t) => e.onKeyDown(t)), M(n, "keypress", (t) => e.onKeyPress(t)), M(n, "input", (t) => e.onInput(t)), M(c, "blur", (t) => e.onBlur(t)), M(c, "focus", (t) => e.onFocus(t)), M(n, "paste", (t) => e.onPaste(t));
		let g = (t) => {
			let i = t.composedPath()[0];
			if (!a.contains(i) && !r.contains(i)) {
				e.isFocused && e.blur(), e.inputState();
				return;
			}
			i == n && e.isOpen ? t.stopPropagation() : j(t, !0);
		}, _ = () => {
			e.isOpen && e.positionDropdown();
		}, v = () => {
			e.isValid && (e.isValid = !1, e.isInvalid = !0, e.refreshState());
		};
		M(s, "invalid", v), M(document, "mousedown", g), M(window, "scroll", _, l), M(window, "resize", _, l), this._destroy = () => {
			s.removeEventListener("invalid", v), document.removeEventListener("mousedown", g), window.removeEventListener("scroll", _), window.removeEventListener("resize", _), m && m.removeEventListener("click", h);
		}, this.revertSettings = {
			innerHTML: s.innerHTML,
			tabIndex: s.tabIndex
		}, s.tabIndex = -1, s.insertAdjacentElement("afterend", e.wrapper), e.sync(!1), t.items = [], delete t.optgroups, delete t.options, e.refreshItems(), e.close(!1), e.inputState(), e.isSetup = !0, e.on("change", this.onChange), H(s, "tomselected", "ts-hidden-accessible"), e.trigger("initialize"), t.preload === !0 && e.preload();
	}
	setupOptions(e = [], t = []) {
		this.addOptions(e), L(t, (e) => {
			this.registerOptionGroup(e);
		});
	}
	setupTemplates() {
		var e = this, t = e.settings.labelField, n = e.settings.optgroupLabelField, r = {
			optgroup: (e) => {
				let t = document.createElement("div");
				return t.className = "optgroup", t.appendChild(e.options), t;
			},
			optgroup_header: (e, t) => "<div class=\"optgroup-header\">" + t(e[n]) + "</div>",
			option: (e, n) => "<div>" + n(e[t]) + "</div>",
			item: (e, n) => "<div>" + n(e[t]) + "</div>",
			option_create: (e, t) => "<div class=\"create\">Add <strong>" + t(e.input) + "</strong>&hellip;</div>",
			no_results: () => "<div class=\"no-results\">No results found</div>",
			loading: () => "<div class=\"spinner\"></div>",
			not_loading: () => {},
			dropdown: () => "<div></div>"
		};
		e.settings.render = Object.assign({}, r, e.settings.render);
	}
	setupCallbacks() {
		var e, t, n = {
			initialize: "onInitialize",
			change: "onChange",
			item_add: "onItemAdd",
			item_remove: "onItemRemove",
			item_select: "onItemSelect",
			clear: "onClear",
			option_add: "onOptionAdd",
			option_remove: "onOptionRemove",
			option_clear: "onOptionClear",
			optgroup_add: "onOptionGroupAdd",
			optgroup_remove: "onOptionGroupRemove",
			optgroup_clear: "onOptionGroupClear",
			dropdown_open: "onDropdownOpen",
			dropdown_close: "onDropdownClose",
			type: "onType",
			load: "onLoad",
			focus: "onFocus",
			blur: "onBlur"
		};
		for (e in n) t = this.settings[n[e]], t && this.on(e, t);
	}
	sync(e = !0) {
		let t = this, n = e ? De(t.input, {
			delimiter: t.settings.delimiter,
			allowEmptyOption: t.settings.allowEmptyOption
		}) : t.settings;
		t.setupOptions(n.options, n.optgroups), t.setValue(n.items || [], !0), t.input.disabled ? t.disable() : t.input.readOnly ? t.setReadOnly(!0) : t.enable(), t.lastQuery = null;
	}
	onClick() {
		var e = this;
		if (e.activeItems.length > 0) {
			e.clearActiveItems(), e.focus();
			return;
		}
		e.isFocused && e.isOpen ? e.blur() : e.focus();
	}
	onMouseDown() {}
	onChange() {
		B(this.input, "input"), B(this.input, "change");
	}
	onPaste(e) {
		var t = this;
		if (t.isInputHidden || t.isLocked) {
			j(e);
			return;
		}
		t.settings.splitOn && setTimeout(() => {
			var e = t.inputValue();
			e.match(t.settings.splitOn) && L(e.trim().split(t.settings.splitOn), (e) => {
				O(e) && (this.options[e] ? t.addItem(e) : t.createItem(e));
			});
		}, 0);
	}
	onKeyPress(e) {
		var t = this;
		if (t.isLocked) {
			j(e);
			return;
		}
		var n = String.fromCharCode(e.keyCode || e.which);
		if (t.settings.create && t.settings.mode === "multi" && n === t.settings.delimiter) {
			t.createItem(), j(e);
			return;
		}
	}
	onKeyDown(e) {
		var t = this;
		if (t.ignoreHover = !0, t.isLocked) {
			e.keyCode !== 9 && j(e);
			return;
		}
		switch (e.keyCode) {
			case 65:
				if (N(q, e) && t.control_input.value == "") {
					j(e), t.selectAll();
					return;
				}
				break;
			case 27:
				t.isOpen && (j(e, !0), t.close()), t.clearActiveItems();
				return;
			case 40:
				if (!t.isOpen && t.hasOptions) t.open();
				else if (t.activeOption) {
					let e = t.getAdjacent(t.activeOption, 1);
					e && t.setActiveOption(e);
				}
				j(e);
				return;
			case 38:
				if (t.activeOption) {
					let e = t.getAdjacent(t.activeOption, -1);
					e && t.setActiveOption(e);
				}
				j(e);
				return;
			case 13:
				t.canSelect(t.activeOption) ? (t.onOptionSelect(e, t.activeOption), j(e)) : (t.settings.create && t.createItem() || document.activeElement == t.control_input && t.isOpen) && j(e);
				return;
			case 37:
				t.advanceSelection(-1, e);
				return;
			case 39:
				t.advanceSelection(1, e);
				return;
			case 9:
				t.settings.selectOnTab && (t.canSelect(t.activeOption) ? (t.onOptionSelect(e, t.activeOption), j(e)) : t.settings.create && t.createItem() && j(e));
				return;
			case 8:
			case 46:
				t.deleteSelection(e);
				return;
		}
		t.isInputHidden && !N(q, e) && j(e);
	}
	onInput(e) {
		if (this.isLocked) return;
		let t = this.inputValue();
		if (this.lastValue !== t) {
			if (this.lastValue = t, t == "") {
				this._onInput();
				return;
			}
			this.refreshTimeout && window.clearTimeout(this.refreshTimeout), this.refreshTimeout = me(() => {
				this.refreshTimeout = null, this._onInput();
			}, this.settings.refreshThrottle);
		}
	}
	_onInput() {
		let e = this.lastValue;
		this.settings.shouldLoad.call(this, e) && this.load(e), this.refreshOptions(), this.trigger("type", e);
	}
	onOptionHover(e, t) {
		this.ignoreHover || this.setActiveOption(t, !1);
	}
	onFocus(e) {
		var t = this, n = t.isFocused;
		if (t.isDisabled || t.isReadOnly) {
			t.blur(), j(e);
			return;
		}
		t.ignoreFocus || (t.isFocused = !0, t.settings.preload === "focus" && t.preload(), n || t.trigger("focus"), t.activeItems.length || (t.inputState(), t.refreshOptions(!!t.settings.openOnFocus)), t.refreshState());
	}
	onBlur(e) {
		if (document.hasFocus() !== !1) {
			var t = this;
			if (t.isFocused) {
				t.isFocused = !1, t.ignoreFocus = !1;
				var n = () => {
					t.close(), t.setActiveItem(), t.setCaret(t.items.length), t.trigger("blur");
				};
				t.settings.create && t.settings.createOnBlur ? t.createItem(null, n) : n();
			}
		}
	}
	onOptionSelect(e, t) {
		var n, r = this;
		t.parentElement && t.parentElement.matches("[data-disabled]") || (t.classList.contains("create") ? r.createItem(null, () => {
			r.settings.closeAfterSelect ? r.close() : r.settings.clearAfterSelect && r.setTextboxValue();
		}) : (n = t.dataset.value, n !== void 0 && (r.isDropdownContentStale = r.settings.hideSelected, r.addItem(n), r.settings.closeAfterSelect ? r.close() : r.settings.clearAfterSelect && r.setTextboxValue(), !r.settings.hideSelected && e.type && /click/.test(e.type) && r.setActiveOption(t))));
	}
	canSelect(e) {
		return !!(this.isOpen && e && this.dropdown_content.contains(e));
	}
	onItemSelect(e, t) {
		var n = this;
		return !n.isLocked && n.settings.mode === "multi" ? (j(e), n.setActiveItem(t, e), !0) : !1;
	}
	canLoad(e) {
		return !(!this.settings.load || this.loadedSearches.hasOwnProperty(e));
	}
	load(e) {
		let t = this;
		if (!t.canLoad(e)) return;
		H(t.wrapper, t.settings.loadingClass), t.loading++;
		let n = t.loadCallback.bind(t);
		t.settings.load.call(t, e, n);
	}
	loadCallback(e, t) {
		let n = this;
		n.loading = Math.max(n.loading - 1, 0), n.isDropdownContentStale = !0, n.clearActiveOption(), n.setupOptions(e, t), n.refreshOptions(n.isFocused && !n.isInputHidden), n.loading || U(n.wrapper, n.settings.loadingClass), n.trigger("load", e, t);
	}
	preload() {
		var e = this.wrapper.classList;
		e.contains("preloaded") || (e.add("preloaded"), this.load(""));
	}
	setTextboxValue(e = "") {
		var t = this.control_input;
		t.value !== e && (t.value = e, B(t, "update"), this.lastValue = e);
	}
	getValue() {
		return this.is_select_tag && this.input.hasAttribute("multiple") ? this.items : this.items.join(this.settings.delimiter);
	}
	setValue(e, t) {
		var n = t ? [] : ["change"];
		ge(this, n, () => {
			this.clear(t), this.addItems(e, t);
		});
	}
	setMaxItems(e) {
		e === 0 && (e = null), this.settings.maxItems = e, this.refreshState();
	}
	setActiveItem(e, t) {
		var n = this, r, i, a, o, s, c;
		if (n.settings.mode !== "single") {
			if (!e) {
				n.clearActiveItems(), n.isFocused && n.inputState();
				return;
			}
			if (r = t && t.type.toLowerCase(), r === "click" && N("shiftKey", t) && n.activeItems.length) {
				for (c = n.getLastActive(), a = Array.prototype.indexOf.call(n.control.children, c), o = Array.prototype.indexOf.call(n.control.children, e), a > o && (s = a, a = o, o = s), i = a; i <= o; i++) e = n.control.children[i], n.activeItems.indexOf(e) === -1 && n.setActiveItemClass(e);
				j(t);
			} else r === "click" && N(q, t) || r === "keydown" && N("shiftKey", t) ? e.classList.contains("active") ? n.removeActiveItem(e) : n.setActiveItemClass(e) : (n.clearActiveItems(), n.setActiveItemClass(e));
			n.inputState(), n.isFocused || n.focus();
		}
	}
	setActiveItemClass(e) {
		let t = this, n = t.control.querySelector(".last-active");
		n && U(n, "last-active"), H(e, "active last-active"), t.trigger("item_select", e), t.activeItems.indexOf(e) == -1 && t.activeItems.push(e);
	}
	removeActiveItem(e) {
		var t = this.activeItems.indexOf(e);
		this.activeItems.splice(t, 1), U(e, "active");
	}
	clearActiveItems() {
		U(this.activeItems, "active"), this.activeItems = [];
	}
	setActiveOption(e, t = !0) {
		e !== this.activeOption && (this.clearActiveOption(), e && (this.activeOption = e, G(this.focus_node, { "aria-activedescendant": e.getAttribute("id") }), G(e, { "aria-selected": "true" }), H(e, "active"), t && this.scrollToOption(e)));
	}
	scrollToOption(e, t) {
		if (!e) return;
		let n = this.dropdown_content, r = n.clientHeight, i = n.scrollTop || 0, a = e.offsetHeight, o = e.getBoundingClientRect().top - n.getBoundingClientRect().top + i;
		o + a > r + i ? this.scroll(o - r + a, t) : o < i && this.scroll(o, t);
	}
	scroll(e, t) {
		let n = this.dropdown_content;
		t && (n.style.scrollBehavior = t), n.scrollTop = e, n.style.scrollBehavior = "";
	}
	clearActiveOption() {
		this.activeOption && (U(this.activeOption, "active"), G(this.activeOption, { "aria-selected": null })), this.activeOption = null, G(this.focus_node, { "aria-activedescendant": null });
	}
	selectAll() {
		let e = this;
		if (e.settings.mode === "single") return;
		let t = e.controlChildren();
		t.length && (e.inputState(), e.close(), e.activeItems = t, L(t, (t) => {
			e.setActiveItemClass(t);
		}));
	}
	inputState() {
		var e = this;
		e.control.contains(e.control_input) && (G(e.control_input, { placeholder: e.settings.placeholder }), e.activeItems.length > 0 || !e.isFocused && e.settings.hidePlaceholder && e.items.length > 0 ? (e.setTextboxValue(), e.isInputHidden = !0) : (e.settings.hidePlaceholder && e.items.length > 0 && G(e.control_input, { placeholder: "" }), e.isInputHidden = !1), e.wrapper.classList.toggle("input-hidden", e.isInputHidden));
	}
	inputValue() {
		return this.control_input.value.trim();
	}
	focus() {
		var e = this;
		if (e.isDisabled || e.isReadOnly) return;
		e.ignoreFocus = !0;
		let t = this.control_input.offsetWidth ? this.control_input : this.focus_node;
		t.focus(), setTimeout(() => {
			e.ignoreFocus = !1, t.getRootNode().activeElement === t && this.onFocus();
		}, 0);
	}
	blur() {
		this.focus_node.blur(), this.onBlur();
	}
	getScoreFunction(e) {
		return this.sifter.getScoreFunction(e, this.getSearchOptions());
	}
	getSearchOptions() {
		var e = this.settings, t = e.sortField;
		return typeof e.sortField == "string" && (t = [{ field: e.sortField }]), {
			fields: e.searchField,
			conjunction: e.searchConjunction,
			sort: t,
			nesting: e.nesting
		};
	}
	search(e) {
		var t, n, r = this, i = this.getSearchOptions();
		if (r.settings.score && (n = r.settings.score.call(r, e), typeof n != "function")) throw Error("Tom Select \"score\" setting must be a function that returns a function");
		return r.isDropdownContentStale || e !== r.lastQuery ? (r.lastQuery = e, /(.)\1{15,}/.test(e) && (e = ""), t = r.sifter.search(e, Object.assign(i, { score: n })), r.currentResults = t) : t = Object.assign({}, r.currentResults), r.settings.hideSelected && (t.items = t.items.filter((e) => {
			let t = O(e.id);
			return !(t !== null && r.items.indexOf(t) !== -1);
		})), t;
	}
	refreshOptions(e = !0) {
		var t, n, r, i, a, o, s, c, l, u;
		let d = {}, f = [];
		var p = this, m = p.inputValue();
		let h = m === p.lastQuery || m == "" && p.lastQuery == null;
		var g = p.search(m), _ = null, v = p.settings.shouldOpen || !1, y = p.dropdown_content;
		h && (_ = p.activeOption, _ && (l = _.closest("[data-group]"))), i = g.items.length, typeof p.settings.maxOptions == "number" && (i = Math.min(i, p.settings.maxOptions)), i > 0 && (v = !0);
		let b = (e, t) => {
			let n = d[e];
			if (n !== void 0) {
				let e = f[n];
				if (e !== void 0) return [n, e.fragment];
			}
			let r = document.createDocumentFragment();
			return n = f.length, f.push({
				fragment: r,
				order: t,
				optgroup: e
			}), [n, r];
		};
		for (t = 0; t < i; t++) {
			let e = g.items[t];
			if (!e) continue;
			let i = e.id, s = p.options[i];
			if (s === void 0) continue;
			let c = k(i), u = p.getOption(c, !0);
			for (p.settings.hideSelected || u.classList.toggle("selected", p.items.includes(c)), a = s[p.settings.optgroupField] || "", o = Array.isArray(a) ? a : [a], n = 0, r = o && o.length; n < r; n++) {
				a = o[n];
				let e = s.$order, t = p.optgroups[a];
				if (t === void 0 && typeof p.settings.optionGroupRegister == "function") {
					var x;
					(x = p.settings.optionGroupRegister.apply(p, [a])) && p.registerOptionGroup(x);
				}
				t = p.optgroups[a], t === void 0 ? a = "" : e = t.$order;
				let [r, c] = b(a, e);
				n > 0 && (u = u.cloneNode(!0), G(u, {
					id: s.$id + "-clone-" + n,
					"aria-selected": null
				}), u.classList.add("ts-cloned"), U(u, "active"), p.activeOption && p.activeOption.dataset.value == i && l && l.dataset.group === a.toString() && (_ = u)), c.appendChild(u), a != "" && (d[a] = r);
			}
		}
		p.settings.lockOptgroupOrder && f.sort((e, t) => e.order - t.order), s = document.createDocumentFragment(), L(f, (e) => {
			let t = e.fragment, n = e.optgroup;
			if (!t || !t.children.length) return;
			let r = p.optgroups[n];
			if (r !== void 0) {
				let e = document.createDocumentFragment();
				I(e, p.render("optgroup_header", r)), I(e, t);
				let n = p.render("optgroup", {
					group: r,
					options: e
				});
				I(s, n);
			} else I(s, t);
		}), y.innerHTML = "", I(y, s), p.isDropdownContentStale = !1, p.settings.highlight && (Te(y), g.query.length && g.tokens.length && L(g.tokens, (e) => {
			we(y, e.regex);
		}));
		var S = (e) => {
			let t = p.render(e, { input: m });
			return t && (v = !0, y.insertBefore(t, y.firstChild)), t;
		};
		if (p.loading ? S("loading") : p.settings.shouldLoad.call(p, m) ? g.items.length === 0 && S("no_results") : S("not_loading"), c = p.canCreate(m), c && (u = S("option_create")), p.hasOptions = g.items.length > 0 || c, v) {
			if (g.items.length > 0) {
				if (!_ && p.settings.mode === "single" && p.items[0] != null && (_ = p.getOption(p.items[0])), !y.contains(_)) {
					let e = 0;
					u && !p.settings.addPrecedence && (e = 1), _ = p.selectable()[e];
				}
			} else u && (_ = u);
			e && !p.isOpen && (p.open(), p.scrollToOption(_, "auto")), p.setActiveOption(_);
		} else p.clearActiveOption(), e && p.isOpen && p.close(!1);
	}
	selectable() {
		return this.dropdown_content.querySelectorAll("[data-selectable]");
	}
	addOption(e, t = !1) {
		let n = this;
		if (Array.isArray(e)) return n.addOptions(e, t), !1;
		let r = O(e[n.settings.valueField]);
		return r === null || n.options.hasOwnProperty(r) ? (n.updateOption(e[n.settings.valueField], e), !1) : (e.$order = e.$order || ++n.order, e.$id = n.inputId + "-opt-" + e.$order, n.options[r] = e, n.isDropdownContentStale = !0, t && (n.userOptions[r] = t, n.trigger("option_add", r, e)), r);
	}
	addOptions(e, t = !1) {
		L(e, (e) => {
			this.addOption(e, t);
		});
	}
	registerOption(e) {
		return this.addOption(e);
	}
	registerOptionGroup(e) {
		var t = O(e[this.settings.optgroupValueField]);
		return t === null ? !1 : (e.$order = e.$order || ++this.order, this.optgroups[t] = e, t);
	}
	addOptionGroup(e, t) {
		var n;
		t[this.settings.optgroupValueField] = e, (n = this.registerOptionGroup(t)) && this.trigger("optgroup_add", n, t);
	}
	removeOptionGroup(e) {
		this.optgroups.hasOwnProperty(e) && (delete this.optgroups[e], this.clearCache(), this.trigger("optgroup_remove", e));
	}
	clearOptionGroups() {
		this.optgroups = {}, this.clearCache(), this.trigger("optgroup_clear");
	}
	updateOption(e, t) {
		let n = this;
		var r, i;
		let a = O(e), o = O(t[n.settings.valueField]);
		if (a === null) return;
		let s = n.options[a];
		if (s == null) return;
		if (typeof o != "string") throw Error("Value must be set in option data");
		let c = n.getOption(a), l = n.getItem(a);
		if (t.$order = t.$order || s.$order, delete n.options[a], n.uncacheValue(o), n.options[o] = t, c) {
			if (n.dropdown_content.contains(c)) {
				let e = n._render("option", t);
				K(c, e), n.activeOption === c && n.setActiveOption(e);
			}
			c.remove();
		}
		l && (i = n.items.indexOf(a), i !== -1 && n.items.splice(i, 1, o), r = n._render("item", t), l.classList.contains("active") && H(r, "active"), K(l, r)), n.isDropdownContentStale = !0;
	}
	removeOption(e, t) {
		let n = this;
		e = k(e), n.uncacheValue(e), delete n.userOptions[e], delete n.options[e], n.isDropdownContentStale = !0, n.trigger("option_remove", e), n.removeItem(e, t);
	}
	clearOptions(e) {
		let t = (e || this.clearFilter).bind(this);
		this.loadedSearches = {}, this.userOptions = {}, this.clearCache();
		let n = {};
		L(this.options, (e, r) => {
			t(e, r) && (n[r] = e);
		}), this.options = this.sifter.items = n, this.isDropdownContentStale = !0, this.trigger("option_clear");
	}
	clearFilter(e, t) {
		return this.items.indexOf(t) >= 0;
	}
	getOption(e, t = !1) {
		let n = O(e);
		if (n === null) return null;
		let r = this.options[n];
		if (r != null) {
			if (r.$div) return r.$div;
			if (t) return this._render("option", r);
		}
		return null;
	}
	getAdjacent(e, t, n = "option") {
		var r = this, i;
		if (!e) return null;
		i = n == "item" ? r.controlChildren() : r.dropdown_content.querySelectorAll("[data-selectable]");
		for (let n = 0; n < i.length; n++) if (i[n] == e) return t > 0 ? i[n + 1] : i[n - 1];
		return null;
	}
	getItem(e) {
		if (typeof e == "object") return e;
		var t = O(e);
		return t === null ? null : this.control.querySelector(`[data-value="${F(t)}"]`);
	}
	addItems(e, t) {
		var n = this, r = Array.isArray(e) ? e : [e];
		r = r.filter((e) => n.items.indexOf(e) === -1);
		let i = r[r.length - 1];
		r.forEach((e) => {
			n.isPending = e !== i, n.addItem(e, t);
		});
	}
	addItem(e, t) {
		var n = t ? [] : ["change", "dropdown_close"];
		ge(this, n, () => {
			var n, r;
			let i = this, a = i.settings.mode, o = O(e);
			if (!(o && i.items.indexOf(o) !== -1 && (a === "single" && i.close(), a === "single" || !i.settings.duplicates)) && !(o === null || !i.options.hasOwnProperty(o)) && (a === "single" && i.clear(t), !(a === "multi" && i.isFull()))) {
				if (n = i._render("item", i.options[o]), i.control.contains(n) && (n = n.cloneNode(!0)), r = i.isFull(), i.items.splice(i.caretPos, 0, o), i.insertAtCaret(n), i.isSetup) {
					if (!i.isPending && i.settings.hideSelected) {
						let e = i.getOption(o), t = i.getAdjacent(e, 1);
						t && i.setActiveOption(t);
					}
					i.settings.clearAfterSelect && i.setTextboxValue(), !i.isPending && !i.settings.closeAfterSelect && i.refreshOptions(i.isFocused && a !== "single"), i.settings.closeAfterSelect != 0 && i.isFull() ? i.close() : i.isPending || i.positionDropdown(), i.trigger("item_add", o, n), i.isPending || i.updateOriginalInput({ silent: t });
				}
				(!i.isPending || !r && i.isFull()) && (i.inputState(), i.refreshState());
			}
		});
	}
	removeItem(e = null, t) {
		let n = this;
		if (e = n.getItem(e), !e) return;
		var r, i;
		let a = e.dataset.value;
		r = Ce(e), e.remove(), e.classList.contains("active") && (i = n.activeItems.indexOf(e), n.activeItems.splice(i, 1), U(e, "active")), n.items.splice(r, 1), n.isDropdownContentStale = !0, !n.settings.persist && n.userOptions.hasOwnProperty(a) && n.removeOption(a, t), r < n.caretPos && n.setCaret(n.caretPos - 1), n.updateOriginalInput({ silent: t }), n.refreshState(), n.positionDropdown(), n.trigger("item_remove", a, e);
	}
	createItem(e = null, t = () => {}) {
		arguments.length === 3 && (t = arguments[2]), typeof t != "function" && (t = () => {});
		var n = this, r = n.caretPos, i;
		if (e ||= n.inputValue(), !n.canCreate(e)) return O(e) && this.options[e] && n.addItem(e), t(), !1;
		n.lock();
		var a = !1, o = (e) => {
			if (n.unlock(), !e || typeof e != "object") return t();
			var i = O(e[n.settings.valueField]);
			if (typeof i != "string") return t();
			n.setTextboxValue(), n.addOption(e, !0), n.setCaret(r), n.addItem(i), t(e), a = !0;
		};
		return i = typeof n.settings.create == "function" ? n.settings.create.call(this, e, o) : {
			[n.settings.labelField]: e,
			[n.settings.valueField]: e
		}, a || o(i), !0;
	}
	refreshItems() {
		var e = this;
		e.isDropdownContentStale = !0, e.isSetup && e.addItems(e.items), e.updateOriginalInput(), e.refreshState();
	}
	refreshState() {
		let e = this;
		e.refreshValidityState();
		let t = e.isFull(), n = e.isLocked;
		e.wrapper.classList.toggle("rtl", e.rtl);
		let r = e.wrapper.classList;
		r.toggle("focus", e.isFocused), r.toggle("disabled", e.isDisabled), r.toggle("readonly", e.isReadOnly), r.toggle("required", e.isRequired), r.toggle("invalid", !e.isValid), r.toggle("locked", n), r.toggle("full", t), r.toggle("input-active", e.isFocused && !e.isInputHidden), r.toggle("dropdown-active", e.isOpen), r.toggle("has-options", Se(e.options)), r.toggle("has-items", e.items.length > 0);
	}
	refreshValidityState() {
		var e = this;
		e.input.validity && (e.isValid = e.input.validity.valid, e.isInvalid = !e.isValid);
	}
	isFull() {
		return this.settings.maxItems !== null && this.items.length >= this.settings.maxItems;
	}
	updateOriginalInput(e = {}) {
		let t = this;
		var n, r;
		let i = t.input.querySelector("option[value=\"\"]");
		if (t.is_select_tag) {
			let e = [], a = t.input.querySelectorAll("option:checked").length;
			function o(n, r, o) {
				return n ||= R("<option value=\"" + A(r) + "\">" + A(o) + "</option>"), n != i && t.input.append(n), e.push(n), (n != i || a > 0) && (n.selected = !0), n;
			}
			t.input.querySelectorAll("option:checked").forEach((e) => {
				e.selected = !1;
			}), t.items.length == 0 && t.settings.mode == "single" ? o(i, "", "") : t.items.forEach((i) => {
				n = t.options[i], r = n[t.settings.labelField] || "", e.includes(n.$option) ? o(t.input.querySelector(`option[value="${F(i)}"]:not(:checked)`), i, r) : n.$option = o(n.$option, i, r);
			});
		} else t.input.value = t.getValue();
		t.isSetup && (e.silent || t.trigger("change", t.getValue()));
	}
	open() {
		var e = this;
		e.isLocked || e.isOpen || e.settings.mode === "multi" && e.isFull() || (e.isOpen = !0, G(e.focus_node, { "aria-expanded": "true" }), e.refreshState(), V(e.dropdown, {
			visibility: "hidden",
			display: "block"
		}), e.positionDropdown(), V(e.dropdown, {
			visibility: "visible",
			display: "block"
		}), e.focus(), e.trigger("dropdown_open", e.dropdown));
	}
	close(e = !0) {
		var t = this, n = t.isOpen;
		e && (t.setTextboxValue(), t.settings.mode === "single" && t.items.length && t.inputState()), t.isOpen = !1, G(t.focus_node, { "aria-expanded": "false" }), V(t.dropdown, { display: "none" }), t.settings.hideSelected && t.clearActiveOption(), t.refreshState(), n && t.trigger("dropdown_close", t.dropdown);
	}
	positionDropdown() {
		if (this.settings.dropdownParent === "body") {
			var e = this.control, t = e.getBoundingClientRect(), n = e.offsetHeight + t.top + window.scrollY, r = t.left + window.scrollX;
			V(this.dropdown, {
				width: t.width + "px",
				top: n + "px",
				left: r + "px"
			});
		}
	}
	clear(e) {
		var t = this;
		t.items.length && (L(t.controlChildren(), (e) => {
			t.removeItem(e, !0);
		}), t.inputState(), e || t.updateOriginalInput(), t.trigger("clear"));
	}
	insertAtCaret(e) {
		let t = this, n = t.caretPos, r = t.control;
		r.insertBefore(e, r.children[n] || null), t.setCaret(n + 1);
	}
	deleteSelection(e) {
		var t, n, r, i, a = this;
		t = e && e.keyCode === 8 ? -1 : 1, n = _e(a.control_input);
		let o = [];
		if (a.activeItems.length) i = xe(a.activeItems, t), r = Ce(i), t > 0 && r++, L(a.activeItems, (e) => o.push(e));
		else if ((a.isFocused || a.settings.mode === "single") && a.items.length) {
			let e = a.controlChildren(), r;
			t < 0 && n.start === 0 && n.length === 0 ? r = e[a.caretPos - 1] : t > 0 && n.start === a.inputValue().length && (r = e[a.caretPos]), r !== void 0 && o.push(r);
		}
		if (!a.shouldDelete(o, e)) return !1;
		for (j(e, !0), r !== void 0 && a.setCaret(r); o.length;) a.removeItem(o.pop());
		return a.inputState(), a.positionDropdown(), a.refreshOptions(!1), !0;
	}
	shouldDelete(e, t) {
		let n = e.map((e) => e.dataset.value);
		return !(!n.length || typeof this.settings.onDelete == "function" && this.settings.onDelete.call(this, n, t) === !1);
	}
	advanceSelection(e, t) {
		var n, r, i = this;
		i.rtl && (e *= -1), !i.inputValue().length && (N(q, t) || N("shiftKey", t) ? (n = i.getLastActive(e), r = n ? n.classList.contains("active") ? i.getAdjacent(n, e, "item") : n : e > 0 ? i.control_input.nextElementSibling : i.control_input.previousElementSibling, r && (r.classList.contains("active") && i.removeActiveItem(n), i.setActiveItemClass(r))) : i.moveCaret(e));
	}
	moveCaret(e) {}
	getLastActive(e) {
		let t = this.control.querySelector(".last-active");
		if (t) return t;
		var n = this.control.querySelectorAll(".active");
		if (n) return xe(n, e);
	}
	setCaret(e) {
		this.caretPos = this.items.length;
	}
	controlChildren() {
		return Array.from(this.control.querySelectorAll("[data-ts-item]"));
	}
	lock() {
		this.setLocked(!0);
	}
	unlock() {
		this.setLocked(!1);
	}
	setLocked(e = this.isReadOnly || this.isDisabled) {
		this.isLocked = e, this.refreshState();
	}
	disable() {
		this.setDisabled(!0), this.close();
	}
	enable() {
		this.setDisabled(!1);
	}
	setDisabled(e) {
		this.focus_node.tabIndex = e ? -1 : this.tabIndex, this.isDisabled = e, this.input.disabled = e, this.control_input.disabled = e, this.setLocked();
	}
	setReadOnly(e) {
		this.isReadOnly = e, this.input.readOnly = e, this.control_input.readOnly = e, this.setLocked();
	}
	destroy() {
		var e = this, t = e.revertSettings;
		e.trigger("destroy"), e.off(), e.wrapper.remove(), e.dropdown.remove(), e.input.innerHTML = t.innerHTML, e.input.tabIndex = t.tabIndex, U(e.input, "tomselected", "ts-hidden-accessible"), e._destroy(), delete e.input.tomselect;
	}
	render(e, t) {
		var n, r;
		let i = this;
		if (typeof this.settings.render[e] != "function" || (r = i.settings.render[e].call(this, t, A), !r)) return null;
		if (r = R(r), e === "option" || e === "option_create" ? t[i.settings.disabledField] ? G(r, { "aria-disabled": "true" }) : G(r, { "data-selectable": "" }) : e === "optgroup" && (n = t.group[i.settings.optgroupValueField], G(r, { "data-group": n }), t.group[i.settings.disabledField] && G(r, { "data-disabled": "" })), e === "option" || e === "item") {
			let n = k(t[i.settings.valueField]);
			G(r, { "data-value": n }), e === "item" ? (H(r, i.settings.itemClass), G(r, { "data-ts-item": "" })) : (H(r, i.settings.optionClass), G(r, {
				role: "option",
				id: t.$id
			}), t.$div = r, i.options[n] = t);
		}
		return r;
	}
	_render(e, t) {
		let n = this.render(e, t);
		if (n == null) throw "HTMLElement expected";
		return n;
	}
	clearCache() {
		L(this.options, (e) => {
			e.$div && (e.$div.remove(), delete e.$div);
		});
	}
	uncacheValue(e) {
		let t = this.getOption(e);
		t && t.remove();
	}
	canCreate(e) {
		return this.settings.create && e.length > 0 && this.settings.createFilter.call(this, e);
	}
	hook(e, t, n) {
		var r = this, i = r[t];
		r[t] = function() {
			var t, a;
			return e === "after" && (t = i.apply(r, arguments)), a = n.apply(r, arguments), e === "instead" ? a : (e === "before" && (t = i.apply(r, arguments)), t);
		};
	}
}, Oe = (e, t, n, r) => {
	e.addEventListener(t, n, r);
};
function ke() {
	Oe(this.input, "change", () => {
		this.sync();
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/checkbox_options/plugin.js
var Ae = (e) => e == null ? null : je(e), je = (e) => typeof e == "boolean" ? e ? "1" : "0" : e + "", Me = (e, t = !1) => {
	e && (e.preventDefault(), t && e.stopPropagation());
}, Ne = (e) => {
	if (e.jquery) return e[0];
	if (e instanceof HTMLElement) return e;
	if (Pe(e)) {
		var t = document.createElement("template");
		return t.innerHTML = e.trim(), t.content.firstChild;
	}
	return document.querySelector(e);
}, Pe = (e) => typeof e == "string" && e.indexOf("<") > -1;
function Fe(e) {
	var t = this, n = t.onOptionSelect;
	t.settings.hideSelected = !1;
	let r = Object.assign({
		className: "tomselect-checkbox",
		checkedClassNames: void 0,
		uncheckedClassNames: void 0
	}, e);
	var i = function(e, t) {
		t ? (e.checked = !0, r.uncheckedClassNames && e.classList.remove(...r.uncheckedClassNames), r.checkedClassNames && e.classList.add(...r.checkedClassNames)) : (e.checked = !1, r.checkedClassNames && e.classList.remove(...r.checkedClassNames), r.uncheckedClassNames && e.classList.add(...r.uncheckedClassNames));
	}, a = function(e) {
		setTimeout(() => {
			var t = e.querySelector("input." + r.className);
			t instanceof HTMLInputElement && i(t, e.classList.contains("selected"));
		}, 1);
	};
	t.hook("after", "setupTemplates", () => {
		var e = t.settings.render.option;
		t.settings.render.option = (n, a) => {
			var o = Ne(e.call(t, n, a)), s = document.createElement("input");
			r.className && s.classList.add(r.className), s.addEventListener("click", function(e) {
				Me(e);
			}), s.type = "checkbox";
			let c = Ae(n[t.settings.valueField]);
			return i(s, !!(c && t.items.indexOf(c) > -1)), o.prepend(s), o;
		};
	}), t.on("item_remove", (e) => {
		var n = t.getOption(e);
		n && (n.classList.remove("selected"), a(n));
	}), t.on("item_add", (e) => {
		var n = t.getOption(e);
		n && a(n);
	}), t.hook("instead", "onOptionSelect", (e, r) => {
		if (r.classList.contains("selected")) {
			r.classList.remove("selected"), t.removeItem(r.dataset.value), t.refreshOptions(), Me(e, !0);
			return;
		}
		n.call(t, e, r), a(r);
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/clear_button/plugin.js
var Ie = (e) => {
	if (e.jquery) return e[0];
	if (e instanceof HTMLElement) return e;
	if (Le(e)) {
		var t = document.createElement("template");
		return t.innerHTML = e.trim(), t.content.firstChild;
	}
	return document.querySelector(e);
}, Le = (e) => typeof e == "string" && e.indexOf("<") > -1;
function Re(e) {
	let t = this, n = Object.assign({
		className: "clear-button",
		title: "Clear All",
		role: "button",
		tabindex: 0,
		html: (e) => `<div class="${e.className}" title="${e.title}" role="${e.role}" tabindex="${e.tabindex}">&times;</div>`
	}, e);
	t.on("initialize", () => {
		var e = Ie(n.html(n));
		e.addEventListener("click", (e) => {
			t.isLocked || (t.clear(), t.settings.mode === "single" && t.settings.allowEmptyOption && t.addItem(""), t.refreshOptions(!1), e.preventDefault(), e.stopPropagation());
		}), t.control.appendChild(e);
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/drag_drop/plugin.js
var ze = (e, t = !1) => {
	e && (e.preventDefault(), t && e.stopPropagation());
}, X = (e, t, n, r) => {
	e.addEventListener(t, n, r);
}, Be = (e, t) => {
	if (Array.isArray(e)) e.forEach(t);
	else for (var n in e) e.hasOwnProperty(n) && t(e[n], n);
}, Ve = (e) => {
	if (e.jquery) return e[0];
	if (e instanceof HTMLElement) return e;
	if (He(e)) {
		var t = document.createElement("template");
		return t.innerHTML = e.trim(), t.content.firstChild;
	}
	return document.querySelector(e);
}, He = (e) => typeof e == "string" && e.indexOf("<") > -1, Ue = (e, t) => {
	Be(t, (t, n) => {
		t == null ? e.removeAttribute(n) : e.setAttribute(n, "" + t);
	});
}, We = (e, t) => {
	var n;
	(n = e.parentNode) == null || n.insertBefore(t, e.nextSibling);
}, Ge = (e, t) => {
	var n;
	(n = e.parentNode) == null || n.insertBefore(t, e);
}, Ke = (e, t) => {
	do
		if (t = t?.previousElementSibling, e == t) return !0;
	while (t && t.previousElementSibling);
	return !1;
};
function qe() {
	var e = this;
	if (e.settings.mode !== "multi") return;
	var t = e.lock, n = e.unlock;
	let r = !0, i;
	e.hook("after", "setupTemplates", () => {
		var t = e.settings.render.item;
		e.settings.render.item = (n, a) => {
			let o = Ve(t.call(e, n, a));
			Ue(o, { draggable: "true" });
			let s = (e) => {
				r || ze(e), e.stopPropagation();
			}, c = (e) => {
				i = o, setTimeout(() => {
					o.classList.add("ts-dragging");
				}, 0);
			}, l = (e) => {
				e.preventDefault(), o.classList.add("ts-drag-over"), d(o, i);
			}, u = () => {
				o.classList.remove("ts-drag-over");
			}, d = (e, t) => {
				t !== void 0 && (Ke(t, o) ? We(e, t) : Ge(e, t));
			};
			return X(o, "mousedown", s), X(o, "dragstart", c), X(o, "dragenter", l), X(o, "dragover", l), X(o, "dragleave", u), X(o, "dragend", () => {
				var t;
				document.querySelectorAll(".ts-drag-over").forEach((e) => e.classList.remove("ts-drag-over")), (t = i) == null || t.classList.remove("ts-dragging"), i = void 0;
				var n = [];
				e.control.querySelectorAll("[data-value]").forEach((e) => {
					if (e.dataset.value) {
						let t = e.dataset.value;
						t && n.push(t);
					}
				}), e.setValue(n);
			}), o;
		};
	}), e.hook("instead", "lock", () => (r = !1, t.call(e))), e.hook("instead", "unlock", () => (r = !0, n.call(e)));
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/dropdown_header/plugin.js
var Je = (e, t = !1) => {
	e && (e.preventDefault(), t && e.stopPropagation());
}, Ye = (e) => {
	if (e.jquery) return e[0];
	if (e instanceof HTMLElement) return e;
	if (Xe(e)) {
		var t = document.createElement("template");
		return t.innerHTML = e.trim(), t.content.firstChild;
	}
	return document.querySelector(e);
}, Xe = (e) => typeof e == "string" && e.indexOf("<") > -1;
function Ze(e) {
	let t = this, n = Object.assign({
		title: "Untitled",
		headerClass: "dropdown-header",
		titleRowClass: "dropdown-header-title",
		labelClass: "dropdown-header-label",
		closeClass: "dropdown-header-close",
		html: (e) => "<div class=\"" + e.headerClass + "\"><div class=\"" + e.titleRowClass + "\"><span class=\"" + e.labelClass + "\">" + e.title + "</span><a class=\"" + e.closeClass + "\">&times;</a></div></div>"
	}, e);
	t.on("initialize", () => {
		var e = Ye(n.html(n)), r = e.querySelector("." + n.closeClass);
		r && r.addEventListener("click", (e) => {
			Je(e, !0), t.close();
		}), t.dropdown.insertBefore(e, t.dropdown.firstChild);
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/caret_position/plugin.js
var Qe = (e, t) => {
	if (Array.isArray(e)) e.forEach(t);
	else for (var n in e) e.hasOwnProperty(n) && t(e[n], n);
}, $e = (e, ...t) => {
	var n = et(t);
	e = tt(e), e.map((e) => {
		n.map((t) => {
			e.classList.remove(t);
		});
	});
}, et = (e) => {
	var t = [];
	return Qe(e, (e) => {
		typeof e == "string" && (e = e.trim().split(/[\t\n\f\r\s]/)), Array.isArray(e) && (t = t.concat(e));
	}), t.filter(Boolean);
}, tt = (e) => (Array.isArray(e) || (e = [e]), e), nt = (e, t) => {
	if (!e) return -1;
	t ||= e.nodeName;
	for (var n = 0; e = e.previousElementSibling;) e.matches(t) && n++;
	return n;
};
function rt() {
	var e = this;
	e.hook("instead", "setCaret", (t) => {
		e.settings.mode === "single" || !e.control.contains(e.control_input) ? t = e.items.length : (t = Math.max(0, Math.min(e.items.length, t)), t != e.caretPos && !e.isPending && e.controlChildren().forEach((n, r) => {
			r < t ? e.control_input.insertAdjacentElement("beforebegin", n) : e.control.appendChild(n);
		})), e.caretPos = t;
	}), e.hook("instead", "moveCaret", (t) => {
		if (!e.isFocused) return;
		let n = e.getLastActive(t);
		if (n) {
			let r = nt(n);
			e.setCaret(t > 0 ? r + 1 : r), e.setActiveItem(), $e(n, "last-active");
		} else e.setCaret(e.caretPos + t);
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/dropdown_input/plugin.js
var it = 27, at = 9, ot = (e, t = !1) => {
	e && (e.preventDefault(), t && e.stopPropagation());
}, st = (e, t, n, r) => {
	e.addEventListener(t, n, r);
}, ct = (e, t) => {
	if (Array.isArray(e)) e.forEach(t);
	else for (var n in e) e.hasOwnProperty(n) && t(e[n], n);
}, lt = (e) => {
	if (e.jquery) return e[0];
	if (e instanceof HTMLElement) return e;
	if (ut(e)) {
		var t = document.createElement("template");
		return t.innerHTML = e.trim(), t.content.firstChild;
	}
	return document.querySelector(e);
}, ut = (e) => typeof e == "string" && e.indexOf("<") > -1, dt = (e, ...t) => {
	var n = ft(t);
	e = pt(e), e.map((e) => {
		n.map((t) => {
			e.classList.add(t);
		});
	});
}, ft = (e) => {
	var t = [];
	return ct(e, (e) => {
		typeof e == "string" && (e = e.trim().split(/[\t\n\f\r\s]/)), Array.isArray(e) && (t = t.concat(e));
	}), t.filter(Boolean);
}, pt = (e) => (Array.isArray(e) || (e = [e]), e);
function mt() {
	let e = this;
	e.settings.shouldOpen = !0, e.hook("before", "setup", () => {
		e.focus_node = e.control, dt(e.control_input, "dropdown-input");
		let t = lt("<div class=\"dropdown-input-wrap\">");
		t.append(e.control_input), e.dropdown.insertBefore(t, e.dropdown.firstChild);
		let n = lt("<input class=\"items-placeholder\" tabindex=\"-1\" />");
		n.placeholder = e.settings.placeholder || "", e.control.append(n);
		let r = e.input?.getAttribute("aria-label");
		r && n.setAttribute("aria-label", r);
	}), e.on("initialize", () => {
		e.control_input.addEventListener("keydown", (t) => {
			switch (t.keyCode) {
				case it:
					e.isOpen && (ot(t, !0), e.close()), e.clearActiveItems();
					return;
				case at:
					e.focus_node.tabIndex = -1;
					break;
			}
			return e.onKeyDown.call(e, t);
		}), e.on("blur", () => {
			e.focus_node.tabIndex = e.isDisabled ? -1 : e.tabIndex;
		}), e.on("dropdown_open", () => {
			e.control_input.focus();
		});
		let t = e.onBlur;
		e.hook("instead", "onBlur", (n) => {
			if (!(n && n.relatedTarget == e.control_input)) return t.call(e);
		}), st(e.control_input, "blur", () => e.onBlur()), e.hook("before", "close", () => {
			e.isOpen && e.focus_node.focus({ preventScroll: !0 });
		});
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/input_autogrow/plugin.js
var Z = (e, t, n, r) => {
	e.addEventListener(t, n, r);
};
function ht() {
	var e = this;
	e.on("initialize", () => {
		var t = document.createElement("span"), n = e.control_input;
		t.style.cssText = "position:absolute; top:-99999px; left:-99999px; width:auto; padding:0; white-space:pre; ", e.wrapper.appendChild(t);
		for (let e of [
			"letterSpacing",
			"fontSize",
			"fontFamily",
			"fontWeight",
			"textTransform"
		]) t.style[e] = n.style[e];
		var r = () => {
			t.textContent = n.value, n.style.width = t.clientWidth + "px";
		};
		r(), e.on("update item_add item_remove", r), Z(n, "input", r), Z(n, "keyup", r), Z(n, "blur", r), Z(n, "update", r);
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/no_backspace_delete/plugin.js
function gt() {
	var e = this, t = e.deleteSelection;
	this.hook("instead", "deleteSelection", (n) => e.activeItems.length ? t.call(e, n) : !1);
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/no_active_items/plugin.js
function _t() {
	this.hook("instead", "setActiveItem", () => {}), this.hook("instead", "selectAll", () => {});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/optgroup_columns/plugin.js
var vt = 37, yt = 39, bt = (e, t, n) => {
	for (; e && e.matches;) {
		if (e.matches(t)) return e;
		e = e.parentNode;
	}
}, xt = (e, t) => {
	if (!e) return -1;
	t ||= e.nodeName;
	for (var n = 0; e = e.previousElementSibling;) e.matches(t) && n++;
	return n;
};
function St() {
	var e = this, t = e.onKeyDown;
	e.hook("instead", "onKeyDown", (n) => {
		var r, i, a, o;
		if (!e.isOpen || !(n.keyCode === vt || n.keyCode === yt)) return t.call(e, n);
		e.ignoreHover = !0, o = bt(e.activeOption, "[data-group]"), r = xt(e.activeOption, "[data-selectable]"), o && (o = n.keyCode === vt ? o.previousSibling : o.nextSibling, o && (a = o.querySelectorAll("[data-selectable]"), i = a[Math.min(a.length - 1, r)], i && e.setActiveOption(i)));
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/remove_button/plugin.js
var Ct = (e) => (e + "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"), wt = (e, t = !1) => {
	e && (e.preventDefault(), t && e.stopPropagation());
}, Tt = (e, t, n, r) => {
	e.addEventListener(t, n, r);
}, Et = (e) => {
	if (e.jquery) return e[0];
	if (e instanceof HTMLElement) return e;
	if (Dt(e)) {
		var t = document.createElement("template");
		return t.innerHTML = e.trim(), t.content.firstChild;
	}
	return document.querySelector(e);
}, Dt = (e) => typeof e == "string" && e.indexOf("<") > -1;
function Ot(e) {
	let t = Object.assign({
		label: "&times;",
		title: "Remove",
		className: "remove",
		append: !0
	}, e);
	var n = this;
	if (t.append) {
		var r = "<a href=\"javascript:void(0)\" class=\"" + t.className + "\" tabindex=\"-1\" title=\"" + Ct(t.title) + "\">" + t.label + "</a>";
		n.hook("after", "setupTemplates", () => {
			var e = n.settings.render.item;
			n.settings.render.item = (t, i) => {
				var a = Et(e.call(n, t, i)), o = Et(r);
				return a.appendChild(o), Tt(o, "mousedown", (e) => {
					wt(e, !0);
				}), Tt(o, "click", (e) => {
					n.isLocked || (wt(e, !0), !n.isLocked && n.shouldDelete([a], e) && (n.removeItem(a), n.refreshOptions(!1), n.inputState()));
				}), a;
			};
		});
	}
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/restore_on_backspace/plugin.js
function kt(e) {
	let t = this, n = Object.assign({ text: (e) => e[t.settings.labelField] }, e);
	t.on("item_remove", function(e) {
		if (t.isFocused && t.control_input.value.trim() === "") {
			var r = t.options[e];
			r && t.setTextboxValue(n.text.call(t, r));
		}
	});
}
//#endregion
//#region ../../node_modules/tom-select/dist/esm/plugins/virtual_scroll/plugin.js
var At = (e, t) => {
	if (Array.isArray(e)) e.forEach(t);
	else for (var n in e) e.hasOwnProperty(n) && t(e[n], n);
}, jt = (e, ...t) => {
	var n = Mt(t);
	e = Nt(e), e.map((e) => {
		n.map((t) => {
			e.classList.add(t);
		});
	});
}, Mt = (e) => {
	var t = [];
	return At(e, (e) => {
		typeof e == "string" && (e = e.trim().split(/[\t\n\f\r\s]/)), Array.isArray(e) && (t = t.concat(e));
	}), t.filter(Boolean);
}, Nt = (e) => (Array.isArray(e) || (e = [e]), e);
function Pt() {
	let e = this, t = e.canLoad, n = e.clearActiveOption, r = e.loadCallback;
	var i = {}, a, o = !1, s, c = [], l = !1, u;
	if (e.settings.shouldLoadMore || (e.settings.shouldLoadMore = () => {
		if (a.clientHeight / (a.scrollHeight - a.scrollTop) > .9) return !0;
		if (e.activeOption) {
			var t = e.selectable();
			if (Array.from(t).indexOf(e.activeOption) >= t.length - 2) return !0;
		}
		return !1;
	}), !e.settings.firstUrl) throw "virtual_scroll plugin requires a firstUrl() method";
	e.settings.sortField = [{ field: "$order" }, { field: "$score" }];
	let d = (t) => typeof e.settings.maxOptions == "number" && a.children.length >= e.settings.maxOptions ? !1 : !!(t in i && i[t]), f = (t, n) => e.items.indexOf(n) >= 0 || c.indexOf(n) >= 0;
	e.setNextUrl = (e, t) => {
		i[e] = t;
	}, e.getUrl = (t) => {
		if (t in i) {
			let e = i[t];
			return i[t] = !1, e;
		}
		return e.clearPagination(), e.settings.firstUrl.call(e, t);
	}, e.clearPagination = () => {
		i = {};
	}, e.hook("instead", "clearActiveOption", () => {
		if (!o) return n.call(e);
	}), e.hook("instead", "canLoad", (n) => n in i ? d(n) : t.call(e, n)), e.hook("instead", "loadCallback", (t, n) => {
		if (!o) e.clearOptions(f);
		else if (s) {
			let n = t[0];
			n !== void 0 && (s.dataset.value = n[e.settings.valueField]);
		}
		r.call(e, t, n), !o && !l && (l = !0, e.lastValue === "" && (c = Object.keys(e.options), u = i[""])), o = !1;
	}), e.hook("before", "refreshOptions", () => {
		e.activeOption && e.activeOption.getAttribute("role") !== "option" && e.setActiveOption(e.activeOption.previousElementSibling);
	}), e.hook("after", "refreshOptions", () => {
		let t = e.lastValue;
		var n;
		d(t) ? (n = e.render("loading_more", { query: t }), n && (n.setAttribute("data-selectable", ""), s = n)) : t in i && !a.querySelector(".no-results") && (n = e.render("no_more_results", { query: t })), n && (jt(n, e.settings.optionClass), a.append(n));
	});
	let p = () => {
		l && (e.clearOptions(f), u && (i[""] = u));
	};
	e.on("type", (t) => {
		t === "" && (p(), e.refreshOptions(!1));
	}), e.on("dropdown_close", p), e.on("initialize", () => {
		c = Object.keys(e.options), a = e.dropdown_content, e.settings.render = Object.assign({}, {
			loading_more: () => "<div class=\"loading-more-results\">Loading more results ... </div>",
			no_more_results: () => "<div class=\"no-more-results\">No more results</div>"
		}, e.settings.render), a.addEventListener("scroll", () => {
			e.settings.shouldLoadMore.call(e) && d(e.lastValue) && (o || (o = !0, e.load.call(e, e.lastValue)));
		});
	});
}
Y.define("change_listener", ke), Y.define("checkbox_options", Fe), Y.define("clear_button", Re), Y.define("drag_drop", qe), Y.define("dropdown_header", Ze), Y.define("caret_position", rt), Y.define("dropdown_input", mt), Y.define("input_autogrow", ht), Y.define("no_backspace_delete", gt), Y.define("no_active_items", _t), Y.define("optgroup_columns", St), Y.define("remove_button", Ot), Y.define("restore_on_backspace", kt), Y.define("virtual_scroll", Pt);
var Ft = Y, It = ".formie-field .ts-wrapper.formie-combobox{width:100%;min-height:0;box-shadow:none;background:0 0;border:0;padding:0;display:block;position:relative}.formie-field select[data-formie-combobox-input].tomselected,.formie-field select[data-formie-combobox-input].ts-hidden-accessible{display:none!important}.formie-field .ts-wrapper.formie-combobox .ts-control{box-sizing:border-box;z-index:1;border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-sm);background-color:var(--formie-color-surface);width:100%;min-height:var(--formie-control-height);padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-control-padding-x);box-shadow:none;font-size:var(--formie-control-font-size);line-height:var(--formie-line-height-tight);color:var(--formie-color-text);align-items:center;gap:var(--formie-space-1);background-image:none;flex-wrap:wrap;transition:border-color .15s,box-shadow .15s,background-color .15s;display:flex;position:relative;overflow:hidden}.formie-field .ts-wrapper.formie-combobox.single .ts-control{--ts-pr-min:var(--formie-control-padding-x);--ts-pr-caret:calc(var(--formie-select-indicator-size) + var(--formie-space-1));background-image:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M7 7l3-3 3 3m0 6l-3 3-3-3' stroke='%239CA3AF' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E\");background-position:right var(--formie-space-2) center;background-repeat:no-repeat;background-size:var(--formie-select-indicator-size) var(--formie-select-indicator-size);padding-right:calc(var(--formie-control-padding-x) + var(--formie-select-indicator-size) + var(--formie-space-2))!important}.formie-field .ts-wrapper.formie-combobox.single .ts-control:after{display:none}.formie-field .ts-wrapper.formie-combobox.multi .ts-control{padding:calc(var(--formie-control-padding-y) - 2px) var(--formie-control-padding-x) calc(var(--formie-control-padding-y) - 4px);--ts-pr-min:var(--formie-control-padding-x)}.formie-field .ts-wrapper.formie-combobox.multi.has-items .ts-control{padding-left:var(--formie-control-padding-x)}.formie-field .ts-wrapper.formie-combobox .ts-control>input{min-width:7rem;max-width:100%;color:var(--formie-color-text);font:inherit;font-size:var(--formie-control-font-size);line-height:inherit;flex:auto;min-height:0!important;max-height:none!important;box-shadow:none!important;background:0 0!important;border:0!important;margin:0!important;padding:0!important}.formie-field .ts-wrapper.formie-combobox .ts-control>input:focus{outline:none!important}.formie-field .ts-wrapper.formie-combobox.has-items .ts-control>input{margin:0 var(--formie-space-1)!important}.formie-field .ts-wrapper.formie-combobox.input-hidden .ts-control>input{opacity:0;position:absolute;left:-10000px}.formie-field .ts-wrapper.formie-combobox.single .ts-control,.formie-field .ts-wrapper.formie-combobox.single .ts-control>input{cursor:pointer}.formie-field .ts-wrapper.formie-combobox.single.input-active .ts-control,.formie-field .ts-wrapper.formie-combobox.single.input-active .ts-control>input{cursor:text}.formie-field .ts-wrapper.formie-combobox .ts-control .items-placeholder{color:var(--formie-color-text-muted);font:inherit;font-size:var(--formie-control-font-size)}.formie-field .ts-wrapper.formie-combobox .ts-control>input::placeholder{color:var(--formie-color-text-muted)}.formie-field .ts-wrapper.formie-combobox.focus .ts-control,.formie-field .ts-wrapper.formie-combobox .ts-control:focus-within,.formie-field .ts-wrapper.formie-combobox.dropdown-active .ts-control{border-color:var(--formie-color-focus-ring);box-shadow:var(--formie-shadow-focus);border-radius:var(--formie-radius-sm);outline:0}.formie-field-has-error .ts-wrapper.formie-combobox .ts-control{border-color:var(--formie-color-danger)}.formie-field-has-error .ts-wrapper.formie-combobox.focus .ts-control,.formie-field-has-error .ts-wrapper.formie-combobox .ts-control:focus-within,.formie-field-has-error .ts-wrapper.formie-combobox.dropdown-active .ts-control{border-color:var(--formie-color-danger);box-shadow:var(--formie-shadow-danger-focus)}.formie-field .ts-wrapper.formie-combobox.multi .ts-control>.item,.formie-field .ts-wrapper.formie-combobox.multi .ts-control [data-value]{align-items:center;gap:var(--formie-space-1);margin:0 var(--formie-space-1) var(--formie-space-1) 0;padding:0 var(--formie-space-2);background:var(--formie-color-surface-muted,#0f172a0f);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);box-shadow:none;color:var(--formie-color-text);text-shadow:none;line-height:calc(var(--formie-control-height) - var(--formie-space-3));background-image:none;display:inline-flex}.formie-field .ts-wrapper.formie-combobox.multi .ts-control>.item.active,.formie-field .ts-wrapper.formie-combobox.multi .ts-control [data-value].active{background:var(--formie-color-surface-muted,#0f172a0f);color:var(--formie-color-text)}.formie-field .ts-wrapper.formie-combobox.plugin-remove_button .item{align-items:center;gap:var(--formie-space-1);display:inline-flex}.formie-field .ts-wrapper.formie-combobox.plugin-remove_button:not(.rtl) .item{padding-right:var(--formie-space-2)!important}.formie-field .ts-wrapper.formie-combobox.plugin-remove_button .item .remove{box-shadow:none;color:var(--formie-color-text-muted);cursor:pointer;background:0 0;border:0;justify-content:center;align-items:center;margin:0;padding:0;font-size:1.125em;line-height:1;text-decoration:none;display:inline-flex}.formie-field .ts-wrapper.formie-combobox.plugin-remove_button .item .remove:hover,.formie-field .ts-wrapper.formie-combobox.plugin-remove_button .item .remove:focus{color:var(--formie-color-text);background:0 0;text-decoration:none}.formie-field .ts-wrapper.formie-combobox .ts-dropdown{width:100%;z-index:var(--formie-z-popover,30);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);box-shadow:var(--formie-shadow-popover,0 8px 24px #0f172a1f);margin-top:var(--formie-space-1);box-sizing:border-box;position:absolute;top:100%;left:0}.formie-field .ts-wrapper.formie-combobox .ts-dropdown-content{scroll-behavior:smooth;max-height:200px;overflow:hidden auto}.formie-field .ts-wrapper.formie-combobox .ts-dropdown .option,.formie-field .ts-wrapper.formie-combobox .ts-dropdown .optgroup-header,.formie-field .ts-wrapper.formie-combobox .ts-dropdown .no-results{color:var(--formie-color-text);padding:var(--formie-space-2) var(--formie-control-padding-x)}.formie-field .ts-wrapper.formie-combobox .ts-dropdown [data-selectable]{cursor:pointer}.formie-field .ts-wrapper.formie-combobox .ts-dropdown .option.active,.formie-field .ts-wrapper.formie-combobox .ts-dropdown .option:hover,.formie-field .ts-wrapper.formie-combobox .ts-dropdown .active{background:var(--formie-color-surface-muted,#0f172a0d);color:var(--formie-color-text)}.formie-field .ts-wrapper.formie-combobox .ts-dropdown .highlight{background:#3b82f62e;border-radius:2px}.formie-field .ts-wrapper.formie-combobox.disabled .ts-control{opacity:.6;background-color:var(--formie-color-surface-muted,#0f172a0a);cursor:not-allowed}", Lt = "select[data-formie-combobox-input]", Q = "combobox", $ = e("fields", "combobox");
t(Q, [It]);
var Rt = [
	"formie-select",
	"formie-dropdown-input",
	"formie-input-error"
];
function zt(e) {
	let t = [];
	return Rt.forEach((n) => {
		e.classList.contains(n) && (e.classList.remove(n), t.push(n));
	}), t;
}
function Bt(e, t) {
	t.forEach((t) => {
		e.classList.add(t);
	});
}
function Vt(e) {
	Rt.forEach((t) => {
		e.classList.remove(t);
	});
}
function Ht(e, t) {
	return t?.trim() || e.querySelector("option[value=\"\"]")?.textContent?.trim() || null;
}
function Ut(e) {
	e.options[""] && e.removeOption("", !0);
}
function Wt(e, t = {}) {
	e._formieTomSelect?.destroy();
	let n = t.multiple === !0, i = zt(e), a = Ht(e, t.placeholder), o = {
		create: !1,
		maxItems: n ? null : 1,
		plugins: n ? ["remove_button"] : [],
		hideSelected: n ? !0 : null,
		clearAfterSelect: n,
		closeAfterSelect: !n,
		allowEmptyOption: !n,
		openOnFocus: !0,
		diacritics: !0,
		copyClassesToDropdown: !1,
		wrapperClass: "ts-wrapper formie-combobox",
		onChange: () => {
			e.dispatchEvent(new Event("input", { bubbles: !0 })), e.dispatchEvent(new Event("change", { bubbles: !0 }));
		}
	};
	a && (o.placeholder = a), r(e, Q, "before-init", {
		select: e,
		options: o
	});
	let s = new Ft(e, o);
	return Ut(s), Vt(s.wrapper), s.dropdown && Vt(s.dropdown), e.style.display = "none", e._formieTomSelect = s, $.log("Initialized.", {
		inputName: e.name,
		multiple: n
	}), r(e, Q, "after-init", {
		combobox: s,
		options: o
	}), () => {
		s.destroy(), e.style.removeProperty("display"), Bt(e, i), delete e._formieTomSelect, $.log("Destroyed.", { inputName: e.name });
	};
}
var Gt = {
	id: Q,
	kind: "field",
	match: (e) => !!e.target.querySelector(Lt),
	setup: async (e) => {
		let t = e.options || {}, r = n(e), i = r.map((e) => {
			let n = e.querySelector(Lt);
			return n instanceof HTMLSelectElement ? Wt(n, t) : ($.warn("Field missing combobox select; skipping."), () => {});
		});
		return $.log("Module setup.", { fieldCount: r.length }), await e.emit("formie:module:combobox:init", { count: i.length }), { destroy: () => {
			i.forEach((e) => {
				e();
			}), $.log("Module destroy.", { fieldCount: r.length }), e.emit("formie:module:combobox:destroy", {});
		} };
	}
};
//#endregion
export { Gt as comboboxModule, Wt as initFormieCombobox };
