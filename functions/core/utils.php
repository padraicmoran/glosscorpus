<?php
// utilities: generic functions

// process form/query string inputs
function cleanInput($key) {
	if (isset($_GET[$key])) {
      return htmlspecialchars($_GET[$key], ENT_QUOTES, 'UTF-8');
   }
   elseif (isset($_POST[$key])) {
      return htmlspecialchars($_POST[$key], ENT_QUOTES, 'UTF-8');
   }
   else return null;
}

// writes option tag, setting selected=true if values match
function writeOption($label, $value, $testValue) {
   echo '<option value="' . $value . '"';
   if ($value == $testValue) echo ' selected="selected"';
   echo '>' . $label . '</option>';
}

// writes radio control, setting selected=true if values match
function writeRadio($name, $value, $label, $testValue) {
   echo '<input type="radio" name="' . $name . '" id="' . $name . '_' . $value . '" value="' . $value . '"';
   if ($value == $testValue) echo ' checked="checked"';
   echo '><label for="' . $name . '_' . $value . '">' . $label . '</label>';
}

// writes radio control, setting selected=true if values match
function writeCheckbox($name, $value, $label, $testValue) {
   echo '<input type="checkbox" name="' . $name . '" id="' . $name . '" value="' . $value . '"';
   if ($value == $testValue) echo ' checked="checked"';
   echo '><label for="' . $name . '">' . $label . '</label>';
}

function escSql($tmp) {
   if (! get_magic_quotes_gpc()) return addslashes($tmp);
   else return $tmp;
}

// strip a HTML tag (first and last, incl. attributes)
function stripTag($tag, $txt) {
	return preg_replace('/<\/?' . $tag . '[^<]*>/i', '', $txt);
}

// return sing. or pl. noun
function switchSgPl($val, $sg, $pl) {
	if ($val == 1) return $sg;
	else return $pl;
}

function fileLink($baseURL, $filePath, $linkText) {
   if (file_exists($filePath)) {
      $str = '<a href="' . $baseURL . $filePath . '">' . $linkText . '</a> ';
      $str .= '(' . number_format(round(filesize($filePath) / 1000)) . ' kB)';
      return $str;
   }
   else return '';
}

?>