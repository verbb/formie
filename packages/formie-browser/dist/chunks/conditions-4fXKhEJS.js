import { t as createDebug } from "./debug-KnZeKYBI.js";
import { t as fieldKeyToInputName } from "./field-references.keys-BpBZ_quS.js";
import { a as g, o as h } from "./dist-D09GnXMW.js";
import { n as escapeSelectorValue } from "./shared-DC6_1u8X.js";
//#region src/js/modules/fields/conditions/config.ts
var CONDITION_SELECTOR = "[data-formie-conditions]";
function parseConditionSource(value) {
	if (!value || typeof value !== "object") return null;
	const candidate = value;
	const transformerParams = candidate.transformerParams;
	return {
		raw: typeof candidate.raw === "string" ? candidate.raw : "",
		target: typeof candidate.target === "string" ? candidate.target : "",
		handle: typeof candidate.handle === "string" ? candidate.handle : "",
		selector: typeof candidate.selector === "string" ? candidate.selector : "",
		defaultValue: typeof candidate.defaultValue === "string" ? candidate.defaultValue : "",
		transformerId: typeof candidate.transformerId === "string" ? candidate.transformerId : "",
		transformerParams: transformerParams && typeof transformerParams === "object" ? Object.fromEntries(Object.entries(transformerParams).map(([key, item]) => {
			return [key, String(item ?? "")];
		})) : {},
		isValid: candidate.isValid !== false
	};
}
function getConditionNodes(root) {
	const nodes = Array.from(root.querySelectorAll(CONDITION_SELECTOR));
	if (root.matches("[data-formie-conditions]")) return [root, ...nodes];
	return nodes;
}
function parseConditionSettings(node) {
	const raw = node.getAttribute("data-formie-conditions");
	if (!raw) return null;
	try {
		const parsed = JSON.parse(raw);
		const conditions = Array.isArray(parsed.conditions) ? parsed.conditions.filter((condition) => {
			if (!condition || typeof condition !== "object") return false;
			const candidate = condition;
			return typeof candidate.field === "string" && typeof candidate.condition === "string";
		}).map((condition) => {
			const candidate = condition;
			return {
				field: condition.field,
				source: parseConditionSource(candidate.source),
				condition: condition.condition,
				value: condition.value
			};
		}) : [];
		return {
			showRule: parsed.showRule === "hide" ? "hide" : "show",
			conditionRule: parsed.conditionRule === "any" ? "any" : "all",
			clearOnHide: parsed.clearOnHide !== false,
			isNested: Boolean(parsed.isNested),
			conditions
		};
	} catch (error) {
		console.error("[formie] Invalid condition JSON.", error);
		return null;
	}
}
//#endregion
//#region src/js/modules/fields/conditions/effects.ts
var CONDITION_DISABLED_ATTR = "data-formie-conditions-disabled";
var PRESERVED_DISABLED_ATTR = "data-formie-preserve-disabled";
var CONDITIONAL_HIDDEN_ATTR = "data-formie-conditionally-hidden";
var PAGE_HIDDEN_ATTR = "data-formie-page-hidden";
var CONDITIONAL_HIDDEN_CLASS = "formie-conditionally-hidden";
var PAGE_HIDDEN_CLASS = "formie-page-hidden";
var ROW_HIDDEN_ATTR = "data-formie-row-hidden";
var ROW_HIDDEN_CLASS = "formie-row-hidden";
var FIELD_COUNT_ATTR = "data-formie-field-count";
var ROW_SELECTOR = "[data-formie-row], [data-formie-subfield-row], [data-formie-nested-field-row]";
var FIELD_SELECTOR = ":scope > [data-formie-field]";
function clearConditionNodeValues(node) {
	node.querySelectorAll("input, select, textarea").forEach((element) => {
		if (!(element instanceof HTMLInputElement) && !(element instanceof HTMLSelectElement) && !(element instanceof HTMLTextAreaElement)) return;
		if (element instanceof HTMLInputElement) {
			if (element.type === "checkbox" || element.type === "radio") element.checked = false;
			else if (element.type !== "hidden") element.value = "";
		}
		if (element instanceof HTMLSelectElement) if (element.multiple) Array.from(element.options).forEach((option) => {
			option.selected = false;
		});
		else element.selectedIndex = 0;
		if (element instanceof HTMLTextAreaElement) element.value = "";
	});
}
function setVisibilityState(node, hidden) {
	const isPage = node.hasAttribute("data-formie-page");
	const hiddenAttr = isPage ? PAGE_HIDDEN_ATTR : CONDITIONAL_HIDDEN_ATTR;
	const hiddenClass = isPage ? PAGE_HIDDEN_CLASS : CONDITIONAL_HIDDEN_CLASS;
	const wasHidden = node.hasAttribute(hiddenAttr);
	if (hidden) {
		if (!wasHidden) node.setAttribute(hiddenAttr, "true");
		if (!node.classList.contains(hiddenClass)) node.classList.add(hiddenClass);
	} else {
		if (wasHidden) node.removeAttribute(hiddenAttr);
		if (node.classList.contains(hiddenClass)) node.classList.remove(hiddenClass);
	}
	return wasHidden !== hidden;
}
function syncDisabledState(node, hidden) {
	node.querySelectorAll("input, textarea, select").forEach((input) => {
		if (hidden) {
			if (!input.hasAttribute(CONDITION_DISABLED_ATTR)) {
				if (input.hasAttribute("disabled")) input.setAttribute(PRESERVED_DISABLED_ATTR, "true");
				input.setAttribute(CONDITION_DISABLED_ATTR, "true");
			}
			input.setAttribute("disabled", "true");
			return;
		}
		if (!input.hasAttribute(CONDITION_DISABLED_ATTR)) return;
		if (input.hasAttribute(PRESERVED_DISABLED_ATTR)) {
			input.setAttribute("disabled", "true");
			input.removeAttribute(PRESERVED_DISABLED_ATTR);
		} else input.removeAttribute("disabled");
		input.removeAttribute(CONDITION_DISABLED_ATTR);
	});
}
function isFieldVisible(field) {
	return !field.hasAttribute(CONDITIONAL_HIDDEN_ATTR) && !field.hasAttribute(PAGE_HIDDEN_ATTR) && !field.hasAttribute(ROW_HIDDEN_ATTR) && !field.hasAttribute("hidden");
}
function syncRowState(row) {
	const visibleFieldCount = Array.from(row.querySelectorAll(FIELD_SELECTOR)).filter((field) => {
		return isFieldVisible(field);
	}).length;
	if (visibleFieldCount > 0) {
		const visibleCount = String(visibleFieldCount);
		if (row.getAttribute(FIELD_COUNT_ATTR) !== visibleCount) row.setAttribute(FIELD_COUNT_ATTR, visibleCount);
		if (row.hasAttribute(ROW_HIDDEN_ATTR)) row.removeAttribute(ROW_HIDDEN_ATTR);
		if (row.classList.contains(ROW_HIDDEN_CLASS)) row.classList.remove(ROW_HIDDEN_CLASS);
		return;
	}
	if (row.hasAttribute(FIELD_COUNT_ATTR)) row.removeAttribute(FIELD_COUNT_ATTR);
	if (!row.hasAttribute(ROW_HIDDEN_ATTR)) row.setAttribute(ROW_HIDDEN_ATTR, "true");
	if (!row.classList.contains(ROW_HIDDEN_CLASS)) row.classList.add(ROW_HIDDEN_CLASS);
}
function syncAncestorRows(node) {
	let currentRow = node.closest(ROW_SELECTOR);
	while (currentRow) {
		syncRowState(currentRow);
		currentRow = currentRow.parentElement?.closest(ROW_SELECTOR) || null;
	}
}
function applyConditionVisibility(node, hidden, clearOnHide) {
	const stateChanged = setVisibilityState(node, hidden);
	syncDisabledState(node, hidden);
	syncAncestorRows(node);
	if (hidden && clearOnHide && stateChanged) clearConditionNodeValues(node);
	return stateChanged;
}
//#endregion
//#region src/js/modules/fields/conditions/references.ts
var CONDITION_INPUT_SELECTOR = "input, select, textarea";
var ROW_SCOPE_SELECTOR = "[data-formie-repeater-item], [data-formie-table-row]";
function isConditionInput(element) {
	return element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement;
}
function getNodeRowToken(node) {
	const nodeInput = node.querySelector(CONDITION_INPUT_SELECTOR);
	if (!nodeInput) return null;
	const name = nodeInput.getAttribute("name") || "";
	const tokens = Array.from(name.matchAll(/\[(\d+)\]/g));
	if (!tokens.length) return null;
	return tokens[tokens.length - 1]?.[1] || null;
}
function getRowScope(node) {
	return node.closest(ROW_SCOPE_SELECTOR);
}
function getFieldInputs(fieldNode) {
	return Array.from(fieldNode.querySelectorAll(CONDITION_INPUT_SELECTOR)).filter((element) => {
		return isConditionInput(element);
	});
}
function getInputNameTokens(input) {
	const name = input.getAttribute("name") || "";
	return Array.from(name.matchAll(/\[([^\]]+)\]/g)).map((match) => {
		return match[1] || "";
	}).filter(Boolean);
}
function matchesSelectorPath(input, selector) {
	if (!selector) return true;
	const selectorTokens = selector.split(/[.:]/).filter(Boolean);
	if (!selectorTokens.length) return true;
	const inputTokens = getInputNameTokens(input);
	if (inputTokens.length < selectorTokens.length) return false;
	return selectorTokens.every((token, index) => {
		return inputTokens[inputTokens.length - selectorTokens.length + index] === token;
	});
}
function filterInputsBySelector(inputs, selector) {
	if (!selector) return inputs;
	const matchedInputs = inputs.filter((input) => {
		return matchesSelectorPath(input, selector);
	});
	return matchedInputs.length ? matchedInputs : inputs;
}
function preferSameRow(targetNode, candidates) {
	const targetRow = getRowScope(targetNode);
	if (!targetRow) return candidates;
	const sameRowCandidates = candidates.filter((candidate) => {
		return getRowScope(candidate) === targetRow;
	});
	return sameRowCandidates.length ? sameRowCandidates : candidates;
}
function resolveConditionSource(condition) {
	if (condition.source?.target === "field" && condition.source.handle) return condition.source;
	return null;
}
function queryConditionInputs(root, targetNode, condition) {
	const source = resolveConditionSource(condition);
	if (!source || source.target !== "field" || !source.handle) return [];
	const escapedFieldHandle = escapeSelectorValue(source.handle);
	const fieldMatches = Array.from(root.querySelectorAll(`[data-formie-field-handle="${escapedFieldHandle}"]`));
	if (fieldMatches.length) return preferSameRow(targetNode, fieldMatches).flatMap((fieldNode) => {
		return filterInputsBySelector(getFieldInputs(fieldNode), source.selector);
	});
	const exactName = escapeSelectorValue(fieldKeyToInputName(source.handle));
	const direct = Array.from(root.querySelectorAll(`[name="${exactName}"]`)).filter((element) => {
		return isConditionInput(element);
	});
	const multi = Array.from(root.querySelectorAll(`[name="${exactName}[]"]`)).filter((element) => {
		return isConditionInput(element);
	});
	if (direct.length || multi.length) return preferSameRow(targetNode, [...direct, ...multi]);
	if (!source.handle.includes("__ROW__")) return [];
	const rowToken = getNodeRowToken(targetNode);
	if (rowToken) {
		const escapedRowFieldName = escapeSelectorValue(fieldKeyToInputName(source.handle.replace(/__ROW__/g, rowToken)));
		const rowDirect = Array.from(root.querySelectorAll(`[name="${escapedRowFieldName}"]`)).filter((element) => {
			return isConditionInput(element);
		});
		const rowMulti = Array.from(root.querySelectorAll(`[name="${escapedRowFieldName}[]"]`)).filter((element) => {
			return isConditionInput(element);
		});
		if (rowDirect.length || rowMulti.length) return [...rowDirect, ...rowMulti];
	}
	const regexString = fieldKeyToInputName(source.handle).replace(/[.*+?^${}()|[\]\\]/g, "\\$&").replace(/__ROW__/g, "\\d+");
	const regex = new RegExp(regexString);
	return Array.from(root.querySelectorAll("[name]")).filter((element) => {
		return isConditionInput(element) && regex.test(element.getAttribute("name") || "");
	});
}
//#endregion
//#region src/js/modules/fields/conditions/transforms.ts
function stringifyValue(value) {
	if (value == null) return "";
	return String(value);
}
function toBoolean(value) {
	if (typeof value === "boolean") return value;
	if (typeof value === "number") return value !== 0;
	const normalized = stringifyValue(value).trim().toLowerCase();
	if (!normalized || [
		"0",
		"false",
		"no",
		"off"
	].includes(normalized)) return false;
	return true;
}
function toTitleCase(value) {
	return value.toLowerCase().replace(/\b\w/g, (match) => {
		return match.toUpperCase();
	});
}
function formatNumber(value, params) {
	const decimals = Number.isFinite(Number(params.decimals)) ? Number(params.decimals) : 0;
	const decimalPoint = params.decimalPoint ?? ".";
	const thousandsSeparator = params.thousandsSeparator ?? ",";
	const [integerPart, decimalPart = ""] = value.toFixed(decimals).split(".");
	const groupedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
	if (decimals === 0) return groupedInteger;
	return `${groupedInteger}${decimalPoint}${decimalPart}`;
}
function pad(value) {
	return String(value).padStart(2, "0");
}
function formatDate(value, pattern) {
	return [
		["Y", String(value.getFullYear())],
		["m", pad(value.getMonth() + 1)],
		["d", pad(value.getDate())],
		["j", String(value.getDate())],
		["H", pad(value.getHours())],
		["h", pad((value.getHours() + 11) % 12 + 1)],
		["i", pad(value.getMinutes())],
		["A", value.getHours() >= 12 ? "PM" : "AM"],
		["F", value.toLocaleString(void 0, { month: "long" })]
	].reduce((formatted, [token, replacement]) => {
		return formatted.replaceAll(token, replacement);
	}, pattern);
}
function resolveDateFormatPattern(preset) {
	switch (preset) {
		case "datetimeUs12": return "m/d/Y h:i A";
		case "datetimeEu12": return "d/m/Y h:i A";
		case "datetimeEu24": return "d/m/Y H:i";
		case "datetimeIso24": return "Y-m-d H:i";
		case "dateUs": return "m/d/Y";
		case "dateEu": return "d/m/Y";
		case "isoDate": return "Y-m-d";
		case "dateLong": return "F j, Y";
		case "time12": return "h:i A";
		case "time24": return "H:i";
		default: return "";
	}
}
function applyTransformer(value, source) {
	const transformerId = source.transformerId;
	const params = source.transformerParams;
	switch (transformerId) {
		case "round":
		case "floor":
		case "ceil": {
			const number = Number(value);
			if (!Number.isFinite(number)) return value;
			if (transformerId === "round") return String(Math.round(number));
			if (transformerId === "floor") return String(Math.floor(number));
			return String(Math.ceil(number));
		}
		case "format": {
			const number = Number(value);
			if (Number.isFinite(number) && value.trim() !== "") return formatNumber(number, params);
			const preset = params.preset || "";
			const pattern = preset === "custom" ? params.pattern || "" : resolveDateFormatPattern(preset);
			if (!pattern) return value;
			const date = new Date(value);
			if (Number.isNaN(date.getTime())) return value;
			return formatDate(date, pattern);
		}
		case "lower": return value.toLowerCase();
		case "upper": return value.toUpperCase();
		case "title": return toTitleCase(value);
		case "capitalize": return value ? value.charAt(0).toUpperCase() + value.slice(1) : value;
		case "replace": {
			const search = params.search || "";
			if (!search) return value;
			return value.split(search).join(params.replace || "");
		}
		case "truncate": {
			const length = Math.max(1, Number.parseInt(params.length || "50", 10) || 50);
			const suffix = params.suffix || "...";
			if (value.length <= length) return value;
			return `${value.slice(0, Math.max(0, length - suffix.length))}${suffix}`;
		}
		case "map": return toBoolean(value) ? params.trueLabel || "Yes" : params.falseLabel || "No";
		default: return value;
	}
}
function applyConditionSource(values, source) {
	if (!source) return values;
	const transformedValues = source.transformerId ? values.map((value) => {
		return applyTransformer(value, source);
	}) : values;
	if ((transformedValues.length === 0 || transformedValues.every((value) => {
		return value.trim() === "";
	})) && source.defaultValue) return [source.defaultValue];
	return transformedValues;
}
//#endregion
//#region src/js/modules/fields/conditions/values.ts
function getInputKey(input, index) {
	return input.name || `__condition_input_${index}`;
}
function getInputLabel(input) {
	const explicitLabel = input.id ? input.ownerDocument.querySelector(`label[for="${input.id}"]`)?.textContent?.trim() : "";
	if (explicitLabel) return explicitLabel;
	return input.closest("label")?.textContent?.trim() || "";
}
function readInputGroupValues(inputs, selector = "") {
	const firstInput = inputs[0];
	if (!firstInput) return [];
	if (firstInput instanceof HTMLInputElement) {
		if (firstInput.type === "checkbox") {
			const checkedInputs = inputs.filter((input) => {
				return input instanceof HTMLInputElement && input.checked;
			});
			if (selector === "label") return checkedInputs.map((input) => {
				return getInputLabel(input);
			}).filter(Boolean);
			return checkedInputs.map((input) => {
				return input.value;
			});
		}
		if (firstInput.type === "radio") {
			const checkedInputs = inputs.filter((input) => {
				return input instanceof HTMLInputElement && input.checked;
			});
			if (selector === "label") return checkedInputs.map((input) => {
				return getInputLabel(input);
			}).filter(Boolean);
			return checkedInputs.map((input) => {
				return input.value;
			});
		}
		if (firstInput.type === "file") return Array.from(firstInput.files || []).map((file) => {
			return file.name;
		});
	}
	if (firstInput instanceof HTMLSelectElement && firstInput.multiple) {
		if (selector === "label") return Array.from(firstInput.selectedOptions).map((option) => {
			return option.label || option.text;
		});
		return Array.from(firstInput.selectedOptions).map((option) => {
			return option.value;
		});
	}
	if (firstInput instanceof HTMLSelectElement && selector === "label") return Array.from(firstInput.selectedOptions).map((option) => {
		return option.label || option.text;
	});
	return inputs.map((input) => {
		return input.value;
	});
}
function getConditionInputEventNames(_input) {
	return ["input", "change"];
}
function readConditionValues(inputs, source = null) {
	const groupedInputs = /* @__PURE__ */ new Map();
	inputs.forEach((input, index) => {
		const key = getInputKey(input, index);
		const existing = groupedInputs.get(key) || [];
		existing.push(input);
		groupedInputs.set(key, existing);
	});
	return applyConditionSource(Array.from(groupedInputs.values()).flatMap((group) => {
		return readInputGroupValues(group, source?.selector || "");
	}), source);
}
//#endregion
//#region src/js/modules/fields/conditions/evaluator.ts
function isInputVisible(input) {
	if (input.closest("[data-formie-conditionally-hidden]") || input.closest("[data-formie-page-hidden]") || input.closest("[hidden]") || input.closest("[aria-hidden=\"true\"]")) return false;
	return !!(input.offsetWidth || input.offsetHeight || input.getClientRects().length);
}
function getConditionVisibility(inputs) {
	if (!inputs.length) return null;
	return inputs.some((input) => {
		return isInputVisible(input);
	});
}
function evaluateConditionSettings(settings, getConditionInputs) {
	return g(settings, settings.conditions.map((condition) => {
		const inputs = getConditionInputs(condition);
		return h(condition, readConditionValues(inputs, resolveConditionSource(condition)), { visibility: getConditionVisibility(inputs) });
	}));
}
//#endregion
//#region src/js/modules/fields/conditions.ts
var MAX_EVALUATION_PASSES = 4;
var debug = createDebug("conditions");
function uniqueConditionInputs(inputs) {
	const seenInputs = /* @__PURE__ */ new Set();
	return inputs.filter((input) => {
		if (seenInputs.has(input)) return false;
		seenInputs.add(input);
		return true;
	});
}
var conditionsModule = {
	id: "conditions",
	kind: "field",
	match: (ctx) => {
		return ctx.target instanceof HTMLElement && (ctx.target.matches("[data-formie-conditions]") || !!ctx.target.querySelector("[data-formie-conditions]"));
	},
	setup: async (ctx) => {
		const scopeRoot = ctx.target instanceof HTMLElement ? ctx.target : ctx.root;
		if (!getConditionNodes(scopeRoot).length) {
			debug.log("No condition nodes in scope.");
			return;
		}
		const sourceUnbinds = [];
		let entries = [];
		let evaluationQueued = false;
		let rebuildQueued = false;
		const cleanupSourceBindings = () => {
			sourceUnbinds.forEach((unbind) => {
				unbind();
			});
			sourceUnbinds.length = 0;
		};
		const buildEntries = () => {
			return getConditionNodes(scopeRoot).flatMap((node) => {
				const settings = parseConditionSettings(node);
				if (!settings || !settings.conditions.length) return [];
				return [{
					node,
					settings,
					sourceInputs: uniqueConditionInputs(settings.conditions.flatMap((condition) => {
						return queryConditionInputs(scopeRoot, node, condition);
					}))
				}];
			});
		};
		const runEvaluationPass = () => {
			let hasStateChanges = false;
			entries.forEach((entry) => {
				const result = evaluateConditionSettings(entry.settings, (condition) => {
					return queryConditionInputs(scopeRoot, entry.node, condition);
				});
				const stateChanged = applyConditionVisibility(entry.node, result.shouldHide, entry.settings.clearOnHide);
				hasStateChanges = hasStateChanges || stateChanged;
				debug.log("Condition evaluated.", {
					shouldHide: result.shouldHide,
					finalResult: result.finalResult,
					stateChanged
				});
				ctx.emit("formie:conditions:evaluated", {
					node: entry.node,
					shouldHide: result.shouldHide,
					finalResult: result.finalResult,
					clearOnHide: entry.settings.clearOnHide
				});
			});
			return hasStateChanges;
		};
		const evaluateAll = () => {
			for (let pass = 0; pass < MAX_EVALUATION_PASSES; pass += 1) {
				if (!runEvaluationPass()) break;
				if (pass === MAX_EVALUATION_PASSES - 1) debug.warn("Reached max evaluation passes.", { maxPasses: MAX_EVALUATION_PASSES });
			}
		};
		const scheduleEvaluateAll = () => {
			if (evaluationQueued) return;
			evaluationQueued = true;
			queueMicrotask(() => {
				evaluationQueued = false;
				evaluateAll();
			});
		};
		const bindSourceInputs = () => {
			uniqueConditionInputs(entries.flatMap((entry) => {
				return entry.sourceInputs;
			})).forEach((input) => {
				const handler = () => {
					scheduleEvaluateAll();
				};
				getConditionInputEventNames(input).forEach((eventName) => {
					input.addEventListener(eventName, handler);
				});
				sourceUnbinds.push(() => {
					getConditionInputEventNames(input).forEach((eventName) => {
						input.removeEventListener(eventName, handler);
					});
				});
			});
			if (ctx.form) {
				const resetHandler = () => {
					window.setTimeout(() => {
						scheduleEvaluateAll();
					}, 0);
				};
				ctx.form.addEventListener("reset", resetHandler);
				sourceUnbinds.push(() => {
					ctx.form?.removeEventListener("reset", resetHandler);
				});
			}
		};
		const rebuild = () => {
			cleanupSourceBindings();
			entries = buildEntries();
			bindSourceInputs();
			debug.log("Rebuilt condition graph.", { entryCount: entries.length });
			scheduleEvaluateAll();
		};
		const scheduleRebuild = () => {
			if (rebuildQueued) return;
			rebuildQueued = true;
			queueMicrotask(() => {
				rebuildQueued = false;
				rebuild();
			});
		};
		const observer = new MutationObserver((mutations) => {
			const shouldRebuild = mutations.some((mutation) => {
				return mutation.type === "childList" && (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0);
			});
			const shouldEvaluate = mutations.some((mutation) => {
				return mutation.type === "attributes";
			});
			if (shouldRebuild) scheduleRebuild();
			else if (shouldEvaluate) scheduleEvaluateAll();
		});
		observer.observe(scopeRoot, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: [
				"class",
				"style",
				"hidden",
				"aria-hidden",
				"data-formie-conditionally-hidden",
				"data-formie-page-hidden",
				"data-formie-row-hidden"
			]
		});
		rebuild();
		await ctx.emit("formie:module:conditions:init", { count: entries.length });
		debug.log("Module setup complete.", { entryCount: entries.length });
		return { destroy: () => {
			cleanupSourceBindings();
			observer.disconnect();
			debug.log("Module destroy.");
			ctx.emit("formie:module:conditions:destroy", {});
		} };
	}
};
//#endregion
export { conditionsModule };
