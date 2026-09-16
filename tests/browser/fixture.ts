import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { createApp, h } from 'vue';
import { FormieClientForm as ReactForm } from '../../packages/formie-react/src/index';
import { FormieClientForm as VueForm } from '../../packages/formie-vue/src/index';
import { registerFormieWebComponents } from '../../packages/formie-web-components/src/index';

const adapter = new URLSearchParams(location.search).get('adapter') ?? 'react';
const source = { transport: 'rest' as const, endpoint: location.origin, formHandle: new URLSearchParams(location.search).get('form') ?? 'browserContract' };
const host = document.querySelector('#host')!;
let teardown: (() => void) | undefined;
function mount() {
    teardown?.();
    host.replaceChildren();
    if (adapter === 'react') {
        const root = createRoot(host);
        root.render(createElement(ReactForm, { source }));
        teardown = () => root.unmount();
    } else if (adapter === 'vue') {
        const app = createApp({ render: () => h(VueForm, { source }) });
        app.mount(host);
        teardown = () => app.unmount();
    } else {
        registerFormieWebComponents();
        const element = document.createElement('formie-core-form');
        element.setAttribute('endpoint', location.origin);
        element.setAttribute('form-handle', source.formHandle);
        host.append(element);
        teardown = () => element.remove();
    }
}
document.querySelector('#unmount')!.addEventListener('click', () => { teardown?.(); teardown = undefined; });
document.querySelector('#mount')!.addEventListener('click', mount);
mount();
