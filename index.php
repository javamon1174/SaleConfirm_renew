<?php
/**
* @license http://opensource.org/licenses/MIT
*
* @version 1.2.1
*/
namespace SaleConfirm;

//develop mode
error_reporting(E_ALL);
ini_set('display_errors', -1);

use SaleConfirm\Processor\ChannelCurlProcessor as ChannelCurlProcessor;

require __DIR__ . '/vendor/autoload.php';

$channel = filter_input(INPUT_GET, "channel");

if(empty($channel))
{
    echo 'need to channel name';
    exit;
}

$search_processor = new ChannelCurlProcessor();
$search_processor->getDataCompany($channel);

// $search_processor = new SearchProcessor();
// $search_processor->Search();
