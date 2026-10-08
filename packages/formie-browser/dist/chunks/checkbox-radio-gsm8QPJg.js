import { t as e } from "./debug-BV0DvdHx.js";
import { l as t, n, u as r } from "./shared-OKhSay59.js";
//#region src/js/modules/fields/checkbox-radio.ts
var i = "[data-formie-checkboxes-field-layout], [data-formie-radio-field-layout]", a = "minmaxOptions", o = "otherOptionText", s = "data-formie-checkbox-radio-max-disabled", c = "checkbox-radio", l = "checkbox-radio", u = e("fields", "checkbox-radio");
function d(e) {
	return e.hasAttribute("data-checkbox-toggle") || e.hasAttribute("data-formie-checkbox-toggle");
}
function f(e) {
	let t = e(a);
	if (!t || t === !0 || typeof t != "object") return {
		min: null,
		max: null
	};
	let n = t;
	return {
		min: typeof n.min == "number" ? n.min : null,
		max: typeof n.max == "number" ? n.max : null
	};
}
function p(e) {
	return e.hasAttribute("data-formie-other-option") || !!e.closest("[data-formie-other-option]");
}
function m(e) {
	let t = [];
	return e.querySelectorAll("[data-formie-other-option-text]").forEach((e) => {
		if (!(e instanceof HTMLInputElement)) return;
		let n = e.closest("[data-formie-field-option]") ?? e.parentElement;
		if (!n) return;
		let r = n.querySelector("input[type=\"checkbox\"][data-formie-other-option], input[type=\"radio\"][data-formie-other-option]") ?? n.querySelector("input[type=\"checkbox\"], input[type=\"radio\"]");
		r instanceof HTMLInputElement && t.push({
			choiceInput: r,
			textInput: e
		});
	}), t;
}
function h(e) {
	m(e).forEach(({ choiceInput: e, textInput: t }) => {
		let n = e.checked;
		t.disabled = !n, n || (t.value = "");
	});
}
function g(e) {
	let t = m(e);
	if (!t.length) return () => {};
	let n = [];
	return Array.from(e.querySelectorAll("input[type=\"checkbox\"], input[type=\"radio\"]")).filter((e) => e instanceof HTMLInputElement && !d(e)).forEach((t) => {
		let r = () => {
			h(e);
		};
		t.addEventListener("change", r), n.push(() => {
			t.removeEventListener("change", r);
		});
	}), t.forEach(({ textInput: t }) => {
		let r = () => {
			h(e);
		};
		t.addEventListener("input", r), t.addEventListener("change", r), n.push(() => {
			t.removeEventListener("input", r), t.removeEventListener("change", r);
		});
	}), h(e), () => {
		n.forEach((e) => {
			e();
		});
	};
}
function _(e) {
	return Array.from(e.querySelectorAll("input[type=\"checkbox\"]")).filter((e) => e instanceof HTMLInputElement && !d(e)).filter((e) => e.checked).length;
}
function v(e) {
	r(e, l, (e) => {
		e.addValidator(o, ({ field: e, getRule: t }) => !e || !t(o) || m(e).every(({ choiceInput: e, textInput: t }) => !e.checked || t.value.trim() !== ""), ({ field: e, label: t, t: n, getRule: r }) => {
			if (!e || !r(o)) return n("{label} is invalid.", { label: t });
			let i = m(e).find(({ choiceInput: e }) => e.checked)?.choiceInput.closest("[data-formie-field-option]")?.querySelector("[data-formie-field-option-label]")?.textContent?.trim() ?? t;
			return e.getAttribute("data-formie-validation-other-option-text-message") ?? n("Please enter a value for “{label}”.", { label: i });
		}), e.addValidator(a, ({ field: e, getRule: t }) => {
			if (!e || !t(a)) return !0;
			let n = _(e), { min: r, max: i } = f(t);
			return !(r !== null && n < r || i !== null && n > i);
		}, ({ field: e, label: t, t: n, getRule: r }) => {
			if (!e) return n("{label} is invalid.", { label: t });
			let i = _(e), { min: a, max: o } = f(r);
			return a !== null && i < a ? e.getAttribute("data-formie-validation-min-options-message") ?? n("{label} should contain at least {min, number} {min, plural, one{option} other{options}}.", {
				label: t,
				min: a
			}) : o !== null && i > o ? e.getAttribute("data-formie-validation-max-options-message") ?? n("{label} should contain at most {max, number} {max, plural, one{option} other{options}}.", {
				label: t,
				max: o
			}) : n("{label} is invalid.", { label: t });
		});
	});
}
function y(e) {
	t(e, l, [a, o]);
}
function b(e) {
	if (!e.length) return;
	let t = e.some((e) => e.checked);
	e.forEach((e) => {
		if (t) {
			e.removeAttribute("required"), e.setAttribute("aria-required", "false");
			return;
		}
		e.setAttribute("required", "true"), e.setAttribute("aria-required", "true");
	});
}
function x(e) {
	let t = parseInt(e.closest("[data-formie-field-handle]")?.getAttribute("data-formie-max-options") || "", 10);
	if (!(t > 0)) return;
	let n = Array.from(e.querySelectorAll("input[type=\"checkbox\"]")).filter((e) => e instanceof HTMLInputElement && !d(e) && !p(e)), r = n.filter((e) => e.checked).length >= t;
	n.forEach((e) => {
		let t = r && !e.checked, n = e.hasAttribute(s);
		if (t) {
			e.disabled || (e.disabled = !0, e.setAttribute(s, "true"));
			return;
		}
		n && (e.disabled = !1, e.removeAttribute(s));
	});
}
function S(e, t) {
	Array.from(e.querySelectorAll("input[type=\"checkbox\"]")).filter((e) => e instanceof HTMLInputElement && e !== t && !d(e)).forEach((e) => {
		(!e.disabled || e.checked) && (e.checked = t.checked, e.dispatchEvent(new Event("change", { bubbles: !0 })), e.dispatchEvent(new Event("input", { bubbles: !0 })));
	});
}
function C(e) {
	let t = Array.from(e.querySelectorAll("input[type=\"checkbox\"], input[type=\"radio\"]")).filter((e) => e instanceof HTMLInputElement);
	if (!t.length) return u.log("No checkbox/radio inputs found for field."), () => {};
	let r = t.filter((e) => e.type === "checkbox" && e.required), i = e.closest("form"), a = (t) => {
		queueMicrotask(() => {
			t.defaultPrevented || (b(r), x(e), h(e));
		});
	};
	i?.addEventListener("reset", a);
	let o = t.map((t) => {
		let n = t.type === "radio" ? "change" : "click", i = () => {
			t.type === "checkbox" && d(t) && S(e, t), b(r), x(e), queueMicrotask(() => {
				h(e);
			}), u.log("Input interaction processed.", {
				inputName: t.name,
				inputType: t.type,
				checked: t.checked
			});
		};
		return t.addEventListener(n, i), () => {
			t.removeEventListener(n, i);
		};
	}), s = g(e);
	return b(r), x(e), h(e), n(e, c, "init", { checkboxRadio: e }), () => {
		o.forEach((e) => {
			e();
		}), i?.removeEventListener("reset", a), s();
	};
}
var w = {
	moduleId: `formie:${c}`,
	version: 2,
	surfaces: [
		"server-rendered",
		"client-rendered",
		"cp-edit"
	],
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && (e.target.matches(i) || !!e.target.querySelector(i)),
	setup: async (e) => {
		if (!(e.target instanceof HTMLElement)) return;
		let t = e.target.matches(i) ? [e.target] : Array.from(e.target.querySelectorAll(i)).filter((e) => e instanceof HTMLElement);
		v(e.form), u.log("Module setup.", { fieldCount: t.length });
		let n = t.map((e) => C(e));
		return await e.emit("formie:module:checkbox-radio:init", { count: t.length }), { destroy: () => {
			n.forEach((e) => {
				e();
			}), y(e.form), u.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:checkbox-radio:destroy", {});
		} };
	}
};
//#endregion
export { w as checkboxRadioModule };
