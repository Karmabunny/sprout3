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

namespace Sprout\Helpers\DocImport;

use DOMDocument;
use DOMElement;
use ZipArchive;

use Sprout\Helpers\Enc;
use Sprout\Helpers\File;


class DocImportDOCX extends DocImport
{
    private $zip;
    private $number_formats;
    private $styles;
    private $relationships;
    private $res;


    /**
     * The main load function for a document.
     * Throw an exception on error.
     *
     * @param string $filename The file. The file will exist, but may not be valid
     * @return string|DOMDocument $data Resultant XML data as a string or DOMDocument element
     */
    public function load($filename)
    {
        $this->number_formats = [];
        $this->styles = [];
        $this->relationships = [];
        $this->res = [];
        $out = '';

        $this->zip = new ZipArchive();
        $this->zip->open($filename);

        $this->number_formats = $this->loadFormats();
        $this->styles = $this->loadStyles();
        $this->numbersFromStyles();
        $this->relationships = $this->loadRelationships();

        $doc = new DOMDocument();
        $doc->loadXML($this->zip->getFromName('word/document.xml'));
        $body = $doc->firstChild->getElementsByTagName('body');

        if ($body->length == 0) return null;
        $body = $body->item(0);

        if (!$body instanceof DOMElement) return null;
        if ($body->tagName != 'w:body') return null;
        if ($body->childNodes->length == 0) return null;

        $out .= '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
        $out .= '<doc>' . PHP_EOL;
        $out .= '<body>' . PHP_EOL;
        $out .= $this->block($body);
        $out .= '</body>' . PHP_EOL;

        foreach ($this->res as $name => $data) {
            $out .= '<res name="' . htmlspecialchars($name) . '">' . base64_encode($data) . '</res>' . PHP_EOL;
        }

        $out .= '</doc>';

        $this->zip->close();

        return $out;
    }


    /**
     * Validates element as block display
     *
     * @param DOMElement $elem
     * @return bool True Valid block element
     * @return bool False Invalid block element
     */
    private function isValidBlockElem($elem)
    {
        if (!in_array($elem->tagName, ['w:p', 'w:tbl'])) return false;

        $runs = $this->renderBlockRuns($elem);

        if (strip_tags($runs, '<img>') == '') return false;

        return true;
    }


