<?php

namespace app\common\behavior;


class Levelup
{
	public function run(&$params){
		$uid =  $params;
		$thismember = \db("user_detail")->where("uid",$uid)->find();
		$jsstr = $uid;
		if(!empty($thismember['tjstr'])){
			$jsstr = $jsstr.','.$thismember['tjstr'];
		}
		$jsarr = explode(',',$jsstr);
		//获取等级
		$levels = \db('user_level')->where('enabled',1)->order('level asc')->select();
		//获取自动升级的参数
		$data = \db('system_setting')->where('type','levelup')->find();
		if($data){
			$data = unserialize($data['contents']);
		}else{
			return false;
		}
		$user = \db("user");
		$user_detail = \db("user_detail");
		foreach($jsarr as $mid) {
			$member = $user->where("id",$mid)->field('level,credit1acc,credit3acc')->find();
			$level = $member['level'];
			foreach($levels as $key=>$item) {

				if ($level < $item['level']) { //小于下一个等级，判断是否可以升级
					$canup = 1;//可以升级，如果不满足勾选的其中一个条件，则改成0，不可以升级
					//$noset = 1;//全部参数都没设置，不能升级
					$need_credit1 = $data['zhlj_'.$item['level']];
					$need_credit3 = $data['sylj_'.$item['level']];
					$need_tj_member_num =  $data['zt_'.$item['level']];
					$need_tj_member_czlj =  $data['czlj_'.$item['level']];
					//判断本人累计矿机数 和 累计推广收益
					if($member['credit1acc']<$need_credit1 || $member['credit3acc']<$need_credit3){
						$canup = 0;
					}
					//判断本人直推会员数
					$member['tj_num'] = $user_detail->where('tjid',$mid)->count();
					if($member['tj_num']<$need_tj_member_num){
						$canup = 0;
					}
					//判断本人直推的会员累计矿机数
					$member['tj_num_czlj'] =  $user_detail->alias('ud')
						->join("__USER__ u","u.id=ud.uid")
						->where('ud.tjid',$mid)
						->sum("credit1acc");
					if($member['tj_num_czlj']<$need_tj_member_czlj){
						$canup = 0;
					}
					if($canup==1){
						//升级
						$user->where('id',$mid)->update(['level'=>$item['level']]);
					}
				}
			}
		}

		return true;
	}


}
