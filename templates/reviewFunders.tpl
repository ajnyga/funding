{**
 * plugins/generic/funding/templates/reviewFunders.tpl
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The template to review the funding data in the submission wizard
 * before completing the submission
 *}
<div class="submissionWizard__reviewPanel">
    <div class="submissionWizard__reviewPanel__header">
        <h3 id="review-plugin-funding">
            {translate key="plugins.generic.funding.submissionWizard.name"}
        </h3>
        <pkp-button
            aria-describedby="review-plugin-funding"
            class="submissionWizard__reviewPanel__edit"
            @click="openStep('{$step.id}')"
        >
            {translate key="common.edit"}
        </pkp-button>
    </div>
    <div class="submissionWizard__reviewPanel__body">
        <table class="pkpTable" valign="top">
            <thead>
                <tr>
                    <th>
                        {translate key="plugins.generic.funding.funderName"}
                    </th>
                    <th>
                        {translate key="plugins.generic.funding.funderIdentification"}
                    </th>
                    <th>
                        {translate key="plugins.generic.funding.funderGrants"}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="funder in components.funders.items"
                    :key="funder.id"
                    class="submissionWizard__reviewPanel__item__value"
                >
                    <td style="vertical-align:top">{{ funder.name }}</td>
                    <td style="vertical-align:top">{{ funder.identification }}</td>
                    <td style="vertical-align:top">{{ funder.awards.join(', ') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>