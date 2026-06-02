# Frontend Assets

Formie handles a lot for you automatically on the front end.

When you render a form normally, Formie outputs the assets it needs so the form can look right and behave properly in the browser. That includes things like validation, conditions, multi-page navigation, loading states, captchas, file uploads, payment flows, and token refresh when the form needs it.

That automatic output also gives you access to Formie's browser-side behavior. Formie emits its startup script, page-level startup settings, and the inline JSON translations seed for front-end messages, so you can listen for events, respond to submission and page changes, add your own validation, build custom modules, or include the browser package in your own bundle when you need more control.

In many projects, this default setup is all you need. When you want to go further, the browser and framework packages are there to build on top of it.

## Asset Output

Using a Form Template or render options, you can change where those assets are output, or switch to manual output and include them yourself. That can be useful when you need tighter control over your page output or build pipeline.

Leaving out Formie's assets entirely is usually a bad idea unless you are deliberately replacing that setup with your own browser-side integration. Without them, a form can lose the behavior it relies on in the browser.

If you need to control asset output yourself, see:

- [Render Options](/templates/render-options)
- [Assets](/templates/assets)

## Browser Package
Formie's assets are part of the `@verbb/formie-browser` package available on npm. This essentially combines the CSS and JavaScript Formie needs to make your forms look and work great.

Because Formie's startup is handled for you by default, you can hook into it for all sorts of common tasks.

For example, you might want to listen for a page change or a completed final submit:

```js
const form = document.querySelector('#formie-form');

form?.addEventListener('formie:page:navigate:after', (event) => {
  console.log('Page changed:', event.detail);
});

form?.addEventListener('formie:submit:final:after', (event) => {
  console.log('Final submit finished:', event.detail);
});
```

Or, you could register your own validation:

```js
document.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;

  validator.addValidator(
    'company-email',
    ({ input, getRule }) => {
      const rule = getRule('company-email');

      if (!rule || !input.value) {
        return true;
      }

      const domain = typeof rule === 'object' && typeof rule.domain === 'string'
        ? rule.domain
        : 'example.com';

      return input.value.toLowerCase().endsWith(`@${domain.toLowerCase()}`);
    },
    ({ label, getRule }) => {
      const rule = getRule('company-email');
      const domain = typeof rule === 'object' && typeof rule.domain === 'string'
        ? rule.domain
        : 'example.com';

      return `${label} must use an @${domain} address.`;
    },
  );
});
```

That sort of extension is useful when you want to adjust behavior without replacing Formie's rendered markup.

Read more in the [Browser package docs](https://packages.formie.test/browser/).

### Styling a Form
While you can use things like Theme Config to easily change the appearance of a form by removing default classes or adding your own, another alternative is to alter the CSS directly without touching theme config.

For example, you might like to change the button colours to match your site's brand.

```css
.formie-form {
  --formie-color-primary: #0f766e;
  --formie-color-primary-hover: #115e59;
  --formie-button-primary-background: var(--formie-color-primary);
  --formie-button-primary-background-hover: var(--formie-color-primary-hover);
  --formie-button-primary-text-color: #ffffff;
}
```

This is often the lightest way to restyle the default output when you are happy with the markup and just want the form to better match your site.

You can find the full variable list in the [CSS variables docs](https://packages.formie.test/browser/ui-reference/css-variables).

### BYO Bundle
If you want to include Formie's browser package in your own bundle, you can disable Formie's automatic per-form startup by creating your own Form template, or using the [Render Options](/templates/render-options) to control that.

Then, it's a matter of installing the `@verbb/formie-browser` package in your own build system and going from there.

For plugin-rendered pages, Formie also seeds front-end translations for you. In headless or fully custom bundle setups, your application should own translations explicitly instead of expecting Craft to preload them.

Start with the [Browser package docs](https://packages.formie.test/browser/).

## Frameworks
We also provide support for popular frontend frameworks to make integration even easier, whether Craft is server-rendering the form HTML for you, or you are loading and submitting forms through REST or GraphQL.

These packages generally give you two approaches:

- server-rendered forms, where Formie still owns the rendered HTML and browser behavior
- client-rendered forms, where your framework takes over the rendered UI while Formie still provides the form definition and submission flow

### React
The `@verbb/formie-react` package gives you a more natural React developer experience, whether you want a quick HTML-based setup or a more component-driven approach.

Check out the [React starter](https://verbb.io/craft-plugins/formie/examples/react) project to see it in action, and read further in the [React package docs](https://packages.formie.test/react/).

### Vue
The `@verbb/formie-vue` package provides the same two-path approach for Vue, with server-rendered forms for a quicker setup and client-rendered forms for more control.

Check out the [Vue starter](https://verbb.io/craft-plugins/formie/examples/vue) project to see it in action, and read further in the [Vue package docs](https://packages.formie.test/vue/).

### Web Components
The `@verbb/formie-web-components` package is useful when you want portable custom elements, or when your front end is not centered around React or Vue.

Check out the [Web Components starter](https://verbb.io/craft-plugins/formie/examples/web-components) project to see it in action, and read further in the [Web Components package docs](https://packages.formie.test/web-components/).

### Next.js

Use the [Next.js starter](https://verbb.io/craft-plugins/formie/examples/next) as the starting point, then refer to the [React package docs](https://packages.formie.test/react/) for the package itself.

### Nuxt

Use the [Nuxt starter](https://verbb.io/craft-plugins/formie/examples/nuxt) as the starting point, then refer to the [Vue package docs](https://packages.formie.test/vue/) for the package itself.

## Examples
While we don't have dedicated packages to support these solutions, we've put together some example pages.

### Barba.js
When using [barba.js](https://barba.js.org/) for a SPA-like application with page transitions, you'll want to have fine-grained control over when Formie forms initialize. Particularly when navigating to and from a page where a Formie form exists.

Have a look at our [Barba.js example](https://verbb.io/craft-plugins/formie/examples/barba).

### Sprig
If you are using [Sprig](https://putyourlightson.com/plugins/sprig), Formie can work well inside reactive Twig components, but you will usually want to pay attention to when forms are re-rendered and when Formie's browser behavior needs to initialize again.

Read more on the [Sprig plugin page](https://putyourlightson.com/plugins/sprig) and have a look at our [Sprig example](https://verbb.io/craft-plugins/formie/examples/sprig).

### Datastar
If you are using [Datastar](https://putyourlightson.com/plugins/datastar), Formie can fit into a Twig-driven reactive frontend without needing a larger JavaScript framework, but the same considerations apply around re-rendering and front-end initialization.

Read more on the [Datastar plugin page](https://putyourlightson.com/plugins/datastar) and have a look at our [Datastar example](https://verbb.io/craft-plugins/formie/examples/datastar).
