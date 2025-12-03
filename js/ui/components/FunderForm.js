const FundersPkpForm = pkp.controllers.Container.components.PkpForm;

pkp.Vue.component('funder-form', {
	name: 'FunderForm',
	extends: FundersPkpForm,
	methods: {
		getField(fieldName) {
			return this.fields.find(field => field.name === fieldName);
		},
		fieldChanged(name, prop, value, localeKey) {
			FundersPkpForm.methods.fieldChanged.call(this, name, prop, value, localeKey);
			if (name != 'funderNameIdentification' || value === null) {
				return;
			}

			const funderSubOrganizationField = this.getField('funderSubOrganization');

			funderSubOrganizationField.options = [];
			funderSubOrganizationField.value = null;
			funderSubOrganizationField.showWhen = ['funderNameIdentification'];

			$.ajax({
				url: this.action + '/subOrganizations',
				type: 'GET',
				data: {
					funder: value,
				},
				success: function (r) {
                    let subOrganizations = r.items;

					if (subOrganizations.length != 0) {
						funderSubOrganizationField.options = subOrganizations;
						funderSubOrganizationField.showWhen = ['funderNameIdentification', value];
					}
				},
			});

		}
	}
});
