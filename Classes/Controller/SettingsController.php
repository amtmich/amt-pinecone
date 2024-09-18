<?php

namespace Amt\AmtPinecone\Controller;

use Psr\Http\Message\ResponseInterface;

class SettingsController extends BaseController
{
    public function settingsAction(): ResponseInterface
    {
        $moduleTemplate = $this->createRequestModuleTemplate();

        $moduleTemplate->assign('message', 'Hello - AmtPinecone Extension!');

        return $moduleTemplate->renderResponse('Settings');
    }
}
