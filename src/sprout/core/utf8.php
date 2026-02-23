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

/**
 * A port of phputf8 to a unified file/class. Checks PHP status to ensure that
 * UTF-8 support is available and normalize global variables to UTF-8. It also
 * provides multi-byte aware replacement string functions.
 *
 * This file is licensed differently from the rest of Kohana. As a port of
 * phputf8, which is LGPL software, this file is released under the LGPL.
 *
 * PCRE needs to be compiled with UTF-8 support (--enable-utf8).
 * Support for Unicode properties is highly recommended (--enable-unicode-properties).
 * @see http://php.net/manual/reference.pcre.pattern.modifiers.php
 *
 * UTF-8 conversion will be much more reliable if the iconv extension is loaded.
 * @see http://php.net/iconv
 *
 * The mbstring extension is highly recommended, but must not be overloading
 * string functions.
 * @see http://php.net/mbstring
 *
 * $Id: utf8.php 3769 2008-12-15 00:48:56Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 * @deprecated replaced by Sprout/Helpers/Utf8
 */

final class utf8 extends Sprout\Helpers\Utf8 {
}
