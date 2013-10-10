<?php
/**
 * "Browsersets" action.
 *
 * @author Marc Fallows, 2013
 * @since 2.0.0
 * @package TestSwarm
 */
class BrowsersetsAction extends Action {

	/**
	 * @actionNote This action takes no parameters.
	 */
	public function doAction() {
		$context = $this->getContext();
		$conf = $context->getConf();

		$browserSets = $conf->browserSets;

		$this->setData( $browserSets );
	}
}
