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

use Exception;

use Kohana;


/**
* Functions for front-end search indexing
**/
class Search
{
    const WORD_LENGTH = 16;

    private static $table;
    private static $record_id;

    private static $query_and = false;

    public static $stop_words = array('a','above','across','after','afterwards','again','against','all','almost',
        'alone','along','already','also','although','always','am','among','amongst','amoungst','amount', 'an','and','another',
        'any','anyhow','anyone','anything','anyway','anywhere','are','around','as', 'at','b','back','be','became','because','become',
        'becomes','becoming','been','before','beforehand','behind','being','below','beside','besides','between','beyond',
        'bill','both','bottom','but','by','c','call','can','cannot','cant','co','con','could','couldnt','cry','d','de','describe',
        'detail','do','done','down','due','during','each','e','eg','eight','either','eleven','else','elsewhere','empty','enough',
        'etc','even','ever','every','everyone','everything','everywhere','except','f','few','fifteen','fill','find','fire',
        'first','five','for','former','formerly','forty','found','four','from','front','full','further','g','get','give','go',
        'h','had','has','hasnt','have','he','hence','her','here','hereafter','hereby','herein','hereupon','hers','herself','him',
        'himself','his','how','however','hundred','i','ie','if','in','inc','indeed','interest','into','is','it','its','itself',
        'j','k','keep','l','last','latter','latterly','least','less','ltd','m','made','many','may','me','meanwhile','might','mill','mine',
        'more','moreover','most','mostly','move','much','must','my','myself','n','name','namely','neither','never','nevertheless',
        'next','nine','no','nobody','none','noone','nor','not','nothing','now','nowhere','o','of','off','often','on','once','one',
        'only','onto','or','other','others','otherwise','our','ours','ourselves','out','over','own','p','part','per','perhaps',
        'please','put','q','r','rather','re','s','same','see','seem','seemed','seeming','seems','serious','several','she','should','show',
        'side','since','sincere','six','sixty','so','some','somehow','someone','something','sometime','sometimes','somewhere',
        'still','such','system','t','take','ten','than','that','the','their','them','themselves','then','thence','there','thereafter',
        'thereby','therefore','therein','thereupon','these','they','thick','thin','third','this','those','though','three','through',
        'throughout','thus','to','together','too','top','toward','towards','twelve','twenty','two','u','un','under','until','up',
        'upon','us','v','very','via','w','was','we','well','were','what','whatever','when','whence','whenever','where','whereafter','whereas',
        'whereby','wherein','whereupon','wherever','whether','which','while','whither','who','whoever','whole','whom','whose','why',
        'will','with','within','without','would','x','y','yet','you','your','yours','yourself','yourselves','z',
    );


    /**
    * Remove "stop words" from keyword list
    *
    * @param array $keywords
    * @return array With removed stop words
    */
    public static function filterStopWords($keywords)
    {
        foreach ($keywords as $idx => $keyword) {
            if (in_array(strtolower($keyword), self::$stop_words)) {
                unset($keywords[$idx]);
            }
        }

        return $keywords;
    }


    /**
     * Selects the currently active index
     * @param string $table Table name, without prefix
     * @param int $record_id Record ID
     * @return void
     */
    public static function selectIndex($table, $record_id)
    {
        Pdb::validateIdentifier($table);

        self::$table = $table;
        self::$record_id = (int) $record_id;
    }


    /**
    * Clears the index chosen with selectIndex()
    **/
    public static function clearIndex()
    {
        Pdb::delete(self::$table, ['record_id' => self::$record_id]);
        return true;
    }


    /**
    * Splits up text, and adds the keywords which are found into the index chosen with selectIndex()
    * Usually should be run from within a transaction
    *
    * @param string $text The text to process.
    * @param float $relevancy_multiplier A multiplier to apply to the relavancy of all keywords found.
    **/
    public static function indexText($text, $relevancy_multiplier = 1)
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('/[^-@a-zA-Z0-9 ]/', '', $text);
        $words = self::filterStopWords(explode(' ', $text));

        $cur_relevancy = 2;

