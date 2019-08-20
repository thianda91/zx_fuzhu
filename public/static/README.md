## 目录文件说明

本目录为静态文件。

### dhtmlx-thianda编译方法

`dhtmlxSuite_v51_std/codebase` 里为dhtmlx组件，~~收录了全部，但很多并没有使用~~

自己重新打包编译了仅自己使用的组件

> 需自己下载完整的``dhtmlxSuite_v51_std.zip`。

- 需要安装`php`以及`java`
- 进入目录`sources/libCompiler`
- 配置文件在`conf/stat_css`和`conf/stat_js`
- 执行`php lib_compiler.php`即可输出到`../../codebase/`

## 依赖组件版本

### bootstrap3

v3.3.7-2018-06-27

### dhtmlxSuite 

v5.1-2018-01-31

### handsontable

v7.1.1-2019-08-20

### sheetjs

v0.15.1-2019-08-20

## 隐藏文件

下列文件涉敏，不再版本控制中保存：

- `sampleData/` 里需要保存导出的 excel 模板。
- `email_config` 为发件箱配置。