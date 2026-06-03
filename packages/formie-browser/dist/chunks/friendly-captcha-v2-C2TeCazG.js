import { a as l } from "./index-DL83ZezE.js";
const i = l({
  id: "friendly-captcha-v2",
  defaultPlaceholderSelector: "[data-friendly-captcha-placeholder]",
  defaultTokenFieldNames: ["frc-captcha-response"],
  load: async () => import("./sdk-3SicTWby.js"),
  mount: ({ api: r, container: n, provider: e, services: t }) => {
    const a = new r.FriendlyCaptchaSDK().createWidget({
      element: n,
      sitekey: e.siteKey || "",
      formFieldName: "frc-captcha-response",
      language: e.language,
      startMode: e.startMode || "none"
    });
    return a.addEventListener("frc:widget.complete", (s) => {
      const o = s.detail;
      typeof o?.response == "string" && o.response.trim() !== "" && t.tokens.write(o.response.trim()), t.errors.clear();
    }), a.addEventListener("frc:widget.expire", () => {
      t.tokens.clear(), t.errors.clear();
    }), a.addEventListener("frc:widget.error", (s) => {
      s.detail?.error && t.tokens.clear();
    }), a;
  },
  screen: async ({ widget: r, placeholder: n, services: e, stageCtx: t }) => {
    if (e.tokens.has())
      return;
    if (r.start(), !await e.tokens.wait()) {
      const a = e.errors.getDefaultMessage();
      e.errors.show(a, n), t.abort(a);
    }
  },
  unmount: ({ widget: r, services: n }) => {
    r.destroy(), n.tokens.clear();
  }
});
export {
  i as friendlyCaptchaV2Module
};
