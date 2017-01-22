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
if ($called_position === 'before_module_proc' && $this->act === 'procFileUpload')
{
	include_once dirname(__FILE__) . '/clamav.class.php';
	if ($clamav_result = XEClamAVAddon::scan($addon_info))
	{
		echo json_encode(array(
			'message_type' => '',
			'error' => -1,
			'message' => $clamav_result,
		));
		exit;
	}
}
