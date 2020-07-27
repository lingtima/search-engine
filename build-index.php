<?php
/**
 * User: Lingtima<lingtima@gmail.com>
 * Date: 2020/7/23 10:59
 *
 * 用户分析语料，构建索引
 */

ini_set('memory_limit', '1G');
require_once __DIR__ . '/vendor/autoload.php';

function handle()
{
    jiebaInit();
    $config = require_once __DIR__ . '/config/config.php';
    $medoo = new Medoo\Medoo($config['db']);
    $redis = new \Predis\Client($config['redis']);

    $data = getData($medoo);
    $idfs = [];
    foreach ($data as $key => $v) {
        $idfs[$key] = getIDF($v);
    }

    $ret = saveIndex($redis, $idfs);
}

function jiebaInit()
{
    \Fukuball\Jieba\Jieba::init(['mode' => 'test', 'dict' => 'small']);
    \Fukuball\Jieba\Finalseg::init();
    \Fukuball\Jieba\JiebaAnalyse::init();
    \Fukuball\Jieba\Jieba::loadUserDict(__DIR__ . '/dict.txt');
}

function getData(\Medoo\Medoo &$medoo)
{
    $tableName = 'documents';

    $data = $medoo->select($tableName, ['id', 'content']);

    $ret = [];
    foreach ($data as $v) {
        $ret[$v['id']] = preg_replace('/[^\w]/u', ' ', strip_tags($v['content']));
    }

    return $ret;
}

function getIDF($content)
{
    $idf = \Fukuball\Jieba\JiebaAnalyse::extractTags($content, 30);

    return $idf;
}

function saveIndex(\Predis\Client &$redis, $idfs)
{
    $ret = [];
    foreach ($idfs as $articleId => $idf) {
        foreach ($idf as $word => $score) {
            $score = round($score * (1000000 * 1000 * 1000000));

            $ret[$articleId][$word] = $redis->zadd(buildRedisKey($word), $score, $articleId);
        }
    }

    return $ret;
}

function buildRedisKey($word)
{
    return 'search-engine:search-word:inverted-index:' . $word;
}

handle();