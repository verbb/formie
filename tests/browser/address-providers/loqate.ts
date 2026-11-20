import type { Page } from '@playwright/test';
import type { AddressProvider } from './types';

// A stand-in for Loqate's PCA library. Like the real one, it finds each field by element ID or input name (not CSS
// selectors), searches as the visitor types, shows suggestions in a list on the page body, and on a click retrieves
// the address and fills the bound fields itself. Find and Retrieve go out as JSONP calls that the routes answer.
const FAKE_PCA = `
(function () {
    var BASE = 'https://services.pcapredict.com/Capture/Interactive/';

    function getElement(reference) {
        if (!reference) return null;
        if (reference.nodeType) return reference;
        return document.getElementById(reference) || document.getElementsByName(reference)[0] || null;
    }

    function jsonp(url) {
        var script = document.createElement('script');
        script.src = url;
        document.head.appendChild(script);
    }

    // With simulateReactEvents, PCA writes through the native setter and fires the events a framework listens to.
    function setValue(element, value) {
        var proto = element instanceof HTMLSelectElement ? HTMLSelectElement.prototype : HTMLInputElement.prototype;
        Object.getOwnPropertyDescriptor(proto, 'value').set.call(element, value);
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setCountry(element, address) {
        if (!(element instanceof HTMLSelectElement)) return setValue(element, address.CountryName);
        var wanted = [address.CountryIso2, address.CountryIso3, address.CountryName].map(function (v) { return String(v).toLowerCase(); });
        for (var i = 0; i < element.options.length; i++) {
            var option = element.options[i];
            if (wanted.indexOf(option.value.toLowerCase()) !== -1 || wanted.indexOf(option.text.trim().toLowerCase()) !== -1) {
                return setValue(element, option.value);
            }
        }
    }

    function Address(fields, options) {
        this.fields = fields;
        this.options = options;
        this.loaded = false;
        window.pca.__controls.push(this);
    }

    Address.prototype.load = function () {
        if (this.loaded) return;
        this.loaded = true;
        var control = this;
        var search = null;

        this.fields.forEach(function (field) {
            if (field.mode === window.pca.fieldMode.SEARCH) search = getElement(field.element);
        });

        if (!search) return;

        var list = document.createElement('div');
        list.className = 'pca pcaautocomplete pcatext';
        list.style.display = 'none';
        document.body.appendChild(list);

        window.pca.__found = function (response) {
            list.innerHTML = '';
            (response.Items || []).forEach(function (item) {
                var row = document.createElement('div');
                row.className = 'pcaitem';
                row.textContent = item.Text + ' ' + item.Description;
                row.addEventListener('click', function () {
                    list.style.display = 'none';
                    jsonp(BASE + 'Retrieve/v1.2/json3ex.ws?Key=' + encodeURIComponent(control.options.key) + '&Id=' + encodeURIComponent(item.Id) + '&callback=pca.__retrieved');
                });
                list.appendChild(row);
            });
            list.style.display = response.Items && response.Items.length ? 'block' : 'none';
        };

        window.pca.__retrieved = function (response) {
            var address = (response.Items || [])[0];
            if (!address) return;
            control.fields.forEach(function (field) {
                var element = getElement(field.element);
                if (!element) return;
                if (field.mode === window.pca.fieldMode.POPULATE) setValue(element, address[field.field] || '');
                if (field.mode === window.pca.fieldMode.COUNTRY) setCountry(element, address);
            });
        };

        search.addEventListener('input', function () {
            if (!search.value) return;
            jsonp(BASE + 'Find/v1.1/json3ex.ws?Key=' + encodeURIComponent(control.options.key) + '&Text=' + encodeURIComponent(search.value) + '&callback=pca.__found');
        });
    };

    window.pca = { fieldMode: { DEFAULT: 3, SEARCH: 1, POPULATE: 2, PRESERVE: 4, SHOW: 8, COUNTRY: 16 }, Address: Address, __controls: [] };
})();`;

const FOUND = {
    Items: [{ Id: 'AU|PARITY|10SMITH', Type: 'Address', Text: '10 Smith St', Highlight: '', Description: 'Collingwood VIC 3066' }],
};

const RETRIEVED = {
    Items: [{
        Id: 'AU|PARITY|10SMITH',
        Line1: '10 Smith St',
        Line2: '',
        Line3: '',
        City: 'Collingwood',
        Province: 'VIC',
        ProvinceCode: 'VIC',
        PostalCode: '3066',
        CountryName: 'Australia',
        CountryIso2: 'AU',
        CountryIso3: 'AUS',
        Label: '10 Smith St\nCOLLINGWOOD VIC 3066\nAUSTRALIA',
    }],
};

// The visitor types part of the address and clicks the suggestion. Waiting for it is bounded, so a surface where
// PCA can't bind the search input is recorded as an unfilled address rather than a timeout.
async function chooseSuggestion(page: Page) {
    const suggestion = page.locator('.pcaautocomplete .pcaitem', { hasText: '10 Smith St' }).first();

    try {
        await suggestion.waitFor({ state: 'visible', timeout: 5000 });
    } catch {
        return;
    }

    await suggestion.click();
    await page.waitForTimeout(500);
}

export const loqate: AddressProvider = {
    name: 'Loqate',
    routes: {
        'https://services.pcapredict.com/js/**': { contentType: 'text/javascript', body: FAKE_PCA },
        'https://services.pcapredict.com/css/**': { contentType: 'text/css', body: '.pcaautocomplete { position: absolute; background: #fff; }' },
        'https://services.pcapredict.com/Capture/Interactive/Find/**': { contentType: 'text/javascript', body: `pca.__found(${JSON.stringify(FOUND)});` },
        'https://services.pcapredict.com/Capture/Interactive/Retrieve/**': { contentType: 'text/javascript', body: `pca.__retrieved(${JSON.stringify(RETRIEVED)});` },
    },
    mounted: async (_field, page) => {
        await page.waitForFunction(() => {
            const controls = (window as any).pca?.__controls ?? [];
            return controls.length > 0 && controls.every((control: any) => control.loaded);
        }, null, { timeout: 15000 });
    },
    pick: async (field, page) => {
        await field.locator('[data-formie-address-autocomplete-input]').pressSequentially('10 Smith', { delay: 20 });
        await chooseSuggestion(page);
    },
};
