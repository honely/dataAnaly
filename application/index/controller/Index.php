<?php
namespace app\index\controller;
use think\Cookie;
use think\Db;
use think\Controller;
use think\Session;

class Index extends Controller
{
    private $tokenk = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=ww5b3ccb6d1dabb1fb&corpsecret=0e8TYhfwlrrfZ5JicEaNcW512pFzaowUyvkxZVajGZM";
    private $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo";
    private $userDetails = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserdetail";

    //获取用户信息单独写一个方法；session   cookie；
    //获取用户信息 存储session
    public function getUserInfoSession(){
        $tooenk = $this->tokenk;
        $tooenkAccss = http_request($tooenk);
        $took = json_decode($tooenkAccss,true);
        $code = input('code');
        $url = $this->url."?access_token=".$took['access_token']."&code=".$code;
        $userPz = http_request($url);
        $tookUserPz = json_decode($userPz,true);
        $cs=$this->userDetails."?access_token=".$took['access_token'];
        $userInfo=http_request($cs,json_encode(array('user_ticket'=>$tookUserPz['user_ticket'])));
        $jsonInfi = json_decode($userInfo,true);
        if(session("userIdBaseData") == ""){
            session("userIdBaseData",$jsonInfi['userid']);
        }else{
            session("userIdBaseData");
        }
        /*存储头像*/
        if(session("userTouxiang") == ""){
            session("userTouxiang",$jsonInfi['avatar']);
        }else{
            session("userIdBaseData");
        }
        /*存储姓名*/
        if(session("userNameData") == ""){
            session("userNameData",$jsonInfi['name']);
        }else{
            session("userIdBaseData");
        }
    }

    //获取用户信息 存储Cookie
    public function getUserInfoCookie(){
        $tooenk = $this->tokenk;
        $tooenkAccss = http_request($tooenk);
        $took = json_decode($tooenkAccss,true);
        $code = input('code');
        $url = $this->url."?access_token=".$took['access_token']."&code=".$code;
        $userPz = http_request($url);
        $tookUserPz = json_decode($userPz,true);
        $cs=$this->userDetails."?access_token=".$took['access_token'];
        $userInfo=http_request($cs,json_encode(array('user_ticket'=>$tookUserPz['user_ticket'])));
        $jsonInfi = json_decode($userInfo,true);
        if(Cookie::get("userIdBaseData") == ""){
//            Cookie::set('userIdBaseData',$jsonInfi['userid'],3600);
            Cookie::forever('userIdBaseData',$jsonInfi['userid']);
        }else{
            Cookie::set("userIdBaseData",'');
        }
        /*存储头像*/
        if(Cookie::get("userTouxiang") == ""){
//            Cookie::set("userTouxiang",$jsonInfi['avatar'],3600);
            Cookie::forever('userTouxiang',$jsonInfi['avatar']);
        }else{
            Cookie::set('userTouxiang','');
        }
        /*存储姓名*/
        if(Cookie::get("userNameData") == ""){
//            Cookie::set('userNameData',$jsonInfi['name'],3600);
            Cookie::forever('userNameData',$jsonInfi['name']);
        }else{
            Cookie::set('userNameData','');
        }
    }
    //销售任务//当前月完成情况
    public function index()
    {
        // $userId = session('userIdBaseData');  //session获取用户信息
        $userId=Cookie::get('userIdBaseData'); //cookie获取用户信息
        if(empty($userId)){
        // $this->getUserInfoSession();
            $this->getUserInfoCookie();
        }else{
            $ywyInfo=Db::connect('db2')
                ->table('oa_userinfo')
                ->where(['userId' => $userId])
                ->field('yewuName')
                ->find();
            $ywy=$ywyInfo['yewuName'];
            $date=date('Y-m-d');
            $year=substr($date,0,4);
            $month=substr($date,5,2);
            $goalMoney=Db::table('bb_ywyrenw')
                ->where(['ywy' => $ywy,'nian' => $year,'yue' => $month])
                ->field('renwje')
                ->find();
            $goalMoney=$goalMoney['renwje'];
            $finishMoney=Db::table('pf_ckhz')
                ->where(['ywy' => $ywy,'kaiprq' =>$date])
                ->sum('je');
            $this->assign('goalMoney',$goalMoney);
            $this->assign('ywy',$ywy);
            $this->assign('finishMoney',$finishMoney);
            return $this->fetch();
        }
    }

