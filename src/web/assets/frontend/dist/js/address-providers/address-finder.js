!function(){"use strict";class t{constructor(){let t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.$form=t.$form,this.form=this.$form.form,this.$field=t.$field,this.$locationBtn=this.$field.querySelector("[data-fui-address-location-btn]"),this.loadingClass=this.form.getClasses("loading"),this.initLocationBtn()}initLocationBtn(){this.$locationBtn&&this.form.addEventListener(this.$locationBtn,function(t){let i=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null;return i||(i=Math.random().toString(36).substr(2,5)),`${t}.${i}`}("click"),(t=>{t.preventDefault(),this.onStartFetchLocation(),navigator.geolocation?navigator.geolocation.getCurrentPosition((t=>{this.onCurrentLocation(t)}),(t=>{console.log(`Unable to fetch location ${t.code}.`),this.onEndFetchLocation()}),{enableHighAccuracy:!0}):(console.log("Browser does not support geolocation."),this.onEndFetchLocation())}))}onCurrentLocation(t){this.onEndFetchLocation()}onStartFetchLocation(){var t,i;t=this.$locationBtn,i=this.loadingClass,t&&i&&("string"==typeof i&&(i=i.split(" ")),i.forEach((i=>{t.classList.add(i)}))),this.$locationBtn.setAttribute("aria-disabled",!0)}onEndFetchLocation(){var t,i;t=this.$locationBtn,i=this.loadingClass,t&&i&&("string"==typeof i&&(i=i.split(" ")),i.forEach((i=>{t.classList.remove(i)}))),this.$locationBtn.setAttribute("aria-disabled",!1)}}window.FormieAddressProvider=t;window.FormieAddressFinder=class extends t{constructor(){let t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};super(t),this.$form=t.$form,this.form=this.$form.form,this.$field=t.$field,this.$input=this.$field.querySelector("[data-autocomplete]"),this.scriptId="FORMIE_ADDRESS_FINDER_SCRIPT",this.apiKey=t.apiKey,this.countryCode=t.countryCode,this.widgetOptions=t.widgetOptions,this.retryTimes=0,this.maxRetryTimes=150,this.waitTimeout=200,this.$input?this.initScript():console.error("Unable to find input `[data-autocomplete]`.")}initScript(){if(document.getElementById(this.scriptId))this.waitForLoad();else{const t=document.createElement("script");t.src="https://api.addressfinder.io/assets/v3/widget.js",t.defer=!0,t.async=!0,t.id=this.scriptId,t.onload=()=>{this.initAutocomplete()},document.body.appendChild(t)}}waitForLoad(){this.retryTimes>this.maxRetryTimes?console.error(`Unable to load AddressFinder API after ${this.retryTimes} times.`):"undefined"==typeof AddressFinder?(this.retryTimes+=1,setTimeout(this.waitForLoad.bind(this),this.waitTimeout)):this.initAutocomplete()}initAutocomplete(){new AddressFinder.Widget(this.$input,this.apiKey,this.countryCode,this.widgetOptions).on("result:select",((t,i)=>{i.address_line_2?(this.setFieldValue("[data-address1]",i.address_line_2),this.setFieldValue("[data-address2]",i.address_line_1)):(this.setFieldValue("[data-address1]",i.address_line_1),this.setFieldValue("[data-address2]","")),this.setFieldValue("[data-city]",i.locality_name),this.setFieldValue("[data-zip]",i.postcode),this.setFieldValue("[data-state]",i.state_territory),this.setFieldValue("[data-country]",this.countryCode)}))}setFieldValue(t,i){this.$field.querySelector(t)&&(this.$field.querySelector(t).value=i||"")}}}();