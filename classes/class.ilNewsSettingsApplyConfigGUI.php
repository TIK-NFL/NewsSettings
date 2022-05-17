<?php

declare(strict_types=1);
/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 ********************************************************************
 */
// sets configuration for existing objects

use ILIAS\Plugin\NewsSettings\GUI\Administration\BaseController;

class ilNewsSettingsApplyConfigGUI extends BaseController
{
    protected function getDefaultCommand() : string
    {
        return 'showConfiguration';
    }

    public function executeCommand() : void
    {
        $nextClass = $this->ctrl->getNextClass();
        switch (strtolower($nextClass)) {
            default:
                parent::executeCommand();
                $this->tabs->activateTab('modify_settings');
                break;
        }
    }

    protected function getForm() : ilPropertyFormGUI
    {
        // Form Definitions
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this, 'confirmApplyConfiguration'));
        $form->setTitle($this->plugin_object->txt('modify_settings'));
        $form->setDescription($this->plugin_object->txt('modify_settings_form_info'));
        $form->addCommandButton('confirmApplyConfiguration', $this->plugin_object->txt('btn_label_migrate_objs'));

        // ???
        $this->lng->loadLanguageModule('trac');

        $refidInput_tree = new ilTextInputGUI(
            $this->plugin_object->txt('enable_timeline_to_crs_and_grp_tree'),
            'enable_timeline_to_crs_and_grp_tree'
        );
        $refidInput_tree->setRequired(true);

        $objectTypes_tree = new ilMultiSelectInputGUI(
            $this->lng->txt('obj_types'),
            'obj_types'
        );
        $objectTypes_tree->setRequired(true);

        $options_tree = [];
        foreach ($this->plugin_object->getValidObjectTypes() as $objectType) {
            $options_tree[$objectType] = $this->lng->txt('obj_' . $objectType);
        }

        $objectTypes_tree->setOptions($options_tree);
        $refidInput_tree->addSubItem($objectTypes_tree);
        $form->addItem($refidInput_tree);
        return $form;
    }

    protected function showConfiguration(ilPropertyFormGUI $form = null) : void
    {
        if (null === $form) {
            $form = $this->getForm();
        }
        $this->pageTemplate->setContent($form->getHTML());
    }


    protected function confirmApplyConfiguration() : void
    {
        $form = $this->getForm();
        if ($form->checkInput()) {
            $confirmation = new ilConfirmationGUI();

            $confirmation->setFormAction($this->ctrl->getFormAction($this, 'applyConfiguration'));
            $confirmation->setConfirm($this->lng->txt('confirm'), 'applyConfiguration');
            $confirmation->setCancel($this->lng->txt('cancel'), $this->getDefaultCommand());

            $objTypes = [];
            foreach ($form->getInput('obj_types') as $objType) {
                $confirmation->addHiddenItem('obj_types[]', $objType);
                $objTypes[] = $this->lng->txt('obj_' . $objType);
            }

            $ref_id = [];

            $confirmation->addHiddenItem('enable_timeline_to_crs_and_grp_tree', $form->getInput('enable_timeline_to_crs_and_grp_tree'));
            $ref_id[] = $form->getInput('enable_timeline_to_crs_and_grp_tree');
            
            $confirmation->setHeaderText(sprintf(
                $this->plugin_object->txt('sure_adopt_preset_x'),
                implode(', ', array_merge($objTypes, $ref_id))
            ));

            $this->pageTemplate->setContent($confirmation->getHTML());
            return;
        }

        $form->setValuesByPost();
        $this->pageTemplate->setContent($form->getHTML());
    }


    // do the actual change to the Database
    protected function applyConfiguration() : void
    {
        // get object tipes to apply changes to
        $objTypes = array_intersect(
            (array) ($this->http->request()->getParsedBody()['obj_types'] ?? []),
            $this->plugin_object->getValidObjectTypes()
        );
        // get ref_id of the subtree that is looked upon
        $ref_id = $this->http->request()->getParsedBody()['enable_timeline_to_crs_and_grp_tree'];

        // SQL
        foreach ($objTypes as $type) {
            $this->dic->database()->manipulateF(
                "UPDATE container_settings 
                INNER JOIN object_reference ON container_settings.id = object_reference.obj_id     
                INNER JOIN tree ON object_reference.ref_id = tree.child   
                INNER JOIN object_data ON object_reference.obj_id = object_data.obj_id 
                        AND object_data.type = %s
                        AND tree.path like '%%.%u.%%'  
                        AND object_reference.deleted is null
                        AND NOT container_settings.value = 1 
                        AND (container_settings.keyword = 'cont_use_news'
                            OR container_settings.keyword = 'news_timeline'
                            OR container_settings.keyword = 'news_timeline_incl_auto')
                SET container_settings.value = 1",
                ['text', 'integer'],
                [$type, $ref_id]
            );
        }
         
        ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
        $this->ctrl->redirect($this);
    }
}