    /**
     * Draw block element
     *
     * @param DOMElement $elem
     * @return string HTML
     */
    private function block($elem)
    {
        $list_stack = [];
        $list_fmt = null;
        $list_lvl = 0;
        $out = [];

        foreach ($elem->childNodes as $para) {
            if (!$para instanceof DOMElement) continue;

            // Tables
            if ($para->tagName == 'w:tbl') {
                while ($tag = array_pop($list_stack)) {
                    $out[] = "</{$tag}>";
                }
                $out[] = $this->drawTable($para);
                continue;
            }

            // Handle tags like w:bookmarkStart, as well as esoteric ones like w:moveToRangeEnd
            if ($para->tagName != 'w:p') {
                continue;
            }

            // Render the inner tags, drop if empty
            $runs = $this->renderBlockRuns($para);
            if (strip_tags($runs, '<img>') == '') continue;

            // Determine the style
            $style = $this->determineStyle($para);

            // Look for style changes (para <-> list)
            if ($style['number_format'] != $list_fmt or $style['number_level'] != $list_lvl) {
                $listtag = $this->determineListTag($style);
                $out[] = "<{$listtag}>";
                array_push($list_stack, $listtag);

                $list_fmt = $style['number_format'];
                $list_lvl = $style['number_level'];
            }

            // Find the next sibling which is a tag we support (paragraphs and tables)
            $nextSibling = $para->nextSibling;
            while ($nextSibling and !$this->isValidBlockElem($nextSibling)) {
                $nextSibling = $nextSibling->nextSibling;
            }

            // Take a look at the next el to see if we will be raising or dropping soon.
            $lvlraise = false;
            $lvldrop = false;
            $typechange = false;
            if ($style['number_format'] and $nextSibling) {
                $nextstyle = $this->determineStyle($nextSibling);
                if ($nextstyle['number_format'] != $style['number_format'] and $nextstyle['number_level'] == $style['number_level']) {
                    $typechange = true;
                    if ($nextstyle['number_format'] == '') $lvldrop = true;

                } else if ($nextstyle['number_level'] < $style['number_level']) {
                    $lvldrop = true;
                } else if ($nextstyle['number_level'] > $style['number_level']) {
                    $lvlraise = true;
                }
            }

            // Render the list item or the paragraph
            if ($lvlraise) {
                $out[] = "<li>{$runs}";

            } else if ($list_fmt) {
                $out[] = "<li>{$runs}</li>";

            } else {
                $tag = $this->determineParaTag($style);

                if ($tag[0] == 'h') {
                    $has_images = preg_match('!<img .+? />!', $runs, $image_tags);

                    // Remove tags from the heading
                    $heading = trim(strip_tags($runs));

                    // Headings in ALL CAPS get converted to Title Case.
                    if (!preg_match('![a-z]!', $heading)) {
                        $heading = ucwords(strtolower($heading));
                    }

                    // If we actually got any content, output it
                    if ($heading) {
                        $out[] = "<{$tag}>{$heading}</{$tag}>";
                    }

                    // If we found images, inject them in a P tag afterwards
                    if ($has_images) {
                        $out[] = '<p>' . implode('', $image_tags) . '</p>';
                    }

                } else {
                    $out[] = "<{$tag}>{$runs}</{$tag}>";
                }
            }

            // If there was a type change or level drop, pop the UL/OL element
            if ($typechange or $lvldrop) {
                $listtag = array_pop($list_stack);
                $out[] = "</{$listtag}>";
            }
            if ($lvldrop) {
                $list_fmt = $nextstyle['number_format'];
                $list_lvl = $nextstyle['number_level'];
                if (count($list_stack)) $out[] = "</li>";
            }
        }

        // Pop any remaining UL or OL elements
        while ($tag = array_pop($list_stack)) {
            $out[] = "</{$tag}>";
        }

        return implode(PHP_EOL, $out) . PHP_EOL;
    }


    /**
	 * Draw a w:tbl element
	 *
     * @param DOMElement $elem
     * @return string HTML table
     */
	private function drawTable($elem)
    {
		$out = '<table class="table--content-standard">' . PHP_EOL;

		$rows = $elem->getElementsByTagName('tr');
		foreach ($rows as $row) {
			$out .= '<tr>' . PHP_EOL;

			$cells = $row->getElementsByTagName('tc');
			foreach ($cells as $cell) {
				$paras = $cell->getElementsByTagName('p');

				$rendered = [];
				foreach($paras as $p) {
					$rendered[] = $this->renderBlockRuns($p);
				}

				$out .= '<td>';
				$out .= implode('<br/>', $rendered);
				$out .= '</td>' . PHP_EOL;
			}

			$out .= '</tr>' . PHP_EOL;
		}

		$out .= '</table>' . PHP_EOL;

		return $out;
	}


    /**
     * Render all the runs (i.e. w:r elements) for a given block element
     *
     * You would think this would be a simple draw_runs call on the getElementsByTagName,
     * but we would never actually have it _that_ easy...
     *
     * @param DOMElement $block
     * @return string XML tags representing the run content
     */
    private function renderBlockRuns($block)
    {
        $runs = [];

        foreach ($block->childNodes as $child) {
            if (! $child instanceof DOMElement) continue;

            if ($child->tagName == 'w:r') {
                $runs[] = new DocImportDOCXRun($child);

            } else if ($child->tagName == 'w:hyperlink') {
                $href = $this->relationships[$child->getAttribute('r:id')];

                $run = new DocImportDOCXRun($child);
                if ($href) {
                    $run->rendered = '<a href="' . Enc::xml($href) . '">' . $this->renderBlockRuns($child) . '</a>';
                } else {
                    $run->rendered = $this->renderBlockRuns($child);
                }
                $runs[] = $run;

            } else if ($child->tagName == 'w:smartTag' or $child->tagName == 'w:ins') {
                $childRuns = $child->getElementsByTagName('r');
                foreach ($childRuns as $run) {
                    $runs[] = new DocImportDOCXRun($run);
                }
            }
        }

        return trim($this->drawRuns($runs));
    }


