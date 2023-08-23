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

namespace Sprout\Helpers;

/**
 * A doomtool for rich text widgets.
 *
 * This is registered in sprout core.
 */
class FindReplaceRichText extends FindReplaceWidget
{

    /** @inheritdoc */
    public static function getWidgetType(): string
    {
        return 'RichText';
    }


    /** @inheritdoc */
    protected function getWidgetText(array $row): string
    {
        $settings = json_decode($row['settings'], true);
        $text = $settings['text'] ?? '';
        return $text;
    }


    /** @inheritdoc */
    protected function setWidgetText(array &$row, string $text)
    {
        $settings = json_decode($row['settings'], true);
        $settings['text'] = $text;
        $row['settings'] = json_encode($settings);
    }
}
