<?php
PHP_SAPI === 'cli' or die();

/**
 * File       import-team.php
 * Created    12/1/14 11:07 AM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2014 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v2 or later
 */

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * A command line cron job to attempt to remove files that should have been deleted at update.
 *
 * @package  Joomla.Cli
 * @since    3.0
 */
class UpdateFieldsCli extends JApplicationCli
{

	/**
	 * For column name mapping
	 *
	 * @var null
	 */
	private $columnMap = null;

	/**
	 * The CSV file
	 *
	 * @var null
	 */
	private $csvfile = null;

	/**
	 *
	 */
	private $fieldsMap = null;

	/**
	 * Constructor.
	 *
	 * @param   object &$subject The object to observe
	 * @param   array  $config   An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		parent::__construct();
		$this->db = JFactory::getDbo();

		// Set JFactory::$application object to avoid system using incorrect defaults
		JFactory::$application = $this;

		if (!$this->input->get('file'))
		{
			$this->out('You must enter the name as follows:');
			$this->out(' --file foo.csv');
			exit;
		}

		$csvData         = $this->readCSVFile($this->input->get('file'));
		$this->columnMap = $this->mapColumnNames($csvData);

		array_shift($csvData);
		$this->csvfile = $csvData;

		// Fields mapping file
		if ($this->input->get('fieldsMap'))
		{
			$this->mapFieldsMap();
		}
	}

	/**
	 *
	 * @param $string
	 *
	 * @return mixed|string
	 */
	private function camelCase($string)
	{
		// Space out any already camel cased strings
		$str = preg_replace('/([A-Z])/', ' $1', $string);
		// Make sure that all words are upper case, but other letters lower
		$str = ucwords(strtolower($str));
		// Remove any duplicate whitespace, and ensure all characters are alphanumeric
		$str = preg_replace('/[^A-Za-z0-9]/', '', $str);
		// Trim whitespace and lower case first String
		$str = trim(lcfirst($str));

		return $str;
	}

	/**
	 * Entry point for CLI script
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function execute()
	{

		if ($this->input->get('v'))
		{
			$this->out(JProfiler::getInstance('Application')->mark('Starting script.'));
		}

		foreach ($this->csvfile as $row)
		{
			$this->insertFieldsattachValues($row);
		}

		if ($this->input->get('v'))
		{
			$this->out(JProfiler::getInstance('Application')->mark('Finished script.'));
		}
	}

	/**
	 * Checks if an article already exists based on the article alias derived from the column "name"
	 *
	 * @param $article
	 *
	 * @return bool
	 */
	private function isDuplicate($row, $fieldId)
	{
		$query = $this->db->getQuery(true);
		$query
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__fieldsattach_values'))
			->where($this->db->quoteName('articleid') . ' = ' . $this->db->quote($row[$this->columnMap->articleId]))
			->where($this->db->quoteName('fieldsid') . ' = ' . $this->db->quote($fieldId));
		$this->db->setQuery($query);

		return $this->db->loadResult() ? $this->db->loadResult() : false;
	}

	/**
	 * Method to insert fields as mapped by the fields mapping file
	 *
	 * @param $article
	 */
	private function insertFieldsattachValues($row)
	{
		foreach ($this->fieldsMap as $fieldMap)
		{
			// Create and populate an object.
			$field            = new stdClass;
			$field->articleid = $row[$this->columnMap->articleId];
			$field->fieldsid  = $fieldMap->fieldid;
			$field->value     = $row[$this->columnMap->{$fieldMap->column}];

			if ($this->isDuplicate($row, $fieldMap->fieldid))
			{
				$field->id = $this->isDuplicate($row, $fieldMap->fieldid);
				$this->db->updateObject('#__fieldsattach_values', $field, 'id');
			}
			else
			{
				$this->db->insertObject('#__fieldsattach_values', $field);
			}
		}
	}

	/**
	 * Read the first row of a CSV to create a name based mapping of column values
	 *
	 * @param $csvfile
	 *
	 * @return mixed
	 */
	private function mapColumnNames($csvfile)
	{
		$return = new stdClass;

		foreach ($csvfile[0] as $key => $value)
		{
			$return->{$this->camelCase($value)} = $key;
		}

		return $return;
	}

	/**
	 * Processes a field mapping file for associating field IDs with column names
	 */
	private function mapFieldsMap()
	{
		$fields       = $this->readCSVFile($this->input->get('fieldsMap'));
		$fieldsHeader = $this->mapColumnNames($fields);

		array_shift($fields);

		foreach ($fields as $field)
		{
			$fieldMap = new stdClass;

			foreach ($fieldsHeader as $key => $value)
			{
				$fieldMap->{$this->camelCase($key)} = $this->camelCase($field[$value]);
			}
			$this->fieldsMap[] = $fieldMap;
		}
	}

	/**
	 * Read a CSV file and return it as a multidimensional array
	 *
	 * @return array
	 */
	public function readCSVFile($fileName)
	{
		return array_map('str_getcsv', file($fileName));
	}
}

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('UpdateFieldsCli')->execute();