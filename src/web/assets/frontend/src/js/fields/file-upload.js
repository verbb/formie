import { t, eventKey } from '../utils/utils';
import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import XHR from '@uppy/xhr-upload';
import Form from '@uppy/form';

// see https://gist.github.com/adamcmoore/498e6fb9e32cea1670cfd79034713755
const setUppyNotLoading = function(uppy) {
    uppy.setOptions({
        autoProceed: true,
    });
};

const getExistingUppyFiles = function(uppy) {
    return uppy.getFiles().filter((file) => { return file.meta.fileId; });
};

const setExistingFilesAsUploaded = function(uppy) {
    getExistingUppyFiles(uppy).forEach((file) => {
        uppy.setFileState(file.id, {
            progress: {
                uploadComplete: true,
                uploadStarted: true,
            },
        });
    });
};

const initializeUppyFiles = function(uppy, files) {
    if (!uppy || !files) {
        return;
    }

    if (files.length === 0) {
        setUppyNotLoading(uppy);
    }

    let processedFiles = 0;
    files.forEach((serverFile) => {
        const request = new XMLHttpRequest();
        request.open('GET', `./index.php?p=actions/formie/file-upload/load-file&id=${serverFile}`);
        request.onload = function() {
            if (request.status >= 200 && request.status < 300) {
                const dispositionHeader = request.getResponseHeader('content-disposition');
                const fileName = dispositionHeader.split('; ')[1].replace(/filename="(.*)"/, (match, fileName) => { return fileName; });
                const file = new File(
                    [request.response],
                    fileName,
                    { type: request.getResponseHeader('content-type'), lastModified: Date.now() },
                );
                uppy.addFile({
                    name: file.name,
                    type: file.type,
                    data: file,
                    meta: {
                        fileId: serverFile,
                    },
                });
                processedFiles++;
                if (processedFiles === files.length) {
                    setExistingFilesAsUploaded(uppy);
                    setUppyNotLoading(uppy);
                }
            }
        };
        request.responseType = 'blob';
        request.send();
    });
};

const createUppyInstance = function($this) {
    const container = $this.$field.querySelector('[type="file"]');
    const { fuiId } = container.dataset;
    const fieldName = container.name;
    const parent = container.parentNode;
    if (!fuiId) {
        return;
    }
    const files = container.dataset.files ? JSON.parse(container.dataset.files) : [];
    const { uppyOptions } = $this;

    // configure the Uppy dashboard with some sensible defaults
    // can be overridden by the uppyOptions

    // Uppy core
    const uppy = new Uppy({
        id: fuiId,
        restrictions: {
            maxNumberOfFiles: parseInt(container.dataset.fileLimit, 10) > 0 ? parseInt(container.dataset.fileLimit, 10) : null,
            maxFileSize: parseInt(container.dataset.sizeMaxLimit, 10) > 0 ? parseInt(container.dataset.sizeMaxLimit, 10) * 1024 * 1024 : null,
            minFileSize: parseInt(container.dataset.sizeMinLimit, 10) > 0 ? parseInt(container.dataset.sizeMinLimit, 10) * 1024 * 1024 : null,
            allowedFileTypes: container.getAttribute('accept').split(', '),
        },
        meta: {
            initiator: container.dataset.fieldHandle,
        },
        autoProceed: false,
        ...uppyOptions.core,
    });

    // Uppy dashboard
    uppy.use(Dashboard, {
        id: `dashboard-${fuiId}`,
        inline: true,
        height: 300,
        width: 'auto',
        target: parent,
        theme: 'auto',
        showRemoveButtonAfterComplete: true,
        doneButtonHandler: null,
        hideCancelButton: false,
        ...uppyOptions.dashboard,
    });

    // Uppy form
    uppy.use(Form, {
        id: `form-${fuiId}`,
        target: $this.$form,
        addResultToForm: false,
        ...uppyOptions.form,
    });

    // Uppy XHR
    uppy.use(XHR, {
        id: `xhr-${fuiId}`,
        endpoint: './index.php?p=actions/formie/file-upload/process-file',
        limit: 1,
        responseType: 'text',
        bundle: false,
        getResponseData: (xhr) => { return JSON.parse(xhr.response); },
        ...uppyOptions.xhr,
    });

    uppy.on('upload-success', (file, response) => {
        const assetId = response?.body?.id;
        if (!assetId) {
            uppy.error(new Error('File upload completed successfully but no asset ID was returned'), 'error', 3000);
            return;
        }
        // recreate our base file input field, as it was removed when Uppy was initialized
        const fileUploadInput = document.createElement('input');
        fileUploadInput.name = fieldName;
        fileUploadInput.type = 'hidden';
        fileUploadInput.value = assetId;
        parent.appendChild(fileUploadInput);
    });

    uppy.on('upload-error', () => {
    });

    uppy.on('file-removed', (file) => {
        const serverId = file.meta.fileId || file.response?.body?.id;
        if (!serverId) {
            return;
        }
        const formData = new FormData(formieForm);
        formData.append('removeFile', serverId);
        const request = new XMLHttpRequest();
        request.open('POST', './index.php?p=actions/formie/file-upload/remove-file');
        request.onerror = function() {
            // ...
        };
        request.onload = function() {
            if (request.status >= 300) {
                uppy.error(new Error('An error occurred when removing the uploaded file'), 'error', 3000);
            }
        };
        request.send(formData);
    });

    // load existing files
    initializeUppyFiles(uppy, files);

    // remove original input element, will be recreated as hidden input later
    container.remove();
};

export class FormieFileUpload {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        // Uppy options for core, dashboard, form, XHR upload
        this.uppyOptions = settings.uppyOptions || {};

        if (this.$field && this.$field.querySelector('[type="file"]')) {
            createUppyInstance(this);
        }

        this.form.addEventListener(this.$form, eventKey('registerFormieValidation'), this.registerValidation.bind(this));
        this.form.addEventListener(this.$form, eventKey('onAfterFormieSubmit'), this.onAfterSubmit.bind(this));
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

    onAfterSubmit() {
        // For multi-page Ajax forms, we don't want to submit the file uploads multiple times, so clear the content after success
        this.$field.querySelector('[type="hidden"]').value = null;
    }
}

window.FormieFileUpload = FormieFileUpload;
