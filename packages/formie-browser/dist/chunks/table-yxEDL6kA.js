import { n as sleep } from "./async-B3DUf1GZ.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { a as getTemplateSource, o as getTemplateSourceHtml, t as dispatchFieldEvent } from "./shared-DC6_1u8X.js";
//#region src/css/theme/fields/_table.css?inline
var _table_default = "@layer formie-theme{.formie-table-wrapper{-webkit-overflow-scrolling:touch;max-width:100%;overflow:auto hidden}.formie-table{width:var(--formie-table-width);margin-bottom:var(--formie-table-margin-bottom);border-collapse:var(--formie-table-border-collapse)}.formie-table th{text-align:var(--formie-table-th-text-align);font-size:var(--formie-table-th-font-size);font-weight:var(--formie-table-th-font-weight);color:var(--formie-table-th-color,var(--formie-gray-500))}.formie-table th,.formie-table td{padding:var(--formie-table-row-padding);vertical-align:top}.formie-table th:first-child,.formie-table td:first-child{padding-left:0}.formie-table th:last-child,.formie-table td:last-child{padding-right:0}.formie-table [data-col-remove]{width:calc(var(--formie-button-icon-button-size) + (var(--formie-table-row-padding) * 2));min-width:calc(var(--formie-button-icon-button-size) + (var(--formie-table-row-padding) * 2));white-space:nowrap;text-align:center;vertical-align:middle}.formie-table [data-formie-table-column-type=checkbox]{text-align:center;vertical-align:middle}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option{width:100%;min-height:var(--formie-check-size);justify-content:center;align-items:center;margin:0;display:flex}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option-label{width:var(--formie-check-size);min-width:var(--formie-check-size);height:var(--formie-check-size);margin:0 auto;padding-left:0;font-size:0;line-height:0;display:block}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option-label:before{position:static}.formie-table-color-input{min-width:4rem;padding:var(--formie-space-1)}.formie-table-multiline-input{min-height:calc(var(--formie-control-height) + var(--formie-space-2))}.formie-table-remove-button{vertical-align:middle;display:inline-flex}.formie-button.formie-table-add-button{width:auto;max-width:100%;padding-left:var(--formie-table-add-button-padding-left);justify-content:center;justify-self:start;align-items:center;display:inline-flex;position:relative}.formie-button.formie-table-add-button:before{content:\"\";width:var(--formie-table-add-button-width);height:var(--formie-table-add-button-height);left:var(--formie-table-add-button-left);-webkit-mask-image:var(--formie-table-add-button-icon-mask);-webkit-mask-image:var(--formie-table-add-button-icon-mask);mask-image:var(--formie-table-add-button-icon-mask);background-color:currentColor;display:block;position:absolute;top:50%;transform:translateY(-50%);-webkit-mask-position:50%;mask-position:50%;-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat}}";
//#endregion
//#region src/js/modules/fields/table.ts
var FIELD_SELECTOR = "[data-formie-table-field-layout]";
var TABLE_SELECTOR = "[data-formie-table]";
var TABLE_BODY_SELECTOR = "[data-formie-table-body]";
var ROW_SELECTOR = "[data-formie-table-row]";
var ADD_SELECTOR = "[data-formie-table-add]";
var REMOVE_SELECTOR = "[data-formie-table-remove]";
var TEMPLATE_ID_ATTR = "data-formie-template-id";
var ROW_ID_ATTR = "data-formie-table-row-id";
var MODULE_ID = "table";
ensureModuleStyles(MODULE_ID, [_table_default]);
function getTemplate(field, templateId) {
	return getTemplateSource(field, templateId);
}
function getRowCount(field) {
	return field.querySelectorAll(ROW_SELECTOR).length;
}
function getNextRowId(field) {
	return Array.from(field.querySelectorAll(ROW_SELECTOR)).reduce((max, row) => {
		const current = parseInt(row.getAttribute(ROW_ID_ATTR) || "", 10);
		return Number.isNaN(current) ? max : Math.max(max, current + 1);
	}, 0);
}
function syncAddButton(addButton, rowCount) {
	if (!addButton) return;
	const maxRows = parseInt(addButton.getAttribute("data-formie-max-rows") || "", 10);
	addButton.disabled = maxRows > 0 && rowCount >= maxRows;
}
function bindTableField(field, options) {
	const table = field.querySelector(TABLE_SELECTOR);
	const tbody = field.querySelector(TABLE_BODY_SELECTOR);
	const addButton = field.querySelector(ADD_SELECTOR);
	if (!(table instanceof HTMLElement) || !(tbody instanceof HTMLElement)) return () => {};
	const removeHandlers = /* @__PURE__ */ new Map();
	let rowCounter = getNextRowId(field);
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
				dispatchFieldEvent(field, MODULE_ID, "remove", {
					table: field,
					row
				});
			};
			button.addEventListener("click", handler);
			removeHandlers.set(button, handler);
		});
	};
	const addRow = async () => {
		if (options.static || !(addButton instanceof HTMLButtonElement)) return;
		if (!addButton.getAttribute("data-formie-table-add")) return;
		const templateId = addButton.getAttribute(TEMPLATE_ID_ATTR) || field.getAttribute(TEMPLATE_ID_ATTR);
		const maxRows = parseInt(addButton.getAttribute("data-formie-max-rows") || "", 10);
		if (maxRows > 0 && getRowCount(field) >= maxRows) return;
		const template = getTemplate(field, templateId);
		if (!template) return;
		const html = getTemplateSourceHtml(template).replaceAll("__ROW__", String(rowCounter++));
		const row = document.createElement("tr");
		row.setAttribute("data-formie-table-row", "true");
		row.setAttribute(ROW_ID_ATTR, String(rowCounter - 1));
		row.innerHTML = html;
		tbody.appendChild(row);
		await sleep(50);
		bindRemoveButtons();
		syncAddButton(addButton, getRowCount(field));
		dispatchFieldEvent(field, MODULE_ID, "append", {
			table: field,
			row
		});
	};
	const addHandler = (event) => {
		event.preventDefault();
		addRow();
	};
	if (addButton instanceof HTMLButtonElement && !options.static) addButton.addEventListener("click", addHandler);
	bindRemoveButtons();
	syncAddButton(addButton instanceof HTMLButtonElement ? addButton : null, getRowCount(field));
	dispatchFieldEvent(field, MODULE_ID, "init", { table: field });
	return () => {
		if (addButton instanceof HTMLButtonElement) addButton.removeEventListener("click", addHandler);
		removeHandlers.forEach((handler, button) => {
			button.removeEventListener("click", handler);
		});
	};
}
var tableModule = {
	id: MODULE_ID,
	kind: "field",
	match: (ctx) => {
		return ctx.target instanceof HTMLElement && (ctx.target.matches(FIELD_SELECTOR) || !!ctx.target.querySelector(FIELD_SELECTOR));
	},
	setup: async (ctx) => {
		const options = ctx.options || {};
		if (!(ctx.target instanceof HTMLElement)) return;
		const fields = ctx.target.matches(FIELD_SELECTOR) ? [ctx.target] : Array.from(ctx.target.querySelectorAll(FIELD_SELECTOR)).filter((field) => {
			return field instanceof HTMLElement;
		});
		const destroyBindings = fields.map((field) => {
			return bindTableField(field, options);
		});
		await ctx.emit("formie:module:table:init", { count: fields.length });
		return { destroy: () => {
			destroyBindings.forEach((destroyBinding) => {
				destroyBinding();
			});
			ctx.emit("formie:module:table:destroy", {});
		} };
	}
};
//#endregion
export { tableModule };
