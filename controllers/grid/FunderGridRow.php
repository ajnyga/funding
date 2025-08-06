<?php

/**
 * @file plugins/generic/funding/controllers/grid/FunderGridRow.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FunderGridRow
 * @ingroup plugins_generic_funding
 *
 * @brief Handle funder grid row requests.
 */

namespace APP\plugins\generic\funding\controllers\grid;

use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class FunderGridRow extends GridRow {
    /** @var boolean */
    var $_readOnly;

    /**
     * Constructor
     */
    function __construct($readOnly = false) {
        $this->_readOnly = $readOnly;
        parent::__construct();
    }

    //
    // Overridden template methods
    //
    /**
     * @copydoc GridRow::initialize()
     */
    function initialize($request, $template = null) {
        parent::initialize($request, $template);
        $funderId = $this->getId();
        $submissionId = $request->getUserVar('submissionId');

        if (!empty($funderId) && !$this->isReadOnly()) {
            $router = $request->getRouter();

            // Create the "edit" action
            $this->addAction(
                new LinkAction(
                    'editFunderItem',
                    new AjaxModal(
                        $router->url($request, null, null, 'editFunder', null, array('funderId' => $funderId, 'submissionId' => $submissionId)),
                        __('grid.action.edit'),
                        'modal_edit',
                        true),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            // Create the "delete" action
            $this->addAction(
                new LinkAction(
                    'delete',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('common.confirmDelete'),
                        __('grid.action.delete'),
                        $router->url($request, null, null, 'deleteFunder', null, array('funderId' => $funderId, 'submissionId' => $submissionId)), 'modal_delete'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }

    /**
     * Determine if this grid row should be read only.
     * @return boolean
     */
    function isReadOnly() {
        return $this->_readOnly;
    }
}