    /**
     * Output one or more `w:r` elements
     *
     * @param array $runs
     * @return string
     */
    private function drawRuns($runs)
    {
        $out = '';
        $currBold = false;
        $currItalic = false;
        $currHyperlink = false;
        $currSubscript = false;
        $currSuperscript = false;
        $tagStack = [];

        foreach ($runs as $run) {
            if (!empty($run->rendered)) {
                $out .= $run->rendered;
                continue;
            }

            $runElem = $run->elem;
            $newBold = false;
            $newItalic = false;
            $newSubscript = false;
            $newSuperscript = false;
            $symbolDecode = false;

            $rpr = $runElem->getElementsByTagName('rPr');
            if ($rpr->length) {
                foreach ($rpr->item(0)->childNodes as $node) {
                    if (! $node instanceof DOMElement) continue;

                    switch ($node->tagName) {
                        case 'w:rStyle':
                            $style = $this->styles[$node->getAttribute('w:val')];
                            if ($style) {
                                if (!empty($style['bold'])) $newBold = true;
                                if (!empty($style['italic'])) $newItalic = true;
                            }
                            break;

                        case 'w:b':
                            $newBold = ($node->getAttribute('w:val') !== 'false' and $node->getAttribute('w:val') !== '0');
                            break;

                        case 'w:i':
                            $newItalic = ($node->getAttribute('w:val') !== 'false' and $node->getAttribute('w:val') !== '0');
                            break;

                        case 'w:vertAlign':
                            if ($node->getAttribute('w:val') == 'subscript') {
                                $newSubscript = true;
                            } else if ($node->getAttribute('w:val') == 'superscript') {
                                $newSuperscript = true;
                            }
                            break;

                        case 'w:rFonts':
                            if ($node->getAttribute('w:ascii') == 'Symbol') {
                                $symbolDecode = true;
                            }
                            break;
                    }
                }
            }

            // Determine tags to close
            $needToClose = [];
            if ($currItalic and !$newItalic) $needToClose[] = 'i';
            if ($currBold and !$newBold) $needToClose[] = 'b';
            if ($currSubscript and !$newSubscript) $needToClose[] = 'sub';
            if ($currSuperscript and !$newSuperscript) $needToClose[] = 'sup';

            // Close the whole tag stack, then reopen any which are meant to be open
            if (count($needToClose)) {
                $reopen = [];
                while ($tag = array_pop($tagStack)) {
                    if (!in_array($tag, $needToClose)) $reopen[] = $tag;
                    $out .= '</' . $tag . '>';
                }

                foreach ($reopen as $tag) {
                    $out .= '<' . $tag . '>';
                }

                $tagStack = $reopen;
            }

            // Open new tags
            if (!$currBold and $newBold) { $out .= '<b>'; $tagStack[] = 'b'; }
            if (!$currItalic and $newItalic) { $out .= '<i>'; $tagStack[] = 'i'; }
            if (!$currSubscript and $newSubscript) { $out .= '<sub>'; $tagStack[] = 'sub'; }
            if (!$currSuperscript and $newSuperscript) { $out .= '<sup>'; $tagStack[] = 'sup'; }

            // Update state variables
            $currBold = $newBold;
            $currItalic = $newItalic;
            $currSubscript = $newSubscript;
            $currSuperscript = $newSuperscript;

            // Output the text, br and graphic elements
            $texts = $runElem->childNodes;
            foreach ($texts as $node) {
                if ($node->tagName == 'w:t') {
                    if ($symbolDecode) {
                        $out .= Enc::xml($this->symbolSanitizeString($node->firstChild->data));
                    } else {
                        $out .= Enc::xml($node->firstChild->data);
                    }

                } else if ($node->tagName == 'w:drawing') {
                    $out .= $this->drawing($node);

                } else if ($node->tagName == 'w:pict') {
                    $out .= $this->pict($node);

                } else if ($node->tagName == 'w:br') {
                    $out .= '<br/>';

                } else if ($node->tagName == 'w:tab') {
                    $out .= "\t";
                }
            }
        }

        // Close any remaining tags
        while ($tag = array_pop($tagStack)) {
            $out .= '</' . $tag . '>';
        }

        // Clean up styled words with unstyled spaces
        $out = preg_replace('!</b>(\s*)<b>!', '$1', $out);
        $out = preg_replace('!</i>(\s*)<i>!', '$1', $out);

        // Clean up unstyled words with styled spaces
        $out = preg_replace('!<b>(\s*)</b>!', '$1', $out);
        $out = preg_replace('!<i>(\s*)</i>!', '$1', $out);

        // Remove multiple BRs in a row
        $out = preg_replace('!<br/>(<br/>)+!', '<br/>', $out);

        // Move BRs outside B and I tags
        $out = preg_replace('!<br/></([bi])>!', '</$1><br/>', $out);
        $out = preg_replace('!<([bi])><br/>!', '<br/><$1>', $out);

        // Remove trailing and leading BRs
        $out = preg_replace('!^<br/>!', '', $out);
        $out = preg_replace('!<br/>$!', '', $out);

        return $out;
    }


