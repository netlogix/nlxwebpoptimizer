<?php

namespace nlxWebPOptimizer\Tests;

use nlxWebPOptimizer\nlxWebPOptimizer as Plugin;
use Shopware\Components\Test\Plugin\TestCase;

class PluginTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'nlxWebPOptimizer' => []
    ];

    public function testCanCreateInstance()
    {
        /** @var Plugin $plugin */
        $plugin = Shopware()->Container()->get('kernel')->getPlugins()['nlxWebPOptimizer'];

        $this->assertInstanceOf(Plugin::class, $plugin);
    }
}
