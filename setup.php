<?php
/**
 * Plugin Name: LDAP Default Group
 * Description: 自动设置LDAP同步用户的默认分组为第一个群组
 * Version: 1.0.0
 * Author: Your Name
 */

// 确保不能直接访问文件
if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly.');
}

define('PLUGIN_LDAPDEFAULTGROUP_VERSION', '1.0.0');

/**
 * 插件初始化函数
 */
function plugin_init_ldapdefaultgroup() {
    global $PLUGIN_HOOKS;
    
    $PLUGIN_HOOKS['csrf_compliant']['ldapdefaultgroup'] = true;
    
    // 在用户添加后触发钩子
    $PLUGIN_HOOKS['item_add']['ldapdefaultgroup'] = [
        'User' => 'plugin_ldapdefaultgroup_item_add_user'
    ];
    
    // 在用户更新后触发钩子（用于LDAP同步更新）
    $PLUGIN_HOOKS['item_update']['ldapdefaultgroup'] = [
        'User' => 'plugin_ldapdefaultgroup_item_update_user'
    ];
}

/**
 * 获取插件版本
 */
function plugin_version_ldapdefaultgroup() {
    return [
        'name'           => 'LDAP Default Group',
        'version'        => PLUGIN_LDAPDEFAULTGROUP_VERSION,
        'author'         => 'Your Name',
        'license'        => 'GPLv2+',
        'homepage'       => 'https://github.com/yourname/ldapdefaultgroup',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0',
                'max' => '10.1'
            ]
        ]
    ];
}

/**
 * 检查插件先决条件
 */
function plugin_ldapdefaultgroup_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '10.0', 'lt')) {
        echo "此插件需要 GLPI >= 10.0";
        return false;
    }
    return true;
}

/**
 * 检查插件配置
 */
function plugin_ldapdefaultgroup_check_config() {
    return true;
}

/**
 * 插件安装函数
 *
 * @return bool 安装是否成功
 */
function plugin_ldapdefaultgroup_install() {
    // 安装时的初始化操作
    // 这里可以添加创建数据库表、设置默认值等操作
    return true;
}

/**
 * 插件卸载函数
 *
 * @return bool 卸载是否成功
 */
function plugin_ldapdefaultgroup_uninstall() {
    // 卸载时的清理操作
    // 这里可以添加删除数据库表、清理配置等操作
    return true;
}
