//#region src/js/utils/event-names.ts
var FORMIE_HTML_EVENT_NAMES = [
	"formie:mount:after",
	"formie:unmount:before",
	"formie:unmount:after",
	"formie:validator:ready",
	"formie:theme:applied",
	"formie:page:navigate",
	"formie:page:navigate:after",
	"formie:page:navigate:error",
	"formie:submit:before",
	"formie:submit:after",
	"formie:submit:final:before",
	"formie:submit:final:after",
	"formie:submit:result",
	"formie:client-event",
	"formie:refresh-tokens:after",
	"formie:refresh-tokens:refreshed"
];
function toDomEventName(eventName) {
	return normalizeFormieEventName(eventName);
}
function normalizeFormieEventName(eventName) {
	return eventName;
}
function getFieldModuleEventName(moduleId, name) {
	return `formie:field:${moduleId}:${name}`;
}
function getValidatorEventName(name) {
	return `formie:validator:${name}`;
}
function getAddressProviderEventName(providerId, name) {
	return `formie:address:${providerId}:${name}`;
}
function getFileUploadEventName(name) {
	return `formie:file-upload:${name}`;
}
function getPaymentProviderActionEventName(providerId, action) {
	return `formie:payment:${providerId}:${action}`;
}
function getFormStateEventName(name) {
	return `formie:state:${name}`;
}
function getScopedModuleLifecycleEventName(moduleId, phase) {
	return `formie:module:${moduleId}:${phase}`;
}
function getGlobalModuleLifecycleEventName(phase) {
	return `formie:module:${phase}`;
}
//#endregion
export { getFormStateEventName as a, getScopedModuleLifecycleEventName as c, toDomEventName as d, getFileUploadEventName as i, getValidatorEventName as l, getAddressProviderEventName as n, getGlobalModuleLifecycleEventName as o, getFieldModuleEventName as r, getPaymentProviderActionEventName as s, FORMIE_HTML_EVENT_NAMES as t, normalizeFormieEventName as u };
