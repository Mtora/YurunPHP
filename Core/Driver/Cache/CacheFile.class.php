<?php
/**
 * 文件缓存驱动
 * @author Yurun <admin@yurunsoft.com>
 */
class CacheFile extends CacheBase
{
	// 缓存路径
	protected $path;
	// 缓存文件扩展名
	protected $ext;
	
	/**
	 * 构造方法
	 */
	public function __construct($config = null)
	{
		parent::__construct();
		// 缓存设置
		if (empty($config))
		{
			$this->path = APP_CACHE;
			$this->ext = Config::get('@.CACHE_EXT');
		}
		else
		{
			$this->path = $config['CACHE_PATH'];
			$this->ext = $config['CACHE_EXT'];
		}
	}
	
	/**
	 * 设置缓存
	 *
	 * @param string $alias        	
	 * @param mixed $value        	
	 * @param array $config        	
	 */
	public function set($alias, $value, $config = array())
	{
		// 打开或创建缓存文件
		$fp = fopen($this->getFileName($alias), 'w');
		if (false === $fp)
		{
			return false;
		}
		else
		{
			// 写锁
			if (flock($fp, LOCK_EX))
			{
				if(isset($config['raw']) && $config['raw'])
				{
					fwrite($fp, $value);
				}
				else 
				{
					// 防止泄露数据
					fwrite($fp, '<?php exit;?>');
					// 写入有效期
					fwrite($fp, sprintf('%012d', isset($config['expire']) ? $config['expire'] : 0));
					// 写入序列化后的值
					fwrite($fp, serialize($value));
				}
				$result = true;
			}
			else
			{
				$result = false;
			}
			// 关闭文件
			fclose($fp);
			return $result;
		}
	}
	
	/**
	 * 获取缓存
	 *
	 * @abstract
	 *
	 */
	public function get($alias, $default = false, $config = array())
	{
		$fileName = $this->getFileName($alias);
		if (! is_file($fileName))
		{
			return $this->parseDefault($default);
		}
		// 打开或创建缓存文件
		$fp = fopen($fileName, 'r');
		if (false === $fp)
		{
			return $this->parseDefault($default);
		}
		else
		{
			if (flock($fp, LOCK_SH))
			{
				$data = '';
				while (! feof($fp))
				{
					$data .= fread($fp, 4096);
				}
				// 关闭文件
				fclose($fp);
				// 获取缓存有效时间
				$expire = (int)substr($data, 13, 12);
				$isRaw = isset($config['raw']) && $config['raw'];
				// 缓存文件最后修改时间和有效时间判定
				if (!$isRaw && $this->isExpired1(filemtime($fileName), $expire))
				{
					// 过期删除
					unlink($fileName);
					return $this->parseDefault($default);
				}
				else if($isRaw)
				{
					// 返回源数据
					return $data;
				}
				else
				{
					// 返回源数据
					return unserialize(substr($data, 25));
				}
			}
			else
			{
				// 关闭文件
				fclose($fp);
				return $this->parseDefault($default);
			}
		}
	}
	private function parseDefault($default)
	{
		if(is_callable($default))
		{
			return $default();
		}
		else
		{
			return $default;
		}
	}
	/**
	 * 删除缓存
	 *
	 * @abstract
	 *
	 */
	public function remove($alias, $config = array())
	{
		unlink($this->getFileName($alias));
	}
	
	/**
	 * 清除所有缓存文件
	 */
	public function clear()
	{
		enumFiles($this->path, 'unlink');
	}
	
	/**
	 * 缓存文件是否存在
	 *
	 * @param string $alias        	
	 * @return boolean
	 */
	public function exists($alias, $config = array())
	{
		return is_file($this->getFileName($alias));
	}
	
	/**
	 * 获取缓存文件名
	 *
	 * @param string $alias        	
	 * @return string
	 */
	public function getFileName($alias)
	{
		$n = dirname($alias);
		$path = $this->path;
		if ('.' !== $n)
		{
			$path .= $n . '/';
		}
		if (! is_dir($path))
		{
			mkdir($path, 0755, true);
		}
		return $path . md5(basename($alias)) . $this->ext;
	}
}