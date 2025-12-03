const fundersListPanelTemplate = pkp.Vue.compile(`
    <div class="fundersListPanel">
		<slot>
            <list-panel-funders
				:items="items"
				class="listPanel--funder"
			>
                <pkp-header slot="header">
					<h2>{{ title }}</h2>
					<spinner v-if="isLoading" />
					<template slot="actions">
						<pkp-button
                            v-if="canEditPublication"
							@click="openAddModal"
							:disabled="isLoading"
						>
							{{ i18nAddFunder }}
						</pkp-button>
					</template>
				</pkp-header>
                <template v-slot:itemsEmpty>
					<div class="full-text">
						{{ emptyLabel }}
					</div>
				</template>
                <template v-slot:item-title="{item}">
					<div class="full-text">
						{{ item.name }}
					</div>
				</template>
				<template v-slot:item-subtitle="{item}">
					<div class="full-text">
						{{ item.identification }}
					</div>
				</template>
                <template
					v-if="canEditPublication"
					v-slot:item-actions="{item}"
				>
                    <pkp-button @click="openEditModal(item)" :disabled="isLoading">
						{{ __('common.edit') }}
					</pkp-button>
					<pkp-button
                        :disabled="isLoading"
                        :isWarnable="true"
                        @click="openDeleteModal(item.id)"
                    >
                        {{ __('common.delete') }}
                    </pkp-button>
                </template>
            </list-panel-funders>
			<modal-funders
				:closeLabel="__('common.close')"
				:name="formModal"
				:title="formTitle"
				@closed="resetForm"
			>
				<search-funders
					:searchLabel="i18nSearchFunder"
					@search-phrase-changed="refreshFormFundersList"
				/>
				<funder-form
					v-bind="form"
					@success="formSuccess"
				/>
			</modal-funders>
        </slot>
    </div>
`);

let SubmissionWizardPageFunders = pkp.controllers.SubmissionWizardPage;
if (!SubmissionWizardPageFunders.hasOwnProperty('components')) {
    SubmissionWizardPageFunders = SubmissionWizardPageFunders.extends;
}

const ListPanelFunders = pkp.controllers.Container.components.ListPanel;
const SearchFunders = pkp.controllers.Container.components.SubmissionsListPanel.components.Search;
const ModalFunders = SubmissionWizardPageFunders.components.Modal;
const ajaxErrorFunders = SubmissionWizardPageFunders.mixins[0];
const dialogFunders = SubmissionWizardPageFunders.mixins[2];


