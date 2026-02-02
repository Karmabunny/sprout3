<?php
namespace Sprout\Helpers;

use Exception;
use Kohana;

use Sprout\Helpers\HttpReq;
use Sprout\Helpers\Needs;
use Sprout\Helpers\PhpView;


class Recaptcha3
{
    /**
     * @var float Less than this: we think they are bad
     */
    const MIN_SCORE = 0.2;


    /**
     * Includes required JS libraries
     * Should be used on all templates
     *
     * @return void
     */
    public static function skin()
    {
        $key = Kohana::config('sprout.recaptcha_public_key');
        if (empty($key)) throw new Exception('ReCAPTCHA key not found');

        Needs::addJavascriptInclude(sprintf('https://www.google.com/recaptcha/api.js?render=%s', $key));
    }


    /**
     * Renders the captcha field
     *
     * @return void Echos HTML directly
     */
    public static function field()
    {
        $key = Kohana::config('sprout.recaptcha_public_key');
        if (empty($key)) throw new Exception('ReCAPTCHA key not found');

        $view = new PhpView('sprout/recaptcha3');
        $view->key = $key;

        echo $view->render();
    }


    /**
     * Checks the captcha field against the submitted text
     *
     * @throws Exception On invalid response
     * @return bool True on success
     */
    public static function check()
    {
        // Validate form
        if (empty($_POST['g-recaptcha-response'])) return false;

        $key = Kohana::config('sprout.recaptcha_private_key');
        if (empty($key)) throw new Exception('ReCAPTCHA key not found');

        // Prep data for request
        $data = [];
        $data['secret'] = $key;
        $data['response'] = $_POST['g-recaptcha-response'];
        $data['remoteip'] = $_SERVER['REMOTE_ADDR'];

        // Post request
        $response = HttpReq::req(
            'https://www.google.com/recaptcha/api/siteverify',
            ['method' => 'post'],
            $data
        );

        // Decode and validate response
        $response = json_decode($response ?: '', true);
        if (!isset($response['score'])) return false;

        // Validate user's score
        if ($response['score'] > self::MIN_SCORE) return true;

        return false;
    }
}
