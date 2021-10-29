<?php

declare(strict_types=1);

namespace core\web;

/**
 * Google reCAPTCHA v3 rendering implementation to add in any form.
 * 
 * @see \core\web\ReCaptcha3
 */
class ReCaptcha3Client extends ReCaptcha3
{
	/**
	 * @var string Api link template, without site key.
	 */
	const API_LINK_TEMPLATE = 'https://www.google.com/recaptcha/api.js?render=%s';

	/**
	 * Get Api link.
	 * 
	 * @return string
	 */
	public function getApiLink(): string
	{
		return sprintf(static::API_LINK_TEMPLATE, $this->siteKey);
	}

	/**
	 * Get JavaScript code to inject Recaptcha hidden input into the form.
	 * Usable for typical forms.
	 * 
	 * @param string $selector JQuery form selector.
	 * 
	 * @return string Generated JS code.
	 */
	public function getJsCode(string $selector): string
	{
		$inputName = static::FORM_INPUT_NAME;
		return "grecaptcha.ready(() => {
			grecaptcha.execute('{$this->siteKey}', { action: '{$this->action}' }).then((token) => {
				$('<input>', {
					type: 'hidden',
					name: '{$inputName}',
					value: token
				}).appendTo('{$selector}');
			});
		});";
	}

	/**
	 * Generate JavaScript function:
	 *  - to inject Recaptcha hidden input into the form,
	 *  - reload captcha before Ajax form submit.
	 * 
	 * Place call to `refreshRecaptcha()` (or other custom name) into Ajax handler runs before form data collected.
	 * Usable for Ajax submitable form.
	 *  
	 * @param string $selector JQuery selector of the form to refresh.
	 * @param string $name JavaScript function name.
	 * Having two forms with captchas on same page, there is problem to call function for specific form,
	 * because function names are same. This parameter make possible to customize function name.
	 * 
	 * @return string Generated JS code.
	 */
	public function getRefreshFunction(string $selector, string $name = 'refreshRecaptcha'): string
	{
		$inputName = static::FORM_INPUT_NAME;
		return "function {$name}() {
			let dfd = $.Deferred();
			grecaptcha.ready(() => {
				grecaptcha.execute('{$this->siteKey}', {action: '{$this->action}'}).then((token) => {
					if ($('{$selector} input[name=\"{$inputName}\"]').length === 0) {
						$('<input>', {
							type: 'hidden',
							name: '{$inputName}',
							value: token
						}).appendTo('{$selector}');
					}
					$('{$selector} input[name=\"{$inputName}\"]').val(token);
					dfd.resolve('complete');
				});
			});
			return dfd.promise();
		}";
	}
}