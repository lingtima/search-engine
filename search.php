<?php
/**
 * User: Lingtima<lingtima@gmail.com>
 * Date: 2020/7/23 09:42
 *
 * 搜索引擎输入端
 */

require_once __DIR__ . '/vendor/autoload.php';

$tips = "请输入关键词继续搜索（如需退出请输入 exit）：";
$exitFlag = 'exit';
$exitMsg = '感谢您的使用，祝您生活愉快~';

while (true) {
    fwrite(STDOUT, $tips);
    $input = trim(fgets(STDIN));

    if ($input === $exitFlag) {
        break;
    }

    $inputs = explode(' ', $input);

    search($inputs);
}

echo $exitMsg;

function search($inputs)
{
    $outKey = getRedisKey(implode('-', $inputs), true);
    $keyNum = count($inputs);
    $strKey = implode(' ', array_map('getRedisKey', $inputs));

    $config = require_once __DIR__ . '/config/config.php';
//    var_dump($config);
    $redis = new \Predis\Client($config['redis']);

    //查询缓存
    if ($ret = $redis->zrevrange($outKey, 0, 9)) {

    } else {
        $store = $redis->zunionstore($outKey, array_map('getRedisKey', $inputs));
        $redis->expire($outKey, 3600);
        $ret = $redis->zrevrange($outKey, 0, 9);
    }

    echo "这是您的搜索结果：";
    echo implode("\t", $ret) . "\r\n";
}

function getRedisKey($word, $isOut = false)
{
    if ($isOut) {
        return 'search-engine:search-word:select-cache:' . $word;
    } else {
        return 'search-engine:search-word:inverted-index:' . $word;
    }
}

