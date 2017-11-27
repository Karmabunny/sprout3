<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace SproutModules\Karmabunny\Demo\Helpers;


/**
 * A basic demonstration of hacky things that can be done in addition to using {@see Fb} on JSON forms
 */
class FbHack {
	// Bunch of languages with ISO 639-1 codes starting with e
	const E_LANGS = [
		'en' => 'English',
		'ee' => 'Eʋegbe',
		'el' => 'ελληνικά',
		'eo' => 'Esperanto',
		'es' => 'Español',
		'et' => 'eesti keel',
		'eu' => 'euskara',
	];

    public static function hack() {
        return '<span style="color: #090;">Hello world from the FbHack::hack() method!</span>';
    }

}
