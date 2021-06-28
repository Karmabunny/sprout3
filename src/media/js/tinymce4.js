/**
* Remove a tag, but keep the content
**/
function nuke_tags_HTML(html, tags) {
    var regex1 = '<(' + tags.join('|') + ')( [^>]*?)?>';     // opening
    var regex2 = '</?(' + tags.join('|') + ')>';             // closing
    return html
        .replace(new RegExp(regex1, 'gi'), '')
        .replace(new RegExp(regex2, 'gi'), '');
}

/**
* Remove elements and content
**/
function nuke_elements_DOM(dom, tags) {
    for (var j = 0; j < tags.length; j++) {
        var elems = dom.getElementsByTagName(tags[j]);
        for (var i = 0; i < elems.length; i++) {
            elems[i].parentNode.removeChild(elems[i]);
        }
    }
}

/**
* Remove elements and content
**/
function nuke_attributes_DOM(dom, tags, attrs) {
    for (var j = 0; j < tags.length; j++) {
        var elems = dom.getElementsByTagName(tags[j]);
        for (var i = 0; i < elems.length; i++) {
            for (var m = 0; m < attrs.length; m++) {
                elems[i].removeAttribute(attrs[m]);
            }
        }
    }
}


var TinyMCE4 = {

    /**
    * Called on page load to set up TinyMCE fields
    **/
    init: function(opts) {
        // Add buttons for P and H2-4
        tinyMCE.PluginManager.add('stylebuttons', function(editor, url) {
            var buttons = [
                { format: 'h2', img: 'btn_h2.png', tooltip: 'Heading 2' },
                { format: 'h3', img: 'btn_h3.png', tooltip: 'Heading 3' },
                { format: 'h4', img: 'btn_h4.png', tooltip: 'Heading 4' },
                { format: 'p', img: 'btn_p.png', tooltip: 'Paragraph' }
            ];
            buttons.forEach(function(obj){
                editor.addButton("style-" + obj.format, {
                    tooltip: obj.tooltip,
                    image: ROOT + 'media/tiny_mce4/' + obj.img,
                    onClick: function() {
                        editor.execCommand('mceToggleFormat', false, obj.format);
                    },
                    onPostRender: function() {
                        var self = this, setup = function() {
                            editor.formatter.formatChanged(obj.format, function(state) {
                                self.active(state);
                            });
                        };
                        editor.formatter ? setup() : editor.on('init', setup);
                    }
                })
            });
        });


        // File and image browser popup window
        opts.file_browser_callback = function(field_name, url, type, win) {
            var title = 'Insert ';
            var form_url = SITE + 'tinymce4/';

            switch (type) {
            case 'media':
                title += 'video';
                form_url += 'video';
                break;

            case 'image':
                title += 'image';
                form_url += 'image';
                break;

            default:
                title += 'link';
                form_url += 'library';
            }

            tinymce.activeEditor.windowManager.open({
                name: 'filemanager',
                title : title,
                width : 816,
                height : 650,
                file : form_url
            }, {
                window : win,
                input : field_name
            });
        };

        opts.paste_preprocess = function(plugin, args) {
            // Convert pasted DIVs into Ps
            args.content = args.content.replace(/<div/ig, '<p', args.content);
            args.content = args.content.replace(/<\/div>/ig, '</p>', args.content);

            // Convert fancy quotes into UTF-8 friendly quotes
            args.content = args.content.replace(/‘/ig, "'", args.content);
            args.content = args.content.replace(/’/ig, "'", args.content);
            args.content = args.content.replace(/“/ig, '"', args.content);
            args.content = args.content.replace(/”/ig, '"', args.content);

            // Nuke tags but leaving content
            args.content = nuke_tags_HTML(args.content, [
                'body', 'html',
                'font', 'u', 'small', 's', 'q', 'small', 'big', 'label',
                'span', 'section', 'nav', 'article', 'aside', 'header', 'footer', 'address', 'main'
            ]);

            // If string is entirely one PRE block, nuke the tag and convert newlines to BR
            if (args.content.match(/^<pre/i) && args.content.match(/<\/pre>$/i)) {
                args.content = nuke_tags_HTML(args.content, ['pre']);
                args.content = args.content.replace(/\r?\n/ig, '<br>');
            }


            // If string contains newlines, convert multiples into new paragraphs
            if (args.content.match(/<br *\/?>/i)) {
                args.content = '<p>' + args.content.replace(/(<br *\/?>|&nbsp;){2,}/ig, '</p><p>') + '</p>';
            }
        }

        opts.paste_postprocess = function(plugin, args) {
            // nuke elements including content
            nuke_elements_DOM(args.node, [
                'head', 'title', 'script', 'base', 'link', 'meta', 'noscript', 'template', 'style',
                'iframe', 'embed', 'object', 'param',
                'form', 'input', 'textarea', 'select'
            ]);

            // nuke attributes from elements
            nuke_attributes_DOM(args.node, [
                'p', 'a', 'ol', 'ul', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'b', 'em', 'strong', 'i', 's',
                'table', 'tbody', 'col', 'tr', 'td', 'th',
            ], [
                'style', 'id', 'class', 'align',
                'accesskey', 'contenteditable', 'contextmenu', 'dir', 'draggable', 'dropzone', 'hidden', 'lang', 'spellcheck', 'tabindex', 'title',
                'itemid', 'itemprop', 'itemref', 'itemscope', 'itemtype'
            ]);

            // nuke attributes from elements
            nuke_attributes_DOM(args.node, [
                'table', 'tbody', 'col', 'tr', 'td', 'th'
            ], [
                'width', 'height', 'cellpadding', 'cellspacing', 'valign', 'align', 'bgcolor'
            ]);

            // Nuke P tags inside LI tags - keeping the content
            var elems = args.node.getElementsByTagName('li');
            for (var i = 0; i < elems.length; i++) {
                elems[i].innerHTML = nuke_tags_HTML(elems[i].innerHTML, ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
            }

            var tables = args.node.getElementsByTagName('table');
            for (var i = 0; i < tables.length; i++) {
                tables[i].className += " table--content-standard";
            }


            // Nuke random empty Ps; blank space should be controlled via CSS margins
            var ps = args.node.getElementsByTagName('p');
            var i = ps.length - 1;
            while (i >= 0) {
                if (ps[i].innerHTML.match(/^\s*$/)) {
                    ps[i].parentNode.removeChild(ps[i]);
                }
                --i;
            }
        }

        // Set dirty flag if field is changed
        opts.setup = function(ed) {
            ed.on('change', function(e) {
                $('#edit-form').triggerHandler('setDirty');
            });
        };

        opts.init_instance_callback = function (editor) {
            editor.on('BeforeSetContent', function (e) {
                // Wrap iframes (which should be videos produced by the media plugin, but may come from other sources)
                if (e.content.match(/^<iframe/)) {
                    e.content = '<div class="tinymce-media-iframe">' + e.content + '</div>';
                }
            });
        }

        tinymce.init(opts);
    },

    /**
    * Called by the image and link managers to set the URL field, and close the popup
    **/
    setUrl: function(url) {
        var args = top.tinymce.activeEditor.windowManager.getParams();
        args.window.document.getElementById(args.input).value = url;
    },


    /**
    * Called by the image and link managers to set an arbitary field, based on the field label
    * This is a bit of a hack.
    **/
    setField: function(label, value) {
        var args = top.tinymce.activeEditor.windowManager.getParams();
        args.window.$('#' + args.input)
            .closest('.mce-window').find('label').filter(':contains("' + label + '")')
            .closest('.mce-container-body').find('input')
            .val(value);
    },

    /**
    * Called by the image and link managers to set the URL field, and close the popup
    **/
    setPopupTitle: function(title) {
        var args = top.tinymce.activeEditor.windowManager.getParams();
        var windows = top.tinymce.activeEditor.windowManager.getWindows();
        $.each(windows, function(key, win) {
            if (win.name() == 'filemanager') {
                // Using their API -- win.title(title) -- doesn't seem to work
                args.window.$('#' + win._id + '-title').text(title);
            }
        });
    },

    /**
    * Called by the image and link managers to close the popup
    **/
    closePopup: function() {
        top.tinymce.activeEditor.windowManager.close();
    }

};


$(document).ready(function() {
    // Bind a custom event handler to insert content into the rte
    $('.tinymce4-editor').on('richtext_insert', function(event, html) {
        tinymce.get($(this).attr('id')).insertContent(html);
    });

    // Bind a custom event handler to set content into the rte
    $('.tinymce4-editor').on('richtext_set', function(event, html) {
        tinymce.get($(this).attr('id')).setContent(html);
    });
});
