<?php
//configuration gui
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
use ILIAS\Plugin\NewsSettings\GUI\Administration\BaseController;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @ilCtrl_Calls ilNewsSettingsConfigGUI: ilNewsSettingsApplyConfigGUI
 */
class ilNewsSettingsConfigGUI extends BaseController
{
    protected function getDefaultCommand() : string
    {
        return 'showPresetConfiguration';
    }

    public function executeCommand() : void
    {
        $nextClass = $this->ctrl->getNextClass();
        switch (strtolower($nextClass)) {
            case strtolower(ilNewsSettingsApplyConfigGUI::class):
                $this->ctrl->forwardCommand(
                    new ilNewsSettingsApplyConfigGUI(
                        $this->plugin_object
                    )
                );
                $this->tabs->activateTab('modify_settings');
                break;

            default:
                parent::executeCommand();
                $this->tabs->activateTab('configuration_presets');
                break;
        }
    }

    protected function getPresetForm() : ilPropertyFormGUI
    {
        // Form
        $form = new ilPropertyFormGUI();
        // Action
        $form->setFormAction($this->ctrl->getFormAction($this, 'savePresetConfiguration'));
        // Titel
        $form->setTitle($this->plugin_object->txt('configuration_presets'));
        
        // Save button
        $form->addCommandButton('savePresetConfiguration', $this->plugin_object->txt('btn_label_save_for_new_objs'));

        // create GUI selection for timeline
        // ioterate over object types (currently crs and grp)
        foreach ($this->getPluginObject()->getValidObjectTypes() as $objectType) {
            // Enable News checkbox
            $newsForType = new ilCheckboxInputGUI(
                sprintf($this->plugin_object->txt('news_service_in_obj_x'), $this->lng->txt('obj_' . $objectType)),
                'news_status_' . $objectType
            );
            $newsForType->setValue('1');
            $newsForType->setInfo($this->plugin_object->txt('news_will_be_enabled'));

            // boxed checkboxes
            // Timeline
            $timelineForType = new ilCheckboxInputGUI(
                $this->plugin_object->txt('news_setting_timeline'),
                'timeline_status_' . $objectType
            );
            $timelineForType->setValue('1');
            $timelineForType->setInfo(
                implode(' ', [
                    $this->plugin_object->txt('news_setting_timeline_description'),
                    //$this->plugin_object->txt('news_dashboard')
                ])
            );

            // Timeline auto entrys
            $timelineAutoEntry = new ilCheckboxInputGUI(
                $this->plugin_object->txt('news_setting_timeline_include_automatic_entries'),
                'timeline_auto_entrys_status_' . $objectType
            );
            $timelineAutoEntry->setValue('1');
            $timelineAutoEntry->setInfo(
                implode(' ', [
                    $this->plugin_object->txt('news_setting_timeline_include_automatic_entries_description'),
                    //$this->plugin_object->txt('news_dashboard')
                ])
            );
            // Visible only when News are Enabled
            $newsForType->addSubItem($timelineForType);
            $newsForType->addSubItem($timelineAutoEntry);

            $form->addItem($newsForType);
        }

        return $form;
    }

    protected function showPresetConfiguration(ilPropertyFormGUI $form = null) : void
    {
        if (null === $form) {
            $form = $this->getPresetForm();
        }
        $this->populateValues($form);
        $this->pageTemplate->setContent($form->getHTML());
    }

    protected function savePresetConfiguration() : void
    {
        $form = $this->getPresetForm();
        if ($form->checkInput()) {
            foreach ($this->getPluginObject()->getValidObjectTypes() as $objectType) {
                $this->pluginSettings->setNewsStatusFor(
                    $objectType,
                    (bool) $form->getInput('news_status_' . $objectType)
                );

                $this->pluginSettings->setTimelineStatusFor(
                    $objectType,
                    (bool) $form->getInput('timeline_status_' . $objectType)
                );

                $this->pluginSettings->setTimelineAutoEntryStatusFor(
                    $objectType,
                    (bool) $form->getInput('timeline_auto_entrys_status_' . $objectType)
                );
            }

            $this->pluginSettings->save();

            ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
            $this->ctrl->redirect($this);
        }

        $form->setValuesByPost();

        $this->pageTemplate->setContent($form->getHTML());
    }

    protected function populateValues(ilPropertyFormGUI $form) : void
    {
        $data = [];
        foreach ($this->getPluginObject()->getValidObjectTypes() as $objectType) {
            $data['news_status_' . $objectType] = $this->pluginSettings->isNewsEnabledFor($objectType);
            $data['timeline_status_' . $objectType] = $this->pluginSettings->isTimelineEnabledFor($objectType);
            $data['timeline_auto_entrys_status_' . $objectType] = $this->pluginSettings->isTimelineAutoEntryEnabledFor($objectType);
        }

        $form->setValuesByArray($data);
    }
}
