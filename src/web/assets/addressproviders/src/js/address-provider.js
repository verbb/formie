import { eventKey } from '../../../frontend/src/js/utils/utils';

export class FormieAddressProvider {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$locationBtn = this.$field.querySelector('[data-fui-address-location-btn]');
        
        this.initLocationBtn();
    }

    initLocationBtn() {
        if (!this.$locationBtn) {
            return;
        }

        this.form.addEventListener(this.$locationBtn, eventKey('click'), (e) => {
            e.preventDefault();

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((position) => {
                    this.onCurrentLocation(position);
                }, (error) => {
                    console.log('Unable to fetch location ' + error.code + '.');
                }, {
                    enableHighAccuracy: true,
                });
            } else {
                console.log('Browser does not support geolocation.');
            }
        });
    }

    onCurrentLocation(position) {
        
    }
}

window.FormieAddressProvider = FormieAddressProvider;
