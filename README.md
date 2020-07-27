# search-engine
基于分词实现的简单搜索引擎

# 流程

## 词库端
1. 定时任务提取关键词
2. 更新自定义词库？
3. 保存、更新词库权重
4. Redis 中保存关键字-（权重-文章ID）的倒排索引

## 搜索端
1. 对搜索词分词成若干关键字
2. 通过 Redis 获取文章ID
	1. 增加查询缓存，key=hash(key)
	2. zunionstore out num key1 key2 weight key1QuanZhong key2QuanZhong
3. MySQL 中获取文章详情


# 详细设计
## 更新词库与索引

### 提取关键字
参考资料：
[中文分词-结巴分词](https://github.com/fxsjy/jieba)
[基于 TF-IDF 算法的关键词抽取](https://github.com/fxsjy/jieba#%E5%9F%BA%E4%BA%8E-tf-idf-%E7%AE%97%E6%B3%95%E7%9A%84%E5%85%B3%E9%94%AE%E8%AF%8D%E6%8A%BD%E5%8F%96)
[]()

关于词性的说明：
paddle模式词性和专名类别标签集合如下表，其中词性标签 24 个（小写字母），专名类别标签 4 个（大写字母）。

| 标签 | 含义     | 标签 | 含义     | 标签 | 含义     | 标签 | 含义     |
| ---- | -------- | ---- | -------- | ---- | -------- | ---- | -------- |
| n    | 普通名词 | f    | 方位名词 | s    | 处所名词 | t    | 时间     |
| nr   | 人名     | ns   | 地名     | nt   | 机构名   | nw   | 作品名   |
| nz   | 其他专名 | v    | 普通动词 | vd   | 动副词   | vn   | 名动词   |
| a    | 形容词   | ad   | 副形词   | an   | 名形词   | d    | 副词     |
| m    | 数量词   | q    | 量词     | r    | 代词     | p    | 介词     |
| c    | 连词     | u    | 助词     | xc   | 其他虚词 | w    | 标点符号 |
| PER  | 人名     | LOC  | 地名     | ORG  | 机构名   | TIME | 时间     |


```php
<?php
/**
 * User: Lingtima<lingtima@gmail.com>
 * Date: 2020/7/22 09:26
 */

ini_set('memory_limit', '1G');

require_once __DIR__ . '/vendor/autoload.php';

$article = file_get_contents(__DIR__ . '/article.txt');
$article = 'Wine Bottle Mockup 3 透明玻璃干白葡萄酒瓶酒标包装设计ps智能贴图样机素材mockup模板';
$userDict = file_get_contents(__DIR__ . '/user_dict.txt');

echo 'memory peak:' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\r\n";
\Fukuball\Jieba\Jieba::init(['mode' => 'test', 'dict' => 'small']);
echo 'memory peak:' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\r\n";
\Fukuball\Jieba\Finalseg::init();
echo 'memory peak:' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\r\n";

\Fukuball\Jieba\Jieba::loadUserDict(__DIR__ . '/user_dict.txt');
echo 'memory peak:' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\r\n";
$time1 = microtime(true);
$cutRet = \Fukuball\Jieba\Jieba::cutForSearch($article);
$time2 = microtime(true);
echo 'memory peak:' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\r\n";
var_dump($cutRet);

\Fukuball\Jieba\JiebaAnalyse::init();
echo 'memory peak:' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\r\n";
$time3 = microtime(true);
$tags = \Fukuball\Jieba\JiebaAnalyse::extractTags($article, 50);
$time4 = microtime(4);
echo 'memory peak:' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\r\n";

var_dump($tags);

var_dump('t1:' . ($time2 - $time1));
var_dump('t2:' . ($time4 - $time3));
```

### 更新自定义词典
```mysql
insert into user_dicts ('word', 'freq', 'tag') values ('test', 1233, 'n');
```

### 更新 Redis 倒排索引
```redis
zset prefix:word article_id freq
```

## 搜索端
### 是否进入搜索引擎模式
```php
if mb_strlen($str) <= 3 {
	return search_from_db();
} else {
	return into_search();
}
```
### 对输入字符分词
```php
$cutRet = \Fukuball\Jieba\Jieba::cutForSearch($article);
```
### 搜索查询缓存
```redis
zrange key 0 9
#查询缓存中查找不到时，搜索 Redis 倒排索引
```

**说明**
1. 在倒排索引更新时，是否需要更新查询缓存？
	1. 应该是不需要的
		1. 实时更新查询缓存耗时耗力，性价比地
		2. 既然语料库每天分析一次，也就没有必要实时维护查询缓存了
	2. 带来的影响
		1. 部分搜索查询不到近一天的语料
### 进入 Redis 倒排索引 查询文章ID
```redis
zunionstore out_key 3 key1 key2 key3 WEIGHTS 23 21 3

zrevrange out_key 0 9 #这里就是查询缓存
```
### 获取结果详情
```mysql
select * from article where id in (1,2,3,4,7);
```
