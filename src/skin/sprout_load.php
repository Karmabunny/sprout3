<?php
use Sprout\Helpers\Needs;
use Sprout\Helpers\Register;


// Convert file/download/id(/size) links to actual file names e.g. 'files/123_example.jpg'
Register::contentReplace('main_content', ['Sprout\\Helpers\\ContentReplace', 'fileDownload']);

// Update document links to have a cache-buster and also Google event tracking
Register::contentReplace('main_content', ['Sprout\\Helpers\\Sprout', 'specialFileLinks']);

// Determine if there is a rel="facebox" in use and if so, load the Facebox library
Register::contentReplace('inner_html', function($html) {
    if (preg_match('/class="[^"]*js-popup[^"]*"/', $html)) {
        Needs::fileGroup('magnific_popup');
    }
    return $html;
});

Register::templateVariable('hello', function() {
    return 'Hello World!';
});
