<?php
/**
 * Copyright (c) 5/3/2021 Created By/Edited By nasekinid nasekinid8591@yandex.ru
 */

if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$moduleId = 'data.importexportexcel';
$moduleFilePrefix = 'data_import_excel';
$moduleJsId = 'data_importexcel';
$moduleJsId2 = str_replace('.', '_', $moduleId);

$moduleShowDemoFunc = $moduleJsId2.'_show_demo';
$moduleRunnerClass = 'CDataImpExpExcelRunner';
CModule::IncludeModule("iblock");
CModule::IncludeModule($moduleId);
$bCatalog = CModule::IncludeModule('catalog');
$bCurrency = CModule::IncludeModule("currency");
CJSCore::Init(array('fileinput', $moduleJsId));
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);

include_once(dirname(__FILE__).'/../install/demo.php');

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

/*Close session*/
$sess = $_SESSION;
session_write_close();
$_SESSION = $sess;
/*/Close session*/

$oProfile = new CKDAImportProfile();
if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!=='new')
{
	$oProfile->Apply($SETTINGS_DEFAULT, $SETTINGS, $PROFILE_ID);
	if($EXTRASETTINGS)
	{
		foreach($EXTRASETTINGS as $k=>$v)
		{
			foreach($v as $k2=>$v2)
			{
				if($v2 && !is_array($v2))
				{
					$EXTRASETTINGS[$k][$k2] = CUtil::JsObjectToPhp($v2);
				}
			}
		}
	}
	$oProfile->ApplyExtra($EXTRASETTINGS, $PROFILE_ID);
	
	/*New file storage*/
	if($SETTINGS_DEFAULT['URL_DATA_FILE'] && !$SETTINGS_DEFAULT["DATA_FILE"])
	{
		$filepath = $_SERVER["DOCUMENT_ROOT"].$SETTINGS_DEFAULT['URL_DATA_FILE'];
		if(!file_exists($filepath))
		{
			if(defined("BX_UTF")) $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'CP1251');
			else $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'UTF-8');
		}
		$arFile = CKDAImportUtils::MakeFileArray($filepath);
		$arFile['external_id'] = 'kda_import_'.$PROFILE_ID;
		$arFile['del_old'] = 'Y';
		$fid = CKDAImportUtils::SaveFile($arFile);
		$SETTINGS_DEFAULT["DATA_FILE"] = $fid;
		$oProfile->Update($PROFILE_ID, $SETTINGS_DEFAULT, $SETTINGS);
	}
	/*/New file storage*/
}

$SHOW_FIRST_LINES =  (isset($SETTINGS_DEFAULT['COUNT_LINES_FOR_PREVIEW']) && intval($SETTINGS_DEFAULT['COUNT_LINES_FOR_PREVIEW']) > 0 ? intval($SETTINGS_DEFAULT['COUNT_LINES_FOR_PREVIEW']) : 10);
$SETTINGS_DEFAULT['IBLOCK_ID'] = intval($SETTINGS_DEFAULT['IBLOCK_ID']);
$STEP = intval($STEP);
if ($STEP <= 0)
	$STEP = 1;

$notRewriteFile = false;
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	if(isset($_POST["backButton"]) && strlen($_POST["backButton"]) > 0) $STEP = $STEP - 2;
	if(isset($_POST["backButton2"]) && strlen($_POST["backButton2"]) > 0) $STEP = 1;
	if(isset($_POST["saveConfigButton"]) && strlen($_POST["saveConfigButton"]) > 0 && $STEP > 2)
	{
		$STEP = $STEP - 1;
		$notRewriteFile = true;
	}
}

$strError = $oProfile->GetErrors();
$htmlError = '';
$io = CBXVirtualIo::GetInstance();

function ShowTblLine($data, $list, $line, $checked = true)
{
	?><tr>
		<td class="line-settings" title="<?echo GetMessage("KDA_IE_LINE_NUM").' '.($line+1);?>">
			<input type="hidden" name="SETTINGS[IMPORT_LINE][<?echo $list;?>][<?echo $line;?>]" value="0">
			<input type="checkbox" name="SETTINGS[IMPORT_LINE][<?echo $list;?>][<?echo $line;?>]" value="1" <?if($checked){echo 'checked';}?>>
			<span class="sandwich" title="<?=GetMessage("KDA_IE_ACTIONS_BTN")?>"></span>
		</td><?
		foreach($data as $row)
		{
			$style = $parentStyle = $dataStyle = '';
			$parentStyle = '';
			if($row['STYLE'])
			{
				$arStyle = $row['STYLE'];
				if(isset($arStyle['EXT']) && is_array($arStyle['EXT']))
				{
					$arStyle = array_merge($arStyle, $arStyle['EXT']);
					unset($arStyle['EXT'], $row['STYLE']['EXT']);
				}
				if($arStyle['BACKGROUND'])
				{
					$style .= 'background-color:#'.$arStyle['BACKGROUND'].';';
					$parentStyle .= 'background-color:#'.$arStyle['BACKGROUND'].';';
				}
				if($arStyle['COLOR']) $style .= 'color:#'.$arStyle['COLOR'].';';
				if($arStyle['FONT-WEIGHT']) $style .= 'font-weight:bold;';
				if($arStyle['FONT-STYLE']) $style .= 'font-style:italic;';
				if($arStyle['TEXT-DECORATION']=='single') $style .= 'text-decoration:underline;';
				if($arStyle['PADDING-LEFT'] > 0) $style .= 'padding-left:'.((int)$arStyle['PADDING-LEFT']*4).'px;';
				$dataStyle = 'data-style="'.htmlspecialcharsex(CUtil::PhpToJSObject($row['STYLE'])).'"';
			}
			$style = ($style ? 'style="'.$style.'"' : '');
			$parentStyle = ($parentStyle ? 'style="'.$parentStyle.'"' : '');
		?><td <?echo $parentStyle;?>><div class="cell" <?echo $parentStyle;?>><div class="cell_inner" <?echo $style;?> <?echo $dataStyle;?>><?echo nl2br(htmlspecialcharsex($row['VALUE']));?></div></div></td><?
		}
	?></tr><?
}
/////////////////////////////////////////////////////////////////////
if ($REQUEST_METHOD == "POST" && $MODE=='AJAX')
{
	define('PUBLIC_AJAX_MODE', 'Y');
	
	if($ACTION=='SHOW_MODULE_MESSAGE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		?><div><?php
		$moduleShowDemoFunc(true);
		?></div><?php
		die();
	}
	
	if($ACTION=='DELETE_TMP_DIRS')
	{
		CKDAImportUtils::RemoveTmpFiles();
		die();
	}
	
	if($ACTION=='REMOVE_PROCESS_PROFILE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$oProfile = new CKDAImportProfile();
		$oProfile->RemoveProcessedProfile($PROCCESS_PROFILE_ID);
		die();
	}
	
	if($ACTION=='GET_PROCESS_PARAMS')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$oProfile = new CKDAImportProfile();
		echo CUtil::PhpToJSObject($oProfile->GetProccessParams($PROCCESS_PROFILE_ID));
		die();
	}
	
	if($ACTION=='GET_SECTION_LIST')
	{
		$fl = new CKDAFieldList($SETTINGS_DEFAULT);
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		?><div><?php
		$fl->ShowSelectSections($IBLOCK_ID, 'sections');
		$fl->ShowSelectFields($IBLOCK_ID, 'fields');
		
		$val = (isset($SETTINGS['SEARCH_SECTIONS'][$LIST_INDEX]) ? $SETTINGS['SEARCH_SECTIONS'][$LIST_INDEX] : array());
		$fl->ShowSelectSections($IBLOCK_ID, 'search_sections', $val, true);
		
		$val = (isset($SETTINGS['LIST_ELEMENT_UID'][$LIST_INDEX]) ? $SETTINGS['LIST_ELEMENT_UID'][$LIST_INDEX] : array());
		$fl->ShowSelectUidFields($IBLOCK_ID, 'element_uid', $val);
		
		$OFFERS_IBLOCK_ID = CKDAImportUtils::GetOfferIblock($IBLOCK_ID);
		$val = (isset($SETTINGS['LIST_ELEMENT_UID_SKU'][$LIST_INDEX]) ? $SETTINGS['LIST_ELEMENT_UID_SKU'][$LIST_INDEX] : array());
		if($OFFERS_IBLOCK_ID) $fl->ShowSelectUidFields($OFFERS_IBLOCK_ID, 'element_uid_sku', $val, 'OFFER_');
		else echo '<select name="element_uid_sku" multiple></select>';
		?></div><?php
		die();
	}
	
	if($ACTION=='GET_UID')
	{
		$fl = new CKDAFieldList($SETTINGS_DEFAULT);
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		?><div><?php
		$fl->ShowSelectUidFields($IBLOCK_ID, 'fields[]');
		$OFFERS_IBLOCK_ID = CKDAImportUtils::GetOfferIblock($IBLOCK_ID);
		if($OFFERS_IBLOCK_ID)
		{
			$fl->ShowSelectUidFields($OFFERS_IBLOCK_ID, 'fields_sku[]', false, 'OFFER_');
		}
		else
		{
			echo '<select name="fields_sku[]" multiple></select>';
		}
		$fl->ShowSelectPropertyList($IBLOCK_ID, 'properties[]');
		?><div id="properties_for_sum"><?php $fl->ShowSelectPropertyListForSum($IBLOCK_ID, 'SETTINGS_DEFAULT[ELEMENT_PROPERTIES_FOR_QUANTITY][]');?></div><?php
		?><div id="properties_for_sum_sku"><?php $fl->ShowSelectPropertyListForSum($OFFERS_IBLOCK_ID, 'SETTINGS_DEFAULT[OFFER_PROPERTIES_FOR_QUANTITY][]', false, true);?></div><?php
		?></div><?php
		die();
	}
	
	if($ACTION=='DELETE_PROFILE')
	{
		$fl = new CKDAImportProfile();
		$fl->Delete($_REQUEST['ID']);
		die();
	}
	
	if($ACTION=='COPY_PROFILE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$fl = new CKDAImportProfile();
		$id = $fl->Copy($_REQUEST['ID']);
		echo CUtil::PhpToJSObject(array('id'=>$id));
		die();
	}
	
	if($ACTION=='RENAME_PROFILE')
	{
		$newName = $_REQUEST['NAME'];
		if((!defined('BX_UTF') || !BX_UTF)) $newName = $APPLICATION->ConvertCharset($newName, 'UTF-8', 'CP1251');
		$fl = new CKDAImportProfile();
		$fl->Rename($_REQUEST['ID'], $newName);
		die();
	}
	
	if($ACTION=='APPLY_TO_LISTS')
	{
		$fl = new CKDAImportProfile();
		$fl->ApplyToLists($_REQUEST['PROFILE_ID'], $_REQUEST['LIST_FROM'], $_REQUEST['LIST_TO']);
		die();
	}
}

