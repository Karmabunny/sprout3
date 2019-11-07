<?php
/**
 * Copyright (C) 2018 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */



/*
 * Specific common exceptions that don't follow the rule set below are handled individually
 *      array of problem words (with word as key, syllable count as value).
 * Common reasons we need to override some words:
 *      - Trailing 'e' is pronounced
 *      - Portmanteaus
*/

// Minimum word count
$config['word_count'] = 300;


// Average words per sentence as percentage
$config['average_words_sentence'] = 20;


// Readability score 0 = hard, 100 = easy
$config['readability_scores'] = [
    [
        'range' => [90, 100],
        'desc' => 'Very easy to read.',
        'type' => 'good',
        'fix' => '',
    ],
    [
        'range' => [80, 90],
        'desc' => 'Easy to read.',
        'type' => 'good',
        'fix' => '',
    ],
    [
        'range' => [70,80],
        'desc' => 'Fairly easy to read.',
        'type' => 'good',
        'fix' => '',
    ],
    [
        'range' => [60,70],
        'desc' => 'Easily understood.',
        'type' => 'good',
        'fix' => '',
    ],
    [
        'range' => [50,60],
        'desc' => 'Fairly difficult to read.',
        'type' => 'problem',
        'fix' => 'Write short sentences, add subheadings and use connecting words.',
    ],
    [
        'range' => [30,50],
        'desc' => 'Difficult to read.',
        'type' => 'problem',
        'fix' => 'Write short sentences, add subheadings and use connecting words.',
    ],
    [
        'range' => [0,30],
        'desc' => 'Very difficult to read.',
        'type' => 'problem',
        'fix' => 'Write short sentences, add subheadings and use connecting words.',
    ]
];


// SEO words to ignore
$config['stop_words'] = ["a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost",
    "alone", "along", "already", "also","although","always","am","among", "amongst", "amoungst", "amount",  "an", "and", "another",
    "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as",  "at", "b", "back","be","became", "because","become",
    "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond",
    "bill", "both", "bottom","but", "by", "c", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "d", "de", "describe",
    "detail", "do", "done", "down", "due", "during", "each", "e", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough",
    "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "f", "few", "fifteen", "fill", "find", "fire",
    "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "g", "get", "give", "go",
    "h", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him",
    "himself", "his", "how", "however", "hundred", "i", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself",
    "j", "k", "keep", "l", "last", "latter", "latterly", "least", "less", "ltd", "m", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine",
    "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "n", "name", "namely", "neither", "never", "nevertheless",
    "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "o", "of", "off", "often", "on", "once", "one",
    "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own", "p", "part", "per", "perhaps",
    "please", "put", "q", "r", "rather", "re", "s", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show",
    "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere",
    "still", "such", "system", "t", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter",
    "thereby", "therefore", "therein", "thereupon", "these", "they", "thick", "thin", "third", "this", "those", "though", "three", "through",
    "throughout", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "u", "un", "under", "until", "up",
    "upon", "us", "v", "very", "via", "w", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas",
    "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why",
    "will", "with", "within", "without", "would", "x", "y", "yet", "you", "your", "yours", "yourself", "yourselves", "z",
];


$config['problem_words'] = [
    'abalone' => 4,
    'abare' => 3,
    'abed' => 2,
    'abruzzese' => 4,
    'abbruzzese' => 4,
    'aborigine' => 5,
    'acreage' => 3,
    'adame' => 3,
    'adieu' => 2,
    'adobe' => 3,
    'anemone' => 4,
    'apache' => 3,
    'aphrodite' => 4,
    'apostrophe' => 4,
    'ariadne' => 4,
    'cafe' => 2,
    'calliope' => 4,
    'catastrophe' => 4,
    'chile' => 2,
    'chloe' => 2,
    'circe' => 2,
    'coyote' => 3,
    'epitome' => 4,
    'forever' => 3,
    'gethsemane' => 4,
    'guacamole' => 4,
    'hyperbole' => 4,
    'jesse' => 2,
    'jukebox' => 2,
    'karate' => 3,
    'machete' => 3,
    'maybe' => 2,
    'people' => 2,
    'recipe' => 3,
    'sesame' => 3,
    'shoreline' => 2,
    'simile' => 3,
    'syncope' => 3,
    'tamale' => 3,
    'yosemite' => 4,
    'daphne' => 2,
    'eurydice' => 4,
    'euterpe' => 3,
    'hermione' => 4,
    'penelope' => 4,
    'persephone' => 4,
    'phoebe' => 2,
    'zoe' => 2,
];

