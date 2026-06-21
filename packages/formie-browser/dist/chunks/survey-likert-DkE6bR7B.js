import { t as e } from "./debug-BV0DvdHx.js";
import { t } from "./styles-BfoIZwJp.js";
import { t as n } from "./_survey-presentations-RbSqcQph.js";
//#region src/js/modules/fields/survey-likert.ts
var r = "[data-formie-likert-field-layout]", i = "survey-likert", a = e("fields", "survey-likert");
t(i, [n]);
var o = {
	id: i,
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && (e.target.matches(r) || !!e.target.querySelector(r)),
	setup: async (e) => {
		if (!(e.target instanceof HTMLElement)) return;
		let t = e.target.matches(r) ? [e.target] : Array.from(e.target.querySelectorAll(r)).filter((e) => e instanceof HTMLElement);
		return a.log("Module setup.", { fieldCount: t.length }), await e.emit("formie:module:survey-likert:init", { count: t.length }), { destroy: () => {
			a.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:survey-likert:destroy", {});
		} };
	}
};
//#endregion
export { o as surveyLikertModule };
