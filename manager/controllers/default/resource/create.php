<?php
/**
 * Loads the create resource page
 *
 * @package modx
 * @subpackage manager.resource
 */
if (!$modx->hasPermission('new_document')) return $modx->error->failure($modx->lexicon('access_denied'));

/* handle template inheritance */
if (!empty($_REQUEST['parent'])) {
    $parent = $modx->getObject('modResource',$_REQUEST['parent']);
    if (!$parent->checkPolicy('add_children')) return $modx->error->failure($modx->lexicon('resource_add_children_access_denied'));
    if ($parent != null) {
        $modx->smarty->assign('parent',$parent);
    }
} else { $parent = null; }

/* set context */
$ctx = !empty($_REQUEST['context_key']) ? $_REQUEST['context_key'] : 'web';
$modx->smarty->assign('_ctx',$ctx);
$context = $modx->getContext($ctx);
if (!$context) { return $modx->error->failure($modx->lexicon('context_err_nf')); }

/* handle custom resource types */
$resourceClass= isset ($_REQUEST['class_key']) ? $_REQUEST['class_key'] : 'modDocument';
$resourceClass = str_replace(array('../','..','/','\\'),'',$resourceClass);
$resourceDir= strtolower(substr($resourceClass, 3));
$delegateView = dirname(__FILE__) . '/' . $resourceDir . '/';
$delegateView = $modx->getOption(strtolower($resourceClass).'_delegate_path',null,$delegateView) . basename(__FILE__);
$delegateView = str_replace(array('{core_path}','{assets_path}','{base_path}'),array(
    $modx->getOption('core_path',null,MODX_CORE_PATH),
    $modx->getOption('assets_path',null,MODX_ASSETS_PATH),
    $modx->getOption('base_path',null,MODX_BASE_PATH),
),$delegateView);
if (file_exists($delegateView)) {
    $overridden= include ($delegateView);
    if ($overridden !== false) {
        return $overridden;
    }
}

$resource = $modx->newObject($resourceClass);

/* invoke OnDocFormPrerender event */
$onDocFormPrerender = $modx->invokeEvent('OnDocFormPrerender',array(
    'id' => 0,
    'mode' => modSystemEvent::MODE_NEW,
));
if (is_array($onDocFormPrerender)) {
    $onDocFormPrerender = implode('',$onDocFormPrerender);
}
$modx->smarty->assign('onDocFormPrerender',$onDocFormPrerender);

/* handle default parent */
$parentname = $context->getOption('site_name', '', $modx->_userConfig);
$resource->set('parent',0);
if (isset ($_REQUEST['parent'])) {
    if ($_REQUEST['parent'] == 0) {
        $parentname = $context->getOption('site_name', '', $modx->_userConfig);
    } else {
        $parent = $modx->getObject('modResource',$_REQUEST['parent']);
        if ($parent != null) {
            $parentname = $parent->get('pagetitle');
            $resource->set('parent',$parent->get('id'));
        }
    }
}
$modx->smarty->assign('parentname',$parentname);


/* invoke OnDocFormRender event */
$onDocFormRender = $modx->invokeEvent('OnDocFormRender',array(
    'id' => 0,
    'mode' => modSystemEvent::MODE_NEW,
));
if (is_array($onDocFormRender)) $onDocFormRender = implode('',$onDocFormRender);
$onDocFormRender = str_replace(array('"',"\n","\r"),array('\"','',''),$onDocFormRender);
$modx->smarty->assign('onDocFormRender',$onDocFormRender);

/* assign resource to smarty */
$modx->smarty->assign('resource',$resource);

/* check permissions */
$publish_document = $modx->hasPermission('publish_document');
$access_permissions = $modx->hasPermission('access_permissions');
$richtext = $context->getOption('richtext_default', true, $modx->_userConfig);

/* register JS scripts */
$rte = isset($_REQUEST['which_editor']) ? $_REQUEST['which_editor'] : $context->getOption('which_editor', '', $modx->_userConfig);
$modx->smarty->assign('which_editor',$rte);

/*
 *  Initialize RichText Editor
 */
/* Set which RTE if not core */
if ($context->getOption('use_editor', false, $modx->_userConfig) && !empty($rte)) {
    /* invoke OnRichTextEditorRegister event */
    $text_editors = $modx->invokeEvent('OnRichTextEditorRegister');
    $modx->smarty->assign('text_editors',$text_editors);

    $replace_richtexteditor = array('ta');
    $modx->smarty->assign('replace_richtexteditor',$replace_richtexteditor);

    /* invoke OnRichTextEditorInit event */
    $onRichTextEditorInit = $modx->invokeEvent('OnRichTextEditorInit',array(
        'editor' => $rte,
        'elements' => $replace_richtexteditor,
        'id' => 0,
        'mode' => modSystemEvent::MODE_NEW,
    ));
    if (is_array($onRichTextEditorInit)) {
        $onRichTextEditorInit = implode('',$onRichTextEditorInit);
        $modx->smarty->assign('onRichTextEditorInit',$onRichTextEditorInit);
    }
}

