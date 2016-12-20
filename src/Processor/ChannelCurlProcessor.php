<?php
namespace SaleConfirm\Processor;

use SaleConfirm;
use Curl;

class ChannelCurlProcessor extends SearchProcessor
{
    use SaleConfirm\Config;

    //주석
    public function getDataCompany($company)
    {
        $channels = $this->processorInit($company);
        //타이틀 에러 11st akmall auction
        foreach ($channels as $asso_array => $channel) {
            /* test single channel */
            $channel['channel'] = '11st';
            $channel_result_info = $this->getDefaultDataOfReport($company);
            $channel_url = $this->getChannelUrl($channel);
            if (!$channel_url)
            {
                continue;
            }
            else
            {
                var_dump($channel['channel']);exit;

                $shop_code = $this->getProductInfoOfChennel($company, $channel['channel']);
                $channel_result_all_data[] = $this->singleContentsController($shop_code, $channel_url, $channel['channel']);
            }
        }
        foreach ($channel_result_all_data as $channel_result_data) {
            $commit_result_array[] = $this->insertResultData($company, $channel_result_data, $channel_result_info);
        }
        $this->removeResouce($channel_result_all_data);
        var_dump($commit_result_array);exit;
    }

    /**
    *** @param string company
    *** @param string channel
    *** @return array Product information with product name and shop_code, retail_price
    **/
    private function getProductInfoOfChennel( $company, $channel )
    {
        $data = array();
        $data['company'] = $company;
        $data['channel'] = $channel;
        $query = $this->query_make_model->ReturnQueryModel("getQueryProductInfoAsChannel", $data);
        $product_info = $this->query_excute_model->QueryExcuteModel("SelectQuery", $query, null);
        return $product_info;
    }

    /**
    *** @param string company
    *** @return array mall list of company
    **/
    private function processorInit($company)
    {
        $this->SetDatabaseObj();
        $query = $this->query_make_model->ReturnQueryModel("GetQueryMallList", $company);
        return $this->query_excute_model->QueryExcuteModel("selectQuery", $query, null);
    }

    private function channelNameException($channel)
    {
        switch ($channel) {
            case 'AK_dep':
                $channel = 'Akmall';
                break;
            case 'Hmall_cheonho':
                $channel = 'Hmall';
                break;
            case 'Hmall_cheonho(GS)':
                $channel = 'Hmall';
                break;
            case 'Hmall_dep':
                $channel = 'Hmall';
                break;
            case 'Hmall_popup':
                $channel = 'Hmall';
                break;
            case 'lotte_busan':
                $channel = 'lotte.com';
                break;
            case 'lotte_dep':
                $channel = 'lotte.com';
                break;
            case 'lotte_jamsil':
                $channel = 'lotteimall';
                break;
        }
        return $channel;
    }

    private function getDefaultDataOfReport($company)
    {
        $query = $this->query_make_model->ReturnQueryModel("getReportLastData", $company);
        $times = $this->query_excute_model->QueryExcuteModel("SelectQuery", $query);
        if (!empty($times[0]))
            $channel_result_info['times'] = $times[0]['times'];
        else
            $channel_result_info['times'] = 1;
        $week = array("일", "월", "화", "수", "목", "금", "토");
        $channel_result_info['todayofweeks'] = $week[date("w")];
        $channel_result_info['date'] = date('Y-m-d', time());
        $channel_result_info['date_time'] = date('H:i:s', time());
        $channel_result_info['regdate'] = date('Y-m-d H:i:s', time());
        return $channel_result_info;
    }

    private function data2BaseArray($channel_result_data, $channel_result_info)
    {
        foreach ($channel_result_data as $assoc_array => $row_data) {
            foreach ($row_data as $value) {
                $basic_array_data[] = $value;
            }
            foreach ($channel_result_info as $assoc_array => $channel_info) {
                $basic_array_data[] = $channel_info;
            }
        }
        return $basic_array_data;
    }

