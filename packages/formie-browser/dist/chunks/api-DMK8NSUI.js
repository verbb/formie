import { a as e } from "./event-names-BCI2FLD8.js";
import { t } from "./debug-BV0DvdHx.js";
import { t as n } from "./theme-classes-Tv7q7ToE.js";
import { i as r } from "./i18n-BY1ds1BL.js";
import { n as i, t as a } from "./async-nPFRNQ06.js";
//#region src/js/modules/captchas/constants.ts
var o = 2e3, s = 5e3, c = 5e3, l = 12e4;
//#endregion
//#region src/js/modules/captchas/utils.ts
function u(e) {
	let t = String(e || "asyncDefer").toLowerCase();
	return {
		async: t.includes("async"),
		defer: t.includes("defer")
	};
}
function d(e, t) {
	let n = Array.from(e.querySelectorAll(`input[name="${t}"], textarea[name="${t}"]`));
	for (let e of n) {
		let t = String(e.value || "").trim();
		if (t !== "") return t;
	}
	return "";
}
function f(e, t) {
	return t.some((t) => d(e, t) !== "");
}
function p(e, t) {
	t.forEach((t) => {
		Array.from(e.querySelectorAll(`input[name="${t}"], textarea[name="${t}"]`)).forEach((e) => {
			e.value = "";
		});
	});
}
function m(e, t, { value: n = "", container: r } = {}) {
	let i = e.querySelector(`input[name="${t}"]`);
	return i || (i = document.createElement("input"), i.type = "hidden", i.name = t, (r || (e instanceof HTMLElement ? e : null))?.appendChild(i)), i.value = n, i;
}
async function h(e, t, n) {
	if (f(e, t)) return !0;
	let r = Date.now() + Math.max(n, 0);
	for (; Date.now() < r;) if (await i(120), f(e, t)) return !0;
	return !1;
}
//#endregion
//#region src/js/modules/captchas/host.ts
var g = new Set([
	"handle",
	"placeholderSelector",
	"errorMessage",
	"sessionKey",
	"value"
]), _ = "[data-formie-captcha-error-container]", v = [
	"formie:page:navigate",
	"formie:page:navigate:after",
	"formie:submit:result"
];
function y(e, t, n) {
	return e.addEventListener(t, n), () => {
		e.removeEventListener(t, n);
	};
}
function b(e, t) {
	return e instanceof HTMLElement && e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function x(e) {
	if (!(e instanceof HTMLElement) || !e.isConnected || e.hidden || e.closest("[hidden]") || e.closest("[data-formie-page-hidden]") || e.closest("[aria-hidden=\"true\"]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display !== "none" && t.visibility !== "hidden";
}
function S(e, t) {
	let n = b(e, t);
	return n.find((e) => x(e)) || n[0] || null;
}
function C(e) {
	e.innerHTML = "";
	let t = document.createElement("div");
	return e.appendChild(t), t;
}
function w(e) {
	e?.querySelector(_)?.remove();
}
function T(e, t, r) {
	if (!e) return;
	w(e);
	let i = document.createElement("div");
	i.setAttribute("data-formie-captcha-error-container", ""), i.setAttribute("aria-live", "polite"), i.setAttribute("aria-atomic", "true"), n(i, r || e, "fieldErrors");
	let a = document.createElement("div");
	a.setAttribute("data-formie-captcha-error", ""), a.setAttribute("role", "alert"), n(a, r || e, "fieldError"), a.textContent = t, i.appendChild(a), e.appendChild(i);
}
function E(e) {
	let t = e instanceof CustomEvent ? e.detail : null;
	return !t || typeof t != "object" ? null : t;
}
function D(e, t) {
	if (!e?.captchas || typeof e.captchas != "object") return null;
	let n = e.captchas[t];
	return !n || typeof n != "object" ? null : n;
}
function O(e, t, n, r) {
	let i = /* @__PURE__ */ new Set(), o = () => {
		let a = b(e, t), o = new Set(a.filter((e) => x(e)));
		a.forEach((e) => {
			o.has(e) && !i.has(e) && (i.add(e), n(e));
		}), Array.from(i).forEach((e) => {
			o.has(e) || (i.delete(e), r(e));
		});
	}, s = a(o, 20), c = new MutationObserver(() => {
		s();
	});
	c.observe(e, {
		childList: !0,
		subtree: !0,
		attributes: !0,
		attributeFilter: [
			"class",
			"style",
			"hidden",
			"aria-hidden",
			"data-formie-page-hidden"
		]
	});
	let l = [y(window, "resize", () => {
		s();
	}), ...v.map((t) => y(e, t, () => {
		s();
	}))];
	return o(), {
		cleanup: () => {
			c.disconnect(), l.forEach((e) => {
				e();
			}), Array.from(i).forEach((e) => {
				r(e);
			}), i.clear();
		},
		reconcile: s,
		getVisible: () => b(e, t).filter((e) => x(e))
	};
}
function k(e, t) {
	return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function A(e, t, { defaultPlaceholderSelector: n, defaultTokenFieldNames: i = [], defaultWaitForValueMs: a = o }) {
	let s = t || {}, c = Object.entries(s).reduce((e, [t, n]) => (g.has(t) || (e[t] = n), e), {}), l = i.map(String).filter(Boolean), u = Number(a), d = typeof s.placeholderSelector == "string" && s.placeholderSelector.trim() !== "" ? s.placeholderSelector.trim() : n, f = typeof s.errorMessage == "string" && s.errorMessage.trim() !== "" ? s.errorMessage.trim() : r("Captcha challenge must be completed."), p = typeof s.sessionKey == "string" && s.sessionKey.trim() !== "" ? s.sessionKey.trim() : null, m = typeof s.value == "string" ? s.value : null;
	return {
		handle: k(e, s),
		ui: {
			placeholderSelector: d,
			errorMessage: f
		},
		transport: {
			tokenFieldNames: l,
			waitForValueMs: Number.isFinite(u) ? u : a,
			sessionKey: p,
			value: m
		},
		provider: c
	};
}
function j(e, t) {
	let n = e.form || e.root, r = t.ui.placeholderSelector, i = t.handle;
	return {
		form: e.form,
		root: e.root,
		placeholder: {
			query: () => b(e.root, r),
			getPrimary: () => S(e.root, r),
			observe: (t, n) => O(e.root, r, t, n),
			createContainer: (e) => C(e),
			clear: (e) => {
				e && (w(e), e.innerHTML = "");
			}
		},
		errors: {
			getDefaultMessage: () => t.ui.errorMessage,
			show: (n, i) => {
				T(i || S(e.root, r), n || t.ui.errorMessage, e.form || e.root);
			},
			clear: (t) => {
				w(t || S(e.root, r));
			}
		},
		tokens: {
			names: t.transport.tokenFieldNames,
			has: (e = t.transport.tokenFieldNames, r = n) => f(r, e),
			read: (e = t.transport.tokenFieldNames[0], r = n) => e ? d(r, e) : "",
			write: (r, { names: i = t.transport.tokenFieldNames, root: a = n, container: o = e.form } = {}) => {
				i.forEach((e) => {
					m(a, e, {
						value: r,
						container: o
					});
				});
			},
			clear: (e = t.transport.tokenFieldNames, r = n) => {
				p(r, e);
			},
			wait: (e = t.transport.waitForValueMs, r = t.transport.tokenFieldNames, i = n) => h(i, r, e)
		},
		refresh: {
			providerHandle: i,
			onTokensRefreshed: (t) => {
				let n = ["formie:refresh-tokens:after", "formie:refresh-tokens:refreshed"].map((n) => y(e.root, n, (e) => {
					let n = D(E(e), i);
					n && t(n);
				}));
				return () => {
					n.forEach((e) => {
						e();
					});
				};
			}
		},
		events: {
			onRoot: (t, n) => y(e.root, t, n),
			onForm: (t, n) => e.form ? y(e.form, t, n) : () => {}
		}
	};
}
//#endregion
//#region src/js/modules/captchas/factories.ts
var M = t("captchas");
function N({ id: e, defaultPlaceholderSelector: t, defaultTokenFieldNames: n = [], defaultWaitForValueMs: r = o, setup: i }) {
	return {
		id: e,
		kind: "captcha",
		match: () => !0,
		setup: async (a) => {
			let o = A(e, a.options || {}, {
				defaultPlaceholderSelector: t,
				defaultTokenFieldNames: n,
				defaultWaitForValueMs: r
			});
			M.log("Setup module.", {
				moduleId: e,
				placeholderSelector: o.ui.placeholderSelector,
				tokenFieldNames: o.transport.tokenFieldNames
			});
			let s = j(a, o);
			return i({
				...a,
				options: o,
				services: s
			});
		}
	};
}
function P({ id: e, defaultPlaceholderSelector: t, defaultTokenFieldNames: n = [], defaultWaitForValueMs: r = o }) {
	return N({
		id: e,
		defaultPlaceholderSelector: t,
		defaultTokenFieldNames: n,
		defaultWaitForValueMs: r,
		setup: async ({ services: t, options: n, root: r }) => {
			let i = [], a = t.placeholder.getPrimary(), o = n.transport.sessionKey, s = n.transport.value || "", c = (e) => {
				!e || !o || (e.innerHTML = "", m(e, o, {
					value: s,
					container: e
				}));
			}, l = t.placeholder.observe((t) => {
				a = t, M.log("Passive placeholder visible.", { moduleId: e }), c(t);
			}, (e) => {
				a === e && (a = t.placeholder.getPrimary()), e.innerHTML = "";
			});
			return i.push(l.cleanup), c(a), i.push(t.refresh.onTokensRefreshed((e) => {
				o = typeof e.sessionKey == "string" && e.sessionKey.trim() !== "" ? e.sessionKey.trim() : o, s = typeof e.value == "string" ? e.value : "";
				let n = t.placeholder.getPrimary() || a;
				a = n, c(n);
			})), {
				destroy: () => {
					i.forEach((e) => {
						e();
					});
				},
				onBeforeStage: async (i) => {
					if (i.stage !== "screen" || i.action !== "submit") return;
					let s = o ? [o] : n.transport.tokenFieldNames;
					if (s.length !== 0 && !await h(r, s, n.transport.waitForValueMs)) {
						let n = t.errors.getDefaultMessage();
						t.errors.show(n, a), M.warn("Passive captcha missing token.", {
							moduleId: e,
							tokenFieldNames: s
						}), i.abort(n);
					}
				}
			};
		}
	});
}
function F(t) {
	return N({
		id: t.id,
		defaultPlaceholderSelector: t.defaultPlaceholderSelector,
		defaultTokenFieldNames: t.defaultTokenFieldNames,
		setup: async (n) => {
			let r = [], i = /* @__PURE__ */ new Map(), a = /* @__PURE__ */ new Map(), o = n.services.placeholder.getPrimary(), s = !1, c = null, l = async () => (c ||= (M.log("Loading captcha provider API.", { moduleId: t.id }), t.load(n)), c), u = async (e) => {
				let r = i.get(e);
				if (n.services.errors.clear(e), !r) {
					e.innerHTML = "";
					return;
				}
				let a = await l();
				t.unmount && await t.unmount({
					api: a,
					widget: r,
					placeholder: e,
					services: n.services,
					options: n.options,
					provider: n.options.provider
				}), i.delete(e), e.innerHTML = "", n.services.tokens.clear(), M.log("Unmounted captcha placeholder widget.", { moduleId: t.id }), o === e && (o = n.services.placeholder.getPrimary());
			}, d = async (e) => {
				if (s || i.has(e) || a.has(e)) return;
				let r = (async () => {
					let r = await l();
					if (s || i.has(e)) return;
					let a = n.services.placeholder.createContainer(e), c = await t.mount({
						api: r,
						placeholder: e,
						container: a,
						services: n.services,
						options: n.options,
						provider: n.options.provider
					});
					i.set(e, c), o = e, M.log("Mounted captcha placeholder widget.", { moduleId: t.id });
				})().finally(() => {
					a.delete(e);
				});
				a.set(e, r), await r;
			}, f = n.services.placeholder.observe((e) => {
				o = e, d(e);
			}, (e) => {
				u(e);
			});
			r.push(f.cleanup);
			let p = async (e) => {
				let r = f.getVisible();
				if (t.reset) {
					let a = await l();
					for (let o of r) {
						let r = i.get(o);
						if (!r) {
							await d(o);
							continue;
						}
						await t.reset({
							api: a,
							widget: r,
							placeholder: o,
							services: n.services,
							options: n.options,
							provider: n.options.provider,
							reason: e
						}), n.services.tokens.clear(), n.services.errors.clear(o);
					}
					f.reconcile();
					return;
				}
				for (let e of Array.from(i.keys())) await u(e);
				for (let e of r) await d(e);
				f.reconcile();
			};
			return r.push(n.services.events.onRoot("formie:submit:result", (e) => {
				let t = e instanceof CustomEvent ? e.detail : null;
				t?.stage !== "validate" && (t?.ok === !1 && t?.stage === "screen" || t?.ok !== !0 && p("submit-result"));
			})), n.form && r.push(n.services.events.onForm(e("reset"), () => {
				o = n.services.placeholder.getPrimary() || o, window.setTimeout(() => {
					p("reset-state");
				}, 0);
			})), {
				destroy: async () => {
					s = !0, r.forEach((e) => {
						e();
					});
					for (let e of Array.from(i.keys())) await u(e);
				},
				onBeforeStage: async (e) => {
					if (e.stage !== "screen" || e.action !== "submit") return;
					let r = f.getVisible();
					if (r.length === 0) return;
					let a = r.find((e) => e === o) || r[0];
					await d(a), a = o || a, n.services.errors.clear(a);
					let s = i.get(a);
					if (!s) {
						let r = n.services.errors.getDefaultMessage();
						n.services.errors.show(r, a), M.warn("Captcha widget unavailable at screen stage.", { moduleId: t.id }), e.abort(r);
						return;
					}
					let c = await l();
					await t.screen({
						api: c,
						widget: s,
						placeholder: a,
						services: n.services,
						options: n.options,
						provider: n.options.provider,
						stageCtx: e
					});
				}
			};
		}
	});
}
//#endregion
//#region src/js/modules/captchas/api.ts
var I = F, L = P;
//#endregion
export { s as a, l as i, L as n, c as o, u as r, I as t };
