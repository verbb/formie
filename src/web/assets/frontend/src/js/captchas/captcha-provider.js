export class FormieCaptchaProvider {

    createInput() {
        const $div = document.createElement('div');

        // We need to handle re-initializing, so always empty the placeholder to start fresh to prevent duplicate captchas
        this.$placeholder.innerHTML = '';
        this.$placeholder.appendChild($div);

        return $div;
    }

}

window.FormieCaptchaProvider = FormieCaptchaProvider;
