<?php
require_once 'common.php';
require_once 'config.php';

# $menuEntries is an array of menu entries for this plugin
# text - menu entry text
# type - status/content/output/help (main menu area)
# page - PHP file to display for this menu item
# wrap - 0/1 to hide or display the FPP header, menu, and footer on this page.
#        This does not apply for pages which begin with http:// or https://

# Comment out sub arrays to not display an item under a menu or add your own
# entries to display additional menu items such as the 3 items under the
# help menu in this example template.

$menuEntries = Array(
	Array(
		'text' => 'D24 Controller - Status',
		'type' => 'status',
		'page' => 'status.php',
		'wrap' => 1,
		'controllerModel' => 'DynamicPixels-D24'
	),
#	Array(
#		'text' => 'Template - Content Setup',
#		'type' => 'content',
#		'page' => 'content.php',
#		'wrap' => 1,
#               'controllerModel' => 'DynamicPixels-D24'
#	),
	Array(
		'text' => 'DynamicPixels Controller - Config',
		'type' => 'output',
		'page' => 'DynPixconfig.php',
		'wrap' => 1,
                'controllerModel' => 'DynamicPixels-D24'
	),
	Array(
		'text' => 'D24 Controller - Help',
		'type' => 'help',
		'page' => 'help/d24-help.php',
		'wrap' => 1,
		'controllerModel' => 'DynamicPixels-D24'
	),
	Array(
		'text' => 'D12 Controller - Help',
		'type' => 'help',
		'page' => 'help/d12-help.php',
		'wrap' => 1,
		'controllerModel' => 'DynamicPixels-D12'
	),
	Array(
		'text' => 'D0/24 Controller - Help',
		'type' => 'help',
		'page' => 'help/d0-24-help.php',
		'wrap' => 1,
		'controllerModel' => 'DynamicPixels-D12'
	),
	Array(
		'text' => 'DynamicPixels Plugin - About',
		'type' => 'help',
		'page' => 'about.php',
		'wrap' => 1,
                'controllerModel' => 'all'
	)
);

##############################################################################
# Display the menu entries for this plugin.
#
# It is expected that two variables are alread set:
# $plugin - contains the name of the current plugin directory/repoName
# $menu - contains the name of the menu section/type
foreach ($menuEntries as $entry)
{
	if ($entry['type'] != $menu)
		continue;

        $currentCapeInfo = json_decode(file_get_contents("http://localhost/api/cape"), true);

        if ( !in_array($entry['controllerModel'], array($currentCapeInfo['name'] ,'all'), true ) )
		continue;

	if (preg_match('/^http.?:\/\//', $entry['page']))
	{
		printf("<li><a href='%s' target='_blank'>%s</a></li>\n",
			$entry['page'], $entry['text']);
	}
	else
	{
		$nopage = '';
		if (isset($entry['wrap']) && ($entry['wrap'] == 0))
			$nopage = '&nopage=1';

		printf("<li><a href='plugin.php?plugin=%s&page=%s%s'>%s</a></li>\n",
			$plugin, $entry['page'], $nopage, $entry['text']);
	}
}
?>
