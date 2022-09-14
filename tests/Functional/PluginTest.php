<?php

namespace nlxWebpOptimizer\Tests;

use nlxWebpOptimizer\nlxWebpOptimizer as Plugin;
use Shopware\Components\Test\Plugin\TestCase;

class PluginTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'nlxWebpOptimizer' => []
    ];

    public function testCanCreateInstance()
    {
        /** @var Plugin $plugin */
        $plugin = Shopware()->Container()->get('kernel')->getPlugins()['nlxWebpOptimizer'];

        $this->assertInstanceOf(Plugin::class, $plugin);
    }
}
