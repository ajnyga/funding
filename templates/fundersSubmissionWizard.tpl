{**
 * plugins/generic/funding/templates/metadataForm.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 *}

<funders-list-panel
    v-if="section.type === 'funding'"
    v-bind="components.funders"
    @set='set'
></funders-list-panel>
