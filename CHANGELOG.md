## 更新日志

### v0.10.0(2019-09-05)

- 修复 bug
- 更新前端组件
- 整理部署过程，可推广


### v0.9.0(2019-09-04)

- 重构大量功能逻辑。
- 调整工作流程，新增`ipbeian.html`及相关操作。

### v0.8.16(2019-09-03)

继续调整代码，与旧仓库分支 `v0.9.0_preview` 合并。待验证。

**todo**

- `Generateor.php` 导出模板调整。取消从前端获取模板的 header。

### v0.8.10(2019-08-29)

修改大量参数，提取到自定义配置`public/static/personalized_config.php`中，便于修改。

- 调整参数，便于推广应用。

**todo**

- 更新 3 个系统导出模板。
- 对比旧仓库的 v0.9.0 分支，合并更新。

### v0.8.3(2019-08-21)

精简项目文件，仅保留[zx_apply]模块，并重命名为[zx_fuzhu]

- 由于修改模块名称，可能存部分 bug
- 提取[zx_fuzhu]模块的文件，移除大量无用文件。
- 整合 `public/static` 内的静态文件，添加到本项目中。
- 移除部分敏感数据

#### v0.8.2(2018-10-09)

[zx_apply]模块修复 bug

- 更新状态字段 status 为
```
0： 刚申请
1： 预分配
2： IDC已备等待录资管等待工信部IP备案
3： 已录资管&工信部已备等待IDC备案
4： 等待做数据  
5： 已完成   
```
- 修复填表单时，若`neFactory`选为 ONU，对`aStation`字段的要求。（`_formStructure.html`中的`fixExtraFields()、onChangeHandler()`函数）
```
涉及的页面有
index/apply.html
index/query.html
manage/todo.html
manage/query.html
```

### v0.8.0(2018-09-25)

[zx_apply]模块添新功能

- **`todo.html`分配 IP、vlan 提交后，完美实现自动生成 idc.isp 模板数据，并做为邮件附件发送**
- 优化 idc.isp 邮件的正文内容（`Manage.php=>sendResultEmail()`）
    > 已修改为`Manage.php=>sendBeiAnResultEmail()`
- 优化`todo.html`页面提交时，冲突/错误的处理逻辑及显示

**TODO**

- IP 申请与 IDC.ISP 备案信息填写分离
- 新增客户经理填写备案信息的逻辑，填写后直接发送备案邮件。

#### v0.7.19(2018-09-21)

[zx_apply]模块修复 bug

- 修复`apply.html`选择为 ONU 后无法提交的 bug

#### v0.7.17(2018-09-10)

[zx_apply]模块修复 bug

- 更新 docs
- 修复`修改申请`报错 bug
- 修复`todo.html`分配后`vlan`无记录的 bug
- 修复其他若干 bugs

#### v0.7.12(2018-08-27)

[zx_apply]模块修复 bug

- `todo.html`和`index/query.html`编辑旧数据时自动修补新增的额外字段信息
- 修改自动修补新增的额外字段的  bugs

#### v0.7.11(2018-08-23)

[zx_apply]模块更新

