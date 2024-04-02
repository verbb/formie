import { t, eventKey } from '../utils/utils';

export class FormieFileUpload {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;

        this.form.registerEvent('registerFormieValidation', this.registerValidation.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit'), this.onAfterSubmit.bind(this));
    }

    registerValidation(e) {
        e.validator.addValidator('fileSizeMinLimit', ({ input }) => {
            const type = input.getAttribute('type');
            const sizeLimit = input.getAttribute('data-size-min-limit');
            const sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

            if (type !== 'file' || !sizeBytes) {
                return true;
            }

            for (const file of input.files) {
                if (file.size < sizeBytes) {
                    return false;
                }
            }
        }, ({ input }) => {
            return t('File must be larger than {filesize} MB.', {
                filesize: input.getAttribute('data-size-min-limit'),
            });
        });

        e.validator.addValidator('fileSizeMaxLimit', ({ input }) => {
            const type = input.getAttribute('type');
            const sizeLimit = input.getAttribute('data-size-max-limit');
            const sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

            if (type !== 'file' || !sizeBytes) {
                return true;
            }

            for (const file of input.files) {
                if (file.size > sizeBytes) {
                    return false;
                }
            }
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
        }, ({ input }) => {
            return t('Choose up to {files} files.', {
                files: input.getAttribute('data-file-limit'),
            });
        });
    }

    onAfterSubmit() {
        // For multi-page Ajax forms, we don't want to submit the file uploads multiple times, so clear the content after success
        this.$field.querySelector('[type="file"]').value = null;
    }
}

window.FormieFileUpload = FormieFileUpload;
