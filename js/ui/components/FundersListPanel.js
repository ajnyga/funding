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
            </list-panel-funders>
        </slot>
    </div>
`);

const ListPanelFunders = pkp.controllers.Container.components.ListPanel;

pkp.Vue.component('funders-list-panel', {
    name: 'FundersListPanel',
    components: {
        ListPanelFunders,
    },
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
    },
    render: function (h) {
        return fundersListPanelTemplate.render.call(this, h);
    },
});