<?php

/**
 * @defgroup plugins_generic_fundRef
 */
 
/**
 * @file plugins/generic/fundRef/index.php
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_fundRef
 * @brief Wrapper for fundRef plugin.
 *
 */


require_once('FundRefPlugin.inc.php');

return new FundRefPlugin();

?>
