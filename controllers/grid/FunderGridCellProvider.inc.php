<?php

/**
 * @file controllers/grid/FunderGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StaticPageGridCellProvider
 * @ingroup controllers_grid_navigation
 *
 * @brief Class for a cell provider to display information about navigation items
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');
import('lib.pkp.classes.linkAction.request.RedirectAction');

class FunderGridCellProvider extends GridCellProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Template methods from GridCellProvider
	//

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$funderItem = $row->getData();

		switch ($column->getId()) {
			case 'funderId':
				return array('label' => $funderItem->getId());
			case 'funderName':
				return array('label' => $funderItem->getFunderName());
			case 'funderIdentification':
				return array('label' => $funderItem->getFunderIdentification());
			case 'funderGrants':
				return array('label' => $funderItem->getFunderGrants());
		}
	}
}





?>
