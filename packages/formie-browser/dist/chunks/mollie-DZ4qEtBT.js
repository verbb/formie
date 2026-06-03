import { u as d, v as l } from "./index-BqkORC7E.js";
const o = l("mollie", "redirect"), s = d({
  id: "mollie",
  defaultRequiredInputSuffixes: [],
  load: async () => null,
  setup: async ({ services: r, root: e }) => {
    const t = (i) => {
      const n = i.detail?.data;
      if (!n?.checkoutUrl) {
        r.addError("Missing Mollie checkout URL.");
        return;
      }
      window.location.href = n.checkoutUrl;
    };
    return e.addEventListener(o, t), {
      destroy: () => {
        e.removeEventListener(o, t);
      }
    };
  }
});
export {
  s as mollieModule
};
