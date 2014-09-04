<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This is a dummy exporter, for testing purposes
 */
class CRM_Donrec_Exporters_PDF extends CRM_Donrec_Logic_Exporter {

	/**
	 * @return the display name
	 */
	static function name() {
		return ts('Export individual PDFs');
	}

	/**
	 * @return a html snippet that defines the options as form elements
	 */
	static function htmlOptions() {
		return '<p>TEST</p>';
	}
}