pkp.Vue.component('funders-list-panel', {
    name: 'FundersListPanel',
    components: {
        ListPanelFunders,
		SearchFunders,
        ModalFunders,
    },
    mixins: [ajaxErrorFunders, dialogFunders],
    props: {
        canEditPublication: {
			type: Boolean,
			required: true,
		},
		form: {
			type: Object,
			required: true,
		},
        id: {
			type: String,
			required: true,
		},
        submissionId: {
			type: Number,
			required: true,
		},
		items: {
			type: Array,
			default() {
				return [];
			},
		},
		title: {
			type: String,
			required: true,
		},
        emptyLabel: {
			type: String,
		},
        fundersApiUrl: {
            type: String,
            required: true,
        },
        i18nAddFunder: {
			type: String,
			required: true,
		},
		i18nEditFunder: {
			type: String,
			required: true,
		},
        i18nDeleteFunder: {
			type: String,
			required: true,
		},
        i18nConfirmDeleteFunder: {
			type: String,
			required: true,
		},
		i18nSearchFunder: {
			type: String,
			required: true,
		}
    },
    data() {
		return {
			formTitle: '',
			resetFocusTo: null,
			isLoading: false,
		};
	},
	computed: {
		formModal() {
			return this.id + 'form';
		},
	},
    methods: {
        openAddModal() {
			this.formTitle = this.i18nAddFunder;
			this.form.action = this.fundersApiUrl;
			this.form.method = 'POST';
			this.$modal.show(this.formModal);
		},
		openEditModal(funder) {
			this.formTitle = this.i18nEditFunder;
			this.form.action = this.fundersApiUrl + '/' + funder.id;
			this.form.method = 'PUT';

			const funderNameField = this.getFormField('funderNameIdentification');
			funderNameField.value = funder.name + ' [' + funder.identification + ']';
			funderNameField.options = [
				{
					label: funder.name,
					value: funder.name + ' [' + funder.identification + ']',
				}
			];

			const funderGrantsField = this.getFormField('funderGrants');
			funderGrantsSelected = [];
			for (const award of funder.awards) {
				funderGrantsSelected.push({label: award, value: award});
			}
			funderGrantsField.selected = funderGrantsSelected;
			funderGrantsField.value = funderGrantsSelected;

			this.$modal.show(this.formModal);
		},
        openDeleteModal(id) {
			const funder = this.items.find((a) => a.id === id);

			this.openDialog({
				name: 'delete',
				title: this.i18nDeleteFunder,
				message: this.replaceLocaleParams(this.i18nConfirmDeleteFunder, {
					name: funder.name,
				}),
				actions: [
					{
						label: this.i18nDeleteFunder,
						isWarnable: true,
						callback: () => {
							$.ajax({
								url: this.fundersApiUrl + '/' + id,
								type: 'POST',
								context: this,
								headers: {
									'X-Csrf-Token': pkp.currentUser.csrfToken,
									'X-Http-Method-Override': 'DELETE',
								},
								error: this.ajaxErrorCallback,
								success(r) {
                                    this.refreshItems();
									this.$modal.hide('delete');
									this.setFocusIn(this.$el);
								}
							});
						},
					},
                    {
						label: this.__('common.cancel'),
						callback: () => this.$modal.hide('delete'),
					},
				],
			});
		},
		formSuccess() {
			this.refreshItems();
			this.$modal.hide(this.formModal);
		},
        refreshItems() {
            let self = this;
            this.isLoading = true;
            this.latestGetRequest = $.pkp.classes.Helper.uuid();

            $.ajax({
				url: this.fundersApiUrl + '/submission/' + this.submissionId,
				type: 'GET',
				_uuid: this.latestGetRequest,
				error: function (response) {
                    if (self.latestGetRequest !== this._uuid) {
                        return;
                    }
                    self.ajaxErrorCallback(response);
                },
				success: function (response) {
                    if (self.latestGetRequest !== this._uuid) {
                        return;
                    }
                    self.items = response.items;
					pkp.registry._instances.app.components.funders.items = self.items;
				},
				complete() {
					if (self.latestGetRequest !== this._uuid) {
                        return;
                    }
                    self.isLoading = false;
				},
			});
        },
		getFormField(fieldName) {
			return this.form.fields.find(field => field.name === fieldName);
		},
		refreshFormFundersList(searchPhrase) {
			let self = this;
			const funderNameField = this.getFormField('funderNameIdentification');

			funderNameField.options = [];
			funderNameField.value = null;

			$.ajax({
				url: self.fundersApiUrl + '/suggestions',
				type: 'GET',
				data: {
					searchPhrase: searchPhrase,
				},
				success: function (r) {
                    let funderOptions = self.form.fields.find(field => field.name === 'funderNameIdentification');
					funderOptions.options = r.items;
				},
			});
		},
		resetForm() {
			const funderNameField = this.getFormField('funderNameIdentification');
			const funderSubOrganizationField = this.getFormField('funderSubOrganization');
			const funderGrantsField = this.getFormField('funderGrants');

			funderNameField.options = [];
			funderNameField.value = null;

			funderSubOrganizationField.options = [];
			funderSubOrganizationField.value = null;
			funderSubOrganizationField.showWhen = ['funderNameIdentification'];

			funderGrantsField.value = [];
		}
    },
    render: function (h) {
        return fundersListPanelTemplate.render.call(this, h);
    },
});