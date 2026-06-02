//#region src/js/utils/field-references.keys.ts
function stripFieldPrefix(value) {
	return value.replace(/^\{field:/, "").replace(/^\{/, "").replace(/\}$/, "").trim();
}
function normalizeFieldKey(value) {
	return stripFieldPrefix(value).replace(/\]/g, "").split("[").join(".").replace(/\.+/g, ".").replace(/^\./, "").replace(/\.$/, "");
}
function fieldKeyToInputName(key) {
	const parts = normalizeFieldKey(key).split(".").filter(Boolean);
	if (!parts.length) return "";
	const [head, ...rest] = parts;
	return `fields[${head}]${rest.map((part) => `[${part}]`).join("")}`;
}
function inputNameToFieldKey(name) {
	const match = String(name || "").trim().match(/^fields\[([^\]]+)\](.*)$/);
	if (!match) return "";
	const first = match[1] || "";
	const tail = match[2] || "";
	return [first, ...Array.from(tail.matchAll(/\[([^\]]+)\]/g)).map((part) => {
		return part[1] || "";
	}).filter(Boolean)].join(".");
}
//#endregion
export { inputNameToFieldKey as n, normalizeFieldKey as r, fieldKeyToInputName as t };
