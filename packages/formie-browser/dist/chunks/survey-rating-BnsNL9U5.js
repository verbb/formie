import { t as e } from "./debug-BV0DvdHx.js";
import { t } from "./styles-BfoIZwJp.js";
import { t as n } from "./shared-Bx9s0i0P.js";
import { t as r } from "./_survey-presentations-RbSqcQph.js";
//#region src/js/modules/fields/survey-rating.ts
var i = "[data-formie-survey-rating]", a = "[data-formie-survey-rating-stars]", o = "[data-formie-rating-option]", s = "input[data-formie-rating-input]", c = "data-formie-rating-pressed-while-checked", l = "survey-rating", u = e("fields", "survey-rating");
t(l, [r]);
function d(e) {
	return Array.from(e.querySelectorAll(s)).filter((e) => e instanceof HTMLInputElement);
}
function f(e) {
	let t = d(e).findIndex((e) => e.checked);
	if (t >= 0) {
		e.setAttribute("data-formie-rating-value", String(t + 1));
		return;
	}
	e.removeAttribute("data-formie-rating-value");
}
function p(e) {
	let t = e.querySelector(a);
	if (!(t instanceof HTMLElement)) return u.warn("Missing rating stars container; skipping field."), () => {};
	let r = [], i = () => {
		t.removeAttribute("data-formie-rating-hover");
	}, p = (e) => {
		let n = Array.from(t.querySelectorAll(o)).indexOf(e);
		n >= 0 && t.setAttribute("data-formie-rating-hover", String(n + 1));
	};
	return t.querySelectorAll(o).forEach((e) => {
		if (!(e instanceof HTMLElement)) return;
		let t = e.querySelector(s), n = () => {
			if (t instanceof HTMLInputElement) {
				if (t.checked) {
					t.setAttribute(c, "true");
					return;
				}
				t.removeAttribute(c);
			}
		}, i = () => {
			p(e);
		};
		e.addEventListener("pointerdown", n), e.addEventListener("mouseenter", i), r.push(() => {
			e.removeEventListener("pointerdown", n), e.removeEventListener("mouseenter", i);
		});
	}), t.addEventListener("mouseleave", i), r.push(() => {
		t.removeEventListener("mouseleave", i);
	}), d(t).forEach((i) => {
		let a = (r) => {
			if (i.getAttribute(c) !== "true") {
				f(t), n(e, l, "change", {
					ratingField: e,
					value: i.checked ? i.value : ""
				});
				return;
			}
			r.preventDefault(), d(t).forEach((e) => {
				e.checked = !1, e.removeAttribute(c);
			}), f(t), n(e, l, "change", {
				ratingField: e,
				value: ""
			});
		};
		i.addEventListener("click", a), r.push(() => {
			i.removeEventListener("click", a), i.removeAttribute(c);
		});
	}), f(t), () => {
		r.forEach((e) => {
			e();
		}), i(), t.removeAttribute("data-formie-rating-value");
	};
}
var m = {
	id: l,
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && (e.target.matches(i) || !!e.target.querySelector(i)),
	setup: async (e) => {
		if (!(e.target instanceof HTMLElement)) return;
		let t = e.target.matches(i) ? [e.target] : Array.from(e.target.querySelectorAll(i)).filter((e) => e instanceof HTMLElement);
		u.log("Module setup.", { fieldCount: t.length });
		let n = t.map((e) => p(e));
		return await e.emit("formie:module:survey-rating:init", { count: t.length }), { destroy: () => {
			n.forEach((e) => {
				e();
			}), u.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:survey-rating:destroy", {});
		} };
	}
};
//#endregion
export { m as surveyRatingModule };
