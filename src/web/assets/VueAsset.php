<?php

namespace pdaleramirez\commercepaymongo\web\assets;

use craft\web\AssetBundle;

class VueAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = '@commerce-paymongo/web/assets/app';

        $this->js = [
            'app.js'
        ];

        $this->css = [
            'app.css'
        ];

        parent::init();
    }
}
