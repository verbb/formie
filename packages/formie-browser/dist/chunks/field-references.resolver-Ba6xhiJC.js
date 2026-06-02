import { n as inputNameToFieldKey, r as normalizeFieldKey, t as fieldKeyToInputName } from "./field-references.keys-BpBZ_quS.js";
//#region src/js/utils/field-references.parser.ts
function parseTransforms(body) {
	const parts = body.split(";").map((part) => {
		return part.trim();
	}).filter(Boolean);
	if (!parts.length) return {
		source: "",
		transforms: []
	};
	const [source, ...metadata] = parts;
	const transforms = [];
	let current = null;
	metadata.forEach((entry) => {
		if (entry.startsWith("transform=")) {
			if (current) transforms.push(current);
			current = {
				id: decodeURIComponent(entry.slice(10) || "").trim(),
				params: {}
			};
			return;
		}
		if (!current || !entry.includes("=")) return;
		const [rawKey, rawValue] = entry.split("=", 2);
		const key = (rawKey || "").trim();
		if (!key || key === "transform") return;
		current.params[key] = decodeURIComponent(rawValue || "").trim();
	});
	if (current) transforms.push(current);
	return {
		source: source || "",
		transforms
	};
}
function parseFieldReference(rawValue) {
	const raw = String(rawValue || "").trim();
	if (!raw) return {
		raw,
		target: "",
		key: "",
		selector: "",
		defaultValue: "",
		transforms: [],
		isToken: false,
		isValid: false
	};
	const tokenMatch = raw.match(/^\{([a-zA-Z]+)(?::(.*))?\}$/);
	if (!tokenMatch) return {
		raw,
		target: "",
		key: normalizeFieldKey(raw),
		selector: "",
		defaultValue: "",
		transforms: [],
		isToken: false,
		isValid: true
	};
	const targetRaw = (tokenMatch[1] || "").trim().toLowerCase();
	const [beforeDefault, defaultRaw = ""] = (tokenMatch[2] || "").trim().split("|", 2);
	const { source, transforms } = parseTransforms(beforeDefault || "");
	if (targetRaw !== "field") return {
		raw,
		target: "",
		key: "",
		selector: "",
		defaultValue: defaultRaw.trim(),
		transforms,
		isToken: true,
		isValid: false
	};
	const separatorIndex = source.indexOf(":");
	const keyRaw = separatorIndex === -1 ? source : source.slice(0, separatorIndex);
	const selectorRaw = separatorIndex === -1 ? "" : source.slice(separatorIndex + 1);
	const key = normalizeFieldKey(keyRaw);
	return {
		raw,
		target: "field",
		key,
		selector: selectorRaw.trim(),
		defaultValue: defaultRaw.trim(),
		transforms,
		isToken: true,
		isValid: key !== ""
	};
}
//#endregion
//#region src/js/utils/field-references.registry.ts
function isFieldValueInput(node) {
	return node instanceof HTMLInputElement || node instanceof HTMLTextAreaElement || node instanceof HTMLSelectElement;
}
function addInputEntry(registry, key, input) {
	const normalizedKey = key.trim();
	const name = String(input.name || "").trim();
	if (!normalizedKey || !name) return;
	const entry = registry.get(normalizedKey) || {
		key: normalizedKey,
		names: [],
		inputs: []
	};
	if (!entry.names.includes(name)) entry.names.push(name);
	if (!entry.inputs.includes(input)) entry.inputs.push(input);
	registry.set(normalizedKey, entry);
}
function buildFieldValueRegistry(root) {
	const registry = /* @__PURE__ */ new Map();
	Array.from(root.querySelectorAll("[name]")).filter((node) => {
		return isFieldValueInput(node);
	}).forEach((input) => {
		const key = inputNameToFieldKey(input.name);
		if (!key) return;
		addInputEntry(registry, key, input);
	});
	return registry;
}
//#endregion
//#region src/js/utils/field-references.resolver.ts
function readInputsValue(inputs) {
	if (!inputs.length) return "";
	const first = inputs[0];
	if (first instanceof HTMLSelectElement && first.multiple) return Array.from(first.selectedOptions).map((option) => {
		return option.value;
	});
	if (inputs.some((input) => {
		return input instanceof HTMLInputElement && (input.type === "checkbox" || input.type === "radio");
	})) {
		const selected = inputs.flatMap((input) => {
			if (!(input instanceof HTMLInputElement) || !input.checked) return [];
			return [input.value];
		});
		return selected.length > 1 ? selected : selected[0] || "";
	}
	return first.value;
}
function getEntry(registry, key) {
	return registry.get(normalizeFieldKey(key)) || null;
}
function resolveFieldReferenceLive(reference, registry) {
	const parsed = parseFieldReference(reference);
	const key = parsed.key;
	const entry = getEntry(registry, key);
	if (!entry) return {
		key,
		value: parsed.defaultValue,
		found: false
	};
	const value = readInputsValue(entry.inputs);
	return {
		key,
		value: value === "" && parsed.defaultValue !== "" ? parsed.defaultValue : value,
		found: true
	};
}
function resolveFieldReferenceFromFormData(reference, formData, registry) {
	const parsed = parseFieldReference(reference);
	const key = parsed.key;
	if (!key) return {
		key,
		value: parsed.defaultValue,
		found: false
	};
	const entry = registry ? getEntry(registry, key) : null;
	const values = (entry?.names?.length ? entry.names : [fieldKeyToInputName(key)]).flatMap((name) => {
		const collected = formData.getAll(name).map((value) => {
			return String(value ?? "");
		});
		if (collected.length) return collected;
		return formData.getAll(`${name}[]`).map((value) => {
			return String(value ?? "");
		});
	}).filter((value) => value !== "");
	if (!values.length) return {
		key,
		value: parsed.defaultValue,
		found: false
	};
	return {
		key,
		value: values.length > 1 ? values : values[0],
		found: true
	};
}
//#endregion
export { parseFieldReference as i, resolveFieldReferenceLive as n, buildFieldValueRegistry as r, resolveFieldReferenceFromFormData as t };