    //每日销售汇总
    public function dayCount(){
        // $userId = session('userIdBaseData');  //session获取用户信息
        $userId=Cookie::get('userIdBaseData'); //cookie获取用户信息
        if(empty($userId)){
        // $this->getUserInfoSession();
            $this->getUserInfoCookie();
        }else{
            $ywyInfo=Db::connect('db2')
                ->table('oa_userinfo')
                ->where(['userId' => $userId])
                ->field('yewuName')
                ->find();
            $ywy=$ywyInfo['yewuName'];
            $date=date('Y-m');
            $BeginDate=date('Y-m-01', strtotime(date($date)));
            $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
            $daylist=$this->get_daylist($BeginDate,$endDate);
            $saleData=[];
            foreach($daylist as $k =>$v){
                $saleinfo=Db::table('pf_ckhz')
                    ->where(['ywy' => $ywy,'kaiprq' =>$v])
                    ->sum('je');
                $saleData[$k]['saledata']=$saleinfo?$saleinfo:0;
                $saleData[$k]['daylist']=$v;
            }
            $this->assign('saleData',$saleData);
            $this->assign('ywy',$ywy);
            return $this->fetch();
        }
    }

    //回款任务
    public function nopayTask(){
        // $userId = session('userIdBaseData');  //session获取用户信息
        $userId=Cookie::get('userIdBaseData'); //cookie获取用户信息
        if(empty($userId)){
        //  $this->getUserInfoSession();
            $this->getUserInfoCookie();
        }else{
            $ywyInfo=Db::connect('db2')
                ->table('oa_userinfo')
                ->where(['userId' => $userId])
                ->field('yewuName')
                ->find();
            $ywy=$ywyInfo['yewuName'];
            $date=date('Y-m');
            $BeginDate=date('Y-m-01', strtotime(date($date)));
            $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
            $daylist=$this->get_daylist($BeginDate,$endDate);
            $noPayData=[];
            foreach($daylist as $k =>$v){
                $sql="select sum(unpayMoney) as 'nopaidMoney'  
                  FROM (select c.rq,a.djbh,b.danwbh,b.dwmch,c.ywy,c.kpman,
                  sum(a.hsje) as 'soldMoney',sum(a.yisfje) 'paidMoney', 
                  sum(a.hsje-a.yisfje) as 'unpayMoney' 
                  from mxysyf a(nolock),mchk b(nolock),pf_ckhz  c(nolock)
                  where a.dwbh=b.dwbh and a.djbh=c.djbh and a.dwbh=c.dwbh
                  and a.djbh like 'XS%' 
                  and a.jieqing='否'
                  group by c.rq,a.djbh,b.danwbh,b.dwmch,c.ywy,c.kpman) teares 
                  where ywy = '".$ywy."' and rq = '".$v."'";
                $saleinfo=Db::query($sql);
                $noPayData[$k]['nopay']=$saleinfo[0]['nopaidMoney']?$saleinfo[0]['nopaidMoney']:0;
                $noPayData[$k]['daylist']=$v;
            }
            $this->assign('noPayData',$noPayData);
            $this->assign('ywy',$ywy);
            return $this->fetch();
        }
    }