    /**
    *** @brief Put all the data in the database
    *** @param string company
    *** @param array channel_result_data
    *** @param array channel_result_info
    **/
    private function insertResultData(
                                      $company,
                                      $channel_result_data = array(),
                                      $channel_result_info = array()
                                     )
    {
        $basic_array_data = $this->data2BaseArray($channel_result_data, $channel_result_info);
        $channel_result_data = array(
                                     'count'=>count($basic_array_data),
                                     'channel'=>$company,
                                 );
        $query = $this->query_make_model->ReturnQueryModel("getQueryInsertData", $channel_result_data);
        $commit_result = $this->query_excute_model->QueryExcuteModel("insertQuery", $query, $basic_array_data);
        return $commit_result;
    }

    /**
    *** @brief get mached data from product.
    *** @param array parsed data(each shopcode)
    *** @param string url
    *** @param int sale price
    *** @param int last price
    *** @param string title
    *** @param string sale_status
    *** @return array matched data
    **/
    private function machedData2Array(
                                      $shop_code,
                                      $url,
                                      $sale_price,
                                      $last_price1,
                                      $title,
                                      $sale_status,
                                      $channel
                                     )
    {
        try
        {
            $is_price_exsist = ($last_price1 != 0 && $sale_price != 0 && $shop_code['retail_price'] != 0);
            if ($is_price_exsist)
            {
                $last_price2 = $last_price1;
                $sale_rate = round((1 - $sale_price/$shop_code['retail_price']) * 100);
                $last_rate1 = round((1 - $last_price1/$shop_code['retail_price']) * 100);
                $last_rate2 = round((1 - $last_price1/$sale_price) * 100);
                $last_rate3 = round((1 - $last_price2/$shop_code['retail_price']) * 100);
            }
            else
            {
                $last_price2 = 0;
                $sale_rate = 0;
                $last_rate1 = 0;
                $last_rate2 = 0;
                $last_rate3 = 0;
            }
            if ($sale_status !== '판매중')
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            return $e;
        }
        $result = array(
            'pcode' => $shop_code['pcode'],
            'shop_code' => $shop_code['shop_code'],
            'sale_price' => $sale_price,
            'last_price1' => $last_price1,
            'title' => $title,
            'sale_status' => $sale_status,
            'sale_rate' => $sale_rate,
            'last_rate1' => $last_rate1,
            'last_rate2' => $last_rate2,
            'going' => $shop_code['retail_price'],
            'channel' => $channel,
            'url' => $url.$shop_code['shop_code'],
        );
        // var_dump($result);exit;
        return $result;
    }
    /**
    *** @brief These functions get data from each shopcode and name of function is value in db
    *** @param array parsed data(each shop code)
    *** @param int shop code
    *** @param string channel
    *** @param string url
    *** @param int retail price
    *** @param string title
    *** @param string sale_status
    *** @var int Others
    *** @return array matched regular expression
    **/
    private function _thehyundai(
                                        $data,
                                        $shop_code,
                                        $channel,
                                        $url,
                                        $RCP = 1,
                                        $title = '',
                                        $sale_status = '',
                                        $sale_price = 0,
                                        $last_price1 = 0,
                                        $last_rate1 = 0,
                                        $last_rate2 = 0,
                                        $last_rate3 = 0
                                      )
    {
        try
        {
        $explode = 'prd-middle-banner';
        $data = explode ($explode, $data);
        $data = $data[0];
        $shop_code['retail_price'] = (int) $shop_code['retail_price'];
        //sale_price
        preg_match('/<span>[0-9].*<\/span>원/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
        if( !empty($matches[0][0]) )
            $sale_price = strip_tags($matches[0][0]);
        $sale_price = str_replace('원', "", $sale_price);
        $sale_price = (int) str_replace(',', "", $sale_price);
        //last_price
        preg_match('/[0-9].*[0-9]<span>원<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
        if( !empty($matches[0][0]) )
            $last_price1 = strip_tags($matches[0][0]);
        $last_price1 = str_replace('원', "", $last_price1);
        $last_price1 = (int) str_replace(',', "", $last_price1);
        //title
        preg_match('/<title>.*<\/title>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
        if( !empty($matches[0][0]) )
            $title = strip_tags($matches[0][0]);
        $title = str_replace(' - 더현대닷컴', "", $title);
        $title = str_replace('- The HYUNDAI', "", $title);
        //sale status
        $pattern = '/<button class="btn size6 color9" type="button" onclick="buyDirect\(\);">/';
        preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
        if( !empty($matches[0][0]) )
            $sale_status = "판매중";
        else
            $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _ShinsegaeMall(
                                        $data,
                                        $shop_code,
                                        $channel,
                                        $url,
                                        $RCP = 1,
                                        $title = '',
                                        $sale_status = '',
                                        $sale_price = 0,
                                        $last_price1 = 0,
                                        $last_rate1 = 0,
                                        $last_rate2 = 0,
                                        $last_rate3 = 0
                                      )
    {
        try
        {
            $this->need2Wait(50);
            $explode = 'link_items_top_desc';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price && last_price
            preg_match('/<span class=\"price\">.*\n.*<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = (int) str_replace(',', "", $sale_price);
            preg_match('/<span class=\"price\">[0-9,]{1,}<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            //last $matches[0][0][0]
            if( !empty($matches[0][0]) )
                $last_price1 = strip_tags($matches[0][0]);
            $last_price1 = (int) str_replace(',', "", $last_price1);
            //title
            preg_match_all('/name\=\"description\" content\=.*\/\>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][1][0]) )
                $title = strip_tags($matches[0][1][0]);
            $title = str_replace('name="description" content="', "", $title);
            $title = (string) str_replace('" />', "", $title);
            //sale status
            $pattern = '/sp_pr bpr_buy/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
            }
            catch (Exception $e)
            {
                echo $e;
            }
            return $this->machedData2Array(
                                            $shop_code,
                                            $url,
                                            $sale_price,
                                            $last_price1,
                                            $title,
                                            $sale_status,
                                            $channel
                                          );
    }

    private function _ShinsegaeGangnam(
                                        $data,
                                        $shop_code,
                                        $channel,
                                        $url,
                                        $RCP = 1,
                                        $title = '',
                                        $sale_status = '',
                                        $sale_price = 0,
                                        $last_price1 = 0,
                                        $last_rate1 = 0,
                                        $last_rate2 = 0,
                                        $last_rate3 = 0
                                      )
    {
        try
        {
            $this->need2Wait(50);
            $explode = 'link_items_top_desc';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price
            preg_match('/<span class=\"num\">[0-9,]{1,}<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = (int) str_replace(',', "", $sale_price);
            //last_price
            preg_match('/<p class=\"optimum\"><span class=\"num\">[0-9,]{1,}<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $last_price1 = strip_tags($matches[0][0]);
            $last_price1 = (int) str_replace(',', "", $last_price1);
            //title
            preg_match_all('/notiTitle\" value=\".*\">/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][1][0]) )
                $title = strip_tags($matches[0][1][0]);
            $title = str_replace('notiTitle" value="', "", $title);
            $title = (string) str_replace('">', "", $title);
            //sale status
            $pattern = '/sp_dtl btn_pay payment/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _nshop(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = '_sns_area sns_area v2';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price & last_price
            preg_match('/name=\"productSalePrice\" value=\"[0-9]{1,}\"/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = str_replace('name="productSalePrice" value="', "", $matches[0][0]);
            $sale_price = (int) str_replace('"', "", $sale_price);
            $last_price1 = $sale_price;
            //title
            preg_match('/<strong class=\"\">.*<\/strong>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            //sale status
            $pattern = '/checkoutPurchase/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _nseshop(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price & last_price
            preg_match('/<strong class=\"fc5\">[0-9,원]{1,}<\/strong>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if (empty($matches[0][0]) )
                preg_match('/<span class=\"fb txt_line\">.*\n.*/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if (!empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price =  str_replace('원', "", $sale_price);
            $sale_price = (int) str_replace(',', "", $sale_price);
            preg_match('/<em class=\"em_TotalPrice\">[0-9,원]{1,}<\/em>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if (!empty($matches[0][0]) )
                $last_price1 = strip_tags($matches[0][0]);
            $last_price1 =  str_replace('원', "", $last_price1);
            $last_price1 = (int) str_replace(',', "", $last_price1);
            //title
            preg_match('/name=\"ip_productName\" value=\".*/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = str_replace('name="ip_productName" value="', "", $matches[0][0]);
                $title = str_replace('/>', "", $title);
                $title = str_replace('">', "", $title);
            //sale status
            preg_match('/btn_orderNow.png/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _lotteimall(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'Detailtab1';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price & last_price
            preg_match('/<span class=\"price\">.*<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if (!empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = (int) str_replace(',', "", $sale_price);
            $last_price1 = $sale_price;
            //title
            preg_match('/\"og\:title.*/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
            {
                $title = explode('=', $matches[0][0]);
                $title = str_replace('"', "", $title[1]);
                $title = str_replace('/>', "", $title);
            }
            //sale status
            preg_match('/btn_01_150601.gif/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _lotte_com(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'bann-event';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price & last_price
            preg_match('/<span class=\"line-t price_vdn\">.*<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if (empty($matches[0][0]) )
                preg_match('/<strong class=\"big\">[0-9,]{1,}<\/strong>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = str_replace('원', "", $sale_price);
            $sale_price = (int) str_replace(',', "", $sale_price);
            $last_price1 = $sale_price;
            //title
            preg_match('/<strong>.*/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            //sale status
            preg_match('/btn_buyNow/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _homeplus(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            sleep(10);
            $explode = 'ir tip-close';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price & last_price
            preg_match('/<span class=\"price fc-ty7\" id=\"f_salem\">[0-9원,]{1,}<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = (int) str_replace('원', "", $sale_price);
            $last_price1 = $sale_price;
            //title
            preg_match('/<b>.*/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            //sale status
            preg_match('/<span class="in">바로구매<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _homenshop(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'viewDetailTab';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price
            preg_match('/<span class=\"sellPrice\">.*<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = str_replace('totItemPrcSpan">', "", $sale_price);
            $sale_price = (int) str_replace(',', "", $sale_price);
            //last_price
            preg_match('/<em id=\"goodsSum\">[0-9,]{1,}<\/em>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $last_price1 = strip_tags($matches[0][0]);
            $last_price1 = (int) str_replace(',', "", $last_price1);
            //title
            preg_match('/<strong>.*/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            //sale status
            preg_match('/button btSizeL bslColorRed/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
            //sale status exception
            if ($sale_status == '일시품절') {
                preg_match('/co_error_img7/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
                if( !empty($matches[0][0]) )
                    $sale_status = "일시품절";
            }
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _Hmall(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'detail_cont_Wrap ';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price
            preg_match('/totItemPrcSpan\">[0-9,]{1,}<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = str_replace('totItemPrcSpan">', "", $sale_price);
            $sale_price = (int) str_replace(',', "", $sale_price);
            //last_price
            preg_match('/<span class=\"benefit_price\">[0-9,]{1,}<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $last_price1 = strip_tags($matches[0][0]);
            $last_price1 = (int) str_replace(',', "", $last_price1);
            //title
            preg_match('/<title>.*<\/title>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            $title = str_replace(' - 현대Hmall', "", $title);
            //sale status
            preg_match('/btn_directBuy.gif/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
            //sale status exception
            if ($sale_status == '일시품절') {
                preg_match('/co_error_img7/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
                if( !empty($matches[0][0]) )
                    $sale_status = "일시품절";
            }
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _Gsshop(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'product_subDetailWrap';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price & last
            preg_match('/name=\"lowPrice\" value=\"[0-9]{1,}\"> <!-- <%=lowPrice/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = str_replace('name="lowPrice" value="', "", $sale_price);
            $sale_price = (int) str_replace('"> ', "", $sale_price);
            $last_price1 = $sale_price;
            //title
            preg_match('/<title>.*<\/title>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            $title = str_replace(' - GS SHOP', "", $title);
            //sale status
            $pattern = '/판매종료/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _gmarket(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'minishop_info';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price & last
            preg_match('/<strong class=\"numstyle\">[0-9,]{1,}<\/strong>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = (int) str_replace(",", "", $sale_price);
            $last_price1 = $sale_price;
            //title
            preg_match('/<title>.*<\/title>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            $title = str_replace('G마켓 - ', "", $title);
            //sale status
            $pattern = '/buy-now/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _emartmall(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $this->need2Wait(50);
            $explode = 'pr_expsection';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price & last
            preg_match_all('/<strong class=\"price\">[0-9,]{1,}<\/strong>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0][0]) )
                $sale_price = strip_tags($matches[0][0][0]);
            $sale_price = (int) str_replace(",", "", $sale_price);
            if( !empty($matches[0][0][0]) )
                $last_price1 = strip_tags($matches[0][1][0]);
            $last_price1 = (int) str_replace(",", "", $last_price1);
            unset($matches);
            //title
            preg_match('/data-item-nm.*data-price/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = str_replace('data-item-nm="', "", $matches[0][0]);
            $title = str_replace('" data-price', "", $title);
            //sale status
            $pattern = '/<span class=\"soldout\"><em class=\"blind\">.*<\/em><\/span>/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _ellotte(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'class="bann-event"';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price 109000
            preg_match('/<span class=\"line-t price_vdn\">[0-9,원]{1,}<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
                $sale_price = str_replace(",", "", $sale_price);
                $sale_price = (int) str_replace("원", "", trim($sale_price));
            //last_price
            $last_price1 = $sale_price;
            //title
            preg_match('/<strong>.*<\/strong>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            $title = str_replace(" | O! Shopping Smart - CJmall", "", $title);
            //sale status
            $pattern = '/<strong>품절<\/strong>/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( empty($matches[0][0]) )
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _coupang(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            //get item id
            preg_match("/data\-vendor\-item\-id=\"([0-9]+)\"/i", $data, $matches);
            if ( !empty($matches[1]) )
            {
                $item_id = $matches[1];
                $temp_url = "https://www.coupang.com/vp/products/{$shop_code["shop_code"]}/product-atf?vendorItemId={$item_id}";
                $data = $this->curlingSingleContent(null, $temp_url);
            }
            //going
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //title
            preg_match('/<h2 class=\"prod-buy-header__title\">.*<\/h2>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            //sale_price
            preg_match('/<span class=\"prod-txt-small prod-txt-red\">[0-9,원]{1,}<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
                $sale_price = str_replace(",", "", $sale_price);
                $sale_price = (int) str_replace("원", "", trim($sale_price));
            //last_price
            $last_price1 = $sale_price;
            //sale status
            $pattern = '/<span class=\"prod-buy-btn__txt\">바로구매<\/span>/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
            	$sale_status = "판매중";
            else
            	$sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _Cjmall(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'box2_bg_pink';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price 109000
            preg_match('/sale_price_text.*/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = str_replace('sale_price_text" >', "", $matches[0][0]);
                $sale_price = str_replace(",", "", $sale_price);
                $sale_price = (int) str_replace("원", "", trim($sale_price));
            //last_price
            $last_price1 = $sale_price;
            //title
            preg_match('/<title>.*<\/title>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            $title = str_replace(" | O! Shopping Smart - CJmall", "", $title);
            //sale status
            $pattern = '/btnBuyNow/';
            preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
            	$sale_status = "판매중";
            else
            	$sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _auction(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'ulVipTab';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price
            preg_match_all('/<span>[0-9,]+<\/span>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][1][0]) )
                $sale_price = strip_tags($matches[0][1][0]);
            $sale_price = (int) str_replace(",", "", $sale_price);
            //last_price
            $last_price1 = $sale_price;
            //title
            preg_match('/<title>.*<\/title>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            $title = str_replace(" - 옥션", "", $title);
            //sale status
            $pattern = '/구매하기/';
            preg_match_all($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][1][0]) )
            	$sale_status = "판매중";
            else
            	$sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _Akmall(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $explode = 'class="tab_wrap tab_st07 tab_recom"';
            $data = explode ($explode, $data);
            $data = $data[0];
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            //sale_price
            preg_match('/<i>[0-9,]{1,6},[0-9]{3}/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $sale_price = strip_tags($matches[0][0]);
            $sale_price = (int) str_replace(",", "", $sale_price);
            //last_price
            preg_match('/<i class=\"won\">[0-9,]{3,}<\/i>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $last_price1 = strip_tags($matches[0][0]);
            $last_price1 = (int) str_replace(",", "", $last_price1);
            //title
            preg_match('/<title>.*<\/title>/', $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][0]) )
                $title = strip_tags($matches[0][0]);
            $title = str_replace(" | 백화점을 클릭하다. AK 몰", "", $title);
            //sale status
            $pattern = '/바로구매/';
            preg_match_all($pattern, $data, $matches, PREG_OFFSET_CAPTURE, 3);
            if( !empty($matches[0][1][0]) )
            	$sale_status = "판매중";
            else
            	$sale_status = "일시품절";
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function _11st(
                            $data,
                            $shop_code,
                            $channel,
                            $url,
                            $RCP = 1,
                            $title = '',
                            $sale_status = '',
                            $sale_price = 0,
                            $last_price1 = 0,
                            $last_rate1 = 0,
                            $last_rate2 = 0,
                            $last_rate3 = 0
                          )
    {
        try
        {
            $shop_code['retail_price'] = (int) $shop_code['retail_price'];
            preg_match('/sale_price.*[0-9,]{1,7}0/', $data, $matched);
            if( !empty($matched[0]) )
                $sale_price = str_replace("sale_price'>", "", $matched[0]);
            $sale_price = (int) str_replace(",", "", $sale_price);
            preg_match('/totalPriceArea.*[0-9,]{1,7}0/', $data, $matched);
            if( !empty($matched[0]) )
                $last_price1 = str_replace("totalPriceArea\">", "", $matched[0]);
            $last_price1 = (int) str_replace(",", "", $last_price1);
            preg_match('/<h2>.*<\/h2>/', $data, $matched);
            if (!empty($matched[0]))
                $title = str_replace("<h2>", "", $matched[0]);
            $title = str_replace("<\/h2>", "", $title);
            preg_match('/구매하기/', $data, $matched);
            if (!empty($matched[0]))
                $sale_status = "판매중";
            else
                $sale_status = "일시품절";
            preg_match('/현재 판매 중인 상품이 아닙니다/', $data, $matched);
            if (!empty($matched[0]))
                $sale_status = "일시품절";
            if ($last_price1 == null)
                $last_price1 = $sale_price;
            elseif ($sale_price == null)
                $sale_price = $last_price1;
        }
        catch (Exception $e)
        {
            echo $e;
        }
        return $this->machedData2Array(
                                        $shop_code,
                                        $url,
                                        $sale_price,
                                        $last_price1,
                                        $title,
                                        $sale_status,
                                        $channel
                                      );
    }

    private function getChannelUrl($channel)
    {
        switch ($channel['channel'])
        {
            case '11st':
                $url = "http://www.11st.co.kr/product/SellerProductDetail.tmall?method=getSellerProductDetail&prdNo=";
                break;
            case 'Akmall':
                $url = "http://www.akmall.com/goods/GoodsDetail.do?goods_id=";
                break;
            case 'AK_dep':
                $url = "http://www.akmall.com/goods/GoodsDetail.do?goods_id=";
                break;
            case 'auction':
                $url = "http://itempage3.auction.co.kr/DetailView.aspx?ItemNo=";
                break;
            case 'Cjmall':
                $url = "http://www.cjmall.com/prd/detail_cate.jsp?item_cd=";
                break;
            case 'coupang':
                $url = "https://www.coupang.com/vp/products/";
                // $url = "https://www.coupang.com/vp/products/{$product_id}/product-atf?vendorItemId={$item_id}";
                break;
            case 'ellotte':
                $url = "http://www.ellotte.com/goods/viewGoodsDetail.lotte?goods_no=";
                break;
            case 'emartmall':
                $url = "http://emart.ssg.com/item/itemDtl.ssg?itemId=";
                break;
            case 'gmarket':
                $url = "http://item2.gmarket.co.kr/Item/detailview/Item.aspx?goodscode=";
                break;
            case 'Gsshop':
                $url = "http://www.gsshop.com/prd/prd.gs?prdid=";
                break;
            case 'Hmall':
                $url = "http://www.hyundaihmall.com/front/pda/itemPtc.do?slitmCd=20";
                break;
            case 'Hmall_cheonho':
                $url = "http://www.hyundaihmall.com/front/pda/itemPtc.do?slitmCd=20";
                break;
            case 'Hmall_cheonho(GS)':
                $url = "http://www.hyundaihmall.com/front/pda/itemPtc.do?slitmCd=20";
                break;
            case 'Hmall_dep':
                $url = "http://www.hyundaihmall.com/front/pda/itemPtc.do?slitmCd=20";
                break;
            case 'Hmall_popup':
                $url = "http://www.hyundaihmall.com/front/pda/itemPtc.do?slitmCd=20";
                break;
            case 'homenshop':
                $url = "http://www.hnsmall.com/display/goods.do?goods_code=";
                break;
            case 'homeplus':
                $url = "http://direct.homeplus.co.kr/app.product.Product.ghs?comm=usr.product.detail&input_type=2&input_value=81879&i_style=";
                break;
            case 'lotte.com':
                $url = "http://www.lotte.com/goods/viewGoodsDetail.lotte?goods_no=";
                break;
            case 'lotteimall':
                $url = "http://www.lotteimall.com/goods/viewGoodsDetail.lotte?goods_no=";
                break;
            case 'lotte_busan':
                $url = "http://www.lotte.com/goods/viewGoodsDetail.lotte?goods_no=";
                break;
            case 'lotte_dep':
                $url = "http://www.lotte.com/goods/viewGoodsDetail.lotte?goods_no=";
                break;
            case 'lotte_jamsil':
                $url = "http://www.lotteimall.com/goods/viewGoodsDetail.lotte?goods_no=";
                break;
            case 'nseshop':
                $url = "http://www.nsmall.com/ProductDisplay?partNumber=";
                break;
            case 'nshop':
                $url = "http://storefarm.naver.com/philipsbrandshop/products/";
                break;
            case 'ShinsegaeMall':
                $url = "http://shinsegaemall.ssg.com/item/itemView01.ssg?itemId=";
                break;
            case 'ShinsegaeCentum':
                $url = "http://shinsegaemall.ssg.com/item/itemView01.ssg?itemId=";
                break;
            case 'ShinsegaeGangnam':
                $url = "http://department.ssg.com/item/itemView.ssg?itemId=";
                break;
            case 'nshop':
                $url = "http://storefarm.naver.com/philipsbrandshop/products/";
                break;
            case 'thehyundai':
                $url = "http://www.thehyundai.com/front/pda/itemPtc.thd?slitmCd=40";
                break;
            default:
                return false;
        }
        return $url;
    }

    /**
    *** @brief single Contents Controller.
    *** @param array parsed data(each shopcode)
    *** @param string url
    *** @param string channel
    *** @return array result_channel_data
    **/
    private function singleContentsController(
                                                $shop_code = array(),
                                                $url = '',
                                                $channel_ = ''
                                             )
    {
        $channel = '_'.$this->channelNameException($channel_);
        $channel = str_replace('.', "_", $channel);
        foreach ($shop_code as $shop_code) {
            $parsed_data = $this->curlingSingleContent($shop_code["shop_code"], $url);
            $result_channel_data[] = $this->$channel($parsed_data, $shop_code, $channel_, $url);
        }
        return $result_channel_data;
    }

    private function curlingSingleContent($shop_code, $url)
    {
        $curl = new Curl\Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $curl->setOpt(CURLOPT_HEADER, 0);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, 1);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->setOpt(CURLOPT_REFERER, 'www.naver.com');
        $curl->get($url.$shop_code);
        if ($curl->error)
        {
            return false;
        }
        else
        {
            $enc = mb_detect_encoding(
                $curl->response,
                array("UTF-8", "EUC-KR", "SJIS")
            );
            if($enc == "EUC-KR")
               $curl->response = iconv($enc, "UTF-8", $curl->response);
            return $curl->response;
        }
    }

    public function need2Wait($second)
    {
        sleep($second);
        return true;
    }
}