    /**
     * Load the number formats from numbering.xml
     *
     * @return array
     */
    private function loadFormats()
    {
        $out = [];

        if (! $this->zip->statName('word/numbering.xml')) return [];

        $doc = new DOMDocument();
        $doc->loadXML($this->zip->getFromName('word/numbering.xml'));

        $tmp = [];
        $abstractnums = $doc->getElementsByTagName('abstractNum');
        foreach ($abstractnums as $elem) {
            $id = $elem->getAttribute('w:abstractNumId');
            $abstractnum = [];

            $e = $elem->getElementsByTagName('numFmt');
            if ($e->length) {
                $abstractnum['numFmt'] = $e->item(0)->getAttribute('w:val');
            }

            $e = $elem->getElementsByTagName('numStyleLink');
            if ($e->length) {
                $abstractnum['styleName'] = $e->item(0)->getAttribute('w:val');
            }

            $tmp[$id] = $abstractnum;
        }

        $nums = $doc->getElementsByTagName('num');
        foreach ($nums as $elem) {
            $id = $elem->getAttribute('w:numId');

            $e = $elem->getElementsByTagName('abstractNumId');
            $e = $e->item(0)->getAttribute('w:val');
            if (! isset($tmp[$e])) continue;

            $out[$id] = $tmp[$e];
        }

        return $out;
    }


    /**
    * Load styles
    *
    * @return array
    */
    private function loadStyles()
    {
        $out = [];

        if (! $this->zip->statName('word/styles.xml')) return [];

        $doc = new DOMDocument();
        $doc->loadXML($this->zip->getFromName('word/styles.xml'));

        $elems = $doc->getElementsByTagName('style');
        foreach ($elems as $elem) {
            $id = $elem->getAttribute('w:styleId');

            $out[$id] = [
                'name' => $elem->getElementsByTagName('name')->item(0)->getAttribute('w:val'),
            ];

            // Numbering style
            $numid = $elem->getElementsByTagName('numId');
            if ($numid->length) {
                $out[$id]['numid'] = $numid->item(0)->getAttribute('w:val');
            }

            // Bold tag
            $bold = $elem->getElementsByTagName('b');
            if ($bold->length != 0 and $bold->item(0)->getAttribute('w:val') !== 'false' and $bold->item(0)->getAttribute('w:val') !== '0') {
                $out[$id]['bold'] = true;
            }

            // Italic tag
            $italic = $elem->getElementsByTagName('i');
            if ($italic->length != 0 and $italic->item(0)->getAttribute('w:val') !== 'false' and $italic->item(0)->getAttribute('w:val') !== '0') {
                $out[$id]['italic'] = true;
            }

            // Base style
            $base = $elem->getElementsByTagName('basedOn');
            if ($base->length) {
                $out[$id]['based_on_id'] = $base->item(0)->getAttribute('w:val');
            }
        }

        foreach ($out as $index => $row) {
            if (isset($row['based_on_id'])) {
                $out[$index]['based_on_names'] = $this->flattenBasedOnTree($out, $row);
            }
        }

        return $out;
    }


