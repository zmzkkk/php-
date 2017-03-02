<?php 
function formatXml($contents,$get_attributes = 1, $priority = 'tag'){
    $parser = xml_parser_create('');
   xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);
    if (!$xml_values)
        return; //Hmm...
    $xml_array = array ();
    $parents = array ();
    $opened_tags = array ();
    $arr = array ();
    $current = & $xml_array;
    $repeated_tag_index = array (); 
    foreach ($xml_values as $data)
    {
        unset ($attributes, $value);
        extract($data);
        $result = array ();
        $attributes_data = array ();
        if (isset ($value))
        {
            if ($priority == 'tag')
                $result = $value;
            else
                $result['value'] = $value;
        }
        if (isset ($attributes) and $get_attributes)
        {
            foreach ($attributes as $attr => $val)
            {
                if ($priority == 'tag')
                    $attributes_data[$attr] = $val;
                else
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }
        if ($type == "open")
        { 
            $parent[$level -1] = & $current;
            if (!is_array($current) or (!in_array($tag, array_keys($current))))
            {
                $current[$tag] = $result;
                if ($attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
            }
            else
            {
                if (isset ($current[$tag][0]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                { 
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    ); 
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if (isset ($current[$tag . '_attr']))
                    {
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset ($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        elseif ($type == "complete")
        {
            if (!isset ($current[$tag]))
            {
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if ($priority == 'tag' and $attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
            }
            else
            {
                if (isset ($current[$tag][0]) and is_array($current[$tag]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data)
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    ); 
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes)
                    {
                        if (isset ($current[$tag . '_attr']))
                        { 
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                        if ($attributes_data)
                        {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        elseif ($type == 'close')
        {
            $current = & $parent[$level -1];
        }
    }
    \SeasLog::log('debug',"请求上游接口：".json_encode($xml_array));
    return ($xml_array);    
}


/**
 * 链接 redis
 * @return [type] [description]
 */
function redisOpen($close = false){
    static $__redis = null;
    if($close){
        if($__redis){
            $__redis->close();
            return ;
        }
    }
    if(empty($__redis)){
        // var_dump(config("database.redis"));
        $redisConfig = config("database.redis");
        // var_dump($redisConfig);
        if($redisConfig){
            $__redis = new \redis();
            $__redis->open($redisConfig['host'],$redisConfig['port']);
            // $__redis->auth('iwqh452785sd612hrifg8w346.nk');
            $__redis->select(2);
        }
    }
    return $__redis;
}



/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @return string
 */
function friendlyDate($sTime) {
    if (!$sTime)
        return '';
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime      =   time();
    if($sTime<$cTime){
        return '已过期';
    }
    $dTime      =   $sTime - $cTime;
    $dDay       =   intval(date("z",$sTime)) - intval(date("z",$cTime));
    $dYear      =   intval(date("Y",$sTime)) - intval(date("Y",$cTime)) ;

    if( $dTime < 60 ){
        return intval(floor($dTime / 10) * 10)."秒后";
    }elseif( $dTime < 3600 ){
        return intval($dTime/60)."分钟后";
    }elseif( $dYear==0 && $dDay == 0  ){//今天的数据.年份相同.日期相同.
        return '今天'.date('H:i',$sTime);
    }elseif($dYear==0){
        return date("m月d日 H:i",$sTime);
    }else{
        return date("Y-m-d H:i",$sTime);
    }
}
