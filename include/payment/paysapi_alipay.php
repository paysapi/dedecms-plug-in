<?php
if (!defined('DEDEINC')) exit('Request Error!');

class PaysapiAlipay
{
    var $dsql;
    var $mid;
    var $return_url = "/plus/carbuyaction.php?dopost=return";

    function PaysapiAlipay()
    {
        global $dsql;
        $this->dsql = $dsql;
    }

    function __construct()
    {
        $this->PaysapiAlipay();
    }

    function SetReturnUrl($returnurl = '')
    {
        if (!empty($returnurl)) {
            $this->return_url = $returnurl;
        }
    }
    
    function getOrderType($orderId)
    {
        if (preg_match("/S-P[0-9]+RN[0-9]/", $orderId)) {
            return 'goods';
        } else if (preg_match("/M[0-9]+T[0-9]+RN[0-9]/", $orderId)) {
            return 'member';
        } else {
            return 'unknown';
        }
    }

	//开始支付
    function GetCode($order, $payment)
    {
        global $cfg_basehost, $cfg_cmspath;
        if (!empty($cfg_cmspath)) $cfg_basehost = $cfg_basehost . $cfg_cmspath;
        $notify_url = $cfg_basehost . $this->return_url . "&code=" . $payment['code'];
        $return_url = $notify_url . "&order_id=" . $order['out_trade_no'];
        
        $istype = 1;
        $order_id =  $order['out_trade_no'];
		$price = (int)$order['price'];
		$uid=  $payment['paysapi_uid'];
		$token = $payment['paysapi_token'];
		$goodsname = '支付单号:' . $order['out_trade_no'];
		$orderuid = "";
		$key = md5($goodsname . $istype . $notify_url . $order_id . $orderuid . $price . $return_url . $token . $uid);
		$url = "https://pay.bearsoftware.net.cn?key="
				.$key."&notify_url=".urlencode($notify_url)
				."&orderid=".$order_id
				."&orderuid=".$orderuid
				."&return_url=".urlencode($return_url)
				."&goodsname=".$goodsname
				."&istype=".$istype
				."&uid=".$uid
				."&price=".$price;
                    
		/* 清空购物车 */
		require_once DEDEINC . '/shopcar.class.php';
		$cart = new MemberShops();
		$cart->clearItem();
		$cart->MakeOrders();

		return '<a href="' . $url . '">立即支付</a>';
        
    }
    
    //支付回调
    function respond()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            /* 引入配置文件 */
            $code = preg_replace("#[^0-9a-z-]#i", "", $_GET['code']);
            require_once DEDEDATA . '/payment/' . $code . '.php';
            
            $token = $payment['paysapi_token'];
            $paysapi_id = $_POST['paysapi_id'];
            $orderId = $_POST['orderid'];
            $price = $_POST['price'];
            $realprice = $_POST['realprice'];
            $orderuid = $_POST['orderuid'];
            $key = $_POST['key'];

            $temps = md5($orderId . $orderuid . $paysapi_id . $price . $realprice . $token);
            // echo $token."|";
            // echo $temps."|";
            // echo $key."|";

            //检查签名
            if ($temps != $key) {
                echo 'SIGN ERROR';
                die();
            }           
            
