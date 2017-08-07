<?php

/**
 * @defgroup plugins_generic_fundRef FundRefPlugin plugin
 */

/**
 * @file plugins/generic/fundRef/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_fundRef
 *
 * @brief Wrapper for FundRefPlugin plugin.
 *
 */

require_once('FundRefPlugin.inc.php');

return new FundRefPlugin();

?>
