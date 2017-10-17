<?php

/**
 * @file plugins/generic/funding/controllers/grid/FunderGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunderGridCellProvider
 * @ingroup plugins_generic_funding
 *
 * @brief Class for a cell provider to display information about funder items
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class FunderGridCellProvider extends GridCellProvider {

	//
	// Template methods from GridCellProvider
	//

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 *
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$funderItem = $row->getData();
		switch ($column->getId()) {
			case 'funderName':
				return array('label' => $funderItem['funderName']);
			case 'funderIdentification':
				return array('label' => $funderItem['funderIdentification']);
			case 'funderGrants':
				return array('label' => $funderItem['funderGrants']);
		}
	}
}

?>
