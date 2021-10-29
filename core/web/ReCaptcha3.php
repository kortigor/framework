<?php

declare(strict_types=1);

namespace core\web;

use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;

/**
 * Google reCAPTCHA v3 check implementation.
 * 
 * @link https://github.com/google/recaptcha (required)
 * @see https://www.google.com/recaptcha/about/
 * @see \core\web\ReCaptcha3Client
 */
class ReCaptcha3
{
	/**
	 * @var string Form input name
	 */
	const FORM_INPUT_NAME = '___reCaptcha_Token_Auto_Added_InputZz';

	/**
	 * @var float
	 */
	protected float $score = 0.5;

	/**
	 * Constructor.
	 *
	 * @param string $siteKey Public reCAPTCHA3 site key.
	 * @param string $action Action name. For further verification and comparison with the answer.
	 */
	public function __construct(protected string $siteKey, protected string $action)
	{
	}

	/**
	 * Set score threshold.
	 * If response score more than threshold value - captcha is resolved.
	 *
	 * @param float $score Score value to resolve
	 *
	 * @return self
	 */
	public function setScore(float $score): self
	{
		$this->score = $score;
		return $this;
	}

	/**
	 * Get recaptcha token from POST response.
	 * 
	 * @return string Token value. If there is no token in response empty string returned.
	 */
	public function getTokenFromPost(): string
	{
		return filter_input(INPUT_POST, static::FORM_INPUT_NAME, FILTER_SANITIZE_STRING) ?? '';
	}

	/**
	 * Check and get recaptcha response.
	 *
	 * @param string $secretKey Secret key.
	 * @param string $token Recaptcha token from the form, to check.
	 *
	 * @return Response Recaptcha response object.
	 */
	public function check(string $secretKey, string $token): Response
	{
		/*
		Using google recaptcha library: https://github.com/google/recaptcha
		If file_get_contents() is locked down on your PHP installation to disallow
		its use with URLs, then you can use the alternative request method instead.
		This makes use of fsockopen() instead.
		$recaptcha = new \ReCaptcha\ReCaptcha($secretKey, new \ReCaptcha\RequestMethod\SocketPost());
		*/

		$recaptcha = new ReCaptcha($secretKey);
		$recaptcha->setExpectedAction($this->action);	// Set expected action
		$recaptcha->setScoreThreshold($this->score);	// Set needed score

		// Make the call to verify the response token and also pass the user's IP address
		return $recaptcha->verify($token, $_SERVER['REMOTE_ADDR']);
	}
}