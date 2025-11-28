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
                    <pkp-button
                        :disabled="isLoading"
                        :isWarnable="true"
                        @click="openDeleteModal(item.id)"
                    >
                        {{ __('common.delete') }}
                    </pkp-button>
                </template>
            </list-panel-funders>
        </slot>
    </div>
`);

let SubmissionWizardPageFunders = pkp.controllers.SubmissionWizardPage;
if (!SubmissionWizardPageFunders.hasOwnProperty('components')) {
    SubmissionWizardPageFunders = SubmissionWizardPageFunders.extends;
}

const ListPanelFunders = pkp.controllers.Container.components.ListPanel;
const ModalFunders = SubmissionWizardPageFunders.components.Modal;
const ajaxErrorFunders = SubmissionWizardPageFunders.mixins[0];
const dialogFunders = SubmissionWizardPageFunders.mixins[2];


pkp.Vue.component('funders-list-panel', {
    name: 'FundersListPanel',
    components: {
        ListPanelFunders,
        ModalFunders,
    },
    mixins: [ajaxErrorFunders, dialogFunders],
    props: {
        canEditPublication: {
			type: Boolean,
			required: true,
		},
        id: {
			type: String,
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
        i18nAddFunder: {
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
    },
    data() {
		return {
			activeForm: null,
			activeFormTitle: '',
			resetFocusTo: null,
			isLoading: false,
		};
	},
    methods: {
        openAddModal() {
			console.log('Open add funder modal');
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
							this.isLoading = true;

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
									this.$modal.hide('delete');
									this.setFocusIn(this.$el);

									const newFunders = this.items.filter(
										(funder) => {
											return funder.id !== id;
										}
									);
									this.$emit('updated:funders', newFunders);
								},
								complete(r) {
									this.isLoading = false;
								},
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
    },
    render: function (h) {
        return fundersListPanelTemplate.render.call(this, h);
    },
});