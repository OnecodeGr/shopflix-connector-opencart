<?php

use Onecode\Library\EventGroup;

require_once DIR_SYSTEM . 'library/onecode/EventGroup.php';

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 * @property-read \ModelSettingEvent $model_setting_event
 */
class ModelExtensionModuleOnecodeShopflixEvent extends Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/event');
    }

    public function getEventList(): EventGroup
    {
        $group = new EventGroup();
        $group->addRaw(
            'admin_menu_item',
            'onecode_shopflix_menu_admin_item',
            'admin/view/common/column_left/before',
            'extension/module/onecode_shopflix/eventInjectAdminMenuItem',
            1
        );
        return $group;
    }

    public function install()
    {
        foreach ($this->getEventList()->get() as $item)
        {
            $this->model_setting_event->addEvent(
                $item->code,
                $item->trigger,
                $item->action,
                $item->status ?? 1,
                $item->order ?? 0
            );
        }
    }

    public function uninstall()
    {
        foreach ($this->getEventList()->get() as $item)
        {
            $this->model_setting_event->deleteEventByCode($item->code);
        }
    }
}