tinymce.PluginManager.add('sprout_gallery', function(editor, url) {
    // Add a button that opens a window
    editor.addButton('sprout_gallery', {
        icon: 'image-gallery',
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
        var ordering = $(elem).attr('data-ordering');
        var type = $(elem).attr('data-type');
        var slider_dots = $(elem).attr('data-slider-dots');
        var slider_arrows = $(elem).attr('data-slider-arrows');
        var slider_autoplay = $(elem).attr('data-slider-autoplay');
        var slider_speed = $(elem).attr('data-slider-speed');

        params = '?cat=' + encodeURIComponent(category);
        params += '&thumbs=' + encodeURIComponent(thumbs);
        params += '&captions=' + encodeURIComponent(captions);
        params += '&crop=' + encodeURIComponent(crop);
        params += '&limit=' + encodeURIComponent(limit);
        params += '&order=' + encodeURIComponent(ordering);
        params += '&display_opts=' + encodeURIComponent(type);
        params += '&slider_dots=' + encodeURIComponent(slider_dots);
        params += '&slider_arrows=' + encodeURIComponent(slider_arrows);
        params += '&slider_autoplay=' + encodeURIComponent(slider_autoplay);
        params += '&slider_speed=' + encodeURIComponent(slider_speed);
    }

    tinymce.activeEditor.windowManager.open({
        title: 'Gallery',
        width : 816,
        height : 650,
        file : SITE + 'tinymce4/gallery' + params
    });
};
