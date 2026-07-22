import { s as l } from "./_survey-presentations-C8LAnIwa.js";
import { e as n } from "./styles-C3aqgtek.js";
import { j as u } from "./index-CZtn5KAB.js";
const t = "[data-formie-likert-field-layout]", s = "survey-likert", o = u("fields", "survey-likert");
n(s, [l]);
const f = {
  id: s,
  kind: "field",
  match: (e) => e.target instanceof HTMLElement && (e.target.matches(t) || !!e.target.querySelector(t)),
  setup: async (e) => {
    if (!(e.target instanceof HTMLElement))
      return;
    const r = e.target.matches(t) ? [e.target] : Array.from(e.target.querySelectorAll(t)).filter((i) => i instanceof HTMLElement);
    return o.log("Module setup.", {
      fieldCount: r.length
    }), await e.emit("formie:module:survey-likert:init", {
      count: r.length
    }), {
      destroy: () => {
        o.log("Module destroy.", {
          fieldCount: r.length
        }), e.emit("formie:module:survey-likert:destroy", {});
      }
    };
  }
};
export {
  f as surveyLikertModule
};
