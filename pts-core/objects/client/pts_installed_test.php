<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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

class pts_installed_test
{
	private $footnote_override = null;
	private $install_path = null;
	private $installed = false;

	private $install_date_time = null;
	private $last_run_date_time = null;
	private $installed_version = null;
	private $average_runtime = null;
	private $last_runtime = null;
	private $last_install_time = null;
	private $times_run = 0;
	private $compiler_data = null;
	private $install_footnote = null;
	private $install_checksum = null;
	private $system_hash = null;
	private $associated_test_identifier = null;

	public function __construct(&$test_profile)
	{
		$this->install_path = $test_profile->get_install_dir();

		if(is_file($this->install_path . 'pts-install.xml'))
		{
			$this->installed = true;
			$xml_options = LIBXML_COMPACT | LIBXML_PARSEHUGE;
			$xml = simplexml_load_file($this->install_path . 'pts-install.xml', 'SimpleXMLElement', $xml_options);
			$this->install_date_time = isset($xml->TestInstallation->History->InstallTime) ? $xml->TestInstallation->History->InstallTime->__toString() : null;
			$this->last_run_date_time = isset($xml->TestInstallation->History->LastRunTime) ? $xml->TestInstallation->History->LastRunTime->__toString() : null;
			$this->installed_version = isset($xml->TestInstallation->Environment->Version) ? $xml->TestInstallation->Environment->Version->__toString() : null;
			$this->average_runtime = isset($xml->TestInstallation->History->AverageRunTime) ? $xml->TestInstallation->History->AverageRunTime->__toString() : null;
			$this->last_runtime = isset($xml->TestInstallation->History->LatestRunTime) ? $xml->TestInstallation->History->LatestRunTime->__toString() : null;
			$this->last_install_time = isset($xml->TestInstallation->History->InstallTimeLength) ? $xml->TestInstallation->History->InstallTimeLength->__toString() : null;
			$this->times_run = isset($xml->TestInstallation->History->TimesRun) ? $xml->TestInstallation->History->TimesRun->__toString() : 0;
			$this->compiler_data = isset($xml->TestInstallation->Environment->CompilerData) ? json_decode($xml->TestInstallation->Environment->CompilerData->__toString(), true) : null;
			$this->install_footnote = isset($xml->TestInstallation->Environment->InstallFootnote) ? $xml->TestInstallation->Environment->InstallFootnote->__toString() : null;
			$this->install_checksum = isset($xml->TestInstallation->Environment->CheckSum) ? $xml->TestInstallation->Environment->CheckSum->__toString() : null;
			$this->system_hash = isset($xml->TestInstallation->Environment->SystemIdentifier) ? $xml->TestInstallation->Environment->SystemIdentifier->__toString() : null;
			$this->associated_test_identifier = isset($xml->TestInstallation->Environment->Identifier) ? $xml->TestInstallation->Environment->Identifier->__toString() : null;
		}
	}
	public function is_installed()
	{
		return $this->installed != false;
	}
	public function get_install_log_location()
	{
		return $this->install_path . 'install.log';
	}
	public function get_associated_test_identifier()
	{
		return $this->associated_test_identifier;
	}
	public function has_install_log()
	{
		return is_file($this->get_install_log_location());
	}
	public function get_install_date_time()
	{
		return $this->install_date_time;
	}
	public function get_install_date()
	{
		return substr($this->get_install_date_time(), 0, 10);
	}
	public function get_last_run_date_time()
	{
		return $this->last_run_date_time;
	}
	public function get_last_run_date()
	{
		return substr($this->get_last_run_date_time(), 0, 10);
	}
	public function get_installed_version()
	{
		return $this->installed_version;
	}
	public function get_average_run_time()
	{
		return $this->average_runtime;
	}
	public function get_latest_run_time()
	{
		return $this->last_runtime;
	}
	public function get_latest_install_time()
	{
		return $this->last_install_time;
	}
	public function get_run_count()
	{
		return $this->times_run;
	}
	public function get_compiler_data()
	{
		return $this->compiler_data;
	}
	public function get_install_footnote()
	{
		return !empty($this->footnote_override) ? $this->footnote_override : $this->install_footnote;
	}
	public function set_install_footnote($f = null)
	{
		return $this->footnote_override = $f;
	}
	public function get_installed_checksum()
	{
		return $this->install_checksum;
	}
	public function get_system_hash()
	{
		return $this->system_hash;
	}
	public function get_install_size()
	{
		$install_size = 0;

		if(pts_client::executable_in_path('du'))
		{
			$du = trim(shell_exec('du -sk ' . $this->install_path . ' 2>&1'));
			$du = substr($du, 0, strpos($du, "\t"));
			if(is_numeric($du) && $du > 1)
			{
				$install_size = $du;
			}
		}

		return $install_size;
	}
	public function update_install_time($t)
	{
		$this->last_install_time = ceil($t);
	}
	public function add_latest_run_time($t)
	{
		$this->last_runtime = ceil($t);

		if(empty($this->average_runtime))
		{
			$this->average_runtime = ceil($t);
		}
		else
		{
			// Yeah this isn't the true average, but once rework is complete allow for more easily storing all the run-times... XXX
			$this->average_runtime = ceil((($this->get_average_run_time() * $this->get_run_count()) + $t) / ($this->get_run_count() + 1));
		}
		$this->times_run++;
		$this->last_run_date_time = date('Y-m-d H:i:s');
	}
	public function update_install_data(&$test_profile, $compiler_data, $install_footnote)
	{
		$this->compiler_data = $compiler_data;
		$this->install_footnote = $install_footnote;
		$this->associated_test_identifier = $test_profile->get_identifier();
		$this->installed_version = $test_profile->get_test_profile_version();
		$this->install_checksum = $test_profile->get_installer_checksum();
		$this->system_hash = phodevi::system_id_string();
		$this->install_date_time = date('Y-m-d H:i:s');
	}
	public function save_test_install_metadata()
	{
		// Refresh/generate an PTS install file

		// JSON output

		$to_json = array();
		$to_json['test_installation']['environment']['test_identifier'] = $this->get_associated_test_identifier();
		$to_json['test_installation']['environment']['test_version'] = $this->get_installed_version();
		$to_json['test_installation']['environment']['install_checksum'] = $this->get_installed_checksum();
		$to_json['test_installation']['environment']['system_hash'] = $this->get_system_hash();
		$to_json['test_installation']['environment']['compiler_data'] = $this->get_compiler_data();
		$to_json['test_installation']['environment']['install_footnote'] = $this->get_install_footnote();
		$to_json['test_installation']['history']['install_date_time'] = $this->get_install_date_time();
		$to_json['test_installation']['history']['install_time_length'] = $this->get_latest_install_time();
		$to_json['test_installation']['history']['times_run'] = $this->get_run_count();
		$to_json['test_installation']['history']['last_run_date_time'] = $this->get_last_run_date_time();
		$to_json['test_installation']['history']['average_runtime'] = $this->get_average_run_time();
		$to_json['test_installation']['history']['latest_runtime'] = $this->get_latest_run_time();
		file_put_contents($this->install_path . 'pts-install.json', json_encode($to_json, JSON_PRETTY_PRINT));

		// The pts-install.xml XML file is traditionally how PTS install metadata was installed...
		// With PTS 10.2, JSON is preferred for storing the data more easily
		// But continue generating the install XML for backwards compatibility and for parts of PTS checking for 'pts-install.xml' to determine if test installed, etc.
		// But PTS 10.2+ will use the data actually stored in the JSON file....
		// TODO XXX PTS 11.0 or so drop the pts-install.xml and use just pts-install.json

		$xml_writer = new nye_XmlWriter();
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/Identifier', $this->get_associated_test_identifier());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/Version', $this->get_installed_version());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/CheckSum', $this->get_installed_checksum());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/CompilerData', json_encode($this->get_compiler_data()));
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/InstallFootnote', $this->get_install_footnote());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/SystemIdentifier', $this->get_system_hash());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/InstallTime', $this->get_install_date_time());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/InstallTimeLength', $this->get_latest_install_time());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/LastRunTime', $this->get_last_run_date_time());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/TimesRun', $this->get_run_count());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/AverageRunTime', $this->get_average_run_time());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/LatestRunTime', $this->get_latest_run_time());
		$xml_writer->saveXMLFile($this->install_path . 'pts-install.xml');
	}
}

?>
