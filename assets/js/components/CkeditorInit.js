import { ClassicEditor, Essentials, Paragraph, Autoformat, Bold, Italic, Underline, Link, List, ListProperties, BlockQuote } from 'ckeditor5';
import coreTranslations from 'ckeditor5/translations/fr.js';
import 'ckeditor5/ckeditor5.css';
import '../../styles/components/ckeditor-dark.scss';

const DEFAULT_PLUGINS = [
    Essentials, Paragraph, Autoformat,
    Bold, Italic, Underline, Link,
    List, ListProperties,
    BlockQuote,
];

const DEFAULT_TOOLBAR = [
    'bold', 'italic', 'underline', 'link',
    '|',
    'bulletedList', 'numberedList',
    '|',
    'blockQuote',
    '|',
    'undo', 'redo',
];

export function initCkeditor(selector, config = {}) {
    const targets = document.querySelectorAll(selector);

    if (targets.length === 0) {
        return;
    }

    targets.forEach((textarea) => {
        if (textarea.dataset.ckeditorInitialized === 'true') {
            return;
        }
        textarea.dataset.ckeditorInitialized = 'true';

        ClassicEditor
            .create(textarea, {
                licenseKey: 'GPL',
                plugins: DEFAULT_PLUGINS,
                toolbar: DEFAULT_TOOLBAR,
                translations: [coreTranslations],
                link: {
                    defaultProtocol: 'https://',
                    addTargetToExternalLinks: true,
                },
                ...config,
            })
            .catch((error) => {
                textarea.dataset.ckeditorInitialized = 'false';
                console.error('CKEditor init error:', error);
            });
    });
}
