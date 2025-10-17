{**
 * templates/settingsForm.tpl
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Funding plugin settings
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#fundingSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="fundingSettingsForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="gaSettingsFormNotification"}

	<div id="description">{translate key="plugins.generic.funding.settings.description"}</div>

	{fbvFormArea id="fundingSettingsFormArea"}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="enableGrantIdValidation" label="plugins.generic.funding.settings.enableGrantIdValidation" checked=$enableGrantIdValidation}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
