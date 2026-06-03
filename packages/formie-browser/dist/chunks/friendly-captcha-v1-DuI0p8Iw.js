import { a as l } from "./index-DL83ZezE.js";
const c = l({
  id: "friendly-captcha-v1",
  defaultPlaceholderSelector: "[data-friendly-captcha-placeholder]",
  defaultTokenFieldNames: ["frc-captcha-solution"],
  load: async () => import("./index-BP7Unm5Y.js"),
  mount: ({ api: e, container: t, provider: a, services: o }) => new e.WidgetInstance(t, {
    sitekey: a.siteKey || "",
    startMode: a.startMode || "none",
    language: a.language || "en",
    solutionFieldName: "frc-captcha-solution",
    doneCallback: (n) => {
      typeof n == "string" && n.trim() !== "" && o.tokens.write(n.trim()), o.errors.clear();
    },
    errorCallback: () => {
      o.tokens.clear();
    }
  }),
  screen: async ({ widget: e, placeholder: t, services: a, stageCtx: o }) => {
    if (a.tokens.has())
      return;
    if (await e.start(), !await a.tokens.wait()) {
      const r = a.errors.getDefaultMessage();
      a.errors.show(r, t), o.abort(r);
    }
  },
  unmount: ({ widget: e, services: t }) => {
    e.destroy(), t.tokens.clear();
  }
});
export {
  c as friendlyCaptchaV1Module
};
