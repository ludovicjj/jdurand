import { initCkeditor } from '../components/CkeditorInit';

document.addEventListener('DOMContentLoaded', () => {
    initCkeditor('textarea[data-ckeditor]');
});
