{**
 * metadataForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *}
<div id="fundref">
		{url|assign:funderGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.fundRef.controllers.grid.FunderGridHandler" op="fetchGrid" submissionId=$submissionId escape=false}
		{load_url_in_div id="funderGridContainer"|uniqid url=$funderGridUrl}	
</div>
