# Headless
"Headless" website architectures are now more popular than ever, and it's a breeze to use Formie for your Nuxt, Next.js, Gatsby or Gridsome project.

For those unfamiliar with the term, "headless" website architecture is the separation of front-end and back-end, rather than the traditional approach of entirely one or the other. In Craft CMS, typically you would write your template code in Twig, which is rendered server-side, and served by your web server and Craft. There's nothing wrong with this approach, but the rise in popularity of JavaScript-based front-end frameworks has driven considerable interest in doing more things client-side. Simply put, it's a JavaScript-based front-end framework taking 100% control over everything front-end, and communication to Craft to get content and data is done through GraphQL or a similar API. Be sure to [read further](https://nystudio107.com/blog/using-the-craft-cms-graphql-api-on-the-frontend) or subscribe to the [CraftQuest](https://craftquest.io/courses/headless-craft) course.

Formie supports the [GraphQL](docs:developers/graphql) query language through Craft. This allows you to fetch form, page, fields and settings from Craft to use in your framework. You can also use mutations to create submissions via GraphQL, rather than the traditional POST request to the server.

:::tip
Have a look at our [headless Formie demo](https://formie-headless.verbb.io/?form=contactForm) to get a feel for what's possible. The demo is fully open sourced, so feel free to look at [how it's built](https://github.com/verbb/formie-headless).
:::
