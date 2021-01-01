<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/10/11
 * Time: 16:52
 */
return [
    //菜单
    'mine' => '我的',
    'find' => '发现',
    'tradingcenter' => '交易中心',
    'nodepower' => '节点算力',
    'home' => '首页',

    //注册
    'register' => '注册',
    'username' => '用户名',
    'mail' => '邮箱',
    'password' => '登录密码',
    'password2' => '确认登录密码',
    'paypwd' => '交易密码',
    'paypwd2' => '确认交易密码',
    'itcode' => '邀请码',
    'yzcode' => '验证码',
    'sendcode' => '发送验证码',
    'yesregister' => '确认注册',
    'codetip' => '请输入四位的验证码',
    'registersuccess' => '注册成功',

    //登录

    'lang' => '切换语言',
    'login' => '登录',
    'fgpassword' => '忘记密码',
    'downloadapp' => '下载App',
    'chinese' => '简体中文',
    'english' => '英文',
    'confirmpassword' => '确认密码',
    'change' => '确认修改',

    //币种
    'fc' => config('site.credit1_text'),
    'usdt' => config('site.credit2_text'),


    //我的
    'myassets' => '我的资产',
    'myincome' => '我的收益',
    'invitefriends' => '邀请好友',
    'myteam' => '我的团队',
    'flash' => '闪兑',
    'mycp' => '我的算力套餐',
    'onlinemessage' => '在线留言',
    'personalcenter' => '个人中心',
    'helpcenter' => '帮助中心',
    'aboutus' => '关于我们',
    //我的资产
    'usdtnum' => config('site.credit2_text').'数额',
    'fcnum' => config('site.credit1_text').'币数额',
    'lockfcnum' => '锁定'.config('site.credit1_text').'代币数额',
    'assetbreakdown' => '资产明细',
    'recharge' => '充值',
    'withdraw' => '提现',
    //资产明细
    'type' => '类型',
    'quantity' => '数量',
    'time' => '时间',
    'lockfc' => '锁定'.config('site.credit1_text').'币',
    //充值
    'rechargerecord' => '充值记录',
    'myqrcode' => '我的钱包二维码',
    'note' => '注意',
    'notecontent' => '请用第三方软件扫描钱包二维码进行充值',
    //充值记录
    'walletaddress' => '钱包地址',
    'status' => '状态',
    'rechargetime' => '充值时间',
    'success' => '成功',
    'fail' => '失败',
    //提现
    'withdrawalsrecord' => '提现记录',
    'pswc' => '请选择提现币种',
    'extractablequantity' => '可提取数量',
    'penw' => '请输入提取数量',
    'mywalletaddress' => '我的钱包地址',
    'penwa' => '请输入钱包地址',
    'applicationextraction' => '申请提取',
    //提现记录
    'cashwithdrawaltime' => '提现时间',
    //我的收益
    'yesterdayearnings' => '昨日收益',
    'n7doi' => '近7天收益',
    'cumulativeincome' => '累积收益',
    'day' => '天',
    'incomeoverview' => '收益总览',
    'switchstaticrevenue' => '切换静态收益',
    'switchdynamicrevenue' => '切换动态收益',
    'alldynamicrevenue' => '所有动态收益',
    'communitycomputingaward' => '社群算力奖',
    'nodereward' => '节点奖励',
    'directaward' => '直推奖',
    'bonustype' => '奖金类型',
    'bonusnum' => '奖金数额',
    'nownum' => '当前总数',
    'total' => '总计',
    //邀请好友
    'shareqrcode' => '分享二维码',
    'sqrcoderegistration' => '扫描二维码注册',
    'invitationcode' => '邀请码',
    'iosdownloadcode' => 'IOS下载码',
    'androiddownloadcode' => '安卓下载码',
    'slogan' => '邀请好友一起注册一款基于区块链的应用吧',
    //我的团队
    'totalteamdeposit' => '团队押金总数',
    'numberofteams' => '团队人数',
    'directpush' => '直推',
    'push' => '间推',
    'name' => '姓名',
    'mailbox' => '邮箱',
    'grade' => '等级',
    'totalperformance' => '总业绩',
    'registrationtime' => '注册时间',
    'people' => '人',
    //闪兑
    'redemptionrecord' => '兑换记录',
    'tokenbalance' => '代币余额',
    'petq' => '请输入数量',
    'determine' => '确定',
    //兑换记录
    'redemptiontime' => '兑换时间',
    //我的算力套餐
    'tctotalmoney' => '算力套餐押金总额',
    'totalrevenue' => '合计收益',
    'all' => '全部',
    'depositamount' => '押金金额',
    'startingtime' => '开始时间',
    'residualincomedays' => '剩余收益天数',
    'earnedgains' => '已获收益',
    'income' => '收益中',
    'expired' => '已过期',
    //在线留言
    'submitquestion' => '提交问题',
    'questionnumber' => '问题编号',

    //个人中心
    'nickname' => '昵称',
    'recommendedID' => '推荐ID',
    'bindmailboxnumber' => '绑定邮箱号',
    'changeloginpassword' => '修改登录密码',
    'changetransactionpassword' => '修改交易密码',
    'withdrawaladdress' => '提现地址',
    'signout' => '退出登录',

    //修改登录密码
    'oldpassword' => '旧密码',
    'confirmchanges' => '确认修改',

    //提现地址
    'newaddress' => '新提现地址',
    'peanwa' => '请输入新提现地址',
    'confirmationaddress' => '确认地址',
    'peaca' => '请输入确认地址',
    'pevc' => '请输入验证码',
    'send' => '发送',

    //帮助中心
    'commonproblem' => '常见问题',
    'listquestions' => '问题列表',
    'problemdetails' => '问题详情',

    //发现
    'consultationdetails' => '咨询详情',

    //交易中心
    'mypay' => '我的买单',
    'mysellorder' => '我的卖单',
    'buy' => '买入',
    'sell' => '卖出',
    'tplc' => '交易价格折线图',
    'date' => '日期',
    'newbuyorder' => '新建买单',
    'newsellorder' => '新建卖单',
    //我的买单
    'created' => '已创建',
    'pendingpayment' => '待付款',
    'completed' => '已完成',
    'ordernumber' => '订单号',
    'nomore' => '没有更多了',
    //我的卖单
    'pendingtransaction' => '待交易',
    'tobeconfirmed' => '待确认',
    //买入
    'paycenter' => '买单中心',
    'purchasecurrency' => '求购币数',
    'unitprice' => '单价',
    'totalamount' => '总金额',
    'sellnow' => '立即卖出',
    'confirm' => '确定',
    'cancel' => '取消',
    'ptpassword' => '请输入交易密码',
    'fytpassword' => '忘记交易密码',
    'tpcbe' => '交易密码不能为空',
    //卖出
    'sellingcenter' => '卖单中心',
    'nocss' => '出售币数',
    'fee' => '手续费',
    'buynow' => '立即买入',

    //节点算力
    'nodepowerplan' => '节点算力套餐',
    'price' => '价格',
    'packagecycle' => '套餐周期',
    'dailyincome' => '日收益',
    'purchase' => '购买',
    'cpd' => '算力套餐详情',
    'contractdescription' => '合约说明',
    'riskintroduction' => '风险介绍',
    'disclaimer' => '免责声明',
    'ihaveread' => '我已阅读',
    'purchaseagreement' => '购买协议',
    'rentnow' => '立即租用',

    //首页
    'announcement' => '公告',
    'more' => '更多',
    'homecarouselmap' => '首页轮播图',
    'fcunitprice' => config('site.credit1_text').'单价',
    'freezerevenue' => '冻结收益',
    'fctokencirculation' => config('site.credit1_text').'代币流通量',
    'recommend' => '推荐',
    'latestmarket' => '最新行情',
    'statement' => '数据来源HooBit交易币，仅供参考',
    'bulletinboard' => '公告栏',

];