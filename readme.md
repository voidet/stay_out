#CakePHP StayOut Component

This component allows all user sessions established to be flushed if logged out. This is helpful when a user may log in across multiple browsers and computers and would want to clear all sessions across each machine. This heightens security in that once logged out, every instance is logged out.

##TODO
This basic project is in its infancy, so tests need to be written and code optimised.

##Installation
Install the plugin:

	cd myapp
	git clone git://github.com/voidet/stay_out.git stay_out

Depending on which user controller you would like the StayOut functions to work on, open up the controller and type in.

	var $components = array('StayOut.StayOut');

The only method that must be called manually is to set the user log out information before you log the user out. For example:

	function logout() {
		$this->StayOut->setLogout();
		$this->StayOut->logout(); // or $this->Auth->logout();
	}