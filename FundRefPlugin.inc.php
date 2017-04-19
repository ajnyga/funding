<?php

/**
 * @file fundRefPlugin.inc.php
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class fundRefPlugin
 * @ingroup plugins_generic_fundRef
 * @brief fundRef plugin class
 *
 *
 *	TODO
 *	- use OJS grid to add funders (see for example article author grid)
 *	- funder form should include: funder name + doi and grant id's 
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class FundRefPlugin extends GenericPlugin {

   function getName() {
        return 'fundRefPlugin';
    }

    function getDisplayName() {
        return "OJS3 fundRef";
    }

    function getDescription() {
        return "Crossref Funder registry autocomplete for OJS3 Sponsoring Agencies field";
    }
	
    function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
				HookRegistry::register ('TemplateManager::include', array(&$this, 'handleTemplateDisplay'));				
        }
		return $success;    
	}
	
	/**
	 * @see TemplateManager::display()
	 */
	function handleTemplateDisplay($hookName, $args) {
		$templateMgr =& $args[0];
		$params =& $args[1];
		$request =& PKPApplication::getRequest();

		if (!isset($params['smarty_include_tpl_file'])) return false;

		switch ($params['smarty_include_tpl_file']) {
			case 'submission/submissionMetadataFormFields.tpl':
				$templateMgr->register_outputfilter(array($this, 'agenciesFilter'));
				break;
		}
		return false;
	}	
	
	/**
	 * Output filter adds CrossRef Funder registry to supporting agencies fields by overriding the exiting controlled vocabulary settings
	 * @param $output string
	 * @param $templateMgr TemplateManager
	 * @return $string
	 */
	function agenciesFilter($output, &$templateMgr) {
		
		$startPoint = '-agencies\]\[\]",';
		$endPoint = '</script>';
		$newscript = "allowSpaces: true,
				tagSource: function(search, response){
						$.ajax({
							url: 'http://api.crossref.org/funders',
							dataType: 'json',
							cache: true,
							data: {
								query: search.term + '*'
							},
							success: 
										function( data ) {
										var output = data.message.items;
										response($.map(output, function(item) {
											return {
												label: item.name + ' [' + item['alt-names'] + ']',
												value: item.name + ' [' + item.uri + ']'
											}
										}));
							}	
							
						});
				}
			});

		});";
		
		$output = preg_replace('#('.$startPoint.')(.*?)('.$endPoint.')#si', '$1'.$newscript.'$3', $output, 1);	
		$templateMgr->unregister_outputfilter('agenciesFilter');
		
		return $output;
	}
	
	
   
}
?>