    //获取两个日期段内所有日期
    public function get_daylist($startdate,$enddate){
        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);
        $days = ($etimestamp-$stimestamp)/86400+1;
        $date = [];
        for($i=0; $i<$days; $i++){
            $date[] = date('Y-m-d', $stimestamp+(86400*$i));
        }
        return $date;
    }

    //销售数据分析最近12个月的份的销售额，增长率（具体到每个人）
    public function monthCount(){
        //需要处理登录
        //$userId = session('userIdBaseData');  //session获取用户信息
        $userId=Cookie::get('userIdBaseData'); //cookie获取用户信息
        if(empty($userId)){
        // $this->getUserInfoSession();
            $this->getUserInfoCookie();
        }else{
            $userId = session('userIdBaseData');
            $ywyInfo=Db::connect('db2')
                ->table('oa_userinfo')
                ->where(['userId' => $userId])
                ->field('yewuName')
                ->find();
            $ywy=$ywyInfo['yewuName'];
            $monthList=$this->getMonthList();
            $monthData=[];
            foreach($monthList as $k =>$v){
                $saleinfo=Db::table('pf_ckhz')
                    ->where(" ywy = '".$ywy."' and kaiprq like '".$v."%'")
                    ->sum('je');
                $monthData=[
                    'saledata' => $saleinfo?$saleinfo:0,
                    'daylist' => $v
                ];
            }
            $this->assign('ywy',$ywy);
            $this->assign('monthData',$monthData);
            return $this->fetch();
        }
    }

   // 最近12个月份的销售额增长百分比
    public function increaseRatio(){
        //需要处理登录
        //$userId = session('userIdBaseData');  //session获取用户信息
        $userId=Cookie::get('userIdBaseData'); //cookie获取用户信息
        if(empty($userId)){
            //$this->getUserInfoSession();
            $this->getUserInfoCookie();
        }else{
            $ywyInfo=Db::connect('db2')
                ->table('oa_userinfo')
                ->where(['userId' => $userId])
                ->field('yewuName')
                ->find();
            $ywy=$ywyInfo['yewuName'];
            $monthList = $this->getMonthList();
            foreach($monthList as $k =>$v){
                $saleinfo = Db::table('pf_ckhz')
                    ->where(" ywy = '".$ywy."' and kaiprq like '".$v."%'")
                    ->sum('je');
                $monthData[] = [
                    'saledata' => $saleinfo ? $saleinfo : 0,
                    'monthlist' => $v
                ];
            }
            //返回增长率和每月的月份；
            $monthRatio=[];
            if(empty($monthData) && is_array($monthData)){
                //增长百分比：（当月销售额-上月销售额）/上月销售额 *100；
                foreach ($monthData as $key => $val){
                    $keys = $key==0 ? $key : $key-1;
                    $ratio=abs($monthData[$key]['saledata']-$monthData[$keys]['saledata'])/
                        ($monthData[$keys]['saledata']==0 ? 1 : $monthData[$keys]['saledata']);
                    //把数字转化成浮点型且格式化输出两位小数
                    $ratio=sprintf("%.2f", floatval($ratio));
                    $monthRatio[]=[
                        'ratio' => $ratio,
                        'monthlist' => $val['monthlist']
                    ];
                }
            }
            $monthRatio=array_reverse($monthRatio);
            $this->assign('ywy',$ywy);
            $this->assign('monthRatio',$monthRatio);
            return $this->fetch();
        }
    }


    //获取最近12个月的列表
    public function getMonthList(){
        $arr = '';
        for ($i = 0; $i < 12; $i++){
            $arr[] = date("Y-m",strtotime("-".$i." month"));
        }
        return $arr;
    }

    //新增客户
    public function userInc(){
        //需要处理登录
        //$userId = session('userIdBaseData');  //session获取用户信息
        $userId=Cookie::get('userIdBaseData'); //cookie获取用户信息
        if(empty($userId)){
            //$this->getUserInfoSession();
            $this->getUserInfoCookie();
        }else{
            $userId = session('userIdBaseData');
            $monthList = $this->getMonthList();
            foreach ($monthList as $k => $v){
                $res=Db::connect('db2')
                    ->table('oa_userinfo')
                    ->where(" userId = '".$userId."' and dataTime like '".$v."%'")
                    ->count();
                $userData[$k]['monthlist']=$v;
                $userData[$k]['usernum']=$res;
            }
            $userData=array_reverse($userData);
            $this->assign('userData',$userData);
            return $this->fetch();
        }
    }
}