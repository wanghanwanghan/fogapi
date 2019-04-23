<?php
/*
 * 2018-12-28
 *  
 */
define('IN_IISS', TRUE);

$siteroot = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
array_pop($siteroot);
$path = implode(DIRECTORY_SEPARATOR, $siteroot);

//echo $path;die;
echo $path.'/include/common.inc.php';
echo PHP_EOL;

//调用网站通用
include_once($path.'/include/common.inc.php');
//die('ss');
//include $path.'/comm.php';

$artList = array();
//$commentDB = IFactory::getCommentDB();
$db = IFactory::getDB();
$today = strtotime('today');


$arr_article_field = array(
	'artid',
	'categoryid',
	'topid',
	'spic',
	'flag',
	'commentnum',
	'userid',
	'pubdate',
	'modifydate',
	'ischeck',
	'sortnum',
	'timestamp',
	'isapp',
	'iswap',
	'hezuoclick',
	'effectivetime',

);

$arr_article_sort_field = array(
	'artid',
'categoryid',
'topid',
'spic_num',
'flag',
'commentnum',
'userid',
'pubdate',
'modifydate',
'ischeck',
'sortnum',
'timestamp',
'isapp',
'iswap',
'hezuoclick',
'effectivetime',

);
$article_fields = implode(', ', $arr_article_field);

$start = strtotime('2018-12-15');
echo $start;

$artid = 0;
while( 1 ){
	$sql = "SELECT {$article_fields} FROM iiss_article Where pubdate > $start ORDER BY pubdate DESC, artid desc limit 1000 ";
	echo $sql.PHP_EOL;
	$artList = array();
	$query = $db->query($sql);
	var_dump($query);

	$row_num = $db->num_rows( $query );
	echo "[$row_num]";
	if( $row_num){
		while($row = $db->fetch_array($query)){
			var_dump($row);
//			die;
			$artid = $row['artid'];

				$arrspic = unserialize( $row['spic'] );

				var_dump($arrspic);
//				die;
				unset( $row['spic'] );

				if( $arrspic !== false ){
					$row['spic_num'] = count( $arrspic );

				}else{
					$row['spic_num'] = 0;

				}

//			var_dump($row);

			$exist = row_exist( $artid );
			var_dump($exist);

			if( $exist ){
				$where = "artid = $artid";
				$db->updateTable('iiss_article_sort', $row, $where);
			}else{
				$db->insertTable('iiss_article_sort', $row, 1);
			}

		}
	    //1000条处理结束
		break;
	}else{
		echo '===';
//		//处理结束
		break;
	}

//	if( $query['num_rows'] ){
//		echo "sdfds";die;

//	}else{
//		echo '===';
//		//处理结束
//		break;
//	}



}

echo "结束";


function row_exist( $artid ){
	global $db;
	$sql = "SELECT artid FROM iiss_article_sort WHERE `artid`={$artid} LIMIT 1";
	$query = $db->query($sql);
	$num = $db->num_rows( $query );

	if( $num ){
		return true;
	}else{
		return false;
	}

}


