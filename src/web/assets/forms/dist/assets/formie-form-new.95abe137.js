<<<<<<< HEAD:src/web/assets/forms/dist/assets/formie-form-new.95abe137.js
import{e as n,g as t,f as r}from"./config.3919d202.js";typeof Craft.Formie=="undefined"&&(Craft.Formie={});Craft.Formie.NewForm=Garnish.Base.extend({init(e){n({data(){return{name:e.name,handle:e.handle,handles:[]}},watch:{name(l){const a=e.maxFormHandleLength;this.handle=t(this.handles,r(this.name),0).substr(0,a)}},created(){this.handles=e.formHandles.concat(e.reservedHandles)},mounted(){this.$el.querySelector('[name="title"]').focus()}}).mount("#fui-new-form")}});
//# sourceMappingURL=formie-form-new.95abe137.js.map
=======
import{e as n,g as t,f as r}from"./config.c58efc49.js";typeof Craft.Formie=="undefined"&&(Craft.Formie={});Craft.Formie.NewForm=Garnish.Base.extend({init(e){n({data(){return{name:e.name,handle:e.handle,handles:[]}},watch:{name(l){const a=e.maxFormHandleLength;this.handle=t(this.handles,r(this.name),0).substr(0,a)}},created(){this.handles=e.formHandles.concat(e.reservedHandles)},mounted(){this.$el.querySelector('[name="title"]').focus()}}).mount("#fui-new-form")}});
//# sourceMappingURL=formie-form-new.2eb0f6cf.js.map
>>>>>>> 3eb744324530e520657caae17627df8c1eb87ab1:src/web/assets/forms/dist/assets/formie-form-new.2eb0f6cf.js
