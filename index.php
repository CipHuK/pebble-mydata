<?php

# Include settings (Please use this file to setup script)
include ('settings.php');

###############################################################

# Get variables from url or settings

$content = array(
    'refresh' => !empty($_GET['refresh']) ? (int)$_GET['refresh'] : $setting['refresh'],
    'font' => !empty($_GET['font']) ? (int)$_GET['font'] : $setting['font'],
    'theme' => !empty($_GET['theme']) ? $_GET['theme'] : $setting['theme'],
    'scroll' => !empty($_GET['scroll']) ? $_GET['scroll'] : $setting['scroll'],
    'light' => !empty($_GET['light']) ? $_GET['light'] : $setting['light'],
    'blink' => !empty($_GET['blink']) ? $_GET['blink'] : $setting['blink'],
    'vibrate' => !empty($_GET['vibrate']) ? $_GET['vibrate'] : $setting['vibrate'],
    'updown' => !empty($_GET['updown']) ? $_GET['updown'] : $setting['updown'],
    'auth' => !empty($_GET['auth']) ? $_GET['auth'] : $setting['auth'],
);

# Verify parameters from url

$select = !empty($_GET['select']) ? $_GET['select'] : 0;
$up = !empty($_GET['up']) ? $_GET['up'] : 0;
$down = !empty($_GET['down']) ? $_GET['down'] : 0;

# Set pebble auth

$pebble_auth = md5(md5($setting['password_auth']).$content['auth']);
	
# Check Security

$error = false;
if ($setting['check_security'] === true)
	if ($_SERVER['HTTP_PEBBLE_TOKEN'] != $setting['pebble_token'])
		if ($_SERVER['HTTP_PEBBLE_AUTH'] != $pebble_auth)
		    $error = true;

# Set content to display with buttons conditions

if ($error == true) {
    $content = array_merge($content, setContentByCond($setting, 'error'));
} /*else if ($shake == 1) {           # When shake pebble
    $content = array_merge($content, setContentByCond($setting, 'shake'));
}*/ elseif ((int)$select === 1) {   # When button select short press
    $content = array_merge($content, setContentByCond($setting, 'select_short'));
} elseif ((int)$select === 2) {     # When button select long press
    $content = array_merge($content, setContentByCond($setting, 'select_long'));
} elseif ((int)$up === 1) {         # When button up short press
    $content = array_merge($content, setContentByCond($setting, 'up_short'));
} elseif ((int)$up === 2) {         # When button up long press
    $content = array_merge($content, setContentByCond($setting, 'up_long'));
} else if ((int)$down === 1) {      # When button down short press
    $content = array_merge($content, setContentByCond($setting, 'down_short'));
} else if ((int)$down === 2) {      # When button down long press
    $content = array_merge($content, setContentByCond($setting, 'down_short'));
} else {                            # Default display
	if ($setting['default']['type'] == 'text')
        $content['message'] = $setting['default']['content'];
	else if ($setting['default']['type'] == 'url')
        $content['message'] = read_content_from_url($setting['default']['content']);
}

# Do Not Disturb mode 

if (($setting['DoNotDisturb']['active'] == true)
    AND ($setting['DoNotDisturb']['start'] > $setting['DoNotDisturb']['stop'])
    AND ((date("H") >= $setting['DoNotDisturb']['start'])
        OR (date("H") < $setting['DoNotDisturb']['stop'])
    )
)
    $DoNotDisturb_mode = true;
else if (
    ($setting['DoNotDisturb']['active'] == true)
    AND ($setting['DoNotDisturb']['start'] < $setting['DoNotDisturb']['stop'])
    AND (date("H") >= $setting['DoNotDisturb']['start'])
    AND (date("H") < $setting['DoNotDisturb']['stop'])
)
    $DoNotDisturb_mode = true;
else
    $DoNotDisturb_mode = false;

if ($DoNotDisturb_mode == true) {
    $content['refresh'] = $setting['DoNotDisturb']['refresh'];
    $content['vibrate'] = $setting['DoNotDisturb']['vibrate'];
    $content['blink'] = $setting['DoNotDisturb']['blink'];
}

# Display of content in Json format
$content['auth'] = 'randomsalt';
$out = json_encode($content);
echo $out;

# Function to read json content from url if you need

function read_content_from_url($url) {
	$contents = file_get_contents($url); 
	$contents = utf8_encode($contents); 
	$results = json_decode($contents, true); 
    $message = (empty($results['content']))
        ? $contents
        : $results['content'];
    //$message = str_replace("\n",'\n',$message); //<-- commented because json_encode will replace
	return $message;
}

# Return array by condition key

function setContentByCond($setting, $condKey)
{
    $message = $setting[$condKey]['content'];
    if ($setting[$condKey]['type'] === 'url')
        $message = read_content_from_url($setting[$condKey]['content']);

    return array(
        'refresh' => (!empty($setting[$condKey]['refresh'])) ? $setting[$condKey]['refresh'] : '',
        'blink' => (!empty($setting[$condKey]['blink'])) ? $setting[$condKey]['blink'] : '',
        'vibrate' => (!empty($setting[$condKey]['vibrate'])) ? $setting[$condKey]['vibrate'] : '',
        'message' => $message
    );
}