// These syllables would be counted as two but should be one
$config['sub_syllables'] = [
    'cia(l|$)',
    'tia',
    'cius',
    'cious',
    '[^aeiou]giu',
    '[aeiouy][^aeiouy]ion',
    'iou',
    'sia$',
    'eous$',
    '[oa]gue$',
    '.[^aeiuoycgltdb]{2,}ed$',
    '.ely$',
    '^jua',
    'uai',
    'eau',
    '[aeiouy](b|c|ch|d|dg|f|g|gh|gn|k|l|ll|lv|m|mm|n|nc|ng|nn|p|r|rc|rn|rs|rv|s|sc|sk|sl|squ|ss|st|t|th|v|y|z)e$',
    '[aeiouy](b|c|ch|dg|f|g|gh|gn|k|l|lch|ll|lv|m|mm|n|nc|ng|nch|nn|p|r|rc|rn|rs|rv|s|sc|sk|sl|squ|ss|th|v|y|z)ed$',
    '[aeiouy](b|ch|d|f|gh|gn|k|l|lch|ll|lv|m|mm|n|nch|nn|p|r|rn|rs|rv|s|sc|sk|sl|squ|ss|st|t|th|v|y)es$',
    '^busi$',
];

$config['add_syllables'] = [
    '([^s]|^)ia',
    'riet',
    'dien',
    'iu',
    'io',
    'eo($|[b-df-hj-np-tv-z])',
    'ii',
    '[ou]a$',
    '[aeiouym]bl$',
    '[aeiou]{3}',
    '[aeiou]y[aeiou]',
    '^mc',
    'ism$',
    'asm$',
    'thm$',
    '([^aeiouy])\1l$',
    '[^l]lien',
    '^coa[dglx].',
    '[^gq]ua[^auieo]',
    'dnt$',
    'uity$',
    '[^aeiouy]ie(r|st|t)$',
    'eings?$',
    '[aeiouy]sh?e[rsd]$',
    'iell',
    'dea$',
    'real',
    '[^aeiou]y[ae]',
    'gean$',
    'uen',
];

// Single syllable prefixes and suffixes
$config['single_affix'] = [
    '`^un`',
    '`^fore`',
    '`^ware`',
    '`^none?`',
    '`^out`',
    '`^post`',
    '`^sub`',
    '`^pre`',
    '`^pro`',
    '`^dis`',
    '`^side`',
    '`ly$`',
    '`less$`',
    '`some$`',
    '`ful$`',
    '`ers?$`',
    '`ness$`',
    '`cians?$`',
    '`ments?$`',
    '`ettes?$`',
    '`villes?$`',
    '`ships?$`',
    '`sides?$`',
    '`ports?$`',
    '`shires?$`',
    '`tion(ed)?$`',
];

// Double syllable prefixes and suffixes
$config['double_affix'] = [
    '`^above`',
    '`^ant[ie]`',
    '`^counter`',
    '`^hyper`',
    '`^afore`',
    '`^agri`',
    '`^in[ft]ra`',
    '`^inter`',
    '`^over`',
    '`^semi`',
    '`^ultra`',
    '`^under`',
    '`^extra`',
    '`^dia`',
    '`^micro`',
    '`^mega`',
    '`^kilo`',
    '`^pico`',
    '`^nano`',
    '`^macro`',
    '`berry$`',
    '`woman$`',
    '`women$`',
];

// Triple syllable prefixes and suffixes
$config['triple_affix'] = [
    '`ology$`',
    '`ologist$`',
    '`onomy$`',
    '`onomist$`',
];
