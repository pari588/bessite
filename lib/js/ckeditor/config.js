/**
 * @license Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function (config) {
    // Define changes to default configuration here. For example:
    // config.language = 'fr';
    // config.uiColor = '#AADC6E';

    config.toolbar_basic = [
        ['Source', 'Maximize'],
        ['Bold', 'Italic', 'Underline'],
        ['NumberedList', 'BulletedList'],
        ['Link', 'Unlink', 'Anchor'],
        ['TextColor', 'BGColor'],
        ['Font', 'FontSize']
    ];

    config.toolbar_medium = [
        ['Source', 'Maximize'],
        ['Cut', 'Copy'],
        ['Paste', 'PasteText', 'PasteFromWord'],
        ['Undo', 'Redo'],
        ['Find', 'Replace'],
        ['Bold', 'Italic', 'Underline'],
        ['Strike', 'Subscript', 'Superscript'],
        ['NumberedList', 'BulletedList','Table'],
        ['Link', 'Unlink', 'Anchor'],
        ['Image'],  
        ['TextColor', 'BGColor'],
        ['Styles'],
        ['Format'],
        ['Font', 'FontSize']
    ];
    config.htmlEncodeOutput = false;
    config.entities = false;
    config.basicEntities = false;
    config.fillEmptyBlocks = false;
    if (THEME == "light" || THEME == "moderate") {
        config.contentsCss = SITEURL + '/' + LIBDIR + '/js/ckeditor/contents.css';
    } else {
        config.contentsCss = SITEURL + '/' + LIBDIR + '/js/ckeditor/skins/prestige/contents.css';
    }
    config.filebrowserBrowseUrl = COREURL + '/js/filebrowser/filebrowser.php';
};
