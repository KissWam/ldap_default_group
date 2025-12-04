# 变更日志

## v1.0.2
- 新增插件配置页面与三项配置开关
- 尊重手动默认分组（仅在为空或失效时自动设置）
- 删除默认分组关联时自动修复
- 修复配置页权限与 CSRF 的错误
- 补充 `locales/` 多语言文件与 `LICENSE`
- 规范 `plugin_ldapdefaultgroup_check_config($verbose=false)` 签名
- 提供插件目录 XML 元数据文件 `ldapdefaultgroup.xml`

## v1.0.1
- 更新作者信息显示
- 修复兼容性问题

## v1.0.0
- 初始版本发布
- 实现 LDAP 用户同步时自动设置默认分组功能
- 支持优先选择动态群组
