# LDAP Default Group 开发文档（v1.0.2）

## 背景与问题
- 需求：在 LDAP 同步后自动为用户设置“默认分组”，并在分组关系变化时保持一致性。
- 初始问题：
  - 仅在用户对象新增/更新时尝试设置默认分组，错过了群组关系写入时机。
  - 当管理员手动选择默认分组后，后续同步会被自动逻辑覆盖。
  - 配置页权限与 CSRF 用法不兼容，导致报错。

## 最近更改（v1.0.2 增补）
- 新增插件配置页，并提供三项开关以控制行为
- 修复权限检查：使用 `READ` 与 `UPDATE` 常量
- 修复 CSRF：表单生成 `_glpi_csrf_token`，避免二次校验导致拒绝
- 保存配置后跳转至插件列表页（`front/plugin.php`）

## 设计与实现
- 钩子注册：`setup.php`
  - `item_add`: `User`, `Group_User`
  - `item_update`: `User`
  - `item_delete`: `Group_User`
- 行为策略：仅对 LDAP 用户生效（`auths_id > 0`）。
- 默认分组选择规则：
  - 数据源：`glpi_groups_users`
  - 排序：`is_dynamic DESC, id ASC`

## 关键函数
- `plugin_ldapdefaultgroup_set_default_group($users_id, $force = false)`
  - 当 `force=false` 时，若当前默认分组仍为有效群组，则不覆盖（尊重手动选择）。
  - 当默认分组为空或已失效：
    - 有群组：按规则选择并设置。
    - 无群组：清空为 `0`。
- `plugin_ldapdefaultgroup_item_add_group_user($item)`
  - 新增群组关联时根据配置决定是否设置默认分组。
- `plugin_ldapdefaultgroup_item_delete_group_user($item)`
  - 删除的是当前默认分组时，按配置自动修复或不变。
- `plugin_ldapdefaultgroup_get_config()`
  - 读取插件配置，含默认值回退。

### 代码引用
- 用户更新钩子：`glpi_manager/plugins/ldapdefaultgroup/hook.php:30-47`
- 群组新增钩子：`glpi_manager/plugins/ldapdefaultgroup/hook.php:53-75`
- 群组删除钩子：`glpi_manager/plugins/ldapdefaultgroup/hook.php:76-97`
- 设置默认分组：`glpi_manager/plugins/ldapdefaultgroup/hook.php:98-157`

## 配置页面
- 路径：`front/config.form.php`
- 权限：`READ`/`UPDATE`
- CSRF：隐藏字段 `_glpi_csrf_token = Session::getNewCSRFToken()`
- 选项：
  - `only_when_empty`（默认1）：仅在默认分组为空或失效时设置
  - `override_manual`（默认0）：允许覆盖手动默认分组
  - `repair_on_delete`（默认1）：删除当前默认分组关联时自动修复

### 保存与重定向
- 保存成功后通过 `Html::redirect($CFG_GLPI['root_doc'].'/front/plugin.php')` 返回插件列表页

## 验证用例
- 用例1：用户属于多个群组，手动选择非首位群组为默认分组 → 同步后不覆盖（`only_when_empty=1`）。
- 用例2：删除当前默认分组的群组关系 → 自动切换至剩余群组中首选或清空（`repair_on_delete=1`）。
- 用例3：首次导入或默认分组为空 → 自动按规则设置默认分组。
- 用例4：强制覆盖模式 → 同步后始终按“动态优先、首位普通”重置（`override_manual=1`）。

## 版本记录
- v1.0.2
  - 新增配置页与三项配置开关
  - 尊重手动默认分组（仅在为空或失效时自动设置）
  - 删除默认分组关联时自动修复
  - 修复权限与 CSRF 问题
- v1.0.1：兼容性修复
- v1.0.0：初始版本实现

## 注意事项
- 仅对 LDAP 用户应用逻辑（`auths_id`）。
- 更新 `User` 使用 `_no_history` 防止循环触发。
- 避免在非必要情况下覆盖管理员手动选择。

## 已知坑与建议
- CSRF 令牌为一次性，表单提交后需刷新页面以生成新令牌；不要在插件页面中重复调用 `Session::checkCSRF()`，核心会自动校验。
- 如果遇到“你的会话已过期。请重新登录。”，需重新登录后再进行保存或同步操作。