if ($REQUEST_METHOD == "POST" && $STEP > 1 && check_bitrix_sessid())
{
	if($ACTION) define('PUBLIC_AJAX_MODE', 'Y');
	
	//*****************************************************************//	
	if ($STEP > 1)
	{
		//*****************************************************************//	
		$sess = $_SESSION;
		session_write_close();
		$_SESSION = $sess;
		
		if (strlen($strError) <= 0)
		{
			if(($STEP==2 && !$notRewriteFile) || $_POST['FORCE_UPDATE_FILE']=='Y')
			{
				if((!isset($_FILES["DATA_FILE"]) || !$_FILES["DATA_FILE"]["tmp_name"]) && (!isset($_POST['DATA_FILE']) || is_numeric($_POST['DATA_FILE'])))
				{
					if($_POST["EXT_DATA_FILE"]) $_POST['DATA_FILE'] = $_POST["EXT_DATA_FILE"];
					elseif($SETTINGS_DEFAULT["EXT_DATA_FILE"]) $_POST['DATA_FILE'] = $SETTINGS_DEFAULT["EXT_DATA_FILE"];
					elseif($SETTINGS_DEFAULT['EMAIL_DATA_FILE'])
					{
						$fileId = \Bitrix\KdaImportexcel\SMail::GetNewFile($SETTINGS_DEFAULT['EMAIL_DATA_FILE'], 0, 'kda_import_'.$PROFILE_ID);
						if($fileId > 0)
						{
							if($_POST['OLD_DATA_FILE'])
							{
								CKDAImportUtils::DeleteFile($_POST['OLD_DATA_FILE']);
							}
							$SETTINGS_DEFAULT["DATA_FILE"] = $_POST['DATA_FILE'] = $fileId;
						}
					}
				}
				elseif($SETTINGS_DEFAULT['EMAIL_DATA_FILE'])
				{
					unset($SETTINGS_DEFAULT['EMAIL_DATA_FILE']);
				}
			}
		
			$DATA_FILE_NAME = "";
			if((isset($_FILES["DATA_FILE"]) && $_FILES["DATA_FILE"]["tmp_name"]) || (isset($_POST['DATA_FILE']) && $_POST['DATA_FILE'] && !is_numeric($_POST['DATA_FILE'])))
			{
				$extFile = false;
				$fid = 0;
				if(isset($_FILES["DATA_FILE"]) && is_uploaded_file($_FILES["DATA_FILE"]["tmp_name"]))
				{
					//$fid = CKDAImportUtils::SaveFile($_FILES["DATA_FILE"]);
					$arFile = CKDAImportUtils::MakeFileArray($_FILES["DATA_FILE"]);
					$arFile['external_id'] = 'kda_import_'.$PROFILE_ID;
					$arFile['del_old'] = 'Y';
					$fid = CKDAImportUtils::SaveFile($arFile);
				}
				elseif(isset($_POST['DATA_FILE']) && strlen($_POST['DATA_FILE']) > 0)
				{
					$extFile = true;
					if(strpos($_POST['DATA_FILE'], '/')===0) 
					{
						$filepath = $_POST['DATA_FILE'];
						if(!file_exists($filepath))
						{
							$filepath = $_SERVER["DOCUMENT_ROOT"].$filepath;
						}
						if(!file_exists($filepath))
						{
							if(defined("BX_UTF")) $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'CP1251');
							else $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'UTF-8');
						}
					}
					else
					{
						//$extFile = true;
						$filepath = $_POST['DATA_FILE'];
						if($filepath && $_POST['OLD_DATA_FILE'])
						{
							$arOldFile = CFIle::GetFileArray($_POST['OLD_DATA_FILE']);
							$oldFileSize = (int)filesize($_SERVER['DOCUMENT_ROOT'].$arOldFile['SRC']);
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true));
							$newFileSize = 0;
							$lastModified = '';
							if(is_callable(array($client, 'head')) && ($headers = $client->head($filepath)) && $client->getStatus()!=404)
							{
								$newFileSize = (int)$headers->get('content-length');
								$lastModified = $client->getHeaders()->get('last-modified');
								if(strlen($lastModified)) $lastModified = date('Y-m-d H:i:s', strtotime($lastModified));
								$SETTINGS_DEFAULT['LAST_MODIFIED_FILE'] = $lastModified;
							}
							if($oldFileSize > 0 && $newFileSize > 0 && $oldFileSize==$newFileSize && (strlen($lastModified)==0 || $lastModified<=$_POST['LAST_MODIFIED_FILE']))
							{
								$fid = $_POST['OLD_DATA_FILE'];
							}
						}
					}
					if(!$fid)
					{
						$arFile = CKDAImportUtils::MakeFileArray($filepath);
						if($arFile['name'])
						{
							if(strpos($arFile['name'], '.')===false) $arFile['name'] .= '.csv';
							$arFile['external_id'] = 'kda_import_'.$PROFILE_ID;
							$arFile['del_old'] = 'Y';
							$fid = CKDAImportUtils::SaveFile($arFile);
						}
					}
				}
				
				if(!$fid)
				{
					$strError.= GetMessage("KDA_IE_FILE_UPLOAD_ERROR")."<br>";
					if($extFile)
					{
						$SETTINGS_DEFAULT["EXT_DATA_FILE"] = $_POST['DATA_FILE'];
					}
				}
				else
				{
					$SETTINGS_DEFAULT["DATA_FILE"] = $fid;
					if($_POST['OLD_DATA_FILE'] && $_POST['OLD_DATA_FILE']!=$fid)
					{
						CKDAImportUtils::DeleteFile($_POST['OLD_DATA_FILE']);
					}
					$SETTINGS_DEFAULT["EXT_DATA_FILE"] = ($extFile ? $_POST['DATA_FILE'] : false);
				}
			}
			elseif(isset($_FILES["DATA_FILE"]) && is_array($_FILES["DATA_FILE"]) && $_FILES["DATA_FILE"]["error"]==1)
			{
				$strError.= GetMessage("KDA_IE_FILE_UPLOAD_ERROR")."<br>";
				$uploadMaxFilesize = CKDAImportUtils::GetIniAbsVal('upload_max_filesize');
				$postMaxSize = CKDAImportUtils::GetIniAbsVal('post_max_size');
				if($uploadMaxFilesize > 0 || $postMaxSize > 0)
				{
					$partError = '';
					if($uploadMaxFilesize > 0) $partError .= 'upload_max_filesize = '.($uploadMaxFilesize/(1024*1024)).'Mb<br>';
					if($postMaxSize > 0) $partError .= 'post_max_size = '.($postMaxSize/(1024*1024)).'Mb<br>';
					$strError.= '<br>'.sprintf(GetMessage("KDA_IE_FILE_UPLOAD_ERROR_MAX_SIZE"), $partError)."<br>";
				}
			}
		}
		
		if(!$SETTINGS_DEFAULT["DATA_FILE"] && $_POST['OLD_DATA_FILE'])
		{
			$SETTINGS_DEFAULT["DATA_FILE"] = $_POST['OLD_DATA_FILE'];
		}
		
		if($SETTINGS_DEFAULT["DATA_FILE"])
		{
			//$arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"]);
			$i = 0;
			while($i < 2 && !($arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"])))
			{
				\CFile::CleanCache($SETTINGS_DEFAULT["DATA_FILE"]);
				$i++;
			}
			if(stripos($arFile['SRC'], 'http')===0)
			{
				$arFileUrl = parse_url($arFile['SRC']);
				if($arFileUrl['path']) $arFile['SRC'] = $arFileUrl['path'];
			}
			$SETTINGS_DEFAULT['URL_DATA_FILE'] = $arFile['SRC'];
		}
		
		if(strlen($PROFILE_ID)==0)
		{
			$strError.= GetMessage("KDA_IE_PROFILE_NOT_CHOOSE")."<br>";
		}

		if (strlen($strError) <= 0)
		{
			if (strlen($DATA_FILE_NAME) <= 0)
			{
				if (strlen($SETTINGS_DEFAULT['URL_DATA_FILE']) > 0)
				{
					$SETTINGS_DEFAULT['URL_DATA_FILE'] = trim(str_replace("\\", "/", trim($SETTINGS_DEFAULT['URL_DATA_FILE'])) , "/");
					$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"], "/".$SETTINGS_DEFAULT['URL_DATA_FILE']);
					if (
						(strlen($FILE_NAME) > 1)
						&& ($FILE_NAME === "/".$SETTINGS_DEFAULT['URL_DATA_FILE'])
						&& $io->FileExists($_SERVER["DOCUMENT_ROOT"].$FILE_NAME)
						/*&& ($APPLICATION->GetFileAccessPermission($FILE_NAME) >= "W")*/
					)
					{
						$DATA_FILE_NAME = $FILE_NAME;
					}
				}
			}

			if (strlen($DATA_FILE_NAME) <= 0)
				$strError.= GetMessage("KDA_IE_NO_DATA_FILE")."<br>";
			else
				$SETTINGS_DEFAULT['URL_DATA_FILE'] = $DATA_FILE_NAME;
			
			/*if(ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME))=='xls' && ini_get('mbstring.func_overload')==2)
			{
				$strError.= GetMessage("KDA_IE_FUNC_OVERLOAD_XLS")."<br>";
			}*/
			
			if(strlen($strError)==0 && !in_array(ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME)), array('txt', 'csv', 'xls', 'xlsx', 'xlsm', 'dbf')))
			{
				$strError.= GetMessage("KDA_IE_FILE_NOT_SUPPORT")."<br>";
				if(in_array(ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME)), array('xml', 'yml')))
				{
					$htmlError.= GetMessage("KDA_IE_USE_XML_MODULE")."<br>";
				}
			}

			if(!$SETTINGS_DEFAULT['IBLOCK_ID'])
				$strError.= GetMessage("KDA_IE_NO_IBLOCK")."<br>";
			elseif (!CIBlockRights::UserHasRightTo($SETTINGS_DEFAULT['IBLOCK_ID'], $SETTINGS_DEFAULT['IBLOCK_ID'], "element_edit_any_wf_status"))
				$strError.= GetMessage("KDA_IE_NO_IBLOCK")."<br>";
			
			if(strlen($strError)==0 && (!$DATA_FILE_NAME = CKDAImportUtils::GetFileName($DATA_FILE_NAME)))
			{
				$strError.= GetMessage("KDA_IE_FILE_NOT_FOUND")."<br>";
			}
			
			if(empty($SETTINGS_DEFAULT['ELEMENT_UID']))
			{
				$strError.= GetMessage("KDA_IE_NO_ELEMENT_UID")."<br>";
			}
		}
		
		if (strlen($strError) <= 0)
		{
			/*Write profile*/
			$oProfile = new CKDAImportProfile();
			if($PROFILE_ID === 'new')
			{
				$PID = $oProfile->Add($NEW_PROFILE_NAME, $SETTINGS_DEFAULT["DATA_FILE"]);
				if($PID===false)
				{
					if($ex = $APPLICATION->GetException())
					{
						$strError .= $ex->GetString().'<br>';
					}
				}
				else
				{
					$PROFILE_ID = $PID;
				}
			}
			/*/Write profile*/
		}

		if (strlen($strError) > 0)
			$STEP = 1;
		
		if(isset($_POST["saveConfigButton"]) && strlen($_POST["saveConfigButton"]) > 0 && !$notRewriteFile)
			$STEP = 1;
		//*****************************************************************//
	}
	
	if($ACTION == 'SHOW_FULL_LIST')
	{
		try{
			$pparams = array_merge($SETTINGS_DEFAULT, (isset($SETTINGS) && is_array($SETTINGS) ? $SETTINGS : array()));
			$arWorksheets = CKDAImportExcel::GetPreviewData($DATA_FILE_NAME, $SHOW_FIRST_LINES, $pparams, $COUNT_COLUMNS, $PROFILE_ID);
		}catch(Exception $ex){
			$APPLICATION->RestartBuffer();
			ob_end_clean();
			echo GetMessage("KDA_IE_ERROR").$ex->getMessage();
			die();
		}
		
		$oProfile = new CKDAImportProfile();
		$arProfile = $oProfile->GetByID($PROFILE_ID);
		if(is_array($arProfile['SETTINGS']['IMPORT_LINE']))
		{
			$SETTINGS['IMPORT_LINE'] = $arProfile['SETTINGS']['IMPORT_LINE'];
		}
		
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		
		if(!$arWorksheets) $arWorksheets = array();
		foreach($arWorksheets as $k=>$worksheet)
		{
			if($k == $LIST_NUMBER)
			{
				foreach($worksheet['lines'] as $line=>$arLine)
				{
					$checked = ((!isset($SETTINGS['IMPORT_LINE'][$k][$line]) && (!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k])) || $SETTINGS['IMPORT_LINE'][$k][$line]);
					ShowTblLine($arLine, $k, $line, $checked);
				}
			}
		}
		die();
	}
	
	if($ACTION == 'SHOW_REVIEW_LIST')
	{
		$fl = new CKDAFieldList($SETTINGS_DEFAULT);
		$arIblocks = $fl->GetIblocks();
		try{
			$pparams = array_merge($SETTINGS_DEFAULT, (isset($SETTINGS) && is_array($SETTINGS) ? $SETTINGS : array()));
			$arWorksheets = CKDAImportExcel::GetPreviewData($DATA_FILE_NAME, $SHOW_FIRST_LINES, $pparams, false, $PROFILE_ID);
			if(true /*$SETTINGS_DEFAULT['AUTO_CREATION_PROPERTIES']=='Y'*/)
			{
				$oProfile = new CKDAImportProfile();
				$oProfile->UpdateFileSettings($SETTINGS, $EXTRASETTINGS, $arWorksheets, $PROFILE_ID);
			}
		}catch(Exception $ex){
			$APPLICATION->RestartBuffer();
			ob_end_clean();
			echo GetMessage("KDA_IE_ERROR").$ex->getMessage();
			die();
		}
		
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		
		if(!$arWorksheets) $arWorksheets = array();
		//$arWorksheets = array_slice($arWorksheets, 0, 1);
		foreach($arWorksheets as $k=>$worksheet)
		{
			$columns = (count($worksheet['lines']) > 0 ? count($worksheet['lines'][0]) : 1) + 1;
			$bEmptyList = empty($worksheet['lines']);
			$iblockId = ($SETTINGS['IBLOCK_ID'][$k] ? $SETTINGS['IBLOCK_ID'][$k] : $SETTINGS_DEFAULT['IBLOCK_ID']);
		?>
			<table class="kda-ie-tbl <?php if($bEmptyList){echo 'empty';}?>" data-list-index="<?php echo $k;?>" data-iblock-id=<?php echo $iblockId;?>>
				<tr class="heading">
					<td class="left"><?php echo GetMessage("KDA_IE_LIST_TITLE"); ?> "<?php echo $worksheet['title'];?>" <?php if($bEmptyList){echo GetMessage("KDA_IE_EMPTY_LIST");}?> <a href="javascript:void(0)" onclick="EList.ShowListSettings(this)" class="list-settings-link" title="<?php echo GetMessage("KDA_IE_LIST_SETTINGS");?>"></a></td>
					<td class="right list-settings">
                        <?php if(count($worksheet['lines']) > 0){?>
                            <?php if(!empty($SETTINGS['TITLES_LIST'][$k])){?>
								<input type="hidden" name="TITLES_JSON" value="<?php ?>">
								<script>EList.SetOldTitles('<?php echo $k;?>', <?php echo CUtil::PhpToJSObject($SETTINGS['TITLES_LIST'][$k]);?>);</script>
                        <?php
                        }?>
							<input type="hidden" name="SETTINGS[ADDITIONAL_SETTINGS][<?php echo $k;?>]" value="<?php if($SETTINGS['ADDITIONAL_SETTINGS'][$k])echo htmlspecialcharsex(CUtil::PhpToJSObject($SETTINGS['ADDITIONAL_SETTINGS'][$k]));?>">
							<input type="hidden" name="SETTINGS[LIST_LINES][<?php echo $k;?>]" value="<?php echo $worksheet['lines_count'];?>">
							<input type="hidden" name="SETTINGS[LIST_ACTIVE][<?php echo $k;?>]" value="N">
							<input type="checkbox" name="SETTINGS[LIST_ACTIVE][<?php echo $k;?>]" id="list_active_<?php echo $k;?>" value="Y" <?=(!isset($SETTINGS['LIST_ACTIVE'][$k]) || $SETTINGS['LIST_ACTIVE'][$k]=='Y' ? 'checked' : '')?>> <label for="list_active_<?php echo $k;?>"><small><?php echo GetMessage("KDA_IE_DOWNLOAD_LIST"); ?></small></label>
							<a href="javascript:void(0)" class="showlist" onclick="EList.ToggleSettings(this)" title="<?php echo GetMessage("KDA_IE_LIST_SHOW"); ?>"></a>
                            <?php
							if(is_array($SETTINGS['LIST_SETTINGS'][$k]))
							{
								foreach($SETTINGS['LIST_SETTINGS'][$k] as $k2=>$v2)
								{
									?><input type="hidden" name="SETTINGS[LIST_SETTINGS][<?php echo $k;?>][<?php echo $k2;?>]"value="<?php echo htmlspecialcharsex($v2);?>"><?php
								}
							}
							if(is_array($EXTRASETTINGS[$k]))
							{
								foreach($EXTRASETTINGS[$k] as $k2=>$v2)
								{
									if(strpos($k2, '__')===0 && !empty($v2))
									{
										?><div><a href="javascript:void(0)" id="field_settings_<?php echo $k;?>_<?php echo $k2;?>" onclick="EList.ShowFieldSettings(this);"><input type="hidden" name="EXTRASETTINGS[<?php echo $k;?>][<?php echo $k2;?>]" value=""><script>EList.SetExtraParams("field_settings_<?php echo $k;?>_<?php echo $k2;?>", <?php echo CUtil::PhpToJSObject($v2);?>)</script></a></div><?php
									}
								}
							}
						}?>
					</td>
				</tr>
				<tr class="settings">
					<td colspan="2">
						<table class="additional">
							<tr>
								<td><?php echo GetMessage("KDA_IE_INFOBLOCK"); ?> </td>
								<td>
									<select name="SETTINGS[IBLOCK_ID][<?php echo $k;?>]" onchange="EList.ChooseIblock(this);">
										<!--<option value=""><?php echo GetMessage("KDA_IE_CHOOSE_IBLOCK"); ?></option>-->
                                        <?php
										foreach($arIblocks as $type)
										{
											?><optgroup label="<?php echo $type['NAME']?>"><?php
											foreach($type['IBLOCKS'] as $iblock)
											{
												?><option value="<?php echo $iblock["ID"];?>" <?php if($iblock["ID"]==$iblockId){echo 'selected';}?>><?php echo htmlspecialcharsbx($iblock["NAME"].' ['.$iblock["ID"].']'); ?></option><?php
											}
											?></optgroup><?php
										}
										?>
									</select>
								</td>
								<td width="50px">&nbsp;</td>
								<td><?php echo GetMessage("KDA_IE_SECTION"); ?> </td>
								<td><?php $fl->ShowSelectSections($iblockId, 'SETTINGS[SECTION_ID]['.$k.']', $SETTINGS['SECTION_ID'][$k]);?></td>
							</tr>
						</table>
						<div class="copysettings">
							<a href="javascript:void(0)" onclick="EList.ApplyToAllLists(this)"><?php echo GetMessage("KDA_IE_APPLY_TO_ALL_LISTS"); ?></a>
						</div>
						<div class="addsettings">
							<a href="javascript:void(0)" class="addsettings_link" onclick="EList.ToggleAddSettingsBlock(this)"><span><?php echo GetMessage("KDA_IE_ADDITIONAL_SETTINGS"); ?></span></a>
							<div class="addsettings_inner">
								<table class="additional">
									<col><col width="400px">
                                    <?php
									$setSections = (bool)($SETTINGS['SET_SEARCH_SECTIONS'][$k]=='Y');
									?>
									<tr>
										<td><?php echo GetMessage("KDA_IE_SET_SEARCH_SECTIONS"); ?>:</td>
										<td>
											<input type="hidden" name="SETTINGS[SET_SEARCH_SECTIONS][<?php echo $k;?>]" value="N">
											<input type="checkbox" name="SETTINGS[SET_SEARCH_SECTIONS][<?php echo $k;?>]" value="Y" <?php if($setSections){echo 'checked';}?>onchange="EList.ToggleAddSettings(this)">
										</td>
									</tr>
									
									<tr class="subfield" <?php if(!$setSections){echo 'style="display: none;"';}?>>
										<td><?php echo GetMessage("KDA_IE_SEARCH_SECTIONS"); ?>: <span id="hint_SEARCH_SECTIONS_<?php echo $k;?>"></span><script>BX.hint_replace(BX('hint_SEARCH_SECTIONS_<?php echo $k;?>'), '<?php echo GetMessage("KDA_IE_SEARCH_SECTIONS_HINT"); ?>');</script></td>
										<td>
                                            <?php
											$val = (isset($SETTINGS['SEARCH_SECTIONS'][$k]) ? $SETTINGS['SEARCH_SECTIONS'][$k] : array());
											$fl->ShowSelectSections($iblockId, 'SETTINGS[SEARCH_SECTIONS]['.$k.'][]', $SETTINGS['SEARCH_SECTIONS'][$k], true);
											?>
										</td>
									</tr>

                                    <?php
									$changeUid = (bool)($SETTINGS['CHANGE_ELEMENT_UID'][$k]=='Y');
									?>
									<tr>
										<td><?php echo GetMessage("KDA_IE_CHANGE_ELEMENT_UID"); ?>:</td>
										<td>
											<input type="hidden" name="SETTINGS[CHANGE_ELEMENT_UID][<?php echo $k;?>]" value="N">
											<input type="checkbox" name="SETTINGS[CHANGE_ELEMENT_UID][<?php echo $k;?>]" value="Y" <?php if($changeUid){echo 'checked';}?>onchange="EList.ToggleAddSettings(this)">
										</td>
									</tr>
									
									<tr class="subfield" <?php if(!$changeUid){echo 'style="display: none;"';}?>>
										<td><?php echo GetMessage("KDA_IE_ELEMENT_UID"); ?>: <span id="hint_ELEMENT_UID_<?php echo $k;?>"></span><script>BX.hint_replace(BX('hint_ELEMENT_UID_<?php echo $k;?>'), '<?php echo GetMessage("KDA_IE_ELEMENT_UID_HINT"); ?>');</script></td>
										<td>
                                            <?php
											$val = (isset($SETTINGS['LIST_ELEMENT_UID'][$k]) ? $SETTINGS['LIST_ELEMENT_UID'][$k] : array());
											$fl->ShowSelectUidFields($iblockId, 'SETTINGS[LIST_ELEMENT_UID]['.$k.'][]', $val);
											?>
										</td>
									</tr>

                                    <?php
									$offersIblockId = CKDAImportUtils::GetOfferIblock($iblockId);
									?>	
									<tr class="subfield" <?php if(!$changeUid || !$offersIblockId){echo 'style="display: none;"';}?>>
										<td><?php echo GetMessage("KDA_IE_ELEMENT_UID_SKU"); ?>: <span id="hint_ELEMENT_UID_SKU_<?php echo $k;?>"></span><script>BX.hint_replace(BX('hint_ELEMENT_UID_SKU_<?php echo $k;?>'), '<?php echo GetMessage("KDA_IE_ELEMENT_UID_SKU_HINT"); ?>');</script></td>
										<td>
                                            <?php
										if($offersIblockId)
										{
											$val = (isset($SETTINGS['LIST_ELEMENT_UID_SKU'][$k]) ? $SETTINGS['LIST_ELEMENT_UID_SKU'][$k] : array());
											$fl->ShowSelectUidFields($offersIblockId, 'SETTINGS[LIST_ELEMENT_UID_SKU]['.$k.'][]', $val, 'OFFER_');
										}
										else
										{
											echo '<select name="SETTINGS[LIST_ELEMENT_UID_SKU]['.$k.'][]" multiple></select>';
										}
										?>
										</td>
									</tr>

                                    <?php
									$fileExt = ToLower(CKDAImportUtils::GetFileExtension($DATA_FILE_NAME));
									$changeCsvParams = (bool)($SETTINGS['CSV_PARAMS']['CHANGE']=='Y');
									if($fileExt=='csv' || $fileExt=='txt')
									{
									?>
										<tr>
											<td><?php echo sprintf(GetMessage("KDA_IE_CHANGE_CSV_PARAMS"), $fileExt); ?>:</td>
											<td>
												<input type="hidden" name="SETTINGS[CSV_PARAMS][CHANGE]" value="N">
												<input type="checkbox" name="SETTINGS[CSV_PARAMS][CHANGE]" value="Y" <?php if($changeCsvParams){echo 'checked';}?>onchange="EList.ToggleAddSettings(this)">
											</td>
										</tr>

										<tr class="subfield" <?php if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?php echo GetMessage("KDA_IE_CHANGE_CSV_SEPARATOR"); ?>:</td>
											<td>
                                                <?php
												$val = (isset($SETTINGS['CSV_PARAMS']['SEPARATOR']) && strlen(trim($SETTINGS['CSV_PARAMS']['SEPARATOR'])) > 0 ? trim($SETTINGS['CSV_PARAMS']['SEPARATOR']) : ';');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][SEPARATOR]" value="<?php echo htmlspecialcharsex($val)?>" size="3" maxlength="3">
											</td>
										</tr>
										<tr class="subfield" <?php if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?php echo GetMessage("KDA_IE_CHANGE_CSV_ENCLOSURE"); ?>:</td>
											<td>
                                                <?php
												$val = (isset($SETTINGS['CSV_PARAMS']['ENCLOSURE']) ? trim($SETTINGS['CSV_PARAMS']['ENCLOSURE']) : '"');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][ENCLOSURE]" value="<?php echo htmlspecialcharsex($val)?>" size="3" maxlength="3">
											</td>
										</tr>
										<tr class="subfield" <?php if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?php echo GetMessage("KDA_IE_CHANGE_CSV_ENCODING"); ?>:</td>
											<td>
                                                <?php
												$val = (isset($SETTINGS['CSV_PARAMS']['ENCODING']) && strlen(trim($SETTINGS['CSV_PARAMS']['ENCODING'])) > 0 ? trim($SETTINGS['CSV_PARAMS']['ENCODING']) : '');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][ENCODING]" value="<?php echo htmlspecialcharsex($val)?>" size="10" maxlength="50">
											</td>
										</tr>
                                        <?php if($fileExt=='txt'){?>
										<tr class="subfield" <?php if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?php echo GetMessage("KDA_IE_CHANGE_CSV_ROW_SEPARATOR"); ?>:</td>
											<td>
                                                <?php
												$val = (isset($SETTINGS['CSV_PARAMS']['ROW_SEPARATOR']) && strlen(trim($SETTINGS['CSV_PARAMS']['ROW_SEPARATOR'])) > 0 ? trim($SETTINGS['CSV_PARAMS']['ROW_SEPARATOR']) : '');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][ROW_SEPARATOR]" value="<?php echo htmlspecialcharsex($val)?>" size="10" maxlength="50">
											</td>
										</tr>
                                    <?php
                                    }?>
                                    <?php
                                    }?>
								</table>
							</div>
						</div>
						<div class="set_scroll">
							<div></div>
						</div>
						<div class="set">						
						<table class="list">
                            <?php
						if(count($worksheet['lines']) > 0)
						{
							?>
								<tr>
									<td>
										<input type="hidden" name="SETTINGS[CHECK_ALL][<?php echo $k;?>]" value="0">
										<span class="checkall">
											<label for="check_all_<?php echo $k;?>"><?php echo GetMessage("KDA_IE_CHECK_ALL"); ?></label><br>
											<input type="checkbox" name="SETTINGS[CHECK_ALL][<?php echo $k;?>]" id="check_all_<?php echo $k;?>" value="1" <?php if(!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k]){echo 'checked';}?>>
										</span>
										<span class="sandwich" title="<?=GetMessage("KDA_IE_ACTIONS_BTN")?>" data-type="titles"></span>
                                        <?php $fl->ShowSelectFields($iblockId, 'FIELDS_LIST['.$k.']')?>
									</td>
                                    <?php
									$num_rows = count($worksheet['lines'][0]);
									for($i = 0; $i < $num_rows; $i++)
									{
										$arKeys = array($i);
										if(is_array($SETTINGS['FIELDS_LIST'][$k]))
											$arKeys = array_merge($arKeys, preg_grep('/^'.$i.'_\d+$/', array_keys($SETTINGS['FIELDS_LIST'][$k])));
										?>
										<td class="kda-ie-field-select" title="#CELL<?php echo ($i+1);?>#">
											<b><?php echo CKDAImportUtils::GetColLetterByIndex($i);?></b>
                                            <?php foreach($arKeys as $j){?>
												<div>
                                                    <?php /*$fl->ShowSelectFields($iblockId, 'SETTINGS[FIELDS_LIST]['.$k.']['.$j.']', $SETTINGS['FIELDS_LIST'][$k][$j])*/?>
													<input type="hidden" name="SETTINGS[FIELDS_LIST][<?php echo $k?>][<?php echo $j?>]" value="<?php echo $SETTINGS['FIELDS_LIST'][$k][$j]?>" >
                                                    <?php /*?><input type="text" name="FIELDS_LIST_SHOW[<?echo $k?>][<?echo $j?>]" value="" class="fieldval"><?*/?>
													<span class="fieldval_wrap"><span class="fieldval" id="field-list-show-<?php echo $k?>-<?php echo $j?>"></span></span>
													<a href="javascript:void(0)" class="field_settings <?=(empty($EXTRASETTINGS[$k][$j]) ? 'inactive' : '')?>" id="field_settings_<?=$k?>_<?=$j?>" title="<?php echo GetMessage("KDA_IE_SETTINGS_FIELD"); ?>" onclick="EList.ShowFieldSettings(this);">
														<input type="hidden" name="EXTRASETTINGS[<?php echo $k?>][<?php echo $j?>]" value="">
                                                        <?php if(!empty($EXTRASETTINGS[$k][$j])){?>
															<script>EList.SetExtraParams("field_settings_<?=$k?>_<?=$j?>", <?php echo CUtil::PhpToJSObject($EXTRASETTINGS[$k][$j]);?>)</script>
                                                        <?php
                                                        }?>
													</a>
													<a href="javascript:void(0)" class="field_delete" title="<?php echo GetMessage("KDA_IE_SETTINGS_DELETE_FIELD"); ?>" onclick="EList.DeleteUploadField(this);"></a>
												</div>
                                            <?php
                                            }?>
											<div class="kda-ie-field-select-btns">
												<div class="kda-ie-field-select-btns-inner">
													<a href="javascript:void(0)" class="kda-ie-move-fields">
														<span title="<?php echo GetMessage("KDA_IE_SETTINGS_MOVE_FIELDS_LEFT"); ?>" onclick="return EList.ColumnsMoveLeft(this);"></span>
														<span title="<?php echo GetMessage("KDA_IE_SETTINGS_MOVE_FIELDS_RIGHT"); ?>" onclick="return EList.ColumnsMoveRight(this);"></span>
													</a>
													<a href="javascript:void(0)" class="kda-ie-add-load-field" title="<?php echo GetMessage("KDA_IE_SETTINGS_ADD_FIELD"); ?>" onclick="EList.AddUploadField(this);"></a>
												</div>
											</div>
										</td>
                                        <?php
									}
									?>
								</tr>
                            <?php
							
						}			
						
						foreach($worksheet['lines'] as $line=>$arLine)
						{
							$checked = ((!isset($SETTINGS['IMPORT_LINE'][$k][$line]) && (!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k])) || $SETTINGS['IMPORT_LINE'][$k][$line]);
							ShowTblLine($arLine, $k, $line, $checked);
						}
						?>
						</table>
						</div>
                        <?php if($worksheet['show_more']){?>
							<input type="button" value="<?php echo GetMessage("KDA_IE_SHOW_LIST"); ?>" onclick="EList.ShowFull(this);">
                        <?php
                        }?>
						<br><br>
					</td>
				</tr>
			</table>
            <?php
		}
		die();
	}

    /* start import */
	if($ACTION == 'DO_IMPORT')
	{
		unset($EXTRASETTINGS);
		$oProfile = new CKDAImportProfile();
		$oProfile->ApplyExtra($EXTRASETTINGS, $PROFILE_ID);
		$params = array_merge($SETTINGS_DEFAULT, $SETTINGS);
		$stepparams = $_POST['stepparams'];
		$arResult = $moduleRunnerClass::ImportIblock($DATA_FILE_NAME, $params, $EXTRASETTINGS, $stepparams, $PROFILE_ID);
		$APPLICATION->RestartBuffer();
		if(ob_get_contents()) ob_end_clean();
		echo CUtil::PhpToJSObject($arResult);
		
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
		die();
	}
	
	/*Profile update*/
	if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!=='new')
	{
		$oProfile->Update($PROFILE_ID, $SETTINGS_DEFAULT, $SETTINGS);
		if(is_array($EXTRASETTINGS)) $oProfile->UpdateExtra($PROFILE_ID, $EXTRASETTINGS);
	}
	/*/Profile update*/
	
	//*****************************************************************//

}