    /**
     * Walk the chain of styles via the "based on" field to generate a list of names
     *
     * @param array $styles
     * @param array $heading
     * @return array List of names
     */
    private function flattenBasedOnTree(&$styles, $heading)
    {
        if (isset($heading['based_on_id'])) {
            if (isset($styles[$heading['based_on_id']])) {
                $parent = $styles[$heading['based_on_id']];
                $chain = $this->flattenBasedOnTree($styles, $parent);
                $chain[] = $parent['name'];
                return $chain;
            }
        }

        return null;
    }


    /**
     * Sometimes a numbering format refers to a style, the style itself contains the actual number format
     * This function dereferences the number formats back again
     *
     * @return void
     */
    private function numbersFromStyles()
    {
        foreach ($this->number_formats as $idx => &$num) {
            if (isset($num['styleName'])) {
                $style = $this->styles[$num['styleName']];
                if (! $style) continue;

                $numId = $style['numid'];
                if (! $numId) continue;

                $upstreamFormat = $this->number_formats[$numId];
                if (! $upstreamFormat) continue;

                $num['numFmt'] = $upstreamFormat['numFmt'];
            }
        }
    }


    /**
     * Relationships is how the main document.xml links together with various media files etc
     *
     * @return array
     */
    private function loadRelationships()
    {
        $out = [];

        $doc = new DOMDocument();
        $doc->loadXML($this->zip->getFromName('word/_rels/document.xml.rels'));

        $elems = $doc->getElementsByTagName('Relationship');
        foreach ($elems as $elem) {
            $id = $elem->getAttribute('Id');
            $target = $elem->getAttribute('Target');
            $out[$id] = $target;
        }

        return $out;
    }


    /**
     * For a given paragraph element, determine the finalised style in use
     *
     * @param DOMElement $elem
     * @return array
     */
    private function determineStyle($elem) {
        $out = [];
        $out['style'] = null;
        $out['style_name'] = null;
        $out['based_on'] = null;
        $out['number_format'] = null;
        $out['number_level'] = 0;

        // Get style id and name
        $style = $elem->getElementsByTagName('pStyle');
        if ($style->length) {
            $out['style'] = $style->item(0)->getAttribute('w:val');
            $out['style_name'] = $this->styles[$out['style']]['name'];
            $out['based_on'] = @$this->styles[$out['style']]['based_on_names'];
        }

        // Apply details from the style
        if (isset($this->styles[$out['style']])) {
            $style = $this->styles[$out['style']];

            if (isset($style['numid'])) {
                $out['number_format'] = $this->number_formats[$style['numid']]['numFmt'];
            }
        }

        // Apply local numbering
        $num = $elem->getElementsByTagName('numPr');
        if ($num->length) {
            $id = $num->item(0)->getElementsByTagName('numId');
            if ($id->length) {
                $numberId = $id->item(0)->getAttribute('w:val');
                if (isset($this->number_formats[$numberId]['numFmt'])) {
                    $out['number_format'] = $this->number_formats[$numberId]['numFmt'];
                }
            }

            $id = $num->item(0)->getElementsByTagName('ilvl');
            if ($id->length) {
                $out['number_level'] = $id->item(0)->getAttribute('w:val');
            }
        }

        // If this is a heading style with numbering, kill the numbering
        $expected_tag = $this->determineParaTag($out);
        if ($expected_tag[0] == 'h') {
            $out['number_format'] = null;
            $out['number_level'] = 0;
        }

        // If this uses the numberfing format "none", kill the numbering
        if ($out['number_format'] == 'none') {
            $out['number_format'] = null;
            $out['number_level'] = 0;
        }

        return $out;
    }


