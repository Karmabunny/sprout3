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

use karmabunny\kb\Uuid;

/**
 * A base class for widget doomtools.
 *
 * Widgets behave the same and use the same boiler code.
 * Override these bits:
 *
 * - getWidgetType()
 * - getWidgetText()
 * - setWidgetText()
 *
 * @see FindReplaceRichText
 * @see FindReplaceHtmlCode
 */
abstract class FindReplaceWidget implements FindReplaceInterface
{


    /** @inheritdoc */
    public function getName(): string
    {
        return 'Widget: ' . static::getWidgetType();
    }


    /** @inheritdoc */
    public function key(): string
    {
        $key = get_class($this) . '.' . static::getWidgetType();
        $key = Uuid::uuid5(FindReplace::NAMESPACE, $key);
        return $key;
    }


    /** @inheritdoc */
    public function find(array $finds, array $settings): iterable
    {
        if (!$finds) return;

        $iterator = Pdb::find('page_widgets')
            ->alias('widget')
            ->select([
                'widget.*',
                'page_revision_id',
                'page_id',
                'status',
            ])
            ->leftJoin('page_revisions AS revision', [
                'widget.page_revision_id = revision.id'
            ])
            ->where([
                'widget.type' => static::getWidgetType(),
            ])
            ->iterator();

        foreach ($iterator as $row) {
            $result = $this->findInWidget($row, $finds, $settings);
            if ($result === null) continue;
            yield $result;
        }
    }


    /** @inheritdoc */
    public function replace(array $replaces, array $settings): int
    {
        $ignore_case = $settings['ignore_case'] ?? true;

        $count = 0;

        foreach ($replaces as $find => $replace) {
            $results = $this->find([$find], $settings);

            foreach ($results as $found) {
                $text = $found['text'];

                $pattern = '!' . $find . '!';

                if ($ignore_case) {
                    $pattern .= 'i';
                }

                $text = preg_replace($pattern, $replace, $text);

                $ok = $this->replaceInWidget($found['id'], $text);

                if ($ok) {
                    $count += $found['count'];
                }
            }
        }

        return $count;
    }


    /**
     * Find the text in a widget.
     *
     * Null results are not included in by `find()`.
     *
     * @param array $row page_widgets db row + page id, revision id, status
     * @param string[] $finds
     * @param array $settings
     * @return null|array result set, see `find()` - or null if not found
     */
    protected function findInWidget(array $row, array $finds, array $settings): ?array
    {
        $ignore_case = $settings['ignore_case'] ?? true;

        $text = $this->getWidgetText($row);
        $indexes = FindReplace::findIndexes($text, $finds, $ignore_case);

        if ($indexes) {
            $url = 'admin/edit/page/' . $row['page_id'];

            if ($row['status'] !== 'live') {
                $url .= '?revision=' . $row['page_revision_id'];
            }

            return [
                'id' => $row['id'],
                'key' => $this->key(),
                'text' => $text,
                'url' => $url,
                'indexes' => $indexes,
                'count' => count($indexes),
            ];
        }

        return null;
    }


    /**
     * Replace the text in a widget.
     *
     * @param int $id
     * @param string $text
     * @return bool
     */
    protected function replaceInWidget(int $id, string $text): bool
    {
        $row = Pdb::find('page_widgets')
            ->where(['id' => $id])
            ->one();

        $this->setWidgetText($row, $text);

        $ok = (bool) Pdb::update('page_widgets', $row, ['id' => $id]);
        return $ok;
    }


    /**
     * Get the widget type this replacer is for.
     *
     * This should match the 'type' field in the `page_widgets` table.
     *
     * @see Widgets::instantiate()
     * @return string
     */
    public static abstract function getWidgetType(): string;


    /**
     * Get the widget text for replacing.
     *
     * Often this is a field within the 'settings' JSON blob.
     *
     * @param array $row
     * @return string
     */
    protected abstract function getWidgetText(array $row): string;


    /**
     * Set the widget text after replacing.
     *
     * This should match the behaviour from `getWidgetText()`.
     *
     * @param array $row db row `page_widgets`
     * @param string $text
     * @return void
     */
    protected abstract function setWidgetText(array &$row, string $text);
}
