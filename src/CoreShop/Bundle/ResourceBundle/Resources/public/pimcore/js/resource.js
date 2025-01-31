/*
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.org)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 *
 */

pimcore.registerNS('coreshop.resources');
coreshop.resources = Class.create({
    resources: {},

    initialize: function () {
        Ext.Ajax.request({
            url: Routing.generate('coreshop_resource_class_map'),
            success: function (response) {
                var resp = Ext.decode(response.responseText);

                coreshop.class_map = resp.classMap;
                coreshop.stack = resp.stack;
                coreshop.full_stack = resp.full_stack;

                coreshop.broker.fireEvent("afterClassMap", coreshop.class_map);
            }.bind(this)
        });

        document.addEventListener(coreshop.events.menu.open, (e) => {
            var item = e.detail.item;
            var type = e.detail.type;

            if (item.attributes.resource) {
                coreshop.global.resource.open(item.attributes.resource, item.attributes.function);
            }
        });

        coreshop.broker.addListener('resource.register', this.resourceRegistered, this);
    },

    resourceRegistered: function (name, resource) {
        this.resources[name] = resource;
    },

    open: function (module, resource) {
        this.resources[module].openResource(resource);
    }
});

coreshop.deepCloneStore = function (source) {
    source = Ext.isString(source) ? Ext.data.StoreManager.lookup(source) : source;

    var target = Ext.create(source.$className, {
        model: source.model,
    });

    target.add(Ext.Array.map(source.getRange(), function (record) {
        return record.copy();
    }));

    return target;
};

coreshop.broker.addListener('pimcore.ready', function() {
    coreshop.global.resource = new coreshop.resources();
});