    /**
     * For a given style tag, return a paragraph tag (either 'p' or 'h1', 'h2', etc)
     *
     * @param array $style
     * @return string Tag name
     */
    private function determineParaTag($style)
    {
        $name = strtolower($style['style_name']);

        // If the style itself is a heading
        if (strpos($name, 'heading 1') === 0) return 'h1';
        if (strpos($name, 'heading 2') === 0) return 'h2';
        if (strpos($name, 'heading 3') === 0) return 'h3';
        if (strpos($name, 'heading 4') === 0) return 'h4';
        if (strpos($name, 'heading 5') === 0) return 'h5';
        if (strpos($name, 'heading 6') === 0) return 'h6';

        // If one of the styles it's based on is a heading
        if (is_array($style['based_on'])) {
            foreach ($style['based_on'] as $name) {
                $name = strtolower($name);
                if (strpos($name, 'heading 1') === 0) return 'h1';
                if (strpos($name, 'heading 2') === 0) return 'h2';
                if (strpos($name, 'heading 3') === 0) return 'h3';
                if (strpos($name, 'heading 4') === 0) return 'h4';
                if (strpos($name, 'heading 5') === 0) return 'h5';
                if (strpos($name, 'heading 6') === 0) return 'h6';
            }
        }

        return 'p';
    }


    /**
     * For a given style tag, return a list tag (either 'ul' or 'ol')
     *
     * @param array $style
     * @return string Tag name
     */
    private function determineListTag($style)
    {
        if ($style['number_format'] == 'bullet') return 'ul';
        return 'ol';
    }


