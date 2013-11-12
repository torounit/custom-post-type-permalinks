<?php

Class CPTP_Loader {

	private static $module_base = "CPTP_Module";

	public static function load_module($modules = array()) {

		include_once CPTP_DIR.'/'.str_replace("_", "/", self::$module_base).'.php';

		foreach ($modules as $module) {

			$class_name = self::$module_base.'_'.$module;
			$file_name = CPTP_DIR."/".str_replace("_", "/", $class_name).".php";

			include_once $file_name;
			new $class_name;

		}
	}
}