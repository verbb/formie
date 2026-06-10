//#region src/js/modules/styles.ts
var e = /* @__PURE__ */ new Set();
function t(t, n) {
	if (typeof document > "u") return;
	let r = `formie-module-style:${t}`;
	if (e.has(r) || document.querySelector(`style[data-formie-module-style="${t}"]`)) {
		e.add(r);
		return;
	}
	let i = n.filter((e) => typeof e == "string" && e.trim().length > 0).join("\n");
	if (!i) {
		e.add(r);
		return;
	}
	let a = document.createElement("style");
	a.setAttribute("data-formie-module-style", t), a.textContent = i, document.head.appendChild(a), e.add(r);
}
//#endregion
export { t };
