# OJS3 fundRef
Integrates Crossref Funder registry to OJS3

Upload and enable in plugin settings. Run *php tools/dbXMLtoSQL.php -schema execute plugins/generic/fundRef/schema.xml*

### TODO

- Add funding data to the CrossRef metadata exports. Currently not possible via a plugin?
- Add funding data to OAI-PMH
- OpenAIRE?

## Version 2.0


Version 2.0 supports the combination of funder name, funder id and grant numbers used in the CrossRef Funder Registry (https://www.crossref.org/services/funder-registry/). The plugin adds a funder grid panel to the metatadata view.

![screenshot_1](https://cloud.githubusercontent.com/assets/16347527/26508478/931a9f20-425d-11e7-828e-e67d9529b6d0.png)

New funders can be added and the form suggest names from the CrossRef registry. The form saves both the DOI associated with the funder and the primary funder name. The other field allows to fill in the grant id's connected to the funder.

![screenshot_3](https://cloud.githubusercontent.com/assets/16347527/26508492/9e603994-425d-11e7-92c9-45bc476496e7.png)

The funding data is shown on the article landing page.

![screenshot_4](https://cloud.githubusercontent.com/assets/16347527/26508495/a217f7e8-425d-11e7-89c7-0416a2267960.png)

The data is saved in a separate database table.


## Version 1.0
Adds an autocomplete to the Supporting Agencies field.
