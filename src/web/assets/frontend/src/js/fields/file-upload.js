import { t, eventKey } from '../utils/utils';

export class FormieFileUpload {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;

        this.form.registerEvent('registerFormieValidation', this.registerValidation.bind(this));
        this.form.addEventListener(this.$form, eventKey('FormieFileUpload'), this.onUploadedAsset.bind(this));
    }

    registerValidation(e) {
        e.validator.addValidator('fileSizeMinLimit', ({ input }) => {
            const type = input.getAttribute('type');
            const sizeLimit = input.getAttribute('data-size-min-limit');
            const sizeBytes = parseFloat(sizeLimit) * 1000 * 1000;

            if (type !== 'file' || !sizeBytes) {
                return true;
            }

            for (const file of input.files) {
                if (file.size < sizeBytes) {
                    return false;
                }
            }

            return true;
        }, ({ input }) => {
            return t('File must be larger than {filesize} MB.', {
                filesize: input.getAttribute('data-size-min-limit'),
            });
        });

        e.validator.addValidator('fileSizeMaxLimit', ({ input }) => {
            const type = input.getAttribute('type');
            const sizeLimit = input.getAttribute('data-size-max-limit');
            const sizeBytes = parseFloat(sizeLimit) * 1000 * 1000;

            if (type !== 'file' || !sizeBytes) {
                return true;
            }

            for (const file of input.files) {
                if (file.size > sizeBytes) {
                    return false;
                }
            }

            return true;
        }, ({ input }) => {
            return t('File must be smaller than {filesize} MB.', {
                filesize: input.getAttribute('data-size-max-limit'),
            });
        });

        e.validator.addValidator('fileLimit', ({ input }) => {
            const type = input.getAttribute('type');
            const fileLimit = parseInt(input.getAttribute('data-file-limit'));

            if (type !== 'file' || !fileLimit) {
                return true;
            }

            if (input.files.length > fileLimit) {
                return false;
            }

            return true;
        }, ({ input }) => {
            return t('Choose up to {files} files.', {
                files: input.getAttribute('data-file-limit'),
            });
        });
    }

    onUploadedAsset(e) {
        const { data } = e.detail;

        const $fileInput = this.$field.querySelector('[type="file"]');
        const fieldKey = this.$field.getAttribute('data-field-handle');

        if (data && data[fieldKey]) {
            const assetIds = data[fieldKey];

            // Find the base input for the field as an anchor for new inputs
            let $anchor = this.$field.querySelector('input[type="hidden"][value=""]');
            const anchorName = `${$anchor.getAttribute('name')}[]`;

            // Remove all existing hidden ID inputs (but not the file input).
            this.$field.querySelectorAll('input[type="hidden"]').forEach((el) => {
                if (el.value) {
                    return el.remove();
                }
            });

            // Insert new hidden inputs directly after the blank input, preserving order.
            assetIds.forEach((id) => {
                const $assetInput = document.createElement('input');
                $assetInput.type = 'hidden';
                $assetInput.name = anchorName;
                $assetInput.value = id;
                $anchor.insertAdjacentElement('afterend', $assetInput);
                $anchor = $assetInput; // next one goes after this one
            });

            // Reset the attribute to prevent re-uploading
            if ($fileInput) {
                $fileInput.value = null;
            }
        }
    }
}

window.FormieFileUpload = FormieFileUpload;
