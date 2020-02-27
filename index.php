<?php

/**
 * @defgroup plugins_generic_funding FundingPlugin plugin
 */

/**
 * @file plugins/generic/funding/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_funding
 *
 * @brief Wrapper for FundingPlugin plugin.
 *
 */

require_once('FundingPlugin.inc.php');

return new FundingPlugin();

?>
