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
	 * Scan the current attachment.
	 * 
	 * @param object $addon_info
	 * @return string|false
	 */
	public static function scan($addon_info)
	{
		Context::loadLang('./addons/clamav/lang');
		$exec_path = $addon_info->clamav_command ? $addon_info->clamav_command : self::$_default_executable_path;
		
		$except_extensions = preg_split('/[,;]+/', trim($addon_info->except_extensions));
		$except_extensions = array_filter(array_map('trim', $except_extensions), function($item) { return $item !== ''; });
		$except_regexp = '/\.(?:' . implode('|', array_map(function($item) { return preg_quote($item, '/'); }, $except_extensions)) . ')$/ui';
		
		foreach ($_FILES as $file)
		{
			if (preg_match($except_regexp, $file['name']))
			{
				continue;
			}
			
			$filename = $file['tmp_name'];
			if (file_exists($filename) && !(fileperms($filename) & 4))
			{
				chmod($filename, fileperms($filename) | 4);
			}
			
			$output = shell_exec(sprintf('%s %s %s', escapeshellcmd($exec_path), '--no-summary', escapeshellarg($filename)));
			if (!strlen($output))
			{
				return Context::getLang('cmd_clamav_executable_not_found');
			}
			
			if (!strncmp($output, $filename . ':', strlen($filename) + 1))
			{
				$scan_result = trim(substr($output, strlen($filename) + 1));
				if (preg_match('/(.+)\sFOUND$/', $scan_result, $matches))
				{
					return sprintf(Context::getLang('cmd_clamav_virus_found'), htmlspecialchars($file['name']), htmlspecialchars($matches[1]));
				}
				elseif (preg_match('/(.+)\sERROR$/', $scan_result, $matches))
				{
					return sprintf(Context::getLang('cmd_clamav_unable_to_scan'), htmlspecialchars($matches[1]));
				}
			}
		}
		
		return false;
	}
}
