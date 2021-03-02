import { eventKey } from '../utils/utils';

const Uppy = require('@uppy/core');
const DragDrop = require('@uppy/drag-drop');
const XHRUpload = require('@uppy/xhr-upload');
const Form = require('@uppy/form');
const FileInput = require('@uppy/file-input');
const Dashboard = require('@uppy/dashboard');

require('@uppy/core/dist/style.css');
require('@uppy/drag-drop/dist/style.css');
require('@uppy/dashboard/dist/style.css');

export class FormieDragDropUpload {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field.querySelector('.fui-input-container');
        this.$fileInput = this.$field.querySelector('input[type="file"]');

        this.initDragAndDrop();
    }

    initDragAndDrop() {
        this.meta = {
            submissionId: '',
        };

        // Get any top-level fields for this form, and serialize that as meta
        (new FormData(this.$form)).forEach((value, key) => {
            if (!key.startsWith('fields[')) {
                this.meta[key] = value;
            }
        });

        // Override the action
        this.meta.action = 'formie/submissions/submit-upload';

        const uppy = new Uppy({ debug: true, autoProceed: true, meta: this.meta });
        
        // uppy.use(DragDrop, {
        //     target: this.$field,
        //     inputName: this.$fileInput.getAttribute('name'),
        // });
        
        uppy.use(Dashboard, {
            target: this.$field,
            metaFields: [],
            inline: true,
            // trigger: this.$field,
            inputName: this.$fileInput.getAttribute('name'),
        });

        uppy.use(XHRUpload, {
            endpoint: '/',
            fieldName: this.$fileInput.getAttribute('name'),
            metaFields: Object.keys(this.meta),
            bundle: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Cache-Control': 'no-cache',
            },
        });

        console.log(this.meta);

        uppy.on('upload-success', (file, response) => {
            // If this was an upload on a form that hadn't been submitted yet, we need to add
            // the submissionId input into the form
            var submissionId = response.body.submissionId || null;

            if (submissionId) {
                var $input = this.$form.querySelector('[name="submissionId"][type="hidden"]');

                if (!$input) {
                    $input = document.createElement('input');
                    $input.setAttribute('type', 'hidden');
                    $input.setAttribute('name', 'submissionId');
                    this.$form.appendChild($input);
                }

                $input.setAttribute('value', submissionId);

                console.log(submissionId);

                // Ensure out meta is always up to date, we need both this apparently...
                this.meta.submissionId = submissionId;
                uppy.setMeta({ submissionId });
            }

            console.log(response);
        });




        let uploadedAssetsJson = this.$fileInput.getAttribute('data-uploaded');

        if (uploadedAssetsJson) {
            uploadedAssetsJson = JSON.parse(uploadedAssetsJson);

            if (Array.isArray(uploadedAssetsJson)) {
                uploadedAssetsJson.forEach(asset => {
                    fetch(asset.url)
                        .then((response) => response.blob()) // returns a Blob
                        .then((blob) => {
                            uppy.addFile({
                                name: asset.name, // file name
                                type: blob.type,
                                data: blob,
                            });

                            // uppy.setFileState(file.id, { 
                            //     progress: { uploadComplete: true, uploadStarted: false }, 
                            // });

                            Object.keys(uppy.state.files).forEach(file => {
                                uppy.setFileState(file, { 
                                    progress: { uploadComplete: true, uploadStarted: true }, 
                                });
                            });
                        });
                });
            }
        }

        // uppy.on('complete', (result) => {
        //     // If this was an upload on a form that hadn't been submitted yet, 
        //     console.log(result);

        //     // this.$fileInput.files = result.successful;
        // });
    }

    // registerValidation(e) {
    //     // Add our custom validations logic and methods
    //     e.detail.validatorSettings.customValidations = {
    //         ...e.detail.validatorSettings.customValidations,
    //         ...this.getFileSizeMinLimitRule(),
    //         ...this.getFileSizeMaxLimitRule(),
    //         ...this.getFileLimitRule(),
    //     };

    //     // Add our custom messages
    //     e.detail.validatorSettings.messages = {
    //         ...e.detail.validatorSettings.messages,
    //         ...this.getFileSizeMinLimitMessage(),
    //         ...this.getFileSizeMaxLimitMessage(),
    //         ...this.getFileLimitMessage(),
    //     };
    // }

    // getFileSizeMinLimitRule() {
    //     return {
    //         fileSizeMinLimit(field) {
    //             const type = field.getAttribute('type');
    //             const sizeLimit = field.getAttribute('data-size-min-limit');
    //             const sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

    //             if (type !== 'file' || !sizeBytes) {
    //                 return;
    //             }

    //             for (const file of field.files) {
    //                 if (file.size < sizeBytes) {
    //                     return true;
    //                 }
    //             }
    //         },
    //     };
    // }

    // getFileSizeMinLimitMessage() {
    //     return {
    //         fileSizeMinLimit(field) {
    //             return t('File must be larger than {filesize} MB.', {
    //                 filesize: field.getAttribute('data-size-min-limit'),
    //             });
    //         },
    //     };
    // }

    // getFileSizeMaxLimitRule() {
    //     return {
    //         fileSizeMaxLimit(field) {
    //             const type = field.getAttribute('type');
    //             const sizeLimit = field.getAttribute('data-size-max-limit');
    //             const sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

    //             if (type !== 'file' || !sizeBytes) {
    //                 return;
    //             }

    //             for (const file of field.files) {
    //                 if (file.size > sizeBytes) {
    //                     return true;
    //                 }
    //             }
    //         },
    //     };
    // }

    // getFileSizeMaxLimitMessage() {
    //     return {
    //         fileSizeMaxLimit(field) {
    //             return t('File must be smaller than {filesize} MB.', {
    //                 filesize: field.getAttribute('data-size-max-limit'),
    //             });
    //         },
    //     };
    // }

    // getFileLimitRule() {
    //     return {
    //         fileLimit(field) {
    //             const type = field.getAttribute('type');
    //             const fileLimit = parseInt(field.getAttribute('data-file-limit'));

    //             if (type !== 'file' || !fileLimit) {
    //                 return;
    //             }

    //             if (field.files.length > fileLimit) {
    //                 return true;
    //             }
    //         },
    //     };
    // }

    // getFileLimitMessage() {
    //     return {
    //         fileLimit(field) {
    //             return t('Choose up to {files} files.', {
    //                 files: field.getAttribute('data-file-limit'),
    //             });
    //         },
    //     };
    // }
}

window.FormieDragDropUpload = FormieDragDropUpload;