            $orderType = $this->getOrderType($orderId);
            if ($orderType == 'goods') {
                $row = $this->dsql->GetOne("SELECT * FROM #@__shops_orders WHERE `oid`= '{$orderId}'");
                $this->mid = $row['userid'];
                if($this->success_db($orderId)) {
                    echo 'SUCCESS';
                    die();
                }

                echo 'update success db error';
                die();
            }
            if ($orderType == 'member') {
                $row = $this->dsql->GetOne("SELECT * FROM #@__member_operation WHERE `buyid`= '{$orderId}'");
                if (!is_array($row) || $row['sta'] == 2) {
//                    return $msg = "您的订单已经处理，请不要重复提交!";
                    echo 'SUCCESS';
                    die();
                }
                $product = $row['product'];
                $pname = $row['pname'];
                $pid = $row['pid'];
                $this->mid = $row['mid'];
                $this->success_mem($orderId, $pname, $product, $pid);
                echo 'SUCCESS';
                die();
            }
            echo '暂不支持此类型的支付！';
            die();
        } else {
            // 结果页面
            $orderId = trim(addslashes($_GET['order_id']));
            $orderType = $this->getOrderType($orderId);
            if ($orderType == 'goods' || $orderType == 'member') {
                return $msg = "支付成功!<br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
            }
            return $msg = "支付失败，您的订单号有问题！";
        }
    }

    /*处理物品交易*/
    function success_db($order_sn)
    {
        //获取订单信息，检查订单的有效性
        $row = $this->dsql->GetOne("SELECT `state` FROM #@__shops_orders WHERE `oid`='$order_sn' ");
        if ($row['state'] > 0) {
            return TRUE;
        }
        /* 改变订单状态_支付成功 */
        $sql = "UPDATE `#@__shops_orders` SET `state`='1' WHERE `oid`='$order_sn' AND `userid`='" . $this->mid . "'";
        if ($this->dsql->ExecuteNoneQuery($sql)) {
            $this->log_result("verify_success,订单号:" . $order_sn); //将验证结果存入文件
            return TRUE;
        } else {
            $this->log_result("verify_failed,订单号:" . $order_sn);//将验证结果存入文件
            return FALSE;
        }
    }

    /*处理点卡，会员升级*/
    function success_mem($order_sn, $pname, $product, $pid)
    {
        //更新交易状态为已付款
        $sql = "UPDATE `#@__member_operation` SET `sta`='1' WHERE `buyid`='$order_sn' AND `mid`='" . $this->mid . "'";
        $this->dsql->ExecuteNoneQuery($sql);

        /* 改变点卡订单状态_支付成功 */
        if ($product == "card") {
            $row = $this->dsql->GetOne("SELECT `cardid` FROM #@__moneycard_record WHERE `ctid`='$pid' AND `isexp`='0' ");;
            //如果找不到某种类型的卡，直接为用户增加金币
            if (!is_array($row)) {
                $nrow = $this->dsql->GetOne("SELECT `num` FROM #@__moneycard_type WHERE `pname`= '{$pname}'");
                $dnum = $nrow['num'];
                $sql1 = "UPDATE `#@__member` SET `money`=`money`+'{$nrow['num']}' WHERE `mid`='" . $this->mid . "'";
                $oldinf = "已经充值了" . $nrow['num'] . "金币到您的帐号！";
            } else {
                $cardid = $row['cardid'];
                $sql1 = " UPDATE #@__moneycard_record SET `uid`='" . $this->mid . "',`isexp`='1',`utime`='" . time() . "' WHERE `cardid`='$cardid' ";
                $oldinf = '您的充值密码是：<font color="green">' . $cardid . '</font>';
            }
            //更新交易状态为已关闭
            $sql2 = " UPDATE #@__member_operation SET `sta`=2,`oldinfo`='$oldinf' WHERE `buyid`='$order_sn'";
            if ($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2)) {
                $this->log_result("verify_success,订单号:" . $order_sn); //将验证结果存入文件
                return $oldinf;
            } else {
                $this->log_result("verify_failed,订单号:" . $order_sn);//将验证结果存入文件
                return "支付失败！";
            }
            /* 改变会员订单状态_支付成功 */
        } else if ($product == "member") {
            $row = $this->dsql->GetOne("SELECT `rank`,`exptime` FROM #@__member_type WHERE `aid`='$pid' ");
            $rank = $row['rank'];
            $exptime = $row['exptime'];
            /*计算原来升级剩余的天数*/
            $rs = $this->dsql->GetOne("SELECT `uptime`,`exptime` FROM #@__member WHERE `mid`='" . $this->mid . "'");
            if ($rs['uptime'] != 0 && $rs['exptime'] != 0) {
                $nowtime = time();
                $mhasDay = $rs['exptime'] - ceil(($nowtime - $rs['uptime']) / 3600 / 24) + 1;
                $mhasDay = ($mhasDay > 0) ? $mhasDay : 0;
            }
            //获取会员默认级别的金币和积分数
            $memrank = $this->dsql->GetOne("SELECT `money`,`scores` FROM #@__arcrank WHERE `rank`='$rank'");
            //更新会员信息
            $sql1 = " UPDATE #@__member SET `rank`='$rank',`money`=`money`+'{$memrank['money']}',
                       `scores`=`scores`+'{$memrank['scores']}',`exptime`='$exptime'+'$mhasDay',`uptime`='" . time() . "' 
                       WHERE mid='" . $this->mid . "'";
            //更新交易状态为已关闭
            $sql2 = " UPDATE #@__member_operation SET `sta`='2',`oldinfo`='会员升级成功!' WHERE `buyid`='$order_sn' ";
            if ($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2)) {
                $this->log_result("verify_success,订单号:" . $order_sn); //将验证结果存入文件
                return "会员升级成功！";
            } else {
                $this->log_result("verify_failed,订单号:" . $order_sn);//将验证结果存入文件
                return "会员升级失败！";
            }
        }
    }

    function log_result($word)
    {
        global $cfg_cmspath;
        $fp = fopen(dirname(__FILE__) . "/../../data/payment/log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, $word . ",执行日期:" . strftime("%Y-%m-%d %H:%I:%S", time()) . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}