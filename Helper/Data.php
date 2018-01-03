<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Script\Base\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

	const XML_DEPLOY_SAT            = 'script_deploy/general/protected_key';
	const XML_DEPLOY_ENABLE         = 'script_deploy/general/protected_key';
	const XML_DEPLOY_SDET           = 'script_deploy/general/static_deploy_exclude_themes';
	const XML_DEPLOY_USE_COMPOSER   = 'script_deploy/general/use_composer';
	const XML_DEPLOY_COMPOSER_HOME  = 'script_deploy/general/composer_home';

    /** @var \Magento\Framework\App\State */
    protected $state;

    protected $enabled = null;

    public function __construct(
        Context $context,
        State $state
    ) {
        parent::__construct($context);
        $this->state = $state;
    }

    public function isEnabledLayoutDebug()
    {
        if (is_null($this->enabled)) $this->enabled = $this->state->getMode() === State::MODE_DEVELOPER;

        return $this->enabled;
    }

	/**
	 * @return string
	 */
	public function getModuleName(): string {
		return $this->_moduleName;
	}

	public function getMagentoConfig($path, $default = null, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
	{
		if($val = $this->scopeConfig->getValue($path, $scopeType, $scopeCode)) {
			return $val;
		}
		return $default;
	}
}
