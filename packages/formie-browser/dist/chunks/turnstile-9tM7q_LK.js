import { a as s, c as u, f as i, b as d, C as p } from "./index-D6npYxvr.js";
import { l as f } from "./scripts-CKTFtxPO.js";
function m(e) {
  const r = e.appearance || "always";
  return (e.execution || (r === "execute" ? "execute" : "render")) === "execute" ? u : i;
}
async function _(e) {
  const { async: r, defer: t } = d(e.loadingMethod);
  return f("turnstile", {
    id: "FORMIE_TURNSTILE_SCRIPT",
    src: "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit",
    async: r,
    defer: t,
    timeoutMs: p
  });
}
const A = s({
  id: "turnstile",
  defaultPlaceholderSelector: "[data-turnstile-placeholder]",
  defaultTokenFieldNames: ["cf-turnstile-response"],
  load: ({ options: e }) => _(e.provider),
  mount: ({ api: e, container: r, provider: t, services: a }) => {
    const l = t.appearance || "always", o = t.execution || (l === "execute" ? "execute" : "render");
    return e.render(r, {
      sitekey: t.siteKey || "",
      theme: t.theme || "auto",
      size: t.size || "normal",
      appearance: l,
      execution: o,
      callback: (n) => {
        typeof n == "string" && n.trim() !== "" && a.tokens.write(n.trim()), a.errors.clear();
      },
      "expired-callback": () => {
        a.tokens.clear(), a.errors.clear();
      },
      "timeout-callback": () => {
        a.tokens.clear(), a.errors.clear();
      },
      "error-callback": () => {
        a.tokens.clear();
      }
    });
  },
  screen: ({ api: e, widget: r, placeholder: t, services: a, provider: l, stageCtx: o }) => {
    if (!a.tokens.has())
      return e.execute(r), a.tokens.wait(m(l)).then((n) => {
        if (!n) {
          const c = a.errors.getDefaultMessage();
          a.errors.show(c, t), o.abort(c);
        }
      });
  },
  unmount: ({ api: e, widget: r, services: t }) => {
    e.reset(r), t.tokens.clear();
  }
});
export {
  A as turnstileModule
};
