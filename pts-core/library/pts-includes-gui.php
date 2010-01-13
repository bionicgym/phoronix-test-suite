<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel
	pts-includes-gui.php: Generic functions frequently needed for a GUI front-end

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

function pts_gui_installed_suites()
{
	$installed_suites = array_map("pts_suite_identifier_to_name", pts_installed_suites_array());
	sort($installed_suites);

	return $installed_suites;
}
function pts_gui_process_show_test($identifier, $dependency_limit, $downloads_limit, $license_types)
{
	$show = true;

	if(is_array($license_types))
	{
		$license_types = array_map("strtoupper", $license_types);

		foreach(pts_contained_tests($identifier) as $contained_test)
		{
			$tp = new pts_test_profile($contained_test);
			$license = $tp->get_license();

			if(!empty($license) && !in_array($license, $license_types))
			{
				$show = false;
				break;
			}
		}
	}

	if($show && $dependency_limit != null)
	{
		$dependencies_satisfied = pts_test_external_dependencies_satisfied($identifier);

		switch($dependency_limit)
		{
			case "DEPENDENCIES_INSTALLED":
				$show = $dependencies_satisfied;
				break;
			case "DEPENDENCIES_MISSING":
				$show = !$dependencies_satisfied;
				break;
		}
	}

	if($show && $downloads_limit != null)
	{
		$all_files_are_local = pts_test_download_files_locally_available($identifier);

		switch($downloads_limit)
		{
			case "DOWNLOADS_LOCAL":
				$show = $all_files_are_local;
				break;
			case "DOWNLOADS_MISSING":
				$show = !$all_files_are_local;
				break;
		}
	}

	return $show;
}
function pts_gui_available_suites($to_show_types, $license_types = null, $dependency_limit = null, $downloads_limit = null)
{
	$test_suites = pts_supported_suites_array();
	$to_show_names = array();

	foreach($test_suites as &$name)
	{
		$ts = new pts_test_suite($name);
		$hw_type = $ts->get_suite_type();

		if(empty($hw_type) || in_array($hw_type, $to_show_types))
		{
			if(pts_gui_process_show_test($name, $dependency_limit, $downloads_limit, $license_types))
			{
				array_push($to_show_names, $name);
			}
		}
	}

	$test_suites = array_map("pts_suite_identifier_to_name", $to_show_names);
	sort($test_suites);

	return $test_suites;
}
function pts_gui_installed_tests($to_show_types, $license_types)
{
	$installed_tests = array();
	$installed = pts_installed_tests_array();
	$license_types = array_map("strtoupper", $license_types);

	foreach($installed as &$test)
	{
		$tp = new pts_test_profile($test);
		$hw_type = $tp->get_test_hardware_type();
		$license = $tp->get_license();

		if((empty($hw_type) || in_array($hw_type, $to_show_types)) && (empty($license) || in_array($license, $license_types)) && $tp->get_test_title() != "")
		{
			array_push($installed_tests, $test);
		}
	}

	$installed_tests = array_map("pts_test_identifier_to_name", $installed_tests);
	sort($installed_tests);

	return $installed_tests;
}
function pts_gui_available_tests($to_show_types, $license_types, $dependency_limit = null, $downloads_limit = null)
{
	$test_names = pts_supported_tests_array();
	$to_show_names = array();

	foreach($test_names as &$name)
	{
		$tp = new pts_test_profile($name);
		$hw_type = $tp->get_test_hardware_type();

		if((empty($hw_type) || in_array($hw_type, $to_show_types)) && $tp->is_verified_state())
		{
			if(pts_gui_process_show_test($name, $dependency_limit, $downloads_limit, $license_types))
			{
				array_push($to_show_names, $name);
			}
		}
	}

	$test_names = array_map("pts_test_identifier_to_name", $to_show_names);
	sort($test_names);

	return $test_names;
}
function pts_test_download_files_locally_available($identifier)
{
	foreach(pts_contained_tests($identifier, true, true, false) as $name)
	{
		$test_object_downloads = pts_objects_test_downloads($name);

		foreach($test_object_downloads as &$download_package)
		{
			if(!pts_test_download_file_local($name, $download_package->get_filename()))
			{
				return false;
			}
		}

		if(count($test_object_downloads) == 0 && !pts_is_base_test($name) && !is_file(pts_location_test_resources($name) . "install.sh") && !is_file(pts_location_test_resources($name) . "install.php"))
		{
			$xml_parser = new pts_test_tandem_XmlReader($name);
			$execute_binary = $xml_parser->getXMLValue(P_TEST_EXECUTABLE);
			$execute_path = pts_trim_explode(",", $xml_parser->getXMLValue(P_TEST_POSSIBLEPATHS));
			array_push($execute_path, TEST_ENV_DIR . $name . "/");

			if(empty($execute_binary))
			{
				$execute_binary = $name;
			}

			foreach($execute_path as $path_check)
			{
				if(is_file($path_check . execute_binary))
				{
					continue;
				}
			}

			return false;
		}
	}

	return true;
}
function pts_test_download_file_local($test_identifier, $download_name)
{
	$is_local = false;

	if(is_file(TEST_ENV_DIR . $test_identifier . "/" . $download_name))
	{
		$is_local = true;
	}
	else
	{
		foreach(pts_test_download_cache_directories() as $download_cache)
		{
			if(is_file($download_cache . $download_name))
			{
				$is_local = true;
				break;
			}
		}
	}

	return $is_local;
}
function pts_test_external_dependencies_satisfied($identifier)
{
	$missing_dependencies = pts_external_dependencies_missing();

	foreach(pts_contained_tests($identifier, true, true, false) as $name)
	{
		$tp = new pts_test_profile($name);

		foreach($tp->get_dependencies() as $dependency)
		{
			if(in_array($dependency, $missing_dependencies))
			{
				return false;
			}
		}
	}

	return true;
}
function pts_archive_result_directory($identifier, $save_to = null)
{
	if($save_to == null)
	{
		$save_to = SAVE_RESULTS_DIR . $identifier . ".zip";
	}

	if(is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml"))
	{
		pts_compress(SAVE_RESULTS_DIR . $identifier . "/", $save_to);
	}
}

?>
