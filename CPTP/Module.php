<?php


Abstract Class CPTP_Module {

	public function __construct() {
		add_action("CPTP_init", array( $this, "add_hook" ));
	}

	abstract function add_hook();

}
