{**
 * plugins/generic/fundRef/templates/listFunders.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The included template that is hooked into Templates::Article::Details.
 *}
<div class="item funders">
	<div class="value">
		<h3>{translate key="plugins.generic.fundRef.fundingData"}</h3>
		<ul>
			{foreach from=$funders item=funder}
				<li>
					{if $funder->getFunderIdentification()}
						{assign var="funderSearch" value=$funder->getFunderIdentification()|explode:"/"}
						<a href="https://search.crossref.org/funding?q={$funderSearch[4]|escape}">{$funder->getFunderName()|escape}</a>
					{else}
						{$funder->getFunderName()|escape}
					{/if}
					<br />
					{if $funder->getFunderGrants()}{translate key="plugins.generic.fundRef.funderGrants"} {$funder->getFunderGrants()|escape}{/if}
				</li>
			{/foreach}
		</ul>
	</div>
</div>
