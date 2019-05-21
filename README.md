# Funding Plugin

About
-----
This plugin adds submission funding data using the Crossref funders registry, considers the data in the Crossref and DataCite XML export and displays them on the submission view page.

License
-------
This plugin is licensed under the GNU General Public License v2. See the file LICENSE for the complete terms of this license.

System Requirements
-------------------
OJS 3.1 i.e. OMP 3.1 or greater.
PHP 5.4 or greater.

Install
-------

 * Copy the release source or unpack the release package into the OJS i.e. OMP plugins/generic/funding/ folder.
 * Run `php tools/upgrade.php upgrade` from the OJS i.e. OMP folder.
 * Go to Settings -> Website -> Plugins -> Generic Plugin -> Funding Plugin and enable the plugin.
 
Version History
---------------

### Version 2.1.1.4

- Added support for funder metadata export to OpenAIRE OAI plugin

### Version 2.1

- Added support for funder metadata exports to CrossRef
- Added support for funder metadata exports to DataCite
- Added support for OMP
- Added support for sub-organization selection
- Fix problem with author's editing permissions upon initial submission, add support for readonly setting
- General code cleanup to match PKP standards
- Change database schema to support richer data

### Version 2.0

Version 2.0 supports the combination of funder name, funder id and grant numbers used in the CrossRef Funder Registry (https://www.crossref.org/services/funder-registry/). The plugin adds a funder grid panel to the submission metatadata form.

![screenshot_1](https://cloud.githubusercontent.com/assets/16347527/26508478/931a9f20-425d-11e7-828e-e67d9529b6d0.png)

New funders can be added and the form suggest names from the CrossRef registry. The form saves both the DOI associated with the funder and the primary funder name. The other field allows to fill in the grant ids connected to the funder.

![screenshot_3](https://cloud.githubusercontent.com/assets/16347527/26508492/9e603994-425d-11e7-92c9-45bc476496e7.png)

The funding data is saved in the database table 'funders' and shown on the submission view page.

![screenshot_4](https://cloud.githubusercontent.com/assets/16347527/26508495/a217f7e8-425d-11e7-89c7-0416a2267960.png)

### Version 1.0

Version 1.0 adds an autocomplete to the Supporting Agencies field.

Sponsors
---------------

Crossref provides funding to support the development of the version 2.1 of this plugin and other Crossref-related plugins.

Versions 1.0 and 2.0 of the plugin were created by The Federation of Finnish Learned Societies (https://tsv.fi) with funding provided by OpenAIRE Alternative Funding Mechanism for APC-free Open Access journals and platforms. 

TODO
---------------
- Add funding data to OAI-PMH, OpenAIRE?