    /**
     * Render a w:drawing object, i.e. an image
     *
     * @param DOMElement $elem
     * @return string HTML img tag
     */
    private function drawing($elem)
    {
        $graphic = $elem->getElementsByTagName('graphic');
        if (! $graphic->length) return;
        $graphic = $graphic->item(0);

        $blip = $graphic->getElementsByTagName('blip');
        if (! $blip->length) return;
        $id = $blip->item(0)->getAttribute('r:embed');

        // Check resource exists
        $stat = $this->zip->statName('word/' . $this->relationships[$id]);
        if (! $stat) return;

        // Get image size props
        $ext = $graphic->getElementsByTagName('ext')->item(0);
        $sizeX = $this->EMUtoPX((float)$ext->getAttribute('cx'));
        $sizeY = $this->EMUtoPX((float)$ext->getAttribute('cy'));

        // Check ext
        $resname = basename($this->relationships[$id]);
        $fileext = strtolower(File::getExt(trim($resname)));
        if (!in_array($fileext, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
            return '<img error="unsupported-type" res="' . $resname . '" width="' . round($sizeX) . '" height="' . round($sizeY) . '" />';
        }

        // Load resource
        if (empty($this->res[$resname])) {
            $this->res[$resname] = $this->zip->getFromName('word/' . $this->relationships[$id]);
        }

        return '<img rel="' . $resname . '" width="' . round($sizeX, 1) . '" height="' . round($sizeY, 1) . '" />';
    }


    /**
     * Render a w:pict object, i.e. an image
     *
     * @param DOMElement $elem
     * @return string HTML img tag
     */
    private function pict($elem)
    {
        $shape = $elem->getElementsByTagName('shape');
        if (! $shape->length) return;
        $shape = $shape->item(0);

        $imagedata = $shape->getElementsByTagName('imagedata');
        if (! $imagedata->length) return;
        $id = $imagedata->item(0)->getAttribute('r:id');

        // Check resource exists
        $stat = $this->zip->statName('word/' . $this->relationships[$id]);
        if (! $stat) return;

        // Get image size props
        $css = $shape->getAttribute('style');
        $css = $this->parseCss($css);
        $sizeX = 0;
        $sizeY = 0;
        if (preg_match('/[0-9]+/', $css['width'] ?? '', $matches) and !empty($matches[0])) $sizeX = (float) $matches[0];
        if (preg_match('/[0-9]+/', $css['height'] ?? '', $matches) and !empty($matches[0])) $sizeY = (float) $matches[0];

        // Check ext
        $resname = basename($this->relationships[$id]);
        $fileext = strtolower(File::getExt(trim($resname)));
        if (!in_array($fileext, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
            return '<img error="unsupported-type" res="' . $resname . '" width="' . round($sizeX) . '" height="' . round($sizeY) . '" />';
        }

        // Load resource
        if (empty($this->res[$resname])) {
            $this->res[$resname] = $this->zip->getFromName('word/' . $this->relationships[$id]);
        }

        return '<img rel="' . $resname . '" width="' . round($sizeX, 1) . '" height="' . round($sizeY, 1) . '" />';
    }


    /**
     * Convert 'Symbol' font Private-Use-Area characters into real characters
     *
     * @param string $string
     * @return string
     */
    public function symbolSanitizeString($string)
    {
        return preg_replace_callback(
            '/([\x{f020}-\x{f0fe}]{1})/u',
            [$this, 'symbolUnicodeToUtf8Entity'],
            $string
        );
    }


    /**
     * Regular expression callback for Symbol font conversion
     *
     * @param array $wchar
     * @return string
     */
    public function symbolUnicodeToUtf8Entity(array $wchar): string
    {
        $conv = hexdec(bin2hex($wchar[1]));
        $charcode = self::$symbol_font_map[$conv];
        return ($charcode ? mb_convert_encoding('&#' . intval($charcode) . ';', 'UTF-8', 'HTML-ENTITIES') : '?');
    }


    /**
     * Parse given css
     *
     * @param string $css
     * @return array
     */
    private function parseCss($css)
    {
        $out = [];

        $rules = explode(';', $css);
        foreach ($rules as $r) {
            list($key, $val) = explode(':', $r, 2);

            if ($key and $val) {
                $out[trim($key)] = trim($val);
            }
        }

        return $out;
    }


    /**
     * EM units to pixels
     *
     * @param int|float $emu
     * @param int $dpi
     * @return float Pixel value
     */
    private function EMUtoPX($emu, $dpi = 72)
    {
        return $emu / 914400 * $dpi;
    }


    /**
    * Mapping between PUA for Symbol font to regular characters
    *
    * Key - UTF-8 encoded bytes
    * Value - Widechar bytes
    **/
    static $symbol_font_map = [
        15696032 => 32,
        15696033 => 33,
        15696034 => 8704,
        15696035 => 35,
        15696036 => 8707,
        15696037 => 37,
        15696038 => 38,
        15696039 => 8715,
        15696040 => 40,
        15696041 => 41,
        15696042 => 8727,
        15696043 => 43,
        15696044 => 44,
        15696045 => 8722,
        15696046 => 46,
        15696047 => 47,
        15696048 => 48,
        15696049 => 49,
        15696050 => 50,
        15696051 => 51,
        15696052 => 52,
        15696053 => 53,
        15696054 => 54,
        15696055 => 55,
        15696056 => 56,
        15696057 => 57,
        15696058 => 58,
        15696059 => 59,
        15696060 => 60,
        15696061 => 61,
        15696062 => 62,
        15696063 => 63,
        15696256 => 8773,
        15696257 => 913,
        15696258 => 914,
        15696259 => 935,
        15696260 => 916,
        15696261 => 917,
        15696262 => 934,
        15696263 => 915,
        15696264 => 919,
        15696265 => 921,
        15696266 => 977,
        15696267 => 922,
        15696268 => 923,
        15696269 => 924,
        15696270 => 925,
        15696271 => 927,
        15696272 => 928,
        15696273 => 920,
        15696274 => 929,
        15696275 => 931,
        15696276 => 932,
        15696277 => 933,
        15696278 => 962,
        15696279 => 937,
        15696280 => 926,
        15696281 => 936,
        15696282 => 918,
        15696283 => 91,
        15696284 => 8756,
        15696285 => 93,
        15696286 => 8869,
        15696287 => 95,
        15696288 => 63717,
        15696289 => 945,
        15696290 => 946,
        15696291 => 967,
        15696292 => 948,
        15696293 => 949,
        15696294 => 966,
        15696295 => 947,
        15696296 => 951,
        15696297 => 953,
        15696298 => 981,
        15696299 => 954,
        15696300 => 955,
        15696301 => 956,
        15696302 => 957,
        15696303 => 959,
        15696304 => 960,
        15696305 => 952,
        15696306 => 961,
        15696307 => 963,
        15696308 => 964,
        15696309 => 965,
        15696310 => 982,
        15696311 => 969,
        15696312 => 958,
        15696313 => 968,
        15696314 => 950,
        15696315 => 123,
        15696316 => 124,
        15696317 => 125,
        15696318 => 8764,
        15696544 => 8364,
        15696545 => 978,
        15696546 => 8242,
        15696547 => 8804,
        15696548 => 8260,
        15696549 => 8734,
        15696550 => 402,
        15696551 => 9827,
        15696552 => 9830,
        15696553 => 9829,
        15696554 => 9824,
        15696555 => 8596,
        15696556 => 8592,
        15696557 => 8593,
        15696558 => 8594,
        15696559 => 8595,
        15696560 => 176,
        15696561 => 177,
        15696562 => 8243,
        15696563 => 8805,
        15696564 => 215,
        15696565 => 8733,
        15696566 => 8706,
        15696567 => 8226,
        15696568 => 247,
        15696569 => 8800,
        15696570 => 8801,
        15696571 => 8776,
        15696572 => 8230,
        15696573 => 63718,
        15696574 => 63719,
        15696575 => 8629,
        15696768 => 8501,
        15696769 => 8465,
        15696770 => 8476,
        15696771 => 8472,
        15696772 => 8855,
        15696773 => 8853,
        15696774 => 8709,
        15696775 => 8745,
        15696776 => 8746,
        15696777 => 8835,
        15696778 => 8839,
        15696779 => 8836,
        15696780 => 8834,
        15696781 => 8838,
        15696782 => 8712,
        15696783 => 8713,
        15696784 => 8736,
        15696785 => 8711,
        15696786 => 63194,
        15696787 => 63193,
        15696788 => 63195,
        15696789 => 8719,
        15696790 => 8730,
        15696791 => 8901,
        15696792 => 172,
        15696793 => 8743,
        15696794 => 8744,
        15696795 => 8660,
        15696796 => 8656,
        15696797 => 8657,
        15696798 => 8658,
        15696799 => 8659,
        15696800 => 9674,
        15696801 => 9001,
        15696802 => 63720,
        15696803 => 63721,
        15696804 => 63722,
        15696805 => 8721,
        15696806 => 63723,
        15696807 => 63724,
        15696808 => 63725,
        15696809 => 63726,
        15696810 => 63727,
        15696811 => 63728,
        15696812 => 63729,
        15696813 => 63730,
        15696814 => 63731,
        15696815 => 63732,
        15696817 => 9002,
        15696818 => 8747,
        15696819 => 8992,
        15696820 => 63733,
        15696821 => 8993,
        15696822 => 63734,
        15696823 => 63735,
        15696824 => 63736,
        15696825 => 63737,
        15696826 => 63738,
        15696827 => 63739,
        15696828 => 63740,
        15696829 => 63741,
        15696830 => 63742,
    ];
}


class DocImportDOCXRun
{
    public $elem;
    public $hyperlink;
    public $rendered;


    /**
     * Constructor
     *
     * @param DOMElement $elem
     */
    public function __construct($elem)
    {
        $this->elem = $elem;
    }
}
