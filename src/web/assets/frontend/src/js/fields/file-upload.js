import { eventKey } from '../utils/utils';

export class FormieFileUpload {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;

        this.form.addEventListener(this.$form, eventKey('registerFormieValidation'), this.registerValidation.bind(this));
    }

    registerValidation(e) {
        // Add our custom validations logic and methods
        e.detail.validatorSettings.customValidations = {
            ...e.detail.validatorSettings.customValidations,
            ...this.getFileSizeMinLimitRule(),
            ...this.getFileSizeMaxLimitRule(),
            ...this.getFileLimitRule(),
        };

        // Add our custom messages
        e.detail.validatorSettings.messages = {
            ...e.detail.validatorSettings.messages,
            ...this.getFileSizeMinLimitMessage(),
            ...this.getFileSizeMaxLimitMessage(),
            ...this.getFileLimitMessage(),
        };
    }

    getFileSizeMinLimitRule() {
        return {
            fileSizeMinLimit(field) {
                const type = field.getAttribute('type');
                const sizeLimit = field.getAttribute('data-size-min-limit');
                const sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

                if (type !== 'file' || !sizeBytes) {
                    return;
                }

                for (const file of field.files) {
                    if (file.size < sizeBytes) {
                        return true;
                    }
                }
            },
        };
    }

    getFileSizeMinLimitMessage() {
        return {
            fileSizeMinLimit(field) {
                return t('File must be larger than {filesize} MB.', {
                    filesize: field.getAttribute('data-size-min-limit'),
                });
            },
        };
    }

    getFileSizeMaxLimitRule() {
        return {
            fileSizeMaxLimit(field) {
                const type = field.getAttribute('type');
                const sizeLimit = field.getAttribute('data-size-max-limit');
                const sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

                if (type !== 'file' || !sizeBytes) {
                    return;
                }

                for (const file of field.files) {
                    if (file.size > sizeBytes) {
                        return true;
                    }
                }
            },
        };
    }

    getFileSizeMaxLimitMessage() {
        return {
            fileSizeMaxLimit(field) {
                return t('File must be smaller than {filesize} MB.', {
                    filesize: field.getAttribute('data-size-max-limit'),
                });
            },
        };
    }

    getFileLimitRule() {
        return {
            fileLimit(field) {
                const type = field.getAttribute('type');
                const fileLimit = parseInt(field.getAttribute('data-file-limit'));

                if (type !== 'file' || !fileLimit) {
                    return;
                }

                if (field.files.length > fileLimit) {
                    return true;
                }
            },
        };
    }

    getFileLimitMessage() {
        return {
            fileLimit(field) {
                return t('Choose up to {files} files.', {
                    files: field.getAttribute('data-file-limit'),
                });
            },
        };
    }
}

window.FormieFileUpload = FormieFileUpload;
