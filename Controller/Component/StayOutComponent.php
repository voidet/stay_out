<?php
/**
 * StayOut Component - A component to log out all sessions
 *
 * http://www.jotlab.com
 * http://www.github/voidet/stay_out
 *
 **/

class StayOutComponent extends Component {

/**
 * Include the neccessary components for StayOut to function with
 */
	public $components = array('Auth', 'Cookie', 'Session');

/**
	* initialize Called beforeFilter
	* @param array $settings overrides default settings for fieldnames
	* @return false
*/
	function initialize($Controller, $settings = array()) {
		$defaults = array(
			'logout_field' => 'logged_out',
			'cache' => false,
		);
		$this->Controller = $Controller;
		$this->settings = array_merge($defaults, $settings);
	}

/**
	* generateHash is a simple uuid to SHA1 with salt handler
	* @return string(40)
	*/
	public function generateHash() {
		return Security::hash(String::uuid(), null, true);
	}

/**
	* initializeModel method loads the required model if not previously loaded
	* @return false
	*/
	private function __initializeModel() {
		if (!isset($this->userModel)) {
			$userModel = '';
			foreach ($this->Auth->authenticate as $adapter) {
				if (is_array($adapter) && !empty($adapter['userModel'])) {
					$userModel = $adapter['userModel'];
					break;
				}
			}

			if (empty($userModel)) {
				die('Please specify what user model to authenticate against');
			}

			App::import('Model', $userModel);
			$this->userModel = new $userModel;
		}
	}

/**
	* startup Called after beforeFilter
	* @return false
	*/
	function startup() {
		$this->__initializeModel();
		if ($this->tableSupports('logout_field') && $this->Auth->user()) {

			if (!empty($this->Controller->data[$this->Auth->userModel]) && !$this->Auth->user($this->settings['logout_field'])) {
				Cache::delete('StayOutUser-'.$this->Auth->user($this->userModel->primaryKey), 'StayOutCache');
				$uuidhash = $this->generateHash();
				$this->userModel->id = $this->Auth->user($this->userModel->primaryKey);
				$this->userModel->saveField($this->settings['logout_field'], $uuidhash);
				$this->Session->write($this->Auth->sessionKey.'.'.$this->settings['logout_field'], $uuidhash);
			}

			if ($this->Auth->user()) {
				//Rewrite session with Auth incase session is lost and an Auto Login script starts up
				if ($this->Auth->user($this->settings['logout_field'])) {
					$this->Session->write($this->Auth->sessionKey.'.'.$this->settings['logout_field'], $this->Auth->user($this->settings['logout_field']));
				}

				if ($this->settings['cache'] === true) {
					$loggedOut = Cache::read('StayOutUser-'.$this->Auth->user($this->userModel->primaryKey), 'StayOutCache');
				}

				if (empty($loggedOut)) {
					$loggedOut = $this->userModel->find('first', array(
						'fields' => array($this->userModel->primaryKey),
						'conditions' => array(
							$this->userModel->primaryKey => $this->Auth->user($this->userModel->primaryKey),
							$this->settings['logout_field'] => $this->Session->read($this->Auth->sessionKey.'.'.$this->settings['logout_field'])),
							'recursive' => -1
						)
					);

					if ($this->settings['cache'] === true) {
						Cache::write('StayOutUser-'.$this->Auth->user($this->userModel->primaryKey), $loggedOut, 'StayOutCache');
					}
				}

				if (empty($loggedOut)) {
					$this->logout();
				}
			}

		}
	}

/**
	* tableSupports checks to see whether or not the current setup supports tracking logouts
	* @param type specifies which field & setting is functional
	* @return bool
	*/
	protected function tableSupports($type = '') {
		if (@$this->userModel->schema($this->settings[$type]) && !empty($this->settings[$type])) {
			return true;
		}
	}

/**
	* setLogout updates the database to store the last log out time
	* @return false
	*/
	public function setLogout() {
		if ($this->Auth->user()) {
			Cache::delete('StayOutUser-'.$this->Auth->user($this->userModel->primaryKey), 'StayOutCache');
			$this->userModel->id = $this->Auth->user($this->userModel->primaryKey);
			$this->userModel->saveField($this->settings['logout_field'], null);
		}
	}

/**
	* logout clears user Cookie, Session
	* @return false
	*/
	public function logout($user = array()) {
		$this->Cookie->destroy();
		$this->Session->destroy();
		$this->Controller->redirect($this->Auth->logout());
	}

}

?>