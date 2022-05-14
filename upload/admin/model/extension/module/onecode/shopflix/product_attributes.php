<?php

/**
 * @property-read \DB $db
 * @property-read \Language $language
 * @property-read \ModelCatalogAttributeGroup $model_catalog_attribute_group
 * @property-read \ModelCatalogAttribute $model_catalog_attribute
 * @property-read \ModelLocalisationLanguage $model_localisation_language
 * @property-read \Loader $load
 */
class ModelExtensionModuleOnecodeShopflixProductAttributes extends Model
{
    const GROUP_NAME = 'ShopFlix';

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('catalog/attribute_group');
        $this->load->model('catalog/attribute');
        $this->load->model('localisation/language');
    }

    protected function createProductAttributes()
    {
        $this->clearProductAttributes();
        $languages = $this->model_localisation_language->getLanguages();
        $descriptions = [];
        foreach ($languages as $lang)
        {
            $descriptions[$lang['language_id']] = ['name' => self::GROUP_NAME];
        }
        $group_id = $this->model_catalog_attribute_group->addAttributeGroup([
            'sort_order' => 10,
            'attribute_group_description' => $descriptions,
        ]);
        $attrs = [
            'list_price' => $this->language->get('text_list_price'),
            'shipping_time' => $this->language->get('text_shipping_time'),
            'offer_from' => $this->language->get('text_offer_from'),
            'offer_to' => $this->language->get('text_offer_to'),
            'offer_price' => $this->language->get('text_offer_price'),
            'offer_quantity' => $this->language->get('text_offer_quantity'),
        ];

        $order = 0;
        foreach ($attrs as $key => $attr)
        {
            $order++;
            $attr_descriptions = [];
            foreach ($languages as $lang)
            {
                $attr_descriptions[$lang['language_id']] = ['name' => $attr];
            }
            $this->model_catalog_attribute->addAttribute([
                'attribute_group_id' => $group_id,
                'sort_order' => $order,
                'attribute_description' => $attr_descriptions,
            ]);
        }
    }

    public function getProductAttributes(): array
    {
        $group_id = [];
        $attributes = [];
        $groups = $this->model_catalog_attribute_group->getAttributeGroups();
        if (count($groups))
        {
            foreach ($groups as $group)
            {
                $descriptions = $this->model_catalog_attribute_group->getAttributeGroupDescriptions($group['attribute_group_id']);
                foreach ($descriptions as $desc)
                {
                    if ($desc['name'] == self::GROUP_NAME)
                    {
                        $group_id[$group['attribute_group_id']] = $group['attribute_group_id'];
                    }
                }
            }
        }
        if (count($group_id))
        {
            foreach ($group_id as $id)
            {
                $attrs = $this->model_catalog_attribute->getAttributes(['filter_attribute_group_id' => $id]);
                $names = $this->model_catalog_attribute->getAttributeDescriptions($id);
                if (count($attrs))
                {
                    array_walk($attrs, function ($row) use (&$attributes, $names) {
                        $row['description'] = $names;
                        $attributes[] = $row;
                    });
                }
            }
        }
        return $attributes;
    }

    protected function clearProductAttributes()
    {
        $attributes = $this->getProductAttributes();
        array_walk($attributes, function ($row) {
            $this->model_catalog_attribute_group->deleteAttributeGroup($row['attribute_group_id']);
            $this->model_catalog_attribute->deleteAttribute($row['attribute_id']);
        });
    }

    public function install()
    {
        $this->createProductAttributes();
    }

    public function uninstall()
    {
        $this->clearProductAttributes();
    }
}