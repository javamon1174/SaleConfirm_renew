<?php
namespace SaleConfirm\Model;
//배열을 인수로 받아 쿼리로 만들어주는 리턴 클래스
class QueryMakeModel
{
    // use SaleConfirm\Config;
    /**
    *** @param string needs query
    *** @param string database and table
    *** @return string query
    **/
    public function returnQueryModel($func, $data = array())
    {
        return $this->$func($data);
    }
    private function getQueryMallList($table = '')
    {
        return 'SELECT `channel` FROM `'.$table.'_product_sub` GROUP BY `channel`';
    }
    private function getQueryInsertData($data)
    {
        try {
            $query = "INSERT INTO `{$data["channel"]}_report_tb` (`idx`, `NC`, `pcode`, `shop_code`, `sale_price`, `last_price1`,   `title`,  `sale_status`,
                    `sale_rate`,  `last_rate1`, `last_rate2`, `Going`, `channel`,  `url`, `times`, `week`, `regdate`, `time`,
                    `times_date`) VALUES ";
            for ($i=0; $i < ($data['count']/17) ; $i++) {
                $query = $query." (NULL, '0000', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?),";
            }
        } catch (Exception $e) {
            return false;
        }
        $query = substr($query , 0, -1);
        $query = $query.";";
        return $query;
    }
    private function getReportLastData($data)
    {
        return "SELECT times FROM ".$data."_report_tb ORDER BY `".$data."_report_tb`.`idx` DESC LIMIT 1";
    }
    private function getQueryProductInfoAsChannel($data)
    {
        //all channel test
        return "SELECT S.pcode, S.shop_code, M.retail_price FROM ".$data['company']."_product_sub S LEFT JOIN ".$data['company']
               ."_product_main M ON S.pcode = M.pcode WHERE S.channel LIKE '".$data['channel']."' AND S.sale_yn != 'N'
               GROUP BY S.shop_code LIMIT 3;";

        return "SELECT S.pcode, S.shop_code, M.retail_price FROM ".$data['company']."_product_sub S LEFT JOIN ".$data['company']
               ."_product_main M ON S.pcode = M.pcode WHERE S.channel LIKE '".$data['channel']."' AND S.sale_yn != 'N'
               GROUP BY S.shop_code";
        // return "SELECT S.pcode, S.shop_code, M.retail_price FROM ".$data['company']."_product_sub S LEFT JOIN ".$data['company']
        //        ."_product_main M ON S.pcode = M.pcode WHERE S.channel LIKE '".$data['channel']."' AND S.sale_yn != 'N' AND S.pcode = 'HQ56/21'
        //        GROUP BY S.shop_code";
    }
}
