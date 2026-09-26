//#region src/js/utils/request-profile.ts
var e = /* @__PURE__ */ new WeakMap();
function t(t, n) {
	e.set(t, { ...n }), t.dataset.formieRequestProfile = n.profile ?? "same-origin-browser";
}
function n(t) {
	return t && e.get(t) || { profile: t?.dataset.formieRequestProfile };
}
//#endregion
export { t as n, n as t };
