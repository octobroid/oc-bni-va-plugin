<?php namespace Octobro\Bniva;

use Backend;
use System\Classes\PluginBase;

/**
 * bniva Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['Responsiv.Pay'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'bniva',
            'description' => 'No description provided yet...',
            'author'      => 'octobro',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Registers any payment gateways implemented in this plugin.
     * The gateways must be returned in the following format:
     * ['className1' => 'alias'],
     * ['className2' => 'anotherAlias']
     */
    public function registerPaymentGateways()
    {
        return [
            'Octobro\Bniva\PaymentTypes\Bniva' => 'bni-va',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Octobro\Bniva\Components\VirtualAccount' => 'myVirtualAccount',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'octobro.bniva.some_permission' => [
                'tab' => 'bniva',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'bniva' => [
                'label'       => 'bniva',
                'url'         => Backend::url('octobro/bniva/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['octobro.bniva.*'],
                'order'       => 500,
            ],
        ];
    }
}
