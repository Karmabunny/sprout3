tinymce.PluginManager.add('sprout_gallery', function(editor, url) {
    // Add a button that opens a window
    editor.addButton('sprout_gallery', {
        icon: 'image',
        title: 'Insert Gallery',
        stateSelector: 'div[class="sprout-editor--widget sprout-editor--gallery"]',
        onclick: openGalleryWindow,
    });
});

function openGalleryWindow() {
    var elem = tinymce.activeEditor.selection.getNode();
    var params = '';

    if (elem.nodeName == 'DIV' && $(elem).hasClass('sprout-editor--gallery')) {
        // Pull data-attributes as widget's settings
        var thumbs = $(elem).attr('data-thumbs');
        var captions = $(elem).attr('data-captions');
        var crop = $(elem).attr('data-crop');
        var limit = $(elem).attr('data-max');
        var category = $(elem).attr('data-id');

        params = '?cat=' + encodeURIComponent(category);
        params += '&thumbs=' + encodeURIComponent(thumbs);
        params += '&captions=' + encodeURIComponent(captions);
        params += '&crop=' + encodeURIComponent(crop);
        params += '&limit=' + encodeURIComponent(limit);
    }

    tinymce.activeEditor.windowManager.open({
        title: 'Gallery',
        width : 816,
        height : 650,
        file : SITE + 'tinymce4/gallery' + params
    });
};