/////////////////////////////////////////////////////////////////////
$APPLICATION->SetTitle(GetMessage("KDA_IE_PAGE_TITLE").$STEP);
require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
/*********************************************************************/
/********************  BODY  *****************************************/
/*********************************************************************/

$arSubMenu = array();
if($oProfile instanceof CKDAImportProfileDB)
{
	$arSubMenu[] = array(
		"TEXT"=>GetMessage("KDA_IE_MENU_PROFILE_LIST"),
		"TITLE"=>GetMessage("KDA_IE_MENU_PROFILE_LIST"),
		"LINK" => "/bitrix/admin/".$moduleFilePrefix."_profile_list.php?lang=".LANG,
	);
}
$arSubMenu[] = array(
	"TEXT"=>GetMessage("KDA_IE_SHOW_CRONTAB"),
	"TITLE"=>GetMessage("KDA_IE_SHOW_CRONTAB"),
	"ONCLICK" => "EProfile.ShowCron();",
);
$arSubMenu[] = array(
	"TEXT" => GetMessage("KDA_IE_TOOLS_IMG_LOADER"),
	"TITLE" => GetMessage("KDA_IE_TOOLS_IMG_LOADER"),
	"ONCLICK" => "EProfile.ShowMassUploader();"
);
$aMenu = array(
	array(
		"TEXT"=>GetMessage("KDA_IE_MENU_VIDEO"),
		"TITLE"=>GetMessage("KDA_IE_MENU_VIDEO"),
		"ONCLICK" => "EHelper.ShowHelp();",
		"ICON" => "",
	),
	array(
		/*"TEXT"=>GetMessage("KDA_IE_MENU_DOC"),
		"TITLE"=>GetMessage("KDA_IE_MENU_DOC"),
		"ONCLICK" => "EHelper.ShowHelp(1);",
		"ICON" => "",*/
		"HTML" => '<a href="https://esolutions.su/docs/kda.importexcel/" target="blank" class="adm-btn" title="'.GetMessage("KDA_IE_MENU_DOC").'">'.GetMessage("KDA_IE_MENU_DOC").'</a>'
	),
	array(
		"TEXT"=>GetMessage("KDA_IE_TOOLS_LIST"),
		"TITLE"=>GetMessage("KDA_IE_TOOLS_LIST"),
		"MENU" => $arSubMenu,
		"ICON" => "btn_green",
	)
);
$context = new CAdminContextMenu($aMenu);
$context->Show();


