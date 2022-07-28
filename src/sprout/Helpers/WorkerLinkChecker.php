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

use DOMDocument;

use Kohana;


class WorkerLinkChecker extends WorkerBase
{
    protected $job_name = 'Link Checker';


    protected $metric_names = array(
        1 => 'Total pages',
        2 => 'Pages processed',
        3 => 'Bad links found',
    );


    /**
    * Do stuff
    **/
    public function run($email_address = null)
    {
        // Downloading pages, parsing them, and producing reports are
        // apparently a bit expensive. So we need a bit more than the
        // regular 32M.
        ini_set('memory_limit', '128M');

        $q = "SELECT page.id, page.subsite_id, page.name, MAX(rev.id) AS rev_id
            FROM ~pages AS page
            INNER JOIN ~page_revisions AS rev ON rev.page_id = page.id
                AND rev.status = 'live' AND rev.type = 'standard'
            WHERE page.active = 1
            GROUP BY page.id
            ORDER BY page.id";
        $res = Pdb::query($q, [], 'map-arr');

        // Fetch and collate rich text widgets to produce page text
        if (count($res) > 0) {
            $rev_ids = [];
            foreach ($res as &$row) {
                $row['text'] = '';
                $rev_ids[] = (int) $row['rev_id'];
            }
            unset($row);
            $rev_ids = implode(', ', $rev_ids);

            $q = "SELECT rev.id AS rev_id, widget.settings
                FROM ~page_revisions AS rev
                INNER JOIN ~page_widgets AS widget ON rev.id = widget.page_revision_id
                    AND widget.area_id = 1 AND widget.type = 'RichText'
                WHERE rev.id IN ({$rev_ids})
                ORDER BY widget.record_order";
            $widgets = Pdb::q($q, [], 'pdo');
            foreach ($widgets as $widget) {
                $settings = json_decode($widget['settings'], true);
                foreach ($res as &$row) {
                    if ($row['rev_id'] == $widget['rev_id']) {
                        if ($row['text']) $row['text'] .= "\n";
                        $row['text'] .= $settings['text'];
                        break;
                    }
                }
                unset($row);
            }
            $widgets->closeCursor();
        }

        Worker::message('Found ' . count($res) . ' page(s) total');
        Worker::metric(1, count($res));
        Worker::metric(2, 0);
        Worker::metric(3, 0);

        $errs = array();
        $processed = 0;
        $found = 0;
        foreach ($res as $row) {
            Worker::message("Checking page # {$row['id']}; '{$row['name']}'");
            $processed++;
            $found += $this->checkPage($row, $errs);
            Worker::metric(2, $processed);
            Worker::metric(3, $found);
        }

        Worker::message('');
        Worker::message(count($errs) . ' pages have bad link(s)');
        Worker::message($found . ' bad link(s) total');
        Worker::message('');

        if (count($errs) > 0) {
            Worker::message("Preparing HTML report");

            $view = new PhpView('sprout/email/link_checker');
            $view->errs = $errs;
            $view = $view->render();


            Worker::message("Preparing CSV report");

            $csv = $this->buildCsv($errs);


            Worker::message('');
            Worker::message("Sending reports via email");

            if ($email_address) {
                $ops = array(array(
                    'name' => 'Unknown user',
                    'email' => $email_address,
                ));
            } else {
                $ops = AdminPerms::getOperatorsWithAccess('access_reportemail');
            }

            $sent = 0;
            foreach ($ops as $row) {
                if ($row['email'] == '') continue;

                $mail = new Email();
                $mail->AddAddress($row['email']);
                $mail->Subject = 'Link checker report for site ' . Kohana::config('sprout.site_title');
                $mail->SkinnedHTML($view);
                $mail->AddAttachment($csv, 'link_checker_report_' . date('Y_m_d') . '.csv', 'base64', 'text/csv');
                $result = $mail->Send();

                if ($result) {
                    Worker::message("Sent report to {$row['name']} ({$row['email']})");
                    $sent++;
                } else {
                    Worker::message("Sending of report to {$row['name']} ({$row['email']}) failed!");
                }
            }

            Worker::message("{$sent} email(s) sent successfully.");
        }

        Worker::message('');
        Worker::success();
    }


    /**
    * Checks a single page
    **/
    private function checkPage(&$row, &$errs)
    {
        $dom = new DOMDocument();
        if (! @$dom->loadHTML($row['text'])) return;

        $resultname = $row['id'] . ':' . $row['subsite_id'] . ':' . $row['name'];

        $as = $dom->getElementsByTagName('a');
        $numfound = 0;
        foreach ($as as $elem) {
            $href = $elem->getAttribute('href');
            $href = urldecode($href);
            $href = str_replace(' ', '%20', $href);

            $found = $this->checkUrl($href, $row['subsite_id']);

            if ($found !== true) {
                $errs[$resultname][] = array('page_id' => $row['id'], 'page_name' => $row['name'], 'link_href' => $href, 'link_text' => $elem->textContent, 'err' => $found);
                $numfound++;
            }
        }

        unset($dom);

        return $numfound;
    }


    /**
    * Returns TRUE if the given URL is found, a string of the error message if the URL was not found.
    **/
    public function checkUrl($href, $subsite_id = 1)
    {
        $href = trim($href);

        if (preg_match('/^(javascript|mailto|news|irc|file|data|sms|tel|callto|skype|chrome|about|ftp):/i', $href)) {
            return true;
        }

        if (! preg_match('!^[a-z]+://!i', $href)) {
            $href = Subsites::getAbsRoot($subsite_id) . trim($href, '/');
        }

        if (! preg_match('!^http!', $href)) {
            return '599 Not a URL';
        }

        // If behind a proxy, we cannot see ourselves properly.
        // TODO: Try to think up a way this could be made to work.
        if (preg_match('!://localhost/!', $href) and $_SERVER['SERVER_PORT'] != 80) {
            return true;
        }

        $href = str_replace(' ', '%20', $href);

        // TODO This whole things should be using HttpReq but I'm in a rush rn.

        $opts = array('http' => array(
            'method' => 'HEAD',
            'follow_location' => true,
            'ignore_errors' => false,
            'user_agent' => 'SproutLinkChecker/' . Sprout::getVersion() . ' (PHP/' . phpversion() . ')',
        ));
        $opts['ssl'] = array(
            'cafile' => APPPATH . 'cacert.pem',
        );
        $context = stream_context_create($opts);


        $h = @fopen($href, 'r', false, $context);

        if ($h === false) {
            if (empty($http_response_header)) {
                return '599 Not a URL';
            }

            foreach ($http_response_header as $hdr) {
                if (strpos($hdr, 'HTTP') === 0) {
                    $status_line = $hdr;
                }
            }

            if (! preg_match('/([0-9][0-9][0-9]).*$/', $status_line, $matches)) {
                return '599 Invalid response';
            }

            list($message, $code) = $matches;

            if ($code >= 400 and $code <= 599) {
                return $message;
            }

            return '599 Network Error';
        }


        fclose($h);
        return true;
    }


    /**
     * Build a CSV for the specified errors.
     *
     * @param array $errs
     * @return string|false csv file path or false on error
     */
    private function buildCsv($errs)
    {
        $csv = function($errs) {
            foreach ($errs as $ee) {
                foreach ($ee as $eee) {
                    yield $eee;
                }
            }
        };

        $path = tempnam(sys_get_temp_dir(), 'export');
        $stream = fopen($path, 'w');

        $ok = QueryTo::csvFile($csv($errs), $stream);
        if (!$ok) return false;

        fclose($stream);

        return $path;
    }

}
