import { a as s, c as u, h as i, b as d, C as f } from "./index-CZtn5KAB.js";
import { l as m } from "./scripts--tQDv1Kx.js";
function p(e) {
  const a = e.appearance || "always";
  return (e.execution || (a === "execute" ? "execute" : "render")) === "execute" ? u : i;
}
async function _(e) {
  const { async: a, defer: t } = d(e.loadingMethod);
  return m("turnstile", {
    id: "FORMIE_TURNSTILE_SCRIPT",
    src: "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit",
    async: a,
    defer: t,
    timeoutMs: f
  });
}
const A = s({
  id: "turnstile",
  defaultPlaceholderSelector: "[data-turnstile-placeholder]",
  defaultTokenFieldNames: ["cf-turnstile-response"],
  load: ({ options: e }) => _(e.provider),
  mount: ({ api: e, container: a, provider: t, services: r }) => {
    const o = t.appearance || "always", l = t.execution || (o === "execute" ? "execute" : "render");
    return e.render(a, {
      sitekey: t.siteKey || "",
      theme: t.theme || "auto",
      size: t.size || "normal",
      appearance: o,
      execution: l,
      callback: (n) => {
        typeof n == "string" && n.trim() !== "" && r.tokens.write(n.trim()), r.errors.clear();
      },
      "expired-callback": () => {
        r.tokens.clear(), r.errors.clear();
      },
      "timeout-callback": () => {
        r.tokens.clear(), r.errors.clear();
      },
      "error-callback": () => {
        r.tokens.clear();
      }
    });
  },
  screen: ({ api: e, widget: a, placeholder: t, services: r, provider: o, stageCtx: l }) => {
    if (!r.tokens.has())
      return e.execute(a), r.tokens.wait(p(o)).then((n) => {
        if (!n) {
          const c = r.errors.getDefaultMessage();
          r.errors.show(c, t), l.abort(c);
        }
      });
  },
  unmount: ({ api: e, widget: a, services: t }) => {
    typeof e.remove == "function" ? e.remove(a) : e.reset(a), t.tokens.clear();
  }
});
export {
  A as turnstileModule
};
