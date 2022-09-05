import { eventKey } from '../utils/utils';

export class FormieAddressProvider {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$locationBtn = this.$field.querySelector('[data-fui-address-location-btn]');
        this.loadingClass = this.form.getClasses('loading');

        this.initLocationBtn();
    }

    initLocationBtn() {
        if (!this.$locationBtn) {
            return;
        }

        this.form.addEventListener(this.$locationBtn, eventKey('click'), (e) => {
            e.preventDefault();

            this.onStartFetchLocation();

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((position) => {
                    this.onCurrentLocation(position);
                }, (error) => {
                    console.log(`Unable to fetch location ${error.code}.`);

                    this.onEndFetchLocation();
                }, {
                    enableHighAccuracy: true,
                });
            } else {
                console.log('Browser does not support geolocation.');

                this.onEndFetchLocation();
            }
        });
    }

    onCurrentLocation(position) {
        this.onEndFetchLocation();
    }

    onStartFetchLocation() {
        this.$locationBtn.classList.add(this.loadingClass);
        this.$locationBtn.setAttribute('aria-disabled', true);
    }

    onEndFetchLocation() {
        this.$locationBtn.classList.remove(this.loadingClass);
        this.$locationBtn.setAttribute('aria-disabled', false);
    }
}

window.FormieAddressProvider = FormieAddressProvider;
