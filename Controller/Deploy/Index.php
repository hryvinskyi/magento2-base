<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Script\Base\Controller\Deploy;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Script\Base\Helper\Data;
use Script\Base\Helper\Environment;
use Script\Base\Helpers\Html;

/**
 * Class Index
 * @package Script\Base\Controller\Run\Index
 */
class Index extends Action
{

	/**
	 * @var Environment
	 */
	protected $env;

	/**
	 * @var Data
	 */
	protected $helper;

	protected $dir;
	protected $storeManager;

	/**
	 * @var array
	 */
	protected $staticDeployExcludeThemes = [];

	/**
	 * Index constructor.
	 *
	 * @param Data $helper
	 * @param Environment $environment
	 * @param StoreManagerInterface $storeManager
	 * @param Context $context
	 */
	public function __construct(
		Data $helper,
		DirectoryList $dir,
		Environment $environment,
		StoreManagerInterface $storeManager,
		Context $context
	) {
		$this->helper                       = $helper;
		$this->dir                          = $dir;
		$this->env                          = $environment;
		$this->storeManager                 = $storeManager;
		$staticDeployExcludeThemes          = $this->helper->getMagentoConfig(Data::XML_DEPLOY_SDET);
		$this->staticDeployExcludeThemes    = explode(',', trim($staticDeployExcludeThemes, ','));

		return parent::__construct( $context );
	}

	/**
	 * Function execute
	 */
	public function execute() {
		if($this->helper->getMagentoConfig(Data::XML_DEPLOY_ENABLE)) {
			$start = microtime(true);
			$sat        = $this->getRequest()->getParam('sat');
			$systemSat  = $this->helper->getMagentoConfig(Data::XML_DEPLOY_SAT);

			if(isset($sat) && $sat == $systemSat) {
				$this->env->setStaticDeployInBuild(false);
				$this->env->log($this->env->startingMessage("Build"));
				$this->env->log("Enable maintenance mode.");
				$output['Enabling Maintenance mode.']   = $this->env->execute("php ./bin/magento maintenance:enable");
				$output['Whoami']                       = $this->env->execute('whoami');
				$output['Git Status']                   = $this->env->execute('git status');
				$output['Git Pull']                     = $this->env->execute('git pull');
				if($this->helper->getMagentoConfig(Data::XML_DEPLOY_USE_COMPOSER)) {
					$output['Composer Update']          = $this->composerUpdate();
				}
				$output['Apply Committed Patches']      = $this->applyCommittedPatches();
				$output['Running DI compilation']       = $this->compileDI();
				$output['Generating static content']    = $this->deployStaticContent();
				$output['Disable maintenance mode']     = $this->env->execute("php ./bin/magento maintenance:disable");
				$this->env->log("Maintenance mode is disabled.");
				echo Html::tag('style', 'body {font-family: Consolas, monaco, monospace;font-size:13px;margin: 0px;background: #242626;color: #FFFFFF; padding: 10px;}');
				foreach($output as $key => $value) {
					$this->colorShell($key, $value);
				}
			}
			$time = microtime(true) - $start;
			echo Html::tag('div', 'Deploying in ' . round($time, 2) . 'sec.', ['style' => 'color:#fff;text-align:right']);
		}
	}

	static $index = 0;
	public function colorShell($key, $value) {
		if($value == '' || (is_array($value) && count($value) == 0)) return;
		$i = 3 + self::$index;
		echo Html::tag('h' . $i , $key, ['style' => 'color:#9876a9;margin-bottom:5px']);
		if(is_array($value)) {
			foreach($value as $k => $v) {
				self::$index++;
				$this->colorShell($k, $v);
			}
		} else {
			echo Html::tag('div', nl2br(str_replace(' ', '&nbsp;', $value)), ['style' => 'color:#69834f']);
			self::$index = 0;
		}
	}

	public function deployStaticContent()
	{
		$this->env->execute('touch ' . Environment::MAGENTO_ROOT . 'pub/static/deployed_version.txt');

		/* Generate static assets */
		$this->env->log("Extract locales");
		$excludeThemesOptions = '';
		$themes = $this->staticDeployExcludeThemes;
		if (count($themes) > 0) {
			if (count($themes) > 1) {
				$excludeThemesOptions = "--exclude-theme " . implode(' --exclude-theme ', $themes);
			} elseif (count($themes) === 1){
				$excludeThemesOptions = "--exclude-theme " .  $themes[0];
			}
		}

		$includeLocales = '';
		$locales = $this->getLocales();
		if (count($locales) > 0) {
			if (count($locales) > 1) {
				$includeLocales = "--language " . implode(' --language ', $locales);
			} elseif (count($locales) === 1) {
				$includeLocales = "--language " .  $locales[0];
			}
		}

		$logMessage = count($locales) ? "Generating static content for locales: " . implode(' ', $locales) : "Generating static content.";
		$this->env->log($logMessage);

		return $this->env->execute("php ./bin/magento setup:static-content:deploy $excludeThemesOptions $includeLocales --force");
	}

	/**
	 * Apply patches distributed through
	 */
	private function applyCommittedPatches()
	{
		$return = [];
		$patchesDir = Environment::MAGENTO_ROOT . 'm2-hotfixes/';
		$this->env->log("Checking if patches exist under " . $patchesDir);
		if (is_dir($patchesDir)) {
			$files = glob($patchesDir . "*.patch");
			sort($files);
			foreach ($files as $file) {
				$cmd = 'git apply '  . $file;
				$return[] = $this->env->execute($cmd);
			}
		}
		return $return;
	}

	private function compileDI()
	{
		$this->env->execute('rm -rf /generated/code/*');
		$this->env->execute('rm -rf /generated/metadata/*');
		$this->env->log("Running DI compilation");
		return $this->env->execute("php ./bin/magento setup:di:compile");
	}

	private function composerUpdate()
	{
		putenv('COMPOSER_HOME='.$this->helper->getMagentoConfig(Data::XML_DEPLOY_COMPOSER_HOME));
		return $this->env->execute(sprintf('composer update -n --no-dev -d %s', $this->dir->getRoot()));
	}

	/**
	 * @return array
	 */
	private function getLocales() {
		$locale = [];

		$stores = $this->storeManager->getStores($withDefault = false);

		foreach($stores as $store) {
			$locale[] = $this->helper->getMagentoConfig('general/locale/code', null,\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getStoreId());
		}

		return $locale;
	}
}
