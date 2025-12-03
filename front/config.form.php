<?php
if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly.');
}

Session::checkRight('config', READ);

$context = 'ldapdefaultgroup';
$current = Config::getConfigurationValues($context, [
    'only_when_empty',
    'override_manual',
    'repair_on_delete'
]);

$defaults = [
    'only_when_empty' => 1,
    'override_manual' => 0,
    'repair_on_delete' => 1
];

foreach ($defaults as $k => $v) {
    if (!isset($current[$k])) {
        $current[$k] = $v;
    }
}

if (isset($_POST['save'])) {
    Session::checkRight('config', UPDATE);
    $values = [
        'only_when_empty' => isset($_POST['only_when_empty']) ? 1 : 0,
        'override_manual' => isset($_POST['override_manual']) ? 1 : 0,
        'repair_on_delete' => isset($_POST['repair_on_delete']) ? 1 : 0
    ];
    Config::setConfigurationValues($context, $values);
    global $CFG_GLPI;
    Html::redirect($CFG_GLPI['root_doc'].'/front/plugin.php');
}

Html::header(__('LDAP Default Group', 'ldapdefaultgroup'), $_SERVER['PHP_SELF'], 'config', 'plugins');

echo '<div class="card"><div class="card-body">';
echo '<form method="post" action="">';
echo Html::hidden('save', ['value' => 1]);
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

echo '<div class="form-group row">';
echo '<label class="col-form-label col-xxl-5 text-xxl-end">'.__('Only set when empty', 'ldapdefaultgroup').'</label>';
echo '<div class="col-xxl-7">';
echo '<input type="checkbox" name="only_when_empty" '.($current['only_when_empty'] ? 'checked' : '').' />';
echo '<div class="form-text">'.__('If enabled, do not override manual default group.', 'ldapdefaultgroup').'</div>';
echo '</div></div>';

echo '<div class="form-group row">';
echo '<label class="col-form-label col-xxl-5 text-xxl-end">'.__('Override manual choice', 'ldapdefaultgroup').'</label>';
echo '<div class="col-xxl-7">';
echo '<input type="checkbox" name="override_manual" '.($current['override_manual'] ? 'checked' : '').' />';
echo '<div class="form-text">'.__('If enabled, force default group to first priority even if manually set.', 'ldapdefaultgroup').'</div>';
echo '</div></div>';

echo '<div class="form-group row">';
echo '<label class="col-form-label col-xxl-5 text-xxl-end">'.__('Repair on group deletion', 'ldapdefaultgroup').'</label>';
echo '<div class="col-xxl-7">';
echo '<input type="checkbox" name="repair_on_delete" '.($current['repair_on_delete'] ? 'checked' : '').' />';
echo '<div class="form-text">'.__('If enabled, when current default group is removed, pick a new one or clear.', 'ldapdefaultgroup').'</div>';
echo '</div></div>';

echo '<div class="form-group row">';
echo '<div class="col-xxl-7 offset-xxl-5">';
echo '<button type="submit" class="btn btn-primary">'.__('Save').'</button>';
echo '</div></div>';

echo '</form>';
echo '</div></div>';

Html::footer();