        $unique_words = array();
        foreach ($words as $word) {
            if (strlen($word) < 2) continue;
            $word = substr($word, 0, self::WORD_LENGTH);

            if (!isset($unique_words[$word])) $unique_words[$word] = 0.0;
            $unique_words[$word] += $cur_relevancy;

            $single = Inflector::singular($word);
            if ($single != $word) {
                if (!isset($unique_words[$single])) {
                    $unique_words[$single] = 0.0;
                }
                $unique_words[$single] += $cur_relevancy * 0.7;
            }

            if ($cur_relevancy > 1) $cur_relevancy -= 0.01;
        }

        return self::addWordsIndex($unique_words, $relevancy_multiplier);
    }


    /**
    * Splits up html, and adds the keywords which are found into the index chosen with selectIndex()
    * Usually should be run from within a transaction
    *
    * @param string $text The text to process.
    * @param float $relevancy_multiplier A multiplier to apply to the relavancy of all keywords found.
    **/
    public static function indexHtml($text, $relevancy_multiplier = 1)
    {
        $text = Text::plain($text, 0);
        return self::indexText($text, $relevancy_multiplier);
    }


    /**
     * Accepts an array of relevant words and adds it into the index chosen with selectIndex()
     * Usually should be run from within a transaction
     * @param array $words word => relevancy mapping
     * @param float $relevancy_multiplier
     */
    private static function addWordsIndex(array $words, $relevancy_multiplier = 1)
    {

        $table = self::$table;

        if (count($words) == 0) return true;


        // Build a reverse index of name => id
        $vals = [];
        $where = Pdb::buildClause([['name', 'IN', array_keys($words)]], $vals);
        $q = "SELECT name, id FROM ~search_keywords WHERE " . $where;
        $res = Pdb::query($q, $vals, 'map');

        // Iterate through the words, doing an insert...update of the records
        // If the search_keywords records do not exist, they will be added
        foreach ($words as $word => $relevancy) {
            $relevancy = $relevancy * $relevancy_multiplier;

            if (empty($word_ids[$word])) {
                $word_ids[$word] = Pdb::insert('search_keywords', ['name' => $word]);
            }

            $q = "INSERT INTO ~{$table}
                (keyword_id, record_id, relevancy)
                VALUES
                (:word_id, :rec_id, :rel)
                ON DUPLICATE KEY UPDATE
                relevancy = relevancy + :rel";
            $params = [
                'word_id' => $word_ids[$word],
                'rec_id' => self::$record_id,
                'rel' => $relevancy
            ];
            Pdb::query($q, $params, 'count');
        }


        return true;
    }


    /**
    * Looks for syncronisation errors in the indexes and fixes them
    **/
    public static function cleanup($reference_table)
    {
        Pdb::validateIdentifier($reference_table);

        $table = self::$table;

        // Find keyword records which don't have a matching reference record
        $q = "SELECT DISTINCT keywords.record_id
            FROM ~{$table} AS keywords
            LEFT JOIN ~{$reference_table} AS ref ON keywords.record_id = ref.id
            WHERE ref.id IS NULL";
        $ids = Pdb::q($q, [], 'col');
        if (count($ids) == 0) return true;

        // And delete them
        Pdb::delete($table, [['record_id', 'IN', $ids]]);

        return true;
    }


    /**
    * Set the 'query_and' parameter, which defines the handling of search terms
    **/
    public static function queryAnd($val)
    {
        self::$query_and = $val;
    }


    /**
    * Does a query for a term, against a set of search handlers
    *
    * @param string $q The keyword(s) to search for.
    * @param array $search_handlers Search handlers to use. Expects an array of SearchHandler objects.
    * @param int $page The page number to show. Should be 0-based (first page is num 0)
    * @param int $num_per_page The number of records to show per page. Defaults to a the sprout config option
    * @return array The array contains 4 keys:
    *         [0] PDOStatement, with three columns: record_id; controller_class; relevancy.
    *         [1] array The list of keywords used for the search
    *         [2] int The total number of results available; results are
    *             paginated, so this is likely to be more than the number of
    *             results returned.
    *         [3] int The total number of pages of results
    **/
    public static function query($query, $search_handlers, $page = 0, $num_per_page = null)
    {

        $page = (int) $page;
        $num_per_page = (int) $num_per_page;
        $conn = Pdb::getConnection();

        if ($num_per_page == 0) $num_per_page = Kohana::config('sprout.search_per_page');
        if ($num_per_page == 0) $num_per_page = 10;

        if (count($search_handlers) == 0) throw new Exception("No search handlers provided");
        if ($page < 0) throw new Exception("Invalid page specified");

        $offset = $page * $num_per_page;

        // Get keywords
        if (is_array($query)) {
            $keywords = $query;
        } else {
            $keywords = explode(' ', trim($query));
        }

        // Pre-process keywords
        foreach ($keywords as $idx => $word) {
            if (strlen($word) <= 2) unset ($keywords[$idx]);
        }

        $sql_keywords = array();
        foreach ($keywords as $word) {
            $word = strtolower($word);
            $word = $conn->quote($word);
            $sql_keywords[] = $word;
        }

        // Build the SELECT queries which will make up the UNION
        $queries = array();
        $i = 0;
        foreach ($search_handlers as $handler) {
            $i++;

            $where = $handler->getWhere();
            $joins = $handler->getJoins();
            $having = $handler->getHaving();

            if ($sql_keywords) {
                $joins[] = "INNER JOIN ~{$handler->getTable()} AS records ON records.record_id = main.id";

                $joins[] = "INNER JOIN ~search_keywords AS keywords ON keywords.id = records.keyword_id";

                $where[] = 'keywords.name IN (' . implode(', ', $sql_keywords) . ')';

                if (self::$query_and) {
                    $having[] = 'COUNT(keywords.name) = ' . count($sql_keywords);
                }

                $having[] = 'relevancy > 0';
            }

            if (count($where) == 0) continue;

            $joins = implode("\n                    ", $joins);
            $where = implode(' AND ', $where);
            $having = implode(' AND ', $having);

            if ($having == '') $having = '1';


            $q = '(SELECT ' . ($i == 1 ? 'SQL_CALC_FOUND_ROWS' : '') . " main.id AS record_id, " . $conn->quote($handler->getCtlrName()) . " AS controller_class, ";
            $q .= ($sql_keywords ? 'SUM(records.relevancy)' : '1') . ' AS relevancy';
            $q .= "
                FROM ~{$handler->getMainTable()} AS main
                {$joins}
                WHERE {$where}
                GROUP BY main.id
                HAVING {$having})";

            $queries[] = $q;
        }

        if (count($queries) == 0) {
            return false;
        }

        // Combine UNIONs
        $q = implode("\nUNION DISTINCT\n", $queries);
        $q .= "\nORDER BY relevancy DESC";
        $q .= "\nLIMIT {$num_per_page} OFFSET {$offset}";

        // Run the query
        $res = Pdb::query($q, [], 'pdo');

        $q = "SELECT FOUND_ROWS()";
        $num_results = Pdb::query($q, [], 'val');
        $num_pages = ceil($num_results / $num_per_page);

        return array($res, $keywords, $num_results, $num_pages);
    }


    /**
    * Return HTML to perform pagination.
    *
    * $curr_page The current page number (1-based).
    * $num_page The total number of pages.
    * $div_class If provided, a wrapper div will be generated, with this class name.
    **/
    public static function paginate($curr_page, $num_pages, $div_class = null)
    {
        $out = '';
        $url_base = Url::withoutArgs('page');

        if ($num_pages == 1) return null;

        // Start and end to show
        $start = $curr_page - 2;
        $end = $curr_page + 4;
        if ($start < 1) $start = 1;
        if ($end > $num_pages) $end = $num_pages;

        if ($div_class) $out .= '<div class="' . $div_class . '">';

        if ($curr_page > 1) {
            $out .= "<a href=\"{$url_base}page=" . ($curr_page - 1) . "\" class=\"page-prev\">Prev</a> ";
        }

        for ($i = $start; $i <= $end; $i++) {
            $class = ($i == $curr_page ? 'page on' : 'page');
            $out .= " <a href=\"{$url_base}page={$i}\" class=\"{$class}\">{$i}</a> ";
        }

        if ($curr_page < $end) {
            $out .= " <a href=\"{$url_base}page=" . ($curr_page + 1) . "\" class=\"page-next\">Next</a>";
        }

        if ($div_class) $out .= '</div>';

        return $out;
    }

}
