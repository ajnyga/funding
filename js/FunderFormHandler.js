/**
 * @file js/FunderFormHandler.js
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.fundRef
 * @class FunderFormHandler
 *
 * @brief Navigation form handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.form.fundRef =
			$.pkp.controllers.form.fundRef || { };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $formElement A wrapped HTML element that
	 *  represents the approved proof form interface element.
	 * @param {Object} options Tabbed modal options.
	 */
	$.pkp.controllers.form.fundRef.FunderFormHandler =
			function($formElement, options) {
		this.parent($formElement, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.fundRef.FunderFormHandler,
			$.pkp.controllers.form.AjaxFormHandler
	);


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
