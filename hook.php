<?php

// 确保不能直接访问文件
if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly.');
}

// 由GLPI自动加载核心类，无需手动include

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
    
    if (!isset($item->fields['auths_id']) || $item->fields['auths_id'] == 0) {
        return true;
    }

    $cfg = plugin_ldapdefaultgroup_get_config();

    if (!empty($cfg['only_when_empty'])) {
        if (empty($item->fields['groups_id']) || (int)$item->fields['groups_id'] === 0) {
            plugin_ldapdefaultgroup_set_default_group($item->fields['id'], false);
        }
    } else {
        $force = !empty($cfg['override_manual']);
        plugin_ldapdefaultgroup_set_default_group($item->fields['id'], $force);
    }
    
    return true;
}

function plugin_ldapdefaultgroup_item_add_group_user($item) {
    if (!isset($item->fields['users_id'])) {
        return true;
    }

    $users_id = (int)$item->fields['users_id'];

    $user = new User();
    if ($user->getFromDB($users_id)) {
        if (empty($user->fields['auths_id']) || (int)$user->fields['auths_id'] === 0) {
            return true;
        }

        $cfg = plugin_ldapdefaultgroup_get_config();
        if (!empty($cfg['only_when_empty'])) {
            if (empty($user->fields['groups_id']) || (int)$user->fields['groups_id'] === 0) {
                plugin_ldapdefaultgroup_set_default_group($users_id, false);
            }
        } else {
            $force = !empty($cfg['override_manual']);
            plugin_ldapdefaultgroup_set_default_group($users_id, $force);
        }
    }

    return true;
}

function plugin_ldapdefaultgroup_item_delete_group_user($item) {
    if (!isset($item->fields['users_id']) || !isset($item->fields['groups_id'])) {
        return true;
    }

    $users_id = (int)$item->fields['users_id'];
    $deleted_group_id = (int)$item->fields['groups_id'];

    $user = new User();
    if ($user->getFromDB($users_id)) {
        if (empty($user->fields['auths_id']) || (int)$user->fields['auths_id'] === 0) {
            return true;
        }

        $cfg = plugin_ldapdefaultgroup_get_config();
        if ((int)$user->fields['groups_id'] === $deleted_group_id) {
            if (!empty($cfg['repair_on_delete'])) {
                plugin_ldapdefaultgroup_set_default_group($users_id, false);
            }
        }
    }

    return true;
}

/**
 * 设置用户的默认分组为第一个群组
 * 
 * @param int $users_id 用户ID
 * @return bool
 */
function plugin_ldapdefaultgroup_set_default_group($users_id, $force = false) {
    global $DB;
    $user = new User();
    if (!$user->getFromDB($users_id)) {
        return false;
    }

    $current_group_id = (int)$user->fields['groups_id'];

    if ($current_group_id > 0 && !$force) {
        $has = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_groups_users',
            'WHERE'  => [
                'users_id'  => $users_id,
                'groups_id' => $current_group_id
            ],
            'LIMIT'  => 1
        ]);
        if (count($has) > 0) {
            return true;
        }
    }

    $iterator = $DB->request([
        'SELECT' => ['groups_id'],
        'FROM'   => 'glpi_groups_users',
        'WHERE'  => [
            'users_id' => $users_id
        ],
        'ORDER'  => ['is_dynamic DESC', 'id ASC'],
        'LIMIT'  => 1
    ]);

    if (count($iterator) === 0) {
        if ($current_group_id !== 0) {
            $user->update([
                'id'        => $users_id,
                'groups_id' => 0,
                '_no_history' => true
            ]);
        }
        return true;
    }

    $row = $iterator->current();
    $groups_id = (int)$row['groups_id'];

    if ($current_group_id === $groups_id) {
        return true;
    }

    $result = $user->update([
        'id'        => $users_id,
        'groups_id' => $groups_id,
        '_no_history' => true
    ]);

    if ($result) {
        if (class_exists('Toolbox')) {
            Toolbox::logInFile(
                "ldapdefaultgroup",
                sprintf(
                    "为用户 ID %d 自动设置默认分组为 ID %d\n",
                    $users_id,
                    $groups_id
                )
            );
        }
        return true;
    }

    return false;
}
function plugin_ldapdefaultgroup_get_config() {
    $context = 'ldapdefaultgroup';
    $values = Config::getConfigurationValues($context, [
        'only_when_empty',
        'override_manual',
        'repair_on_delete'
    ]);
    if (!isset($values['only_when_empty'])) {
        $values['only_when_empty'] = 1;
    }
    if (!isset($values['override_manual'])) {
        $values['override_manual'] = 0;
    }
    if (!isset($values['repair_on_delete'])) {
        $values['repair_on_delete'] = 1;
    }
    return $values;
}