if ($STEP < 2)
{
	$oProfile = new CKDAImportProfile();
	$arProfiles = $oProfile->GetProcessedProfiles();
	if(!empty($arProfiles))
	{
		$message = '';
		foreach($arProfiles as $k=>$v)
		{
			$message .= '<div class="kda-proccess-item">'.GetMessage("KDA_IE_PROCESSED_PROFILE").': '.$v['name'].' ('.GetMessage("KDA_IE_PROCESSED_PERCENT_LOADED").' '.$v['percent'].'%). &nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(0)" onclick="EProfile.ContinueProccess(this, '.$v['key'].')">'.GetMessage("KDA_IE_PROCESSED_CONTINUE").'</a> &nbsp; <a href="javascript:void(0)" onclick="EProfile.RemoveProccess(this, '.$v['key'].')">'.GetMessage("KDA_IE_PROCESSED_DELETE").'</a></div>';
		}
		CAdminMessage::ShowMessage(array(
			'TYPE' => 'error',
			'MESSAGE' => GetMessage("KDA_IE_PROCESSED_TITLE"),
			'DETAILS' => $message,
			'HTML' => true
		));
	}
}

if($SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y')
{
	CAdminMessage::ShowMessage(array(
		'TYPE' => 'ok',
		'MESSAGE' => GetMessage("KDA_IE_DELETE_MODE_TITLE"),
		'DETAILS' => GetMessage("KDA_IE_DELETE_MODE_MESSAGE"),
		'HTML' => true
	));	
}

if(strlen($strError) > 0)
{
	CAdminMessage::ShowMessage(array(
		'MESSAGE' => $strError,
		'DETAILS' => $htmlError,
		'HTML' => true
	));
}
?>

<form method="POST" action="<?php echo $sDocPath ?>?<?php if(strlen($PROFILE_ID) > 0){ echo 'PROFILE_ID='.$PROFILE_ID.'&'; }?>lang=<?php echo LANG ?>" ENCTYPE="multipart/form-data" name="dataload" id="dataload" class="kda-ie-s1-form">

    <?php
$arProfile = (strlen($PROFILE_ID) > 0 ? $oProfile->GetFieldsByID($PROFILE_ID) : array());
$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage("KDA_IE_TAB1") ,
		"ICON" => "iblock",
		"TITLE" => GetMessage("KDA_IE_TAB1_ALT"),
	) ,
	array(
		"DIV" => "edit2",
		"TAB" => GetMessage("KDA_IE_TAB2") ,
		"ICON" => "iblock",
		"TITLE" => sprintf(GetMessage("KDA_IE_TAB2_ALT"), (isset($arProfile['NAME']) ? $arProfile['NAME'] : '')),
	) ,
	array(
		"DIV" => "edit3",
		"TAB" => GetMessage("KDA_IE_TAB3") ,
		"ICON" => "iblock",
		"TITLE" => sprintf(GetMessage("KDA_IE_TAB3_ALT"), (isset($arProfile['NAME']) ? $arProfile['NAME'] : '')),
	) ,
);

$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();
?>

    <?php $tabControl->BeginNextTab();
