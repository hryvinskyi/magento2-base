<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Script\Base\Model\Config\Source;

class Themes implements \Magento\Framework\Option\ArrayInterface
{
	/**
	 * Labels collection array
	 *
	 * @var array
	 */
	protected $_themeList;

	/**
	 * @var array
	 */
	private $_options;
	/**
	 * Constructor
	 *
	 * @param \Magento\Framework\View\Design\Theme\ThemeList $themeList
	 */
	public function __construct(\Magento\Framework\View\Design\Theme\ThemeList $themeList)
	{
		$this->_themeList = $themeList;
	}

	/**
	 * {@inheritdoc}
	 */
	public function toOptionArray()
	{
		foreach( $this->_themeList->loadData() as $load_datum ) {
			$this->_options[] = [
				'label' => $load_datum->getData('theme_title'),
				'value' => $load_datum->getData('code'),
			];
		}
		return $this->_options;
	}
}
