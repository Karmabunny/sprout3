<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2016 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Helpers;


/**
 * Implements FPDI library without having to hack up its source code (much).
 * FPDI is a PDF document importer which works with FPDF, so you can use nicely designed PDFs as templates,
 * and then dynamically add data to them.
 * See https://github.com/Setasign/FPDI or https://www.setasign.com/products/fpdi/about/
 */
class Fpdi extends \setasign\Fpdi\Tfpdf\Fpdi
{
    // See the FPDI class for the actual code
}
