<?php

/**
 * @file clamav.addon.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 */ 
if (!defined('__XE__')) exit();

/**
 * Check uploaded file.
 */
if ($called_position === 'before_module_proc' && $this->act === 'procFileUpload' && count($_FILES))
{
	// Load the class only when needed.
	include_once dirname(__FILE__) . '/clamav.class.php';
	$clamav_scanner = new XEClamAVAddon($addon_info);
	
	// Ensure compatibility with Rhymix chunked uploads.
	if (defined('RX_VERSION') && ($oModuleController = getController('module')) && method_exists($oModuleController, 'addTriggerFunction'))
	{
		$skip_filedata = true;
		$oModuleController->addTriggerFunction('file.insertFile', 'before', function($obj) use($clamav_scanner) {
			if ($obj->file_info && ($clamav_result = $clamav_scanner->scan($obj->file_info)) !== false)
			{
				return new Object(-1, $clamav_result);
			}
		});
	}
	else
	{
		$skip_filedata = false;
	}
	
	// Check all uploaded files.
	foreach ($_FILES as $file_key => $file_info)
	{
		if ($skip_filedata && $file_key === 'Filedata')
		{
			continue;
		}
		
		if (($clamav_result = $clamav_scanner->scan($file_info)) !== false)
		{
			$clamav_scanner->displayError($clamav_result);
		}
	}
}
