<?php
namespace SaleConfirm\Processor;

use SaleConfirm;
use SaleConfirm\Processor;
use SaleConfirm\Model;

class SearchProcessor {

    use SaleConfirm\Config;

    protected $query_make_model;
    protected $query_excute_model;
    private $channels = array();

    public function __construct() { }

    public function configging()
    {
        $this->ConfigInit();
        $this->setDatabaseObj();
    }

    public function Search()
    {
        $this->configging();
        // main control
        //채널별 반복문 -> 샵코드 -> 서브클래스 전달 -> 배열 받고 쿼리 -> 쿼리실행
        foreach ($this->company as $asso_array => $company) {
            $channel_curl_processor = $this->factory("Processor\ChannelCurlProcessor");
            $channel_curl_processor->getDataCompany($company);

            //testing cl
            exit;
        }
    }

    private function setCompanyInfo($company)
    {
        $this->removeResouce($this->table);
        $this->table[] = $company."_product_main";
        $this->table[] = $company."_product_sub";
        $this->table[] = $company."_report_tb";
    }

    private function factory($class_name)
    {
        try {
            $class_name = 'SaleConfirm\\'.$class_name;
            return new $class_name;
        } catch (Exception $e) {
            return $e;
        }
    }

    protected function setDatabaseObj()
    {
        $this->query_make_model =  $this->factory("Model\QueryMakeModel");
        $this->query_excute_model =  $this->factory("Model\QueryExcuteModel");
        if (!empty($this->query_excute_model) && !empty($this->query_excute_model))
            return true;
        else
            return die;
    }

    private function getMallList($company)
    {
        $query = $this->query_make_model->ReturnQueryModel('getQueryMallList', $company);
        $this->channels = $this->query_excute_model->QueryExcuteModel('selectQuery', $query);
        return true;
    }

    protected function removeResouce($resouce)
    {
        unset($resouce);
        return true;
    }

}