/* set default template */
$default_template = (isset($_REQUEST['template']) ? $_REQUEST['template'] : ($parent != null ? $parent->get('template') : $context->getOption('default_template', 0, $modx->_userConfig)));
$userGroups = $modx->user->getUserGroups();
$c = $modx->newQuery('modActionDom');
$c->leftJoin('modAccessActionDom','Access');
$principalCol = $this->modx->getSelectColumns('modAccessActionDom','Access','',array('principal'));
$c->where(array(
    'action' => $this->action['id'],
    'name' => 'template',
    'container' => 'modx-panel-resource',
    'rule' => 'fieldDefault',
    'active' => true,
    array(
        array(
            'Access.principal_class:=' => 'modUserGroup',
            $principalCol.' IN ('.implode(',',$userGroups).')',
        ),
        'OR:Access.principal:IS' => null,
    ),
));
$fcDt = $modx->getObject('modActionDom',$c);
if ($fcDt) {
    $parentIds = array();
    if ($parent) { /* ensure get all parents */
        $p = $parent ? $parent->get('id') : 0;
        $rCtx = $parent->get('context_key');
        $oCtx = $modx->context->get('key');
        if (!empty($rCtx) && $rCtx != 'mgr') {
            $modx->switchContext($rCtx);
        }
        $parentIds = $modx->getParentIds($p);
        $parentIds[] = $p;
        $parentIds = array_unique($parentIds);
        if (!empty($rCtx)) {
            $modx->switchContext($oCtx);
        }
    } else {
        $parentIds = array(0);
    }

    $constraintField = $fcDt->get('constraint_field');
    if ($constraintField == 'id' && in_array($fcDt->get('constraint'),$parentIds)) {
        $default_template = $fcDt->get('value');
    } else if (empty($constraintField)) {
        $default_template = $fcDt->get('value');
    }
}

$defaults = array(
    'template' => $default_template,
    'content_type' => 1,
    'class_key' => isset($_REQUEST['class_key']) ? $_REQUEST['class_key'] : 'modDocument',
    'context_key' => $ctx,
    'parent' => isset($_REQUEST['parent']) ? $_REQUEST['parent'] : 0,
    'richtext' => $richtext,
    'hidemenu' => $context->getOption('hidemenu_default', 0, $modx->_userConfig),
    'published' => $context->getOption('publish_default', 0, $modx->_userConfig),
    'searchable' => $context->getOption('search_default', 1, $modx->_userConfig),
    'cacheable' => $context->getOption('cache_default', 1, $modx->_userConfig),
);

/* handle FC rules */
if ($parent == null) {
    $parent = $modx->newObject('modResource');
    $parent->set('id',0);
    $parent->set('parent',0);
}
$parent->fromArray($defaults);
$parent->set('template',$default_template);
$overridden = $this->checkFormCustomizationRules($parent,true);
$defaults = array_merge($defaults,$overridden);

/* register JS */
$managerUrl = $context->getOption('manager_url', MODX_MANAGER_URL, $modx->_userConfig);
$modx->regClientStartupScript($managerUrl.'assets/modext/util/datetime.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/widgets/element/modx.panel.tv.renders.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/widgets/resource/modx.grid.resource.security.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/widgets/resource/modx.panel.resource.tv.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/widgets/resource/modx.panel.resource.js');
$modx->regClientStartupScript($managerUrl.'assets/modext/sections/resource/create.js');
$modx->regClientStartupHTMLBlock('
<script type="text/javascript">
// <![CDATA[
MODx.config.publish_document = "'.$publish_document.'";
MODx.onDocFormRender = "'.$onDocFormRender.'";
MODx.ctx = "'.$ctx.'";
Ext.onReady(function() {
    MODx.load({
        xtype: "modx-page-resource-create"
        ,record: '.$modx->toJSON($defaults).'
        ,access_permissions: "'.$access_permissions.'"
        ,publish_document: "'.$publish_document.'"
        ,canSave: "'.($modx->hasPermission('save_document') ? 1 : 0).'"
    });
});
// ]]>
</script>');

return $modx->smarty->fetch('resource/create.tpl');
