# dedecms-sdk

## 安装插件

1, 复制 `include/payment/bapp.php` 到您的织梦CMS的 `include/payment/` 下
2, 在您的织梦CMS里面找到 `/plus/carbuyction.php` ，打开并编辑

找到

```php
$write_list = array('alipay', 'bank', 'cod', 'yeepay');
```

将$write_list增加三个内容:'paysapi_alipay', 'paysapi_alipay_nophone', 'paysapi_wechat'

变成如下面的内容

```php
$write_list = array('alipay', 'bank', 'cod', 'yeepay', 'paysapi_alipay', 'paysapi_alipay_nophone', 'paysapi_wechat');
```
  
3, 进入 「织梦CMS管理后台」-「系统」-「系统设置」-「SQL命令行工具」-「运行SQL命令行」-「单行命令」
  复制以下代码，并点击 「确认」
 
```sql
insert ignore into `dede_payment` set `code`='paysapi_alipay',`name`='支付宝',`fee`=0,`description`='paysapi提供的支付宝支付',`rank`=1,`config`='a:2:{s:12:"paysapi_uid";a:4:{s:5:"title";s:13:"paysapi.com提供的Uid";s:11:"description";s:21:"在paysapi.com:账号设置->API接口信息拿到";s:4:"type";s:4:"text";s:5:"value";s:0:"";}s:15:"paysapi_token";a:4:{s:5:"title";s:16:"paysapi.com提供的Token";s:11:"description";s:21:"在paysapi.com:账号设置->API接口信息拿到";s:4:"type";s:4:"text";s:5:"value";s:0:"";}}',`enabled`=0,`cod`=0,`online`=1;

insert ignore into `dede_payment` set `code`='paysapi_alipay_nophone',`name`='支付宝',`fee`=0,`description`='paysapi提供的支付宝(不挂机)支付',`rank`=1,`config`='a:2:{s:12:"paysapi_uid";a:4:{s:5:"title";s:13:"paysapi.com提供的Uid";s:11:"description";s:21:"在paysapi.com:账号设置->API接口信息拿到";s:4:"type";s:4:"text";s:5:"value";s:0:"";}s:15:"paysapi_token";a:4:{s:5:"title";s:16:"paysapi.com提供的Token";s:11:"description";s:21:"在paysapi.com:账号设置->API接口信息拿到";s:4:"type";s:4:"text";s:5:"value";s:0:"";}}',`enabled`=0,`cod`=0,`online`=1;

insert ignore into `dede_payment` set `code`='paysapi_wechat',`name`='微信',`fee`=0,`description`='paysapi提供的微信支付',`rank`=1,`config`='a:2:{s:12:"paysapi_uid";a:4:{s:5:"title";s:13:"paysapi.com提供的Uid";s:11:"description";s:21:"在paysapi.com:账号设置->API接口信息拿到";s:4:"type";s:4:"text";s:5:"value";s:0:"";}s:15:"paysapi_token";a:4:{s:5:"title";s:16:"paysapi.com提供的Token";s:11:"description";s:21:"在paysapi.com:账号设置->API接口信息拿到";s:4:"type";s:4:"text";s:5:"value";s:0:"";}}',`enabled`=0,`cod`=0,`online`=1;

```

  当有提示 `成功执行1个SQL语句！` 即可完成插件安装
  
## 配置参数

1. 进入 「织梦CMS管理后台」-「系统」-「系统设置」-「支付工具」-「支付接口设置」  
1. 分别找到 'paysapi_alipay', 'paysapi_alipay_nophone', 'paysapi_wechat' 后点击 「安装」  
1。 点 「更改」，填上paysapi `Uid` 和 `Token` ，并点 「确定」


------


如有任何问题联系:paysapi.work@gmail.com