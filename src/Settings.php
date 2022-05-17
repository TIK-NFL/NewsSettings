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

namespace ILIAS\Plugin\NewsSettings\GUI\Administration;

use ilSetting;

class Settings
{
    /** @var ilSetting */
    private $settings;

    private $newsByObjType = [];

    public function __construct(ilSetting $settings)
    {
        $this->settings = $settings;
        $this->read();
    }

    
    private function read() : void
    {
        $newsByObjType = $this->settings->get('news_by_obj_type', null);
        if ($newsByObjType !== null && $newsByObjType !== '') {
            $newsByObjType = json_decode($newsByObjType, true);
        }

        if (!is_array($newsByObjType)) {
            $newsByObjType  = [];
        }

        $this->newsByObjType = $newsByObjType;
    }

    // news setting, needed for news blocks(legacy/other plugin? I don't see them in a ILIAS 7 install) and timeline
    public function setNewsStatusFor(string $objType, bool $status) : void
    {
        $this->newsByObjType[$objType]['news'] = $status;
    }

    public function isNewsEnabledFor(string $objType) : bool
    {
        return $this->newsByObjType[$objType]['news'] ?? false;
    }

    // show timeline
    public function setTimelineStatusFor(string $objType, bool $status) : void
    {
        $this->newsByObjType[$objType]['timeline'] = $status;
    }

    public function isTimelineEnabledFor(string $objType) : bool
    {
        return $this->newsByObjType[$objType]['timeline'] ?? false;
    }

    // allow timeline to include auomatic entreis
    public function setTimelineAutoEntryStatusFor(string $objType, bool $status) : void
    {
        $this->newsByObjType[$objType]['timeline_auto_entry'] = $status;
    }

    public function isTimelineAutoEntryEnabledFor(string $objType) : bool
    {
        return $this->newsByObjType[$objType]['timeline_auto_entry'] ?? false;
    }


    public function save() : void
    {
        $this->settings->set('news_by_obj_type', json_encode($this->newsByObjType));
    }
}
