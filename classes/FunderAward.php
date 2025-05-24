<?php

/**
 * @file plugins/generic/funding/classes/FunderAward.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FunderAward
 * @ingroup plugins_generic_funding
 *
 * Data object representing a funderAward.
 */

namespace APP\plugins\generic\funding\classes; 

use PKP\core\DataObject;

class FunderAward extends DataObject {

	//
	// Get/set methods
	//

	/**
	 * Get Funder ID.
	 * @return int
	 */
	function getFunderId(){
		return $this->getData('funderId');
	}

	/**
	 * Set Funder ID.
	 * @param $funderId int
	 */
	function setFunderId($funderId) {
		return $this->setData('funderId', $funderId);
	}	

	/**
	 * Get Funder Award Number.
	 * @return string
	 */
	function getFunderAwardNumber() {
		return $this->getData('funderAwardNumber');
	}

	/**
	 * Set Funder Award Number.
	 * @param $funderAwardNumber string
	 */
	function setFunderAwardNumber($funderAwardNumber) {
		return $this->setData('funderAwardNumber', $funderAwardNumber);
	}

}
