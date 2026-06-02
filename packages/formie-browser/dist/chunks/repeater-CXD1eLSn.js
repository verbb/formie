import { t as createDebug } from "./debug-KnZeKYBI.js";
import { n as sleep } from "./async-B3DUf1GZ.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { a as getTemplateSource, o as getTemplateSourceHtml, t as dispatchFieldEvent } from "./shared-DC6_1u8X.js";
//#region src/css/theme/fields/_repeater.css?inline
var _repeater_default = "@layer formie-theme{.formie-repeater-container{gap:var(--formie-space-4);display:grid}.formie-repeater-item-wrapper{gap:var(--formie-space-4);padding:var(--formie-space-4);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-md);transition:border-color .15s,box-shadow .15s,background-color .15s;display:grid;position:relative}.formie-repeater-item-wrapper:focus-within{border-color:var(--formie-focus-ring-border-color);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error .formie-repeater-item-wrapper{border-color:var(--formie-color-danger)}.formie-field-has-error .formie-repeater-item-wrapper:focus-within{box-shadow:var(--formie-shadow-danger-focus)}.formie-repeater-item-wrapper>.formie-repeater-remove-button{top:var(--formie-repeater-remove-button-top);right:var(--formie-repeater-remove-button-right);transform:var(--formie-repeater-remove-button-transform);font-size:0;line-height:0;position:absolute}.formie-button.formie-repeater-add-button{width:auto;max-width:100%;padding-left:var(--formie-repeater-add-button-padding-left);justify-content:center;justify-self:start;align-items:center;display:inline-flex;position:relative}.formie-button.formie-repeater-add-button:before{content:\"\";width:var(--formie-repeater-add-button-width);height:var(--formie-repeater-add-button-height);left:var(--formie-repeater-add-button-left);-webkit-mask-image:var(--formie-repeater-add-button-icon-mask);-webkit-mask-image:var(--formie-repeater-add-button-icon-mask);mask-image:var(--formie-repeater-add-button-icon-mask);background-color:currentColor;display:block;position:absolute;top:50%;transform:translateY(-50%);-webkit-mask-position:50%;mask-position:50%;-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat}}";
//#endregion
//#region src/js/modules/fields/repeater.ts
var FIELD_SELECTOR = "[data-formie-repeater-field-layout]";
var CONTAINER_SELECTOR = "[data-formie-repeater-container]";
var ROW_SELECTOR = "[data-formie-repeater-item]";
var ADD_SELECTOR = "[data-formie-repeater-add]";
var REMOVE_SELECTOR = "[data-formie-repeater-remove]";
var TEMPLATE_ID_ATTR = "data-formie-template-id";
var MODULE_ID = "repeater";
var debug = createDebug("fields", "repeater");
ensureModuleStyles(MODULE_ID, [_repeater_default]);
function getTemplate(field, templateId) {
	return getTemplateSource(field, templateId);
}
function buildRowFromTemplate(templateHtml, rowId) {
	const wrapper = document.createElement("div");
	wrapper.innerHTML = templateHtml.replaceAll("__ROW__", String(rowId)).trim();
	return wrapper.firstElementChild instanceof HTMLElement ? wrapper.firstElementChild : null;
}
function getRowCount(field) {
	return field.querySelectorAll(ROW_SELECTOR).length;
}
function syncAddButton(addButton, rowCount) {
	if (!addButton) return;
	const maxRows = parseInt(addButton.getAttribute("data-formie-max-rows") || "", 10);
	if (maxRows > 0 && rowCount >= maxRows) {
		addButton.disabled = true;
		return;
	}
	addButton.disabled = false;
}
function bindRepeaterField(field) {
	const container = field.matches(CONTAINER_SELECTOR) ? field : field.querySelector(CONTAINER_SELECTOR);
	const addButton = field.querySelector(ADD_SELECTOR);
	if (!(container instanceof HTMLElement)) {
		debug.warn("Missing repeater container; skipping field.");
		return () => {};
	}
	const removeHandlers = /* @__PURE__ */ new Map();
	let rowCounter = Array.from(field.querySelectorAll(ROW_SELECTOR)).reduce((max, row) => {
		const current = parseInt(row.getAttribute("data-formie-repeater-item-id") || "", 10);
		return Number.isNaN(current) ? max : Math.max(max, current + 1);
	}, 0);
	const bindRemoveButtons = () => {
		field.querySelectorAll(REMOVE_SELECTOR).forEach((button) => {
			if (!(button instanceof HTMLElement) || removeHandlers.has(button)) return;
			const handler = (event) => {
				event.preventDefault();
				const row = button.closest(ROW_SELECTOR);
				if (!(row instanceof HTMLElement)) return;
				const minRows = parseInt((addButton instanceof HTMLButtonElement ? addButton.getAttribute("data-formie-min-rows") : "") || "", 10);
				if (minRows > 0 && getRowCount(field) <= minRows) return;
				row.remove();
				syncAddButton(addButton instanceof HTMLButtonElement ? addButton : null, getRowCount(field));
				debug.log("Row removed.", { rowCount: getRowCount(field) });
				dispatchFieldEvent(field, MODULE_ID, "remove", {
					repeater: field,
					row
				});
			};
			button.addEventListener("click", handler);
			removeHandlers.set(button, handler);
		});
	};
	const addRow = async () => {
		if (!(addButton instanceof HTMLButtonElement)) return;
		const handle = addButton.getAttribute("data-formie-repeater-add");
		if (!handle) {
			debug.warn("Add handle missing.");
			return;
		}
		const templateId = addButton.getAttribute(TEMPLATE_ID_ATTR) || field.getAttribute(TEMPLATE_ID_ATTR);
		const maxRows = parseInt(addButton.getAttribute("data-formie-max-rows") || "", 10);
		if (maxRows > 0 && getRowCount(field) >= maxRows) return;
		const template = getTemplate(field, templateId);
		if (!template) {
			debug.warn("Template not found for add action.", { handle });
			return;
		}
		const row = buildRowFromTemplate(getTemplateSourceHtml(template), rowCounter++);
		if (!row) {
			debug.warn("Failed to build row from template.");
			return;
		}
		container.appendChild(row);
		await sleep(50);
		bindRemoveButtons();
		syncAddButton(addButton, getRowCount(field));
		debug.log("Row appended.", { rowCount: getRowCount(field) });
		dispatchFieldEvent(field, MODULE_ID, "append", {
			repeater: field,
			row
		});
		dispatchFieldEvent(field, MODULE_ID, "init-row", {
			repeater: field,
			row
		});
	};
	const addHandler = (event) => {
		event.preventDefault();
		addRow();
	};
	if (addButton instanceof HTMLButtonElement) addButton.addEventListener("click", addHandler);
	bindRemoveButtons();
	syncAddButton(addButton instanceof HTMLButtonElement ? addButton : null, getRowCount(field));
	if (addButton instanceof HTMLButtonElement && getRowCount(field) === 0) {
		const minRows = parseInt(addButton.getAttribute("data-formie-min-rows") || "", 10);
		for (let index = 0; index < minRows; index += 1) addRow();
	}
	dispatchFieldEvent(field, MODULE_ID, "init", { repeater: field });
	debug.log("Field initialized.", { rowCount: getRowCount(field) });
	return () => {
		if (addButton instanceof HTMLButtonElement) addButton.removeEventListener("click", addHandler);
		removeHandlers.forEach((handler, button) => {
			button.removeEventListener("click", handler);
		});
	};
}
var repeaterModule = {
	id: MODULE_ID,
	kind: "field",
	match: (ctx) => {
		return ctx.target instanceof HTMLElement && (ctx.target.matches(FIELD_SELECTOR) || !!ctx.target.querySelector(FIELD_SELECTOR));
	},
	setup: async (ctx) => {
		if (!(ctx.target instanceof HTMLElement)) return;
		const fields = ctx.target.matches(FIELD_SELECTOR) ? [ctx.target] : Array.from(ctx.target.querySelectorAll(FIELD_SELECTOR)).filter((field) => {
			return field instanceof HTMLElement;
		});
		const destroyBindings = fields.map((field) => {
			return bindRepeaterField(field);
		});
		debug.log("Module setup.", { fieldCount: fields.length });
		await ctx.emit("formie:module:repeater:init", { count: fields.length });
		return { destroy: () => {
			destroyBindings.forEach((destroyBinding) => {
				destroyBinding();
			});
			debug.log("Module destroy.", { fieldCount: fields.length });
			ctx.emit("formie:module:repeater:destroy", {});
		} };
	}
};
//#endregion
export { repeaterModule };
