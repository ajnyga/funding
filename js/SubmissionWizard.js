/**
 * @file plugins/generic/funding/js/SubmissionWizard.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionWizard
 * @ingroup plugins_generic_funding
 *
 * @brief Add information about funder's to the root component of the submission
 *   wizard UI and keep the data in sync as funders are added, edited and removed
 */
(function() {
    if (typeof pkp === 'undefined' || typeof pkp.eventBus === 'undefined') {
        return;
    }

    var root;
    pkp.eventBus.$on('root:mounted', function(id, component) {
        root = component;
    });
    pkp.eventBus.$on('plugin:funding:added', function(data) {
        root.funders.push({
            id: data.id,
            name: data.name,
            identification: data.identification,
            awards: data.awards,
        })
    });
    pkp.eventBus.$on('plugin:funding:edited', function(data) {
        root.funders = root.funders.map(function(funder) {
            if (data.id === funder.id) {
                return {
                    id: data.id,
                    name: data.name,
                    identification: data.identification,
                    awards: data.awards,
                };
            }
            return funder;
        });
    });
    pkp.eventBus.$on('plugin:funding:deleted', function(data) {
        root.funders = root.funders.filter(function(funder) {
            return data.id !== funder.id;
        });
    });
}());