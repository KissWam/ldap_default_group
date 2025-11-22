<?php

// 确保不能直接访问文件
if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly.');
}

// 确保包含必要的类
include_once(GLPI_ROOT . "/inc/user.class.php");

/**
 * 用户添加后的钩子函数
 * 当通过LDAP创建新用户时触发
 */
function plugin_ldapdefaultgroup_item_add_user($item) {
    // 只处理通过LDAP认证创建的用户
    if (!isset($item->fields['auths_id']) || $item->fields['auths_id'] == 0) {
        return true;
    }
    
    // 调用设置默认分组的函数
    plugin_ldapdefaultgroup_set_default_group($item->fields['id']);
    
    return true;
}

/**
 * 用户更新后的钩子函数
 * 当LDAP同步更新用户时触发
 */
function plugin_ldapdefaultgroup_item_update_user($item) {
    global $DB;
    
    // 只处理LDAP用户且当前没有默认分组的情况
    if (!isset($item->fields['auths_id']) || $item->fields['auths_id'] == 0) {
        return true;
    }
    
    // 检查是否已有默认分组
    if (!empty($item->fields['groups_id'])) {
        return true;
    }
    
    // 调用设置默认分组的函数
    plugin_ldapdefaultgroup_set_default_group($item->fields['id']);
    
    return true;
}

/**
 * 设置用户的默认分组为第一个群组
 * 
 * @param int $users_id 用户ID
 * @return bool
 */
function plugin_ldapdefaultgroup_set_default_group($users_id) {
    global $DB;
    
    // 查询用户所属的第一个群组（优先选择动态群组）
    $iterator = $DB->request([
        'SELECT' => ['groups_id'],
        'FROM'   => 'glpi_groups_users',
        'WHERE'  => [
            'users_id' => $users_id
        ],
        'ORDER'  => ['is_dynamic DESC', 'id ASC'],  // 优先动态群组
        'LIMIT'  => 1
    ]);
    
    if (count($iterator) > 0) {
        $row = $iterator->current();
        $groups_id = $row['groups_id'];
        
        // 更新用户的默认分组
        $user = new User();
        $result = $user->update([
            'id'        => $users_id,
            'groups_id' => $groups_id,
            '_no_history' => true  // 不记录历史，避免触发循环
        ]);
        
        if ($result) {
            // 记录日志
            Toolbox::logInFile(
                "ldapdefaultgroup",
                sprintf(
                    "为用户 ID %d 自动设置默认分组为 ID %d\n",
                    $users_id,
                    $groups_id
                )
            );
            return true;
        }
    }
    
    return false;
}