if ($STEP == 1)
{
	CKDAImportUtils::SaveStat();
	$fl = new CKDAFieldList($SETTINGS_DEFAULT);
	$oProfile = new CKDAImportProfile();
?>

	<tr class="heading">
		<td colspan="2" class="kda-ie-profile-header">
			<div>
                <?php echo GetMessage("KDA_IE_PROFILE_HEADER"); ?>
				<a href="javascript:void(0)" onclick="EHelper.ShowHelp();" title="<?php echo GetMessage("KDA_IE_MENU_HELP"); ?>" class="kda-ie-help-link"></a>
			</div>
		</td>
	</tr>

	<tr>
		<td><?php echo GetMessage("KDA_IE_PROFILE"); ?>:</td>
		<td>
            <?php
			if($PROFILE_ID=='new') $profileVersion = 2;
			else $profileVersion = (array_key_exists('PROFILE_VERSION', $SETTINGS_DEFAULT) ? $SETTINGS_DEFAULT['PROFILE_VERSION'] : 1);
			?>
			<input type="hidden" name="SETTINGS_DEFAULT[PROFILE_VERSION]" value="<?php echo htmlspecialcharsbx($profileVersion)?>">
            <?php $oProfile->ShowProfileList('PROFILE_ID');?>

            <?php if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!='new'){?>
				<span class="kda-ie-edit-btns">
					<a href="javascript:void(0)" class="adm-table-btn-edit" onclick="EProfile.ShowRename();" title="<?php echo GetMessage("KDA_IE_RENAME_PROFILE");?>" id="action_edit_button"></a>
					<a href="javascript:void(0);" class="adm-table-btn-copy" onclick="EProfile.Copy();" title="<?php echo GetMessage("KDA_IE_COPY_PROFILE");?>" id="action_copy_button"></a>
					<a href="javascript:void(0);" class="adm-table-btn-delete" onclick="if(confirm('<?php echo GetMessage("KDA_IE_DELETE_PROFILE_CONFIRM");?>')){EProfile.Delete();}" title="<?php echo GetMessage("KDA_IE_DELETE_PROFILE");?>" id="action_delete_button"></a>
				</span>
            <?php
            }?>
		</td>
	</tr>
	
	<tr id="new_profile_name">
		<td><?php echo GetMessage("KDA_IE_NEW_PROFILE_NAME"); ?>:</td>
		<td>
			<input type="text" name="NEW_PROFILE_NAME" value="<?php echo htmlspecialcharsbx($NEW_PROFILE_NAME)?>">
		</td>
	</tr>

    <?php
	if(strlen($PROFILE_ID) > 0)
	{
	?>
		<tr class="heading">
			<td colspan="2"><?php echo GetMessage("KDA_IE_DEFAULT_SETTINGS"); ?></td>
		</tr>
		
		<tr>
			<td width="40%"><?php echo GetMessage("KDA_IE_URL_DATA_FILE"); ?></td>
			<td width="60%" class="kda-ie-file-choose">
				<!--KDA_IE_CHOOSE_FILE-->
                <?php if($SETTINGS_DEFAULT['EMAIL_DATA_FILE']) echo '<input type="hidden" name="SETTINGS_DEFAULT[EMAIL_DATA_FILE]" value="'.htmlspecialcharsbx(base64_encode($SETTINGS_DEFAULT['EMAIL_DATA_FILE'])).'">';?>
                <?php if($SETTINGS_DEFAULT['EXT_DATA_FILE']) echo '<input type="hidden" name="EXT_DATA_FILE" value="'.htmlspecialcharsbx($SETTINGS_DEFAULT['EXT_DATA_FILE']).'">';?>
				<input type="hidden" name="LAST_MODIFIED_FILE" value="<?php echo htmlspecialcharsbx($SETTINGS_DEFAULT['LAST_MODIFIED_FILE']); ?>">
				<input type="hidden" name="OLD_DATA_FILE" value="<?php echo htmlspecialcharsbx($SETTINGS_DEFAULT['DATA_FILE']); ?>">
                <?php
				$arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"]);
				if(stripos($arFile['SRC'], 'http')===0)
				{
					$arFileUrl = parse_url($arFile['SRC']);
					if($arFileUrl['path']) $arFile['SRC'] = $arFileUrl['path'];
				}
				if($arFile['SRC'])
				{
					if(!file_exists($_SERVER['DOCUMENT_ROOT'].$arFile['SRC']))
					{
						if(defined("BX_UTF")) $arFile['SRC'] = $APPLICATION->ConvertCharsetArray($arFile['SRC'], LANG_CHARSET, 'CP1251');
						else $arFile['SRC'] = $APPLICATION->ConvertCharsetArray($arFile['SRC'], LANG_CHARSET, 'UTF-8');
						if(!file_exists($_SERVER['DOCUMENT_ROOT'].$arFile['SRC']))
						{
							unset($SETTINGS_DEFAULT["DATA_FILE"]);
						}
					}
				}
				else
				{
					unset($SETTINGS_DEFAULT["DATA_FILE"]);
				}
				//Cmodule::IncludeModule('fileman');
				echo \Bitrix\KdaImportexcel\CFileInput::Show("DATA_FILE", $SETTINGS_DEFAULT["DATA_FILE"], array(
					"IMAGE" => "N",
					"PATH" => "Y",
					"FILE_SIZE" => "Y",
					"DIMENSIONS" => "N"
				), array(
					'upload' => true,
					'medialib' => false,
					'file_dialog' => true,
					'cloud' => true,
					'email' => true,
					'linkauth' => true,
					'del' => false,
					'description' => false,
				));
				CKDAImportUtils::AddFileInputActions();
				?>
				<!--/KDA_IE_CHOOSE_FILE-->
			</td>
		</tr>

		<tr>
			<td><?php echo GetMessage("KDA_IE_INFOBLOCK"); ?></td>
			<td>
                <?php echo GetIBlockDropDownList($SETTINGS_DEFAULT['IBLOCK_ID'], 'SETTINGS_DEFAULT[IBLOCK_TYPE_ID]', 'SETTINGS_DEFAULT[IBLOCK_ID]', false, 'class="adm-detail-iblock-types"', 'class="adm-detail-iblock-list"'); ?>
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?php echo GetMessage("KDA_IE_SETTINGS_PROCESSING"); ?></td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_UID"); ?>: <span id="hint_ELEMENT_UID"></span><script>BX.hint_replace(BX('hint_ELEMENT_UID'), '<?php echo GetMessage("KDA_IE_ELEMENT_UID_HINT"); ?>');</script></td>
			<td>
				<input type="hidden" name="SETTINGS_DEFAULT[SHOW_MODE_ELEMENT_UID]" value="<?php echo htmlspecialcharsbx($SETTINGS_DEFAULT['SHOW_MODE_ELEMENT_UID']);?>">
                <?php $fl->ShowSelectUidFields($SETTINGS_DEFAULT['IBLOCK_ID'], 'SETTINGS_DEFAULT[ELEMENT_UID][]', $SETTINGS_DEFAULT['ELEMENT_UID']);?>
			</td>
		</tr>

        <?php
		$OFFERS_IBLOCK_ID = CKDAImportUtils::GetOfferIblock($SETTINGS_DEFAULT['IBLOCK_ID']);
		?>	
		<tr <?php if(!$OFFERS_IBLOCK_ID){echo 'style="display: none;"';}?> id="element_uid_sku">
			<td><?php echo GetMessage("KDA_IE_ELEMENT_UID_SKU"); ?>: <span id="hint_ELEMENT_UID_SKU"></span><script>BX.hint_replace(BX('hint_ELEMENT_UID_SKU'), '<?php echo GetMessage("KDA_IE_ELEMENT_UID_SKU_HINT"); ?>');</script></td>
			<td>
			<input type="hidden" name="SETTINGS_DEFAULT[SHOW_MODE_ELEMENT_UID_SKU]" value="<?php echo htmlspecialcharsbx($SETTINGS_DEFAULT['SHOW_MODE_ELEMENT_UID_SKU']);?>">
                <?php
			if($OFFERS_IBLOCK_ID)
			{
				$fl->ShowSelectUidFields($OFFERS_IBLOCK_ID, 'SETTINGS_DEFAULT[ELEMENT_UID_SKU][]', $SETTINGS_DEFAULT['ELEMENT_UID_SKU'], 'OFFER_');
			}
			else
			{
				echo '<select name="SETTINGS_DEFAULT[ELEMENT_UID_SKU][]" multiple></select>';
			}
			?>
			</td>
		</tr>

		<tr>
			<td><?php echo GetMessage("KDA_IE_ONLY_UPDATE_MODE"); ?>: <span id="hint_ONLY_UPDATE_MODE_ELEMENT"></span><script>BX.hint_replace(BX('hint_ONLY_UPDATE_MODE_ELEMENT'), '<?php echo GetMessage("KDA_IE_ONLY_UPDATE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_UPDATE_MODE_ELEMENT]" value="Y" <?php if($SETTINGS_DEFAULT['ONLY_UPDATE_MODE']=='Y' || $SETTINGS_DEFAULT['ONLY_UPDATE_MODE_ELEMENT']=='Y'){echo 'checked';}?>onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_CREATE_MODE_ELEMENT]', 'SETTINGS_DEFAULT[ONLY_DELETE_MODE]'])">
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ONLY_CREATE_MODE"); ?>: <span id="hint_ONLY_CREATE_MODE_ELEMENT"></span><script>BX.hint_replace(BX('hint_ONLY_CREATE_MODE_ELEMENT'), '<?php echo GetMessage("KDA_IE_ONLY_CREATE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_CREATE_MODE_ELEMENT]" value="Y" <?php if($SETTINGS_DEFAULT['ONLY_CREATE_MODE']=='Y' || $SETTINGS_DEFAULT['ONLY_CREATE_MODE_ELEMENT']=='Y'){echo 'checked';}?>onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_UPDATE_MODE_ELEMENT]', 'SETTINGS_DEFAULT[ONLY_DELETE_MODE]'])">
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ONLY_DELETE_MODE"); ?>: <span id="hint_ONLY_DELETE_MODE"></span><script>BX.hint_replace(BX('hint_ONLY_DELETE_MODE'), '<?php echo GetMessage("KDA_IE_ONLY_DELETE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_DELETE_MODE]" value="Y" <?php if($SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y'){echo 'checked';}?>onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_UPDATE_MODE_ELEMENT]', 'SETTINGS_DEFAULT[ONLY_CREATE_MODE_ELEMENT]'], '<?php echo htmlspecialcharsex(GetMessage("KDA_IE_ONLY_DELETE_MODE_CONFIRM")); ?>')">
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_NEW_DEACTIVATE"); ?>: <span id="hint_ELEMENT_NEW_DEACTIVATE"></span><script>BX.hint_replace(BX('hint_ELEMENT_NEW_DEACTIVATE'), '<?php echo GetMessage("KDA_IE_ELEMENT_NEW_DEACTIVATE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NEW_DEACTIVATE]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_NEW_DEACTIVATE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>

        <?php if($bCatalog){?>
			<tr>
				<td><?php echo GetMessage("KDA_IE_ELEMENT_NO_QUANTITY_DEACTIVATE"); ?>: <span id="hint_ELEMENT_NO_QUANTITY_DEACTIVATE"></span><script>BX.hint_replace(BX('hint_ELEMENT_NO_QUANTITY_DEACTIVATE'), '<?php echo GetMessage("KDA_IE_ELEMENT_NO_QUANTITY_DEACTIVATE_HINT"); ?>');</script></td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NO_QUANTITY_DEACTIVATE]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_NO_QUANTITY_DEACTIVATE']=='Y'){echo 'checked';}?>>
				</td>
			</tr>
			
			<tr>
				<td><?php echo GetMessage("KDA_IE_ELEMENT_NO_PRICE_DEACTIVATE"); ?>: <span id="hint_ELEMENT_NO_PRICE_DEACTIVATE"></span><script>BX.hint_replace(BX('hint_ELEMENT_NO_PRICE_DEACTIVATE'), '<?php echo GetMessage("KDA_IE_ELEMENT_NO_PRICE_DEACTIVATE_HINT"); ?>');</script></td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NO_PRICE_DEACTIVATE]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_NO_PRICE_DEACTIVATE']=='Y'){echo 'checked';}?>>
				</td>
			</tr>
    <?php
    }?>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_LOADING_ACTIVATE"); ?>: <span id="hint_ELEMENT_LOADING_ACTIVATE"></span><script>BX.hint_replace(BX('hint_ELEMENT_LOADING_ACTIVATE'), '<?php echo GetMessage("KDA_IE_ELEMENT_LOADING_ACTIVATE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_LOADING_ACTIVATE]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_LOADING_ACTIVATE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_NOT_UPDATE_WO_CHANGES"); ?>: <span id="hint_ELEMENT_NOT_UPDATE_WO_CHANGES"></span><script>BX.hint_replace(BX('hint_ELEMENT_NOT_UPDATE_WO_CHANGES'), '<?php echo GetMessage("KDA_IE_ELEMENT_NOT_UPDATE_WO_CHANGES_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_UPDATE_WO_CHANGES]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_NOT_UPDATE_WO_CHANGES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_ADD_NEW_SECTIONS"); ?>: <span id="hint_ELEMENT_ADD_NEW_SECTIONS"></span><script>BX.hint_replace(BX('hint_ELEMENT_ADD_NEW_SECTIONS'), '<?php echo GetMessage("KDA_IE_ELEMENT_ADD_NEW_SECTIONS_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_ADD_NEW_SECTIONS]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_ADD_NEW_SECTIONS']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_NOT_CHANGE_SECTIONS"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_CHANGE_SECTIONS]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_NOT_CHANGE_SECTIONS']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_NOT_LOAD_ELEMENTS_WO_SECTION"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[NOT_LOAD_ELEMENTS_WO_SECTION]" value="Y" <?php if($SETTINGS_DEFAULT['NOT_LOAD_ELEMENTS_WO_SECTION']=='Y'){echo 'checked';}?>>
			</td>
		</tr>

		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_MULTIPLE_SEPARATOR"); ?>:</td>
			<td>
				<input type="text" name="SETTINGS_DEFAULT[ELEMENT_MULTIPLE_SEPARATOR]" size="3" value="<?php echo ($SETTINGS_DEFAULT['ELEMENT_MULTIPLE_SEPARATOR'] ? htmlspecialcharsbx($SETTINGS_DEFAULT['ELEMENT_MULTIPLE_SEPARATOR']) : ';'); ?>">
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?php echo GetMessage("KDA_IE_SETTINGS_PROCESSING_MISSING_ELEMENTS"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show"><?php echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_MISSING_DEACTIVATE"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[CELEMENT_MISSING_DEACTIVATE]" value="Y" <?php if($SETTINGS_DEFAULT['CELEMENT_MISSING_DEACTIVATE']=='Y' || $SETTINGS_DEFAULT['ELEMENT_MISSING_DEACTIVATE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>

        <?php if($bCatalog){?>
			<tr>
				<td><?php echo GetMessage("KDA_IE_ELEMENT_MISSING_TO_ZERO"); ?>:</td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[CELEMENT_MISSING_TO_ZERO]" value="Y" <?php if($SETTINGS_DEFAULT['CELEMENT_MISSING_TO_ZERO']=='Y' || $SETTINGS_DEFAULT['ELEMENT_MISSING_TO_ZERO']=='Y'){echo 'checked';}?>>
				</td>
			</tr>
			
			<tr>
				<td><?php echo GetMessage("KDA_IE_ELEMENT_MISSING_REMOVE_PRICE"); ?>:</td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[CELEMENT_MISSING_REMOVE_PRICE]" value="Y" <?php if($SETTINGS_DEFAULT['CELEMENT_MISSING_REMOVE_PRICE']=='Y' || $SETTINGS_DEFAULT['ELEMENT_MISSING_REMOVE_PRICE']=='Y'){echo 'checked';}?>>
				</td>
			</tr>
    <?php
    }?>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ELEMENT_MISSING_REMOVE_ELEMENT"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[CELEMENT_MISSING_REMOVE_ELEMENT]" value="Y" <?php if($SETTINGS_DEFAULT['CELEMENT_MISSING_REMOVE_ELEMENT']=='Y'){echo 'checked';}?>data-confirm="<?php echo GetMessage("KDA_IE_ELEMENT_MISSING_REMOVE_ELEMENT_CONFIRM"); ?>">
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_MISSING_ACTIONS_IN_SECTION"); ?>: <span id="hint_MISSING_ACTIONS_IN_SECTION"></span><script>BX.hint_replace(BX('hint_MISSING_ACTIONS_IN_SECTION'), '<?php echo GetMessage("KDA_IE_MISSING_ACTIONS_IN_SECTION_HINT"); ?>');</script></td>
			<td>
				<input type="hidden" name="SETTINGS_DEFAULT[MISSING_ACTIONS_IN_SECTION]" value="N">
				<input type="checkbox" name="SETTINGS_DEFAULT[MISSING_ACTIONS_IN_SECTION]" value="Y" <?php if($SETTINGS_DEFAULT['MISSING_ACTIONS_IN_SECTION']!='N'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" align="center">
				<input type="hidden" id="CELEMENT_MISSING_DEFAULTS" name="SETTINGS_DEFAULT[CELEMENT_MISSING_DEFAULTS]" value="<?php echo htmlspecialcharsbx($SETTINGS_DEFAULT['CELEMENT_MISSING_DEFAULTS']);?>">
				<a href="javascript:void(0)" onclick="EProfile.OpenMissignElementFields(this)" class="kda-ie-link2window"><?php echo GetMessage("KDA_IE_ELEMENT_MISSING_SET_FIELDS"); ?></a>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" align="center">
				<input type="hidden" id="CELEMENT_MISSING_FILTER" name="SETTINGS_DEFAULT[CELEMENT_MISSING_FILTER]" value="<?php echo htmlspecialcharsbx($SETTINGS_DEFAULT['CELEMENT_MISSING_FILTER']);?>">
				<a href="javascript:void(0)" onclick="EProfile.OpenMissignElementFilter(this)" class="kda-ie-link2window"><?php echo GetMessage("KDA_IE_ELEMENT_MISSING_SET_FILTER"); ?></a>
			</td>
		</tr>
		
		<tr>
			<td colspan="2" align="center">
                <?php
				echo BeginNote();
				echo sprintf(GetMessage("KDA_IE_ELEMENT_MISSING_NOTE"), ' href="javascript:void(0)" onclick="EProfile.OpenMissignElementFilter(this)"');
				echo EndNote();
				?>
			</td>
		</tr>
		
		<tr <?php if(!$OFFERS_IBLOCK_ID){echo 'style="display: none;"';}?> class="heading kda-sku-block">
			<td colspan="2"><?php echo GetMessage("KDA_IE_SETTINGS_PROCESSING_MISSING_OFFERS"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show"><?php echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr <?php if(!$OFFERS_IBLOCK_ID){echo 'style="display: none;"';}?> class="kda-sku-block">
			<td><?php echo GetMessage("KDA_IE_OFFER_MISSING_DEACTIVATE"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[OFFER_MISSING_DEACTIVATE]" value="Y" <?php if($SETTINGS_DEFAULT['OFFER_MISSING_DEACTIVATE']=='Y' || $SETTINGS_DEFAULT['ELEMENT_MISSING_DEACTIVATE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>

        <?php if($bCatalog){?>
			<tr <?php if(!$OFFERS_IBLOCK_ID){echo 'style="display: none;"';}?> class="kda-sku-block">
				<td><?php echo GetMessage("KDA_IE_OFFER_MISSING_TO_ZERO"); ?>:</td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[OFFER_MISSING_TO_ZERO]" value="Y" <?php if($SETTINGS_DEFAULT['OFFER_MISSING_TO_ZERO']=='Y' || $SETTINGS_DEFAULT['ELEMENT_MISSING_TO_ZERO']=='Y'){echo 'checked';}?>>
				</td>
			</tr>
			
			<tr <?php if(!$OFFERS_IBLOCK_ID){echo 'style="display: none;"';}?> class="kda-sku-block">
				<td><?php echo GetMessage("KDA_IE_OFFER_MISSING_REMOVE_PRICE"); ?>:</td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[OFFER_MISSING_REMOVE_PRICE]" value="Y" <?php if($SETTINGS_DEFAULT['OFFER_MISSING_REMOVE_PRICE']=='Y' || $SETTINGS_DEFAULT['ELEMENT_MISSING_REMOVE_PRICE']=='Y'){echo 'checked';}?>>
				</td>
			</tr>
    <?php
    }?>
		
		<tr <?php if(!$OFFERS_IBLOCK_ID){echo 'style="display: none;"';}?> class="kda-sku-block">
			<td><?php echo GetMessage("KDA_IE_OFFER_MISSING_REMOVE_ELEMENT"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[OFFER_MISSING_REMOVE_ELEMENT]" value="Y" <?php if($SETTINGS_DEFAULT['OFFER_MISSING_REMOVE_ELEMENT']=='Y'){echo 'checked';}?>data-confirm="<?php echo GetMessage("KDA_IE_OFFER_MISSING_REMOVE_ELEMENT_CONFIRM"); ?>">
			</td>
		</tr>
		
		<tr <?php if(!$OFFERS_IBLOCK_ID){echo 'style="display: none;"';}?> class="kda-sku-block">
			<td colspan="2" align="center">
				<input type="hidden" id="OFFER_MISSING_DEFAULTS" name="SETTINGS_DEFAULT[OFFER_MISSING_DEFAULTS]" value="<?php echo htmlspecialcharsbx($SETTINGS_DEFAULT['OFFER_MISSING_DEFAULTS']);?>">
				<a href="javascript:void(0)" onclick="EProfile.OpenMissignElementFields(this)" class="kda-ie-link2window"><?php echo GetMessage("KDA_IE_ELEMENT_MISSING_SET_FIELDS"); ?></a>
			</td>
		</tr>
		
		<tr <?php if(!$OFFERS_IBLOCK_ID){echo 'style="display: none;"';}?> class="kda-sku-block">
			<td colspan="2" align="center">
                <?php
				echo BeginNote();
				echo sprintf(GetMessage("KDA_IE_OFFER_MISSING_NOTE"), ' href="javascript:void(0)" onclick="EProfile.OpenMissignElementFilter(this)"');
				echo EndNote();
				?>
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?php echo GetMessage("KDA_IE_SETTINGS_PROCESSING_SECTIONS"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show"><?php echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_SECTION_UID"); ?>:</td>
			<td>
                <?php $fl->ShowSelectSectionUidFields($SETTINGS_DEFAULT['IBLOCK_ID'], 'SETTINGS_DEFAULT[SECTION_UID]', $SETTINGS_DEFAULT['SECTION_UID']);?>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ONLY_UPDATE_MODE_SECTION"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_UPDATE_MODE_SECTION]" value="Y" <?php if($SETTINGS_DEFAULT['ONLY_UPDATE_MODE']=='Y' || $SETTINGS_DEFAULT['ONLY_UPDATE_MODE_SECTION']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_ONLY_CREATE_MODE_SECTION"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_CREATE_MODE_SECTION]" value="Y" <?php if($SETTINGS_DEFAULT['ONLY_CREATE_MODE']=='Y' || $SETTINGS_DEFAULT['ONLY_CREATE_MODE_SECTION']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_SECTION_NOTEMPTY_ACTIVATE"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[SECTION_NOTEMPTY_ACTIVATE]" value="Y" <?php if($SETTINGS_DEFAULT['SECTION_NOTEMPTY_ACTIVATE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_SECTION_EMPTY_DEACTIVATE"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[SECTION_EMPTY_DEACTIVATE]" value="Y" <?php if($SETTINGS_DEFAULT['SECTION_EMPTY_DEACTIVATE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_SECTION_EMPTY_REMOVE"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[SECTION_EMPTY_REMOVE]" value="Y" <?php if($SETTINGS_DEFAULT['SECTION_EMPTY_REMOVE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_MAX_SECTION_LEVEL"); ?>:  <span id="hint_MAX_SECTION_LEVEL"></span><script>BX.hint_replace(BX('hint_MAX_SECTION_LEVEL'), '<?php echo GetMessage("KDA_IE_MAX_SECTION_LEVEL_HINT"); ?>');</script></td>
			<td>
				<input type="text" name="SETTINGS_DEFAULT[MAX_SECTION_LEVEL]" size="3" value="<?php echo (strlen($SETTINGS_DEFAULT['MAX_SECTION_LEVEL']) > 0 ? htmlspecialcharsbx($SETTINGS_DEFAULT['MAX_SECTION_LEVEL']) : '5'); ?>" maxlength="3">
			</td>
		</tr>


        <?php if($bCatalog){?>
			<tr class="heading">
				<td colspan="2"><?php echo GetMessage("KDA_IE_SETTINGS_CATALOG"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show"><?php echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
			</tr>

        <?php if($bCurrency){?>
			<tr>
				<td><?php echo GetMessage("KDA_IE_DEFAULT_CURRENCY"); ?>:</td>
				<td>
					<select name="SETTINGS_DEFAULT[DEFAULT_CURRENCY]">
                        <?php
					$lcur = CCurrency::GetList(($by="sort"), ($order1="asc"), LANGUAGE_ID);
					while($arr = $lcur->Fetch())
					{
						?><option value="<?php echo $arr['CURRENCY']?>" <?php if($arr['CURRENCY']==$SETTINGS_DEFAULT['DEFAULT_CURRENCY'] || (!$SETTINGS_DEFAULT['DEFAULT_CURRENCY'] && $arr['BASE']=='Y')){echo 'selected';}?>>[<?php echo $arr['CURRENCY']?>] <?php echo $arr['FULL_NAME']?></option><?php
					}
					?>
					</select>
				</td>
			</tr>
        <?php
        }?>
			
			<tr>
				<td><?php echo GetMessage("KDA_IE_QUANTITY_TRACE"); ?>:</td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[QUANTITY_TRACE]" value="Y" <?php if($SETTINGS_DEFAULT['QUANTITY_TRACE']=='Y'){echo 'checked';}?>>
				</td>
			</tr>
			
			<tr>
				<td><?php echo GetMessage("KDA_IE_QUANTITY_AS_SUM_STORE"); ?>:</td>
				<td>
					<table cellspacing="0"><tr>
					<td style="padding-left: 0px;"><input type="checkbox" name="SETTINGS_DEFAULT[QUANTITY_AS_SUM_STORE]" value="Y" <?php if($SETTINGS_DEFAULT['QUANTITY_AS_SUM_STORE']=='Y'){echo 'checked';}?>onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[QUANTITY_AS_SUM_PROPERTIES]', 'SETTINGS_DEFAULT[CALCULATE_PRICE]']); if(this.checked){$('#quantity_sum_stores').show();}else{$('#quantity_sum_stores').hide();}"></td>
					<td>&nbsp; &nbsp;</td>
					<td id="quantity_sum_stores"<?php if($SETTINGS_DEFAULT['QUANTITY_AS_SUM_STORE']!='Y'){echo ' style="display: none;"';}?>>
                        <?php $fl->ShowSelectStoreListForSum('SETTINGS_DEFAULT[ELEMENT_STORES_FOR_QUANTITY][]', $SETTINGS_DEFAULT['ELEMENT_STORES_FOR_QUANTITY']);?>
					</td>
					</tr></table>
				</td>
			</tr>
			
			<tr>
				<td><?php echo GetMessage("KDA_IE_QUANTITY_AS_SUM_PROPERTIES"); ?>:</td>
				<td>
					<table cellspacing="0"><tr>
					<td style="padding-left: 0px;"><input type="checkbox" name="SETTINGS_DEFAULT[QUANTITY_AS_SUM_PROPERTIES]" value="Y" <?php if($SETTINGS_DEFAULT['QUANTITY_AS_SUM_PROPERTIES']=='Y'){echo 'checked';}?>onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[QUANTITY_AS_SUM_STORE]', 'SETTINGS_DEFAULT[CALCULATE_PRICE]']); if(this.checked){$('#quantity_sum_props').show();}else{$('#quantity_sum_props').hide();}"></td>
					<td>&nbsp; &nbsp;</td>
					<td id="quantity_sum_props"<?php if($SETTINGS_DEFAULT['QUANTITY_AS_SUM_PROPERTIES']!='Y'){echo ' style="display: none;"';}?>>
						<div id="properties_for_sum"><?php $fl->ShowSelectPropertyListForSum($SETTINGS_DEFAULT['IBLOCK_ID'], 'SETTINGS_DEFAULT[ELEMENT_PROPERTIES_FOR_QUANTITY][]', $SETTINGS_DEFAULT['ELEMENT_PROPERTIES_FOR_QUANTITY']);?></div>
						<div id="properties_for_sum_sku"><?php $fl->ShowSelectPropertyListForSum($OFFERS_IBLOCK_ID, 'SETTINGS_DEFAULT[OFFER_PROPERTIES_FOR_QUANTITY][]', $SETTINGS_DEFAULT['OFFER_PROPERTIES_FOR_QUANTITY'], true);?></div>
					</td>
					</tr></table>
				</td>
			</tr>
			
			<tr>
				<td><?php echo GetMessage("KDA_IE_CALCULATE_PRICE"); ?>:</td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[CALCULATE_PRICE]" value="Y" <?php if($SETTINGS_DEFAULT['CALCULATE_PRICE']=='Y'){echo 'checked';}?>onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[QUANTITY_AS_SUM_STORE]', 'SETTINGS_DEFAULT[QUANTITY_AS_SUM_PROPERTIES]'])">
					&nbsp;
					(<a href="javascript:void(0)" onclick="EProfile.OpenCalcPriceForm(this)" class="kda-ie-link2window"><?php echo GetMessage("KDA_IE_CALCULATE_PRICE_WINDOW"); ?></a>)
				</td>
			</tr>
			
			<tr>
				<td><?php echo GetMessage("KDA_IE_REMOVE_EXPIRED_DISCOUNT"); ?>:</td>
				<td>
					<input type="checkbox" name="SETTINGS_DEFAULT[REMOVE_EXPIRED_DISCOUNT]" value="Y" <?php if($SETTINGS_DEFAULT['REMOVE_EXPIRED_DISCOUNT']=='Y'){echo 'checked';}?>>
				</td>
			</tr>
    <?php
    }?>
		
		<tr class="heading">
			<td colspan="2"><?php echo GetMessage("KDA_IE_SETTINGS_STATISTIC"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show" id="kda-head-more-link"><?php echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_STAT_SAVE"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[STAT_SAVE]" value="Y" <?php if($SETTINGS_DEFAULT['STAT_SAVE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>

        <?php $removeOldStat = (bool)($SETTINGS_DEFAULT['STAT_DELETE_OLD']=='Y');?>
		<tr>
			<td><?php echo GetMessage("KDA_IE_STAT_DELETE_OLD"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[STAT_DELETE_OLD]" value="Y" <?php if($removeOldStat){echo 'checked';}?>onchange="/*EList.ToggleAddSettings(this)*/">
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_STAT_SAVE_LAST_N"); ?>:</td>
			<td>
				<input type="text" name="SETTINGS_DEFAULT[STAT_SAVE_LAST_N]" value="<?php echo max(1, (int)$SETTINGS_DEFAULT['STAT_SAVE_LAST_N'])?>" size="5">
			</td>
		</tr>
		
		<tr>
			<td colspan="2" align="center">
                <?php
				echo BeginNote();
				echo sprintf(GetMessage("KDA_IE_STAT_NOTE"), '/bitrix/admin/'.$moduleFilePrefix.'_profile_list.php?lang='.LANGUAGE_ID);
				echo EndNote();
				?>
			</td>
		</tr>
		
		
		<tr class="heading">
			<td colspan="2"><?php echo GetMessage("KDA_IE_SETTINGS_FILE_READING"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show" id="kda-head-more-link"><?php echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_COPY_FILE_TO_PATH"); ?>:</td>
			<td>
				<table cellspacing="0"><tr>
				<td style="padding-left: 0px;"><input type="checkbox" name="SETTINGS_DEFAULT[COPY_FILE_TO_PATH]" value="Y" <?php if($SETTINGS_DEFAULT['COPY_FILE_TO_PATH']=='Y'){echo 'checked';}?>onchange="if(this.checked){$('#copy_file_path').show();}else{$('#copy_file_path').hide();}"></td>
				<td>&nbsp; &nbsp;</td>
				<td id="copy_file_path"<?php if($SETTINGS_DEFAULT['COPY_FILE_TO_PATH']!='Y'){echo ' style="display: none;"';}?>>
					<input type="text" name="SETTINGS_DEFAULT[COPY_FILE_PATH]" value="<?php echo htmlspecialcharsex($SETTINGS_DEFAULT['COPY_FILE_PATH'])?>" placeholder="/upload/file.xlsx" size="50">
				</td>
				</tr></table>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_OPTIMIZE_RAM"); ?>: <span id="hint_OPTIMIZE_RAM"></span><script>BX.hint_replace(BX('hint_OPTIMIZE_RAM'), '<?php echo GetMessage("KDA_IE_OPTIMIZE_RAM_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[OPTIMIZE_RAM]" value="Y" <?php if($SETTINGS_DEFAULT['OPTIMIZE_RAM']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_LOAD_IMAGES"); ?>: <span id="hint_ELEMENT_LOAD_IMAGES"></span><script>BX.hint_replace(BX('hint_ELEMENT_LOAD_IMAGES'), '<?php echo GetMessage("KDA_IE_LOAD_IMAGES_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_LOAD_IMAGES]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_LOAD_IMAGES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_NOT_LOAD_STYLES"); ?>: <span id="hint_ELEMENT_NOT_LOAD_STYLES"></span><script>BX.hint_replace(BX('hint_ELEMENT_NOT_LOAD_STYLES'), '<?php echo GetMessage("KDA_IE_NOT_LOAD_STYLES_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_LOAD_STYLES]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_NOT_LOAD_STYLES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_NOT_LOAD_FORMATTING"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_LOAD_FORMATTING]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_NOT_LOAD_FORMATTING']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_COUNT_LINES_FOR_PREVIEW"); ?>:</td>
			<td>
				<input type="text" name="SETTINGS_DEFAULT[COUNT_LINES_FOR_PREVIEW]" value="<?php echo htmlspecialcharsex($SETTINGS_DEFAULT['COUNT_LINES_FOR_PREVIEW'])?>" placeholder="10">
			</td>
		</tr>
		
		
		<tr class="heading">
			<td colspan="2"><?php echo GetMessage("KDA_IE_SETTINGS_ADDITONAL"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show" id="kda-head-more-link"><?php echo GetMessage("KDA_IE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_REMOVE_COMPOSITE_CACHE"); ?>: <span id="hint_REMOVE_COMPOSITE_CACHE"></span><script>BX.hint_replace(BX('hint_REMOVE_COMPOSITE_CACHE'), '<?php echo GetMessage("KDA_IE_REMOVE_COMPOSITE_CACHE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[REMOVE_COMPOSITE_CACHE]" value="Y" <?php if($SETTINGS_DEFAULT['REMOVE_COMPOSITE_CACHE']=='Y'){echo 'checked';}?>onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[REMOVE_COMPOSITE_CACHE_PART]'])">
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_REMOVE_COMPOSITE_CACHE_PART"); ?>: <span id="hint_REMOVE_COMPOSITE_CACHE_PART"></span><script>BX.hint_replace(BX('hint_REMOVE_COMPOSITE_CACHE_PART'), '<?php echo GetMessage("KDA_IE_REMOVE_COMPOSITE_CACHE_PART_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[REMOVE_COMPOSITE_CACHE_PART]" value="Y" <?php if($SETTINGS_DEFAULT['REMOVE_COMPOSITE_CACHE_PART']=='Y'){echo 'checked';}?>onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[REMOVE_COMPOSITE_CACHE]'])">
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_REMOVE_CACHE_AFTER_IMPORT"); ?>: <span id="hint_REMOVE_CACHE_AFTER_IMPORT"></span><script>BX.hint_replace(BX('hint_REMOVE_CACHE_AFTER_IMPORT'), '<?php echo GetMessage("KDA_IE_REMOVE_CACHE_AFTER_IMPORT_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[REMOVE_CACHE_AFTER_IMPORT]" value="Y" <?php if($SETTINGS_DEFAULT['REMOVE_CACHE_AFTER_IMPORT']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_IMAGES_FORCE_UPDATE"); ?>: <span id="hint_ELEMENT_IMAGES_FORCE_UPDATE"></span><script>BX.hint_replace(BX('hint_ELEMENT_IMAGES_FORCE_UPDATE'), '<?php echo GetMessage("KDA_IE_IMAGES_FORCE_UPDATE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_IMAGES_FORCE_UPDATE]" value="Y" <?php if($SETTINGS_DEFAULT['ELEMENT_IMAGES_FORCE_UPDATE']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_AUTO_CREATION_PROPERTIES"); ?>: <span id="hint_AUTO_CREATION_PROPERTIES"></span><script>BX.hint_replace(BX('hint_AUTO_CREATION_PROPERTIES'), '<?php echo GetMessage("KDA_IE_AUTO_CREATION_PROPERTIES_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[AUTO_CREATION_PROPERTIES]" value="Y" <?php if($SETTINGS_DEFAULT['AUTO_CREATION_PROPERTIES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?php echo GetMessage("KDA_IE_PROPERTIES_REMOVE"); ?>: <span id="hint_ELEMENT_PROPERTIES_REMOVE"></span><script>BX.hint_replace(BX('hint_ELEMENT_PROPERTIES_REMOVE'), '<?php echo GetMessage("KDA_IE_PROPERTIES_REMOVE_HINT"); ?>');</script></td>
			<td>
                <?php $fl->ShowSelectPropertyList($SETTINGS_DEFAULT['IBLOCK_ID'], 'SETTINGS_DEFAULT[ELEMENT_PROPERTIES_REMOVE][]', $SETTINGS_DEFAULT['ELEMENT_PROPERTIES_REMOVE']);?>
			</td>
		</tr>

        <?php /*?><tr>
			<td><?echo GetMessage("KDA_IE_ELEM_API_OPTIMIZE"); ?>: <span id="hint_ELEM_API_OPTIMIZE"></span><script>BX.hint_replace(BX('hint_ELEM_API_OPTIMIZE'), '<?echo GetMessage("KDA_IE_ELEM_API_OPTIMIZE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEM_API_OPTIMIZE]" value="Y" <?if($SETTINGS_DEFAULT['ELEM_API_OPTIMIZE']=='Y'){echo 'checked';}?>>
			</td>
		</tr><?*/ ?>

        <?php if($OFFERS_IBLOCK_ID){?>
			<tr>
				<td valign="top"><?php echo GetMessage("KDA_IE_SEARCH_OFFERS_WO_PRODUCTS"); ?>: <span id="hint_SEARCH_OFFERS_WO_PRODUCTS"></span><script>BX.hint_replace(BX('hint_SEARCH_OFFERS_WO_PRODUCTS'), '<?php echo GetMessage("KDA_IE_SEARCH_OFFERS_WO_PRODUCTS_HINT"); ?>');</script></td>
				<td valign="top">
					<input type="checkbox" name="SETTINGS_DEFAULT[SEARCH_OFFERS_WO_PRODUCTS]" value="Y" <?php if($SETTINGS_DEFAULT['SEARCH_OFFERS_WO_PRODUCTS']=='Y'){echo 'checked';}?>onchange="if(this.checked){$('#create_new_offers_wrap').show();}else{$('#create_new_offers_wrap').hide();}">
					<div id="create_new_offers_wrap" style="margin-top: 7px;<?php if($SETTINGS_DEFAULT['SEARCH_OFFERS_WO_PRODUCTS']!='Y'){echo 'display: none;';}?>">
						<input type="checkbox" name="SETTINGS_DEFAULT[CREATE_NEW_OFFERS]" value="Y" <?php if($SETTINGS_DEFAULT['CREATE_NEW_OFFERS']=='Y'){echo 'checked';}?>id="create_new_offers_chb">
						<label for="create_new_offers_chb"><?php echo GetMessage("KDA_IE_CREATE_NEW_OFFERS"); ?></label>
					</div>
				</td>
			</tr>
    <?php
    }?>
		
		<tr>
			<td class="kda-ie-settings-margin-container" colspan="2" align="center">
				<a href="javascript:void(0)" onclick="ESettings.ShowPHPExpression(this)"><?php echo GetMessage("KDA_IE_ONAFTERSAVE_HANDLER");?></a>
				<div class="kda-ie-settings-phpexpression" style="display: none;">
                    <?php echo GetMessage("KDA_IE_ONAFTERSAVE_HANDLER_HINT");?>
					<textarea name="SETTINGS_DEFAULT[ONAFTERSAVE_HANDLER]"><?php echo $SETTINGS_DEFAULT['ONAFTERSAVE_HANDLER']?></textarea>
				</div>
			</td>
		</tr>

        <?php
	}
}
$tabControl->EndTab();
?>

    <?php $tabControl->BeginNextTab();
if ($STEP == 2)
{
?>
	
	<tr>
		<td colspan="2" id="preview_file">
			<div class="kda-ie-file-preloader">
                <?php echo GetMessage("KDA_IE_PRELOADING"); ?>
			</div>
		</td>
	</tr>

    <?php
}
$tabControl->EndTab();
?>


    <?php $tabControl->BeginNextTab();
if ($STEP == 3)
{
?>
	<tr>
		<td id="resblock" class="kda-ie-result">
		 <table width="100%"><tr><td width="50%">
			<div id="progressbar"><span class="pline"></span><span class="presult load"><b>0%</b><span 
				data-prefix="<?php echo GetMessage("KDA_IE_READ_LINES"); ?>"
				data-import="<?php echo GetMessage("KDA_IE_STATUS_IMPORT"); ?>"
				data-deactivate_elements="<?php echo GetMessage("KDA_IE_STATUS_DEACTIVATE_ELEMENTS"); ?>"
				data-deactivate_sections="<?php echo GetMessage("KDA_IE_STATUS_DEACTIVATE_SECTIONS"); ?>"
			><?php echo GetMessage("KDA_IE_IMPORT_INIT"); ?></span></span></div>

			<div id="block_error_import" style="display: none;">
                <?php echo CAdminMessage::ShowMessage(array(
					"TYPE" => "ERROR",
					"MESSAGE" => GetMessage("KDA_IE_IMPORT_ERROR_CONNECT"),
					"DETAILS" => '<div>'.(COption::GetOptionString($moduleId, 'AUTO_CONTINUE_IMPORT', 'N')=='Y' ? sprintf(GetMessage("KDA_IE_IMPORT_AUTO_CONTINUE"), '<span id="kda_ie_auto_continue_time"></span>').'<br>' : '').'<a href="javascript:void(0)" onclick="EProfile.ContinueProccess(this, '.$PROFILE_ID.');" id="kda_ie_continue_link">'.GetMessage("KDA_IE_PROCESSED_CONTINUE").'</a><br><br>'.sprintf(GetMessage("KDA_IE_IMPORT_ERROR_CONNECT_COMMENT"), '/bitrix/admin/settings.php?lang=ru&mid='.$moduleId.'&mid_menu=1').'</div>',
					"HTML" => true,
				))?>
			</div>
			
			<div id="block_error" style="display: none;">
                <?php echo CAdminMessage::ShowMessage(array(
					"TYPE" => "ERROR",
					"MESSAGE" => GetMessage("KDA_IE_IMPORT_ERROR"),
					"DETAILS" => '<div id="res_error"></div>',
					"HTML" => true,
				))?>
			</div>
		 </td><td>
			<div class="detail_status" id="kda_ie_result_wrap">
                <?php echo CAdminMessage::ShowMessage(array(
					"TYPE" => "PROGRESS",
					"MESSAGE" => '<!--<div id="res_continue">'.GetMessage("KDA_IE_AUTO_REFRESH_CONTINUE").'</div><div id="res_finish" style="display: none;">'.GetMessage("KDA_IE_SUCCESS").'</div>-->',
					"DETAILS" =>
					'<div class="kda-ie-result-block">'
						.'<span>'.GetMessage("KDA_IE_SU_ALL").' <b id="total_line">0</b></span>'
						.'<span>'.GetMessage("KDA_IE_SU_CORR").' <b id="correct_line">0</b></span>'
						.'<span>'.GetMessage("KDA_IE_SU_ER").' <b id="error_line">0</b></span>'
					.'</div>'
					.'<div class="kda-ie-result-block">'
						.'<span class="kda-ie-result-item-green">'.GetMessage("KDA_IE_SU_ELEMENT_ADDED").' <b id="element_added_line">0</b></span>'
						.'<span>'.GetMessage("KDA_IE_SU_ELEMENT_UPDATED").' <b id="element_updated_line">0</b></span>'
						.'<span>'.GetMessage("KDA_IE_SU_ELEMENT_CHANGED").' <b id="element_changed_line">0</b></span>'
						.($SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y' ? ('<span class="kda-ie-result-item-red">'.GetMessage("KDA_IE_SU_ELEMENT_DELETED").' <b id="element_removed_line">0</b></span>') : '')
						.($SETTINGS_DEFAULT['CELEMENT_MISSING_DEACTIVATE']=='Y' ? ('<span class="kda-ie-result-item-red">'.GetMessage("KDA_IE_SU_HIDED").' <b id="killed_line">0</b></span>') : '')
						.($SETTINGS_DEFAULT['CELEMENT_MISSING_TO_ZERO']=='Y' ? ('<span>'.GetMessage("KDA_IE_SU_ZERO_STOCK").' <b id="zero_stock_line">0</b></span>') : '')
						.($SETTINGS_DEFAULT['CELEMENT_MISSING_REMOVE_ELEMENT']=='Y' ? ('<span class="kda-ie-result-item-red">'.GetMessage("KDA_IE_SU_REMOVE_ELEMENT").' <b id="old_removed_line">0</b></span>') : '')
					.'</div>'
					.'<div class="kda-ie-result-block">'
						.(!empty($SETTINGS_DEFAULT['ELEMENT_UID_SKU']) ? ('<span class="kda-ie-result-item-green">'.GetMessage("KDA_IE_SU_SKU_ADDED").' <b id="sku_added_line">0</b></span>') : '')
						.(!empty($SETTINGS_DEFAULT['ELEMENT_UID_SKU']) ? ('<span>'.GetMessage("KDA_IE_SU_SKU_UPDATED").' <b id="sku_updated_line">0</b></span>') : '')
						.(!empty($SETTINGS_DEFAULT['ELEMENT_UID_SKU']) ? ('<span>'.GetMessage("KDA_IE_SU_SKU_CHANGED").' <b id="sku_changed_line">0</b></span>') : '')
						.($SETTINGS_DEFAULT['OFFER_MISSING_DEACTIVATE']=='Y' ? ('<span class="kda-ie-result-item-red">'.GetMessage("KDA_IE_SU_OFFER_HIDED").' <b id="offer_killed_line">0</b></span>') : '')
						.($SETTINGS_DEFAULT['OFFER_MISSING_TO_ZERO']=='Y' ? ('<span>'.GetMessage("KDA_IE_SU_OFFER_ZERO_STOCK").' <b id="offer_zero_stock_line">0</b></span>') : '')
						.($SETTINGS_DEFAULT['OFFER_MISSING_REMOVE_ELEMENT']=='Y' ? ('<span class="kda-ie-result-item-red">'.GetMessage("KDA_IE_SU_OFFER_REMOVE_ELEMENT").' <b id="offer_old_removed_line">0</b></span>') : '')
					.'</div>'
					.'<div class="kda-ie-result-block">'
						.'<span class="kda-ie-result-item-green">'.GetMessage("KDA_IE_SU_SECTION_ADDED").' <b id="section_added_line">0</b></span>'
						.'<span>'.GetMessage("KDA_IE_SU_SECTION_UPDATED").' <b id="section_updated_line">0</b></span>'
						.($SETTINGS_DEFAULT['SECTION_EMPTY_DEACTIVATE']=='Y' ? ('<span class="kda-ie-result-item-red">'.GetMessage("KDA_IE_SU_SECTION_EMPTY_DEACTIVATE").' <b id="section_deactivate_line">0</b></span>') : '')
						.($SETTINGS_DEFAULT['SECTION_EMPTY_REMOVE']=='Y' ? ('<span class="kda-ie-result-item-red">'.GetMessage("KDA_IE_SU_SECTION_EMPTY_REMOVE").' <b id="section_remove_line">0</b></span>') : '')
					.'</div>'
					.'<div>'.GetMessage("KDA_IE_EXECUTION_TIME").' <b id="execution_time"></b></div>'
					.($SETTINGS_DEFAULT['STAT_SAVE']=='Y' ? ('<b><a target="_blank" href="/bitrix/admin/'.$moduleFilePrefix.'_event_log.php?lang='.LANGUAGE_ID.'&find_profile_id='.($PROFILE_ID + 1).'&find_exec_id=" id="kda_ie_stat_profile_link">'.GetMessage("KDA_IE_STATISTIC_LINK").'</a></b>') : '')
					.'<div id="redirect_message">'.GetMessage("KDA_IE_REDIRECT_MESSAGE").'</div>',
					"HTML" => true,
				))?>
			</div>
		 </td></tr></table>
		</td>
	</tr>
    <?php
}
$tabControl->EndTab();
?>

    <?php $tabControl->Buttons();
?>


    <?php echo bitrix_sessid_post(); ?>
    <?php
if($STEP > 1)
{
	if(strlen($PROFILE_ID) > 0)
	{
		?><input type="hidden" name="PROFILE_ID" value="<?php echo htmlspecialcharsbx($PROFILE_ID) ?>"><?php
	}
	else
	{
		foreach($SETTINGS_DEFAULT as $k=>$v)
		{
			?><input type="hidden" name="SETTINGS_DEFAULT[<?php echo $k?>]"value="<?php echo htmlspecialcharsbx($v) ?>"><?php
		}
	}
}
?>


    <?php
if($STEP == 2){ ?>
<input type="submit" name="backButton" value="&lt;&lt; <?php echo GetMessage("KDA_IE_BACK"); ?>">
    <?php
}

if($STEP == 1 || $STEP == 2){ ?>
<input type="submit" name="saveConfigButton" value="<?php echo GetMessage("KDA_IE_SAVE_CONFIGURATION"); ?>" style="float: right;">
    <?php
}

if($STEP < 3)
{
?>
	<input type="hidden" name="STEP" value="<?php echo $STEP + 1; ?>">
	<input type="submit" value="<?php echo ($STEP == 2) ? GetMessage("KDA_IE_NEXT_STEP_F") : GetMessage("KDA_IE_NEXT_STEP"); ?> &gt;&gt;" name="submit_btn" class="adm-btn-save">
    <?php
}
else
{
?>
	<input type="hidden" name="STEP" value="1">
	<input type="submit" name="backButton2" value="&lt;&lt; <?php echo GetMessage("KDA_IE_2_1_STEP"); ?>" class="adm-btn-save">
    <?php
}
?>

    <?php $tabControl->End();
?>

</form>

<script language="JavaScript">
    <?php if ($STEP < 2):
	$arFile = CKDAImportUtils::GetShowFileBySettings($SETTINGS_DEFAULT);
	if($arFile['link'])
	{
		?>
		$('#bx_file_data_file_cont .adm-input-file-name').attr('target', '_blank').attr('href', '<?php echo htmlspecialcharsex($arFile['link'])?>');<?php
	}
	if($arFile['path'])
	{
		?>
		$('#bx_file_data_file_cont .adm-input-file-name').text('<?php echo $arFile['path']?>');<?php
	}
?>
tabControl.SelectTab("edit1");
tabControl.DisableTab("edit2");
tabControl.DisableTab("edit3");
    <?php elseif ($STEP == 2):
	$fl = new CKDAFieldList($SETTINGS_DEFAULT);
	$arMenu = $fl->GetLineActions();
?>
tabControl.SelectTab("edit2");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit3");

var admKDAMessages = {};
admKDAMessages['lineActions'] = <?php echo CUtil::PhpToJSObject($arMenu);?>;
    <?php elseif ($STEP > 2): ?>
tabControl.SelectTab("edit3");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit2");

    <?php
$arPost = $_POST;
unset($arPost['EXTRASETTINGS']);
if(COption::GetOptionString($moduleId, 'SET_MAX_EXECUTION_TIME')=='Y')
{
	$delay = (int)COption::GetOptionString($moduleId, 'EXECUTION_DELAY');
	$stepsTime = (int)COption::GetOptionString($moduleId, 'MAX_EXECUTION_TIME');
	if($delay > 0) $arPost['STEPS_DELAY'] = $delay;
	if($stepsTime > 0) $arPost['STEPS_TIME'] = $stepsTime;
}
else
{
	$stepsTime = intval(ini_get('max_execution_time'));
	if($stepsTime > 0) $arPost['STEPS_TIME'] = $stepsTime;
}

if ($_POST['PROCESS_CONTINUE']=='Y'){
	$oProfile = new CKDAImportProfile();
?>
	EImport.Init(<?=CUtil::PhpToJSObject($arPost);?>, <?=CUtil::PhpToJSObject($oProfile->GetProccessParams($_POST['PROFILE_ID']));?>);
    <?php } else{?>
	EImport.Init(<?=CUtil::PhpToJSObject($arPost);?>);
    <?php } ?>
    <?php endif; ?>
//-->
</script>

<?php
require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
?>
