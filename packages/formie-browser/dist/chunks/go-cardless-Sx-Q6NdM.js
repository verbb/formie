import { v as a, x as o } from "./index-CSO3KCTK.js";
const d = o("go-cardless", "redirect"), l = a({
  id: "go-cardless",
  defaultRequiredInputSuffixes: [],
  load: async () => null,
  setup: async ({ services: n, root: e }) => {
    const r = (s) => {
      const t = s.detail?.data;
      if (!t?.redirectUrl) {
        n.addError("Missing GoCardless redirect URL.");
        return;
      }
      window.location.href = t.redirectUrl;
    };
    return e.addEventListener(d, r), {
      destroy: () => {
        e.removeEventListener(d, r);
      }
    };
  }
});
export {
  l as goCardlessModule
};
