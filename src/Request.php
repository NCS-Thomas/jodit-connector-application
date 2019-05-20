<?php
/**
 * @package    jodit
 *
 * @author     Valeriy Chupurnov <chupurnov@gmail.com>
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       https://xdsoft.net/jodit/
 */

namespace Jodit;

use Riimu\Kit\PathJoin\Path;

/**
 * Class Request
 * @package jodit
 * @property string $action
 * @property string $source
 * @property string $name
 * @property string $newname
 * @property string $path
 * @property string $url
 * @property array $box
 */
class Request
{
	private $_raw_data = [];

	public function __construct()
    {
		$data = file_get_contents('php://input');
		if ($data) {
			switch ($_SERVER["CONTENT_TYPE"]) {
				case 'application/json':
					$this->_raw_data =  json_decode($data, true);
					break;
				default:
					parse_str($data, $this->_raw_data);

			}
		}

		$this->sanitize();
	}

	public function get($key, $default_value = null)
    {
		if (isset($_REQUEST[$key])) {
			return $_REQUEST[$key];
		}
		if (isset($this->_raw_data[$key])) {
			return $this->_raw_data[$key];
		}
		return $default_value;
	}

	public function __get($key)
    {
		return $this->get($key);
	}

	public function post($keys, $default_value = null)
    {
		$keys_chain = explode('/', $keys);
		$result = $_POST;

		foreach ($keys_chain as $key) {
			if ($key and isset($result[$key])) {
				$result = $result[$key];
			} else {
				$result = $default_value;
				break;
			}
		}

		return $result;
	}

	private function sanitize(): void
    {
        if (isset($_REQUEST['path'])) {
            $_REQUEST['path'] = Path::normalize($_REQUEST['path']);
        }

        if (isset($this->_raw_data['path'])) {
            $this->_raw_data['path'] = Path::normalize($this->_raw_data['path']);
        }

    }
}