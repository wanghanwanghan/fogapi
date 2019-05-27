<?php
/*
* 探索世界--更新用户周排行
 *
 * v2  根据用户迷雾分表 统计
*/
function calulateLevelInfo2($count)
{
	$area = $count * 0.0079;
    $p1 = 729.509938539109;
    $p2 = 4786.43982054646;
    $leveld = $p1*$area/($p2+$area) + 1;
    $level = floor($leveld);
    $progress =  $leveld - $level + 1;
    return array(
		"area"=>$area,
		"level"=>$level,
		"progress"=>$progress
	);
}


$siteroot = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
array_pop($siteroot);
array_pop($siteroot);
$path = implode(DIRECTORY_SEPARATOR, $siteroot);
include_once($path.'/include/Config.php');

$start = time();

$tMember = new TDB_Member();

//周排行起始时间是周一零点

//取得数据变化的用户
$obj_user_fog_cal_status = new TDB_UserFogCalStatus();

$obj_user_fog_table_router = new TDB_UserFogTableRouter();



$id = 0;

$sort_flag = false;

$date_range = $obj_user_fog_table_router::date_range();
//$date_range = $obj_user_fog_table_router::date_range(1541029112);

$cal_type = 'week_rank';

while( 1 ){
	$users = $obj_user_fog_cal_status->getCalUser($cal_type, $id, 30 );

	if( !$users ){
		//处理结束
		break;
	}

//	var_dump($users);

	foreach( $users AS $user ){
		$sort_flag = true;

		$id = $user['id'];
		$userid = $user['userid'];

		$date_range = $obj_user_fog_table_router::date_range();
//		$date_range = $obj_user_fog_table_router::date_range(1541029112);

		//根据周的起始时间戳取得用户分表名称
		$arr_user_fog_table[] = $obj_user_fog_table_router::_routerUserFogTableName($userid, $date_range['week_start'] );
		$arr_user_fog_table[] = $obj_user_fog_table_router::_routerUserFogTableName($userid, $date_range['week_end'] );

		$arr_user_fog_table = array_unique( $arr_user_fog_table );

		$count = count( $arr_user_fog_table );

		if( $count == 0 ){
			//异常
			continue;
		}

		$obj_user_fog_table = array();

		//同一个月表
		if( $count == 1 ){
			$obj_user_fog_table[] = $obj_user_fog_table_router::getInstance($userid, $date_range['week_start'] );
		}else{ //跨月表
			$obj_user_fog_table[] = $obj_user_fog_table_router::getInstance($userid, $date_range['week_start'] );

			$obj_user_fog_table[] = $obj_user_fog_table_router::getInstance($userid, $date_range['week_end'] );
		}


		//迷雾点统计
		$arr_fog_count = array();

		foreach( $obj_user_fog_table AS $obj_table ){
			//周统计
			$arr_fog_count[] = $obj_table->week_count( $userid , $date_range['week_of_year']);
		}

		$fog_num = array_sum( $arr_fog_count );

		//本周没有上传的迷雾点
		if ( $fog_num == 0 ){
			//处理下一个用户
			continue;
		}
//		var_dump($fog_num);die;

		$user_fog_num_week[$userid] = $fog_num;


		$info = calulateLevelInfo2($fog_num);

		echo "fog_num: $fog_num".PHP_EOL;

		var_dump($info);
//		if( $userid == 30556)die;
//		die;

		if( $info ){
			//获取排名序号
			$week_start = $date_range['week_start'];

			$area = $info['area'];
			$sortnum = sort_num($area, $date_range['week_start'] );

//			var_dump($sortnum);die;

			//更新用户周排行
			$sql = "select * from tssj_member_rank_week where `userid` = {$userid} and `dateline` = {$week_start}";

			$tmpvalue = $tMember->SqlCommand->ExecuteArrayQuery($sql, 1, 1, 'assoc');

//			var_dump($tmpvalue);die;
			if(empty($tmpvalue)){

				$sql = "insert into tssj_member_rank_week(`userid`, `area`, `dateline`, `sortnum`) values ( {$userid}, {$area}, {$week_start}, {$sortnum})";
				echo $sql.PHP_EOL;
				$tMember->SqlCommand->ExecuteNonQuery($sql);
//				sleep(1);
			}else{
				$id = $tmpvalue[0]['id'];

				//面积增加才更新
				if( $area > $tmpvalue[0]['area'] ){
					$sql = "update tssj_member_rank_week set `area`=$area, `sortnum`={$sortnum} where id={$id}";
					echo $sql.PHP_EOL;
//				sleep(1);
					$tMember->SqlCommand->ExecuteNonQuery( $sql );

				}

			}

			file_put_contents('./log/rank_week.log', $sql.PHP_EOL, FILE_APPEND);

			$update_data['status'] = 1;
			$obj_user_fog_cal_status->save($userid, $cal_type, $update_data);
		}

	}
}


//有排序更新,则重新刷新排序序号
if( $sort_flag ){
	$week_start = $date_range['week_start'];
	$id = 0;
	$sortnum = 0;

	$page_size = 1;


	$sql  = "select max(area) AS area  from tssj_member_rank_week where  `dateline` = {$week_start} ";

	echo PHP_EOL.$sql.PHP_EOL;

	$tmpvalue = $tMember->SqlCommand->ExecuteArrayQuery($sql, 0, 0, 'assoc');

	$max_area = $tmpvalue[0]['area'];


	while( 1 ){


//		var_dump( $tmpvalue );die;

		$sql = "select * from tssj_member_rank_week where  `dateline` = {$week_start} AND `area` = $max_area ORDER BY `area` DESC, id ASC ";
		echo PHP_EOL.$sql.PHP_EOL;

		$tmpvalue = $tMember->SqlCommand->ExecuteArrayQuery($sql, 0, 0, 'assoc');

		foreach( $tmpvalue AS $value ){
			$sortnum ++;

			$id = $value['id'];

			//相同时不更新
			if( $value['sortnum'] != $sortnum ){
				$sql = "update tssj_member_rank_week set `sortnum`={$sortnum} where id={$id}";
				echo PHP_EOL.$sql.PHP_EOL;
				$tMember->SqlCommand->ExecuteNonQuery( $sql );
			}else{
				echo $sortnum.PHP_EOL;
			}

		}


		//取得下一个剩余的最大面积
		$sql  = "select max(area) AS area  from tssj_member_rank_week where  `dateline` = {$week_start} AND area < $max_area";
		$tmpvalue = $tMember->SqlCommand->ExecuteArrayQuery($sql, 0, 0, 'assoc');
		var_dump($tmpvalue);
		echo PHP_EOL.">>>>====".PHP_EOL;


		if( !is_null( $tmpvalue[0]['area'] ) ){
			$max_area = $tmpvalue[0]['area'];

		}else{
			//排序结束
			echo PHP_EOL."排序更新结束";
			break;
		}

	}


}



//周排行序号获取
function sort_num( $area, $week_start ){
	$sql = "select count(*) AS count from tssj_member_rank_week where dateline={$week_start} AND area > $area";

	global $tMember;

	$tmpvalue = $tMember->SqlCommand->ExecuteArrayQuery($sql, 1, 1, 'assoc');

	$sort_num = $tmpvalue[0]['count'] + 1;

	return $sort_num;
}




$end = time();

echo '用户信息更新完成,耗时： '.($end-$start).' 秒';
