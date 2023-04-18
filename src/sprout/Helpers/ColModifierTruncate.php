<?php
namespace Sprout\Helpers;

use Sprout\Helpers\Enc;
use Sprout\Helpers\Text;
use Sprout\Helpers\UnescapedColModifier;


/**
 * Truncate the word count of a column with given word limit trailed by ellipsis symbol, with the full text in a title attribute
 */
final class ColModifierTruncate extends UnescapedColModifier
{
    private $word_limit = 4;

    public function __construct($word_limit = 4)
    {
        $this->word_limit = max((int)$word_limit, 1);
    }

    public function modify($val, $field_name, $row)
    {
        return '<span title="' . Enc::html($val) . '">' . Enc::html(Text::plain($val, $this->word_limit)) . '</span>';
    }
}
