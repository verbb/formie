# vue-accessible-tabs

## Usage

```vue
<Tabs>
  <TabList>
    <Tab>Give</Tab>
    <Tab>
      <span class="font-bold">You</span>
    </Tab>
    <Tab>Up</Tab>
  </TabList>

  <TabPanels>
    <TabPanel>
      <div>Panel #1</div>
    </TabPanel>
    <TabPanel>
      <div>Panel #2</div>
    </TabPanel>
    <TabPanel>
      <div>Panel #3</div>
    </TabPanel>
  </TabPanels>
</Tabs>
```

## Installation

```bash
# yarn
yarn add vue-accessible-tabs

# npm
npm install vue-accessible-tabs
```

## Using the Components

### Importing

```js
import { Tabs, Tab, TabList, TabPanels, TabPanel } from 'vue-accessible-tabs'
```

### Global Registration

```js
import { Tabs, Tab, TabList, TabPanels, TabPanel } from 'vue-accessible-tabs'

// Globally register
Vue.component('Tabs', Tabs)
Vue.component('Tab', Tab)
Vue.component('TabList', TabList)
Vue.component('TabPanels', TabPanels)
Vue.component('TabPanel', TabPanel)
```

## Styling

This library provides no default styling, it's totally BYOS. The good news is: each component only renders one element, so you can style it by simply adding classes. This means it'll play nice with pretty much any approach to styling.

## Roadmap

- Document <Tab> APIs
- Add examples
  - How to style
  - Animating `<TabPanel>` in/out
- Document keyboard behavior

## FAQ

**Q**: How do I use this on a site where I'm not bundling my code?<br/>
**A**: You can't, yet. I'll get around to bundling, sometime, maybe, tho I might not because ESM rules and I hate bundling...

**Q**: Can I pass HTML into the slots of these components?<br/>
**A**: Yes, anything is fair game **except** you can _only_ pass `<Tab>` elements to `<TabList>`, and you can only pass `<TabPanel>` elements to `<TabPanels>`.
