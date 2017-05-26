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
 */

import('lib.pkp.classes.plugins.GenericPlugin');
class fundRefPlugin extends GenericPlugin {

   function getName() {
        return 'fundRefPlugin';
    }

    function getDisplayName() {
        return "fundRef";
    }

    function getDescription() {
        return "Plugin for searching and saving funder name, funder id, and grant number";
    }
	
    function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			
			import('plugins.generic.fundRef.classes.FunderDAO');
			$funderDao = new FunderDAO();
			DAORegistry::registerDAO('FunderDAO', $funderDao);		

			HookRegistry::register('Templates::Submission::SubmissionMetadataForm::AdditionalMetadata', array($this, 'metadataFieldEdit'));
			
			HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			
			HookRegistry::register('TemplateManager::display',array($this, 'addGridhandlerJs'));
			
			HookRegistry::register('Templates::Article::Details', array($this, 'addArticleDisplay'));
				
        }
		return $success;
	}
	
	
	/**
	 * Permit requests to the Funder grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $params) {
		$component =& $params[0];
		
		if ($component == 'plugins.generic.fundRef.controllers.grid.FunderGridHandler') {
			import($component);
			FunderGridHandler::setPlugin($this);
			return true;
		}
		return false;
	}	
	
	
	/**
	 * Insert funder panel in the metadata form
	 */
	function metadataFieldEdit($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$request = $this->getRequest();		
		$output .= $smarty->fetch($this->getTemplatePath() . 'metadataForm.tpl');
		return false;
	}
	
	/**
	 * Add custom gridhandlerJS for backend
	 */
	function addGridhandlerJs($hookName, $params) {
		$templateMgr = $params[0];
		$gridHandlerJs = $this->getJavaScriptURL() . DIRECTORY_SEPARATOR . 'FunderGridHandler.js';
		$templateMgr->addJavaScript(
			'FunderGridHandlerJs',
			$gridHandlerJs,
			array(
					'contexts' => 'backend',
				)
		);
		
		return false;
	}
	
	/**
	 * Hook to Templates::Article::Details and list funder information
	 * @param $hookName string
	 * @param $params array
	 */
	function addArticleDisplay($hookName, $params) {
		$templateMgr = $params[1];
		$output =& $params[2];
		
		$article = $templateMgr->get_template_vars('article');
		
		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funders = $funderDao->getBySubmissionId($article->getId());
		$funders = $funders->toArray();		
		
		if ($funders){
			$templateMgr->assign('funders', $funders);
			$output .= $templateMgr->fetch($this->getTemplatePath() . 'listFunders.tpl');
		}
		
		return false;
		
	}	
	
	
	/**
	 * @copydoc Plugin::getTemplatePath()
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}	
	
	/**
	 * Get the JavaScript URL for this plugin.
	 */
	function getJavaScriptURL() {
		return Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js';
	}	
	
   
}
?>