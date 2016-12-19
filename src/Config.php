<?php
namespace SaleConfirm;

trait Config
{
    /**
    *** @param host
    **/
    public $host = '';
    /**
    *** @param database
    **/
    protected $database = '';
    /**
    *** @param table
    **/
    protected $table = array();
    /**
    *** @param table
    **/
    protected $charset = 'utf8';
    /**
    *** @param flag channel
    **/
    protected $channel = array();
    /**
    *** @param array company
    **/
    protected $company = ['cl', 'lighting', 'cenovis', 'hasbro', 'fwill', 'omron', 'omron_seller'];
    /**
    *** @param flag name of databases
    **/
    protected $databases = array(
            'cl'=>'fwill',
            'ligting'=>'ligting',
            'cenovis'=>'cenovis',
            'hasbro'=>'hasbro',
            'fwill'=>'fwill',
            'omron'=>'omron',
            'omron_seller'=>'omron_seller',
    );
    /**
    *** @param flag name of tables
    **/
    // protected $tables = array(
    //         'cl'=>'cl_product_sub',
    //         'ligting'=>'report_tb',
    //         'cenovis'=>'report_tb',
    //         'hasbro'=>'report_tb',
    //         'fwill'=>'report_tb',
    //         'omron'=>'report_tb',
    //         'omron_seller'=>'seller_report',
    // );
    /**
    *** @todo arguments to this object variable
    **/
    public function configInit()
    {
        //service mode
        // error_reporting(0);
        //develop mode
        error_reporting(E_ALL);
        ini_set('display_errors', -1);
        $return = function() {
            // define('HOST', '220.118.158.10');
            $this->host = '220.118.158.10';
            if (!empty($this->host))
                return true;
            else
                return false;
        };
        return $return();
    }
    public function __construct() { }
}