- 自动生成 idc.isp 模板数据，并做为邮件附件发送
- 导出 excel 功能可选择使用前端或者后端
- 调整 LECENSE
- fix [#3](https://github.com/thianda/phpweb/issues/3), fix [#4](https://github.com/thianda/phpweb/issues/4)
- 修改若干 bugs
- `index/query.html`右键新增`额外再申请1个IP`功能
- 同 vlan 提醒后确认，可强制录入
- ONU 业务在`todo.html`可显示当前所处状态（在 vlan 字段上方）

#### v0.7.3(2018-08-05)

[zx_apply]模块更新

- 完善前端导出 excel 功能
- 分离部分`Manage.php`的操作到`Generator.php`
- `Infotables`新增 5 个额外字段（extra）
- 调整`apply.html`表单字段位置，新增字段 4 个，隐藏无需编辑的字段 4 个

### v0.7.0(2018-08-03)

[zx_apply]模块更新

- 导出 excel 功能全部迁移到前端

TODO

-  php 文件读写，发邮件带附件
-  同 vlan 强制录入

#### v0.6.7(2018-08-03)

[zx_apply]模块更新

- todo提交后发送邮件给客户经理和申请人
- close [#6](https://github.com/thianda/phpweb/issues/6)

#### v0.6.6(2018-08-01)

[zx_apply]模块更新

- 代码仓库脱敏处理，永久删除敏感信息
- 细节调整

#### v0.6.2(2018-07-27)

[zx_apply]模块更新

- 修复搜索bug [#5](https://github.com/thianda/phpweb/issues/5).

### v0.6.0(2018-07-13)

[zx_apply]模块更新

- 实现ONU业务申请时无需`A端基站`，先预分配ip不分配vlan，待做ONU上线数据时再选择`A端基站`并分配`vlan`。
- apply.html申请、query.html修改申请、todo代办页面同步修改表单动态逻辑
- 修改`Vlantables::check()`逻辑，优化存在`vlan`但不存在`aStation`时的结果

#### v0.5.5(2018-07-13)

[zx_apply]模块更新

- 调整：申请时若为ONU业务则不需要填写`A端基站`，此规则在`修改申请`时亦生效

- 修复`manage/query.html`。在此之前，批量删除时前端显示与实际不符
- 完善vlan删除与修改功能：修改状态`[0]申请`，到待办里直接修改提交即可，若不填vlan即为删除
- 修复其他若干bug

#### v0.5.1(2018-07-12)

[zx_apply]模块代码格式化

- 格式化`zx_apply`模块的代码（php、html），遵循PSR-2
- 未知原因导致部分代码丢失，已补回

### v0.5.0(2018-07-11)

[zx_apply]模块部分重构

- 前端部分重构
- 修复申请填表单的bug
- 需进一步调整

#### v0.4.18(2018-07-11)

[zx_apply]模块更新

- 更新资管导入模板
- 优化`XLSX`组件的加载逻辑
- 计划将`导出excel操作`移至前端，加速导出速度，兼容性更强
- 修复细节bug

#### v0.4.16(2018-07-05)

[zx_apply]模块更新

- 修改`index/query.html`中`afterChange`事件的bug
- 新增ip列表生成：`tool/swscript.html`

#### V0.4.13(2018-06-28)

[zx_apply]模块更新

- `manage/query.html`新增批量已完成功能，批量选取设置状态为[4]:已完成
- 导出资管模板数据时提示批量修改状态为[2]:已录资管等待IP备案

TODO：`更多操作`页面新功能：统计空闲ip

> 需基于专线类型，细节有待研究

#### V0.4.12(2018-06-26)

[zx_apply]模块修复若干bug

- 完善细节功能，修复大量bug
- 支持按照`专线类型`进行`全局搜索`以及`基本信息查询`

#### v0.4.9(2018-06-12)

- 用户可右键选择`修改申请`，修改个别字段信息后重新提交
- 用户申请提交前自动清除每个字段里的空格并给出提示

#### v0.4.8(2018-06-06)

[zx_apply]模块bug修复

- 累计优化多处UI布局
- `todo`页提示同客户名的台账信息
- `apply`页提交前过滤空格符
- 模板导出时文件名的时间字段放后面

#### v0.4.6(2018-05-29)

[zx_apply]模块更新

- 视图显示优化、安全事件逻辑优化
- 显示和导出全量数据可基于专线类别筛选，申请与待办暂未实现
- 查询、导出操作均记录log

#### V0.4.5(2018-05-24)

[zx_apply]模块bug修复

- 修复ip分配时的小bug
- 调整查询页面排序为`status,create_time desc`
- 更新资管系统导出模板
- 调整各种模板导出时文件名的命名规则

#### v0.4.4(2018-05-18)

[zx_apply]模块大量更新。

- 待办页`vlan`字段不再是必填
- 待办页“自动预分配”结果显示由`alert`改为`window`
- 查询页显示详细信息布局优化
- 查询页取消显示备注列
- 调整待办通知的邮件内容
- 申请人查询页可查询历史数据的基本信息（去除客户联系方式等敏感信息，并限制查询次数）

#### v0.4.3(2018-05-16)

- [zx_apply]模块预分配ip后给申请人自动发送邮件提醒（手动判断是否发送）

#### v0.4.2(2018-05-11)

#### v0.4.1(2018-05-04)

[zx_apply]模块开始公测使用

- 添加`jsSheet`组件，用于zx_apply前端导出全量台账的xlsx文件
- 敏感配置信息独立存储

### v0.4.0(2018-04-02)

[zx_apply]模块大量更新。

- `Infotables`增加 ip掩码和ipB掩码，互联网ip掩码默认32，其他默认30，所有ipB掩码默认29
- 取消`Iptables`数据表，保留其model类及方法
- 定位为辅助平台，提供有限的台账维护功能。主要功能为限制数据录入的规范性。导出为各项工作所需的数据。包含但不限于：
  - 台账格式
  - 资管流程模板格式
  - 做数据脚本
  - ip备案模板格式（2种）
- 管理员查询页面可右键调用数据制作脚本生成、资管系统录入模板、集团IP备案导入模板、工信部IP备案导入模板功能。
- 更新`handsontable`组件到v1.18.1/v0.38.1

### v0.3.2(2018-01-31)

- 更新`dhtmlx`组件到5.1.0
- 修复esserver的bug
- 添加系统log记录功能
- 添加zx_apply模块的登陆控制

#### v0.2.2.0108(2018-01-08)

- 更新`/docs/_posts/2017-12-21-handsontable-usage-notes.md`内容

> 总结的自己脑袋都乱了。官网这个[docs](https://docs.handsontable.com)真的一点也不友好。

- 更新zx_apply核心代码

#### v0.2.2.0107(2018-01-07)

- 更新`/docs/_posts/2017-12-21-handsontable-usage-notes.md`内容
- 添加`handsontable`作为grid组件。与`dhtmlXGrid`相比的优点：
  - 有配置默认值，上手更简单
  - UI更接近Excel，功能也更近似Excel
  - 可全局搜索，性能良好

#### v0.2.2(2017-12-22)

- 更新docs入口为项目名，可在github pages自动发布
- 添加静态资源handsontable
- 更新docs开发文档样式，修复几处bug

### v0.2.0(2017-12-18)

- 添加docs，记录开发文档
- 大量微调整

### v0.1.0(2017-11-16)

开始托管于github，整合多个小项目（checkME60、esserver、kxeams、trouble、zx_apply）为本项目的子模块。

- initial release

### v0.0.1(forgotten timeline)

```
2017-09-14 15:17:27	zx_apply 模块，apply.html 36行 zxInfoTitle

2017-05-03 17:09:57	密码找回功能细节更新。

2017-04-25 17:04:45	todo：resetpwd.html 暂不设置myFrom的自定义validate，密码找回功能基本完成。kxeams项目暂停。

2017-04-17 17:37:58	todo：resetpwd.html 设置myFrom的自定义validate。提交post前校验 （行44），esserver/Controller/index.php 行128:更新密码操作

2017-04-12 17:40:21	todo：暂不支持 领用申请 点击再领一个后删除。今后可以使用sessionStorage 保存，刷新页面后再自动填写来实现。

2017-04-10 17:43:47	todo：user_apply.html	removeAcc() 时，id问题：myAcc.cells(id).setText(text);以及Controller/User->apply()的数据接收问题。

2017-04-07 02:00:33	修改大量架构。功能逻辑。可能有bug，待测试。todo： manage/index，manage/loglist。todo.html 操作 领用地点or 接收地点 判断显示。

2017-04-06 23:25:46	修复bug。优化 申请/领用/审批流程。{$_SERVER["REQUEST_URI"]}

2017-04-06 17:18:10	完成整合，完成部分架构整改。todo：领用时点击再领一个后无法减少再领一个。 优化修改库存统计算法。（Manage/index 方法）

2017-03-31 17:37:51	整合kxeams项目进到phpweb完成。

2017-03-30 17:37:34	架构调整(未完待续)

2017-03-24 18:34:01	整合kxeams项目进到phpweb里的一分支。（未完待验证）

----------------------------------------
###	manage/index 新增 删除 item 功能（无删除数据库操作。）

###	尝试 使用 dataview 组件，代替 grid 组件来显示数据

###	2017-01-10_all，kxeams，manage，new_change.html 行155。

###	2017-01-11 17:10:05 	new_changev2.html 行81.

###	2017-01-11 17:10:05 	new_changev2.html 新入库 不在 生产厂商 列做限制

###	2017-01-19 17:00:55	new_changev2.html 行205. 处理 From 数据到 Grid

###	2017-01-22 17:04:18	new_changev2.html 行240. 处理 From 数据到 Grid完毕。继续编写批量录入。

###	2017-2-7 01:28:59	bugReport.html 行 41，继续改进。

###	2017-02-07 15:59:52	User/todo.html 初始化时需要区分县区来查询显示。
				User/apply	三级领用需要填写使用去向，（提示填写在备注里。）

###	2017-2-14 01:03:26	todo：	在asset_user中添加一个字段，关联Excel服务器的数据库。
					编写phpweb项目里的模块用来连接SQLServer验证账号。

###	2017-2-17 02:22:33	新增phpweb首页导航样式，新增PHPmail发送邮件功能。

###	2017-02-20 04:00:26	phpweb/esserver/index/resetpwd 发邮件无法显示a标签，理清resetpwd和sendemail大逻辑区别
				1) 发邮件操作。 2) 重置密码操作（验证URL有效期）。 3) 输入密文重置密码操作(暂不开发)

###	2017-02-20 17:51:00	resetpwd.html 页面设计
```