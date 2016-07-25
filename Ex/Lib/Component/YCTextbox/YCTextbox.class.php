<?php
class YCTextbox extends YCBase
{
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array(
			'text'			=> '',
			'popup_type'	=> 'tree',
	);
	/**
	 * 构造方法
	 * @param unknown $attrs
	 * @param string $tagName
	 */
	public function __construct($attrs = array(), $tagName = null)
	{
		parent::__construct($attrs,$tagName);
		$this->excludeAttrs = array_merge($this->excludeAttrs,array(
			'text','val','popup_url','popup_title','popup_type','mode','elem'
		));
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		if(null!==$this->text_field && null!==$this->dataset)
		{
			$this->getTextFromDataset();
		}
		$this->value = $this->text;
		$this->popup_type = $this->popup_type;
		parent::prepareView();
	}
	private function getTextFromDataset()
	{
		if(isset($this->text_field) && is_array($this->dataset) && count($this->dataset)>0)
		{
			if(isset($this->dataset[$this->text_field]))
			{
				$this->text = $this->dataset[$this->text_field];
			}
			else if(isset($this->dataset[0][$this->text_field]))
			{
				$this->text = $this->dataset[0][$this->text_field];
			}
		}
	}
}