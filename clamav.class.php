<?php

/**
 * @file clamav.class.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 */
class XEClamAVAddon
{
	/**
	 * Default executable path.
	 */
	protected static $_default_executable_path = '/usr/bin/clamdscan';
	
	/**
	 * Configuration cache.
	 */
	protected $_addon_info;
	protected $_exec_path;
	protected $_except_regexp;
	
	/**
	 * Constructor.
	 * 
	 * @param object $addon_info
	 */
	public function __construct($addon_info)
	{
		// Set addon config to intance properties.
		Context::loadLang('./addons/clamav/lang');
		$this->_addon_info = $addon_info;
		$this->_exec_path = $addon_info->clamav_command ? $addon_info->clamav_command : self::$_default_executable_path;
		
		// Compile the regexp for excluded extensions.
		$except_extensions = preg_split('/[,;]+/', trim($addon_info->except_extensions));
		$except_extensions = array_filter(array_map('trim', $except_extensions), function($item) { return $item !== ''; });
		$this->_except_regexp = '/\.(?:' . implode('|', array_map(function($item) { return preg_quote($item, '/'); }, $except_extensions)) . ')$/ui';
	}
	
	/**
	 * Scan the current attachment.
	 * 
	 * @param array $file_info
	 * @return string|false
	 */
	public function scan($file_info)
	{
		// Return if the file extension is excluded.
		if (preg_match($this->_except_regexp, $file_info['name']))
		{
			return false;
		}
		
		// Change the permissions on the file to make it readable to the ClamAV scanner.
		$filename = $file_info['tmp_name'];
		if (file_exists($filename) && !(fileperms($filename) & 4))
		{
			chmod($filename, fileperms($filename) | 4);
		}
		
		// Perform the scan using an external command.
		$output = shell_exec(sprintf('%s %s %s', escapeshellcmd($this->_exec_path), '--no-summary', escapeshellarg($filename)));
		if (!strlen($output))
		{
			return Context::getLang('cmd_clamav_executable_not_found');
		}
		
		// If a virus is found or an error occurred, return an appropriate response string.
		if (!strncmp($output, $filename . ':', strlen($filename) + 1))
		{
			$scan_result = trim(substr($output, strlen($filename) + 1));
			if (preg_match('/(.+)\sFOUND$/', $scan_result, $matches))
			{
				return sprintf(Context::getLang('cmd_clamav_virus_found'), htmlspecialchars($file_info['name']), htmlspecialchars($matches[1]));
			}
			elseif (preg_match('/(.+)\sERROR$/', $scan_result, $matches))
			{
				return sprintf(Context::getLang('cmd_clamav_unable_to_scan'), htmlspecialchars($matches[1]));
			}
		}
		
		// If all is well, return false.
		return false;
	}
	
	/**
	 * Display an error message and exit.
	 * 
	 * @param string $message
	 * @return void
	 */
	public function displayError($message)
	{
		echo json_encode(array(
			'message_type' => '',
			'error' => -1,
			'message' => $messsage,
		));
		exit;
	}
}
